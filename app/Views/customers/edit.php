<?= $this->extend('layout/main-layout'); ?>

<?= $this->section('content'); ?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <!-- Main content -->
  <section class="content ipb-saas-list">

    
    <?= $this->include('components/page-header', [
      'title' => 'Edit Customer',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Customers', 'url' => route_to('route.customer')],
        ['label' => 'Edit Customer'],
      ],
    ]); ?>

<div class="box box-warning">

      <?= form_open('', 'id="form"'); ?>

      <div class="box-body">

        <div class="row">

          <div class="col-lg-12">
            <h4 class="text-primary">Account Info</h4>
            <br />
          </div>

          <div class="form-group col-lg-6">
            <label>Customer Name</label>

            <?= form_input([
              'name' => 'name',
              'class' => 'form-control',
              'value' => $details->name,
            ]); ?>

            <small id="name-error" class="error text-danger"></small>
          </div>
          <?php if (getSession('user_role') === 'admin'): ?>
            <div class="col-xs-6">
              <div class="form-group">
                <label>Package</label>

                <?php
                // Prepare package options
                $data = [];
                if (empty($packages)) {
                  $data[''] = 'No package found!';
                } else {
                  $data = ['' => '--Select--'];
                  foreach ($packages as $package) {
                    $packageId = is_object($package) ? $package->id : $package['id'];
                    $packageName = is_object($package) ? $package->package_name : $package['package_name'];
                    $data[$packageId] = $packageName;
                  }
                }

                $selected = '';
                $showReadonly = false;
                $readonlyValue = '';

                if (!empty($packageIds) && is_array($packageIds)) {
                  $uniquePackages = array_unique($packageIds);

                  if (count($uniquePackages) === 1) {
                    // Single package
                    $selected = reset($uniquePackages);
                  } else {
                    // Multiple packages
                    $showReadonly = true;
                    $packageNames = [];
                    foreach ($uniquePackages as $pkgId) {
                      if (isset($data[$pkgId])) {
                        $packageNames[] = $data[$pkgId];
                      }
                    }
                    $readonlyValue = implode(', ', $packageNames);
                    $selected = reset($uniquePackages); // Preselect first package in dropdown
                  }
                } else {
                  if (isset($details->package_id)) {
                    $selected = $details->package_id;
                  }
                }
                ?>

                <?php if ($showReadonly): ?>
                  <!-- Show comma-separated readonly input -->
                  <input type="text"
                    id="packageReadonly"
                    class="form-control"
                    value="<?= esc($readonlyValue) ?>"
                    readonly
                    style="cursor: pointer;">
                <?php endif; ?>

                <!-- Dropdown always exists -->
                <?= form_dropdown('package_id', $data, $selected, [
                  'class' => 'form-control',
                  'id' => 'packageDropdown',
                  'style' => $showReadonly ? 'display:none;' : ''
                ]) ?>

                <small id="package_id-error" class="error text-danger"></small>
              </div>
            </div>
          <?php endif; ?>

          <div class="form-group col-lg-6">
            <label>Service Area</label>

            <?php $data = array();

            if (empty($areas)):

              $data[''] = 'No service area found!';

            else:

              $data = ['' => '--Select--'];

              foreach ($areas as $area) {
                $data[$area->id] = $area->area_name;
              }

            endif;

            echo form_dropdown('area_id', $data, $details->area_id, 'class="form-control"'); ?>

            <small id="area_id-error" class="error text-danger"></small>
          </div>

          <div class="form-group col-lg-6">
            <label>Service Sub Area*</label>

            <?php $data = array();

            if (empty($areas)):

              $data[''] = 'No service sub-area found!';

            else:

              $data = ['' => '--Select--'];



            endif;

            echo form_dropdown('sub_area_id', $data, $ConnDetails[0]['sub_area_id'] ?? '', 'class="form-control"'); ?>

            <small id="sub_area_id-error" class="error text-danger"></small>
          </div>

          <div class="form-group col-lg-6">
            <label>Mobile Number</label>

            <?= form_input([
              'type' => 'number',
              'name' => 'mobile',
              'class' => 'form-control',
              'value' => $details->mobile,
              'placeholder' => '--'
            ]); ?>

            <small id="mobile-error" class="error text-danger"></small>
          </div>

          <div class="form-group col-lg-6">
            <label>Email Id</label>

            <?= form_input([
              'type' => 'text',
              'name' => 'email',
              'class' => 'form-control',
              'value' => $details->email,
              'placeholder' => '--'
            ]); ?>

            <small id="email-error" class="error text-danger"></small>
          </div>

          <div class="form-group col-lg-12">
            <label>Address</label>

            <?= form_textarea([
              'name' => 'address',
              'class' => 'form-control',
              'style' => 'max-height: 80px',
              'value' => $details->address,
            ]); ?>

            <small id="address-error" class="error text-danger"></small>
          </div>
          <div class="form-group col-lg-6">
            <label>NID Number</label>

            <?= form_input([
              'type' => 'number',
              'name' => 'nid_number',
              'class' => 'form-control',
              'value' => $details->nid_number,
              'placeholder' => '--'
            ]); ?>

            <small id="nid_number-error" class="error text-danger"></small>
          </div>

          <div class="form-group col-lg-6">
            <label>Password</label>

            <?= form_input([
              'type' => 'password',
              'name' => 'password',
              'class' => 'form-control',
              'placeholder' => '--'
            ]); ?>

            <small id="password-error" class="error text-danger"></small>
            <p><small class="text-info">Keep it blank if you dont want to change the password</small></p>
          </div>

          <div class="form-group col-lg-6">
            <label>Rewrite Password</label>

            <?= form_input([
              'type' => 'password',
              'name' => 're_password',
              'class' => 'form-control',
              'placeholder' => '--'
            ]); ?>

            <small id="re_password-error" class="error text-danger"></small>
            <p><small class="text-info">Keep it blank if you dont want to change the password</small></p>
          </div>

          <div class="form-group col-lg-12">
            <label>Acc. Status</label>

            <div class="radio">
              <label class="radio-inline">
                <?= form_radio([
                  'name' => 'status',
                  'value' => 'active',
                  'checked' => $details->status === 'active',
                ]); ?>
                Active
              </label>

              <label class="radio-inline">
                <?= form_radio([
                  'name' => 'status',
                  'value' => 'inactive',
                  'checked' => $details->status === 'inactive',
                ]); ?>
                Inactive
              </label>
            </div>

            <small id="status-error" class="error text-danger"></small>
          </div>

          <div class="col-lg-12">
            <h4 class="text-primary">Mikrotik PPPoE</h4>
            <br />
          </div>

          <div class="form-group col-lg-6">
            <label>Mikrotik Router</label>

            <?= form_input([
              'class' => 'form-control',
              'value' => $router ?? '--',
              'readonly' => 'readonly',
            ]); ?>
          </div>

          <div class="form-group col-lg-6">
            <label>PPPoE Username</label>

            <?= form_input([
              'name' => 'pppoe_name',
              'class' => 'form-control',
              'value' => $pppoe_name ?? '--',
              'placeholder' => '--'
            ]); ?>

            <small id="pppoe_name-error" class="error text-danger"></small>
          </div>

          <div class="form-group col-lg-6">
            <label>PPPoE Password</label>

            <?= form_input([
              'type' => 'password',
              'name' => 'pppoe_password',
              'class' => 'form-control',
              'value' => !empty($pppoe_password) ? $pppoe_password : '',
              'placeholder' => '--'
            ]); ?>

            <small id="pppoe_password-error" class="error text-danger"></small>
          </div>

          <div class="form-group col-lg-6">
            <label>PPPoE Service</label>

            <?php

            $data = [
              'any' => 'any',
              'async' => 'async',
              'I2tp' => 'I2tp',
              'ovpn' => 'ovpn',
              'pppoe' => 'pppoe',
              'pptp' => 'pptp',
              'sstp' => 'sstp',
            ];

            echo form_dropdown('pppoe_service', $data, $pppoe_service, 'class="form-control"'); ?>

            <small id="pppoe_service-error" class="error text-danger"></small>
          </div>
          <?php if (getSession('user_role') === 'admin'): ?>

            <div class="form-group col-lg-6">
              <label>PPPoE Profile</label>

              <?php
              $data = array();

              if (empty($profiles)):
                $data[''] = 'No profile found!';
              else:
                $data = ['' => '--Select--'];

                // Initially show all profiles, but JavaScript will filter them
                foreach ($profiles as $profile) {
                  $data[$profile] = $profile;
                }
              endif;

              echo form_dropdown(
                'pppoe_profile',
                $data,
                isset($pppoe_profile) ? $pppoe_profile : '',
                'class="form-control"'
              );

              ?>

              <small id="pppoe_profile-error" class="error text-danger"></small>
            </div>
          <?php endif; ?>


          <div class="form-group col-lg-6">
            <div class="checkbox">
              <label>
                <?= form_checkbox([
                  'name' => 'auto_disconnect',
                  'checked' => ($details->auto_disconnect === 'yes'),
                  'value' => 'yes'
                ]); ?>
                Auto Disconnect
              </label>
            </div>
            <small id="auto_disconnect-error" class="error text-danger"></small>
          </div>

          <div class="col-lg-12">
            <h4 class="text-primary">Connection Details</h4>
            <br />
          </div>

          <div class="form-group col-lg-6">
            <label>Connection Type</label>
            <?php
            $data = [
              'utp' => 'UTP',
              'optional_fiber' => 'Optical Fiber',
              'wireless' => 'Wireless',
            ];
            echo form_dropdown('connection_type', $data, $ConnDetails[0]['connection_type'] ?? '', 'class="form-control"');
            ?>
            <small id="connection_type-error" class="error text-danger"></small>
          </div>

          <div class="form-group col-lg-6 utp-fields">
            <label>Cable Requirement in Metre</label>
            <?= form_input([
              'type' => 'number',
              'name' => 'cable_requirement',
              'class' => 'form-control',
              'value' => $ConnDetails[0]['cable_requirement'] ?? ''
            ]); ?>
            <small id="cable_requirement-error" class="error text-danger"></small>
          </div>

          <div class="form-group col-lg-6 fiber-fields">
            <label>Fiber Code</label>
            <?= form_input([
              'name' => 'fiber_code',
              'class' => 'form-control',
              'value' => $ConnDetails[0]['fiber_code'] ?? ''
            ]); ?>
            <small id="fiber_code-error" class="error text-danger"></small>
          </div>

          <div class="form-group col-lg-6 fiber-fields">
            <label>Number of Core</label>
            <?= form_input([
              'type' => 'number',
              'name' => 'number_of_core',
              'class' => 'form-control',
              'value' => $ConnDetails[0]['number_of_core'] ?? ''
            ]); ?>
            <small id="number_of_core-error" class="error text-danger"></small>
          </div>

          <div class="form-group col-lg-6 utp-fields">
            <label>Core Color</label>
            <?= form_input([
              'name' => 'core_color',
              'class' => 'form-control',
              'value' => $ConnDetails[0]['core_color'] ?? ''
            ]); ?>
            <small id="core_color-error" class="error text-danger"></small>
          </div>

          <div class="form-group col-lg-6">
            <label>Client Type</label>
            <?php
            $data = [
              'home' => 'Home',
              'office' => 'Office',
            ];
            echo form_dropdown('client_type', $data, $ConnDetails[0]['client_type'] ?? '', 'class="form-control"');
            ?>
            <small id="client_type-error" class="error text-danger"></small>
          </div>

          <div class="form-group col-lg-6">
            <label>Billing Status</label>
            <?php
            $data = [
              'active' => 'Active',
              'inactive' => 'Inactive',
              'personal' => 'Personal',
            ];
            $current_billing_status = $ConnDetails[0]['billing_status'] ?? '';
            $roleToCheck = strtolower(trim(session()->get('user_role') ?? getSession('user_role') ?? ''));
            if (in_array($roleToCheck, ['super_admin', 'admin', 'reselleradmin']) || userHasPermission('customer', 'free_customer_create') || strtolower($current_billing_status) === 'free') {
              $data['free'] = 'Free';
            }
            echo form_dropdown('billing_status', $data, $ConnDetails[0]['billing_status'] ?? '', 'class="form-control"');
            ?>
            <small id="billing_status-error" class="error text-danger"></small>
          </div>
          <div class="form-group col-lg-6 ">
            <label>OTC</label>
            <?= form_input([
              'name' => 'otc',
              'class' => 'form-control',
              'placeholder' => 'OTC',
              'value' => $ConnDetails[0]['otc'] ?? ''
            ]); ?>
            <small id="otc-error" class="error text-danger"></small>
          </div>

        </div>

      </div>
      <div class="box-footer">
        <?= form_button([
          "content" => "Update Customer",
          "class" => "btn",
          "style" => "background: linear-gradient(135deg, #ff8000ff, #fb6908ff, #f3b393ff); round: 8px; border: none;",
          "type" => "submit",
        ]); ?>
      </div>

      <?= form_close(); ?>
    </div>

  </section>
  <!-- /.content -->
</div>

<?= $this->endSection('content'); ?>

<?= $this->section('script'); ?>

<script>
  $(document).ready(function() {
    const userRole = "<?= session()->get('user_role'); ?>";

    function toggleConnectionFields() {
      var connectionType = $('select[name="connection_type"]').val();
      if (connectionType === 'utp') {
        $('.utp-fields').show();
        $('.fiber-fields').hide();
      } else if (connectionType === 'optional_fiber') {
        $('.utp-fields').hide();
        $('.fiber-fields').show();
      } else {
        // For 'wireless' or any other type, hide both groups
        $('.utp-fields, .fiber-fields').hide();
      }
    }

    // Initial check when the page loads
    toggleConnectionFields();

    // Check on change event
    $('select[name="connection_type"]').on('change', function() {
      toggleConnectionFields();
    });

    <?php if (!empty($mikrotik_pending)): ?>
    // Page-load-performance audit (Axis1 #3): the live PPPoE name/password/
    // service/profile list used to be fetched from MikroTik synchronously
    // before this page could render at all — and the whole form was replaced
    // by an error page if the router was offline. The form above already
    // renders with the last-known (DB) values; this fills in the live values
    // once the router answers, without blocking first paint or edits to the
    // customer's other fields.
    $.ajax({
      url: '<?= route_to('route.customer.getEditMikrotikInfo', $details->id); ?>',
      type: 'GET',
      dataType: 'json'
    }).done(function(resp) {
      if (!resp || !resp.ok) return;

      $('input[name="pppoe_name"]').val(resp.pppoe_name || '--');
      $('input[name="pppoe_password"]').val(resp.pppoe_password || '');
      $('select[name="pppoe_service"]').val(resp.pppoe_service || 'pppoe');

      var $profileSelect = $('select[name="pppoe_profile"]');
      if ($profileSelect.length) {
        var current = $profileSelect.val();
        var profiles = resp.profiles || [];
        $profileSelect.empty();
        if (!profiles.length) {
          $profileSelect.append($('<option>', {value: '', text: 'No profile found!'}));
        } else {
          $profileSelect.append($('<option>', {value: '', text: '--Select--'}));
          profiles.forEach(function(p) {
            $profileSelect.append($('<option>', {value: p, text: p}));
          });
          if (current && profiles.indexOf(current) !== -1) {
            $profileSelect.val(current);
          } else if (resp.pppoe_profile && profiles.indexOf(resp.pppoe_profile) !== -1) {
            $profileSelect.val(resp.pppoe_profile);
          }
        }
      }
    });
    <?php endif; ?>


    // // Filter PPPoE profiles based on selected package - EXACT NUMERIC MATCH
    // function filterProfilesByPackage(selectedPackageName) {
    //   var profileDropdown = $('select[name="pppoe_profile"]');
    //   var allProfiles = <?= json_encode($profiles ?? []) ?>;

    //   if (!selectedPackageName || allProfiles.length === 0) {
    //     return;
    //   }

    //   // Extract the numeric value from package name
    //   var packageMatch = selectedPackageName.match(/(\d+)/);
    //   if (!packageMatch) return;

    //   var packageNumber = parseInt(packageMatch[1]);

    //   // Find profiles that have the EXACT same numeric value
    //   var matchingProfiles = allProfiles.filter(function(profile) {
    //     var profileMatch = profile.match(/(\d+)/);
    //     if (!profileMatch) return false;

    //     var profileNumber = parseInt(profileMatch[1]);
    //     return profileNumber === packageNumber;
    //   });

    //   // Get current selected profile
    //   var currentSelectedProfile = profileDropdown.val();

    //   // Update profile dropdown options
    //   profileDropdown.html('<option value="">--Select--</option>');

    //   if (matchingProfiles.length > 0) {
    //     matchingProfiles.forEach(function(profile) {
    //       var selected = (profile === currentSelectedProfile) ? 'selected' : '';
    //       profileDropdown.append('<option value="' + profile + '" ' + selected + '>' + profile + '</option>');
    //     });
    //   } else {
    //     profileDropdown.append('<option value="">No matching profile found</option>');
    //   }
    // }

    // // Get package name from package ID
    // function getPackageName(packageId) {
    //   var packageDropdown = $('select[name="package_id"]');
    //   var selectedOption = packageDropdown.find('option:selected');
    //   return selectedOption.text();
    // }

    // // Initial profile filtering on page load
    // var initialPackageId = $('select[name="package_id"]').val();
    // if (initialPackageId && userRole !== 'admin') {
    //   var packageName = getPackageName(initialPackageId);
    //   filterProfilesByPackage(packageName);
    // }

    // // Filter profiles when package changes

    // // Only attach the change event if user is NOT sAdmin

    //   // Filter profiles when package changes
    //   $('select[name="package_id"]').on('change', function() {
    //     var packageId = $(this).val();

    //     if (packageId && userRole !== 'admin') {
    //       var packageName = getPackageName(packageId);
    //       filterProfilesByPackage(packageName);
    //     } else {
    //       // Reset to all profiles if no package selected
    //       var allProfiles = <?= json_encode($profiles ?? []) ?>;
    //       var profileDropdown = $('select[name="pppoe_profile"]');

    //       profileDropdown.html('<option value="">--Select--</option>');
    //       allProfiles.forEach(function(profile) {
    //         profileDropdown.append(
    //           '<option value="' + profile + '">' + profile + '</option>'
    //         );
    //       });
    //     }
    //   });




    // Check the selected area on page load and fetch sub-area data
    var selectedAreaId = $('select[name="area_id"]').val();
    if (selectedAreaId) {
      fetchDataByAreaId(selectedAreaId);
    }

    // Example function to call when an area is selected
    function fetchDataByAreaId(areaId) {
      console.log("Fetching data for Area ID:", areaId);

      $.ajax({
        url: '<?= route_to("route.fetchDataByAreaId"); ?>', // Replace with your route
        type: 'POST',
        data: {
          area_id: areaId
        },
        headers: {
          '<?= csrf_header() ?>': '<?= csrf_hash() ?>',
        },
        beforeSend: function() {
          $('select[name="sub_area_id"]').html(`<option value="">Loading. Please wait...</option>`);
          $('button[type="submit"]').attr('disabled', 'true'); // Disable the submit button while fetching data
        },
        success: function(result) {
          $('button[type="submit"]').removeAttr('disabled'); // Re-enable the submit button

          if (result.response && result.response.length > 0) {
            var options = '<option value="">--Select Sub Area--</option>';
            var selectedSubAreaId = "<?= $ConnDetails[0]['sub_area_id'] ?? '' ?>"; // Get the selected sub-area ID

            // Loop through the response and add options
            result.response.forEach(function(subArea) {
              options += `<option value="${subArea.id}" ${subArea.id === selectedSubAreaId ? 'selected' : ''}>${subArea.area_name}</option>`;
            });

            // Populate the sub-area dropdown
            $('select[name="sub_area_id"]').html(options);
          } else {
            // If no data is found, show a message
            $('select[name="sub_area_id"]').html('<option value="">No sub-area found</option>');
          }
        },
        error: function({
          responseText
        }) {
          const result = JSON.parse(responseText);
          $('button[type="submit"]').removeAttr('disabled'); // Re-enable the submit button
          $('select[name="sub_area_id"]').html('<option value="">Error fetching sub-areas</option>');
          tata.error("Couldn't load sub-areas", result.response);
        }
      });
    }



  });
</script>

<script>
  $("#form").submit(function(e) {

    const form = this;

    $.ajax({
      url: '<?= route_to('route.customer.update', $details->id); ?>',
      type: 'POST',
      data: new FormData(form),
      contentType: false,
      cache: false,
      processData: false,

      beforeSend: function() {

        $(form).find('.error').html("");
        $(form).find('#feedback').html("");

        $(form).find('button[type="submit"]').html("<i class='fas fa-spinner fa-spin'></i> Please wait");

        $(form).find('button[type="submit"]').attr('disabled', 'true');
      },

      success: function(result) {

        $(form).find('button[type="submit"]').html('Update Customer');

        $(form).find('button[type="submit"]').removeAttr('disabled');

        tata.success('Customer updated', result.response, {
          onClose: () => {
            location.href = '<?= route_to("route.customer"); ?>';
          },
        });
      },

      error: function({
        responseText
      }) {

        const result = JSON.parse(responseText);

        $(form).find('button[type="submit"]').html('Update Customer');

        $(form).find('button[type="submit"]').removeAttr('disabled');

        if (result.status === 'validation-error') {

          $.each(result.response, function(prefix, val) {
            $(form).find('#' + prefix + '-error').text(val);
          });
          tata.error('Validation Error', 'Please check the form and fix the errors.');

        } else {

          tata.error("Couldn't update customer", result.response);
        }
      }
    });

    e.preventDefault();
  });
</script>

<?= $this->endSection('script'); ?>