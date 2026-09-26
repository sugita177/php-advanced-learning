<?php

declare(strict_types=1);

/**
 * Lesson 1.2: 宇宙船演算子 (<=>) と複合ソートアルゴリズム
 * 
 * 実行方法:
 *   make run FILE=src/Phase1/Lesson1_2_SpaceshipOperator.php
 */

echo "=== 1. 宇宙船演算子 (<=>) の基本比較 ===" . PHP_EOL;

$comparisons = [
    '"10" <=> 2' => "10" <=> 2,                     //  1: 数値形式文字列と数値
    '[1, 2] <=> [1, 2, 3]' => [1, 2] <=> [1, 2, 3], // -1: 要素数の違い
    '[1, 9] <=> [2, 0]' => [1, 9] <=> [2, 0],       // -1: 先頭要素で判定
    "['a'=>1,'b'=>2] <=> ['b'=>2,'a'=>1]" => ['a' => 1, 'b' => 2] <=> ['b' => 2, 'a' => 1], // 0: 連想配列はキーと値の一致
    'NAN <=> 0' => NAN <=> 0,                       //  1: 全順序維持のため NAN は全数値より大
];

foreach ($comparisons as $expr => $result) {
    printf("%-38s => %2d\n", $expr, $result);
}

echo PHP_EOL . "=== 2. usort() による複数条件ソート（タプル比較） ===" . PHP_EOL;

$users = [
    ['name' => 'Alice', 'score' => 80, 'age' => 25],
    ['name' => 'Bob',   'score' => 90, 'age' => 30],
    ['name' => 'Carol', 'score' => 80, 'age' => 22],
    ['name' => 'Dave',  'score' => 80, 'age' => 25],
];

// ソート条件: score降順 -> age昇順 -> name昇順
usort($users, fn (array $a, array $b): int => 
    [$b['score'], $a['age'], $a['name']] <=> [$a['score'], $b['age'], $b['name']]
);

foreach ($users as $user) {
    printf("Score: %2d | Age: %2d | Name: %s\n", $user['score'], $user['age'], $user['name']);
}

echo PHP_EOL . "※ PHP 8.0 以降、usort() を含むすべてのソート関数は「安定ソート (Stable Sort)」になりました。" . PHP_EOL;
