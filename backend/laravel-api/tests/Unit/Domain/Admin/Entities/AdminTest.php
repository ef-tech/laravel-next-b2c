<?php

declare(strict_types=1);

use Ddd\Domain\Admin\Entities\Admin;
use Ddd\Domain\Admin\ValueObjects\AdminId;
use Ddd\Domain\Admin\ValueObjects\AdminRole;
use Ddd\Domain\Admin\ValueObjects\Email;

test('Admin Entity can be created with valid data', function () {
    $admin = new Admin(
        id: AdminId::fromInt(123),
        email: new Email('admin@example.com'),
        name: 'Admin User',
        role: new AdminRole('super_admin'),
        isActive: true
    );

    expect($admin->id->value())->toBe(123)
        ->and($admin->email->value)->toBe('admin@example.com')
        ->and($admin->name)->toBe('Admin User')
        ->and($admin->role->value)->toBe('super_admin')
        ->and($admin->isActive)->toBeTrue();
});

test('canAccessAdminPanel returns true when is_active is true', function () {
    $admin = new Admin(
        id: AdminId::fromInt(123),
        email: new Email('admin@example.com'),
        name: 'Admin User',
        role: new AdminRole('admin'),
        isActive: true
    );

    expect($admin->canAccessAdminPanel())->toBeTrue();
});

test('canAccessAdminPanel returns false when is_active is false', function () {
    $admin = new Admin(
        id: AdminId::fromInt(123),
        email: new Email('admin@example.com'),
        name: 'Admin User',
        role: new AdminRole('admin'),
        isActive: false
    );

    expect($admin->canAccessAdminPanel())->toBeFalse();
});

test('equals returns true for same id', function () {
    $admin1 = new Admin(
        id: AdminId::fromInt(123),
        email: new Email('admin1@example.com'),
        name: 'Admin 1',
        role: new AdminRole('admin'),
        isActive: true
    );

    $admin2 = new Admin(
        id: AdminId::fromInt(123),
        email: new Email('admin2@example.com'),
        name: 'Admin 2',
        role: new AdminRole('super_admin'),
        isActive: false
    );

    expect($admin1->equals($admin2))->toBeTrue();
});

test('equals returns false for different id', function () {
    $admin1 = new Admin(
        id: AdminId::fromInt(123),
        email: new Email('admin@example.com'),
        name: 'Admin User',
        role: new AdminRole('admin'),
        isActive: true
    );

    $admin2 = new Admin(
        id: AdminId::fromInt(456),
        email: new Email('admin@example.com'),
        name: 'Admin User',
        role: new AdminRole('admin'),
        isActive: true
    );

    expect($admin1->equals($admin2))->toBeFalse();
});
