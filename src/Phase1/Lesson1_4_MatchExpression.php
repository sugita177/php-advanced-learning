<?php

declare(strict_types=1);

/**
 * Lesson 1.4: match 式の厳密一致 (===) と網羅性検査 (Exhaustiveness Check)
 * 
 * 実行方法:
 *   make run FILE=src/Phase1/Lesson1_4_MatchExpression.php
 */

echo "=== 1. switch (==) vs match (===) の型判定比較 ===" . PHP_EOL;

$input = '1'; // string

// switch: 緩やかな比較 (==) のため '1' == 1 で最初の case に入ってしまう
$switchResult = '';
switch ($input) {
    case 1:
        $switchResult = 'int 1 にマッチ (==)';
        break;
    case '1':
        $switchResult = 'string 1 にマッチ (===)';
        break;
}

// match: 厳密比較 (===) のため型まで完全一致するアームへ入る
$matchResult = match ($input) {
    1   => 'int 1 にマッチ (==)',
    '1' => 'string 1 にマッチ (===)',
};

printf("入力値: string('1')\n");
printf(" - switch 文: %s\n", $switchResult);
printf(" - match  式: %s\n", $matchResult);

echo PHP_EOL . "=== 2. 網羅性検査 (UnhandledMatchError) ===" . PHP_EOL;

try {
    $unknown = 999;
    match ($unknown) {
        1 => 'one',
        2 => 'two',
    };
} catch (UnhandledMatchError $e) {
    printf("default なしで合致しない場合: %s がスローされる\n", get_class($e));
}

echo PHP_EOL . "=== 3. 実務応用：match (true) パターンによる HTTP ステータス判定 ===" . PHP_EOL;

$evaluateHttpStatus = fn (int $code): string => match (true) {
    $code >= 100 && $code < 200 => 'Informational (1xx)',
    $code >= 200 && $code < 300 => 'Success (2xx)',
    $code >= 300 && $code < 400 => 'Redirection (3xx)',
    $code >= 400 && $code < 500 => 'Client Error (4xx)',
    $code >= 500 && $code < 600 => 'Server Error (5xx)',
    default                     => 'Unknown Status',
};

$testCodes = [100, 200, 204, 301, 404, 500, 999];
foreach ($testCodes as $code) {
    printf("HTTP %-3d => %s\n", $code, $evaluateHttpStatus($code));
}
