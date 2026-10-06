<?php

declare(strict_types=1);

/**
 * Lesson 7.2: ストリームフィルタ (php_user_filter) によるオンザフライデータ変換
 * 
 * 実行方法:
 *   make run FILE=src/Phase7/Lesson7_2_StreamFilter.php
 */

echo "=== 1. 組み込みストリームフィルター (string.toupper, convert.base64-encode) ===" . PHP_EOL;

$fp = fopen('php://memory', 'r+');
assert(is_resource($fp));

// 書き込みストリームにフィルターを追加
stream_filter_append($fp, 'string.toupper', STREAM_FILTER_WRITE);
fwrite($fp, 'hello, stream filter!');

rewind($fp);
$rawUpper = stream_get_contents($fp);
assert(is_string($rawUpper));
printf("string.toupper 適用後の書き込みデータ: '%s'\n", $rawUpper);
fclose($fp);

echo PHP_EOL . "=== 2. 高レベル API での php://filter 構文 (ストリームメタラッパー) ===" . PHP_EOL;

// 一時ファイルに元データを保存
$tempFile = sys_get_temp_dir() . '/stream_filter_test.txt';
file_put_contents($tempFile, 'secret password 12345');

// php://filter メタラッパーを使って読み込み時に Base64 エンコード
$encoded = file_get_contents("php://filter/read=convert.base64-encode/resource={$tempFile}");
assert(is_string($encoded));
printf("php://filter で読み出し時に Base64 変換: '%s'\n", $encoded);

// デコード確認
printf("Base64 復元確認: '%s'\n", base64_decode($encoded, true));
unlink($tempFile);

echo PHP_EOL . "=== 3. カスタムストリームフィルター (php_user_filter) ===" . PHP_EOL;

class MaskSensitiveFilter extends php_user_filter
{
    /**
     * @param resource $in
     * @param resource $out
     * @param int $consumed
     * @param bool $closing
     */
    public function filter($in, $out, &$consumed, bool $closing): int
    {
        while ($bucket = stream_bucket_make_writeable($in)) {
            // 機密ワードのマスキング
            $bucket->data = str_replace(
                ['API_KEY_SECRET', 'CREDIT_CARD_1234'],
                ['[MASKED_API_KEY]', '[MASKED_CARD]'],
                (string) $bucket->data
            );

            $consumed += (int) $bucket->datalen;
            stream_bucket_append($out, $bucket);
        }

        return PSFS_PASS_ON;
    }
}

// フィルターの登録
stream_filter_register('mask.sensitive', MaskSensitiveFilter::class);
echo "カスタムフィルター 'mask.sensitive' を登録しました。\n";

$stream = fopen('php://memory', 'r+');
assert(is_resource($stream));

// フィルターをチェーン（連結）: マスク -> 大文字化
stream_filter_append($stream, 'mask.sensitive', STREAM_FILTER_WRITE);
stream_filter_append($stream, 'string.toupper', STREAM_FILTER_WRITE);

$logData = "2026-10-06 [INFO] User login with API_KEY_SECRET and payment with CREDIT_CARD_1234 done.";
fwrite($stream, $logData);

rewind($stream);
$processed = stream_get_contents($stream);
assert(is_string($processed));
printf("\nパイプライン変換後のストリームデータ:\n%s\n", $processed);

fclose($stream);
