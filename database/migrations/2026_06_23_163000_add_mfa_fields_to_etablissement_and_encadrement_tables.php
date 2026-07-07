<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('etablissement')) {
            Schema::table('etablissement', function (Blueprint $table) {
                if (!Schema::hasColumn('etablissement', 'google2fa_secret')) {
                    $table->text('google2fa_secret')->nullable();
                }
                if (!Schema::hasColumn('etablissement', 'mfa_enabled')) {
                    $table->boolean('mfa_enabled')->default(false);
                }
                if (!Schema::hasColumn('etablissement', 'mfa_enabled_at')) {
                    $table->timestamp('mfa_enabled_at')->nullable();
                }
            });
        }

        if (Schema::hasTable('encadrement')) {
            Schema::table('encadrement', function (Blueprint $table) {
                if (!Schema::hasColumn('encadrement', 'google2fa_secret')) {
                    $table->text('google2fa_secret')->nullable();
                }
                if (!Schema::hasColumn('encadrement', 'mfa_enabled')) {
                    $table->boolean('mfa_enabled')->default(false);
                }
                if (!Schema::hasColumn('encadrement', 'mfa_enabled_at')) {
                    $table->timestamp('mfa_enabled_at')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('etablissement')) {
            Schema::table('etablissement', function (Blueprint $table) {
                if (Schema::hasColumn('etablissement', 'google2fa_secret')) {
                    $table->dropColumn('google2fa_secret');
                }
                if (Schema::hasColumn('etablissement', 'mfa_enabled')) {
                    $table->dropColumn('mfa_enabled');
                }
                if (Schema::hasColumn('etablissement', 'mfa_enabled_at')) {
                    $table->dropColumn('mfa_enabled_at');
                }
            });
        }

        if (Schema::hasTable('encadrement')) {
            Schema::table('encadrement', function (Blueprint $table) {
                if (Schema::hasColumn('encadrement', 'google2fa_secret')) {
                    $table->dropColumn('google2fa_secret');
                }
                if (Schema::hasColumn('encadrement', 'mfa_enabled')) {
                    $table->dropColumn('mfa_enabled');
                }
                if (Schema::hasColumn('encadrement', 'mfa_enabled_at')) {
                    $table->dropColumn('mfa_enabled_at');
                }
            });
        }
    }
};
