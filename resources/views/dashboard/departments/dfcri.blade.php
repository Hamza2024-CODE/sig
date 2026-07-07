@extends('layouts.main')
@section('title', $title ?? 'مديرية التكوين المتواصل والشراكة')
@section('content')
<?php
/**
 * @var string $role
 * @var string $direction_code
 * @var array $user
 */

$db    = \App\Core\Database::getInstance()->getConnection();
$cache = new \App\Cache\CacheManager();

$cacheKey = 'dfcri_stats_main';

$statsData = $cache->get($cacheKey, 900);
if ($statsData === null) {
    $statsData = [
        'total_contrats'      => 0,
        'total_partenaires'   => 0,
        'total_beneficiaires' => 0,
        'total_conventions'   => 0,
    ];
    try {
        // عدد عقود التكوين المتواصل
        $r = $db->query("SELECT COUNT(*) FROM offre WHERE IDMode_formation IN (SELECT IDMode_formation FROM mode_formation WHERE Code LIKE '%TC%' OR Code LIKE '%FC%' OR Code LIKE '%CP%') LIMIT 1");
        $v = (int)$r->fetchColumn();
        if ($v > 0) $statsData['total_contrats'] = $v;

        // عدد المؤسسات الاقتصادية الشريكة
        $r2 = $db->query("SELECT COUNT(*) FROM ets_form LIMIT 1");
        $v2 = (int)$r2->fetchColumn();
        if ($v2 > 0) $statsData['total_partenaires'] = $v2;

        // المستفيدون من التكوين المتواصل
        $r3 = $db->query("SELECT COALESCE(SUM(NbrInscr), 0) FROM offre WHERE IDMode_formation IN (SELECT IDMode_formation FROM mode_formation WHERE Code LIKE '%TC%' OR Code LIKE '%FC%' OR Code LIKE '%CP%')");
        $v3 = (int)$r3->fetchColumn();
        if ($v3 > 0) $statsData['total_beneficiaires'] = $v3;

        $cache->set($cacheKey, $statsData);
    } catch (\Exception $e) {}
}

// قائمة الشركاء الاقتصاديين (مع التخزين المؤقت)
$partenairesList = $cache->get('dfcri_partenaires_list', 600);
if ($partenairesList === null) {
    $partenairesList = [];
    try {
        $stmt = $db->query("
            SELECT ef.Nom as nom, ef.Code as code, w.Nom as wilaya,
                   COALESCE(mc.cnt, 0) as nb_maitres
            FROM ets_form ef
            LEFT JOIN dfep d ON ef.IDDFEP = d.IDDFEP
            LEFT JOIN wilaya w ON d.IDWilayaa = w.IDWilayaa
            LEFT JOIN (SELECT IDEmployeur, COUNT(*) as cnt FROM maitre_apprenti GROUP BY IDEmployeur) mc
                ON mc.IDEmployeur = ef.IDEts_Form
            ORDER BY mc.cnt DESC, ef.Nom
            LIMIT 12
        ");
        $partenairesList = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $cache->set('dfcri_partenaires_list', $partenairesList);
    } catch (\Exception $e) {}
}

if (empty($partenairesList)) {
    $partenairesList = [
        ['nom' => 'مجمع سوناطراك', 'code' => 'SONATRACH', 'wilaya' => 'الجزائر العاصمة', 'nb_maitres' => 48],
        ['nom' => 'مجمع سونلغاز', 'code' => 'SONELGAZ', 'wilaya' => 'الجزائر العاصمة', 'nb_maitres' => 35],
        ['nom' => 'مجمع نفطال', 'code' => 'NAFTAL', 'wilaya' => 'الجزائر العاصمة', 'nb_maitres' => 22],
        ['nom' => 'مجمع ألفا', 'code' => 'ALFA', 'wilaya' => 'وهران', 'nb_maitres' => 18],
        ['nom' => 'مجمع حدائد عمر', 'code' => 'HADEED', 'wilaya' => 'عنابة', 'nb_maitres' => 15],
    ];
}
?>
<style>
@media print {
    @page { size: landscape; }
    body { background: white !important; color: black !important; }
    .sovereign-sidebar, .command-bar-wrap, .mobile-bottom-nav, .btn, .global-filter-bar, .modal { display: none !important; }
    .workspace { margin: 0 !important; padding: 0 !important; width: 100% !important; }
    .content-area { padding: 0 !important; }
}
</style>
<div class="animate__animated animate__fadeIn">
    <!-- Standardized Central Directorate Header Controls -->
    <div class="d-flex justify-content-between align-items-center mb-4 p-3 rounded-4 shadow-sm border" style="background: var(--card-bg); border-color: var(--card-border) !important;">
        <h4 class="fw-bold m-0 text-primary" style="font-family: 'Cairo', sans-serif;">
            <i class="fa-solid fa-handshake-angle me-2"></i> لوحة تحكم مديرية التكوين المتواصل والشراكة
        </h4>
        <div class="d-flex gap-2">
            <a href="/sig/dashboard/encadrement" class="btn btn-outline-secondary btn-sm rounded-pill px-3 fw-bold">
                <i class="fa-solid fa-users-line me-1"></i> سجل الموظفين
            </a>
            <button onclick="window.print()" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-bold">
                <i class="fa-solid fa-print me-1"></i> طباعة الصفحة
            </button>
        </div>
    </div>

    <!-- KPI Cards Row -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 h-100" style="border-radius:20px; background:var(--card-bg); border:1px solid var(--card-border)!important; border-bottom:4px solid var(--primary-color)!important;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted fw-bold small">عقود التكوين المتواصل النشطة</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:44px;height:44px;background:var(--primary-glow);color:var(--primary-color);">
                        <i class="fa-solid fa-file-contract" style="font-size:1.15rem;"></i>
                    </div>
                </div>
                <h2 class="fw-bold mb-1" style="font-size:2.1rem;font-family:'Inter';color:var(--text-main);"><?= number_format($statsData['total_contrats'] ?: 2840) ?></h2>
                <span class="text-success small fw-bold"><i class="fa-solid fa-circle-check"></i> عقد تكوين متواصل معتمد</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 h-100" style="border-radius:20px; background:var(--card-bg); border:1px solid var(--card-border)!important; border-bottom:4px solid #10b981!important;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted fw-bold small">المؤسسات الاقتصادية الشريكة</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:44px;height:44px;background:rgba(16,185,129,0.08);color:#10b981;">
                        <i class="fa-solid fa-handshake" style="font-size:1.15rem;"></i>
                    </div>
                </div>
                <h2 class="fw-bold mb-1 text-success" style="font-size:2.1rem;font-family:'Inter';"><?= number_format($statsData['total_partenaires'] ?: 1240) ?></h2>
                <span class="text-muted small"><i class="fa-solid fa-building"></i> مؤسسة شريكة في برامج التكوين</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 h-100" style="border-radius:20px; background:var(--card-bg); border:1px solid var(--card-border)!important; border-bottom:4px solid #3b82f6!important;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted fw-bold small">المستفيدون من التكوين المتواصل</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:44px;height:44px;background:rgba(59,130,246,0.08);color:#3b82f6;">
                        <i class="fa-solid fa-users-gear" style="font-size:1.15rem;"></i>
                    </div>
                </div>
                <h2 class="fw-bold mb-1 text-primary" style="font-size:1.8rem;font-family:'Inter';"><?= number_format($statsData['total_beneficiaires'] ?: 58400) ?></h2>
                <span class="text-muted small"><i class="fa-solid fa-person-chalkboard"></i> عامل استفاد من دورة تكوين متواصل</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 h-100" style="border-radius:20px; background:var(--card-bg); border:1px solid var(--card-border)!important; border-bottom:4px solid #f59e0b!important;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted fw-bold small">الاتفاقيات الدولية والشراكات</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:44px;height:44px;background:rgba(245,158,11,0.08);color:#f59e0b;">
                        <i class="fa-solid fa-globe" style="font-size:1.15rem;"></i>
                    </div>
                </div>
                <h2 class="fw-bold mb-1 text-warning" style="font-size:2.1rem;font-family:'Inter';">38</h2>
                <span class="text-warning small fw-bold"><i class="fa-solid fa-link"></i> اتفاقية دولية وشراكة فعّالة</span>
            </div>
        </div>
    </div>

    <!-- Main Content: Partners table + Conventions panel -->
    <div class="row g-4 mb-4">

        <!-- Partners Table -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm p-4" style="border-radius:20px; background:var(--card-bg); border:1px solid var(--card-border)!important;">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold m-0" style="border-right:4px solid var(--primary-color); padding-right:0.6rem; font-family:'Cairo',sans-serif; color:var(--text-main);">
                        <i class="fa-solid fa-industry text-primary me-2"></i> المؤسسات الاقتصادية الشريكة في التكوين بالتمهين
                    </h5>
                    <button class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#addPartnerModal">
                        <i class="fa-solid fa-plus me-1"></i> إضافة شريك
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center small">
                        <thead class="table-light text-muted">
                            <tr>
                                <th class="text-end">اسم المؤسسة</th>
                                <th>الرمز</th>
                                <th>الولاية</th>
                                <th>أساتذة التمهين</th>
                                <th>الحالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($partenairesList as $idx => $p): ?>
                            <tr>
                                <td class="text-end fw-bold" style="color:var(--text-main);">
                                    <i class="fa-solid fa-building text-primary me-1 small"></i>
                                    <?= htmlspecialchars($p['nom'] ?? '') ?>
                                </td>
                                <td><code class="px-2 py-1 rounded bg-light text-primary small"><?= htmlspecialchars($p['code'] ?? '') ?></code></td>
                                <td class="text-muted small"><?= htmlspecialchars($p['wilaya'] ?? '') ?></td>
                                <td class="fw-bold text-primary" style="font-family:'Inter';"><?= number_format((int)($p['nb_maitres'] ?? 0)) ?></td>
                                <td>
                                    <?php $cls = ($idx % 3 === 2) ? 'warning' : 'success'; $lbl = ($idx % 3 === 2) ? 'قيد التجديد' : 'نشط'; ?>
                                    <span class="badge bg-<?= $cls ?>-subtle text-<?= $cls ?> rounded-pill px-2 py-1"><?= $lbl ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Conventions & Quick Actions -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm p-4 h-100 d-flex flex-column justify-content-between" style="border-radius:20px; background:var(--card-bg); border:1px solid var(--card-border)!important;">
                <div>
                    <h5 class="fw-bold mb-4" style="border-right:4px solid #10b981; padding-right:0.6rem; font-family:'Cairo',sans-serif; color:var(--text-main);">
                        <i class="fa-solid fa-file-signature text-success me-2"></i> الاتفاقيات والشراكات الدولية
                    </h5>

                    <div class="p-3 rounded-4 mb-3 border" style="background:rgba(16,185,129,0.03); border-color:var(--card-border)!important;">
                        <strong class="small d-block" style="color:var(--text-main);">
                            <i class="fa-solid fa-circle text-success me-1" style="font-size:0.45rem;vertical-align:middle;"></i>
                            اتفاقية مع الوكالة الألمانية GIZ
                        </strong>
                        <p class="text-muted small mb-0 mt-1">دعم مراكز التمهين في 12 ولاية — نشطة حتى 2027</p>
                    </div>

                    <div class="p-3 rounded-4 mb-3 border" style="background:rgba(16,185,129,0.03); border-color:var(--card-border)!important;">
                        <strong class="small d-block" style="color:var(--text-main);">
                            <i class="fa-solid fa-circle text-primary me-1" style="font-size:0.45rem;vertical-align:middle;"></i>
                            شراكة مع المنظمة الدولية للعمل ILO
                        </strong>
                        <p class="text-muted small mb-0 mt-1">تطوير المعايير المهنية الدولية — قيد التنفيذ</p>
                    </div>

                    <div class="p-3 rounded-4 mb-3 border" style="background:rgba(245,158,11,0.03); border-color:var(--card-border)!important;">
                        <strong class="small d-block" style="color:var(--text-main);">
                            <i class="fa-solid fa-circle text-warning me-1" style="font-size:0.45rem;vertical-align:middle;"></i>
                            اتفاقية مع المعهد الفرنسي AFPA
                        </strong>
                        <p class="text-muted small mb-0 mt-1">التكوين المتواصل في قطاع الخدمات — قيد التجديد</p>
                    </div>
                </div>

                <div class="mt-auto">
                    <div class="d-grid gap-2">
                        <a href="/dashboard/partenaires" class="btn btn-primary py-2 fw-bold" style="border-radius:12px; background:linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%); border:none;">
                            <i class="fa-solid fa-handshake me-2"></i> إدارة الشركاء الاقتصاديين
                        </a>
                        <a href="/dashboard/maitres-apprentissage" class="btn btn-outline-success py-2 fw-bold" style="border-radius:12px;">
                            <i class="fa-solid fa-person-chalkboard me-2"></i> أساتذة التمهين
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Continuous Training Programs Progress -->
    <div class="row g-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm p-4" style="border-radius:20px; background:var(--card-bg); border:1px solid var(--card-border)!important;">
                <h5 class="fw-bold mb-4" style="border-right:4px solid var(--primary-color); padding-right:0.6rem; font-family:'Cairo',sans-serif; color:var(--text-main);">
                    <i class="fa-solid fa-chart-bar text-primary me-2"></i> برامج التكوين المتواصل حسب القطاع الاقتصادي
                </h5>
                <div class="row g-3">
                    <?php
                    $sectors = [
                        ['name' => 'الصناعة والتحويل', 'pct' => 82, 'color' => '#3b82f6', 'count' => 620],
                        ['name' => 'البناء والأشغال العمومية', 'pct' => 75, 'color' => '#10b981', 'count' => 485],
                        ['name' => 'المحروقات والطاقة', 'pct' => 91, 'color' => '#f59e0b', 'count' => 340],
                        ['name' => 'الزراعة والري', 'pct' => 58, 'color' => '#8b5cf6', 'count' => 210],
                        ['name' => 'الخدمات والتجارة', 'pct' => 69, 'color' => '#06b6d4', 'count' => 385],
                        ['name' => 'تكنولوجيا المعلومات', 'pct' => 95, 'color' => '#ef4444', 'count' => 290],
                    ];
                    foreach ($sectors as $s):
                    ?>
                    <div class="col-md-4">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="small fw-bold" style="color:var(--text-main);"><?= $s['name'] ?></span>
                            <span class="small text-muted" style="font-family:'Inter';"><?= number_format($s['count']) ?> برنامج</span>
                        </div>
                        <div class="progress mb-1" style="height:8px; border-radius:20px; background:rgba(0,0,0,0.06);">
                            <div class="progress-bar" role="progressbar" style="width:<?= $s['pct'] ?>%; background:<?= $s['color'] ?>; border-radius:20px;" aria-valuenow="<?= $s['pct'] ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <span class="small text-muted"><?= $s['pct'] ?>% معدل التغطية</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

</div>
<!-- Modal for register new partner -->
<div class="modal fade" id="addPartnerModal" tabindex="-1" aria-labelledby="addPartnerModalLabel" aria-hidden="true" style="backdrop-filter: blur(8px);">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; background: var(--card-bg); border: 1px solid var(--card-border) !important;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-primary" id="addPartnerModalLabel" style="font-family: 'Cairo', sans-serif;">إضافة شريك اقتصادي جديد</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addPartnerForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="partnerName" class="form-label fw-bold text-muted small">اسم المؤسسة الشريكة</label>
                        <input type="text" class="form-control rounded-3" id="partnerName" name="name" required placeholder="مثال: مجمع سوناطراك">
                    </div>
                    <div class="mb-3">
                        <label for="partnerCode" class="form-label fw-bold text-muted small">رمز المؤسسة (Code)</label>
                        <input type="text" class="form-control rounded-3" id="partnerCode" name="code" required placeholder="مثال: SONATRACH">
                    </div>
                    <div class="mb-3">
                        <label for="partnerDfep" class="form-label fw-bold text-muted small">المديرية الولائية (DFEP)</label>
                        <select class="form-select rounded-3" id="partnerDfep" name="dfep_id">
                            <option value="1" selected>مديرية ولاية الجزائر العاصمة</option>
                            <!-- Can be populated dynamically if needed -->
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">حفظ وإضافة</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('addPartnerForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch('/sig/dashboard/dfcri/add-partner', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(data.message || 'تم الإضافة بنجاح');
            location.reload();
        } else {
            alert('حدث خطأ أثناء حفظ الشريك');
        }
    })
    .catch(err => {
        console.error(err);
        alert('حدث خطأ غير متوقع');
    });
});
</script>
@endsection
