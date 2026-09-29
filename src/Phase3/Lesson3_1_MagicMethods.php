<?php

declare(strict_types=1);

/**
 * Lesson 3.1: マジックメソッドの発火境界とオーバーヘッド
 * 
 * 実行方法:
 *   make run FILE=src/Phase3/Lesson3_1_MagicMethods.php
 */

class DemoUser
{
    public string $publicName = 'Alice';
    private string $secretKey = 'secret_123';
    /** @var array<string, mixed> */
    public array $data = [];

    public function __get(string $name): mixed
    {
        return "Magic get: {$name}";
    }

    public function __set(string $name, mixed $value): void
    {
        $this->data[$name] = $value;
    }

    public function __isset(string $name): bool
    {
        return isset($this->data[$name]);
    }

    public function getInternalSecret(): string
    {
        return $this->secretKey; // 内部からは直接アクセス（__get は発火しない）
    }
}

echo "=== 1. マジックメソッドの発火境界検証 ===" . PHP_EOL;

$user = new DemoUser();

// 1. public プロパティ
printf("1. public プロパティへのアクセス:      \$user->publicName => %s (直接アクセス)\n", $user->publicName);

// 2. private プロパティ（外部から）
printf("2. private プロパティへの外部アクセス:  \$user->secretKey  => %s (アクセス不能のため __get 発火！)\n", $user->secretKey);

// 3. private プロパティ（内部から）
printf("3. private プロパティへの内部アクセス:  getInternalSecret => %s (アクセス可能なため直接取得)\n", $user->getInternalSecret());

// 4. 未定義プロパティと __set / __isset
$user->dynamicAge = 30;
printf("4. 未定義プロパティ代入後の isset:      isset(\$user->dynamicAge) => %s (__isset 実装により true)\n", isset($user->dynamicAge) ? 'true' : 'false');

echo PHP_EOL . "=== 2. 直接アクセス vs マジックメソッドの速度比較 (100,000回ループ) ===" . PHP_EOL;

// 直接アクセス
$startT1 = hrtime(true);
for ($i = 0; $i < 100000; $i++) {
    $x = $user->publicName;
}
$timeT1 = hrtime(true) - $startT1;

// マジックメソッド経由
$startT2 = hrtime(true);
for ($i = 0; $i < 100000; $i++) {
    $x = $user->secretKey;
}
$timeT2 = hrtime(true) - $startT2;

printf("直接アクセス時間 (t1):        %' 10d ns (%6.2f ms)\n", $timeT1, $timeT1 / 1_000_000);
printf("マジックメソッド時間 (t2):    %' 10d ns (%6.2f ms)\n", $timeT2, $timeT2 / 1_000_000);
printf("速度差: マジックメソッドは直接アクセスの約 %.1f 倍の時間がかかります！\n", $timeT2 / $timeT1);
