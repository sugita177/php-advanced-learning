<?php

declare(strict_types=1);

enum Suit
{
    case Hearts;
    case Diamonds;
    case Clubs;
    case Spades;
}

enum TaskStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Done = 'done';
}

function getStatusLabel(TaskStatus $status): string
{
    // default を書かずに全 Case を網羅 (Exhaustiveness Check)
    return match ($status) {
        TaskStatus::Pending => '保留中',
        TaskStatus::InProgress => '進行中',
        TaskStatus::Done => '完了',
    };
}

test('Pure Enum properties and immutability', function () {
    // 1. シングルトン同一性 (===)
    $h1 = Suit::Hearts;
    $h2 = Suit::Hearts;
    expect($h1)->toBe($h2);
    expect($h1)->not->toBe(Suit::Diamonds);
    
    // 2. clone の禁止 (Error スロー)
    expect(fn() => clone Suit::Hearts)->toThrow(Error::class);
    
    // 3. name プロパティと value の不在 (Reflection / Pure Enum)
    expect(Suit::Hearts->name)->toBe('Hearts');
    $ref = new ReflectionEnum(Suit::class);
    /** @phpstan-ignore method.impossibleType */
    expect($ref->isBacked())->toBeFalse();
    expect($ref->hasProperty('value'))->toBeFalse();
    
    // 4. Suit::cases() の要素数
    expect(Suit::cases())->toHaveCount(4);
    expect(Suit::cases())->toContain(Suit::Hearts, Suit::Diamonds, Suit::Clubs, Suit::Spades);
});

test('Backed Enum parsing and ValueError', function () {
    // 1. TaskStatus::from() の正常系
    expect(TaskStatus::from('pending'))->toBe(TaskStatus::Pending);
    expect(TaskStatus::tryFrom('pending'))->toBe(TaskStatus::Pending);

    // 2. TaskStatus::tryFrom() の null 返却
    expect(TaskStatus::tryFrom('unknown'))->toBeNull();

    // 3. TaskStatus::from('unknown') での ValueError スロー検証
    expect(fn() => TaskStatus::from('unknown'))->toThrow(ValueError::class);
});

test('match expression exhaustiveness', function () {
    // getStatusLabel() の結果を検証
    expect(getStatusLabel(TaskStatus::Pending))->toBe('保留中');
    expect(getStatusLabel(TaskStatus::InProgress))->toBe('進行中');
    expect(getStatusLabel(TaskStatus::Done))->toBe('完了');
});
