<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use OpenApi\Attributes as OA;
use App\Models\Encadrement;
use Illuminate\Http\Request;

class EmployeesApiController extends Controller
{
    /**
     * GET /api/v1/employees
     * 
     * Returns a paginated list of employees/staff (encadrement).
     * Supports search and filtering by establishment, grade, and function.
     */
    #[OA\Get(
        path: "/api/v1/employees",
        summary: "جلب قائمة الموظفين والمؤطرين مع الفلترة والبحث",
        tags: ["Employees"],
        security: [["bearerAuth" => []], ["ApiKeyAuth" => []]],
        parameters: [
            new OA\Parameter(name: "etablissement_id", in: "query", description: "معرف المؤسسة", required: false, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "grade", in: "query", description: "رتبة الموظف", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "fonction", in: "query", description: "وظيفة الموظف", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "q", in: "query", description: "بحث بالاسم أو اللقب أو البريد الإلكتروني أو رقم التعريف الوطني", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 1)),
            new OA\Parameter(name: "limit", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 20))
        ],
        responses: [
            new OA\Response(response: 200, description: "تم جلب قائمة الموظفين بنجاح"),
            new OA\Response(response: 401, description: "غير مصرح بالدخول")
        ]
    )]
    public function index(Request $request)
    {
        $query = Encadrement::with('etablissement');

        // 1. Filter by establishment ID
        if ($request->filled('etablissement_id')) {
            $etabId = (int)$request->input('etablissement_id');
            $query->where('IDetablissement', $etabId);
        }

        // 2. Filter by grade
        if ($request->filled('grade')) {
            $query->where('Grade', 'like', '%' . $request->input('grade') . '%');
        }

        // 3. Filter by function
        if ($request->filled('fonction')) {
            $query->where('Fonction', 'like', '%' . $request->input('fonction') . '%');
        }

        // 4. Full text search (Nom, Prenom, Email, nin)
        if ($request->filled('q')) {
            $term = trim($request->input('q'));
            $query->where(function ($q) use ($term) {
                $q->where('Nom', 'like', "%{$term}%")
                  ->orWhere('Prenom', 'like', "%{$term}%")
                  ->orWhere('Email', 'like', "%{$term}%")
                  ->orWhere('nin', 'like', "%{$term}%");
            });
        }

        try {
            // Paginate results with a default/cap of 50 records
            $limit = min(50, max(1, (int)$request->input('limit', 20)));
            $paginator = $query->paginate($limit);

            // Format items to keep the REST contract clean
            $data = collect($paginator->items())->map(function ($item) {
                return [
                    'id'               => $item->IDEncadrement,
                    'nom'              => $item->Nom,
                    'prenom'           => $item->Prenom,
                    'nom_complet'      => $item->nom_complet,
                    'nin'              => $item->nin,
                    'email'            => $item->Email,
                    'grade'            => $item->Grade,
                    'fonction'         => $item->Fonction,
                    'etablissement'    => $item->etablissement ? [
                        'id'     => $item->etablissement->IDetablissement,
                        'nom_ar' => $item->etablissement->Nom,
                        'nom_fr' => $item->etablissement->NomFr,
                        'code'   => $item->etablissement->Code
                    ] : null,
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
                'message' => 'Failed to retrieve employees list.',
                'detail'  => $e->getMessage(),
            ], 500);
        }
    }
}
