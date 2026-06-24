<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin=User::updateOrCreate(
            ['email' => 'workbridgeAdmin@gmail.com'],
            [
                'name' => 'Work Bridge Admin',
                'password' => 'password@123',
                'role' => 'admin',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        Wallet::updateOrCreate(
            ['type'=>'admin'],
            ['balance'=>0, 'is_active'=>true]
        );

        Wallet::updateOrCreate(
            ['type'=>'escrow'],
            ['balance'=>0, 'is_active'=>true]
        );
    }
}
