<?= $this->extend('layout/main-layout'); ?>

<?= $this->section('content'); ?>

<?= saas_css('areas.css') ?>

<div class="content-wrapper">
  <section class="content ipb-areas-page">

    <?= $this->include('components/page-header', [
      'title' => 'Service Area',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Service Area'],
      ],
    ]); ?>

    <div class="ipb-areas-stats">
      <div id="statCardTotalAreas">
        <?= view('components/stat-card', [
          'label' => 'Total Areas',
          'value' => (int) ($stats['total_areas'] ?? 0),
          'icon' => 'fa fa-map',
          'tone' => 'navy',
        ]); ?>
      </div>
      <div id="statCardActiveAreas">
        <?= view('components/stat-card', [
          'label' => 'Active Areas',
          'value' => (int) ($stats['active_areas'] ?? 0),
          'icon' => 'fa fa-circle-check',
          'tone' => 'success',
        ]); ?>
      </div>
      <div id="statCardTotalSubareas">
        <?= view('components/stat-card', [
          'label' => 'Total Sub-areas',
          'value' => (int) ($stats['total_sub_areas'] ?? 0),
          'icon' => 'fa fa-sitemap',
          'tone' => 'info',
        ]); ?>
      </div>
      <div id="statCardAreasWithoutSubareas">
        <?= view('components/stat-card', [
          'label' => 'Areas without Sub-areas',
          'value' => (int) ($stats['areas_without_subareas'] ?? 0),
          'icon' => 'fa fa-triangle-exclamation',
          'tone' => ((int) ($stats['areas_without_subareas'] ?? 0) > 0) ? 'warning' : 'brand',
        ]); ?>
      </div>
    </div>

    <div class="box box-warning">
      <div class="box-header with-border ipb-box-toolbar">
        <?php
          ob_start();
          if (userHasPermission('area', 'create')): ?>
              <button type="button" class="btn btn-primary" id="btnNewArea">
                <i class="fa fa-plus" aria-hidden="true"></i> New Area
              </button>
          <?php endif;
          $areaActionsHtml = ob_get_clean();

          echo view('components/list-toolbar', [
              'filters' => [
                  [
                      'id' => 'filter-area-search',
                      'type' => 'text',
                      'ariaLabel' => 'Search areas by name or code',
                      'placeholder' => 'Search by name or code…',
                  ],
                  [
                      'id' => 'filter-area-status',
                      'type' => 'select',
                      'ariaLabel' => 'Filter by status',
                      'emptyLabel' => 'All Status',
                      'options' => [
                          ['value' => 'active', 'label' => 'Active'],
                          ['value' => 'inactive', 'label' => 'Inactive'],
                      ],
                  ],
              ],
              'actionsHtml' =>
                  '<button type="button" class="ipb-areas-drawer-trigger" id="areasTreeDrawerTrigger" aria-expanded="false" aria-controls="areasTreePanel">'
                  . '<i class="fa fa-list" aria-hidden="true"></i> Areas <span class="ipb-count-badge" id="areasTreeDrawerCount">0</span>'
                  . '</button>'
                  . $areaActionsHtml,
              'filterLabel' => 'Filter',
              'manualBind' => true,
              'showReset' => false,
              'showCount' => false,
          ]);
        ?>
      </div>

      <div class="box-body">
        <div class="ipb-areas-workspace">
          <div class="ipb-areas-tree-backdrop" id="areasTreeBackdrop" aria-hidden="true"></div>

          <nav class="ipb-areas-tree-panel" id="areasTreePanel" aria-label="Service areas">
            <div class="ipb-areas-tree-head">
              <span>Areas</span>
              <button type="button" class="ipb-areas-tree-close" id="areasTreeCloseBtn" aria-label="Close areas list">
                <i class="fa fa-times" aria-hidden="true"></i>
              </button>
            </div>
            <div class="ipb-areas-tree-scroll" id="areasTreeScroll"></div>
          </nav>

          <div class="ipb-areas-detail-panel" id="areasDetailPanel">
            <div class="ipb-detail-placeholder">
              <i class="fa fa-diagram-project" aria-hidden="true"></i>
              <p>Select an area from the list to view its details and sub-areas.</p>
            </div>
          </div>
        </div>
      </div>
    </div>

  </section>
</div>

<!-- Hidden templates consumed by areas.js -->
<template id="areasEmptyStateTpl">
  <?= view('components/empty-state', [
    'title' => 'No service areas yet',
    'subtitle' => 'Create your first service area to start organizing sub-areas and customers.',
    'icon' => 'fa fa-map',
    'action' => userHasPermission('area', 'create')
      ? '<button type="button" class="btn btn-primary" onclick="document.getElementById(\'btnNewArea\').click()"><i class="fa fa-plus" aria-hidden="true"></i> New Area</button>'
      : null,
  ]); ?>
</template>

<!-- ── Area create drawer ─────────────────────────────────────────────── -->
<?php if (userHasPermission('area', 'create')): ?>
<aside class="ipb-drawer-panel" id="drawerAreaCreate" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="drawerAreaCreateTitle">
  <div class="ipb-drawer-header">
    <div>
      <div class="ipb-drawer-title" id="drawerAreaCreateTitle">New Area</div>
      <p class="ipb-drawer-sub">Add a new service area</p>
    </div>
    <button type="button" class="ipb-icon-btn" data-ipb-drawer-close aria-label="Close">
      <i class="fa fa-times" aria-hidden="true"></i>
    </button>
  </div>
  <div class="ipb-drawer-body">
    <form id="formAreaCreate" class="ipb-drawer-form">
      <div class="form-group">
        <label for="areaCreateName">Area Name</label>
        <input type="text" id="areaCreateName" name="area_name" class="form-control">
        <small id="area_name-error" class="error text-danger"></small>
      </div>
      <div class="form-group">
        <label for="areaCreateCode">Area Code</label>
        <input type="text" id="areaCreateCode" name="area_code" class="form-control">
        <small id="area_code-error" class="error text-danger"></small>
      </div>
      <div class="form-group">
        <label>Status</label>
        <div class="radio">
          <label class="radio-inline"><input type="radio" name="status" value="active" checked> Active</label>
          <label class="radio-inline"><input type="radio" name="status" value="inactive"> Inactive</label>
        </div>
        <small id="status-error" class="error text-danger"></small>
      </div>
      <button type="submit" class="btn btn-warning">Add Area</button>
    </form>
  </div>
</aside>
<?php endif; ?>

<!-- ── Area edit drawer ───────────────────────────────────────────────── -->
<?php if (userHasPermission('area', 'update')): ?>
<aside class="ipb-drawer-panel" id="drawerAreaEdit" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="drawerAreaEditTitle">
  <div class="ipb-drawer-header">
    <div>
      <div class="ipb-drawer-title" id="drawerAreaEditTitle">Edit Area</div>
      <p class="ipb-drawer-sub">Update service area details</p>
    </div>
    <button type="button" class="ipb-icon-btn" data-ipb-drawer-close aria-label="Close">
      <i class="fa fa-times" aria-hidden="true"></i>
    </button>
  </div>
  <div class="ipb-drawer-body">
    <form id="formAreaEdit" class="ipb-drawer-form">
      <div class="form-group">
        <label for="areaEditName">Area Name</label>
        <input type="text" id="areaEditName" name="area_name" class="form-control">
        <small id="area_name-error" class="error text-danger"></small>
      </div>
      <div class="form-group">
        <label for="areaEditCode">Area Code</label>
        <input type="text" id="areaEditCode" name="area_code" class="form-control">
        <small id="area_code-error" class="error text-danger"></small>
      </div>
      <div class="form-group">
        <label>Status</label>
        <div class="radio">
          <label class="radio-inline"><input type="radio" name="status" value="active"> Active</label>
          <label class="radio-inline"><input type="radio" name="status" value="inactive"> Inactive</label>
        </div>
        <small id="status-error" class="error text-danger"></small>
      </div>
      <button type="submit" class="btn btn-warning">Update</button>
    </form>
  </div>
</aside>
<?php endif; ?>

<!-- ── Sub-area create drawer ─────────────────────────────────────────── -->
<?php if (userHasPermission('area', 'create')): ?>
<aside class="ipb-drawer-panel" id="drawerSubareaCreate" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="drawerSubareaCreateTitle">
  <div class="ipb-drawer-header">
    <div>
      <div class="ipb-drawer-title" id="drawerSubareaCreateTitle">New Sub-area</div>
      <p class="ipb-drawer-sub">Add a sub-area under the selected area</p>
    </div>
    <button type="button" class="ipb-icon-btn" data-ipb-drawer-close aria-label="Close">
      <i class="fa fa-times" aria-hidden="true"></i>
    </button>
  </div>
  <div class="ipb-drawer-body">
    <form id="formSubareaCreate" class="ipb-drawer-form">
      <div class="form-group">
        <label for="subareaCreateName">Sub-area Name</label>
        <input type="text" id="subareaCreateName" name="area_name" class="form-control">
        <small id="area_name-error" class="error text-danger"></small>
      </div>
      <div class="form-group">
        <label for="subareaCreateCode">Sub-area Code</label>
        <input type="text" id="subareaCreateCode" name="area_code" class="form-control">
        <small id="area_code-error" class="error text-danger"></small>
      </div>
      <div class="form-group">
        <label>Status</label>
        <div class="radio">
          <label class="radio-inline"><input type="radio" name="status" value="active" checked> Active</label>
          <label class="radio-inline"><input type="radio" name="status" value="inactive"> Inactive</label>
        </div>
        <small id="status-error" class="error text-danger"></small>
      </div>
      <button type="submit" class="btn btn-warning">Add Sub-area</button>
    </form>
  </div>
</aside>
<?php endif; ?>

<!-- ── Sub-area edit drawer ───────────────────────────────────────────── -->
<?php if (userHasPermission('area', 'update')): ?>
<aside class="ipb-drawer-panel" id="drawerSubareaEdit" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="drawerSubareaEditTitle">
  <div class="ipb-drawer-header">
    <div>
      <div class="ipb-drawer-title" id="drawerSubareaEditTitle">Edit Sub-area</div>
      <p class="ipb-drawer-sub">Update sub-area details</p>
    </div>
    <button type="button" class="ipb-icon-btn" data-ipb-drawer-close aria-label="Close">
      <i class="fa fa-times" aria-hidden="true"></i>
    </button>
  </div>
  <div class="ipb-drawer-body">
    <form id="formSubareaEdit" class="ipb-drawer-form">
      <div class="form-group">
        <label for="subareaEditName">Sub-area Name</label>
        <input type="text" id="subareaEditName" name="area_name" class="form-control">
        <small id="area_name-error" class="error text-danger"></small>
      </div>
      <div class="form-group">
        <label for="subareaEditCode">Sub-area Code</label>
        <input type="text" id="subareaEditCode" name="area_code" class="form-control">
        <small id="area_code-error" class="error text-danger"></small>
      </div>
      <div class="form-group">
        <label>Status</label>
        <div class="radio">
          <label class="radio-inline"><input type="radio" name="status" value="active"> Active</label>
          <label class="radio-inline"><input type="radio" name="status" value="inactive"> Inactive</label>
        </div>
        <small id="status-error" class="error text-danger"></small>
      </div>
      <button type="submit" class="btn btn-warning">Update</button>
    </form>
  </div>
</aside>
<?php endif; ?>

<?= $this->endSection('content'); ?>

<?= $this->section('script'); ?>

<?= saas_js('areas.js') ?>
<script>
  window.IpbAreasConfig = <?= json_encode([
    'areas' => $areas,
    'stats' => $stats,
    'preselectAreaId' => $preselectAreaId,
    'canCreate' => userHasPermission('area', 'create'),
    'canUpdate' => userHasPermission('area', 'update'),
    'canDelete' => userHasPermission('area', 'delete'),
    'csrfName' => csrf_token(),
    'csrfHash' => csrf_hash(),
    'csrfHeader' => csrf_header(),
    'urls' => [
      'tree' => route_to('route.area.tree'),
      'subtree' => str_replace('918273645', '__ID__', route_to('route.area.subtree', 918273645)),
      'areaCreate' => route_to('route.area.create'),
      'areaUpdate' => str_replace('918273645', '__ID__', route_to('route.area.update', 918273645)),
      'areaDelete' => route_to('route.area.delete'),
      'subareaCreate' => route_to('route.subarea.create'),
      'subareaUpdate' => str_replace('918273645', '__ID__', route_to('route.subarea.update', 918273645)),
      'subareaDelete' => route_to('route.subarea.delete'),
    ],
  ], JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) ?>;
</script>

<?= $this->endSection('script'); ?>
