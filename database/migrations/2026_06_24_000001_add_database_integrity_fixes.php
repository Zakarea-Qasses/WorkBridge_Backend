<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->unique('application_id', 'contracts_application_id_unique');
            $table->unique('service_request_id', 'contracts_service_request_id_unique');
            $table->unique('user_project_id', 'contracts_user_project_id_unique');
            $table->unique(['job_post_id', 'freelancer_id'], 'contracts_job_post_freelancer_unique');
        });

        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropForeign(['wallet_id']);
            $table->foreign('wallet_id')
                ->references('id')
                ->on('wallets')
                ->restrictOnDelete();
        });

        $this->addCheck('contracts', 'contracts_positive_amounts_check', 'amount > 0 AND commission_amount >= 0 AND freelancer_amount > 0');
        $this->addCheck('wallets', 'wallets_non_negative_balance_check', 'balance >= 0');
        $this->addCheck('wallet_transactions', 'wallet_transactions_valid_amounts_check', 'amount > 0 AND balance_before >= 0 AND balance_after >= 0');
        $this->addCheck('reviews', 'reviews_rating_range_check', 'rating BETWEEN 1 AND 5');
        $this->addCheck('conversations', 'conversations_different_users_check', 'user1_id <> user2_id');
        $this->addCheck('services', 'services_positive_price_check', 'price > 0');
        $this->addCheck('user_projects', 'user_projects_positive_budget_check', 'budget > 0');
        $this->addCheck('job_posts', 'job_posts_positive_salary_check', 'salary IS NULL OR salary > 0');
        $this->addCheck('applications', 'applications_positive_price_check', 'price > 0');
    }

    public function down(): void
    {
        $this->dropCheck('applications', 'applications_positive_price_check');
        $this->dropCheck('job_posts', 'job_posts_positive_salary_check');
        $this->dropCheck('user_projects', 'user_projects_positive_budget_check');
        $this->dropCheck('services', 'services_positive_price_check');
        $this->dropCheck('conversations', 'conversations_different_users_check');
        $this->dropCheck('reviews', 'reviews_rating_range_check');
        $this->dropCheck('wallet_transactions', 'wallet_transactions_valid_amounts_check');
        $this->dropCheck('wallets', 'wallets_non_negative_balance_check');
        $this->dropCheck('contracts', 'contracts_positive_amounts_check');

        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropForeign(['wallet_id']);
            $table->foreign('wallet_id')
                ->references('id')
                ->on('wallets')
                ->cascadeOnDelete();
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->dropUnique('contracts_job_post_freelancer_unique');
            $table->dropUnique('contracts_user_project_id_unique');
            $table->dropUnique('contracts_service_request_id_unique');
            $table->dropUnique('contracts_application_id_unique');
        });
    }

    private function addCheck(string $table, string $name, string $condition): void
    {
        $driver = DB::getDriverName();

        if (! in_array($driver, ['mysql', 'pgsql'], true)) {
            return;
        }

        $this->silently(function () use ($driver, $table, $name, $condition) {
            $wrappedTable = $this->wrapIdentifier($table, $driver);
            $wrappedName = $this->wrapIdentifier($name, $driver);

            DB::statement("ALTER TABLE {$wrappedTable} ADD CONSTRAINT {$wrappedName} CHECK ({$condition})");
        });
    }

    private function dropCheck(string $table, string $name): void
    {
        $driver = DB::getDriverName();

        if (! in_array($driver, ['mysql', 'pgsql'], true)) {
            return;
        }

        $this->silently(function () use ($driver, $table, $name) {
            $wrappedTable = $this->wrapIdentifier($table, $driver);
            $wrappedName = $this->wrapIdentifier($name, $driver);

            if ($driver === 'pgsql') {
                DB::statement("ALTER TABLE {$wrappedTable} DROP CONSTRAINT IF EXISTS {$wrappedName}");
                return;
            }

            DB::statement("ALTER TABLE {$wrappedTable} DROP CHECK {$wrappedName}");
        });
    }

    private function wrapIdentifier(string $identifier, string $driver): string
    {
        return $driver === 'pgsql'
            ? '"' . str_replace('"', '""', $identifier) . '"'
            : '`' . str_replace('`', '``', $identifier) . '`';
    }

    private function silently(callable $callback): void
    {
        try {
            $callback();
        } catch (\Throwable) {
            //
        }
    }
};
