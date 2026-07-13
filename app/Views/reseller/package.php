<?= $this->extend('layout/main-layout'); ?>

<?= $this->section('css'); ?>
<style>
  /* The modal header was hardcoded to Bootstrap blue (#007bff) with white text —
     the one bright blue bar in an app whose brand is set per tenant in Theme
     Studio. It now uses the same tokens as every other panel header, so it
     follows the tenant's colour instead of fighting it. */
  .ipb .modal-header {
    background: var(--surface-2);
    color: var(--text-primary);
    border-bottom: 1px solid var(--border);
  }

  .ipb .modal-footer .btn {
    margin-right: 5px;
  }

  .profile-loading {
    display: none;
    color: var(--text-muted);
    font-size: 12px;
    margin-top: 3px;
  }
</style>
<?= $this->endSection('css'); ?>

<?= $this->section('content'); ?>
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <!-- Main content -->
  <section class="content ipb-saas-list">
    
    <?= $this->include('components/page-header', [
      'title' => 'Package Configuration',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Package Configuration'],
      ],
    ]); ?>

<?php
    // The "+ Package" button used to float bare on the page background, and the
    // table sat in a .box-body with no .box around it — so this was the only list
    // page in the app with no card, no header bar and no toolbar. Same shell as
    // every other list now: card > toolbar (label + actions) > table.
    ob_start(); ?>
      <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addPackageModal">
        <i class="fa fa-plus" aria-hidden="true"></i> New package
      </button>
    <?php $packageActionsHtml = ob_get_clean(); ?>

    <div class="box box-warning">
      <div class="box-header with-border ipb-box-toolbar">
        <?= view('components/list-toolbar', [
          'toolbarId' => 'package-toolbar',
          'filters' => [],
          'actionsHtml' => $packageActionsHtml,
          'showReset' => false,
          'showCount' => false,
          'filterLabel' => 'Packages',
        ]); ?>
      </div>

      <div class="box-body">
      <div class="table-responsive">
          <table class="table table-bordered table-striped datatable" width="100%">
        <caption class="sr-only">Package configuration</caption>
        <thead class="text-nowrap">
          <tr>
            <th scope="col">#</th>
            <th scope="col">Package Name</th>
            <th scope="col">Bandwidth </th>
            <th scope="col">Price</th>
            <th scope="col">Package Type</th>
            <th scope="col">Preview</th>
            <th scope="col" class="admin-only-col">Router</th>
            <th scope="col" class="admin-only-col">Profile</th>
            <th scope="col">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($packages) && is_array($packages)): ?>
            <?php foreach ($packages as $index => $package): ?>
              <?php
              $routerName = '--';
              if (!empty($package['mikrotik_router_id'])) {
                  foreach ($routers as $r) {
                      $rid = is_object($r) ? $r->id : $r['id'];
                      $rname = is_object($r) ? $r->name : $r['name'];
                      if ($rid == $package['mikrotik_router_id']) {
                          $routerName = esc($rname);
                          break;
                      }
                  }
              }
              ?>
              <?php /* esc() on every cell: package_name/bandwidth/price/pricing_type/preview
                       were echoed raw, so a package name is a stored-XSS vector — it is
                       operator-entered free text that lands straight in this table. */ ?>
              <tr>
                <td><?= $index + 1 ?></td>
                <td><?= esc($package['package_name'] ?? '') ?></td>
                <td><?= esc($package['bandwidth'] ?? '') ?></td>
                <td><?= esc($package['price'] ?? '') ?></td>
                <td><?= esc($package['pricing_type'] ?? '') ?></td>
                <td><?= esc($package['preview'] ?? '') ?></td>
                <td class="admin-only-col"><?= $routerName ?></td>
                <td class="admin-only-col"><?= !empty($package['mikrotik_profile']) ? esc($package['mikrotik_profile']) : '--' ?></td>
                <td>
                  <?php /* Action-column buttons: ux.css collapses any icon-bearing .btn-sm in a
                           <td> into a 32px icon chip (font-size:0 hides the label), which is the
                           app-wide row-action look. So the label has to live in title/aria-label,
                           or these buttons end up nameless for a screen reader. */ ?>
                  <div class="ipb-row-actions">
                    <button type="button" class="btn btn-sm btn-primary editPackageBtn"
                      title="Edit package" aria-label="Edit package"
                      data-id="<?= esc($package['id'], 'attr') ?>"
                      data-router="<?= esc($package['mikrotik_router_id'] ?? '', 'attr') ?>"
                      data-profile="<?= esc($package['mikrotik_profile'] ?? '', 'attr') ?>"
                      data-toggle="modal"
                      data-target="#editPackageModal">
                      <i class="fa fa-pen-to-square" aria-hidden="true"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-danger deletePackageBtn"
                      title="Delete package" aria-label="Delete package"
                      data-id="<?= esc($package['id'], 'attr') ?>">
                      <i class="fa fa-trash" aria-hidden="true"></i>
                    </button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="9">
                <?= $this->include('components/empty-state', [
                  'icon' => 'fa fa-box-open',
                  'title' => 'No packages yet',
                  'subtitle' => 'Create your first package to start assigning bandwidth and pricing to customers.',
                ]); ?>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
        </div>
    </div>

    <!-- Add Package Modal -->
    <div class="modal fade" id="addPackageModal" tabindex="-1" role="dialog" aria-labelledby="addPackageModalLabel">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title" id="addPackageModalLabel">Add Package</h4>
          </div>
          <div class="modal-body">
            <form id="addPackageForm">
              <div class="form-group">
                <label for="packageName">Package Name</label>
                <input type="text" class="form-control" id="packageName" name="packageName" required>
              </div>
              <div class="form-group">
                <label for="bandwidth">Bandwidth Allocation (MB)</label>
                <input type="number" class="form-control" id="bandwidth" name="bandwidth" required>
              </div>
              <div class="form-group">
                <label for="details">Package Price</label>
                <input type="text" class="form-control" id="details" name="details">
              </div>
              <div class="form-group">
                <label for="pricing_type">Pricing Type</label>
                <select class="form-control" id="pricing_type" name="pricing_type" required>
                  <option value="">--Select--</option>
                  <option value="weekly">Weekly</option>
                  <option value="monthly">Monthly</option>
                  <option value="yearly">Yearly</option>
                </select>
              </div>
              <div class="form-group">
                <label for="preview">Preview day's</label>
                <input type="text" class="form-control" id="preview" name="preview">
              </div>

              <!-- MikroTik Router Dropdown (admin only) -->
              <div class="form-group">
                <label for="add_router_id">MikroTik Router</label>
                <select class="form-control" id="add_router_id" name="mikrotik_router_id">
                  <option value="">-- Select Router --</option>
                  <?php foreach ($routers as $router):
                    $rid   = is_object($router) ? $router->id   : $router['id'];
                    $rname = is_object($router) ? $router->name : $router['name'];
                  ?>
                    <option value="<?= $rid ?>"><?= esc($rname) ?></option>
                  <?php endforeach; ?>
                </select>
                <small class="profile-loading" id="add_profile_loading"><i class="fa fa-spinner fa-spin"></i> Loading profiles...</small>
              </div>

              <!-- PPPoE Profile Dropdown (populated via AJAX) -->
              <div class="form-group">
                <label for="add_mikrotik_profile">PPPoE Profile</label>
                <select class="form-control" id="add_mikrotik_profile" name="mikrotik_profile">
                  <option value="">-- Select Router First --</option>
                </select>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary" id="savePackageBtn">Save Package</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Package Modal -->
    <div class="modal fade" id="editPackageModal" tabindex="-1" role="dialog" aria-labelledby="editPackageModalLabel">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title" id="editPackageModalLabel">Edit Package</h4>
          </div>
          <div class="modal-body">
            <form id="editPackageForm">
              <input type="hidden" id="editPackageId">
              <div class="form-group">
                <label for="editPackageName">Package Name</label>
                <input type="text" class="form-control" id="editPackageName" name="packageName">
              </div>
              <div class="form-group">
                <label for="editBandwidth">Bandwidth Allocation (MB)</label>
                <input type="number" class="form-control" id="editBandwidth" name="bandwidth">
              </div>
              <div class="form-group">
                <label for="editDetails">Package price</label>
                <input type="text" class="form-control" id="editDetails" name="details">
              </div>
              <div class="form-group">
                <label for="editpricingtype">Pricing Type</label>
                <select class="form-control" id="editpricingtype" name="pricing_type">
                  <option value="">--Select--</option>
                  <option value="weekly">Weekly</option>
                  <option value="monthly">Monthly</option>
                  <option value="yearly">Yearly</option>
                </select>
              </div>
              <div class="form-group">
                <label for="editPreview">Preview Day's</label>
                <input type="text" class="form-control" id="editPreview" name="preview">
              </div>

              <!-- MikroTik Router Dropdown -->
              <div class="form-group">
                <label for="edit_router_id">MikroTik Router</label>
                <select class="form-control" id="edit_router_id" name="mikrotik_router_id">
                  <option value="">-- Select Router --</option>
                  <?php foreach ($routers as $router):
                    $rid   = is_object($router) ? $router->id   : $router['id'];
                    $rname = is_object($router) ? $router->name : $router['name'];
                  ?>
                    <option value="<?= $rid ?>"><?= esc($rname) ?></option>
                  <?php endforeach; ?>
                </select>
                <small class="profile-loading" id="edit_profile_loading"><i class="fa fa-spinner fa-spin"></i> Loading profiles...</small>
              </div>

              <!-- PPPoE Profile Dropdown -->
              <div class="form-group">
                <label for="edit_mikrotik_profile">PPPoE Profile</label>
                <select class="form-control" id="edit_mikrotik_profile" name="mikrotik_profile">
                  <option value="">-- Select Router First --</option>
                </select>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary" id="updatePackageBtn">Update Package</button>
          </div>
        </div>
      </div>
    </div>
  </section>
  <!-- /.content -->
</div>
<?= $this->endSection('content'); ?>

<?= $this->section('script'); ?>
<script>
  $(document).ready(function () {
    var csrfToken  = $('meta[name="csrf-token"]').attr('content');
    var csrfHeader = $('meta[name="csrf-header"]').attr('content');

    /* ── Hide admin-only columns from resellers ── */
    var userRole = '<?= session()->get('user_role') ?>';
    if (userRole !== 'super_admin' && userRole !== 'admin') {
      $('.admin-only-col').hide();
    }

    /* ──────────────────────────────────────────
       AJAX: Load PPPoE profiles from a router
    ─────────────────────────────────────────── */
    function loadProfiles(routerId, profileSelect, loadingEl, selectedProfile) {
      profileSelect.html('<option value="">Loading...</option>');
      loadingEl.show();

      if (!routerId) {
        profileSelect.html('<option value="">-- Select Router First --</option>');
        loadingEl.hide();
        return;
      }

      $.ajax({
        url: '/reseller/router-profiles/' + routerId,
        type: 'GET',
        success: function (res) {
          loadingEl.hide();
          profileSelect.html('<option value="">-- Select Profile --</option>');
          if (res.success && res.profiles.length > 0) {
            $.each(res.profiles, function (i, profile) {
              var selected = (profile === selectedProfile) ? 'selected' : '';
              profileSelect.append('<option value="' + profile + '" ' + selected + '>' + profile + '</option>');
            });
          } else {
            profileSelect.append('<option value="">No profiles found</option>');
          }
        },
        error: function () {
          loadingEl.hide();
          profileSelect.html('<option value="">Error loading profiles</option>');
        }
      });
    }

    /* Add modal router change */
    $('#add_router_id').on('change', function () {
      loadProfiles($(this).val(), $('#add_mikrotik_profile'), $('#add_profile_loading'), '');
    });

    /* Edit modal router change */
    $('#edit_router_id').on('change', function () {
      loadProfiles($(this).val(), $('#edit_mikrotik_profile'), $('#edit_profile_loading'), '');
    });

    /* ──────────────────────────────────────────
       Save new package
    ─────────────────────────────────────────── */
    $('#savePackageBtn').on('click', function () {
      var formData = {
        packageName:        $('#packageName').val(),
        bandwidth:          $('#bandwidth').val(),
        details:            $('#details').val(),
        preview:            $('#preview').val(),
        pricing_type:       $('#pricing_type').val(),
        mikrotik_router_id: $('#add_router_id').val(),
        mikrotik_profile:   $('#add_mikrotik_profile').val(),
      };
      $.ajax({
        url: '<?= route_to('reseller.savePackage'); ?>',
        type: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken },
        data: formData,
        success: function (response) {
          if (response.success) {
            alert('Package added successfully!');
            $('#addPackageModal').modal('hide');
            location.reload();
          } else {
            alert('Failed to add package. Please try again.');
          }
        },
        error: function () { alert('An error occurred. Please try again.'); }
      });
    });

    /* ──────────────────────────────────────────
       Edit package — open modal and populate
    ─────────────────────────────────────────── */
    $('.editPackageBtn').on('click', function () {
      var packageId      = $(this).data('id');
      var savedRouter    = $(this).data('router') || '';
      var savedProfile   = $(this).data('profile') || '';
      var url = '/reseller/resellergetPackage/' + encodeURIComponent(packageId);

      $.ajax({
        url: url,
        type: 'GET',
        success: function (response) {
          if (response.success) {
            var pkg = response.package;

            $('#editPackageId').val(pkg.id);
            $('#editPackageName').val(pkg.package_name);
            $('#editBandwidth').val(pkg.bandwidth);
            $('#editDetails').val(pkg.price);
            $('#editPreview').val(pkg.preview);

            /* Pricing type */
            var typeFromServer = (pkg.pricing_type || '').trim().toLowerCase();
            $('#editpricingtype option').prop('selected', false);
            $('#editpricingtype option').each(function () {
              if ($(this).val().toLowerCase() === typeFromServer) $(this).prop('selected', true);
            });
            $('#editpricingtype').trigger('change');

            /* Router */
            var routerId = pkg.mikrotik_router_id || savedRouter || '';
            var profile  = pkg.mikrotik_profile   || savedProfile || '';
            $('#edit_router_id').val(routerId);

            if (routerId) {
              loadProfiles(routerId, $('#edit_mikrotik_profile'), $('#edit_profile_loading'), profile);
            } else {
              $('#edit_mikrotik_profile').html('<option value="">-- Select Router First --</option>');
            }

            $('#editPackageModal').modal('show');
          } else {
            alert('Failed to fetch package details. Please try again.');
          }
        },
        error: function () { alert('An error occurred. Please try again.'); }
      });
    });

    /* ──────────────────────────────────────────
       Update package
    ─────────────────────────────────────────── */
    $('#updatePackageBtn').on('click', function () {
      var packageId = $('#editPackageId').val();
      var formData  = {
        packageName:        $('#editPackageName').val(),
        bandwidth:          $('#editBandwidth').val(),
        details:            $('#editDetails').val(),
        preview:            $('#editPreview').val(),
        pricing_type:       $('#editpricingtype').val(),
        mikrotik_router_id: $('#edit_router_id').val(),
        mikrotik_profile:   $('#edit_mikrotik_profile').val(),
      };
      var url2 = '/reseller/updatePackage/' + encodeURIComponent(packageId);

      $.ajax({
        url: url2,
        type: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken },
        data: formData,
        success: function (response) {
          if (response.success) {
            alert('Package updated successfully!');
            $('#editPackageModal').modal('hide');
            location.reload();
          } else {
            alert('Failed to update package. Please try again.');
          }
        },
        error: function () { alert('An error occurred. Please try again.'); }
      });
    });

    /* ──────────────────────────────────────────
       Delete package
    ─────────────────────────────────────────── */
    $('.deletePackageBtn').on('click', function () {
      var packageId = $(this).data('id');
      var url3 = '/reseller/deletePackage/' + encodeURIComponent(packageId);

      if (confirm('Are you sure you want to delete this package?')) {
        $.ajax({
          url: url3,
          type: 'DELETE',
          headers: { 'X-CSRF-TOKEN': csrfToken },
          success: function (response) {
            if (response.success) {
              alert('Package deleted successfully!');
              location.reload();
            } else {
              alert('Failed to delete package. Please try again.');
            }
          },
          error: function () { alert('An error occurred. Please try again.'); }
        });
      }
    });
  });
</script>
<?= $this->endSection('script'); ?>