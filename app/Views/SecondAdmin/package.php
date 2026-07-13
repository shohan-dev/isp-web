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

  /* Custom styles for the modal */
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
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <!-- Main content -->
  <section class="content ipb-saas-list">
    
    <?= $this->include('components/page-header', [
      'title' => 'Admins Package Configuration',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Admins Package Configuration'],
      ],
    ]); ?>

    <?= $this->include('components/trial-banner', ['trialUser' => $trialUser ?? null]); ?>

<div class="box box-warning">
      <div class="box-header with-border ipb-box-toolbar">
        <div class="ipb-list-toolbar">
          <div class="ipb-list-toolbar-filters">
            <span class="ipb-filter-label"><i class="fa fa-cube" aria-hidden="true"></i> Packages</span>
          </div>
          <div class="ipb-list-toolbar-actions">
            <?php if (session()->get('user_role') === 'super_admin'): ?>
              <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addPackageModal">
                <i class="fa fa-plus" aria-hidden="true"></i> Package
              </button>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Packages Table -->
      <div class="box-body table-responsive">
        <table class="table table-bordered table-striped datatable">
          <thead class="text-nowrap">
            <tr>
              <th>#</th>
              <th>Package Name</th>
              <?php if (session()->get('user_role') === 'admin'): ?>
                <th>Duration</th>
                <th>price</th>
                <th>Package Type</th>
                <th>Activity</th>
              <?php endif; ?>
              <?php if (session()->get('user_role') === 'user'): ?>
                <th>Price</th>
                <th>Bandwidth</th>
                <th>Package_type</th>
                <th>Preview</th>
              <?php endif; ?>
              <?php if (session()->get('user_role') === 'super_admin'): ?>
                <th>Plan Type</th>
                <th>Pricing</th>
                <th>Preview</th>
              <?php endif; ?>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($packages) && is_array($packages)): ?>
              <?php foreach ($packages as $index => $package): ?>
                <?php
                if (!function_exists('get_value')) {
                  function get_value($item, $key)
                  {
                    if (is_array($item) && isset($item[$key])) {
                      return $item[$key];
                    } elseif (is_object($item) && isset($item->$key)) {
                      return $item->$key;
                    }
                    return null;
                  }
                }
                ?>

                <tr>
                  <td><?= $index + 1 ?></td>
                  <td>
                    <a href="#" class="package-name-link">
                      <?= !empty(get_value($package, 'package_name')) ? get_value($package, 'package_name') : '--' ?>
                    </a>
                  </td>

                  <?php if (session()->get('user_role') === 'admin'): ?>
                    <td><span class="badge-duration"><?= $package['duration'] ?? '--' ?></span></td>
                    <td><span class="badge-price"><?= number_format((float)($package['price'] ?? 0), 2) ?></span></td>
                    <td><span class="badge-type"><?= $package['pricing_type'] ?? 'monthly' ?></span></td>
                    <td>
                      <?php if (!empty($package['Activity']) && $package['Activity'] === 'active'): ?>
                        <span class="status active">Active</span>
                      <?php else: ?>
                        <span class="status inactive">Inactive</span>
                      <?php endif; ?>
                    </td>
                  <?php endif; ?>

                  <?php if (session()->get('user_role') === 'user'): ?>
                    <td>
                      <span class="badge-price">
                        <?php
                        $selling_price = trim(get_value($package, 'selling_price'));
                        $price = trim(get_value($package, 'price'));
                        $display_price = ($selling_price !== '--' && $selling_price !== '') ? $selling_price : (($price !== '--' && $price !== '') ? $price : '0.00');
                        echo number_format((float)$display_price, 2);
                        ?>
                      </span>
                    </td>

                    <td>
                      <span class="badge-duration">
                        <?= !empty(get_value($package, 'bandwidth')) ? get_value($package, 'bandwidth') : '--' ?>
                      </span>
                    </td>

                    <td>
                      <span class="badge-type">
                        <?=
                        !empty(get_value($package, 'pricing_type'))
                          ? get_value($package, 'pricing_type')
                          : (!empty(get_value($package, 'package_type')) ? get_value($package, 'package_type') : '--')
                        ?>
                      </span>
                    </td>

                    <td>
                      <span class="badge-duration" style="background-color: rgba(108, 117, 125, 0.1); color: var(--text-secondary, #6c757d);">
                        <?= !empty(get_value($package, 'preview')) ? get_value($package, 'preview') : '--' ?>
                      </span>
                    </td>
                  <?php endif; ?>

                  <?php if (session()->get('user_role') === 'super_admin'): ?>
                    <td>
                      <?php $pt = $package['plan_type'] ?? 'fixed'; ?>
                      <span class="badge-type"><?= esc($pt ?: 'fixed'); ?></span>
                      <?php if ($pt === 'custom' && !empty($package['assigned_user_id'])): ?>
                        <?php $assignedTenant = getUserById($package['assigned_user_id']); ?>
                        <small style="display:block;color:var(--text-secondary, #6c757d)">for <?= esc($assignedTenant->name ?? ('#' . $package['assigned_user_id'])); ?></small>
                      <?php endif; ?>
                      <?php if (isset($package['is_public']) && (int) $package['is_public'] !== 1 && $pt !== 'custom'): ?>
                        <small style="display:block;color:var(--text-secondary, #6c757d)">hidden</small>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if (($package['plan_type'] ?? 'fixed') === 'payg'): ?>
                        ৳<?= number_format((float) ($package['base_fee'] ?? 0)); ?>/mo +
                        ৳<?= number_format((float) ($package['per_user_rate'] ?? 0), 2); ?>/user
                      <?php else: ?>
                        ৳<?= number_format((float) ($package['price'] ?? 0)); ?> · <?= esc($package['pricing_type'] ?? 'monthly'); ?>
                      <?php endif; ?>
                    </td>
                    <td><?= $package['preview'] ?></td>
                  <?php endif; ?>
                  <td>
                    <?php
                    $subscriptionStatus = getUserById(session()->get('user_id'))->subscription_status;
                    $pendingPackageId = $pending_package_id ?? null;
                    ?>

                    <?php if (session()->get('user_role') === 'super_admin'): ?>
                      <button class="btn btn-sm btn-primary editPackageBtn" data-id="<?= $package['id'] ?>" data-toggle="modal"
                        data-target="#editPackageModal">Edit</button>
                      <button class="btn btn-sm btn-danger deletePackageBtn" data-id="<?= $package['id'] ?>">Delete</button>
                    <?php endif; ?>
                    <?php if (session()->get('user_role') === 'admin' || session()->get('user_role') === 'user'): ?>

                      <?php
                      $pkgId = !empty(get_value($package, 'id')) ? get_value($package, 'id') : null;
                      $pkgName = get_value($package, 'package_name') ?? 'Package';
                      ?>

                      <?php if (!empty($pkgId) && (string) $package_id === (string) $pkgId && empty($pendingPackageId)): ?>
                        <button class="btn btn-sm btn-success" disabled>Current</button>

                      <?php elseif (!empty($pkgId) && (string) $pendingPackageId === (string) $pkgId): ?>
                        <button class="btn btn-sm btn-warning activatePackageBtn" data-id="<?= $pkgId ?>"
                          data-name="<?= esc($pkgName, 'attr'); ?>">Pending — Pay</button>

                      <?php elseif (!empty($pkgId)): ?>
                        <button class="btn btn-sm btn-primary activatePackageBtn" data-id="<?= $pkgId ?>"
                          data-name="<?= esc($pkgName, 'attr'); ?>">Switch plan</button>

                      <?php endif; ?>


                      <script>
                        console.log("Subscription status: '<?= $subscriptionStatus ?>'");
                      </script>


                    <?php endif; ?>

                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="6">No packages found.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Package change disclaimer modal (sAdmin) -->
      <div class="modal fade" id="packageChangeModal" tabindex="-1" role="dialog" aria-labelledby="packageChangeModalLabel">
        <div class="modal-dialog" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h4 class="modal-title" id="packageChangeModalLabel">Confirm package change</h4>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
            </div>
            <div class="modal-body">
              <p><strong id="pcTargetName">New package</strong></p>
              <ul style="padding-left:18px;margin-bottom:12px">
                <li>Your <strong>current package stays active</strong> until payment completes.</li>
                <li>The new package is <strong>pending</strong> until you pay the invoice.</li>
                <li>You can pay now or anytime from <strong>My Payment</strong>.</li>
              </ul>
              <p class="text-muted" style="margin:0" id="pcAmountLine" data-bs-toggle="tooltip"
                title="Prorated based on days left in your billing period"></p>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-default" id="pcPayLaterBtn">Pay later</button>
              <button type="button" class="btn btn-primary" id="pcPayNowBtn">Pay now</button>
            </div>
          </div>
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
                  <label for="plan_type">Plan Type</label>
                  <select class="form-control plan-type-select" id="plan_type" name="plan_type" data-scope="add">
                    <option value="fixed">Fixed monthly plan</option>
                    <option value="payg">Pay-As-You-Go (wallet)</option>
                    <option value="custom">Custom (pinned to one tenant)</option>
                  </select>
                </div>
                <div class="form-group">
                  <label for="packageName">Package Name</label>
                  <input type="text" class="form-control" id="packageName" name="packageName" required>
                </div>
                <div class="form-group">
                  <label for="duration">Customer Limit (0 = unlimited)</label>
                  <input type="number" class="form-control" id="duration" name="duration" required>
                </div>
                <div class="form-group plan-fixed-fields" data-scope="add">
                  <label for="price">Price</label>
                  <input type="text" class="form-control" id="price" name="price">
                </div>
                <div class="form-group plan-fixed-fields" data-scope="add">
                  <label for="pricing_type">Pricing Type</label>
                  <select class="form-control" id="pricing_type" name="pricing_type">
                    <option value="">--Select--</option>
                    <option value="weekly">Weekly</option>
                    <option value="monthly">Monthly</option>
                    <option value="yearly">Yearly</option>
                  </select>
                </div>
                <div class="plan-payg-fields" data-scope="add" hidden>
                  <div class="form-group">
                    <label for="base_fee">Monthly Platform Fee (৳)</label>
                    <input type="number" step="0.01" class="form-control" id="base_fee" name="base_fee" value="500">
                  </div>
                  <div class="form-group">
                    <label for="per_user_rate">Per Customer Rate — billed on total customers, active or not (৳/user/mo)</label>
                    <input type="number" step="0.01" class="form-control" id="per_user_rate" name="per_user_rate" value="1.50">
                  </div>
                  <div class="form-group">
                    <label for="min_topup">Minimum Wallet Top-up (৳)</label>
                    <input type="number" step="1" class="form-control" id="min_topup" name="min_topup" value="750">
                  </div>
                  <div class="form-group">
                    <label for="addons">Add-ons (JSON: [{"key","label","price"}])</label>
                    <textarea class="form-control" id="addons" name="addons" rows="4" placeholder='[{"key":"sms","label":"SMS Credits","price":200}]'></textarea>
                  </div>
                </div>
                <div class="plan-custom-fields" data-scope="add" hidden>
                  <div class="form-group">
                    <label for="assigned_user_id">Assigned Tenant</label>
                    <select class="form-control" id="assigned_user_id" name="assigned_user_id">
                      <option value="">-- Select tenant --</option>
                      <?php foreach (($tenants ?? []) as $tenant): ?>
                        <option value="<?= (int) $tenant->id; ?>"><?= esc($tenant->name); ?> (<?= esc($tenant->mobile); ?>)</option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
                <div class="form-group">
                  <label for="trial_days">Free Trial Days (0 = use preview days)</label>
                  <input type="number" class="form-control" id="trial_days" name="trial_days" value="0">
                </div>
                <div class="form-group">
                  <label for="is_public">Visibility</label>
                  <select class="form-control" id="is_public" name="is_public">
                    <option value="1">Public (landing page &amp; registration)</option>
                    <option value="0">Hidden</option>
                  </select>
                </div>
                <div class="form-group">
                  <label for="sort_order">Sort Order (landing page)</label>
                  <input type="number" class="form-control" id="sort_order" name="sort_order" value="0">
                </div>
                <div class="form-group">
                  <label for="preview">Preview day's</label>
                  <input type="text" class="form-control" id="preview" name="preview">
                </div>
                <div class="form-group">
                  <label for="features">Package Features (Prefix with + for checked, - for unchecked, one per line)</label>
                  <textarea class="form-control" id="features" name="features" rows="5" placeholder="+ Basic Features&#10;+ Email Support&#10;- Premium Features&#10;- 24/7 Support"></textarea>
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
                  <label for="editPlanType">Plan Type</label>
                  <select class="form-control plan-type-select" id="editPlanType" name="plan_type" data-scope="edit">
                    <option value="fixed">Fixed monthly plan</option>
                    <option value="payg">Pay-As-You-Go (wallet)</option>
                    <option value="custom">Custom (pinned to one tenant)</option>
                  </select>
                </div>
                <div class="form-group">
                  <label for="editPackageName">Package Name</label>
                  <input type="text" class="form-control" id="editPackageName" name="packageName">
                </div>
                <div class="form-group">
                  <label for="editDuration">Customer Limit (0 = unlimited)</label>
                  <input type="number" class="form-control" id="editDuration" name="duration">
                </div>
                <div class="form-group plan-fixed-fields" data-scope="edit">
                  <label for="editPrice">Price</label>
                  <input type="text" class="form-control" id="editPrice" name="price">
                </div>
                <div class="form-group plan-fixed-fields" data-scope="edit">
                  <label for="editpricingtype">Pricing Type</label>
                  <select class="form-control" id="editpricingtype" name="pricing_type">
                    <option value="">--Select--</option>
                    <option value="weekly">Weekly</option>
                    <option value="monthly">Monthly</option>
                    <option value="yearly">Yearly</option>
                  </select>
                </div>
                <div class="plan-payg-fields" data-scope="edit" hidden>
                  <div class="form-group">
                    <label for="editBaseFee">Monthly Platform Fee (৳)</label>
                    <input type="number" step="0.01" class="form-control" id="editBaseFee" name="base_fee">
                  </div>
                  <div class="form-group">
                    <label for="editPerUserRate">Per Customer Rate — billed on total customers, active or not (৳/user/mo)</label>
                    <input type="number" step="0.01" class="form-control" id="editPerUserRate" name="per_user_rate">
                  </div>
                  <div class="form-group">
                    <label for="editMinTopup">Minimum Wallet Top-up (৳)</label>
                    <input type="number" step="1" class="form-control" id="editMinTopup" name="min_topup">
                  </div>
                  <div class="form-group">
                    <label for="editAddons">Add-ons (JSON: [{"key","label","price"}])</label>
                    <textarea class="form-control" id="editAddons" name="addons" rows="4"></textarea>
                  </div>
                </div>
                <div class="plan-custom-fields" data-scope="edit" hidden>
                  <div class="form-group">
                    <label for="editAssignedUserId">Assigned Tenant</label>
                    <select class="form-control" id="editAssignedUserId" name="assigned_user_id">
                      <option value="">-- Select tenant --</option>
                      <?php foreach (($tenants ?? []) as $tenant): ?>
                        <option value="<?= (int) $tenant->id; ?>"><?= esc($tenant->name); ?> (<?= esc($tenant->mobile); ?>)</option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
                <div class="form-group">
                  <label for="editTrialDays">Free Trial Days (0 = use preview days)</label>
                  <input type="number" class="form-control" id="editTrialDays" name="trial_days">
                </div>
                <div class="form-group">
                  <label for="editIsPublic">Visibility</label>
                  <select class="form-control" id="editIsPublic" name="is_public">
                    <option value="1">Public (landing page &amp; registration)</option>
                    <option value="0">Hidden</option>
                  </select>
                </div>
                <div class="form-group">
                  <label for="editSortOrder">Sort Order (landing page)</label>
                  <input type="number" class="form-control" id="editSortOrder" name="sort_order">
                </div>
                <div class="form-group">
                  <label for="editPreview">Preview Day's</label>
                  <input type="text" class="form-control" id="editPreview" name="preview">
                </div>
                <div class="form-group">
                  <label for="editFeatures">Package Features (Prefix with + for checked, - for unchecked, one per line)</label>
                  <textarea class="form-control" id="editFeatures" name="features" rows="5" placeholder="+ Basic Features&#10;+ Email Support&#10;- Premium Features&#10;- 24/7 Support"></textarea>
                </div>
                <div class="form-group">
                  <label for="editActivity">Activity</label>
                  <select class="form-control" id="editActivity" name="Activity">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
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

  .package-name-link {
    color: #3f51b5;
    /* Deep Indigo/Blue */
    font-weight: 700;
    font-size: 14px;
    text-decoration: none;
    transition: color 0.2s;
    display: block;
  }

  .package-name-link:hover {
    color: #303f9f;
    /* Slightly darker on hover */
    text-decoration: none;
  }

  .badge-duration {
    background: #E8EAF6;
    color: #5C6BC0;
    padding: 6px 12px;
    border-radius: 6px;
    font-weight: 600;
  }

  .badge-price {
    color: #2E7D32;
    font-weight: 700;
    font-size: 1.1em;
  }

  .badge-type {
    background: #FFF3E0;
    color: #EF6C00;
    padding: 5px 10px;
    border-radius: 6px;
    text-transform: lowercase;
  }

  .badge-activity {
    background: #E8F5E9;
    color: #43A047;
    padding: 5px 10px;
    border-radius: 6px;
  }

  .btn-primary {
    background: var(--primary-gradient);
    color: white;
  }

  .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.4);
  }

  .btn-danger {
    background: var(--danger-gradient);
    color: white;
  }

  .btn-danger:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(220, 38, 38, 0.4);
  }

  .status {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 500;
  }

  .status.active {
    background-color: rgba(34, 197, 94, 0.15);
    color: #16a34a;
  }

  .status.inactive {
    background-color: rgba(239, 68, 68, 0.15);
    color: #dc2626;
  }
</style>
<script>
  $(document).ready(function() {
    var csrfToken = $('meta[name="csrf-token"]').attr('content');
    var csrfHeader = $('meta[name="csrf-header"]').attr('content');

    // Show/hide plan-type specific field groups in both modals
    function togglePlanFields(scope, planType) {
      $('.plan-payg-fields[data-scope="' + scope + '"]').prop('hidden', planType !== 'payg');
      $('.plan-custom-fields[data-scope="' + scope + '"]').prop('hidden', planType !== 'custom');
      $('.plan-fixed-fields[data-scope="' + scope + '"]').prop('hidden', planType === 'payg');
    }
    $(document).on('change', '.plan-type-select', function() {
      togglePlanFields($(this).data('scope'), $(this).val());
    });

    // Save new package
    $('#savePackageBtn').on('click', function() {
      var formData = {
        packageName: $('#packageName').val(),
        duration: $('#duration').val(),
        price: $('#price').val(),
        preview: $('#preview').val(),
        // No #Activity element exists in the Add modal (only #editActivity, in the
        // Edit modal), so this always sent `activity: undefined`. Harmless only
        // because Admin::savePackage ignores it and hardcodes Activity = 'Active'
        // for new packages. Dropped the dead field rather than inventing a control.
        pricing_type: $('#pricing_type').val() || 'monthly',
        features: $('#features').val(),
        plan_type: $('#plan_type').val(),
        base_fee: $('#base_fee').val(),
        per_user_rate: $('#per_user_rate').val(),
        min_topup: $('#min_topup').val(),
        addons: $('#addons').val(),
        assigned_user_id: $('#assigned_user_id').val(),
        trial_days: $('#trial_days').val(),
        is_public: $('#is_public').val(),
        sort_order: $('#sort_order').val()
      };
      $.ajax({
        url: '<?= route_to('Admin.savePackage'); ?>',
        type: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrfToken // Include CSRF token in the headers
        },
        data: formData,
        success: function(response) {
          if (response.success) {
            alert('Package added successfully!');
            $('#addPackageModal').modal('hide');
            location.reload();
          } else {
            alert('Failed to add package. Please try again.');
          }
        },
        error: function() {
          alert('An error occurred. Please try again.');
        }
      });
    });

    // Edit package
    $('.editPackageBtn').on('click', function() {
      var packageId = $(this).data('id');

      var url = '/admins/getPackage/' + encodeURIComponent(packageId);

      // Fetch package details
      $.ajax({
        url: url,
        type: 'GET',
        success: function(response) {
          if (response.success) {
            var package = response.package;
            $('#editPackageId').val(package.id);
            $('#editPackageName').val(package.package_name);
            $('#editDuration').val(package.duration);
            $('#editPrice').val(package.price);
            $('#editActivity').val(package.Activity);
            $('#editpricingtype').val((package.pricing_type || '').trim());
            $('#editPreview').val(package.preview);
            $('#editFeatures').val(package.features);

            var planType = package.plan_type || 'fixed';
            $('#editPlanType').val(planType);
            $('#editBaseFee').val(package.base_fee || 0);
            $('#editPerUserRate').val(package.per_user_rate || 0);
            $('#editMinTopup').val(package.min_topup || 0);
            $('#editAddons').val(package.addons || '');
            $('#editAssignedUserId').val(package.assigned_user_id || '');
            $('#editTrialDays').val(package.trial_days || 0);
            $('#editIsPublic').val(package.is_public === null || typeof package.is_public === 'undefined' ? '1' : String(package.is_public));
            $('#editSortOrder').val(package.sort_order || 0);
            togglePlanFields('edit', planType);

            // Show the modal after setting values
            $('#editPackageModal').modal('show');

            console.log('pricing_type ...:', package.pricing_type);
          } else {
            alert('Failed to fetch package details. Please try again.');
          }
        },
        error: function() {
          alert('An error occurred. Please try again.');
        }
      });
    });

    // Activate / switch package (sAdmin)
    var pcState = { packageId: null, packageName: '', paymentUrl: '', invoice: '' };

    $('.activatePackageBtn').on('click', function() {
      var packageId = $(this).data('id');
      var packageName = $(this).data('name') || 'this package';
      var url = '/admins/activatePackage/' + encodeURIComponent(packageId);

      $.ajax({
        url: url,
        type: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken },
        success: function(response) {
          if (!response.success) {
            tata.error("Couldn't request package change", response.message || 'Failed to request package change.');
            return;
          }
          pcState.packageId = packageId;
          pcState.packageName = packageName;
          pcState.paymentUrl = response.payment_url || '';
          pcState.invoice = response.invoice || '';
          $('#pcTargetName').text(packageName);
          var amountLine = response.amount
            ? 'Estimated charge: ৳' + Number(response.amount).toLocaleString()
            : 'An invoice will be created for this package change.';
          $('#pcAmountLine').text(amountLine);
          $('#packageChangeModal').modal('show');
        },
        error: function(xhr) {
          var msg = 'Failed to request package change.';
          try {
            var result = JSON.parse(xhr.responseText);
            msg = result.response || result.message || msg;
          } catch (e) {}
          tata.error("Couldn't request package change", msg);
        }
      });
    });

    $('#pcPayNowBtn').on('click', function() {
      if (pcState.paymentUrl) {
        window.location.href = pcState.paymentUrl;
      } else {
        $('#packageChangeModal').modal('hide');
      }
    });

    $('#pcPayLaterBtn').on('click', function() {
      $('#packageChangeModal').modal('hide');
      var inv = pcState.invoice ? 'Invoice ' + pcState.invoice + ' ' : '';
      tata.info('Payment saved', inv + 'for ' + pcState.packageName
        + ' is in My Payment — open My Payment and tap Pay when ready.', { duration: 6000 });
      setTimeout(function() { location.reload(); }, 800);
    });


    // Update package
    $('#updatePackageBtn').on('click', function() {
      var packageId = $('#editPackageId').val();
      var formData = {
        packageName: $('#editPackageName').val(),
        duration: $('#editDuration').val(),
        price: $('#editPrice').val(),
        Activity: $('#editActivity').val(),
        preview: $('#editPreview').val(),
        pricing_type: $('#editpricingtype').val() || 'monthly',
        features: $('#editFeatures').val(),
        plan_type: $('#editPlanType').val(),
        base_fee: $('#editBaseFee').val(),
        per_user_rate: $('#editPerUserRate').val(),
        min_topup: $('#editMinTopup').val(),
        addons: $('#editAddons').val(),
        assigned_user_id: $('#editAssignedUserId').val(),
        trial_days: $('#editTrialDays').val(),
        is_public: $('#editIsPublic').val(),
        sort_order: $('#editSortOrder').val()
      };
      var url2 = '/admins/updatePackage/' + encodeURIComponent(packageId);


      $.ajax({
        url: url2,
        type: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrfToken // Include CSRF token in the headers
        },
        data: formData,
        success: function(response) {
          if (response.success) {
            alert('Package updated successfully!');
            $('#editPackageModal').modal('hide');
            location.reload();
          } else {
            alert('Failed to update package. Please try again.');
          }
        },
        error: function() {
          alert('An error occurred. Please try again.');
        }
      });
    });

    // Delete package
    $('.deletePackageBtn').on('click', function() {
      var packageId = $(this).data('id');

      var url3 = '/admins/deletePackage/' + encodeURIComponent(packageId);

      if (confirm('Are you sure you want to delete this package?')) {
        $.ajax({
          url: url3,
          type: 'DELETE',
          headers: {
            'X-CSRF-TOKEN': csrfToken // Include CSRF token in the headers
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
  });
</script>
<?= $this->endSection('script'); ?>