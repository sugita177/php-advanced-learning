<?php

test('PHP 8 loose comparisons', function () {
    expect(0 == '0')->toBeTrue();
    expect(0 == '0.0')->toBeTrue();
    expect(0 == "foo")->toBeFalse();
    expect(0 == "")->toBeFalse();
    expect(42 == "  42  ")->toBeTrue();
    expect("42" == "42.0")->toBeTrue();
});

test('in_array loose comparison trap', function () {
    // 第3引数を省略またはfalseの時、PHP 7の場合はtrueになってしまう（脆弱性）
    expect(in_array(0, ['pending', 'approved', 'rejected']))->toBeFalse();
    expect(in_array(0, ['pending', 'approved', 'rejected'], true))->toBeFalse();
});
