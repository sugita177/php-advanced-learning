<?php

declare(strict_types=1);

class InMemoryStreamWrapper
{
    /** @var array<string, string> 仮想ストレージ（全インスタンスで共有するため static） */
    public static array $storage = [];

    /** @var resource|null コンテキスト（PHP が自動でセットするプロパティ） */
    public $context;

    private string $currentPath = '';
    private int $position = 0;

    /**
     * fopen / file_get_contents / file_put_contents 時に最初に呼ばれる
     */
    public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool
    {
        $this->currentPath = $path;
        $this->position = 0;

        // 書き込みモード（w, wb 等）なら既存データをクリアして準備
        if (str_contains($mode, 'w')) {
            self::$storage[$this->currentPath] = '';
        }

        // 読み込みモード（r, rb 等）でファイルが存在しなければ失敗
        if (str_contains($mode, 'r') && !isset(self::$storage[$this->currentPath])) {
            return false;
        }

        return true;
    }

    /**
     * データ読み込み（現在の position から $count バイト分取得）
     */
    public function stream_read(int $count): string
    {
        $data = self::$storage[$this->currentPath] ?? '';
        $chunk = substr($data, $this->position, $count);
        $this->position += strlen($chunk);
        return $chunk;
    }

    /**
     * データ書き込み
     */
    public function stream_write(string $data): int
    {
        $current = self::$storage[$this->currentPath] ?? '';
        // 現在の書き込み位置より短い場合は空白で埋める（シーク対応）
        if (strlen($current) < $this->position) {
            $current = str_pad($current, $this->position, "\0");
        }
        // 現在位置からデータを上書き結合
        self::$storage[$this->currentPath] = substr_replace($current, $data, $this->position, strlen($data));
        $this->position += strlen($data);
        return strlen($data);
    }

    /**
     * ファイル終端 (EOF) の判定
     */
    public function stream_eof(): bool
    {
        $data = self::$storage[$this->currentPath] ?? '';
        return $this->position >= strlen($data);
    }

    /**
     * stat 情報（file_get_contents や is_file 等が呼ぶ場合がある）
     * @return array<int|string, int>|false
     */
    public function stream_stat(): array|false
    {
        return [];
    }

    /**
     * url_stat (file_exists 等で呼ばれる)
     * @return array<int|string, int>|false
     */
    public function url_stat(string $path, int $flags): array|false
    {
        return [];
    }

    public function unlink(string $path): bool
    {
        unset(self::$storage[$path]);
        return true;
    }
}

test('stream wrapper read and write', function () {
    stream_wrapper_register('vfs', InMemoryStreamWrapper::class);
    expect(file_put_contents('vfs://test.txt', 'hello, stream!'))->toBe(14);
    expect(file_get_contents('vfs://test.txt'))->toBe('hello, stream!');
    expect(InMemoryStreamWrapper::$storage)->toBe([
        'vfs://test.txt' => 'hello, stream!',
    ]);

    // clean up
    InMemoryStreamWrapper::$storage = [];
    stream_wrapper_unregister('vfs');
});

test('stream wrapper read when file does not exist', function () {
    stream_wrapper_register('vfs', InMemoryStreamWrapper::class);
    expect(file_get_contents('vfs://not_found.txt'))->toBeFalse();

    // clean up
    InMemoryStreamWrapper::$storage = [];
    stream_wrapper_unregister('vfs');
});

test('stream wrapper write multiple times and unlink', function () {
    stream_wrapper_register('vfs', InMemoryStreamWrapper::class);
    $fp = fopen('vfs://stream.txt', 'w');
    assert(is_resource($fp));
    fwrite($fp, 'Hello');
    expect(file_get_contents('vfs://stream.txt'))->toBe('Hello');
    expect(InMemoryStreamWrapper::$storage)->toBe([
        'vfs://stream.txt' => 'Hello',
    ]);
    fwrite($fp, ', world!');
    fclose($fp);
    expect(file_get_contents('vfs://stream.txt'))->toBe('Hello, world!');
    expect(InMemoryStreamWrapper::$storage)->toBe([
        'vfs://stream.txt' => 'Hello, world!',
    ]);
    expect(unlink('vfs://stream.txt'))->toBeTrue();
    expect(file_get_contents('vfs://stream.txt'))->toBeFalse();
    expect(InMemoryStreamWrapper::$storage)->toBe([]);

    // clean up
    InMemoryStreamWrapper::$storage = [];
    stream_wrapper_unregister('vfs');
});
