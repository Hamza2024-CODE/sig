<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('dashboards')) {
            Schema::create('dashboards', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('user_id'); // Matches utilisateur.IDUtilisateur (bigint(20))
                $table->integer('portal_number'); // e.g., 1 to 11
                $table->json('layout_config')->nullable(); // JSON configuration (grid format, etc.)
                $table->timestamps();

                $table->unique(['user_id', 'portal_number']);
                $table->foreign('user_id')->references('IDUtilisateur')->on('utilisateur')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboards');
    }
};
