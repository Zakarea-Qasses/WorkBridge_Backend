<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasTargetForeign = false;

        if (DB::getDriverName() === 'mysql') {
            $hasTargetForeign = ! empty(DB::select("
                select constraint_name
                from information_schema.key_column_usage
                where table_schema = database()
                    and table_name = 'reports'
                    and column_name = 'target_id'
                    and referenced_table_name is not null
            "));
        }

        Schema::table('reports', function (Blueprint $table) use ($hasTargetForeign) {
            if ($hasTargetForeign) {
                $table->dropForeign(['target_id']);
            }

            if (! Schema::hasColumn('reports', 'contract_id')) {
                $table->unsignedBigInteger('contract_id')->nullable()->after('target_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            if (Schema::hasColumn('reports', 'contract_id')) {
                $table->dropColumn('contract_id');
            }
        });
    }
};
