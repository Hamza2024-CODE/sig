@extends('layouts.main')
@section('title', 'سجل العمليات والتدقيق الأمني — SGFEP')
@section('content')
<div class="animate__animated animate__fadeIn py-4">

    <!-- ── Page Header ─────────────────────────────────────────── -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom">
        <div>
            <h3 class="fw-bold mb-1 text-dark" style="font-family:'Cairo';">
                <i class="fa-solid fa-list-check text-primary me-2"></i>
                سجلات التدقيق والرقابة الأمنية (Audit Logs)
            </h3>
            <p class="text-muted small mb-0">عرض متقدم لكافة العمليات الحساسة ونشاط المستخدمين بالنظام.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap mt-2 mt-md-0">
            <a href="{{ request()->is('sig/*') ? '/sig/admin/security' : '/admin/security' }}" class="btn btn-outline-secondary rounded-pill px-4 fw-bold">
                <i class="fa-solid fa-arrow-right me-1"></i> العودة للمركز
            </a>
            <!-- Direct CSV stream link with query params mapped -->
            <a href="{{ (request()->is('sig/*') ? '/sig/admin/security/logs/export' : '/admin/security/logs/export') . '?' . http_build_query(request()->query()) }}" class="btn btn-success rounded-pill px-4 fw-bold" style="background: linear-gradient(135deg, #10b981, #059669); border: none;">
                <i class="fa-solid fa-file-csv me-1"></i> تصدير البيانات (CSV)
            </a>
        </div>
    </div>

    <!-- ── Filters ──────────────────────────────────────────────── -->
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
        <form method="GET" action="{{ request()->is('sig/*') ? '/sig/admin/security/logs' : '/admin/security/logs' }}" class="row g-3 align-items-end">
            <!-- Search field -->
            <div class="col-md-4">
                <label class="form-label small fw-bold text-muted" style="font-family:'Cairo';">البحث العام</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-search text-primary"></i></span>
                    <input type="text" name="search" class="form-control border-start-0"
                           placeholder="المستخدم، عنوان IP، الوصف..." value="{{ request('search') }}">
                </div>
            </div>

            <!-- Severity dropdown -->
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted" style="font-family:'Cairo';">مستوى الخطورة</label>
                <select name="severity" class="form-select">
                    <option value="">كل المستويات</option>
                    <option value="info" {{ request('severity') === 'info' ? 'selected' : '' }}>INFO</option>
                    <option value="warning" {{ request('severity') === 'warning' ? 'selected' : '' }}>WARNING</option>
                    <option value="danger" {{ request('severity') === 'danger' ? 'selected' : '' }}>DANGER</option>
                    <option value="critical" {{ request('severity') === 'critical' ? 'selected' : '' }}>CRITICAL</option>
                </select>
            </div>

            <!-- Event Type dropdown -->
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted" style="font-family:'Cairo';">نوع الحدث</label>
                <select name="event_type" class="form-select">
                    <option value="">كل الأحداث</option>
                    @foreach($eventTypes as $type)
                        <option value="{{ $type }}" {{ request('event_type') === $type ? 'selected' : '' }}>{{ $type }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Buttons -->
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold flex-grow-1" style="background: linear-gradient(135deg, #2563eb, #1d4ed8); border: none;">
                    <i class="fa-solid fa-filter me-1"></i> تصفية
                </button>
                <a href="{{ request()->is('sig/*') ? '/sig/admin/security/logs' : '/admin/security/logs' }}" class="btn btn-outline-secondary rounded-pill px-3">
                    <i class="fa-solid fa-xmark"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- ── Logs Table ───────────────────────────────────────────── -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white border-0 pt-4 pb-0 px-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold text-dark mb-0" style="font-family:'Cairo';">
                <i class="fa-solid fa-table-list text-primary me-2"></i>
                سجل عمليات الأمن المفصل
            </h5>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 rounded-pill fw-bold" style="font-size:0.8rem;">
                إجمالي المفلتر: {{ $logs->total() }} سجل
            </span>
        </div>
        
        <div class="card-body p-0 mt-3">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-family:'Outfit',sans-serif;">
                    <thead class="table-light text-muted small fw-bold" style="font-family:'Cairo';">
                        <tr>
                            <th class="px-4 py-3">المسؤول / الحساب</th>
                            <th class="py-3">نوع الحدث</th>
                            <th class="py-3">الخطورة</th>
                            <th class="py-3">الوصف</th>
                            <th class="py-3">عنوان IP</th>
                            <th class="py-3">التاريخ والوقت</th>
                            <th class="py-3 text-center">التفاصيل</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($logs->isEmpty())
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <i class="fa-solid fa-inbox text-muted" style="font-size:2.5rem; opacity:0.3;"></i>
                                    <p class="text-muted mt-3 mb-0 fw-bold" style="font-family:'Cairo';">لا توجد سجلات مطابقة للفلاتر</p>
                                    <p class="text-muted small">جرب تعديل خيارات البحث أو التصفية أعلاه</p>
                                </td>
                            </tr>
                        @else
                            @foreach($logs as $log)
                                <?php
                                    $sevColors = [
                                        'info' => ['bg' => 'info', 'text' => 'info'],
                                        'warning' => ['bg' => 'warning', 'text' => 'warning'],
                                        'danger' => ['bg' => 'danger', 'text' => 'danger'],
                                        'critical' => ['bg' => 'danger', 'text' => 'danger']
                                    ];
                                    $sc = $sevColors[$log->severity] ?? ['bg' => 'secondary', 'text' => 'secondary'];
                                ?>
                                <tr>
                                    <td class="px-4">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                                 style="width:34px;height:34px;background:linear-gradient(135deg, #3b82f6, #1e3a8a);color:#fff;font-family:'Cairo';font-weight:700;font-size:0.8rem;">
                                                {{ mb_strtoupper(mb_substr($log->user ? ($log->user->Nom ?? $log->user->NomUser) : 'S', 0, 1)) }}
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark small">{{ $log->user ? ($log->user->Nom ?? $log->user->NomUser) : 'نظامي / تلقائي' }}</div>
                                                <div class="text-muted" style="font-size:0.75rem;">{{ $log->user ? $log->user->NomUser : 'system' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-dark small" style="font-family: 'Outfit';">{{ $log->event_type }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $sc['bg'] }}-subtle text-{{ $sc['text'] }} border border-{{ $sc['bg'] }}-subtle rounded-pill px-3 py-1 fw-bold small">
                                            {{ strtoupper($log->severity) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-muted small" style="font-family:'Cairo';">{{ $log->description }}</span>
                                    </td>
                                    <td>
                                        <span class="text-muted small" style="font-family:'Outfit';">
                                            <i class="fa-solid fa-network-wired text-primary me-1" style="font-size:0.7rem;"></i>
                                            {{ $log->ip_address }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-muted small" style="font-family:'Outfit';">
                                            <i class="fa-regular fa-clock text-muted me-1" style="font-size:0.7rem;"></i>
                                            {{ $log->created_at->format('Y-m-d H:i:s') }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-light rounded-pill px-3" 
                                                onclick="showLogDetail({{ json_encode($log) }})">
                                            <i class="fa-solid fa-eye text-primary"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination footer -->
        @if($logs->hasPages())
            <div class="card-footer bg-white border-0 py-3 px-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
                <span class="text-muted small fw-bold" style="font-family:'Cairo';">
                    عرض {{ $logs->firstItem() }} — {{ $logs->lastItem() }} من إجمالي {{ $logs->total() }} سجل
                </span>
                <div>
                    {{ $logs->links() }}
                </div>
            </div>
        @endif
    </div>

</div>

<!-- Details Modal -->
<div class="modal fade" id="logDetailModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
            <div class="modal-header border-0 text-white p-3" style="background: linear-gradient(135deg, #1e293b, #0f172a);">
                <h5 class="modal-title fw-bold" style="font-family:'Cairo';">
                    <i class="fa-solid fa-circle-info text-primary me-2"></i>
                    تفاصيل حدث التدقيق الأمني والملف الشخصي
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" style="background-color: #f8fafc;">
                <!-- Event info table -->
                <div class="table-responsive rounded-4 border bg-white shadow-sm mb-4">
                    <table class="table table-hover align-middle mb-0 small">
                        <tbody>
                            <tr>
                                <th class="w-25 bg-light fw-bold px-3 py-2" style="font-family:'Cairo';">معرف السجل</th>
                                <td id="modalId" style="font-family:'Outfit';"></td>
                            </tr>
                            <tr>
                                <th class="bg-light fw-bold px-3 py-2" style="font-family:'Cairo';">نوع الحدث</th>
                                <td id="modalType" class="fw-bold text-dark" style="font-family:'Outfit';"></td>
                            </tr>
                            <tr>
                                <th class="bg-light fw-bold px-3 py-2" style="font-family:'Cairo';">الخطورة</th>
                                <td id="modalSeverity"></td>
                            </tr>
                            <tr>
                                <th class="bg-light fw-bold px-3 py-2" style="font-family:'Cairo';">وصف الحدث</th>
                                <td id="modalDesc" class="text-muted" style="font-family:'Cairo';"></td>
                            </tr>
                            <tr>
                                <th class="bg-light fw-bold px-3 py-2" style="font-family:'Cairo';">عنوان IP</th>
                                <td id="modalIp" style="font-family:'Outfit';"></td>
                            </tr>
                            <tr>
                                <th class="bg-light fw-bold px-3 py-2" style="font-family:'Cairo';">بصمة الجهاز / المتصفح</th>
                                <td id="modalUa" class="small text-muted" style="font-family:'Outfit'; font-size: 0.75rem;"></td>
                            </tr>
                            <tr>
                                <th class="bg-light fw-bold px-3 py-2" style="font-family:'Cairo';">الوقت والتاريخ</th>
                                <td id="modalTime" style="font-family:'Outfit';"></td>
                            </tr>
                            <tr id="modalHardwareRow" style="display: none;">
                                <th class="bg-light fw-bold px-3 py-2" style="font-family:'Cairo';">تفاصيل عتاد الجهاز</th>
                                <td class="p-0">
                                    <table class="table table-bordered table-sm mb-0 small border-0">
                                        <tbody>
                                            <tr>
                                                <th class="w-30 bg-light px-2 py-1 text-muted" style="font-family:'Cairo';">نوع الجهاز / النظام</th>
                                                <td id="modalHardwarePlatform" style="font-family:'Outfit';"></td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light px-2 py-1 text-muted" style="font-family:'Cairo';">المعالج (CPU)</th>
                                                <td id="modalHardwareCpu" style="font-family:'Cairo'; font-size: 0.8rem;"></td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light px-2 py-1 text-muted" style="font-family:'Cairo';">الذاكرة (RAM)</th>
                                                <td id="modalHardwareRam" style="font-family:'Outfit'; font-weight: bold;"></td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light px-2 py-1 text-muted" style="font-family:'Cairo';">كرت الشاشة (GPU)</th>
                                                <td id="modalHardwareGpu" class="small text-muted" style="font-family:'Outfit'; font-size: 0.75rem;"></td>
                                            </tr>
                                            <tr>
                                                <th class="bg-light px-2 py-1 text-muted" style="font-family:'Cairo';">دقة الشاشة</th>
                                                <td id="modalHardwareRes" style="font-family:'Outfit';"></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            <tr id="modalMetaRow">
                                <th class="bg-light fw-bold px-3 py-2" style="font-family:'Cairo';">بيانات تقنية إضافية</th>
                                <td>
                                    <pre class="bg-light rounded-3 p-3 small mb-0" id="modalMeta" style="font-family:'Outfit'; direction:ltr; text-align:left; overflow:auto; max-height:150px; font-size: 0.75rem;"></pre>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Rich User Profile Section -->
                <div id="modalUserSection" class="p-3 rounded-4 border bg-white shadow-sm" style="display: none;">
                    <h6 class="fw-bold mb-3 text-dark border-bottom pb-2" style="font-family:'Cairo';">
                        <i class="fa-solid fa-user-shield text-primary me-2"></i>
                        بيانات وملف المستخدم الكاملة (User Audited Profile)
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <table class="table table-bordered table-sm mb-0 small">
                                <tbody>
                                    <tr>
                                        <th class="w-40 bg-light fw-bold px-2 py-1" style="font-family:'Cairo';">الاسم الكامل:</th>
                                        <td id="userFullname" class="text-dark"></td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light fw-bold px-2 py-1" style="font-family:'Cairo';">اسم المستخدم:</th>
                                        <td id="userUsername" class="fw-bold text-primary" style="font-family:'Outfit';"></td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light fw-bold px-2 py-1" style="font-family:'Cairo';">الرمز التعريفي (ID):</th>
                                        <td id="userDbId" class="text-secondary" style="font-family:'Outfit';"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-bordered table-sm mb-0 small">
                                <tbody>
                                    <tr>
                                        <th class="w-40 bg-light fw-bold px-2 py-1" style="font-family:'Cairo';">جدول قاعدة البيانات:</th>
                                        <td><span id="userTableSource" class="badge bg-secondary-subtle text-secondary border px-2 py-0.5 rounded-pill" style="font-family:'Cairo'; font-size:0.75rem;"></span></td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light fw-bold px-2 py-1" style="font-family:'Cairo';">الصفة والدور:</th>
                                        <td id="userRoleType" class="text-dark" style="font-family:'Cairo';"></td>
                                    </tr>
                                    <tr>
                                        <th class="bg-light fw-bold px-2 py-1" style="font-family:'Cairo';">حالة حماية الـ MFA:</th>
                                        <td><span id="userMfaStatus" style="font-size:0.75rem; font-family:'Cairo';"></span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-0 p-3">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal" style="font-family:'Cairo';">إغلاق النافذة</button>
            </div>
        </div>
    </div>
</div>

<script>
function showLogDetail(log) {
    document.getElementById('modalId').textContent = log.id;
    document.getElementById('modalType').textContent = log.event_type;
    document.getElementById('modalSeverity').textContent = log.severity.toUpperCase();
    document.getElementById('modalDesc').textContent = log.description;
    document.getElementById('modalIp').textContent = log.ip_address;
    document.getElementById('modalUa').textContent = log.user_agent;
    document.getElementById('modalTime').textContent = log.created_at;

    // Severity badge style
    const severityEl = document.getElementById('modalSeverity');
    let badgeClass = 'badge bg-secondary-subtle text-secondary border rounded-pill px-3 py-1 fw-bold';
    if (log.severity === 'info') badgeClass = 'badge bg-info-subtle text-info border rounded-pill px-3 py-1 fw-bold';
    else if (log.severity === 'warning') badgeClass = 'badge bg-warning-subtle text-warning border rounded-pill px-3 py-1 fw-bold';
    else if (log.severity === 'danger' || log.severity === 'critical') badgeClass = 'badge bg-danger-subtle text-danger border rounded-pill px-3 py-1 fw-bold';
    severityEl.innerHTML = `<span class="${badgeClass}">${log.severity.toUpperCase()}</span>`;

    // Extract user details (from metadata user_details or fallback to relation)
    const userSection = document.getElementById('modalUserSection');
    let hasUserDetails = false;

    if (log.metadata && log.metadata.user_details) {
        hasUserDetails = true;
        const u = log.metadata.user_details;
        document.getElementById('userFullname').textContent = u.name || 'غير محدد';
        document.getElementById('userUsername').textContent = u.username || 'غير محدد';
        document.getElementById('userDbId').textContent = '#' + (u.id || '');
        document.getElementById('userTableSource').textContent = u.table === 'utilisateur' ? 'جدول المسؤولين (utilisateur)' : (u.table === 'etablissement' ? 'جدول المؤسسات (etablissement)' : 'جدول المؤطرين (encadrement)');
        document.getElementById('userRoleType').textContent = u.role || 'مستعمل المنصة';
        
        const mfaBadge = document.getElementById('userMfaStatus');
        mfaBadge.className = u.mfa === 'مفعل' ? 'badge bg-success-subtle text-success border px-2 py-0.5 rounded-pill' : 'badge bg-danger-subtle text-danger border px-2 py-0.5 rounded-pill';
        mfaBadge.textContent = u.mfa;
    } else if (log.user) {
        hasUserDetails = true;
        document.getElementById('userFullname').textContent = log.user.Nom || 'مسؤول النظام';
        document.getElementById('userUsername').textContent = log.user.NomUser || 'admin';
        document.getElementById('userDbId').textContent = '#' + log.user.IDUtilisateur;
        document.getElementById('userTableSource').textContent = 'جدول المسؤولين (utilisateur)';
        
        let roleName = 'مستعمل المنصة';
        if (log.user.IDNature == 1) roleName = 'مدير النظام (Admin)';
        else if (log.user.IDNature == 2) roleName = 'إدارة مركزية (Central)';
        else if (log.user.IDNature == 4) roleName = 'مديرية التكوين المهني (DFEP)';
        document.getElementById('userRoleType').textContent = roleName;
        
        const mfaBadge = document.getElementById('userMfaStatus');
        mfaBadge.className = log.user.mfa_enabled ? 'badge bg-success-subtle text-success border px-2 py-0.5 rounded-pill' : 'badge bg-danger-subtle text-danger border px-2 py-0.5 rounded-pill';
        mfaBadge.textContent = log.user.mfa_enabled ? 'مفعل' : 'غير مفعل';
    }

    if (hasUserDetails) {
        userSection.style.display = 'block';
    } else {
        userSection.style.display = 'none';
    }

    // Extract hardware details
    const hwRow = document.getElementById('modalHardwareRow');
    if (log.metadata && log.metadata.hardware_specs) {
        const hw = log.metadata.hardware_specs;
        hwRow.style.display = '';
        document.getElementById('modalHardwarePlatform').textContent = hw.platform || 'غير معروف';
        
        const cores = hw.cpu_cores || 'غير معروف';
        document.getElementById('modalHardwareCpu').textContent = cores !== 'غير معروف' ? (cores + ' أنوية منطقية (Logical Cores)') : 'غير معروف';
        
        document.getElementById('modalHardwareRam').textContent = hw.ram_size || 'غير معروف';
        document.getElementById('modalHardwareGpu').textContent = hw.gpu || 'غير معروف';
        document.getElementById('modalHardwareRes').textContent = hw.screen_res || 'غير معروف';
    } else {
        hwRow.style.display = 'none';
    }

    // Clean up metadata printing (omit user_details and hardware_specs payloads to avoid clutter)
    if (log.metadata) {
        const printMeta = { ...log.metadata };
        delete printMeta.user_details;
        delete printMeta.hardware_specs;
        
        if (Object.keys(printMeta).length > 0) {
            document.getElementById('modalMetaRow').style.display = '';
            document.getElementById('modalMeta').textContent = JSON.stringify(printMeta, null, 2);
        } else {
            document.getElementById('modalMetaRow').style.display = 'none';
        }
    } else {
        document.getElementById('modalMetaRow').style.display = 'none';
    }

    new bootstrap.Modal(document.getElementById('logDetailModal')).show();
}
</script>
@endsection
