<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            AdminUserSeeder::class,
            LocationSeeder::class,
        ]);
        // User::factory(10)->create();

        $testUser = User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => 'password',
                'role' => 'personal',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        $testUser->profile()->firstOrCreate([
            'user_id' => $testUser->id,
        ]);

        $testUser->wallet()->firstOrCreate([
            'user_id' => $testUser->id,
        ], [
            'type' => 'user',
            'balance' => 0,
            'is_active' => true,
        ]);
    }
}
