@extends('layouts.main')
@section('title', 'طلب جديد — محرك سير العمل | SGFEP')

@section('styles')
<style>
.type-selector {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: .75rem;
    margin-bottom: 1.5rem;
}
@media(min-width:576px) { .type-selector { grid-template-columns: repeat(4,1fr); } }

.type-card {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    gap: .5rem;
    padding: 1.1rem .75rem;
    border-radius: 12px;
    background: var(--bg-glass);
    border: 1.5px solid var(--border);
    cursor: pointer;
    transition: all .2s;
    text-align: center;
}
.type-card:hover { border-color: var(--electric); transform: translateY(-2px); }
.type-card.selected { border-color: var(--electric); background: rgba(26,107,204,.08); box-shadow: 0 4px 16px rgba(26,107,204,.12); }
.type-card i { font-size: 1.5rem; display: block; }
.type-card span { font-size: .8rem; font-weight: 700; font-family: 'Cairo'; color: var(--tx-2); }

.section-panel { display: none; }
.section-panel.active { display: block; }

.wf-label { display: block; font-size: .8rem; font-weight: 800; color: var(--tx-2); margin-bottom: .4rem; font-family: 'Cairo'; }
.wf-label span { color: #dc3545; margin-right: .2rem; }
.wf-control {
    width: 100%; background: rgba(255,255,255,.04);
    border: 1px solid var(--border); border-radius: 9px;
    color: var(--tx-1); padding: .6rem .9rem;
    font-family: 'Cairo'; font-size: .88rem; transition: border-color .2s;
}
.wf-control:focus { outline: none; border-color: var(--electric); }
select.wf-control { cursor: pointer; }
textarea.wf-control { resize: vertical; min-height: 90px; }

.info-tip {
    background: rgba(26,107,204,.07);
    border: 1px solid rgba(26,107,204,.15);
    border-radius: 9px;
    padding: .7rem .9rem;
    font-size: .8rem;
    color: var(--electric);
    margin-bottom: 1.25rem;
    display: flex; align-items: flex-start; gap: .5rem;
    font-family: 'Cairo';
}
</style>
@endsection

@section('content')
<div class="animate__animated animate__fadeIn">

    {{-- Page Header --}}
    <div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
        <a href="{{ route('workflow.index') }}" class="btn btn-sm" style="background:var(--bg-glass);border:1px solid var(--border);color:var(--tx-2);font-family:'Cairo';border-radius:9px;">
            <i class="fa-solid fa-arrow-right me-1"></i> العودة
        </a>
        <div style="width:42px;height:42px;border-radius:12px;background:linear-gradient(135deg,var(--electric),#6366f1);display:flex;align-items:center;justify-content:center;">
            <i class="fa-solid fa-plus-circle text-white"></i>
        </div>
        <div>
            <h1 class="fw-black m-0" style="font-size:1.2rem;font-family:'Cairo';color:var(--tx-1);">إنشاء طلب جديد</h1>
            <p class="text-muted mb-0" style="font-size:.78rem;font-weight:600;">اختر نوع الطلب ثم أملأ البيانات المطلوبة</p>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">

            {{-- Type Selector --}}
            <div class="type-selector">
                <div class="type-card tc-conge {{ $type==='conge' ? 'selected' : '' }}" onclick="selectType('conge')">
                    <i class="fa-solid fa-umbrella-beach" style="color:#3b82f6;"></i>
                    <span>طلب إجازة</span>
                </div>
                <div class="type-card tc-promotion {{ $type==='promotion' ? 'selected' : '' }}" onclick="selectType('promotion')">
                    <i class="fa-solid fa-arrow-trend-up" style="color:#8b5cf6;"></i>
                    <span>طلب ترقية</span>
                </div>
                <div class="type-card tc-transfert {{ $type==='transfert' ? 'selected' : '' }}" onclick="selectType('transfert')">
                    <i class="fa-solid fa-shuffle" style="color:#F0A500;"></i>
                    <span>طلب تحويل</span>
                </div>
                <div class="type-card tc-formation {{ $type==='formation' ? 'selected' : '' }}" onclick="selectType('formation')">
                    <i class="fa-solid fa-graduation-cap" style="color:#0EA66E;"></i>
                    <span>طلب تكوين</span>
                </div>
            </div>

            {{-- Dynamic Form --}}
            <form method="POST" action="{{ route('workflow.store') }}">
                @csrf
                <input type="hidden" name="type" id="typeInput" value="{{ $type }}">

                {{-- CONGE --}}
                <div class="section-panel {{ $type==='conge' ? 'active' : '' }}" id="panel-conge">
                    <div class="glass-panel p-4">
                        <h5 class="fw-black mb-3" style="font-family:'Cairo';color:var(--tx-1);font-size:1rem;">
                            <i class="fa-solid fa-umbrella-beach me-2" style="color:#3b82f6;"></i> طلب إجازة
                        </h5>
                        <div class="info-tip"><i class="fa-solid fa-info-circle"></i> سيُرسَل الطلب للمدير المباشر للمعالجة.</div>
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="wf-label">تاريخ البداية <span>*</span></label>
                                <input type="date" name="date_debut" class="wf-control" required>
                            </div>
                            <div class="col-6">
                                <label class="wf-label">تاريخ النهاية <span>*</span></label>
                                <input type="date" name="date_fin" class="wf-control" required>
                            </div>
                            <div class="col-12">
                                <label class="wf-label">نوع الإجازة <span>*</span></label>
                                <select name="type_conge" class="wf-control">
                                    <option value="سنوية">إجازة سنوية</option>
                                    <option value="مرضية">إجازة مرضية</option>
                                    <option value="دون أجر">إجازة دون أجر</option>
                                    <option value="استثنائية">إجازة استثنائية</option>
                                    <option value="أمومة">إجازة أمومة</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="wf-label">سبب / ملاحظة</label>
                                <textarea name="motif" class="wf-control" placeholder="اذكر سبب الطلب..."></textarea>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 mt-3 fw-bold" style="font-family:'Cairo';border-radius:10px;">
                            <i class="fa-solid fa-paper-plane me-2"></i> إرسال الطلب
                        </button>
                    </div>
                </div>

                {{-- PROMOTION --}}
                <div class="section-panel {{ $type==='promotion' ? 'active' : '' }}" id="panel-promotion">
                    <div class="glass-panel p-4">
                        <h5 class="fw-black mb-3" style="font-family:'Cairo';color:var(--tx-1);font-size:1rem;">
                            <i class="fa-solid fa-arrow-trend-up me-2" style="color:#8b5cf6;"></i> طلب ترقية
                        </h5>
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="wf-label">الرتبة الحالية <span>*</span></label>
                                <input type="text" name="grade_actuel" class="wf-control" placeholder="مثال: أستاذ تكوين مهني" required>
                            </div>
                            <div class="col-6">
                                <label class="wf-label">الرتبة المطلوبة <span>*</span></label>
                                <input type="text" name="grade_demande" class="wf-control" placeholder="مثال: أستاذ رئيسي" required>
                            </div>
                            <div class="col-12">
                                <label class="wf-label">سنوات الخبرة</label>
                                <input type="number" name="annees_exp" class="wf-control" placeholder="0" min="0" max="50">
                            </div>
                            <div class="col-12">
                                <label class="wf-label">مبررات الطلب <span>*</span></label>
                                <textarea name="motif" class="wf-control" placeholder="اذكر مبررات طلب الترقية..." required></textarea>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 mt-3 fw-bold" style="font-family:'Cairo';border-radius:10px;">
                            <i class="fa-solid fa-paper-plane me-2"></i> إرسال الطلب
                        </button>
                    </div>
                </div>

                {{-- TRANSFERT --}}
                <div class="section-panel {{ $type==='transfert' ? 'active' : '' }}" id="panel-transfert">
                    <div class="glass-panel p-4">
                        <h5 class="fw-black mb-3" style="font-family:'Cairo';color:var(--tx-1);font-size:1rem;">
                            <i class="fa-solid fa-shuffle me-2" style="color:#F0A500;"></i> طلب تحويل
                        </h5>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="wf-label">المؤسسة الحالية</label>
                                <input type="text" name="etab_actuel" class="wf-control" placeholder="اسم المؤسسة الحالية">
                            </div>
                            <div class="col-12">
                                <label class="wf-label">المؤسسة المطلوب التحويل إليها <span>*</span></label>
                                <input type="text" name="etab_demande" class="wf-control" placeholder="اسم المؤسسة الهدف" required>
                            </div>
                            <div class="col-12">
                                <label class="wf-label">الولاية المطلوبة</label>
                                <input type="text" name="wilaya_demande" class="wf-control" placeholder="مثال: ولاية الجزائر">
                            </div>
                            <div class="col-12">
                                <label class="wf-label">أسباب التحويل <span>*</span></label>
                                <textarea name="motif" class="wf-control" placeholder="اذكر أسباباً وجيهة..." required></textarea>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 mt-3 fw-bold" style="font-family:'Cairo';border-radius:10px;">
                            <i class="fa-solid fa-paper-plane me-2"></i> إرسال الطلب
                        </button>
                    </div>
                </div>

                {{-- FORMATION --}}
                <div class="section-panel {{ $type==='formation' ? 'active' : '' }}" id="panel-formation">
                    <div class="glass-panel p-4">
                        <h5 class="fw-black mb-3" style="font-family:'Cairo';color:var(--tx-1);font-size:1rem;">
                            <i class="fa-solid fa-graduation-cap me-2" style="color:#0EA66E;"></i> طلب تكوين / تدريب
                        </h5>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="wf-label">عنوان الدورة / التكوين <span>*</span></label>
                                <input type="text" name="intitule_formation" class="wf-control" placeholder="مثال: تكوين في الذكاء الاصطناعي" required>
                            </div>
                            <div class="col-6">
                                <label class="wf-label">الجهة المنظِّمة</label>
                                <input type="text" name="organisme" class="wf-control" placeholder="مثال: المعهد الوطني">
                            </div>
                            <div class="col-6">
                                <label class="wf-label">المدة</label>
                                <input type="text" name="duree" class="wf-control" placeholder="مثال: 5 أيام">
                            </div>
                            <div class="col-12">
                                <label class="wf-label">مبررات التكوين</label>
                                <textarea name="motif" class="wf-control" placeholder="كيف سيفيد هذا التكوين عملك؟"></textarea>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 mt-3 fw-bold" style="font-family:'Cairo';border-radius:10px;">
                            <i class="fa-solid fa-paper-plane me-2"></i> إرسال الطلب
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
function selectType(type) {
    document.getElementById('typeInput').value = type;
    document.querySelectorAll('.type-card').forEach(c => c.classList.remove('selected'));
    document.querySelector('.tc-' + type).classList.add('selected');
    document.querySelectorAll('.section-panel').forEach(p => p.classList.remove('active'));
    document.getElementById('panel-' + type).classList.add('active');
}
</script>
@endsection
