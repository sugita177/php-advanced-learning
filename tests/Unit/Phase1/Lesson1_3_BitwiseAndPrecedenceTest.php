<?php

test('Bitwise AND and logical AND precedence', function () {
    // A: 代入演算子 (=) と論理演算子 (&& vs and)
    $x = true && false;
    $y = true and false;
    expect($x)->toBeFalse();
    expect($y)->toBeTrue();

    // B: 文字列連結 (.) と加算 (+) の優先順位（PHP 8 の仕様）
    $result = "Sum: " . 3 + 5;
    expect($result)->toBe('Sum: 8');
    // php7では "Sum: 3" + 5 -> 0 + 5 -> 5 になっていた

    // C: ビット演算子 (&) と厳密比較 (===) の結合順序
    $flags = 0b0101; // 5
    $mask  = 0b0001; // 1
    $check = $flags & $mask === $mask;
    expect($check)->toBe(1);

    // D: 短絡評価（Short-circuit evaluation）と関数の副作用
    $executed = false;
    $trigger = function () use (&$executed): bool {
        $executed = true;
        return true;
    };

    $status = true || $trigger();
    expect($status)->toBeTrue();
    expect($executed)->toBeFalse();

});

test('bitwise permission check', function () {
    $PERM_READ = 1 << 0; // 1
    $PERM_WRITE = 1 << 1; // 2
    $PERM_EXECUTE = 1 << 2; // 4

    $user = $PERM_READ | $PERM_EXECUTE;

    $hasPermission = fn (int $user, int $perm): bool => ($user & $perm) === $perm;


    expect($hasPermission($user, $PERM_READ))->toBeTrue();
    expect($hasPermission($user, $PERM_WRITE))->toBeFalse();
    expect($hasPermission($user, $PERM_EXECUTE))->toBeTrue();
    
});