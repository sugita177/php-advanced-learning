<?php

declare(strict_types=1);

class EvenFilterIterator extends FilterIterator
{
    public function accept(): bool
    {
        return $this->current() % 2 === 0;
    }
}

test('IteratorIterator, FilterIterator, LimitIterator', function () {
    $numbers = new ArrayIterator([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);
    $evenIterator = new EvenFilterIterator($numbers);
    $pipeline = new LimitIterator($evenIterator, offset: 1, limit: 2);
    $result = iterator_to_array($pipeline, preserve_keys: false);
    expect($result)->toBe([4, 6]);
});

class TrackedEvenFilterIterator extends FilterIterator
{
    public int $callCount = 0;

    public function accept(): bool
    {
        $this->callCount++;
        return $this->current() % 2 === 0;
    }
}

test('Iterator and short circuit evaluation ', function () {
    $trackedIterator = new TrackedEvenFilterIterator(new ArrayIterator([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]));
    expect($trackedIterator->callCount)->toBe(0);
    $pipeline = new LimitIterator($trackedIterator, offset: 1, limit: 2);
    $result = iterator_to_array($pipeline, preserve_keys: false);
    expect($result)->toBe([4, 6]);
    expect($trackedIterator->callCount)->toBe(6 + 2);
});

test('InfiniteIterator with LimitIterator', function () {
    $colors = new ArrayIterator(['red', 'green', 'blue']);
    $infinite = new InfiniteIterator($colors);

    // 無限ループするイテレータから、安全に先頭 7 件だけを切り出す
    $pipeline = new LimitIterator($infinite, offset: 0, limit: 7);
    $result = iterator_to_array($pipeline, preserve_keys: false);

    expect($result)->toBe([
        'red', 'green', 'blue',
        'red', 'green', 'blue',
        'red',
    ]);
});
