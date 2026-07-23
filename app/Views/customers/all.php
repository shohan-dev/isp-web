<?= $this->extend('layout/main-layout'); ?>
<?php $this->section('needsDataTable'); ?>1<?php $this->endSection(); ?>
<?php
$areasForFilter = $areasForFilter ?? [];
$packagesForFilter = $packagesForFilter ?? [];

// Bulk actions (SMS / recharge / transfer / change-router / delete) all need
// row checkboxes. Transfer is always in the More menu, so the select column
// must always render — gating it on delete-only left those other actions
// permanently stuck on "Please select at least one customer."
$needsBulkSelect = true;

$areaFilterOptions = [];
foreach ($areasForFilter as $a) {
    $areaFilterOptions[] = ['value' => (string) $a->id, 'label' => (string) $a->area_name];
}

$packageFilterOptions = [];
foreach ($packagesForFilter as $p) {
    $packageFilterOptions[] = ['value' => (string) $p->id, 'label' => (string) $p->package_name];
}

$customerListFilters = [
    [
        'id' => 'filter-area',
        'type' => 'select',
        'ariaLabel' => 'Filter by area',
        'emptyLabel' => 'All Areas',
        'options' => $areaFilterOptions,
    ],
    [
        'id' => 'filter-package',
        'type' => 'select',
        'ariaLabel' => 'Filter by package',
        'emptyLabel' => 'All Packages',
        'options' => $packageFilterOptions,
    ],
    [
        'id' => 'filter-connection-status',
        'type' => 'select',
        'ariaLabel' => 'Filter by connection status',
        'emptyLabel' => 'All Status',
        'options' => [
            ['value' => 'active', 'label' => 'Online'],
            ['value' => 'inactive', 'label' => 'Offline'],
        ],
    ],
    [
        'id' => 'filter-acc-status',
        'type' => 'select',
        'ariaLabel' => 'Filter by account status',
        'emptyLabel' => 'All Acc Status',
        'options' => [
            ['value' => 'conn', 'label' => 'Connected'],
            ['value' => 'disconn', 'label' => 'Disconnected'],
        ],
    ],
    [
        'id' => 'filter-expiry',
        'type' => 'select',
        'ariaLabel' => 'Filter by expiry',
        'emptyLabel' => 'All Expiry',
        'optgroups' => [
            [
                'label' => 'Expired',
                'options' => [
                    ['value' => 'expired_today', 'label' => 'Expired Today'],
                    ['value' => 'expired_yesterday', 'label' => 'Expired Yesterday'],
                    ['value' => 'expired_7', 'label' => 'Expired (Last 7 Days)'],
                ],
            ],
            [
                'label' => 'Due Soon',
                'options' => [
                    ['value' => 'due_today', 'label' => 'Due Today'],
                    ['value' => 'due_tomorrow', 'label' => 'Due Tomorrow'],
                    ['value' => 'due_3', 'label' => 'Due (Next 3 Days)'],
                    ['value' => 'due_5', 'label' => 'Due (Next 5 Days)'],
                    ['value' => 'due_7', 'label' => 'Due (Next 7 Days)'],
                ],
            ],
            [
                'label' => 'Active',
                'options' => [
                    ['value' => 'paid_1', 'label' => 'Paid (1+ Days left)'],
                    ['value' => 'paid_3', 'label' => 'Paid (3+ Days left)'],
                    ['value' => 'paid_7', 'label' => 'Paid (7+ Days left)'],
                ],
            ],
        ],
    ],
];

ob_start();
?>
            <?php if (userHasPermission('customer', 'create')): ?>
              <?php /* Real href, not javascript:void(0) — this button previously did
                 nothing unless a chain of JS (jQuery loaded, ready handler ran,
                 reached this binding, AJAX succeeded, response parsed) all completed.
                 If any link in that chain broke, the click was a silent no-op. With a
                 real href, a plain click — or any failure in the JS below — still
                 navigates via the browser's native link behavior instead of doing
                 nothing. */ ?>
              <a href="<?= route_to('route.customer.new'); ?>" class="new-customer-btn ipb-tool-btn is-primary" title="New Customer" aria-label="New Customer">
                <i class="fa fa-user-plus" aria-hidden="true"></i>
                <span class="ipb-tool-label">New</span>
              </a>
            <?php endif; ?>

            <div class="ipb-col-picker" id="ipbColPicker">
              <button type="button" class="ipb-tool-btn is-ghost" id="ipbColPickerBtn" title="Choose columns" aria-haspopup="true" aria-expanded="false">
                <i class="fa fa-columns" aria-hidden="true"></i>
                <span class="ipb-tool-label">Columns</span>
              </button>
              <div class="ipb-col-picker-panel" id="ipbColPickerPanel" hidden>
                <div class="ipb-col-picker-head">
                  <strong>Show columns</strong>
                  <button type="button" class="ipb-col-picker-reset" id="ipbColPickerReset">Reset</button>
                </div>
                <div class="ipb-col-picker-list" id="ipbColPickerList"></div>
                <p class="ipb-col-picker-note">Saved on this device only</p>
              </div>
            </div>

            <a href="<?= route_to('route.customer.export'); ?>" class="ipb-tool-btn is-ghost" title="Download Excel" aria-label="Download Excel">
              <i class="fa fa-download" aria-hidden="true"></i>
              <span class="ipb-tool-label">Export</span>
            </a>

            <div class="ipb-cust-more" id="ipbCustMore">
              <button type="button" class="ipb-tool-btn is-ghost ipb-cust-more-btn" id="ipbCustMoreBtn" aria-haspopup="true" aria-expanded="false" title="More actions" aria-label="More actions">
                <i class="fa fa-ellipsis-v" aria-hidden="true"></i>
              </button>
              <div class="ipb-cust-more-menu" id="ipbCustMoreMenu" hidden>
                <?php /* JS-only actions use <button>, not <a href="javascript:void(0)">.
                   void(0) is a silent no-op when the click handler fails to bind or throws —
                   the click looks dead with no navigation fallback. Buttons have no href,
                   so they never pretend to be links. Import Excel keeps a real href so a
                   plain click still navigates if its AJAX guard fails. */ ?>
                <?php if (userHasPermission('sms_message')): ?>
                  <button type="button" id="sms-choice-btn" class="ipb-cust-more-item">
                    <i class="fa fa-envelope" aria-hidden="true"></i><span>Send SMS / Voice</span>
                  </button>
                <?php endif; ?>
                <?php if (getSession('user_role') === 'resellerAdmin' || getSession('user_role') === 'admin'): ?>
                  <button type="button" id="updatePppoeBtn" class="ipb-cust-more-item">
                    <i class="fa fa-sync" aria-hidden="true"></i><span>Update PPPoE IDs</span>
                  </button>
                  <button type="button" id="change-router-btn" class="ipb-cust-more-item">
                    <i class="fa fa-server" aria-hidden="true"></i><span>Change router</span>
                  </button>
                <?php endif; ?>
                <?php if (userHasPermission('customer', 'create')): ?>
                  <a href="<?= route_to('route.customer.excel_index'); ?>" class="import-excel-button ipb-cust-more-item">
                    <i class="fa fa-file-excel" aria-hidden="true"></i><span>Import Excel</span>
                  </a>
                  <button type="button" class="update-all-btn ipb-cust-more-item">
                    <i class="fa fa-pen-to-square" aria-hidden="true"></i><span>Recharge all</span>
                  </button>
                <?php endif; ?>
                <button type="button" class="customer-transfer-button ipb-cust-more-item">
                  <i class="fa fa-right-left" aria-hidden="true"></i><span>Transfer</span>
                </button>
                <?php if (userHasPermission('customer', 'delete')): ?>
                  <button type="button" class="delete-btn ipb-cust-more-item is-danger">
                    <i class="fa fa-trash" aria-hidden="true"></i><span>Delete selected</span>
                  </button>
                <?php endif; ?>
              </div>
            </div>
<?php
$customerListActionsHtml = ob_get_clean();

// 06 §9 — domain empty states for the DataTables emptyTable/zeroRecords language overrides.
$customerAddAction = userHasPermission('customer', 'create')
    ? '<a href="' . esc(route_to('route.customer.new'), 'attr') . '" class="btn btn-primary btn-sm"><i class="fa fa-user-plus" aria-hidden="true"></i> Add customer</a>'
    : '';
$customerEmptyHtml = '<div class="ipb-empty ipb-dt-empty"><div class="ipb-empty-icon"><i class="fa fa-users" aria-hidden="true"></i></div>'
    . '<div class="ipb-empty-title">No subscribers yet</div>'
    . '<div class="ipb-empty-sub">Add your first customer to start provisioning and billing.</div>'
    . ($customerAddAction !== '' ? '<div class="ipb-empty-action">' . $customerAddAction . '</div>' : '')
    . '</div>';
$customerZeroHtml = '<div class="ipb-empty ipb-dt-empty"><div class="ipb-empty-icon"><i class="fa fa-filter" aria-hidden="true"></i></div>'
    . '<div class="ipb-empty-title">Nothing matches these filters</div>'
    . '<div class="ipb-empty-sub">Your filters are hiding everything. Clear them to see the full list.</div>'
    . '<div class="ipb-empty-action"><button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById(\'filter-reset-btn\').click()">Clear filters</button></div>'
    . '</div>';
?>

<?= $this->section('content'); ?>

<div class="content-wrapper ipb-customers-page">
  <section class="content">
   <div class="ipb-page">

    <?= $this->include('components/page-header', [
      'title' => 'Customers',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Customers'],
      ],
    ]); ?>

    <div class="ipb-page-body">
    <div class="box box-warning">

      <div class="box-header with-border ipb-box-toolbar">
        <?= view('components/list-toolbar', [
          'toolbarId' => 'header-flex-row',
          'filtersBarId' => 'customer-filter-bar',
          'filters' => $customerListFilters,
          'actionsHtml' => $customerListActionsHtml,
          'showReset' => true,
          'showCount' => true,
          'resetId' => 'filter-reset-btn',
          'countId' => 'filter-count-badge',
          'storageKey' => 'ipb_filters_customers_all',
          'manualBind' => true,
        ]); ?>
      </div>



      <div class="ipb-loading-overlay" id="circularLoader">
        <div class="ipb-loading-overlay-inner">
          <span class="ipb-spinner ipb-spinner--lg" aria-hidden="true"></span>
          <span class="ipb-loading-overlay-label">Updating…</span>
        </div>
      </div>
      <div class="box-body">
        <div class="table-responsive ipb-customers-table-wrap">
          <table class="table table-bordered table-striped datatable ipb-stagger" id="customersTable">
            <caption class="sr-only">Customer list</caption>
            <thead class="text-nowrap">
              <tr>
                <?php if ($needsBulkSelect): ?>
                  <th scope="col" data-data="select" data-col="select" data-col-locked="1">
                    <input type="checkbox" class="form-check-input" id="select_all" aria-label="Select all">
                  </th>
                <?php endif; ?>

                <th scope="col" data-data="id" data-name="users.id" data-col="id" data-col-label="C.ID">C.id</th>
                <th scope="col" data-data="name" data-name="users.name" data-col="name" data-col-label="Customer" data-col-locked="1">Customer</th>
                <th scope="col" data-data="package" data-name="users.package_id" data-col="package" data-col-label="Package">Package</th>
                <th scope="col" data-data="area_name" data-name="areas.area_name" data-col="area_name" data-col-label="Area">Area</th>
                <th scope="col" data-data="mobile" data-name="users.mobile" data-col="mobile" data-col-label="Mobile">Mobile</th>
                <th scope="col" data-data="address" data-name="users.address" data-col="address" data-col-label="Address">Address</th>
                <th scope="col" data-data="router_name" data-name="routers.name" data-col="router_name" data-col-label="Router">Router</th>
                <th scope="col" data-data="pppoe_secret" data-name="user_router_data.pppoe_secret" data-col="pppoe_secret" data-col-label="PPPoE Secret">PPPoE Secret</th>
                <th scope="col" data-data="router_password" data-name="user_router_data.router_password" data-col="router_password" data-col-label="Password">Password</th>
                <th scope="col" data-data="payment_expiry_sort" data-name="users.will_expire" data-col="payment" data-col-label="Payment">Payment</th>
                <th scope="col" data-data="conn_status" data-name="users.activity" data-col="conn_status" data-col-label="Status">Status</th>
                <th scope="col" data-data="acc_status" data-name="users.status" data-col="acc_status" data-col-label="Acc. Status">Acc. Status</th>
                <th scope="col" data-data="action" data-col="action" data-col-label="Action" data-col-locked="1">Action</th>
              </tr>
            </thead>
            <?php
              // 04 §4 — zero-blank-frame first paint: skeleton rows show before
              // JS/DataTables boots; DataTables replaces this <tbody> on its first draw.
              // Uses the view() helper, not $this->include() — the latter is View::include(),
              // whose 2nd param is $options (cache/debug flags), not view data, so cols/rows
              // silently never reached the component and it fell back to its default of 5.
              $customersSkeletonCols = 13 + ($needsBulkSelect ? 1 : 0);
            ?>
            <?= view('components/skeleton-table', ['cols' => $customersSkeletonCols, 'rows' => 8]) ?>
          </table>
        </div>
      </div>

    </div>
    </div>
   </div>
  </section>
</div>

<?= $this->endSection('content'); ?>
<?= $this->section('css'); ?>
<?= saas_css('customers-list.css') ?>
<style>
  /* Action-specific SweetAlert button accents only (SMS / router / voice).
     Base modal / cancel / danger skin lives in overrides.css — do not
     re-declare .swal-button--cancel as an absolute X here; that broke the
     delete confirmation "No" button layout. */
  .swal-button--sms {
    background: var(--info-600, #2563eb) !important;
    color: #fff !important;
  }
  .swal-button--sms:hover {
    background: color-mix(in srgb, var(--info-600, #2563eb) 88%, #000) !important;
  }
  .swal-button--router {
    background: var(--primary-500) !important;
    color: #fff !important;
  }
  .swal-button--router:hover {
    background: color-mix(in srgb, var(--primary-500) 88%, #000) !important;
  }
  .swal-button--voice {
    background: #0891b2 !important;
    color: #fff !important;
  }
  .swal-button--voice:hover {
    background: color-mix(in srgb, #0891b2 88%, #000) !important;
  }

  /* SMS choice modal still uses a corner dismiss (×). Scoped so it does not
     affect Cancel on delete / transfer confirms. */
  .swal-modal.ipb-swal-sms .swal-button--cancel {
    position: absolute !important;
    top: 12px !important;
    right: 12px !important;
    width: 34px !important;
    height: 34px !important;
    border-radius: 50% !important;
    font-size: 18px !important;
    background: var(--surface-hover, #f1f5f9) !important;
    color: var(--text-secondary, #475569) !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    padding: 0 !important;
    border: none !important;
    z-index: 10 !important;
  }
</style>
<?= $this->endSection('css'); ?>
<?= $this->section('script'); ?>
<script src="<?= base_url('assets/js/saas/customers-list.js?v=6'); ?>"></script>

<script>
  // Delegated + namespaced so SPA re-entry can rebind cleanly, and so a
  // missing button (permission-gated) never throws the way a direct
  // $('#updatePppoeBtn').on(...) / querySelector().addEventListener would.
  $(document)
    .off('click.ipbCustToolbar', '#updatePppoeBtn')
    .on('click.ipbCustToolbar', '#updatePppoeBtn', function () {
    $('#circularLoader').addClass('is-visible');

    $.ajax({
      url: '<?= route_to('route.routers.getRouterPassById') ?>',
      type: 'POST',
      dataType: 'json',
      data: {
        '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
      },
      success: function (response) {
        if (response.status === 'success') {
          tata.success('PPPoE IDs updated', response.response, {
            duration: 2000,
          });

        } else {
          tata.error("Update failed", response.response || "Couldn't save. Please retry.", {
            duration: 2000
          });
        }

      },
      error: function (xhr, status, error) {
        tata.error('Update failed', error || "Couldn't reach the server. Please retry.");
      },
      complete: function () {
        $('#circularLoader').removeClass('is-visible');
      }
    });
  });

  $(document)
    .off('click.ipbCustToolbar', '#sms-choice-btn')
    .on('click.ipbCustToolbar', '#sms-choice-btn', function () {
    const selectedIds = $('.input-check-selected:checkbox:checked');
    const ids = [];
    $(selectedIds).each(function () {
      ids.push($(this).val());
    });

    if (ids.length === 0) {
      swal("Warning", "Please select at least one customer.", "warning");
      return;
    }

    swal({
      title: "Message Type",
      text: "Select the type of message you want to send",
      icon: "info",
      className: "ipb-swal-sms",
      buttons: {
        cancel: {
          text: "×",
          value: null,
          visible: true,
          className: "swal-button--cancel",
          closeModal: true,
        },
        sms: {
          text: "SMS",
          value: "sms",
          className: "swal-button--sms",
        },
        voice: {
          text: "Voice SMS",
          value: "voice",
          className: "swal-button--voice",
        },
      },
    }).then((value) => {
      if (value === "sms") {
        const route = `<?= route_to('route.sms.new') ?>`;
        window.location.href = `${route}?ids=${encodeURIComponent(ids.join(','))}`;
      } else if (value === "voice") {
        const route = `<?= route_to('route.voice-sms.new') ?>`;
        window.location.href = `${route}?ids=${encodeURIComponent(ids.join(','))}`;
      }
    });

    setTimeout(() => {
      const cancelBtn = document.querySelector('.swal-button--cancel');
      const modal = document.querySelector('.swal-modal');
      if (cancelBtn && modal) {
        modal.appendChild(cancelBtn);
      }
    }, 10);
  });

  $(document)
    .off('click.ipbCustToolbar', '#change-router-btn')
    .on('click.ipbCustToolbar', '#change-router-btn', function () {
    const selectedIds = $('.input-check-selected:checkbox:checked');
    const ids = [];
    $(selectedIds).each(function () {
      ids.push($(this).val());
    });

    if (ids.length === 0) {
      swal("Warning", "Please select at least one customer.", "warning");
      return;
    }

    function openChangeRouterSwal(routers) {
      let routerOptions = '<select id="swal-router-select" class="form-control" style="width: 100%; border-radius: 8px; height: 40px; margin-top: 10px;">';
      routerOptions += '<option value="">-- Select Router --</option>';
      (routers || []).forEach(function (r) {
        routerOptions += '<option value="' + r.id + '">' + $('<div>').text(r.name || '').html() + '</option>';
      });
      routerOptions += '</select>';

      swal({
        title: "Change Router",
        text: "Select a new router for " + ids.length + " selected customers:",
        content: {
          element: "div",
          attributes: {
            innerHTML: routerOptions
          }
        },
        buttons: {
          cancel: "Cancel",
          confirm: {
            text: "Confirm Change",
            closeModal: false,
            className: "swal-button--router"
          }
        },
      }).then((willChange) => {
        if (willChange) {
          const routerId = $('#swal-router-select').val();
          if (!routerId) {
            swal.stopLoading();
            swal("Error", "Please select a router.", "error");
            return;
          }

        $.ajax({
          url: '<?= route_to('route.customer.change_router') ?>',
          type: 'POST',
          data: {
            ids: ids.join(','),
            router_id: routerId,
            '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
          },
          success: function (response) {
            if (response.status === 'success') {
              swal("Success", response.message, "success").then(() => {
                $('.datatable').DataTable().ajax.reload();
              });
            } else {
              swal("Error", response.message, "error");
            }
          },
          error: function () {
            swal("Couldn't change router", "The router didn't accept the update. Check it's online and try again.", "error");
          }
        });
        }
      });
    }

    if (window.__ipbModalLookups && window.__ipbModalLookups.routers) {
      openChangeRouterSwal(window.__ipbModalLookups.routers);
      return;
    }
    fetch('<?= route_to('route.customer.modalLookups'); ?>', { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        window.__ipbModalLookups = data || {};
        openChangeRouterSwal((data && data.routers) || []);
      })
      .catch(function () {
        swal("Error", "Could not load routers.", "error");
      });
  });

  $(document)
    .off('click.ipbCustToolbar', '.update-all-btn')
    .on('click.ipbCustToolbar', '.update-all-btn', function () {
    const $selected = $('.input-check-selected:checkbox:checked');
    const ids = [];
    $selected.each(function () {
      ids.push($(this).val());
    });

    if (ids.length === 0) {
      swal("Warning", "Please select at least one customer.", "warning");
      return;
    }

    const targetId = ids[0];
    const route = `<?= route_to('route.customer.subscription', 0) ?>`.replace('/0', '/' + targetId);
    window.location.href = `${route}?ids=${encodeURIComponent(ids.join(','))}`;
  });

  // ---------------------------------------------------------------------------
  // Bulk select + Transfer + Delete — bound OUTSIDE DataTable ready() so a
  // table-init failure cannot kill these handlers. Also .off() un-namespaced
  // legacy listeners left behind by SPA re-entry (those opened swal on the
  // same click and got auto-dismissed, making Transfer/Delete look dead).
  // ---------------------------------------------------------------------------
  (function bindCustomerBulkActions() {
    function selectedIds() {
      var ids = [];
      $('#customersTable tbody .input-check-selected:checkbox:checked').each(function () {
        ids.push(String(this.value));
      });
      return ids;
    }

    function syncSelectAll() {
      var $rows = $('#customersTable tbody .input-check-selected:checkbox');
      var total = $rows.length;
      var checked = $rows.filter(':checked').length;
      var $all = $('#select_all');
      if (!$all.length) return;
      $all.prop('checked', total > 0 && checked === total);
      $all.prop('indeterminate', checked > 0 && checked < total);
    }

    // Clear legacy + namespaced select handlers, then rebind.
    $(document)
      .off('click.ipbCustSelect change.ipbCustSelect click', '#select_all')
      .off('click.ipbCustSelectRow change.ipbCustSelect click', '.input-check-selected')
      .on('click.ipbCustSelect', '#select_all', function (e) {
        // Keep DataTables from treating the header click as a sort.
        e.stopPropagation();
      })
      .on('change.ipbCustSelect', '#select_all', function () {
        var on = !!this.checked;
        $('#customersTable tbody .input-check-selected').prop('checked', on);
      })
      .on('change.ipbCustSelectRow', '#customersTable tbody .input-check-selected', function () {
        syncSelectAll();
      });

    // Re-sync header checkbox after every DataTables redraw.
    $(document)
      .off('draw.dt.ipbCustSelect')
      .on('draw.dt.ipbCustSelect', '#customersTable', function () {
        syncSelectAll();
      });

    // --- Transfer ---
    $(document)
      .off('click', '.customer-transfer-button')
      .off('click.ipbCustTransfer', '.customer-transfer-button')
      .on('click.ipbCustTransfer', '.customer-transfer-button', function (e) {
        e.preventDefault();
        // Close overflow menu; do not stopImmediatePropagation — that would
        // block the menu's own close handler when bind order differs.
        $('.ipb-cust-more').removeClass('is-open')
          .find('.ipb-cust-more-menu').prop('hidden', true)
          .end().find('.ipb-cust-more-btn').attr('aria-expanded', 'false');

        var idsNow = selectedIds();
        if (!idsNow.length) {
          swal('Warning', 'Please select at least one customer.', 'warning');
          return;
        }

        var isImpersonating = <?= session()->has('original_user') ? 'true' : 'false' ?>;
        var originalAdminId = <?= session()->has('original_user') ? (int) session()->get('original_user')['user_id'] : 'null' ?>;
        var userRole = '<?= session()->get('user_role') ?>';
        var parentAdminId = <?= json_encode(isset($details) ? ($details->admin_id ?? null) : null) ?>;
        var isDirectReseller = (userRole === 'resellerAdmin' || userRole === 'employee') && !isImpersonating;

        // Defer past this click so SweetAlert's outside-click listener does not
        // immediately dismiss the dialog.
        setTimeout(function () {
          var transferContainer = document.createElement('div');
          transferContainer.style.cssText = 'display:flex;flex-direction:column;gap:12px;';
          var transferMode = 'reseller';

          if (isImpersonating) {
            var radioGroup = document.createElement('div');
            radioGroup.style.cssText = 'display:flex;gap:16px;margin-bottom:4px;';
            radioGroup.innerHTML =
              '<label style="cursor:pointer"><input type="radio" name="transfer-mode" value="admin" style="margin-right:6px"> Transfer to Admin</label>' +
              '<label style="cursor:pointer"><input type="radio" name="transfer-mode" value="reseller" checked style="margin-right:6px"> Transfer to Reseller</label>';
            transferContainer.appendChild(radioGroup);
          }

          var resellerWrap = document.createElement('div');
          var resellerSelect = document.createElement('select');
          resellerSelect.id = 'reseller-select';
          resellerSelect.className = 'swal-content__input';
          resellerSelect.style.width = '100%';
          resellerSelect.innerHTML = '<option value="">Select reseller</option>';
          resellerWrap.appendChild(resellerSelect);
          transferContainer.appendChild(resellerWrap);

          function fillResellerOptions(resellers) {
            resellerSelect.innerHTML = '<option value="">Select reseller</option>';
            (resellers || []).forEach(function (r) {
              var opt = document.createElement('option');
              opt.value = r.id;
              opt.textContent = r.name || '';
              resellerSelect.appendChild(opt);
            });
          }

          function ensureModalLookups() {
            if (window.__ipbModalLookups && window.__ipbModalLookups.resellers) {
              fillResellerOptions(window.__ipbModalLookups.resellers);
              return Promise.resolve(window.__ipbModalLookups);
            }
            return fetch('<?= route_to('route.customer.modalLookups'); ?>', {
              credentials: 'same-origin',
              headers: { 'Accept': 'application/json' }
            })
              .then(function (r) { return r.json(); })
              .then(function (data) {
                window.__ipbModalLookups = data || {};
                fillResellerOptions((data && data.resellers) || []);
                return data;
              });
          }

          var packageSelect = document.createElement('select');
          packageSelect.id = 'package-select';
          packageSelect.className = 'swal-content__input';
          packageSelect.style.cssText = 'width:100%;display:none;';
          packageSelect.disabled = true;
          packageSelect.innerHTML = '<option value="">Select package</option>';
          transferContainer.appendChild(packageSelect);

          var packageNote = document.createElement('div');
          packageNote.style.cssText = 'font-size:0.9em;color:#6b7280;display:none;';
          packageNote.innerText = 'Select a package to assign to the transferred customer(s).';
          transferContainer.appendChild(packageNote);

          function loadPackages(url) {
            packageSelect.innerHTML = '<option value="">Select package</option>';
            packageSelect.disabled = true;
            packageSelect.style.display = 'none';
            packageNote.style.display = 'none';
            $.ajax({
              url: url, type: 'GET', dataType: 'json',
              success: function (response) {
                if (response.status !== 'success' || !Array.isArray(response.packages) || response.packages.length === 0) {
                  swal('Error', 'No packages available for this destination.', 'error');
                  return;
                }
                response.packages.forEach(function (pkg) {
                  var opt = document.createElement('option');
                  opt.value = pkg.id;
                  opt.dataset.packageName = pkg.package_name;
                  var displayPrice = (pkg.selling_price && pkg.selling_price !== '--' && pkg.selling_price !== 'null' && pkg.selling_price !== '')
                    ? pkg.selling_price : pkg.price;
                  opt.text = pkg.package_name + ' - ' + displayPrice + '৳';
                  packageSelect.appendChild(opt);
                });
                packageSelect.disabled = false;
                packageSelect.style.display = 'block';
                packageNote.style.display = 'block';
              },
              error: function () { swal('Error', 'Failed to fetch packages.', 'error'); }
            });
          }

          if (isImpersonating) {
            transferContainer.querySelectorAll('[name="transfer-mode"]').forEach(function (radio) {
              radio.addEventListener('change', function () {
                transferMode = this.value;
                if (transferMode === 'admin') {
                  resellerWrap.style.display = 'none';
                  loadPackages('<?= base_url('reseller/admin/packages/json') ?>');
                } else {
                  resellerWrap.style.display = 'block';
                  packageSelect.innerHTML = '<option value="">Select package</option>';
                  packageSelect.disabled = true;
                  packageSelect.style.display = 'none';
                  packageNote.style.display = 'none';
                }
              });
            });
          }

          if (isDirectReseller) {
            resellerWrap.style.display = 'none';
            loadPackages('<?= base_url('reseller/admin/packages/json') ?>');
          }

          resellerSelect.addEventListener('change', function () {
            var sel = this.value;
            if (!sel) {
              packageSelect.innerHTML = '<option value="">Select package</option>';
              packageSelect.disabled = true;
              packageSelect.style.display = 'none';
              packageNote.style.display = 'none';
              return;
            }
            loadPackages('<?= base_url('reseller/resellerpackages/json') ?>/' + sel);
          });

          ensureModalLookups().catch(function () {}).then(function () {
            swal({
              title: 'Transfer Customers',
              text: 'Select destination and package:',
              content: transferContainer,
              buttons: { cancel: 'Cancel', confirm: { text: 'Transfer', closeModal: false } },
              dangerMode: true
            }).then(function (confirmed) {
              if (!confirmed) return;

              var isAdminTransfer = (isImpersonating && transferMode === 'admin') || isDirectReseller;
              var resellerId = isAdminTransfer
                ? (isDirectReseller ? parentAdminId : originalAdminId)
                : resellerSelect.value;

              if (!resellerId) { swal('Error', 'Please select a destination.', 'error'); return; }
              var packageId = packageSelect.value;
              if (!packageId) { swal('Error', 'Please select a package.', 'error'); return; }

              var ids = selectedIds();
              if (!ids.length) { swal('Error', 'Please select at least one customer.', 'error'); return; }

              $.ajax({
                url: '<?= route_to("route.customer.transfer") ?>',
                type: 'POST',
                data: {
                  ids: ids,
                  reseller_id: resellerId,
                  package_id: packageId,
                  package_name: packageSelect.options[packageSelect.selectedIndex].dataset.packageName,
                  transfer_to_admin: isAdminTransfer ? '1' : '0'
                },
                headers: { '<?= csrf_header() ?>': '<?= csrf_hash() ?>' },
                success: function (result) {
                  swal.close();
                  tata.success('Customers transferred', result.response);
                  $('.datatable').DataTable().ajax.reload(null, false);
                },
                error: function (response) {
                  var r;
                  try { r = jQuery.parseJSON(response.responseText); }
                  catch (err) {
                    swal.close();
                    tata.error("Couldn't transfer customers", "Something went wrong (status " + response.status + "). Please refresh and try again.");
                    return;
                  }
                  swal.close();
                  tata.error("Couldn't transfer customers", r.response);
                }
              });
            });
          });
        }, 0);
      });

    <?php if (userHasPermission('customer', 'delete')): ?>
    $(document)
      .off('click', '.delete-btn')
      .off('click.ipbCustDelete', '.delete-btn')
      .on('click.ipbCustDelete', '.delete-btn', function (e) {
        e.preventDefault();
        $('.ipb-cust-more').removeClass('is-open')
          .find('.ipb-cust-more-menu').prop('hidden', true)
          .end().find('.ipb-cust-more-btn').attr('aria-expanded', 'false');

        var idsNow = selectedIds();
        if (!idsNow.length) {
          swal('Warning', 'Please select at least one customer.', 'warning');
          return;
        }

        setTimeout(function () {
          var count = idsNow.length;
          var card = document.createElement('div');
          card.className = 'ipb-confirm-card';
          card.innerHTML =
            '<div class="ipb-confirm-icon" aria-hidden="true">' +
              '<i class="fa fa-trash"></i>' +
            '</div>' +
            '<div class="ipb-confirm-title">Delete customers?</div>' +
            '<div class="ipb-confirm-count">' +
              '<strong>' + count + '</strong> selected' +
            '</div>' +
            '<p class="ipb-confirm-body">' +
              'This permanently removes the selected customers from your account.' +
            '</p>' +
            '<p class="ipb-confirm-note">' +
              '<i class="fa fa-exclamation-triangle" aria-hidden="true"></i>' +
              '<span>If the router is online, matching PPPoE secrets are also removed from MikroTik.</span>' +
            '</p>';

          swal({
            content: card,
            className: 'ipb-swal-danger',
            dangerMode: true,
            buttons: {
              cancel: {
                text: 'Cancel',
                value: null,
                visible: true,
                className: 'swal-button--cancel',
                closeModal: true,
              },
              confirm: {
                text: count === 1 ? 'Delete customer' : 'Delete ' + count + ' customers',
                value: true,
                closeModal: false,
                className: 'swal-button--danger',
              },
            },
          }).then(function (willDelete) {
            if (!willDelete) return;

            var ids = selectedIds();
            if (!ids.length) {
              swal.close();
              swal('Warning', 'Please select at least one customer.', 'warning');
              return;
            }

            $.ajax({
              url: '<?= route_to("route.customer.delete"); ?>',
              type: 'DELETE',
              data: { ids: ids },
              headers: { '<?= csrf_header() ?>': '<?= csrf_hash() ?>' },
              success: function (result) {
                swal.close();
                tata.success('Customers deleted', result.response);
                $('.datatable').DataTable().ajax.reload(null, false);
              },
              error: function (response) {
                var result;
                try { result = jQuery.parseJSON(response.responseText); }
                catch (err) {
                  swal.close();
                  tata.error("Couldn't delete customers", "Something went wrong (status " + response.status + "). Please refresh and try again.");
                  return;
                }
                swal.close();
                tata.error("Couldn't delete customers", result.response);
              }
            });
          });
        }, 0);
      });
    <?php endif; ?>
  })();
</script>



<script>
  let activeRequests = [];
  const status = "<?= esc($status) ?>";

  $(document).ready(function () {
    const status = "<?= esc($status) ?>";

    var colVisible = function (key) {
      return window.IpbCustomersList ? IpbCustomersList.colVisible(key) : true;
    };

    if (window.IpbFilters) {
      IpbFilters.restore({
        storageKey: 'ipb_filters_customers_all',
        root: '#customer-filter-bar',
      });
    }

    var ipbCustFilters = null;

    // SPA re-entry: destroy any leftover instance before re-init.
    if ($.fn.dataTable.isDataTable('.datatable')) {
      try { $('.datatable').DataTable().clear().destroy(true); } catch (e) {}
    }

    // Everything below this block (.new-customer-btn, #select_all, .delete-btn,
    // and every other handler through line ~1220) lives in this SAME
    // synchronous $(document).ready() function, bound AFTER the DataTable
    // call below. An uncaught exception constructing the DataTable used to
    // abort the whole function — silently killing every button binding that
    // follows, with no visible sign beyond a console error. try/catch this
    // constructor so a table-init failure can't take the rest of the toolbar
    // down with it.
    let table = null;
    try {
      table = $('.datatable').DataTable({
      serverSide: true,
      processing: false,
      scrollX: false,
      autoWidth: false,
      order: [
        [<?php echo $needsBulkSelect ? 1 : 0; ?>, 'desc']
      ],
      language: {
        search: "Search:",
        lengthMenu: "Show _MENU_",
        info: "Showing _START_ to _END_ of _TOTAL_",
        paginate: { previous: "Prev", next: "Next" },
        // processing:false — skeleton tbody is the only load cue (no centred spinner)
        emptyTable: <?= json_encode($customerEmptyHtml) ?>,
        zeroRecords: <?= json_encode($customerZeroHtml) ?>
      },
      ajax: {
        url: '<?= route_to("route.customer.fetch"); ?>',
        type: 'POST',
        timeout: 60000,
        data: function (d) {
          d.status = status;
          d.area_filter = $('#filter-area').val();
          d.package_filter = $('#filter-package').val();
          d.connection_filter = $('#filter-connection-status').val();
          d.acc_status_filter = $('#filter-acc-status').val();
          d.expiry_filter = $('#filter-expiry').val();
          d.<?= csrf_token() ?> = '<?= csrf_hash() ?>'; // Include token in data
        },
        beforeSend: function (req) {
          req.setRequestHeader('<?= csrf_header() ?>', '<?= csrf_hash() ?>');
        },
        error: function (xhr, error) {
          // Navigating away aborts the XHR — keep skeleton briefly; don't toast.
          if (error === 'abort') return;
          // Without this, a failed/timed-out fetch leaves the skeleton tbody forever
          // (processing:false + no draw) — "stuck on Loading / no data".
          var cols = $('.datatable thead th').length || 14;
          var msg = error === 'timeout'
            ? 'Request timed out. Check your connection and retry.'
            : 'Could not load customers. Please retry.';
          $('.datatable tbody').html(
            '<tr class="odd"><td valign="top" colspan="' + cols + '" class="dataTables_empty">' +
            '<div class="ipb-empty ipb-dt-empty">' +
            '<div class="ipb-empty-icon"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i></div>' +
            '<div class="ipb-empty-title">Load failed</div>' +
            '<div class="ipb-empty-sub">' + msg + '</div>' +
            '<div class="ipb-empty-action"><button type="button" class="btn btn-primary btn-sm" id="ipb-dt-retry">Retry</button></div>' +
            '</div></td></tr>'
          );
          if (window.tata) tata.error('Load failed', msg);
        }
      },
      columns: [
        <?php if ($needsBulkSelect): ?>
                {
                  data: "select",
                  name: "select",
                  orderable: false,
                  searchable: false,
                  visible: true,
                  className: "ipb-c-select",
                  render: function (data) { return data || ''; }
                },
        <?php endif; ?>
        { data: "id", name: "users.id", orderable: true, searchable: true, visible: colVisible("id"), className: "ipb-c-id" },
        { data: "name", name: "users.name", orderable: true, searchable: true, visible: true, className: "ipb-c-name" },
        { data: "package", name: "package", orderable: false, searchable: false, visible: colVisible("package"), className: "ipb-c-package" },
        { data: "area_name", name: "areas.area_name", orderable: true, searchable: true, visible: colVisible("area_name"), className: "ipb-c-area" },
        { data: "mobile", name: "users.mobile", orderable: true, searchable: true, visible: colVisible("mobile"), className: "ipb-c-mobile" },
        { data: "address", name: "users.address", orderable: true, searchable: true, visible: colVisible("address"), className: "ipb-c-address" },
        { data: "router_name", name: "routers.name", orderable: true, searchable: true, visible: colVisible("router_name"), className: "ipb-c-router" },
        { data: "pppoe_secret", name: "user_router_data.pppoe_secret", orderable: true, searchable: true, visible: colVisible("pppoe_secret"), className: "ipb-c-pppoe" },
        { data: "router_password", name: "user_router_data.router_password", orderable: true, searchable: true, visible: colVisible("router_password"), className: "ipb-c-password" },
        { data: "payment_expiry_sort", name: "users.will_expire", orderable: true, searchable: true, visible: colVisible("payment"), className: "ipb-c-payment" },
        { data: "conn_status", name: "users.activity", orderable: true, searchable: false, visible: colVisible("conn_status"), className: "ipb-c-connstatus" },
        { data: "acc_status", name: "users.status", orderable: true, searchable: false, visible: colVisible("acc_status"), className: "ipb-c-accstatus" },
        { data: "action", name: "action", orderable: false, searchable: false, visible: true, className: "ipb-c-action" }
      ],
      columnDefs: [{
        targets: "_all",
        defaultContent: "-"
      }],
      lengthMenu: [
        [25, 50, 100, 250, 500, 1000],
        [25, 50, 100, 250, 500, "All"]
      ],
      pageLength: 25,
      drawCallback: function () {
        const api = this.api();

        // Live MikroTik PPPoE polling used to run here (1 POST per router, up to
        // ~15s each). On `php spark serve` (single-threaded) those requests
        // blocked the next sidebar nav — /customers came back empty and
        // /dashboard stuck on Loading. DB `activity`/`conn_status` is already
        // rendered in the conn_status column; live refresh is not required for
        // the list to be usable.
        if (window.IpbCustomersList) IpbCustomersList.bindRowTooltips(api);
      },

      initComplete: function () {
        if (window.IpbCustomersList) IpbCustomersList.initColumnPicker(this.api());
        // 05 §4.1 — stagger the first paint only (not every filter redraw —
        // that already gets the .is-loading treatment; two motion cues on
        // one click would violate the "1-2 animated elements" budget, 05 §1).
        if (window.IpbUI && window.IpbUI.staggerRows) window.IpbUI.staggerRows(document.getElementById('customersTable'));
      }

      });
    } catch (e) {
      console.error('DataTable initialization failed:', e);
      if (window.tata) tata.error('Failed to load customer table', 'Please refresh the page. If this keeps happening, contact support.');
    }

    $(document).on('click', '#ipb-dt-retry', function () {
      if (table) table.ajax.reload();
    });

    if (window.IpbFilters) {
      ipbCustFilters = IpbFilters.bind(table, {
        storageKey: 'ipb_filters_customers_all',
        root: '#customer-filter-bar',
        skeletonOnly: true,
        onUpdateBadge: function (api, $root, $badge) {
          if (!$badge || !$badge.length) return;
          try {
            var json = api.ajax.json();
            var total = json ? json.recordsTotal : 0;
            var connectionVal = $root.find('#filter-connection-status').val();

            if (connectionVal) {
              var visibleRowsCount = $('.datatable tbody tr:visible').filter(function () {
                return !$(this).find('td.dataTables_empty').length;
              }).length;
              $badge.text(visibleRowsCount + ' of ' + total + ' shown').show();
            } else {
              IpbFilters.defaultUpdateBadge(api, $root, $badge);
            }
          } catch (e) {
            $badge.hide();
          }
        },
      });
    }

    // Client-side search push removed for server-side mode


    // Throttled status queue processor.
    //     function processStatusQueue() {
    //   if (navigationTriggered || statusQueue.length === 0) {
    //     isProcessingQueue = false;
    //     return;
    //   }
    //   isProcessingQueue = true;

    //   // collect all IDs for this page
    //   const routerId = statusQueue[0].data.router_id;
    //   const pppoeIds = statusQueue.map(item => item.data.pppoe_id);

    //   $.ajax({
    //     url: "<?= route_to('route.getPppoeStatus'); ?>",
    //     type: "POST",
    //     data: {
    //       router_id: routerId,
    //       pppoe_ids: pppoeIds
    //     },
    //     headers: {
    //       "<?= csrf_header() ?>": "<?= csrf_hash() ?>"
    //     },
    //     success: function(response) {
    //       console.log("Batch Response:", response);

    //       // Loop through queue and update each row
    //       statusQueue.forEach(item => {
    //         const id = item.data.pppoe_id;
    //         const isOnline = response[id] === true;

    //         let color = isOnline ? "var(--success-600, #15803d)" : "var(--error-600, #b91c1c)";
    //         let bg = isOnline ? "var(--success-100, #dcfce7)" : "var(--error-100, #fee2e2)";
    //         let label = isOnline ? "Online" : "Offline";

    //         item.$statusCell.html(
    //           `<span style="background:${bg}; color:${color}; padding:2px 8px; border-radius:50px; font-weight:500;">${label}</span>`
    //         );
    //       });
    //     },
    //     complete: function() {
    //       isProcessingQueue = false;
    //     }
    //   });
    // }


    // Immediate action handler
    // $(document).on('click', '.action-button', function(e) {
    //   // Set navigation flag first
    //   console.log('Navigation triggered, clearing queue.');
    //   navigationTriggered = true;

    //   // Clear all pending status checks
    //   statusQueue.length = 0;

    //   // Immediate navigation
    //   const targetUrl = this.href;
    //   window.location.href = targetUrl;

    //   // Prevent default behavior
    //   e.preventDefault();
    //   e.stopImmediatePropagation();
    // });
    $(document).on('click', '.action-button', function (e) {
      // Prevent any further queue processing
      // console.log('Navigation triggered, clearing queue.');
      navigationTriggered = true;
      statusQueue.length = 0;

      // Immediately go to target page
      window.location.assign(this.href);
      // console.log(this.href);
      // Stop default link behavior
      e.preventDefault();
    });


    // Current user's role from server-side session
    const userRole = '<?= session()->get('user_role'); ?>';

    $('.new-customer-btn').click(function (e) {
      // The href above is now the real destination. This handler only exists to
      // show a nicer "you're at your limit" dialog instead of a raw JSON error
      // page — so it has to preventDefault() to hold the click while it checks.
      // Every previous version of this handler had some path that could leave
      // the click looking like it silently did nothing (an unbound handler, an
      // unguarded JSON.parse, an AJAX call that never resolved). This version
      // guarantees one of two outcomes on every path, including ones that throw:
      // either the limit-reached dialog shows, or the browser navigates for real.
      e.preventDefault();
      var targetUrl = this.href;

      try {
        var navigated = false;
        var goNow = function () {
          if (navigated) return;
          navigated = true;
          window.location.href = targetUrl;
        };
        // Safety net: a hung request (dead connection, server not responding)
        // must not leave the click looking like it did nothing forever.
        var fallbackTimer = setTimeout(goNow, 4000);

        $.ajax({
          url: targetUrl,
          type: 'GET',
          success: function () {
            clearTimeout(fallbackTimer);
            goNow();
          },
          error: function (response) {
            clearTimeout(fallbackTimer);
            try {
              var result = jQuery.parseJSON(response.responseText);

              if (result && result.response && result.response.limitReached === true) {
                var swalButtons = userRole === 'admin' ? ["No", {
                  text: "Yes",
                  closeModal: false,
                }] : ["No"];

                swal({
                  title: "Confirmation",
                  text: "You have reached your limit. Do you want to update your package?",
                  icon: 'warning',
                  dangerMode: true,
                  buttons: swalButtons,
                }).then((willUpdate) => {
                  if (willUpdate && userRole === 'admin') {
                    window.location.href = '<?= route_to("Admin.packages"); ?>';
                  }
                });
                return;
              }

              if (result && result.response && result.response.message) {
                tata.error("Couldn't open new customer form", result.response.message);
                return;
              }
            } catch (parseErr) {
              // Non-JSON (permission/CSRF/server-error HTML) — can't tell what
              // happened, so fall through to goNow() rather than guess.
            }
            // Any error shape we didn't recognize above: still navigate rather
            // than leave the click with no visible result.
            goNow();
          }
        });
      } catch (outerErr) {
        // $.ajax itself unavailable, or anything else unexpected — navigate
        // directly rather than swallow the click.
        window.location.href = targetUrl;
      }
    });

    $('.import-excel-button').click(function (e) {
      // Real href is the destination; this handler only exists to surface a
      // nicer permission/error toast. Same guarantee as .new-customer-btn:
      // either show an error, or navigate for real — never silent no-op.
      e.preventDefault();
      var targetUrl = this.href;
      try {
        var navigated = false;
        var goNow = function () {
          if (navigated) return;
          navigated = true;
          window.location.href = targetUrl;
        };
        var fallbackTimer = setTimeout(goNow, 4000);

        $.ajax({
          url: targetUrl,
          type: 'GET',
          success: function () {
            clearTimeout(fallbackTimer);
            goNow();
          },
          error: function (response) {
            clearTimeout(fallbackTimer);
            try {
              var result = jQuery.parseJSON(response.responseText);
              if (result && result.response && result.response.message) {
                tata.error("Couldn't open Excel import", result.response.message);
                return;
              }
              if (result && result.response) {
                tata.error("Couldn't open Excel import", result.response);
                return;
              }
            } catch (parseErr) {}
            goNow();
          }
        });
      } catch (outerErr) {
        window.location.href = targetUrl;
      }
    });


    $(document).on('change', '.conn-switch', function () {
      const id = $(this).data('id');
      const status = $(this).is(':checked') ? 'conn' : 'disconn';

      // console.log('Switch changed:', id, status);
      $.ajax({
        url: '<?= route_to("route.customer.update_conn_status"); ?>', // Change to your actual route
        method: 'POST',
        data: {
          user: id,
          status: status
        },
        headers: {
          '<?= csrf_header() ?>': '<?= csrf_hash() ?>'
        },
        success: function (response) {

          // console.log('Status updated:', response);

          tata.success('Connection status updated', response.response, {
            onClose: () => {
              location.reload();
            },
          });
          // Optionally show a success toast/message
        },
        error: function (xhr) {
          const response = JSON.parse(xhr.responseText);
          if (response.message === "Page Not Found") {
            tata.error('Permission required', "You don't have permission to update connections. Ask your admin to grant \"Update Connection\".");
            console.log("You doesn't have this permission, ask your Admin.");
          } else {
            tata.error("Couldn't update connection status", result.message);

            console.error(response.message);
          }
          // tata.error('Error', result.response);

        }
      });
    });



    // Transfer / Delete / select-all handlers live in bindCustomerBulkActions()
    // above (outside this ready) so DataTable init cannot swallow them.



  });
</script>

<?= $this->endSection('script'); ?>
