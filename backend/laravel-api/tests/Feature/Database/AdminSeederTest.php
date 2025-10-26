<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('AdminSeeder creates super_admin account', function () {
    $this->seed(\Database\Seeders\AdminSeeder::class);

    $admin = \App\Models\Admin::where('email', 'admin@example.com')->first();

    expect($admin)->not->toBeNull()
        ->and($admin->name)->toBe('Admin User')
        ->and($admin->role)->toBe('super_admin')
        ->and($admin->is_active)->toBeTrue()
        ->and(Hash::check('password', $admin->password))->toBeTrue();
});

test('AdminSeeder creates staff account', function () {
    $this->seed(\Database\Seeders\AdminSeeder::class);

    $staff = \App\Models\Admin::where('email', 'staff@example.com')->first();

    expect($staff)->not->toBeNull()
        ->and($staff->name)->toBe('Staff User')
        ->and($staff->role)->toBe('admin')
        ->and($staff->is_active)->toBeTrue()
        ->and(Hash::check('password', $staff->password))->toBeTrue();
});

test('AdminSeeder only runs once with same data', function () {
    $this->seed(\Database\Seeders\AdminSeeder::class);
    $count1 = \App\Models\Admin::count();

    // Seederを再実行しても重複しない
    $this->seed(\Database\Seeders\AdminSeeder::class);
    $count2 = \App\Models\Admin::count();

    expect($count1)->toBe(2)
        ->and($count2)->toBe(2);
});
