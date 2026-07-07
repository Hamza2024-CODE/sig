<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('user_id')->nullable()->index();
                $table->string('username', 100)->nullable();
                $table->string('user_role', 50)->nullable();
                $table->string('action', 100)->nullable();
                $table->string('table_name', 100)->nullable();
                $table->unsignedBigInteger('record_id')->nullable();
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 500)->nullable();
                $table->timestamp('created_at')->nullable()->useCurrent();
                $table->index(['username', 'created_at']);
            });
        } else {
            // Add missing columns to existing table
            Schema::table('audit_logs', function (Blueprint $table) {
                if (!Schema::hasColumn('audit_logs', 'user_role')) {
                    $table->string('user_role', 50)->nullable()->after('username');
                }
                if (!Schema::hasColumn('audit_logs', 'user_agent')) {
                    $table->string('user_agent', 500)->nullable()->after('ip_address');
                }
            });
        }

        if (!Schema::hasTable('login_attempts')) {
            Schema::create('login_attempts', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('ip_address', 45)->nullable()->index();
                $table->string('username', 100)->nullable();
                $table->unsignedSmallInteger('attempts')->default(0);
                $table->timestamp('last_attempt')->nullable();
                $table->timestamp('locked_until')->nullable();
                $table->index(['ip_address', 'locked_until']);
            });
        }

        if (!Schema::hasTable('active_sessions')) {
            Schema::create('active_sessions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('user_id')->nullable()->index();
                $table->string('session_id', 255)->nullable()->unique();
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 500)->nullable();
                $table->timestamp('last_activity')->nullable();
                $table->timestamp('created_at')->nullable()->useCurrent();
            });
        }
    }

    public function down(): void {}
};
