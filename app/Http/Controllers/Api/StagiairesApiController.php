<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use OpenApi\Attributes as OA;
use App\Models\Apprenant;
use Illuminate\Http\Request;

class StagiairesApiController extends Controller
{
    /**
     * GET /api/v1/stagiaires
     * 
     * Returns a paginated list of trainees.
     * Uses Apprenant::forListing() which has optimized eager loading to avoid N+1 queries.
     * Capped at 50 items per page for safety.
     */
    #[OA\Get(
        path: "/api/v1/stagiaires",
        summary: "جلب قائمة المتربصين مع الفلترة والبحث",
        tags: ["Trainees"],
        security: [["bearerAuth" => []], ["ApiKeyAuth" => []]],
        parameters: [
            new OA\Parameter(name: "valide", in: "query", description: "فلترة حسب النشاط (1 لـ نشط، 0 لـ غير نشط)", required: false, schema: new OA\Schema(type: "integer", enum: [0, 1])),
            new OA\Parameter(name: "etablissement_id", in: "query", description: "معرف المؤسسة", required: false, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "specialite_id", in: "query", description: "معرف التخصص", required: false, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "sexe", in: "query", description: "الجنس (M أو F)", required: false, schema: new OA\Schema(type: "string", enum: ["M", "F"])),
            new OA\Parameter(name: "q", in: "query", description: "بحث بالاسم أو اللقب أو رقم الملف", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 1)),
            new OA\Parameter(name: "limit", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 20))
        ],
        responses: [
            new OA\Response(response: 200, description: "تم جلب قائمة المتربصين بنجاح"),
            new OA\Response(response: 401, description: "غير مصرح بالدخول")
        ]
    )]
    public function index(Request $request)
    {
        $query = Apprenant::forListing();

        // 1. Filter by status (valide = 1 for actif, 0 for inactif)
        if ($request->has('valide')) {
            $valide = $request->input('valide');
            if ($valide !== '' && in_array($valide, ['0', '1'], true)) {
                $query->where('statut', ($valide === '1') ? 'actif' : 'inactif');
            }
        }

        // 2. Filter by institution ID
        if ($request->filled('etablissement_id')) {
            $etabId = (int)$request->input('etablissement_id');
            $query->whereHas('section.offre', function ($q) use ($etabId) {
                $q->where('IDEts_Form', $etabId);
            });
        }

        // 3. Filter by specialty ID
        if ($request->filled('specialite_id')) {
            $specId = (int)$request->input('specialite_id');
            $query->whereHas('section', function ($q) use ($specId) {
                $q->where('IDSpecialite', $specId);
            });
        }

        // 4. Filter by gender (Civ: 1=M, 2=F)
        if ($request->filled('sexe')) {
            $sexe = strtoupper($request->input('sexe'));
            if ($sexe === 'M') {
                $query->whereHas('candidat', function ($q) {
                    $q->where('Civ', 1);
                });
            } elseif ($sexe === 'F') {
                $query->whereHas('candidat', function ($q) {
                    $q->where('Civ', 2);
                });
            }
        }

        // 5. Full text search (Nom, Prenom, Nccp)
        if ($request->filled('q')) {
            $term = trim($request->input('q'));
            $query->searchQuery($term);
        }

        try {
            // Paginate results with a default/cap of 50 records
            $paginator = $query->paginate(50);

            // Map and format items to keep the REST contract clean and language-structured
            $data = collect($paginator->items())->map(function ($item) {
                return [
                    'id'               => $item->IDapprenant,
                    'num_acte'         => $item->NumActe,
                    'valide'           => ($item->statut === 'actif') ? 1 : 0,
                    'groupe'           => $item->Groupe,
                    'nom_ar'           => $item->candidat?->Nom,
                    'nom_fr'           => $item->candidat?->NomFr,
                    'prenom_ar'        => $item->candidat?->Prenom,
                    'prenom_fr'        => $item->candidat?->PrenomFr,
                    'date_naissance'   => $item->candidat?->DateNais,
                    'sexe'             => ($item->candidat?->Civ == 2) ? 'F' : 'M',
                    'specialite_ar'    => $item->section?->offre?->specialite?->Nom,
                    'specialite_fr'    => $item->section?->offre?->specialite?->NomFr,
                    'etablissement_ar' => $item->section?->offre?->etablissement?->Nom,
                    'etablissement_fr' => $item->section?->offre?->etablissement?->NomFr,
                    'section_nom'      => $item->section?->Nom,
                    'offre_id'         => $item->section?->offre?->IDOffre,
                ];
            });

            return response()->json([
                'status'     => 'success',
                'code'       => 200,
                'pagination' => [
                    'total'        => $paginator->total(),
                    'page'         => $paginator->currentPage(),
                    'limit'        => $paginator->perPage(),
                    'total_pages'  => $paginator->lastPage(),
                    'has_next'     => $paginator->hasMorePages(),
                    'has_prev'     => $paginator->currentPage() > 1,
                ],
                'count'      => $data->count(),
                'data'       => $data,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Failed to retrieve trainees list.',
                'detail'  => $e->getMessage(),
            ], 500);
        }
    }
}
