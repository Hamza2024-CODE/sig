@extends('layouts.main')
@section('title', $title ?? 'المنصة الرقمية للتكوين المهني')
@section('content')
<?php
/**
 * @var string $title
 * @var \Illuminate\Pagination\LengthAwarePaginator $progressData
 * @var array $wilayas
 * @var array $etablissements
 * @var array $years
 * @var string $role_code
 * @var int|null $dfep_id
 * @var int|null $selected_wilaya
 * @var int|null $selected_etab
 * @var int|null $selected_year
 * @var int|null $selected_semestre
 */
?>
<div class="animate__animated animate__fadeIn">
    <!-- Breadcrumbs -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1" style="font-family:'Cairo';">
                <i class="fa-solid fa-chart-line text-primary me-2"></i> <?= htmlspecialchars($title) ?>
            </h4>
            <p class="text-muted small mb-0">متابعة نسب إنجاز رصد علامات الامتحانات والتقييمات المستمرة للأساتذة والمكونين.</p>
        </div>
        <a href="/dashboard/grades" class="btn btn-outline-secondary rounded-pill px-4 fw-bold">
            <i class="fa-solid fa-arrow-right me-1"></i> العودة للوحة البيداغوجية
        </a>
    </div>

    <!-- Stats summary cards -->
    <?php
    $totalModules = count($progressData);
    $completedModules = 0;
    $totalStudentsAcross = 0;
    $totalGradedAcross = 0;
    foreach ($progressData as $row) {
        $totalStudentsAcross += $row['total_students'];
        $totalGradedAcross += $row['graded_students'];
        if ($row['total_students'] > 0 && $row['graded_students'] >= $row['total_students']) {
            $completedModules++;
        }
    }
    $overallPercent = $totalStudentsAcross > 0 ? round(($totalGradedAcross / $totalStudentsAcross) * 100, 1) : 0;
    ?>
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3 bg-white" style="border-radius:16px; border-right:4px solid var(--primary-color) !important;">
                <span class="text-muted small fw-bold d-block mb-1">إجمالي الأفواج والمواد</span>
                <h3 class="fw-bold text-dark mb-0" style="font-family:'Outfit';"><?= $totalModules ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3 bg-white" style="border-radius:16px; border-right:4px solid var(--green) !important;">
                <span class="text-muted small fw-bold d-block mb-1">المواد المكتملة الرصد</span>
                <h3 class="fw-bold text-success mb-0" style="font-family:'Outfit';"><?= $completedModules ?> <span class="fs-6 text-muted">/ <?= $totalModules ?></span></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3 bg-white" style="border-radius:16px; border-right:4px solid var(--electric) !important;">
                <span class="text-muted small fw-bold d-block mb-1">إجمالي علامات الطلبة المرصودة</span>
                <h3 class="fw-bold text-primary mb-0" style="font-family:'Outfit';"><?= $totalGradedAcross ?> <span class="fs-6 text-muted">/ <?= $totalStudentsAcross ?></span></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-3 bg-white" style="border-radius:16px; border-right:4px solid var(--purple) !important;">
                <span class="text-muted small fw-bold d-block mb-1">نسبة الإنجاز الإجمالية</span>
                <h3 class="fw-bold text-purple mb-0" style="font-family:'Outfit';"><?= $overallPercent ?>%</h3>
            </div>
        </div>
    </div>

    <!-- Progress Table Card -->
    <div class="card border-0 shadow-sm p-4 bg-white mb-4" style="border-radius:20px;">
        <div class="mb-4 bg-light p-3.5 rounded-4 no-print" style="background: rgba(248, 250, 252, 0.8) !important; border: 1px solid #e2e8f0;">
            <?php 
            $isAdmin = in_array($role_code, ['admin', 'central', 'high_admin', 'secretaire_general', 'ministre']);
            $isDfep = ($role_code === 'dfep');
            
            if ($isAdmin) {
                $searchCol = 'col-md-3';
                $wilayaCol = 'col-md-2';
                $etabCol   = 'col-md-3';
                $yearCol   = 'col-md-2';
                $semCol    = 'col-md-1';
                $btnCol    = 'col-md-1';
            } elseif ($isDfep) {
                $searchCol = 'col-md-4';
                $wilayaCol = '';
                $etabCol   = 'col-md-4';
                $yearCol   = 'col-md-2';
                $semCol    = 'col-md-1';
                $btnCol    = 'col-md-1';
            } else {
                $searchCol = 'col-md-6';
                $wilayaCol = '';
                $etabCol   = '';
                $yearCol   = 'col-md-3';
                $semCol    = 'col-md-1';
                $btnCol    = 'col-md-2';
            }
            ?>
            <form method="GET" action="/dashboard/grades/progress" class="row g-3 align-items-end">
                <div class="col-12 <?= $searchCol ?>">
                    <label class="form-label small fw-bold text-muted mb-1"><i class="fa-solid fa-magnifying-glass me-1"></i>بحث نصي (المادة، الأستاذ...)</label>
                    <input type="text" name="search" class="form-control rounded-pill border-light bg-white" placeholder="بحث مقياس، أستاذ..." value="<?= htmlspecialchars(request('search', '')) ?>" style="font-size:0.85rem; padding: 0.6rem 1rem;">
                </div>
                
                <?php if ($isAdmin): ?>
                <div class="col-12 <?= $wilayaCol ?>">
                    <label class="form-label small fw-bold text-muted mb-1"><i class="fa-solid fa-map-location-dot me-1"></i>المديرية الولائية (Wilaya)</label>
                    <select name="filter_wilaya" id="filter_wilaya" class="form-select rounded-pill border-light bg-white" style="font-size:0.85rem; padding: 0.6rem 1rem;" onchange="this.form.submit()">
                        <option value="">كل الولايات</option>
                        <?php foreach($wilayas as $w): ?>
                            <option value="<?= $w['id'] ?>" <?= $selected_wilaya == $w['id'] ? 'selected' : '' ?>><?= htmlspecialchars($w['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <?php if ($isAdmin || $isDfep): ?>
                <div class="col-12 <?= $etabCol ?>">
                    <label class="form-label small fw-bold text-muted mb-1"><i class="fa-solid fa-hotel me-1"></i>المؤسسة التكوينية</label>
                    <select name="filter_etab" id="filter_etab" class="form-select rounded-pill border-light bg-white" style="font-size:0.85rem; padding: 0.6rem 1rem;" onchange="this.form.submit()">
                        <option value="">كل المؤسسات</option>
                        <?php foreach($etablissements as $e): 
                            if ($role_code === 'dfep' && $dfep_id && $e['IDDFEP'] != $dfep_id) continue;
                        ?>
                            <option value="<?= $e['id'] ?>" <?= $selected_etab == $e['id'] ? 'selected' : '' ?>><?= htmlspecialchars($e['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <!-- Year Filter -->
                <div class="col-12 <?= $yearCol ?>">
                    <label class="form-label small fw-bold text-muted mb-1"><i class="fa-solid fa-calendar-days me-1"></i>السنة (الدورة)</label>
                    <select name="filter_year" class="form-select rounded-pill border-light bg-white" style="font-size:0.85rem; padding: 0.6rem 1rem;" onchange="this.form.submit()">
                        <option value="">كل السنوات</option>
                        <?php foreach($years as $y): ?>
                            <option value="<?= $y ?>" <?= $selected_year == $y ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Semester Filter -->
                <div class="col-12 <?= $semCol ?>">
                    <label class="form-label small fw-bold text-muted mb-1">السداسي</label>
                    <select name="filter_semestre" class="form-select rounded-pill border-light bg-white" style="font-size:0.85rem; padding: 0.6rem 1rem;" onchange="this.form.submit()">
                        <option value="">الكل</option>
                        <?php for($i=1; $i<=6; $i++): ?>
                            <option value="<?= $i ?>" <?= $selected_semestre == $i ? 'selected' : '' ?>><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="col-12 <?= $btnCol ?> d-flex gap-1">
                    <button type="submit" class="btn btn-primary rounded-pill px-3 py-2 fw-bold w-100" style="font-size:0.85rem; height: 38px;" title="بحث"><i class="fa-solid fa-magnifying-glass"></i></button>
                    <a href="/dashboard/grades/progress" class="btn btn-outline-secondary rounded-pill px-3 py-2 fw-bold w-100" style="font-size:0.85rem; height: 38px;" title="إعادة تعيين"><i class="fa-solid fa-rotate-right"></i></a>
                </div>
            </form>
        </div>

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <h5 class="fw-bold text-dark m-0" style="font-family:'Cairo'; border-right:3px solid var(--primary-color); padding-right:0.5rem;">
                مؤشرات تقدم المكونين حسب المقياس والفوج الدراسي
            </h5>
        </div>

        <div class="table-responsive">
            <table class="table align-middle table-hover text-center mb-0" id="progressTable">
                <thead class="table-light text-muted small fw-bold">
                    <tr>
                        <th class="text-right">المؤسسة / Établissement</th>
                        <th>التخصص والشعبة</th>
                        <th>الفوج</th>
                        <th>السداسي</th>
                        <th>المادة / المقياس</th>
                        <th>الأستاذ المكون</th>
                        <th>عدد الطلبة</th>
                        <th>نسبة الرصد</th>
                        <th>خيارات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($progressData)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-folder-open fs-1 mb-3 d-block text-opacity-50"></i>
                                لا توجد بيانات تقدم بيداغوجي مسجلة حالياً.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($progressData as $row): ?>
                            <?php
                            $total = (int)$row['total_students'];
                            $graded = (int)$row['graded_students'];
                            $percent = $total > 0 ? round(($graded / $total) * 100) : 0;
                            
                            $barColorClass = 'bg-danger';
                            if ($percent >= 100) {
                                $barColorClass = 'bg-success';
                            } elseif ($percent >= 50) {
                                $barColorClass = 'bg-primary';
                            } elseif ($percent > 0) {
                                $barColorClass = 'bg-warning';
                            }
                            ?>
                            <tr>
                                <td class="text-right fw-semibold text-dark" style="font-size:0.8rem;"><?= htmlspecialchars($row['etab_nom']) ?></td>
                                <td class="small" style="font-size:0.78rem;"><?= htmlspecialchars($row['specialite_nom']) ?></td>
                                <td><span class="badge bg-light text-primary rounded px-2.5 py-1.5 fw-bold"><?= htmlspecialchars($row['section_nom']) ?></span></td>
                                <td class="fw-bold" style="font-family:'Outfit';"><?= $row['semestre'] ?></td>
                                <td class="fw-bold text-dark" style="font-size:0.82rem;"><?= htmlspecialchars($row['module_nom']) ?></td>
                                <td>
                                    <?php if ($row['teacher_nom']): ?>
                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($row['teacher_nom'] . ' ' . $row['teacher_prenom']) ?></div>
                                    <?php else: ?>
                                        <span class="text-muted small">غير مسند / Non affecté</span>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-semibold" style="font-family:'Outfit';"><?= $graded ?> / <?= $total ?></td>
                                <td style="width:180px;">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height:6px; border-radius:10px;">
                                            <div class="progress-bar <?= $barColorClass ?>" role="progressbar" style="width: <?= $percent ?>%; border-radius:10px;" aria-valuenow="<?= $percent ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <span class="fw-bold small" style="font-family:'Outfit'; min-width:35px;"><?= $percent ?>%</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex gap-1 justify-content-center">
                                        <a href="/dashboard/grades/input?offre_id=<?= \App\Helpers\SecureIdHelper::encrypt($row['offre_id']) ?>&semestre=<?= \App\Helpers\SecureIdHelper::encrypt($row['semestre']) ?>&matiere_id=<?= $row['ssm_id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-2.5" title="معاينة رصد الدرجات">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>
                                        <a href="/dashboard/grades/deliberation?offre_id=<?= \App\Helpers\SecureIdHelper::encrypt($row['offre_id']) ?>&semestre=<?= \App\Helpers\SecureIdHelper::encrypt($row['semestre']) ?>" class="btn btn-sm btn-outline-success rounded-pill px-2.5" title="معاينة محضر المداولات">
                                            <i class="fa-solid fa-stamp"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center mt-4 no-print">
            {{ $progressData->links() }}
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('progressSearch');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const query = this.value.toLowerCase();
            const rows = document.querySelectorAll('#progressTable tbody tr');
            
            rows.forEach(row => {
                if (row.cells.length > 1) {
                    const text = row.textContent.toLowerCase();
                    if (text.includes(query)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                }
            });
        });
    }
});
</script>

@endsection
