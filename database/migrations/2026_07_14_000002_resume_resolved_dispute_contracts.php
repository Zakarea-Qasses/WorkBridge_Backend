<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('contracts')
            ->where('status', 'dispute')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('reports')
                    ->whereColumn('reports.contract_id', 'contracts.id')
                    ->where('reports.status', 'pending');
            })
            ->update(['status' => 'in_progress']);
    }

    public function down(): void
    {
        // A resolved contract cannot be safely returned to a dispute state.
    }
};
