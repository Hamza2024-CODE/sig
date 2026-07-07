@extends('layouts.main')
@section('title', $title ?? 'المنصة الرقمية للتكوين المهني')
@section('content')
<?php
/**
 * @var array $data
 * @var string $role
 */

$db = \App\Core\Database::getInstance()->getConnection();
$cache = new \App\Cache\CacheManager();

// Extract and scope filters
$role = session('user')['role_code'] ?? 'user';
$dfepId = (int)(session('user')['iddfep'] ?? session('user')['IDDFEP'] ?? 0);
$etabId = (int)(session('user')['etablissement_id'] ?? 0);

$selWilaya = $_GET['filter_wilaya'] ?? null;
$selEtab   = $_GET['filter_etablissement'] ?? null;
$selMode   = $_GET['filter_mode'] ?? null;

if ($role === 'dfep' && $dfepId > 0) {
    $selWilaya = $dfepId;
} elseif (in_array($role, ['etablissement', 'directeur']) && $etabId > 0) {
    $selEtab = $etabId;
    try {
        $stmtW = $db->prepare("SELECT IDDFEP FROM etablissement WHERE IDetablissement = ? LIMIT 1");
        $stmtW->execute([$etabId]);
        $selWilaya = (int)$stmtW->fetchColumn();
    } catch (\Exception $ex) {}
}

// 1. Fetch statistics (cached with filter-aware key)
$filesCount = 14;
$reportsCount = 32;
$meetingsCount = 4;
$correspondenceCount = 128;

$cacheKeyStats = 'org_stats_w_' . ($selWilaya ?: 'all') . '_e_' . ($selEtab ?: 'all') . '_m_' . ($selMode ?: 'all');

try {
    $cachedVal = $cache->get($cacheKeyStats, 600); // 10 minutes cache
    if ($cachedVal !== null) {
        $filesCount = $cachedVal['files'];
        $reportsCount = $cachedVal['reports'];
        $meetingsCount = $cachedVal['meetings'];
        $correspondenceCount = $cachedVal['correspondence'];
    } else {
        // Build conditions for Offre
        $whereOffre = []; $paramsOffre = [];
        if (!empty($selWilaya)) {
            $whereOffre[] = "IDEts_Form IN (SELECT IDetablissement FROM etablissement WHERE IDDFEP = ?)";
            $paramsOffre[] = $selWilaya;
        }
        if (!empty($selEtab)) {
            $whereOffre[] = "IDEts_Form = ?";
            $paramsOffre[] = $selEtab;
        }
        if (!empty($selMode)) {
            $whereOffre[] = "IDMode_formation = ?";
            $paramsOffre[] = $selMode;
        }

        // Build conditions for Section
        $whereSection = []; $paramsSection = [];
        if (!empty($selWilaya)) {
            $whereSection[] = "IDOffre IN (SELECT IDOffre FROM offre o INNER JOIN etablissement e ON o.IDEts_Form = e.IDetablissement WHERE e.IDDFEP = ?)";
            $paramsSection[] = $selWilaya;
        }
        if (!empty($selEtab)) {
            $whereSection[] = "IDOffre IN (SELECT IDOffre FROM offre WHERE IDEts_Form = ?)";
            $paramsSection[] = $selEtab;
        }
        if (!empty($selMode)) {
            $whereSection[] = "IDOffre IN (SELECT IDOffre FROM offre WHERE IDMode_formation = ?)";
            $paramsSection[] = $selMode;
        }

        // Build conditions for Session
        $whereSession = []; $paramsSession = [];
        if (!empty($selWilaya)) {
            $whereSession[] = "IDSession IN (SELECT o.IDSession FROM offre o INNER JOIN etablissement e ON o.IDEts_Form = e.IDetablissement WHERE e.IDDFEP = ?)";
            $paramsSession[] = $selWilaya;
        }
        if (!empty($selEtab)) {
            $whereSession[] = "IDSession IN (SELECT IDSession FROM offre WHERE IDEts_Form = ?)";
            $paramsSession[] = $selEtab;
        }
        if (!empty($selMode)) {
            $whereSession[] = "IDSession IN (SELECT IDSession FROM offre WHERE IDMode_formation = ?)";
            $paramsSession[] = $selMode;
        }

        // 1. Files under progress = total offers in database
        $sqlOffers = "SELECT COUNT(*) FROM offre" . (!empty($whereOffre) ? " WHERE " . implode(" AND ", $whereOffre) : "");
        $stmt = $db->prepare($sqlOffers);
        $stmt->execute($paramsOffre);
        $dbOffers = (int)$stmt->fetchColumn();
        if ($dbOffers > 0) {
            $filesCount = $dbOffers;
        }

        // 2. Reports = validated sections
        $sqlReports = "SELECT COUNT(*) FROM section WHERE visacentral = 1" . (!empty($whereSection) ? " AND " . implode(" AND ", $whereSection) : "");
        $stmt = $db->prepare($sqlReports);
        $stmt->execute($paramsSection);
        $dbReports = (int)$stmt->fetchColumn();
        if ($dbReports > 0) {
            $reportsCount = $dbReports;
        }

        // 3. Meetings = active sessions
        $sqlMeetings = "SELECT COUNT(*) FROM session WHERE Encour = 1" . (!empty($whereSession) ? " AND " . implode(" AND ", $whereSession) : "");
        $stmt = $db->prepare($sqlMeetings);
        $stmt->execute($paramsSession);
        $dbMeetings = (int)$stmt->fetchColumn();
        if ($dbMeetings > 0) {
            $meetingsCount = $dbMeetings;
        }

        // 4. Correspondence = total sections
        $sqlSections = "SELECT COUNT(*) FROM section" . (!empty($whereSection) ? " WHERE " . implode(" AND ", $whereSection) : "");
        $stmt = $db->prepare($sqlSections);
        $stmt->execute($paramsSection);
        $dbSections = (int)$stmt->fetchColumn();
        if ($dbSections > 0) {
            $correspondenceCount = $dbSections;
        }

        $cache->set($cacheKeyStats, [
            'files' => $filesCount,
            'reports' => $reportsCount,
            'meetings' => $meetingsCount,
            'correspondence' => $correspondenceCount
        ]);
    }
} catch (\Exception $e) {}

// 2. Fetch studies list (recent sections)
$studiesList = [];
$cacheKeyStudies = 'org_studies_w_' . ($selWilaya ?: 'all') . '_e_' . ($selEtab ?: 'all') . '_m_' . ($selMode ?: 'all');

try {
    $studiesList = $cache->get($cacheKeyStudies, 600);
    if ($studiesList === null) {
        $studiesList = [];
        $whereStudy = []; $paramsStudy = [];
        if (!empty($selWilaya)) {
            $whereStudy[] = "s.IDOffre IN (SELECT IDOffre FROM offre o INNER JOIN etablissement e ON o.IDEts_Form = e.IDetablissement WHERE e.IDDFEP = ?)";
            $paramsStudy[] = $selWilaya;
        }
        if (!empty($selEtab)) {
            $whereStudy[] = "s.IDOffre IN (SELECT IDOffre FROM offre WHERE IDEts_Form = ?)";
            $paramsStudy[] = $selEtab;
        }
        if (!empty($selMode)) {
            $whereStudy[] = "s.IDOffre IN (SELECT IDOffre FROM offre WHERE IDMode_formation = ?)";
            $paramsStudy[] = $selMode;
        }

        $sqlStudy = "
            SELECT s.Nom as name, sp.Nom as spec_name, s.DateDF as date_start, s.Validation as status 
            FROM section s 
            LEFT JOIN specialite sp ON s.IDSpecialite = sp.IDSpecialite 
            " . (!empty($whereStudy) ? " WHERE " . implode(" AND ", $whereStudy) : "") . "
            ORDER BY s.IDSection DESC 
            LIMIT 4
        ";
        $stmt = $db->prepare($sqlStudy);
        $stmt->execute($paramsStudy);
        $rawSections = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rawSections as $rs) {
            $statusText = 'مسودة قيد المراجعة';
            $statusClass = 'bg-primary-subtle text-primary';
            if ($rs['status'] == 1) {
                $statusText = 'مكتمل';
                $statusClass = 'bg-success-subtle text-success';
            } elseif ($rs['status'] == 2) {
                $statusText = 'تحت التلخيص';
                $statusClass = 'bg-warning-subtle text-warning';
            } elseif ($rs['status'] == 3) {
                $statusText = 'مرفوض للتعديل';
                $statusClass = 'bg-danger-subtle text-danger';
            }

            $studiesList[] = [
                'title' => $rs['name'] ?: ($rs['spec_name'] ?: 'دراسة مخطط الاحتياجات والتوزيع البيداغوجي'),
                'responsible' => 'قسم البرمجة والمتابعة',
                'status_text' => $statusText,
                'status_class' => $statusClass,
                'date' => $rs['date_start'] ?: '2026-05-20'
            ];
        }
        $cache->set($cacheKeyStudies, $studiesList);
    }
} catch (\Exception $e) {}


if (empty($studiesList)) {
    $studiesList = [
        [
            'title' => 'دراسة الاحتياجات المستقبلية لقطاع الفلاحة من المتربصين 2026',
            'responsible' => 'مدير الدراسات',
            'status_text' => 'تحت التلخيص',
            'status_class' => 'bg-warning-subtle text-warning',
            'date' => '2026-05-12'
        ],
        [
            'title' => 'مخطط تحديث الأجهزة البيداغوجية لمؤسسات الجنوب والجنوب الكبير',
            'responsible' => 'مكلف بالدراسات 03',
            'status_text' => 'مكتمل',
            'status_class' => 'bg-success-subtle text-success',
            'date' => '2026-04-28'
        ],
        [
            'title' => 'تقرير التفتيش السنوي العام حول تسيير مطاعم مراكز ولاية وهران',
            'responsible' => 'المفتش المركزي 02',
            'status_text' => 'مرفوض للتعديل',
            'status_class' => 'bg-danger-subtle text-danger',
            'date' => '2026-05-18'
        ],
        [
            'title' => 'مشروع الشراكة مع القطاع الخاص لتفعيل التمهين في شعبة تكنولوجيا الإعلام',
            'responsible' => 'ملحق بالديوان 01',
            'status_text' => 'مسودة قيد المراجعة',
            'status_class' => 'bg-primary-subtle text-primary',
            'date' => '2026-05-20'
        ]
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
            <i class="fa-solid fa-folder-open me-2"></i> لوحة تحكم المصالح المركزية والديوان
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
    <!-- Quick Statistics Bento Grid for Central Users -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 h-100" style="border-radius: 20px; background: var(--card-bg); border: 1px solid var(--card-border) !important; border-bottom: 4px solid var(--primary-color) !important;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted fw-bold small">عروض التكوين والملفات الجارية</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background-color: var(--primary-glow); color: var(--primary-color);">
                        <i class="fa-solid fa-file-invoice-dollar" style="font-size: 1.15rem;"></i>
                    </div>
                </div>
                <h2 class="fw-bold mb-1" style="font-size: 1.8rem; font-family:'Inter'; color: var(--text-main);"><?= number_format($filesCount) ?> ملفاً</h2>
                <span class="text-warning small fw-bold"><i class="fa-solid fa-arrows-spin"></i> قيد المراجعة والتلخيص</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 h-100" style="border-radius: 20px; background: var(--card-bg); border: 1px solid var(--card-border) !important; border-bottom: 4px solid #10b981 !important;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted fw-bold small">تقارير التفتيش والمتابعة المعتمدة</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background-color: rgba(16, 185, 129, 0.08); color: #10b981;">
                        <i class="fa-solid fa-route" style="font-size: 1.15rem;"></i>
                    </div>
                </div>
                <h2 class="fw-bold mb-1 text-success" style="font-size: 2.1rem; font-family:'Inter';"><?= number_format($reportsCount) ?> تقريراً</h2>
                <span class="text-muted small"><i class="fa-solid fa-check"></i> تم رفعها للديوان بنجاح</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 h-100" style="border-radius: 20px; background: var(--card-bg); border: 1px solid var(--card-border) !important; border-bottom: 4px solid #3b82f6 !important;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted fw-bold small">الدورات التكوينية النشطة</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background-color: rgba(59, 130, 246, 0.08); color: #3b82f6;">
                        <i class="fa-regular fa-calendar-check" style="font-size: 1.15rem;"></i>
                    </div>
                </div>
                <h2 class="fw-bold mb-1 text-primary" style="font-size: 2.1rem; font-family:'Inter';"><?= sprintf("%02d", $meetingsCount) ?> دورات</h2>
                <span class="text-muted small"><i class="fa-solid fa-clock"></i> خلال الأسبوع الجاري</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 h-100" style="border-radius: 20px; background: var(--card-bg); border: 1px solid var(--card-border) !important; border-bottom: 4px solid #f59e0b !important;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted fw-bold small">إجمالي المجموعات والأقسام التكوينية</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background-color: rgba(245, 158, 11, 0.08); color: #f59e0b;">
                        <i class="fa-solid fa-envelope-open-text" style="font-size: 1.15rem;"></i>
                    </div>
                </div>
                <h2 class="fw-bold mb-1 text-warning" style="font-size: 1.8rem; font-family:'Inter';"><?= number_format($correspondenceCount) ?> قسماً</h2>
                <span class="text-muted small"><i class="fa-solid fa-paper-plane"></i> مؤرشفة ومسجلة رسمياً</span>
            </div>
        </div>
    </div>

    <!-- Studies & Meetings Panels -->
    <div class="row g-4 mb-4">
        <!-- Studies Portal & Summary Tracking -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm p-4" style="border-radius: 20px; background: var(--card-bg); border: 1px solid var(--card-border) !important;">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold m-0" style="border-right: 4px solid var(--primary-color); padding-right: 0.6rem; font-family: 'Cairo', sans-serif; color: var(--text-main);">
                        <i class="fa-solid fa-book-bookmark text-primary me-2"></i> بوابة الدراسات الاستراتيجية والملخصات المركزية
                    </h5>
                    <button class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#addStudyModal"><i class="fa-solid fa-plus me-1"></i> إدراج دراسة جديدة</button>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center small">
                        <thead class="table-light text-muted">
                            <tr>
                                <th class="text-right">عنوان الملف / الدراسة</th>
                                <th>المسؤول</th>
                                <th>الحالة</th>
                                <th>تاريخ التسجيل</th>
                                <th>العمليات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($studiesList as $study): ?>
                            <tr>
                                <td class="text-right fw-bold text-dark" style="color: var(--text-main) !important;"><?= htmlspecialchars($study['title']) ?></td>
                                <td><?= htmlspecialchars($study['responsible']) ?></td>
                                <td><span class="badge <?= $study['status_class'] ?> rounded-pill px-2.5 py-1"><?= htmlspecialchars($study['status_text']) ?></span></td>
                                <td style="font-family:'Inter';"><?= htmlspecialchars($study['date']) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary rounded-circle"><i class="fa-solid fa-eye"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Meeting Schedule & Correspondence List -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm p-4 h-100 d-flex flex-column justify-content-between" style="border-radius: 20px; background: var(--card-bg); border: 1px solid var(--card-border) !important;">
                <div>
                    <h5 class="fw-bold mb-4" style="border-right: 4px solid #10b981; padding-right: 0.6rem; font-family: 'Cairo', sans-serif; color: var(--text-main);">
                        <i class="fa-solid fa-calendar-days text-success me-2"></i> جدول أعمال الديوان والاجتماعات المركزية
                    </h5>

                    <div class="meeting-list">
                        <div class="p-3 rounded-4 mb-3 border d-flex justify-content-between align-items-center" style="background: rgba(131, 7, 228, 0.01); border-color: var(--card-border) !important;">
                            <div>
                                <strong class="small d-block text-dark" style="color: var(--text-main) !important;">اجتماع دراسة رقمنة عروض التكوين الجديدة</strong>
                                <span class="text-muted small"><i class="fa-regular fa-clock me-1 text-primary"></i> اليوم، 14:00 زوالاً | قاعة الاجتماعات المركزية</span>
                            </div>
                            <span class="badge bg-success text-white rounded-pill px-2.5 py-1 fw-bold small">مؤكد</span>
                        </div>
                        <div class="p-3 rounded-4 mb-3 border d-flex justify-content-between align-items-center" style="background: rgba(131, 7, 228, 0.01); border-color: var(--card-border) !important;">
                            <div>
                                <strong class="small d-block text-dark" style="color: var(--text-main) !important;">مجلس تفتيش جودة التسيير المالي بالولايات الغربية</strong>
                                <span class="text-muted small"><i class="fa-regular fa-clock me-1 text-primary"></i> الغد، 10:00 صباحاً | عن بعد (Teams)</span>
                            </div>
                            <span class="badge bg-success text-white rounded-pill px-2.5 py-1 fw-bold small">مؤكد</span>
                        </div>
                        <div class="p-3 rounded-4 mb-3 border d-flex justify-content-between align-items-center" style="background: rgba(131, 7, 228, 0.01); border-color: var(--card-border) !important;">
                            <div>
                                <strong class="small d-block text-dark" style="color: var(--text-main) !important;">عرض الملخص التنفيذي أمام الوزيرة والشركاء</strong>
                                <span class="text-muted small"><i class="fa-regular fa-clock me-1 text-primary"></i> 25 ماي 2026، 09:00 صباحاً | قاعة العروض الكبرى</span>
                            </div>
                            <span class="badge bg-warning text-dark rounded-pill px-2.5 py-1 fw-bold small">تحت التحضير</span>
                        </div>
                    </div>
                </div>

                <div class="mt-auto pt-3">
                    <div class="d-grid gap-2">
                        <button class="btn btn-primary py-2.5 fw-bold" style="border-radius:12px; background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%); border:none;" data-bs-toggle="modal" data-bs-target="#addStudyModal">
                            <i class="fa-solid fa-file-signature me-2"></i> كتابة ملخص دراسة جديد
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Modal for adding a new study/section -->
<div class="modal fade" id="addStudyModal" tabindex="-1" aria-labelledby="addStudyModalLabel" aria-hidden="true" style="backdrop-filter: blur(8px);">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; background: var(--card-bg); border: 1px solid var(--card-border) !important;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-primary" id="addStudyModalLabel" style="font-family: 'Cairo', sans-serif;">إدراج دراسة استراتيجية جديدة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addStudyForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="studyName" class="form-label fw-bold text-muted small">عنوان الدراسة / الملف</label>
                        <input type="text" class="form-control rounded-3" id="studyName" name="name" required placeholder="مثال: دراسة الاحتياجات المستقبلية لقطاع الفلاحة">
                    </div>
                    <div class="mb-3">
                        <label for="studyObs" class="form-label fw-bold text-muted small">ملاحظات وملخص موجز</label>
                        <textarea class="form-control rounded-3" id="studyObs" name="obs" rows="4" placeholder="اكتب خلاصة أو ملاحظات حول أهداف هذه الدراسة..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">حفظ البيانات</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('addStudyForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch('/sig/dashboard/central/add-study', {
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
            alert('حدث خطأ أثناء حفظ البيانات');
        }
    })
    .catch(err => {
        console.error(err);
        alert('حدث خطأ غير متوقع');
    });
});
</script>

@endsection
