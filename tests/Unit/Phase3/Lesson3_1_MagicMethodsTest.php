<?php

declare(strict_types=1);

class User {
    public string $paulicName = 'Alice';
    private string $secretKey = 'secret_123';
    public array $data = [];

    public function __get(string $name): mixed
    {
        return "Magic get: {$name}";
    }

    public function __set(string $name, mixed $value): void
    {
        $this->data[$name] = $value;
    }

    public function getInternalSecret(): string {
        return $this->secretKey;
    }
}

class UserWithIsset extends User {
    public function __isset(string $name): bool {
        return isset($this->data[$name]);
    }
}
    

test('magic method invocation timing', function () {
    $user = new User();

    // publicプロパティの直接参照は発火しない
    expect($user->paulicName)->toBe('Alice');

    // privateプロパティへのアクセスは__getが発火する
    expect($user->secretKey)->toBe('Magic get: secretKey');

    // getInernalSecret()はクラス内部でprivateプロパティにアクセスするので、__getは発火しない
    expect($user->getInternalSecret())->toBe('secret_123');

    // 未定義のプロパティへの書き込みは__setが発火する
    $user->dynamicAge = 30;
    expect($user->data['dynamicAge'])->toBe(30);

    // __issetが実装されていないのでfalseを返す
    expect(isset($user->dynamicAge))->toBe(false);

    // __issetが実装されているクラスでの確認
    $userWithIsset = new UserWithIsset();
    $userWithIsset->dynamicAge = 30;
    expect(isset($userWithIsset->dynamicAge))->toBe(true);
});

test('magic method bench mark', function () {
    $user = new User();

    $t1_start = hrtime(true);
    for ($i = 0; $i < 100000; $i++) {
        $x = $user->paulicName;
    }
    $t1_time = hrtime(true) - $t1_start;
    
    $t2_start = hrtime(true);
    for ($i = 0; $i < 100000; $i++) {
        $x = $user->secretKey;
    }
    $t2_time = hrtime(true) - $t2_start;

    echo "t1_time: $t1_time".PHP_EOL;
    echo "t2_time: $t2_time".PHP_EOL;
    expect($t2_time)->toBeGreaterThan($t1_time * 2);
});