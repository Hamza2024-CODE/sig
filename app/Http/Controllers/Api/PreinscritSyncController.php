<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Helpers\TakwinHelper;

class PreinscritSyncController extends Controller
{
    /**
     * Receive pre-registration data from Takwin platform
     */
    public function sync(Request $request)
    {
        // 1. Authenticate Request using API Key (bearer or header)
        $apiKey = $request->header('X-API-Key');
        if (empty($apiKey)) {
            $authHeader = $request->header('Authorization');
            if (str_starts_with($authHeader, 'Bearer ')) {
                $apiKey = substr($authHeader, 7);
            }
        }

        if (empty($apiKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Missing API Key.'
            ], 401);
        }

        $settings = TakwinHelper::getSettings();
        if (empty($settings['api_token']) || $apiKey !== $settings['api_token']) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Invalid API Key.'
            ], 401);
        }

        // 2. Validate Inputs
        $validated = $request->validate([
            'nin' => 'required|string|size:18',
            'nom_ar' => 'required|string|max:50',
            'prenom_ar' => 'required|string|max:50',
            'nom_fr' => 'nullable|string|max:50',
            'prenom_fr' => 'nullable|string|max:50',
            'date_naissance' => 'required|date',
            'civ' => 'nullable|integer|in:1,2',
            'lieu_naissance' => 'nullable|string|max:50',
            'lieu_naissance_fr' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:40',
            'telephone' => 'nullable|string|max:20',
            'adresse' => 'nullable|string|max:100',
            'adresse_fr' => 'nullable|string|max:100',
            
            'nom_pere' => 'nullable|string|max:50',
            'nom_pere_fr' => 'nullable|string|max:50',
            'nom_mere' => 'nullable|string|max:50',
            'nom_mere_fr' => 'nullable|string|max:50',
            'prenom_mere' => 'nullable|string|max:50',
            'prenom_mere_fr' => 'nullable|string|max:50',

            'code_etablissement' => 'required|string',
            'code_specialite' => 'required|string',
            'code_mode_formation' => 'required|integer',
            'code_session' => 'nullable|string',
            'nss' => 'nullable|string|max:50',
            'nom_employeur' => 'nullable|string|max:255',
        ]);

        // 3. Resolve Institution & Specialty to local Offer ID (IDOffre)
        $etab = DB::table('etablissement')
            ->where('nomUser', $validated['code_etablissement'])
            ->orWhere('code', $validated['code_etablissement'])
            ->first();

        if (!$etab) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid institution code.'
            ], 400);
        }

        $spec = DB::table('specialite')
            ->where('CodeSpec', $validated['code_specialite'])
            ->first();

        if (!$spec) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid specialty code.'
            ], 400);
        }

        $offre = DB::table('offre')
            ->where('IDEts_Form', $etab->IDetablissement)
            ->where('IDSpecialite', $spec->IDSpecialite)
            ->where('IDMode_formation', $validated['code_mode_formation'])
            ->first();

        if (!$offre) {
            return response()->json([
                'success' => false,
                'message' => 'Training offer matching this specialty, institution, and mode was not found in local system.'
            ], 400);
        }

        // 4. Save Attachments if present
        $filePaths = [];
        $docs = $request->input('documents', []);
        $fileMapping = [
            'photo' => 'photo_path',
            'certificat_scolaire' => 'certscol_path',
            'acte_naissance' => 'actnaispdf_path',
            'diplome' => 'diplomecert_path',
            'contrat_apprentissage' => 'contratpdf_path'
        ];

        foreach ($fileMapping as $key => $column) {
            if (!empty($docs[$key])) {
                $filePath = $this->saveBase64File($docs[$key], $validated['nin'], $key);
                if ($filePath) {
                    $filePaths[$column] = $filePath;
                }
            }
        }

        // 5. Insert / Update preinscrit Table
        try {
            $existing = DB::table('preinscrit')
                ->where('Nin', $validated['nin'])
                ->first();

            $insertData = [
                'IDOffre' => $offre->IDOffre,
                'Nom' => $validated['nom_ar'],
                'Prenom' => $validated['prenom_ar'],
                'NomFr' => $validated['nom_fr'] ?? null,
                'PrenomFr' => $validated['prenom_fr'] ?? null,
                'DateNais' => $validated['date_naissance'],
                'Civ' => $validated['civ'] ?? 1,
                'LieuNais' => $validated['lieu_naissance'] ?? null,
                'LieuNaisFr' => $validated['lieu_naissance_fr'] ?? null,
                'email' => $validated['email'] ?? null,
                'tel1' => $validated['telephone'] ?? null,
                'Adres' => $validated['adresse'] ?? null,
                'AdresFr' => $validated['adresse_fr'] ?? null,
                
                'PrenomPere' => $validated['nom_pere'] ?? null,
                'PrenomPereFr' => $validated['nom_pere_fr'] ?? null,
                'NomMere' => $validated['nom_mere'] ?? null,
                'NomMereFr' => $validated['nom_mere_fr'] ?? null,
                'PrenomMere' => $validated['prenom_mere'] ?? null,
                'PrenomMereFr' => $validated['prenom_mere_fr'] ?? null,

                'Nss' => $validated['nss'] ?? null,
                'Nom_employeur' => $validated['nom_employeur'] ?? null,
                'dateInscr' => now(),
                'Validation' => 0, // Pending review by default
                'ValidationDfp' => 0,
            ];

            // Merge uploaded file paths
            $insertData = array_merge($insertData, $filePaths);

            if ($existing) {
                DB::table('preinscrit')
                    ->where('IDPreinscrit', $existing->IDPreinscrit)
                    ->update($insertData);
                $status = 'updated';
            } else {
                DB::table('preinscrit')->insert($insertData);
                $status = 'created';
            }

            return response()->json([
                'success' => true,
                'status' => $status,
                'message' => 'Pre-registration synced successfully.'
            ], 201);

        } catch (\Exception $e) {
            Log::error('Takwin Preinscrit API Sync failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Internal server error occurred.'
            ], 500);
        }
    }

    /**
     * Decode and save base64-encoded files to storage
     */
    private function saveBase64File($base64String, $nin, $fileKey)
    {
        try {
            if (preg_match('/^data:(\w+\/\w+);base64,/', $base64String, $match)) {
                $base64String = substr($base64String, strpos($base64String, ',') + 1);
                $mimeType = $match[1];
                $extension = match ($mimeType) {
                    'image/jpeg', 'image/jpg' => 'jpg',
                    'image/png' => 'png',
                    'application/pdf' => 'pdf',
                    default => 'bin'
                };
            } else {
                $extension = 'pdf'; // default fallback
            }

            $fileData = base64_decode($base64String);
            $fileName = "preinscriptions/{$nin}_{$fileKey}." . $extension;
            
            // Save to public storage directory
            Storage::disk('public')->put($fileName, $fileData);

            return 'storage/' . $fileName;
        } catch (\Exception $e) {
            Log::error("Failed to save base64 file for NIN {$nin}: " . $e->getMessage());
            return null;
        }
    }
}