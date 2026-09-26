<?php

test('spaceship operator', function () {
    // A: 数値形式文字列と数値
    expect('10' <=> 2)->toBe(1);
    // B: 配列同士の比較（要素数が異なる場合）
    expect([1, 2] <=> [1, 2, 3])->toBe(-1);
    // C: 配列同士の比較（要素数は同じだが要素が異なる場合）
    expect([1, 9] <=> [2, 0])->toBe(-1);
    // D: 配列同士の比較（キーの定義順が異なる場合）
    expect(['a' => 1, 'b' => 2] <=> ['b' => 2, 'a' => 1])->toBe(0);
    // E: 特殊な浮動小数点
    expect(NAN <=> 0)->toBe(1);
});

test('sort and spaceship operator', function () {
    $users = [
        ['name' => 'Alice', 'score' => 80, 'age' => 25],
        ['name' => 'Bob',   'score' => 90, 'age' => 30],
        ['name' => 'Carol', 'score' => 80, 'age' => 22],
        ['name' => 'Dave',  'score' => 80, 'age' => 25],
    ];

    usort($users, fn($a, $b) => 
        [$b['score'], $a['age'], $a['name']] <=> [$a['score'], $b['age'], $b['name']]
    );

    expect($users)->toBe([
        ['name' => 'Bob',   'score' => 90, 'age' => 30],
        ['name' => 'Carol', 'score' => 80, 'age' => 22],
        ['name' => 'Alice', 'score' => 80, 'age' => 25],
        ['name' => 'Dave',  'score' => 80, 'age' => 25],
    ]);
    
});