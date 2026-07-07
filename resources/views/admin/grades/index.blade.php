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
    'CAP' => 'شهادة الكفاءة المهنية',
    'BEP' => 'شهادة التعليم المهني',
    'BP'  => 'شهادة المهارة المهنية',
    'BTS' => 'شهادة تقني سامي',
    'TS'  => 'شهادة تقني',
    'BFCA'=> 'شهادة مكوّن متخصص',
    'CMP' => 'شهادة التحكم المهني',
    'Qualifiant' => 'شهادة تأهيلية',
];
?>

<div class="page-header-banner mb-4" style="background: linear-gradient(135deg, var(--color-sky-blue) 0%, var(--color-sky-blue-hover) 100%); padding: 2.2rem 2rem; color: #fff; border-radius: 0 0 20px 20px;">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
            <h3 class="fw-bold mb-1"><i class="fa-solid fa-graduation-cap me-2"></i>دفتر العلامات والمداولات البيداغوجية الموحد</h3>
            <p class="mb-0 opacity-75 small">بوابة رصد علامات المراقبة المستمرة والامتحانات، استخراج كشوف النقاط ومحاضر المداولات الرسمية للوزارة.</p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <?php 
            $roleCode = strtolower(session('user')['role_code'] ?? '');
            if (in_array($roleCode, ['admin','dfep','etablissement','directeur'])): ?>
                <a href="/dashboard/grades/progress" class="btn btn-light px-3.5 py-2 fw-bold shadow-sm text-decoration-none" style="border-radius: 12px; color: var(--color-sky-blue-hover); font-size: 0.88rem;">
                    <i class="fa-solid fa-chart-line me-1"></i> متابعة تقدم الرصد
                </a>
            <?php endif; ?>
            <?php if ($roleCode === 'admin'): ?>
                <a href="/dashboard/grades/control" class="btn btn-light px-3.5 py-2 fw-bold shadow-sm text-decoration-none" style="border-radius: 12px; color: var(--color-sky-blue-hover); font-size: 0.88rem;">
                    <i class="fa-solid fa-gears me-1"></i> إعدادات النظام
                </a>
            <?php endif; ?>
            <a href="/dashboard" class="btn btn-light px-4 py-2 fw-bold shadow-sm text-decoration-none" style="border-radius: 12px; color: var(--color-sky-blue-hover); font-size: 0.88rem;">
                <i class="fa-solid fa-house me-1"></i> لوحة التحكم
            </a>
        </div>
    </div>
</div>

<div class="container-fluid px-4">

    <?php if ($success): ?>
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4">
            <i class="fa-solid fa-circle-check me-2"></i> <?= $success ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-4">
            <i class="fa-solid fa-triangle-exclamation me-2"></i> <?= $error ?>
        </div>
    <?php endif; ?>

    <!-- 🔍 Advanced Dashboard Filters -->
    <div class="card border-0 shadow-sm rounded-4 mb-4" style="background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.6);">
        <div class="card-body p-3.5">
            <form method="GET" action="" id="dashboardFilterForm" class="row g-3 align-items-end">
                <?php 
                $user = session('user');
                $role = strtolower($user['role_code'] ?? '');
                $isAdmin = in_array($role, ['admin', 'central', 'high_admin', 'secretaire_general', 'ministre']);
                $isDfep = ($role === 'dfep');
                ?>

                <!-- Wilaya Filter -->
                <div class="col-md-4" <?php if(!$isAdmin): ?> style="display:none;" <?php endif; ?>>
                    <label class="form-label fw-bold small text-muted"><i class="fa-solid fa-map-location-dot me-1"></i>الولاية (DFEP)</label>
                    <select name="filter_wilaya" class="form-select border-0 shadow-sm bg-light" style="border-radius: 12px; padding: 0.65rem;" onchange="this.form.submit()">
                        <option value="">-- كل الولايات --</option>
                        <?php foreach($wilayas as $w): ?>
                            <option value="<?= $w['id'] ?>" <?= $selected_wilaya == $w['id'] ? 'selected' : '' ?>><?= htmlspecialchars($w['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Establishment Filter -->
                <div class="col-md-5" <?php if(!$isAdmin && !$isDfep): ?> style="display:none;" <?php endif; ?>>
                    <label class="form-label fw-bold small text-muted"><i class="fa-solid fa-hotel me-1"></i>المؤسسة التكوينية</label>
                    <select name="filter_etab" class="form-select border-0 shadow-sm bg-light" style="border-radius: 12px; padding: 0.65rem;" onchange="this.form.submit()">
                        <option value="">-- كل المؤسسات --</option>
                        <?php foreach($etablissements as $e): ?>
                            <option value="<?= $e['id'] ?>" <?= $selected_etab == $e['id'] ? 'selected' : '' ?>><?= htmlspecialchars($e['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Year Filter -->
                <div class="col-md-2">
                    <label class="form-label fw-bold small text-muted"><i class="fa-solid fa-calendar-days me-1"></i>سنة الدورة</label>
                    <select name="filter_year" class="form-select border-0 shadow-sm bg-light" style="border-radius: 12px; padding: 0.65rem;" onchange="this.form.submit()">
                        <option value="">-- كل السنوات --</option>
                        <?php foreach($years as $y): ?>
                            <option value="<?= $y ?>" <?= $selected_year == $y ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Reset Button -->
                <div class="col-md-1 text-end">
                    <a href="?" class="btn btn-light w-100 fw-bold border" style="border-radius: 12px; padding: 0.65rem;" title="إعادة تعيين">
                        <i class="fa-solid fa-arrows-rotate"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- 📊 Quick Metrics Grid -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 rounded-4" style="background: rgba(255,255,255,0.9); border-right: 4px solid var(--color-sky-blue) !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-bold d-block mb-1">المتربصون النشطون</span>
                            <h3 class="fw-extrabold text-dark mb-0" style="font-family:'Outfit';"><?= $stats['total_stagiaires'] ?? 0 ?></h3>
                        </div>
                        <div class="p-2.5 bg-primary-subtle text-primary rounded-3" style="color: var(--color-sky-blue) !important; background-color: var(--color-sky-blue-light) !important;">
                            <i class="fa-solid fa-users fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 rounded-4" style="background: rgba(255,255,255,0.9); border-right: 4px solid #10b981 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-bold d-block mb-1">النقاط المرصودة</span>
                            <h3 class="fw-extrabold text-dark mb-0" style="font-family:'Outfit';"><?= $stats['total_notes'] ?? 0 ?></h3>
                        </div>
                        <div class="p-2.5 bg-success-subtle text-success rounded-3">
                            <i class="fa-solid fa-pen-to-square fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 rounded-4" style="background: rgba(255,255,255,0.9); border-right: 4px solid #f59e0b !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-bold d-block mb-1">النتائج المحتسبة</span>
                            <h3 class="fw-extrabold text-dark mb-0" style="font-family:'Outfit';"><?= $stats['resultats_valides'] ?? 0 ?></h3>
                        </div>
                        <div class="p-2.5 bg-warning-subtle text-warning rounded-3">
                            <i class="fa-solid fa-square-poll-vertical fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 rounded-4" style="background: rgba(255,255,255,0.9); border-right: 4px solid #8b5cf6 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-bold d-block mb-1">المحاضر المعتمدة</span>
                            <h3 class="fw-extrabold text-dark mb-0" style="font-family:'Outfit';"><?= $stats['pvs_approuves'] ?? 0 ?></h3>
                        </div>
                        <div class="p-2.5 bg-purple-subtle text-purple rounded-3" style="color:#8b5cf6; background-color:#f5f3ff;">
                            <i class="fa-solid fa-file-signature fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ⚡️ Quick Access: PV Deliberations & Grades Entry -->
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4" style="background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.6);">
        <div class="row align-items-center">
            <div class="col-lg-5 mb-3 mb-lg-0">
                <h5 class="fw-bold text-dark mb-1"><i class="fa-solid fa-scale-balanced text-primary me-2" style="color: var(--color-sky-blue) !important;"></i> لوحة التحكم والمداولات البيداغوجية</h5>
                <p class="text-muted mb-0 small">اختر التخصص الدراسي والسداسي للانتقال مباشرة لرصد النقاط أو طباعة محضر المداولات الرسمي للقسم.</p>
            </div>
            <div class="col-lg-7">
                <div class="row g-3">
                    <div class="col-md-6" id="offreCol">
                        <select id="selectOffre" class="form-select" style="border-radius: 12px; padding: 0.7rem;" required>
                            <option value="" disabled selected>-- اختر الشعبة والتخصص البيداغوجي --</option>
                            <?php foreach ($offres as $o): ?>
                                <option value="<?= \App\Helpers\SecureIdHelper::encrypt($o['id']) ?>" data-maxsem="<?= $o['duree_semestres'] ?? 4 ?>" data-diplome="<?= $o['diplome_vise'] ?? '' ?>" data-mode="<?= $o['mode_formation'] ?>">
                                    <?= htmlspecialchars($o['spec_ar']) ?> - <?= htmlspecialchars($o['etab_nom']) ?> (<?= $diplomeLabels[$o['diplome_vise'] ?? ''] ?? ($o['spec_code'] ?? '') ?> - <?= $o['nb_actifs'] ?> متربص)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3" id="employeurContainer" style="display: none;">
                        <select id="selectEmployeur" class="form-select" style="border-radius: 12px; padding: 0.7rem;">
                            <option value="">-- كل المؤسسات المستقبلة --</option>
                        </select>
                    </div>
                    <div class="col-md-3" id="semCol">
                        <select id="selectSemestre" class="form-select" style="border-radius: 12px; padding: 0.7rem;" required>
                            <option value="<?= \App\Helpers\SecureIdHelper::encrypt(1) ?>">السداسي الأول (S1)</option>
                            <option value="<?= \App\Helpers\SecureIdHelper::encrypt(2) ?>">السداسي الثاني (S2)</option>
                            <option value="<?= \App\Helpers\SecureIdHelper::encrypt(3) ?>">السداسي الثالث (S3)</option>
                            <option value="<?= \App\Helpers\SecureIdHelper::encrypt(4) ?>">السداسي الرابع (S4)</option>
                            <option value="<?= \App\Helpers\SecureIdHelper::encrypt(5) ?>">السداسي الخامس (S5)</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-grid">
                        <button onclick="navigateAction('input')" class="btn text-white py-2.5 fw-bold shadow-sm" style="background: linear-gradient(135deg, var(--color-sky-blue) 0%, var(--color-sky-blue-hover) 100%); border: none; border-radius: 12px;">
                            <i class="fa-solid fa-pen-to-square me-1"></i> رصد النقاط
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 📚 Available Training Offers Cards -->
    <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-folder-open text-muted me-2"></i> الشعب والتخصصات المفتوحة للمؤسسة:</h5>
    <div class="row g-4">
        <?php if (empty($offres)): ?>
            <div class="col-12 text-center py-5 text-muted bg-white rounded-4 shadow-sm">
                <i class="fa-solid fa-graduation-cap d-block mb-3" style="font-size: 3.5rem; opacity: 0.3;"></i>
                لا توجد عروض تكوين أو تخصصات نشطة في فرعك حالياً.
            </div>
        <?php else: ?>
            <?php foreach ($offres as $o): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm overflow-hidden card-hover" style="border-radius: 18px; border: 1px solid rgba(14, 165, 233, 0.1); border-right: 4px solid var(--color-sky-blue) !important; transition: all 0.3s;">
                        <div class="card-body p-4 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <span class="badge px-3 py-1.5 fw-bold" style="font-size: 0.8rem; background-color: var(--color-sky-blue-light); color: var(--color-sky-blue-hover);">
                                        <?= $diplomeLabels[$o['diplome_vise'] ?? ''] ?? ($o['spec_code'] ?? '—') ?>
                                    </span>
                                    <span class="badge bg-light text-muted small fw-bold px-2 py-1" style="font-family:'Outfit';">
                                        <?= (int)$o['mode_formation'] === 10 ? 'تمهين' : 'حضوري' ?>
                                    </span>
                                </div>
                                <h5 class="fw-bold text-dark mb-1.5 leading-snug"><?= htmlspecialchars($o['spec_ar']) ?></h5>
                                <p class="text-muted small mb-3"><i class="fa-solid fa-hotel me-1.5"></i><?= htmlspecialchars($o['etab_nom']) ?></p>
                                
                                <div class="p-3 rounded-3 d-flex justify-content-around mb-4 text-muted small" style="background-color: #f8fafc; border: 1px solid #f1f5f9;">
                                    <div class="text-center">
                                        <small class="d-block text-muted">المتربصين</small>
                                        <strong class="text-dark fs-6" style="font-family:'Outfit';"><?= $o['nb_actifs'] ?></strong>
                                    </div>
                                    <div class="vr" style="opacity: 0.15;"></div>
                                    <div class="text-center">
                                        <small class="d-block text-muted">السداسيات</small>
                                        <strong class="text-dark fs-6" style="font-family:'Outfit';"><?= $o['duree_semestres'] ?? '—' ?></strong>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <a href="/dashboard/grades/input?offre_id=<?= \App\Helpers\SecureIdHelper::encrypt($o['id']) ?>&semestre=<?= \App\Helpers\SecureIdHelper::encrypt(1) ?>" class="btn btn-primary btn-sm flex-fill py-2 fw-bold text-white shadow-sm" style="border-radius: 10px; background-color: var(--color-sky-blue); border:none;">
                                    <i class="fa-solid fa-pen-to-square me-1"></i> رصد النقاط
                                </a>
                                <a href="/dashboard/grades/deliberation?offre_id=<?= \App\Helpers\SecureIdHelper::encrypt($o['id']) ?>&semestre=<?= \App\Helpers\SecureIdHelper::encrypt(1) ?>" target="_blank" class="btn btn-outline-warning btn-sm py-2 px-3 fw-bold" style="border-radius: 10px; border-color: rgba(245, 158, 11, 0.2); color: #d97706; background-color: #fffbeb;">
                                    <i class="fa-solid fa-gavel"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
const encryptedSemesters = {
    1: '<?= \App\Helpers\SecureIdHelper::encrypt(1) ?>',
    2: '<?= \App\Helpers\SecureIdHelper::encrypt(2) ?>',
    3: '<?= \App\Helpers\SecureIdHelper::encrypt(3) ?>',
    4: '<?= \App\Helpers\SecureIdHelper::encrypt(4) ?>',
    5: '<?= \App\Helpers\SecureIdHelper::encrypt(5) ?>',
    6: '<?= \App\Helpers\SecureIdHelper::encrypt(6) ?>'
};

// Limit semesters based on duration
document.getElementById('selectOffre').addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    const maxSem = parseInt(opt.dataset.maxsem) || 4;
    const mode = opt.dataset.mode || '';
    const semSelect = document.getElementById('selectSemestre');
    
    // Clear & refill based on duration
    semSelect.innerHTML = '';
    const semesterLabels = {
        1: 'السداسي الأول (S1)',
        2: 'السداسي الثاني (S2)',
        3: 'السداسي الثالث (S3)',
        4: 'السداسي الرابع (S4)',
        5: 'السداسي الخامس (S5)'
    };
    
    for (let s = 1; s <= maxSem; s++) {
        const o = document.createElement('option');
        o.value = encryptedSemesters[s] || s;
        o.textContent = semesterLabels[s] || `السداسي ${s}`;
        semSelect.appendChild(o);
    }

    // Toggle Host Enterprise select for apprenticeship
    const empContainer = document.getElementById('employeurContainer');
    const offreCol = document.getElementById('offreCol');
    const semCol = document.getElementById('semCol');
    const selectEmployeur = document.getElementById('selectEmployeur');

    if (mode == '10') { // 10 is apprenticeship mode
        empContainer.style.display = 'block';
        offreCol.className = 'col-md-4';
        semCol.className = 'col-md-2';
        
        // Fetch employers via AJAX
        selectEmployeur.innerHTML = '<option value="">جاري التحميل...</option>';
        fetch(`/sig/dashboard/grades/get-employeurs?offre_id=${this.value}`)
            .then(res => res.json())
            .then(data => {
                selectEmployeur.innerHTML = '<option value="">-- كل المؤسسات المستقبلة --</option>';
                data.forEach(emp => {
                    const o = document.createElement('option');
                    o.value = emp.id;
                    o.textContent = emp.nom || emp.nom_fr;
                    selectEmployeur.appendChild(o);
                });
            })
            .catch(err => {
                console.error(err);
                selectEmployeur.innerHTML = '<option value="">خطأ في التحميل</option>';
            });
    } else {
        empContainer.style.display = 'none';
        offreCol.className = 'col-md-6';
        semCol.className = 'col-md-3';
        selectEmployeur.innerHTML = '<option value="">-- كل المؤسسات المستقبلة --</option>';
    }
});

function navigateAction(action) {
    const oId = document.getElementById('selectOffre').value;
    const sem = document.getElementById('selectSemestre').value;
    const empId = document.getElementById('selectEmployeur') ? document.getElementById('selectEmployeur').value : '';
    
    if (!oId) {
        alert('الرجاء اختيار التخصص البيداغوجي أولاً.');
        return;
    }
    
    if (action === 'input') {
        let url = `/sig/dashboard/grades/input?offre_id=${oId}&semestre=${sem}`;
        if (empId) {
            url += `&employeur_id=${empId}`;
        }
        window.location.href = url;
    }
}
</script>

<style>
.card-hover:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(15, 23, 42, 0.08) !important;
}
.leading-snug {
    line-height: 1.35;
}
</style>

@endsection
