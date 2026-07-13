<?= $this->extend('layout/main-layout'); ?>
<?= $this->section('content'); ?>

<?php
$isActive = strtolower((string) ($tenant->status ?? '')) === 'active';
$portalUrl = $portalUrl ?? tenantPortalUrl((string) $tenant->slug);
$color = $tenant->primary_color ?? '#2563eb';
?>

<style>
  .ipb-portal-url-cell {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
    max-width: 100%;
  }
  .ipb-portal-url-link {
    flex: 1 1 auto;
    min-width: 0;
    word-break: break-all;
    overflow-wrap: anywhere;
  }
  .ipb-portal-url-copy {
    flex: 0 0 auto;
    white-space: nowrap;
  }
  /* btn-xs (~22px tall) is fine next to a mouse pointer, but on a phone this
     is the only way to copy the portal URL — bump it to a thumb-safe target
     without touching the shared .btn-xs class used sitewide. */
  @media (max-width: 767px) {
    .ipb-portal-url-copy {
      min-width: 44px;
      min-height: 44px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }
  }
</style>

<div class="content-wrapper">
  <section class="content ipb-saas-list">
    <?php
    $actions = '<a class="btn btn-default" href="' . esc(route_to('route.tenants.edit', (int) $tenant->id), 'attr') . '"><i class="fa fa-pen" aria-hidden="true"></i> Edit</a> '
      . '<a class="btn btn-primary" href="' . esc($portalUrl, 'attr') . '" target="_blank" rel="noopener noreferrer"><i class="fa fa-arrow-up-right-from-square" aria-hidden="true"></i> Open portal</a>';
    echo $this->include('components/page-header', [
      'title' => $tenant->name,
      'subtitle' => 'Tenant #' . (int) $tenant->id . ' · ' . $tenant->slug . '.' . ($baseDomain ?? ''),
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Tenant Portals', 'url' => route_to('route.tenants')],
        ['label' => 'Details'],
      ],
      'actions' => $actions,
    ]);
    ?>

    <div class="row">
      <div class="col-md-8">
        <div class="box box-primary">
          <div class="box-header with-border">
            <h3 class="box-title">Portal overview</h3>
            <span class="label label-<?= $isActive ? 'success' : 'warning'; ?> pull-right" style="margin-top:4px">
              <?= $isActive ? 'Active' : 'Suspended'; ?>
            </span>
          </div>
          <div class="box-body">
            <div style="display:flex;gap:18px;align-items:flex-start;flex-wrap:wrap">
              <div style="width:88px;height:88px;border-radius:14px;border:1px solid var(--border,#e2e8f0);display:flex;align-items:center;justify-content:center;overflow:hidden;background:var(--surface, #fff)">
                <img src="<?= esc($logoUrl ?? tenantLogoUrl($tenant), 'attr'); ?>" alt="" style="max-width:100%;max-height:100%;object-fit:contain" />
              </div>
              <div style="flex:1;min-width:220px">
                <div class="table-responsive">
                <table class="table table-bordered" style="margin:0">
                  <caption class="sr-only">Portal details</caption>
                  <tr>
                    <th scope="row" style="width:160px">Company</th>
                    <td><?= esc($tenant->name); ?></td>
                  </tr>
                  <tr>
                    <th scope="row">Subdomain</th>
                    <td><code><?= esc($tenant->slug); ?></code></td>
                  </tr>
                  <tr>
                    <th scope="row">Portal URL</th>
                    <td>
                      <div class="ipb-portal-url-cell">
                        <a href="<?= esc($portalUrl, 'attr'); ?>" class="ipb-portal-url-link" target="_blank" rel="noopener noreferrer"><?= esc($portalUrl); ?></a>
                        <button type="button" class="btn btn-xs btn-default js-copy-url ipb-portal-url-copy" data-url="<?= esc($portalUrl, 'attr'); ?>" aria-label="Copy portal URL">
                          <i class="fa fa-copy" aria-hidden="true"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                  <tr>
                    <th scope="row">Plan</th>
                    <td><?= esc($tenant->plan ?: '—'); ?></td>
                  </tr>
                  <tr>
                    <th scope="row">Brand color</th>
                    <td>
                      <span style="display:inline-block;width:14px;height:14px;border-radius:4px;background:<?= esc($color, 'attr'); ?>;vertical-align:middle;margin-right:6px;border:1px solid #ddd"></span>
                      <?= esc($color); ?>
                    </td>
                  </tr>
                  <tr>
                    <th scope="row">Created</th>
                    <td><?= !empty($tenant->created_at) ? esc(date('d M Y, h:i a', strtotime($tenant->created_at))) : '—'; ?></td>
                  </tr>
                  <tr>
                    <th scope="row">Updated</th>
                    <td><?= !empty($tenant->updated_at) ? esc(date('d M Y, h:i a', strtotime($tenant->updated_at))) : '—'; ?></td>
                  </tr>
                  <?php if (!empty($tenant->notes)): ?>
                    <tr>
                      <th scope="row">Notes</th>
                      <td><?= nl2br(esc($tenant->notes)); ?></td>
                    </tr>
                  <?php endif; ?>
                </table>
                </div>
              </div>
            </div>
          </div>
          <div class="box-footer" style="display:flex;flex-wrap:wrap;gap:8px">
            <button type="button" class="btn btn-<?= $isActive ? 'warning' : 'success'; ?> js-tenant-status"
              data-id="<?= (int) $tenant->id; ?>"
              data-status="<?= $isActive ? 'suspended' : 'active'; ?>">
              <i class="fa fa-<?= $isActive ? 'ban' : 'circle-check'; ?>" aria-hidden="true"></i>
              <?= $isActive ? 'Suspend portal' : 'Activate portal'; ?>
            </button>
            <a class="btn btn-default" href="<?= route_to('route.tenants.edit', (int) $tenant->id); ?>">
              <i class="fa fa-pen" aria-hidden="true"></i> Edit branding
            </a>
            <?php if (!empty($tenant->owner_user_id)): ?>
              <a class="btn btn-info" href="<?= route_to('route.Admin.details', (int) $tenant->owner_user_id); ?>">
                <i class="fa fa-user" aria-hidden="true"></i> Owner profile
              </a>
            <?php endif; ?>
          </div>
        </div>

        <div class="box box-default">
          <div class="box-header with-border">
            <h3 class="box-title">Owner (Second Admin)</h3>
          </div>
          <div class="box-body">
            <div class="table-responsive">
            <table class="table table-bordered" style="margin:0">
              <caption class="sr-only">Owner details</caption>
              <tr>
                <th scope="row" style="width:160px">Name</th>
                <td><?= esc($tenant->owner_name ?? '—'); ?></td>
              </tr>
              <tr>
                <th scope="row">Email</th>
                <td><?= esc($tenant->owner_email ?? '—'); ?></td>
              </tr>
              <tr>
                <th scope="row">Mobile</th>
                <td><?= esc($tenant->owner_mobile ?? '—'); ?></td>
              </tr>
              <tr>
                <th scope="row">Account</th>
                <td><?= esc($tenant->owner_status ?? '—'); ?></td>
              </tr>
              <tr>
                <th scope="row">Subscription</th>
                <td><?= esc($tenant->owner_subscription ?? '—'); ?></td>
              </tr>
            </table>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="ipb-stat-card tone-info" style="margin-bottom:14px">
          <div class="ipb-stat-icon"><i class="fa fa-users" aria-hidden="true"></i></div>
          <div class="ipb-stat-value"><?= (int) ($counts['customers'] ?? 0); ?></div>
          <div class="ipb-stat-label">Customers (direct)</div>
        </div>
        <div class="ipb-stat-card tone-navy" style="margin-bottom:14px">
          <div class="ipb-stat-icon"><i class="fa fa-user-group" aria-hidden="true"></i></div>
          <div class="ipb-stat-value"><?= (int) ($counts['resellers'] ?? 0); ?></div>
          <div class="ipb-stat-label">Resellers</div>
        </div>
        <div class="ipb-stat-card tone-success" style="margin-bottom:14px">
          <div class="ipb-stat-icon"><i class="fa fa-user-gear" aria-hidden="true"></i></div>
          <div class="ipb-stat-value"><?= (int) ($counts['employees'] ?? 0); ?></div>
          <div class="ipb-stat-label">Employees</div>
        </div>

        <div class="box box-solid">
          <div class="box-header with-border">
            <h3 class="box-title">How it works</h3>
          </div>
          <div class="box-body" style="font-size:13px;line-height:1.65;color:var(--text-secondary, #64748b)">
            <p>Visitors open <strong><?= esc($tenant->slug); ?>.<?= esc($baseDomain ?? ''); ?></strong>.</p>
            <p>Nginx routes all subdomains to this app. PHP loads this tenant and scopes branding and access.</p>
            <p style="margin:0">No server restart is required when you create or edit portals.</p>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<?= $this->endSection('content'); ?>

<?= $this->section('script'); ?>
<script>
(function () {
  const csrfHeader = '<?= csrf_header(); ?>';
  const csrfHash = '<?= csrf_hash(); ?>';

  $(document).on('click', '.js-copy-url', function () {
    const url = $(this).data('url');
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(url).then(function () {
        if (window.tata) tata.success('Copied', 'Portal URL copied');
      });
    } else {
      window.prompt('Copy URL', url);
    }
  });

  $(document).on('click', '.js-tenant-status', function () {
    const id = $(this).data('id');
    const status = $(this).data('status');
    const label = status === 'suspended' ? 'suspend' : 'activate';
    swal({
      title: 'Confirm',
      text: 'Are you sure you want to ' + label + ' this portal?',
      icon: 'warning',
      buttons: ['Cancel', label.charAt(0).toUpperCase() + label.slice(1)],
      dangerMode: status === 'suspended',
    }).then(function (ok) {
      if (!ok) return;
      $.ajax({
        url: '<?= site_url('tenants/status'); ?>/' + id,
        type: 'POST',
        data: { status: status },
        beforeSend: function (req) { req.setRequestHeader(csrfHeader, csrfHash); },
        success: function (res) {
          if (res.status === 'success') {
            window.location.reload();
          } else if (window.tata) {
            tata.error("Couldn't update portal status", res.response || 'Failed');
          }
        },
        error: function () {
          if (window.tata) tata.error("Couldn't update portal status", 'Request failed');
        }
      });
    });
  });
})();
</script>
<?= $this->endSection('script'); ?>
