<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Wallet;

class AdminWalletSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Wallet::firstOrCreate(
            ['type' => 'admin'],
            [
                'user_id' => 1,
                'balance' => 0,
                'is_active' => true,
            ]
        );
    }
}
