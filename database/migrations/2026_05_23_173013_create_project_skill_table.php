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
      Schema::create('project_skill', function (Blueprint $table) {
    $table->id();

    $table->foreignId('userproject_id')->constrained('user_projects')->cascadeOnDelete();
    $table->foreignId('skill_id')->constrained('skills')->cascadeOnDelete();

    $table->timestamps();

    $table->unique(['userproject_id', 'skill_id']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_skill');
    }
};
