<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('trusted_devices')) {
            Schema::create('trusted_devices', function (Blueprint $table) {
                $table->bigIncrements('id');
                // References utilisateur.IDUtilisateur (bigint)
                $table->bigInteger('user_id')->index();
                $table->string('device_fingerprint', 64)->index();
                $table->string('device_name', 100)->default('Trusted Device');
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 255)->nullable();
                $table->timestamp('last_activity')->useCurrent();
                $table->timestamp('expires_at')->nullable()->index();

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
        Schema::dropIfExists('trusted_devices');
    }
};
