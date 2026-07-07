@extends('layouts.main')

@section('title', 'منشئ لوحات التحكم الديناميكي — إدارة النظام')

@section('styles')
<style>
    .builder-container {
        display: grid;
        grid-template-columns: 320px 1fr;
        gap: 1.5rem;
        align-items: start;
        margin-top: 1.5rem;
    }
    
    .builder-sidebar {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: var(--r-xl);
        padding: 1.5rem;
        box-shadow: var(--shadow-sm);
    }
    
    .builder-workspace {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: var(--r-xl);
        padding: 1.5rem;
        box-shadow: var(--shadow-sm);
        display: flex;
        flex-direction: column;
        min-height: 550px;
    }

    .portal-selector-tabs {
        display: flex;
        gap: 6px;
        overflow-x: auto;
        padding-bottom: 8px;
        margin-bottom: 1.5rem;
        border-bottom: 1px solid var(--border);
    }
    .portal-selector-tab {
        padding: 8px 14px;
        background: var(--bg-portal);
        border: 1px solid var(--border-portal);
        border-radius: var(--r-md);
        color: var(--tx-2);
        font-family: 'Cairo', sans-serif;
        font-size: 0.8rem;
        font-weight: 700;
        text-decoration: none;
        cursor: pointer;
        transition: all var(--d-fast);
        white-space: nowrap;
    }
    .portal-selector-tab:hover {
        background: rgba(26, 107, 204, 0.05);
        color: var(--electric);
    }
    .portal-selector-tab.active {
        background: linear-gradient(135deg, var(--electric) 0%, var(--electric-dark) 100%);
        color: #fff;
        border-color: var(--electric-border);
    }

    /* Grid Visualizer Canvas */
    .grid-visualizer-canvas {
        background-color: var(--bg-portal);
        background-image: radial-gradient(var(--border-portal) 1px, transparent 1px);
        background-size: 24px 24px;
        border: 2px dashed var(--border-portal);
        border-radius: var(--r-lg);
        min-height: 380px;
        position: relative;
        padding: 1.25rem;
        display: grid;
        grid-template-columns: repeat(12, 1fr);
        grid-auto-rows: 100px;
        gap: 1rem;
        margin-bottom: 1.5rem;
        transition: all var(--d-normal);
    }
    .grid-visualizer-canvas.empty::before {
        content: 'قم بإضافة مكونات (Widgets) للوحة التحكم لعرض التخطيط هنا';
        position: absolute;
        top: 50%; left: 50%;
        transform: translate(-50%, -50%);
        color: var(--tx-3);
        font-family: 'Cairo', sans-serif;
        font-size: 0.85rem;
        font-weight: 600;
        pointer-events: none;
    }

    .visual-widget {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: var(--r-md);
        box-shadow: var(--shadow-sm);
        padding: 0.75rem 1rem;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        position: relative;
        transition: all var(--d-fast) var(--ease);
        cursor: grab;
    }
    .visual-widget:hover {
        border-color: var(--electric-border);
        box-shadow: 0 4px 12px var(--electric-glow);
    }
    .visual-widget-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 0.5rem;
    }
    .visual-widget-title {
        font-family: 'Cairo';
        font-size: 0.82rem;
        font-weight: 800;
        color: var(--tx-1);
        margin: 0;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .visual-widget-size {
        font-family: 'Outfit';
        font-size: 0.7rem;
        font-weight: 700;
        color: var(--tx-3);
    }

    .widget-actions-btns {
        display: flex;
        gap: 6px;
    }

    .widget-action-btn {
        width: 24px;
        height: 24px;
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.7rem;
        border: none;
        cursor: pointer;
        transition: all var(--d-fast);
    }
    .widget-action-btn-edit {
        background: rgba(26, 107, 204, 0.08);
        color: var(--electric);
    }
    .widget-action-btn-edit:hover {
        background: var(--electric);
        color: #fff;
    }
    .widget-action-btn-delete {
        background: rgba(220, 53, 69, 0.08);
        color: var(--danger);
    }
    .widget-action-btn-delete:hover {
        background: var(--danger);
        color: #fff;
    }
</style>
@endsection

@section('content')
<!-- Page Banner -->
<div class="profile-banner">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
            <h1 class="profile-banner-title">
                <i class="fa-solid fa-compass-drafting me-2"></i>منشئ لوحات التحكم الديناميكي
            </h1>
            <p class="profile-banner-sub mb-0">تخصيص البوابات والـ Widgets للمستخدمين دون تعديل الكود البرمجي</p>
        </div>
        <a href="{{ url('dashboard') }}" class="btn btn-light px-4 fw-bold shadow-sm" style="border-radius: var(--r-md); color: var(--electric);">
            <i class="fa-solid fa-house me-1"></i> لوحة التحكم
        </a>
    </div>
</div>

<div class="builder-container">
    <!-- Sidebar: User Selector & Save -->
    <div class="builder-sidebar">
        <div class="mb-4">
            <label for="userSelect" class="form-label fw-bold text-dark" style="font-family:'Cairo'; font-size:0.85rem;">اختر حساب المستخدم</label>
            <select id="userSelect" class="form-select border-0 bg-light py-2.5 px-3 rounded-3" style="font-family:'Cairo'; font-size:0.85rem;" onchange="loadUserConfig()">
                <option value="">-- اختر مستخدم / User --</option>
                @foreach ($users as $u)
                    <option value="{{ $u['id'] }}">
                        {{ $u['nom_complet'] }} ({{ $u['username'] }}) - {{ $u['role_ar'] }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-4">
            <label for="layoutTypeSelect" class="form-label fw-bold text-dark" style="font-family:'Cairo'; font-size:0.85rem;">نوع التوزيع (Grid Layout)</label>
            <select id="layoutTypeSelect" class="form-select border-0 bg-light py-2.5 px-3 rounded-3" style="font-family:'Cairo'; font-size:0.85rem;">
                <option value="grid-12-cols">شبكة 12 عمود (Grid 12 Cols)</option>
            </select>
        </div>

        <div class="d-grid gap-2 mt-4">
            <button class="btn btn-primary py-2.5 fw-bold d-flex align-items-center justify-content-center gap-2" style="font-family:'Cairo'; font-size:0.85rem; border-radius: var(--r-md);" onclick="savePortalConfig()">
                <i class="fa-solid fa-floppy-disk"></i> حفظ لوحة التحكم
            </button>
            <button class="btn btn-secondary py-2.5 fw-bold d-flex align-items-center justify-content-center gap-2" style="font-family:'Cairo'; font-size:0.85rem; border-radius: var(--r-md);" onclick="loadPreset()">
                <i class="fa-solid fa-wand-magic-sparkles"></i> تطبيق قالب افتراضي
            </button>
        </div>
    </div>

    <!-- Workspace Panel -->
    <div class="builder-workspace">
        <!-- Portal Selector tabs -->
        <div class="portal-selector-tabs" id="portalTabs">
            @for ($i = 1; $i <= 11; $i++)
                <div class="portal-selector-tab {{ $i === 1 ? 'active' : '' }}" data-portal="{{ $i }}" onclick="switchPortal({{ $i }})">
                    البوابة {{ $i }}
                </div>
            @endfor
        </div>

        <!-- Toolbar -->
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h5 class="fw-bold mb-0 text-dark" style="font-family:'Cairo'; font-size:0.95rem;" id="workspaceTitle">
                تخطيط البوابة 1 للمستخدم المختار
            </h5>
            <button class="btn btn-success btn-sm px-3 py-1.5 fw-bold d-flex align-items-center gap-1.5" style="font-family:'Cairo'; font-size:0.8rem; border-radius: 6px;" onclick="openAddWidgetModal()">
                <i class="fa-solid fa-circle-plus"></i> إضافة مكون (Widget)
            </button>
        </div>

        <!-- Grid Visualizer Canvas -->
        <div class="grid-visualizer-canvas empty" id="gridCanvas">
            <!-- Dynamic elements loaded here -->
        </div>
    </div>
</div>

<!-- Add/Edit Widget Modal -->
<div class="modal fade" id="widgetModal" tabindex="-1" aria-hidden="true" style="font-family:'Cairo';">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: var(--r-xl);">
            <div class="modal-header bg-light border-0 py-3">
                <h5 class="modal-title fw-bold text-dark" style="font-size: 0.95rem;" id="widgetModalTitle">إضافة مكون جديد للوحة التحكم</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="widgetForm">
                    <input type="hidden" id="editingWidgetId" value="">
                    
                    <div class="mb-3">
                        <label for="widgetTypeSelect" class="form-label fw-bold">نوع المكون (Widget Template)</label>
                        <select id="widgetTypeSelect" class="form-select border-0 bg-light py-2" required onchange="onWidgetTypeChange()">
                            <option value="">-- اختر مكوناً --</option>
                            @foreach ($availableWidgets as $widgetTpl)
                                <option value="{{ $widgetTpl['type'] }}" data-w="{{ $widgetTpl['default_w'] }}" data-h="{{ $widgetTpl['default_h'] }}">
                                    {{ $widgetTpl['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="widgetTitleInput" class="form-label fw-bold">عنوان المكون</label>
                        <input type="text" id="widgetTitleInput" class="form-control border-0 bg-light py-2" placeholder="أدخل عنواناً مخصصاً (مثال: إحصائيات الغيابات)" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label for="widgetWidthInput" class="form-label fw-bold">العرض (عدد الأعمدة 1-12)</label>
                            <input type="number" id="widgetWidthInput" class="form-control border-0 bg-light py-2" min="1" max="12" required>
                        </div>
                        <div class="col-6">
                            <label for="widgetHeightInput" class="form-label fw-bold">الارتفاع (الصفوف 1-4)</label>
                            <input type="number" id="widgetHeightInput" class="form-control border-0 bg-light py-2" min="1" max="4" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label for="widgetThemeSelect" class="form-label fw-bold">اللون المميز (Theme)</label>
                            <select id="widgetThemeSelect" class="form-select border-0 bg-light py-2">
                                <option value="primary">أزرق بوابي</option>
                                <option value="success">أخضر إيجابي</option>
                                <option value="danger">أحمر تحذيري</option>
                                <option value="warning">أصفر ذهبي</option>
                                <option value="dark">داكن متين</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label for="widgetLimitInput" class="form-label fw-bold">حد عدد السجلات</label>
                            <input type="number" id="widgetLimitInput" class="form-control border-0 bg-light py-2" min="1" max="30" value="5">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 py-3 bg-light d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-secondary px-4 fw-bold" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-success px-4 fw-bold" onclick="saveWidgetForm()">حفظ المكون</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    let activePortal = 1;
    let widgetsList = []; // Array of widgets config
    let nextWidgetTempId = 1;

    function switchPortal(portalNum) {
        document.querySelectorAll('.portal-selector-tab').forEach(tab => {
            tab.classList.remove('active');
        });
        document.querySelector(`.portal-selector-tab[data-portal="${portalNum}"]`).classList.add('active');
        
        activePortal = portalNum;
        document.getElementById('workspaceTitle').innerText = `تخطيط البوابة ${activePortal} للمستخدم المختار`;
        loadUserConfig();
    }

    function loadUserConfig() {
        const userId = document.getElementById('userSelect').value;
        const canvas = document.getElementById('gridCanvas');
        
        if (!userId) {
            canvas.innerHTML = '';
            canvas.classList.add('empty');
            widgetsList = [];
            return;
        }

        fetch(`${APP_URL}/dashboard/builder/config/${userId}/${activePortal}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    widgetsList = data.widgets || [];
                    document.getElementById('layoutTypeSelect').value = data.layout_type || 'grid-12-cols';
                    renderWidgetsOnCanvas();
                } else {
                    showAlert('خطأ في تحميل التكوين', 'danger');
                }
            })
            .catch(err => {
                console.error(err);
                showAlert('فشل الاتصال بالخادم', 'danger');
            });
    }

    function renderWidgetsOnCanvas() {
        const canvas = document.getElementById('gridCanvas');
        canvas.innerHTML = '';
        
        if (widgetsList.length === 0) {
            canvas.classList.add('empty');
            return;
        }
        
        canvas.classList.remove('empty');
        
        widgetsList.forEach((w, index) => {
            const visualId = w.id || `temp_${index}`;
            const themeColor = w.config.theme_color || 'primary';
            const limit = w.config.limit || 5;

            const div = document.createElement('div');
            div.className = 'visual-widget';
            div.style.gridColumn = `span ${w.grid_w}`;
            div.style.gridRow = `span ${w.grid_h}`;
            
            div.innerHTML = `
                <div class="visual-widget-header">
                    <h6 class="visual-widget-title">
                        <span class="d-inline-block rounded-circle bg-${themeColor}-glow" style="width: 8px; height: 8px;"></span>
                        ${escapeHtml(w.title || 'مكون')}
                    </h6>
                    <div class="widget-actions-btns">
                        <button class="widget-action-btn widget-action-btn-edit" onclick="editWidget(${index})" title="تعديل">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <button class="widget-action-btn widget-action-btn-delete" onclick="deleteWidget(${index})" title="حذف">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-between mt-auto">
                    <span class="visual-widget-size">
                        <i class="fa-solid fa-table-cells me-1"></i> ${w.grid_w}x${w.grid_h}
                    </span>
                    <span class="text-secondary" style="font-size:0.68rem; font-family:'Cairo';">
                        ${getWidgetTypeName(w.type)} (${limit} سجلات)
                    </span>
                </div>
            `;
            canvas.appendChild(div);
        });
    }

    function getWidgetTypeName(type) {
        const names = {
            'absences_summary': 'غيابات',
            'trainee_stats': 'إحصائيات المتربصين',
            'grades_overview': 'نتائج المداولات'
        };
        return names[type] || type;
    }

    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }

    function openAddWidgetModal() {
        const userId = document.getElementById('userSelect').value;
        if (!userId) {
            alert('يرجى تحديد مستخدم أولاً!');
            return;
        }

        document.getElementById('widgetModalTitle').innerText = 'إضافة مكون جديد للوحة التحكم';
        document.getElementById('editingWidgetId').value = '';
        document.getElementById('widgetForm').reset();
        
        const modal = new bootstrap.Modal(document.getElementById('widgetModal'));
        modal.show();
    }

    function onWidgetTypeChange() {
        const select = document.getElementById('widgetTypeSelect');
        const selectedOpt = select.options[select.selectedIndex];
        if (!selectedOpt || select.value === '') return;

        const defaultW = selectedOpt.getAttribute('data-w');
        const defaultH = selectedOpt.getAttribute('data-h');
        const defaultTitle = selectedOpt.text.trim();

        document.getElementById('widgetWidthInput').value = defaultW;
        document.getElementById('widgetHeightInput').value = defaultH;
        document.getElementById('widgetTitleInput').value = defaultTitle;
    }

    function saveWidgetForm() {
        const type = document.getElementById('widgetTypeSelect').value;
        const title = document.getElementById('widgetTitleInput').value;
        const w = parseInt(document.getElementById('widgetWidthInput').value) || 4;
        const h = parseInt(document.getElementById('widgetHeightInput').value) || 2;
        const theme = document.getElementById('widgetThemeSelect').value;
        const limit = parseInt(document.getElementById('widgetLimitInput').value) || 5;
        const editingIndex = document.getElementById('editingWidgetId').value;

        if (!type || !title) {
            alert('يرجى ملء جميع الحقول المطلوبة!');
            return;
        }

        const widgetData = {
            type: type,
            title: title,
            grid_w: w,
            grid_h: h,
            grid_x: 0,
            grid_y: 0,
            config: {
                theme_color: theme,
                limit: limit
            }
        };

        if (editingIndex !== '') {
            // Edit existing
            widgetsList[parseInt(editingIndex)] = widgetData;
        } else {
            // Add new
            widgetsList.push(widgetData);
        }

        // Hide modal
        const modalEl = document.getElementById('widgetModal');
        const modalInstance = bootstrap.Modal.getInstance(modalEl);
        modalInstance.hide();

        renderWidgetsOnCanvas();
    }

    function editWidget(index) {
        const w = widgetsList[index];
        
        document.getElementById('widgetModalTitle').innerText = 'تعديل بيانات المكون';
        document.getElementById('editingWidgetId').value = index;
        document.getElementById('widgetTypeSelect').value = w.type;
        document.getElementById('widgetTitleInput').value = w.title;
        document.getElementById('widgetWidthInput').value = w.grid_w;
        document.getElementById('widgetHeightInput').value = w.grid_h;
        document.getElementById('widgetThemeSelect').value = w.config.theme_color || 'primary';
        document.getElementById('widgetLimitInput').value = w.config.limit || 5;

        const modal = new bootstrap.Modal(document.getElementById('widgetModal'));
        modal.show();
    }

    function deleteWidget(index) {
        if (confirm('هل أنت متأكد من حذف هذا المكون من اللوحة؟')) {
            widgetsList.splice(index, 1);
            renderWidgetsOnCanvas();
        }
    }

    function savePortalConfig() {
        const userId = document.getElementById('userSelect').value;
        if (!userId) {
            alert('يرجى اختيار مستخدم أولاً لحفظ التخطيط له!');
            return;
        }

        const layoutType = document.getElementById('layoutTypeSelect').value;

        const payload = {
            user_id: parseInt(userId),
            portal_number: activePortal,
            layout_type: layoutType,
            widgets: widgetsList,
            _token: '{{ csrf_token() }}'
        };

        fetch(`${APP_URL}/dashboard/builder/save`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');
                loadUserConfig();
            } else {
                showAlert(data.message || 'فشلت عملية الحفظ', 'danger');
            }
        })
        .catch(err => {
            console.error(err);
            showAlert('حدث خطأ في الشبكة أثناء الحفظ', 'danger');
        });
    }

    function loadPreset() {
        const userId = document.getElementById('userSelect').value;
        if (!userId) {
            alert('يرجى اختيار مستخدم لتطبيق القالب الافتراضي عليه!');
            return;
        }

        if (confirm('تنبيه: سيؤدي تطبيق هذا القالب لمسح المكونات الحالية في البوابة الحالية. هل تود الاستمرار؟')) {
            // Default preset widgets: trainee stats and absences
            widgetsList = [
                {
                    type: 'trainee_stats',
                    title: 'إحصائيات تعداد المتربصين الكلية',
                    grid_w: 4,
                    grid_h: 2,
                    grid_x: 0,
                    grid_y: 0,
                    config: { theme_color: 'primary', limit: 5 }
                },
                {
                    type: 'absences_summary',
                    title: 'جدول غيابات المتربصين',
                    grid_w: 8,
                    grid_h: 2,
                    grid_x: 4,
                    grid_y: 0,
                    config: { theme_color: 'danger', limit: 5 }
                }
            ];
            renderWidgetsOnCanvas();
        }
    }

    function showAlert(msg, type) {
        // Simple custom notification alert using existing main layouts alerts styling
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-4 left-4 shadow-lg`;
        alertDiv.style.zIndex = '9999';
        alertDiv.style.borderRadius = 'var(--r-md)';
        alertDiv.style.fontFamily = 'Cairo';
        alertDiv.style.fontSize = '0.82rem';
        alertDiv.style.fontWeight = '700';
        alertDiv.innerHTML = `
            <i class="fa-solid ${type === 'success' ? 'fa-circle-check text-success' : 'fa-circle-exclamation text-danger'} me-2"></i>
            ${msg}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="padding: 0.8rem 1rem;"></button>
        `;
        document.body.appendChild(alertDiv);
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alertDiv);
            bsAlert.close();
        }, 3500);
    }
</script>
@endsection
