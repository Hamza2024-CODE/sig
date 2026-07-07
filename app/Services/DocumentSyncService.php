<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class DocumentSyncService
{
    /**
     * Check sync status and retrieve statistics.
     */
    public function checkSyncStatus(string $table = 'candidat_document', ?int $wilayaId = null, ?int $etabId = null): array
    {
        $stats = [
            'total_candidates'   => 0,
            'unsynced_documents' => 0,
            'synced_documents'   => 0,
            'storage_size_mb'    => 0,
        ];

        try {
            // Count total matching candidates or staff members
            $query = DB::table($table);

            if ($table === 'candidat_document' || $table === 'candidat_certifscol' || $table === 'candidat_contratapp') {
                $query->join('candidat', "{$table}.IDCandidat", '=', 'candidat.IDCandidat');
                if ($wilayaId) {
                    $query->where('candidat.IDWilayaa', $wilayaId);
                }
                if ($etabId) {
                    $query->whereIn('candidat.IDOffre', function($q) use ($etabId) {
                        $q->select('IDOffre')->from('offre')->where('IDEts_Form', $etabId);
                    });
                }
            } elseif ($table === 'encadremen_memo') {
                $query->join('encadrement', 'encadremen_memo.IDEncadrement', '=', 'encadrement.IDEncadrement');
                if ($wilayaId || $etabId) {
                    $query->join('etablissement', 'encadrement.IDEts_Form', '=', 'etablissement.IDEts_Form');
                    if ($wilayaId) {
                        $query->where('etablissement.IDDFEP', $wilayaId);
                    }
                    if ($etabId) {
                        $query->where('etablissement.IDEts_Form', $etabId);
                    }
                }
            }

            $stats['total_candidates'] = $query->count();

            // Run optimized aggregation query
            if ($table === 'candidat_document') {
                // For candidat_document, sum the 4 blob fields
                $res = $query->selectRaw("
                    SUM(CASE WHEN relevedenotes_doc IS NOT NULL AND relevedenotes_doc != '' THEN 1 ELSE 0 END +
                        CASE WHEN enneexperience_doc IS NOT NULL AND enneexperience_doc != '' THEN 1 ELSE 0 END +
                        CASE WHEN exdiplome_doc IS NOT NULL AND exdiplome_doc != '' THEN 1 ELSE 0 END +
                        CASE WHEN actn_doc IS NOT NULL AND actn_doc != '' THEN 1 ELSE 0 END) as unsynced,
                        
                    SUM(CASE WHEN relevedenotes_url IS NOT NULL AND relevedenotes_url != '' AND (relevedenotes_doc IS NULL OR relevedenotes_doc = '') THEN 1 ELSE 0 END +
                        CASE WHEN enneexperience_url IS NOT NULL AND enneexperience_url != '' AND (enneexperience_doc IS NULL OR enneexperience_doc = '') THEN 1 ELSE 0 END +
                        CASE WHEN exdiplome_url IS NOT NULL AND exdiplome_url != '' AND (exdiplome_doc IS NULL OR exdiplome_doc = '') THEN 1 ELSE 0 END +
                        CASE WHEN actn_url IS NOT NULL AND actn_url != '' AND (actn_doc IS NULL OR actn_doc = '') THEN 1 ELSE 0 END) as synced
                ")->first();

                $stats['unsynced_documents'] = (int)($res->unsynced ?? 0);
                $stats['synced_documents']   = (int)($res->synced ?? 0);
            } else {
                // For other tables with 'photo' columns: unsynced is binary blob, synced contains 'documents/' path
                $rawRows = $query->select('photo')->get();
                $unsynced = 0;
                $synced = 0;
                foreach ($rawRows as $r) {
                    $val = $r->photo;
                    if ($val !== null && $val !== '') {
                        if (strpos($val, 'documents/') === 0) {
                            $synced++;
                        } else {
                            $unsynced++;
                        }
                    }
                }
                $stats['unsynced_documents'] = $unsynced;
                $stats['synced_documents']   = $synced;
            }

            // Compute size on disk
            $baseDir = storage_path('app/public/documents');
            $sizeBytes = 0;
            if (is_dir($baseDir)) {
                $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($baseDir));
                foreach ($files as $file) {
                    if ($file->isFile()) {
                        $sizeBytes += $file->getSize();
                    }
                }
            }
            $stats['storage_size_mb'] = round($sizeBytes / 1024 / 1024, 2);

        } catch (\Exception $e) {
            // Log or handle gracefully
        }

        return $stats;
    }

    /**
     * Get preview records of unsynced documents with owner names and details.
     */
    public function getPreviewDocuments(string $table = 'candidat_document', int $limit = 10, ?int $wilayaId = null, ?int $etabId = null): array
    {
        $documents = [];
        try {
            $query = DB::table($table);

            if ($table === 'candidat_document') {
                $query->join('candidat', 'candidat_document.IDCandidat', '=', 'candidat.IDCandidat')
                      ->leftJoin('wilaya', 'candidat.IDWilayaa', '=', 'wilaya.IDWilayaa')
                      ->leftJoin('offre', 'candidat.IDOffre', '=', 'offre.IDOffre')
                      ->leftJoin('etablissement', 'offre.IDEts_Form', '=', 'etablissement.IDEts_Form')
                      ->select(
                          'candidat_document.IDcandidat_document AS doc_id',
                          'candidat.Nom AS owner_nom',
                          'candidat.Prenom AS owner_prenom',
                          'wilaya.Nom AS wilaya_name',
                          'etablissement.Nom AS etab_name',
                          'candidat_document.relevedenotes_doc',
                          'candidat_document.enneexperience_doc',
                          'candidat_document.exdiplome_doc',
                          'candidat_document.actn_doc',
                          'candidat_document.relevedenotes_url',
                          'candidat_document.enneexperience_url',
                          'candidat_document.exdiplome_url',
                          'candidat_document.actn_url'
                      );

                if ($wilayaId) {
                    $query->where('candidat.IDWilayaa', $wilayaId);
                }
                if ($etabId) {
                    $query->where('offre.IDEts_Form', $etabId);
                }

                $rows = $query->limit($limit)->get();
                foreach ($rows as $row) {
                    $docTypes = [
                        'relevedenotes'  => ['doc_col' => 'relevedenotes_doc',  'url_col' => 'relevedenotes_url', 'label' => 'كشف النقاط'],
                        'enneexperience' => ['doc_col' => 'enneexperience_doc', 'url_col' => 'enneexperience_url', 'label' => 'شهادة الخبرة'],
                        'exdiplome'      => ['doc_col' => 'exdiplome_doc',      'url_col' => 'exdiplome_url', 'label' => 'نسخة الشهادة'],
                        'actn'           => ['doc_col' => 'actn_doc',           'url_col' => 'actn_url', 'label' => 'شهادة الميلاد'],
                    ];

                    foreach ($docTypes as $key => $cols) {
                        $blob = $row->{$cols['doc_col']};
                        $url = $row->{$cols['url_col']};
                        
                        $isSynced = $url && !$blob;
                        $hasDoc = $blob || $url;
                        
                        if ($hasDoc) {
                            $documents[] = [
                                'doc_id'       => $row->doc_id . '_' . $key,
                                'owner_type'   => 'مترشح / متربص',
                                'owner_name'   => trim(($row->owner_nom ?? '') . ' ' . ($row->owner_prenom ?? '')),
                                'wilaya'       => $row->wilaya_name ?? '—',
                                'institution'  => $row->etab_name ?? '—',
                                'doc_label'    => $cols['label'],
                                'status'       => $isSynced ? 'synced' : 'unsynced',
                                'path'         => $url ?? '—',
                            ];
                        }
                    }
                }
            } else {
                // candidat_certifscol, candidat_contratapp, encadremen_memo
                if ($table === 'encadremen_memo') {
                    $query->join('encadrement', 'encadremen_memo.IDEncadrement', '=', 'encadrement.IDEncadrement')
                          ->leftJoin('etablissement', 'encadrement.IDEts_Form', '=', 'etablissement.IDEts_Form')
                          ->leftJoin('wilaya', 'etablissement.IDDFEP', '=', 'wilaya.IDWilayaa')
                          ->select(
                              'encadremen_memo.IDEncadremen_memo AS doc_id',
                              'encadrement.Nom AS owner_nom',
                              'encadrement.Prenom AS owner_prenom',
                              'wilaya.Nom AS wilaya_name',
                              'etablissement.Nom AS etab_name',
                              'encadremen_memo.photo AS photo_val'
                          );

                    if ($wilayaId) {
                        $query->where('etablissement.IDDFEP', $wilayaId);
                    }
                    if ($etabId) {
                        $query->where('etablissement.IDEts_Form', $etabId);
                    }

                    $label = 'ملف/صورة إطار';
                    $ownerType = 'مكون / إطار';
                } else {
                    $query->join('candidat', "{$table}.IDCandidat", '=', 'candidat.IDCandidat')
                          ->leftJoin('wilaya', 'candidat.IDWilayaa', '=', 'wilaya.IDWilayaa')
                          ->leftJoin('offre', 'candidat.IDOffre', '=', 'offre.IDOffre')
                          ->leftJoin('etablissement', 'offre.IDEts_Form', '=', 'etablissement.IDEts_Form')
                          ->select(
                              "{$table}.ID{$table} AS doc_id",
                              'candidat.Nom AS owner_nom',
                              'candidat.Prenom AS owner_prenom',
                              'wilaya.Nom AS wilaya_name',
                              'etablissement.Nom AS etab_name',
                              "{$table}.photo AS photo_val"
                          );

                    if ($wilayaId) {
                        $query->where('candidat.IDWilayaa', $wilayaId);
                    }
                    if ($etabId) {
                        $query->where('offre.IDEts_Form', $etabId);
                    }

                    $label = $table === 'candidat_certifscol' ? 'شهادة مدرسية' : 'عقد تمهين';
                    $ownerType = 'مترشح / متربص';
                }

                $rows = $query->limit($limit)->get();
                foreach ($rows as $row) {
                    $val = $row->photo_val;
                    if ($val !== null && $val !== '') {
                        $isSynced = (strpos($val, 'documents/') === 0);
                        $documents[] = [
                            'doc_id'       => $row->doc_id,
                            'owner_type'   => $ownerType,
                            'owner_name'   => trim(($row->owner_nom ?? '') . ' ' . ($row->owner_prenom ?? '')),
                            'wilaya'       => $row->wilaya_name ?? '—',
                            'institution'  => $row->etab_name ?? '—',
                            'doc_label'    => $label,
                            'status'       => $isSynced ? 'synced' : 'unsynced',
                            'path'         => $isSynced ? $val : '—',
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            // 
        }

        return $documents;
    }

    /**
     * Detect file extension from binary payload signature.
     */
    private function getExtensionFromBinary(string $data): string
    {
        if (strpos($data, '%PDF') === 0) {
            return 'pdf';
        }
        if (strpos($data, "\xff\xd8\xff") === 0) {
            return 'jpg';
        }
        if (strpos($data, "\x89PNG") === 0) {
            return 'png';
        }
        if (strpos($data, "GIF8") === 0) {
            return 'gif';
        }
        if (strpos($data, "PK\x03\x04") === 0) {
            return 'docx';
        }
        return 'bin'; // fallback binary
    }

    /**
     * Batch process document blobs: writes them to files and clears them from MySQL.
     */
    public function syncBlobsToFiles(string $table = 'candidat_document', int $limit = 500, ?int $wilayaId = null, ?int $etabId = null): array
    {
        $syncedCount = 0;
        $errors = [];

        try {
            $query = DB::table($table);

            // Add joins/where for Wilaya/Etablissement
            if ($table === 'candidat_document' || $table === 'candidat_certifscol' || $table === 'candidat_contratapp') {
                $query->join('candidat', "{$table}.IDCandidat", '=', 'candidat.IDCandidat');
                if ($wilayaId) {
                    $query->where('candidat.IDWilayaa', $wilayaId);
                }
                if ($etabId) {
                    $query->whereIn('candidat.IDOffre', function($q) use ($etabId) {
                        $q->select('IDOffre')->from('offre')->where('IDEts_Form', $etabId);
                    });
                }
            } elseif ($table === 'encadremen_memo') {
                $query->join('encadrement', 'encadremen_memo.IDEncadrement', '=', 'encadrement.IDEncadrement');
                if ($wilayaId || $etabId) {
                    $query->join('etablissement', 'encadrement.IDEts_Form', '=', 'etablissement.IDEts_Form');
                    if ($wilayaId) {
                        $query->where('etablissement.IDDFEP', $wilayaId);
                    }
                    if ($etabId) {
                        $query->where('etablissement.IDEts_Form', $etabId);
                    }
                }
            }

            // Unsynced filter:
            if ($table === 'candidat_document') {
                $query->where(function ($q) {
                    $q->whereNotNull('relevedenotes_doc')
                      ->orWhereNotNull('enneexperience_doc')
                      ->orWhereNotNull('exdiplome_doc')
                      ->orWhereNotNull('actn_doc');
                });
            } else {
                $query->whereNotNull('photo')
                      ->where('photo', '!=', '')
                      ->where('photo', 'not like', 'documents/%');
            }

            $rows = $query->limit($limit)->get();

            $baseDir = storage_path('app/public/documents');
            if (!is_dir($baseDir)) {
                mkdir($baseDir, 0755, true);
            }

            // Ensure directory security
            $this->secureStorageDirectory();

            if ($table === 'candidat_document') {
                $docTypes = [
                    'relevedenotes'  => ['doc_col' => 'relevedenotes_doc',  'url_col' => 'relevedenotes_url'],
                    'enneexperience' => ['doc_col' => 'enneexperience_doc', 'url_col' => 'enneexperience_url'],
                    'exdiplome'      => ['doc_col' => 'exdiplome_doc',      'url_col' => 'exdiplome_url'],
                    'actn'           => ['doc_col' => 'actn_doc',           'url_col' => 'actn_url'],
                ];

                foreach ($rows as $row) {
                    $rowArray = (array)$row;
                    $id = $rowArray['IDcandidat_document'];
                    $candidateId = $rowArray['IDCandidat'] ?? $id;

                    $updates = [];
                    $filesWritten = [];

                    try {
                        foreach ($docTypes as $type => $cols) {
                            $blob = $rowArray[$cols['doc_col']];
                            if ($blob !== null && $blob !== '') {
                                $ext = $this->getExtensionFromBinary($blob);
                                
                                $year = date('Y');
                                $month = date('m');
                                $subPath = "{$year}/{$month}";
                                $dirPath = "{$baseDir}/{$subPath}";
                                
                                if (!is_dir($dirPath)) {
                                    mkdir($dirPath, 0755, true);
                                }

                                $fileName = "candidat_{$candidateId}_{$type}.{$ext}";
                                $relativeFilePath = "documents/{$subPath}/{$fileName}";
                                $absoluteFilePath = "{$baseDir}/{$subPath}/{$fileName}";

                                file_put_contents($absoluteFilePath, $blob);
                                $filesWritten[] = $absoluteFilePath;

                                $updates[$cols['doc_col']] = null;
                                $updates[$cols['url_col']] = $relativeFilePath;
                            }
                        }

                        if (!empty($updates)) {
                            DB::transaction(function () use ($id, $updates) {
                                DB::table('candidat_document')
                                    ->where('IDcandidat_document', $id)
                                    ->update($updates);
                            });
                            $syncedCount++;
                        }
                    } catch (\Exception $e) {
                        foreach ($filesWritten as $f) {
                            if (file_exists($f)) {
                                @unlink($f);
                            }
                        }
                        $errors[] = "ID {$id}: " . $e->getMessage();
                    }
                }
            } else {
                // For other tables: candidat_certifscol, candidat_contratapp, encadremen_memo
                $pkCol = $table === 'candidat_certifscol' ? 'IDCandidat_certifscol' : 
                        ($table === 'candidat_contratapp' ? 'IDCandidat_contratapp' : 'IDEncadremen_memo');
                
                $subFolder = $table === 'candidat_certifscol' ? 'certifscol' : 
                            ($table === 'candidat_contratapp' ? 'contratapp' : 'encadrement');

                foreach ($rows as $row) {
                    $rowArray = (array)$row;
                    $id = $rowArray[$pkCol];
                    $ownerId = $rowArray['IDCandidat'] ?? ($rowArray['IDEncadrement'] ?? $id);
                    $blob = $rowArray['photo'];
                    $filesWritten = [];

                    try {
                        if ($blob !== null && $blob !== '' && strpos($blob, 'documents/') !== 0) {
                            $ext = $this->getExtensionFromBinary($blob);
                            
                            $year = date('Y');
                            $month = date('m');
                            $subPath = "{$subFolder}/{$year}/{$month}";
                            $dirPath = "{$baseDir}/{$subPath}";
                            
                            if (!is_dir($dirPath)) {
                                mkdir($dirPath, 0755, true);
                            }

                            $fileName = "owner_{$ownerId}_{$id}.{$ext}";
                            $relativeFilePath = "documents/{$subPath}/{$fileName}";
                            $absoluteFilePath = "{$baseDir}/{$subPath}/{$fileName}";

                            file_put_contents($absoluteFilePath, $blob);
                            $filesWritten[] = $absoluteFilePath;

                            DB::transaction(function () use ($table, $pkCol, $id, $relativeFilePath) {
                                DB::table($table)
                                    ->where($pkCol, $id)
                                    ->update(['photo' => $relativeFilePath]);
                            });
                            $syncedCount++;
                        }
                    } catch (\Exception $e) {
                        foreach ($filesWritten as $f) {
                            if (file_exists($f)) {
                                @unlink($f);
                            }
                        }
                        $errors[] = "ID {$id}: " . $e->getMessage();
                    }
                }
            }

        } catch (\Exception $e) {
            $errors[] = "Global sync error: " . $e->getMessage();
        }

        return [
            'success' => count($errors) === 0 || $syncedCount > 0,
            'synced'  => $syncedCount,
            'errors'  => $errors
        ];
    }

    /**
     * Clean up files on disk that are not registered in the database.
     */
    public function cleanupOrphans(): array
    {
        $baseDir = storage_path('app/public/documents');
        if (!is_dir($baseDir)) {
            return ['success' => true, 'deleted' => 0];
        }

        $deletedCount = 0;
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($baseDir, \RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $fileName = $file->getFilename();
                    if ($fileName === '.htaccess') {
                        continue; // Skip security file
                    }

                    $filePath = $file->getPathname();
                    $relative = str_replace(storage_path('app/public') . DIRECTORY_SEPARATOR, '', $filePath);
                    $relative = str_replace('\\', '/', $relative); // Normalize slashes for db query

                    // Check if exists in db
                    $exists = DB::table('candidat_document')
                        ->where('relevedenotes_url', $relative)
                        ->orWhere('enneexperience_url', $relative)
                        ->orWhere('exdiplome_url', $relative)
                        ->orWhere('actn_url', $relative)
                        ->exists()
                        || DB::table('candidat_certifscol')->where('photo', $relative)->exists()
                        || DB::table('candidat_contratapp')->where('photo', $relative)->exists()
                        || DB::table('encadremen_memo')->where('photo', $relative)->exists();

                    if (!$exists) {
                        @unlink($filePath);
                        $deletedCount++;
                    }
                }
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage(), 'deleted' => $deletedCount];
        }

        return [
            'success' => true,
            'deleted' => $deletedCount
        ];
    }

    /**
     * Secures the storage folder from script execution by writing a .htaccess file.
     */
    public function secureStorageDirectory(): bool
    {
        try {
            $baseDir = storage_path('app/public/documents');
            if (!is_dir($baseDir)) {
                mkdir($baseDir, 0755, true);
            }
            $htaccessPath = $baseDir . '/.htaccess';
            $content = "# SGFEP Security: Disable PHP and CGI engine execution\nphp_flag engine off\nOptions -ExecCGI\n";
            return (bool)file_put_contents($htaccessPath, $content);
        } catch (\Exception $e) {
            return false;
        }
    }
}
