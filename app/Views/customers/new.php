<?= $this->extend('layout/main-layout'); ?>

<?= $this->section('content'); ?>
<?php $prefill = $referral_prefill ?? null; ?>
<?php
  $pfName    = is_array($prefill) ? ($prefill['name'] ?? '') : '';
  $pfMobile  = is_array($prefill) ? ($prefill['mobile'] ?? '') : '';
  $pfEmail   = is_array($prefill) ? ($prefill['email'] ?? '') : '';
  $pfAddress = is_array($prefill) ? ($prefill['address'] ?? '') : '';
  $pfPackage = is_array($prefill) ? (string) ($prefill['package_id'] ?? '') : '';
?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <section class="content ipb-saas-list">

    <?= $this->include('components/page-header', [
      'title' => $title ?? 'New Customer',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Customers', 'url' => route_to('route.customer')],
        ['label' => $title ?? 'New Customer'],
      ],
    ]); ?>


    <?php if (!empty($prefill)): ?>
      <div class="alert alert-info">
        <strong><i class="fa fa-user-plus"></i> Referral customer setup</strong><br>
        Completing registration for <strong><?= esc($prefill['name']) ?></strong>
        (referred by <?= esc($prefill['referrer_name'] ?: 'customer') ?>, code <?= esc($prefill['referral_code']) ?>).
        Fill router, PPPoE, area and connection details, then save to activate and award referral points.
      </div>
    <?php endif; ?>

    <div class="box box-warning">

      <?= form_open('', 'id="form"'); ?>

      <?php if (!empty($prefill)): ?>
        <input type="hidden" name="referral_id" value="<?= (int) $prefill['referral_id'] ?>">
      <?php endif; ?>

      <div class="box-body">

        <div class="row">

          <div class="col-lg-12">
            <h4 class="text-primary">Account Info</h4>
            <br />
          </div>

          <div class="form-group col-lg-6">
            <label>Customer Name*</label>

            <?= form_input([
              'name' => 'name',
              'id' => 'name',
              'class' => 'form-control',
              'value' => $pfName,
            ]); ?>

            <small id="name-error" class="error text-danger"></small>
          </div>

          <div class="form-group col-lg-6">
            <label>Package*</label>

            <?php $data = array();

            if (empty($packages)):

              $data[''] = 'No package found!';

            else:

              $data = ['' => '--Select--'];


              foreach ($packages as $package) {

                if (is_object($package)) {
                  // If $package is an object
                  $data[$package->id] = $package->package_name;
                } elseif (is_array($package)) {
                  // If $package is an array
                  $data[$package['id']] = $package['package_name'];
                }
              }

            endif;

            echo form_dropdown('package_id', $data, $pfPackage, 'class="form-control" id="package_id"'); ?>

            <small id="package_id-error" class="error text-danger"></small>
          </div>

          <div class="form-group col-lg-6">
            <label>Service Area*</label>

            <?php $data = array();

            if (empty($areas)):

              $data[''] = 'No service area found!';

            else:

              $data = ['' => '--Select--'];

              foreach ($areas as $area) {
                $data[$area->id] = $area->area_name;
              }

            endif;

            echo form_dropdown('area_id', $data, "", 'class="form-control"'); ?>

            <small id="area_id-error" class="error text-danger"></small>
          </div>

          <div class="form-group col-lg-6">
            <label>Service Sub Area</label>

            <?php $data = array();

            if (empty($areas)):

              $data[''] = 'No service sub-area found!';

            else:

              $data = ['' => '--Select--'];



            endif;

            echo form_dropdown('sub_area_id', $data, "", 'class="form-control"'); ?>

            <small id="sub_area_id-error" class="error text-danger"></small>
          </div>

          <div class="form-group col-lg-6">
            <label>Mobile Number*</label>

            <?= form_input([
              'type' => 'number',
              'name' => 'mobile',
              'id' => 'mobile',
              'class' => 'form-control',
              'value' => $pfMobile,
            ]); ?>

            <small id="mobile-error" class="error text-danger"></small>
          </div>

          <div class="form-group col-lg-6">
            <label>Email Id</label>

            <?= form_input([
              'type' => 'text',
              'name' => 'email',
              'id' => 'email',
              'class' => 'form-control',
              'value' => $pfEmail,
            ]); ?>

            <small id="email-error" class="error text-danger"></small>
          </div>

          <div class="form-group col-lg-12">
            <label>Address</label>

            <?= form_textarea([
              'name' => 'address',
              'id' => 'address',
              'class' => 'form-control',
              'style' => 'max-height: 80px',
              'value' => $pfAddress,
            ]); ?>

            <small id="address-error" class="error text-danger"></small>
          </div>

          <!-- <div class="form-group col-lg-6">
            <label>Password</label>

            <?= form_input([
              'type' => 'password',
              'name' => 'password',
              'class' => 'form-control',
            ]); ?>

            <small id="password-error" class="error text-danger"></small>
          </div>

          <div class="form-group col-lg-6">
            <label>Rewrite Password</label>

            <?= form_input([
              'type' => 'password',
              'name' => 're_password',
              'class' => 'form-control',
            ]); ?>

            <small id="re_password-error" class="error text-danger"></small>
          </div> -->

          <div class="form-group col-lg-12">
            <label>Status*</label>

            <div class="radio">
              <label class="radio-inline">
                <?= form_radio([
                  'name' => 'status',
                  'value' => 'active',
                  'checked' => !empty($prefill) ? true : false,
                ]); ?>
                Active
              </label>

              <label class="radio-inline">
                <?= form_radio([
                  'name' => 'status',
                  'value' => 'inactive',
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
          <?php
          $userole = session()->get('user_role'); ?>

          <div class="form-group col-lg-6">
            <label>Mikrotik Router*</label>

            <?php $data = array();

            if (empty($routers)):

              $data[''] = 'No router found!';

            else:

              $data = ['' => '--Select--'];

              foreach ($routers as $router) {
                $data[$router->id] = $router->name;
              }

            endif;

            echo form_dropdown('router_id', $data, "", 'class="form-control"'); ?>

            <small id="router_id-error" class="error text-danger"></small>
          </div>
          <div class="form-group col-lg-6">
            <label>PPPoE Username*</label>

            <?= form_input([
              'name' => 'pppoe_name',
              'class' => 'form-control',
            ]); ?>

            <small id="pppoe_name-error" class="error text-danger"></small>
          </div>

          <div class="form-group col-lg-6">
            <label>PPPoE Password*</label>

            <?= form_input([
              'type' => 'password',
              'name' => 'pppoe_password',
              'class' => 'form-control',
            ]); ?>

            <small id="pppoe_password-error" class="error text-danger"></small>
          </div>

          <div class="form-group col-lg-6">
            <label>PPPoE Service*</label>

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

            echo form_dropdown('pppoe_service', $data, $data['pppoe'], 'class="form-control"'); ?>

            <small id="pppoe_service-error" class="error text-danger"></small>
          </div>
          <div class="form-group col-lg-6">
            <div class="form-group">
              <label>PPPoE Profile*</label>

              <?php $data = ['' => '--Select Router--'];

              echo form_dropdown('pppoe_profile', $data, "", 'class="form-control"'); ?>

              <small id="pppoe_profile-error" class="error text-danger"></small>
            </div>
          </div>

          <div class="form-group col-lg-6">
            <div class="checkbox">
              <label>
                <?= form_checkbox([
                  'name' => 'auto_disconnect',
                  'checked' => 'checked',
                  'value' => 'yes'
                ]); ?>
                Auto Disconnect*
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
              'optional_fiber' => 'Optional Fiber',
              'wireless' => 'Wireless',
            ];
            echo form_dropdown('connection_type', $data, '', 'class="form-control"');
            ?>
            <small id="connection_type-error" class="error text-danger"></small>
          </div>

          <!-- UTP-specific fields -->
          <div class="form-group col-lg-6 utp-fields">
            <label>Cable Requirement in Metre</label>
            <?= form_input([
              'type' => 'number',
              'name' => 'cable_requirement',
              'class' => 'form-control',
            ]); ?>
            <small id="cable_requirement-error" class="error text-danger"></small>
          </div>

          <div class="form-group col-lg-6 utp-fields">
            <label>Core Color</label>
            <?= form_input([
              'name' => 'core_color',
              'class' => 'form-control',
            ]); ?>
            <small id="core_color-error" class="error text-danger"></small>
          </div>

          <!-- Optional Fiber-specific fields -->
          <div class="form-group col-lg-6 fiber-fields">
            <label>Fiber Code</label>
            <?= form_input([
              'name' => 'fiber_code',
              'class' => 'form-control',
            ]); ?>
            <small id="fiber_code-error" class="error text-danger"></small>
          </div>

          <div class="form-group col-lg-6 fiber-fields">
            <label>Number of Core</label>
            <?= form_input([
              'type' => 'number',
              'name' => 'number_of_core',
              'class' => 'form-control',
            ]); ?>
            <small id="number_of_core-error" class="error text-danger"></small>
          </div>


          <div class="form-group col-lg-6">
            <label>Client Type</label>
            <?php
            $data = [
              'home' => 'Home',
              'office' => 'Office',
            ];
            echo form_dropdown('client_type', $data, '', 'class="form-control"');
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
            $roleToCheck = strtolower(trim(session()->get('user_role') ?? getSession('user_role') ?? ''));
            if (in_array($roleToCheck, ['super_admin', 'admin', 'reselleradmin']) || userHasPermission('customer', 'free_customer_create')) {
              $data['free'] = 'Free';
            }
            echo form_dropdown('billing_status', $data, '', 'class="form-control"');
            ?>
            <small id="billing_status-error" class="error text-danger"></small>
          </div>
          <div class="form-group col-lg-6 ">
            <label>OTC</label>
            <?= form_input([
              'name' => 'otc',
              'class' => 'form-control',
              'placeholder' => 'OTC',
            ]); ?>
            <small id="otc-error" class="error text-danger"></small>
          </div>



        </div>
      </div>
      <div class="box-footer">
        <?= form_button([
          "content" => "Add Customer",
          "class" => "btn btn-warning",
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
    // Referral prefill from URL query params (fallback if server render missed values).
    (function applyReferralQueryPrefill() {
      var params = new URLSearchParams(window.location.search);
      if (!params.has('referral_id') && !params.has('name') && !params.has('mobile')) {
        return;
      }

      function setField(name, value) {
        if (value === null || value === undefined || value === '') {
          return;
        }
        var $field = $('[name="' + name + '"]');
        if ($field.length) {
          $field.val(value);
        }
      }

      setField('name', params.get('name'));
      setField('mobile', params.get('mobile'));
      setField('email', params.get('email'));
      setField('package_id', params.get('package_id'));

      var referralId = params.get('referral_id');
      if (referralId) {
        var $hidden = $('input[name="referral_id"]');
        if ($hidden.length) {
          $hidden.val(referralId);
        } else {
          $('#form').prepend('<input type="hidden" name="referral_id" value="' + referralId.replace(/"/g, '&quot;') + '">');
        }
      }

      var pkgId = params.get('package_id');
      if (pkgId) {
        $('select[name="package_id"]').val(pkgId).trigger('change');
      }
    })();
  });
</script>

<script>
  const packages = <?= json_encode(array_map(function ($p) {
                      return is_object($p) ? $p->package_name : $p['package_name'];
                    }, $packages)); ?>;

  // console.log("Available packages:", packages);
  const role = <?= json_encode(getSession('user_role')); ?>;
  // console.log("User role:", role);
</script>

<script>
  $(document).ready(function() {
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
  });
</script>


<script>
  $(document).ready(function() {
    // Log when the page is ready
    // console.log("Page is ready");

    // When an area is selected, trigger the function
    $('select[name="area_id"]').on('change', function() {
      var areaId = $(this).val(); // Get the selected area ID

      // Log the selected area ID
      // console.log("Selected Area ID:", areaId);

      if (areaId) {
        // Only fetch data if an area is selected
        fetchDataByAreaId(areaId);
      }
    });

    // Example function to call when an area is selected
    function fetchDataByAreaId(areaId) {
      // console.log("Fetching data for Area ID:", areaId);

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
          $(form).find('button[type="submit"]').attr('disabled', 'true');
        },
        success: function(result) {
          // console.log("Response:", result.response);
          $(form).find('button[type="submit"]').removeAttr('disabled');
          if (result.response && result.response.length > 0) {
            var options = '<option value="">--Select Sub Area--</option>';
            // Loop through the response and add options
            result.response.forEach(function(subArea) {
              options += `<option value="${subArea.id}">${subArea.area_name}</option>`;
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
          $(form).find('button[type="submit"]').removeAttr('disabled');
          $('select[name="sub_area_id"]').html(`<option value="">--Select Router--</option>`);
          tata.error("Couldn't load sub-areas", result.response);
        },

        error: function(error) {
          console.log("Error fetching data for area ID:", areaId, error);
        }
      });
    }
  });




  $('select[name="router_id"]').change(function() {
    const router = $(this).val();
    const form = $('#form');

    $.ajax({
      url: '<?= route_to("route.customer.getprofiles"); ?>',
      type: 'POST',
      data: {
        router
      },
      headers: {
        '<?= csrf_header() ?>': '<?= csrf_hash() ?>',
      },
      beforeSend: function() {
        $('select[name="pppoe_profile"]').html(`<option value="">Loading. Please wait...</option>`);
        $(form).find('button[type="submit"]').attr('disabled', 'true');
      },
      success: function(result) {
        $(form).find('button[type="submit"]').removeAttr('disabled');

        // 1️⃣ Parse HTML response into profile names
        let temp = $('<select>' + result.response + '</select>');
        let profiles = temp.find('option').map(function() {
          return $(this).text().trim(); // get profile names
        }).get();

        // console.log("=== Raw Profiles from Response ===", profiles);
        // console.log("=== Packages ===", packages);

        if (role !== 'admin') {

          // 2️⃣ Step 1: Keep only profiles that match packages (number OR letter)
          profiles = profiles.filter(profile => {
            const profileName = profile.toLowerCase();
            const profileNum = profileName.match(/\d+/);

            return packages.some(pkg => {
              const pkgName = pkg.toString().trim().toLowerCase();
              const pkgNum = pkgName.match(/\d+/);

              // Numeric packages → compare numbers
              if (pkgNum && profileNum) {
                return pkgNum[0] === profileNum[0];
              }

              // Letter-only packages → exact or partial match
              if (!pkgNum && !profileNum) {
                return pkgName === profileName || profileName.includes(pkgName) || pkgName.includes(profileName);
              }

              return false;
            });
          });

          // 3️⃣ Step 2: Best variant selection only for numeric packages
          let bestVariant = {};
          packages.forEach(pkg => {
            const pkgName = pkg.toString().trim().toLowerCase();
            const pkgNumMatch = pkgName.match(/\d+/);
            if (!pkgNumMatch) return; // skip letter-only packages

            const pkgNum = pkgNumMatch[0];
            const pkgWords = pkgName.split(/[^a-z0-9]+/).filter(Boolean);

            const matchingProfiles = profiles.filter(p => {
              const pNum = p.toLowerCase().match(/\d+/);
              return pNum && pNum[0] === pkgNum;
            });

            let bestMatch = null;
            let highestScore = -1;

            matchingProfiles.forEach(profile => {
              const profileWords = profile.toLowerCase().split(/[^a-z0-9]+/).filter(Boolean);
              const score = profileWords.filter(profileWord =>
                pkgWords.some(pkgWord =>
                  profileWord.includes(pkgWord) || pkgWord.includes(profileWord)
                )
              ).length;

              if (score > highestScore || (score === highestScore && !bestMatch)) {
                highestScore = score;
                bestMatch = profile;
              }
            });

            if (bestMatch) bestVariant[pkg] = bestMatch;
          });

          // 4️⃣ Step 3: Keep numeric best variants + all letter-only matches
          profiles = profiles.filter(p => {
            const pName = p.trim();
            const pNum = pName.match(/\d+/);

            if (pNum) {
              return Object.values(bestVariant).includes(p);
            } else {
              return packages.map(x => x.toString().trim()).includes(pName);
            }
          });

          // console.log("=== Final Filtered Profiles ===", profiles);
        }

        // 5️⃣ Build the options HTML
        let options = '<option value="">--Select PPPoE Profile--</option>';
        profiles.forEach(profile => {
          options += `<option value="${profile}">${profile}</option>`;
        });

        $('select[name="pppoe_profile"]').html(options);
      },



      error: function({
        responseText
      }) {
        const result = JSON.parse(responseText);
        $(form).find('button[type="submit"]').removeAttr('disabled');
        $('select[name="pppoe_profile"]').html(`<option value="">--Select Router--</option>`);
        tata.error("Couldn't load PPPoE profiles", result.response);
      },
    })
  });

  // Filter PPPoE profiles based on selected package - EXACT NUMERIC MATCH
    function filterProfilesByPackage(selectedPackageName) {
      var profileDropdown = $('select[name="pppoe_profile"]');
      var allProfiles = <?= json_encode($profiles ?? []) ?>;

      if (!selectedPackageName || allProfiles.length === 0) {
        return;
      }

      // Extract the numeric value from package name
      var packageMatch = selectedPackageName.match(/(\d+)/);
      if (!packageMatch) return;

      var packageNumber = parseInt(packageMatch[1]);

      // Find profiles that have the EXACT same numeric value
      var matchingProfiles = allProfiles.filter(function(profile) {
        var profileMatch = profile.match(/(\d+)/);
        if (!profileMatch) return false;

        var profileNumber = parseInt(profileMatch[1]);
        return profileNumber === packageNumber;
      });

      // Get current selected profile
      var currentSelectedProfile = profileDropdown.val();

      // Update profile dropdown options
      profileDropdown.html('<option value="">--Select--</option>');

      if (matchingProfiles.length > 0) {
        matchingProfiles.forEach(function(profile) {
          var selected = (profile === currentSelectedProfile) ? 'selected' : '';
          profileDropdown.append('<option value="' + profile + '" ' + selected + '>' + profile + '</option>');
        });
      } else {
        profileDropdown.append('<option value="">No matching profile found</option>');
      }
    }

    // Get package name from package ID
    function getPackageName(packageId) {
      var packageDropdown = $('select[name="package_id"]');
      var selectedOption = packageDropdown.find('option:selected');
      return selectedOption.text();
    }

    // Initial profile filtering on page load
    var initialPackageId = $('select[name="package_id"]').val();
    if (initialPackageId) {
      var packageName = getPackageName(initialPackageId);
      filterProfilesByPackage(packageName);
    }

    // Filter profiles when package changes
    $('select[name="package_id"]').on('change', function() {
      var packageId = $(this).val();
      if (packageId) {
        var packageName = getPackageName(packageId);
        filterProfilesByPackage(packageName);
      } else {
        // Reset to all profiles if no package selected
        var allProfiles = <?= json_encode($profiles ?? []) ?>;
        var profileDropdown = $('select[name="pppoe_profile"]');

        profileDropdown.html('<option value="">--Select--</option>');
        allProfiles.forEach(function(profile) {
          profileDropdown.append('<option value="' + profile + '">' + profile + '</option>');
        });
      }
    });

    

  $("#form").submit(function(e) {

    const form = this;

    $.ajax({
      url: '<?= route_to('route.customer.create'); ?>',
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

        $(form).find('button[type="submit"]').html('Add Customer');

        $(form).find('button[type="submit"]').removeAttr('disabled');

        $(form).trigger('reset');

        tata.success('Customer added', result.response, {
          onClose: () => {
            location.href = '<?= route_to("route.customer"); ?>';
          },
        });
      },

      error: function({
        responseText
      }) {

        const result = JSON.parse(responseText);

        $(form).find('button[type="submit"]').html('Add Customer');

        $(form).find('button[type="submit"]').removeAttr('disabled');

        if (result.status === 'validation-error') {

          $.each(result.response, function(prefix, val) {

            $(form).find('#' + prefix + '-error').text(val);
          });

        } else {

          tata.error("Couldn't add customer", result.response);
        }
      }
    });

    e.preventDefault();
  });
</script>

<?= $this->endSection('script'); ?>