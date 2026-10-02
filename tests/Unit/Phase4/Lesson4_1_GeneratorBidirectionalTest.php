<?php

declare(strict_types=1);

function myGen(array &$logList): Generator
{
    $logList[] = 'one';
    yield 1;

    $logList[] = 'two';
    yield 2;

    $logList[] = 'three';
    yield 3;
}

test('generator and Lazy Execution', function () {
    $logs = [];

    $gen = myGen($logs);
    expect($gen)->toBeInstanceOf(Generator::class);
    expect($logs)->toBe([]);

    expect($gen->current())->toBe(1);
    expect($logs)->toBe(['one']);

    $gen->next();
    expect($gen->current())->toBe(2);
    expect($logs)->toBe(['one', 'two']);

    $gen->next();
    expect($gen->current())->toBe(3);
    expect($logs)->toBe(['one', 'two', 'three']);

    // 再度current()を呼び出しても状態は変わらない
    expect($gen->current())->toBe(3);
    expect($logs)->toBe(['one', 'two', 'three']);
});

function numberGenerator(array &$logList): Generator
{
    $logList[] = 'A';
    $received = yield 1;
    $logList[] = "B:{$received}";
    yield 2;
    $logList[] = "C";
}

test('generator and send()', function () {
    $logs = [];

    $gen = numberGenerator($logs);
    expect($gen->current())->toBe(1);
    expect($logs)->toBe(['A']);

    expect($gen->send('Hello'))->toBe(2);
    expect($logs)->toBe(['A', 'B:Hello']);
    expect($gen->current())->toBe(2);
    $gen->next();
    expect($logs)->toBe(['A', 'B:Hello', 'C']);
    $gen->next();
    expect($logs)->toBe(['A', 'B:Hello', 'C']);
    expect($gen->current())->toBeNull();
});

function errorGenerator(): Generator
{
    try {
        yield 1;
    } catch (RuntimeException $e) {
        yield 'received';
    }
    yield 2;
}

test('generator and throw', function () {
    $gen = errorGenerator();
    expect($gen->current())->toBe(1);
    expect($gen->throw(new RuntimeException('Error')))->toBe('received');
    expect($gen->current())->toBe('received');
    $gen->next();
    expect($gen->current())->toBe(2);
});
