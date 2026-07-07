<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Expand columns to TEXT to fit encrypted AES-256 strings safely (Raw SQL to avoid DBAL dependency)
        DB::statement("ALTER TABLE encadrement MODIFY nin TEXT NULL, MODIFY DateNais TEXT NULL");

        // 2. Perform data migration: Encrypt all plaintext values
        $count = 0;
        DB::table('encadrement')
            ->orderBy('IDEncadrement')
            ->chunk(200, function ($rows) use (&$count) {
                foreach ($rows as $row) {
                    $update = [];

                    // Encrypt nin if not already encrypted
                    if ($row->nin !== null && $row->nin !== '') {
                        try {
                            // Check if already encrypted
                            Crypt::decryptString($row->nin);
                        } catch (\Illuminate\Contracts\Encryption\DecryptException) {
                            // Plain text -> encrypt it
                            $update['nin'] = Crypt::encryptString($row->nin);
                        }
                    }

                    // Encrypt DateNais if not already encrypted
                    if ($row->DateNais !== null && $row->DateNais !== '') {
                        try {
                            // Check if already encrypted
                            Crypt::decryptString($row->DateNais);
                        } catch (\Illuminate\Contracts\Encryption\DecryptException) {
                            // Plain text -> encrypt it
                            $update['DateNais'] = Crypt::encryptString($row->DateNais);
                        }
                    }

                    if (!empty($update)) {
                        DB::table('encadrement')
                            ->where('IDEncadrement', $row->IDEncadrement)
                            ->update($update);
                        $count++;
                    }
                }
            });

        Log::info("[SECURITY] Encrypted nin and DateNais values for {$count} records in encadrement table.");
    }

    public function down(): void
    {
        // 1. Decrypt all encrypted values back to plain text
        $count = 0;
        DB::table('encadrement')
            ->orderBy('IDEncadrement')
            ->chunk(200, function ($rows) use (&$count) {
                foreach ($rows as $row) {
                    $update = [];

                    if ($row->nin !== null && $row->nin !== '') {
                        try {
                            $update['nin'] = mb_substr(Crypt::decryptString($row->nin), 0, 50);
                        } catch (\Illuminate\Contracts\Encryption\DecryptException) {
                            // Already plain text or couldn't decrypt
                        }
                    }

                    if ($row->DateNais !== null && $row->DateNais !== '') {
                        try {
                            $update['DateNais'] = mb_substr(Crypt::decryptString($row->DateNais), 0, 30);
                        } catch (\Illuminate\Contracts\Encryption\DecryptException) {
                            // Already plain text or couldn't decrypt
                        }
                    }

                    if (!empty($update)) {
                        DB::table('encadrement')
                            ->where('IDEncadrement', $row->IDEncadrement)
                            ->update($update);
                        $count++;
                    }
                }
            });

        // 2. Shrink columns back to their original size limits (Raw SQL)
        DB::statement("ALTER TABLE encadrement MODIFY nin VARCHAR(50) NULL, MODIFY DateNais VARCHAR(30) NULL");

        Log::info("[SECURITY] Decrypted and reverted columns for {$count} records in encadrement table.");
    }
};