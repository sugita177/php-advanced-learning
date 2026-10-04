<?php

declare(strict_types=1);

use PHPUnit\Event\Runtime\Runtime;

// finally 内での return の上書き
function trickyFinally(): string
{
    try {
        return 'TRY';
    } finally {
        return 'FINALLY';
    }
}

test('return iside finally block', function () {
    expect(trickyFinally())->toBe('FINALLY');
});

// finally 内での return による例外の揉み消し
function swallowException(): string
{
    try {
        throw new RuntimeException('Fatal Boom!');
    } catch (LogicException $e) {
        return 'CAUGHT LOGIC';
    } finally {
        return 'CLEAN UP';
    }
}

test('swallow exception', function () {
    expect(swallowException())->toBe('CLEAN UP');
});

function handleFault(Throwable $t): string
{
    try {
        throw $t;
    } catch (TypeError | InvalidArgumentException $e) {
        return 'CAUGHT_SPECIFIC: ' . get_class($e);
    } catch (Throwable $e) {
        return 'CAUGHT_GENERIC';
    }
}

test('handle fault', function () {
    expect(handleFault(new TypeError('BAD')))->toBe('CAUGHT_SPECIFIC: TypeError');
    expect(handleFault(new InvalidArgumentException('BAD2')))->toBe('CAUGHT_SPECIFIC: InvalidArgumentException');
    expect(handleFault(new RuntimeException('BAD3')))->toBe('CAUGHT_GENERIC');
});

