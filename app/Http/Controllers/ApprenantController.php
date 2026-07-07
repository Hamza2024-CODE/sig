<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * ApprenantController — لوحة تحكم المتربص
 *
 * يعرض:
 *  - بطاقة المتكون
 *  - النتائج (موادّ + معدلات)
 *  - استعمال الزمن (Emploi du Temps)
 *  - الوثائق (وثيقة التسجيل، الشهادة إن وُجدت)
 *
 * الوصول: role_code = 'apprenant' فقط
 */
class ApprenantController extends Controller
{
    /**
     * ApprenantController constructor.
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = session('user');
            if (!$user || ($user['role_code'] ?? '') !== 'apprenant') {
                return redirect()->route('login')->withErrors(['auth' => 'هذه الصفحة مخصصة للمتربصين فقط.']);
            }
            return $next($request);
        });
    }

    /**
     * التحقق من أن المستخدم متربص مُصادَق عليه.
     */
    private function getApprenant(Request $request): ?array
    {
        $user = session('user');
        if (!$user || ($user['role_code'] ?? '') !== 'apprenant') {
            return null;
        }
        return $user;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Dashboard الرئيسي
    // ─────────────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $user = $this->getApprenant($request);
        if (!$user) return redirect()->route('login')->withErrors(['auth' => 'يرجى تسجيل الدخول أولاً.']);

        $apprenantId = $user['id'];

        // ── بيانات المتربص الكاملة ──────────────────────────────────────
        $apprenant = DB::selectOne("
            SELECT a.IDapprenant, a.Nccp, a.statut, a.IDSection, c.IDCandidat,
                   c.Nom, c.Prenom, c.NomFr, c.PrenomFr, c.Nin,
                   c.DateNais, c.LieuNais, c.Civ, c.photo,
                   s.Nom AS section_nom, s.IDOffre,
                   o.IDEts_Form, o.IDSpecialite,
                   sp.Nom AS specialite_nom, sp.NomFr AS specialite_fr,
                   ni.Nom AS niveau_nom,
                   e.Nom AS etab_nom, e.NomFr AS etab_fr,
                   e.Adres AS etab_adresse, e.Tel AS etab_tel,
                   af.IDApprenant_Fin AS id_fin,
                   mf.Nom AS mode_nom
            FROM apprenant a
            JOIN candidat c ON c.IDCandidat = a.IDCandidat
            LEFT JOIN section s ON s.IDSection = a.IDSection
            LEFT JOIN offre o ON o.IDOffre = s.IDOffre
            LEFT JOIN mode_formation mf ON mf.IDMode_formation = o.IDMode_formation
            LEFT JOIN specialite sp ON sp.IDSpecialite = o.IDSpecialite
            LEFT JOIN niveau_fp ni ON ni.IDNiveau_Fp = sp.IDNiveau_Fp
            LEFT JOIN etablissement e ON e.IDetablissement = o.IDEts_Form
            LEFT JOIN apprenant_fin af ON af.IDApprenant = a.IDapprenant
            WHERE a.IDapprenant = ?
            LIMIT 1
        ", [$apprenantId]);

        if (!$apprenant) abort(404, 'المتربص غير موجود');

        // ── نتائج الفصول (Semestres) ───────────────────────────────────
        $semestres = DB::select("
            SELECT ssem.IDSection_Semestre, ssem.IDSemestre_formation,
                   sem.Nom AS sem_nom, ssem.NumSem,
                   ssem.DateD AS DateDebut, ssem.DateF AS DateFin,
                   aps.MoyApr AS moyenne_generale,
                   aps.IDDecision_evals,
                   (SELECT COUNT(*) FROM apprenant_section_semstre_module WHERE IDapprenant_Section_semstre = aps.IDapprenant_Section_semstre) AS nb_modules
            FROM section_semestre ssem
            JOIN semestre_formation sem ON sem.IDSemestre_formation = ssem.IDSemestre_formation
            LEFT JOIN apprenant_section_semstre aps ON aps.IDSection_Semestre = ssem.IDSection_Semestre AND aps.IDapprenant = ?
            WHERE ssem.IDSection = ?
            ORDER BY ssem.NumSem ASC
        ", [$apprenantId, $apprenant->IDSection ?? 0]);

        // ── نتائج المواد للفصل الحالي ──────────────────────────────────
        $notes = DB::select("
            SELECT ssm.NomMdl AS module_nom, ssm.coef AS Coef, ssm.VolHor AS NbrH,
                   assm.NoteC1, assm.NoteC2, assm.NoteCs, assm.NoteR,
                   assm.MoyAvr, assm.MoyApr, assm.Obs,
                   assm.absc1, assm.absc2,
                   de.Nom AS decision,
                   ass.IDSection_Semestre
            FROM apprenant_section_semstre_module assm
            JOIN apprenant_section_semstre ass ON ass.IDapprenant_Section_semstre = assm.IDapprenant_Section_semstre
            JOIN section_semestre_module ssm ON ssm.IDsection_semestre_Module = assm.IDsection_semestre_Module
            LEFT JOIN decision_eval_mdl de ON de.IDDecision_Eval_Mdl = assm.IDDecision_Eval_Mdl
            WHERE ass.IDapprenant = ?
            ORDER BY ssm.NumOrd ASC
        ", [$apprenantId]);

        // ── حساب المعدلات برمجياً للفصول الفارغة ─────────────────────────
        foreach ($semestres as $sem) {
            // تحديد حالة النجاح بناء على معطيات قاعدة البيانات أولا
            $decisionId = isset($sem->IDDecision_evals) ? (int)$sem->IDDecision_evals : null;
            if (in_array($decisionId, [1, 2, 3])) {
                $sem->is_admis_general = 1;
            } elseif (in_array($decisionId, [4, 5, 6, 8])) {
                $sem->is_admis_general = 0;
            } else {
                $sem->is_admis_general = null;
            }

            // إذا كان المعدل غير محسوب بقاعدة البيانات، نقوم باحتسابه برمجياً
            if (empty($sem->moyenne_generale) || $sem->moyenne_generale == 0) {
                $semNotes = collect($notes)->where('IDSection_Semestre', $sem->IDSection_Semestre);
                $totalPoints = 0;
                $totalCoef = 0;
                foreach ($semNotes as $n) {
                    $coef = (float)($n->Coef ?? 1);
                    $moyMdl = (float)($n->MoyApr ?? $n->MoyAvr ?? 0);
                    
                    // حساب معدل المادة يدوياً إذا كانت القيمة 0
                    if ($moyMdl == 0) {
                        $c1 = $n->NoteC1 !== null ? (float)$n->NoteC1 : null;
                        $c2 = $n->NoteC2 !== null ? (float)$n->NoteC2 : null;
                        $cs = $n->NoteCs !== null ? (float)$n->NoteCs : null;
                        $r = $n->NoteR !== null ? (float)$n->NoteR : null;

                        $evals = [];
                        if ($c1 !== null) $evals[] = $c1;
                        if ($c2 !== null) $evals[] = $c2;
                        
                        $base = count($evals) > 0 ? array_sum($evals) / count($evals) : 0;
                        if ($cs !== null) {
                            $moyMdl = ($base + $cs * 2) / 3;
                        } else {
                            $moyMdl = $base;
                        }
                        
                        if ($r !== null && $r > $moyMdl) {
                            $moyMdl = $r;
                        }
                    }
                    $totalPoints += $moyMdl * $coef;
                    $totalCoef += $coef;
                }
                if ($totalCoef > 0) {
                    $sem->moyenne_generale = $totalPoints / $totalCoef;
                    if ($sem->is_admis_general === null) {
                        $sem->is_admis_general = $sem->moyenne_generale >= 10 ? 1 : 0;
                    }
                }
            }
        }

        // ── استعمال الزمن ──────────────────────────────────────────────
        $emploiTemps = DB::select("
            SELECT et.Jour, et.Heured, et.Heuref, et.Crenaux, et.Duree,
                   ssm.NomMdl AS module_nom,
                   loc.Nom AS salle
            FROM emploitemp et
            JOIN section_semestre_module ssm ON ssm.IDsection_semestre_Module = et.IDsection_semestre_Module
            LEFT JOIN locaux loc ON loc.IDLocaux = et.IDLocaux
            WHERE ssm.IDSection_Semestre IN (
                SELECT IDSection_Semestre FROM section_semestre WHERE IDSection = ?
            )
            ORDER BY et.Jour ASC, et.Heured ASC
        ", [$apprenant->IDSection ?? 0]);

        // ── الوثائق ────────────────────────────────────────────────────
        $documents = DB::selectOne("
            SELECT * FROM candidat_document WHERE IDCandidat = ? LIMIT 1
        ", [$apprenant->IDCandidat ?? 0]);

        // ── الأساتذة الذين يدرسونهم ─────────────────────────────────────
        $teachers = DB::select("
            SELECT DISTINCT enc.IDEncadrement, enc.Nom, enc.Prenom, enc.Tel, enc.Email,
                   ssm.NomMdl AS module_nom
            FROM section_semestre_module ssm
            JOIN encadrement enc ON enc.IDEncadrement = ssm.IDEncadrement
            WHERE ssm.IDSection_Semestre IN (
                SELECT IDSection_Semestre FROM section_semestre WHERE IDSection = ?
            )
        ", [$apprenant->IDSection ?? 0]);

        // ── الشهادة / الدبلوم ──────────────────────────────────────────
        $estDiplome = !is_null($apprenant->id_fin);

        return view('apprenant.dashboard', compact(
            'user', 'apprenant', 'semestres', 'notes',
            'emploiTemps', 'documents', 'teachers', 'estDiplome'
        ));
    }

    // ─────────────────────────────────────────────────────────────────────
    // طباعة بطاقة المتكون
    // ─────────────────────────────────────────────────────────────────────
    public function carteMetkoun(Request $request)
    {
        $user = $this->getApprenant($request);
        if (!$user) return redirect()->route('login');

        $apprenant = DB::selectOne("
            SELECT a.IDapprenant, a.Nccp, a.statut,
                   c.Nom, c.Prenom, c.NomFr, c.PrenomFr, c.Nin, c.DateNais, c.LieuNais, c.Civ, c.photo,
                   s.Nom AS section_nom,
                   sp.Nom AS specialite_nom, sp.NomFr AS specialite_fr,
                   ni.Nom AS niveau_nom,
                   e.Nom AS etab_nom, e.NomFr AS etab_fr,
                   e.Adres AS etab_adresse, e.Tel AS etab_tel,
                   mf.Nom AS mode_nom,
                   o.DateD AS date_debut, o.DateF AS date_fin
            FROM apprenant a
            JOIN candidat c ON c.IDCandidat = a.IDCandidat
            LEFT JOIN section s ON s.IDSection = a.IDSection
            LEFT JOIN offre o ON o.IDOffre = s.IDOffre
            LEFT JOIN mode_formation mf ON mf.IDMode_formation = o.IDMode_formation
            LEFT JOIN specialite sp ON sp.IDSpecialite = o.IDSpecialite
            LEFT JOIN niveau_fp ni ON ni.IDNiveau_Fp = sp.IDNiveau_Fp
            LEFT JOIN etablissement e ON e.IDetablissement = o.IDEts_Form
            WHERE a.IDapprenant = ?
            LIMIT 1
        ", [$user['id']]);

        return view('apprenant.carte', compact('apprenant'));
    }
}
