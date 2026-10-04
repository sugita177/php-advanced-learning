<?php

declare(strict_types=1);

/**
 * Lesson 6.2: カスタムエラーハンドラと ErrorException への変換
 * 
 * 実行方法:
 *   make run FILE=src/Phase6/Lesson6_2_ErrorHandlerAndErrorException.php
 */

echo "=== 1. 通常の try-catch では Warning をキャッチできない問題 ===" . PHP_EOL;

$caught = false;
try {
    echo "Warning を発生させます...\n";
    // @ を付けて画面出力を抑えつつ Warning を発生
    @trigger_error('未定義プロパティへのアクセスなどの警告', E_USER_WARNING);
    echo "Warning 発生後も処理がそのまま続行されてしまいます。\n";
} catch (Throwable $e) {
    $caught = true;
}

printf("try-catch でキャッチできたか？: %s (Warning は例外ではないため素通り！)\n", $caught ? 'はい' : 'いいえ');

echo PHP_EOL . "=== 2. set_error_handler による ErrorException への昇格 ===" . PHP_EOL;

// PHP のあらゆるエラーを本物の例外に変換するモダンハンドラ
set_error_handler(function (int $severity, string $message, string $file, int $line) {
    // エラー抑制演算子 (@) が付いている場合は例外化せずスルー
    if (! (error_reporting() & $severity)) {
        echo " [@ 検知] エラー抑制演算子により例外化をスキップしました。\n";
        return false;
    }

    throw new ErrorException($message, 0, $severity, $file, $line);
});

try {
    echo "エラーハンドラ登録後に Warning を発生させます...\n";
    trigger_error('データベースクエリの構文不備警告', E_USER_WARNING);
} catch (ErrorException $e) {
    printf("[昇格成功] ErrorException として完全キャッチ！: '%s' (重要度: %d)\n", $e->getMessage(), $e->getSeverity());
}

echo PHP_EOL . "=== 3. エラー抑制演算子 (@) の尊重 ===" . PHP_EOL;

try {
    echo "@ 付きで Warning を発生させます...\n";
    @trigger_error('あえて抑制したいファイル読み込み警告', E_USER_WARNING);
    echo "@ により例外が投げられず、安全に後続処理が継続されました。\n";
} catch (ErrorException $e) {
    echo "例外が投げられました（これは発生しません）。\n";
}

// 元のエラーハンドラに復元
restore_error_handler();
echo PHP_EOL . "エラーハンドラを元の状態に復元しました (restore_error_handler)。\n";
