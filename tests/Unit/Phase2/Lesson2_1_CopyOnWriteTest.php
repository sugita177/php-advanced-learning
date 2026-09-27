<?php

test('array copy on write behavior', function () {
    // 1. 巨大配列作成
    $m1 = memory_get_usage();
    $a = range(1, 100000);
    $mAfterCreate = memory_get_usage();
    $arraySize = $mAfterCreate - $m1; // 配列が消費したメモリ
    echo "Original Array Size: " . $arraySize . " bytes\n";

    // 2. 単純代入 ($b = $a)
    $mBeforeAssign = memory_get_usage();
    $b = $a;
    $mAfterAssign = memory_get_usage();
    
    // 代入によるメモリ増加は、配列本体のサイズに比べて極めて小さい（ほぼゼロ）ことを検証
    expect($mAfterAssign - $mBeforeAssign)->toBeLessThan(1024); // 1KB未満
    echo "Assigned Array Size: " . ($mAfterAssign - $mBeforeAssign) . " bytes\n";

    // 3. 要素の書き込み ($b[0] = 999)
    $mBeforeWrite = memory_get_usage();
    $b[0] = 999;
    $mAfterWrite = memory_get_usage();

    // 書き込んだ瞬間に、配列ほぼ丸ごと1個分のメモリが新たに消費されることを検証
    expect($mAfterWrite - $mBeforeWrite)->toBeGreaterThan($arraySize * 0.8);
    echo "Written Array Size: " . ($mAfterWrite - $mBeforeWrite) . " bytes\n";

    // 4. 値の独立性（$a は書き換わっていない）
    expect($a[0])->toBe(1);
    expect($b[0])->toBe(999);

    // 5. 関数渡し（値渡し）によるメモリ使用量の検証
    $inspectArray = fn(array $array): int => count($array);
    $mBeforeReadCount = memory_get_usage();
    $inspectArray($a);
    $mAfterReadCount = memory_get_usage();
    expect($mAfterReadCount - $mBeforeReadCount)->toBeLessThan(1024);
    echo "Read Count Size: " . ($mAfterReadCount - $mBeforeReadCount) . " bytes\n";

    $changeArray = function (array $array): int {
        $array[0] = 11;
        return memory_get_usage();
    };
    $mBeforeClosureWrite = memory_get_usage();
    $mAfterClosureWriteInner = $changeArray($a);
    expect($mAfterClosureWriteInner - $mBeforeClosureWrite)->toBeGreaterThan($arraySize * 0.8);
    echo "Closure Write Size Inner: " . ($mAfterClosureWriteInner - $mBeforeClosureWrite) . " bytes\n";
    
    
    
});
