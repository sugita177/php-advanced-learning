<?php

declare(strict_types=1);

/**
 * Lesson 7.1: ストリームラッパー (stream_wrapper_register) による仮想ファイルシステムの構築
 * 
 * 実行方法:
 *   make run FILE=src/Phase7/Lesson7_1_StreamWrapper.php
 */

class MemoryStreamWrapper
{
    /** @var array<string, string> 仮想ストレージ（全インスタンスで共有） */
    public static array $storage = [];

    /** @var resource|null コンテキスト */
    public $context;

    private string $currentPath = '';
    private int $position = 0;

    public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool
    {
        $this->currentPath = $path;
        $this->position = 0;

        if (str_contains($mode, 'w')) {
            self::$storage[$this->currentPath] = '';
        }

        if (str_contains($mode, 'r') && !isset(self::$storage[$this->currentPath])) {
            return false;
        }

        return true;
    }

    public function stream_read(int $count): string
    {
        $data = self::$storage[$this->currentPath] ?? '';
        $chunk = substr($data, $this->position, $count);
        $this->position += strlen($chunk);
        return $chunk;
    }

    public function stream_write(string $data): int
    {
        $current = self::$storage[$this->currentPath] ?? '';
        if (strlen($current) < $this->position) {
            $current = str_pad($current, $this->position, "\0");
        }
        self::$storage[$this->currentPath] = substr_replace($current, $data, $this->position, strlen($data));
        $this->position += strlen($data);
        return strlen($data);
    }

    public function stream_tell(): int
    {
        return $this->position;
    }

    public function stream_eof(): bool
    {
        $data = self::$storage[$this->currentPath] ?? '';
        return $this->position >= strlen($data);
    }

    public function stream_seek(int $offset, int $whence = SEEK_SET): bool
    {
        $data = self::$storage[$this->currentPath] ?? '';
        $length = strlen($data);

        switch ($whence) {
            case SEEK_SET:
                if ($offset >= 0 && $offset <= $length) {
                    $this->position = $offset;
                    return true;
                }
                return false;
            case SEEK_CUR:
                if ($this->position + $offset >= 0) {
                    $this->position += $offset;
                    return true;
                }
                return false;
            case SEEK_END:
                if ($length + $offset >= 0) {
                    $this->position = $length + $offset;
                    return true;
                }
                return false;
            default:
                return false;
        }
    }

    /**
     * @return array<int|string, int>|false
     */
    public function stream_stat(): array|false
    {
        return [];
    }

    /**
     * @return array<int|string, int>|false
     */
    public function url_stat(string $path, int $flags): array|false
    {
        if (isset(self::$storage[$path])) {
            return [
                'size' => strlen(self::$storage[$path]),
                7 => strlen(self::$storage[$path]),
            ];
        }
        return false;
    }

    public function unlink(string $path): bool
    {
        if (isset(self::$storage[$path])) {
            unset(self::$storage[$path]);
            return true;
        }
        return false;
    }
}

echo "=== 1. ストリームラッパーの登録 ===" . PHP_EOL;
stream_wrapper_register('vfs', MemoryStreamWrapper::class);
printf("vfs プロトコルの登録に成功しました。\n");

echo PHP_EOL . "=== 2. 高レベル API (file_put_contents / file_get_contents) の利用 ===" . PHP_EOL;
$bytes = file_put_contents('vfs://config.json', '{"app": "PHP Advanced", "version": 8.5}');
printf("書き込んだバイト数: %d bytes\n", (int) $bytes);

$readContent = file_get_contents('vfs://config.json');
printf("読み込んだデータ: %s\n", (string) $readContent);

echo PHP_EOL . "=== 3. 低レベルストリーム操作 (fopen / fwrite / fseek / fread) ===" . PHP_EOL;
$fp = fopen('vfs://log.txt', 'w+');
assert(is_resource($fp));

fwrite($fp, '[INFO] 処理を開始しました。');
fwrite($fp, ' [DEBUG] 初期化完了。');

// 先頭から 7 文字目へシーク
fseek($fp, 7);
$partial = fread($fp, 12);
printf("シーク後の部分読み出し: '%s'\n", (string) $partial);
fclose($fp);

echo PHP_EOL . "=== 4. ファイル削除 (unlink) と存在確認 ===" . PHP_EOL;
printf("削除前: storage 内のファイル数 = %d\n", count(MemoryStreamWrapper::$storage));
$unlinked = unlink('vfs://config.json');
printf("unlink('vfs://config.json') の結果: %s\n", $unlinked ? 'true' : 'false');
printf("削除後: storage 内のファイル数 = %d\n", count(MemoryStreamWrapper::$storage));

// 登録解除
stream_wrapper_unregister('vfs');
echo PHP_EOL . "vfs プロトコルを登録解除しました (stream_wrapper_unregister)。\n";
