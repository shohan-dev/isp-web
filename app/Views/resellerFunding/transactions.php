<?= $this->extend('layout/main-layout'); ?>
<?= $this->section('content'); ?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <!-- Main content -->
  <section class="content ipb-saas-list">

    
    <?= $this->include('components/page-header', [
      'title' => 'POPs Transaction',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'POPs Transaction'],
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
                'options' => [
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
          <caption class="sr-only">POPs transaction history</caption>
          <thead class="text-nowrap">
            <tr>

              <!-- <?php if (userHasPermission('customer_payment', 'delete')) : ?>

                <th data-data="select">
                  <input type="checkbox" class="form-check-input" id="select_all">
                </th>

              <?php endif; ?> -->

              <th scope="col" data-data="serial">#</th>

              <th scope="col" data-data="customer">Customer</th>
              <!-- <th data-data="invoice">Invoice Id</th> -->
              <th scope="col" data-data="amount">Amount (৳)</th>
              <th scope="col" data-data="package_price">package_price </th>
              <th scope="col" data-data="active_for">Activation Days</th>

              <th scope="col" data-data="created_at">Received at</th>
              <!-- <th data-data="paid_at">Paid at </th> -->
              <!-- <th data-data="paid_via">Paid via</th> -->
              <th scope="col" data-data="comments">comment</th>
              <!-- <th data-data="status">Status</th> -->

              <!-- <?php if (userHasPermission('customer_payment', 'update')) : ?>

                <th data-data="action">Action</th>

              <?php endif; ?> -->

            </tr>
          </thead>
          <?php
            // Zero-blank-frame first paint: skeleton rows show before
            // JS/DataTables boots; DataTables replaces this <tbody> on its first draw.
            // Uses the view() helper, not $this->include() — the latter is View::include(),
            // whose 2nd param is $options (cache/debug flags), not view data, so cols/rows
            // would silently never reach the component and it would fall back to its default of 5.
            $transactionsSkeletonCols = 7;
          ?>
          <?= view('components/skeleton-table', ['cols' => $transactionsSkeletonCols, 'rows' => 8]) ?>
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
        url: '<?= route_to("route.Reseller.transaction.fetch"); ?>',
        type: 'post',
        data: function(d) {
          // Pass filter data
          d.reseller = $('#reseller').val();
          // d.status = $('#status').val();
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

    <?php if (userHasPermission('customer_payment', 'delete')) : ?>
      //check all checkbox function
      $("#select_all").click(function() {

        if (this.checked) {

          $("input:checkbox").each(function() {
            this.checked = true;
          });

        } else {

          $("input:checkbox").each(function() {
            this.checked = false;
          });
        }
      });

      $(document).on("click", ".input-check-selected:checkbox", function() {

        if ($(".input-check-selected:checkbox:checked").length === $(".input-check-selected:checkbox").length) {

          $("#select_all").prop("checked", true);

        } else {

          $("#select_all").prop("checked", false);
        }
      });

      //Function for delete packages
      $(document).on('click', '.delete-btn', function() {

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

            const selectedIds = $('.input-check-selected:checkbox:checked');

            const ids = [];

            $(selectedIds).each(function() {
              ids.push($(this).val());
            });

            $.ajax({
              url: '<?= route_to("route.Reseller.transaction.delete"); ?>',
              type: 'DELETE',
              data: {
                ids
              },
              headers: {
                '<?= csrf_header() ?>': '<?= csrf_hash() ?>',
              },
              success: function(result) {

                swal.close();

                tata.success('Transaction deleted', result.response);

                $('.datatable').DataTable().ajax.reload(null, false);
              },

              error: function(response) {

                const result = jQuery.parseJSON(response.responseText);

                swal.close();
                tata.error("Couldn't delete transaction", result.response);
              }
            });
          }
        });
      });
    <?php endif; ?>

  })
</script>

<?= $this->endSection('script'); ?>