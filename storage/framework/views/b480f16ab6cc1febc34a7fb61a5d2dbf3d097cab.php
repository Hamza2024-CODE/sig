<?php $__env->startSection('title', 'البريد الداخلي | SGFEP'); ?>

<?php $__env->startSection('styles'); ?>
<style>
/* ── Messaging Layout ────────────────────────────── */
.msg-layout {
    display: grid;
    grid-template-columns: 260px 1fr;
    gap: 1.25rem;
    min-height: calc(100vh - 160px);
    align-items: start;
}
@media(max-width:768px) { .msg-layout { grid-template-columns: 1fr; } }

/* Sidebar */
.msg-sidebar {
    position: sticky;
    top: 80px;
}
.msg-nav-item {
    display: flex; align-items: center; gap: 10px;
    padding: 9px 12px;
    border-radius: 9px;
    color: var(--tx-2);
    font-size: .83rem;
    font-weight: 700;
    font-family: 'Cairo';
    text-decoration: none;
    border: 1px solid transparent;
    transition: all .17s;
    margin-bottom: 3px;
}
.msg-nav-item i {
    width: 26px; height: 26px; border-radius: 6px;
    display: flex; align-items: center; justify-content: center;
    background: rgba(26,107,204,.06); color: var(--tx-3);
    font-size: .78rem; flex-shrink: 0;
    transition: all .17s;
}
.msg-nav-item:hover, .msg-nav-item.active {
    background: rgba(26,107,204,.08);
    color: var(--electric);
    border-color: rgba(26,107,204,.15);
}
.msg-nav-item:hover i, .msg-nav-item.active i {
    background: rgba(26,107,204,.14); color: var(--electric);
}
.msg-nav-item.active {
    background: linear-gradient(135deg,rgba(26,107,204,.12),rgba(26,107,204,.05));
}
.unread-pill {
    background: #dc3545; color: #fff;
    border-radius: 20px; padding: .1rem .45rem;
    font-size: .68rem; font-weight: 800; font-family: 'Outfit';
    margin-right: auto;
}

/* Message list */
.msg-item {
    display: flex; align-items: flex-start; gap: .85rem;
    padding: 1rem 1.1rem;
    border-bottom: 1px solid var(--border);
    text-decoration: none; color: inherit;
    cursor: pointer;
    transition: background .15s;
    position: relative;
}
.msg-item:last-child { border-bottom: none; }
.msg-item:hover { background: rgba(26,107,204,.03); }
.msg-item.unread { border-right: 3px solid var(--electric); background: rgba(26,107,204,.04); }
.msg-avatar {
    width: 40px; height: 40px; border-radius: 50%;
    background: linear-gradient(135deg, var(--electric), #6366f1);
    display: flex; align-items: center; justify-content: center;
    font-size: .95rem; font-weight: 800; font-family: 'Outfit';
    color: #fff; flex-shrink: 0;
}
.msg-avatar.broadcast { background: linear-gradient(135deg, #F0A500, #ef4444); }
.msg-content { flex: 1; min-width: 0; }
.msg-sender { font-weight: 800; font-size: .87rem; font-family: 'Cairo'; color: var(--tx-1); }
.msg-subject { font-size: .84rem; font-weight: 700; font-family: 'Cairo'; color: var(--tx-2); margin-bottom: .15rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.msg-preview { font-size: .77rem; color: var(--tx-3); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-family: 'Outfit'; }
.msg-meta { display: flex; justify-content: space-between; align-items: center; margin-bottom: .25rem; }
.msg-time { font-size: .73rem; color: var(--tx-3); font-family: 'Outfit'; }

.priority-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; margin-top: 5px; }
.p-urgent { background: #dc3545; }
.p-high   { background: #F0A500; }
.p-normal { background: #0EA66E; }
.p-low    { background: var(--tx-3); }

/* Compose button */
.compose-btn {
    display: flex; align-items: center; justify-content: center; gap: .5rem;
    width: 100%;
    padding: .65rem 1rem;
    background: var(--electric);
    color: #fff;
    border: none; border-radius: 10px;
    font-family: 'Cairo'; font-size: .9rem; font-weight: 800;
    cursor: pointer;
    transition: all .2s;
    margin-bottom: 1rem;
}
.compose-btn:hover { filter: brightness(1.12); transform: translateY(-1px); }

/* Compose Modal */
.compose-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(2,9,26,.65); backdrop-filter: blur(6px);
    z-index: 9999; align-items: flex-end; justify-content: center;
}
.compose-overlay.open { display: flex; }
.compose-panel {
    background: var(--bg-surface, #1a1d27);
    border: 1px solid var(--border);
    border-radius: 18px 18px 0 0;
    padding: 1.75rem 2rem;
    width: 100%; max-width: 600px;
    animation: slideUp .22s ease;
}
@keyframes slideUp { from { transform: translateY(24px); opacity: 0; } to { transform: none; opacity: 1; } }
.compose-panel h5 { font-family: 'Cairo'; font-weight: 800; font-size: 1rem; margin-bottom: 1.1rem; }

.cp-label { display: block; font-size: .78rem; font-weight: 800; color: var(--tx-2); margin-bottom: .35rem; font-family: 'Cairo'; }
.cp-input {
    width: 100%; background: rgba(255,255,255,.04);
    border: 1px solid var(--border); border-radius: 9px;
    color: var(--tx-1); padding: .55rem .85rem;
    font-family: 'Cairo'; font-size: .87rem;
}
.cp-input:focus { outline: none; border-color: var(--electric); }
select.cp-input { cursor: pointer; }
textarea.cp-input { resize: vertical; min-height: 100px; }
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="animate__animated animate__fadeIn">

    
    <div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
        <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,var(--electric),#6366f1);display:flex;align-items:center;justify-content:center;">
            <i class="fa-solid fa-envelope text-white fs-5"></i>
        </div>
        <div>
            <h1 class="fw-black m-0" style="font-size:1.25rem;font-family:'Cairo';color:var(--tx-1);">
                البريد الداخلي
                <?php if($unread > 0): ?>
                <span class="unread-pill ms-2"><?php echo e($unread); ?></span>
                <?php endif; ?>
            </h1>
            <p class="text-muted mb-0" style="font-size:.78rem;font-weight:600;">الرسائل الداخلية للمؤسسة — للموظفين المعتمدين فقط</p>
        </div>
    </div>

    <div class="msg-layout">

        
        <aside class="msg-sidebar">
            <div class="glass-panel p-3">
                <button class="compose-btn" onclick="openCompose()">
                    <i class="fa-solid fa-pen-to-square"></i> رسالة جديدة
                </button>

                <p class="mb-2" style="font-size:.7rem;font-weight:800;color:var(--tx-3);letter-spacing:.06em;text-transform:uppercase;padding-right:12px;">صناديق البريد</p>
                <nav>
                    <a href="?filter=inbox" class="msg-nav-item <?php echo e($filter==='inbox' ? 'active' : ''); ?>">
                        <i class="fa-solid fa-inbox"></i>
                        الوارد
                        <?php if($unread > 0): ?><span class="unread-pill"><?php echo e($unread); ?></span><?php endif; ?>
                    </a>
                    <a href="?filter=sent" class="msg-nav-item <?php echo e($filter==='sent' ? 'active' : ''); ?>">
                        <i class="fa-solid fa-paper-plane"></i>
                        المرسَل
                    </a>
                    <a href="?filter=broadcast" class="msg-nav-item <?php echo e($filter==='broadcast' ? 'active' : ''); ?>">
                        <i class="fa-solid fa-bullhorn"></i>
                        الإذاعة العامة
                    </a>
                </nav>

                <hr style="border-color:var(--border);margin:.75rem 0;">
                <div class="d-flex align-items-center gap-2" style="font-size:.75rem;color:var(--tx-3);padding:0 6px;">
                    <i class="fa-solid fa-circle-info" style="color:var(--electric);"></i>
                    رسائل داخلية للمؤسسة فقط
                </div>
            </div>
        </aside>

        
        <main>
            <div class="glass-panel p-0 overflow-hidden">
                
                <div class="d-flex align-items-center justify-content-between px-4 py-3" style="border-bottom:1px solid var(--border);">
                    <h6 class="fw-black mb-0 d-flex align-items-center gap-2" style="font-family:'Cairo';font-size:.92rem;color:var(--tx-1);">
                        <?php if($filter==='sent'): ?>
                            <i class="fa-solid fa-paper-plane" style="color:var(--electric);"></i> الرسائل المرسَلة
                        <?php elseif($filter==='broadcast'): ?>
                            <i class="fa-solid fa-bullhorn" style="color:#F0A500;"></i> الإذاعة العامة
                        <?php else: ?>
                            <i class="fa-solid fa-inbox" style="color:var(--electric);"></i> صندوق الوارد
                        <?php endif; ?>
                        <span style="font-size:.78rem;font-weight:600;color:var(--tx-3);">(<?php echo e($messages->total()); ?> رسالة)</span>
                    </h6>
                </div>

                <?php if($messages->isEmpty()): ?>
                <div class="text-center py-5">
                    <i class="fa-solid fa-envelope-open fa-3x mb-3" style="color:var(--tx-3);opacity:.25;"></i>
                    <p class="fw-bold mb-1" style="font-family:'Cairo';color:var(--tx-2);">لا توجد رسائل</p>
                    <p class="small mb-0" style="color:var(--tx-3);">أرسل رسالة جديدة عبر الزر أعلاه</p>
                </div>
                <?php else: ?>
                <?php $__currentLoopData = $messages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $msg): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="<?php echo e(route('messages.show', $msg->id)); ?>" class="msg-item <?php echo e(!$msg->is_read ? 'unread' : ''); ?>">
                    <div class="msg-avatar <?php echo e($msg->channel==='broadcast' ? 'broadcast' : ''); ?>">
                        <?php if($msg->channel==='broadcast'): ?>
                            <i class="fa-solid fa-bullhorn" style="font-size:.8rem;"></i>
                        <?php else: ?>
                            <?php echo e(mb_strtoupper(mb_substr($msg->subject ?: 'ر', 0, 1))); ?>

                        <?php endif; ?>
                    </div>
                    <div class="msg-content">
                        <div class="msg-meta">
                            <span class="msg-sender">
                                <?php if($msg->channel==='broadcast'): ?> إذاعة عامة
                                <?php elseif($filter==='sent'): ?> إلى: #<?php echo e($msg->receiver_id); ?>

                                <?php else: ?> من: #<?php echo e($msg->sender_id); ?> <?php endif; ?>
                            </span>
                            <span class="msg-time"><?php echo e($msg->created_at->diffForHumans()); ?></span>
                        </div>
                        <div class="msg-subject"><?php echo e($msg->subject ?: '(بدون موضوع)'); ?></div>
                        <div class="msg-preview"><?php echo e(Str::limit(strip_tags($msg->body), 75)); ?></div>
                    </div>
                    <div class="priority-dot
                        <?php if($msg->priority==='urgent'): ?> p-urgent
                        <?php elseif($msg->priority==='high'): ?> p-high
                        <?php elseif($msg->priority==='low'): ?> p-low
                        <?php else: ?> p-normal <?php endif; ?>">
                    </div>
                </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                <?php if($messages->hasPages()): ?>
                <div class="d-flex justify-content-center gap-2 p-3">
                    <?php if(!$messages->onFirstPage()): ?>
                    <a href="<?php echo e($messages->previousPageUrl()); ?>" class="btn btn-sm" style="background:var(--bg-glass);border:1px solid var(--border);color:var(--tx-2);font-family:'Cairo';">
                        <i class="fa-solid fa-chevron-right"></i> السابق
                    </a>
                    <?php endif; ?>
                    <?php if($messages->hasMorePages()): ?>
                    <a href="<?php echo e($messages->nextPageUrl()); ?>" class="btn btn-sm" style="background:var(--bg-glass);border:1px solid var(--border);color:var(--tx-2);font-family:'Cairo';">
                        التالي <i class="fa-solid fa-chevron-left"></i>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>


<div class="compose-overlay" id="composeOverlay">
    <div class="compose-panel">
        <h5 class="d-flex align-items-center justify-content-between">
            <span><i class="fa-solid fa-pen-to-square me-2" style="color:var(--electric);"></i> إنشاء رسالة جديدة</span>
            <button onclick="closeCompose()" style="background:none;border:none;color:var(--tx-3);font-size:1.1rem;cursor:pointer;">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </h5>
        <form method="POST" action="<?php echo e(route('messages.send')); ?>">
            <?php echo csrf_field(); ?>
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="cp-label">نوع الإرسال</label>
                    <select name="channel" class="cp-input" id="channelSel" onchange="toggleReceiver()">
                        <option value="direct">رسالة مباشرة</option>
                        <?php if(in_array($role, ['admin','drh','directeur','dfep','high_admin'])): ?>
                        <option value="broadcast">إذاعة عامة (للجميع)</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-12 col-md-6" id="receiverGroup">
                    <label class="cp-label">المستلم</label>
                    <select name="receiver_id" class="cp-input">
                        <option value="">— اختر موظفاً —</option>
                        <?php $__currentLoopData = $employees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $emp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($emp->id); ?>"><?php echo e($emp->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="cp-label">الأولوية</label>
                    <select name="priority" class="cp-input">
                        <option value="normal">عادي</option>
                        <option value="high">هام</option>
                        <option value="urgent">عاجل</option>
                        <option value="low">منخفض</option>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="cp-label">الموضوع</label>
                    <input type="text" name="subject" class="cp-input" placeholder="موضوع الرسالة..." maxlength="200">
                </div>
                <div class="col-12">
                    <label class="cp-label">نص الرسالة <span style="color:#dc3545;">*</span></label>
                    <textarea name="body" class="cp-input" placeholder="اكتب رسالتك هنا..." required></textarea>
                </div>
            </div>
            <div class="d-flex gap-2 justify-content-end mt-3">
                <button type="button" onclick="closeCompose()" class="btn btn-sm" style="background:var(--bg-glass);border:1px solid var(--border);color:var(--tx-2);font-family:'Cairo';">إلغاء</button>
                <button type="submit" class="btn btn-primary btn-sm fw-bold" style="font-family:'Cairo';">
                    <i class="fa-solid fa-paper-plane me-1"></i> إرسال
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openCompose()  { document.getElementById('composeOverlay').classList.add('open'); }
function closeCompose() { document.getElementById('composeOverlay').classList.remove('open'); }
function toggleReceiver() {
    const ch = document.getElementById('channelSel').value;
    document.getElementById('receiverGroup').style.display = ch === 'broadcast' ? 'none' : '';
}
document.getElementById('composeOverlay').addEventListener('click', function(e) {
    if (e.target === this) closeCompose();
});
// Auto-refresh unread badge every 60s
setInterval(() => {
    fetch('<?php echo e(route("messages.unread")); ?>')
        .then(r => r.json())
        .then(data => {
            document.querySelectorAll('.unread-pill').forEach(b => {
                b.textContent = data.count || '';
                b.style.display = data.count ? '' : 'none';
            });
        }).catch(() => {});
}, 60000);
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.main', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\sig\resources\views/dashboard/messaging/index.blade.php ENDPATH**/ ?>