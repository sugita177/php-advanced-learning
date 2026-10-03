<?php

declare(strict_types=1);

test('pipe operator', function () {
    $result = "  php 8.5 pipe operator  "
        |> trim(...)
        |> strtoupper(...)
        |> (fn(string $s) => str_replace(' ', '_', $s));

    expect($result)->toBe("PHP_8.5_PIPE_OPERATOR");
});

function pipe(callable ...$functions): Closure
{
    return function (mixed $initialValue) use ($functions): mixed {
        return array_reduce(
            $functions,
            fn(mixed $carry, callable $fn) => $fn($carry),
            $initialValue
        );
    };
}

test('pipe helper function', function () {
    $processor = pipe(
        trim(...),
        strtoupper(...),
        fn(string $s) => $s . '!'
    );

    expect($processor("  hello  "))->toBe("HELLO!");
});

function compose(callable ...$functions): Closure
{
    return pipe(...array_reverse($functions));
}

test('compose helper function', function () {
    $add10 = fn(int $n): int => $n + 10;
    $multiply2 = fn(int $n): int => $n * 2;

    $pipe = pipe($add10, $multiply2);
    expect($pipe(5))->toBe(30);

    $composer = compose($add10, $multiply2);
    expect($composer(5))->toBe(20);
});
        