<?php

declare(strict_types=1);

/**
 * Lesson 3.3: readonly クラス / プロパティの不変性とリフレクションによる破壊検証
 * 
 * 実行方法:
 *   make run FILE=src/Phase3/Lesson3_3_ReadonlyAndReflection.php
 */

echo "=== 1. readonly class の基本不変性 ===" . PHP_EOL;

readonly class UserProfile
{
    public function __construct(
        public string $name,
        public int $age,
    ) {}
}

$user = new UserProfile('Alice', 25);
printf("ユーザー作成: %s (%d歳)\n", $user->name, $user->age);

// 再代入の試行
try {
    // @phpstan-ignore-next-line
    $user->name = 'Bob';
} catch (Error $e) {
    printf("[ブロック成功] 直接再代入エラー: %s\n", $e->getMessage());
}

// Reflection による強制書き換えの試行
try {
    $refProp = new ReflectionProperty(UserProfile::class, 'name');
    $refProp->setValue($user, 'Bob');
} catch (Error $e) {
    printf("[ブロック成功] Reflection 書き換えエラー: %s\n", $e->getMessage());
}

echo PHP_EOL . "=== 2. 浅い不変性 (Shallow Immutability) の挙動 ===" . PHP_EOL;

class MutableAddress
{
    public function __construct(public string $city) {}
}

readonly class Company
{
    public function __construct(public MutableAddress $address) {}
}

$company = new Company(new MutableAddress('Tokyo'));
printf("会社所在地: %s\n", $company->address->city);

// プロパティ自体の差し替えは不可
try {
    // @phpstan-ignore-next-line
    $company->address = new MutableAddress('Osaka');
} catch (Error $e) {
    printf("[ブロック成功] プロパティの再代入不可: %s\n", $e->getMessage());
}

// しかし内部オブジェクトのプロパティは変更できてしまう（Shallow）
$company->address->city = 'Nagoya';
printf("[要注意] 保持オブジェクトのプロパティ変更後: %s (readonly は参照の固定に過ぎない)\n", $company->address->city);

echo PHP_EOL . "=== 3. 未初期化状態 (Uninitialized) と isset() の仕様 ===" . PHP_EOL;

class DraftPost
{
    public readonly string $title;
}

$draft = new DraftPost();
printf("未初期化プロパティに対する isset(): %s (エラーにならず安全に判定)\n", isset($draft->title) ? 'true' : 'false');

try {
    echo $draft->title;
} catch (Error $e) {
    printf("[ブロック成功] 未初期化プロパティへの直接アクセス: %s\n", $e->getMessage());
}

echo PHP_EOL . "=== 4. PHP 8.3: __clone() による readonly の再初期化 (Withers パターン) ===" . PHP_EOL;

readonly class ImmutableMember
{
    public function __construct(
        public string $name,
        public int $age,
    ) {}

    public function __clone()
    {
        // PHP 8.3: clone 時に unset して未初期化に戻す
        unset($this->age);
    }

    public function withAge(int $newAge): self
    {
        $clone = clone $this;
        $clone->age = $newAge; // 1回だけ再代入可能
        return $clone;
    }
}

$m1 = new ImmutableMember('Alice', 20);
$m2 = $m1->withAge(21);

printf("元インスタンス m1: %s (%d歳)\n", $m1->name, $m1->age);
printf("新インスタンス m2: %s (%d歳)\n", $m2->name, $m2->age);
printf("別インスタンスの検証: %s\n", $m1 !== $m2 ? '完全分離成功' : '同一');
