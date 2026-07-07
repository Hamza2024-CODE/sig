@extends('layouts.main')
@section('title', 'الترقيات والمسابقات المهنية — DRH')
@section('content')
<?php
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

// ── استخراج صلاحيات السكوب وحالة المستخدم ─────────────────────
$user = session('user') ?? [];
$role = strtolower($user['role_code'] ?? 'user');
$iddfep = (int)($user['iddfep'] ?? $user['IDDFEP'] ?? 0);
$etabId = (int)($user['etablissement_id'] ?? 0);

// التحقق من صلاحيات الأزرار
$permissions = $user['permissions'] ?? ['ajout' => 0, 'modif' => 0, 'suppr' => 0, 'tous' => 0];
$canAdd = ($role === 'admin' || $role === 'central' || !empty($permissions['ajout']) || !empty($permissions['tous']));
$canEdit = ($role === 'admin' || $role === 'central' || !empty($permissions['modif']) || !empty($permissions['tous']));
$canDelete = ($role === 'admin' || $role === 'central' || !empty($permissions['suppr']) || !empty($permissions['tous']));

// بناء شروط التصفية حسب الدور
$whereEg = [];
$paramsEg = [];
if ($role === 'dfep' && $iddfep > 0) {
    $whereEg[] = "IDetablissement IN (SELECT IDetablissement FROM etablissement WHERE IDDFEP = ?)";
    $paramsEg[] = $iddfep;
} elseif (in_array($role, ['etablissement', 'directeur']) && $etabId > 0) {
    $whereEg[] = "IDetablissement = ?";
    $paramsEg[] = $etabId;
}
$whereEgSql = !empty($whereEg) ? " WHERE " . implode(" AND ", $whereEg) : "";

$whereEnc = [];
$paramsEnc = [];
if ($role === 'dfep' && $iddfep > 0) {
    $whereEnc[] = "et.IDDFEP = ?";
    $paramsEnc[] = $iddfep;
} elseif (in_array($role, ['etablissement', 'directeur']) && $etabId > 0) {
    $whereEnc[] = "e.IDetablissement = ?";
    $paramsEnc[] = $etabId;
}
$whereEncSql = !empty($whereEnc) ? " WHERE " . implode(" AND ", $whereEnc) : "";

// ── KPIs الترقيات ──────────────────────────────────────────
$kpi = ['total_postes' => 0, 'total_alloc' => 0, 'total_vacant' => 0, 'total_surplus' => 0];
try {
    $row = DB::selectOne("SELECT COUNT(*) as total, SUM(allo) as alloc, SUM(vacan) as vacant, SUM(Surplus) as surplus FROM etablissement_grade" . $whereEgSql, $paramsEg);
    $kpi['total_postes']  = (int)($row->total  ?? 0);
    $kpi['total_alloc']   = (int)($row->alloc  ?? 0);
    $kpi['total_vacant']  = (int)($row->vacant ?? 0);
    $kpi['total_surplus'] = (int)($row->surplus ?? 0);
} catch (\Exception $e) {}

// أنواع الترقية
$modesPromotion = [];
try {
    $modesPromotion = DB::table('mode_promotion')->get()->toArray();
} catch (\Exception $e) {}

// توزيع المناصب حسب الولاية
$wilayas_dist = [];
try {
    $wilayas_dist = DB::select("
        SELECT w.Nom as wilaya, SUM(eg.Occu) as occupied, SUM(eg.vacan) as vacant, SUM(eg.allo) as allocated
        FROM etablissement_grade eg
        INNER JOIN etablissement et ON eg.IDetablissement = et.IDetablissement
        INNER JOIN wilaya w ON et.IDDFEP = w.IDWilayaa
        " . (!empty($whereEg) ? " WHERE " . implode(" AND ", $whereEg) : "") . "
        GROUP BY w.Nom
        ORDER BY occupied DESC
        LIMIT 10
    ", $paramsEg);
} catch (\Exception $e) {}

// سجل الترقيات الأخيرة
$recent_grades = [];
try {
    $recent_grades = DB::select("
        SELECT eg.IDEncadrement_Grade, eg.dateinstal, eg.dateconf, eg.IDMode_Promotion, eg.NumOrd,
               e.Nom, e.Prenom, e.Civ, e.IDEncadrement,
               et.Nom as etab_nom, w.Nom as wilaya,
               mp.Nom as mode_nom
        FROM encadrement_grade eg
        LEFT JOIN encadrement e ON eg.IDEncadrement = e.IDEncadrement
        LEFT JOIN etablissement et ON e.IDetablissement = et.IDetablissement
        LEFT JOIN wilaya w ON et.IDDFEP = w.IDWilayaa
        LEFT JOIN mode_promotion mp ON eg.IDMode_Promotion = mp.IDMode_Promotion
        " . (!empty($whereEnc) ? " WHERE " . implode(" AND ", $whereEnc) : "") . "
        ORDER BY eg.IDEncadrement_Grade DESC
        LIMIT 15
    ", $paramsEnc);
} catch (\Exception $e) {}

// المناصب البيداغوجية
$postes_budget = null;
try {
    $postes_budget = DB::selectOne("
        SELECT COUNT(*) as total, COUNT(CASE WHEN pb.IDEtat=1 THEN 1 END) as actif 
        FROM poste_budgetaire pb
        INNER JOIN etablissement_grade eg ON pb.IDetablissement_Grade = eg.IDetablissement_Grade
        INNER JOIN etablissement et ON eg.IDetablissement = et.IDetablissement
        " . ($role === 'dfep' && $iddfep > 0 ? " WHERE et.IDDFEP = " . $iddfep : "") . "
        " . (in_array($role, ['etablissement', 'directeur']) && $etabId > 0 ? " WHERE eg.IDetablissement = " . $etabId : "")
    );
} catch (\Exception $e) {}

// الموظفين المتاحين للاختيار عند الإضافة
$scoped_employees = [];
try {
    $empClauses = []; $empParams = [];
    if ($role === 'dfep' && $iddfep > 0) {
        $empClauses[] = "et.IDDFEP = ?";
        $empParams[] = $iddfep;
    } elseif (in_array($role, ['etablissement', 'directeur']) && $etabId > 0) {
        $empClauses[] = "e.IDetablissement = ?";
        $empParams[] = $etabId;
    }
    $empSql = "SELECT e.IDEncadrement, e.Nom, e.Prenom, et.Nom as etab_nom 
               FROM encadrement e 
               INNER JOIN etablissement et ON e.IDetablissement = et.IDetablissement 
               " . (!empty($empClauses) ? " WHERE " . implode(" AND ", $empClauses) : "") . " 
               ORDER BY e.Nom ASC LIMIT 1000";
    $scoped_employees = DB::select($empSql, $empParams);
} catch (\Exception $e) {}
?>

<style>
.promo-header { background: linear-gradient(135deg, #1a1f3e 0%, #7c3aed 60%, #a855f7 100%); border-radius:20px; padding:2rem; margin-bottom:1.5rem; color:#fff; position:relative; overflow:hidden; }
.promo-header::before { content:''; position:absolute; top:-60px; right:-60px; width:250px; height:250px; border-radius:50%; background:rgba(255,255,255,0.04); }
.kpi-card { border-radius:18px; border:1px solid var(--card-border); background:var(--card-bg); transition:transform .2s,box-shadow .2s; }
.kpi-card:hover { transform:translateY(-4px); box-shadow:0 12px 32px rgba(0,0,0,.12); }
.kpi-icon { width:52px; height:52px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:1.3rem; }
.stat-bar { height:8px; border-radius:20px; background:rgba(0,0,0,.06); }
.stat-bar-fill { height:100%; border-radius:20px; transition:width 1s ease; }
.mode-badge { display:inline-flex; align-items:center; gap:6px; padding:6px 14px; border-radius:100px; font-size:0.78rem; font-weight:700; }
</style>

<div class="animate__animated animate__fadeIn">

    {{-- HEADER --}}
    <div class="promo-header mb-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                <div class="d-flex align-items-center gap-3 mb-2">
                    <div style="width:54px;height:54px;border-radius:16px;background:rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;">
                        <i class="fa-solid fa-arrow-trend-up" style="font-size:1.5rem;color:#fff;"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold m-0" style="font-family:'Cairo';">الترقيات والمناصب الوظيفية</h4>
                        <small style="opacity:0.8;">Promotions & Postes Budgétaires — DRH</small>
                    </div>
                </div>
                <p class="m-0" style="opacity:0.75;font-size:0.85rem;">تسيير المناصب البيداغوجية المخصصة والشاغرة وملفات الترقيات والانتقالات الإدارية</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="/sig/dashboard/rh" class="btn btn-sm fw-bold px-3 py-2 rounded-pill" style="background:rgba(255,255,255,0.18);color:#fff;border:1px solid rgba(255,255,255,0.3);">
                    <i class="fa-solid fa-arrow-right me-1"></i> الموارد البشرية
                </a>
                <?php if ($canAdd): ?>
                <button data-bs-toggle="modal" data-bs-target="#addPromoModal" class="btn btn-sm fw-bold px-3 py-2 rounded-pill btn-success text-white border-0 shadow">
                    <i class="fa-solid fa-plus me-1"></i> تسجيل ترقية جديدة
                </button>
                <?php endif; ?>
                <button onclick="window.print()" class="btn btn-sm fw-bold px-3 py-2 rounded-pill" style="background:rgba(255,255,255,0.12);color:#fff;border:1px solid rgba(255,255,255,0.25);">
                    <i class="fa-solid fa-print me-1"></i> طباعة
                </button>
            </div>
        </div>
    </div>

    {{-- KPI CARDS --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="kpi-card p-4 h-100" style="border-bottom:4px solid #7c3aed;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted fw-bold" style="font-size:0.78rem;">إجمالي المناصب المدرجة</span>
                    <div class="kpi-icon" style="background:rgba(124,58,237,0.1);color:#7c3aed;">
                        <i class="fa-solid fa-briefcase"></i>
                    </div>
                </div>
                <h2 class="fw-black mb-1" style="font-family:'Inter';font-size:2rem;color:var(--text-main);"><?= number_format($kpi['total_postes']) ?></h2>
                <small class="text-muted">منصب مدرج في السكوب الخاص بك</small>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="kpi-card p-4 h-100" style="border-bottom:4px solid #10b981;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted fw-bold" style="font-size:0.78rem;">المناصب المخصصة</span>
                    <div class="kpi-icon" style="background:rgba(16,185,129,0.1);color:#10b981;">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                </div>
                <h2 class="fw-black mb-1 text-success" style="font-family:'Inter';font-size:2rem;"><?= number_format($kpi['total_alloc']) ?></h2>
                <small class="text-muted">منصب مخصص فعلياً</small>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="kpi-card p-4 h-100" style="border-bottom:4px solid #f59e0b;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted fw-bold" style="font-size:0.78rem;">المناصب الشاغرة</span>
                    <div class="kpi-icon" style="background:rgba(245,158,11,0.1);color:#f59e0b;">
                        <i class="fa-solid fa-circle-exclamation"></i>
                    </div>
                </div>
                <h2 class="fw-black mb-1 text-warning" style="font-family:'Inter';font-size:2rem;"><?= number_format($kpi['total_vacant']) ?></h2>
                <small class="text-muted">منصب شاغر لم يُشغل</small>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="kpi-card p-4 h-100" style="border-bottom:4px solid #ef4444;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted fw-bold" style="font-size:0.78rem;">المناصب الفائضة</span>
                    <div class="kpi-icon" style="background:rgba(239,68,68,0.1);color:#ef4444;">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                </div>
                <h2 class="fw-black mb-1 text-danger" style="font-family:'Inter';font-size:2rem;"><?= number_format($kpi['total_surplus']) ?></h2>
                <small class="text-muted">منصب فائض عن الحاجة</small>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        {{-- أنواع الترقية --}}
        <div class="col-lg-4">
            <div class="kpi-card p-4 h-100">
                <h6 class="fw-bold mb-4" style="font-family:'Cairo';border-right:4px solid #7c3aed;padding-right:.6rem;color:var(--text-main);">
                    <i class="fa-solid fa-star text-warning me-2"></i>أنواع الترقية المعتمدة
                </h6>
                <?php
                $modeColors = ['#7c3aed','#3b82f6','#10b981','#f59e0b','#ef4444'];
                foreach ($modesPromotion as $i => $m):
                    $clr = $modeColors[$i % count($modeColors)];
                ?>
                <div class="d-flex align-items-center gap-3 mb-3 p-3 rounded-3" style="background:<?= $clr ?>12;border:1px solid <?= $clr ?>30;">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:38px;height:38px;background:<?= $clr ?>20;color:<?= $clr ?>;">
                        <i class="fa-solid fa-medal" style="font-size:.9rem;"></i>
                    </div>
                    <div>
                        <div class="fw-bold small" style="color:var(--text-main);"><?= htmlspecialchars($m->Nom ?? '') ?></div>
                        <small class="text-muted">كود: <?= (int)($m->IDMode_Promotion ?? 0) ?></small>
                    </div>
                    <span class="badge rounded-pill ms-auto" style="background:<?= $clr ?>20;color:<?= $clr ?>;font-size:.7rem;">نشط</span>
                </div>
                <?php endforeach; ?>

                <?php if ($postes_budget): ?>
                <div class="mt-3 p-3 rounded-3" style="background:rgba(124,58,237,0.06);border:1px solid rgba(124,58,237,0.15);">
                    <div class="small text-muted mb-1">المناصب الميزانياتية الإجمالية</div>
                    <div class="fw-black" style="font-family:'Inter';font-size:1.5rem;color:#7c3aed;"><?= number_format($postes_budget->total ?? 0) ?></div>
                    <small class="text-muted">نشط ومدرج: <?= number_format($postes_budget->actif ?? 0) ?></small>
                </div>
                <?php endif; ?>
            </div>
        </div>

        {{-- توزيع المناصب حسب الولاية --}}
        <div class="col-lg-8">
            <div class="kpi-card p-4 h-100">
                <h6 class="fw-bold mb-4" style="font-family:'Cairo';border-right:4px solid #10b981;padding-right:.6rem;color:var(--text-main);">
                    <i class="fa-solid fa-map-location-dot text-success me-2"></i>توزيع المناصب والتثبيت
                </h6>
                <div style="height:260px;position:relative;">
                    <canvas id="chart-wilayas-promo"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- جدول آخر الترقيات --}}
    <div class="kpi-card p-4 mb-4">
        <h6 class="fw-bold mb-4" style="font-family:'Cairo';border-right:4px solid #7c3aed;padding-right:.6rem;color:var(--text-main);">
            <i class="fa-solid fa-clock-rotate-left text-primary me-2"></i>سجل ملفات الترقية والتعيين
        </h6>
        <div class="table-responsive">
            <table class="table align-middle small mb-0" style="text-align:right;">
                <thead style="background:rgba(0,0,0,0.03);">
                    <tr class="text-muted fw-bold" style="font-size:0.78rem;">
                        <th class="py-2 ps-3">الموظف</th>
                        <th>نوع الترقية</th>
                        <th>تاريخ التثبيت</th>
                        <th>تاريخ التأكيد</th>
                        <th>المؤسسة</th>
                        <th>الولاية</th>
                        <th class="text-center">الرتبة</th>
                        <th class="text-center">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_grades)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">
                        <i class="fa-solid fa-database me-2"></i>لا توجد سجلات ترقيات في السكوب الخاص بك بعد
                    </td></tr>
                    <?php else: ?>
                    <?php foreach ($recent_grades as $g):
                        $isFemale = ((int)($g->Civ ?? 1) === 2);
                        $modeColor = match((int)($g->IDMode_Promotion ?? 0)) {
                            1 => '#7c3aed', 2 => '#3b82f6', 3 => '#f59e0b', 4 => '#10b981', default => '#94a3b8'
                        };
                    ?>
                    <tr style="border-bottom:1px solid var(--card-border,#eee);">
                        <td class="fw-bold py-2 ps-3" style="color:var(--text-main);">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                     style="width:32px;height:32px;background:<?= $isFemale ? 'rgba(236,72,153,0.1)' : 'rgba(124,58,237,0.1)' ?>;color:<?= $isFemale ? '#ec4899' : '#7c3aed' ?>;">
                                    <i class="fa-solid fa-user" style="font-size:.75rem;"></i>
                                </div>
                                <?= htmlspecialchars(($g->Nom ?? '') . ' ' . ($g->Prenom ?? '')) ?>
                            </div>
                        </td>
                        <td>
                            <span class="badge rounded-pill px-2" style="background:<?= $modeColor ?>20;color:<?= $modeColor ?>;font-size:.7rem;">
                                <?= htmlspecialchars($g->mode_nom ?? 'غير محدد') ?>
                            </span>
                        </td>
                        <td class="text-muted" style="font-family:'Inter';font-size:.8rem;"><?= htmlspecialchars($g->dateinstal ?? '—') ?></td>
                        <td class="text-muted" style="font-family:'Inter';font-size:.8rem;"><?= htmlspecialchars($g->dateconf ?? '—') ?></td>
                        <td style="font-size:.78rem;"><?= mb_substr(htmlspecialchars($g->etab_nom ?? '—'), 0, 35) ?></td>
                        <td style="font-size:.78rem;"><?= htmlspecialchars($g->wilaya ?? '—') ?></td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border" style="font-family:'Inter';">
                                <?= (int)($g->NumOrd ?? 0) ?: '—' ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-2">
                                <?php if ($canEdit): ?>
                                <button onclick="editPromo(<?= htmlspecialchars(json_encode($g)) ?>)" class="btn btn-sm btn-outline-primary border-0 p-1">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <?php endif; ?>
                                <?php if ($canDelete): ?>
                                <button onclick="deletePromo(<?= (int)$g->IDEncadrement_Grade ?>)" class="btn btn-sm btn-outline-danger border-0 p-1">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

{{-- MODALS --}}
<?php if ($canAdd): ?>
<div class="modal fade" id="addPromoModal" tabindex="-1" aria-hidden="true" style="backdrop-filter: blur(8px);">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; background: var(--card-bg); border: 1px solid var(--card-border) !important;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-primary" style="font-family: 'Cairo', sans-serif;">تسجيل ملف ترقية جديد</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addPromoForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small">الموظف المعني</label>
                        <select class="form-select rounded-3" name="employee_id" required>
                            <option value="">اختر الموظف...</option>
                            <?php foreach ($scoped_employees as $emp): ?>
                            <option value="<?= $emp->IDEncadrement ?>"><?= htmlspecialchars($emp->Nom . ' ' . $emp->Prenom) ?> (<?= htmlspecialchars($emp->etab_nom) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small">نوع الترقية / التثبيت</label>
                        <select class="form-select rounded-3" name="mode_promotion_id" required>
                            <option value="">اختر نوع الترقية...</option>
                            <?php foreach ($modesPromotion as $m): ?>
                            <option value="<?= $m->IDMode_Promotion ?>"><?= htmlspecialchars($m->Nom) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold text-muted small">تاريخ التنصيب / التثبيت</label>
                            <input type="date" class="form-control rounded-3" name="date_install">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold text-muted small">تاريخ التأكيد</label>
                            <input type="date" class="form-control rounded-3" name="date_conf">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small">رقم الترتيب الرتبوي (NumOrd)</label>
                        <input type="number" class="form-control rounded-3" name="num_ord" value="0">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 text-white">حفظ البيانات</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($canEdit): ?>
<div class="modal fade" id="editPromoModal" tabindex="-1" aria-hidden="true" style="backdrop-filter: blur(8px);">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; background: var(--card-bg); border: 1px solid var(--card-border) !important;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-primary" style="font-family: 'Cairo', sans-serif;">تحديث بيانات الترقية</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editPromoForm" method="POST">
                @csrf
                <input type="hidden" name="id" id="edit_promo_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small">الموظف المعني</label>
                        <select class="form-select rounded-3" name="employee_id" id="edit_employee_id" required>
                            <?php foreach ($scoped_employees as $emp): ?>
                            <option value="<?= $emp->IDEncadrement ?>"><?= htmlspecialchars($emp->Nom . ' ' . $emp->Prenom) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small">نوع الترقية / التثبيت</label>
                        <select class="form-select rounded-3" name="mode_promotion_id" id="edit_mode_promotion_id" required>
                            <?php foreach ($modesPromotion as $m): ?>
                            <option value="<?= $m->IDMode_Promotion ?>"><?= htmlspecialchars($m->Nom) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold text-muted small">تاريخ التنصيب / التثبيت</label>
                            <input type="date" class="form-control rounded-3" name="date_install" id="edit_date_install">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold text-muted small">تاريخ التأكيد</label>
                            <input type="date" class="form-control rounded-3" name="date_conf" id="edit_date_conf">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small">رقم الترتيب الرتبوي (NumOrd)</label>
                        <input type="number" class="form-control rounded-3" name="num_ord" id="edit_num_ord">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 text-white">تحديث</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const textColor = isDark ? '#94a3b8' : '#64748b';
    const gridColor = isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)';

    const wiData = <?= json_encode(array_map(fn($r) => [
        'wilaya' => $r->wilaya,
        'occupied' => (int)$r->occupied,
        'vacant' => (int)$r->vacant,
    ], $wilayas_dist)) ?>;

    if (wiData.length > 0) {
        const ctx = document.getElementById('chart-wilayas-promo').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: wiData.map(r => r.wilaya),
                datasets: [
                    { label: 'مشغول', data: wiData.map(r => r.occupied), backgroundColor: '#7c3aed', borderRadius: 6 },
                    { label: 'شاغر', data: wiData.map(r => r.vacant), backgroundColor: '#f59e0b44', borderRadius: 6 }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { labels: { color: textColor, font: { family: 'Cairo' } } } },
                scales: {
                    y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: textColor } },
                    x: { grid: { display: false }, ticks: { color: textColor, font: { family: 'Cairo', size: 10 } } }
                }
            }
        });
    }
});

// CRUD HANDLERS
<?php if ($canAdd): ?>
document.getElementById('addPromoForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    fetch('/sig/dashboard/promotions/store', {
        method: 'POST',
        body: new FormData(this),
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.message || 'خطأ أثناء تسجيل البيانات');
        }
    });
});
<?php endif; ?>

<?php if ($canEdit): ?>
function editPromo(promo) {
    document.getElementById('edit_promo_id').value = promo.IDEncadrement_Grade;
    document.getElementById('edit_employee_id').value = promo.IDEncadrement;
    document.getElementById('edit_mode_promotion_id').value = promo.IDMode_Promotion;
    document.getElementById('edit_date_install').value = promo.dateinstal;
    document.getElementById('edit_date_conf').value = promo.dateconf;
    document.getElementById('edit_num_ord').value = promo.NumOrd;

    const modal = new bootstrap.Modal(document.getElementById('editPromoModal'));
    modal.show();
}

document.getElementById('editPromoForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    fetch('/sig/dashboard/promotions/update', {
        method: 'POST',
        body: new FormData(this),
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.message || 'خطأ أثناء تحديث البيانات');
        }
    });
});
<?php endif; ?>

<?php if ($canDelete): ?>
function deletePromo(id) {
    if (confirm('هل أنت متأكد من رغبتك في حذف هذا الملف للترقية نهائياً؟')) {
        fetch('/sig/dashboard/promotions/delete/' + id, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '<?= csrf_token() ?>',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert(data.message || 'خطأ أثناء حذف الملف');
            }
        });
    }
}
<?php endif; ?>
</script>
@endsection
