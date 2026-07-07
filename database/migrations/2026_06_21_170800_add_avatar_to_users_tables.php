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
        Schema::table('utilisateur', function (Blueprint $table) {
            if (!Schema::hasColumn('utilisateur', 'avatar')) {
                $table->string('avatar', 255)->nullable();
            }
        });

        Schema::table('etablissement', function (Blueprint $table) {
            if (!Schema::hasColumn('etablissement', 'avatar')) {
                $table->string('avatar', 255)->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('utilisateur', function (Blueprint $table) {
            if (Schema::hasColumn('utilisateur', 'avatar')) {
                $table->dropColumn('avatar');
            }
        });

        Schema::table('etablissement', function (Blueprint $table) {
            if (Schema::hasColumn('etablissement', 'avatar')) {
                $table->dropColumn('avatar');
            }
        });
    }
};
