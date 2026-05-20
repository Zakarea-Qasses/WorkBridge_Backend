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
        Schema::create('posts', function (Blueprint $table) {

            $table->id();
             $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');


            $table->enum('type', [
                'project',
                'service'
            ]);
            $table->string('title');
            $table->text('description');
           
            //للخدمات
            $table->decimal('price',10,2)
                ->nullable();

            //للمشاريع
            $table->decimal('budget', 10, 2)
                ->nullable();
            
         $table->string('address')
         ->nullable();
            

            $table->enum('status', [
                'draft',
                'published',
                'paused',
                'archived',
                'closed'
            ])->default('draft');
            $table->softDeletes();
            $table->timestamps();
     
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
