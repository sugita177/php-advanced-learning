<?php

declare(strict_types=1);

/**
 * Lesson 8.1: Pure Enum / Backed Enum の内部仕様と完全網羅性検査
 * 
 * 実行方法:
 *   make run FILE=src/Phase8/Lesson8_1_EnumBasicsAndExhaustiveness.php
 */

echo "=== 1. Pure Enum のオブジェクト構造とシングルトン性 ===" . PHP_EOL;

enum Direction
{
    case North;
    case South;
    case East;
    case West;
}

function resolveDirection(string $name): Direction
{
    $val = constant("Direction::{$name}");
    assert($val instanceof Direction);
    return $val;
}

$d1 = resolveDirection('North');
$d2 = resolveDirection('North');
$dSouth = resolveDirection('South');

printf("同一インスタンス比較 (\$d1 === \$d2): %s\n", $d1 === $d2 ? 'true' : 'false');
printf("異なる Case との比較 (\$d1 === Direction::South): %s\n", $d1 === $dSouth ? 'true' : 'false');
printf("Direction::North->name: '%s'\n", $d1->name);

try {
    $clone = clone $d1;
    echo $clone->name;
    /** @phpstan-ignore catch.neverThrown */
} catch (Error $e) {
    printf("[clone 禁止] %s: '%s'\n", get_class($e), $e->getMessage());
}

$ref = new ReflectionEnum(Direction::class);
/** @phpstan-ignore method.impossibleType */
$isBacked = $ref->isBacked();
printf("Reflection による検証 -> isBacked(): %s, hasProperty('value'): %s\n",
    $isBacked ? 'true' : 'false',
    $ref->hasProperty('value') ? 'true' : 'false'
);

echo PHP_EOL . "=== 2. Backed Enum と from() / tryFrom() の境界 ===" . PHP_EOL;

enum Priority: int
{
    case Low = 1;
    case Medium = 2;
    case High = 3;
}

$high = Priority::from(3);
printf("Priority::from(3) -> name: %s, value: %d\n", $high->name, $high->value);

$unknown = Priority::tryFrom(999);
printf("Priority::tryFrom(999) -> %s\n", var_export($unknown, true));

try {
    $invalid = Priority::from(999);
    echo $invalid->name;
} catch (ValueError $e) {
    printf("[ValueError 検知] from() に無効な値を渡した時: '%s'\n", $e->getMessage());
}

echo PHP_EOL . "=== 3. match 式による網羅性検査 (Exhaustiveness Check) ===" . PHP_EOL;

function getPriorityBadge(Priority $priority): string
{
    // default 句を書かずに全 Case を網羅
    return match ($priority) {
        Priority::Low => '[緑: 低優先度]',
        Priority::Medium => '[黄: 中優先度]',
        Priority::High => '[赤: 高優先度]',
    };
}

foreach (Priority::cases() as $case) {
    printf("Case: %-6s (Value: %d) -> Badge: %s\n", $case->name, $case->value, getPriorityBadge($case));
}

echo PHP_EOL . "全 Case が match 式で型安全かつ網羅的に処理されました！\n";
