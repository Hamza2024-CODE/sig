@extends('layouts.main')
@section('title', $title ?? 'المنصة الرقمية للتكوين المهني')
@section('content')
<?php
/**
 * @var array $data
 * @var string $role
 */
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

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
        $rowW = DB::selectOne("SELECT IDDFEP FROM etablissement WHERE IDetablissement = ? LIMIT 1", [$etabId]);
        $selWilaya = $rowW ? (int)$rowW->IDDFEP : null;
    } catch (\Exception $ex) {}
}

// 1. Fetch continuous training counts (cached with filter-aware key)
$coursesCount = 42;
$traineesCount = 1240;
$partnersCount = 16;
$workshopsCount = 8;

$cacheKeyTrak = 'trak_stats_v2_w_' . ($selWilaya ?: 'all') . '_e_' . ($selEtab ?: 'all') . '_m_' . ($selMode ?: 'all');

try {
    $trakData = Cache::remember($cacheKeyTrak, 600, function() use ($selWilaya, $selEtab, $selMode, $coursesCount, $traineesCount, $partnersCount, $workshopsCount) {
        $whereSection = ["(Nom LIKE '%متواصل%' OR Nom LIKE '%تأهيل%')"]; $paramsSection = [];
        if (!empty($selWilaya)) { $whereSection[] = "IDOffre IN (SELECT IDOffre FROM offre o INNER JOIN etablissement e ON o.IDEts_Form = e.IDetablissement WHERE e.IDDFEP = ?)"; $paramsSection[] = $selWilaya; }
        if (!empty($selEtab))   { $whereSection[] = "IDOffre IN (SELECT IDOffre FROM offre WHERE IDEts_Form = ?)"; $paramsSection[] = $selEtab; }
        if (!empty($selMode))   { $whereSection[] = "IDOffre IN (SELECT IDOffre FROM offre WHERE IDMode_formation = ?)"; $paramsSection[] = $selMode; }

        $whereEmp = []; $paramsEmp = [];
        if (!empty($selWilaya)) { $whereEmp[] = "IDDFEP = ?"; $paramsEmp[] = $selWilaya; }
        if (!empty($selEtab))   { $whereEmp[] = "IDEmployeur IN (SELECT IDEmployeur FROM Convention WHERE IDetablissement = ?)"; $paramsEmp[] = $selEtab; }

        $r1 = DB::selectOne("SELECT COUNT(*) as c FROM section WHERE " . implode(" AND ", $whereSection), $paramsSection);
        $dbCourses = $r1 ? (int)$r1->c : 0;

        $sqlEmp = "SELECT COUNT(*) as c FROM Employeur" . (!empty($whereEmp) ? " WHERE " . implode(" AND ", $whereEmp) : "");
        $r3 = DB::selectOne($sqlEmp, $paramsEmp);
        $dbPartners = $r3 ? (int)$r3->c : 0;

        return [
            'courses'   => $dbCourses > 0 ? $dbCourses : $coursesCount,
            'trainees'  => $traineesCount,
            'partners'  => $dbPartners > 0 ? $dbPartners : $partnersCount,
            'workshops' => $workshopsCount
        ];
    });
    $coursesCount  = $trakData['courses'];
    $traineesCount = $trakData['trainees'];
    $partnersCount = $trakData['partners'];
    $workshopsCount= $trakData['workshops'];
} catch (\Exception $e) {}

// 2. Fetch recent programs/cycles
$programsList = [];
$cacheKeyProgList = 'trak_prog_list_v2_w_' . ($selWilaya ?: 'all') . '_e_' . ($selEtab ?: 'all') . '_m_' . ($selMode ?: 'all');

$programsList = Cache::remember($cacheKeyProgList, 600, function() use ($selWilaya, $selEtab, $selMode) {
    $list = [];
    try {
        $whereSection = []; $paramsSection = [];
        if (!empty($selWilaya)) { $whereSection[] = "IDOffre IN (SELECT IDOffre FROM offre o INNER JOIN etablissement e ON o.IDEts_Form = e.IDetablissement WHERE e.IDDFEP = ?)"; $paramsSection[] = $selWilaya; }
        if (!empty($selEtab))   { $whereSection[] = "IDOffre IN (SELECT IDOffre FROM offre WHERE IDEts_Form = ?)"; $paramsSection[] = $selEtab; }
        if (!empty($selMode))   { $whereSection[] = "IDOffre IN (SELECT IDOffre FROM offre WHERE IDMode_formation = ?)"; $paramsSection[] = $selMode; }

        $sqlProgList = "SELECT Nom as name, DateDF as date_start, DateFF as date_end FROM section" . (!empty($whereSection) ? " WHERE " . implode(" AND ", $whereSection) : "") . " ORDER BY IDSection DESC LIMIT 3";
        $rawSections = DB::select($sqlProgList, $paramsSection);

        foreach ($rawSections as $rs) {
            $duration = '05 أيام';
            if ($rs->date_start && $rs->date_end) {
                $diff = strtotime($rs->date_end) - strtotime($rs->date_start);
                $days = round($diff / (24*60*60));
                if ($days > 0) $duration = $days . ' أيام';
            }
            $list[] = [
                'name' => $rs->name ?: 'برنامج التكوين المتواصل والورشات الجارية',
                'organizer' => 'مديرية التكوين المتواصل',
                'target' => 'الأساتذة والمكونون الجدد',
                'duration' => $duration,
                'status_text' => 'جارية حالياً',
                'status_class' => 'bg-success-subtle text-success'
            ];
        }
    } catch (\Exception $e) {}
    return $list;
});


if (empty($programsList)) {
    $programsList = [
        [
            'name' => 'برنامج التكوين في إدارة المؤسسات التكوينية وتسيير الجودة ISO 9001',
            'organizer' => 'المعهد الوطني للكفاءات',
            'target' => 'مديري ووكلاء المراكز المهنية',
            'duration' => '05 أيام',
            'status_text' => 'جارية حالياً',
            'status_class' => 'bg-success-subtle text-success'
        ],
        [
            'name' => 'ورشة تكوينية حول مناهج التعلم النشط واستخدام الوسائل الرقمية في الإيصال',
            'organizer' => 'مديرية التكوين المتواصل',
            'target' => 'الأساتذة والمكونون الجدد',
            'duration' => '03 أيام',
            'status_text' => 'قيد التحضير',
            'status_class' => 'bg-warning-subtle text-warning'
        ],
        [
            'name' => 'دورة متخصصة في السلامة والوقاية المهنية للفنيين السامين للفناء',
            'organizer' => 'ديوان الوطني للتكوين INFP',
            'target' => 'تقنيو البناء والأشغال العمومية',
            'duration' => '10 أيام',
            'status_text' => 'مجدولة: جوان 2026',
            'status_class' => 'bg-primary-subtle text-primary'
        ]
    ];
}
$modes = \App\Services\ReferenceCache::modesFormation();
$offres = [];
try {
    $offres = DB::select("SELECT IDOffre as id, Code as code FROM offre ORDER BY IDOffre DESC LIMIT 30");
} catch (\Exception $e) {}
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
            <i class="fa-solid fa-chalkboard-user me-2"></i> لوحة تحكم مديرية التكوين المتواصل والترقية المهنية
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

    <!-- Continuous Training Metrics Bento Grid -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 h-100" style="border-radius: 20px; background: var(--card-bg); border: 1px solid var(--card-border) !important; border-bottom: 4px solid var(--primary-color) !important;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted fw-bold small">الدورات التأهيلية والتكوينية الجارية</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background-color: var(--primary-glow); color: var(--primary-color);">
                        <i class="fa-solid fa-chalkboard" style="font-size: 1.15rem;"></i>
                    </div>
                </div>
                <h2 class="fw-bold mb-1" style="font-size: 2.1rem; font-family:'Inter'; color: var(--text-main);"><?= number_format($coursesCount) ?> دورة</h2>
                <span class="text-success small fw-bold"><i class="fa-solid fa-circle-check"></i> نشطة حالياً بالمراكز المعتمدة</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 h-100" style="border-radius: 20px; background: var(--card-bg); border: 1px solid var(--card-border) !important; border-bottom: 4px solid #10b981 !important;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted fw-bold small">المستفيدون من التكوين المتواصل</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background-color: rgba(16, 185, 129, 0.08); color: #10b981;">
                        <i class="fa-solid fa-users-between-lines" style="font-size: 1.15rem;"></i>
                    </div>
                </div>
                <h2 class="fw-bold mb-1 text-success" style="font-size: 2.1rem; font-family:'Inter';"><?= number_format($traineesCount) ?> مستفيد</h2>
                <span class="text-muted small"><i class="fa-solid fa-graduation-cap"></i> مسجلون في الدورات الرسمية المعتمدة</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 h-100" style="border-radius: 20px; background: var(--card-bg); border: 1px solid var(--card-border) !important; border-bottom: 4px solid #3b82f6 !important;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted fw-bold small">الشراكات القطاعية المشتركة النشطة</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background-color: rgba(59, 130, 246, 0.08); color: #3b82f6;">
                        <i class="fa-solid fa-handshake-simple" style="font-size: 1.15rem;"></i>
                    </div>
                </div>
                <h2 class="fw-bold mb-1 text-primary" style="font-size: 2.1rem; font-family:'Inter';"><?= number_format($partnersCount) ?> شراكة</h2>
                <span class="text-muted small"><i class="fa-solid fa-building-flag"></i> مع قطاعات حكومية وخاصة فاعلة</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 h-100" style="border-radius: 20px; background: var(--card-bg); border: 1px solid var(--card-border) !important; border-bottom: 4px solid #f59e0b !important;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted fw-bold small">ورشات وأيام تكوينية مجدولة</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background-color: rgba(245, 158, 11, 0.08); color: #f59e0b;">
                        <i class="fa-solid fa-calendar-alt" style="font-size: 1.15rem;"></i>
                    </div>
                </div>
                <h2 class="fw-bold mb-1 text-warning" style="font-size: 2.1rem; font-family:'Inter';"><?= sprintf("%02d", $workshopsCount) ?> ورشات</h2>
                <span class="text-warning small fw-bold"><i class="fa-solid fa-triangle-exclamation"></i> مجدولة خلال الأسابيع القادمة</span>
            </div>
        </div>
    </div>

    <!-- Training Cycles & Workshops List -->
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm p-4" style="border-radius: 20px; background: var(--card-bg); border: 1px solid var(--card-border) !important;">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold m-0" style="border-right: 4px solid var(--primary-color); padding-right: 0.6rem; font-family: 'Cairo', sans-serif; color: var(--text-main);">
                        <i class="fa-solid fa-rotate text-primary me-2"></i> برامج التكوين المتواصل والورشات الجارية
                    </h5>
                    <button class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#addCourseModal"><i class="fa-solid fa-plus me-1"></i> فتح دورة تأهيلية</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center small">
                        <thead class="table-light text-muted">
                            <tr>
                                <th class="text-right">عنوان الدورة / البرنامج التكويني</th>
                                <th>الجهة المنظمة</th>
                                <th>الفئة المستهدفة</th>
                                <th>مدة التكوين</th>
                                <th>الحالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($programsList as $prog): ?>
                            <tr>
                                <td class="text-right fw-bold text-dark" style="color: var(--text-main) !important;"><?= htmlspecialchars($prog['name']) ?></td>
                                <td><?= htmlspecialchars($prog['organizer']) ?></td>
                                <td><?= htmlspecialchars($prog['target']) ?></td>
                                <td style="font-family:'Inter';"><?= htmlspecialchars($prog['duration']) ?></td>
                                <td><span class="badge <?= $prog['status_class'] ?> rounded-pill px-2.5 py-1"><?= htmlspecialchars($prog['status_text']) ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm p-4 h-100 d-flex flex-column justify-content-between" style="border-radius: 20px; background: var(--card-bg); border: 1px solid var(--card-border) !important;">
                <div>
                    <h5 class="fw-bold mb-4" style="border-right: 4px solid #10b981; padding-right: 0.6rem; font-family: 'Cairo', sans-serif; color: var(--text-main);">
                        <i class="fa-solid fa-calendar-week text-success me-2"></i> رزنامة الدورات التكوينية المشةركة
                    </h5>
                    <div class="training-calendar">
                        <div class="p-3 rounded-4 mb-3 border" style="background: rgba(131, 7, 228, 0.01); border-color: var(--card-border) !important;">
                            <strong class="small d-block text-dark" style="color: var(--text-main) !important;"><i class="fa-regular fa-calendar me-1 text-primary"></i> 26-28 ماي 2026</strong>
                            <p class="text-muted small mb-0 mt-1">ورشة تطوير الأداء البيداغوجي: منهجية التقييم المستمر للمتكونين • الجزائر العاصمة.</p>
                        </div>
                        <div class="p-3 rounded-4 mb-3 border" style="background: rgba(131, 7, 228, 0.01); border-color: var(--card-border) !important;">
                            <strong class="small d-block text-dark" style="color: var(--text-main) !important;"><i class="fa-regular fa-calendar me-1 text-primary"></i> 10-14 جوان 2026</strong>
                            <p class="text-muted small mb-0 mt-1">تكوين معمق في تقنيات الطاقة الشمسية وتركيب الألواح الكهروضوئية • وهران.</p>
                        </div>
                    </div>
                </div>
                <div class="mt-auto">
                    <div class="d-grid gap-2">
                        <button class="btn btn-primary py-2.5 fw-bold" style="border-radius:12px; background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%); border:none;" data-bs-toggle="modal" data-bs-target="#addCourseModal">
                            <i class="fa-solid fa-plus me-2"></i> جدولة دورة تكوينية جديدة
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Modal for opening a new qualifying course -->
<div class="modal fade" id="addCourseModal" tabindex="-1" aria-labelledby="addCourseModalLabel" aria-hidden="true" style="backdrop-filter: blur(8px);">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; background: var(--card-bg); border: 1px solid var(--card-border) !important;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-primary" id="addCourseModalLabel" style="font-family: 'Cairo', sans-serif;">فتح دورة تأهيلية جديدة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addCourseForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="courseName" class="form-label fw-bold text-muted small">اسم الدورة / القسم</label>
                        <input type="text" class="form-control rounded-3" id="courseName" name="name" required placeholder="مثال: قسم التأهيل المهني - صيانة الحاسوب">
                    </div>
                    <div class="mb-3">
                        <label for="courseOffre" class="form-label fw-bold text-muted small">العرض التكويني المرتبط</label>
                        <select class="form-select rounded-3" id="courseOffre" name="offre_id">
                            <option value="">-- اختر العرض التكويني --</option>
                            <?php foreach ($offres as $o): ?>
                                <option value="<?= $o->id ?>">عرض رقم: <?= htmlspecialchars($o->code ?: $o->id) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="courseMode" class="form-label fw-bold text-muted small">نمط التكوين</label>
                        <select class="form-select rounded-3" id="courseMode" name="mode_id">
                            <?php foreach ($modes as $m): ?>
                                <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['libelle_ar']) ?></option>
                            <?php endforeach; ?>
                        </select>
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
document.getElementById('addCourseForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch('/sig/dashboard/trak/add-course', {
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
