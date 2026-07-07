@extends('layouts.main')
@section('title', 'محرك سير العمل — طلبات الموارد البشرية | SGFEP')

@section('styles')
<style>
/* ── Workflow Index Styles ────────────────────────────────── */
.wf-stat-card {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 1rem 1.25rem;
    border-radius: 12px;
    background: var(--card-bg, var(--bg-glass));
    border: 1px solid var(--border);
    transition: transform .2s;
}
.wf-stat-card:hover { transform: translateY(-2px); }
.wf-stat-icon {
    width: 44px; height: 44px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.15rem;
    flex-shrink: 0;
}
.wf-stat-val { font-size: 1.6rem; font-weight: 800; font-family: 'Outfit'; line-height: 1; }
.wf-stat-lbl { font-size: .75rem; color: var(--tx-3); font-family: 'Cairo'; }

.filter-pill {
    display: inline-flex; align-items: center; gap: .4rem;
    padding: .35rem .85rem;
    border-radius: 20px;
    border: 1px solid var(--border);
    background: transparent;
    color: var(--tx-2);
    font-size: .8rem;
    font-weight: 600;
    font-family: 'Cairo';
    text-decoration: none;
    cursor: pointer;
    transition: all .2s;
}
.filter-pill:hover, .filter-pill.active {
    background: var(--electric);
    border-color: var(--electric);
    color: #fff;
}

.wf-table thead th {
    font-size: .75rem;
    font-weight: 800;
    font-family: 'Cairo';
    color: var(--tx-3);
    letter-spacing: .05em;
    text-transform: uppercase;
    padding: .75rem 1rem;
    border-bottom: 1px solid var(--border);
    background: transparent;
}
.wf-table tbody td {
    padding: .9rem 1rem;
    border-bottom: 1px solid var(--border);
    vertical-align: middle;
    font-size: .87rem;
    color: var(--tx-1);
}
.wf-table tbody tr:last-child td { border-bottom: none; }
.wf-table tbody tr:hover td { background: rgba(26,107,204,.03); }

.status-badge {
    display: inline-flex; align-items: center; gap: .35rem;
    padding: .25rem .65rem;
    border-radius: 6px;
    font-size: .74rem;
    font-weight: 800;
    font-family: 'Outfit';
}
.status-pending  { background: rgba(245,158,11,.12); color: #F0A500; }
.status-approved { background: rgba(14,166,110,.12); color: #0EA66E; }
.status-rejected { background: rgba(220,53,69,.12);  color: #dc3545; }
.status-cancelled{ background: rgba(100,116,139,.1); color: var(--tx-3); }

.action-btn {
    display: inline-flex; align-items: center; gap: .35rem;
    padding: .3rem .7rem;
    border-radius: 7px;
    border: 1px solid var(--border);
    background: transparent;
    color: var(--tx-2);
    font-size: .78rem;
    font-weight: 700;
    font-family: 'Cairo';
    text-decoration: none;
    cursor: pointer;
    transition: all .18s;
}
.action-btn:hover { border-color: var(--electric); color: var(--electric); }
.action-btn.approve-btn { border-color: rgba(14,166,110,.4); color: #0EA66E; }
.action-btn.approve-btn:hover { background: #0EA66E; color: #fff; border-color: #0EA66E; }
.action-btn.reject-btn  { border-color: rgba(220,53,69,.4); color: #dc3545; }
.action-btn.reject-btn:hover  { background: #dc3545; color: #fff; border-color: #dc3545; }

/* Modal */
.wf-modal-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(2,9,26,.65); backdrop-filter: blur(6px);
    z-index: 9999; align-items: center; justify-content: center;
}
.wf-modal-overlay.open { display: flex; }
.wf-modal {
    background: var(--bg-surface, #1a1d27);
    border: 1px solid var(--border);
    border-radius: 18px;
    padding: 1.75rem 2rem;
    width: 100%; max-width: 460px;
    animation: wfSlide .22s ease;
}
@keyframes wfSlide { from { transform: translateY(16px); opacity: 0; } to { transform: none; opacity: 1; } }
.wf-modal h5 { font-family: 'Cairo'; font-weight: 800; font-size: 1.05rem; margin-bottom: 1rem; }

.wf-input {
    width: 100%;
    background: var(--bg-input, rgba(255,255,255,.04));
    border: 1px solid var(--border);
    border-radius: 9px;
    color: var(--tx-1);
    padding: .6rem .9rem;
    font-family: 'Cairo';
    font-size: .88rem;
    resize: vertical; min-height: 85px;
}
.wf-input:focus { outline: none; border-color: var(--electric); }
</style>
@endsection

@section('content')
<div class="animate__animated animate__fadeIn">

    {{-- Page Header --}}
    <div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
        <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,var(--electric),#6366f1);display:flex;align-items:center;justify-content:center;">
            <i class="fa-solid fa-diagram-project text-white fs-5"></i>
        </div>
        <div>
            <h1 class="fw-black m-0" style="font-size:1.25rem;font-family:'Cairo';color:var(--tx-1);">محرك سير العمل</h1>
            <p class="text-muted mb-0" style="font-size:.8rem;font-weight:600;">إدارة طلبات الإجازات والترقيات والتحويلات والتكوين</p>
        </div>
        <div class="me-auto d-flex gap-2 align-items-center flex-wrap">
            @if($isAdmin)
            <span class="status-badge" style="background:rgba(26,107,204,.1);color:var(--electric);">
                <i class="fa-solid fa-shield-halved" style="font-size:.65rem;"></i> صلاحية المشرف
            </span>
            @endif
            <a href="{{ route('workflow.create') }}" class="btn btn-primary btn-sm d-flex align-items-center gap-2 fw-bold" style="font-family:'Cairo';border-radius:9px;">
                <i class="fa-solid fa-plus"></i> طلب جديد
            </a>
        </div>
    </div>

    {{-- Flash --}}
    @if(session('success'))
    <div class="alert alert-success border-0 py-2 px-3 mb-3 small fw-bold d-flex align-items-center gap-2" style="font-family:'Cairo';border-radius:10px;">
        <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger border-0 py-2 px-3 mb-3 small fw-bold d-flex align-items-center gap-2" style="font-family:'Cairo';border-radius:10px;">
        <i class="fa-solid fa-triangle-exclamation"></i> {{ session('error') }}
    </div>
    @endif

    {{-- Stats Row --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-2">
            <div class="wf-stat-card">
                <div class="wf-stat-icon" style="background:rgba(26,107,204,.1);color:var(--electric);"><i class="fa-solid fa-list-check"></i></div>
                <div><div class="wf-stat-val">{{ $stats['total'] }}</div><div class="wf-stat-lbl">الإجمالي</div></div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="wf-stat-card">
                <div class="wf-stat-icon" style="background:rgba(240,165,0,.1);color:#F0A500;"><i class="fa-solid fa-hourglass-half"></i></div>
                <div><div class="wf-stat-val" style="color:#F0A500;">{{ $stats['pending'] }}</div><div class="wf-stat-lbl">انتظار</div></div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="wf-stat-card">
                <div class="wf-stat-icon" style="background:rgba(14,166,110,.1);color:#0EA66E;"><i class="fa-solid fa-check-circle"></i></div>
                <div><div class="wf-stat-val" style="color:#0EA66E;">{{ $stats['approved'] }}</div><div class="wf-stat-lbl">مقبولة</div></div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="wf-stat-card">
                <div class="wf-stat-icon" style="background:rgba(220,53,69,.1);color:#dc3545;"><i class="fa-solid fa-times-circle"></i></div>
                <div><div class="wf-stat-val" style="color:#dc3545;">{{ $stats['rejected'] }}</div><div class="wf-stat-lbl">مرفوضة</div></div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="wf-stat-card">
                <div class="wf-stat-icon" style="background:rgba(59,130,246,.1);color:#3b82f6;"><i class="fa-solid fa-umbrella-beach"></i></div>
                <div><div class="wf-stat-val" style="color:#3b82f6;">{{ $stats['conge'] }}</div><div class="wf-stat-lbl">إجازات</div></div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="wf-stat-card">
                <div class="wf-stat-icon" style="background:rgba(139,92,246,.1);color:#8b5cf6;"><i class="fa-solid fa-arrow-trend-up"></i></div>
                <div><div class="wf-stat-val" style="color:#8b5cf6;">{{ $stats['promotion'] }}</div><div class="wf-stat-lbl">ترقيات</div></div>
            </div>
        </div>
    </div>

    {{-- Filters + Table --}}
    <div class="glass-panel p-0 overflow-hidden">
        {{-- Filter Bar --}}
        <div class="d-flex align-items-center gap-2 flex-wrap p-3" style="border-bottom:1px solid var(--border);">
            <a href="?type=&status=" class="filter-pill {{ !request('type') && !request('status') ? 'active' : '' }}"><i class="fa-solid fa-list me-1"></i> الكل</a>
            <a href="?status=pending"  class="filter-pill {{ request('status')==='pending' ? 'active' : '' }}"><i class="fa-solid fa-hourglass-half me-1"></i> انتظار</a>
            <a href="?status=approved" class="filter-pill {{ request('status')==='approved' ? 'active' : '' }}"><i class="fa-solid fa-circle-check me-1"></i> مقبول</a>
            <a href="?status=rejected" class="filter-pill {{ request('status')==='rejected' ? 'active' : '' }}"><i class="fa-solid fa-circle-xmark me-1"></i> مرفوض</a>
            <span style="width:1px;height:20px;background:var(--border);margin:0 .25rem;"></span>
            <a href="?type=conge"     class="filter-pill {{ request('type')==='conge' ? 'active' : '' }}"><i class="fa-solid fa-umbrella-beach me-1"></i> إجازة</a>
            <a href="?type=promotion" class="filter-pill {{ request('type')==='promotion' ? 'active' : '' }}"><i class="fa-solid fa-arrow-trend-up me-1"></i> ترقية</a>
            <a href="?type=transfert" class="filter-pill {{ request('type')==='transfert' ? 'active' : '' }}"><i class="fa-solid fa-shuffle me-1"></i> تحويل</a>
            <a href="?type=formation" class="filter-pill {{ request('type')==='formation' ? 'active' : '' }}"><i class="fa-solid fa-graduation-cap me-1"></i> تكوين</a>
        </div>

        @if($requests->isEmpty())
        <div class="text-center py-5">
            <i class="fa-solid fa-inbox fa-3x mb-3" style="color:var(--tx-3);opacity:.3;"></i>
            <p class="fw-bold mb-1" style="font-family:'Cairo';color:var(--tx-2);">لا توجد طلبات بعد</p>
            <p class="small mb-0" style="color:var(--tx-3);">ابدأ بإنشاء طلب جديد من الزر أعلاه</p>
        </div>
        @else
        <div class="table-responsive">
            <table class="table wf-table mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>نوع الطلب</th>
                        <th>المرسِل</th>
                        <th>الحالة</th>
                        <th>التاريخ</th>
                        @if($isAdmin)<th>المعالج</th>@endif
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($requests as $req)
                    <tr>
                        <td style="font-family:'Outfit';font-size:.8rem;color:var(--tx-3);">#{{ $req->id }}</td>
                        <td>
                            <span class="status-badge" style="background:rgba(26,107,204,.1);color:var(--electric);">
                                {{ \App\Models\WorkflowRequest::typeLabel($req->type) }}
                            </span>
                        </td>
                        <td>
                            <span class="fw-bold" style="font-family:'Cairo';">موظف #{{ $req->employee_id }}</span>
                            @if($req->motif)
                            <div style="font-size:.75rem;color:var(--tx-3);">{{ Str::limit($req->motif, 45) }}</div>
                            @endif
                        </td>
                        <td>
                            <span class="status-badge status-{{ $req->status }}">
                                @if($req->status==='pending') <i class="fa-solid fa-hourglass-half me-1"></i> انتظار
                                @elseif($req->status==='approved') <i class="fa-solid fa-circle-check me-1"></i> مقبول
                                @elseif($req->status==='rejected') <i class="fa-solid fa-circle-xmark me-1"></i> مرفوض
                                @else <i class="fa-solid fa-ban me-1"></i> ملغى @endif
                            </span>
                        </td>
                        <td style="font-size:.8rem;color:var(--tx-3);font-family:'Outfit';">{{ $req->created_at->diffForHumans() }}</td>
                        @if($isAdmin)
                        <td style="font-size:.8rem;color:var(--tx-3);">{{ $req->approved_by ? '#'.$req->approved_by : '—' }}</td>
                        @endif
                        <td>
                            <div class="d-flex gap-1 flex-wrap">
                                <a href="{{ route('workflow.show', $req->id) }}" class="action-btn" title="عرض"><i class="fa-solid fa-eye"></i></a>
                                @if($isAdmin && $req->status==='pending')
                                <button onclick="openDecide({{ $req->id }},'approved')" class="action-btn approve-btn"><i class="fa-solid fa-check"></i></button>
                                <button onclick="openDecide({{ $req->id }},'rejected')" class="action-btn reject-btn"><i class="fa-solid fa-xmark"></i></button>
                                @endif
                                @if(!$isAdmin && $req->status==='pending')
                                <form method="POST" action="{{ route('workflow.cancel', $req->id) }}" onsubmit="return confirm('إلغاء الطلب؟')">@csrf
                                    <button type="submit" class="action-btn" style="color:#F0A500;border-color:rgba(240,165,0,.3);"><i class="fa-solid fa-ban"></i></button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{-- Pagination --}}
        @if($requests->hasPages())
        <div class="d-flex justify-content-center gap-2 p-3 flex-wrap">
            @if(!$requests->onFirstPage())
            <a href="{{ $requests->previousPageUrl() }}" class="filter-pill">‹ السابق</a>
            @endif
            @if($requests->hasMorePages())
            <a href="{{ $requests->nextPageUrl() }}" class="filter-pill">التالي ›</a>
            @endif
        </div>
        @endif
        @endif
    </div>
</div>

{{-- Decide Modal --}}
<div class="wf-modal-overlay" id="decideModal">
    <div class="wf-modal">
        <h5 id="modalTitle"><i class="fa-solid fa-gavel me-2" style="color:var(--electric);"></i> البت في الطلب</h5>
        <form method="POST" id="decideForm">@csrf
            <input type="hidden" name="action" id="decideAction">
            <div class="mb-3">
                <label class="form-label small fw-bold" style="font-family:'Cairo';color:var(--tx-2);">تعليق (اختياري)</label>
                <textarea name="comment" class="wf-input" placeholder="أضف ملاحظة..."></textarea>
            </div>
            <div class="d-flex gap-2 justify-content-end">
                <button type="button" onclick="closeModal()" class="btn btn-sm" style="background:var(--bg-glass);color:var(--tx-2);font-family:'Cairo';border:1px solid var(--border);">إلغاء</button>
                <button type="submit" id="decideSubmitBtn" class="btn btn-success btn-sm fw-bold" style="font-family:'Cairo';">تأكيد</button>
            </div>
        </form>
    </div>
</div>

<script>
function openDecide(id, action) {
    const modal = document.getElementById('decideModal');
    const form  = document.getElementById('decideForm');
    const btn   = document.getElementById('decideSubmitBtn');
    const title = document.getElementById('modalTitle');
    form.action  = `/dashboard/workflow/${id}/decide`;
    document.getElementById('decideAction').value = action;
    if (action === 'approved') {
        title.innerHTML = '<i class="fa-solid fa-circle-check me-2" style="color:#0EA66E;"></i> تأكيد قبول الطلب';
        btn.className = 'btn btn-success btn-sm fw-bold';
        btn.textContent = '✅ قبول';
    } else {
        title.innerHTML = '<i class="fa-solid fa-circle-xmark me-2" style="color:#dc3545;"></i> تأكيد رفض الطلب';
        btn.className = 'btn btn-danger btn-sm fw-bold';
        btn.textContent = '❌ رفض';
    }
    modal.classList.add('open');
}
function closeModal() { document.getElementById('decideModal').classList.remove('open'); }
document.getElementById('decideModal').addEventListener('click', e => { if(e.target===document.getElementById('decideModal')) closeModal(); });
</script>
@endsection
