<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('security_logs')) {
            Schema::create('security_logs', function (Blueprint $table) {
                $table->bigIncrements('id');
                // References utilisateur.IDUtilisateur (bigint)
                $table->bigInteger('user_id')->nullable()->index();
                $table->string('event_type', 100);
                $table->string('severity', 20)->default('info'); // info, warning, danger, critical
                $table->text('description')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 255)->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('created_at')->useCurrent()->index();

                // Foreign key constraint
                $table->foreign('user_id')
                      ->references('IDUtilisateur')
                      ->on('utilisateur')
                      ->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('security_logs');
    }
};
