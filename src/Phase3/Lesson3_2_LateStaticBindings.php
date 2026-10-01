<?php

declare(strict_types=1);

/**
 * Lesson 3.2: 遅延静的結合 (Late Static Bindings) の内部探索順序とフォワーディングコール
 * 
 * 実行方法:
 *   make run FILE=src/Phase3/Lesson3_2_LateStaticBindings.php
 */

class DemoBaseModel
{
    public static function getClassName(): string
    {
        return 'DemoBaseModel';
    }

    public static function testSelf(): string
    {
        return self::getClassName(); // コンパイル時にこのクラスに固定束縛
    }

    public static function testStatic(): string
    {
        return static::getClassName(); // 実行時の呼び出し元クラス（called class）を解決
    }
}

class DemoUser extends DemoBaseModel
{
    public static function getClassName(): string
    {
        return 'DemoUser';
    }

    public static function forwardParent(): string
    {
        // parent:: はフォワーディングコール（元の呼び出し元クラス情報を保持して親へ転送）
        return parent::testStatic();
    }

    public static function forwardParentSelf(): string
    {
        return parent::testSelf();
    }
}

class DemoAdminUser extends DemoUser
{
    public static function getClassName(): string
    {
        return 'DemoAdminUser';
    }
}

echo "=== 1. self:: vs static:: の静的解決の違い ===" . PHP_EOL;

printf("DemoUser::testSelf()   => %s (self は宣言場所の DemoBaseModel を解決)\n", DemoUser::testSelf());
printf("DemoUser::testStatic() => %s (static は呼び出し元の DemoUser を解決)\n", DemoUser::testStatic());

echo PHP_EOL . "=== 2. parent:: 経由のフォワーディングコール（呼び出し情報の転送） ===" . PHP_EOL;

printf("DemoAdminUser::forwardParent()     => %s (called class: DemoAdminUser を保持して親へ転送！)\n", DemoAdminUser::forwardParent());
printf("DemoAdminUser::forwardParentSelf() => %s (親の self:: はどこから呼ばれても DemoBaseModel 固定)\n", DemoAdminUser::forwardParentSelf());

echo PHP_EOL . "=== 3. 実務応用：new static() vs new self() によるファクトリメソッド ===" . PHP_EOL;

abstract class Entity
{
    public static function create(): static
    {
        return new static(); // 呼び出し元サブクラスのインスタンスを生成
    }
}

class Customer extends Entity
{
    public static function createBySelf(): self
    {
        return new self(); // このクラス (Customer) に固定される
    }
}

class SpecialCustomer extends Customer {}

$inst1 = SpecialCustomer::create();
$inst2 = SpecialCustomer::createBySelf();

printf("SpecialCustomer::create()       => %s (ポリモーフィズムが正常に機能)\n", get_class($inst1));
printf("SpecialCustomer::createBySelf() => %s (self:: により親の Customer に固定されてしまう！)\n", get_class($inst2));
