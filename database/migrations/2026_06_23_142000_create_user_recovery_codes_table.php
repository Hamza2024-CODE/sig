<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('user_recovery_codes')) {
            Schema::create('user_recovery_codes', function (Blueprint $table) {
                $table->bigIncrements('id');
                // References utilisateur.IDUtilisateur (bigint)
                $table->bigInteger('user_id')->index();
                $table->string('code_hash', 255);
                $table->timestamp('used_at')->nullable();
                $table->timestamp('created_at')->useCurrent();

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
        Schema::dropIfExists('user_recovery_codes');
    }
};
