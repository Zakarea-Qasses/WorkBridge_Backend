<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')
            ->constrained('wallets')
            ->cascadeOnDelete();

        $table->foreignId('user_id')
            ->nullable()
            ->constrained('users')
            ->nullOnDelete();

        $table->enum('type', [
            'deposit',
            'withdraw',
            'transfer_to_admin'
        ]);

        $table->decimal('amount', 15, 2);

        $table->decimal('balance_before', 15, 2);

        $table->decimal('balance_after', 15, 2);

        $table->string('description')
            ->nullable();

            $table->timestamps();

         $table->enum('direction', ['credit', 'debit'])
                ->default('credit')
                ->after('type');

            $table->enum('status', ['completed', 'failed', 'pending'])
                ->default('completed')
                ->after('balance_after');
      });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
