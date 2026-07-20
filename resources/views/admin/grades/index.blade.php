@extends('layouts.main')
@section('title', $title ?? 'المنصة الرقمية للتكوين المهني')
@section('content')
<?php
/**
 * @var array $offres
 * @var array $stats
 * @var array $wilayas
 * @var array $etablissements
 * @var array $years
 * @var int|null $selected_wilaya
 * @var int|null $selected_etab
 * @var int|null $selected_year
 */

$success = session('success') ?? (isset($_SESSION) ? ($_SESSION['success'] ?? null) : null);
$error   = session('error') ?? (isset($_SESSION) ? ($_SESSION['error'] ?? null) : null);
if (isset($_SESSION)) {
    unset($_SESSION['success'], $_SESSION['error']);
}

$diplomeLabels = [
    'CAP'        => ['label' => 'شهادة الكفاءة المهنية',   'color' => '#06b6d4', 'bg' => '#ecfeff'],
    'BEP'        => ['label' => 'شهادة التعليم المهني',    'color' => '#8b5cf6', 'bg' => '#f5f3ff'],
    'BP'         => ['label' => 'شهادة المهارة المهنية',   'color' => '#10b981', 'bg' => '#ecfdf5'],
    'BTS'        => ['label' => 'شهادة تقني سامي',         'color' => '#f59e0b', 'bg' => '#fffbeb'],
    'TS'         => ['label' => 'شهادة تقني',              'color' => '#0ea5e9', 'bg' => '#f0f9ff'],
    'BFCA'       => ['label' => 'شهادة مكوّن متخصص',       'color' => '#e11d48', 'bg' => '#fff1f2'],
    'CMP'        => ['label' => 'شهادة التحكم المهني',     'color' => '#6366f1', 'bg' => '#eef2ff'],
    'Qualifiant' => ['label' => 'شهادة تأهيلية',           'color' => '#84cc16', 'bg' => '#f7fee7'],
];

$encSemesters = [];
for ($i = 1; $i <= 6; $i++) {
    $encSemesters[$i] = \App\Helpers\SecureIdHelper::encrypt($i);
}
?>

{{-- ── PAGE HEADER BANNER ─────────────────────────────────────────────── --}}
<div style="background:linear-gradient(135deg,#0284c7 0%,#0369a1 100%);padding:2.2rem 2rem 0;color:#fff;border-radius:0 0 24px 24px;margin-bottom:1.5rem;">
    <div class="d-flex align-items-start justify-content-between flex-wrap gap-3 mb-3">
        <div>
            <h3 class="fw-bold mb-1"><i class="fa-solid fa-graduation-cap me-2"></i>دفتر العلامات والمداولات البيداغوجية الموحد</h3>
            <p class="mb-0 opacity-75 small">بوابة رصد علامات المراقبة المستمرة والامتحانات، استخراج كشوف النقاط ومحاضر المداولات الرسمية للوزارة.</p>
        </div>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <?php $roleCode = strtolower(session('user')['role_code'] ?? ''); ?>
            <?php if ($roleCode === 'admin'): ?>
            <a href="/dashboard/grades/windows" class="btn btn-light px-3 py-2 fw-bold shadow-sm text-decoration-none rounded-3" style="color:#0d9488;font-size:.88rem;">
                <i class="fa-solid fa-clock-rotate-left me-1"></i> فترات رصد النقاط
            </a>
            <?php endif; ?>
            <?php if (in_array($roleCode, ['admin','dfep','etablissement','directeur'])): ?>
            <a href="/dashboard/grades/progress" class="btn btn-light px-3 py-2 fw-bold shadow-sm text-decoration-none rounded-3" style="color:#0369a1;font-size:.88rem;">
                <i class="fa-solid fa-chart-line me-1"></i> متابعة تقدم الرصد
            </a>
            <?php endif; ?>
            <?php if ($roleCode === 'admin'): ?>
            <a href="/dashboard/grades/control" class="btn btn-light px-3 py-2 fw-bold shadow-sm text-decoration-none rounded-3" style="color:#0369a1;font-size:.88rem;">
                <i class="fa-solid fa-gears me-1"></i> إعدادات النظام
            </a>
            <?php endif; ?>
            <a href="/dashboard" class="btn btn-light px-4 py-2 fw-bold shadow-sm text-decoration-none rounded-3" style="color:#0369a1;font-size:.88rem;">
                <i class="fa-solid fa-house me-1"></i> لوحة التحكم
            </a>
        </div>
    </div>

    {{-- ── 4 STATS CHIPS inside banner ──────────────────────────────── --}}
    <div class="row g-3 pb-4">
        <div class="col-6 col-md-3">
            <div class="rounded-3 p-3 text-center" style="background:rgba(255,255,255,.12);backdrop-filter:blur(8px);">
                <div class="fw-bold fs-4" style="font-family:'Outfit';"><?= number_format($stats['total_stagiaires'] ?? 0) ?></div>
                <div class="small opacity-75">المتربصون النشطون</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="rounded-3 p-3 text-center" style="background:rgba(255,255,255,.12);backdrop-filter:blur(8px);">
                <div class="fw-bold fs-4" style="font-family:'Outfit';"><?= number_format($stats['total_notes'] ?? 0) ?></div>
                <div class="small opacity-75">النقاط المرصودة</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="rounded-3 p-3 text-center" style="background:rgba(255,255,255,.12);backdrop-filter:blur(8px);">
                <div class="fw-bold fs-4" style="font-family:'Outfit';"><?= number_format($stats['resultats_valides'] ?? 0) ?></div>
                <div class="small opacity-75">النتائج المحتسبة</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="rounded-3 p-3 text-center" style="background:rgba(255,255,255,.12);backdrop-filter:blur(8px);">
                <div class="fw-bold fs-4" style="font-family:'Outfit';"><?= number_format($stats['pvs_approuves'] ?? 0) ?></div>
                <div class="small opacity-75">المحاضر المعتمدة</div>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid px-4">

    <?php if (empty($hasActiveWindow)): ?>
    <div class="alert alert-warning border-0 shadow-sm rounded-4 mb-4 d-flex align-items-center gap-3" style="background:#fffbeb; color:#854d0e; border:1px solid rgba(245,158,11,.25);">
        <i class="fa-solid fa-circle-exclamation fs-4"></i>
        <div>
            <strong class="d-block mb-0.5">فترة رصد النقاط مغلقة حالياً:</strong>
            <span class="small">لا توجد فترة رصد مفعلة لنطاقك البيداغوجي. يمكنك فقط استعراض ومعاينة النقاط والنتائج دون إمكانية إدخالها أو تعديلها.</span>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($success): ?>
    <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4"><i class="fa-solid fa-circle-check me-2"></i> <?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-4"><i class="fa-solid fa-triangle-exclamation me-2"></i> <?= $error ?></div>
    <?php endif; ?>

    {{-- ── SCOPE FILTER (Wilaya / Etab / Year) ─────────────────────── --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4" style="background:rgba(255,255,255,.9);backdrop-filter:blur(12px);">
        <div class="card-body p-3">
            <form method="GET" action="" id="dashboardFilterForm" class="row g-3 align-items-end">
                <?php
                $user = session('user');
                $role = strtolower($user['role_code'] ?? '');
                $isAdmin = in_array($role, ['admin', 'central', 'high_admin', 'secretaire_general', 'ministre']);
                $isDfep  = ($role === 'dfep');
                ?>
                <div class="col-md-4" <?php if(!$isAdmin): ?> style="display:none;" <?php endif; ?>>
                    <label class="form-label fw-bold small text-muted"><i class="fa-solid fa-map-location-dot me-1"></i>الولاية (DFEP)</label>
                    <select name="filter_wilaya" class="form-select border-0 shadow-sm bg-light rounded-3" onchange="this.form.submit()">
                        <option value="">-- كل الولايات --</option>
                        <?php foreach($wilayas as $w): ?>
                        <option value="<?= $w['id'] ?>" <?= $selected_wilaya == $w['id'] ? 'selected' : '' ?>><?= htmlspecialchars($w['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5" <?php if(!$isAdmin && !$isDfep): ?> style="display:none;" <?php endif; ?>>
                    <label class="form-label fw-bold small text-muted"><i class="fa-solid fa-hotel me-1"></i>المؤسسة التكوينية</label>
                    <select name="filter_etab" class="form-select border-0 shadow-sm bg-light rounded-3" onchange="this.form.submit()">
                        <option value="">-- كل المؤسسات --</option>
                        <?php foreach($etablissements as $e): ?>
                        <option value="<?= $e['id'] ?>" <?= $selected_etab == $e['id'] ? 'selected' : '' ?>><?= htmlspecialchars($e['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold small text-muted"><i class="fa-solid fa-calendar-days me-1"></i>سنة الدورة</label>
                    <select name="filter_year" class="form-select border-0 shadow-sm bg-light rounded-3" onchange="this.form.submit()">
                        <option value="">-- كل السنوات --</option>
                        <?php foreach($years as $y): ?>
                        <option value="<?= $y ?>" <?= $selected_year == $y ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-1 text-end">
                    <a href="?" class="btn btn-light w-100 fw-bold border rounded-3" title="إعادة تعيين"><i class="fa-solid fa-arrows-rotate"></i></a>
                </div>
            </form>
        </div>
    </div>

    {{-- ── LIVE SEARCH + FILTER BAR ─────────────────────────────────── --}}
    <?php if (!empty($offres)): ?>
    <div class="card border-0 shadow-sm rounded-4 mb-4" style="background:rgba(255,255,255,.9);backdrop-filter:blur(12px);">
        <div class="card-body p-3">
            <div class="row g-3 align-items-center">
                <div class="col-md-5">
                    <div class="input-group" style="border-radius:14px;overflow:hidden;">
                        <span class="input-group-text bg-light border-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="text" id="liveSearch" class="form-control border-0 bg-light fw-semibold" placeholder="بحث سريع عن تخصص…" oninput="filterCards()">
                    </div>
                </div>
                <div class="col-md-4 d-flex gap-2 flex-wrap">
                    <button class="btn btn-sm fw-bold filter-mode active rounded-pill px-3" data-mode="all" onclick="setMode('all',this)"><i class="fa-solid fa-layer-group me-1"></i>الكل</button>
                    <button class="btn btn-sm fw-bold filter-mode rounded-pill px-3" data-mode="resident" onclick="setMode('resident',this)"><i class="fa-solid fa-school me-1"></i>حضوري</button>
                    <button class="btn btn-sm fw-bold filter-mode rounded-pill px-3" data-mode="apprenti" onclick="setMode('apprenti',this)"><i class="fa-solid fa-building me-1"></i>تمهين</button>
                </div>
                <div class="col-md-3">
                    <select id="filterDiplome" class="form-select border-0 bg-light rounded-3 fw-semibold" onchange="filterCards()">
                        <option value="">-- كل الشهادات --</option>
                        <option value="BTS">BTS — تقني سامي</option>
                        <option value="TS">TS — تقني</option>
                        <option value="CMP">CMP — تحكم مهني</option>
                        <option value="CAP">CAP — كفاءة مهنية</option>
                        <option value="Qualifiant">تأهيلية</option>
                    </select>
                </div>
            </div>
            <div class="mt-2">
                <small class="text-muted fw-bold" id="countBadge"></small>
            </div>
        </div>
    </div>
    <?php endif; ?>

    {{-- ── OFFER CARDS GRID ─────────────────────────────────────────── --}}
    <div class="row g-4" id="offresGrid">
    <?php if (empty($offres)): ?>
        <div class="col-12 text-center py-5 text-muted bg-white rounded-4 shadow-sm">
            <i class="fa-solid fa-graduation-cap d-block mb-3" style="font-size:3.5rem;opacity:.3;"></i>
            لا توجد عروض تكوين أو تخصصات نشطة في فرعك للدورات الحالية (2024–2026).
        </div>
    <?php else: ?>
        <?php foreach ($offres as $o):
            $diplome    = $o['diplome_vise'] ?? 'CAP';
            $diplomeInfo= $diplomeLabels[$diplome] ?? ['label'=>$diplome,'color'=>'#64748b','bg'=>'#f8fafc'];
            $diplomeText = !empty($o['diplome_exact']) ? $o['diplome_exact'] : $diplomeInfo['label'];
            $maxSem     = max(1, (int)($o['duree_semestres'] ?? 1));
            $isMode10   = ((int)$o['mode_formation'] === 10);
            $encId      = \App\Helpers\SecureIdHelper::encrypt($o['id']);
            $cardId     = 'card_' . $o['id'];
        ?>
        <div class="col-md-6 col-lg-4 offre-card"
             data-name="<?= htmlspecialchars(strtolower($o['spec_ar'])) ?>"
             data-mode="<?= $isMode10 ? 'apprenti' : 'resident' ?>"
             data-diplome="<?= htmlspecialchars($diplome) ?>">
            <div class="card h-100 border-0 shadow-sm card-hover rounded-4 overflow-hidden">
                {{-- colour stripe top --}}
                <div style="height:5px;background:<?= $diplomeInfo['color'] ?>;"></div>
                <div class="card-body p-4 d-flex flex-column">
                    {{-- header --}}
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <span class="badge fw-bold px-3 py-1 rounded-pill" style="background:<?= $diplomeInfo['bg'] ?>;color:<?= $diplomeInfo['color'] ?>;font-size:.78rem;">
                            <?= htmlspecialchars($diplomeText) ?>
                        </span>
                        <span class="badge bg-light text-muted small fw-bold px-2 py-1" style="font-family:'Outfit';">
                            <?= $isMode10 ? '<i class="fa-solid fa-building me-1"></i>تمهين' : '<i class="fa-solid fa-school me-1"></i>حضوري' ?>
                        </span>
                    </div>

                    {{-- spec name + etab --}}
                    <h5 class="fw-bold text-dark mb-1" style="line-height:1.35;"><?= htmlspecialchars($o['spec_ar']) ?></h5>
                    <p class="text-muted small mb-3"><i class="fa-solid fa-hotel me-1"></i><?= htmlspecialchars($o['etab_nom']) ?></p>

                    {{-- quick metrics --}}
                    <div class="p-3 rounded-3 d-flex justify-content-around mb-3" style="background:#f8fafc;border:1px solid #f1f5f9;">
                        <div class="text-center">
                            <small class="d-block text-muted">المتربصون</small>
                            <strong class="text-dark fs-5" style="font-family:'Outfit';"><?= $o['nb_actifs'] ?></strong>
                        </div>
                        <div class="vr" style="opacity:.15;"></div>
                        <div class="text-center">
                            <small class="d-block text-muted">السداسيات</small>
                            <strong class="text-dark fs-5" style="font-family:'Outfit';"><?= $maxSem <= 1 ? '—' : $maxSem ?></strong>
                        </div>
                    </div>

                    {{-- dynamic semester selector --}}
                    <div class="mb-3">
                        <?php if ($maxSem <= 1): ?>
                            {{-- Short-term / qualifying: no semester choice --}}
                            <div class="text-center py-1">
                                <span class="badge rounded-pill px-3 py-2 fw-bold" style="background:<?= $diplomeInfo['bg'] ?>;color:<?= $diplomeInfo['color'] ?>;font-size:.8rem;">
                                    <i class="fa-solid fa-flag-checkered me-1"></i> التقييم النهائي للدورة القصيرة
                                </span>
                            </div>
                        <?php else: ?>
                            <label class="form-label small fw-bold text-muted mb-1">اختر السداسي:</label>
                            <div class="d-flex flex-wrap gap-1" id="semBtns_<?= $o['id'] ?>">
                                <?php
                                $semLabels = ['S1','S2','S3','S4','S5'];
                                for ($i = 1; $i <= $maxSem; $i++):
                                    $active = ($i === 1) ? 'active' : '';
                                    $isAccessible = ($i === 1 || in_array($i - 1, $validatedSemesters[$o['id']] ?? []));
                                ?>
                                <button type="button"
                                    class="btn btn-sm sem-btn <?= $active ?> rounded-pill px-3 fw-bold"
                                    data-card="<?= $o['id'] ?>"
                                    data-sem="<?= $encSemesters[$i] ?>"
                                    data-offre="<?= $encId ?>"
                                    data-accessible="<?= $isAccessible ? '1' : '0' ?>"
                                    onclick="selectSem(this)"
                                    style="font-family:'Outfit';font-size:.82rem;<?= $isAccessible ? '' : 'opacity:0.55;' ?>">
                                    <?= $semLabels[$i-1] ?><?= $isAccessible ? '' : ' 🔒' ?>
                                </button>
                                <?php endfor; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    {{-- action buttons --}}
                    <div class="d-flex gap-2 mt-auto">
                        <?php if ($maxSem <= 1): ?>
                        <a href="/dashboard/grades/input?offre_id=<?= $encId ?>&semestre=<?= $encSemesters[1] ?>"
                           class="btn btn-sm flex-fill py-2 fw-bold text-white shadow-sm rounded-3"
                           style="background:<?= $diplomeInfo['color'] ?>;border:none;font-size:.85rem;">
                            <?php if (empty($hasActiveWindow)): ?>
                                <i class="fa-solid fa-eye me-1"></i> معاينة النقاط النهائية
                            <?php else: ?>
                                <i class="fa-solid fa-pen-to-square me-1"></i> رصد النقاط النهائية
                            <?php endif; ?>
                        </a>
                        <a href="/dashboard/grades/deliberation?offre_id=<?= $encId ?>&semestre=<?= $encSemesters[1] ?>"
                           target="_blank"
                           class="btn btn-sm btn-light border py-2 px-3 fw-bold rounded-3"
                           title="محضر المداولة">
                            <i class="fa-solid fa-gavel" style="color:#d97706;"></i>
                        </a>
                        <?php else: ?>
                        <a id="inputLink_<?= $o['id'] ?>" href="/dashboard/grades/input?offre_id=<?= $encId ?>&semestre=<?= $encSemesters[1] ?>"
                           class="btn btn-sm flex-fill py-2 fw-bold text-white shadow-sm rounded-3"
                           style="background:#0284c7;border:none;font-size:.85rem;">
                            <?php if (empty($hasActiveWindow)): ?>
                                <i class="fa-solid fa-eye me-1"></i> معاينة النقاط
                            <?php else: ?>
                                <i class="fa-solid fa-pen-to-square me-1"></i> رصد النقاط
                            <?php endif; ?>
                        </a>
                        <a id="pvLink_<?= $o['id'] ?>" href="/dashboard/grades/deliberation?offre_id=<?= $encId ?>&semestre=<?= $encSemesters[1] ?>"
                           target="_blank"
                           class="btn btn-sm py-2 px-3 fw-bold rounded-3"
                           style="border:1px solid rgba(245,158,11,.25);color:#d97706;background:#fffbeb;"
                           title="محضر المداولة">
                            <i class="fa-solid fa-gavel"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
    </div>

    <!-- ===== Pagination Controls ===== -->
    <?php if (isset($lastPage) && $lastPage > 1): ?>
    <div class="d-flex justify-content-between align-items-center mt-4 p-3 bg-white rounded-4 shadow-sm border">
        <div class="small text-muted fw-bold">
            عرض <?= (($currentPage - 1) * $perPage) + 1 ?> إلى <?= min($currentPage * $perPage, $totalOffres) ?> من إجمالي <?= $totalOffres ?> عرض
        </div>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <?php if ($currentPage > 1): ?>
                    <li class="page-item">
                        <a class="page-link rounded-2 px-3 fw-bold ms-1" href="?<?= http_build_query(array_merge(request()->all(), ['page' => $currentPage - 1])) ?>">
                            <i class="fa-solid fa-chevron-right me-1"></i> السابقة
                        </a>
                    </li>
                <?php endif; ?>

                <?php 
                $startP = max(1, $currentPage - 2);
                $endP   = min($lastPage, $currentPage + 2);
                for ($p = $startP; $p <= $endP; $p++): 
                ?>
                    <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>">
                        <a class="page-link rounded-2 fw-bold mx-1" href="?<?= http_build_query(array_merge(request()->all(), ['page' => $p])) ?>">
                            <?= $p ?>
                        </a>
                    </li>
                <?php endfor; ?>

                <?php if ($currentPage < $lastPage): ?>
                    <li class="page-item">
                        <a class="page-link rounded-2 px-3 fw-bold me-1" href="?<?= http_build_query(array_merge(request()->all(), ['page' => $currentPage + 1])) ?>">
                            التالية <i class="fa-solid fa-chevron-left ms-1"></i>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>

</div>

<script>
// ── Encrypted semester map ────────────────────────────────────────────────
const encSem = {
    1: '<?= $encSemesters[1] ?>',
    2: '<?= $encSemesters[2] ?>',
    3: '<?= $encSemesters[3] ?>',
    4: '<?= $encSemesters[4] ?>',
    5: '<?= $encSemesters[5] ?>',
    6: '<?= $encSemesters[6] ?>'
};

// ── Semester selection per-card ───────────────────────────────────────────
function selectSem(btn) {
    if (btn.dataset.accessible === '0') {
        Swal.fire({
            icon: 'warning',
            title: 'تنبيه بيداغوجي',
            text: 'عذراً، لا يمكن الانتقال لرصد نقاط أو مداولات هذا السداسي قبل إتمام ومداولة السداسي السابق أولاً.',
            confirmButtonText: 'حسناً',
            confirmButtonColor: '#0284c7'
        });
        return;
    }
    const cardId = btn.dataset.card;
    // deactivate siblings
    document.querySelectorAll(`#semBtns_${cardId} .sem-btn`).forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    const offreId = btn.dataset.offre;
    const semEnc  = btn.dataset.sem;
    // update links
    const inp = document.getElementById(`inputLink_${cardId}`);
    const pv  = document.getElementById(`pvLink_${cardId}`);
    
    const pathPrefix = window.location.pathname.startsWith('/sig') ? '/sig' : '';
    if (inp) inp.href = `${pathPrefix}/dashboard/grades/input?offre_id=${offreId}&semestre=${semEnc}`;
    if (pv)  pv.href  = `${pathPrefix}/dashboard/grades/deliberation?offre_id=${offreId}&semestre=${semEnc}`;
}

// ── Live search + filter ──────────────────────────────────────────────────
let activeMode = 'all';

function setMode(mode, btn) {
    activeMode = mode;
    document.querySelectorAll('.filter-mode').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    filterCards();
}

function filterCards() {
    const q      = (document.getElementById('liveSearch')?.value || '').toLowerCase().trim();
    const dip    = document.getElementById('filterDiplome')?.value || '';
    const cards  = document.querySelectorAll('.offre-card');
    let visible  = 0;

    cards.forEach(card => {
        const nameOk   = !q || card.dataset.name.includes(q);
        const modeOk   = activeMode === 'all' || card.dataset.mode === activeMode;
        const diplomeOk= !dip || card.dataset.diplome === dip;
        const show     = nameOk && modeOk && diplomeOk;
        card.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    const badge = document.getElementById('countBadge');
    if (badge) badge.textContent = `${visible} تخصص معروض من أصل ${cards.length}`;
}

// init count
document.addEventListener('DOMContentLoaded', () => filterCards());
</script>

<style>
/* Semester btn states */
.sem-btn { border:1.5px solid #cbd5e1; color:#475569; background:#fff; transition:.2s; }
.sem-btn.active { background:#0284c7 !important; color:#fff !important; border-color:#0284c7 !important; }
.sem-btn:hover:not(.active) { background:#f0f9ff; border-color:#0284c7; color:#0284c7; }

/* Filter mode btns */
.filter-mode { border:1.5px solid #e2e8f0; color:#64748b; background:#fff; transition:.2s; }
.filter-mode.active { background:#0284c7 !important; color:#fff !important; border-color:#0284c7 !important; }
.filter-mode:hover:not(.active) { background:#f0f9ff; color:#0284c7; border-color:#0284c7; }

/* card hover */
.card-hover { transition:transform .25s, box-shadow .25s; }
.card-hover:hover { transform:translateY(-6px); box-shadow:0 18px 40px rgba(15,23,42,.09) !important; }

/* input search */
#liveSearch:focus { box-shadow: none; }
</style>

@endsection
