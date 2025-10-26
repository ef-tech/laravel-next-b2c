<?php

declare(strict_types=1);

use Ddd\Domain\Admin\ValueObjects\AdminRole;

test('AdminRole can be created with "admin" value', function () {
    $role = new AdminRole('admin');

    expect($role->value)->toBe('admin');
});

test('AdminRole can be created with "super_admin" value', function () {
    $role = new AdminRole('super_admin');

    expect($role->value)->toBe('super_admin');
});

test('AdminRole throws exception for invalid role', function () {
    new AdminRole('invalid_role');
})->throws(InvalidArgumentException::class, 'Invalid admin role: invalid_role');

test('isSuperAdmin returns true for super_admin', function () {
    $role = new AdminRole('super_admin');

    expect($role->isSuperAdmin())->toBeTrue();
});

test('isSuperAdmin returns false for admin', function () {
    $role = new AdminRole('admin');

    expect($role->isSuperAdmin())->toBeFalse();
});

test('equals returns true for same value', function () {
    $role1 = new AdminRole('admin');
    $role2 = new AdminRole('admin');

    expect($role1->equals($role2))->toBeTrue();
});

test('equals returns false for different value', function () {
    $role1 = new AdminRole('admin');
    $role2 = new AdminRole('super_admin');

    expect($role1->equals($role2))->toBeFalse();
});
