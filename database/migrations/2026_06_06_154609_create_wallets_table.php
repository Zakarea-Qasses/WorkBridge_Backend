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
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
            ->nullable()
            ->unique()
            ->constrained('users')
            ->cascadeOnDelete();

        $table->string('type')
            ->default('user');

        $table->decimal('balance', 15, 2)
            ->default(0);

        $table->boolean('is_active')
            ->default(true);

        $table->timestamps();
    });
}
            
        
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
