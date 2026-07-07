<?php
/**
 * Reusable BentoCard Component
 * @var string $content
 * @var int $span // grid columns (e.g. 1-12)
 * @var int|null $rowSpan // grid rows (e.g. 1-3)
 * @var string|null $class
 * @var bool|null $isGlass // whether to use glass-panel class
 */
$gridClass = "bento-" . ($span ?? 4);
if (!empty($rowSpan)) {
    $gridClass .= " bento-row-" . $rowSpan;
}
$cardStyle = ($isGlass ?? false) ? 'glass-panel' : 'float-card';
?>
<div class="<?= $gridClass ?> <?= $cardStyle ?> <?= $class ?? '' ?>">
    <?= $content ?>
</div>

