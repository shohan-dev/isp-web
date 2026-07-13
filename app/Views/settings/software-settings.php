<?= $this->extend('layout/main-layout'); ?>

<?= $this->section('css'); ?>

<!-- summernote -->
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">

<style>
  /* Tab chrome comes from list-pages.css (global SaaS shell) */
</style>

<?= $this->endSection('css'); ?>

<?= $this->section('content'); ?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <!-- Main content -->
  <section class="content ipb-saas-list">

    
    <?= $this->include('components/page-header', [
      'title' => 'Software Settings',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Software Settings'],
      ],
    ]); ?>

<div class="box box-warning">
      <div class="box-header with-border">
        <ul class="nav nav-tabs">
          <li class="active">
            <a data-toggle="tab" href="#tab_general">General Settings</a>
          </li>

          <li>
            <a data-toggle="tab" href="#tab_smtp">SMTP Settings</a>
          </li>

          <li>
            <a data-toggle="tab" href="#tab_email">Email Settings</a>
          </li>
          <li>
            <a data-toggle="tab" href="#tab_sms">SMS Settings</a>
          </li>
          <li>
            <a data-toggle="tab" href="#tab_payment">Payment Settings</a>
          </li>
          <li>
            <a data-toggle="tab" href="#tab_movie">Movie Servers</a>
          </li>
          <li>
            <a data-toggle="tab" href="#tab_news">News Servers</a>
          </li>
          <li>
            <a data-toggle="tab" href="#tab_cronjob">CronJOB Settings</a>
          </li>
          <li>
            <a data-toggle="tab" href="#tab_radius">RADIUS Settings</a>
          </li>
          <li>
            <a data-toggle="tab" href="#tab_theme">Theme Studio</a>
          </li>
        </ul>
      </div>

      <?= form_open('', 'id="form"'); ?>

      <div class="box-body">

        <div class="tab-content">

          <div id="tab_general" class="tab-pane fade in active">
            <?= $this->include('settings/pages/general'); ?>
          </div>

          <div id="tab_smtp" class="tab-pane fade">
            <?= $this->include('settings/pages/smtp'); ?>
          </div>

          <div id="tab_email" class="tab-pane fade">
            <?= $this->include('settings/pages/email'); ?>
          </div>

          <div id="tab_sms" class="tab-pane fade">
            <?= $this->include('settings/pages/sms'); ?>
          </div>

          <div id="tab_payment" class="tab-pane fade">
            <?= $this->include('settings/pages/payment'); ?>
          </div>
          <div id="tab_movie" class="tab-pane fade">
            <?= $this->include('settings/pages/movie_servers'); ?>
          </div>
          <div id="tab_news" class="tab-pane fade">
            <?= $this->include('settings/pages/news_servers'); ?>
          </div>
          <div id="tab_cronjob" class="tab-pane fade">
            <?= $this->include('settings/pages/cronjob'); ?>
          </div>
          <div id="tab_radius" class="tab-pane fade">
            <?= $this->include('settings/pages/radius'); ?>
          </div>

          <?php /* Client-side only (localStorage); inputs have no name= so they never POST */ ?>
          <div id="tab_theme" class="tab-pane fade">
            <div class="ipb-theme-page-intro">
              <div>
                <h3 class="ipb-theme-page-title">Theme Studio</h3>
                <p class="ipb-theme-page-sub">Brand colors, density and presets apply across the app on this device. Full page also available under <a href="<?= route_to('route.theme.studio'); ?>">Theme Studio</a>.</p>
              </div>
              <button type="button" class="btn btn-default" data-theme-reset>
                <i class="fa fa-rotate-left" aria-hidden="true"></i> Reset to default
              </button>
            </div>
            <?= $this->include('components/theme-studio-panel', ['layout' => 'page']); ?>
          </div>

        </div>
      </div>

      <div class="box-footer">
        <?= form_button([
          "content" => "Update",
          "class"   => "btn btn-primary",
          "type"    => "submit",
        ]); ?>
      </div>

      <?= form_close(); ?>
    </div>
  </section>
  <!-- /.content -->
</div>

<!-- Modal -->
<div id="balance_modal" class="modal fade" role="dialog">
  <div class="modal-dialog">

    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Credit Balance</h4>
      </div>
      <div class="modal-body"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection('content'); ?>

<?= $this->section('script'); ?>

<!-- summernote -->
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>


<!-- Summernote Init Script -->
<script>
  function toggleSubmitButton() {
    const activeTab = $('.tab-pane.active').attr('id');

    if (activeTab === 'tab_movie' || activeTab === 'tab_news' || activeTab === 'tab_theme') {
        $('.box-footer').hide();
    } else {
        $('.box-footer').show();
    }
}

// On tab change
$('a[data-toggle="tab"]').on('shown.bs.tab', function () {
    toggleSubmitButton();
});

// On page load
$(document).ready(function () {
    if (window.location.hash === '#tab_theme') {
        $('a[data-toggle="tab"][href="#tab_theme"]').tab('show');
    }
    toggleSubmitButton();
});


  $(document).ready(function(params) {

    $('.summernote').summernote({
      fontNames: ['Montserrat'],
      tabsize: 2,
      height: 200,
    });


    //check balance
    $('.check-balance').click(function(e) {
      const gateway = $(this).data('gateway');
      const self = this;
      $.ajax({
        type: 'POST',
        url: '<?= route_to("route.settings.checkbalance"); ?>',
        data: {
          gateway: gateway
        },
        headers: {
          '<?= csrf_header() ?>': '<?= csrf_hash() ?>',
        },
        beforeSend: function() {
          $(self).html('<i class="fa fa-spinner fa-spin"></i>');
        },
        success: function(result) {
          $(self).html('<i class="fa fa-rotate"></i>');
          $('#balance_modal .modal-title').text(`${result.response.gateway} Credit Balance`);

          if (result.response.status === 'success') {

            $('#balance_modal .modal-body').text(`Credit : ${result.response.balance}`);

          } else {

            $('#balance_modal .modal-body').text(`Error : ${result.response.message}`);
          }

          $('#balance_modal').modal();
        },
        error: function(xhr, status, error) {
          console.error("Check Balance Error:", xhr.responseText);
          let message = 'Something went wrong! Please try again';
          try {
            if (xhr.responseText) {
              const result = JSON.parse(xhr.responseText);
              message = result.response || result.message || message;
            }
          } catch (e) {
            console.error("JSON Parse Error:", e);
          }
          tata.error("Couldn't check balance", message);
        }
      });
    });

    $("#form").submit(function(e) {
    
    const activeTab = $('.tab-pane.active').attr('id');

    if (activeTab === 'tab_movie' || activeTab === 'tab_news') {
        e.preventDefault();
        return false;
    }


      const form = this;

      $.ajax({
        url: '<?= route_to('route.settings.software'); ?>',
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

          $(form).find('button[type="submit"]').html('Update');

          $(form).find('button[type="submit"]').removeAttr('disabled');

          tata.success('Settings updated', result.response, {
            onClose: () => {
              location.reload();
            }
          });
        },

        error: function(xhr, status, error) {
          console.error("Update Error:", xhr.responseText);
          let message = 'Something went wrong! Please try again';
          try {
            if (xhr.responseText) {
              const result = JSON.parse(xhr.responseText);
              message = result.response || result.message || message;
            }
          } catch (e) {
            console.error("JSON Parse Error:", e);
          }

          $(form).find('button[type="submit"]').html("Update");
          $(form).find('button[type="submit"]').removeAttr('disabled');
          tata.error("Couldn't update settings", message);
        }
      });

      e.preventDefault();
    });

  });
</script>

<?= $this->endSection('script'); ?>