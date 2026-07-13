<?= $this->extend('layout/main-layout'); ?>
<?= $this->section('content'); ?>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Main content -->
    <section class="content ipb-saas-list">
      
      
    <?= $this->include('components/page-header', [
      'title' => 'Change Password',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Change Password'],
      ],
    ]); ?>

<div class="box box-warning" style="max-width: 520px;">
        <?= form_open('', 'id="form"'); ?>

          <div class="box-body">

            <div class="form-group">
              <label>Current Password</label>

              <?= form_input([
                'name'  => 'old_password',
                'class' => 'form-control',
                'type'  => 'password',
              ]); ?>

              <small id="old_password-error" class="error text-danger"></small>
            </div>

            <div class="form-group">
              <label>New Password</label>

              <?= form_input([
                'name'  => 'new_password',
                'class' => 'form-control',
                'type'  => 'password',
              ]); ?>

              <small id="new_password-error" class="error text-danger"></small>
            </div>

            <div class="form-group">
              <label>Rewrite New Password</label>

              <?= form_input([
                'name'  => 'retyped_new_password',
                'class' => 'form-control',
                'type'  => 'password',
              ]); ?>

              <small id="retyped_new_password-error" class="error text-danger"></small>
            </div>

          </div>

          <div class="box-body">
            <?= form_button([
              "content" => "Change Password",
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

  <script>
    $("#form").submit(function(e) {

        const form = this;

        $.ajax({
          url: '<?= route_to('route.cngpass'); ?>',
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


            $(form).find('button[type="submit"]').html('Change Password');

            $(form).find('button[type="submit"]').removeAttr('disabled');

            $(form).trigger('reset');
            
            tata.success('Password changed', result.response);
          },

          error: function({responseText}){

            const result = JSON.parse(responseText);

            $(form).find('button[type="submit"]').html('Change Password');
            
            $(form).find('button[type="submit"]').removeAttr('disabled');

            if (result.status === 'validation-error') {

              $.each(result.response, function(prefix, val) {

                $(form).find('#' + prefix + '-error').text(val);
              });

            } else {

              tata.error("Couldn't change password", result.response);
            }
          }
        });

        e.preventDefault();
    });
  </script>

<?= $this->endSection('script'); ?>
