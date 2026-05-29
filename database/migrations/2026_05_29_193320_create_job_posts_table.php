<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_posts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete();

            $table->string('title');
            $table->text('description');

            // تجاهل الموقع حاليا
            $table->enum('location_type', ['remote', 'on_site', 'hybrid'])->nullable();

            $table->foreignId('city_id')
                ->nullable()
                ->constrained('cities')
                ->nullOnDelete();

            $table->decimal('salary', 12, 2)->nullable();

            // active = منشور، paused = موقوف، closed = مغلق
            $table->enum('status', ['active', 'paused', 'closed'])
                ->default('active');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_posts');
    }
};