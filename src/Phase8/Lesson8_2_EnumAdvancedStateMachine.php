<?php

declare(strict_types=1);

/**
 * Lesson 8.2: Enum へのインターフェース・トレイト・メソッド実装と高度なステートマシン
 * 
 * 実行方法:
 *   make run FILE=src/Phase8/Lesson8_2_EnumAdvancedStateMachine.php
 */

interface RenderableBadgeInterface
{
    public function getLabel(): string;
    public function getColorCode(): string;
}

trait EnumLogTrait
{
    public function logMessage(string $action): string
    {
        return "[AuditLog] Action '{$action}' applied to status '{$this->name}' (value: {$this->value})";
    }
}

enum OrderState: string implements RenderableBadgeInterface
{
    use EnumLogTrait;

    case PendingPayment = 'pending_payment';
    case Paid           = 'paid';
    case Shipped        = 'shipped';
    case Cancelled      = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::PendingPayment => '支払い待ち',
            self::Paid           => '入金確認済み',
            self::Shipped        => '発送完了',
            self::Cancelled      => '注文キャンセル',
        };
    }

    public function getColorCode(): string
    {
        return match ($this) {
            self::PendingPayment => '#f39c12', // オレンジ
            self::Paid           => '#3498db', // 青
            self::Shipped        => '#2ecc71', // 緑
            self::Cancelled      => '#e74c3c', // 赤
        };
    }

    /**
     * 遷移可能かどうかの判定 (ステートマシンのルール定義)
     */
    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::PendingPayment => in_array($next, [self::Paid, self::Cancelled], true),
            self::Paid           => in_array($next, [self::Shipped, self::Cancelled], true),
            self::Shipped,
            self::Cancelled      => false, // 終了状態
        };
    }

    /**
     * ステート遷移の実行
     */
    public function transitionTo(self $next): self
    {
        if (! $this->canTransitionTo($next)) {
            throw new LogicException("無効な状態遷移です: {$this->getLabel()} ({$this->name}) から {$next->getLabel()} ({$next->name}) へは変更できません。");
        }

        return $next;
    }

    /**
     * キャンセル可能かどうか (Tell, Don't Ask 原則)
     */
    public function isCancellable(): bool
    {
        return $this->canTransitionTo(self::Cancelled);
    }
}

echo "=== 1. インターフェースとトレイトの振る舞い検証 ===" . PHP_EOL;

$state = OrderState::PendingPayment;
printf("初期状態: %s (ラベル: %s, カラー: %s)\n", $state->name, $state->getLabel(), $state->getColorCode());
printf("トレイトによる監査ログ: %s\n", $state->logMessage('ORDER_CREATED'));
$interfaces = class_implements(OrderState::class) ?: [];
printf("RenderableBadgeInterface を実装しているか: %s\n",
    isset($interfaces[RenderableBadgeInterface::class]) ? 'true' : 'false'
);

echo PHP_EOL . "=== 2. ステートマシンの正常遷移フロー ===" . PHP_EOL;

printf("注文作成時: %s (キャンセル可能か: %s)\n", $state->getLabel(), $state->isCancellable() ? 'はい' : 'いいえ');

$state = $state->transitionTo(OrderState::Paid);
printf("入金完了後: %s (キャンセル可能か: %s)\n", $state->getLabel(), $state->isCancellable() ? 'はい' : 'いいえ');

$state = $state->transitionTo(OrderState::Shipped);
printf("発送完了後: %s (キャンセル可能か: %s)\n", $state->getLabel(), $state->isCancellable() ? 'はい' : 'いいえ');

echo PHP_EOL . "=== 3. 不正遷移に対する防御的プログラミング (例外発生) ===" . PHP_EOL;

try {
    echo "発送完了状態から入金待ちへ不正に戻そうと試みます...\n";
    $state->transitionTo(OrderState::PendingPayment);
} catch (LogicException $e) {
    printf("[例外検知] %s\n", $e->getMessage());
}

try {
    echo "発送完了状態からキャンセルへ不正に変更しようと試みます...\n";
    $state->transitionTo(OrderState::Cancelled);
} catch (LogicException $e) {
    printf("[例外検知] %s\n", $e->getMessage());
}

echo PHP_EOL . "すべてのステート遷移が型安全かつカプセル化されて完了しました！\n";
