<?php

declare(strict_types=1);

readonly class RoUser
{
    public function __construct(public string $name) {}
}

test('readonly class is immutable', function () {
    $user = new RoUser('Alice');
    expect($user->name)->toBe('Alice');
    expect(fn() => $user->name = 'Bob')->toThrow(Error::class);
    $refProp = new ReflectionProperty(RoUser::class, 'name');
    expect($refProp->isReadOnly())->toBeTrue();
    expect(fn() => $refProp->setValue($user, 'Bob'))->toThrow(Error::class);
});

class RoAddress
{
    public function __construct(public string $city) {}
}

readonly class RoCompany
{
    public function __construct(public RoAddress $address) {}
}

test('readonly class and shallow immutability', function () {
    $company = new RoCompany(new RoAddress('Tokyo'));
    expect($company->address->city)->toBe('Tokyo');
    expect(fn() => $company->address = new RoAddress('Osaka'))->toThrow(Error::class);
    $company->address->city = 'Aichi';
    expect($company->address->city)->toBe('Aichi');
});

test('readonly class and reference', function () {
    $user = new RoUser('Alice');
    expect(fn() => $ref = &$user->name)->toThrow(Error::class);
});

class RoDraftPost
{
    public readonly string $title;
}

test('uninitialized readonly property throws Error on access, but isset returns false', function () {
    $draft = new RoDraftPost();

    // 未初期化アクセスは Fatal Error
    expect(fn() => $draft->title)->toThrow(Error::class);

    // しかし isset() は安全に false を返す
    expect(isset($draft->title))->toBeFalse();    
});

readonly class RoWithersUser
{
    public function __construct(
        public string $name,
        public int $age,
    ) {}

    public function __clone()
    {
        unset($this->age);
    }

    // PHP 8.3 の Withers パターン
    public function withAge(int $newAge): self
    {
        $clone = clone $this;
        $clone->age = $newAge; // __clone や clone スコープでの再初期化
        return $clone;
    }
}

test('PHP 8.3 allows modifying readonly property during clone', function () {
    $user1 = new RoWithersUser('Alice', 25);
    $user2 = $user1->withAge(26);

    // 元のインスタンスは不変
    expect($user1->age)->toBe(25);
    // 新しいインスタンスは更新されている
    expect($user2->age)->toBe(26);
    expect($user1)->not->toBe($user2);
});
    