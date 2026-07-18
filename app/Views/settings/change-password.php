<?= $this->extend('layout/main-layout'); ?>

<?= $this->section('css'); ?>
<style>
  /* Themed to the saas tokens — dark-mode safe, no hardcoded colours. */
  .cp-card {
    max-width: 560px;
    background: var(--surface, #fff);
    border: 1px solid var(--border, #e2e8f0);
    border-radius: 16px;
    box-shadow: var(--shadow-1, 0 4px 18px rgba(15, 23, 42, .06));
    overflow: hidden;
  }
  .cp-head {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 22px 24px;
    border-bottom: 1px solid var(--border, #e2e8f0);
    background: linear-gradient(180deg, color-mix(in srgb, var(--primary-500, #6c5ce7) 6%, var(--surface, #fff)), var(--surface, #fff));
  }
  .cp-head__ic {
    width: 46px;
    height: 46px;
    flex-shrink: 0;
    display: grid;
    place-items: center;
    border-radius: 13px;
    background: color-mix(in srgb, var(--primary-500, #6c5ce7) 14%, transparent);
    color: var(--primary-600, #6c5ce7);
    font-size: 18px;
  }
  .cp-head__title { margin: 0; font-size: 1.05rem; font-weight: 700; color: var(--text-primary, #1f2937); }
  .cp-head__sub { margin: 2px 0 0; font-size: .8125rem; color: var(--text-secondary, #64748b); }

  .cp-body { padding: 22px 24px; }
  .cp-field { margin-bottom: 18px; }
  .cp-field > label {
    display: block;
    margin-bottom: 7px;
    font-size: .8125rem;
    font-weight: 600;
    color: var(--text-secondary, #475569);
  }
  .cp-input-wrap { position: relative; }
  .cp-input-wrap .form-control { padding-right: 42px; }
  .cp-eye {
    position: absolute;
    top: 50%;
    right: 8px;
    transform: translateY(-50%);
    width: 30px;
    height: 30px;
    display: grid;
    place-items: center;
    border: none;
    background: none;
    color: var(--text-muted, #94a3b8);
    cursor: pointer;
    border-radius: 7px;
    transition: color .2s ease, background .2s ease;
  }
  .cp-eye:hover { color: var(--primary-600, #6c5ce7); background: color-mix(in srgb, var(--primary-500, #6c5ce7) 8%, transparent); }

  .cp-hint {
    display: flex;
    gap: 8px;
    align-items: flex-start;
    margin: 2px 0 22px;
    padding: 10px 12px;
    border-radius: 10px;
    background: color-mix(in srgb, var(--primary-500, #6c5ce7) 6%, transparent);
    border: 1px solid color-mix(in srgb, var(--primary-500, #6c5ce7) 16%, transparent);
    font-size: .78rem;
    color: var(--text-secondary, #64748b);
    line-height: 1.5;
  }
  .cp-hint i { color: var(--primary-600, #6c5ce7); margin-top: 2px; }

  .cp-actions { display: flex; }
  .cp-actions .btn { width: 100%; display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 11px; border-radius: 10px; font-weight: 700; }
</style>
<?= $this->endSection(); ?>

<?= $this->section('content'); ?>
<div class="content-wrapper">
  <section class="content ipb-saas-list">

    <?= $this->include('components/page-header', [
      'title' => 'Change Password',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Change Password'],
      ],
    ]); ?>

    <div class="cp-card">
      <div class="cp-head">
        <span class="cp-head__ic"><i class="fa fa-shield-halved" aria-hidden="true"></i></span>
        <div>
          <h3 class="cp-head__title">Update your password</h3>
          <p class="cp-head__sub">Use a strong password you don't reuse on other sites.</p>
        </div>
      </div>

      <?= form_open('', 'id="form"'); ?>
        <div class="cp-body">

          <div class="cp-field">
            <label>Current Password</label>
            <div class="cp-input-wrap">
              <?= form_input(['name' => 'old_password', 'class' => 'form-control', 'type' => 'password', 'autocomplete' => 'current-password', 'placeholder' => 'Enter current password']); ?>
              <button type="button" class="cp-eye" aria-label="Show password"><i class="fa fa-eye"></i></button>
            </div>
            <small id="old_password-error" class="error text-danger"></small>
          </div>

          <div class="cp-field">
            <label>New Password</label>
            <div class="cp-input-wrap">
              <?= form_input(['name' => 'new_password', 'class' => 'form-control', 'type' => 'password', 'autocomplete' => 'new-password', 'placeholder' => 'Enter new password']); ?>
              <button type="button" class="cp-eye" aria-label="Show password"><i class="fa fa-eye"></i></button>
            </div>
            <small id="new_password-error" class="error text-danger"></small>
          </div>

          <div class="cp-field">
            <label>Re-enter New Password</label>
            <div class="cp-input-wrap">
              <?= form_input(['name' => 'retyped_new_password', 'class' => 'form-control', 'type' => 'password', 'autocomplete' => 'new-password', 'placeholder' => 'Re-type new password']); ?>
              <button type="button" class="cp-eye" aria-label="Show password"><i class="fa fa-eye"></i></button>
            </div>
            <small id="retyped_new_password-error" class="error text-danger"></small>
          </div>

          <div class="cp-hint">
            <i class="fa fa-circle-info" aria-hidden="true"></i>
            <span>Pick at least 8 characters. Mixing letters, numbers, and a symbol makes it far harder to guess.</span>
          </div>

          <div class="cp-actions">
            <?= form_button([
              'content' => '<i class="fa fa-lock"></i> Change Password',
              'class'   => 'btn btn-primary',
              'type'    => 'submit',
            ]); ?>
          </div>

        </div>
      <?= form_close(); ?>
    </div>

  </section>
</div>
<?= $this->endSection('content'); ?>

<?= $this->section('script'); ?>
<script>
  // Show / hide toggle for each password field.
  $(document).on('click', '.cp-eye', function () {
    var $inp = $(this).closest('.cp-input-wrap').find('input');
    var show = $inp.attr('type') === 'password';
    $inp.attr('type', show ? 'text' : 'password');
    $(this).find('i').toggleClass('fa-eye', !show).toggleClass('fa-eye-slash', show);
    $(this).attr('aria-label', show ? 'Hide password' : 'Show password');
  });

  $("#form").submit(function (e) {
    e.preventDefault();
    const form = this;

    $.ajax({
      url: '<?= route_to('route.cngpass'); ?>',
      type: 'POST',
      data: new FormData(form),
      contentType: false,
      cache: false,
      processData: false,
      beforeSend: function () {
        $(form).find('.error').html("");
        $(form).find('button[type="submit"]').html("<i class='fas fa-spinner fa-spin'></i> Please wait").attr('disabled', 'true');
      },
      success: function (result) {
        $(form).find('button[type="submit"]').html('<i class="fa fa-lock"></i> Change Password').removeAttr('disabled');
        $(form).trigger('reset');
        tata.success('Password changed', result.response);
      },
      error: function ({ responseText }) {
        const result = JSON.parse(responseText);
        $(form).find('button[type="submit"]').html('<i class="fa fa-lock"></i> Change Password').removeAttr('disabled');
        if (result.status === 'validation-error') {
          $.each(result.response, function (prefix, val) {
            $(form).find('#' + prefix + '-error').text(val);
          });
        } else {
          tata.error("Couldn't change password", result.response);
        }
      }
    });
  });
</script>
<?= $this->endSection('script'); ?>
