<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallet_requests', function (Blueprint $table) {
            $table->string('deposit_receipt_path')->nullable()->after('payment_note');
        });
    }

    public function down(): void
    {
        Schema::table('wallet_requests', function (Blueprint $table) {
            $table->dropColumn('deposit_receipt_path');
        });
    }
};
