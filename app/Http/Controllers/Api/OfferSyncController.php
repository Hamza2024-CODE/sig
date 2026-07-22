<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Helpers\TakwinHelper;

class OfferSyncController extends Controller
{
    /**
     * Receive and sync training offers from Takwin platform
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

        // 2. Validate Payload (Array of offers)
        $request->validate([
            'offers' => 'required|array',
            'offers.*.code_etablissement' => 'required|string',
            'offers.*.code_specialite' => 'required|string',
            'offers.*.code_mode_formation' => 'required|integer',
            'offers.*.date_ouverture' => 'nullable|date',
            'offers.*.date_fermeture' => 'nullable|date',
            'offers.*.statut' => 'nullable|integer',
            'offers.*.code_session' => 'nullable|string',
        ]);

        $offers = $request->input('offers');
        $added = 0;
        $updated = 0;
        $errors = [];

        foreach ($offers as $index => $o) {
            try {
                // Resolve Etablissement
                $etab = DB::table('etablissement')
                    ->where('nomUser', $o['code_etablissement'])
                    ->orWhere('code', $o['code_etablissement'])
                    ->first();

                if (!$etab) {
                    $errors[] = "Index {$index}: Institution code '{$o['code_etablissement']}' not found.";
                    continue;
                }

                // Resolve Specialty
                $spec = DB::table('specialite')
                    ->where('CodeSpec', $o['code_specialite'])
                    ->first();

                if (!$spec) {
                    $errors[] = "Index {$index}: Specialty code '{$o['code_specialite']}' not found.";
                    continue;
                }

                // Resolve Session (IDSession)
                $sessionId = 1; // Default session
                if (!empty($o['code_session'])) {
                    $session = DB::table('session')
                        ->where('code', $o['code_session'])
                        ->orWhere('Nom', $o['code_session'])
                        ->first();
                    if ($session) {
                        $sessionId = $session->IDSession;
                    }
                }

                // Resolve Annee_Formation (Year ID)
                $yearId = 1;
                $activeYear = DB::table('annee_formation')
                    ->orderBy('IDAnnee_Formation', 'desc')
                    ->first();
                if ($activeYear) {
                    $yearId = $activeYear->IDAnnee_Formation;
                }

                // Check if offer already exists locally
                $existing = DB::table('offre')
                    ->where('IDEts_Form', $etab->IDetablissement)
                    ->where('IDSpecialite', $spec->IDSpecialite)
                    ->where('IDMode_formation', $o['code_mode_formation'])
                    ->where('IDSession', $sessionId)
                    ->first();

                $offerData = [
                    'IDEts_Form' => $etab->IDetablissement,
                    'IDSpecialite' => $spec->IDSpecialite,
                    'IDMode_formation' => $o['code_mode_formation'],
                    'DateOuverture' => $o['date_ouverture'] ?? null,
                    'DateFermeture' => $o['date_fermeture'] ?? null,
                    'Statut' => $o['statut'] ?? 1,
                    'IDSession' => $sessionId,
                    'IDAnnee_Formation' => $yearId
                ];

                if ($existing) {
                    DB::table('offre')
                        ->where('IDOffre', $existing->IDOffre)
                        ->update($offerData);
                    $updated++;
                } else {
                    DB::table('offre')->insert($offerData);
                    $added++;
                }

            } catch (\Exception $e) {
                $errors[] = "Index {$index}: " . $e->getMessage();
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Offers synced successfully. Added: {$added}, Updated: {$updated}.",
            'errors' => $errors
        ], 201);
    }
}