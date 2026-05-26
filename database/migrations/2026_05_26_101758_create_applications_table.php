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
       Schema::create('applications', function (Blueprint $table) {
    $table->id();

    $table->foreignId('user_project_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();

    $table->decimal('price', 10, 2);
    $table->integer('duration_days');
    $table->text('description');

    $table->enum('status', ['pending', 'accepted', 'rejected'])
          ->default('pending');

    $table->timestamps();

    $table->unique(['user_project_id', 'user_id']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
