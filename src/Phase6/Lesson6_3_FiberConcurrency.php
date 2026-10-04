<?php

declare(strict_types=1);

/**
 * Lesson 6.3: Fiber を使った協調的マルチタスクとスケジューラ
 * 
 * 実行方法:
 *   make run FILE=src/Phase6/Lesson6_3_FiberConcurrency.php
 */

echo "=== 1. Fiber のライフサイクルと双方向データ受け渡し ===" . PHP_EOL;

$fiber = new Fiber(function (string $name): string {
    echo "  [Fiber内部] 開始: ようこそ {$name} さん\n";
    
    // 値を呼び出し元へ渡しつつ一時停止 (suspend)
    $resumeInput = Fiber::suspend("タスクA: 中間成果物");
    assert(is_string($resumeInput));
    
    // resume() で渡された値を受け取って再開
    echo "  [Fiber内部] 再開: 呼び出し元から '{$resumeInput}' を受信しました\n";
    
    return "タスクA: 完了結果";
});

echo "[Main] Fiber::start('Alice') を呼び出します\n";
$yielded = $fiber->start('Alice');
assert(is_string($yielded));
printf("[Main] start() の戻り値 (suspend された値): '%s'\n", $yielded);
printf("[Main] 状態確認 -> started: %s, suspended: %s, terminated: %s\n",
    $fiber->isStarted() ? 'true' : 'false',
    $fiber->isSuspended() ? 'true' : 'false',
    $fiber->isTerminated() ? 'true' : 'false'
);

echo "\n[Main] Fiber::resume('Bobからの返信') を呼び出します\n";
$resumeReturn = $fiber->resume('Bobからの返信');
printf("[Main] resume() の戻り値 (終了したため null): %s\n", var_export($resumeReturn, true));
printf("[Main] 状態確認 -> terminated: %s\n", $fiber->isTerminated() ? 'true' : 'false');
$finalReturn = $fiber->getReturn();
assert(is_string($finalReturn));
printf("[Main] getReturn() による最終戻り値: '%s'\n", $finalReturn);

echo PHP_EOL . "=== 2. スタックフル (Stackful) コルーチンの実証 ===" . PHP_EOL;

class HttpQueryClient
{
    public function fetchUser(int $id): string
    {
        return $this->sendHttpRequest("/users/{$id}");
    }

    private function sendHttpRequest(string $path): string
    {
        return $this->socketWriteAndRead($path);
    }

    private function socketWriteAndRead(string $path): string
    {
        echo "    [Deep Stack] 深いコールスタックの底から Fiber::suspend() を実行！\n";
        // ジェネレータ(yield)と異なり、呼び出し階層のすべてに yield を連鎖させる必要がない！
        $response = Fiber::suspend("HTTP GET {$path}");
        assert(is_string($response));
        return "Response from {$path}: " . $response;
    }
}

$client = new HttpQueryClient();
$fiberIo = new Fiber(function () use ($client): void {
    echo "  [Fiber IO] リクエスト開始\n";
    $result = $client->fetchUser(42);
    echo "  [Fiber IO] 完了: {$result}\n";
});

$request = $fiberIo->start();
assert(is_string($request));
printf("[Main Loop] I/O 待ちを検知: '%s'\n", $request);
echo "[Main Loop] 疑似非同期 I/O 完了後、結果を注入して resume\n";
$fiberIo->resume('{"id": 42, "name": "PHP 8"}');

echo PHP_EOL . "=== 3. ラウンドロビン方式のミニ・イベントループスケジューラ ===" . PHP_EOL;

/**
 * @param array<Fiber<mixed, mixed, mixed, mixed>> $tasks
 */
function runScheduler(array $tasks): void
{
    $queue = $tasks;
    $round = 1;

    while (!empty($queue)) {
        echo "--- Round {$round} ---" . PHP_EOL;
        $activeFibers = count($queue);

        for ($i = 0; $i < $activeFibers; $i++) {
            $current = array_shift($queue);
            assert($current instanceof Fiber);

            if (!$current->isStarted()) {
                $current->start();
            } elseif ($current->isSuspended()) {
                $current->resume();
            }

            // まだ完了していなければキューの末尾へ再登録
            if (!$current->isTerminated()) {
                $queue[] = $current;
            }
        }
        $round++;
    }
}

$taskA = new Fiber(function (): void {
    echo "  [Task 1] Step 1: 認証トークン取得中...\n";
    Fiber::suspend();
    echo "  [Task 1] Step 2: データ取得完了！\n";
});

$taskB = new Fiber(function (): void {
    echo "  [Task 2] Step 1: キャッシュ確認中...\n";
    Fiber::suspend();
    echo "  [Task 2] Step 2: DBクエリ実行中...\n";
    Fiber::suspend();
    echo "  [Task 2] Step 3: レスポンス生成完了！\n";
});

$taskC = new Fiber(function (): void {
    echo "  [Task 3] Step 1: ログ書き込み完了！\n";
});

runScheduler([$taskA, $taskB, $taskC]);

echo PHP_EOL . "すべての Fiber タスクが協調的に完了しました！\n";
