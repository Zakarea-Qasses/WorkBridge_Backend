<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (! Schema::hasColumn('contracts', 'job_post_id')) {
                $table->foreignId('job_post_id')->nullable()->after('service_request_id')->constrained('job_posts')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (Schema::hasColumn('contracts', 'job_post_id')) {
                $table->dropConstrainedForeignId('job_post_id');
            }
        });
    }
};
