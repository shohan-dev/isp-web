<?php
/**
 * Config-driven list toolbar (.ipb-list-toolbar contract).
 *
 * @var array       $filters      [{id, type: select|date|text, label?, ariaLabel, emptyLabel?, options?, optgroups?, value?, name?, placeholder?}]
 * @var string|null $actionsHtml  Trusted HTML for action buttons (permission-gated by caller)
 * @var bool        $searchable   Reserved — DataTables search stays in table wrapper
 * @var bool        $showReset    Show reset button (default true)
 * @var bool        $showCount    Show result count badge (default true)
 * @var string|null $toolbarId    Optional id on root toolbar
 * @var string|null $filtersBarId Optional id on filters container
 * @var string|null $filterLabel  Toolbar filter heading (default "Filter")
 * @var string|null $resetId      Optional id on reset button
 * @var string|null $countId      Optional id on count badge
 * @var string|null $storageKey   sessionStorage key for IpbFilters (optional)
 * @var bool        $manualBind   Skip global IpbFilters.autoInit when true
 */
$filters = is_array($filters ?? null) ? $filters : [];
$actionsHtml = $actionsHtml ?? null;
$searchable = (bool) ($searchable ?? false);
$showReset = (bool) ($showReset ?? true);
$showCount = (bool) ($showCount ?? true);
$toolbarId = ($toolbarId ?? '') !== '' ? (string) $toolbarId : null;
$filtersBarId = ($filtersBarId ?? '') !== '' ? (string) $filtersBarId : null;
$filterLabel = (string) ($filterLabel ?? 'Filter');
$resetId = ($resetId ?? '') !== '' ? (string) $resetId : null;
$countId = ($countId ?? '') !== '' ? (string) $countId : null;
$storageKey = ($storageKey ?? '') !== '' ? (string) $storageKey : null;
$manualBind = (bool) ($manualBind ?? false);
$toolbarAttrs = '';
if ($toolbarId !== null) {
    $toolbarAttrs .= ' id="' . esc($toolbarId, 'attr') . '"';
}
if ($storageKey !== null) {
    $toolbarAttrs .= ' data-ipb-storage-key="' . esc($storageKey, 'attr') . '"';
}
if ($manualBind) {
    $toolbarAttrs .= ' data-ipb-manual="1"';
}
?>
<div class="ipb-list-toolbar"<?= $toolbarAttrs ?>>
  <div class="ipb-list-toolbar-filters"<?= $filtersBarId !== null ? ' id="' . esc($filtersBarId, 'attr') . '"' : ''; ?>>
    <span class="ipb-filter-label filter-label">
      <i class="fa fa-filter" aria-hidden="true"></i> <?= esc($filterLabel) ?>
    </span>

    <?php foreach ($filters as $filter): ?>
      <?php
        $type = (string) ($filter['type'] ?? 'select');
        $id = (string) ($filter['id'] ?? '');
        if ($id === '') {
            continue;
        }
        $ariaLabel = (string) ($filter['ariaLabel'] ?? $filter['label'] ?? $id);
        $name = (string) ($filter['name'] ?? $id);
        $value = $filter['value'] ?? '';
        $value = $value !== null && $value !== '' ? (string) $value : '';
      ?>
      <?php if ($type === 'date'): ?>
        <input
          type="date"
          id="<?= esc($id, 'attr') ?>"
          name="<?= esc($name, 'attr') ?>"
          class="form-control ipb-filter-date"
          aria-label="<?= esc($ariaLabel, 'attr') ?>"
          value="<?= esc($value, 'attr') ?>"
        >
      <?php elseif ($type === 'text'): ?>
        <input
          type="text"
          id="<?= esc($id, 'attr') ?>"
          name="<?= esc($name, 'attr') ?>"
          class="form-control ipb-filter-text"
          aria-label="<?= esc($ariaLabel, 'attr') ?>"
          placeholder="<?= esc((string) ($filter['placeholder'] ?? ''), 'attr') ?>"
          value="<?= esc($value, 'attr') ?>"
        >
      <?php else: ?>
        <select
          id="<?= esc($id, 'attr') ?>"
          name="<?= esc($name, 'attr') ?>"
          class="form-control ipb-filter-select"
          aria-label="<?= esc($ariaLabel, 'attr') ?>"
        >
          <?php if (isset($filter['emptyLabel'])): ?>
            <option value=""><?= esc((string) $filter['emptyLabel']) ?></option>
          <?php endif; ?>
          <?php
            $options = is_array($filter['options'] ?? null) ? $filter['options'] : [];
            foreach ($options as $opt):
              $optValue = (string) ($opt['value'] ?? '');
              $optLabel = (string) ($opt['label'] ?? $optValue);
          ?>
            <option value="<?= esc($optValue, 'attr') ?>"><?= esc($optLabel) ?></option>
          <?php endforeach; ?>
          <?php
            $optgroups = is_array($filter['optgroups'] ?? null) ? $filter['optgroups'] : [];
            foreach ($optgroups as $group):
              $groupLabel = (string) ($group['label'] ?? '');
              $groupOptions = is_array($group['options'] ?? null) ? $group['options'] : [];
          ?>
            <optgroup label="<?= esc($groupLabel, 'attr') ?>">
              <?php foreach ($groupOptions as $opt):
                $optValue = (string) ($opt['value'] ?? '');
                $optLabel = (string) ($opt['label'] ?? $optValue);
              ?>
                <option value="<?= esc($optValue, 'attr') ?>"><?= esc($optLabel) ?></option>
              <?php endforeach; ?>
            </optgroup>
          <?php endforeach; ?>
        </select>
      <?php endif; ?>
    <?php endforeach; ?>

    <?php if ($showReset): ?>
      <button type="button" class="ipb-filter-reset"<?= $resetId !== null ? ' id="' . esc($resetId, 'attr') . '"' : ''; ?> style="display:none;" aria-label="Clear filters">
        <i class="fa fa-times" aria-hidden="true"></i> Reset
      </button>
    <?php endif; ?>

    <?php if ($showCount): ?>
      <span class="ipb-filter-count"<?= $countId !== null ? ' id="' . esc($countId, 'attr') . '"' : ''; ?> style="display:none;" aria-live="polite"></span>
    <?php endif; ?>
  </div>

  <?php if (is_string($actionsHtml) && $actionsHtml !== ''): ?>
    <div class="ipb-list-toolbar-actions" id="action-buttons-group">
      <?= $actionsHtml ?>
    </div>
  <?php endif; ?>
</div>
