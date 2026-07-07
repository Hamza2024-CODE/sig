@extends('layouts.main')

@section('content')
<div class="container-fluid py-4" dir="rtl">

    {{-- العنوان --}}
    <div class="d-flex align-items-center gap-3 mb-4">
        <div class="rounded-3 p-3" style="background:rgba(34,197,94,0.12)">
            <i class="fa-solid fa-database fa-lg text-success"></i>
        </div>
        <div>
            <h4 class="mb-0 fw-bold">أداة مزامنة ملفات HFSQL</h4>
            <small class="text-muted">استخراج الصور وملفات PDF من قاعدة HFSQL القديمة وحفظها في المنصة</small>
        </div>
        <div class="ms-auto">
            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">
                <i class="fa-solid fa-circle-check me-1"></i> الصفحة تُحمَّل من MySQL فقط — لا تأثير على سرعة المنصة
            </span>
        </div>
    </div>

    {{-- تنبيه --}}
    <div class="alert alert-info border-0 rounded-3 mb-4 d-flex gap-2 align-items-start" role="alert">
        <i class="fa-solid fa-circle-info mt-1 flex-shrink-0"></i>
        <div>
            الاتصال بخادم HFSQL البعيد <code>197.112.101.166:4900</code>
            يحدث <strong>فقط عند الضغط على زر البدء</strong> ولا يؤثر على سرعة بقية صفحات المنصة.
            الإحصائيات تُحمَّل تلقائياً عند فتح هذه الصفحة فقط.
        </div>
    </div>

    {{-- فلتر الولاية والمؤسسة --}}
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header fw-semibold border-bottom-0 bg-body-tertiary rounded-top-3">
            <i class="fa-solid fa-filter me-2 text-secondary"></i>
            فلترة الاستخراج <span class="text-muted fw-normal small">(اختياري)</span>
        </div>
        <div class="card-body py-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small fw-semibold mb-1">الولاية</label>
                    <select class="form-select form-select-sm" id="filter-wilaya">
                        <option value="0">— كل الولايات —</option>
                        @foreach($wilayas as $w)
                            <option value="{{ $w->IDWilayaa }}">
                                {{ str_pad($w->Num ?? $w->IDWilayaa, 2, '0', STR_PAD_LEFT) }} — {{ $w->Nom ?? $w->NomFr }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label small fw-semibold mb-1">المؤسسة</label>
                    <select class="form-select form-select-sm" id="filter-etab" disabled>
                        <option value="0">— اختر الولاية أولاً —</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <small class="text-muted d-block text-center">
                        <i class="fa-solid fa-circle-info text-info me-1"></i>
                        اتركهما فارغين للاستخراج الكامل
                    </small>
                </div>
            </div>
        </div>
    </div>

    {{-- شبكة الجداول --}}
    <div class="row g-3" id="tables-grid">
        @foreach($tableStats as $table => $info)
        <div class="col-xl-4 col-md-6" id="card-{{ $table }}">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body p-4">
                    {{-- أيقونة + اسم --}}
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="rounded-3 p-2 bg-primary bg-opacity-10 flex-shrink-0">
                            <i class="fa-solid {{ $info['icon'] }} text-primary"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-bold">{{ $info['label'] }}</h6>
                            <code class="text-muted small">{{ $table }}</code>
                        </div>
                    </div>

                    {{-- إحصائيات (lazy loading) --}}
                    <div class="stats-container" id="stats-{{ $table }}">
                        <div class="d-flex gap-2 mb-3">
                            <div class="flex-fill text-center bg-body-tertiary rounded-2 py-2 px-1">
                                <div class="fw-bold text-primary stats-total">—</div>
                                <small class="text-muted">إجمالي</small>
                            </div>
                            <div class="flex-fill text-center bg-body-tertiary rounded-2 py-2 px-1">
                                <div class="fw-bold text-success stats-with">—</div>
                                <small class="text-muted">لديه ملف</small>
                            </div>
                            <div class="flex-fill text-center bg-body-tertiary rounded-2 py-2 px-1">
                                <div class="fw-bold text-danger stats-without">—</div>
                                <small class="text-muted">بدون ملف</small>
                            </div>
                        </div>
                    </div>

                    {{-- الأعمدة المدعومة --}}
                    <div class="mb-3">
                        @foreach($info['columns'] as $col => $colInfo)
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div>
                                <span class="badge {{ $colInfo['type'] === 'pdf' ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' }} me-1">
                                    <i class="fa-solid {{ $colInfo['type'] === 'pdf' ? 'fa-file-pdf' : 'fa-image' }} me-1"></i>
                                    {{ strtoupper($colInfo['ext']) }}
                                </span>
                                <small class="text-muted">{{ $colInfo['label'] }}</small>
                                <code class="text-muted small ms-1">{{ $col }}</code>
                            </div>

                            {{-- شريط التقدم --}}
                            <div class="progress-container-{{ $table }}-{{ $col }}"
                                 data-table="{{ $table }}" data-col="{{ $col }}" style="display:none; width:80px;">
                                <div class="progress" style="height:8px;">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                                         style="width:0%"></div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    {{-- أزرار الاستخراج لكل عمود --}}
                    @foreach($info['columns'] as $col => $colInfo)
                    <button class="btn btn-sm btn-primary w-100 mb-1 btn-extract"
                            data-table="{{ $table }}"
                            data-col="{{ $col }}"
                            data-label="{{ $info['label'] }} — {{ $colInfo['label'] }}"
                            id="btn-{{ $table }}-{{ $col }}">
                        <i class="fa-solid fa-play me-1"></i>
                        استخراج {{ $colInfo['type'] === 'pdf' ? 'ملفات PDF' : 'الصور' }}
                        <span class="text-white-50 small">({{ $col }})</span>
                    </button>
                    @endforeach

                    {{-- سجل العمليات --}}
                    <div class="sync-log mt-2" id="log-{{ $table }}"
                         style="display:none; max-height:150px; overflow-y:auto; background:#0d1117;
                                color:#c9d1d9; padding:8px; border-radius:6px; font-size:11px; font-family:monospace;">
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const wilayaSel  = document.getElementById('filter-wilaya');
    const etabSel    = document.getElementById('filter-etab');
    let pollInterval = null;

    // ── 1. تحميل الإحصائيات تلقائياً (Lazy) ──
    loadStats();

    function loadStats() {
        fetch('{{ route("admin.sync-files.stats") }}')
            .then(r => r.json())
            .then(data => {
                for (const [table, s] of Object.entries(data)) {
                    const el = document.getElementById(`stats-${table}`);
                    if (!el) continue;
                    el.querySelector('.stats-total').textContent  = s.total.toLocaleString('ar-DZ');
                    el.querySelector('.stats-with').textContent   = s.with_files.toLocaleString('ar-DZ');
                    el.querySelector('.stats-without').textContent = s.without.toLocaleString('ar-DZ');
                }
            })
            .catch(() => {/* silent fail */});
    }

    // ── 2. جلب المؤسسات ──
    wilayaSel.addEventListener('change', function () {
        const wId = this.value;
        etabSel.disabled = true;
        etabSel.innerHTML = '<option value="0">— جاري التحميل... —</option>';
        if (!wId || wId === '0') {
            etabSel.innerHTML = '<option value="0">— اختر الولاية أولاً —</option>';
            return;
        }
        fetch('{{ route("admin.sync-files.etablissements") }}?wilaya_id=' + wId)
            .then(r => r.json())
            .then(data => {
                etabSel.innerHTML = '<option value="0">— كل مؤسسات الولاية —</option>';
                data.forEach(e => {
                    const o = document.createElement('option');
                    o.value = e.IDetablissement;
                    o.textContent = (e.Abr ? `[${e.Abr.trim()}] ` : '') + e.Nom;
                    etabSel.appendChild(o);
                });
                etabSel.disabled = false;
            });
    });

    // ── 3. زر الاستخراج ──
    document.querySelectorAll('.btn-extract').forEach(btn => {
        btn.addEventListener('click', function () {
            const table  = this.dataset.table;
            const col    = this.dataset.col;
            const label  = this.dataset.label;
            const etabId = parseInt(etabSel.value) || 0;

            if (!confirm(`بدء استخراج:\n"${label}"\n\nستعمل المزامنة في الخلفية بأمان لتفادي انقطاع الاتصال.`)) return;

            // تعطيل الزر وتجهيز الواجهة
            this.disabled = true;
            this.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin me-1"></i>جاري بدء المزامنة...';

            const log = document.getElementById(`log-${table}`);
            log.style.display = 'block';
            log.innerHTML = '';
            appendLog(log, `>>> بدء العملية لـ [${table}.${col}]`, '#58a6ff');

            const progressWrap = document.querySelector(`.progress-container-${table}-${col}`);
            const progressBar  = progressWrap?.querySelector('.progress-bar');
            if (progressWrap) progressWrap.style.display = 'block';
            if (progressBar) progressBar.style.width = '0%';

            // إرسال طلب البدء في الخلفية
            fetch('{{ route("admin.sync-files.start") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ table, column: col, etab_id: etabId })
            })
            .then(r => r.json())
            .then(data => {
                if (data.error) throw new Error(data.error);
                appendLog(log, data.message, '#e1b33e');
                
                // بدء فحص الحالة
                if (pollInterval) clearInterval(pollInterval);
                pollInterval = setInterval(() => checkStatus(table, col, progressBar, log, this), 3000);
            })
            .catch(err => {
                appendLog(log, `✗ خطأ في بدء المزامنة: ${err.message}`, '#f85149');
                this.disabled = false;
                this.innerHTML = '<i class="fa-solid fa-play me-1"></i>استخراج الصور';
            });
        });
    });

    function checkStatus(table, col, bar, log, btn) {
        fetch(`{{ route("admin.sync-files.status") }}?table=${table}&column=${col}`)
            .then(r => r.json())
            .then(data => {
                if (!data || data.status === 'idle') return;

                if (data.status === 'connecting') {
                    btn.innerHTML = '<i class="fa-solid fa-plug fa-bounce me-1"></i>جاري الاتصال بـ HFSQL...';
                    if (bar) bar.style.width = '5%';
                } else if (data.status === 'running') {
                    const pct = data.percent || 0;
                    if (bar) bar.style.width = `${pct}%`;
                    btn.innerHTML = `<i class="fa-solid fa-spinner fa-spin me-1"></i>جاري المزامنة (${pct}%)`;
                    
                    // تحديث محتوى الـ log
                    log.innerHTML = `<div>[${new Date().toLocaleTimeString('fr-DZ')}] <span style="color:#58a6ff">>>> جاري الاستخراج:</span> معالجة ${data.processed.toLocaleString()} سجل (تم استخراج ${data.extracted.toLocaleString()} ملف)</div>`;
                } else if (data.status === 'done') {
                    if (pollInterval) clearInterval(pollInterval);
                    if (bar) { bar.style.width = '100%'; bar.parentElement.parentElement.style.display = 'none'; }
                    btn.disabled = false;
                    btn.classList.replace('btn-primary', 'btn-success');
                    btn.innerHTML = '<i class="fa-solid fa-check me-1"></i>اكتملت';
                    log.innerHTML = `<div style="color:#3fb950">[${new Date().toLocaleTimeString('fr-DZ')}] ✓ اكتملت المزامنة بنجاح! تم استخراج ${data.extracted.toLocaleString()} ملف.</div>`;
                    loadStats();
                } else if (data.status === 'error') {
                    if (pollInterval) clearInterval(pollInterval);
                    appendLog(log, `✗ فشلت العملية: ${data.error}`, '#f85149');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-rotate-right me-1"></i>إعادة المحاولة';
                }
            })
            .catch(() => {});
    }

    function appendLog(c, msg, color) {
        const d = document.createElement('div');
        d.style.color = color || '#c9d1d9';
        d.textContent = `[${new Date().toLocaleTimeString('fr-DZ')}] ${msg}`;
        c.appendChild(d);
        c.scrollTop = c.scrollHeight;
    }
});
</script>
@endsection
