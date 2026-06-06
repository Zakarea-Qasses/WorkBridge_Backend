<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
          DB::statement("
            ALTER TABLE users
            MODIFY status ENUM('pending_review', 'under_review', 'active', 'blocked')
            DEFAULT 'pending_review'
        ");
    }

    public function down(): void
    {
        DB::table('users')
            ->whereIn('status', ['pending_review', 'under_review'])
            ->update(['status' => 'active']);

        DB::statement("
            ALTER TABLE users
            MODIFY status ENUM('active', 'blocked')
            DEFAULT 'active'
        ");
    }
};