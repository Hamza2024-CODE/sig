@extends('layouts.main')
@section('title', 'إدارة فترات رصد النقاط البيداغوجية')
@section('content')

<?php
$success = session('success') ?? (isset($_SESSION) ? ($_SESSION['success'] ?? null) : null);
$error   = session('error') ?? (isset($_SESSION) ? ($_SESSION['error'] ?? null) : null);
if (isset($_SESSION)) {
    unset($_SESSION['success'], $_SESSION['error']);
}
?>

<div style="background:linear-gradient(135deg,#0d9488 0%,#0f766e 100%);padding:2.2rem 2rem 2rem;color:#fff;border-radius:0 0 24px 24px;margin-bottom:1.5rem;">
    <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
        <div>
            <h3 class="fw-bold mb-1"><i class="fa-solid fa-clock-rotate-left me-2"></i>إدارة فترات ونوافذ رصد النقاط</h3>
            <p class="mb-0 opacity-75 small">قم بتحديد تواريخ البداية والنهاية للسماح للمؤسسات والأستاذة بإدخال أو تعديل علامات المتربصين.</p>
        </div>
        <a href="/dashboard/grades" class="btn btn-light px-4 py-2 fw-bold shadow-sm text-decoration-none rounded-3" style="color:#0f766e;font-size:.88rem;">
            <i class="fa-solid fa-arrow-right-to-bracket fa-rotate-180 me-1"></i> العودة لدفتر العلامات
        </a>
    </div>
</div>

<div class="container-fluid px-4">
    <?php if ($success): ?>
    <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4"><i class="fa-solid fa-circle-check me-2"></i> <?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-4"><i class="fa-solid fa-triangle-exclamation me-2"></i> <?= $error ?></div>
    <?php endif; ?>

    <div class="row g-4">
        {{-- Create Window Form --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-2">
                    <h5 class="fw-bold text-teal mb-0"><i class="fa-solid fa-calendar-plus me-1 text-teal"></i>فتح فترة رصد جديدة</h5>
                </div>
                <div class="card-body px-4 pb-4">
                    <form method="POST" action="/dashboard/grades/windows/store">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                        {{-- label --}}
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">اسم الفترة (مثال: رصد نقاط السداسي الأول دورة فيفري)</label>
                            <input type="text" name="label" class="form-control border-0 bg-light rounded-3 fw-semibold" placeholder="أدخل اسم تعريفي لفترة الرصد..." required>
                        </div>

                        {{-- semestre --}}
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">السداسي المعني</label>
                            <select name="semestre" class="form-select border-0 bg-light rounded-3 fw-semibold">
                                <option value="">-- كل السداسيات --</option>
                                <option value="1">السداسي الأول (S1)</option>
                                <option value="2">السداسي الثاني (S2)</option>
                                <option value="3">السداسي الثالث (S3)</option>
                                <option value="4">السداسي الرابع (S4)</option>
                                 <option value="5">السداسي الخامس (S5)</option>
                            </select>
                        </div>

                        {{-- scope_type --}}
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">نطاق الفتح (Scope)</label>
                            <select name="scope_type" id="scopeTypeSelect" class="form-select border-0 bg-light rounded-3 fw-semibold" onchange="toggleScopeInputs(this.value)">
                                <option value="global">عام (كل الولايات والمؤسسات) 🌍</option>
                                <option value="wilaya">ولاية محددة فقط 📍</option>
                                <option value="etablissement">مؤسسة معينة فقط 🏫</option>
                            </select>
                        </div>

                        {{-- wilaya scope input --}}
                        <div class="mb-3" id="wilayaScopeContainer" style="display:none;">
                            <label class="form-label fw-bold small text-muted">اختر الولاية المعنية</label>
                            <select name="wilaya_id" class="form-select border-0 bg-light rounded-3 fw-semibold">
                                <option value="">-- اختر الولاية --</option>
                                <?php foreach ($wilayas as $w): ?>
                                <option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        {{-- etab scope input --}}
                        <div class="mb-3" id="etabScopeContainer" style="display:none;">
                            <label class="form-label fw-bold small text-muted">اختر المؤسسة المعنية</label>
                            <select name="etablissement_id" class="form-select border-0 bg-light rounded-3 fw-semibold select2">
                                <option value="">-- اختر المؤسسة --</option>
                                <?php foreach ($etablissements as $e): ?>
                                <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        {{-- dates --}}
                        <div class="row g-2 mb-4">
                            <div class="col-6">
                                <label class="form-label fw-bold small text-muted">تاريخ الفتح</label>
                                <input type="datetime-local" name="date_ouverture" class="form-control border-0 bg-light rounded-3" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-bold small text-muted">تاريخ الإغلاق</label>
                                <input type="datetime-local" name="date_cloture" class="form-control border-0 bg-light rounded-3" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-teal w-100 fw-bold py-2 rounded-3 text-white shadow-sm" style="background:#0f766e;border:none;">
                            <i class="fa-solid fa-floppy-disk me-1"></i> حفظ وتفعيل فترة الرصد
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Existing Windows List --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-list-check me-1 text-teal"></i>فترات الرصد الحالية والمسجلة</h5>
                    <span class="badge bg-teal-subtle text-teal fw-bold" style="color:#0f766e;background:#e0f2fe;"><?= count($windows) ?> فترة مسجلة</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size:.9rem;">
                            <thead class="table-light">
                                <tr>
                                    <th class="px-4">اسم فترة الرصد</th>
                                    <th>النطاق</th>
                                    <th>السداسي</th>
                                    <th>تاريخ ووقت الفتح</th>
                                    <th>تاريخ ووقت الإغلاق</th>
                                    <th>الحالة</th>
                                    <th class="text-center px-4">إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($windows)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="fa-solid fa-clock d-block mb-3" style="font-size:3rem;opacity:.35;"></i>
                                        لا توجد فترات رصد معرفة حالياً. جميع الرصد للمؤسسات مغلق حتى تقوم بفتح فترة جديدة.
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($windows as $w):
                                        $now = time();
                                        $open = strtotime($w['date_ouverture']);
                                        $close = strtotime($w['date_cloture']);
                                        $isActive = ($now >= $open && $now <= $close);
                                        $isPast = ($now > $close);
                                        
                                        $scopeLabel = 'عام 🌍';
                                        if ($w['scope_type'] === 'wilaya') {
                                            $scopeLabel = 'ولاية 📍';
                                        } elseif ($w['scope_type'] === 'etablissement') {
                                            $scopeLabel = 'مؤسسة 🏫';
                                        }
                                    ?>
                                    <tr>
                                        <td class="px-4 fw-bold text-dark">
                                            <?= htmlspecialchars($w['label']) ?>
                                            <div class="small text-muted fw-normal mt-1" style="font-size:.78rem;">بواسطة: <?= htmlspecialchars($w['creator_name'] ?? 'المدير') ?></div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark fw-bold border rounded-pill px-2.5 py-1">
                                                <?= $scopeLabel ?>
                                            </span>
                                        </td>
                                        <td class="fw-bold" style="font-family:'Outfit';">
                                            <?= $w['semestre'] ? 'S' . $w['semestre'] : 'الكل' ?>
                                        </td>
                                        <td class="text-muted" style="font-family:'Outfit';">
                                            <?= date('Y-m-d H:i', $open) ?>
                                        </td>
                                        <td class="text-muted" style="font-family:'Outfit';">
                                            <?= date('Y-m-d H:i', $close) ?>
                                        </td>
                                        <td>
                                            <?php if ($isActive): ?>
                                                <span class="badge bg-success text-white fw-bold px-2.5 py-1 rounded-pill"><i class="fa-solid fa-circle-dot me-1 small"></i>مفتوح ونشط</span>
                                            <?php elseif ($isPast): ?>
                                                <span class="badge bg-secondary text-white fw-bold px-2.5 py-1 rounded-pill">مغلق (منتهي)</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark fw-bold px-2.5 py-1 rounded-pill">مجدول قادماً</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center px-4">
                                            <form method="POST" action="/dashboard/grades/windows/delete/<?= $w['id'] ?>" onsubmit="return confirm('هل أنت متأكد من حذف فترة الرصد هذه؟ سيؤدي ذلك لإغلاق الرصد فوراً للمؤسسات المتأثرة.');" style="display:inline;">
                                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger border-0 rounded-circle" title="حذف فترة الرصد">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleScopeInputs(val) {
    const wilayaDiv = document.getElementById('wilayaScopeContainer');
    const etabDiv = document.getElementById('etabScopeContainer');
    
    if (val === 'wilaya') {
        wilayaDiv.style.display = '';
        etabDiv.style.display = 'none';
    } else if (val === 'etablissement') {
        wilayaDiv.style.display = 'none';
        etabDiv.style.display = '';
    } else {
        wilayaDiv.style.display = 'none';
        etabDiv.style.display = 'none';
    }
}
</script>

<style>
.text-teal { color: #0f766e !important; }
.badge.bg-teal-subtle { background-color: #f0fdfa !important; color: #0f766e !important; }
</style>

@endsection
