<?php

test(' Intersection Types and DNF (Disjunctive Normal Form)', function () {

    function processCollection((Countable&Traversable)|null $collection): int {
        if ($collection === null) {
            return 0;
        }
        return count($collection);
    }

    expect(processCollection(new ArrayIterator(['a', 'b', 'c'])))->toBe(3);
    expect(processCollection(null))->toBe(0);
    expect(fn () => processCollection(['a', 'b']))->toThrow(TypeError::class);
    $onlyCountable = new class implements Countable {
        public function count(): int { return 1; }
    };
    expect(fn () => processCollection($onlyCountable))->toThrow(TypeError::class);
});
