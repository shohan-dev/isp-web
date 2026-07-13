<?= $this->extend('layout/main-layout'); ?>
<?= $this->section('content'); ?>

<?php
$isEdit = ($mode ?? '') === 'edit' && !empty($tenant);
$actionUrl = $isEdit
  ? site_url('tenants/update/' . (int) $tenant->id)
  : site_url('tenants/store');
$portalPreviewSlug = $isEdit ? (string) $tenant->slug : 'your-isp';
?>

<div class="content-wrapper">
  <section class="content ipb-saas-list">
    <?= $this->include('components/page-header', [
      'title' => $isEdit ? 'Edit Tenant Portal' : 'Create Tenant Portal',
      'subtitle' => 'Subdomain goes live immediately — no Nginx or DNS change per tenant',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Tenant Portals', 'url' => route_to('route.tenants')],
        ['label' => $isEdit ? 'Edit' : 'Create'],
      ],
    ]); ?>

    <div id="feedback"></div>

    <form id="tenant-form" method="post" action="<?= esc($actionUrl, 'attr'); ?>" enctype="multipart/form-data" novalidate>
      <?= csrf_field(); ?>

      <div class="row">
        <div class="col-md-8">
          <div class="box box-primary">
            <div class="box-header with-border">
              <h3 class="box-title"><i class="fa fa-building" aria-hidden="true"></i> Company &amp; portal</h3>
            </div>
            <div class="box-body">
              <div class="row">
                <div class="form-group col-md-6">
                  <label for="name">Company name <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="name" name="name" required
                    value="<?= esc($isEdit ? ($tenant->name ?? '') : ''); ?>"
                    placeholder="ABC ISP" autocomplete="organization" />
                  <small class="text-danger" id="name-error"></small>
                </div>
                <div class="form-group col-md-6">
                  <label for="slug">Subdomain <span class="text-danger">*</span></label>
                  <div class="input-group">
                    <input type="text" class="form-control" id="slug" name="slug" required
                      value="<?= esc($isEdit ? ($tenant->slug ?? '') : ''); ?>"
                      placeholder="abc" pattern="[a-z0-9]([a-z0-9-]*[a-z0-9])?" autocomplete="off" />
                    <span class="input-group-addon">.<?= esc($baseDomain ?? 'isppaybd.com'); ?></span>
                  </div>
                  <small class="text-muted">Letters, numbers, hyphens. Reserved names blocked.</small>
                  <small class="text-danger" id="slug-error" style="display:block"></small>
                </div>
                <div class="form-group col-md-6">
                  <label for="plan">Plan</label>
                  <input type="text" class="form-control" id="plan" name="plan"
                    value="<?= esc($isEdit ? ($tenant->plan ?? '') : ''); ?>"
                    placeholder="Basic / Pro / Enterprise" />
                </div>
                <div class="form-group col-md-6">
                  <label for="primary_color">Primary color</label>
                  <div style="display:flex;gap:8px;align-items:center">
                    <input type="color" id="primary_color_picker"
                      value="<?= esc($isEdit ? ($tenant->primary_color ?? '#2563eb') : '#2563eb'); ?>"
                      style="width:46px;height:34px;padding:0;border:1px solid #ddd;border-radius:6px" aria-label="Pick color" />
                    <input type="text" class="form-control" id="primary_color" name="primary_color"
                      value="<?= esc($isEdit ? ($tenant->primary_color ?? '#2563eb') : '#2563eb'); ?>"
                      placeholder="#2563eb" />
                  </div>
                  <small class="text-danger" id="primary_color-error"></small>
                </div>
                <div class="form-group col-md-6">
                  <label for="status">Status</label>
                  <select class="form-control" id="status" name="status">
                    <?php $st = $isEdit ? (string) ($tenant->status ?? 'active') : 'active'; ?>
                    <option value="active" <?= $st === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="suspended" <?= $st === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                  </select>
                </div>
                <div class="form-group col-md-6">
                  <label for="logo">Logo</label>
                  <input type="file" class="form-control" id="logo" name="logo" accept="image/png,image/jpeg,image/gif,image/webp,image/svg+xml" />
                  <small class="text-muted">PNG, JPG, GIF, WebP, SVG · max ~2MB</small>
                </div>
                <div class="form-group col-md-12">
                  <label for="notes">Internal notes</label>
                  <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="Optional notes for platform staff"><?= esc($isEdit ? ($tenant->notes ?? '') : ''); ?></textarea>
                </div>
              </div>
            </div>
          </div>

          <?php if (!$isEdit): ?>
            <div class="box box-info">
              <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-user-shield" aria-hidden="true"></i> Portal owner (Second Admin)</h3>
              </div>
              <div class="box-body">
                <div class="form-group">
                  <label class="radio-inline" style="margin-right:18px">
                    <input type="radio" name="owner_mode" value="new" checked /> Create new owner
                  </label>
                  <label class="radio-inline">
                    <input type="radio" name="owner_mode" value="existing" <?= empty($unlinkedOwners) ? 'disabled' : ''; ?> />
                    Link existing Second Admin
                    <?php if (empty($unlinkedOwners)): ?>
                      <span class="text-muted">(none available)</span>
                    <?php endif; ?>
                  </label>
                </div>

                <div id="owner-existing" style="display:none">
                  <div class="form-group">
                    <label for="owner_user_id">Second Admin</label>
                    <select class="form-control" id="owner_user_id" name="owner_user_id">
                      <option value="">Select admin…</option>
                      <?php foreach (($unlinkedOwners ?? []) as $owner): ?>
                        <option value="<?= (int) $owner->id; ?>">
                          <?= esc($owner->name); ?> — <?= esc($owner->email); ?> (<?= esc($owner->mobile); ?>)
                        </option>
                      <?php endforeach; ?>
                    </select>
                    <small class="text-danger" id="owner_user_id-error"></small>
                  </div>
                </div>

                <div id="owner-new">
                  <div class="row">
                    <div class="form-group col-md-6">
                      <label for="owner_name">Owner name <span class="text-danger">*</span></label>
                      <input type="text" class="form-control" id="owner_name" name="owner_name" autocomplete="name" />
                      <small class="text-danger" id="owner_name-error"></small>
                    </div>
                    <div class="form-group col-md-6">
                      <label for="owner_email">Owner email <span class="text-danger">*</span></label>
                      <input type="email" class="form-control" id="owner_email" name="owner_email" autocomplete="email" />
                      <small class="text-danger" id="owner_email-error"></small>
                    </div>
                    <div class="form-group col-md-6">
                      <label for="owner_mobile">Owner mobile <span class="text-danger">*</span></label>
                      <input type="text" class="form-control" id="owner_mobile" name="owner_mobile" autocomplete="tel" />
                      <small class="text-danger" id="owner_mobile-error"></small>
                    </div>
                    <div class="form-group col-md-6">
                      <label for="owner_password">Password <span class="text-danger">*</span></label>
                      <input type="password" class="form-control" id="owner_password" name="owner_password" autocomplete="new-password" />
                      <small class="text-danger" id="owner_password-error"></small>
                    </div>
                    <div class="form-group col-md-6">
                      <label for="package_id">Subscription package</label>
                      <select class="form-control" id="package_id" name="package_id">
                        <option value="">None / assign later</option>
                        <?php foreach (($packages ?? []) as $pkg): ?>
                          <?php
                            $pkgId = is_array($pkg) ? ($pkg['id'] ?? '') : ($pkg->id ?? '');
                            $pkgName = is_array($pkg) ? ($pkg['package_name'] ?? '') : ($pkg->package_name ?? '');
                          ?>
                          <option value="<?= esc((string) $pkgId, 'attr'); ?>"><?= esc((string) $pkgName); ?></option>
                        <?php endforeach; ?>
                      </select>
                      <small class="text-danger" id="package_id-error"></small>
                    </div>
                    <div class="form-group col-md-6">
                      <label for="address">Address</label>
                      <input type="text" class="form-control" id="address" name="address" />
                    </div>
                  </div>
                </div>
              </div>
            </div>
          <?php endif; ?>
        </div>

        <div class="col-md-4">
          <div class="box box-solid" style="border-top:3px solid var(--primary-500, #2563eb)">
            <div class="box-header with-border">
              <h3 class="box-title">Live preview</h3>
            </div>
            <div class="box-body">
              <p class="text-muted" style="margin-bottom:8px">Portal URL</p>
              <p style="font-weight:700;word-break:break-all" id="portal-preview">
                https://<span id="preview-slug"><?= esc($portalPreviewSlug); ?></span>.<?= esc($baseDomain ?? 'isppaybd.com'); ?>
              </p>
              <hr />
              <ul class="list-unstyled" style="font-size:13px;line-height:1.7;color:var(--text-secondary,#64748b)">
                <li><i class="fa fa-check text-success" aria-hidden="true"></i> Same app &amp; database</li>
                <li><i class="fa fa-check text-success" aria-hidden="true"></i> No Nginx update per tenant</li>
                <li><i class="fa fa-check text-success" aria-hidden="true"></i> No DNS change per tenant</li>
                <li><i class="fa fa-check text-success" aria-hidden="true"></i> Live immediately after save</li>
              </ul>
              <p class="text-muted" style="font-size:12px;margin-top:12px">
                Requires one-time wildcard DNS (<code>*.<?= esc($baseDomain ?? ''); ?></code>) and Nginx
                <code>server_name *.<?= esc($baseDomain ?? ''); ?></code>.
              </p>
            </div>
            <div class="box-footer">
              <button type="submit" class="btn btn-primary btn-block" id="tenant-submit">
                <i class="fa fa-save" aria-hidden="true"></i>
                <?= $isEdit ? 'Save changes' : 'Create portal'; ?>
              </button>
              <a href="<?= route_to('route.tenants'); ?>" class="btn btn-default btn-block">Cancel</a>
            </div>
          </div>
        </div>
      </div>
    </form>
  </section>
</div>

<?= $this->endSection('content'); ?>

<?= $this->section('script'); ?>
<script>
(function () {
  const $form = $('#tenant-form');
  const $slug = $('#slug');
  const $preview = $('#preview-slug');
  const csrfHeader = '<?= csrf_header(); ?>';
  const csrfHash = '<?= csrf_hash(); ?>';

  function clearErrors() {
    $form.find('small.text-danger').text('');
    $('#feedback').empty();
  }

  function showErrors(errors) {
    if (!errors || typeof errors !== 'object') return;
    Object.keys(errors).forEach(function (key) {
      const $el = $('#' + key + '-error');
      if ($el.length) $el.text(errors[key]);
    });
  }

  $slug.on('input', function () {
    let v = ($(this).val() || '').toLowerCase().replace(/[^a-z0-9-]/g, '');
    $(this).val(v);
    $preview.text(v || 'your-isp');
  });

  $('#primary_color_picker').on('input change', function () {
    $('#primary_color').val($(this).val());
  });
  $('#primary_color').on('input', function () {
    const v = $(this).val();
    if (/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/.test(v)) {
      $('#primary_color_picker').val(v.length === 4
        ? '#' + v[1] + v[1] + v[2] + v[2] + v[3] + v[3]
        : v);
    }
  });

  $('input[name="owner_mode"]').on('change', function () {
    const mode = $('input[name="owner_mode"]:checked').val();
    if (mode === 'existing') {
      $('#owner-existing').show();
      $('#owner-new').hide();
    } else {
      $('#owner-existing').hide();
      $('#owner-new').show();
    }
  });

  $form.on('submit', function (e) {
    e.preventDefault();
    clearErrors();
    const $btn = $('#tenant-submit').prop('disabled', true);
    const fd = new FormData(this);

    $.ajax({
      url: $form.attr('action'),
      type: 'POST',
      data: fd,
      processData: false,
      contentType: false,
      beforeSend: function (req) {
        req.setRequestHeader(csrfHeader, csrfHash);
      },
      success: function (res) {
        if (res.status === 'success') {
          const msg = (res.response && res.response.msg) || 'Saved';
          if (window.tata) tata.success('Portal saved', msg);
          const redirect = res.response && res.response.redirect;
          if (redirect) {
            window.location.href = redirect;
            return;
          }
          window.location.href = '<?= route_to('route.tenants'); ?>';
          return;
        }
        if (res.status === 'validation-error') {
          showErrors(res.response);
          $('#feedback').html('<div class="alert alert-danger">Please fix the highlighted fields.</div>');
        } else {
          $('#feedback').html('<div class="alert alert-danger">' + (res.response || 'Failed') + '</div>');
        }
        $btn.prop('disabled', false);
      },
      error: function (xhr) {
        const res = xhr.responseJSON;
        if (res && res.status === 'validation-error') {
          showErrors(res.response);
          $('#feedback').html('<div class="alert alert-danger">Please fix the highlighted fields.</div>');
        } else {
          $('#feedback').html('<div class="alert alert-danger">' + ((res && res.response) || 'Request failed') + '</div>');
        }
        $btn.prop('disabled', false);
      }
    });
  });
})();
</script>
<?= $this->endSection('script'); ?>
