@extends('layouts.main')
@section('title', "تفاصيل الطلب #{{ $req->id }} | SGFEP")

@section('styles')
<style>
.detail-row {
    display: flex; align-items: flex-start; gap: 1rem;
    padding: .75rem 0;
    border-bottom: 1px solid var(--border);
    font-size: .87rem;
}
.detail-row:last-child { border-bottom: none; }
.detail-row .dr-label { color: var(--tx-3); font-weight: 700; font-family: 'Cairo'; min-width: 140px; flex-shrink: 0; }
.detail-row .dr-val   { color: var(--tx-1); font-weight: 600; font-family: 'Outfit'; }

.status-badge {
    display: inline-flex; align-items: center; gap: .35rem;
    padding: .25rem .65rem; border-radius: 6px;
    font-size: .74rem; font-weight: 800; font-family: 'Outfit';
}
.status-pending  { background: rgba(245,158,11,.12); color: #F0A500; }
.status-approved { background: rgba(14,166,110,.12); color: #0EA66E; }
.status-rejected { background: rgba(220,53,69,.12);  color: #dc3545; }
.status-cancelled{ background: rgba(100,116,139,.1); color: var(--tx-3); }

/* Timeline */
.tl-wrap { padding-right: 1.5rem; position: relative; }
.tl-wrap::before {
    content: ''; position: absolute;
    right: 7px; top: 0; bottom: 0;
    width: 2px; background: var(--border);
}
.tl-item { display: flex; gap: 1rem; margin-bottom: 1.1rem; position: relative; }
.tl-dot {
    width: 16px; height: 16px; border-radius: 50%;
    background: var(--bg-surface); border: 2px solid var(--border);
    flex-shrink: 0; margin-right: -1.5rem; margin-top: 2px;
    display: flex; align-items: center; justify-content: center;
    font-size: .5rem;
}
.tl-dot.approved { background: #0EA66E; border-color: #0EA66E; color: #fff; }
.tl-dot.rejected { background: #dc3545; border-color: #dc3545; color: #fff; }
.tl-dot.pending  { background: #F0A500; border-color: #F0A500; }
.tl-body {
    flex: 1; background: var(--bg-glass);
    border: 1px solid var(--border);
    border-radius: 10px; padding: .75rem 1rem;
}
.tl-body .tl-meta   { font-size: .75rem; color: var(--tx-3); margin-bottom: .25rem; font-family: 'Outfit'; }
.tl-body .tl-action { font-weight: 700; font-size: .87rem; font-family: 'Cairo'; }
.tl-body .tl-cmt    { font-size: .82rem; color: var(--tx-2); margin-top: .25rem; }

/* Decision box */
.decision-box {
    background: linear-gradient(135deg, rgba(26,107,204,.07), rgba(26,107,204,.03));
    border: 1px solid rgba(26,107,204,.18);
    border-radius: 14px;
    padding: 1.5rem;
}
.wf-textarea {
    width: 100%; background: rgba(255,255,255,.04);
    border: 1px solid var(--border); border-radius: 9px;
    color: var(--tx-1); padding: .6rem .9rem;
    font-family: 'Cairo'; font-size: .88rem;
    resize: vertical; min-height: 80px;
}
.wf-textarea:focus { outline: none; border-color: var(--electric); }

.payload-table { width: 100%; }
.payload-table td { padding: .5rem .6rem; border-bottom: 1px solid var(--border); font-size: .86rem; }
.payload-table td:first-child { color: var(--tx-3); font-weight: 700; font-family: 'Cairo'; width: 45%; }
.payload-table td:last-child { color: var(--tx-1); font-weight: 600; font-family: 'Outfit'; }
.payload-table tr:last-child td { border-bottom: none; }
</style>
@endsection

@section('content')
<div class="animate__animated animate__fadeIn">

    {{-- Page Header --}}
    <div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
        <a href="{{ route('workflow.index') }}" class="btn btn-sm" style="background:var(--bg-glass);border:1px solid var(--border);color:var(--tx-2);font-family:'Cairo';border-radius:9px;">
            <i class="fa-solid fa-arrow-right me-1"></i> عودة للقائمة
        </a>
        <div style="width:42px;height:42px;border-radius:12px;background:linear-gradient(135deg,var(--electric),#6366f1);display:flex;align-items:center;justify-content:center;">
            <i class="fa-solid fa-file-lines text-white"></i>
        </div>
        <div>
            <h1 class="fw-black m-0" style="font-size:1.2rem;font-family:'Cairo';color:var(--tx-1);">
                طلب <span style="font-family:'Outfit';">#{{ $req->id }}</span> — {{ \App\Models\WorkflowRequest::typeLabel($req->type) }}
            </h1>
            <p class="mb-0" style="font-size:.78rem;font-family:'Cairo';color:var(--tx-3);">تقدَّم بتاريخ {{ $req->created_at->format('Y-m-d H:i') }}</p>
        </div>
        <div class="me-auto">
            <span class="status-badge status-{{ $req->status }}">
                @if($req->status==='pending')    <i class="fa-solid fa-hourglass-half"></i> قيد الانتظار
                @elseif($req->status==='approved') <i class="fa-solid fa-circle-check"></i> مقبول
                @elseif($req->status==='rejected') <i class="fa-solid fa-circle-xmark"></i> مرفوض
                @else <i class="fa-solid fa-ban"></i> ملغى @endif
            </span>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-lg-8">

            {{-- Basic Info --}}
            <div class="glass-panel p-4 mb-3">
                <h6 class="fw-black mb-3 d-flex align-items-center gap-2" style="font-family:'Cairo';color:var(--tx-1);font-size:.95rem;">
                    <i class="fa-solid fa-circle-info" style="color:var(--electric);"></i> بيانات الطلب
                </h6>
                <div>
                    <div class="detail-row">
                        <span class="dr-label">رقم الطلب</span>
                        <span class="dr-val">#{{ $req->id }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="dr-label">النوع</span>
                        <span class="dr-val">{{ \App\Models\WorkflowRequest::typeLabel($req->type) }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="dr-label">الحالة</span>
                        <span class="dr-val">
                            <span class="status-badge status-{{ $req->status }}">
                                @if($req->status==='pending') <i class="fa-solid fa-hourglass-half"></i> انتظار
                                @elseif($req->status==='approved') <i class="fa-solid fa-circle-check"></i> مقبول
                                @elseif($req->status==='rejected') <i class="fa-solid fa-circle-xmark"></i> مرفوض
                                @else <i class="fa-solid fa-ban"></i> ملغى @endif
                            </span>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="dr-label">تاريخ الإرسال</span>
                        <span class="dr-val">{{ $req->created_at->format('Y-m-d H:i') }}</span>
                    </div>
                    @if($req->approved_at)
                    <div class="detail-row">
                        <span class="dr-label">تاريخ المعالجة</span>
                        <span class="dr-val">{{ $req->approved_at->format('Y-m-d H:i') }}</span>
                    </div>
                    @endif
                    @if($req->motif)
                    <div class="detail-row">
                        <span class="dr-label">السبب / الملاحظة</span>
                        <span class="dr-val" style="font-family:'Cairo';">{{ $req->motif }}</span>
                    </div>
                    @endif
                    @if($req->response_comment)
                    <div class="detail-row">
                        <span class="dr-label">تعليق المشرف</span>
                        <span class="dr-val" style="background:rgba(240,165,0,.07);border:1px solid rgba(240,165,0,.2);border-radius:8px;padding:.4rem .75rem;font-family:'Cairo';color:#F0A500;">
                            <i class="fa-solid fa-comment-dots me-1"></i>{{ $req->response_comment }}
                        </span>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Payload --}}
            @if($req->payload && count($req->payload))
            <div class="glass-panel p-4 mb-3">
                <h6 class="fw-black mb-3 d-flex align-items-center gap-2" style="font-family:'Cairo';color:var(--tx-1);font-size:.95rem;">
                    <i class="fa-solid fa-list-ul" style="color:#F0A500;"></i> تفاصيل الطلب
                </h6>
                <table class="payload-table">
                    @foreach($req->payload as $key => $val)
                    @if($val)
                    <tr>
                        <td>{{ ucfirst(str_replace('_', ' ', $key)) }}</td>
                        <td>{{ $val }}</td>
                    </tr>
                    @endif
                    @endforeach
                </table>
            </div>
            @endif

            {{-- Admin Decision --}}
            @if($isAdmin && $req->status === 'pending')
            <div class="decision-box mb-3">
                <h6 class="fw-black mb-3 d-flex align-items-center gap-2" style="font-family:'Cairo';color:var(--electric);font-size:.95rem;">
                    <i class="fa-solid fa-gavel"></i> البت في هذا الطلب
                </h6>
                <form method="POST" action="{{ route('workflow.decide', $req->id) }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small fw-bold" style="font-family:'Cairo';color:var(--tx-2);">تعليق (اختياري)</label>
                        <textarea name="comment" class="wf-textarea" placeholder="أضف ملاحظة أو تعليقاً..."></textarea>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="submit" name="action" value="approved" class="btn btn-success btn-sm fw-bold" style="font-family:'Cairo';"
                                onclick="return confirm('تأكيد قبول الطلب؟')">
                            <i class="fa-solid fa-circle-check me-1"></i> قبول الطلب
                        </button>
                        <button type="submit" name="action" value="rejected" class="btn btn-danger btn-sm fw-bold" style="font-family:'Cairo';"
                                onclick="return confirm('تأكيد رفض الطلب؟')">
                            <i class="fa-solid fa-circle-xmark me-1"></i> رفض الطلب
                        </button>
                    </div>
                </form>
            </div>
            @endif
        </div>

        <div class="col-12 col-lg-4">
            {{-- Timeline --}}
            <div class="glass-panel p-4">
                <h6 class="fw-black mb-3 d-flex align-items-center gap-2" style="font-family:'Cairo';color:var(--tx-1);font-size:.95rem;">
                    <i class="fa-solid fa-timeline" style="color:#3b82f6;"></i> سجل المراحل
                </h6>
                @if($req->steps->isEmpty())
                <div class="text-center py-3">
                    <i class="fa-solid fa-clock-rotate-left fa-2x mb-2" style="color:var(--tx-3);opacity:.3;"></i>
                    <p class="small mb-0" style="color:var(--tx-3);font-family:'Cairo';">لم تُسجَّل أي خطوات بعد</p>
                </div>
                @else
                <div class="tl-wrap">
                    @foreach($req->steps->sortBy('order') as $step)
                    <div class="tl-item">
                        <div class="tl-dot {{ $step->action ?? 'pending' }}">
                            @if($step->action==='approved') <i class="fa-solid fa-check" style="font-size:.45rem;"></i>
                            @elseif($step->action==='rejected') <i class="fa-solid fa-xmark" style="font-size:.45rem;"></i>
                            @endif
                        </div>
                        <div class="tl-body">
                            <div class="tl-meta">الخطوة {{ $step->order }} · {{ $step->actor_role }} · {{ $step->created_at?->diffForHumans() }}</div>
                            <div class="tl-action">
                                @if($step->action==='approved') <i class="fa-solid fa-circle-check me-1" style="color:#0EA66E;"></i> تمت الموافقة
                                @elseif($step->action==='rejected') <i class="fa-solid fa-circle-xmark me-1" style="color:#dc3545;"></i> تم الرفض
                                @else <i class="fa-solid fa-hourglass-half me-1" style="color:#F0A500;"></i> انتظار المعالجة
                                @endif
                            </div>
                            @if($step->comment)
                            <div class="tl-cmt">{{ $step->comment }}</div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
