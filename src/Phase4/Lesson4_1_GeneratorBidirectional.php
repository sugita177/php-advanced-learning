<?php

declare(strict_types=1);

/**
 * Lesson 4.1: Generator の双方向通信 (send, throw) と遅延評価・委譲 (yield from)
 * 
 * 実行方法:
 *   make run FILE=src/Phase4/Lesson4_1_GeneratorBidirectional.php
 */

echo "=== 1. 遅延実行 (Lazy Execution) の実証 ===" . PHP_EOL;

function taskRunner(array &$history): Generator
{
    $history[] = 'タスク開始';
    yield 'Step 1';

    $history[] = 'Step 1 完了';
    yield 'Step 2';

    $history[] = '全タスク完了';
}

$history = [];
$gen = taskRunner($history);
printf("呼び出し直後のログ件数: %d (関数本体はまだ 1 行も走っていない)\n", count($history));

printf("1回目の current(): %s\n", $gen->current());
printf("current() 後のログ: %s\n", implode(' -> ', $history));

echo PHP_EOL . "=== 2. send() による双方向値注入 (コルーチン) ===" . PHP_EOL;

function bidirectionalWorker(): Generator
{
    echo "ワーカー: 準備完了、指示を待機します...\n";
    $command1 = yield 'READY';
    echo "ワーカー: 受信した指示 1 => '{$command1}'\n";

    $command2 = yield 'PROCESSING';
    echo "ワーカー: 受信した指示 2 => '{$command2}'\n";

    return 'ALL_DONE';
}

$worker = bidirectionalWorker();
$status1 = $worker->current(); // READY まで進む
printf("メイン: ワーカー状態 => %s\n", $status1);

$status2 = $worker->send('FETCH_DATA'); // 指示 1 を注入し、次の yield へ
printf("メイン: ワーカー状態 => %s\n", $status2);

$worker->send('SAVE_DATABASE'); // 指示 2 を注入

// PHP 7.0: ジェネレータの戻り値 (return) の取得
printf("ワーカー終了後の getReturn(): %s\n", $worker->getReturn());

echo PHP_EOL . "=== 3. throw() による例外注入とリカバリ ===" . PHP_EOL;

function resilientService(): Generator
{
    try {
        echo "サービス: 外部APIと通信中...\n";
        yield 'WAITING_RESPONSE';
    } catch (RuntimeException $e) {
        echo "サービス: 例外検知 ('{$e->getMessage()}')。バックアップサーバーへ切り替えます。\n";
        yield 'FALLBACK_SUCCESS';
    }

    yield 'NORMAL_FINISH';
}

$service = resilientService();
$service->current();
// 外側から障害（例外）をサービス内部へ投げ込む
$recoveryStatus = $service->throw(new RuntimeException('API Connection Timeout'));
printf("リカバリ結果: %s\n", $recoveryStatus);

$service->next();
printf("最終状態: %s (ジェネレータは死なずに続行成功)\n", $service->current());

echo PHP_EOL . "=== 4. yield from によるジェネレータの委譲 (Delegation) ===" . PHP_EOL;

function subGenerator(): Generator
{
    yield 'Sub-1';
    yield 'Sub-2';
}

function mainGenerator(): Generator
{
    yield 'Main-Start';
    yield from subGenerator(); // サブジェネレータの出力をそのまま透過的に中継
    yield 'Main-End';
}

foreach (mainGenerator() as $value) {
    printf("委譲ストリーム値: %s\n", $value);
}
