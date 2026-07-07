<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use OpenApi\Attributes as OA;

use PDO;

/**
 * PortalApiController
 *
 * Public-facing REST endpoints consumed by ministerial portals.
 * All endpoints require JWT or X-API-Key authentication (enforced by
 * JwtAuthMiddleware in the router).
 *
 * Mapped to WINDEV legacy schema:
 *   stagiaires      → apprenant (linked to preinscrit via IDPreinscrit not used directly)
 *   candidats       → preinscrit  (pre-registered candidates)
 *   offres_formation → offre
 *   specialites      → specialite
 *   etablissements   → etablissement
 *   sessions_formation → session
 *
 * Pagination: ?page=1&limit=50 (max limit capped at 200)
 */
class PortalApiController extends Controller
{
    protected $db;

    /** Hard cap to prevent runaway queries */
    private const MAX_LIMIT = 200;

    public function __construct()
    {
        if (app()->runningInConsole()) { return; }
        $this->db = new \App\Core\LaravelDbAdapter();
    }

    // -------------------------------------------------------------------------
    // Auth helper
    // -------------------------------------------------------------------------

    private function validateRequest(): array
    {
        $user = \App\Core\Router::$authenticatedUser;
        if (!$user) {
            throw new \Illuminate\Http\Exceptions\HttpResponseException(response()->json([
                'status'  => 'error',
                'code'    => 401,
                'message' => 'Unauthorized access. Authentication failed.',
            ], 401));
        }
        return $user;
    }

    // -------------------------------------------------------------------------
    // Pagination helper
    // -------------------------------------------------------------------------

    /**
     * Parse ?page and ?limit from GET, return [page, limit, offset].
     */
    private function pagination(): array
    {
        $page  = max(1, (int)(request()->all()['page']  ?? 1));
        $limit = min(self::MAX_LIMIT, max(1, (int)(request()->all()['limit'] ?? 50)));
        return [$page, $limit, ($page - 1) * $limit];
    }

    /**
     * Build a standard paginated JSON response envelope.
     */
    private function paginatedResponse(array $data, int $total, int $page, int $limit): \Illuminate\Http\JsonResponse
    {
        return $this->json([
            'status' => 'success',
            'code'   => 200,
            'pagination' => [
                'total'        => $total,
                'page'         => $page,
                'limit'        => $limit,
                'total_pages'  => (int)ceil($total / $limit),
                'has_next'     => ($page * $limit) < $total,
                'has_prev'     => $page > 1,
            ],
            'count' => count($data),
            'data'  => $data,
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/verify
    // -------------------------------------------------------------------------

    #[OA\Get(
        path: "/api/v1/verify",
        summary: "التحقق من صلاحية مفتاح API أو التوكن الحالي",
        tags: ["System"],
        security: [["bearerAuth" => []], ["ApiKeyAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "المفتاح صالح ومصادق عليه",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "string", example: "success"),
                        new OA\Property(property: "code", type: "integer", example: 200),
                        new OA\Property(property: "message", type: "string", example: "API Key verified successfully."),
                        new OA\Property(property: "authenticated_as", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "غير مصرح بالدخول - المفتاح أو التوكن غير صالح")
        ]
    )]
    public function verify(): mixed
    {
        $user = $this->validateRequest();
        return $this->json([
            'status'  => 'success',
            'code'    => 200,
            'message' => 'API Key verified successfully.',
            'authenticated_as' => [
                'name'       => $user['nom_complet'],
                'username'   => $user['username'],
                'role'       => $user['role_code'],
                'role_label' => $user['role_ar'],
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/stagiaires
    //
    // Returns enrolled trainees from WINDEV legacy tables:
    //   apprenant  — enrolled trainee record (IDapprenant, IDSection, Valide, Groupe)
    //   section    — section linked to apprenant (IDOffre, IDSpecialite)
    //   offre      — training offer (IDEts_Form, IDSpecialite, IDSession)
    //   specialite — specialty (Nom, NomFr)
    //   etablissement — institution (Nom, IDDFEP)
    //   preinscrit — pre-registration with personal data (Nom, Prenom, DateNais, Civ)
    //
    // Filters (all optional, combined with AND):
    //   ?valide=1|0           — Valide flag on apprenant
    //   ?etablissement_id=N   — IDEts_Form on offre
    //   ?specialite_id=N      — IDSpecialite on section
    //   ?sexe=M|F             — Civ on preinscrit (1=M, 2=F)
    //   ?q=<search>           — searches Nom, Prenom, NomFr, PrenomFr on preinscrit
    //   ?page=N&limit=N
    // -------------------------------------------------------------------------

    public function getStagiaires(): mixed
    {
        $this->validateRequest();

        [$page, $limit, $offset] = $this->pagination();

        // --- Build WHERE clause ---
        $where  = ['1=1'];
        $params = [];

        // Filter by validity status
        $valide = request()->all()['valide'] ?? '';
        if ($valide !== '' && in_array($valide, ['0', '1'], true)) {
            $where[]           = 'a.statut = :statut';
            $params['statut']  = ($valide === '1') ? 'actif' : 'inactif';
        }

        // Filter by institution
        $etabId = isset(request()->all()['etablissement_id']) ? (int)request()->all()['etablissement_id'] : null;
        if ($etabId > 0) {
            $where[]                    = 'o.IDEts_Form = :etablissement_id';
            $params['etablissement_id'] = $etabId;
        }

        // Filter by specialty
        $specId = isset(request()->all()['specialite_id']) ? (int)request()->all()['specialite_id'] : null;
        if ($specId > 0) {
            $where[]                 = 'sec.IDSpecialite = :specialite_id';
            $params['specialite_id'] = $specId;
        }

        // Filter by gender — Civ: 1=Male, 2=Female in WINDEV candidat
        $sexe = strtoupper(request()->all()['sexe'] ?? '');
        if ($sexe === 'M') {
            $where[]      = 'c.Civ = 1';
        } elseif ($sexe === 'F') {
            $where[]      = 'c.Civ = 2';
        }

        // Full-text search
        $q = trim(request()->all()['q'] ?? '');
        if ($q !== '') {
            $where[]    = '(c.Nom LIKE :q OR c.Prenom LIKE :q OR c.NomFr LIKE :q OR c.PrenomFr LIKE :q)';
            $params['q'] = '%' . $q . '%';
        }

        $whereSQL = implode(' AND ', $where);

        // Dynamically determine which joins are needed for the COUNT query to avoid scanning extra tables
        $needsSection = ($specId > 0) || ($etabId > 0);
        $needsOffre = ($etabId > 0);
        $needsCandidat = ($sexe !== '' || $q !== '');

        $countJoins = "";
        if ($needsSection) {
            $countJoins .= " JOIN section sec ON a.IDSection = sec.IDSection";
        }
        if ($needsOffre) {
            $countJoins .= " JOIN offre o ON sec.IDOffre = o.IDOffre";
        }
        if ($needsCandidat) {
            $countJoins .= " JOIN candidat c ON a.IDCandidat = c.IDCandidat";
        }

        // Use COUNT(*) if no joins are needed (primary key ensures unique IDapprenant)
        $countSelect = ($needsSection || $needsOffre || $needsCandidat) ? "COUNT(DISTINCT a.IDapprenant)" : "COUNT(*)";

        // apprenant → section → offre → specialite + etablissement + candidat (STRAIGHT_JOIN order optimized)
        $baseJoins = "
            FROM apprenant a
            JOIN section sec          ON a.IDSection = sec.IDSection
            JOIN offre o              ON sec.IDOffre = o.IDOffre
            JOIN specialite sp        ON sec.IDSpecialite = sp.IDSpecialite
            JOIN etablissement e      ON o.IDEts_Form = e.IDetablissement
            LEFT JOIN candidat c      ON a.IDCandidat = c.IDCandidat
        ";

        try {
            // COUNT query
            $countStmt = $this->db->prepare("SELECT $countSelect $countJoins WHERE $whereSQL");
            if (empty($countJoins)) {
                // If no joins, we run count directly on the main table for instant results
                $countStmt = $this->db->prepare("SELECT $countSelect FROM apprenant a WHERE $whereSQL");
            }
            $countStmt->execute($params);
            $total = (int)$countStmt->fetchColumn();

            // DATA query (STRAIGHT_JOIN forces MySQL to stream first 50 rows using IDapprenant primary index instantly)
            $dataStmt = $this->db->prepare("
                SELECT STRAIGHT_JOIN
                    a.IDapprenant        AS id,
                    a.NumActe            AS num_acte,
                    IF(a.statut = 'actif', 1, 0) AS valide,
                    a.Groupe             AS groupe,
                    c.Nom               AS nom_ar,
                    c.NomFr             AS nom_fr,
                    c.Prenom            AS prenom_ar,
                    c.PrenomFr          AS prenom_fr,
                    c.DateNais          AS date_naissance,
                    c.Civ               AS civ,
                    sp.Nom               AS specialite_ar,
                    sp.NomFr             AS specialite_fr,
                    e.Nom                AS etablissement_ar,
                    e.NomFr              AS etablissement_fr,
                    sec.Nom              AS section_nom,
                    o.IDOffre            AS offre_id
                $baseJoins
                WHERE $whereSQL
                ORDER BY a.IDapprenant ASC
                LIMIT :limit OFFSET :offset
            ");

            foreach ($params as $k => $v) {
                $dataStmt->bindValue($k, $v);
            }
            $dataStmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
            $dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $dataStmt->execute();

            $stagiaires = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

            // Normalize gender display
            foreach ($stagiaires as &$s) {
                $s['sexe'] = ($s['civ'] == 2) ? 'F' : 'M';
                unset($s['civ']);
            }
            unset($s);

            return $this->paginatedResponse($stagiaires, $total, $page, $limit);
        } catch (\Exception $e) {
            return $this->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Failed to retrieve trainees list.',
                'detail'  => $e->getMessage(),
            ], 500);
        }
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/offres
    //
    // Returns training offers from WINDEV legacy tables:
    //   offre      — training offer (IDOffre, IDSession, IDSpecialite, IDEts_Form,
    //                 DateD, DateF, NbrInscr, nbrPrevision, Valide, ValidDfp, ValideCentral)
    //   specialite — specialty (Nom, NomFr, CodeSpec)
    //   etablissement — institution (Nom, NomFr)
    //   session    — training session (Nom, NomFr, DateD, DateF)
    //   mode_formation — formation mode (Nom, NomFr, Abr)
    //
    // Filters (all optional):
    //   ?etablissement_id=N
    //   ?specialite_id=N
    //   ?session_id=N
    //   ?valide_centrale=1  — only centrally approved (ValideCentral=1)
    //   ?page=N&limit=N
    // -------------------------------------------------------------------------

    #[OA\Get(
        path: "/api/v1/offres",
        summary: "جلب عروض التكوين المتوفرة مع الفلترة",
        tags: ["Offers"],
        security: [["bearerAuth" => []], ["ApiKeyAuth" => []]],
        parameters: [
            new OA\Parameter(name: "etablissement_id", in: "query", description: "معرف المؤسسة", required: false, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "specialite_id", in: "query", description: "معرف التخصص", required: false, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "session_id", in: "query", description: "معرف الدورة التكوينية", required: false, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 1)),
            new OA\Parameter(name: "limit", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 50))
        ],
        responses: [
            new OA\Response(response: 200, description: "تم جلب عروض التكوين بنجاح"),
            new OA\Response(response: 401, description: "غير مصرح بالدخول")
        ]
    )]
    public function getOffres(): mixed
    {
        $this->validateRequest();

        [$page, $limit, $offset] = $this->pagination();

        // --- Build WHERE clause ---
        $where  = ['1=1'];
        $params = [];

        // Filter by institution
        $etabId = isset(request()->all()['etablissement_id']) ? (int)request()->all()['etablissement_id'] : null;
        if ($etabId > 0) {
            $where[]                    = 'o.IDEts_Form = :etablissement_id';
            $params['etablissement_id'] = $etabId;
        }

        // Filter by specialty
        $specId = isset(request()->all()['specialite_id']) ? (int)request()->all()['specialite_id'] : null;
        if ($specId > 0) {
            $where[]                 = 'o.IDSpecialite = :specialite_id';
            $params['specialite_id'] = $specId;
        }

        // Filter by session
        $sessionId = isset(request()->all()['session_id']) ? (int)request()->all()['session_id'] : null;
        if ($sessionId > 0) {
            $where[]              = 'o.IDSession = :session_id';
            $params['session_id'] = $sessionId;
        }

        // Filter by central approval status
        if (isset(request()->all()['valide_centrale']) && request()->all()['valide_centrale'] === '1') {
            $where[] = 'o.ValideCentral = 1';
        }

        $whereSQL = implode(' AND ', $where);

        $baseJoins = "
            FROM offre o
            LEFT JOIN specialite sp       ON o.IDSpecialite = sp.IDSpecialite
            LEFT JOIN etablissement e     ON o.IDEts_Form = e.IDetablissement
            LEFT JOIN session sess        ON o.IDSession = sess.IDSession
            LEFT JOIN mode_formation mf   ON (CASE o.IDMode_formation WHEN 'residentiel' THEN 1 WHEN 'apprentissage' THEN 10 WHEN 'distance' THEN 21 WHEN 'soir' THEN 3 ELSE o.IDMode_formation END) = mf.IDMode_formation
        ";

        try {
            $countStmt = $this->db->prepare("SELECT COUNT(DISTINCT o.IDOffre) $baseJoins WHERE $whereSQL");
            $countStmt->execute($params);
            $total = (int)$countStmt->fetchColumn();

            $dataStmt = $this->db->prepare("
                SELECT
                    o.IDOffre             AS id,
                    o.IDSession           AS session_id,
                    sess.Nom              AS session_ar,
                    sess.NomFr            AS session_fr,
                    sess.DateD            AS session_date_debut,
                    sess.DateF            AS session_date_fin,
                    o.IDSpecialite        AS specialite_id,
                    sp.Nom                AS specialite_ar,
                    sp.NomFr              AS specialite_fr,
                    sp.CodeSpec           AS specialite_code,
                    o.IDEts_Form          AS etablissement_id,
                    e.Nom                 AS etablissement_ar,
                    e.NomFr               AS etablissement_fr,
                    mf.Nom                AS mode_formation_ar,
                    mf.NomFr              AS mode_formation_fr,
                    mf.Abr                AS mode_formation_abr,
                    o.DateD               AS date_debut,
                    o.DateF               AS date_fin,
                    o.DateSelection       AS date_selection,
                    o.DateVisiteMedical   AS date_visite_medical,
                    o.DateVisiteAtelier   AS date_visite_atelier,
                    o.NbrInscr            AS inscrits,
                    o.nbrPrevision        AS places_prevues,
                    o.nbrGroupe           AS nbr_groupes,
                    o.Valide              AS valide_etablissement,
                    o.ValidDfp            AS valide_dfep,
                    o.ValideCentral       AS valide_centrale,
                    o.Obs                 AS observation
                $baseJoins
                WHERE $whereSQL
                ORDER BY o.IDOffre DESC
                LIMIT :limit OFFSET :offset
            ");

            foreach ($params as $k => $v) {
                $dataStmt->bindValue($k, $v);
            }
            $dataStmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
            $dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $dataStmt->execute();

            $offres = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

            // Enrich with derived status label
            foreach ($offres as &$o) {
                if ($o['valide_centrale']) {
                    $o['statut'] = 'مقبول مركزيا';
                } elseif ($o['valide_dfep']) {
                    $o['statut'] = 'مصادق عليه ولائيا';
                } elseif ($o['valide_etablissement']) {
                    $o['statut'] = 'مرفوع للولاية';
                } else {
                    $o['statut'] = 'مسودة';
                }
            }
            unset($o);

            return $this->paginatedResponse($offres, $total, $page, $limit);
        } catch (\Exception $e) {
            return $this->json([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Failed to retrieve offers list.',
                'detail'  => $e->getMessage(),
            ], 500);
        }
    }
}
