<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: export_requests
 * جدول طلبات التصدير — يتتبّع حالة كل طلب Export (pending/ready/failed)
 * يُستخدم من ExportStatistiquesJob و DashboardController::exportStatus()
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('export_requests')) {
            Schema::create('export_requests', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('user_id')->nullable()->index();
                $table->string('type', 50)->default('stagiaires');       // stagiaires|offres|encadrements
                $table->json('filters')->nullable();
                $table->enum('status', ['pending', 'processing', 'ready', 'failed'])->default('pending');
                $table->string('file_path', 500)->nullable();
                $table->timestamp('created_at')->nullable()->useCurrent();
                $table->timestamp('completed_at')->nullable();
                $table->index(['user_id', 'status']);
            });
        }

        if (!Schema::hasTable('jobs')) {
            Schema::create('jobs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('queue')->index();
                $table->longText('payload');
                $table->unsignedTinyInteger('attempts')->default(0);
                $table->unsignedInteger('reserved_at')->nullable();
                $table->unsignedInteger('available_at');
                $table->unsignedInteger('created_at');
            });
        }

        if (!Schema::hasTable('failed_jobs')) {
            Schema::create('failed_jobs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('uuid')->unique();
                $table->text('connection');
                $table->text('queue');
                $table->longText('payload');
                $table->longText('exception');
                $table->timestamp('failed_at')->useCurrent();
            });
        }

        if (!Schema::hasTable('section_semestre_results')) {
            Schema::create('section_semestre_results', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('IDSection_Semestre')->unique();
                $table->decimal('moyenne_generale', 5, 2)->default(0);
                $table->tinyInteger('is_admis_general')->default(0);
                $table->string('triggered_by', 100)->nullable();
                $table->timestamp('calculated_at')->nullable();
            });
        }
    }

    public function down(): void {}
};
