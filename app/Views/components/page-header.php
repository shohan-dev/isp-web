<?php
/**
 * Reusable page header with SaaS breadcrumb.
 *
 * @var string      $title
 * @var array       $breadcrumb [['label' => '', 'url' => ''], ...]
 * @var string|null $actions    Trusted HTML for action buttons (not escaped)
 * @var string|null $subtitle
 */
$title = (string) ($title ?? '');
$breadcrumb = is_array($breadcrumb ?? null) ? $breadcrumb : [];
$actions = $actions ?? null;
$subtitle = ($subtitle ?? '') !== '' ? (string) $subtitle : null;
?>
<div class="ipb-page-header fade-in">
  <div class="ipb-page-header-main">
    <?php if ($breadcrumb !== []): ?>
      <nav class="ipb-breadcrumb" aria-label="Breadcrumb">
        <ol>
          <?php foreach ($breadcrumb as $i => $crumb): ?>
            <?php
              $isLast = $i === count($breadcrumb) - 1;
              $label = (string) ($crumb['label'] ?? '');
              $url = $crumb['url'] ?? null;
              $url = ($url !== null && $url !== '') ? (string) $url : null;
            ?>
            <li<?= $isLast ? ' aria-current="page"' : ''; ?>>
              <?php if (!$isLast && $url !== null): ?>
                <a href="<?= esc($url, 'attr') ?>"><?= esc($label) ?></a>
              <?php else: ?>
                <span><?= esc($label) ?></span>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ol>
      </nav>
    <?php endif; ?>
    <h1>
      <?= esc($title) ?>
      <?php if ($subtitle !== null): ?>
        <small><?= esc($subtitle) ?></small>
      <?php endif; ?>
    </h1>
  </div>
  <?php if (is_string($actions) && $actions !== ''): ?>
    <div class="ipb-page-actions"><?= $actions ?></div>
  <?php endif; ?>
</div>
