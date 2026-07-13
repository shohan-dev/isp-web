<?= $this->extend('layout/main-layout'); ?>

<?= $this->section('css'); ?>
<style>
  .list-group-item-heading {
    font-size: 1.6rem;
    font-weight: bold;
  }

  .list-group-item-text {
    font-size: 1.2rem;
  }

  .modal-header {
    background-color: #007bff;
    color: white;
  }

  .modal-footer .btn {
    margin-right: 5px;
  }
</style>
<?= $this->endSection('css'); ?>

<?= $this->section('content'); ?>
<div class="content-wrapper">
  <section class="content ipb-saas-list">
    
    <?= $this->include('components/page-header', [
      'title' => 'Package Configuration',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Package Configuration'],
      ],
    ]); ?>

<div class="box box-warning">
      <?php if (getSession('user_role') === 'admin'): ?>
        <button class="btn btn-primary" id="syncPackagesBtn">
          <i class="fa fa-plus"></i> Sync Package
        </button>
      <?php endif; ?>

      <div class="box-body">
        <div class="table-responsive">
          <table class="table table-bordered table-striped datatable" width="100%">
          <caption class="sr-only">Package list</caption>
          <thead class="text-nowrap">
            <tr>
              <th scope="col">#</th>
              <th scope="col">Package Name</th>
              <th scope="col">Price</th>
              <th scope="col">Selling Price</th>
              <th scope="col">Bandwidth</th>
              <th scope="col">Package Type</th>
              <th scope="col">Preview</th>
              <?php if (in_array(session()->get('user_role'), ['super_admin', 'admin'])): ?>
                <th scope="col">Router</th>
                <th scope="col">Profile</th>
              <?php endif; ?>
              <th scope="col">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($packages) && is_array($packages)): ?>
              <?php foreach ($packages as $index => $package): ?>
                <tr>
                  <td><?= $index + 1 ?></td>
                  <td>
                    <?= isset($package['package_name']) && !empty($package['package_name']) ? esc($package['package_name']) : '--' ?>
                  </td>
                  <td><?= isset($package['price']) && !empty($package['price']) ? esc($package['price']) : '--' ?></td>
                  <td>
                    <?php 
                      $s_price = isset($package['selling_price']) && !empty($package['selling_price']) && $package['selling_price'] !== '--' ? $package['selling_price'] : null;
                      $c_price = isset($package['price']) && !empty($package['price']) ? $package['price'] : '--';
                      echo esc($s_price ?? $c_price);
                    ?>
                  </td>
                  <td><?= isset($package['bandwidth']) && !empty($package['bandwidth']) ? esc($package['bandwidth']) : '--' ?>
                  </td>
                  <td>
                    <?= isset($package['package_type']) && !empty($package['package_type']) ? esc($package['package_type']) : '--' ?>
                  </td>
                  <td><?= isset($package['preview']) && $package['preview'] !== '' ? esc($package['preview']) : 0 ?></td>
                  <?php if (in_array(session()->get('user_role'), ['super_admin', 'admin'])): ?>
                    <?php
                    $routerName = '--';
                    if (!empty($package['mikrotik_router_id']) && !empty($routers)) {
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
                    <td><?= $routerName ?></td>
                    <td><?= !empty($package['mikrotik_profile']) ? esc($package['mikrotik_profile']) : '--' ?></td>
                  <?php endif; ?>

                  <td>
                    <?php if (session()->get('user_role') === 'admin'): ?>
                      <!-- Use hyphenated data attribute names so jQuery converts them to camelCase -->
                      <button class="btn btn-sm btn-primary editPackagesBtn"
                        data-package-name="<?= isset($package['package_name']) ? esc($package['package_name']) : '--' ?>"
                        data-package-price="<?= isset($package['price']) ? esc($package['price']) : '--' ?>"
                        data-package-selling-price="<?= isset($package['selling_price']) ? esc($package['selling_price']) : '--' ?>"
                        data-package-bandwidth="<?= isset($package['bandwidth']) ? esc($package['bandwidth']) : '--' ?>"
                        data-package-type="<?= isset($package['package_type']) ? esc($package['package_type']) : '--' ?>"
                        data-package-preview="<?= isset($package['preview']) && $package['preview'] !== '' ? esc($package['preview']) : 0 ?>">
                        Edit
                      </button>
                    <?php endif; ?>
                    <button class="btn btn-sm btn-danger deletePackageBtn" data-id="<?= $package['id'] ?>">Delete</button>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="<?= in_array(session()->get('user_role'), ['super_admin', 'admin']) ? 10 : 8 ?>">No packages found.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
        </div>
      </div>
    </div>
  </section>
</div>

<!-- Edit Package Modal -->
<div id="editPackagesModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="editPackagesModalLabel"
  aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editPackagesModalLabel">Edit Package</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <!-- Form fields for editing package details -->
        <div class="form-group">
          <label for="editPackageName">Package Name</label>
          <input type="text" id="editPackageName" class="form-control">
        </div>
        <div class="form-group">
          <label for="editPrice">Price</label>
          <input type="text" id="editPrice" class="form-control">
        </div>
        <div class="form-group">
          <label for="editSellingPrice">Selling Price</label>
          <input type="text" id="editSellingPrice" class="form-control">
        </div>
        <div class="form-group">
          <label for="editBandwidth">Bandwidth</label>
          <input type="text" id="editBandwidth" class="form-control">
        </div>
        <div class="form-group">
          <label for="editPackageType">Package Type</label>
          <input type="text" id="editPackageType" class="form-control">
        </div>
        <div class="form-group">
          <label for="editPreview">Preview</label>
          <input type="text" id="editPreview" class="form-control">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" id="closeModalBtn">Close</button>
        <button type="button" id="updatePackageBtn" class="btn btn-primary">Update</button>
      </div>
    </div>
  </div>
</div>
<?= $this->endSection('content'); ?>

<?= $this->section('script'); ?>
<script>
  $(document).ready(function() {
    var csrfToken = $('meta[name="csrf-token"]').attr('content');
    var userId = <?= json_encode($userId) ?>;
    // When the Edit button is clicked
    $('.editPackagesBtn').on('click', function() {
      // jQuery converts the hyphenated attribute names to camelCase automatically.
      // So "data-package-name" becomes "packageName", etc.
      var packageName = $(this).data('packageName') || '--';
      var packagePrice = $(this).data('packagePrice') || '--';
      var packageSelling = $(this).data('packageSellingPrice') || '--';
      var packageBandwidth = $(this).data('packageBandwidth') || '--';
      var packageType = $(this).data('packageType') || '--';
      var packagePreview = $(this).data('packagePreview') || 0;


      // Fill the modal fields
      $('#editPackageName').val(packageName);
      $('#editPrice').val(packagePrice);
      $('#editSellingPrice').val(packageSelling);
      $('#editBandwidth').val(packageBandwidth);
      $('#editPackageType').val(packageType);
      $('#editPreview').val(packagePreview);

      // If needed, adjust input properties based on user role.
      // For example:
      // var userRole = getSession('user_role');
      // if (userRole === 'resellerAdmin') {
      //   $('#editPackageName, #editPrice, #editBandwidth, #editPackageType, #editPreview').prop('disabled', false);
      // }

      // Show the modal
      $('#editPackagesModal').modal('show');
    });

    // Close modal functionality
    $('#closeModalBtn').on('click', function() {
      $('#editPackagesModal').modal('hide');
    });

    // When update button is clicked
    $('#updatePackageBtn').on('click', function() {
      var packageName = $('#editPackageName').val();
      var packagePrice = $('#editPrice').val();
      var packageSelling = $('#editSellingPrice').val();
      var packageBandwidth = $('#editBandwidth').val();
      var packageType = $('#editPackageType').val();
      var packagePreview = $('#editPreview').val();


      if (!userId) {
        alert('User ID is missing.');
        return;
      }

      $.ajax({
        url: '<?= route_to('resellers.updatePackage') ?>',
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrfToken
        },
        data: {
          userId: userId,
          packageName: packageName,
          price: packagePrice,
          sellingPrice: packageSelling,
          bandwidth: packageBandwidth,
          packageType: packageType,
          preview: packagePreview
        },
        success: function(response) {
          console.log('Server Response:', response);
          if (response.status === 'success') {
            tata.success('Package updated', response.message);
            setTimeout(function() {
              $('#editPackagesModal').modal('hide');
              location.reload();
            }, 1000);
          } else {
            tata.error("Couldn't update package", response.message || 'Failed to update package');
          }
        },
        error: function(xhr, status, error) {
          console.log('AJAX Error:', xhr.responseText);
          alert('An error occurred: ' + xhr.responseText);
        }
      });
    });
    $('.deletePackageBtn').on('click', function() {
      var packageId = $(this).data('id');

      var url3 = '/reseller/deleteUserPackage/' + encodeURIComponent(packageId);

      if (confirm('Are you sure you want to delete this package?')) {
        $.ajax({
          url: url3,
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': csrfToken // Include CSRF token in the headers
          },
          data: {
            userId: userId,

          },
          success: function(response) {
            if (response.success) {
              alert('Package deleted successfully!');
              location.reload();
            } else {
              alert('Failed to delete package. Please try again.');
            }
          },
          error: function() {
            alert('An error occurred. Please try again.');
          }
        });
      }
    });
    // Sync Packages Button
    $('#syncPackagesBtn').on('click', function() {
      var userId = <?= json_encode($userId) ?>;
      if (confirm('Sync packages from the master list for this POP?')) {
        $.ajax({
          url: '<?= route_to('resellers.syncPackages') ?>',
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': csrfToken
          },
          data: {
            userId: userId
          },
          success: function(response) {
            console.log('Sync Response:', response);
            if (response.status === 'success') {
              alert('Packages synchronized successfully');
              location.reload();
            } else {
              alert('Failed to sync packages');
            }
          },
          error: function(xhr) {
            console.log('Sync AJAX Error:', xhr.responseText);
            alert('An error occurred while syncing');
          }
        });
      }
    });
  });
</script>
<?= $this->endSection('script'); ?>