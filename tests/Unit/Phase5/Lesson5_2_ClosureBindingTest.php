<?php

declare(strict_types=1);

class CbVault
{
    private string $secret = 'my_secret';
}

$fn = function () {
    return $this->secret;
};

test('$this error due to non context', function () use ($fn)
{
    expect($fn)->toThrow(Error::class);
});

test('scope of bindTo()', function () use ($fn)
{
    $vault = new CbVault();
    $boundNoScope = $fn->bindTo($vault);
    expect(fn() => $boundNoScope())->toThrow(Error::class);

    $boundWithScope = $fn->bindTo($vault, CbVault::class);
    expect($boundWithScope())->toBe('my_secret');
});

test('Closure:call()', function() use ($fn)
{
    $vault = new CbVault();
    $writer = function (string $newSecret) {$this->secret = $newSecret;};
    $writer->call($vault, 'hacked_secret');
    $reader = function () {return $this->secret;};
    expect($reader->call($vault))->toBe('hacked_secret');
});
