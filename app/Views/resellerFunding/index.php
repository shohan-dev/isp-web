<?= $this->extend('layout/main-layout'); ?>
<?php $this->section('needsDataTable'); ?>1<?php $this->endSection(); ?>
<?= $this->section('content'); ?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <!-- Main content -->
  <section class="content ipb-saas-list">

    
    <?= $this->include('components/page-header', [
      'title' => 'POPs Funding',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'POPs Funding'],
      ],
    ]); ?>

<div class="box box-warning">
      <div class="box-header with-border ipb-box-toolbar">
        <?php
          $resellerOptions = [];
          foreach ($resellers as $reseller) {
              $resellerOptions[] = ['value' => $reseller->id, 'label' => $reseller->name];
          }

          ob_start();
        ?>
          <button type="button" id="applyFilter" class="btn btn-primary">Apply</button>
          <button type="button" id="clearFilter" class="btn btn-default">Clear</button>
          <?php if (getSession('user_role') != 'resellerAdmin' && userHasPermission('customer_payment', 'delete')): ?>
            <button type="button" class="btn btn-danger delete-btn" id="deleteSelectedBtn" disabled>
              <i class="far fa-trash-can" aria-hidden="true"></i> Delete Selected
            </button>
          <?php endif; ?>
          <?php if (getSession('user_role') != 'resellerAdmin' && userHasPermission('customer_payment', 'create')): ?>
            <a class="btn btn-primary" href="<?= route_to('route.Reseller.Funding.new'); ?>">
              <i class="fa fa-plus" aria-hidden="true"></i> New Fund
            </a>
          <?php endif; ?>
        <?php $filterActionsHtml = ob_get_clean(); ?>
        <form id="filterForm" method="post">
          <?= view('components/list-toolbar', [
            'manualBind' => true,
            'actionsHtml' => $filterActionsHtml,
            'filters' => [
              [
                'id' => 'reseller',
                'type' => 'select',
                'ariaLabel' => 'MAC POPs',
                'emptyLabel' => 'Select POP',
                'options' => $resellerOptions,
              ],
              [
                'id' => 'status',
                'type' => 'select',
                'ariaLabel' => 'Status',
                'emptyLabel' => 'All Status',
                'options' => [
                  ['value' => 'pending', 'label' => 'Pending'],
                  ['value' => 'successful', 'label' => 'Completed'],
                ],
              ],
              [
                'id' => 'fromDate',
                'type' => 'date',
                'ariaLabel' => 'From',
              ],
              [
                'id' => 'toDate',
                'type' => 'date',
                'ariaLabel' => 'To',
              ],
            ],
          ]); ?>
        </form>
      </div>

      <div class="box-body">

        <div class="table-responsive">
          <table class="table table-bordered table-striped datatable" width="100%">
          <caption class="sr-only">POPs funding history</caption>
          <thead class="text-nowrap">
            <tr>

              <?php if (userHasPermission('customer_payment', 'delete')): ?>

                <th scope="col" data-data="select">
                  <input type="checkbox" class="form-check-input" id="select_all">
                </th>

              <?php endif; ?>

              <th scope="col" data-data="serial">#</th>

              <th scope="col" data-data="customer">POPs</th>
              <th scope="col" data-data="invoice">Invoice Id</th>
              <th scope="col" data-data="amount">Amount (৳)</th>
              <th scope="col" data-data="paid">Received </th>

              <th scope="col" data-data="created_at">Received at</th>
              <th scope="col" data-data="paid_at">Paid at </th>
              <th scope="col" data-data="paid_via">Paid via</th>
              <th scope="col" data-data="comments">comment</th>
              <th scope="col" data-data="status">Status</th>

              <?php if (userHasPermission('customer_payment', 'update')): ?>

                <th scope="col" data-data="action">Action</th>

              <?php endif; ?>

            </tr>
          </thead>
          <?php
            // Zero-blank-frame first paint: skeleton rows show before JS/DataTables
            // boots; DataTables replaces this <tbody> on its first draw.
            // Uses the view() helper, not $this->include() — the latter is View::include(),
            // whose 2nd param is $options (cache/debug flags), not view data, so cols/rows
            // would silently never reach the component and it would fall back to its default of 5.
            $resellerFundingSkeletonCols = 10
              + (userHasPermission('customer_payment', 'delete') ? 1 : 0)
              + (userHasPermission('customer_payment', 'update') ? 1 : 0);
          ?>
          <?= view('components/skeleton-table', ['cols' => $resellerFundingSkeletonCols, 'rows' => 8]) ?>
        </table>
        </div>
      </div>
      <!-- Self Recharge Modal -->
      


    </div>
  </section>
  <!-- /.content -->
</div>

<?= $this->endSection('content'); ?>

<?= $this->section('script'); ?>

<script>

  



  $(document).ready(function() {

    var table = $('.datatable').DataTable({
      serverSide: true,
      processing: true,
      ajax: {
        url: '<?= route_to("route.Reseller.Funding.fetch"); ?>',
        type: 'post',
        data: function(d) {
          // Pass filter data
          d.reseller = $('#reseller').val();
          d.status = $('#status').val();
          d.fromDate = $('#fromDate').val();
          d.toDate = $('#toDate').val();
        },
        beforeSend: function(req) {
          req.setRequestHeader('<?= csrf_header() ?>', '<?= csrf_hash() ?>');
        }
      },
      columnDefs: [{
        "targets": "_all",
        "defaultContent": "-"
      }],
    });

    // Apply filter button click event
    $('#applyFilter').click(function() {
      table.ajax.reload();
    });

    // Clear filter button click event
    $('#clearFilter').click(function() {
      $('#filterForm')[0].reset(); // Reset form fields
      table.ajax.reload(); // Reload DataTable without filters
    });

    <?php if (userHasPermission('customer_payment', 'delete')): ?>
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
          text: "Are you sure you want to delete the selected records?",
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
              url: '<?= route_to("route.Reseller.Funding.delete"); ?>',
              type: 'DELETE',
              data: {
                ids
              },
              headers: {
                '<?= csrf_header() ?>': '<?= csrf_hash() ?>',
              },
              success: function(result) {

                swal.close();

                tata.success('Funding record deleted', result.response);

                $("#select_all").prop("checked", false);
                updateDeleteButtonState();
                $('.datatable').DataTable().ajax.reload(null, false);
              },

              error: function(response) {

                const result = jQuery.parseJSON(response.responseText);

                swal.close();
                tata.error("Couldn't delete funding record", result.response);
              }
            });
          }
        });
      });
    <?php endif; ?>

  })
</script>

<?= $this->endSection('script'); ?>