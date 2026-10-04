<?php

declare(strict_types=1);

test('Fiber life-cycle', function () {
    $fiber = new Fiber(function () {
        echo "A";
        $input = Fiber::suspend('PAUSED_1');
        assert(is_string($input));
        echo "B:{$input}";
        return 'ALL_FINISHED';
    });

    expect($fiber->isStarted())->toBeFalse();
    $val1 = $fiber->start();
    expect($val1)->toBe('PAUSED_1');
    expect($fiber->isStarted())->toBeTrue();
    expect($fiber->isSuspended())->toBeTrue();
    $val2 = $fiber->resume('RESUMED_DATA');
    expect($val2)->toBeNull();
    expect($fiber->isSuspended())->toBeFalse();
    expect($fiber->isTerminated())->toBeTrue();
    $final = $fiber->getReturn();
    expect($final)->toBe('ALL_FINISHED');
});

class DeepCaller
{
    public function methodA(): void
    {
        $this->methodB();
    }

    private function methodB(): void
    {
        $this->methodC();
    }

    private function methodC(): mixed
    {
        return Fiber::suspend('FROM_DEPTH');
    }
}

test('Fiber is Stackful', function () {
    $caller = new DeepCaller();
    $fiber = new Fiber(function () use ($caller) {
        $caller->methodA();
    });

    $val1 = $fiber->start();
    expect($val1)->toBe('FROM_DEPTH');
    expect($fiber->isSuspended())->toBeTrue();
});

test('scheduler', function () {
    $executionLogs = [];

    // タスク 1: 3ステップの処理
    $task1 = new Fiber(function () use (&$executionLogs) {
        $executionLogs[] = 'Task1-Step1';
        Fiber::suspend();
        $executionLogs[] = 'Task1-Step2';
        Fiber::suspend();
        $executionLogs[] = 'Task1-Step3';
    });

    // タスク 2: 2ステップの処理
    $task2 = new Fiber(function () use (&$executionLogs) {
        $executionLogs[] = 'Task2-Step1';
        Fiber::suspend();
        $executionLogs[] = 'Task2-Step2';
    });

    // スケジューラ（ラウンドロビン方式のイベントループ）
    $queue = [$task1, $task2];
    while (!empty($queue)) {
        $fiber = array_shift($queue);
        if (!$fiber->isStarted()) {
            $fiber->start();
        } elseif ($fiber->isSuspended()) {
            $fiber->resume();
        }
        // まだ終わっていないならキューの最後尾へ戻す
        if (!$fiber->isTerminated()) {
            $queue[] = $fiber;
        }
    }

    expect($executionLogs)->toBe([
        'Task1-Step1',
        'Task2-Step1',
        'Task1-Step2',
        'Task2-Step2',
        'Task1-Step3',
    ]);
});