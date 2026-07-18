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
  /* Package list cells — themed to the tenant palette and dark-mode safe. Was
     hardcoded indigo/cyan + #333/#fff, which ignored the brand color and turned
     unreadable in dark mode; now everything routes through the saas tokens. */
  .ipb-saas-list .datatable td { vertical-align: middle; }

  .package-name {
    font-weight: 700;
    color: var(--text-primary, #1f2937);
    letter-spacing: -0.01em;
  }

  /* Bandwidth as a soft branded chip — scannable, not just colored text. */
  .bandwidth {
    display: inline-block;
    font-weight: 700;
    font-size: 12.5px;
    color: var(--primary-600, #4f46e5);
    background: var(--primary-50, rgba(79, 70, 229, .08));
    border: 1px solid color-mix(in srgb, var(--primary-500, #4f46e5) 22%, transparent);
    padding: 3px 11px;
    border-radius: 8px;
  }

  .pricing {
    font-weight: 700;
    color: var(--success-600, #16a34a);
  }

  /* Status / visibility pills: subtle tint + matching border + a leading dot. */
  .status_active,
  .status_inactive,
  .visibility {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 11px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
    border: 1px solid transparent;
    white-space: nowrap;
  }
  .status_active::before,
  .status_inactive::before,
  .visibility::before {
    content: '';
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: currentColor;
    flex-shrink: 0;
  }
  .status_active {
    background: var(--success-50, rgba(34, 197, 94, .12));
    color: var(--success-600, #16a34a);
    border-color: color-mix(in srgb, var(--success-500, #22c55e) 26%, transparent);
  }
  .status_inactive {
    background: var(--error-50, rgba(239, 68, 68, .12));
    color: var(--error-600, #dc2626);
    border-color: color-mix(in srgb, var(--error-500, #ef4444) 26%, transparent);
  }
  .visibility {
    background: var(--info-50, rgba(59, 130, 246, .12));
    color: var(--info-600, #2563eb);
    border-color: color-mix(in srgb, var(--info-500, #3b82f6) 26%, transparent);
  }

  /* Action button follows the tenant brand (was a fixed indigo→cyan gradient). */
  .action_btn {
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 13px;
    cursor: pointer;
    border: none;
    transition: transform .16s ease, box-shadow .16s ease, filter .16s ease;
  }
  .action_btn.update {
    background: linear-gradient(135deg, var(--primary-500, #4f46e5), var(--primary-600, #4338ca));
    color: #fff;
  }
  .action_btn.update:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px color-mix(in srgb, var(--primary-500, #4f46e5) 35%, transparent);
    filter: brightness(1.04);
  }

  /* Gentle row hover for scanability. */
  .ipb-saas-list .datatable tbody tr { transition: background .15s ease; }
  .ipb-saas-list .datatable tbody tr:hover td { background: var(--surface-2, rgba(15, 23, 42, .025)); }
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