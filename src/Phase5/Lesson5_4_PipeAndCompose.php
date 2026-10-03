<?php

declare(strict_types=1);

/**
 * Lesson 5.4: パイプライン演算子代替の関数合成 (compose, pipe) と PHP 8.5 ネイティブ |>
 * 
 * 実行方法:
 *   make run FILE=src/Phase5/Lesson5_4_PipeAndCompose.php
 */

echo "=== 1. PHP 8.5 ネイティブパイプ演算子 (|>) ===" . PHP_EOL;

$rawInput = "  modern php 8.5 functional architecture  ";

// 値を次々と変換関数へ流し込む
$pipedOutput = $rawInput
    |> trim(...)
    |> ucwords(...)
    |> (fn(string $s) => str_replace(' ', '', $s)) // PascalCase 化
    |> (fn(string $s) => "Vendor\\{$s}");

printf("元の値: '%s'\n", $rawInput);
printf("変換後: '%s'\n", $pipedOutput);

echo PHP_EOL . "=== 2. 汎用 pipe() による変換レシピの部品化 (Point-free スタイル) ===" . PHP_EOL;

function pipe(callable ...$functions): Closure
{
    return function (mixed $initialValue) use ($functions): mixed {
        return array_reduce(
            $functions,
            fn(mixed $carry, callable $fn) => $fn($carry),
            $initialValue
        );
    };
}

// ユーザー入力用の再利用可能なサニタイザー関数を定義
$sanitizeUsername = pipe(
    trim(...),
    strtolower(...),
    fn(string $s) => preg_replace('/[^a-z0-9_]/', '', $s)
);

$dirtyUsers = ["  Alice_01!  ", "  BOB-SMITH# ", "Charlie @Home  "];
$cleanedUsers = array_map($sanitizeUsername, $dirtyUsers);

printf("クリーンアップ結果: %s\n", implode(', ', $cleanedUsers));

echo PHP_EOL . "=== 3. 汎用 compose() による数学的関数合成 (右から左) ===" . PHP_EOL;

function compose(callable ...$functions): Closure
{
    return pipe(...array_reverse($functions));
}

$double = fn(int $n): int => $n * 2;
$add3 = fn(int $n): int => $n + 3;

// pipe: (5 * 2) + 3 = 13 (左から右)
$pipeResult = pipe($double, $add3)(5);
// compose: (5 + 3) * 2 = 16 (右から左)
$composeResult = compose($double, $add3)(5);

printf("pipe(double, add3)(5)    = %d ((5 * 2) + 3)\n", $pipeResult);
printf("compose(double, add3)(5) = %d ((5 + 3) * 2)\n", $composeResult);

echo PHP_EOL . "=== 4. 部分適用 (partial) とパイプラインの最強連携 ===" . PHP_EOL;

function partial(callable $fn, mixed ...$fixedArgs): Closure
{
    return fn(mixed ...$remainingArgs) => $fn(...$fixedArgs, ...$remainingArgs);
}

// 3引数を取る str_replace を部分適用で「1引数の置換関数」に仕立てて pipe に接続！
$hyphenate = partial(str_replace(...), ' ', '-');
$slugify = pipe(
    trim(...),
    strtolower(...),
    $hyphenate,
    fn(string $s) => urlencode($s)
);

printf("記事タイトルから Slug 生成: %s\n", $slugify("  Modern PHP 8.5 Released Today!  "));
