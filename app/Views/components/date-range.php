<?php
/**
 * Date range filter block for list toolbars (from_date / to_date convention).
 *
 * @var string      $fromId      Input id (default from_date)
 * @var string      $toId        Input id (default to_date)
 * @var string      $fromName    POST name (default from_date)
 * @var string      $toName      POST name (default to_date)
 * @var string|null $fromValue   Initial from value (Y-m-d)
 * @var string|null $toValue     Initial to value (Y-m-d)
 * @var string|null $fromLabel   Accessible label for from field
 * @var string|null $toLabel     Accessible label for to field
 * @var bool        $showApply   Show Apply button (default true)
 * @var bool        $showClear   Show Clear button (default true)
 */
$fromId = (string) ($fromId ?? 'from_date');
$toId = (string) ($toId ?? 'to_date');
$fromName = (string) ($fromName ?? 'from_date');
$toName = (string) ($toName ?? 'to_date');
$fromValue = ($fromValue ?? '') !== '' ? (string) $fromValue : '';
$toValue = ($toValue ?? '') !== '' ? (string) $toValue : '';
$fromLabel = (string) ($fromLabel ?? 'From date');
$toLabel = (string) ($toLabel ?? 'To date');
$showApply = (bool) ($showApply ?? true);
$showClear = (bool) ($showClear ?? true);
?>
<div class="ipb-date-range" role="group" aria-label="Date range filter">
  <label class="ipb-date-range-label" for="<?= esc($fromId, 'attr') ?>"><?= esc($fromLabel) ?></label>
  <input
    type="date"
    id="<?= esc($fromId, 'attr') ?>"
    name="<?= esc($fromName, 'attr') ?>"
    class="form-control ipb-filter-date"
    aria-label="<?= esc($fromLabel, 'attr') ?>"
    value="<?= esc($fromValue, 'attr') ?>"
  >
  <label class="ipb-date-range-label" for="<?= esc($toId, 'attr') ?>"><?= esc($toLabel) ?></label>
  <input
    type="date"
    id="<?= esc($toId, 'attr') ?>"
    name="<?= esc($toName, 'attr') ?>"
    class="form-control ipb-filter-date"
    aria-label="<?= esc($toLabel, 'attr') ?>"
    value="<?= esc($toValue, 'attr') ?>"
  >
  <?php if ($showClear): ?>
    <button type="button" class="ipb-date-range-clear btn btn-default btn-sm" aria-label="Clear date range">
      <i class="fa fa-times" aria-hidden="true"></i> Clear
    </button>
  <?php endif; ?>
  <?php if ($showApply): ?>
    <button type="button" class="ipb-date-range-apply btn btn-primary btn-sm" aria-label="Apply date range">
      <i class="fa fa-check" aria-hidden="true"></i> Apply
    </button>
  <?php endif; ?>
</div>
