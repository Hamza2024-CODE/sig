@extends('layouts.main')
@section('title', "{{ $msg->subject ?: 'رسالة' }} | البريد الداخلي | SGFEP")

@section('styles')
<style>
.priority-badge {
    display: inline-flex; align-items: center; gap: .35rem;
    padding: .25rem .65rem; border-radius: 6px;
    font-size: .74rem; font-weight: 800; font-family: 'Outfit';
}
.p-urgent { background: rgba(220,53,69,.12); color: #dc3545; }
.p-high   { background: rgba(240,165,0,.12);  color: #F0A500; }
.p-normal { background: rgba(14,166,110,.1);  color: #0EA66E; }
.p-low    { background: rgba(100,116,139,.1); color: var(--tx-3); }

.read-badge {
    display: inline-flex; align-items: center; gap: .35rem;
    padding: .25rem .65rem; border-radius: 6px;
    font-size: .74rem; font-weight: 800; font-family: 'Outfit';
}
.read-yes { background: rgba(14,166,110,.1);  color: #0EA66E; }
.read-no  { background: rgba(240,165,0,.1);   color: #F0A500; }

.meta-grid {
    display: flex; gap: 1rem; flex-wrap: wrap;
    padding: 1rem 0;
    border-bottom: 1px solid var(--border);
    margin-bottom: 1.25rem;
}
.meta-item {
    display: flex; align-items: center; gap: .4rem;
    font-size: .8rem; color: var(--tx-3); font-family: 'Outfit';
}
.meta-item i { font-size: .72rem; }

.msg-body-content {
    font-size: .92rem;
    color: var(--tx-1);
    font-family: 'Cairo';
    line-height: 1.85;
    padding: 1.25rem 0;
}

.reply-box {
    background: linear-gradient(135deg, rgba(26,107,204,.07), rgba(26,107,204,.03));
    border: 1px solid rgba(26,107,204,.18);
    border-radius: 14px;
    padding: 1.25rem;
}
.cp-input {
    width: 100%; background: rgba(255,255,255,.04);
    border: 1px solid var(--border); border-radius: 9px;
    color: var(--tx-1); padding: .6rem .9rem;
    font-family: 'Cairo'; font-size: .88rem;
    resize: vertical; min-height: 90px;
}
.cp-input:focus { outline: none; border-color: var(--electric); }
</style>
@endsection

@section('content')
<div class="animate__animated animate__fadeIn">

    {{-- Page Header --}}
    <div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
        <a href="{{ route('messages.index') }}" class="btn btn-sm" style="background:var(--bg-glass);border:1px solid var(--border);color:var(--tx-2);font-family:'Cairo';border-radius:9px;">
            <i class="fa-solid fa-arrow-right me-1"></i> البريد الوارد
        </a>
        <div style="width:42px;height:42px;border-radius:12px;background:linear-gradient(135deg,var(--electric),#6366f1);display:flex;align-items:center;justify-content:center;">
            <i class="fa-solid fa-envelope-open text-white"></i>
        </div>
        <div class="flex-fill" style="min-width:0;">
            <h1 class="fw-black m-0 text-truncate" style="font-size:1.15rem;font-family:'Cairo';color:var(--tx-1);">
                {{ $msg->subject ?: '(بدون موضوع)' }}
            </h1>
            <p class="mb-0" style="font-size:.76rem;font-family:'Outfit';color:var(--tx-3);">{{ $msg->created_at->format('Y-m-d H:i') }}</p>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-lg-8">

            {{-- Message Card --}}
            <div class="glass-panel p-4 mb-3">
                {{-- Subject --}}
                <h5 class="fw-black mb-0" style="font-family:'Cairo';color:var(--tx-1);">
                    {{ $msg->subject ?: '(بدون موضوع)' }}
                </h5>

                {{-- Meta --}}
                <div class="meta-grid">
                    <div class="meta-item">
                        <i class="fa-solid fa-user"></i>
                        @if($msg->channel==='broadcast') إذاعة عامة
                        @else من: موظف #{{ $msg->sender_id }} @endif
                    </div>
                    @if($msg->receiver_id)
                    <div class="meta-item">
                        <i class="fa-solid fa-user-check"></i>
                        إلى: موظف #{{ $msg->receiver_id }}
                    </div>
                    @endif
                    <div class="meta-item">
                        <i class="fa-solid fa-clock"></i>
                        {{ $msg->created_at->format('Y-m-d H:i') }}
                    </div>
                    <div class="meta-item">
                        <span class="priority-badge
                            @if($msg->priority==='urgent') p-urgent
                            @elseif($msg->priority==='high') p-high
                            @elseif($msg->priority==='low') p-low
                            @else p-normal @endif">
                            <i class="fa-solid fa-circle-dot" style="font-size:.5rem;"></i>
                            @if($msg->priority==='urgent') عاجل
                            @elseif($msg->priority==='high') هام
                            @elseif($msg->priority==='low') منخفض
                            @else عادي @endif
                        </span>
                    </div>
                    @if($msg->channel==='broadcast')
                    <div class="meta-item">
                        <span class="priority-badge" style="background:rgba(26,107,204,.1);color:var(--electric);">
                            <i class="fa-solid fa-bullhorn" style="font-size:.65rem;"></i> إذاعة عامة
                        </span>
                    </div>
                    @endif
                    <div class="meta-item">
                        <span class="read-badge {{ $msg->is_read ? 'read-yes' : 'read-no' }}">
                            <i class="fa-solid {{ $msg->is_read ? 'fa-check-double' : 'fa-circle-dot' }}" style="{{ $msg->is_read ? '' : 'font-size:.45rem;' }}"></i>
                            @if($msg->is_read) مقروءة @else جديدة @endif
                        </span>
                    </div>
                </div>

                {{-- Body --}}
                <div class="msg-body-content">
                    {!! nl2br(e($msg->body)) !!}
                </div>
            </div>

            {{-- Actions --}}
            @php $userId = session('user.id', session('user')['id'] ?? 0); @endphp
            <div class="d-flex gap-2 flex-wrap mb-3">
                @if($msg->receiver_id && $msg->sender_id != $userId)
                <button onclick="toggleReply()" class="btn btn-primary btn-sm fw-bold" style="font-family:'Cairo';">
                    <i class="fa-solid fa-reply me-1"></i> رد
                </button>
                @endif
                @if($msg->sender_id == $userId)
                <form method="POST" action="{{ route('messages.destroy', $msg->id) }}"
                      onsubmit="return confirm('هل تريد حذف هذه الرسالة؟')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm fw-bold" style="background:rgba(220,53,69,.1);color:#dc3545;border:1px solid rgba(220,53,69,.2);font-family:'Cairo';">
                        <i class="fa-solid fa-trash me-1"></i> حذف
                    </button>
                </form>
                @endif
                <a href="{{ route('messages.index') }}" class="btn btn-sm" style="background:var(--bg-glass);border:1px solid var(--border);color:var(--tx-2);font-family:'Cairo';">
                    <i class="fa-solid fa-inbox me-1"></i> العودة للبريد
                </a>
            </div>

            {{-- Quick Reply --}}
            @if($msg->receiver_id && $msg->sender_id != $userId)
            <div class="reply-box" id="replyBox" style="display:none;">
                <h6 class="fw-black mb-3 d-flex align-items-center gap-2" style="font-family:'Cairo';font-size:.92rem;color:var(--electric);">
                    <i class="fa-solid fa-reply"></i> رد سريع
                </h6>
                <form method="POST" action="{{ route('messages.send') }}">
                    @csrf
                    <input type="hidden" name="channel" value="direct">
                    <input type="hidden" name="receiver_id" value="{{ $msg->sender_id }}">
                    <input type="hidden" name="subject" value="رد: {{ $msg->subject }}">
                    <input type="hidden" name="priority" value="{{ $msg->priority }}">
                    <div class="mb-3">
                        <textarea name="body" class="cp-input" placeholder="اكتب ردك هنا..." required></textarea>
                    </div>
                    <div class="d-flex gap-2 justify-content-end">
                        <button type="button" onclick="toggleReply()" class="btn btn-sm" style="background:var(--bg-glass);border:1px solid var(--border);color:var(--tx-2);font-family:'Cairo';">إلغاء</button>
                        <button type="submit" class="btn btn-primary btn-sm fw-bold" style="font-family:'Cairo';">
                            <i class="fa-solid fa-paper-plane me-1"></i> إرسال الرد
                        </button>
                    </div>
                </form>
            </div>
            @endif
        </div>

        {{-- Side Info --}}
        <div class="col-12 col-lg-4">
            <div class="glass-panel p-4">
                <h6 class="fw-black mb-3" style="font-family:'Cairo';font-size:.9rem;color:var(--tx-2);">
                    <i class="fa-solid fa-circle-info me-2" style="color:var(--electric);"></i> معلومات الرسالة
                </h6>
                <div style="font-size:.82rem;font-family:'Outfit';color:var(--tx-3);line-height:2;">
                    <div><strong style="color:var(--tx-2);">رقم الرسالة:</strong> #{{ $msg->id }}</div>
                    <div><strong style="color:var(--tx-2);">القناة:</strong> {{ $msg->channel === 'broadcast' ? 'إذاعة عامة' : 'رسالة مباشرة' }}</div>
                    <div><strong style="color:var(--tx-2);">الأولوية:</strong>
                        @if($msg->priority==='urgent') عاجل
                        @elseif($msg->priority==='high') هام
                        @elseif($msg->priority==='low') منخفض
                        @else عادي @endif
                    </div>
                    <div><strong style="color:var(--tx-2);">الحالة:</strong>
                        @if($msg->is_read)
                            مقروءة
                            @if($msg->read_at) — {{ $msg->read_at->diffForHumans() }} @endif
                        @else جديدة @endif
                    </div>
                    <div><strong style="color:var(--tx-2);">الإرسال:</strong> {{ $msg->created_at->diffForHumans() }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleReply() {
    const box = document.getElementById('replyBox');
    if (box) {
        box.style.display = box.style.display === 'none' ? '' : 'none';
        if (box.style.display !== 'none') box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}
</script>
@endsection
