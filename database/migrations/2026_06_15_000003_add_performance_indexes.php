<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index(['role', 'status', 'created_at'], 'users_role_status_created_at_index');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'services_status_created_at_index');
            $table->index(['category_id', 'status', 'created_at'], 'services_category_status_created_at_index');
            $table->index(['user_id', 'status', 'created_at'], 'services_user_status_created_at_index');
        });

        Schema::table('user_projects', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'user_projects_status_created_at_index');
            $table->index(['category_id', 'status', 'created_at'], 'user_projects_category_status_created_at_index');
            $table->index(['user_id', 'status', 'created_at'], 'user_projects_user_status_created_at_index');
            $table->index(['governorate_id', 'city_id', 'status'], 'user_projects_location_status_index');
        });

        Schema::table('job_posts', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'job_posts_status_created_at_index');
            $table->index(['company_id', 'created_at'], 'job_posts_company_created_at_index');
            $table->index(['city_id', 'status', 'created_at'], 'job_posts_city_status_created_at_index');
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'applications_user_created_at_index');
            $table->index(['user_project_id', 'status', 'created_at'], 'applications_project_status_created_at_index');
        });

        Schema::table('service_requests', function (Blueprint $table) {
            $table->index(['client_id', 'created_at'], 'service_requests_client_created_at_index');
            $table->index(['service_id', 'status', 'created_at'], 'service_requests_service_status_created_at_index');
        });

        Schema::table('job_apply', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'job_apply_user_created_at_index');
            $table->index(['job_id', 'status', 'created_at'], 'job_apply_job_status_created_at_index');
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->index(['client_id', 'status', 'created_at'], 'contracts_client_status_created_at_index');
            $table->index(['freelancer_id', 'status', 'created_at'], 'contracts_freelancer_status_created_at_index');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->index(['reviewed_user_id', 'created_at'], 'reviews_reviewed_user_created_at_index');
        });

        Schema::table('user_notifications', function (Blueprint $table) {
            $table->index(['user_id', 'read_at', 'created_at'], 'user_notifications_user_read_created_at_index');
        });

        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->index(['wallet_id', 'direction', 'type', 'created_at'], 'wallet_transactions_wallet_direction_type_created_at_index');
            $table->index(['user_id', 'created_at'], 'wallet_transactions_user_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropIndex('wallet_transactions_wallet_direction_type_created_at_index');
            $table->dropIndex('wallet_transactions_user_created_at_index');
        });

        Schema::table('user_notifications', function (Blueprint $table) {
            $table->dropIndex('user_notifications_user_read_created_at_index');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex('reviews_reviewed_user_created_at_index');
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->dropIndex('contracts_client_status_created_at_index');
            $table->dropIndex('contracts_freelancer_status_created_at_index');
        });

        Schema::table('job_apply', function (Blueprint $table) {
            $table->dropIndex('job_apply_user_created_at_index');
            $table->dropIndex('job_apply_job_status_created_at_index');
        });

        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropIndex('service_requests_client_created_at_index');
            $table->dropIndex('service_requests_service_status_created_at_index');
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->dropIndex('applications_user_created_at_index');
            $table->dropIndex('applications_project_status_created_at_index');
        });

        Schema::table('job_posts', function (Blueprint $table) {
            $table->dropIndex('job_posts_status_created_at_index');
            $table->dropIndex('job_posts_company_created_at_index');
            $table->dropIndex('job_posts_city_status_created_at_index');
        });

        Schema::table('user_projects', function (Blueprint $table) {
            $table->dropIndex('user_projects_status_created_at_index');
            $table->dropIndex('user_projects_category_status_created_at_index');
            $table->dropIndex('user_projects_user_status_created_at_index');
            $table->dropIndex('user_projects_location_status_index');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropIndex('services_status_created_at_index');
            $table->dropIndex('services_category_status_created_at_index');
            $table->dropIndex('services_user_status_created_at_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_role_status_created_at_index');
        });
    }
};
