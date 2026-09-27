<?php

declare(strict_types=1);

/**
 * Lesson 1.5: 交差型 (Intersection Types) と DNF型 (Disjunctive Normal Form) の型境界
 * 
 * 実行方法:
 *   make run FILE=src/Phase1/Lesson1_5_DnfTypes.php
 */

echo "=== 1. DNF型 (Disjunctive Normal Form) の実践 ===" . PHP_EOL;

// (Countable&Traversable)|null
// 「要素数を数えられ、かつループ可能なオブジェクト」または「null」のみを許可
function processCollection((Countable&Traversable)|null $collection): int
{
    if ($collection === null) {
        return 0;
    }

    return count($collection);
}

// 1. ArrayIterator (Countable & Traversable を両方満たすオブジェクト)
$iterator = new ArrayIterator(['PHP 8.0', 'PHP 8.1', 'PHP 8.2', 'PHP 8.4', 'PHP 8.5']);
printf("ArrayIterator を渡した場合: 要素数 %d (正常動作)\n", processCollection($iterator));

// 2. null を渡した場合
printf("null を渡した場合: 要素数 %d (正常動作)\n", processCollection(null));

echo PHP_EOL . "=== 2. 型境界の検証：プリミティブな配列とインターフェース ===" . PHP_EOL;

// 3. 配列 ['apple', 'banana'] を渡す
try {
    // @phpstan-ignore argument.type
    processCollection(['apple', 'banana']);
} catch (TypeError $e) {
    printf("配列 ['apple', 'banana'] を渡した場合: %s\n", $e->getMessage());
    echo " -> 理由: 配列は count も foreach も可能だが、「オブジェクト」ではないため Traversable インターフェースを実装していない。\n";
}

// 4. Countable のみ実装した無名クラスを渡す
$onlyCountable = new class implements Countable {
    public function count(): int { return 1; }
};

try {
    // @phpstan-ignore argument.type
    processCollection($onlyCountable);
} catch (TypeError $e) {
    printf("Countable のみ実装したクラスを渡した場合: %s\n", $e->getMessage());
    echo " -> 理由: Traversable を満たしていないため、交差型 (Countable&Traversable) の契約に違反する。\n";
}

echo PHP_EOL . "=== 3. DNF型と構文ルールのまとめ ===" . PHP_EOL;
echo " - Valid:   (A&B)|C       （交差型セグメントは丸括弧で囲む）\n";
echo " - Invalid: A&B|C         （暗黙の優先順位がないため Parse Error）\n";
echo " - Invalid: (A|B)&C       （CNF / 和の積 は PHP では文法上禁止）\n";
echo " - Invalid: int&string    （スカラー型は交差できない）\n";
