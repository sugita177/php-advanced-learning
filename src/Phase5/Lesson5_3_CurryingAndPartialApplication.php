<?php

declare(strict_types=1);

/**
 * Lesson 5.3: カリー化 (Currying) と部分適用 (Partial Application) の実装
 * 
 * 実行方法:
 *   make run FILE=src/Phase5/Lesson5_3_CurryingAndPartialApplication.php
 */

echo "=== 1. 手動カリー化 (アロー関数の数珠つなぎ) ===" . PHP_EOL;

$manualCurryAdd = fn(int $x) => fn(int $y) => fn(int $z) => $x + $y + $z;

printf("1引数ずつのチェーン実行: 1 + 2 + 3 = %d\n", $manualCurryAdd(1)(2)(3));

$add10 = $manualCurryAdd(10);       // 10 を固定した新しい関数 (fn($y) => fn($z) => ...)
$add15 = $add10(5);                // 10 + 5 を固定した新しい関数 (fn($z) => ...)
printf("段階的評価 (10 + 5 + 7): %d\n", $add15(7));

echo PHP_EOL . "=== 2. 汎用部分適用関数 partial() ===" . PHP_EOL;

function partial(callable $fn, mixed ...$fixedArgs): Closure
{
    return fn(mixed ...$remainingArgs) => $fn(...$fixedArgs, ...$remainingArgs);
}

// 汎用ロガー関数
$logger = function (string $level, string $module, string $message): void {
    printf("[%s] [%s] %s\n", strtoupper($level), $module, $message);
};

// 'ERROR', 'AUTH' をあらかじめ固定した専用ロガーを作成
$authErrorLogger = partial($logger, 'error', 'AUTH');

$authErrorLogger('ユーザー認証に失敗しました (IP: 192.168.1.1)');
$authErrorLogger('トークンの有効期限が切れています');

echo PHP_EOL . "=== 3. 汎用カリー化関数 curry() (Reflection と自己再帰クロージャ) ===" . PHP_EOL;

function curry(callable $fn): Closure
{
    $ref = new ReflectionFunction(Closure::fromCallable($fn));
    $requiredCount = $ref->getNumberOfRequiredParameters();

    // 外側の工場関数: 過去の引数リュック ($accumulated) を受け取って、内側の作業員関数を返す
    $currier = function (array $accumulated) use (&$currier, $fn, $requiredCount): mixed {
        // 内側の作業員関数: 呼び出し時の新しい引数 (...$newArgs) を手渡しされる
        return function (mixed ...$newArgs) use ($accumulated, $currier, $fn, $requiredCount): mixed {
            $allArgs = array_merge($accumulated, $newArgs);
            if (count($allArgs) >= $requiredCount) {
                // 必要個数に達したら元の関数を実行して結果を返す
                return $fn(...$allArgs);
            }
            // まだ足りない場合は、引数を増やした新しいクロージャを返して待機
            return $currier($allArgs);
        };
    };

    return $currier([]);
}

$buildUrl = function (string $protocol, string $domain, string $path): string {
    return "{$protocol}://{$domain}/{$path}";
};

$curriedUrl = curry($buildUrl);

printf("1個ずつ渡す : %s\n", $curriedUrl('https')('example.com')('api/users'));
printf("2個+1個で渡す: %s\n", $curriedUrl('https', 'example.com')('api/posts'));
printf("3個一気に渡す: %s\n", $curriedUrl('https', 'example.com', 'api/comments'));

// 実務応用: HTTPS 専用のベース URL ジェネレータを作成
$httpsGenerator = $curriedUrl('https');
$siteUrlGenerator = $httpsGenerator('my-service.com');
printf("設定固定後の URL 生成: %s\n", $siteUrlGenerator('dashboard'));
