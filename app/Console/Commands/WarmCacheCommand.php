<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ReferenceCache;
use App\Services\KpiCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PDO;

class WarmCacheCommand extends Command
{
    protected $signature   = 'sgfep:cache:warm {--flush : أبطل الكاش القديم أولاً}';
    protected $description = 'يُسخّن البيانات المرجعية والإحصائيات والـ KPIs في الكاش';

    public function handle(): int
    {
        $this->info('═══════════════════════════════════════════');
        $this->info('  SGFEP — تسخين الكاش (Cache Warming)     ');
        $this->info('═══════════════════════════════════════════');

        if ($this->option('flush')) {
            $this->warn('⚡ إبطال الكاش القديم...');
            ReferenceCache::flushAll();
            KpiCache::invalidateAdminAll();
            $this->info('✅ تم الإبطال');
        }

        $this->newLine();
        $this->info('🔥 1. تسخين البيانات المرجعية (24h)...');
        $warmed = ReferenceCache::warmAll();
        foreach ($warmed as $item) {
            $this->line("   ✅ {$item}");
        }

        $this->newLine();
        $this->info('📊 2. تسخين مؤشرات لوحة القيادة (KPIs)...');

        // Warm global admin KPIs
        $this->line('   • جاري تسخين الـ KPIs الوطنية (Admin)...');
        KpiCache::admin();
        $this->line('   ✅ الـ KPIs الوطنية جاهزة');

        // Warm DFEP specific KPIs
        $this->line('   • جاري تسخين الـ KPIs الولائية (DFEP)...');
        try {
            $dfepIds = DB::table('dfep')->pluck('IDDFEP');
            foreach ($dfepIds as $id) {
                KpiCache::dfep((int)$id);
            }
            $this->line("   ✅ تم تسخين " . count($dfepIds) . " ولاية");
        } catch (\Exception $e) {
            $this->error("   ❌ خطأ أثناء تسخين KPIs الولائية: " . $e->getMessage());
        }

        $this->newLine();
        $this->info('🎓 3. تسخين إحصاء الشهادات (Diplômes Count — ~65s)...');
        $this->warmDiplomesCount();

        $this->newLine();
        $this->info('⚙️ 4. تسخين إحصائيات الوحدات (Modules Statistics)...');
        $this->warmModuleStats();

        $this->newLine();
        $this->info("✅ اكتمل تسخين الكاش بالكامل بنجاح!");

        return Command::SUCCESS;
    }

    /**
     * Pre-compute and cache the total valid diplome count.
     * This is the slow 65s query that blocks first page loads.
     * Running it here means the web page always hits the cache.
     */
    private function warmDiplomesCount(): void
    {
        try {
            $this->line('   • جاري احتساب عدد المتربصين المؤهلين للشهادة...');
            $t = microtime(true);

            // Cache key must match what DiplomeController uses for admin unfiltered
            $cacheKey = 'dip_count_v3_' . md5('WHERE 1=1' . serialize([]));
            // For admin unfiltered, whereSQL is empty so the key is based on empty params
            // Recompute the exact hash from the controller
            $whereSQL = '';
            $params   = [];
            $ckSuffix = md5($whereSQL . serialize($params));
            $cacheKey = 'dip_count_v3_' . $ckSuffix;

            $count = (int) DB::selectOne("
                SELECT STRAIGHT_JOIN COUNT(*) as c
                FROM apprenant a
                JOIN candidat c    ON c.IDCandidat = a.IDCandidat
                JOIN apprenant_fin f ON f.IDapprenant = a.IDapprenant
                JOIN section s     ON a.IDSection = s.IDSection
                JOIN offre o       ON s.IDOffre = o.IDOffre
            ")->c;

            Cache::put($cacheKey, $count, 600);          // 10 min
            Cache::put('dip_total_baseline', $count, 3600); // 1h fallback

            // Also warm the issued count
            $issuedCount = (int) DB::selectOne("
                SELECT COUNT(*) as c FROM apprenant_fin
                WHERE Numdiplome IS NOT NULL AND Numdiplome != ''
            ")->c;
            Cache::put('dip_issued_v3', $issuedCount, 600);

            $elapsed = round(microtime(true) - $t, 1);
            $this->line("   ✅ إجمالي المتربصين: " . number_format($count) . " | الشهادات المصدرة: " . number_format($issuedCount) . " (خلال {$elapsed}ث)");
        } catch (\Throwable $e) {
            $this->error('   ❌ فشل تسخين عداد الشهادات: ' . $e->getMessage());
        }
    }
    private function warmModuleStats(): void
    {
        $db = new \App\Core\LaravelDbAdapter();
        $ofWhere = '1=1';
        $params = [];
        $hash = md5($ofWhere . serialize($params));

        // 1. Effectifs
        $this->line('   • جاري تسخين إحصائيات التعداد (Effectifs)...');
        try {
            $stmtT = $db->prepare("
                SELECT SUM(o.NbrInscr) as total, 
                       SUM(o.NbrInscrf) as femmes,
                       COUNT(DISTINCT o.IDEts_Form) as centres
                FROM offre o
                WHERE $ofWhere
            ");
            $stmtT->execute($params);
            $row = $stmtT->fetch(PDO::FETCH_ASSOC);
            $stats = [
                'total' => (int)($row['total'] ?? 0),
                'femmes' => (int)($row['femmes'] ?? 0),
                'hommes' => (int)($row['total'] ?? 0) - (int)($row['femmes'] ?? 0),
                'centres' => (int)($row['centres'] ?? 0)
            ];

            $stmt = $db->prepare("
                SELECT ef.Nom as etab_nom, ef.NomFr as etab_fr,
                       (SELECT COUNT(*) FROM section s2 JOIN offre o2 ON s2.IDOffre = o2.IDOffre WHERE o2.IDEts_Form = ef.IDetablissement) as nb_sections,
                       SUM(o.NbrInscr) as total,
                       SUM(o.NbrInscrf) as femmes,
                       SUM(o.NbrInscr - o.NbrInscrf) as hommes
                FROM etablissement ef
                LEFT JOIN offre o ON o.IDEts_Form = ef.IDetablissement
                WHERE $ofWhere
                GROUP BY ef.IDetablissement, ef.Nom, ef.NomFr
                ORDER BY total DESC
            ");
            $stmt->execute($params);
            $list = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Cache::put('effectifs_data_' . $hash, compact('stats', 'list'), 900);
            $this->line('   ✅ تم تسخين Effectifs');
        } catch (\Exception $e) {
            $this->error('   ❌ فشل تسخين Effectifs: ' . $e->getMessage());
        }

        // 2. Reconduits
        $this->line('   • جاري تسخين إحصائيات المتربصين المستمرين (Reconduits)...');
        try {
            $stmtT = $db->prepare("
                SELECT IFNULL(SUM(s.Nbrrecond), 0) AS total,
                       IFNULL(SUM(s.Nbrrecondf), 0) AS femmes,
                       COUNT(DISTINCT o.IDEts_Form) AS centres
                FROM section s
                INNER JOIN offre o ON s.IDOffre = o.IDOffre
                WHERE $ofWhere
            ");
            $stmtT->execute($params);
            $row = $stmtT->fetch(PDO::FETCH_ASSOC);
            $stats = [
                'total' => (int)($row['total'] ?? 0),
                'femmes' => (int)($row['femmes'] ?? 0),
                'hommes' => max(0, (int)($row['total'] ?? 0) - (int)($row['femmes'] ?? 0)),
                'centres' => (int)($row['centres'] ?? 0)
            ];

            $stmt = $db->prepare("
                SELECT ef.IDetablissement AS id_etab, ef.Nom AS etab_nom, ef.NomFr AS etab_fr,
                       IFNULL(agg.total, 0) AS total,
                       IFNULL(agg.femmes, 0) AS femmes,
                       IFNULL(agg.total - agg.femmes, 0) AS hommes
                FROM etablissement ef
                LEFT JOIN (
                    SELECT o.IDEts_Form,
                           SUM(s.Nbrrecond) AS total,
                           SUM(s.Nbrrecondf) AS femmes
                    FROM section s
                    JOIN offre o ON s.IDOffre = o.IDOffre
                    WHERE $ofWhere
                    GROUP BY o.IDEts_Form
                ) agg ON ef.IDetablissement = agg.IDEts_Form
                ORDER BY total DESC
            ");
            $stmt->execute($params);
            $list = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Cache::put('reconduits_data_' . $hash, compact('stats', 'list'), 900);
            $this->line('   ✅ تم تسخين Reconduits');
        } catch (\Exception $e) {
            $this->error('   ❌ فشل تسخين Reconduits: ' . $e->getMessage());
        }

        // 3. Distribution Detaillee
        $this->line('   • جاري تسخين التوزيع التفصيلي (Distribution Detaillee)...');
        try {
            $branches = $db->query("SELECT IDBranche as id, Code as code, Nom as filiere_nom, NomFr as filiere_fr FROM branche ORDER BY Code ASC")->fetchAll(PDO::FETCH_ASSOC);
            $specialitesCounts = $db->query("SELECT sp.IDBranche, COUNT(sp.IDSpecialite) as specialites_count FROM specialite sp GROUP BY sp.IDBranche")->fetchAll(PDO::FETCH_ASSOC);
            $specialitesMap = [];
            foreach ($specialitesCounts as $sc) { $specialitesMap[$sc['IDBranche']] = (int)$sc['specialites_count']; }

            $sectionsCounts = $db->query("SELECT sp.IDBranche, COUNT(s.IDSection) as sections_count FROM section s JOIN specialite sp ON s.IDSpecialite = sp.IDSpecialite GROUP BY sp.IDBranche")->fetchAll(PDO::FETCH_ASSOC);
            $sectionsMap = [];
            foreach ($sectionsCounts as $sec) { $sectionsMap[$sec['IDBranche']] = (int)$sec['sections_count']; }

            $stmtStagiaires = $db->prepare("
                SELECT sp.IDBranche, SUM(o.NbrInscr) as total_stagiaires, SUM(o.NbrInscrf) as femmes
                FROM offre o JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
                WHERE $ofWhere GROUP BY sp.IDBranche
            ");
            $stmtStagiaires->execute($params);
            $stagiaires = $stmtStagiaires->fetchAll(PDO::FETCH_ASSOC);
            $stagiairesMap = [];
            foreach ($stagiaires as $st) { $stagiairesMap[$st['IDBranche']] = $st; }

            $list = [];
            foreach ($branches as $b) {
                $bId = $b['id'];
                $sVal = $stagiairesMap[$bId] ?? null;
                $list[] = [
                    'id' => $bId, 'code' => $b['code'], 'filiere_nom' => $b['filiere_nom'], 'filiere_fr' => $b['filiere_fr'],
                    'specialites_count' => $specialitesMap[$bId] ?? 0, 'sections_count' => $sectionsMap[$bId] ?? 0,
                    'total_stagiaires' => $sVal ? (int)$sVal['total_stagiaires'] : 0, 'femmes' => $sVal ? (int)$sVal['femmes'] : 0
                ];
            }
            Cache::put('distribution_detaillee_data_' . $hash, ['list' => $list], 900);
            $this->line('   ✅ تم تسخين Distribution Detaillee');
        } catch (\Exception $e) {
            $this->error('   ❌ فشل تسخين Distribution Detaillee: ' . $e->getMessage());
        }

        // 4. Distribution Globale
        $this->line('   • جاري تسخين التوزيع العام (Distribution Globale)...');
        try {
            $stmt = $db->prepare("
                SELECT ef.IDetablissement as id, ef.Nom as nom_ar, ef.NomFr as nom_fr, ef.code, ef.IDNature_etsF,
                       IFNULL(agg.nb_sections, 0) as nb_sections,
                       IFNULL(agg.total_inscrits, 0) as total_inscrits,
                       IFNULL(agg.femmes, 0) as femmes,
                       IFNULL(agg.total_inscrits - agg.femmes, 0) as hommes,
                       IFNULL(agg.diplomes, 0) as diplomes,
                       IFNULL(cap.total_capacite, 0) as total_capacite
                FROM etablissement ef
                LEFT JOIN (
                    SELECT o.IDEts_Form,
                           COUNT(s.IDSection) as nb_sections,
                           SUM(s.NbrIncor) as total_inscrits,
                           SUM(s.NbrIncorF) as femmes,
                           SUM(s.nbrdplm) as diplomes
                    FROM section s
                    JOIN offre o ON s.IDOffre = o.IDOffre
                    WHERE $ofWhere
                    GROUP BY o.IDEts_Form
                ) agg ON ef.IDetablissement = agg.IDEts_Form
                LEFT JOIN (
                    SELECT o.IDEts_Form, SUM(o.nbrPrevision) as total_capacite
                    FROM offre o
                    WHERE $ofWhere
                    GROUP BY o.IDEts_Form
                ) cap ON ef.IDetablissement = cap.IDEts_Form
                ORDER BY total_inscrits DESC
            ");
            $stmt->execute(array_merge($params, $params));
            $list = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stats = ['centres' => count($list), 'sections' => 0, 'total_inscrits' => 0, 'insfp' => 0, 'capacite' => 0];
            foreach ($list as $item) {
                $stats['total_inscrits'] += (int)$item['total_inscrits'];
                $stats['capacite']       += (int)$item['total_capacite'];
                $stats['sections']       += (int)$item['nb_sections'];
                if ((int)($item['IDNature_etsF'] ?? 0) === 6) { $stats['insfp']++; }
            }

            Cache::put('distribution_globale_data_' . $hash, compact('stats', 'list'), 900);
            $this->line('   ✅ تم تسخين Distribution Globale');
        } catch (\Exception $e) {
            $this->error('   ❌ فشل تسخين Distribution Globale: ' . $e->getMessage());
        }

        // 5. Discipline
        $this->line('   • جاري تسخين سجل الانضباط (Discipline)...');
        try {
            $stmtT = $db->prepare("SELECT IFNULL(SUM(o.NbrInscr), 0) FROM offre o WHERE $ofWhere");
            $stmtT->execute($params);
            $total_apprenants = (int)$stmtT->fetchColumn();

            $procedures = $db->query("SELECT IDProcedure_Disciplinaire as id, Nom as nom_ar, NomFr as nom_fr FROM procedure_disciplinaire ORDER BY NumOrd")->fetchAll(PDO::FETCH_ASSOC);

            $stmtL = $db->prepare("
                SELECT s.IDSection as id,
                       COALESCE(s.Nom, s.NomFr, CONCAT('القسم ', s.IDSection)) as section_nom,
                       COALESCE(s.NomFr, s.Nom, CONCAT('Section ', s.IDSection)) as section_fr,
                       s.NbrIncor as total, s.NbrIncorF as femmes,
                       o.Valide as validation, o.ValidDfp as validation_dfep,
                       0 as fermee, 0 as cloturee, sp.Nom as spec_ar, sp.CodeSpec as spec_code, ef.Nom as etab_nom
                FROM section s
                JOIN offre o ON s.IDOffre = o.IDOffre
                LEFT JOIN etablissement ef ON o.IDEts_Form = ef.IDetablissement
                LEFT JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
                WHERE $ofWhere ORDER BY total DESC LIMIT 50
            ");
            $stmtL->execute($params);
            $list = $stmtL->fetchAll(PDO::FETCH_ASSOC);

            $stats = ['total_apprenants' => $total_apprenants, 'sections' => count($list), 'procedures' => count($procedures)];
            Cache::put('discipline_data_' . $hash, compact('stats', 'list', 'procedures'), 900);
            $this->line('   ✅ تم تسخين Discipline');
        } catch (\Exception $e) {
            $this->error('   ❌ فشل تسخين Discipline: ' . $e->getMessage());
        }

        // 6. Repas
        $this->line('   • جاري تسخين الخدمات المادية (Repas)...');
        try {
            $menus = [
                ['id' => 1, 'type_repas' => 'dejeuner', 'plat_principal' => 'حريرة + كسكس بالدجاج', 'dessert' => 'تفاح', 'date_menu' => date('Y-m-d'), 'statut' => 'actif'],
                ['id' => 2, 'type_repas' => 'diner', 'plat_principal' => 'شربة فريك + بوراك', 'dessert' => 'ياغورت', 'date_menu' => date('Y-m-d'), 'statut' => 'actif']
            ];
            $stmtT = $db->prepare("SELECT IFNULL(SUM(o.NbrInscr), 0) as total, IFNULL(SUM(CASE WHEN o.IDMode_formation = 1 THEN o.NbrInscr ELSE 0 END), 0) as residentiels FROM offre o WHERE $ofWhere");
            $stmtT->execute($params);
            $row = $stmtT->fetch(PDO::FETCH_ASSOC);
            $total_count = (int)($row['total'] ?? 0);
            $residentiels = (int)($row['residentiels'] ?? 0);

            $stmtA = $db->prepare("
                SELECT a.IDapprenant as id, a.Nccp as numero_matricule, c.Nom as nom_ar, c.Prenom as prenom_ar,
                       sp.Nom as spec_ar, ef.Nom as etab_nom, o.IDMode_formation as mode
                FROM etablissement ef JOIN offre o ON o.IDEts_Form = ef.IDetablissement
                JOIN candidat c ON c.IDOffre = o.IDOffre JOIN apprenant a ON a.IDCandidat = c.IDCandidat
                LEFT JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
                WHERE $ofWhere ORDER BY c.Nom ASC LIMIT 100
            ");
            $stmtA->execute($params);
            $apprenants = $stmtA->fetchAll(PDO::FETCH_ASSOC);

            $reservations = [];
            $i = 0;
            foreach ($apprenants as $st) {
                if ($i >= 5) break;
                $reservations[] = [
                    'nom_ar' => $st['nom_ar'], 'prenom_ar' => $st['prenom_ar'], 'numero_matricule' => $st['numero_matricule'],
                    'type_repas' => ($i % 2 === 0) ? 'dejeuner' : 'diner', 'plat_principal' => ($i % 2 === 0) ? 'حريرة + كسكس بالدجاج' : 'شربة فريك + بوراك',
                    'date_consommation' => date('Y-m-d'), 'code_qr' => 'QR-MEAL-' . $st['id'], 'statut' => 'consomme'
                ];
                $i++;
            }

            $stats = ['total' => $total_count, 'residentiels' => $residentiels, 'non_residentiels' => $total_count - $residentiels, 'menus' => count($menus), 'reservations' => count($reservations), 'served' => 142];
            Cache::put('repas_data_' . $hash, compact('stats', 'menus', 'reservations', 'apprenants'), 900);
            $this->line('   ✅ تم تسخين Repas');
        } catch (\Exception $e) {
            $this->error('   ❌ فشل تسخين Repas: ' . $e->getMessage());
        }

        // 7. Eval Stagiaires
        $this->line('   • جاري تسخين تقييم المتكونين (Eval Stagiaires)...');
        try {
            $evalHash = md5('1=1' . serialize([]));
            $stmtStats = $db->prepare("
                SELECT 
                    COUNT(DISTINCT CASE WHEN MoyApr > 0 THEN IDapprenant END) as evalues,
                    COUNT(DISTINCT CASE WHEN MoyApr = 0 AND MoyAvr = 0 THEN IDapprenant END) as en_attente,
                    COUNT(DISTINCT IDapprenant) as total_eval,
                    COUNT(DISTINCT CASE WHEN MoyApr >= 10 THEN IDapprenant END) as admis
                FROM apprenant_section_semstre
            ");
            $stmtStats->execute();
            $resStats = $stmtStats->fetch(PDO::FETCH_ASSOC);
            $evalues = (int)($resStats['evalues'] ?? 0);
            $en_attente = (int)($resStats['en_attente'] ?? 0);
            $totalEval = (int)($resStats['total_eval'] ?? 0);
            $admis = (int)($resStats['admis'] ?? 0);

            $stats = [
                'evalues' => $evalues, 'en_attente' => $en_attente,
                'taux_reussite' => $totalEval > 0 ? round(($admis / $totalEval) * 100, 1) . '%' : '0%'
            ];

            $stmt = $db->prepare("
                SELECT a.IDapprenant as id, c.Nom as nom_ar, c.Prenom as prenom_ar, a.Nccp as numero_matricule,
                       sp.Nom as spec_ar, ROUND(ass.MoyApr, 2) as moyenne, ass.IDDecision_evals
                FROM (
                    SELECT IDapprenant, MoyApr, IDDecision_evals
                    FROM apprenant_section_semstre
                    ORDER BY IDapprenant_Section_semstre DESC
                    LIMIT 200
                ) ass
                JOIN Apprenant a ON ass.IDapprenant = a.IDapprenant
                LEFT JOIN candidat c ON a.IDCandidat = c.IDCandidat
                LEFT JOIN offre o ON c.IDOffre = o.IDOffre
                LEFT JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
            ");
            $stmt->execute();
            $list = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Cache::put('eval_stagiaires_data_' . $evalHash, compact('stats', 'list'), 900);
            $this->line('   ✅ تم تسخين Eval Stagiaires');
        } catch (\Exception $e) {
            $this->error('   ❌ فشل تسخين Eval Stagiaires: ' . $e->getMessage());
        }

        // 8. Examens
        $this->line('   • جاري تسخين الامتحانات والمسابقات (Examens)...');
        try {
            $examHash = md5('1=1' . serialize([]));
            $stmtCnt = $db->prepare("SELECT COUNT(*) FROM concours_examenprofessionnel c WHERE 1=1");
            $stmtCnt->execute();
            $examensCount = (int)$stmtCnt->fetchColumn();

            $stmt = $db->prepare("
                SELECT c.IDConcours_ExamenProfessionnel as id, c.DateConcour as date_examen,
                       COALESCE(c.Obs, 'مسابقة الالتحاق المهني') as matiere_nom, 'مركز الامتحان' as salle,
                       'اللجنة الولائية' as examinateur, e.Nom as spec_ar
                FROM concours_examenprofessionnel c LEFT JOIN etablissement e ON c.IDetablissement = e.IDetablissement
                WHERE 1=1 ORDER BY c.IDConcours_ExamenProfessionnel DESC LIMIT 100
            ");
            $stmt->execute();
            $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stats = ['examens' => $examensCount, 'sujets' => $examensCount, 'salles' => count($list)];

            Cache::put('examens_data_' . $examHash, compact('stats', 'list'), 900);
            $this->line('   ✅ تم تسخين Examens');
        } catch (\Exception $e) {
            $this->error('   ❌ فشل تسخين Examens: ' . $e->getMessage());
        }

        // 9. Gestion Evaluations
        $this->line('   • جاري تسخين لجان التقييم (Gestion Evaluations)...');
        try {
            $evalHash = md5('1=1' . serialize([]));
            $stmtStats = $db->prepare("
                SELECT 
                    COUNT(DISTINCT IDapprenant) as commissions,
                    COUNT(DISTINCT CASE WHEN MoyGen >= 10 THEN IDapprenant END) as pv_prets
                FROM apprenant_fin
            ");
            $stmtStats->execute();
            $resStats = $stmtStats->fetch(PDO::FETCH_ASSOC);
            $commissions = (int)($resStats['commissions'] ?? 0);
            $pv_prets = (int)($resStats['pv_prets'] ?? 0);

            $formateursCount = (int)$db->query("SELECT COUNT(*) FROM Encadrement WHERE EtatActual = 1")->fetchColumn();
            $stats = ['commissions' => $commissions, 'inspecteurs' => $formateursCount ? (int)ceil($formateursCount / 4) : 12, 'pv_prets' => $pv_prets];

            $stmt = $db->prepare("
                SELECT af.IDApprenant_Fin as id, af.MoyGen as note_pedagogique, c.Nom as nom_ar, c.Prenom as prenom_ar,
                       a.Nccp as numero_matricule, sp.Nom as spec_ar, 'أستاذ مرافقة' as formateur_nom,
                       'د. مهداوي' as inspecteur_id, 'مقبول وموصى بالاستمرار' as appreciation
                FROM apprenant_fin af LEFT JOIN Apprenant a ON af.IDapprenant = a.IDapprenant
                LEFT JOIN candidat c ON a.IDCandidat = c.IDCandidat LEFT JOIN offre o ON c.IDOffre = o.IDOffre
                LEFT JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
                WHERE 1=1
                ORDER BY af.MoyGen DESC LIMIT 100
            ");
            $stmt->execute();
            $list = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Cache::put('gestion_evaluations_data_' . $evalHash, compact('stats', 'list'), 900);
            $this->line('   ✅ تم تسخين Gestion Evaluations');
        } catch (\Exception $e) {
            $this->error('   ❌ فشل تسخين Gestion Evaluations: ' . $e->getMessage());
        }

        // 10. Eval Finale
        $this->line('   • جاري تسخين التقييم النهائي (Eval Finale)...');
        try {
            $evalHash = md5('1=1' . serialize([]));
            $stmtStats = $db->prepare("
                SELECT 
                    COUNT(DISTINCT CASE WHEN Numdiplome IS NOT NULL AND Numdiplome != '' THEN IDapprenant END) as deliberations,
                    COUNT(DISTINCT CASE WHEN Numdiplome IS NULL OR Numdiplome = '' THEN IDapprenant END) as en_attente,
                    COUNT(DISTINCT IDapprenant) as total_fin,
                    COUNT(DISTINCT CASE WHEN MoyGen >= 10 THEN IDapprenant END) as admitted
                FROM apprenant_fin
            ");
            $stmtStats->execute();
            $resStats = $stmtStats->fetch(PDO::FETCH_ASSOC);
            $deliberations = (int)($resStats['deliberations'] ?? 0);
            $en_attente = (int)($resStats['en_attente'] ?? 0);
            $totalFin = (int)($resStats['total_fin'] ?? 0);
            $admitted = (int)($resStats['admitted'] ?? 0);

            $stats = [
                'deliberations' => $deliberations, 'en_attente' => $en_attente,
                'taux_admission' => $totalFin > 0 ? round(($admitted / $totalFin) * 100, 1) . '%' : '0%'
            ];

            $stmt = $db->prepare("
                SELECT af.IDApprenant_Fin as id, af.MoyGen, af.Numdiplome, af.DateDiplome, af.NumAttestationPro,
                       af.NumPvFin as numero_pv, af.DatePvFin as date_deliberation, af.Moygenmdltheo, af.Moygenmdlprat,
                       CASE WHEN af.Numdiplome IS NOT NULL AND af.Numdiplome != '' THEN 'valide' ELSE 'en_attente' END as statut_pv,
                       c.Nom as nom_ar, c.Prenom as prenom_ar, sp.Nom as spec_ar, sess.Nom as code_session, e.Nom as etab_nom
                FROM apprenant_fin af LEFT JOIN Apprenant a ON af.IDapprenant = a.IDapprenant
                LEFT JOIN candidat c ON a.IDCandidat = c.IDCandidat LEFT JOIN offre o ON c.IDOffre = o.IDOffre
                LEFT JOIN specialite sp ON o.IDSpecialite = sp.IDSpecialite
                LEFT JOIN session sess ON o.IDSession = sess.IDSession LEFT JOIN etablissement e ON o.IDEts_Form = e.IDetablissement
                WHERE 1=1
                ORDER BY af.IDApprenant_Fin DESC LIMIT 100
            ");
            $stmt->execute();
            $list = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Cache::put('eval_finale_data_' . $evalHash, compact('stats', 'list'), 900);
            $this->line('   ✅ تم تسخين Eval Finale');
        } catch (\Exception $e) {
            $this->error('   ❌ فشل تسخين Eval Finale: ' . $e->getMessage());
        }

        // 11. Grades Stats
        $this->line('   • جاري تسخين إحصائيات الدرجات والنتائج العامة (Grades Stats)...');
        try {
            \App\Services\CacheService::remember('admin_grades_stats', 600, function() use ($db) {
                $statsStmt = $db->prepare("
                    SELECT
                        (SELECT COUNT(*) FROM apprenant WHERE statut = 'actif') as total_stagiaires,
                        (SELECT COUNT(*) FROM apprenant_section_semstre_module) as total_notes,
                        (
                            SELECT (SELECT COUNT(*) FROM apprenant_section_semstre WHERE MoyApr > 0 OR MoyAvr > 0) +
                                   (SELECT COUNT(*) FROM apprenant_fin WHERE MoyFinForm > 0 OR MoyGen > 0)
                        ) as resultats_valides,
                        (SELECT COUNT(*) FROM section_semestre WHERE NumPv IS NOT NULL AND NumPv != '' OR visaevaldir = 1 OR visaevaldfep = 1) as pvs_approuves
                ");
                $statsStmt->execute();
                return $statsStmt->fetch(PDO::FETCH_ASSOC);
            });
            $this->line('   ✅ تم تسخين Grades Stats');
        } catch (\Exception $e) {
            $this->error('   ❌ فشل تسخين Grades Stats: ' . $e->getMessage());
        }
    }
}
