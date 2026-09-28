<?php

class Node {
    public ?Node $next = null;
    public static array $messages = [];
    public function __construct(public string $name){}
    public function __destruct()
    {
        self::$messages[] = "Destroyed: {$this->name}\n";
    }
}

test('circular references and gc', function (){
    // 通常のオブジェクト破棄
    $nodeA = new Node('A');
    unset($nodeA);
    Node::$messages[] = "Point 1\n";

    // シナリオ B: 循環参照の発生
    $nodeB1 = new Node('B1');
    $nodeB2 = new Node('B2');
    $nodeB1->next = $nodeB2;
    $nodeB2->next = $nodeB1; // ここで B1 と B2 が相互参照（循環参照）
    unset($nodeB1, $nodeB2); // 外側の変数を破棄
    Node::$messages[] = "Point 2\n";

    // 1. GC 起動前：まだ B1 も B2 も破棄されていないことを証明！
    expect(Node::$messages)->not->toContain("Destroyed: B1\n")
                           ->not->toContain("Destroyed: B2\n");

    // 2. GC を起動
    expect(gc_collect_cycles())->toBeGreaterThan(0); // ガベージコレクタを強制起動
    Node::$messages[] = "Point 3\n";

    // 3. GC 起動後：ここで初めて両方が入ったことを証明！
    expect(Node::$messages)->toContain("Destroyed: B1\n")
                           ->toContain("Destroyed: B2\n");

    // GCにより、Nodeの破棄順序は実行時により異なるので、2通りのパターンを想定
    $pattern1 = [
        "Destroyed: A\n", "Point 1\n", "Point 2\n",
        "Destroyed: B1\n", "Destroyed: B2\n", "Point 3\n",
    ];
    $pattern2 = [
        "Destroyed: A\n", "Point 1\n", "Point 2\n",
        "Destroyed: B2\n", "Destroyed: B1\n", "Point 3\n",
    ];

    // pattern1 または pattern2 のどちらかであれば合格
    expect(Node::$messages)->toBeIn([$pattern1, $pattern2]);
});