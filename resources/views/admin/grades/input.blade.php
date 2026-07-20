@extends('layouts.main')
@section('title', $title ?? 'المنصة الرقمية للتكوين المهني')
@section('content')
<?php
/**
 * @var array  $offre
 * @var array  $config
 * @var array  $matieres
 * @var array  $matiere
 * @var array  $students
 * @var int    $semestre
 * @var array  $employeurs
 * @var int|null $employeur_id
 */

$employeur_id = $employeur_id ?? null;
$employeurs   = $employeurs ?? [];
$empParam     = $employeur_id ? '&employeur_id=' . $employeur_id : '';

$diplome      = $offre['diplome_vise']   ?? 'BEP';
$mode         = $offre['mode_formation'] ?? 'presentiel';
$duree_sem    = $offre['duree_semestres'] ?? 4;
$nbSem        = $config['nb_semestres']  ?? $duree_sem;
$aMemoire     = !empty($config['a_memoire']);
$semMem       = (int)($config['semestre_memoire'] ?? 0);
$isMemSem     = $aMemoire && ($semestre == $semMem);

$semestreLabels = [
    1 => 'السداسي الأول (S1)',
    2 => 'السداسي الثاني (S2)',
    3 => 'السداسي الثالث (S3)',
    4 => 'السداسي الرابع (S4)',
    5 => 'السداسي الخامس – مذكرة التخرج (S5)',
];

$typeLabels = [
    'theorique'    => 'نظري',
    'tp'           => 'أ. تطبيقية',
    'stage_pratique'=> 'تربص',
    'memoire'      => 'مذكرة التخرج',
    'oral'         => 'مرافعة شفهية',
    'projet_fin'   => 'مشروع نهاية',
];

$success = $_SESSION['success'] ?? null;
$error   = $_SESSION['error']   ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>

<div class="page-header-banner mb-0" style="background:linear-gradient(135deg,#0284c7 0%,#0ea5e9 100%);padding:1.8rem 2rem;color:#fff;">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
            <h4 class="fw-bold mb-1"><i class="fa-solid fa-pen-to-square me-2"></i>رصد نقاط المتربصين – نظام MFEP</h4>
            <p class="mb-0 opacity-75 small"><?= htmlspecialchars($offre['spec_ar']) ?> | <?= $diplome ?> | <?= htmlspecialchars($offre['etab_nom']) ?></p>
        </div>
        <div class="d-flex gap-2">
            <a href="/dashboard/grades" class="btn btn-light btn-sm fw-bold">
                <i class="fa-solid fa-arrow-right me-1"></i> لوحة التنقيط
            </a>
            <a href="/dashboard/grades/deliberation?offre_id=<?= $offre['id'] ?>&semestre=<?= $semestre ?>"
               class="btn btn-warning btn-sm fw-bold text-dark">
                <i class="fa-solid fa-gavel me-1"></i> محضر المداولات
            </a>
        </div>
    </div>
</div>

<?php if ($success): ?>
<div class="alert alert-success m-3 fw-bold border-0 shadow-sm rounded-4">
    <i class="fa-solid fa-circle-check me-2"></i><?= $success ?>
</div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-danger m-3 fw-bold border-0 shadow-sm rounded-4">
    <i class="fa-solid fa-triangle-exclamation me-2"></i><?= $error ?>
</div>
<?php endif; ?>
<?php if ($is_locked ?? false): ?>
<div class="alert alert-danger m-3 fw-bold border-0 shadow-sm rounded-4">
    <i class="fa-solid fa-lock me-2"></i>لقد تم قفل إدخال النقاط لانتهاء فترة الحجز المحددة من الإدارة.
</div>
<?php endif; ?>

<div class="container-fluid px-4 py-3">

    <!-- ===== STEP 1: اختيار السداسي والمادة ===== -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-filter me-2 text-primary"></i>تحديد السداسي والمادة</h6>
            <div class="row g-3">
                <!-- السداسيات -->
                <div class="col-md-6">
                    <label class="form-label fw-bold small text-muted">السداسي / Semestre</label>
                    <div class="d-flex flex-wrap gap-2">
                        <?php for ($s = 1; $s <= $nbSem; $s++): ?>
                            <a href="?offre_id=<?= $offre['id'] ?>&semestre=<?= $s ?>&matiere_id=<?= $matiere['id'] ?? '' ?><?= $empParam ?>"
                               class="btn btn-sm fw-bold rounded-pill px-3 py-2
                                      <?= $s == $semestre ? 'btn-primary' : 'btn-outline-secondary' ?>">
                                <?php if ($aMemoire && $s == $semMem): ?>
                                    <i class="fa-solid fa-scroll me-1"></i>
                                <?php endif; ?>
                                S<?= $s ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                    <small class="text-muted d-block mt-1">
                        <?= $semestreLabels[$semestre] ?? 'السداسي '.$semestre ?>
                        <?php if ($isMemSem): ?>
                            <span class="badge bg-warning-subtle text-warning ms-2 fw-bold">يشمل مذكرة التخرج</span>
                        <?php endif; ?>
                    </small>
                </div>

                <!-- المواد -->
                <div class="col-md-6">
                    <label class="form-label fw-bold small text-muted">المادة / Matière</label>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($matieres as $m): ?>
                            <a href="?offre_id=<?= $offre['id'] ?>&semestre=<?= $semestre ?>&matiere_id=<?= $m['id'] ?><?= $empParam ?>"
                               class="btn btn-sm fw-bold rounded-pill px-3 py-1
                                      <?= $m['id'] == ($matiere['id'] ?? 0) ? 'btn-primary' : 'btn-outline-secondary' ?>">
                                <?= htmlspecialchars($m['code'] ?? 'M'.$m['id']) ?>
                                <span class="badge bg-white text-dark ms-1"><?= $m['coefficient'] ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($matiere): ?>
                    <small class="text-muted d-block mt-1">
                        <?= htmlspecialchars($matiere['libelle_ar']) ?>
                        | النوع: <strong><?= $typeLabels[$matiere['type_matiere']] ?? $matiere['type_matiere'] ?></strong>
                        | المعامل: <strong><?= $matiere['coefficient'] ?></strong>
                    </small>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ((int)$offre['mode_formation'] === 10): ?>
                <div class="row g-3 mt-2 pt-3 border-top">
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-muted">تصفية حسب المؤسسة المستقبلة / Filtrer par Entreprise</label>
                        <select id="filterEmployeur" class="form-select" style="border-radius: 12px; padding: 0.6rem;" onchange="filterByEmployeur(this.value)">
                            <option value="">-- كل المؤسسات المستقبلة --</option>
                            <?php foreach ($employeurs as $emp): ?>
                                <option value="<?= $emp['id'] ?>" <?= $employeur_id == $emp['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($emp['nom'] ?: $emp['nom_fr']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!$matiere): ?>
        <div class="alert alert-info fw-bold rounded-4">
            <i class="fa-solid fa-circle-info me-2"></i>
            لا توجد مواد مسجلة لهذا السداسي. يرجى إضافة المواد أولاً من إعدادات التخصص.
        </div>
    <?php elseif (empty($students)): ?>
        <div class="alert alert-warning fw-bold rounded-4">
            <i class="fa-solid fa-users me-2"></i>
            لا يوجد متربصون نشطون في هذا العرض.
        </div>
    <?php else: ?>

    <!-- ===== معلومات الصيغة الرسمية (تظهر فقط للأدمن) ===== -->
    <?php if (in_array(strtolower(session('user')['role_code'] ?? ''), ['admin', 'high_admin'])): ?>
    <div class="alert border-0 rounded-4 mb-3 p-3"
         style="background:rgba(2,132,199,0.05); border-right: 4px solid #0284c7 !important;">
        <div class="row align-items-center">
            <div class="col-auto">
                <i class="fa-solid fa-circle-info fa-2x text-primary opacity-75"></i>
            </div>
            <div class="col">
                <p class="mb-0 fw-bold text-dark small">صيغة الحساب الرسمية لوزارة التكوين والتعليم المهنيين</p>
                <?php if ($matiere['type_matiere'] === 'memoire'): ?>
                    <p class="mb-0 text-muted small">
                        معدل المذكرة = <strong>نقطة المذكرة × 60%</strong> + <strong>نقطة المناقشة × 40%</strong>
                    </p>
                <?php elseif ($matiere['type_matiere'] === 'stage_pratique'): ?>
                    <p class="mb-0 text-muted small">التقييم الميداني: نقطة واحدة مباشرة من المشرف على التربص.</p>
                <?php elseif (in_array((int)$offre['mode_formation'], [18, 21])): ?>
                    <p class="mb-0 text-muted small">
                        معدل المادة (عن بعد) = <strong>(نقطة التفاعل)</strong> + <strong>(الفروض)</strong> + <strong>(الامتحان)</strong> حسب أوزان التكوين عن بعد.
                        &nbsp;| النقطة الإقصائية: أقل من 5/20
                    </p>
                <?php else: ?>
                    <p class="mb-0 text-muted small">
                        معدل المادة = <strong>(ف1 + ف2) / 2 × 40%</strong> + <strong>أكبر(اختبار، استدراكي) × 60%</strong>
                        &nbsp;| النقطة الإقصائية: أقل من 5/20
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ===== إشعار حالة فترة رصد النقاط ===== -->
    <?php if (!empty($is_locked)): ?>
        <div class="alert alert-danger border-0 rounded-4 mb-3 d-flex align-items-center gap-3"
             style="background:#fef2f2; color:#991b1b; border: 1px solid rgba(220,38,38,0.2);">
            <i class="fa-solid fa-lock fa-2x"></i>
            <div>
                <strong class="d-block mb-1">وضعية المعاينة فقط (رصد النقاط مغلق):</strong>
                <?php if (!empty($windowAccess['next_window'])): ?>
                    <span class="small">فترة الرصد مغلقة حالياً. من المقرر فتح فترة الرصد القادمة: <strong><?= htmlspecialchars($windowAccess['next_window']['label']) ?></strong> ابتداءً من <?= htmlspecialchars($windowAccess['next_window']['date_ouverture']) ?>.</span>
                <?php else: ?>
                    <span class="small">فترة الرصد مغلقة حالياً لعدم تفعيل نافذة رصد من قبل الإدارة العامة. يرجى الاتصال بالمسؤول لفتح الرصد.</span>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-success border-0 rounded-4 mb-3 d-flex align-items-center gap-3"
             style="background:#f0fdf4; color:#166534; border: 1px solid rgba(22,163,74,0.2);">
            <i class="fa-solid fa-lock-open fa-2x text-success"></i>
            <div>
                <strong class="d-block mb-1">فترة الرصد نشطة ومفتوحة:</strong>
                <span class="small">يمكنك الآن إدخال وتعديل علامات المتربصين بحرية. تنتهي هذه الفترة تلقائياً في <strong><?= htmlspecialchars($windowAccess['window']['date_cloture'] ?? '') ?></strong>.</span>
            </div>
        </div>
    <?php endif; ?>

    <!-- ===== جدول إدخال النقاط ===== -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 pt-4 pb-2 px-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="fa-solid fa-table-list me-2 text-primary"></i>
                    قائمة المتربصين – <?= htmlspecialchars($matiere['libelle_ar']) ?>
                    <span class="badge bg-primary-subtle text-primary ms-2"><?= count($students) ?> متربص</span>
                </h6>
                <div class="d-flex gap-2 no-print">
                    <button type="button" onclick="exportTableToExcel('gradesTable', 'releve_notes_matiere.xls')" class="btn btn-sm btn-success rounded-pill px-3 fw-bold shadow-sm">
                        <i class="fa-solid fa-file-excel me-1"></i> Excel
                    </button>
                    <button type="button" onclick="exportTableToCSV('gradesTable', 'releve_notes_matiere.csv')" class="btn btn-sm btn-info text-white rounded-pill px-3 fw-bold shadow-sm">
                        <i class="fa-solid fa-file-csv me-1"></i> CSV
                    </button>
                    <button type="button" onclick="fillAllMax()" class="btn btn-sm btn-outline-success fw-bold">
                        <i class="fa-solid fa-fill me-1"></i> ملء تجريبي (20)
                    </button>
                    <button type="button" onclick="clearAll()" class="btn btn-sm btn-outline-secondary fw-bold">
                        <i class="fa-solid fa-eraser me-1"></i> مسح الكل
                    </button>
                </div>
            </div>
        </div>

        <form method="POST" action="/dashboard/grades/store" id="gradesForm">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?? '' ?>">
            <input type="hidden" name="matiere_id" value="<?= $matiere['id'] ?>">
            <input type="hidden" name="offre_id"   value="<?= $offre['id'] ?>">
            <input type="hidden" name="semestre"   value="<?= $semestre ?>">

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 grades-input-table" id="gradesTable">
                    <thead class="table-light">
                        <tr class="text-center small fw-bold text-muted">
                            <th class="text-right" style="min-width:200px;">الاسم واللقب</th>
                            <th>رقم التسجيل</th>
                            <?php if (in_array($matiere['type_matiere'], ['theorique','tp','oral'])): ?>
                                <?php if (in_array((int)$offre['mode_formation'], [18, 21])): ?>
                                    <th>نقطة التفاعل<br><small class="text-muted">/20</small></th>
                                    <th>الفروض<br><small class="text-muted">/20</small></th>
                                    <th>الامتحان التجمعي<br><small class="text-muted">/40</small></th>
                                <?php else: ?>
                                    <th>المراقبة 1<br><small class="text-muted">/20</small></th>
                                    <th>المراقبة 2<br><small class="text-muted">/20</small></th>
                                    <th>الامتحان الشامل<br><small class="text-muted">/40</small></th>
                                <?php endif; ?>
                                <th>استدراكي<br><small class="text-muted">/20</small></th>
                            <?php elseif ($matiere['type_matiere'] === 'stage_pratique'): ?>
                                <th>نقطة التربص<br><small class="text-muted">/20</small></th>
                            <?php elseif ($matiere['type_matiere'] === 'memoire'): ?>
                                <th>المذكرة<br><small class="text-muted">/20</small></th>
                                <th>المناقشة<br><small class="text-muted">/20</small></th>
                            <?php endif; ?>
                            <th class="text-primary">معدل المادة</th>
                            <th>ملاحظة</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $st): ?>
                        <?php $sid = $st['id']; ?>
                        <tr class="<?= $st['est_eliminatoire'] ? 'table-danger' : '' ?>">
                            <td class="text-right fw-bold">
                                <?php 
                                $fullName = trim(($st['nom_ar'] ?? '') . ' ' . ($st['prenom_ar'] ?? ''));
                                if (empty($fullName)) {
                                    $fullName = 'متربص #' . $st['id'];
                                }
                                ?>
                                <?= htmlspecialchars($fullName) ?>
                                <?php if ($st['est_eliminatoire']): ?>
                                    <span class="badge bg-danger ms-1 small">إقصائية</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center text-muted small">
                                <code><?= htmlspecialchars($st['numero_matricule']) ?></code>
                            </td>

                            <?php if (in_array($matiere['type_matiere'], ['theorique','tp','oral'])): ?>
                                <td>
                                    <input type="number" name="grades[<?= $sid ?>][cc1]"
                                           value="<?= $st['note_cc1'] !== null ? $st['note_cc1'] : 0 ?>"
                                           class="form-control form-control-sm grade-input text-center fw-bold"
                                           min="0" max="20" step="0.25" placeholder="0"
                                           data-student="<?= $sid ?>" data-type="cc1" <?= ($is_locked ?? false) ? 'disabled' : '' ?>>
                                </td>
                                <td>
                                    <input type="number" name="grades[<?= $sid ?>][cc2]"
                                           value="<?= $st['note_cc2'] !== null ? $st['note_cc2'] : 0 ?>"
                                           class="form-control form-control-sm grade-input text-center fw-bold"
                                           min="0" max="20" step="0.25" placeholder="0"
                                           data-student="<?= $sid ?>" data-type="cc2" <?= ($is_locked ?? false) ? 'disabled' : '' ?>>
                                </td>
                                <td>
                                    <input type="number" name="grades[<?= $sid ?>][exam]"
                                           value="<?= $st['note_examen'] !== null ? $st['note_examen'] : 0 ?>"
                                           class="form-control form-control-sm grade-input text-center fw-bold"
                                           min="0" max="20" step="0.25" placeholder="0"
                                           data-student="<?= $sid ?>" data-type="exam" <?= ($is_locked ?? false) ? 'disabled' : '' ?>>
                                </td>
                                <td>
                                    <input type="number" name="grades[<?= $sid ?>][rattrapage]"
                                           value="<?= $st['note_rattrapage'] !== null ? $st['note_rattrapage'] : 0 ?>"
                                           class="form-control form-control-sm grade-input text-center"
                                           min="0" max="20" step="0.25" placeholder="0"
                                           style="border-color:#f59e0b;"
                                           data-student="<?= $sid ?>" data-type="rattrapage" <?= ($is_locked ?? false) ? 'disabled' : '' ?>>
                                </td>
                            <?php elseif ($matiere['type_matiere'] === 'stage_pratique'): ?>
                                <td>
                                    <input type="number" name="grades[<?= $sid ?>][stage]"
                                           value="<?= $st['note_stage'] !== null ? $st['note_stage'] : 0 ?>"
                                           class="form-control form-control-sm grade-input text-center fw-bold"
                                           min="0" max="20" step="0.25" placeholder="0"
                                           data-student="<?= $sid ?>" data-type="stage" <?= ($is_locked ?? false) ? 'disabled' : '' ?>>
                                </td>
                            <?php elseif ($matiere['type_matiere'] === 'memoire'): ?>
                                <td>
                                    <input type="number" name="grades[<?= $sid ?>][memoire]"
                                           value="<?= $st['note_memoire'] !== null ? $st['note_memoire'] : 0 ?>"
                                           class="form-control form-control-sm grade-input text-center fw-bold"
                                           min="0" max="20" step="0.25" placeholder="0"
                                           data-student="<?= $sid ?>" data-type="memoire" <?= ($is_locked ?? false) ? 'disabled' : '' ?>>
                                </td>
                                <td>
                                    <input type="number" name="grades[<?= $sid ?>][soutenance]"
                                           value="<?= $st['note_soutenance'] !== null ? $st['note_soutenance'] : 0 ?>"
                                           class="form-control form-control-sm grade-input text-center fw-bold"
                                           min="0" max="20" step="0.25" placeholder="0"
                                           data-student="<?= $sid ?>" data-type="soutenance" <?= ($is_locked ?? false) ? 'disabled' : '' ?>>
                                </td>
                            <?php endif; ?>

                            <!-- إضافة حقول مخفية فارغة للأنواع غير المعروضة -->
                            <?php foreach (['cc1','cc2','exam','rattrapage','stage','memoire','soutenance'] as $f): ?>
                                <?php if (!isset($_POST['grades'][$sid][$f])): ?>
                                    <input type="hidden" name="grades[<?= $sid ?>][<?= $f ?>]" value="">
                                <?php endif; ?>
                            <?php endforeach; ?>

                            <!-- معدل المادة المحسوب تلقائياً -->
                            <td class="text-center">
                                <span id="avg_<?= $sid ?>"
                                      class="fw-bold fs-6 <?= $st['note_finale'] >= 10 ? 'text-success' : ($st['note_finale'] > 0 ? 'text-danger' : 'text-muted') ?>"
                                      style="font-family:'Outfit';">
                                    <?= $st['note_finale'] > 0 ? number_format($st['note_finale'], 2) : '0.00' ?>
                                </span>
                                <?php if ($st['note_finale'] < 5 && $st['note_finale'] > 0): ?>
                                    <span class="d-block badge bg-danger-subtle text-danger small">إقصائية!</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <input type="text" name="grades[<?= $sid ?>][observation]"
                                       value="<?= htmlspecialchars($st['observation'] ?? '') ?>"
                                       class="form-control form-control-sm" placeholder="—" maxlength="100">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center p-4">
                <p class="text-muted small mb-0">
                    <i class="fa-solid fa-circle-exclamation text-danger me-1"></i>
                    النقطة الإقصائية: أقل من <strong>5/20</strong> تُوجب الاستدراك وقد تؤدي للإقصاء
                </p>
                <div class="d-flex gap-2">
                    <a href="/dashboard/grades/input?offre_id=<?= $offre['id'] ?>&semestre=<?= $semestre ?>&matiere_id=<?= $matiere['id'] ?><?= $empParam ?>"
                       class="btn btn-outline-secondary fw-bold">
                        <i class="fa-solid fa-rotate me-1"></i> إلغاء
                    </a>
                    <button type="submit" class="btn btn-primary fw-bold px-4 shadow-sm" <?= ($is_locked ?? false) ? 'disabled' : '' ?>>
                        <i class="fa-solid fa-floppy-disk me-2"></i> حفظ النقاط
                    </button>
                </div>
            </div>
        </form>
    </div>
    <?php endif; ?>
</div>

<script>
// =============================================================
// حساب المعدل الرسمي تلقائياً عند تغيير أي نقطة
// صيغة MFEP: (ف1+ف2)/2 × 40% + max(اختبار، استدراكي) × 60%
// =============================================================
document.querySelectorAll('.grade-input').forEach(input => {
    input.addEventListener('input', function() {
        const sid = this.dataset.student;
        calcAvg(sid);
    });
});

function calcAvg(sid) {
    const type = document.querySelector(`input[data-student="${sid}"][data-type="cc1"]`) ? 'theorique' :
                 document.querySelector(`input[data-student="${sid}"][data-type="memoire"]`) ? 'memoire' : 'stage';

    let avg = 0;

    if (type === 'theorique') {
        const cc1  = parseFloat(document.querySelector(`input[data-student="${sid}"][data-type="cc1"]`)?.value) || 0;
        const cc2  = parseFloat(document.querySelector(`input[data-student="${sid}"][data-type="cc2"]`)?.value) || 0;
        const exam = parseFloat(document.querySelector(`input[data-student="${sid}"][data-type="exam"]`)?.value) || 0;
        const ratt = parseFloat(document.querySelector(`input[data-student="${sid}"][data-type="rattrapage"]`)?.value) || 0;
        const ccAvg   = (cc1 + cc2) / 2;
        const bestExp = Math.max(exam, ratt);
        avg = Math.round((ccAvg * 0.40 + bestExp * 0.60) * 100) / 100;
    } else if (type === 'memoire') {
        const mem  = parseFloat(document.querySelector(`input[data-student="${sid}"][data-type="memoire"]`)?.value) || 0;
        const sout = parseFloat(document.querySelector(`input[data-student="${sid}"][data-type="soutenance"]`)?.value) || 0;
        avg = Math.round((mem * 0.60 + sout * 0.40) * 100) / 100;
    } else {
        avg = parseFloat(document.querySelector(`input[data-student="${sid}"][data-type="stage"]`)?.value) || 0;
    }

    const el = document.getElementById(`avg_${sid}`);
    if (!el) return;

    el.textContent = avg.toFixed(2);
    el.className = avg >= 10 ? 'fw-bold fs-6 text-success' :
                   avg > 0 && avg < 10 ? 'fw-bold fs-6 text-danger' : 'fw-bold fs-6 text-muted';

    el.style.fontFamily = "'Outfit', monospace";

    // تظليل الصف إذا كانت النقطة إقصائية
    const row = el.closest('tr');
    if (avg > 0 && avg < 5) {
        row.classList.add('table-danger');
    } else {
        row.classList.remove('table-danger');
    }
}

function fillAllMax() {
    document.querySelectorAll('.grade-input').forEach(i => {
        if (!i.value) i.value = 18;
        const ev = new Event('input'); i.dispatchEvent(ev);
    });
}
function clearAll() {
    document.querySelectorAll('.grade-input').forEach(i => {
        i.value = '';
        const ev = new Event('input'); i.dispatchEvent(ev);
    });
}

function filterByEmployeur(val) {
    const urlParams = new URLSearchParams(window.location.search);
    if (val) {
        urlParams.set('employeur_id', val);
    } else {
        urlParams.delete('employeur_id');
    }
    window.location.search = urlParams.toString();
}
</script>

<style>
.grade-input {
    border-radius: 10px !important;
    border: 1px solid #cbd5e1;
    font-family: 'Outfit', monospace;
    font-weight: 700;
    font-size: 0.92rem;
    width: 80px;
    transition: all 0.2s;
}
.grade-input:focus {
    border-color: #0284c7 !important;
    box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.1) !important;
}
.grades-input-table th, .grades-input-table td {
    vertical-align: middle;
    padding: 0.65rem 0.8rem;
}
</style>

@endsection
