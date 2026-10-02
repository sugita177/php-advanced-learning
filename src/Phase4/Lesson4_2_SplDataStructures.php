<?php

declare(strict_types=1);

/**
 * Lesson 4.2: SPL データ構造 vs 組み込み配列のメモリ・実行速度特性
 * 
 * 実行方法:
 *   make run FILE=src/Phase4/Lesson4_2_SplDataStructures.php
 */

echo "=== 1. SplFixedArray vs 通常配列のメモリ消費量比較 ===" . PHP_EOL;

$size = 100_000;

// SplFixedArray のメモリ計測
$m1 = memory_get_usage();
$fixedArray = new SplFixedArray($size);
for ($i = 0; $i < $size; $i++) {
    $fixedArray[$i] = $i;
}
$m2 = memory_get_usage();
$splMem = $m2 - $m1;

// 通常配列のメモリ計測
$m3 = memory_get_usage();
$normalArray = [];
for ($i = 0; $i < $size; $i++) {
    $normalArray[] = $i;
}
$m4 = memory_get_usage();
$normalMem = $m4 - $m3;

printf("要素数: %d 件\n", $size);
printf("SplFixedArray メモリ: %s bytes (約 %.2f MB)\n", number_format($splMem), $splMem / 1024 / 1024);
printf("通常配列 メモリ      : %s bytes (約 %.2f MB)\n", number_format($normalMem), $normalMem / 1024 / 1024);
printf("削減効果            : SplFixedArray は通常配列の約 %.1f%% のメモリで動作\n", ($splMem / $normalMem) * 100);

echo PHP_EOL . "=== 2. SplFixedArray の厳格な制約とサイズ変更 ===" . PHP_EOL;

$fixed = new SplFixedArray(3);
$fixed[0] = 'A';
$fixed[1] = 'B';
$fixed[2] = 'C';

// 文字列キー代入のブロック
try {
    // @phpstan-ignore-next-line
    $fixed['key'] = 'D';
} catch (TypeError $e) {
    printf("[ブロック成功] 文字列キー代入エラー: %s\n", $e->getMessage());
}

// 範囲外インデックスアクセスのブロック
try {
    $fixed[3] = 'D';
} catch (OutOfBoundsException $e) {
    printf("[ブロック成功] 範囲外アクセスエラー: %s\n", $e->getMessage());
}

// 明示的リサイズ
$fixed->setSize(4);
$fixed[3] = 'D (setSize後)';
printf("リサイズ後の要素数: %d, 追加された値: %s\n", $fixed->getSize(), $fixed[3]);

echo PHP_EOL . "=== 3. SplQueue (O(1)) vs array_shift (O(N)) の FIFO パフォーマンス ===" . PHP_EOL;

$queueSize = 10_000;

// SplQueue の計測
$queue = new SplQueue();
for ($i = 0; $i < $queueSize; $i++) {
    $queue->enqueue($i);
}

$startQueue = hrtime(true);
while (!$queue->isEmpty()) {
    $queue->dequeue(); // 先頭ポインタの更新のみ: O(1)
}
$endQueue = hrtime(true);
$queueTimeMs = ($endQueue - $startQueue) / 1_000_000;

// array_shift の計測
$arr = [];
for ($i = 0; $i < $queueSize; $i++) {
    $arr[] = $i;
}

$startArray = hrtime(true);
while (!empty($arr)) {
    array_shift($arr); // 先頭削除＋全要素の再インデックス化: O(N)
}
$endArray = hrtime(true);
$arrayTimeMs = ($endArray - $startArray) / 1_000_000;

printf("キュー処理件数: %d 件\n", $queueSize);
printf("SplQueue::dequeue() 所要時間: %.3f ms\n", $queueTimeMs);
printf("array_shift() 所要時間       : %.3f ms\n", $arrayTimeMs);
printf("性能差                       : SplQueue が約 %.1f 倍 高速！\n", $arrayTimeMs / max($queueTimeMs, 0.001));

echo PHP_EOL . "=== 4. SplStack (LIFO / 後入れ先出し) の挙動 ===" . PHP_EOL;

$stack = new SplStack();
$stack->push('First');
$stack->push('Second');
$stack->push('Third');

printf("ポップ順 (LIFO): %s -> %s -> %s\n", $stack->pop(), $stack->pop(), $stack->pop());
