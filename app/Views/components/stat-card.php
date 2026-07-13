<?php
/**
 * KPI / stat card.
 *
 * @var string           $label
 * @var string|int|float $value
 * @var string           $icon   Font Awesome class e.g. 'fa fa-users'
 * @var string           $tone   brand|success|warning|error|info|navy
 * @var string|null      $href
 * @var string|null      $footer Trusted HTML when no href (not escaped)
 */
$label = (string) ($label ?? '');
$value = (string) ($value ?? '0');
$icon = (string) ($icon ?? 'fa fa-chart-simple');
$tone = (string) ($tone ?? 'brand');
$href = isset($href) && $href !== '' ? (string) $href : null;
$footer = $footer ?? null;
$toneClass = in_array($tone, ['success', 'warning', 'error', 'info', 'navy'], true) ? 'tone-' . $tone : '';
?>
<div class="ipb-stat-card <?= esc($toneClass, 'attr') ?>">
  <div class="ipb-stat-icon"><i class="<?= esc($icon, 'attr') ?>" aria-hidden="true"></i></div>
  <div class="ipb-stat-value"><?= esc($value) ?></div>
  <div class="ipb-stat-label"><?= esc($label) ?></div>
  <?php if ($href !== null): ?>
    <a href="<?= esc($href, 'attr') ?>" class="small-box-footer ipb-stat-link">
      View details <i class="fa fa-arrow-right" aria-hidden="true"></i>
    </a>
  <?php elseif (is_string($footer) && $footer !== ''): ?>
    <div class="ipb-stat-footer"><?= $footer ?></div>
  <?php endif; ?>
</div>
