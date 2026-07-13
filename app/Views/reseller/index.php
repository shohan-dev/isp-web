<?= $this->extend('layout/main-layout'); ?>
<?= $this->section('content'); ?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <!-- Main content -->
  <section class="content ipb-saas-list">

    
    <?= $this->include('components/page-header', [
      'title' => 'POP',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'POP'],
      ],
    ]); ?>

<div class="box box-warning">
      <div class="box-header with-border ipb-box-toolbar">
        <div class="ipb-list-toolbar">
          <div class="ipb-list-toolbar-filters">
            <span class="ipb-filter-label"><i class="fa fa-list" aria-hidden="true"></i> Records</span>
          </div>
          <div class="ipb-list-toolbar-actions">
<?php if (userHasPermission('Resellers', 'create')|| userHasPermission('reseller', 'create')) : ?>

            <a class="btn btn-primary" href="<?= route_to('reseller.add'); ?>">
              <i class="fa fa-plus"></i> New Reseller
            </a>

          <?php endif; ?>

          <?php if (userHasPermission('Resellers', 'delete') ||userHasPermission('reseller', 'delete')) : ?>

            <button type="button" class="btn btn-danger delete-btn" id="deleteSelectedBtn" disabled>
              <i class="far fa-trash-can"></i> Delete Selected
            </button>

          <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="box-body">

        <div class="table-responsive">
          <table class="table table-bordered table-striped datatable" width="100%">
          <caption class="sr-only">POP / Reseller list</caption>
          <thead class="text-nowrap">
            <tr>

              <?php if (userHasPermission('Resellers', 'delete')||userHasPermission('reseller', 'delete')) : ?>

                <th data-data="select" scope="col">
                  <input type="checkbox" class="form-check-input" id="select_all">
                </th>
              <?php endif; ?>

              <th data-data="serial" scope="col">#</th>
              <th data-data="billing_type" scope="col">Type</th>
              <th data-data="name" scope="col">Resellers</th>
              <th data-data="mobile" scope="col">Mobile</th>
              <th data-data="clients_running" scope="col">Clients<br>(Running)</th>
              <th data-data="clients_enabled" scope="col">Clients<br>(Enabled)</th>
              <th data-data="clients_disabled" scope="col">Clients<br>(Disabled)</th>
              <th data-data="clients_left" scope="col">Clients<br>(Left)</th>
              <th data-data="remaining_fund" scope="col">Remaining<br>Fund</th>
              <th data-data="toggle_reseller" scope="col">Reseller<br>Enabled?</th>
              <th data-data="toggle_fund" scope="col">Fund<br>Enabled?</th>
              <th data-data="action" scope="col">Action</th>
            </tr>
          </thead>
          <?php
            $resellerSkeletonCols = 12 + ((userHasPermission('Resellers', 'delete') || userHasPermission('reseller', 'delete')) ? 1 : 0);
          ?>
          <?= view('components/skeleton-table', ['cols' => $resellerSkeletonCols, 'rows' => 8]) ?>
        </table>
        </div>
      </div>

    </div>
  </section>
  <!-- /.content -->
</div>

<?= $this->endSection('content'); ?>

<?= $this->section('script'); ?>

<script>
  $(document).ready(function() {

    var table = $('.datatable').DataTable({
      ajax: {
        url: '<?= route_to("route.Reseller.fetch"); ?>',
          type: 'post',
          
        beforeSend: function (req) {
            req.setRequestHeader('<?= csrf_header() ?>', '<?= csrf_hash() ?>',);
          }
        },
        columnDefs: [
            {
              "targets": "_all",  
              "defaultContent": "-"
            },
            {
              "targets": "th[data-data='toggle_reseller'], th[data-data='toggle_fund']",
              "orderable": false
            }
          ],
          lengthMenu: [
            [50, 100, 150, 200, 500], // Values for the dropdown
            [50, 100, 150, 200, "All"] // Labels displayed in the dropdown
          ],
          pageLength: 100
      });

    <?php if (userHasPermission('Resellers', 'delete')||userHasPermission('reseller', 'delete')) : ?>

      function updateDeleteButtonState() {
        const hasSelection = $('.input-check-selected:checkbox:checked').length > 0;
        $('#deleteSelectedBtn').prop('disabled', !hasSelection);
      }

      $("#select_all").click(function() {
        $('.input-check-selected:checkbox').prop('checked', this.checked);
        updateDeleteButtonState();
      });

      $(document).on("change", ".input-check-selected:checkbox", function() {
        const total = $(".input-check-selected:checkbox").length;
        const checked = $(".input-check-selected:checkbox:checked").length;

        $("#select_all").prop("checked", total > 0 && checked === total);
        updateDeleteButtonState();
      });

      table.on('draw', function() {
        $("#select_all").prop("checked", false);
        updateDeleteButtonState();
      });

      $(document).on('click', '#deleteSelectedBtn', function() {
        const selectedIds = $('.input-check-selected:checkbox:checked');

        if (selectedIds.length === 0) {
          return;
        }

        swal({
          title: "Confirmation",
          text: "Are you sure you want to delete the selected Reseller?",
          dangerMode: true,
          icon: 'warning',
          buttons: ["No", {
            text: "Yes",
            closeModal: false,
          }],
        }).then((willDelete) => {

          if (willDelete) {
            const ids = [];

            selectedIds.each(function() {
              ids.push($(this).val());
            });

            $.ajax({
              url: '<?= route_to("route.reseller.delete"); ?>',
              type: 'DELETE',
              data: {
                ids
              },
              headers: {
                '<?= csrf_header() ?>': '<?= csrf_hash() ?>',
              },
              success: function(result) {

                swal.close();

                tata.success('Reseller deleted', result.response);

                $("#select_all").prop("checked", false);
                updateDeleteButtonState();
                $('.datatable').DataTable().ajax.reload(null, false);
              },

              error: function(response) {

                const result = jQuery.parseJSON(response.responseText);

                swal.close();

                tata.error("Couldn't delete reseller", result.response);
              }

            });
          }
        });
      });

    <?php endif; ?>

    // Toggle: Reseller Enabled (label pill toggle)
    $(document).on('click', '.toggle-reseller-status', function() {
      const label = $(this);
      const resellerId = label.data('id');
      const currentStatus = label.data('status');
      const newStatus = (currentStatus === 'active') ? 'inactive' : 'active';
      $.ajax({
        url: '<?= route_to("route.reseller.toggle.status"); ?>',
        type: 'POST',
        data: { id: resellerId, status: newStatus },
        headers: { '<?= csrf_header() ?>': '<?= csrf_hash() ?>' },
        success: function(res) {
          // Update label visually
          const isActive = (newStatus === 'active');
          label.data('status', newStatus);
          label.text(isActive ? 'ON' : 'OFF');
          label.css({
            'color': isActive ? '#28a745' : '#dc3545',
            'background': isActive ? '#d4edda' : '#f8d7da',
            'border-color': isActive ? '#28a745' : '#dc3545'
          });
          tata.success('Status updated', res.response ?? 'Status updated');
        },
        error: function() {
          tata.error("Couldn't update status", 'Could not update status');
        }
      });
    });

    // Toggle: Fund Enabled (label pill toggle)
    $(document).on('click', '.toggle-fund-enabled', function() {
      const label = $(this);
      const resellerId = label.data('id');
      const currentEnabled = label.data('enabled');
      const newEnabled = (currentEnabled == 1) ? 0 : 1;
      $.ajax({
        url: '<?= route_to("route.reseller.toggle.fund"); ?>',
        type: 'POST',
        data: { id: resellerId, fund_enabled: newEnabled },
        headers: { '<?= csrf_header() ?>': '<?= csrf_hash() ?>' },
        success: function(res) {
          const isEnabled = (newEnabled == 1);
          label.data('enabled', newEnabled);
          label.text(isEnabled ? 'ON' : 'OFF');
          label.css({
            'color': isEnabled ? '#28a745' : '#dc3545',
            'background': isEnabled ? '#d4edda' : '#f8d7da',
            'border-color': isEnabled ? '#28a745' : '#dc3545'
          });
          tata.success('Fund status updated', res.response ?? 'Fund status updated');
        },
        error: function() {
          tata.error("Couldn't update fund status", 'Could not update fund status');
        }
      });
    });

    // Toggle: Clients Enabled/Disabled (label pill toggle)
    $(document).on('click', '.toggle-clients-status', function() {
      const label = $(this);
      const resellerId = label.data('id');
      const currentStatus = label.data('status');
      const newStatus = (currentStatus === 'active') ? 'inactive' : 'active';
      $.ajax({
        url: '<?= route_to("route.reseller.toggle.clients"); ?>',
        type: 'POST',
        data: { id: resellerId, status: newStatus },
        headers: { '<?= csrf_header() ?>': '<?= csrf_hash() ?>' },
        success: function(res) {
          const isActive = (newStatus === 'active');
          label.data('status', newStatus);
          label.text(isActive ? 'ON' : 'OFF');
          label.css({
            'color': isActive ? '#28a745' : '#dc3545',
            'background': isActive ? '#d4edda' : '#f8d7da',
            'border-color': isActive ? '#28a745' : '#dc3545'
          });
          tata.success('Clients status updated', res.response ?? 'Clients status updated');
          // Reload to update client counts
          setTimeout(function() {
            $('.datatable').DataTable().ajax.reload(null, false);
          }, 1000);
        },
        error: function() {
          tata.error("Couldn't update clients status", 'Could not update clients status');
        }
      });
    });

    <?php if(session()->getFlashdata('success')): ?>
      tata.success('Success', '<?= esc(session()->getFlashdata('success'), 'js') ?>');
    <?php endif; ?>
    <?php if(session()->getFlashdata('error')): ?>
      tata.error('Error', '<?= esc(session()->getFlashdata('error'), 'js') ?>');
    <?php endif; ?>

  });
</script>

<?= $this->endSection('script'); ?>