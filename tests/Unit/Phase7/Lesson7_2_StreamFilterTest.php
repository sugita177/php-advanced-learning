<?php

declare(strict_types=1);

class MaskSecretFilter extends php_user_filter
{
    /**
     * @param resource $in 入力バケットブリゲード（データのバケツリレーの入り口）
     * @param resource $out 出力バケットブリゲード（次の処理へ流すバケツリレーの出口）
     * @param int $consumed 消費したバイト数（参照渡し）
     * @param bool $closing ストリームが閉じられつつあるか
     */
    public function filter($in, $out, &$consumed, bool $closing): int
    {
        // 1. 入力からバケットを1つずつ取り出す
        while ($bucket = stream_bucket_make_writeable($in)) {
            // 2. データをオンザフライ変換（例: "SECRET" や "PASSWORD" を "********" に置換）
            $bucket->data = str_replace('SECRET', '********', (string) $bucket->data);

            // 3. 消費したバイト数を加算
            $consumed += (int) $bucket->datalen;

            // 4. 加工したバケットを出力キューへ追加
            stream_bucket_append($out, $bucket);
        }

        // 5. データを次に流す（Pass On）
        return PSFS_PASS_ON;
    }
}

test('stream filter execution', function () {
    $fp = fopen('php://memory', 'r+');
    assert(is_resource($fp));

    stream_filter_append($fp, 'string.toupper', STREAM_FILTER_WRITE);
    fwrite($fp, 'hello, world!');
    rewind($fp);
    $content = stream_get_contents($fp);
    expect($content)->toBe('HELLO, WORLD!');
    fclose($fp);
});

test('custom stream filter', function () {
    stream_filter_register('mask.secrete', MaskSecretFilter::class);
    $fp = fopen('php://memory', 'r+');
    assert(is_resource($fp));
    stream_filter_append($fp, 'mask.secrete', STREAM_FILTER_WRITE);

    fwrite($fp, 'User: Alice, Key: SECRET');
    rewind($fp);
    $content = stream_get_contents($fp);
    expect($content)->toBe('User: Alice, Key: ********');
    fclose($fp);
});