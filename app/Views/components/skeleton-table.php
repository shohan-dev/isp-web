<?php
/**
 * Skeleton table body — server-rendered first-paint placeholder for a list table.
 * Drop inside your <table class="table datatable"> in place of an empty <tbody>.
 * DataTables replaces it on its first draw, so no JS teardown is needed.
 *
 * @var int         $cols   Number of columns (must match the real <thead>)
 * @var int         $rows   Number of skeleton rows to render (default 8)
 * @var string|null $id     Optional id on the <tbody>
 */
$cols = max(1, (int) ($cols ?? 5));
$rows = max(1, (int) ($rows ?? 8));
$id   = ($id ?? '') !== '' ? (string) $id : null;
// varied widths so it reads as content, not a grid of identical bars
$widths = [70, 55, 62, 48, 66, 52, 58, 60, 45, 64];
?>
<tbody<?= $id !== null ? ' id="' . esc($id, 'attr') . '"' : '' ?> class="ipb-skeleton-tbody" aria-hidden="true">
  <?php for ($r = 0; $r < $rows; $r++): ?>
    <tr class="ipb-skeleton-row">
      <?php for ($c = 0; $c < $cols; $c++): ?>
        <td><span class="ipb-skeleton ipb-skeleton-text" style="width: <?= (int) $widths[($r + $c) % count($widths)] ?>%"></span></td>
      <?php endfor; ?>
    </tr>
  <?php endfor; ?>
</tbody>
