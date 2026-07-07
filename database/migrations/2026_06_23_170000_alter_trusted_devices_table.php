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
        Schema::table('trusted_devices', function (Blueprint $table) {
            // Drop foreign key constraint on user_id if not SQLite
            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                $table->dropForeign('trusted_devices_user_id_foreign');
            }

            // Add user_type column
            $table->string('user_type', 50)->default('utilisateur')->after('user_id');

            // Create composite index for optimized performance
            $table->index(['user_id', 'user_type'], 'trusted_devices_user_composite_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trusted_devices', function (Blueprint $table) {
            // Drop composite index
            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                $table->dropIndex('trusted_devices_user_composite_index');
            }

            // Drop user_type column
            $table->dropColumn('user_type');

            // Re-add foreign key constraint if not SQLite
            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                $table->foreign('user_id')
                      ->references('IDUtilisateur')
                      ->on('utilisateur')
                      ->onDelete('cascade');
            }
        });
    }
};
