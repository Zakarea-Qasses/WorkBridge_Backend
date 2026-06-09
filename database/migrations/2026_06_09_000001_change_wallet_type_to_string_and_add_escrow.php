<?php

use App\Models\Wallet;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE wallets MODIFY type VARCHAR(255) NOT NULL DEFAULT 'user'");
        }

        Wallet::firstOrCreate(
            ['type' => 'escrow'],
            ['balance' => 0, 'is_active' => true]
        );

        Wallet::firstOrCreate(
            ['type' => 'admin'],
            ['balance' => 0, 'is_active' => true]
        );
    }

    public function down(): void
    {
        Wallet::where('type', 'escrow')->delete();
    }
};
