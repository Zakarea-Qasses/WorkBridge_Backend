<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('profiles', 'rating_avg')) {
                $table->decimal('rating_avg', 3, 2)->default(0)->after('bio');
            }
        });

        DB::table('profiles')->orderBy('id')->each(function ($profile) {
            $avg = DB::table('reviews')
                ->join('contracts', 'reviews.contract_id', '=', 'contracts.id')
                ->where('contracts.freelancer_id', $profile->user_id)
                ->where('reviews.reviewed_user_id', $profile->user_id)
                ->avg('reviews.rating');

            DB::table('profiles')
                ->where('id', $profile->id)
                ->update(['rating_avg' => round((float) $avg, 2)]);
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            if (Schema::hasColumn('profiles', 'rating_avg')) {
                $table->dropColumn('rating_avg');
            }
        });
    }
};
