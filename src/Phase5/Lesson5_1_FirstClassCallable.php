<?php

declare(strict_types=1);

/**
 * Lesson 5.1: 第一級 Callable 構文 (callable(...)) とスコープ保持・早期エラー検知
 * 
 * 実行方法:
 *   make run FILE=src/Phase5/Lesson5_1_FirstClassCallable.php
 */

echo "=== 1. 第一級 Callable の具象型と組み込み関数のオブジェクト化 ===" . PHP_EOL;

$fnStrlen = strlen(...);
printf("strlen(...) の型: %s\n", get_class($fnStrlen));
printf("実行結果 ('Modern PHP'): %d 文字\n", $fnStrlen('Modern PHP'));

// 高階関数 (array_map) への受け渡し
$languages = ['php', 'rust', 'typescript'];
$uppercased = array_map(strtoupper(...), $languages);
printf("大文字変換: %s\n", implode(', ', $uppercased));

echo PHP_EOL . "=== 2. 未定義関数の早期エラー検知 (実行時まで持ち越さない) ===" . PHP_EOL;

// 昔の文字列指定: 代入時点ではエラーにならず、呼び出し時までバグが潜伏する
$oldCallback = 'nonExistentFunction';
printf("文字列指定の代入: エラーなし (値: '%s')\n", $oldCallback);

// 第一級 Callable: 評価されたその瞬間に即座に Fatal Error となる
try {
    // @phpstan-ignore-next-line
    $newCallback = nonExistentFunction(...);
} catch (Error $e) {
    printf("[早期検知成功] 第一級 Callable 生成時エラー: %s\n", $e->getMessage());
}

echo PHP_EOL . "=== 3. プライベートメソッドのスコープ保持 (カプセル化された関数の返却) ===" . PHP_EOL;

class AuthGateway
{
    public function __construct(private string $secretSalt) {}

    private function generateHash(string $password): string
    {
        return hash('sha256', $password . $this->secretSalt);
    }

    // 内部の private メソッドを第一級 Callable として安全に公開
    public function getHasher(): Closure
    {
        return $this->generateHash(...);
    }
}

$gateway = new AuthGateway('sUpEr_sEcReT_kEy');

// 外部から直接 private メソッドを呼ぶと即死
try {
    // @phpstan-ignore-next-line
    $gateway->generateHash('mypassword');
} catch (Error $e) {
    printf("[ブロック成功] 外部からの private メソッド直接呼び出し: %s\n", $e->getMessage());
}

// しかし、getHasher() 経由の Closure は $this と private スコープを保持しているため実行可能！
$hasher = $gateway->getHasher();
$hash = $hasher('mypassword');
printf("Closure 経由のハッシュ生成成功: %s...\n", substr($hash, 0, 16));
