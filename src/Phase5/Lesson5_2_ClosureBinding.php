<?php

declare(strict_types=1);

/**
 * Lesson 5.2: Closure::bind / Closure::bindTo による動的スコープ操作
 * 
 * 実行方法:
 *   make run FILE=src/Phase5/Lesson5_2_ClosureBinding.php
 */

echo "=== 1. オブジェクトコンテキスト外での \$this 利用エラー ===" . PHP_EOL;

class BankAccount
{
    public function __construct(
        private string $accountNumber,
        private int $balance,
    ) {}
}

$account = new BankAccount('ACC-998877', 1_000_000);

// オブジェクトに束縛されていないグローバル関数
$probe = function () {
    // @phpstan-ignore-next-line
    return $this->balance;
};

try {
    $probe();
} catch (Error $e) {
    printf("[ブロック成功] コンテキスト外 \$this 呼び出し: %s\n", $e->getMessage());
}

echo PHP_EOL . "=== 2. bindTo() による \$this の束縛とスコープ権限の昇格 ===" . PHP_EOL;

// パターン A: $this だけバインド（スコープはグローバルのまま）
$boundWithoutScope = $probe->bindTo($account);
try {
    $boundWithoutScope();
} catch (Error $e) {
    printf("[ブロック成功] スコープなし（\$this のみ）での private アクセス: %s\n", $e->getMessage());
}

// パターン B: $this と同時にクラススコープも BankAccount に昇格！
$boundWithScope = $probe->bindTo($account, BankAccount::class);
$balance = $boundWithScope();
printf("スコープ昇格後の private プロパティ読み取り成功: 残高 = %s 円\n", number_format($balance));

echo PHP_EOL . "=== 3. PHP 7: Closure::call() による一括バインド即時実行 ===" . PHP_EOL;

// クロージャを定義してその場で対象オブジェクトの内部として実行
$injector = function (int $bonus) {
    $this->balance += $bonus;
    return $this->balance;
};

$newBalance = $injector->call($account, 500_000);
printf("Closure::call() による private プロパティ書き換え成功: 新残高 = %s 円\n", number_format($newBalance));

// 読み取りもワンライナーで実行可能
$currentBalance = (function () {
    return $this->balance;
})->call($account);
printf("インライン Closure::call() による検証: 残高 = %s 円\n", number_format($currentBalance));
