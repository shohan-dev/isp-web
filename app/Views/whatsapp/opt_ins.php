<?= $this->extend('layout/main-layout') ?>

<?= $this->section('css') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/saas/whatsapp-pages.css?v=1') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="content-wrapper">
  <section class="content ipb-saas-list ipb-wa-page">

    <?= $this->include('components/page-header', [
      'title' => 'Marketing Opt-ins',
      'subtitle' => 'Only opted-in numbers can receive marketing campaigns',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'WhatsApp Business', 'url' => route_to('route.whatsapp.settings')],
        ['label' => 'Opt-ins'],
      ],
    ]) ?>

    <?php if (!empty($can_edit)): ?>
    <div class="ipb-wa-card box" style="margin-bottom:16px;">
      <div class="ipb-wa-panel-head">
        <div>
          <h3>Add or update opt-in</h3>
          <p class="ipb-wa-panel-sub">Use E.164 or local BD format (01XXXXXXXXX)</p>
        </div>
      </div>
      <div class="box-body">
        <form id="wa-optin-form" class="ipb-wa-toolbar">
          <div class="ipb-wa-field">
            <label class="ipb-wa-label" for="wa-optin-phone">Phone</label>
            <input id="wa-optin-phone" class="form-control" name="wa_phone" placeholder="8801XXXXXXXXX" required>
          </div>
          <div class="ipb-wa-field" style="flex:0 0 auto;">
            <label class="ipb-wa-label">&nbsp;</label>
            <label class="ipb-wa-toggle" style="padding:9px 14px;">
              <input type="checkbox" name="marketing_opt_in" value="1" checked>
              <span class="ipb-wa-toggle__text"><strong>Opted in</strong></span>
            </label>
          </div>
          <div class="ipb-wa-field" style="flex:0 0 auto;">
            <label class="ipb-wa-label">&nbsp;</label>
            <button class="btn btn-primary" type="submit">
              <i class="fa fa-save" aria-hidden="true"></i> Save
            </button>
          </div>
        </form>
      </div>
    </div>
    <?php endif; ?>

    <div class="ipb-wa-card box">
      <div class="ipb-wa-panel-head">
        <div>
          <h3>Opt-in list</h3>
        </div>
        <span class="ipb-pay-badge is-info"><?= count($rows ?? []) ?> number(s)</span>
      </div>
      <div class="box-body table-responsive ipb-wa-table-wrap">
        <?php if (empty($rows)): ?>
          <?= $this->include('components/empty-state', [
            'title' => 'No opt-ins yet',
            'subtitle' => 'Add numbers that consented to marketing WhatsApp messages.',
            'icon' => 'fa fa-check-square-o',
          ]) ?>
        <?php else: ?>
          <table class="table table-striped table-bordered">
            <thead>
              <tr>
                <th>Phone</th>
                <th>Opt-in</th>
                <th>Source</th>
                <th>In at</th>
                <th>Out at</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $r): ?>
                <tr>
                  <td class="ipb-acc-nowrap"><strong><?= esc($r->wa_phone) ?></strong></td>
                  <td>
                    <?php if ((int) $r->marketing_opt_in): ?>
                      <span class="ipb-pay-badge is-success">Opted in</span>
                    <?php else: ?>
                      <span class="ipb-pay-badge is-danger">Opted out</span>
                    <?php endif; ?>
                  </td>
                  <td><?= esc($r->source ?? '') ?: '—' ?></td>
                  <td class="ipb-acc-nowrap"><?= esc($r->opted_in_at ?? '') ?: '—' ?></td>
                  <td class="ipb-acc-nowrap"><?= esc($r->opted_out_at ?? '') ?: '—' ?></td>
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
  var form = document.getElementById('wa-optin-form');
  if (!form) return;
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    fetch(location.href, { method: 'POST', body: new FormData(form), headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (r) { return r.json(); })
      .then(function (j) { alert(j.message || 'Done'); location.reload(); });
  });
})();
</script>
<?= $this->endSection() ?>
