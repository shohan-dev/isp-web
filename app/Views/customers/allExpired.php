<?= $this->extend('layout/main-layout'); ?>

<?php
$areasForFilter = $areasForFilter ?? [];
$packagesForFilter = $packagesForFilter ?? [];

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

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper ipb-customers-page">
  <section class="content">

    <?= $this->include('components/page-header', [
      'title' => 'Expired Customers',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Expired Customers'],
      ],
    ]); ?>

    <div class="box box-warning">
      <div class="box-header with-border ipb-box-toolbar">
        <?php
          ob_start();
        ?>
            <div class="ipb-col-picker" id="ipbColPicker">
              <button type="button" class="ipb-tool-btn is-ghost" id="ipbColPickerBtn" title="Choose columns" aria-haspopup="true" aria-expanded="false">
                <i class="fa fa-columns" aria-hidden="true"></i><span class="ipb-tool-label">Columns</span>
              </button>
              <div class="ipb-col-picker-panel" id="ipbColPickerPanel" hidden>
                <div class="ipb-col-picker-head"><strong>Show columns</strong><button type="button" class="ipb-col-picker-reset" id="ipbColPickerReset">Reset</button></div>
                <div class="ipb-col-picker-list" id="ipbColPickerList"></div>
                <p class="ipb-col-picker-note">Saved on this device only</p>
              </div>
            </div>
            <div class="ipb-cust-more">
              <button type="button" class="ipb-tool-btn is-ghost ipb-cust-more-btn" aria-haspopup="true" aria-expanded="false" title="More actions" aria-label="More actions">
                <i class="fa fa-ellipsis-v" aria-hidden="true"></i>
              </button>
              <div class="ipb-cust-more-menu" hidden>
                <?php if (userHasPermission('sms_message')): ?>
                  <a href="javascript:void(0);" id="sms-choice-btn" class="ipb-cust-more-item"><i class="fa fa-envelope" aria-hidden="true"></i><span>Send SMS / Voice</span></a>
                <?php endif; ?>
                <?php if (getSession('user_role') === 'resellerAdmin' || getSession('user_role') === 'admin'): ?>
                  <a href="javascript:void(0);" id="updatePppoeBtn" class="ipb-cust-more-item"><i class="fa fa-sync" aria-hidden="true"></i><span>Update PPPoE IDs</span></a>
                <?php endif; ?>
                <a href="javascript:void(0);" class="update-all-btn ipb-cust-more-item"><i class="fa fa-pen-to-square" aria-hidden="true"></i><span>Recharge all</span></a>
                <a href="javascript:void(0);" class="customer-transfer-button ipb-cust-more-item"><i class="fa fa-right-left" aria-hidden="true"></i><span>Transfer</span></a>
                <?php if (userHasPermission('customer', 'delete')): ?>
                  <button type="button" class="delete-btn ipb-cust-more-item is-danger"><i class="fa fa-trash" aria-hidden="true"></i><span>Delete selected</span></button>
                <?php endif; ?>
              </div>
            </div>
        <?php
          $customerActionsHtml = ob_get_clean();
          $areaOptions = [];
          foreach ($areasForFilter as $a) {
              $areaOptions[] = ['value' => $a->id, 'label' => $a->area_name];
          }
          $packageOptions = [];
          foreach ($packagesForFilter as $p) {
              $packageOptions[] = ['value' => $p->id, 'label' => $p->package_name];
          }
          echo view('components/list-toolbar', [
              'toolbarId' => 'header-flex-row',
              'filtersBarId' => 'customer-filter-bar',
              'resetId' => 'filter-reset-btn',
              'countId' => 'filter-count-badge',
              'manualBind' => true,
              'actionsHtml' => $customerActionsHtml,
              'filters' => [
                  [
                      'id' => 'filter-area',
                      'ariaLabel' => 'Filter by area',
                      'emptyLabel' => 'All Areas',
                      'options' => $areaOptions,
                  ],
                  [
                      'id' => 'filter-package',
                      'ariaLabel' => 'Filter by package',
                      'emptyLabel' => 'All Packages',
                      'options' => $packageOptions,
                  ],
                  [
                      'id' => 'filter-connection-status',
                      'ariaLabel' => 'Filter by connection',
                      'emptyLabel' => 'All Status',
                      'options' => [
                          ['value' => 'active', 'label' => 'Online'],
                          ['value' => 'inactive', 'label' => 'Offline'],
                      ],
                  ],
                  [
                      'id' => 'filter-acc-status',
                      'ariaLabel' => 'Filter by account status',
                      'emptyLabel' => 'All Acc Status',
                      'options' => [
                          ['value' => 'conn', 'label' => 'Connected'],
                          ['value' => 'disconn', 'label' => 'Disconnected'],
                      ],
                  ],
              ],
          ]);
        ?>
      </div>

      <div class="ipb-loading-overlay" id="circularLoader">
        <div class="ipb-loading-overlay-inner">
          <span class="ipb-spinner ipb-spinner--lg" aria-hidden="true"></span>
          <span class="ipb-loading-overlay-label">Updating…</span>
        </div>
      </div>
      <div class="box-body">
        <div class="table-responsive ipb-customers-table-wrap">
          <table class="table table-bordered table-striped datatable">
            <caption class="sr-only">Expired customers</caption>
            <thead class="text-nowrap">
              <tr>
                <?php if (userHasPermission('customer', 'delete')): ?>
                  <th data-data="select" data-col="select" data-col-locked="1" scope="col"><input type="checkbox" class="form-check-input" id="select_all" aria-label="Select all"></th>
                <?php endif; ?>
                <th data-data="id" data-name="users.id" data-col="id" data-col-label="C.ID" scope="col">C.id</th>
                <th data-data="name" data-name="users.name" data-col="name" data-col-label="Customer" data-col-locked="1" scope="col">Customer</th>
                <th data-data="package" data-name="users.package_id" data-col="package" data-col-label="Package" scope="col">Package</th>
                <th data-data="area_name" data-name="areas.area_name" data-col="area_name" data-col-label="Area" scope="col">Area</th>
                <th data-data="mobile" data-name="users.mobile" data-col="mobile" data-col-label="Mobile" scope="col">Mobile</th>
                <th data-data="address" data-name="users.address" data-col="address" data-col-label="Address" scope="col">Address</th>
                <th data-data="router_name" data-name="routers.name" data-col="router_name" data-col-label="Router" scope="col">Router</th>
                <th data-data="pppoe_secret" data-name="user_router_data.pppoe_secret" data-col="pppoe_secret" data-col-label="PPPoE Secret" scope="col">PPPoE Secret</th>
                <th data-data="router_password" data-name="user_router_data.router_password" data-col="router_password" data-col-label="Password" scope="col">Password</th>
                <th data-data="payment_expiry_sort" data-name="users.will_expire" data-col="payment" data-col-label="Payment" scope="col">Payment</th>
                <th data-data="conn_status" data-name="users.activity" data-col="conn_status" data-col-label="Status" scope="col">Status</th>
                <th data-data="acc_status" data-name="users.status" data-col="acc_status" data-col-label="Acc. Status" scope="col">Acc. Status</th>
                <th data-data="action" data-col="action" data-col-label="Action" data-col-locked="1" scope="col">Action</th>
              </tr>
            </thead>
            <?php
              // 04 §4 — zero-blank-frame first paint: skeleton rows show before
              // JS/DataTables boots; DataTables replaces this <tbody> on its first draw.
              // Uses the view() helper, not $this->include() — the latter is View::include(),
              // whose 2nd param is $options (cache/debug flags), not view data, so cols/rows
              // silently never reached the component and it fell back to its default of 5.
              $customersExpiredSkeletonCols = 13 + (userHasPermission('customer', 'delete') ? 1 : 0);
            ?>
            <?= view('components/skeleton-table', ['cols' => $customersExpiredSkeletonCols, 'rows' => 8]) ?>
          </table>
        </div>
      </div>
    </div>
  </section>
</div>

<?= $this->endSection('content'); ?>
<?= $this->section('css'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/saas/customers-list.css?v=21'); ?>">
<style>
  /* PREMIUM CARD DESIGN */
  .box.box-warning {
    border-top: 3px solid #f39c12 !important;
    border-radius: 12px !important;
    box-shadow: var(--shadow-1, 0 10px 30px rgba(0, 0, 0, 0.08)) !important;
    border: none;
    transition: all 0.3s ease;
  }

  .box-header.with-border {
    border-bottom: 1px solid #f4f4f4;
    padding: 14px 20px;
  }

  /* ── Header flex row ─────────────────────────────────────── */
  #header-flex-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
  }

  /* ── Filter bar (LEFT on desktop) ───────────────────────── */
  #customer-filter-bar {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    flex: 1;
    min-width: 0;
  }

  #customer-filter-bar .filter-label {
    font-size: 13px;
    font-weight: 600;
    color: #475569;
    white-space: nowrap;
  }

  #customer-filter-bar .filter-label i {
    margin-right: 4px;
    color: #6366f1;
  }

  #customer-filter-bar select {
    height: 34px;
    border-radius: 8px;
    border: 1px solid #cbd5e1;
    padding: 0 10px;
    font-size: 13px;
    min-width: 160px;
    background: #fff;
    color: #334155;
    cursor: pointer;
    outline: none;
    transition: border-color 0.2s;
  }

  #customer-filter-bar select:focus {
    border-color: #6366f1;
  }

  #filter-reset-btn {
    height: 34px;
    padding: 0 14px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    background: #fff;
    color: #64748b;
    font-size: 13px;
    cursor: pointer;
    transition: background 0.15s;
  }

  #filter-reset-btn:hover {
    background: #f1f5f9;
  }

  #filter-count-badge {
    font-size: 12px;
    background: #6366f1;
    color: #fff;
    padding: 4px 12px;
    border-radius: 20px;
    font-weight: 600;
    white-space: nowrap;
  }

  /* ── Action buttons (RIGHT) ──────────────────────────────── */
  #action-buttons-group {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    flex-shrink: 0;
  }

  /* ── Mobile: stack filter BELOW buttons ──────────────────── */
  @media (max-width: 768px) {
    #header-flex-row {
      flex-direction: column;
      align-items: flex-start;
    }

    #action-buttons-group {
      order: 1;
      width: 100%;
      justify-content: flex-end;
    }

    #customer-filter-bar {
      order: 2;
      width: 100%;
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 10px;
      padding: 10px 12px;
    }

    #customer-filter-bar select {
      flex: 1;
      min-width: 120px;
    }
  }


  /* MODAL CARD DESIGN BY USER */
  .swal-modal {
    border-radius: 18px !important;
    padding: 30px 25px 25px !important;
    width: 420px !important;
    text-align: center !important;
    position: relative !important;
    box-shadow: var(--shadow-3, 0 20px 60px rgba(0,0,0,0.15)) !important;
  }
  .swal-icon {
    margin-top: 5px !important;
    margin-bottom: 10px !important;
  }
  .swal-title {
    font-size: 24px !important;
    font-weight: 700 !important;
    color: #1e293b !important;
  }
  .swal-text {
    font-size: 15px !important;
    color: #64748b !important;
    margin-bottom: 25px !important;
  }
  .swal-footer {
    display: flex !important;
    justify-content: center !important;
    gap: 15px !important;
    margin-top: 10px !important;
  }
  .swal-button {
    border-radius: 10px !important;
    padding: 10px 26px !important;
    font-size: 14px !important;
    font-weight: 600 !important;
    transition: all 0.2s ease !important;
  }
  .swal-button--sms {
    background: linear-gradient(45deg, #3b82f6, #2563eb) !important;
    color: #fff !important;
  }
  .swal-button--sms:hover {
    transform: translateY(-1px);
    box-shadow: 0 5px 15px rgba(59,130,246,0.3);
  }
  .swal-button--voice {
    background: linear-gradient(45deg, #06b6d4, #0891b2) !important;
    color: #fff !important;
  }
  .swal-button--voice:hover {
    transform: translateY(-1px);
    box-shadow: 0 5px 15px rgba(6,182,212,0.3);
  }
  .swal-button--cancel {
    position: absolute !important;
    top: 12px !important;
    right: 12px !important;
    width: 34px !important;
    height: 34px !important;
    border-radius: 50% !important;
    font-size: 18px !important;
    background: #f1f5f9 !important;
    color: #475569 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    padding: 0 !important;
    border: none !important;
    z-index: 10 !important;
  }
  .swal-button--cancel:hover {
    background: #e2e8f0 !important;
    color: #0f172a !important;
  }
</style>
<?= $this->endSection('css'); ?>
<?= $this->section('script'); ?>
<script src="<?= base_url('assets/js/saas/customers-list.js?v=2'); ?>"></script>


<script>
  $('#updatePppoeBtn').on('click', function () {
    // $('#updateResult').text('Updating...');
    $('#circularLoader').addClass('is-visible');

    $.ajax({
      url: '<?= route_to('route.routers.getRouterPassById') ?>', // your route here
      type: 'POST',
      dataType: 'json',
      data: {
        '<?= csrf_token() ?>': '<?= csrf_hash() ?>' // CSRF token here
      },
      success: function (response) {
        if (response.status === 'success') {
          // console.log(response);
          tata.success('PPPoE IDs updated', response.response, {
            duration: 2000,
            // onClose: () => {
            //   // Redirect after the toast disappears
            //   window.location.href = '<?= route_to("route.customer"); ?>';
            // }
          });

        } else {
          tata.error("Update failed", response.response || "Couldn't save. Please retry.", {
            duration: 2000
          });
        }

      },
      error: function (xhr, status, error) {
        $('#updateResult').text('Error: ' + error);
      },
      complete: function () {
        $('#circularLoader').removeClass('is-visible');
      }
    });
  });

  $(document).on('click', '#sms-choice-btn', function () {
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

    // JS Fix to move cancel button to top-right of modal
    setTimeout(() => {
      const cancelBtn = document.querySelector('.swal-button--cancel');
      const modal = document.querySelector('.swal-modal');
      if (cancelBtn && modal) {
        modal.appendChild(cancelBtn);
      }
    }, 10);
  });
</script>


<script>
  document.querySelector('.update-all-btn').addEventListener('click', function () {
    const selectedIds = $('.input-check-selected:checkbox:checked');
    const ids = [];
    $(selectedIds).each(function () {
      ids.push($(this).val());
    });

    if (selectedIds.length === 0) {
      alert('Please select at least one user to update.');
      return;
    }

    // Log the selected user IDs to the console
    // console.log('Selected User IDs:', selectedIds);
    // console.log('Selected User IDs:', selectedIds[0].value);

    // Example: Redirect to a new page with selected user IDs as query string
    const targetId = selectedIds.length > 0 ? selectedIds[0].value : 123; // Replace with dynamic ID if needed
    const route = `<?= route_to('route.customer.subscription', 0) ?>`.replace('/0', '/' + targetId);

    // Redirect to the route with the selected user IDs in query string
    window.location.href = `${route}?ids=${encodeURIComponent(ids.join(','))}`;

  });
</script>



<script>
  let activeRequests = [];

  $(document).ready(function () {
    var colVisible = function (key) {
      return window.IpbCustomersList ? IpbCustomersList.colVisible(key) : true;
    };

    const table = $('.datatable').DataTable({
      processing: false,
      serverSide: true,
      scrollX: false,
      autoWidth: false,
      language: {
        emptyTable: <?= json_encode($customerEmptyHtml) ?>,
        zeroRecords: <?= json_encode($customerZeroHtml) ?>
      },
      ajax: {
        url: '<?= route_to("route.customer.expired_fetch"); ?>',
        type: 'POST',

        beforeSend: function (req) {
          req.setRequestHeader('<?= csrf_header() ?>', '<?= csrf_hash() ?>');
        },
        data: function(d) {
          d.area_filter = $('#filter-area').val();
          d.package_filter = $('#filter-package').val();
          d.connection_filter = $('#filter-connection-status').val();
          d.acc_status_filter = $('#filter-acc-status').val();
        }
      },
      columns: [
        <?php if (userHasPermission('customer', 'delete')): ?>
        { data: "select", orderable: false, searchable: false, visible: true },
        <?php endif; ?>
        { data: "id", name: "users.id", orderable: true, searchable: true, visible: colVisible("id") },
        { data: "name", name: "users.name", orderable: true, searchable: true, visible: true },
        { data: "package", name: "users.package_id", orderable: false, searchable: false, visible: colVisible("package") },
        { data: "area_name", name: "areas.area_name", orderable: true, searchable: true, visible: colVisible("area_name") },
        { data: "mobile", name: "users.mobile", orderable: true, searchable: true, visible: colVisible("mobile") },
        { data: "address", name: "users.address", orderable: true, searchable: true, visible: colVisible("address") },
        { data: "router_name", name: "routers.name", orderable: true, searchable: true, visible: colVisible("router_name") },
        { data: "pppoe_secret", name: "user_router_data.pppoe_secret", orderable: true, searchable: true, visible: colVisible("pppoe_secret") },
        { data: "router_password", name: "user_router_data.router_password", orderable: true, searchable: true, visible: colVisible("router_password") },
        { data: "payment_expiry_sort", name: "users.will_expire", orderable: true, searchable: true, visible: colVisible("payment") },
        { data: "conn_status", name: "users.activity", orderable: true, searchable: true, visible: colVisible("conn_status") },
        { data: "acc_status", name: "users.status", orderable: true, searchable: true, visible: colVisible("acc_status") },
        { data: "action", orderable: false, searchable: false, visible: true }
      ],
      columnDefs: [{
        targets: "_all",
        defaultContent: "-"
      }],
      order: [
        [<?php echo userHasPermission('customer', 'delete') ? 1 : 0; ?>, 'desc']
      ],
      lengthMenu: [
        [25, 50, 100, 250, 500, 1000],
        [25, 50, 100, 250, 500, "All"]
      ],
      pageLength: 25,
      drawCallback: function () {
        const api = this.api();
        const rows = api.rows({
          page: 'current'
        }).nodes();

        const routerMap = {}; // router_id => [pppoe_ids]
        const cellMap = {}; // router_id => {pppoe_id: cell}

        $(rows).each((i, row) => {
          const data = api.row(row).data();
          // console.log("Row Data:", data);
          if (!data || !data.pppoe_secret || !data.router_id) return;

          if (!routerMap[data.router_id]) {
            routerMap[data.router_id] = [];
            cellMap[data.router_id] = {};
          }

          routerMap[data.router_id].push(data.pppoe_secret);
          cellMap[data.router_id][data.pppoe_secret] = { rowNode: row };
        });

        const statusColIdx = <?php echo userHasPermission('customer', 'delete') ? 11 : 10; ?>;

        // Make 1 request per router
        for (const routerId in routerMap) {
          $.ajax({
            url: "<?= route_to('route.getPppoeStatus'); ?>",
            type: "POST",
            data: {
              router_id: routerId,
              pppoe_ids: routerMap[routerId]
            },
            headers: {
              "<?= csrf_header() ?>": "<?= csrf_hash() ?>"
            },
            success: function (response) {
              console.log("Full Response:", response);

              for (const routerId in response) {
                const routerData = response[routerId];

                // Handle router connection error
                if (routerData.error) {
                  // console.warn("Router", routerId, "Error:", routerData.error);
                  // // Optionally mark all PPPoE cells for this router as "Error"
                  // for (const pppoeId in cellMap[routerId]) {
                  //   cellMap[routerId][pppoeId].html(
                  //     `<span style="background:var(--error-50, #fef2f2); color:var(--error-600, #b91c1c); padding:2px 8px; border-radius:50px; font-weight:500;">Error</span>`
                  //   );
                  // }
                  continue;
                }

                // Loop PPPoE IDs
                for (const pppoeId in routerData) {
                  const isOnline = routerData[pppoeId] === true;
                  const color = isOnline ? "var(--success-600, #15803d)" : "var(--error-600, #b91c1c)";
                  const bg = isOnline ? "var(--success-100, #dcfce7)" : "var(--error-100, #fee2e2)";
                  const label = isOnline ? "Online" : "Offline";

                  if (cellMap[routerId] && cellMap[routerId][pppoeId]) {
                    var cellNode = api.cell(cellMap[routerId][pppoeId].rowNode, statusColIdx).node();
                    if (cellNode) {
                      $(cellNode).html(
                        `<span style="background:${bg}; color:${color}; padding:2px 8px; border-radius:50px; font-weight:500;">${label}</span>`
                      );
                    }
                  }
                }
              }
            }

          });
        }
        if (window.IpbCustomersList) IpbCustomersList.bindRowTooltips(api);
      },
      initComplete: function () {
        if (window.IpbCustomersList) IpbCustomersList.initColumnPicker(this.api());
      }

    });

    function applyFilters() {
      var areaVal = $('#filter-area').val();
      var packageVal = $('#filter-package').val();
      var connectionVal = $('#filter-connection-status').val();
      var accStatusVal = $('#filter-acc-status').val();
      var hasFilter = areaVal || packageVal || connectionVal || accStatusVal;

      $('#filter-reset-btn').toggle(!!hasFilter);

      table.ajax.reload();
    }

    $('.datatable').on('draw.dt', function () {
      var dt = $(this).DataTable();
      var info = dt.page.info();
      var hasFilter = $('#filter-area').val() || $('#filter-package').val() || $('#filter-connection-status').val() || $('#filter-acc-status').val();

      if (hasFilter) {
        $('#filter-count-badge').text(info.recordsDisplay + ' of ' + info.recordsTotal + ' shown').show();
      } else {
        $('#filter-count-badge').hide();
      }
    });

    $('#filter-area, #filter-package, #filter-connection-status, #filter-acc-status').on('change', applyFilters);

    $('#filter-reset-btn').on('click', function () {
      $('#filter-area').val('');
      $('#filter-package').val('');
      $('#filter-connection-status').val('');
      $('#filter-acc-status').val('');
      applyFilters();
    });






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

    $('.new-customer-btn').click(function () {
      $.ajax({
        url: '<?= route_to("route.customer.new"); ?>',
        type: 'GET',
        success: function (response) {
          window.location.href = '<?= route_to("route.customer.new"); ?>';
        },
        error: function (response) {
          // console.log("AJAX Error Response:", response.responseJSON.response);
          const result = jQuery.parseJSON(response.responseText);

          if (response.responseJSON && response.responseJSON.response.message) {
            tata.error("Couldn't open new customer form", response.responseJSON.response.message);
          }

          if (response.responseJSON && response.responseJSON.response.limitReached === true) {
            // Configure SweetAlert buttons based on user role
            const swalButtons = userRole === 'admin' ? ["No", {
              text: "Yes",
              closeModal: false,
            }] : ["No"];

            // Show SweetAlert confirmation
            swal({
              title: "Confirmation",
              text: "You have reached your limit. Do you want to update your package?",
              icon: 'warning',
              dangerMode: true,
              buttons: swalButtons,
            }).then((willUpdate) => {
              if (willUpdate && userRole === 'admin') {
                // Redirect to the package update page if confirmed
                window.location.href = '<?= route_to("Admin.packages"); ?>';
              }
            });

          } else {
            tata.error("Couldn't open new customer form", result.response);
          }
        }
      });
    });

    $('.import-excel-button').click(function () {
      $.ajax({
        url: '<?= route_to("route.customer.excel_index"); ?>',
        type: 'GET',
        success: function (response) {
          window.location.href = '<?= route_to("route.customer.excel_index"); ?>';
        },
        error: function (response) {
          // console.log("AJAX Error Response:", response.responseJSON.response);
          const result = jQuery.parseJSON(response.responseText);

          if (response.responseJSON && response.responseJSON.response.message) {
            tata.error("Couldn't open Excel import", response.responseJSON.response.message);
          }

          // if (response.responseJSON && response.responseJSON.response.limitReached === true) {
          //   // Configure SweetAlert buttons based on user role
          //   const swalButtons = userRole === 'admin'
          //     ? ["No", {
          //         text: "Yes",
          //         closeModal: false,
          //       }]
          //     : ["No"];

          //   // Show SweetAlert confirmation
          //   swal({
          //     title: "Confirmation",
          //     text: "You have reached your limit. Do you want to update your package?",
          //     icon: 'warning',
          //     dangerMode: true,
          //     buttons: swalButtons,
          //   }).then((willUpdate) => {
          //     if (willUpdate && userRole === 'admin') {
          //       // Redirect to the package update page if confirmed
          //       window.location.href = '<?= route_to("Admin.packages"); ?>';
          //     }
          //   });

          // }
          else {
            tata.error("Couldn't open Excel import", result.response);
          }
        }
      });
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
              location.href = '<?= route_to("route.customer"); ?>';
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


    //Function for delete users

    $(document).on('click', '.customer-transfer-button', function () {
      var isImpersonating = <?= session()->has('original_user') ? 'true' : 'false' ?>;
      var originalAdminId = <?= session()->has('original_user') ? (int) session()->get('original_user')['user_id'] : 'null' ?>;
      var userRole = '<?= session()->get('user_role') ?>';
      var parentAdminId = <?= $details->admin_id ?? 'null' ?>;
      var isDirectReseller = (userRole === 'resellerAdmin' || userRole === 'employee') && !isImpersonating;

      var transferContainer = document.createElement('div');
      transferContainer.style.cssText = 'display:flex;flex-direction:column;gap:12px;';

      // --- Radio buttons (only when impersonating) ---
      var transferMode = 'reseller'; // default

      if (isImpersonating) {
        var radioGroup = document.createElement('div');
        radioGroup.style.cssText = 'display:flex;gap:16px;margin-bottom:4px;';
        radioGroup.innerHTML = `
          <label style="cursor:pointer"><input type="radio" name="transfer-mode" value="admin" style="margin-right:6px"> Transfer to Admin</label>
          <label style="cursor:pointer"><input type="radio" name="transfer-mode" value="reseller" checked style="margin-right:6px"> Transfer to Reseller</label>`;
        transferContainer.appendChild(radioGroup);
      }

      // --- Reseller select ---
      var resellerWrap = document.createElement('div');
      var resellerSelect = document.createElement('select');
      resellerSelect.id = 'reseller-select';
      resellerSelect.className = 'swal-content__input';
      resellerSelect.style.width = '100%';
      resellerSelect.innerHTML = `<option value="">Select reseller</option><?php foreach ($resellers as $r): ?><option value="<?= $r->id ?>"><?= htmlspecialchars($r->name) ?></option><?php endforeach; ?>`;
      resellerWrap.appendChild(resellerSelect);
      transferContainer.appendChild(resellerWrap);

      // --- Package select ---
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
          success: function(response) {
            if (response.status !== 'success' || !Array.isArray(response.packages) || response.packages.length === 0) {
              swal('Error', 'No packages available for this destination.', 'error'); return;
            }
            response.packages.forEach(function(pkg) {
              var opt = document.createElement('option');
              opt.value = pkg.id;
              opt.dataset.packageName = pkg.package_name;
              var displayPrice = (pkg.selling_price && pkg.selling_price !== '--' && pkg.selling_price !== 'null' && pkg.selling_price !== '') ? pkg.selling_price : pkg.price;
              opt.text = pkg.package_name + ' - ' + displayPrice + '৳';
              packageSelect.appendChild(opt);
            });
            packageSelect.disabled = false;
            packageSelect.style.display = 'block';
            packageNote.style.display = 'block';
          },
          error: function() { swal('Error', 'Failed to fetch packages.', 'error'); }
        });
      }

      // Auto-load admin packages if impersonating and admin mode is default... (we start on reseller mode)
      if (isImpersonating) {
        transferContainer.querySelector('[name="transfer-mode"]') && transferContainer.querySelectorAll('[name="transfer-mode"]').forEach(function(radio) {
          radio.addEventListener('change', function() {
            transferMode = this.value;
            if (transferMode === 'admin') {
              resellerWrap.style.display = 'none';
              loadPackages('<?= base_url('reseller/admin/packages/json') ?>');
            } else {
              resellerWrap.style.display = 'block';
              packageSelect.innerHTML = '<option value="">Select package</option>';
              packageSelect.disabled = true; packageSelect.style.display = 'none'; packageNote.style.display = 'none';
            }
          });
        });
      }

      if (isDirectReseller) {
        resellerWrap.style.display = 'none';
        loadPackages('<?= base_url('reseller/admin/packages/json') ?>');
      }

      resellerSelect.addEventListener('change', function() {
        var sel = this.value;
        if (!sel) { packageSelect.innerHTML = '<option value="">Select package</option>'; packageSelect.disabled = true; packageSelect.style.display = 'none'; packageNote.style.display = 'none'; return; }
        loadPackages('<?= base_url('reseller/resellerpackages/json') ?>/' + sel);
      });

      swal({ title: 'Transfer Customers', text: 'Select destination and package:', content: transferContainer, buttons: { cancel: 'Cancel', confirm: { text: 'Transfer', closeModal: false } }, dangerMode: true })
      .then(function(confirmed) {
        if (!confirmed) return;

        var isAdminTransfer = (isImpersonating && transferMode === 'admin') || isDirectReseller;
        var resellerId = isAdminTransfer ? (isDirectReseller ? parentAdminId : originalAdminId) : resellerSelect.value;

        if (!resellerId) { swal('Error', 'Please select a destination.', 'error'); return; }

        var packageId = packageSelect.value;
        if (!packageId) { swal('Error', 'Please select a package.', 'error'); return; }

        var selectedIds = [];
        $('.input-check-selected:checkbox:checked').each(function() { selectedIds.push($(this).val()); });
        if (selectedIds.length === 0) { swal('Error', 'Please select at least one customer.', 'error'); return; }

        $.ajax({
          url: '<?= route_to("route.customer.transfer") ?>',
          type: 'POST',
          data: {
            ids: selectedIds,
            reseller_id: resellerId,
            package_id: packageId,
            package_name: packageSelect.options[packageSelect.selectedIndex].dataset.packageName,
            transfer_to_admin: isAdminTransfer ? '1' : '0'
          },
          headers: { '<?= csrf_header() ?>': '<?= csrf_hash() ?>' },
          success: function(result) { swal.close(); tata.success('Customers transferred', result.response); $('.datatable').DataTable().ajax.reload(null, false); },
          error: function(response) { var r = jQuery.parseJSON(response.responseText); swal.close(); tata.error("Couldn't transfer customers", r.response); }
        });
      });
    });

    <?php if (userHasPermission('customer', 'delete')): ?>

      //check all checkbox function
      $("#select_all").click(function () {

        if (this.checked) {

          $(".input-check-selected").each(function () {
            this.checked = true;
          });

        } else {

          $(".input-check-selected").each(function () {
            this.checked = false;
          });
        }
      });

      $(document).on("click", ".input-check-selected:checkbox", function () {

        if ($(".input-check-selected:checkbox:checked").length === $(".input-check-selected:checkbox").length) {

          $("#select_all").prop("checked", true);

        } else {

          $("#select_all").prop("checked", false);
        }
      });

      //Function for delete users
      $(document).on('click', '.delete-btn', function () {
        swal({
          title: "Confirmation",
          text: "Are you sure you want to delete the selected customers? If the router is active, this will also delete these PPPoE users from your MikroTik router.",
          dangerMode: true,
          icon: 'warning',
          buttons: ["No", {
            text: "Delete customers",
            closeModal: false,
          }],
        }).then((willDelete) => {

          if (willDelete) {

            const selectedIds = $('.input-check-selected:checkbox:checked');

            const ids = [];

            $(selectedIds).each(function () {
              ids.push($(this).val());
            });

            $.ajax({
              url: '<?= route_to("route.customer.delete"); ?>',
              type: 'DELETE',
              data: {
                ids
              },
              headers: {
                '<?= csrf_header() ?>': '<?= csrf_hash() ?>',
              },
              success: function (result) {

                swal.close();

                tata.success('Customers deleted', result.response);

                $('.datatable').DataTable().ajax.reload(null, false);
              },

              error: function (response) {

                const result = jQuery.parseJSON(response.responseText);

                swal.close();

                tata.error("Couldn't delete customers", result.response);
              }

            });
          }
        });
      });

    <?php endif; ?>

      // Auto-reload on filter change
      $('#filter-area, #filter-package, #filter-connection-status, #filter-acc-status').on('change', function () {
        table.ajax.reload();
      });

      // Reset filters
      $('#filter-reset-btn').on('click', function () {
        $('#filter-area, #filter-package, #filter-connection-status, #filter-acc-status').val('');
        table.ajax.reload();
      });

  });
</script>

<?= $this->endSection('script'); ?>