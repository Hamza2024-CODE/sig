<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sync_errors', function (Blueprint $table) {
            $table->id();
            $table->string('job_id', 50)->nullable();
            $table->string('table_name', 100);
            $table->string('record_id', 100)->nullable();
            $table->text('error_message')->nullable();
            $table->longText('payload')->nullable();
            $table->string('status', 50)->default('pending_retry');
            $table->integer('retry_count')->default(0);
            $table->timestamps();

            // Indexes for performance
            $table->index(['table_name', 'status']);
            $table->index('job_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_errors');
    }
};
