@extends('layouts.main')
@section('title', 'استخراج حسابات ورموز ولوج المؤسسات - SGFEP')
@section('content')
<style>
    .credentials-card {
        border-radius: 16px;
        border: none;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        background: #ffffff;
        transition: all 0.3s ease;
    }
    .wilaya-card {
        border: 1px solid rgba(0,0,0,0.06);
        border-radius: 16px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        background: #ffffff;
    }
    .wilaya-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 25px rgba(0,0,0,0.08);
        border-color: rgba(0, 56, 112, 0.15);
    }
    .table-credentials th {
        background-color: #f8fafc;
        color: #1e293b;
        font-weight: 700;
        font-size: 0.9rem;
        border-bottom: 2px solid #e2e8f0;
    }
    .table-credentials td {
        vertical-align: middle;
        font-size: 0.9rem;
    }
    .badge-count {
        background-color: rgba(2, 132, 199, 0.1);
        color: #0284c7;
        font-weight: 700;
        padding: 0.35rem 0.75rem;
        border-radius: 20px;
        font-size: 0.85rem;
    }
    .secret-code-item {
        background-color: #f1f5f9;
        border-radius: 8px;
        padding: 4px 8px;
        margin-bottom: 4px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 0.8rem;
    }
</style>

<div class="animate__animated animate__fadeIn">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-light">
        <div>
            <h3 class="fw-bold mb-1" style="color: #003870; font-family:'Cairo', sans-serif;">
                <i class="fa-solid fa-key text-primary me-2" style="color: var(--color-gov-blue) !important;"></i> استخراج بيانات ولوج المؤسسات / Export Credentials
            </h3>
            <p class="text-muted mb-0 small">بوابة مركزية آمنة لاستخراج وتوزيع بيانات تسجيل الدخول والرموز السرية للمؤسسات (الانطلاق الرسمي اليوم)</p>
        </div>
        <div>
            @if($selectedWilayaId > 0 || !empty($searchQuery))
                <a href="{{ request()->url() }}" class="btn btn-outline-primary rounded-pill px-4 fw-bold shadow-sm">
                    <i class="fa-solid fa-arrow-right me-2"></i> العودة لقائمة الولايات
                </a>
            @else
                <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn btn-success rounded-pill px-4 fw-bold shadow-sm d-flex align-items-center">
                    <i class="fa-solid fa-file-excel me-2"></i> تحميل كل الولايات Excel
                </a>
            @endif
        </div>
    </div>

    <!-- Search / Filter Card (Always Visible) -->
    <div class="card credentials-card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ request()->url() }}" class="row g-3">
                <div class="col-md-9">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0 rounded-end-3" placeholder="بحث سريع عن مؤسسة بالاسم، اسم المستخدم، أو الرمز..." value="{{ $searchQuery }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary rounded-3 w-100 fw-bold">
                        <i class="fa-solid fa-search me-1"></i> ابحث الآن
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if($selectedWilayaId === 0 && empty($searchQuery))
        <!-- GRID OF WILAYAS (Fast & Visual) -->
        <h5 class="fw-bold mb-3 text-secondary" style="font-family:'Cairo', sans-serif;">يرجى اختيار الولاية لعرض وتصدير الحسابات:</h5>
        <div class="row g-3">
            @foreach($wilayas as $w)
                @if($w->count_etabs > 0)
                    <div class="col-md-4 col-lg-3">
                        <div class="card wilaya-card shadow-sm h-100">
                            <div class="card-body d-flex flex-column justify-content-between p-3">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="fw-bold mb-0 text-dark" style="font-size:1.05rem;">{{ $w->Nom }}</h6>
                                    <span class="badge-count">{{ $w->count_etabs }} مؤسسة</span>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="{{ request()->url() }}?wilaya_id={{ $w->IDWilayaa }}" class="btn btn-outline-primary btn-sm rounded-pill flex-grow-1 fw-bold">
                                        <i class="fa-solid fa-eye me-1"></i> عرض
                                    </a>
                                    <a href="{{ request()->url() }}?wilaya_id={{ $w->IDWilayaa }}&export=csv" class="btn btn-success btn-sm rounded-pill px-3 fw-bold" title="تحميل Excel">
                                        <i class="fa-solid fa-file-csv"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    @else
        <!-- WILAYA SELECTED - SHOW TABLE -->
        <div class="card credentials-card">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-0">
                <div>
                    <h5 class="card-title fw-bold mb-0 text-dark">
                        @if($selectedWilayaId > 0)
                            بيانات حسابات ولاية: <span class="text-primary">{{ $etabs[0]->wilaya_nom ?? '' }}</span>
                        @else
                            نتائج البحث عن: <span class="text-primary">"{{ $searchQuery }}"</span>
                        @endif
                        ({{ count($etabs) }} مؤسسة)
                    </h5>
                </div>
                <div class="d-flex gap-2">
                    @if($selectedWilayaId > 0)
                        <a href="{{ request()->url() }}?wilaya_id={{ $selectedWilayaId }}&export=csv" class="btn btn-success rounded-pill btn-sm px-3 fw-bold">
                            <i class="fa-solid fa-file-excel me-1"></i> تصدير هذه الولاية Excel
                        </a>
                    @endif
                    <input type="text" id="table-search" class="form-control form-control-sm rounded-pill" placeholder="تصفية سريعة داخل الجدول..." style="max-width: 250px;">
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-credentials mb-0" id="credentials-table">
                        <thead>
                            <tr>
                                <th class="ps-4">الولاية</th>
                                <th>رمز المؤسسة</th>
                                <th>اسم المؤسسة</th>
                                <th>اسم المستخدم</th>
                                <th>كلمة المرور</th>
                                <th class="pe-4">الرموز السرية للحسابات الفرعية والمصالح</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($etabs as $e)
                                <tr>
                                    <td class="ps-4 fw-semibold text-primary">{{ $e->wilaya_nom }}</td>
                                    <td><code class="text-dark fw-bold">{{ $e->etab_code }}</code></td>
                                    <td class="fw-semibold">{{ $e->etab_nom }}</td>
                                    <td><code class="bg-light text-secondary px-2 py-1 rounded">{{ $e->nomUser }}</code></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <code class="text-danger fw-bold" id="pass-{{ $e->IDetablissement }}">{{ $e->MotDePass }}</code>
                                            <button class="btn btn-link p-0 text-muted btn-copy" data-target="pass-{{ $e->IDetablissement }}" title="نسخ">
                                                <i class="fa-solid fa-copy small"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <td class="pe-4">
                                        <div class="d-flex flex-wrap gap-2">
                                            @php
                                                $assocUsers = $natureUsers[$e->nature_id] ?? [];
                                            @endphp
                                            @forelse($assocUsers as $au)
                                                <div class="secret-code-item shadow-sm">
                                                    <span class="badge-role bg-primary text-white" style="font-size:0.7rem;padding:2px 6px;border-radius:4px;">{{ $au['name'] }}</span>
                                                    <code>{{ $au['username'] }}</code>
                                                    <span class="text-muted">|</span>
                                                    <code class="text-success fw-bold" id="code-{{ $e->IDetablissement }}-{{ $au['username'] }}">{{ $au['secret_code'] }}</code>
                                                    <button class="btn btn-link p-0 text-muted btn-copy" data-target="code-{{ $e->IDetablissement }}-{{ $au['username'] }}" title="نسخ">
                                                        <i class="fa-solid fa-copy" style="font-size:0.75rem;"></i>
                                                    </button>
                                                </div>
                                            @empty
                                                <span class="text-muted small">لا توجد حسابات فرعية لهذه الطبيعة.</span>
                                            @endforelse
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fa-solid fa-info-circle fa-2x mb-3 text-secondary"></i>
                                        <p class="mb-0">لم يتم العثور على أي مؤسسات تطابق معايير البحث.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Copy to clipboard functionality
    const copyButtons = document.querySelectorAll('.btn-copy');
    copyButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('data-target');
            const element = document.getElementById(targetId);
            if (element) {
                const text = element.innerText || element.textContent;
                navigator.clipboard.writeText(text).then(() => {
                    const icon = this.querySelector('i');
                    const originalClass = icon.className;
                    icon.className = 'fa-solid fa-check text-success';
                    setTimeout(() => {
                        icon.className = originalClass;
                    }, 1500);
                }).catch(err => {
                    console.error('Failed to copy: ', err);
                });
            }
        });
    });

    // Client-side fast search filter
    const tableSearch = document.getElementById('table-search');
    if (tableSearch) {
        tableSearch.addEventListener('keyup', function() {
            const value = this.value.toLowerCase().trim();
            const rows = document.querySelectorAll('#credentials-table tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(value)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
});
</script>
@endsection
