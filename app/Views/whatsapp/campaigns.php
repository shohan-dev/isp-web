<?= $this->extend('layout/main-layout') ?>

<?= $this->section('css') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/saas/whatsapp-pages.css?v=1') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$statusBadge = [
  'draft'     => 'is-info',
  'running'   => 'is-warning',
  'completed' => 'is-success',
  'failed'    => 'is-danger',
  'cancelled' => '',
];
?>
<div class="content-wrapper">
  <section class="content ipb-saas-list ipb-wa-page">

    <?= $this->include('components/page-header', [
      'title' => 'Marketing Campaigns',
      'subtitle' => 'Broadcast APPROVED marketing templates to opted-in numbers',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'WhatsApp Business', 'url' => route_to('route.whatsapp.settings')],
        ['label' => 'Campaigns'],
      ],
    ]) ?>

    <div class="ipb-wa-callout is-warn">
      <p class="ipb-wa-callout__body" style="margin:0;">
        <strong>Billable:</strong>
        Only opted-in numbers are messaged. Marketing template sends are charged by Meta.
      </p>
    </div>

    <?php if (!empty($can_edit)): ?>
    <div class="ipb-wa-card box" style="margin-bottom:16px;">
      <div class="ipb-wa-panel-head">
        <div>
          <h3>Create draft campaign</h3>
          <p class="ipb-wa-panel-sub">Launch only after reviewing the template and opt-in list</p>
        </div>
      </div>
      <div class="box-body">
        <form id="wa-camp-form" class="ipb-wa-toolbar">
          <input type="hidden" name="action" value="create">
          <div class="ipb-wa-field">
            <label class="ipb-wa-label" for="camp-name">Campaign name</label>
            <input id="camp-name" class="form-control" name="name" placeholder="April promo" required>
          </div>
          <div class="ipb-wa-field">
            <label class="ipb-wa-label" for="camp-tpl">Marketing template</label>
            <select id="camp-tpl" name="template_id" class="form-control" required>
              <option value="">Select template…</option>
              <?php foreach ($templates as $t): ?>
                <option value="<?= (int) $t->id ?>"><?= esc($t->name) ?> (<?= esc($t->language) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="ipb-wa-field" style="flex:0 0 auto;">
            <label class="ipb-wa-label">&nbsp;</label>
            <button class="btn btn-primary" type="submit">
              <i class="fa fa-plus" aria-hidden="true"></i> Create draft
            </button>
          </div>
        </form>
        <?php if (empty($templates)): ?>
          <p class="ipb-wa-hint" style="margin-top:8px;">No marketing templates mapped yet. Add one under Templates first.</p>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <div class="ipb-wa-card box">
      <div class="ipb-wa-panel-head">
        <div>
          <h3>Campaigns</h3>
        </div>
        <span class="ipb-pay-badge is-info"><?= count($campaigns ?? []) ?> campaign(s)</span>
      </div>
      <div class="box-body table-responsive ipb-wa-table-wrap">
        <?php if (empty($campaigns)): ?>
          <?= $this->include('components/empty-state', [
            'title' => 'No campaigns yet',
            'subtitle' => 'Create a draft, then launch when ready.',
            'icon' => 'fa fa-bullhorn',
          ]) ?>
        <?php else: ?>
          <table class="table table-bordered table-striped">
            <thead>
              <tr>
                <th>Name</th>
                <th>Status</th>
                <th>Stats</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($campaigns as $c):
                $st = strtolower((string) ($c->status ?? ''));
                $badge = $statusBadge[$st] ?? 'is-info';
              ?>
                <tr>
                  <td><strong><?= esc($c->name) ?></strong></td>
                  <td>
                    <span class="ipb-pay-badge <?= esc($badge) ?>"><?= esc($c->status) ?></span>
                  </td>
                  <td>
                    <code class="ipb-wa-preview" title="<?= esc($c->stats_json ?? '', 'attr') ?>">
                      <?= esc($c->stats_json ?? '') ?: '—' ?>
                    </code>
                  </td>
                  <td>
                    <?php if (!empty($can_edit) && $c->status === 'draft'): ?>
                      <button class="btn btn-sm btn-warning wa-camp-launch" type="button" data-id="<?= (int) $c->id ?>">
                        <i class="fa fa-rocket" aria-hidden="true"></i> Launch
                      </button>
                    <?php else: ?>
                      —
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
  var form = document.getElementById('wa-camp-form');
  if (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      fetch(location.href, { method: 'POST', body: new FormData(form), headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { return r.json(); })
        .then(function (j) { alert(j.message || 'Done'); location.reload(); });
    });
  }
  document.querySelectorAll('.wa-camp-launch').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (!confirm('Launch campaign to all opted-in numbers? This is billable.')) return;
      var fd = new FormData();
      fd.append('action', 'launch');
      fd.append('id', btn.getAttribute('data-id'));
      fetch(location.href, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { return r.json(); })
        .then(function (j) { alert(j.message || 'Done'); location.reload(); });
    });
  });
})();
</script>
<?= $this->endSection() ?>
