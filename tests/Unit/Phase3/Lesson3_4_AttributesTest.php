<?php

declare(strict_types=1);

#[Attribute(Attribute::TARGET_CLASS)]
class AttrRoute
{
    public function __construct(
        public string $path,
    ) {}
}

#[AttrRoute(path: "/api/users")]
class AttrController {}

test('Lazy Initialization and ReflectionAttributes', function () {
    $refClass = new ReflectionClass(AttrController::class);
    $attributes = $refClass->getAttributes(AttrRoute::class);

    expect($attributes)->toHaveCount(1)
        ->and($attributes[0])->toBeInstanceOf(ReflectionAttribute::class);
    
    // 属性クラス自体が初期化される
    $route = $attributes[0]->newInstance();
    expect($route)->toBeInstanceOf(AttrRoute::class)
        ->and($route->path)->toBe("/api/users");
});

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class AttrMiddleware
{
    public function __construct(
        public string $methodName
    ) {}
}

class AttrControllerWithMiddleware
{
    #[AttrMiddleware(methodName: "GET")]
    #[AttrMiddleware(methodName: "POST")]
    public function index(): void
    {
    }
}

test('Repeatable Attributes', function () {
    $refClass = new ReflectionClass(AttrControllerWithMiddleware::class);
    $attributes = $refClass->getMethod("index")->getAttributes(AttrMiddleware::class);
    expect($attributes)->toHaveCount(2)
        ->and($attributes[0])->toBeInstanceOf(ReflectionAttribute::class)
        ->and($attributes[0]->newInstance()->methodName)->toBe("GET")
        ->and($attributes[1])->toBeInstanceOf(ReflectionAttribute::class)
        ->and($attributes[1]->newInstance()->methodName)->toBe("POST");
});

#[Attribute(Attribute::TARGET_CLASS)]
class AttrSingle {}

#[AttrSingle]
#[AttrSingle]
class AttrTestClass {}

test('attribute without IS_REPEATABLE', function () {
    $refClass = new ReflectionClass(AttrTestClass::class);
    $attributes = $refClass->getAttributes(AttrSingle::class);
    expect($attributes)->toHaveCount(2);
    expect(fn() => $attributes[0]->newInstance())->toThrow(Error::class);
});