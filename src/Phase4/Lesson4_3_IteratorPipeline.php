<?php

declare(strict_types=1);

/**
 * Lesson 4.3: IteratorIterator, FilterIterator, LimitIterator によるストリームパイプライン
 * 
 * 実行方法:
 *   make run FILE=src/Phase4/Lesson4_3_IteratorPipeline.php
 */

echo "=== 1. FilterIterator + LimitIterator によるパイプライン処理 ===" . PHP_EOL;

$numbers = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
$inner = new ArrayIterator($numbers);

class EvenFilter extends FilterIterator
{
    public int $evaluatedCount = 0;

    public function accept(): bool
    {
        $this->evaluatedCount++;
        $val = $this->current();
        $isEven = ($val % 2 === 0);
        printf(" [accept() 判定] 値: %d => %s\n", $val, $isEven ? '通過 (true)' : '除外 (false)');
        return $isEven;
    }
}

$evenFilter = new EvenFilter($inner);
// 偶数のうち、最初の 1 件をスキップして 2 件だけ取得する
$pipeline = new LimitIterator($evenFilter, offset: 1, limit: 2);

echo "--- パイプライン反復開始 ---" . PHP_EOL;
foreach ($pipeline as $val) {
    printf("★ 取得値: %d\n", $val);
}
echo "--- パイプライン反復終了 ---" . PHP_EOL;

printf("全10件中、accept() が呼ばれた回数: %d 回 (9, 10 は評価されずにスキップ)\n", $evenFilter->evaluatedCount);

echo PHP_EOL . "=== 2. InfiniteIterator (無限イテレータ) からの安全なストリーム切り出し ===" . PHP_EOL;

$colors = new ArrayIterator(['RED', 'GREEN', 'BLUE']);
$infinite = new InfiniteIterator($colors);

// 無限ループするデータソースから、必要な 7 件だけを切り出す
$colorStream = new LimitIterator($infinite, offset: 0, limit: 7);

$extracted = [];
foreach ($colorStream as $color) {
    $extracted[] = $color;
}
printf("無限イテレータから安全に取得: %s\n", implode(' -> ', $extracted));

echo PHP_EOL . "=== 3. 実務応用：大規模データのメモリ消費比較 (配列チェーン vs イテレータパイプライン) ===" . PHP_EOL;

$largeSize = 100_000;
$largeArray = range(1, $largeSize);

// 通常の配列関数（全件走査＋中間配列がメモリに生成される）
$m1 = memory_get_usage();
$filteredArray = array_filter($largeArray, fn($n) => $n % 2 === 0);
$slicedArray = array_slice($filteredArray, 10, 5);
$m2 = memory_get_usage();
$arrayMem = $m2 - $m1;

// SPL イテレータパイプライン（1件ずつ遅延評価するため中間配列を作らない）
$m3 = memory_get_usage();
$splInner = new ArrayIterator($largeArray);
$splFilter = new class($splInner) extends FilterIterator {
    public function accept(): bool {
        return $this->current() % 2 === 0;
    }
};
$splLimit = new LimitIterator($splFilter, 10, 5);
$splResult = iterator_to_array($splLimit, false);
$m4 = memory_get_usage();
$splMem = $m4 - $m3;

printf("配列チェーン (array_filter + array_slice) の追加メモリ : %s bytes\n", number_format($arrayMem));
printf("SPL パイプライン (FilterIterator + LimitIterator) の追加メモリ: %s bytes\n", number_format($splMem));
printf("メモリ効率: SPL パイプラインは中間配列メモリの発生を強力に抑止！\n");
