<?php

declare(strict_types=1);

/**
 * Lesson 2.1: 配列と変数の Copy on Write (COW) 挙動の検証
 * 
 * 実行方法:
 *   make run FILE=src/Phase2/Lesson2_1_CopyOnWrite.php
 */

function formatBytes(int $bytes): string
{
    return sprintf("%' 10d bytes (%6.2f MB)", $bytes, $bytes / (1024 * 1024));
}

echo "=== 1. 配列作成とメモリ消費 ===" . PHP_EOL;

$baseMemory = memory_get_usage();
$a = range(1, 100000);
$arraySize = memory_get_usage() - $baseMemory;

printf("100,000 要素の配列 \$a を作成: %s\n", formatBytes($arraySize));

echo PHP_EOL . "=== 2. 単純代入 (\$b = \$a) における COW 共有 ===" . PHP_EOL;

$beforeAssign = memory_get_usage();
$b = $a;
$assignDiff = memory_get_usage() - $beforeAssign;

printf("\$b = \$a 代入直後のメモリ増加:  %s (メモリ共有のためほぼ 0)\n", formatBytes($assignDiff));

echo PHP_EOL . "=== 3. 要素書き込み (\$b[0] = 999) によるコピー発生 ===" . PHP_EOL;

$beforeWrite = memory_get_usage();
$b[0] = 999;
$writeDiff = memory_get_usage() - $beforeWrite;

printf("\$b[0] = 999 書き換え時のメモリ増加: %s (ここで丸ごと複製！)\n", formatBytes($writeDiff));
printf("値の独立性: \$a[0] = %d / \$b[0] = %d\n", $a[0], $b[0]);

echo PHP_EOL . "=== 4. 関数渡し（値渡し）における COW ===" . PHP_EOL;

// 読み取り専用関数
$readOnly = fn (array $data): int => count($data);

$beforeCall = memory_get_usage();
$readOnly($a);
$callDiff = memory_get_usage() - $beforeCall;

printf("読み取り関数 inspectArray(\$a): %s (値渡しでも複製ゼロ)\n", formatBytes($callDiff));

// 関数内での書き換えとライフサイクル
$writeInside = function (array $data): int {
    $before = memory_get_usage();
    $data[0] = 11; // 関数内で COW 発生
    $diff = memory_get_usage() - $before;
    return $diff;
};

$insideDiff = $writeInside($a);
printf("関数内での書き換え (\$data[0] = 11): %s (関数内で一時的に複製)\n", formatBytes($insideDiff));

$afterFunctionExit = memory_get_usage();
printf("関数終了後のメモリ増加（外側で計測）: %s (スコープ脱出で即座に解放)\n", formatBytes($afterFunctionExit - $beforeCall));
