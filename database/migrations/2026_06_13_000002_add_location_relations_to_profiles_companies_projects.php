<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('profiles', 'governorate_id')) {
                $table->foreignId('governorate_id')->nullable()->constrained('governorates')->nullOnDelete();
            }

            if (! Schema::hasColumn('profiles', 'city_id')) {
                $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();
            }
        });

        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'governorate_id')) {
                $table->foreignId('governorate_id')->nullable()->constrained('governorates')->nullOnDelete();
            }

            if (! Schema::hasColumn('companies', 'city_id')) {
                $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();
            }
        });

        Schema::table('user_projects', function (Blueprint $table) {
            if (! Schema::hasColumn('user_projects', 'governorate_id')) {
                $table->foreignId('governorate_id')->nullable()->constrained('governorates')->nullOnDelete();
            }

            if (! Schema::hasColumn('user_projects', 'city_id')) {
                $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            if (Schema::hasColumn('profiles', 'city_id')) {
                $table->dropConstrainedForeignId('city_id');
            }

            if (Schema::hasColumn('profiles', 'governorate_id')) {
                $table->dropConstrainedForeignId('governorate_id');
            }
        });

        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'city_id')) {
                $table->dropConstrainedForeignId('city_id');
            }

            if (Schema::hasColumn('companies', 'governorate_id')) {
                $table->dropConstrainedForeignId('governorate_id');
            }
        });

        Schema::table('user_projects', function (Blueprint $table) {
            if (Schema::hasColumn('user_projects', 'city_id')) {
                $table->dropConstrainedForeignId('city_id');
            }

            if (Schema::hasColumn('user_projects', 'governorate_id')) {
                $table->dropConstrainedForeignId('governorate_id');
            }
        });
    }
};
