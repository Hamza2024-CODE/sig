<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('utilisateur')) {
            Schema::table('utilisateur', function (Blueprint $table) {
                if (!Schema::hasColumn('utilisateur', 'google2fa_secret')) {
                    $table->text('google2fa_secret')->nullable();
                }
                if (!Schema::hasColumn('utilisateur', 'mfa_enabled')) {
                    $table->boolean('mfa_enabled')->default(false);
                }
                if (!Schema::hasColumn('utilisateur', 'mfa_enabled_at')) {
                    $table->timestamp('mfa_enabled_at')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('utilisateur')) {
            Schema::table('utilisateur', function (Blueprint $table) {
                if (Schema::hasColumn('utilisateur', 'google2fa_secret')) {
                    $table->dropColumn('google2fa_secret');
                }
                if (Schema::hasColumn('utilisateur', 'mfa_enabled')) {
                    $table->dropColumn('mfa_enabled');
                }
                if (Schema::hasColumn('utilisateur', 'mfa_enabled_at')) {
                    $table->dropColumn('mfa_enabled_at');
                }
            });
        }
    }
};
