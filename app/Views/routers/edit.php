<?= $this->extend('layout/main-layout'); ?>

<?= $this->section('content'); ?>

<?php
$isHotspot = !empty($details->hotspot_name) || !empty($details->dns_name);
$routerType = $isHotspot ? 'hotspot' : 'pppoe';
?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <!-- Main content -->
  <section class="content ipb-saas-list ipb-router-form">

    <?= $this->include('components/page-header', [
      'title' => 'Update Router',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Mikrotik Routers', 'url' => route_to('route.routers')],
        ['label' => 'Update Router'],
      ],
    ]); ?>

    <div class="box box-warning">
      <div class="box-header with-border ipb-box-toolbar">
        <div class="ipb-list-toolbar">
          <div class="ipb-list-toolbar-filters">
            <span class="ipb-filter-label"><i class="fa fa-pen" aria-hidden="true"></i> Details</span>
          </div>
          <div class="ipb-list-toolbar-actions">
            <a href="<?= route_to('route.routers'); ?>" class="btn btn-default">
              <i class="fa fa-arrow-left" aria-hidden="true"></i> Back
            </a>
          </div>
        </div>
      </div>

      <?= form_open('', 'id="form"'); ?>

      <div class="box-body">

        <div class="form-group">
          <label for="router_id">ID</label>
          <?= form_input([
            'id'       => 'router_id',
            'name'     => 'id',
            'class'    => 'form-control',
            'value'    => $details->id,
            'readonly' => 'readonly',
          ]); ?>
          <small id="id-error" class="error text-danger"></small>
        </div>

        <div class="form-group">
          <label>Router Type</label>

          <div class="router-type-wrapper">
            <label class="router-type-option<?= $routerType === 'pppoe' ? ' active' : '' ?>">
              <input type="radio" name="router_type" value="pppoe"<?= $routerType === 'pppoe' ? ' checked' : '' ?>>
              <i class="fa fa-exchange-alt" aria-hidden="true"></i> PPPoE
            </label>

            <label class="router-type-option<?= $routerType === 'hotspot' ? ' active' : '' ?>">
              <input type="radio" name="router_type" value="hotspot"<?= $routerType === 'hotspot' ? ' checked' : '' ?>>
              <i class="fa fa-wifi" aria-hidden="true"></i> Hotspot
            </label>
          </div>

          <small id="router_type-error" class="error text-danger"></small>
        </div>

        <div class="form-group">
          <label for="router_name">Router Name</label>
          <?= form_input([
            'id'    => 'router_name',
            'name'  => 'name',
            'class' => 'form-control',
            'value' => $details->name,
          ]); ?>
          <small id="name-error" class="error text-danger"></small>
        </div>

        <div class="form-group">
          <label for="router_host">Host | IP Address</label>
          <?= form_input([
            'id'    => 'router_host',
            'name'  => 'host',
            'class' => 'form-control',
            'value' => $details->host,
          ]); ?>
          <small id="host-error" class="error text-danger"></small>
        </div>

        <div class="form-group">
          <label for="router_port">API Port</label>
          <?= form_input([
            'id'    => 'router_port',
            'type'  => 'number',
            'name'  => 'port',
            'class' => 'form-control',
            'value' => $details->port,
          ]); ?>
          <small id="port-error" class="error text-danger"></small>
        </div>

        <div class="form-group">
          <label for="router_username">Username</label>
          <?= form_input([
            'id'    => 'router_username',
            'name'  => 'username',
            'class' => 'form-control',
            'value' => $details->username,
            'autocomplete' => 'username',
          ]); ?>
          <small id="username-error" class="error text-danger"></small>
        </div>

        <div class="form-group">
          <label for="router_password">Password</label>
          <?= form_input([
            'id'    => 'router_password',
            'type'  => 'password',
            'name'  => 'password',
            'class' => 'form-control',
            'value' => $details->password,
            'autocomplete' => 'current-password',
          ]); ?>
          <small id="password-error" class="error text-danger"></small>
        </div>

        <div id="hotspot-fields"<?= $isHotspot ? '' : ' style="display:none;"' ?>>
          <div class="form-group">
            <label for="hotspot_name">Hotspot Name</label>
            <?= form_input([
              'id'    => 'hotspot_name',
              'name'  => 'hotspot_name',
              'class' => 'form-control',
              'value' => $details->hotspot_name ?? '',
            ]); ?>
            <small id="hotspot_name-error" class="error text-danger"></small>
          </div>

          <div class="form-group">
            <label for="dns_name">DNS Name</label>
            <?= form_input([
              'id'    => 'dns_name',
              'name'  => 'dns_name',
              'class' => 'form-control',
              'value' => $details->dns_name ?? '',
            ]); ?>
            <small id="dns_name-error" class="error text-danger"></small>
          </div>

          <div class="form-group">
            <label for="currency">Currency</label>
            <?= form_input([
              'id'          => 'currency',
              'name'        => 'currency',
              'class'       => 'form-control',
              'placeholder' => 'BDT / USD / EUR',
              'value'       => $details->currency ?? '',
            ]); ?>
            <small id="currency-error" class="error text-danger"></small>
          </div>
        </div>

        <div class="form-group">
          <label>Status</label>

          <div class="ipb-status-radios">
            <label class="ipb-status-option<?= ($details->status === 'active') ? ' active' : '' ?>">
              <?= form_radio([
                'name'    => 'status',
                'value'   => 'active',
                'checked' => ($details->status === 'active'),
              ]); ?>
              Active
            </label>

            <label class="ipb-status-option<?= ($details->status === 'inactive') ? ' active' : '' ?>">
              <?= form_radio([
                'name'    => 'status',
                'value'   => 'inactive',
                'checked' => ($details->status === 'inactive'),
              ]); ?>
              Inactive
            </label>
          </div>

          <small id="status-error" class="error text-danger"></small>
        </div>

      </div>

      <div class="box-body">
        <?= form_button([
          "content" => "Update Router",
          "class"   => "btn btn-primary",
          "type"    => "submit",
        ]); ?>
      </div>

      <?= form_close(); ?>
    </div>
  </section>
  <!-- /.content -->
</div>

<?= $this->endSection('content'); ?>

<?= $this->section('script'); ?>
<style>
  .ipb-router-form .router-type-wrapper,
  .ipb-router-form .ipb-status-radios {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
  }

  .ipb-router-form .router-type-option,
  .ipb-router-form .ipb-status-option {
    border: 1.5px solid var(--border, #e6eaf0);
    padding: 12px 20px;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 700;
    background: var(--surface-2, #f8fafc);
    color: var(--text-primary, #0f172a);
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin: 0;
    transition: border-color .15s ease, background .15s ease, color .15s ease;
  }

  .ipb-router-form .router-type-option input,
  .ipb-router-form .ipb-status-option input {
    display: none;
  }

  .ipb-router-form .router-type-option.active,
  .ipb-router-form .ipb-status-option.active {
    background: var(--primary-500, #f75803);
    /* !important is load-bearing, not decorative: overrides.css has a global
       `body.ipb label:not(.btn) { color: var(--text-secondary) !important }` for
       plain form labels. These toggles ARE <label> elements (radios styled as
       pills), not .btn, so they were caught by it — an !important rule always
       beats a non-important one regardless of specificity or source order, so
       the selected pill's text rendered in muted navy/gray instead of white. */
    color: #fff !important;
    border-color: var(--primary-500, #f75803);
  }

  .ipb-router-form .router-type-option:hover,
  .ipb-router-form .ipb-status-option:hover {
    border-color: var(--primary-500, #f75803);
  }

  .ipb-router-form label:not(.router-type-option):not(.ipb-status-option) {
    color: var(--text-secondary, #64748b);
    font-weight: 700;
  }

  .ipb-router-form .form-control[readonly] {
    background: var(--surface-2, #f8fafc);
    color: var(--text-secondary, #64748b);
    cursor: not-allowed;
  }
</style>

<script>
  function setActiveOption(groupSelector, input) {
    $(groupSelector).removeClass('active');
    $(input).closest(groupSelector).addClass('active');
  }

  $('input[name="router_type"]').on('change', function() {
    setActiveOption('.router-type-option', this);

    if (this.value === 'hotspot') {
      $('#hotspot-fields').slideDown();
    } else {
      $('#hotspot-fields').slideUp();
    }
  });

  $('input[name="status"]').on('change', function() {
    setActiveOption('.ipb-status-option', this);
  });

  $("#form").submit(function(e) {
    e.preventDefault();

    const form = this;
    const $btn = $(form).find('button[type="submit"]');

    $.ajax({
      url: '<?= route_to('route.routers.update', $details->id); ?>',
      type: 'POST',
      data: new FormData(form),
      contentType: false,
      cache: false,
      processData: false,

      beforeSend: function() {
        $(form).find('.error').html("");
        $btn.html("<i class='fa fa-spinner fa-spin' aria-hidden='true'></i> Please wait");
        $btn.attr('disabled', 'true');
      },

      success: function(result) {
        $btn.html('Update Router').removeAttr('disabled');

        tata.success('Router updated', result.response, {
          onClose: () => {
            location.href = '<?= route_to("route.routers"); ?>';
          },
        });
      },

      error: function({ responseText }) {
        $btn.html('Update Router').removeAttr('disabled');

        let result = {};
        try {
          result = JSON.parse(responseText);
        } catch (err) {
          tata.error("Couldn't update router", 'Server error');
          return;
        }

        if (result.status === 'validation-error') {
          $.each(result.response, function(prefix, val) {
            $(form).find('#' + prefix + '-error').text(val);
          });
        } else {
          tata.error("Couldn't update router", result.response || 'Something went wrong');
        }
      }
    });
  });
</script>

<?= $this->endSection('script'); ?>
