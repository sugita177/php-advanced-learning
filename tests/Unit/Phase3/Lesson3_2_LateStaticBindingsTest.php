<?php

declare(strict_types=1);

class LsBaseModel
{
    public static function getClassName(): string
    {
        return 'BaseModel';
    }

    public static function testSelf(): string
    {
        return self::getClassName();
    }

    public static function testStatic(): string
    {
        return static::getClassName();
    }
}

class LsUser extends LsBaseModel
{
    public static function getClassName(): string
    {
        return 'User';
    }

    public static function forwardParent(): string
    {
        return parent::testStatic();
    }
}

class LsAdminUser extends LsUser
{
    public static function getClassName(): string
    {
        return 'AdminUser';
    }
}

test('late static bindings: self:: vs static::', function () {
    expect(LsUser::testSelf())->toBe('BaseModel');
    expect(LsUser::testStatic())->toBe('User');

    expect(LsAdminUser::testSelf())->toBe('BaseModel');
    expect(LsAdminUser::testStatic())->toBe('AdminUser');

    expect(LsUser::forwardParent())->toBe('User');
    expect(LsAdminUser::forwardParent())->toBe('AdminUser');
});

abstract class LsModel
{
    public static function create(): static
    {
        return new static();
    }
}

class LsCustomer extends LsModel
{
    public static function createBySelf(): self
    {
        return new self();
    }
}

class LsSpecialCustomer extends LsCustomer
{
}

test('late static bindings: create() vs createBySelf()', function () {
    expect(LsCustomer::create())->toBeInstanceOf(LsCustomer::class);
    expect(LsCustomer::createBySelf())->toBeInstanceOf(LsCustomer::class);

    expect(LsSpecialCustomer::create())->toBeInstanceOf(LsSpecialCustomer::class);
    expect(LsSpecialCustomer::createBySelf())->toBeInstanceOf(LsCustomer::class);
});