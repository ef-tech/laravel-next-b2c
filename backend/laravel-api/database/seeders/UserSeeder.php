<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // E2E テスト用 User アカウント
        User::firstOrCreate(
            ['email' => 'user@example.com'],
            [
                'name' => 'User Account',
                'password' => Hash::make('password'),
            ]
        );

        // 追加のテスト用 User アカウント
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
            ]
        );
    }
}
