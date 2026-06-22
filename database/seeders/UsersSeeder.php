<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $User=User::updateOrCreate(
            ['email' => 'personal@gmail.com'],
            [
                'name' => 'Personal User',
                'password' => 'password@123',
                'role' => 'personal',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        $User=User::updateOrCreate(
            ['email' => 'company@gmail.com'],
            [
                'name' => 'Company User',
                'password' => 'password@123',
                'role' => 'company',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        $User=User::updateOrCreate(
            ['email' => 'personal2@gmail.com'],
            [
                'name' => 'Personal User 2',
                'password' => 'password@123',
                'role' => 'personal',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

          $User=User::updateOrCreate(
            ['email' => 'company2@gmail.com'],
            [
                'name' => 'Company User 2',
                'password' => 'password@123',
                'role' => 'company',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );
    }
}
