<?php

declare(strict_types=1);

/**
 * Lesson 6.1: Throwable 階層構造と try-catch-finally 内の制御フロー
 * 
 * 実行方法:
 *   make run FILE=src/Phase6/Lesson6_1_ThrowableAndFinally.php
 */

echo "=== 1. finally 内の return による戻り値上書き ===" . PHP_EOL;

function testReturnOverride(): string
{
    try {
        echo " [try] return 'TRY' を実行\n";
        return 'TRY';
    } finally {
        echo " [finally] 割り込み！ return 'FINALLY' で上書き\n";
        return 'FINALLY';
    }
}

$ret = testReturnOverride();
printf("最終的な戻り値: '%s'\n", $ret);

echo PHP_EOL . "=== 2. finally 内の return による未捕捉例外の揉み消し (危険) ===" . PHP_EOL;

function testExceptionSwallowing(): string
{
    try {
        echo " [try] 重大な例外をスロー！\n";
        throw new RuntimeException('データベース接続が爆発しました');
    } catch (InvalidArgumentException $e) {
        // ここにはマッチしない
        return 'CAUGHT_INVALID_ARG';
    } finally {
        echo " [finally] 割り込み return！\n";
        return 'CLEANUP_SUCCESS'; // 未捕捉の例外を Zend Engine が破棄して正常終了！
    }
}

$swallowed = testExceptionSwallowing();
printf("関数実行結果: '%s' (例外が外に伝播せず揉み消された！)\n", $swallowed);

echo PHP_EOL . "=== 3. finally 内でさらに例外を投げた場合の挙動 ===" . PHP_EOL;

function testExceptionOverriding(): void
{
    try {
        throw new Exception('最初の例外: A');
    } finally {
        throw new RuntimeException('finally の例外: B'); // 元の例外 A は消滅！
    }
}

try {
    testExceptionOverriding();
} catch (Throwable $e) {
    printf("[検知された例外] クラス: %s, メッセージ: '%s' (元の例外 A は失われた)\n", get_class($e), $e->getMessage());
}

echo PHP_EOL . "=== 4. Throwable 階層と multi-catch ===" . PHP_EOL;

$errors = [
    new TypeError('引数の型が違います (Error)'),
    new InvalidArgumentException('引数の値が不正です (Exception)'),
    new OutOfBoundsException('範囲外アクセスです (RuntimeException)'),
];

foreach ($errors as $err) {
    try {
        throw $err;
    } catch (TypeError | InvalidArgumentException $e) {
        printf("特定エラーを multi-catch: [%s] %s\n", get_class($e), $e->getMessage());
    } catch (Throwable $e) {
        printf("汎用 Throwable でフォールバック: [%s] %s\n", get_class($e), $e->getMessage());
    }
}
