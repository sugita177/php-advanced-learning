<?php

test('references and copy on write', function () {
    $a = range(1, 100000);
    $b = &$a;
    $mBefore = memory_get_usage();
    $b[0] = 999;
    $mAfter = memory_get_usage();

    // 参照同士の書き換えではメモリ複製（2MB）は発生しない（1KB未満）
    expect($mAfter - $mBefore)->toBeLessThan(1024);
    expect($a[0])->toBe(999);
});

test('assigning a reference and modifying the new array', function () {
    $m1 = memory_get_usage();
    $a = range(1, 100000);
    $m2 = memory_get_usage();
    $arraySize = $m2 - $m1;
    echo "Original Array Size: " . $arraySize . " bytes\n";
    
    $b = &$a; // 参照渡し
    $c = $a; // 通常の代入
    $mBefore = memory_get_usage();
    $c[0] = 777;
    $mAfter = memory_get_usage();

    // 通常の代入での書き換えではメモリ複製（2MB）が発生する
    expect($mAfter - $mBefore)->toBeGreaterThan(0.8 * $arraySize);
    expect($a[0])->toBe(1);
    expect($b[0])->toBe(1);
    expect($c[0])->toBe(777);
});

test('the dangerous foreach reference trap', function () {
    $numbers = [1, 2, 3];

    // 1 回目のループ（参照渡しで 2 倍にする）
    foreach ($numbers as &$val) {
        $val = $val * 2;
    }

    // 2 回目のループ（何もしないで回すだけ）
    foreach ($numbers as $val) {
        // no-op
    }
    // foreachはスコープを作らないため、1回目のループを抜けた後も$valは$numbersの最後の要素を指したまま
    // 2回目のループでは$valは$numbersの最後の要素を参照した状態で各ループでvalにnumbersの各要素が逐次代入される
    expect($numbers)->toBe([2, 4, 4]);
});

test('foreach reference with unset', function () {
    $numbers2 = [4, 5, 6];
    foreach ($numbers2 as &$val2) {
        $val2 = $val2 * 2;
    }
    // unsetで参照を解除する
    unset($val2);
    foreach ($numbers2 as $val2) {
        // no-op
    }
    expect($numbers2)->toBe([8, 10, 12]);
});
