<?php

declare(strict_types=1);

/**
 * Lesson 2.3: 循環参照と gc_collect_cycles() の内部挙動・デストラクタの遅延
 * 
 * 実行方法:
 *   make run FILE=src/Phase2/Lesson2_3_CircularReferencesAndGc.php
 */

class DemoNode
{
    public ?DemoNode $next = null;

    public function __construct(public string $name)
    {
        printf("Created:   Node(%s)\n", $this->name);
    }

    public function __destruct()
    {
        printf("Destroyed: Node(%s)\n", $this->name);
    }
}

echo "=== 1. 通常のオブジェクト破棄（即時解放） ===" . PHP_EOL;

$nodeA = new DemoNode('A');
unset($nodeA); // refcount が 0 になるため、即座にデストラクタが走る
echo "--- Point 1: unset(\$nodeA) 完了 ---" . PHP_EOL;

echo PHP_EOL . "=== 2. 循環参照の発生とデストラクタの遅延 ===" . PHP_EOL;

$b1 = new DemoNode('B1');
$b2 = new DemoNode('B2');

// 相互参照を作成
$b1->next = $b2;
$b2->next = $b1;

echo "--- 外側の変数 \$b1, \$b2 を unset() します ---" . PHP_EOL;
unset($b1, $b2);

echo "--- Point 2: unset 完了（しかしデストラクタはまだ走らない！） ---" . PHP_EOL;
printf("GC ルートバッファの循環参照サイクル数: %d\n", gc_status()['roots']);

echo PHP_EOL . "=== 3. gc_collect_cycles() によるガベージコレクタ強制起動 ===" . PHP_EOL;

$collected = gc_collect_cycles(); // サイクル収集アルゴリズムを実行
printf("回収されたオブジェクトサイクル数: %d\n", $collected);

echo "--- Point 3: GC 完了 ---" . PHP_EOL;
