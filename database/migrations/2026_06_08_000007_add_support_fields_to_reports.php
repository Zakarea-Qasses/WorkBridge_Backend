<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            if (! Schema::hasColumn('reports', 'title')) {
                $table->string('title')->nullable()->after('contract_id');
            }

            if (! Schema::hasColumn('reports', 'category')) {
                $table->string('category')->default('complaint')->after('title');
            }

            if (! Schema::hasColumn('reports', 'priority')) {
                $table->string('priority')->default('normal')->after('category');
            }

            if (! Schema::hasColumn('reports', 'attachments')) {
                $table->json('attachments')->nullable()->after('description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            foreach (['title', 'category', 'priority', 'attachments'] as $column) {
                if (Schema::hasColumn('reports', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
