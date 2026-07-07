<?php
/**
 * Reusable KpiCard Component
 * @var string $label
 * @var string $value
 * @var string|null $icon // FontAwesome icon class
 * @var string|null $iconType // e.g. blue, green, gold, red, navy, orange
 * @var string|null $delta // trend percentage e.g. "+12%" or "-3%"
 * @var string|null $deltaType // e.g. up, down, flat
 * @var string|null $subtitle
 * @var string|null $class
 */
?>
<div class="kpi-card <?= $class ?? '' ?>">
    <div class="kpi-card-header">
        <div class="d-flex flex-column gap-1">
            <span class="kpi-label"><?= htmlspecialchars($label) ?></span>
            <?php if (!empty($subtitle)): ?>
                <span class="text-muted small" style="font-size: 0.7rem; font-weight: 600;"><?= htmlspecialchars($subtitle) ?></span>
            <?php endif; ?>
        </div>
        <?php if (!empty($icon)): ?>
            <div class="kpi-icon-box kpi-icon-<?= $iconType ?? 'blue' ?>">
                <i class="<?= $icon ?>"></i>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="d-flex align-items-baseline justify-content-between mt-auto" style="direction: ltr;">
        <span class="kpi-value"><?= htmlspecialchars($value) ?></span>
        <?php if (!empty($delta)): ?>
            <span class="kpi-delta <?= $deltaType ?? 'flat' ?>">
                <?php if (($deltaType ?? 'flat') === 'up'): ?>
                    <i class="fa-solid fa-arrow-trend-up me-1"></i>
                <?php elseif (($deltaType ?? 'flat') === 'down'): ?>
                    <i class="fa-solid fa-arrow-trend-down me-1"></i>
                <?php endif; ?>
                <?= htmlspecialchars($delta) ?>
            </span>
        <?php endif; ?>
    </div>
</div>
