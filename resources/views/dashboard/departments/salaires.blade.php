@extends('layouts.main')
@section('title', 'ميزانية الأجور والرواتب — DRH')
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
$whereS = [];
$paramsS = [];
if ($role === 'dfep' && $iddfep > 0) {
    $whereS[] = "IDetablissement IN (SELECT IDetablissement FROM etablissement WHERE IDDFEP = ?)";
    $paramsS[] = $iddfep;
} elseif (in_array($role, ['etablissement', 'directeur']) && $etabId > 0) {
    $whereS[] = "IDetablissement = ?";
    $paramsS[] = $etabId;
}
$whereSSql = !empty($whereS) ? " WHERE " . implode(" AND ", $whereS) : "";

$whereSJoin = [];
$paramsSJoin = [];
if ($role === 'dfep' && $iddfep > 0) {
    $whereSJoin[] = "et.IDDFEP = ?";
    $paramsSJoin[] = $iddfep;
} elseif (in_array($role, ['etablissement', 'directeur']) && $etabId > 0) {
    $whereSJoin[] = "eg.IDetablissement = ?";
    $paramsSJoin[] = $etabId;
}
$whereSJoinSql = !empty($whereSJoin) ? " WHERE " . implode(" AND ", $whereSJoin) : "";

// KPIs
$kpi = [
    'total_expense' => 0.0,
    'total_salary' => 0.0,
    'total_primes' => 0.0,
    'total_allocated_posts' => 0,
    'avg_salary' => 0.0
];
try {
    $row = DB::selectOne("SELECT 
        SUM(Depenceannuel) as total_expense,
        SUM(Traitementannuel) as total_salary,
        SUM(Primeetindemnites) as total_primes,
        SUM(allo) as total_posts,
        AVG(CASE WHEN Traitementannuel > 0 THEN Traitementannuel END) as avg_sal
        FROM etablissement_grade" . $whereSSql, $paramsS);
        
    $kpi['total_expense'] = (double)($row->total_expense ?? 0.0);
    $kpi['total_salary']  = (double)($row->total_salary ?? 0.0);
    $kpi['total_primes']  = (double)($row->total_primes ?? 0.0);
    $kpi['total_allocated_posts'] = (int)($row->total_posts ?? 0);
    $kpi['avg_salary']    = (double)($row->avg_sal ?? 0.0);
} catch (\Exception $e) {}

// أعلى المؤسسات من حيث كتلة الأجور
$top_etab_expense = [];
try {
    $top_etab_expense = DB::select("
        SELECT et.Nom as etab_nom, SUM(eg.Depenceannuel) as expense
        FROM etablissement_grade eg
        INNER JOIN etablissement et ON eg.IDetablissement = et.IDetablissement
        " . $whereSJoinSql . "
        GROUP BY et.Nom
        ORDER BY expense DESC
        LIMIT 10
    ", $paramsSJoin);
} catch (\Exception $e) { $top_etab_expense = []; }

// إحصائيات الرواتب حسب الصنف
$category_stats = [];
try {
    $category_stats = DB::select("
        SELECT categorie, COUNT(*) as count, AVG(Traitementannuel) as avg_salary, SUM(Depenceannuel) as total_expense
        FROM etablissement_grade
        " . $whereSSql . ($whereSSql ? " AND " : " WHERE ") . " Traitementannuel > 0
        GROUP BY categorie
        ORDER BY CAST(categorie AS UNSIGNED) ASC, categorie ASC
    ", $paramsS);
} catch (\Exception $e) { $category_stats = []; }

// تفاصيل كتلة الأجور للمؤسسات
$detailed_records = [];
try {
    $detailed_records = DB::select("
        SELECT eg.IDetablissement_Grade, eg.categorie, eg.allo as allocated, eg.Occu as occupied, eg.vacan as vacant, eg.Surplus as surplus,
               eg.Traitementannuel as base_salary, eg.Primeetindemnites as primes, eg.IDGrade as grade_id, eg.IDetablissement as etablissement_id,
               eg.Depenceannuel as total_expense, et.Nom as etab_nom, w.Nom as wilaya
        FROM etablissement_grade eg
        INNER JOIN etablissement et ON eg.IDetablissement = et.IDetablissement
        INNER JOIN wilaya w ON et.IDDFEP = w.IDWilayaa
        " . $whereSJoinSql . ($whereSJoinSql ? " AND " : " WHERE ") . " eg.Traitementannuel > 0
        ORDER BY eg.Depenceannuel DESC
        LIMIT 30
    ", $paramsSJoin);
} catch (\Exception $e) { $detailed_records = []; }

// قائمة المؤسسات التابعة للسكوب الحالي
$scoped_etabs = [];
try {
    $etabClauses = []; $etabParams = [];
    if ($role === 'dfep' && $iddfep > 0) {
        $etabClauses[] = "IDDFEP = ?";
        $etabParams[] = $iddfep;
    } elseif (in_array($role, ['etablissement', 'directeur']) && $etabId > 0) {
        $etabClauses[] = "IDetablissement = ?";
        $etabParams[] = $etabId;
    }
    $etabSql = "SELECT IDetablissement, Nom FROM etablissement 
               " . (!empty($etabClauses) ? " WHERE " . implode(" AND ", $etabClauses) : "") . " 
               ORDER BY Nom ASC";
    $scoped_etabs = DB::select($etabSql, $etabParams);
} catch (\Exception $e) {}

// قائمة الرتب من جدول الرتب
$grades = [];
try {
    $grades = DB::table('grade')->select('IDGrade', 'Nom')->orderBy('Nom', 'ASC')->get()->toArray();
} catch (\Exception $e) {}
?>

<style>
.salaires-header { background: linear-gradient(135deg, #064e3b 0%, #059669 60%, #10b981 100%); border-radius:20px; padding:2rem; margin-bottom:1.5rem; color:#fff; position:relative; overflow:hidden; }
.salaires-header::before { content:''; position:absolute; top:-60px; right:-60px; width:250px; height:250px; border-radius:50%; background:rgba(255,255,255,0.04); }
.kpi-card { border-radius:18px; border:1px solid var(--card-border); background:var(--card-bg); transition:transform .2s,box-shadow .2s; }
.kpi-card:hover { transform:translateY(-4px); box-shadow:0 12px 32px rgba(0,0,0,.12); }
.kpi-icon { width:52px; height:52px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:1.3rem; }
.currency-badge { font-size: 0.75rem; font-weight: normal; opacity: 0.8; margin-right: 4px; }
</style>

<div class="animate__animated animate__fadeIn">

    {{-- HEADER --}}
    <div class="salaires-header mb-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                <div class="d-flex align-items-center gap-3 mb-2">
                    <div style="width:54px;height:54px;border-radius:16px;background:rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;">
                        <i class="fa-solid fa-coins" style="font-size:1.5rem;color:#fff;"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold m-0" style="font-family:'Cairo';">ميزانية الأجور والرواتب</h4>
                        <small style="opacity:0.8;">Masse Salariale & Dépenses du Personnel — DRH</small>
                    </div>
                </div>
                <p class="m-0" style="opacity:0.75;font-size:0.85rem;">متابعة النفقات السنوية للموظفين، الرواتب الأساسية، العلاوات والتعويضات موزعة حسب المؤسسات والأصناف</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="/sig/dashboard/rh" class="btn btn-sm fw-bold px-3 py-2 rounded-pill" style="background:rgba(255,255,255,0.18);color:#fff;border:1px solid rgba(255,255,255,0.3);">
                    <i class="fa-solid fa-arrow-right me-1"></i> الموارد البشرية
                </a>
                <?php if ($canAdd): ?>
                <button data-bs-toggle="modal" data-bs-target="#addSalaireModal" class="btn btn-sm fw-bold px-3 py-2 rounded-pill btn-success text-white border-0 shadow">
                    <i class="fa-solid fa-plus me-1"></i> تسجيل كشف أجور جديد
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
            <div class="kpi-card p-4 h-100" style="border-bottom:4px solid #059669;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted fw-bold" style="font-size:0.78rem;">إجمالي كتلة الأجور السنوية</span>
                    <div class="kpi-icon" style="background:rgba(5,150,105,0.1);color:#059669;">
                        <i class="fa-solid fa-scale-balanced"></i>
                    </div>
                </div>
                <h2 class="fw-black mb-1" style="font-family:'Inter';font-size:1.6rem;color:var(--text-main);">
                    <?= number_format($kpi['total_expense'], 2) ?><span class="currency-badge">د.ج</span>
                </h2>
                <small class="text-muted">الإنفاق الميزانياتي الإجمالي للرواتب</small>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="kpi-card p-4 h-100" style="border-bottom:4px solid #3b82f6;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted fw-bold" style="font-size:0.78rem;">إجمالي الرواتب الأساسية</span>
                    <div class="kpi-icon" style="background:rgba(59,130,246,0.1);color:#3b82f6;">
                        <i class="fa-solid fa-money-bill-wave"></i>
                    </div>
                </div>
                <h2 class="fw-black mb-1 text-primary" style="font-family:'Inter';font-size:1.6rem;">
                    <?= number_format($kpi['total_salary'], 2) ?><span class="currency-badge">د.ج</span>
                </h2>
                <small class="text-muted">الرواتب الأساسية بدون منح</small>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="kpi-card p-4 h-100" style="border-bottom:4px solid #f59e0b;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted fw-bold" style="font-size:0.78rem;">إجمالي المنح والعلاوات</span>
                    <div class="kpi-icon" style="background:rgba(245,158,11,0.1);color:#f59e0b;">
                        <i class="fa-solid fa-gift"></i>
                    </div>
                </div>
                <h2 class="fw-black mb-1 text-warning" style="font-family:'Inter';font-size:1.6rem;">
                    <?= number_format($kpi['total_primes'], 2) ?><span class="currency-badge">د.ج</span>
                </h2>
                <small class="text-muted">علاوات المردودية والتعويضات</small>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="kpi-card p-4 h-100" style="border-bottom:4px solid #6366f1;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted fw-bold" style="font-size:0.78rem;">متوسط الراتب الأساسي السنوي</span>
                    <div class="kpi-icon" style="background:rgba(99,102,241,0.1);color:#6366f1;">
                        <i class="fa-solid fa-calculator"></i>
                    </div>
                </div>
                <h2 class="fw-black mb-1 text-indigo" style="font-family:'Inter';font-size:1.6rem;">
                    <?= number_format($kpi['avg_salary'], 2) ?><span class="currency-badge">د.ج</span>
                </h2>
                <small class="text-muted">للوظيفة الواحدة (الدرجة القاعدية)</small>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        {{-- كتلة الأجور حسب المؤسسات --}}
        <div class="col-lg-7">
            <div class="kpi-card p-4 h-100">
                <h6 class="fw-bold mb-4" style="font-family:'Cairo';border-right:4px solid #059669;padding-right:.6rem;color:var(--text-main);">
                    <i class="fa-solid fa-chart-pie text-success me-2"></i>أعلى المؤسسات من حيث نفقات الأجور السنوية
                </h6>
                <div style="height:320px;position:relative;">
                    <canvas id="chart-etab-expense"></canvas>
                </div>
            </div>
        </div>

        {{-- متوسط الرواتب حسب الصنف --}}
        <div class="col-lg-5">
            <div class="kpi-card p-4 h-100">
                <h6 class="fw-bold mb-4" style="font-family:'Cairo';border-right:4px solid #3b82f6;padding-right:.6rem;color:var(--text-main);">
                    <i class="fa-solid fa-layer-group text-primary me-2"></i>كتلة الأجور السنوية حسب الصنف (Categorie)
                </h6>
                <div class="table-responsive" style="max-height:320px; overflow-y:auto;">
                    <table class="table align-middle small mb-0" style="text-align:right;">
                        <thead style="position: sticky; top: 0; background:var(--card-bg); z-index: 1;">
                            <tr class="text-muted fw-bold" style="font-size:0.75rem; border-bottom: 2px solid var(--card-border);">
                                <th class="py-2">الصنف</th>
                                <th>عدد الوظائف</th>
                                <th>متوسط الراتب</th>
                                <th>إجمالي الإنفاق</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($category_stats)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">لا توجد بيانات أصناف بعد في السكوب الخاص بك</td></tr>
                            <?php else: ?>
                            <?php foreach ($category_stats as $cs): ?>
                            <tr style="border-bottom:1px solid var(--card-border,#eee);">
                                <td class="fw-bold text-success">صنف <?= htmlspecialchars($cs->categorie ?: 'غير محدد') ?></td>
                                <td style="font-family:'Inter';"><?= number_format($cs->count) ?></td>
                                <td style="font-family:'Inter';"><?= number_format($cs->avg_salary, 2) ?></td>
                                <td class="fw-semibold text-primary" style="font-family:'Inter';"><?= number_format($cs->total_expense, 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- جدول تفصيلي لنفقات الأجور للمؤسسات --}}
    <div class="kpi-card p-4 mb-4">
        <h6 class="fw-bold mb-4" style="font-family:'Cairo';border-right:4px solid #6366f1;padding-right:.6rem;color:var(--text-main);">
            <i class="fa-solid fa-list text-indigo me-2"></i>تفاصيل كشوف الأجور ونسب استهلاك المناصب
        </h6>
        <div class="table-responsive">
            <table class="table align-middle small mb-0" style="text-align:right;">
                <thead style="background:rgba(0,0,0,0.03);">
                    <tr class="text-muted fw-bold" style="font-size:0.78rem;">
                        <th class="py-2 ps-3">المؤسسة</th>
                        <th>الولاية</th>
                        <th>الصنف</th>
                        <th class="text-center">المناصب المخصصة</th>
                        <th class="text-center">المناصب المشغولة</th>
                        <th>الراتب الأساسي السنوي</th>
                        <th>المنح السنوية</th>
                        <th>الإنفاق الكلي السنوي</th>
                        <th class="text-center">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($detailed_records)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">لا توجد بيانات تفصيلية متوفرة في السكوب الحالي</td></tr>
                    <?php else: ?>
                    <?php foreach ($detailed_records as $r): ?>
                    <tr style="border-bottom:1px solid var(--card-border,#eee);">
                        <td class="fw-bold py-2 ps-3" style="color:var(--text-main); font-size:0.8rem;">
                            <?= htmlspecialchars($r->etab_nom) ?>
                        </td>
                        <td>
                            <i class="fa-solid fa-location-dot text-primary me-1" style="font-size:0.7rem;"></i>
                            <?= htmlspecialchars($r->wilaya) ?>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border">
                                صنف <?= htmlspecialchars($r->categorie ?: '—') ?>
                            </span>
                        </td>
                        <td class="text-center fw-bold text-success" style="font-family:'Inter';"><?= number_format($r->allocated) ?></td>
                        <td class="text-center fw-bold text-info" style="font-family:'Inter';"><?= number_format($r->occupied) ?></td>
                        <td style="font-family:'Inter';"><?= number_format($r->base_salary, 2) ?> د.ج</td>
                        <td style="font-family:'Inter';"><?= number_format($r->primes, 2) ?> د.ج</td>
                        <td class="fw-bold text-primary" style="font-family:'Inter';"><?= number_format($r->total_expense, 2) ?> د.ج</td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-2">
                                <?php if ($canEdit): ?>
                                <button onclick="editSalaire(<?= htmlspecialchars(json_encode($r)) ?>)" class="btn btn-sm btn-outline-primary border-0 p-1">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <?php endif; ?>
                                <?php if ($canDelete): ?>
                                <button onclick="deleteSalaire(<?= (int)$r->IDetablissement_Grade ?>)" class="btn btn-sm btn-outline-danger border-0 p-1">
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
<div class="modal fade" id="addSalaireModal" tabindex="-1" aria-hidden="true" style="backdrop-filter: blur(8px);">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; background: var(--card-bg); border: 1px solid var(--card-border) !important;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-primary" style="font-family: 'Cairo', sans-serif;">تسجيل كشف أجور جديد</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addSalaireForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-muted small">المؤسسة المعنية</label>
                            <select class="form-select rounded-3" name="etablissement_id" required>
                                <option value="">اختر المؤسسة...</option>
                                <?php foreach ($scoped_etabs as $et): ?>
                                <option value="<?= $et->IDetablissement ?>"><?= htmlspecialchars($et->Nom) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-muted small">الرتبة المحددة (Grade)</label>
                            <select class="form-select rounded-3" name="grade_id">
                                <option value="">اختر الرتبة (إن وجدت)...</option>
                                <?php foreach ($grades as $g): ?>
                                <option value="<?= $g->IDGrade ?>"><?= htmlspecialchars($g->Nom) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 col-6 mb-3">
                            <label class="form-label fw-bold text-muted small">المناصب المخصصة</label>
                            <input type="number" class="form-control rounded-3" name="allo" required value="0">
                        </div>
                        <div class="col-md-3 col-6 mb-3">
                            <label class="form-label fw-bold text-muted small">المناصب المشغولة</label>
                            <input type="number" class="form-control rounded-3" name="occu" required value="0">
                        </div>
                        <div class="col-md-3 col-6 mb-3">
                            <label class="form-label fw-bold text-muted small">المناصب الشاغرة</label>
                            <input type="number" class="form-control rounded-3" name="vacan" required value="0">
                        </div>
                        <div class="col-md-3 col-6 mb-3">
                            <label class="form-label fw-bold text-muted small">المناصب الفائضة</label>
                            <input type="number" class="form-control rounded-3" name="surplus" required value="0">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold text-muted small">الصنف (Categorie)</label>
                            <input type="text" class="form-control rounded-3" name="categorie" placeholder="مثال: 13" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold text-muted small">الراتب الأساسي السنوي (د.ج)</label>
                            <input type="number" step="0.01" class="form-control rounded-3" name="base_salary" required placeholder="0.00">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold text-muted small">المنح والعلاوات السنوية (د.ج)</label>
                            <input type="number" step="0.01" class="form-control rounded-3" name="primes" required placeholder="0.00">
                        </div>
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
<div class="modal fade" id="editSalaireModal" tabindex="-1" aria-hidden="true" style="backdrop-filter: blur(8px);">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; background: var(--card-bg); border: 1px solid var(--card-border) !important;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-primary" style="font-family: 'Cairo', sans-serif;">تعديل كشف الأجور</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editSalaireForm" method="POST">
                @csrf
                <input type="hidden" name="id" id="edit_salaire_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-muted small">المؤسسة المعنية</label>
                            <select class="form-select rounded-3" name="etablissement_id" id="edit_etablissement_id" required>
                                <?php foreach ($scoped_etabs as $et): ?>
                                <option value="<?= $et->IDetablissement ?>"><?= htmlspecialchars($et->Nom) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-muted small">الرتبة المحددة (Grade)</label>
                            <select class="form-select rounded-3" name="grade_id" id="edit_grade_id">
                                <option value="">اختر الرتبة...</option>
                                <?php foreach ($grades as $g): ?>
                                <option value="<?= $g->IDGrade ?>"><?= htmlspecialchars($g->Nom) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 col-6 mb-3">
                            <label class="form-label fw-bold text-muted small">المناصب المخصصة</label>
                            <input type="number" class="form-control rounded-3" name="allo" id="edit_allo" required>
                        </div>
                        <div class="col-md-3 col-6 mb-3">
                            <label class="form-label fw-bold text-muted small">المناصب المشغولة</label>
                            <input type="number" class="form-control rounded-3" name="occu" id="edit_occu" required>
                        </div>
                        <div class="col-md-3 col-6 mb-3">
                            <label class="form-label fw-bold text-muted small">المناصب الشاغرة</label>
                            <input type="number" class="form-control rounded-3" name="vacan" id="edit_vacan" required>
                        </div>
                        <div class="col-md-3 col-6 mb-3">
                            <label class="form-label fw-bold text-muted small">المناصب الفائضة</label>
                            <input type="number" class="form-control rounded-3" name="surplus" id="edit_surplus" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold text-muted small">الصنف (Categorie)</label>
                            <input type="text" class="form-control rounded-3" name="categorie" id="edit_categorie" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold text-muted small">الراتب الأساسي السنوي (د.ج)</label>
                            <input type="number" step="0.01" class="form-control rounded-3" name="base_salary" id="edit_base_salary" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold text-muted small">المنح والعلاوات السنوية (د.ج)</label>
                            <input type="number" step="0.01" class="form-control rounded-3" name="primes" id="edit_primes" required>
                        </div>
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

    const etabData = <?= json_encode(array_map(fn($r) => [
        'etab' => mb_substr($r->etab_nom, 0, 30) . '...',
        'expense' => (double)$r->expense
    ], $top_etab_expense)) ?>;

    if (etabData.length > 0) {
        const ctx = document.getElementById('chart-etab-expense').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: etabData.map(r => r.etab),
                datasets: [{
                    label: 'الإنفاق الإجمالي السنوي (د.ج)',
                    data: etabData.map(r => r.expense),
                    backgroundColor: 'rgba(5, 150, 105, 0.85)',
                    hoverBackgroundColor: '#059669',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: {
                        labels: {
                            color: textColor,
                            font: { family: 'Cairo', size: 11 }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: { color: gridColor },
                        ticks: { color: textColor, font: { family: 'Inter', size: 10 } }
                    },
                    y: {
                        grid: { display: false },
                        ticks: { color: textColor, font: { family: 'Cairo', size: 10 } }
                    }
                }
            }
        });
    }
});

// CRUD HANDLERS
<?php if ($canAdd): ?>
document.getElementById('addSalaireForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    fetch('/sig/dashboard/salaires/store', {
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
function editSalaire(r) {
    document.getElementById('edit_salaire_id').value = r.IDetablissement_Grade;
    document.getElementById('edit_etablissement_id').value = r.etablissement_id;
    document.getElementById('edit_grade_id').value = r.grade_id || '';
    document.getElementById('edit_allo').value = r.allocated;
    document.getElementById('edit_occu').value = r.occupied;
    document.getElementById('edit_vacan').value = r.vacant;
    document.getElementById('edit_surplus').value = r.surplus;
    document.getElementById('edit_categorie').value = r.categorie || '';
    document.getElementById('edit_base_salary').value = r.base_salary;
    document.getElementById('edit_primes').value = r.primes;

    const modal = new bootstrap.Modal(document.getElementById('editSalaireModal'));
    modal.show();
}

document.getElementById('editSalaireForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    fetch('/sig/dashboard/salaires/update', {
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
function deleteSalaire(id) {
    if (confirm('هل أنت متأكد من رغبتك في حذف هذا الكشف للأجور نهائياً؟')) {
        fetch('/sig/dashboard/salaires/delete/' + id, {
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
                alert(data.message || 'خطأ أثناء حذف السجل');
            }
        });
    }
}
<?php endif; ?>
</script>
@endsection
