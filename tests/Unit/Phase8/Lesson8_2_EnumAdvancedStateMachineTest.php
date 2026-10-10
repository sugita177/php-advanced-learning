<?php

declare(strict_types=1);

interface HasLabelInterface
{
    public function getLabel(): string;
}

trait EnumDescriptionTrait
{
    public function describe(): string
    {
        return "Status: {$this->name}";
    }
}

enum OrderStatus: string implements HasLabelInterface
{
    use EnumDescriptionTrait;

    case Draft = 'draft';
    case Paid = 'paid';
    case Shipped = 'shipped';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => '下書き',
            self::Paid => '決済完了',
            self::Shipped => '発送済み',
            self::Cancelled => 'キャンセル',
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Draft => in_array($next, [self::Paid, self::Cancelled], true),
            self::Paid => in_array($next, [self::Shipped, self::Cancelled], true),
            self::Shipped, self::Cancelled => false,
        };
    }

    public function transitionTo(self $next): self
    {
        if (! $this->canTransitionTo($next)) {
            throw new LogicException("Cannot transition from {$this->name} to {$next->name}");
        }

        return $next;
    }
}

test('normal case of state transition', function () {
    $orderStatus = OrderStatus::Draft;
    expect($orderStatus)->toBe(OrderStatus::Draft);
    $orderStatus = $orderStatus->transitionTo(OrderStatus::Paid);
    expect($orderStatus)->toBe(OrderStatus::Paid);
    $orderStatus = $orderStatus->transitionTo(OrderStatus::Shipped);
    expect($orderStatus)->toBe(OrderStatus::Shipped);
});

test('abnormal case of state transition', function () {
    $orderStatus = OrderStatus::Draft;
    expect(fn() => $orderStatus->transitionTo(OrderStatus::Shipped))->toThrow(LogicException::class);
    $orderStatus = $orderStatus->transitionTo(OrderStatus::Cancelled);
    expect($orderStatus)->toBe(OrderStatus::Cancelled);
    expect(fn() => $orderStatus->transitionTo(OrderStatus::Paid))->toThrow(LogicException::class);
});

test('enum with trait and interface', function () {
    $orderStatus = OrderStatus::Draft;
    expect($orderStatus->getLabel())->toBe('下書き');
    expect($orderStatus->describe())->toBe('Status: Draft');
    expect($orderStatus)->toBeInstanceOf(HasLabelInterface::class);
});
