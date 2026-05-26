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
       Schema::create('service_requests', function (Blueprint $table) {
    $table->id();

    $table->foreignId('service_id')->constrained()->cascadeOnDelete();
    $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();

    $table->string('title');
    $table->text('description');
    $table->text('references')->nullable();
    $table->integer('delivery_days');

    $table->enum('status', ['pending', 'accepted', 'rejected'])
        ->default('pending');

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_requests');
    }
};
