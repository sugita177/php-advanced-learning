<?php

declare(strict_types=1);

/**
 * Lesson 2.2: 参照渡し (&) が COW に与える影響と参照の分離・foreach の罠
 * 
 * 実行方法:
 *   make run FILE=src/Phase2/Lesson2_2_ReferencesAndCow.php
 */

echo "=== 1. 参照代入 (\$b = &\$a) とメモリ挙動 ===" . PHP_EOL;

$a = range(1, 100000);
$b = &$a;

$beforeWrite = memory_get_usage();
$b[0] = 999;
$diff = memory_get_usage() - $beforeWrite;

printf("参照代入後の \$b[0] = 999 書き換え時のメモリ増加: %d bytes (複製なし)\n", $diff);
printf("参照連動: \$a[0] = %d / \$b[0] = %d\n", $a[0], $b[0]);

echo PHP_EOL . "=== 2. 通常代入と参照の混在による「参照の分離」 ===" . PHP_EOL;

$c = $a; // 通常代入 ($a と $b は参照関係、$c は通常コピー)
$beforeCWrite = memory_get_usage();
$c[0] = 777;
$cDiff = memory_get_usage() - $beforeCWrite;

printf("\$c[0] = 777 書き換え時のメモリ増加: %d bytes (ここで COW 複製が発生)\n", $cDiff);
printf("独立性: \$a[0] = %d / \$b[0] = %d / \$c[0] = %d\n", $a[0], $b[0], $c[0]);

echo PHP_EOL . "=== 3. 恐怖の foreach 参照スコープ漏れバグ ===" . PHP_EOL;

$numbers = [1, 2, 3];

echo "初期状態: " . json_encode($numbers) . PHP_EOL;

// 1回目の参照ループ
foreach ($numbers as &$val) {
    $val *= 2;
}

echo "1回目終了直後 (期待値 [2, 4, 6]): " . json_encode($numbers) . PHP_EOL;
echo "※ この時点で \$val は \$numbers[2] への参照を保持したまま生存している！" . PHP_EOL;

// 2回目の通常ループ
foreach ($numbers as $val) {
    // no-op
}

echo "2回目終了後: " . json_encode($numbers) . " (最後の要素が [2, 4, 4] に破壊された！)" . PHP_EOL;

echo PHP_EOL . "=== 4. unset() による安全な参照解除 ===" . PHP_EOL;

$safeNumbers = [1, 2, 3];
foreach ($safeNumbers as &$v) {
    $v *= 2;
}
unset($v); // 参照を明示的に解除！

foreach ($safeNumbers as $v) {
    // no-op
}

echo "unset() 後: " . json_encode($safeNumbers) . " (正常に [2, 4, 6] を維持)" . PHP_EOL;
