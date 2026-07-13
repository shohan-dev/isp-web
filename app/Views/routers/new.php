<?= $this->extend('layout/main-layout'); ?>


<?= $this->section('content'); ?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <!-- Main content -->
  <section class="content ipb-saas-list">

    
    <?= $this->include('components/page-header', [
      'title' => 'New Router',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Mikrotik Routers'],
        ['label' => 'New Router'],
      ],
    ]); ?>
<div class="box box-warning">
      <div class="box-header with-border ipb-box-toolbar">
        <div class="ipb-list-toolbar">
          <div class="ipb-list-toolbar-filters">
            <span class="ipb-filter-label"><i class="fa fa-pen" aria-hidden="true"></i> Details</span>
          </div>
        </div>
      </div>

      <?= form_open('', 'id="form"'); ?>

      <div class="box-body">
        <div class="form-group">
          <label>Router Type</label>

          <div class="router-type-wrapper">
            <label class="router-type-option active">
              <input type="radio" name="router_type" value="pppoe" checked>
              <i class="fa fa-exchange-alt"></i> PPPoE
            </label>

            <label class="router-type-option">
              <input type="radio" name="router_type" value="hotspot">
              <i class="fa fa-wifi"></i> Hotspot
            </label>
          </div>

          <small id="router_type-error" class="error text-danger"></small>
        </div>


        <div id="pppoe-fields" class="form-group">
          <label>Router Name</label>

          <?= form_input([
            'name'  => 'name',
            'class' => 'form-control',
          ]); ?>

          <small id="name-error" class="error text-danger"></small>
        </div>

        <div class="form-group">
          <label>Host | IP Address</label>

          <?= form_input([
            'name'  => 'host',
            'class' => 'form-control',
          ]); ?>

          <small id="host-error" class="error text-danger"></small>
        </div>

        <div class="form-group">
          <label>Api Port (8728 for Hotspot)</label>

          <?= form_input([
            'type'  => 'number',
            'name'  => 'port',
            'class' => 'form-control',
          ]); ?>

          <small id="port-error" class="error text-danger"></small>
        </div>

        <div class="form-group">
          <label>Username</label>

          <?= form_input([
            'name'  => 'username',
            'class' => 'form-control',
          ]); ?>

          <small id="username-error" class="error text-danger"></small>
        </div>

        <div class="form-group">
          <label>Password</label>

          <?= form_input([
            'name'  => 'password',
            'class' => 'form-control',
          ]); ?>

          <small id="password-error" class="error text-danger"></small>
        </div>

        <div id="hotspot-fields" style="display:none;">

          <div class="form-group">
            <label>Hotspot Name</label>
            <?= form_input([
              'name'  => 'hotspot_name',
              'class' => 'form-control',
            ]); ?>
            <small id="hotspot_name-error" class="error text-danger"></small>
          </div>

          <div class="form-group">
            <label>DNS Name</label>
            <?= form_input([
              'name'  => 'dns_name',
              'class' => 'form-control',
            ]); ?>
            <small id="dns_name-error" class="error text-danger"></small>
          </div>

          <div class="form-group">
            <label>Currency</label>
            <?= form_input([
              'name'  => 'currency',
              'class' => 'form-control',
              'placeholder' => 'BDT / USD / EUR',
            ]); ?>
            <small id="currency-error" class="error text-danger"></small>
          </div>

        </div>


        <div class="form-group">
          <label>Status</label>

          <div class="radio">
            <label class="radio-inline">
              <?= form_radio([
                'name'  => 'status',
                'value' => 'active',
              ]); ?>
              Active
            </label>

            <label class="radio-inline">
              <?= form_radio([
                'name'  => 'status',
                'value' => 'inactive',
              ]); ?>
              Inactive
            </label>
          </div>

          <small id="status-error" class="error text-danger"></small>
        </div>

      </div>

      <div class="box-body">
        <?= form_button([
          "content" => "Add Router",
          "class"   => "btn btn-warning",
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
  .router-type-wrapper {
    display: flex;
    gap: 15px;
  }

  .router-type-option {
    border: 1px solid #ddd;
    padding: 12px 25px;
    border-radius: 5px;
    cursor: pointer;
    font-weight: 600;
    background: #f9f9f9;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .router-type-option input {
    display: none;
  }

  .router-type-option.active {
    background: #f39c12;
    /* !important is load-bearing, not decorative — see routers/edit.php's
       identical toggle for why: overrides.css forces every plain <label> to
       var(--text-secondary), and these toggles are <label>s, not .btn. */
    color: #fff !important;
    border-color: #f39c12;
  }

  .router-type-option:hover {
    border-color: #f39c12;
  }

  /* .router-type-wrapper has no flex-wrap, so the PPPoE/Hotspot pair (each
     12px 25px padded + icon) doesn't wrap on phones ≤767px and pushes the
     row past the viewport edge. routers/edit.php's identical selector
     already carries flex-wrap: wrap for this reason — mirror it here without
     touching the desktop side-by-side layout. */
  @media (max-width: 767px) {
    .router-type-wrapper {
      flex-wrap: wrap;
    }
  }
</style>

<script>
  $('input[name="router_type"]').on('change', function() {
    $('.router-type-option').removeClass('active');
    $(this).closest('.router-type-option').addClass('active');

    if (this.value === 'hotspot') {
      $('#pppoe-fields').hide();
      $('#hotspot-fields').slideDown();
    } else {
      $('#hotspot-fields').hide();
      $('#pppoe-fields').slideDown();
    }
  });


  $("#form").submit(function(e) {

    const form = this;

    $.ajax({
      url: '<?= route_to('route.routers.create'); ?>',
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

        $(form).find('button[type="submit"]').html('Add Router');

        $(form).find('button[type="submit"]').removeAttr('disabled');

        $(form).trigger('reset');

        tata.success('Router added', result.response, {
          onClose: () => {
            location.href = '<?= route_to("route.routers"); ?>';
          },
        });
      },

      error: function({
        responseText
      }) {

        const result = JSON.parse(responseText);

        $(form).find('button[type="submit"]').html('Add Router');

        $(form).find('button[type="submit"]').removeAttr('disabled');

        if (result.status === 'validation-error') {

          $.each(result.response, function(prefix, val) {

            $(form).find('#' + prefix + '-error').text(val);
          });

        } else {

          tata.error("Couldn't add router", result.response);
        }
      }
    });

    e.preventDefault();
  });
</script>

<?= $this->endSection('script'); ?>