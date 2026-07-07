@extends('layouts.main')
@section('title', $title ?? 'المنصة الرقمية للتكوين المهني')
@section('content')
<?php
/**
 * @var array $recent_logins
 * @var string $cpu_usage
 * @var string $cpu_status
 * @var string $ram_usage
 * @var string $ram_details
 * @var string $ssd_usage
 * @var string $ssd_details
 * @var string $total_logins
 * @var string $backup_status
 * @var string $backup_details
 * @var string $sync_status
 * @var string $sync_details
 * @var float $success_pct
 * @var float $failed_pct
 */
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
            <i class="fa-solid fa-server me-2"></i> لوحة تحكم مديرية المعلوماتية والإحصاء
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

    <!-- Server Performance Bento Grid -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 h-100" style="border-radius: 20px; background: var(--card-bg); border: 1px solid var(--card-border) !important; border-bottom: 4px solid var(--primary-color) !important;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted fw-bold small">استهلاك وحدة المعالجة المركزية (CPU)</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background-color: var(--primary-glow); color: var(--primary-color);">
                        <i class="fa-solid fa-microchip" style="font-size: 1.15rem;"></i>
                    </div>
                </div>
                <h2 class="fw-bold mb-1" style="font-size: 2.1rem; font-family:'Inter'; color: var(--text-main);">{{ $cpu_usage }}% active</h2>
                <span class="text-success small fw-bold"><i class="fa-solid fa-circle-check"></i> {{ $cpu_status }}</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 h-100" style="border-radius: 20px; background: var(--card-bg); border: 1px solid var(--card-border) !important; border-bottom: 4px solid #10b981 !important;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted fw-bold small">استهلاك ذاكرة الوصول العشوائي (RAM)</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background-color: rgba(16, 185, 129, 0.08); color: #10b981;">
                        <i class="fa-solid fa-memory" style="font-size: 1.15rem;"></i>
                    </div>
                </div>
                <h2 class="fw-bold mb-1 text-success" style="font-size: 2.1rem; font-family:'Inter';">{{ $ram_usage }}% utilized</h2>
                <span class="text-muted small"><i class="fa-solid fa-database"></i> {{ $ram_details }}</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 h-100" style="border-radius: 20px; background: var(--card-bg); border: 1px solid var(--card-border) !important; border-bottom: 4px solid #3b82f6 !important;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted fw-bold small">سعة مساحة التخزين (SSD NVMe)</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background-color: rgba(59, 130, 246, 0.08); color: #3b82f6;">
                        <i class="fa-solid fa-hard-drive" style="font-size: 1.15rem;"></i>
                    </div>
                </div>
                <h2 class="fw-bold mb-1 text-primary" style="font-size: 2.1rem; font-family:'Inter';">{{ $ssd_usage }}% used</h2>
                <span class="text-muted small"><i class="fa-solid fa-server"></i> {{ $ssd_details }}</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 h-100" style="border-radius: 20px; background: var(--card-bg); border: 1px solid var(--card-border) !important; border-bottom: 4px solid #f59e0b !important;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted fw-bold small">إجمالي محاولات الدخول المسجلة</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background-color: rgba(245, 158, 11, 0.08); color: #f59e0b;">
                        <i class="fa-solid fa-shield-halved" style="font-size: 1.15rem;"></i>
                    </div>
                </div>
                <h2 class="fw-bold mb-1 text-warning" style="font-size: 1.75rem; font-family:'Inter';">{{ number_format((float)$total_logins) }}</h2>
                <span class="text-warning small fw-bold"><i class="fa-solid fa-shield-halved"></i> مراقبة وتتبع العمليات مستمرة</span>
            </div>
        </div>
    </div>

    <!-- Interactive Charts Section (Bar & Doughnut) -->
    <div class="row g-4 mb-4">
        <!-- Chart 1: Server resource usage Bar Chart -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm p-4 h-100" style="border-radius: 20px; background: var(--card-bg); border: 1px solid var(--card-border) !important;">
                <h5 class="fw-bold mb-3" style="border-right: 4px solid var(--primary-color); padding-right: 0.6rem; font-family: 'Cairo', sans-serif; color: var(--text-main);">
                    <i class="fa-solid fa-chart-bar text-primary me-2"></i> استهلاك موارد الخادم / Server Resources
                </h5>
                <div style="height: 250px; position: relative;">
                    <canvas id="chart-server-resources"></canvas>
                </div>
            </div>
        </div>

        <!-- Chart 2: Login success vs failed Doughnut Chart -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm p-4 h-100" style="border-radius: 20px; background: var(--card-bg); border: 1px solid var(--card-border) !important;">
                <h5 class="fw-bold mb-3" style="border-right: 4px solid #10b981; padding-right: 0.6rem; font-family: 'Cairo', sans-serif; color: var(--text-main);">
                    <i class="fa-solid fa-chart-pie text-success me-2"></i> مراقبة عمليات الدخول والأمن / Access Audit
                </h5>
                <div style="height: 250px; position: relative;">
                    <canvas id="chart-it-security"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Security logs, API monitoring & Database backups -->
    <div class="row g-4 mb-4">
        <!-- Security & Cyber-attack protection log -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm p-4" style="border-radius: 20px; background: var(--card-bg); border: 1px solid var(--card-border) !important;">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold m-0" style="border-right: 4px solid var(--primary-color); padding-right: 0.6rem; font-family: 'Cairo', sans-serif; color: var(--text-main);">
                        <i class="fa-solid fa-shield-halved text-primary me-2"></i> سجل العمليات ونظام جدار الحماية (Firewall)
                    </h5>
                    <span class="badge bg-danger-subtle text-danger rounded-pill px-3 py-1.5 fw-bold" style="font-size:0.75rem;">جدار حماية WAF نشط</span>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center small">
                        <thead class="table-light text-muted">
                            <tr>
                                <th class="text-right">نوع الحدث الأمني / الجهاز</th>
                                <th>عنوان IP المصدر</th>
                                <th>المستخدم / الحساب</th>
                                <th>الإجراء المتخذ</th>
                                <th>التوقيت</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recent_logins as $log)
                            <tr>
                                <td class="text-right fw-bold text-dark" style="color: var(--text-main) !important;"><i class="{{ $log['icon'] }} me-1"></i> {{ $log['event'] }}</td>
                                <td style="font-family:'Inter';">{{ $log['ip'] }}</td>
                                <td style="font-family:'Inter';">{{ $log['target'] }}</td>
                                <td><span class="badge {{ $log['badge'] }} rounded-pill px-2.5 py-1">{{ $log['action'] }}</span></td>
                                <td style="font-family:'Inter';">{{ $log['time'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Backups status & API actions -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm p-4 h-100 d-flex flex-column justify-content-between" style="border-radius: 20px; background: var(--card-bg); border: 1px solid var(--card-border) !important;">
                <div>
                    <h5 class="fw-bold mb-4" style="border-right: 4px solid #10b981; padding-right: 0.6rem; font-family: 'Cairo', sans-serif; color: var(--text-main);">
                        <i class="fa-solid fa-circle-check text-success me-2"></i> النسخ الاحتياطي والأمن السيبراني
                    </h5>

                    <div class="backups-list">
                        <div class="p-3 rounded-4 mb-3 border d-flex justify-content-between align-items-center" style="background: rgba(131, 7, 228, 0.01); border-color: var(--card-border) !important;">
                            <div>
                                <strong class="small d-block text-dark" style="color: var(--text-main) !important;"><i class="fa-solid fa-database text-primary me-1"></i> نسخة احتياطية يومية مجدولة</strong>
                                <span class="text-muted small">{{ $backup_details }}</span>
                            </div>
                            <span class="badge {{ $backup_status === 'ناجحة' ? 'bg-success' : 'bg-danger' }} text-white rounded-pill px-2.5 py-1 fw-bold small">{{ $backup_status }}</span>
                        </div>
                        <div class="p-3 rounded-4 mb-3 border d-flex justify-content-between align-items-center" style="background: rgba(131, 7, 228, 0.01); border-color: var(--card-border) !important;">
                            <div>
                                <strong class="small d-block text-dark" style="color: var(--text-main) !important;"><i class="fa-solid fa-cloud-arrow-up text-success me-1"></i> مزامنة سحابية خارجية (Offsite)</strong>
                                <span class="text-muted small">{{ $sync_details }}</span>
                            </div>
                            <span class="badge {{ $sync_status === 'ناجحة' ? 'bg-success' : 'bg-danger' }} text-white rounded-pill px-2.5 py-1 fw-bold small">{{ $sync_status }}</span>
                        </div>
                    </div>
                </div>

                <div class="mt-auto">
                    <div class="d-grid gap-2">
                        <button class="btn btn-primary py-2.5 fw-bold" data-bs-toggle="modal" data-bs-target="#addApiKeyModal" style="border-radius:12px; background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%); border:none;">
                            <i class="fa-solid fa-key me-2"></i> توليد وإصدار مفاتيح واجهة البرمجيات APIs
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Server Resource Utilization Bar Chart
    const ctxResources = document.getElementById('chart-server-resources').getContext('2d');
    new Chart(ctxResources, {
        type: 'bar',
        data: {
            labels: ['معالج النظام (CPU)', 'ذاكرة الوصول (RAM)', 'سعة التخزين (SSD)'],
            datasets: [{
                label: 'نسبة الاستهلاك (%)',
                data: [{{ $cpu_usage }}, {{ $ram_usage }}, {{ $ssd_usage }}],
                backgroundColor: ['#3b82f6', '#10b981', '#f59e0b'],
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function(value) { return value + '%'; }
                    }
                },
                x: {
                    ticks: {
                        font: { family: 'Cairo', size: 11 }
                    }
                }
            }
        }
    });

    // 2. Login Security Audit Doughnut Chart
    const ctxSecurity = document.getElementById('chart-it-security').getContext('2d');
    new Chart(ctxSecurity, {
        type: 'doughnut',
        data: {
            labels: ['تسجيل دخول ناجح', 'محاولات محجوبة/مرفوضة'],
            datasets: [{
                data: [{{ $success_pct }}, {{ $failed_pct }}],
                backgroundColor: ['#28a745', '#dc3545'],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        font: { family: 'Cairo', size: 11 }
                    }
                }
            }
        }
    });
});
</script>

<!-- Modal for register new API Key / client -->
<div class="modal fade" id="addApiKeyModal" tabindex="-1" aria-labelledby="addApiKeyModalLabel" aria-hidden="true" style="backdrop-filter: blur(8px);">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; background: var(--card-bg); border: 1px solid var(--card-border) !important;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-primary" id="addApiKeyModalLabel" style="font-family: 'Cairo', sans-serif;">توليد مفتاح API جديد</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addApiKeyForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="apiKeyClient" class="form-label fw-bold text-muted small">اسم العميل / التطبيق الشريك</label>
                        <input type="text" class="form-control rounded-3" id="apiKeyClient" name="client_name" required placeholder="مثال: تطبيق وزارة التعليم العالي">
                    </div>
                    <div class="mb-3">
                        <label for="apiKeyVal" class="form-label fw-bold text-muted small">مفتاح API المتولد</label>
                        <div class="input-group">
                            <input type="text" class="form-control rounded-3-start" id="apiKeyVal" name="api_key" required readonly>
                            <button type="button" class="btn btn-outline-secondary" onclick="generateRandomKey()">توليد تلقائي</button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="apiKeyIps" class="form-label fw-bold text-muted small">عناوين IP المسموح بها (اختياري)</label>
                        <input type="text" class="form-control rounded-3" id="apiKeyIps" name="allowed_ips" placeholder="*" value="*">
                        <span class="text-muted small">استخدم * للسماح بجميع العناوين، أو افصل بين العناوين بفاصلة.</span>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">حفظ وتفعيل</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function generateRandomKey() {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    let key = 'sgfep_';
    for (let i = 0; i < 32; i++) {
        key += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    document.getElementById('apiKeyVal').value = key;
}

// Generate key automatically when modal opens
document.getElementById('addApiKeyModal').addEventListener('show.bs.modal', function () {
    generateRandomKey();
});

document.getElementById('addApiKeyForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch('/sig/dashboard/it/add-api-key', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(data.message || 'تم التوليد بنجاح');
            location.reload();
        } else {
            alert('حدث خطأ أثناء حفظ مفتاح الـ API');
        }
    })
    .catch(err => {
        console.error(err);
        alert('حدث خطأ غير متوقع');
    });
});
</script>
@endsection
