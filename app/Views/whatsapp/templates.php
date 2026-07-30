<?= $this->extend('layout/main-layout') ?>

<?= $this->section('css') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/saas/whatsapp-pages.css?v=1') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$statusBadge = [
  'APPROVED' => 'is-success',
  'PENDING'  => 'is-warning',
  'REJECTED' => 'is-danger',
];
?>
<div class="content-wrapper">
  <section class="content ipb-saas-list ipb-wa-page">

    <?= $this->include('components/page-header', [
      'title' => 'WhatsApp Templates',
      'subtitle' => 'Map approved Meta templates to ISP events',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'WhatsApp Business', 'url' => route_to('route.whatsapp.settings')],
        ['label' => 'Templates'],
      ],
    ]) ?>

    <?php if (!empty($can_update)): ?>
    <div class="ipb-wa-card box" style="margin-bottom:16px;">
      <div class="ipb-wa-panel-head">
        <div>
          <h3>Add / update template</h3>
          <p class="ipb-wa-panel-sub">Only APPROVED templates can be used for outbound sends</p>
        </div>
      </div>
      <div class="box-body">
        <form id="wa-tpl-form" class="form">
          <input type="hidden" name="action" value="save">
          <input type="hidden" name="id" id="tpl-id" value="">
          <div class="ipb-wa-form-grid">
            <div class="ipb-wa-field">
              <label class="ipb-wa-label" for="tpl-name">Template name</label>
              <input id="tpl-name" class="form-control" name="name" placeholder="invoice_ready_en" required>
            </div>
            <div class="ipb-wa-field">
              <label class="ipb-wa-label" for="tpl-lang">Language</label>
              <input id="tpl-lang" class="form-control" name="language" value="en" placeholder="en / bn">
            </div>
            <div class="ipb-wa-field">
              <label class="ipb-wa-label" for="tpl-cat">Category</label>
              <select id="tpl-cat" name="category" class="form-control">
                <option value="UTILITY">UTILITY</option>
                <option value="AUTHENTICATION">AUTHENTICATION</option>
                <option value="MARKETING">MARKETING</option>
              </select>
            </div>
            <div class="ipb-wa-field">
              <label class="ipb-wa-label" for="tpl-status">Status</label>
              <select id="tpl-status" name="status" class="form-control">
                <option value="PENDING">PENDING</option>
                <option value="APPROVED">APPROVED</option>
                <option value="REJECTED">REJECTED</option>
              </select>
            </div>
            <div class="ipb-wa-field">
              <label class="ipb-wa-label" for="tpl-event">Event key</label>
              <input id="tpl-event" class="form-control" name="event_key" placeholder="e.g. invoice_ready, otp">
            </div>
            <div class="ipb-wa-field">
              <label class="ipb-wa-label" for="tpl-meta-id">Meta template ID</label>
              <input id="tpl-meta-id" class="form-control" name="meta_template_id" placeholder="Optional Meta ID">
            </div>
            <div class="ipb-wa-field is-wide">
              <label class="ipb-wa-label" for="tpl-preview">Body preview</label>
              <input id="tpl-preview" class="form-control" name="body_preview" placeholder="Hello {{1}}, your invoice for {{2}} is ready…">
            </div>
            <div class="ipb-wa-field">
              <label class="ipb-wa-toggle" style="height:100%;">
                <input type="checkbox" name="is_enabled" value="1">
                <span class="ipb-wa-toggle__text">
                  <strong>Enabled</strong>
                  <span>Allow outbound use of this template</span>
                </span>
              </label>
            </div>
          </div>
          <div class="ipb-wa-actions" style="margin-top:14px;">
            <button class="btn btn-primary" type="submit">
              <i class="fa fa-save" aria-hidden="true"></i> Save template
            </button>
          </div>
        </form>
      </div>
    </div>
    <?php endif; ?>

    <div class="ipb-wa-card box">
      <div class="ipb-wa-panel-head">
        <div>
          <h3>Mapped templates</h3>
        </div>
        <span class="ipb-pay-badge is-info"><?= count($templates) ?> template(s)</span>
      </div>
      <div class="box-body table-responsive ipb-wa-table-wrap">
        <?php if (empty($templates)): ?>
          <?= $this->include('components/empty-state', [
            'title' => 'No templates yet',
            'subtitle' => 'Add an APPROVED Meta template and map it to an event key.',
            'icon' => 'fa fa-file-text-o',
          ]) ?>
        <?php else: ?>
          <table class="table table-bordered table-striped">
            <thead>
              <tr>
                <th>Name</th>
                <th>Lang</th>
                <th>Category</th>
                <th>Status</th>
                <th>Event</th>
                <th>Enabled</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($templates as $t):
                $st = (string) ($t->status ?? '');
                $badge = $statusBadge[$st] ?? 'is-info';
              ?>
                <tr>
                  <td><strong><?= esc($t->name) ?></strong></td>
                  <td><?= esc($t->language) ?></td>
                  <td><span class="ipb-pay-badge is-info"><?= esc($t->category) ?></span></td>
                  <td><span class="ipb-pay-badge <?= esc($badge) ?>"><?= esc($st) ?></span></td>
                  <td><?= esc($t->event_key ?? '') ?: '—' ?></td>
                  <td>
                    <?php if ((int) $t->is_enabled): ?>
                      <span class="ipb-pay-badge is-success">Yes</span>
                    <?php else: ?>
                      <span class="ipb-pay-badge">No</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if (!empty($can_update)): ?>
                      <button class="btn btn-xs btn-danger wa-tpl-del" type="button" data-id="<?= (int) $t->id ?>">Delete</button>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>

  </section>
</div>
<script>
(function () {
  var form = document.getElementById('wa-tpl-form');
  if (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      fetch(location.href, { method: 'POST', body: new FormData(form), headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { return r.json(); })
        .then(function (j) { alert(j.message || 'Done'); if (j.status === 'succes' || j.status === 'success') location.reload(); });
    });
  }
  document.querySelectorAll('.wa-tpl-del').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (!confirm('Delete template?')) return;
      var fd = new FormData();
      fd.append('action', 'delete');
      fd.append('id', btn.getAttribute('data-id'));
      fetch(location.href, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { return r.json(); })
        .then(function () { location.reload(); });
    });
  });
})();
</script>
<?= $this->endSection() ?>
