<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

/**
 * Migration: Security Hardening — Phase 1
 *
 * 1. encadrement  → ajoute nin_hash (HMAC-SHA256 pour recherche)
 * 2. etablissement → chiffre smtpmotdepass + ip_local_serv
 *
 * Approche "Lazy Migration" sans coupure de service :
 *  - Les colonnes existantes restent inchangées
 *  - Les nouvelles colonnes chiffrées sont ajoutées en parallèle
 *  - Le code de login sera mis à jour pour utiliser nin_hash
 *
 * @conformant ISO 27001 A.8.3, A.8.11, A.8.24 | RGPD Art. 9, 32
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. encadrement : ajouter nin_hash pour recherche sécurisée ────────
        if (!Schema::hasColumn('encadrement', 'nin_hash')) {
            Schema::table('encadrement', function (Blueprint $table) {
                $table->string('nin_hash', 64)->nullable()->after('nin')
                      ->comment('HMAC-SHA256 du NIN pour recherche indexée — ISO 27001 A.8.11');
                $table->index('nin_hash', 'idx_encadrement_nin_hash');
            });

            // Remplir nin_hash pour tous les enregistrements existants
            $secret = config('app.key');
            $count  = 0;

            DB::table('encadrement')
                ->whereNotNull('nin')
                ->where('nin', '!=', '')
                ->orderBy('IDEncadrement')
                ->chunk(500, function ($rows) use ($secret, &$count) {
                    foreach ($rows as $row) {
                        if (!$row->nin) continue;

                        // Extraire la valeur brute du NIN (déchiffrer si déjà chiffré)
                        $rawNin = $row->nin;
                        try {
                            $rawNin = Crypt::decryptString($row->nin);
                        } catch (\Exception $e) {
                            // Valeur en clair — utiliser telle quelle
                        }

                        $hash = hash_hmac('sha256', mb_strtoupper(trim($rawNin)), $secret);

                        DB::table('encadrement')
                            ->where('IDEncadrement', $row->IDEncadrement)
                            ->update(['nin_hash' => $hash]);

                        $count++;
                    }
                });

            Log::info("[SECURITY] nin_hash populated for {$count} encadrement records.");
        }

        // ── 2. etablissement : chiffrement SMTP + IP ──────────────────────────
        if (!Schema::hasColumn('etablissement', 'smtpmotdepass_enc')) {
            Schema::table('etablissement', function (Blueprint $table) {
                $table->text('smtpmotdepass_enc')->nullable()->after('smtpmotdepass')
                      ->comment('Mot de passe SMTP chiffré AES-256 — ISO 27001 A.8.24');
                $table->text('ip_local_serv_enc')->nullable()->after('ip_local_serv')
                      ->comment('IP serveur local chiffré — ISO 27001 A.8.3');
            });

            // Migrer les données existantes
            $encryptedSmtp = 0;
            $encryptedIp   = 0;

            DB::table('etablissement')
                ->chunkById(100, function ($rows) use (&$encryptedSmtp, &$encryptedIp) {
                    foreach ($rows as $row) {
                        $update = [];

                        // Chiffrer SMTP password
                        if (!empty($row->smtpmotdepass)) {
                            try {
                                $update['smtpmotdepass_enc'] = Crypt::encryptString($row->smtpmotdepass);
                                $update['smtpmotdepass']     = null; // Effacer texte clair
                                $encryptedSmtp++;
                            } catch (\Exception $e) {
                                Log::error("SMTP encrypt error ets#{$row->IDetablissement}: " . $e->getMessage());
                            }
                        }

                        // Chiffrer IP serveur local
                        if (!empty($row->ip_local_serv)) {
                            try {
                                $update['ip_local_serv_enc'] = Crypt::encryptString($row->ip_local_serv);
                                $update['ip_local_serv']     = null; // Effacer texte clair
                                $encryptedIp++;
                            } catch (\Exception $e) {
                                Log::error("IP encrypt error ets#{$row->IDetablissement}: " . $e->getMessage());
                            }
                        }

                        if (!empty($update)) {
                            DB::table('etablissement')
                                ->where('IDetablissement', $row->IDetablissement)
                                ->update($update);
                        }
                    }
                }, 'IDetablissement');

            Log::info("[SECURITY] SMTP encrypted: {$encryptedSmtp} | IP encrypted: {$encryptedIp}");
        }
    }

    public function down(): void
    {
        // Restore SMTP plaintext from encrypted (requires valid APP_KEY)
        DB::table('etablissement')
            ->whereNotNull('smtpmotdepass_enc')
            ->chunkById(100, function ($rows) {
                foreach ($rows as $row) {
                    try {
                        DB::table('etablissement')
                            ->where('IDetablissement', $row->IDetablissement)
                            ->update([
                                'smtpmotdepass'     => Crypt::decryptString($row->smtpmotdepass_enc),
                                'smtpmotdepass_enc' => null,
                                'ip_local_serv'     => $row->ip_local_serv_enc
                                    ? Crypt::decryptString($row->ip_local_serv_enc)
                                    : null,
                                'ip_local_serv_enc' => null,
                            ]);
                    } catch (\Exception $e) {}
                }
            }, 'IDetablissement');

        Schema::table('etablissement', function (Blueprint $table) {
            $table->dropColumn(['smtpmotdepass_enc', 'ip_local_serv_enc']);
        });

        Schema::table('encadrement', function (Blueprint $table) {
            $table->dropIndex('idx_encadrement_nin_hash');
            $table->dropColumn('nin_hash');
        });
    }
};
