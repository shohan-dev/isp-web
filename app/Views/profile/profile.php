<?php
// Assuming $details is an instance of a specific class, e.g., ResellerDetails
/** @var \App\Entities\ResellerDetails $details */

// If $details is a standard class object without a specific class, you can use:
/** @var object $details */
/** @var object $rdetails */
?>

<?= $this->extend('layout/main-layout'); ?>

<?= $this->section('css'); ?>
<?php /* Tab chrome comes from list-pages.css (global SaaS shell). */ ?>
<?= saas_css('profile-page.css') ?>
<?= $this->endSection('css'); ?>

<?= $this->section('content'); ?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <!-- Main content -->
  <section class="content ipb-saas-list">

    
    <?= $this->include('components/page-header', [
      'title' => 'My Profile',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'My Profile'],
      ],
    ]); ?>

<div class="box box-warning">
      <div class="box-header with-border">


        <ul class="nav nav-tabs">

          <?php if (userHasPermission('profile_update', 'read')): ?>
            <li class="active">
              <a data-toggle="tab" href="#details">Profile Details</a>
            </li>
          <?php endif; ?>

          <?php if (userHasPermission('profile_update', 'update')): ?>
            <li class="<?= !userHasPermission('profile_update', 'read') ? 'active' : null; ?>">
              <a data-toggle="tab" href="#update">Update profile</a>
            </li>
          <?php endif; ?>
          <?php if (userHasPermission('profile_update', 'update')): ?>
            <li class="<?= !userHasPermission('profile_update', 'read') ? 'active' : null; ?>">
              <a data-toggle="tab" href="#updateOrg">Update Org. Details</a>
            </li>
          <?php endif; ?>
        </ul>

      </div>

      <div class="box-body">

        <div class="tab-content">

          <?php if (userHasPermission('profile_update', 'read')): ?>

            <div id="details" class="tab-pane fade in active">

              <?php
                $roleLabels = [
                    'super_admin'   => 'Administrator',
                    'admin'         => 'Admin',
                    'employee'      => 'Employee',
                    'resellerAdmin' => 'Reseller',
                ];
                $roleLabel = $roleLabels[$details->role] ?? 'Customer';
                $isActive  = ($details->status ?? '') === 'active';

                // getUserArea() used to be called three times to print one row.
                $area      = getUserArea($details->id);
                $areaLabel = !empty($area) ? $area->area_name . ' (' . $area->area_code . ')' : '';

                $isCustomer   = getSession('user_role') === 'user';
                $package      = $isCustomer ? getUserPackage($details->id) : null;
                $packageName  = '';
                $packagePrice = '';
                if (!empty($package)) {
                    $packageName = is_array($package) ? ($package['package_name'] ?? '') : ($package->package_name ?? '');
                    $price       = is_array($package) ? ($package['price'] ?? '') : ($package->price ?? '');
                    $pricingType = is_array($package) ? ($package['pricing_type'] ?? '') : ($package->pricing_type ?? '');
                    if ($price !== '' && $price !== null) {
                        $packagePrice = $price . '৳ — ' . ucwords((string) $pricingType);
                    }
                }

                /**
                 * One field. An unset value renders as "Not set", not "--": a double dash
                 * reads as broken data rather than "you have not filled this in".
                 * Everything is escaped — these fields were echoed raw, and they are all
                 * user-entered.
                 */
                $field = static function (string $icon, string $label, string $value, bool $numeric = false): void {
                    $empty = trim($value) === '';
                    echo '<div class="ipb-field' . ($empty ? ' is-empty' : '') . '">'
                        . '<span class="ipb-field-icon"><i class="' . esc($icon, 'attr') . '" aria-hidden="true"></i></span>'
                        . '<span class="ipb-field-body">'
                        . '<span class="ipb-field-label">' . esc($label) . '</span>'
                        . '<span class="ipb-field-value' . ($numeric && !$empty ? ' is-num' : '') . '">'
                        . ($empty ? 'Not set' : esc($value))
                        . '</span></span></div>';
                };
              ?>

              <div class="ipb-profile">

                <header class="ipb-profile-hero">
                  <div class="ipb-profile-avatar">
                    <img src="<?= base_url('assets/img/icon/avatar.png'); ?>" alt="" width="88" height="88">
                    <span class="ipb-profile-presence <?= $isActive ? 'is-active' : 'is-inactive'; ?>"
                      title="<?= $isActive ? 'Active account' : 'Inactive account'; ?>"></span>
                  </div>

                  <div class="ipb-profile-ident">
                    <h2 class="ipb-profile-name"><?= esc($details->name); ?></h2>

                    <div class="ipb-profile-chips">
                      <span class="ipb-profile-chip is-brand">
                        <i class="fa fa-shield-halved" aria-hidden="true"></i> <?= esc($roleLabel); ?>
                      </span>
                      <span class="ipb-profile-chip <?= $isActive ? 'is-success' : 'is-danger'; ?>">
                        <i class="fa <?= $isActive ? 'fa-circle-check' : 'fa-circle-xmark'; ?>" aria-hidden="true"></i>
                        <?= $isActive ? 'Active' : 'Inactive'; ?>
                      </span>
                    </div>

                    <div class="ipb-profile-meta">
                      <span><i class="fa fa-calendar-plus" aria-hidden="true"></i>Joined <?= esc(date('d M Y', strtotime($details->created_at))); ?></span>
                      <span><i class="fa fa-clock-rotate-left" aria-hidden="true"></i>Updated <?= esc(date('d M Y, h:i a', strtotime($details->updated_at))); ?></span>
                    </div>
                  </div>

                  <?php if (userHasPermission('profile_update', 'update')): ?>
                    <div class="ipb-profile-hero-actions">
                      <a href="#update" data-toggle="tab" class="btn btn-primary btn-sm">
                        <i class="fa fa-pen-to-square" aria-hidden="true"></i> Edit profile
                      </a>
                    </div>
                  <?php endif; ?>
                </header>

                <section class="ipb-profile-section">
                  <h3 class="ipb-profile-section-title">Contact</h3>
                  <div class="ipb-profile-grid">
                    <?php
                      $field('fa fa-user', 'Name', (string) ($details->name ?? ''));
                      $field('fa fa-phone', 'Mobile number', (string) ($details->mobile ?? ''), true);
                      $field('fa fa-envelope', 'Email', (string) ($details->email ?? ''));
                      $field('fa-brands fa-whatsapp', 'WhatsApp number', (string) ($details->whatsapp_number ?? ''), true);
                      $field('fa fa-money-bill-transfer', 'Payment receive number', (string) ($details->payment_receive_number ?? ''), true);
                      $field('fa fa-map-location-dot', 'Service area', $areaLabel);
                      $field('fa fa-location-dot', 'Address', (string) ($details->address ?? ''));
                    ?>
                  </div>
                </section>

                <?php if ($isCustomer): ?>
                  <section class="ipb-profile-section">
                    <h3 class="ipb-profile-section-title">Subscription</h3>
                    <div class="ipb-profile-grid">
                      <?php
                        $field('fa fa-box', 'Current package', (string) $packageName);
                        $field('fa fa-tag', 'Package price', (string) $packagePrice, true);
                        $field('fa fa-rotate', 'Last renewed', !empty($details->last_renewed) ? date('d M Y, h:i a', strtotime($details->last_renewed)) : '');
                        $field('fa fa-hourglass-half', 'Expires', !empty($details->will_expire) ? date('d M Y, h:i a', strtotime($details->will_expire)) : '');
                      ?>
                    </div>
                  </section>
                <?php endif; ?>

                <?php if (getSession('user_role') === 'resellerAdmin'): ?>
                  <?php
                    $customerTypes = [];
                    if (!empty($rdetails['customer_type'])) {
                        $decoded = json_decode((string) $rdetails['customer_type'], true);
                        if (is_array($decoded)) {
                            $customerTypes = $decoded;
                        }
                    }
                  ?>
                  <section class="ipb-profile-section">
                    <h3 class="ipb-profile-section-title">Organization</h3>
                    <div class="ipb-profile-grid">
                      <?php
                        $field('fa fa-building', 'Organization name', (string) ($rdetails['organization_name'] ?? ''));
                        $field('fa fa-user-tie', "Admin's name", (string) ($rdetails['admin_name'] ?? ''));
                        $field('fa fa-id-card', 'National ID', (string) ($rdetails['nationalid'] ?? ''), true);
                        $field('fa fa-map', 'Division', (string) ($rdetails['division'] ?? ''));
                      ?>

                      <?php /* These were real checkboxes in a READ-ONLY tab — clicking them did
                               nothing and saved nothing. Show what is enabled instead. */ ?>
                      <div class="ipb-field<?= empty($customerTypes) ? ' is-empty' : ''; ?>">
                        <span class="ipb-field-icon"><i class="fa fa-network-wired" aria-hidden="true"></i></span>
                        <span class="ipb-field-body">
                          <span class="ipb-field-label">Customer types</span>
                          <?php if (empty($customerTypes)): ?>
                            <span class="ipb-field-value">Not set</span>
                          <?php else: ?>
                            <span class="ipb-field-tags">
                              <?php foreach (['PPPOE', 'Static', 'Hotspot'] as $type): ?>
                                <?php if (in_array($type, $customerTypes, true)): ?>
                                  <span class="ipb-field-tag is-on"><i class="fa fa-check" aria-hidden="true"></i> <?= esc($type); ?></span>
                                <?php endif; ?>
                              <?php endforeach; ?>
                            </span>
                          <?php endif; ?>
                        </span>
                      </div>
                    </div>
                  </section>
                <?php endif; ?>

              </div>
            </div>
          <?php endif; ?>

          <?php if (userHasPermission('profile_update', 'update')): ?>

            <div id="update"
              class="tab-pane fade <?= !userHasPermission('profile_update', 'read') ? 'in active' : null; ?>">

              <?= form_open('', 'id="form" class="ipb-profile-form"'); ?>

              <section class="ipb-profile-section">
                <h3 class="ipb-profile-section-title">Contact details</h3>

                <div class="ipb-form-grid">
                  <div class="ipb-form-field">
                    <label class="ipb-form-label" for="pf-name">Full name</label>
                    <?= form_input([
                      'name' => 'name',
                      'id' => 'pf-name',
                      'class' => 'form-control',
                      'value' => $details->name,
                      'autocomplete' => 'name',
                    ]); ?>
                    <small id="name-error" class="error text-danger"></small>
                  </div>

                  <div class="ipb-form-field">
                    <label class="ipb-form-label" for="pf-email">Email address</label>
                    <?= form_input([
                      'type' => 'email',
                      'name' => 'email',
                      'id' => 'pf-email',
                      'class' => 'form-control',
                      'value' => $details->email,
                      'autocomplete' => 'email',
                    ]); ?>
                    <small id="email-error" class="error text-danger"></small>
                  </div>

                  <?php /* These were type="number". A phone number is not a quantity: number
                           inputs carry spinner arrows, change value on mouse-wheel, and drop a
                           leading zero — which every BD mobile number starts with. tel +
                           inputmode=numeric gives the numeric keypad without any of that. */ ?>
                  <div class="ipb-form-field">
                    <label class="ipb-form-label" for="pf-mobile">Mobile number</label>
                    <?= form_input([
                      'type' => 'tel',
                      'name' => 'mobile',
                      'id' => 'pf-mobile',
                      'class' => 'form-control',
                      'value' => $details->mobile,
                      'inputmode' => 'numeric',
                      'autocomplete' => 'tel',
                    ]); ?>
                    <small class="ipb-form-hint">Used for sign-in, SMS alerts and payment notices.</small>
                    <small id="mobile-error" class="error text-danger"></small>
                  </div>

                  <div class="ipb-form-field">
                    <label class="ipb-form-label" for="pf-whatsapp">WhatsApp number</label>
                    <?= form_input([
                      'type' => 'tel',
                      'name' => 'whatsapp_number',
                      'id' => 'pf-whatsapp',
                      'class' => 'form-control',
                      'value' => $details->whatsapp_number,
                      'inputmode' => 'numeric',
                    ]); ?>
                    <small class="ipb-form-hint">Optional. Leave blank if it is the same as your mobile.</small>
                    <small id="whatsapp_number-error" class="error text-danger"></small>
                  </div>

                  <div class="ipb-form-field">
                    <label class="ipb-form-label" for="pf-payrecv">Payment receive number</label>
                    <?= form_input([
                      'type' => 'tel',
                      'name' => 'payment_receive_number',
                      'id' => 'pf-payrecv',
                      'class' => 'form-control',
                      'value' => $details->payment_receive_number,
                      'inputmode' => 'numeric',
                    ]); ?>
                    <small class="ipb-form-hint">The bKash / Nagad number that collects customer payments.</small>
                    <small id="payment_receive_number-error" class="error text-danger"></small>
                  </div>

                  <div class="ipb-form-field is-wide">
                    <label class="ipb-form-label" for="pf-address">Address</label>
                    <?php /* was style="max-height:60px", which clipped a two-line address. */ ?>
                    <?= form_textarea([
                      'name' => 'address',
                      'id' => 'pf-address',
                      'class' => 'form-control',
                      'value' => $details->address,
                      'rows' => 3,
                    ]); ?>
                    <small id="address-error" class="error text-danger"></small>
                  </div>
                </div>
              </section>

              <div class="ipb-form-actions">
                <a href="#details" data-toggle="tab" class="btn btn-default">Cancel</a>
                <button type="submit" class="btn btn-primary">
                  <i class="fa fa-floppy-disk" aria-hidden="true"></i> Save changes
                </button>
              </div>

              <?= form_close(); ?>

            </div>

          <?php endif; ?>

          <?php if (userHasPermission('profile_update', 'update')): ?>

            <div id="updateOrg"
              class="tab-pane fade <?= !userHasPermission('profile_update', 'read') ? 'in active' : null; ?>">

              <?php
                $customerTypes = [];
                if (!empty($rdetails['customer_type'])) {
                    $decodedTypes = json_decode((string) $rdetails['customer_type'], true);
                    if (is_array($decodedTypes)) {
                        $customerTypes = $decodedTypes;
                    }
                }
              ?>

              <?= form_open('', 'id="orgform" class="ipb-profile-form"'); ?>

              <section class="ipb-profile-section">
                <h3 class="ipb-profile-section-title">Organization</h3>

                <div class="ipb-form-grid">
                  <?php /* These defaulted to '--' when unset. That is not a placeholder: it
                           pre-fills the input with the literal string "--", and Orgupdate()
                           runs no validation, so pressing Save stored "--" as the
                           organization's name. Empty means empty. */ ?>
                  <div class="ipb-form-field">
                    <label class="ipb-form-label" for="org-name">Organization name</label>
                    <?= form_input([
                      'name' => 'organization_name',
                      'id' => 'org-name',
                      'class' => 'form-control',
                      'value' => $rdetails['organization_name'] ?? '',
                      'placeholder' => 'e.g. Mango Teleservices Ltd.',
                      'autocomplete' => 'organization',
                    ]); ?>
                    <small id="organization_name-error" class="error text-danger"></small>
                  </div>

                  <div class="ipb-form-field">
                    <label class="ipb-form-label" for="org-nid">National ID</label>
                    <?= form_input([
                      'type' => 'text',
                      'name' => 'nationalid',
                      'id' => 'org-nid',
                      'class' => 'form-control',
                      'value' => $rdetails['nationalid'] ?? '',
                      'inputmode' => 'numeric',
                    ]); ?>
                    <small class="ipb-form-hint">NID of the organization's authorised admin.</small>
                    <small id="nationalid-error" class="error text-danger"></small>
                  </div>

                  <div class="ipb-form-field is-wide">
                    <span class="ipb-form-label">Customer types</span>
                    <small class="ipb-form-hint">The connection types your organization sells.</small>

                    <?php /* The ids stay org_* — the read-only Details tab renders the same
                             three values, and a <label for="x"> binds to the FIRST element with
                             that id in the document. Sharing ids across the tabs made these
                             labels toggle the other tab's control. */ ?>
                    <div class="ipb-choice-group">
                      <label class="ipb-choice" for="org_pppoe">
                        <input type="checkbox" name="customer_type[]" id="org_pppoe" value="PPPOE"
                          <?= in_array('PPPOE', $customerTypes, true) ? 'checked' : ''; ?>>
                        <span>PPPoE</span>
                      </label>

                      <label class="ipb-choice" for="org_static">
                        <input type="checkbox" name="customer_type[]" id="org_static" value="Static"
                          <?= in_array('Static', $customerTypes, true) ? 'checked' : ''; ?>>
                        <span>Static</span>
                      </label>

                      <label class="ipb-choice" for="org_hotspot">
                        <input type="checkbox" name="customer_type[]" id="org_hotspot" value="Hotspot"
                          <?= in_array('Hotspot', $customerTypes, true) ? 'checked' : ''; ?>>
                        <span>Hotspot</span>
                      </label>
                    </div>

                    <small id="customer_type-error" class="error text-danger"></small>
                  </div>
                </div>
              </section>

              <div class="ipb-form-actions">
                <a href="#details" data-toggle="tab" class="btn btn-default">Cancel</a>
                <button type="submit" class="btn btn-primary">
                  <i class="fa fa-floppy-disk" aria-hidden="true"></i> Save changes
                </button>
              </div>

              <?= form_close(); ?>

            </div>

<?php endif; ?>

        </div>

      </div>
    </div>
  </section>
  <!-- /.content -->
</div>

<?= $this->endSection('content'); ?>

<?= $this->section('script'); ?>

<?php if (userHasPermission('profile_update', 'update')): ?>
  <script>
    $("#form").submit(function (e) {

      const form = this;

      $.ajax({
        url: '<?= route_to('route.profile.update'); ?>',
        type: 'POST',
        data: new FormData(form),
        contentType: false,
        cache: false,
        processData: false,

        beforeSend: function () {

          $(form).find('.error').html("");
          $(form).find('#feedback').html("");

          $(form).find('button[type="submit"]').html("<i class='fas fa-spinner fa-spin'></i> Please wait");

          $(form).find('button[type="submit"]').attr('disabled', 'true');
        },

        success: function (result) {

          $(form).find('button[type="submit"]').html('<i class="fa fa-floppy-disk" aria-hidden="true"></i> Save changes');

          $(form).find('button[type="submit"]').removeAttr('disabled');

          tata.success('Profile updated', result.response, {
            onClose: () => {
              location.href = '<?= route_to("route.profile"); ?>';
            },
          });
        },

        error: function ({
          responseText
        }) {

          const result = JSON.parse(responseText);

          $(form).find('button[type="submit"]').html('<i class="fa fa-floppy-disk" aria-hidden="true"></i> Save changes');

          $(form).find('button[type="submit"]').removeAttr('disabled');

          if (result.status === 'validation-error') {

            $.each(result.response, function (prefix, val) {

              $(form).find('#' + prefix + '-error').text(val);
            });

          } else {

            tata.error("Couldn't update profile", result.response);
          }
        }
      });

      e.preventDefault();
    });
    // Handle Organization Update Form Submission
    $("#orgform").submit(function (e) {

const form = this;

$.ajax({
  url: '<?= route_to('route.organization.update'); ?>', // Replace with the correct route for updating the organization details
  type: 'POST',
  data: new FormData(form),
  contentType: false,
  cache: false,
  processData: false,

  beforeSend: function () {

    $(form).find('.error').html("");
    $(form).find('#feedback').html("");

    $(form).find('button[type="submit"]').html("<i class='fas fa-spinner fa-spin'></i> Please wait");

    $(form).find('button[type="submit"]').attr('disabled', 'true');
  },

  success: function (result) {

    $(form).find('button[type="submit"]').html('<i class="fa fa-floppy-disk" aria-hidden="true"></i> Save changes');

    $(form).find('button[type="submit"]').removeAttr('disabled');

    tata.success('Organization updated', result.response, {
      onClose: () => {
        location.href = '<?= route_to("route.profile"); ?>'; // Adjust the redirection if necessary
      },
    });
  },

  error: function ({
    responseText
  }) {

    const result = JSON.parse(responseText);

    $(form).find('button[type="submit"]').html('<i class="fa fa-floppy-disk" aria-hidden="true"></i> Save changes');

    $(form).find('button[type="submit"]').removeAttr('disabled');

    if (result.status === 'validation-error') {

      $.each(result.response, function (prefix, val) {

        $(form).find('#' + prefix + '-error').text(val);
      });

    } else {

      tata.error("Couldn't update organization", result.response);
    }
  }
});

e.preventDefault();
});
  </script>
<?php endif; ?>

<?= $this->endSection('script'); ?>