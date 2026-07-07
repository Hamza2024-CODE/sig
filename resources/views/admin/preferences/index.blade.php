@extends('layouts.main')
@section('title', 'إعدادات المظهر والتفضيلات — SGFEP')

@section('styles')
<style>
.pref-hero {
    background: linear-gradient(135deg, #0f1e3a 0%, #1a3a6b 60%, #2255a0 100%);
    border-radius: 20px; padding: 2rem 2.5rem; margin-bottom: 2rem;
    position: relative; overflow: hidden;
    box-shadow: 0 20px 60px rgba(15,30,58,0.35);
}
.pref-hero::before {
    content:''; position:absolute; top:-40%; right:-10%; width:50%; height:200%;
    background: radial-gradient(ellipse, rgba(255,255,255,0.05) 0%, transparent 70%);
    pointer-events:none;
}
.pref-hero-title { font-size:1.55rem; font-weight:900; color:#fff; font-family:'Cairo',sans-serif; margin-bottom:.3rem; }
.pref-hero-sub   { font-size:.88rem; color:rgba(255,255,255,.65); font-family:'Cairo',sans-serif; }

.pref-grid { display:grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap:1.5rem; }
.pref-card {
    background: var(--bg-surface-elevated, #fff);
    border-radius: 18px; border: 1.5px solid var(--border, #e8edf5);
    padding: 1.5rem; box-shadow: 0 4px 20px rgba(0,0,0,0.04);
    transition: box-shadow 0.2s;
}
.pref-card:hover { box-shadow: 0 8px 30px rgba(0,0,0,0.08); }
.pref-card-title {
    font-size:.92rem; font-weight:800; color:var(--tx-1);
    font-family:'Cairo',sans-serif; margin-bottom:1.2rem;
    display:flex; align-items:center; gap:8px;
    border-bottom:1px solid var(--border); padding-bottom:.75rem;
}
.pref-card-title i { width:30px; height:30px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:.85rem; }
.icon-theme   { background:rgba(26,107,204,.1); color:#1a6bcc; }
.icon-brand   { background:rgba(28,176,95,.1);  color:#1cb05f; }
.icon-dash    { background:rgba(111,66,193,.1); color:#6f42c1; }
.icon-notif   { background:rgba(220,53,69,.1);  color:#dc3545; }

/* Form helpers */
.pref-field { margin-bottom:1rem; }
.pref-label { font-size:.78rem; font-weight:700; color:var(--tx-2); font-family:'Cairo',sans-serif; margin-bottom:.4rem; display:block; }
.pref-input {
    width:100%; padding:.55rem .9rem; border:1.5px solid var(--border);
    border-radius:10px; font-family:'Cairo',sans-serif; font-size:.84rem;
    color:var(--tx-1); background:var(--bg-surface-elevated);
    transition:border-color .2s, box-shadow .2s;
}
.pref-input:focus { outline:none; border-color:#1a6bcc; box-shadow:0 0 0 3px rgba(26,107,204,.1); }
.pref-row { display:grid; grid-template-columns:1fr 1fr; gap:.8rem; }
@media(max-width:500px){ .pref-row { grid-template-columns:1fr; } }

/* Theme picker */
.theme-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:.6rem; }
.theme-opt {
    border:2px solid var(--border); border-radius:12px; padding:.6rem .5rem;
    text-align:center; cursor:pointer; transition:all .2s; font-size:.7rem;
    font-family:'Cairo',sans-serif; font-weight:700; color:var(--tx-2);
}
.theme-opt:hover { border-color:#1a6bcc; color:#1a6bcc; }
.theme-opt.selected { border-color:#1a6bcc; background:rgba(26,107,204,.08); color:#1a6bcc; }
.theme-dot { width:28px; height:28px; border-radius:50%; margin:0 auto .4rem; }

/* Color swatch */
.color-swatch { display:flex; gap:.5rem; flex-wrap:wrap; }
.color-btn {
    width:32px; height:32px; border-radius:8px; border:2.5px solid transparent;
    cursor:pointer; transition:transform .2s, border-color .2s;
}
.color-btn:hover { transform:scale(1.15); }
.color-btn.selected { border-color:#fff; box-shadow:0 0 0 3px var(--btn-color,#1a6bcc); }

/* Toggle switch */
.pref-toggle { display:flex; align-items:center; justify-content:space-between; padding:.5rem 0; border-bottom:1px solid var(--border); }
.pref-toggle:last-child { border-bottom:none; }
.pref-toggle-label { font-size:.82rem; font-weight:700; color:var(--tx-1); font-family:'Cairo',sans-serif; }
.pref-toggle-desc  { font-size:.72rem; color:var(--tx-3); font-family:'Cairo',sans-serif; }
.switch { position:relative; display:inline-block; width:42px; height:24px; flex-shrink:0; }
.switch input { opacity:0; width:0; height:0; }
.slider { position:absolute; inset:0; background:#cbd5e0; border-radius:24px; cursor:pointer; transition:.3s; }
.slider::before { content:''; position:absolute; width:18px; height:18px; left:3px; bottom:3px; background:#fff; border-radius:50%; transition:.3s; box-shadow:0 2px 5px rgba(0,0,0,.2); }
input:checked + .slider { background:#1a6bcc; }
input:checked + .slider::before { transform:translateX(18px); }

/* Per-page selector */
.per-page-grid { display:flex; gap:.5rem; flex-wrap:wrap; }
.pp-btn {
    padding:.4rem .9rem; border-radius:8px; border:1.5px solid var(--border);
    background:transparent; color:var(--tx-2); font-size:.78rem; font-weight:700;
    font-family:'Cairo',sans-serif; cursor:pointer; transition:all .15s;
}
.pp-btn:hover, .pp-btn.selected { background:#1a6bcc; color:#fff; border-color:#1a6bcc; }

/* Actions */
.pref-actions { display:flex; gap:.75rem; margin-top:1.5rem; justify-content:flex-end; flex-wrap:wrap; }
.btn-save {
    background:linear-gradient(135deg,#1a6bcc,#1453a0); color:#fff; border:none;
    padding:.65rem 1.5rem; border-radius:10px; font-size:.85rem; font-weight:700;
    font-family:'Cairo',sans-serif; cursor:pointer; transition:all .2s;
    box-shadow:0 4px 12px rgba(26,107,204,.3); display:flex; align-items:center; gap:7px;
}
.btn-save:hover { transform:translateY(-2px); box-shadow:0 8px 20px rgba(26,107,204,.4); }
.btn-reset {
    background:var(--bg-surface); color:var(--tx-2); border:1.5px solid var(--border);
    padding:.65rem 1.2rem; border-radius:10px; font-size:.85rem; font-weight:700;
    font-family:'Cairo',sans-serif; cursor:pointer; transition:all .2s;
}
.btn-reset:hover { border-color:#dc3545; color:#dc3545; }

/* Toast */
.pref-toast {
    position:fixed; bottom:2rem; left:50%; transform:translateX(-50%);
    padding:.75rem 1.5rem; border-radius:12px; font-family:'Cairo',sans-serif;
    font-size:.85rem; font-weight:700; box-shadow:0 10px 40px rgba(0,0,0,.2);
    z-index:9999; display:none; white-space:nowrap;
}
.pref-toast.success { background:#1cb05f; color:#fff; }
.pref-toast.error   { background:#dc3545; color:#fff; }
</style>
@endsection

@section('content')
<div class="pref-toast" id="prefToast"></div>

{{-- Hero --}}
<div class="pref-hero">
    <div class="pref-hero-title"><i class="fa-solid fa-palette me-2"></i>إعدادات المظهر والتفضيلات</div>
    <div class="pref-hero-sub">تُحفظ جميع الإعدادات في قاعدة البيانات — مرتبطة بحسابك الشخصي</div>
</div>

<form id="prefForm">
<div class="pref-grid">

    {{-- 1. Theme & Appearance --}}
    <div class="pref-card">
        <div class="pref-card-title">
            <i class="fa-solid fa-moon icon-theme"></i> المظهر والألوان
        </div>

        <div class="pref-field">
            <label class="pref-label">سمة العرض</label>
            <div class="theme-grid">
                @foreach([
                    ['val'=>'light',       'label'=>'فاتح',       'bg'=>'#f4f6fb', 'tx'=>'#0f2752'],
                    ['val'=>'dark',        'label'=>'غامق',        'bg'=>'#0f1e3a', 'tx'=>'#e8f0fe'],
                    ['val'=>'transparent', 'label'=>'شفاف',       'bg'=>'linear-gradient(135deg,#1a6bcc44,#6f42c144)', 'tx'=>'#1a3a6b'],
                    ['val'=>'color',       'label'=>'ملون',        'bg'=>'linear-gradient(135deg,#1a6bcc,#6f42c1)', 'tx'=>'#fff'],
                ] as $t)
                <div class="theme-opt {{ ($prefs->theme??'light')===$t['val'] ? 'selected':'' }}" onclick="selectTheme('{{ $t['val'] }}')">
                    <div class="theme-dot" style="background:{{ $t['bg'] }}; border:1.5px solid rgba(0,0,0,.1);"></div>
                    {{ $t['label'] }}
                </div>
                @endforeach
            </div>
            <input type="hidden" name="theme" id="themeInput" value="{{ $prefs->theme ?? 'light' }}">
        </div>

        <div class="pref-field">
            <label class="pref-label">لون التمييز (Accent)</label>
            <div class="color-swatch" id="accentSwatches">
                @foreach([
                    '#1a6bcc','#6f42c1','#1cb05f','#e07b00','#dc3545',
                    '#0dcaf0','#fd7e14','#20c997','#d63384','#495057'
                ] as $clr)
                <button type="button" class="color-btn {{ ($prefs->accent_color??'#1a6bcc')===$clr?'selected':'' }}"
                    style="background:{{ $clr }}; --btn-color:{{ $clr }};"
                    onclick="selectAccent('{{ $clr }}', this)"></button>
                @endforeach
                <input type="color" id="customAccentPicker" value="{{ $prefs->accent_color ?? '#1a6bcc' }}"
                    title="لون مخصص" style="width:32px;height:32px;border-radius:8px;cursor:pointer;border:none;padding:2px;"
                    onchange="selectAccent(this.value, null)">
            </div>
            <input type="hidden" name="accent_color" id="accentInput" value="{{ $prefs->accent_color ?? '#1a6bcc' }}">
        </div>

        <div class="pref-row">
            <div class="pref-field">
                <label class="pref-label">حجم الخط</label>
                <select name="font_size" class="pref-input">
                    <option value="sm" {{ ($prefs->font_size??'md')==='sm'?'selected':'' }}>صغير</option>
                    <option value="md" {{ ($prefs->font_size??'md')==='md'?'selected':'' }}>متوسط</option>
                    <option value="lg" {{ ($prefs->font_size??'md')==='lg'?'selected':'' }}>كبير</option>
                </select>
            </div>
            <div class="pref-field">
                <label class="pref-label">اللغة</label>
                <select name="language" class="pref-input">
                    <option value="ar" {{ ($prefs->language??'ar')==='ar'?'selected':'' }}>العربية</option>
                    <option value="fr" {{ ($prefs->language??'ar')==='fr'?'selected':'' }}>Français</option>
                </select>
            </div>
        </div>

        <div>
            <div class="pref-toggle">
                <div>
                    <div class="pref-toggle-label">الوضع المضغوط</div>
                    <div class="pref-toggle-desc">تقليل المساحات لعرض أكثر</div>
                </div>
                <label class="switch">
                    <input type="checkbox" name="compact_mode" id="compactMode" {{ ($prefs->compact_mode??false)?'checked':'' }}>
                    <span class="slider"></span>
                </label>
            </div>
            <div class="pref-toggle">
                <div>
                    <div class="pref-toggle-label">تفعيل الحركات</div>
                    <div class="pref-toggle-desc">الرسوم المتحركة والانتقالات</div>
                </div>
                <label class="switch">
                    <input type="checkbox" name="animations_enabled" id="animationsEnabled" {{ ($prefs->animations_enabled??true)?'checked':'' }}>
                    <span class="slider"></span>
                </label>
            </div>
        </div>
    </div>

    {{-- 2. Institution Branding --}}
    <div class="pref-card">
        <div class="pref-card-title">
            <i class="fa-solid fa-building-columns icon-brand"></i> هوية المؤسسة
        </div>
        <div class="pref-field">
            <label class="pref-label">اسم المؤسسة بالعربية</label>
            <input type="text" name="institution_name_ar" class="pref-input" value="{{ $prefs->institution_name_ar ?? ($user['nom_complet'] ?? '') }}" placeholder="اسم المؤسسة أو المديرية...">
        </div>
        <div class="pref-field">
            <label class="pref-label">Nom de l'établissement (Français)</label>
            <input type="text" name="institution_name_fr" class="pref-input" value="{{ $prefs->institution_name_fr ?? '' }}" placeholder="Nom en français...">
        </div>
        <div class="pref-row">
            <div class="pref-field">
                <label class="pref-label">الرمز / الكود</label>
                <input type="text" name="institution_code" class="pref-input" value="{{ $prefs->institution_code ?? ($user['username'] ?? '') }}" placeholder="DFEP-16 / CFPA-...">
            </div>
            <div class="pref-field">
                <label class="pref-label">نوع المؤسسة</label>
                <select name="institution_type" class="pref-input">
                    <option value="dfep"      {{ ($prefs->institution_type??'')==='dfep'?'selected':'' }}>مديرية تكوين مهني (DFEP)</option>
                    <option value="centre"    {{ ($prefs->institution_type??'')==='centre'?'selected':'' }}>مركز التكوين المهني</option>
                    <option value="institut"  {{ ($prefs->institution_type??'')==='institut'?'selected':'' }}>معهد وطني متخصص</option>
                    <option value="prive"     {{ ($prefs->institution_type??'')==='prive'?'selected':'' }}>مؤسسة خاصة</option>
                    <option value="ministere" {{ ($prefs->institution_type??'')==='ministere'?'selected':'' }}>وزارة / مديرية مركزية</option>
                </select>
            </div>
        </div>
        <div class="pref-field">
            <label class="pref-label">رابط شعار المؤسسة (URL)</label>
            <input type="url" name="institution_logo_url" class="pref-input" value="{{ $prefs->institution_logo_url ?? '' }}" placeholder="https://...">
        </div>
        @if($prefs->institution_logo_url)
        <div style="text-align:center; margin-top:.75rem;">
            <img src="{{ $prefs->institution_logo_url }}" style="max-height:80px; border-radius:10px; border:1.5px solid var(--border);" onerror="this.style.display='none'">
        </div>
        @endif
    </div>

    {{-- 3. Dashboard Preferences --}}
    <div class="pref-card">
        <div class="pref-card-title">
            <i class="fa-solid fa-gauge icon-dash"></i> إعدادات لوحة التحكم
        </div>
        <div class="pref-field">
            <label class="pref-label">عدد السجلات في الصفحة (Pagination)</label>
            <div class="per-page-grid" id="perPageGrid">
                @foreach([10, 15, 25, 50, 100] as $pp)
                <button type="button" class="pp-btn {{ ($prefs->items_per_page??25)==$pp?'selected':'' }}" onclick="selectPerPage({{ $pp }}, this)">{{ $pp }}</button>
                @endforeach
            </div>
            <input type="hidden" name="items_per_page" id="perPageInput" value="{{ $prefs->items_per_page ?? 25 }}">
        </div>
        <div class="pref-field">
            <label class="pref-label">التبويب الافتراضي عند الدخول</label>
            <select name="default_tab" class="pref-input">
                <option value=""          {{ ($prefs->default_tab??'')==''?'selected':'' }}>لوحة التحكم الرئيسية</option>
                <option value="finances"  {{ ($prefs->default_tab??'')==='finances'?'selected':'' }}>التسيير المالي</option>
                <option value="offres"    {{ ($prefs->default_tab??'')==='offres'?'selected':'' }}>العروض التكوينية</option>
                <option value="apprenants"{{ ($prefs->default_tab??'')==='apprenants'?'selected':'' }}>المتربصون</option>
            </select>
        </div>
        <div>
            <div class="pref-toggle">
                <div>
                    <div class="pref-toggle-label">إظهار لافتة الترحيب</div>
                    <div class="pref-toggle-desc">عند الدخول للوحة التحكم</div>
                </div>
                <label class="switch">
                    <input type="checkbox" name="show_welcome_banner" {{ ($prefs->show_welcome_banner??true)?'checked':'' }}>
                    <span class="slider"></span>
                </label>
            </div>
        </div>
    </div>

    {{-- 4. Notifications --}}
    <div class="pref-card">
        <div class="pref-card-title">
            <i class="fa-solid fa-bell icon-notif"></i> الإشعارات
        </div>
        <div>
            <div class="pref-toggle">
                <div>
                    <div class="pref-toggle-label">إشعارات البريد الإلكتروني</div>
                    <div class="pref-toggle-desc">استقبال الإشعارات عبر البريد</div>
                </div>
                <label class="switch">
                    <input type="checkbox" name="notif_email" {{ ($prefs->notif_email??false)?'checked':'' }}>
                    <span class="slider"></span>
                </label>
            </div>
            <div class="pref-toggle">
                <div>
                    <div class="pref-toggle-label">إشعارات المتصفح</div>
                    <div class="pref-toggle-desc">نافذة منبثقة في المتصفح</div>
                </div>
                <label class="switch">
                    <input type="checkbox" name="notif_browser" {{ ($prefs->notif_browser??true)?'checked':'' }}>
                    <span class="slider"></span>
                </label>
            </div>
            <div class="pref-toggle">
                <div>
                    <div class="pref-toggle-label">الصوت عند الإشعار</div>
                    <div class="pref-toggle-desc">تشغيل صوت عند وصول إشعار</div>
                </div>
                <label class="switch">
                    <input type="checkbox" name="notif_sound" {{ ($prefs->notif_sound??true)?'checked':'' }}>
                    <span class="slider"></span>
                </label>
            </div>
        </div>

        {{-- Live Preview --}}
        <div style="margin-top:1.5rem; padding:1rem; border-radius:12px; border:1.5px dashed var(--border); text-align:center;">
            <div style="font-size:.78rem; font-weight:700; color:var(--tx-3); font-family:'Cairo',sans-serif; margin-bottom:.5rem;">معاينة اللون المحدد</div>
            <div id="accentPreview" style="width:60px; height:60px; border-radius:14px; margin:0 auto; background:{{ $prefs->accent_color ?? '#1a6bcc' }}; box-shadow:0 8px 20px {{ $prefs->accent_color ?? '#1a6bcc' }}44; transition:all .3s;"></div>
            <div style="font-size:.7rem; color:var(--tx-3); margin-top:.5rem;" id="accentHexLabel">{{ $prefs->accent_color ?? '#1a6bcc' }}</div>
        </div>
    </div>

</div>

{{-- Actions --}}
<div class="pref-actions">
    <button type="button" class="btn-reset" onclick="resetPrefs()">
        <i class="fa-solid fa-rotate-left"></i> إعادة الضبط
    </button>
    <button type="submit" class="btn-save">
        <i class="fa-solid fa-floppy-disk"></i> حفظ الإعدادات
    </button>
</div>
</form>
@endsection

@section('scripts')
<script>
const BASE = window.location.pathname.split('/dashboard/')[0];
const CSRF = '{{ csrf_token() }}';

function showToast(msg, type='success') {
    const t = document.getElementById('prefToast');
    t.textContent = msg; t.className = 'pref-toast ' + type; t.style.display='block';
    setTimeout(()=>{ t.style.display='none'; }, 3000);
}

// ── Theme selector ────────────────────────────────
function selectTheme(val) {
    document.getElementById('themeInput').value = val;
    document.querySelectorAll('.theme-opt').forEach(o => o.classList.toggle('selected', o.onclick.toString().includes(val)));
    // Apply preview
    document.documentElement.setAttribute('data-theme', val);
    localStorage.setItem('sgfep_theme', val);
}

// ── Accent color selector ─────────────────────────
function selectAccent(color, btn) {
    document.getElementById('accentInput').value = color;
    document.getElementById('accentPreview').style.background = color;
    document.getElementById('accentPreview').style.boxShadow = `0 8px 20px ${color}44`;
    document.getElementById('accentHexLabel').textContent = color;
    document.querySelectorAll('.color-btn').forEach(b => b.classList.remove('selected'));
    if (btn) { btn.classList.add('selected'); btn.style.setProperty('--btn-color', color); }
    document.getElementById('customAccentPicker').value = color;
}

// ── Per-page selector ─────────────────────────────
function selectPerPage(val, btn) {
    document.getElementById('perPageInput').value = val;
    document.querySelectorAll('.pp-btn').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');
}

// ── Save ──────────────────────────────────────────
document.getElementById('prefForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    const data = {};
    for (const [k, v] of fd.entries()) data[k] = v;
    // Collect checkboxes
    ['compact_mode','animations_enabled','show_welcome_banner','notif_email','notif_browser','notif_sound'].forEach(k => {
        data[k] = document.querySelector(`[name="${k}"]`)?.checked ? 1 : 0;
    });

    try {
        const res = await fetch(BASE + '/dashboard/preferences/save', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify(data)
        });
        const json = await res.json();
        if (json.success) {
            showToast('✓ ' + json.message);
            // Reload page to let layout settings apply globally
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            showToast('✗ ' + (json.error || 'خطأ'), 'error');
        }
    } catch(err) {
        showToast('حدث خطأ في الاتصال', 'error');
    }
});

// ── Reset ─────────────────────────────────────────
async function resetPrefs() {
    if (!confirm('هل تريد إعادة ضبط جميع الإعدادات إلى القيم الافتراضية؟')) return;
    const res = await fetch(BASE + '/dashboard/preferences/reset', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: '{}'
    });
    const json = await res.json();
    if (json.success) { showToast(json.message); setTimeout(()=>location.reload(), 1000); }
    else showToast(json.error || 'خطأ', 'error');
}
</script>
@endsection
