<?php

declare(strict_types=1);

test('SPL FixedArray vs Array', function () {
    $size = 100_000;
    $memoryBeforeMakingFixedArray = memory_get_usage();
    $fixedArray = new SplFixedArray($size);
    for ($i = 0; $i < $size; $i++) {
        $fixedArray[$i] = $i;
    }
    $memoryAfterMakingFixedArray = memory_get_usage();
    $memoryOfFixedArray = $memoryAfterMakingFixedArray - $memoryBeforeMakingFixedArray;
    echo 'memoryOfFixedArray: ' . $memoryOfFixedArray . PHP_EOL;

    $memoryBeforeMakingNormalArray = memory_get_usage();
    $array = [];
    for ($i = 0; $i < $size; $i++) {
        $array[] = $i;
    }
    $memoryAfterMakingNormalArray = memory_get_usage();
    $memoryOfNormalArray = $memoryAfterMakingNormalArray - $memoryBeforeMakingNormalArray;
    echo 'memoryOfNormalArray: ' . $memoryOfNormalArray . PHP_EOL;

    expect($memoryOfNormalArray)->toBeGreaterThan($memoryOfFixedArray * 1.3);
});

test('SplFixedArray and constraint', function () {
    $fixedArray = new SplFixedArray(5);
    expect($fixedArray->getSize())->toBe(5);
    expect(fn() => $fixedArray['invalid'] = 1)->toThrow(TypeError::class);
    expect(fn() => $fixedArray[5] = 100)->toThrow(OutOfBoundsException::class);
    $fixedArray->setSize(10);
    expect($fixedArray->getSize())->toBe(10);
    $fixedArray[5] = 100;
    expect($fixedArray[5])->toBe(100);
    unset($fixedArray[5]);
    expect($fixedArray[5])->toBe(null);
});

test('SplQueue vs array_shift()', function(){
    $size = 10_000;
    $queue = new SplQueue();
    for ($i = 0; $i < $size; $i++) {
        $queue->enqueue($i);
    }
    $start = hrtime(true);
    while (!$queue->isEmpty()) {
        $queue->dequeue();
    }
    $end = hrtime(true);
    $timeOfSplQueue = $end - $start;
    echo 'SplQueue: ' . $timeOfSplQueue . PHP_EOL;

    $array = [];
    for ($i = 0; $i < $size; $i++) {
        $array[] = $i;
    }
    $start = hrtime(true);
    while (!empty($array)) {
        array_shift($array);
    }
    $end = hrtime(true);
    $timeOfArrayShift = $end - $start;
    echo 'array_shift(): ' . $timeOfArrayShift . PHP_EOL;
    
    expect($timeOfArrayShift)->toBeGreaterThan($timeOfSplQueue * 3);
});