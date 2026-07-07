<?php

namespace App\Domains\Reporting\Services;

use App\Domains\Reporting\Contracts\ReportEngineInterface;
use App\Domains\Reporting\Engines\CsvEngine;
use App\Domains\Reporting\Engines\HtmlPrintEngine;
use App\Domains\Reporting\Engines\PdfEngine;
use App\Domains\Academic\Repositories\ApprenantRepository;
use App\Domains\Academic\Repositories\CandidatRepository;
use App\Domains\Academic\Repositories\OffresRepository;
use App\Domains\Security\AuthorizationService;
use Exception;

/**
 * ReportingService
 *
 * Central orchestrator for all data export/print operations.
 *
 * Responsibilities:
 *   1. Validate the requested report type and format
 *   2. Authorize the requesting user (delegates to AuthorizationService)
 *   3. Build the role-scoped SQL filter (same logic as domain Services)
 *   4. Resolve the correct Repository Generator (lazy, O(1) memory)
 *   5. Resolve the output Engine (CSV or HTML)
 *   6. Stream output — zero disk I/O, zero full dataset in RAM
 *
 * Supported report types:
 *   - 'apprenants'   → active trainees list
 *   - 'candidats'    → pre-inscription candidates list
 *   - 'offres'       → training offers catalog
 *   - 'absences'     → absence report
 *
 * Supported formats:
 *   - 'csv'          → CsvEngine (UTF-8 BOM, Excel-compatible)
 *   - 'print' / 'html' → HtmlPrintEngine (RTL Arabic, browser print)
 */
class ReportingService
{
    protected ApprenantRepository  $apprenantRepo;
    protected CandidatRepository   $candidatRepo;
    protected OffresRepository     $offresRepo;
    protected AuthorizationService $authService;

    public function __construct(
        ApprenantRepository  $apprenantRepo,
        CandidatRepository   $candidatRepo,
        OffresRepository     $offresRepo,
        AuthorizationService $authService
    ) {
        $this->apprenantRepo = $apprenantRepo;
        $this->candidatRepo  = $candidatRepo;
        $this->offresRepo    = $offresRepo;
        $this->authService   = $authService;
    }

    // ─── Public API ───────────────────────────────────────────────────────────

    /**
     * Resolve and stream the requested report.
     *
     * This method MUST be the last thing called in the controller action —
     * it sets HTTP headers and streams directly to php://output.
     *
     * @param  string $reportType  'apprenants' | 'candidats' | 'offres' | 'absences'
     * @param  string $format      'csv' | 'print'
     * @param  array  $user        $_SESSION['user']
     * @param  array  $filters     Optional GET filters (status, etab, session, etc.)
     * @throws Exception
     */
    public function generate(string $reportType, string $format, array $user, array $filters = []): void
    {
        // 1. Authorization
        $this->authService->authorize($user, 'reports.export');

        // 2. Resolve engine
        $engine = $this->resolveEngine($format);

        // 3. Resolve data generator + column map
        [$generator, $columns] = $this->resolveReport($reportType, $user, $filters);

        // 4. Build metadata block
        $meta = [
            'report_type'  => $reportType,
            'title'        => $this->reportTitle($reportType),
            'generated_by' => $user['username'] ?? ($user['nom'] ?? ''),
            'generated_at' => date('Y-m-d H:i:s'),
            'filters'      => $filters,
        ];

        // 5. Stream — no return value; output goes directly to HTTP response
        $engine->stream($generator, $columns, $meta);
    }

    // ─── Engine Resolution ────────────────────────────────────────────────────

    private function resolveEngine(string $format): ReportEngineInterface
    {
        return match(strtolower($format)) {
            'csv'           => new CsvEngine(),
            'print', 'html' => new HtmlPrintEngine(),
            'pdf'           => new PdfEngine(),
            default         => throw new Exception("صيغة التصدير غير مدعومة: {$format}"),
        };
    }

    // ─── Report Resolution ────────────────────────────────────────────────────

    /**
     * Returns [$generator, $columns] for the given report type.
     */
    private function resolveReport(string $reportType, array $user, array $filters): array
    {
        return match($reportType) {
            'apprenants'          => $this->reportApprenants($user, $filters),
            'candidats'           => $this->reportCandidats($user, $filters),
            'offres'              => $this->reportOffres($user, $filters),
            'absences'            => $this->reportAbsences($user, $filters),
            'specialites_encours' => $this->reportSpecialitesEnCours($user, $filters),
            default               => throw new Exception("نوع التقرير غير مدعوم: {$reportType}"),
        };
    }

    // ─── Individual Report Builders ───────────────────────────────────────────

    /**
     * Active trainees report — optimized O(N) query (no correlated subquery)
     */
    private function reportApprenants(array $user, array $filters): array
    {
        [$extraWhere, $params] = $this->buildSectionFilter($user);

        $maxRows   = (int)($filters['max_rows'] ?? 10000);
        $generator = $this->apprenantRepo->streamActiveChunked($extraWhere, $params, 500, $maxRows);

        $columns = [
            'matricule'    => 'رقم التسجيل (Matricule)',
            'nom_ar'       => 'اللقب (عربي)',
            'prenom_ar'    => 'الاسم (عربي)',
            'nom_fr'       => 'اللقب (فرنسي)',
            'prenom_fr'    => 'الاسم (فرنسي)',
            'sexe_label'   => 'الجنس',
            'specialite_ar'=> 'التخصص',
            'etab_nom'     => 'المركز التكويني',
            'statut_label' => 'الحالة',
        ];

        return [$generator, $columns];
    }

    /**
     * Candidats (pre-inscriptions) report
     */
    private function reportCandidats(array $user, array $filters): array
    {
        [$extraWhere, $params] = $this->buildOffreFilter($user);

        $statusFilter = $filters['status'] ?? 'all';
        $generator    = $this->candidatRepo->streamCandidatsChunked($extraWhere, $params, $statusFilter);

        $columns = [
            'numero_inscription' => 'رقم التسجيل',
            'nom_ar'             => 'اللقب (عربي)',
            'prenom_ar'          => 'الاسم (عربي)',
            'nom_fr'             => 'اللقب (فرنسي)',
            'prenom_fr'          => 'الاسم (فرنسي)',
            'telephone'          => 'رقم الهاتف',
            'specialite_ar'      => 'التخصص',
            'decision'           => 'القرار',
        ];

        return [$generator, $columns];
    }

    /**
     * Training offers report
     */
    private function reportOffres(array $user, array $filters): array
    {
        [$extraWhere, $params] = $this->buildOffreEtabFilter($user);

        $generator = $this->offresRepo->streamOffresChunked($extraWhere, $params);

        $columns = [
            'code'           => 'رمز العرض',
            'spec_ar'        => 'التخصص (عربي)',
            'spec_fr'        => 'التخصص (فرنسي)',
            'diplome_vise'   => 'الشهادة المستهدفة',
            'mode_formation' => 'نمط التكوين',
            'etab_ar'        => 'المركز التكويني',
            'session_name'   => 'الدورة',
            'capacite'       => 'الطاقة الاستيعابية',
            'inscrits'       => 'عدد المتربصين',
            'date_debut'     => 'تاريخ البداية',
            'date_fin'       => 'تاريخ النهاية',
        ];

        return [$generator, $columns];
    }

    /**
     * Absences report — delegates to ApprenantRepository
     */
    private function reportAbsences(array $user, array $filters): array
    {
        [$extraWhere, $params] = $this->buildSectionFilter($user);

        $generator = $this->apprenantRepo->streamAbsencesChunked($extraWhere, $params);

        $columns = [
            'nom_ar'          => 'اللقب',
            'prenom_ar'       => 'الاسم',
            'numero_matricule' => 'رقم التسجيل',
            'specialite_ar'   => 'التخصص',
            'date_absence'    => 'تاريخ الغياب',
            'heure'           => 'الوقت',
            'Type'            => 'نوع الغياب',
        ];

        return [$generator, $columns];
    }

    /**
     * Specialties in progress report — delegates to ApprenantRepository
     */
    private function reportSpecialitesEnCours(array $user, array $filters): array
    {
        [$extraWhere, $params] = $this->buildSectionFilter($user);

        $maxRows   = isset($filters['max_rows']) ? (int)$filters['max_rows'] : null;
        $generator = $this->apprenantRepo->streamSpecialitesEnCoursChunked($extraWhere, $params, 200, $maxRows);

        $columns = [
            'id_offre'            => 'id offre',
            'wilaya'              => 'wilaya',
            'id_etablissement'    => 'id etablissement',
            'nom_etablissement'   => 'nom etablissement',
            'nature_etablissement'=> 'nature etablissement (cfpa, institut….)',
            'id_specialite'       => 'ID Specialite',
            'code_specialite'     => 'code Specialite',
            'nom_specialite'      => 'nom de spécialité',
            'nom_formation'       => 'nom de formation',
            'id_session'          => 'ID Session',
            'nom_session'         => 'Nom Session',
            'id_mode_formation'   => 'ID mode de formation',
            'nom_mode_formation'  => 'nom de mode formation',
            'regime_cours'        => "régime d'études",
            'hebergement'         => "régime d'hébergement",
            'numero_semestre'     => 'Numero de semestre',
            'nombre_stagiaires'   => 'Nombre de stagiaires',
            'equipements'         => 'Equipements',
        ];

        return [$generator, $columns];
    }

    // ─── Role-Scoped SQL Filters ──────────────────────────────────────────────

    /**
     * Filter scoped to section.IDDFEP or offre.IDEts_Form (for trainee/absence queries)
     * Logic consistent with AbsencesController::getEtabFilter()
     */
    private function buildSectionFilter(array $user): array
    {
        $role   = strtolower($user['role_code'] ?? '');
        $etabId = (int)($user['etablissement_id'] ?? 0);
        $dfepId = (int)($user['iddfep'] ?? 0);

        if (in_array($role, ['admin', 'central'])) {
            return ['', []];
        }
        if ($role === 'dfep' && $dfepId > 0) {
            return [' AND s.IDDFEP = ?', [$dfepId]];
        }
        if ($etabId > 0) {
            return [' AND s.IDOffre IN (SELECT IDOffre FROM offre WHERE IDEts_Form = ?)', [$etabId]];
        }
        return ['', []];
    }

    /**
     * Filter scoped on offre.IDEts_Form (for candidat queries)
     * Logic consistent with CandidateController::index()
     */
    private function buildOffreFilter(array $user): array
    {
        $role   = strtolower($user['role_code'] ?? '');
        $dfepId = $user['iddfep'] ?? null;
        $etabId = $user['etablissement_id'] ?? null;

        if ($role === 'dfep' && $dfepId) {
            return [
                ' AND o.IDEts_Form IN (SELECT IDetablissement FROM etablissement WHERE IDDFEP = ?)',
                [$dfepId],
            ];
        }
        if (in_array($role, ['etablissement', 'directeur', 'formateur']) && $etabId) {
            return [' AND o.IDEts_Form = ?', [$etabId]];
        }
        return ['', []];
    }

    /**
     * Filter for offre queries (etablissement join)
     * Logic consistent with OffresController::index()
     */
    private function buildOffreEtabFilter(array $user): array
    {
        $role   = strtolower($user['role_code'] ?? '');
        $etabId = (int)($user['etablissement_id'] ?? 0);
        $dfepId = (int)($user['iddfep'] ?? 0);

        if (in_array($role, ['admin', 'central'])) {
            return ['1=1', []];
        }
        if ($role === 'dfep' && $dfepId > 0) {
            return ['e.IDDFEP = ?', [$dfepId]];
        }
        if ($etabId > 0) {
            return ['o.IDEts_Form = ?', [$etabId]];
        }
        return ['1=1', []];
    }

    // ─── Utilities ────────────────────────────────────────────────────────────

    private function reportTitle(string $type): string
    {
        return match($type) {
            'apprenants'          => 'قائمة المتربصين النشطين',
            'candidats'           => 'قائمة المترشحين',
            'offres'              => 'كشف عروض التكوين',
            'absences'            => 'تقرير الغيابات',
            'specialites_encours' => 'تقرير التخصصات في طور التكوين',
            default               => 'تصدير البيانات',
        };
    }
}
