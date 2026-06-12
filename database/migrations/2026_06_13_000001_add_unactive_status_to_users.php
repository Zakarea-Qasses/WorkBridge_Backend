<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY status ENUM('unactive', 'pending_review', 'under_review', 'active', 'blocked') NOT NULL DEFAULT 'unactive'");
        }

        DB::table('users')
            ->where('role', 'company')
            ->whereIn('status', ['pending_review', 'under_review'])
            ->update(['status' => 'unactive']);

        DB::table('users')
            ->where('role', 'personal')
            ->whereIn('status', ['pending_review', 'under_review'])
            ->update(['status' => 'active']);
    }

    public function down(): void
    {
        DB::table('users')
            ->where('status', 'unactive')
            ->update(['status' => 'pending_review']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY status ENUM('pending_review', 'under_review', 'active', 'blocked') NOT NULL DEFAULT 'pending_review'");
        }
    }
};
