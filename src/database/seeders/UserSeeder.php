<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (config('database.default') === 'mysql_test') {
            // テスト環境用のシーディング処理
            User::factory()->create([
                'name' => '管理者テストユーザー',
                'email' => 'test_admin@example.com',
                'password' => Hash::make('test_password'),
                'role' => 'admin',
            ]);
            User::factory(10)->create();
        } else {
            // 開発環境用のシーディング処理
            User::factory()->create([
                'name' => '管理者ユーザー',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]);

            User::factory(10)->create();
        }
    }
}
