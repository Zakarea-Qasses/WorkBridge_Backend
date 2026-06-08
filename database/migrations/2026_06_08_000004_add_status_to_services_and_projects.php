<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (! Schema::hasColumn('services', 'status')) {
                $table->string('status')->default('active')->after('delivery_days');
            }
        });

        Schema::table('user_projects', function (Blueprint $table) {
            if (! Schema::hasColumn('user_projects', 'status')) {
                $table->string('status')->default('active')->after('duration_days');
            }
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (Schema::hasColumn('services', 'status')) {
                $table->dropColumn('status');
            }
        });

        Schema::table('user_projects', function (Blueprint $table) {
            if (Schema::hasColumn('user_projects', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
