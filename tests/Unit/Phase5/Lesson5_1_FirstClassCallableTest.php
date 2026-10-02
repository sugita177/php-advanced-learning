<?php

declare(strict_types=1);

test('closure and first class callable', function () {
    $myStrLen = strlen(...);
    expect($myStrLen)->toBeInstanceOf(Closure::class);
    expect($myStrLen('hello'))->toBe(5);

    expect(array_map(strtoupper(...), ['php', 'pest']))->toBe(['PHP', 'PEST']);
});

test('undefined function and first class callable', function () {
    expect(fn() => undefined(...))->toThrow(Error::class);
});

class FccSecretService
{
    private function decrypt(string $token): string
    {
        return "secret:{$token}";
    }

    public function getDecryptor(): Closure
    {
        return $this->decrypt(...);
    }
}

test('private method and first class callable', function () {
    $service = new FccSecretService();
    $decrypt = $service->getDecryptor();
    expect($decrypt('hoge'))->toBe('secret:hoge');
    expect(fn() => $service->decrypt('hoge'))->toThrow(Error::class);
});