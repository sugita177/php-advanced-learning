<?php

declare(strict_types=1);

/**
 * Lesson 3.4: Attributes (属性) を用いた宣言的メタプログラミング
 * 
 * 実行方法:
 *   make run FILE=src/Phase3/Lesson3_4_Attributes.php
 */

echo "=== 1. 属性の宣言とターゲット制約 ===" . PHP_EOL;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Route
{
    public function __construct(
        public string $path,
        public string $method = 'GET',
    ) {}
}

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Middleware
{
    public function __construct(public string $name) {}
}

#[Route('/api/users')]
class UserController
{
    #[Route('/list', 'GET')]
    #[Middleware('auth')]
    #[Middleware('log')] // IS_REPEATABLE なので複数宣言可能
    public function list(): void {}
}

echo "UserController クラスをロードしました（この時点ではインスタンス化も検証も走らない）。" . PHP_EOL;

echo PHP_EOL . "=== 2. 遅延インスタンス化 (Lazy Instantiation) の検証 ===" . PHP_EOL;

$refClass = new ReflectionClass(UserController::class);
$classAttrs = $refClass->getAttributes(Route::class);

printf("取得された属性数: %d\n", count($classAttrs));
printf("属性メタオブジェクトの型: %s\n", get_class($classAttrs[0]));

// newInstance() を呼んで初めてインスタンス化される
$routeInstance = $classAttrs[0]->newInstance();
printf("newInstance() 後の型: %s\n", get_class($routeInstance));
printf("Route のプロパティ: path=%s, method=%s\n", $routeInstance->path, $routeInstance->method);

echo PHP_EOL . "=== 3. IS_REPEATABLE な属性の走査 ===" . PHP_EOL;

$refMethod = $refClass->getMethod('list');
$middlewareAttrs = $refMethod->getAttributes(Middleware::class);

printf("list() メソッドに付与された Middleware 属性数: %d\n", count($middlewareAttrs));
foreach ($middlewareAttrs as $attr) {
    $mw = $attr->newInstance();
    printf(" - 適用ミドルウェア: %s\n", $mw->name);
}

echo PHP_EOL . "=== 4. 非リピータブル属性の重複エラー検証（遅延バリデーション） ===" . PHP_EOL;

#[Attribute(Attribute::TARGET_CLASS)]
class SingleOnly {}

#[SingleOnly]
#[SingleOnly]
class DuplicateTest {}

$refDup = new ReflectionClass(DuplicateTest::class);
$dupAttrs = $refDup->getAttributes(SingleOnly::class);
printf("重複付与された SingleOnly 属性数: %d (取得時点ではエラーなし)\n", count($dupAttrs));

try {
    // newInstance() の呼び出しで Zend Engine の重複バリデーションが発火！
    $dupAttrs[0]->newInstance();
} catch (Error $e) {
    printf("[ブロック成功] newInstance() 時に検知されたエラー: %s\n", $e->getMessage());
}
