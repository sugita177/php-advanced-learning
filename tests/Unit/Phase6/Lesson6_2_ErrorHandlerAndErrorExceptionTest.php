<?php

declare(strict_types=1);

test('try-catch does not catch warning', function () {
    $caught = false;
    try {
        @trigger_error('My warning', E_USER_WARNING);
    } catch (Throwable $e) {
        $caught = true;
    }
    expect($caught)->toBeFalse();
});

test('set_error_handler', function () {
    set_error_handler(function (int $severity, string $message, string $file, int $line) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    });

    $caught = false;
    try {
        @trigger_error('My warning', E_USER_WARNING);
    } catch (Throwable $e) {
        $caught = true;
    }
    expect($caught)->toBeTrue();
    restore_error_handler();
});

test('error suppression operator', function () {
    $prevReporting = error_reporting(E_ALL);

    set_error_handler(function (int $severity, string $message, string $file, int $line) {
        if (! (error_reporting() & $severity)) {
            return false;
        }
        throw new ErrorException($message, 0, $severity, $file, $line);
    });

    $caught = false;
    try {
        trigger_error('My warning', E_USER_WARNING);
    } catch (Throwable $e) {
        $caught = true;
    }
    expect($caught)->toBeTrue();

    $caught = false;
    try {
        @trigger_error('My warning', E_USER_WARNING);
    } catch (Throwable $e) {
        $caught = true;
    }
    expect($caught)->toBeFalse();

    restore_error_handler();
    error_reporting($prevReporting);
});