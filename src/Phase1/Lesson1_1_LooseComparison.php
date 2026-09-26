<?php

declare(strict_types=1);

/**
 * Lesson 1.1: 緩やかな比較 (==) の PHP 8 における刷新と型ジャグリング境界
 * 
 * 実行方法:
 *   make run FILE=src/Phase1/Lesson1_1_LooseComparison.php
 */

echo "=== 1. PHP 8 における緩やかな比較 (==) の挙動 ===" . PHP_EOL;

$comparisons = [
    '0 == "0"'     => 0 == "0",       // true: 数値形式文字列
    '0 == "0.0"'   => 0 == "0.0",     // true: 数値形式文字列
    '0 == "foo"'   => 0 == "foo",     // false: "0" == "foo" の比較になる - (PHP 7: true - "foo"を数値化して0にするため)
    '0 == ""'      => 0 == "",        // false: "0" == "" の比較になる - (PHP 7: true -  ""を数値化して0にするため)
    '42 == " 42 "' => 42 == " 42 ",   // true: 前後空白は許容
    '"42" == "42.0"' => "42" == "42.0", // true: 両辺が数値文字列のため数値比較
];

foreach ($comparisons as $expr => $result) {
    printf("%-20s => %s\n", $expr, $result ? 'true' : 'false');
}

echo PHP_EOL . "=== 2. in_array() の型ジャグリング境界の罠 ===" . PHP_EOL;

$statuses = ['pending', 'approved', 'rejected'];
$userInputStatus = 0; // 未初期化や整数の 0

// 第3引数 $strict = false（デフォルト）
$isPendingLoose = in_array($userInputStatus, $statuses);
// 第3引数 $strict = true
$isPendingStrict = in_array($userInputStatus, $statuses, true);

echo "in_array(0, ['pending', ...], strict: false) => " . ($isPendingLoose ? 'MATCH (true)' : 'NO MATCH (false)') . PHP_EOL;
echo "in_array(0, ['pending', ...], strict: true)  => " . ($isPendingStrict ? 'MATCH (true)' : 'NO MATCH (false)') . PHP_EOL;

echo PHP_EOL . "※ PHP 7 では strict: false の場合、0 == 'pending' が 0 == 0 と評価され MATCH してしまっていた。" . PHP_EOL;
