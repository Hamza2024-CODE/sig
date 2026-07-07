<?php
/**
 * Reusable FloatingCard Component
 * @var string $content
 * @var string|null $title
 * @var string|null $icon
 * @var string|null $class
 * @var string|null $header_actions
 * @var string|null $footer
 */
?>
<div class="float-card <?= $class ?? '' ?>">
    <?php if (!empty($title) || !empty($icon) || !empty($header_actions)): ?>
        <div class="card-header d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <?php if (!empty($icon)): ?>
                    <span class="text-primary"><i class="<?= $icon ?>"></i></span>
                <?php endif; ?>
                <?php if (!empty($title)): ?>
                    <span class="fw-bold text-dark" style="font-family: 'Cairo', sans-serif;"><?= htmlspecialchars($title) ?></span>
                <?php endif; ?>
            </div>
            <?php if (!empty($header_actions)): ?>
                <div class="card-actions">
                     <?= $header_actions ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <div class="card-body">
        <?= $content ?>
    </div>
    <?php if (!empty($footer)): ?>
        <div class="card-footer bg-transparent border-top p-3">
            <?= $footer ?>
        </div>
    <?php endif; ?>
</div>
