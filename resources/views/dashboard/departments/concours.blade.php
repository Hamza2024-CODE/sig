@extends('layouts.main')
@section('title', 'المسابقات والامتحانات المهنية — DRH')
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
$whereC = [];
$paramsC = [];
if ($role === 'dfep' && $iddfep > 0) {
    $whereC[] = "c.IDetablissement IN (SELECT IDetablissement FROM etablissement WHERE IDDFEP = ?)";
    $paramsC[] = $iddfep;
} elseif (in_array($role, ['etablissement', 'directeur']) && $etabId > 0) {
    $whereC[] = "c.IDetablissement = ?";
    $paramsC[] = $etabId;
}
$whereCSql = !empty($whereC) ? " WHERE " . implode(" AND ", $whereC) : "";

// KPIs
$kpi = ['total' => 0, 'this_year' => 0, 'future' => 0, 'past' => 0];
try {
    $today = date('Y-m-d');
    $kpi['total'] = (int)DB::table('concours_examenprofessionnel as c')
        ->when(!empty($whereC), function($q) use ($role, $iddfep, $etabId) {
            if ($role === 'dfep') {
                return $q->whereIn('c.IDetablissement', function($sq) use ($iddfep) {
                    $sq->select('IDetablissement')->from('etablissement')->where('IDDFEP', $iddfep);
                });
            } else {
                return $q->where('c.IDetablissement', $etabId);
            }
        })
        ->count();
        
    $kpi['future'] = (int)DB::table('concours_examenprofessionnel as c')
        ->where('DateConcour', '>=', $today)
        ->when(!empty($whereC), function($q) use ($role, $iddfep, $etabId) {
            if ($role === 'dfep') {
                return $q->whereIn('c.IDetablissement', function($sq) use ($iddfep) {
                    $sq->select('IDetablissement')->from('etablissement')->where('IDDFEP', $iddfep);
                });
            } else {
                return $q->where('c.IDetablissement', $etabId);
            }
        })
        ->count();

    $kpi['past'] = (int)DB::table('concours_examenprofessionnel as c')
        ->where('DateConcour', '<', $today)
        ->when(!empty($whereC), function($q) use ($role, $iddfep, $etabId) {
            if ($role === 'dfep') {
                return $q->whereIn('c.IDetablissement', function($sq) use ($iddfep) {
                    $sq->select('IDetablissement')->from('etablissement')->where('IDDFEP', $iddfep);
                });
            } else {
                return $q->where('c.IDetablissement', $etabId);
            }
        })
        ->count();

    $kpi['this_year'] = (int)DB::table('concours_examenprofessionnel as c')
        ->whereYear('DateConcour', date('Y'))
        ->when(!empty($whereC), function($q) use ($role, $iddfep, $etabId) {
            if ($role === 'dfep') {
                return $q->whereIn('c.IDetablissement', function($sq) use ($iddfep) {
                    $sq->select('IDetablissement')->from('etablissement')->where('IDDFEP', $iddfep);
                });
            } else {
                return $q->where('c.IDetablissement', $etabId);
            }
        })
        ->count();
} catch (\Exception $e) {}

// قائمة المسابقات
$concoursListRaw = [];
try {
    $concoursListRaw = DB::select("
        SELECT c.IDConcours_ExamenProfessionnel, c.DateDepoDossier, c.DateConcour, c.Obs, c.IDetablissement,
               et.Nom as etab_nom, w.Nom as wilaya, w.IDWilayaa as wilaya_id
        FROM concours_examenprofessionnel c
        LEFT JOIN etablissement et ON c.IDetablissement = et.IDetablissement
        LEFT JOIN wilaya w ON et.IDDFEP = w.IDWilayaa
        " . $whereCSql . "
        ORDER BY c.DateConcour DESC
        LIMIT 30
    ", $paramsC);
} catch (\Exception $e) {}

// أنواع الترقية
$modesPromotion = [];
try {
    $modesPromotion = DB::table('mode_promotion')->get()->toArray();
} catch (\Exception $e) {}

// توزيع المسابقات حسب الولاية
$byWilaya = [];
try {
    $byWilaya = DB::select("
        SELECT w.Nom as wilaya, COUNT(*) as total,
               SUM(CASE WHEN c.DateConcour >= CURDATE() THEN 1 ELSE 0 END) as upcoming
        FROM concours_examenprofessionnel c
        LEFT JOIN etablissement et ON c.IDetablissement = et.IDetablissement
        LEFT JOIN wilaya w ON et.IDDFEP = w.IDWilayaa
        " . $whereCSql . "
        GROUP BY w.Nom ORDER BY total DESC LIMIT 8
    ", $paramsC);
} catch (\Exception $e) {}

// المؤسسات المتاحة للسكوب الحالي
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
?>

<style>
.concours-header { background: linear-gradient(135deg, #0c1445 0%, #1e40af 55%, #2563eb 100%); border-radius:20px; padding:2rem; margin-bottom:1.5rem; color:#fff; position:relative; overflow:hidden; }
.concours-header::before { content:''; position:absolute; top:-50px; right:-50px; width:220px; height:220px; border-radius:50%; background:rgba(255,255,255,0.04); }
.kpi-card { border-radius:18px; border:1px solid var(--card-border); background:var(--card-bg); transition:transform .2s,box-shadow .2s; }
.kpi-card:hover { transform:translateY(-4px); box-shadow:0 12px 32px rgba(0,0,0,.12); }
.kpi-icon { width:52px; height:52px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:1.3rem; }
.stat-bar { height:8px; border-radius:20px; background:rgba(0,0,0,.06); }
.stat-bar-fill { height:100%; border-radius:20px; transition:width 1s ease; }
.upcoming-badge { background:rgba(16,185,129,0.1); color:#10b981; }
.past-badge { background:rgba(148,163,184,0.1); color:#64748b; }
</style>

<div class="animate__animated animate__fadeIn">

    {{-- HEADER --}}
    <div class="concours-header mb-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                <div class="d-flex align-items-center gap-3 mb-2">
                    <div style="width:54px;height:54px;border-radius:16px;background:rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;">
                        <i class="fa-solid fa-file-signature" style="font-size:1.5rem;color:#fff;"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold m-0" style="font-family:'Cairo';">المسابقات والامتحانات المهنية</h4>
                        <small style="opacity:0.8;">Concours & Examens Professionnels — DRH</small>
                    </div>
                </div>
                <p class="m-0" style="opacity:0.75;font-size:0.85rem;">سجل المسابقات والامتحانات المهنية المفتوحة والمبرمجة لتوظيف الإطارات في قطاع التكوين المهني</p>
            </div>
            <div class="d-flex gap-2">
                <a href="/sig/dashboard/rh" class="btn btn-sm fw-bold px-3 py-2 rounded-pill" style="background:rgba(255,255,255,0.18);color:#fff;border:1px solid rgba(255,255,255,0.3);">
                    <i class="fa-solid fa-arrow-right me-1"></i> الموارد البشرية
                </a>
                <?php if ($canAdd): ?>
                <button data-bs-toggle="modal" data-bs-target="#addConcoursModal" class="btn btn-sm fw-bold px-3 py-2 rounded-pill btn-success text-white border-0 shadow">
                    <i class="fa-solid fa-plus me-1"></i> تسجيل مسابقة جديدة
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
            <div class="kpi-card p-4 h-100" style="border-bottom:4px solid #1e40af;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted fw-bold" style="font-size:.78rem;">إجمالي المسابقات المسجلة</span>
                    <div class="kpi-icon" style="background:rgba(30,64,175,0.1);color:#1e40af;"><i class="fa-solid fa-file-contract"></i></div>
                </div>
                <h2 class="fw-black mb-1" style="font-family:'Inter';font-size:2rem;color:var(--text-main);"><?= number_format($kpi['total']) ?></h2>
                <small class="text-muted">مسابقة في السكوب الخاص بك</small>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="kpi-card p-4 h-100" style="border-bottom:4px solid #10b981;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted fw-bold" style="font-size:.78rem;">مسابقات قادمة / مبرمجة</span>
                    <div class="kpi-icon" style="background:rgba(16,185,129,0.1);color:#10b981;"><i class="fa-solid fa-calendar-check"></i></div>
                </div>
                <h2 class="fw-black mb-1 text-success" style="font-family:'Inter';font-size:2rem;"><?= number_format($kpi['future']) ?></h2>
                <div class="d-flex align-items-center gap-1">
                    <span style="width:8px;height:8px;background:#10b981;border-radius:50%;display:inline-block;animation:pulse 2s infinite;"></span>
                    <small class="text-muted">تحت إيداع الملفات أو مبرمجة</small>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="kpi-card p-4 h-100" style="border-bottom:4px solid #64748b;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted fw-bold" style="font-size:.78rem;">مسابقات منجزة</span>
                    <div class="kpi-icon" style="background:rgba(100,116,139,0.1);color:#64748b;"><i class="fa-solid fa-flag-checkered"></i></div>
                </div>
                <h2 class="fw-black mb-1" style="font-family:'Inter';font-size:2rem;color:var(--text-main);"><?= number_format($kpi['past']) ?></h2>
                <small class="text-muted">مسابقة مكتملة</small>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="kpi-card p-4 h-100" style="border-bottom:4px solid #f59e0b;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted fw-bold" style="font-size:.78rem;">مسابقات <?= date('Y') ?></span>
                    <div class="kpi-icon" style="background:rgba(245,158,11,0.1);color:#f59e0b;"><i class="fa-solid fa-calendar-days"></i></div>
                </div>
                <h2 class="fw-black mb-1 text-warning" style="font-family:'Inter';font-size:2rem;"><?= number_format($kpi['this_year']) ?></h2>
                <small class="text-muted">في العام الجاري</small>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        {{-- التوزيع الجغرافي بمخطط --}}
        <div class="col-lg-5">
            <div class="kpi-card p-4 h-100">
                <h6 class="fw-bold mb-4" style="font-family:'Cairo';border-right:4px solid #1e40af;padding-right:.6rem;color:var(--text-main);">
                    <i class="fa-solid fa-chart-bar text-primary me-2"></i>المسابقات الموزعة جغرافيًا
                </h6>
                <?php if (empty($byWilaya)): ?>
                <div class="text-center text-muted py-5">
                    <i class="fa-solid fa-database fa-2x mb-3" style="opacity:.3;"></i>
                    <p class="mb-0">لا توجد بيانات توزيع جغرافي بعد</p>
                </div>
                <?php else: ?>
                <div style="height:260px;position:relative;">
                    <canvas id="chart-concours-wilaya"></canvas>
                </div>
                <?php endif; ?>

                {{-- أنواع الترقية --}}
                <div class="mt-4">
                    <div class="small fw-bold text-muted mb-2">أنواع التوظيف / الترقية المعتمدة:</div>
                    <div class="d-flex flex-wrap gap-2">
                        <?php
                        $mColors = ['#1e40af','#7c3aed','#10b981','#f59e0b'];
                        foreach($modesPromotion as $i => $m):
                            $c = $mColors[$i % count($mColors)];
                        ?>
                        <span class="badge rounded-pill px-3 py-2" style="background:<?= $c ?>18;color:<?= $c ?>;font-size:.75rem;font-weight:700;">
                            <i class="fa-solid fa-circle me-1" style="font-size:.4rem;vertical-align:middle;"></i>
                            <?= htmlspecialchars($m->Nom ?? '') ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        {{-- قائمة المسابقات --}}
        <div class="col-lg-7">
            <div class="kpi-card p-4 h-100">
                <h6 class="fw-bold mb-4" style="font-family:'Cairo';border-right:4px solid #10b981;padding-right:.6rem;color:var(--text-main);">
                    <i class="fa-solid fa-list-check text-success me-2"></i>سجل المسابقات والامتحانات المهنية
                </h6>

                <?php if (empty($concoursListRaw)): ?>
                <div class="text-center text-muted py-5">
                    <i class="fa-solid fa-inbox fa-3x mb-3" style="opacity:.3;"></i>
                    <p class="fw-bold mb-1">لا توجد مسابقات مسجلة</p>
                    <p class="small mb-0">لم يتم إدخال أي مسابقات في السكوب الخاص بك بعد</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle small mb-0" style="text-align:right;">
                        <thead style="background:rgba(0,0,0,.03);">
                            <tr class="text-muted fw-bold" style="font-size:.76rem;">
                                <th class="py-2 ps-3">المؤسسة</th>
                                <th>الولاية</th>
                                <th>تاريخ إيداع الملفات</th>
                                <th>تاريخ المسابقة</th>
                                <th class="text-center">الحالة</th>
                                <th class="text-center">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($concoursListRaw as $c):
                            $today = date('Y-m-d');
                            $isUpcoming = !empty($c->DateConcour) && $c->DateConcour >= $today;
                        ?>
                        <tr style="border-bottom:1px solid var(--card-border,#eee);">
                            <td class="fw-semibold py-2 ps-3" style="color:var(--text-main);font-size:.79rem;">
                                <?= mb_substr(htmlspecialchars($c->etab_nom ?? '—'), 0, 35) ?>
                            </td>
                            <td style="font-size:.79rem;">
                                <i class="fa-solid fa-location-dot text-primary me-1" style="font-size:.7rem;"></i>
                                <?= htmlspecialchars($c->wilaya ?? '—') ?>
                            </td>
                            <td class="text-muted" style="font-family:'Inter';font-size:.79rem;"><?= htmlspecialchars($c->DateDepoDossier ?? '—') ?></td>
                            <td class="fw-bold" style="font-family:'Inter';font-size:.79rem;color:var(--text-main);"><?= htmlspecialchars($c->DateConcour ?? '—') ?></td>
                            <td class="text-center">
                                <span class="badge rounded-pill px-2" style="font-size:.68rem;<?= $isUpcoming ? 'background:rgba(16,185,129,0.12);color:#10b981;' : 'background:rgba(100,116,139,0.12);color:#64748b;' ?>">
                                    <?= $isUpcoming ? 'قادم' : 'منجز' ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-2">
                                    <?php if ($canEdit): ?>
                                    <button onclick="editConcours(<?= htmlspecialchars(json_encode($c)) ?>)" class="btn btn-sm btn-outline-primary border-0 p-1">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                    <?php endif; ?>
                                    <?php if ($canDelete): ?>
                                    <button onclick="deleteConcours(<?= (int)$c->IDConcours_ExamenProfessionnel ?>)" class="btn btn-sm btn-outline-danger border-0 p-1">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

{{-- MODALS --}}
<?php if ($canAdd): ?>
<div class="modal fade" id="addConcoursModal" tabindex="-1" aria-hidden="true" style="backdrop-filter: blur(8px);">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; background: var(--card-bg); border: 1px solid var(--card-border) !important;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-primary" style="font-family: 'Cairo', sans-serif;">تسجيل مسابقة جديدة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addConcoursForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small">المؤسسة المنظمة</label>
                        <select class="form-select rounded-3" name="etablissement_id" required>
                            <option value="">اختر المؤسسة...</option>
                            <?php foreach ($scoped_etabs as $et): ?>
                            <option value="<?= $et->IDetablissement ?>"><?= htmlspecialchars($et->Nom) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold text-muted small">تاريخ إيداع الملفات</label>
                            <input type="date" class="form-control rounded-3" name="date_depot">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold text-muted small">تاريخ المسابقة / الامتحان</label>
                            <input type="date" class="form-control rounded-3" name="date_concour">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small">ملاحظات / توصيف المسابقة</label>
                        <textarea class="form-control rounded-3" name="obs" rows="2" placeholder="أدخل هنا الاختصاصات المطلوبة أو تفاصيل إضافية..."></textarea>
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
<div class="modal fade" id="editConcoursModal" tabindex="-1" aria-hidden="true" style="backdrop-filter: blur(8px);">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; background: var(--card-bg); border: 1px solid var(--card-border) !important;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-primary" style="font-family: 'Cairo', sans-serif;">تعديل بيانات المسابقة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editConcoursForm" method="POST">
                @csrf
                <input type="hidden" name="id" id="edit_concours_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small">المؤسسة المنظمة</label>
                        <select class="form-select rounded-3" name="etablissement_id" id="edit_etablissement_id" required>
                            <?php foreach ($scoped_etabs as $et): ?>
                            <option value="<?= $et->IDetablissement ?>"><?= htmlspecialchars($et->Nom) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold text-muted small">تاريخ إيداع الملفات</label>
                            <input type="date" class="form-control rounded-3" name="date_depot" id="edit_date_depot">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold text-muted small">تاريخ المسابقة / الامتحان</label>
                            <input type="date" class="form-control rounded-3" name="date_concour" id="edit_date_concour">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small">ملاحظات / توصيف المسابقة</label>
                        <textarea class="form-control rounded-3" name="obs" id="edit_obs" rows="2"></textarea>
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

<style>
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.4} }
</style>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const textColor = isDark ? '#94a3b8' : '#64748b';
    const gridColor = isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)';

    const byWilaya = <?= json_encode(array_map(fn($r) => [
        'wilaya' => $r->wilaya,
        'total' => (int)$r->total,
        'upcoming' => (int)$r->upcoming,
    ], $byWilaya)) ?>;

    if (byWilaya.length > 0) {
        const ctx = document.getElementById('chart-concours-wilaya')?.getContext('2d');
        if (ctx) {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: byWilaya.map(r => r.wilaya),
                    datasets: [
                        { label: 'إجمالي', data: byWilaya.map(r => r.total), backgroundColor: '#1e40af', borderRadius: 6 },
                        { label: 'قادم', data: byWilaya.map(r => r.upcoming), backgroundColor: '#10b981', borderRadius: 6 }
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
    }
});

// CRUD HANDLERS
<?php if ($canAdd): ?>
document.getElementById('addConcoursForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    fetch('/sig/dashboard/concours/store', {
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
            alert(data.message || 'خطأ أثناء تسجيل المسابقة');
        }
    });
});
<?php endif; ?>

<?php if ($canEdit): ?>
function editConcours(c) {
    document.getElementById('edit_concours_id').value = c.IDConcours_ExamenProfessionnel;
    document.getElementById('edit_etablissement_id').value = c.IDetablissement;
    document.getElementById('edit_date_depot').value = c.DateDepoDossier;
    document.getElementById('edit_date_concour').value = c.DateConcour;
    document.getElementById('edit_obs').value = c.Obs || '';

    const modal = new bootstrap.Modal(document.getElementById('editConcoursModal'));
    modal.show();
}

document.getElementById('editConcoursForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    fetch('/sig/dashboard/concours/update', {
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
            alert(data.message || 'خطأ أثناء تحديث المسابقة');
        }
    });
});
<?php endif; ?>

<?php if ($canDelete): ?>
function deleteConcours(id) {
    if (confirm('هل أنت متأكد من رغبتك في حذف هذا الملف للمسابقة نهائياً؟')) {
        fetch('/sig/dashboard/concours/delete/' + id, {
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
                alert(data.message || 'خطأ أثناء حذف المسابقة');
            }
        });
    }
}
<?php endif; ?>
</script>
@endsection
