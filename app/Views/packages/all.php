<?= $this->extend('layout/main-layout'); ?>
<?= $this->section('content'); ?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <!-- Main content -->
  <section class="content ipb-saas-list">

    
    <?= $this->include('components/page-header', [
      'title' => 'Packages',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Packages'],
      ],
    ]); ?>

<div class="box box-warning">
      <div class="box-header with-border ipb-box-toolbar">
        <?php
          ob_start();
          if (userHasPermission('packages', 'delete')) :
        ?>
            <button type="button" class="btn btn-danger delete-btn" id="deleteSelectedBtn" disabled>
              <i class="fa fa-trash"></i> Delete Selected
            </button>
        <?php
          endif;
          if (userHasPermission('packages', 'create')) :
        ?>
            <a class="btn btn-primary" href="<?= route_to('route.packages.new'); ?>">
              <i class="fa fa-plus"></i> Add New Package
            </a>
        <?php
          endif;
          $packagesActionsHtml = ob_get_clean();

          $packagesFilters = [];
          if ($role !== 'reseller') {
              $packagesFilters[] = [
                  'id' => 'filter-package-status',
                  'type' => 'select',
                  'ariaLabel' => 'Filter by status',
                  'emptyLabel' => 'All Status',
                  'options' => [
                      ['value' => 'active', 'label' => 'Active'],
                      ['value' => 'inactive', 'label' => 'Inactive'],
                  ],
              ];
              $packagesFilters[] = [
                  'id' => 'filter-package-visibility',
                  'type' => 'select',
                  'ariaLabel' => 'Filter by visibility',
                  'emptyLabel' => 'All Visibility',
                  'options' => [
                      ['value' => 'active', 'label' => 'Visible'],
                      ['value' => 'inactive', 'label' => 'Hidden'],
                  ],
              ];
          }

          echo view('components/list-toolbar', [
              'filters' => $packagesFilters,
              'actionsHtml' => $packagesActionsHtml,
              'filterLabel' => $role !== 'reseller' ? 'Filter' : 'Packages',
          ]);
        ?>
      </div>

      <div class="box-body">

        <?php
          // Column labels in the exact order the <th> cells below are rendered,
          // used to drive the DataTables columnDefs createdCell data-label below.
          $packagesColumnLabels = [];
          if (userHasPermission('packages', 'delete')) {
              $packagesColumnLabels[] = 'Select';
          }
          $packagesColumnLabels[] = '#';
          $packagesColumnLabels[] = 'Package Name';
          $packagesColumnLabels[] = 'Bandwidth';
          $packagesColumnLabels[] = 'Pricing';
          if ($role !== 'reseller') {
              $packagesColumnLabels[] = 'Status';
              $packagesColumnLabels[] = 'Visibility';
          }
          $packagesActionColumnIndex = null;
          if (userHasPermission('packages', 'update')) {
              $packagesColumnLabels[] = 'Action';
              $packagesActionColumnIndex = count($packagesColumnLabels) - 1;
          }
        ?>
        <div class="table-responsive">
          <table class="table table-bordered table-striped datatable ipb-cards-sm" width="100%">
          <caption class="sr-only">Package list</caption>
          <thead class="text-nowrap">
            <tr>

              <?php if (userHasPermission('packages', 'delete')) : ?>

                <th data-data="select" scope="col">
                  <input type="checkbox" class="form-check-input" id="select_all">
                </th>

              <?php endif; ?>

              <th data-data="serial" scope="col">#</th>
              <th data-data="package_name" scope="col">Package Name</th>

              <th data-data="bandwidth" scope="col">Bandwidth</th>

              <th data-data="pricing" scope="col">Pricing</th>
              <?php if ($role !== 'reseller') : ?>

                <th data-data="status" scope="col">Status</th>
                <th data-data="visibility" scope="col">Visibility</th>
              <?php endif; ?>
              <?php if (userHasPermission('packages', 'update')) : ?>

                <th data-data="action" scope="col">Action</th>

              <?php endif; ?>

            </tr>
          </thead>
          <?php
            // 04 §4 — zero-blank-frame first paint: skeleton rows show before
            // JS/DataTables boots; DataTables replaces this <tbody> on its first draw.
            $packagesSkeletonCols = 4
              + (userHasPermission('packages', 'delete') ? 1 : 0)
              + ($role !== 'reseller' ? 2 : 0)
              + (userHasPermission('packages', 'update') ? 1 : 0);
          ?>
          <?= view('components/skeleton-table', ['cols' => $packagesSkeletonCols, 'rows' => 8]) ?>
        </table>
        </div>
      </div>

    </div>
  </section>
  <!-- /.content -->
</div>

<?= $this->endSection('content'); ?>

<?= $this->section('script'); ?>
<style>
  :root {
    --bg-color: #f5f7fb;
    --text-color: #333;
    --card-bg: #fff;
    --border-color: #e2e8f0;
    --header-bg: linear-gradient(135deg, #4f46e5, #06b6d4);
    --primary-gradient: linear-gradient(135deg, #4f46e5, #06b6d4);
    --danger-gradient: linear-gradient(135deg, #dc2626, #ef4444);
    --success-gradient: linear-gradient(135deg, #16a34a, #22c55e);
    --warning-gradient: linear-gradient(135deg, #d97706, #f59e0b);
  }
  .btn-primary {
      background: var(--primary-gradient);
      color: white;
    }

    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow-brand, 0 4px 12px rgba(79, 70, 229, 0.4));
    }


  .action_btn {
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 13px;
    cursor: pointer;
    margin-right: 8px;
    transition: all 0.3s ease;
    border: none;
    position: relative;
    overflow: hidden;
    z-index: 1;
  }

  .action-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s;
    z-index: -1;
  }

  .action_btn:hover::before {
    left: 100%;
  }

  .action_btn.update {
    background: linear-gradient(135deg, #4f46e5, #06b6d4);
    color: white;
  }

  .action_btn.update:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-2, 0 4px 8px rgba(79, 70, 229, 0.3));
  }

  .package-name {
    font-weight: 600;
    color: var(--text-color);
  }

  .bandwidth {
    font-weight: 500;
    color: #4f46e5;
  }

  .pricing {
    font-weight: 600;
    color: #16a34a;
  }

  .status_active {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 500;
    background-color: rgba(34, 197, 94, 0.15);
    color: #16a34a;
  }

  .status_inactive {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 500;
    background-color: rgba(239, 68, 68, 0.15);
    color: #dc2626;
  }

  .visibility {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 500;
    background-color: rgba(59, 130, 246, 0.15);
    color: #2563eb;
  }
</style>

<script>
  $(document).ready(function() {
    <?php $orderCol = userHasPermission('packages', 'delete') ? 2 : 1; ?>

    // Column labels (in <th> order) drive the ipb-cards-sm data-label attributes
    // on phone widths; the Action column is excluded from the label pseudo-element.
    const packagesColumnLabels = <?= json_encode($packagesColumnLabels) ?>;
    const packagesActionColumnIndex = <?= $packagesActionColumnIndex === null ? 'null' : (int) $packagesActionColumnIndex ?>;
    const packagesColumnDefs = packagesColumnLabels.map(function(label, idx) {
      return {
        targets: idx,
        createdCell: function(td) {
          td.setAttribute('data-label', label);
          if (idx === packagesActionColumnIndex) {
            td.classList.add('ipb-cards-actions');
          }
        }
      };
    });

    const packageTable = $('.datatable').DataTable({
      serverSide: true,
      processing: false,
      pageLength: 25,
      lengthMenu: [[25, 50, 100, 250, 500, 1000], [25, 50, 100, 250, 500, "All"]],
      order: [[<?= $orderCol ?>, 'desc']],
      columnDefs: packagesColumnDefs,
      ajax: {
        url: '<?= route_to("route.packages.fetch"); ?>',
        type: 'POST',
        data: function(d) {
          d.status_filter = $('#filter-package-status').val();
          d.visibility_filter = $('#filter-package-visibility').val();
          d.<?= csrf_token() ?> = '<?= csrf_hash() ?>';
        },
        beforeSend: function(req) {
          req.setRequestHeader('<?= csrf_header() ?>', '<?= csrf_hash() ?>');
        },
      },
    });

    $('#filter-package-status, #filter-package-visibility').on('change', function() {
      packageTable.ajax.reload();
    });


    <?php if (userHasPermission('packages', 'delete')) : ?>

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

      packageTable.on('draw', function() {
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
          text: "Are your sure you want to delete the selected records",
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
              url: '<?= route_to("route.packages.delete"); ?>',
              type: 'DELETE',
              data: {
                ids
              },
              headers: {
                '<?= csrf_header() ?>': '<?= csrf_hash() ?>',
              },
              success: function(result) {

                swal.close();

                tata.success('Package deleted', result.response);

                $("#select_all").prop("checked", false);
                updateDeleteButtonState();
                $('.datatable').DataTable().ajax.reload(null, false);
              },

              error: function(response) {

                swal.close();

                const result = jQuery.parseJSON(response.responseText);
                tata.error("Couldn't delete package", result.response);
              }
            });
          }
        });
      });

    <?php endif; ?>

  })
</script>

<?= $this->endSection('script'); ?>