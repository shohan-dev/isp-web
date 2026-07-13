<?php
/**
 * Skeleton KPI card — first-paint placeholder mirroring stat-card.php's box.
 *
 * @var int $count  How many skeleton cards to emit (default 1)
 */
$count = max(1, (int) ($count ?? 1));
?>
<?php for ($i = 0; $i < $count; $i++): ?>
  <div class="ipb-skeleton-kpi" aria-hidden="true">
    <span class="ipb-skeleton sk-value"></span>
    <span class="ipb-skeleton sk-label"></span>
  </div>
<?php endfor; ?>
