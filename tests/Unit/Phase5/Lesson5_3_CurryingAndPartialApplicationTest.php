<?php

declare(strict_types=1);

test('currying', function () {
    $curriedCalc = fn(int $x) => fn(int $y) => fn(int $z) => $x + $y - $z;
    expect($curriedCalc(1)(2)(3))->toBe(0);
    $minusFrom5 = $curriedCalc(2)(3);
    expect($minusFrom5(4))->toBe(1);
});

function partial(callable $fn, mixed ...$fixedArgs): Closure
{
    return fn(mixed ...$rest) => $fn(...$fixedArgs, ...$rest);
}

test('partial application', function () {
    $myFn = fn(float $taxRate, int $price): int => (int) ($price * ($taxRate + 1));
    $tax10 = partial($myFn, 0.10);
    expect($tax10(100))->toBe(110);
    expect($tax10(500))->toBe(550);
});

function curry(callable $fn): Closure
{
    $ref = new ReflectionFunction(Closure::fromCallable($fn));
    $requiredCount = $ref->getNumberOfRequiredParameters();

    $currier = function (array $accumulated) use (&$currier, $fn, $requiredCount): mixed {
        return function (mixed ...$newArgs) use ($accumulated, $currier, $fn, $requiredCount): mixed {
            $allArgs = array_merge($accumulated, $newArgs);
            if (count($allArgs) >= $requiredCount) {
                return $fn(...$allArgs);
            }
            return $currier($allArgs);
        };
    };

    return $currier([]);
}

test('general curry function', function () {
    $fn = fn($a, $b, $c) => "{$a}-{$b}-{$c}";
    $curriedFn = curry($fn);
    expect($curriedFn(1)(2)(3))->toBe("1-2-3");
    expect($curriedFn(1, 2)(3))->toBe("1-2-3");
    expect($curriedFn(1, 2, 3))->toBe("1-2-3");
    expect($curriedFn(1)(2, 3))->toBe("1-2-3");

    $step1 = $curriedFn(1);
    $step2 = $step1(2);
    expect($step2(3))->toBe("1-2-3");

    $step1 = $curriedFn(1);
    $step2 = $step1(2, 3);
    expect($step2)->toBe("1-2-3");
});
