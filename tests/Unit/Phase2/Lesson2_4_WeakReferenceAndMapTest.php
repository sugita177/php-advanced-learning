<?php

declare(strict_types=1);

test("WeakReference", function () {
    $user = new stdClass();
    $ref = WeakReference::create($user);

    expect($ref->get())->toBe($user);

    unset($user);

    // $refは弱参照なので、参照先の$userがメモリから破棄されるとgetしてもNullが返る
    expect($ref->get())->toBeNull();
});

class ParentNode
{
    public ?ChildNode $child = null;

    public function __construct()
    {
        echo "ParentNode is constructed" . PHP_EOL;
    }

    public function __destruct()
    {
        echo "ParentNode is destructed" . PHP_EOL;
    }
}

class ChildNode
{
    public ?WeakReference $parent = null;

    public function __construct()
    {
        echo "ChildNode is constructed" . PHP_EOL;
    }

    public function __destruct()
    {
        echo "ChildNode is destructed" . PHP_EOL;
    }
}

test('WeakReference and Parent-Child Reference', function () {
    

    $parent = new ParentNode();
    $child = new ChildNode();

    $parent->child = $child;
    $child->parent = WeakReference::create($parent);

    unset($parent);
    // $childは弱参照として$parentを保持するので、$parentが破棄されたことにより、nullが返ってくる
    expect($child->parent->get())->toBeNull();
    unset($child);
});

test('WeakMap', function () {
    $map = new WeakMap();
    $entity1 = new stdClass();
    $entity2 = new stdClass();

    $map[$entity1] = ['role' => 'admin'];
    $map[$entity2] = ['role' => 'editor'];

    expect(count($map))->toBe(2);

    unset($entity1);
    // $entity1が破棄に伴い、$mapから該当の要素が削除されている
    expect(count($map))->toBe(1);
    expect($map->offsetExists($entity2))->toBeTrue();
});
    