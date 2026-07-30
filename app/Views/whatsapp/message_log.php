<?= $this->extend('layout/main-layout') ?>

<?= $this->section('css') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/saas/whatsapp-pages.css?v=1') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$statusBadge = [
  'sent'      => 'is-success',
  'delivered' => 'is-success',
  'read'      => 'is-info',
  'failed'    => 'is-danger',
  'pending'   => 'is-warning',
  'queued'    => 'is-warning',
];
$dirBadge = [
  'in'       => 'is-info',
  'inbound'  => 'is-info',
  'out'      => 'is-success',
  'outbound' => 'is-success',
];
?>
<div class="content-wrapper">
  <section class="content ipb-saas-list ipb-wa-page">

    <?= $this->include('components/page-header', [
      'title' => 'Message Log',
      'subtitle' => 'Outbound and inbound WhatsApp message history',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'WhatsApp Business', 'url' => route_to('route.whatsapp.settings')],
        ['label' => 'Message Log'],
      ],
    ]) ?>

    <div class="ipb-wa-card box">
      <div class="ipb-wa-panel-head">
        <div>
          <h3>Recent messages</h3>
          <p class="ipb-wa-panel-sub">Payloads are redacted for security</p>
        </div>
        <span class="ipb-pay-badge is-info"><?= count($logs ?? []) ?> row(s)</span>
      </div>
      <div class="box-body table-responsive ipb-wa-table-wrap">
        <?php if (empty($logs)): ?>
          <?= $this->include('components/empty-state', [
            'title' => 'No messages logged yet',
            'subtitle' => 'Sends, webhooks, and AI replies will appear here.',
            'icon' => 'fa fa-list-alt',
          ]) ?>
        <?php else: ?>
          <table class="table table-striped table-bordered">
            <thead>
              <tr>
                <th>ID</th>
                <th>Phone</th>
                <th>Category</th>
                <th>Dir</th>
                <th>Template</th>
                <th>Status</th>
                <th>Billable</th>
                <th>Preview</th>
                <th>When</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($logs as $log):
                $st = strtolower((string) ($log->status ?? ''));
                $dir = strtolower((string) ($log->direction ?? ''));
                $stClass = $statusBadge[$st] ?? 'is-info';
                $dirClass = $dirBadge[$dir] ?? '';
              ?>
                <tr>
                  <td><?= (int) $log->id ?></td>
                  <td class="ipb-acc-nowrap"><?= esc($log->wa_phone) ?></td>
                  <td><?= esc($log->category) ?></td>
                  <td>
                    <span class="ipb-pay-badge <?= esc($dirClass) ?>"><?= esc($log->direction) ?></span>
                  </td>
                  <td><?= esc($log->template_name ?? '') ?: '—' ?></td>
                  <td>
                    <span class="ipb-pay-badge <?= esc($stClass) ?>"><?= esc($log->status) ?></span>
                  </td>
                  <td>
                    <?php if ((int) $log->billable): ?>
                      <span class="ipb-pay-badge is-warning">Yes</span>
                    <?php else: ?>
                      <span class="ipb-pay-badge">No</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div class="ipb-wa-preview" title="<?= esc($log->payload_redacted ?? '', 'attr') ?>">
                      <?= esc($log->payload_redacted ?? '') ?: '—' ?>
                    </div>
                  </td>
                  <td class="ipb-acc-nowrap"><?= esc($log->created_at ?? '') ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>

  </section>
</div>
<?= $this->endSection() ?>
