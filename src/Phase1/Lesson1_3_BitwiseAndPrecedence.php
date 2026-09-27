<?php

declare(strict_types=1);

/**
 * Lesson 1.3: ビット演算・論理演算子の短絡評価と優先順位の罠
 * 
 * 実行方法:
 *   make run FILE=src/Phase1/Lesson1_3_BitwiseAndPrecedence.php
 */

echo "=== 1. 代入演算子 (=) と論理演算子 (&& vs and) の優先順位 ===" . PHP_EOL;

$x = true && false; // ($x = (true && false)) => false
$y = true and false; // (($y = true) and false)  => true

printf("\$x = true && false => %s (&& が = より優先)\n", $x ? 'true' : 'false');
printf("\$y = true and false => %s (= が and より優先！)\n", $y ? 'true' : 'false');

echo PHP_EOL . "=== 2. 文字列連結 (.) と算術加算 (+) の優先順位 ===" . PHP_EOL;

$result = "Sum: " . 3 + 5;
// PHP 8: "Sum: " . (3 + 5) => "Sum: 8"
// PHP 7: ("Sum: " . 3) + 5 => "Sum: 3" + 5 => 0 + 5 => 5 (Notice発生)
printf('"Sum: " . 3 + 5 => "%s" (PHP 8 では + が . より高優先度)' . PHP_EOL, $result);

echo PHP_EOL . "=== 3. ビット演算子 (&) と厳密比較 (===) の結合の罠 ===" . PHP_EOL;

$flags = 0b0101; // 5
$mask  = 0b0001; // 1

// 括弧なし: $flags & ($mask === $mask) => 5 & true => 1 (int型！)
$trapResult = $flags & $mask === $mask;
// 括弧あり: ($flags & $mask) === $mask => 1 === 1 => true (bool型)
$safeResult = ($flags & $mask) === $mask;

printf("括弧なし: \$flags & \$mask === \$mask => %s (型: %s)\n", var_export($trapResult, true), gettype($trapResult));
printf("括弧あり: (\$flags & \$mask) === \$mask => %s (型: %s)\n", var_export($safeResult, true), gettype($safeResult));

echo PHP_EOL . "=== 4. 短絡評価 (Short-circuit Evaluation) と副作用 ===" . PHP_EOL;

$executed = false;
$trigger = function () use (&$executed): bool {
    $executed = true;
    return true;
};

$status = true || $trigger();
printf("true || \$trigger() => 評価結果: %s / 関数の実行フラグ: %s (短絡により未実行)\n",
    $status ? 'true' : 'false',
    $executed ? 'true' : 'false'
);

echo PHP_EOL . "=== 5. 実務応用：ビットマスクによる権限管理 ===" . PHP_EOL;

const PERM_READ    = 1 << 0; // 0b001 = 1
const PERM_WRITE   = 1 << 1; // 0b010 = 2
const PERM_EXECUTE = 1 << 2; // 0b100 = 4

$userPerms = PERM_READ | PERM_EXECUTE; // 0b101 = 5

$hasPermission = fn (int $user, int $perm): bool => ($user & $perm) === $perm;

printf("User Permissions: READ | EXECUTE (bit: %03b = %d)\n", $userPerms, $userPerms);
printf(" - Has READ?    => %s\n", $hasPermission($userPerms, PERM_READ) ? 'YES' : 'NO');
printf(" - Has WRITE?   => %s\n", $hasPermission($userPerms, PERM_WRITE) ? 'YES' : 'NO');
printf(" - Has EXECUTE? => %s\n", $hasPermission($userPerms, PERM_EXECUTE) ? 'YES' : 'NO');
