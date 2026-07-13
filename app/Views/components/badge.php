<?php
/**
 * Status badge.
 *
 * @var string $label
 * @var string $tone  success|warning|error|info|neutral|brand
 * @var bool   $dot
 */
$label = (string) ($label ?? '');
$tone = (string) ($tone ?? 'neutral');
$dot = !empty($dot);
$map = [
  'success' => 'label-success',
  'warning' => 'label-warning',
  'error' => 'label-danger',
  'info' => 'label-info',
  'brand' => 'ipb-badge-brand',
  'neutral' => 'label-default',
];
$class = $map[$tone] ?? 'label-default';
?>
<span class="label <?= esc($class, 'attr') ?><?= $dot ? ' ipb-badge-dot' : '' ?>"><?= esc($label) ?></span>
