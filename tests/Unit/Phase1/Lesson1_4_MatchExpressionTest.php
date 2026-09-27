<?php

test('Match expression', function () {
    // A: 緩やかな比較 (switch) vs 厳密比較 (match)
    $input = '1';

    // switch の場合
    $switchResult = '';
    switch ($input) {
        case 1:
            $switchResult = 'int 1';
            break;
        case '1':
            $switchResult = 'string 1';
            break;
        default:
            break;
    }

    // match の場合
    $matchResult = match ($input) {
        1   => 'int 1',
        '1' => 'string 1',
    };

    // 予測: $switchResult と $matchResult の値はそれぞれ何になるか？
    expect($switchResult)->toBe('int 1');
    expect($matchResult)->toBe('string 1');

    // B: フォールスルーの有無と複数条件
    $code = 200;
    $status = match ($code) {
        200, 201, 204 => 'Success',
        400, 404      => 'Client Error',
        500           => 'Server Error',
    };
    // 予測: $status の値は？カンマ区切りの条件はどう評価されるか？
    expect($status)->toBe('Success');

    // C: パターンに合致せず default がない場合
    $unknown = 999;
    expect(fn () => match ($unknown) {
        1 => 'one',
        2 => 'two',
    })->toThrow(UnhandledMatchError::class);

    // D: 戻り値ではなく副作用（評価タイミング）
    $called = false;
    $action = function () use (&$called) {
        $called = true;
        return 'done';
    };

    $res = match ('test') {
        'other' => $action(),
        'test'  => 'ok',
    };
    // 予測: $called は true か false か？（マッチしなかったアームの式は評価されるか？）
    expect($called)->toBeFalse();
    expect($res)->toBe('ok');
});

test('use match expression to evaluate http status', function () {
    // 100 〜 199: Informational
    // 200 〜 299: Success
    // 300 〜 399: Redirection
    // 400 〜 499: Client Error
    // 500 〜 599: Server Error
    $evaluateHttpStatusCode = fn($code) => match (true) {
        $code >= 100 && $code < 200 => 'Informational',
        $code >= 200 && $code < 300 => 'Success',
        $code >= 300 && $code < 400 => 'Redirection',
        $code >= 400 && $code < 500 => 'Client Error',
        $code >= 500 && $code < 600 => 'Server Error',
        default => 'Unknown',
    };
    // 境界値と、その前後の値をチェックする
    expect($evaluateHttpStatusCode(99))->toBe('Unknown');
    expect($evaluateHttpStatusCode(100))->toBe('Informational');
    expect($evaluateHttpStatusCode(199))->toBe('Informational');
    expect($evaluateHttpStatusCode(200))->toBe('Success');
    expect($evaluateHttpStatusCode(299))->toBe('Success');
    expect($evaluateHttpStatusCode(300))->toBe('Redirection');
    expect($evaluateHttpStatusCode(399))->toBe('Redirection');
    expect($evaluateHttpStatusCode(400))->toBe('Client Error');
    expect($evaluateHttpStatusCode(499))->toBe('Client Error');
    expect($evaluateHttpStatusCode(500))->toBe('Server Error');
    expect($evaluateHttpStatusCode(599))->toBe('Server Error');
    expect($evaluateHttpStatusCode(600))->toBe('Unknown');
    expect($evaluateHttpStatusCode(999))->toBe('Unknown');
});