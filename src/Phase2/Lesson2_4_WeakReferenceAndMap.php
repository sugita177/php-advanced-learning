<?php

declare(strict_types=1);

/**
 * Lesson 2.4: WeakReference と WeakMap によるメモリリーク抑止
 * 
 * 実行方法:
 *   make run FILE=src/Phase2/Lesson2_4_WeakReferenceAndMap.php
 */

echo "=== 1. WeakReference（弱参照）の基本挙動 ===" . PHP_EOL;

$user = new stdClass();
$user->name = 'Alice';

$weakRef = WeakReference::create($user);

printf("オブジェクト生存中: \$weakRef->get()->name = %s\n", $weakRef->get()?->name);

unset($user); // オブジェクト本体を破棄

printf("オブジェクト破棄後: \$weakRef->get() === %s (安全に null を返す)\n", var_export($weakRef->get(), true));

echo PHP_EOL . "=== 2. 親子ノード（Parent-Child）における循環参照の解消 ===" . PHP_EOL;

class ParentNode
{
    public ?ChildNode $child = null;

    public function __construct(public string $name)
    {
        printf("Created:   Parent(%s)\n", $this->name);
    }

    public function __destruct()
    {
        printf("Destroyed: Parent(%s)\n", $this->name);
    }
}

class ChildNode
{
    // 子から親へは「弱参照」で保持することで循環参照を断ち切る
    public ?WeakReference $parent = null;

    public function __construct(public string $name)
    {
        printf("Created:   Child(%s)\n", $this->name);
    }

    public function __destruct()
    {
        printf("Destroyed: Child(%s)\n", $this->name);
    }
}

$parent = new ParentNode('Window');
$child  = new ChildNode('Button');

$parent->child = $child;                      // 親 -> 子 は強参照
$child->parent = WeakReference::create($parent); // 子 -> 親 は弱参照

echo "--- unset(\$parent) を実行します ---" . PHP_EOL;
unset($parent); // 子が弱参照しか持たないため、GC 待ちにならず即座に親が解体される！

printf("親が破棄された後の子から見た親: %s\n", var_export($child->parent->get(), true));

echo "--- unset(\$child) を実行します ---" . PHP_EOL;
unset($child);

echo PHP_EOL . "=== 3. WeakMap による自動蒸発キャッシュ ===" . PHP_EOL;

$map = new WeakMap();

$entity1 = new stdClass();
$entity2 = new stdClass();

$map[$entity1] = ['permission' => 'read'];
$map[$entity2] = ['permission' => 'write'];

printf("エントリ登録後: count(\$map) = %d\n", count($map));

unset($entity1); // キーとなっていた $entity1 を破棄！

printf("unset(\$entity1) 直後: count(\$map) = %d (該当エントリが自動消滅！)\n", count($map));
printf("\$entity2 のエントリ残存: %s\n", $map->offsetExists($entity2) ? 'YES' : 'NO');
