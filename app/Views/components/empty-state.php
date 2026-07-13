<?php
/**
 * Empty / error state block.
 *
 * @var string      $title
 * @var string|null $subtitle
 * @var string      $icon     Font Awesome class list (escaped as attr)
 * @var string|null $action   Trusted HTML (not escaped) — e.g. a retry/create button
 * @var string      $variant  'empty' (default) | 'error'
 */
$variant = $variant ?? 'empty';
$variant = in_array($variant, ['empty', 'error'], true) ? $variant : 'empty';
$isError = $variant === 'error';
$title = (string) ($title ?? ($isError ? 'Something went wrong' : 'Nothing here yet'));
$subtitle = isset($subtitle) && $subtitle !== '' ? (string) $subtitle
    : ($isError ? 'We could not load this. Check your connection and try again.' : null);
$icon = (string) ($icon ?? ($isError ? 'fa fa-triangle-exclamation' : 'fa fa-inbox'));
$action = $action ?? null;
?>
<div class="ipb-empty<?= $isError ? ' is-error' : '' ?>"<?= $isError ? ' role="alert"' : '' ?>>
  <div class="ipb-empty-icon"><i class="<?= esc($icon, 'attr') ?>" aria-hidden="true"></i></div>
  <div class="ipb-empty-title"><?= esc($title) ?></div>
  <?php if ($subtitle !== null): ?>
    <div class="ipb-empty-sub"><?= esc($subtitle) ?></div>
  <?php endif; ?>
  <?php if (is_string($action) && $action !== ''): ?>
    <div class="ipb-empty-action"><?= $action ?></div>
  <?php endif; ?>
</div>
