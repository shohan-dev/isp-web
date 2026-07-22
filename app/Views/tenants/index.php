<?= $this->extend('layout/main-layout'); ?>
<?= $this->section('content'); ?>

<div class="content-wrapper">
  <section class="content ipb-saas-list">
    <?php
    $createUrl = route_to('route.tenants.create');
    $actions = '<a class="btn btn-primary" href="' . esc($createUrl, 'attr') . '"><i class="fa fa-plus" aria-hidden="true"></i> Create Portal</a>';
    echo $this->include('components/page-header', [
      'title' => 'Tenant Portals',
      'subtitle' => 'Subdomain portals for each ISP — one app, isolated data',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Tenant Portals'],
      ],
      'actions' => $actions,
    ]);
    ?>

    <?php if (session()->getFlashdata('error')): ?>
      <div class="alert alert-danger" role="alert"><?= esc(session()->getFlashdata('error')); ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('success')): ?>
      <div class="alert alert-success" role="alert"><?= esc(session()->getFlashdata('success')); ?></div>
    <?php endif; ?>

    <div class="row" style="margin-bottom:18px">
      <div class="col-md-3 col-sm-6" style="margin-bottom:12px">
        <div class="ipb-stat-card tone-navy">
          <div class="ipb-stat-icon"><i class="fa fa-globe" aria-hidden="true"></i></div>
          <div class="ipb-stat-value"><?= (int) ($stats['total'] ?? 0); ?></div>
          <div class="ipb-stat-label">Total portals</div>
        </div>
      </div>
      <div class="col-md-3 col-sm-6" style="margin-bottom:12px">
        <div class="ipb-stat-card tone-success">
          <div class="ipb-stat-icon"><i class="fa fa-circle-check" aria-hidden="true"></i></div>
          <div class="ipb-stat-value"><?= (int) ($stats['active'] ?? 0); ?></div>
          <div class="ipb-stat-label">Active</div>
        </div>
      </div>
      <div class="col-md-3 col-sm-6" style="margin-bottom:12px">
        <div class="ipb-stat-card tone-warning">
          <div class="ipb-stat-icon"><i class="fa fa-ban" aria-hidden="true"></i></div>
          <div class="ipb-stat-value"><?= (int) ($stats['suspended'] ?? 0); ?></div>
          <div class="ipb-stat-label">Suspended</div>
        </div>
      </div>
      <div class="col-md-3 col-sm-6" style="margin-bottom:12px">
        <div class="ipb-stat-card tone-info">
          <div class="ipb-stat-icon"><i class="fa fa-link-slash" aria-hidden="true"></i></div>
          <div class="ipb-stat-value"><?= (int) ($stats['unlinked'] ?? 0); ?></div>
          <div class="ipb-stat-label">Admins without portal</div>
        </div>
      </div>
    </div>

    <div class="box box-warning">
      <div class="box-header with-border ipb-box-toolbar">
        <form method="get" action="<?= route_to('route.tenants'); ?>" class="ipb-list-toolbar" style="width:100%;display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between">
          <div class="ipb-list-toolbar-filters" style="display:flex;flex-wrap:wrap;gap:10px;align-items:center">
            <label class="sr-only" for="tenantSearch">Search</label>
            <input type="search" id="tenantSearch" name="q" value="<?= esc($q ?? ''); ?>" class="form-control" placeholder="Search name, slug, email…" style="min-width:220px;max-width:280px" autocomplete="off" />
            <label class="sr-only" for="tenantStatus">Status</label>
            <select id="tenantStatus" name="status" class="form-control" style="min-width:140px">
              <option value="">All statuses</option>
              <option value="active" <?= ($status ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
              <option value="suspended" <?= ($status ?? '') === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
            </select>
            <label class="sr-only" for="tenantPerPage">Per page</label>
            <select id="tenantPerPage" name="per_page" class="form-control" style="min-width:100px">
              <?php foreach ([10, 25, 50, 100] as $n): ?>
                <option value="<?= $n; ?>" <?= (int) ($perPage ?? 25) === $n ? 'selected' : ''; ?>><?= $n; ?></option>
              <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-default"><i class="fa fa-search" aria-hidden="true"></i> Filter</button>
            <?php if (!empty($q) || !empty($status)): ?>
              <a href="<?= route_to('route.tenants'); ?>" class="btn btn-link">Clear</a>
            <?php endif; ?>
          </div>
          <div class="ipb-list-toolbar-actions">
            <a class="btn btn-primary" href="<?= route_to('route.tenants.create'); ?>">
              <i class="fa fa-plus" aria-hidden="true"></i> Create Portal
            </a>
          </div>
        </form>
      </div>

      <div class="box-body">
        <?php if (empty($tenants)): ?>
          <div class="ipb-empty">
            <div class="ipb-empty-icon"><i class="fa fa-globe" aria-hidden="true"></i></div>
            <div class="ipb-empty-title">No tenant portals yet</div>
            <p>Create a subdomain portal for an ISP. No Nginx or DNS change is required per tenant.</p>
            <a class="btn btn-primary" href="<?= route_to('route.tenants.create'); ?>">
              <i class="fa fa-plus" aria-hidden="true"></i> Create first portal
            </a>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-bordered table-striped" width="100%">
              <caption class="sr-only">Tenant portals</caption>
              <thead class="text-nowrap">
                <tr>
                  <th scope="col">#</th>
                  <th scope="col">Company</th>
                  <th scope="col">Portal URL</th>
                  <th scope="col">Owner</th>
                  <th scope="col">Plan</th>
                  <th scope="col">Status</th>
                  <th scope="col">Created</th>
                  <th scope="col">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($tenants as $i => $t): ?>
                  <?php
                    $isActive = strtolower((string) ($t->status ?? '')) === 'active';
                    $portal = tenantPortalUrl((string) $t->slug);
                  ?>
                  <tr>
                    <td><?= (int) $t->id; ?></td>
                    <td>
                      <strong><?= esc($t->name); ?></strong>
                      <div class="text-muted" style="font-size:12px"><?= esc($t->slug); ?></div>
                    </td>
                    <td>
                      <a href="<?= esc($portal, 'attr'); ?>" target="_blank" rel="noopener noreferrer" title="Open portal">
                        <?= esc($t->slug . '.' . ($baseDomain ?? '')); ?>
                        <i class="fa fa-arrow-up-right-from-square" aria-hidden="true" style="font-size:11px;opacity:.7"></i>
                      </a>
                    </td>
                    <td>
                      <?= esc($t->owner_name ?? '—'); ?>
                      <div class="text-muted" style="font-size:12px"><?= esc($t->owner_email ?? ''); ?></div>
                    </td>
                    <td><?= esc($t->plan ?: '—'); ?></td>
                    <td>
                      <span class="label label-<?= $isActive ? 'success' : 'warning'; ?>">
                        <?= $isActive ? 'Active' : 'Suspended'; ?>
                      </span>
                    </td>
                    <td class="text-nowrap">
                      <?= !empty($t->created_at) ? esc(date('d M Y', strtotime($t->created_at))) : '—'; ?>
                    </td>
                    <td class="text-nowrap">
                      <a class="btn btn-xs btn-info" href="<?= route_to('route.tenants.details', (int) $t->id); ?>" title="Details" aria-label="View tenant details">
                        <i class="fa fa-eye" aria-hidden="true"></i>
                      </a>
                      <a class="btn btn-xs btn-primary" href="<?= route_to('route.tenants.edit', (int) $t->id); ?>" title="Edit" aria-label="Edit tenant portal">
                        <i class="fa fa-pen" aria-hidden="true"></i>
                      </a>
                      <button type="button" class="btn btn-xs btn-<?= $isActive ? 'warning' : 'success'; ?> js-tenant-status"
                        data-id="<?= (int) $t->id; ?>"
                        data-status="<?= $isActive ? 'suspended' : 'active'; ?>"
                        title="<?= $isActive ? 'Suspend' : 'Activate'; ?>"
                        aria-label="<?= $isActive ? 'Suspend portal' : 'Activate portal'; ?>">
                        <i class="fa fa-<?= $isActive ? 'ban' : 'circle-check'; ?>" aria-hidden="true"></i>
                      </button>
                      <button type="button" class="btn btn-xs btn-default js-copy-url" data-url="<?= esc($portal, 'attr'); ?>" title="Copy URL" aria-label="Copy portal URL">
                        <i class="fa fa-copy" aria-hidden="true"></i>
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?= $pager->links() ?>
        <?php endif; ?>
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

  function toast(type, msg) {
    if (window.tata) {
      tata[type === 'error' ? 'error' : 'success'](type === 'error' ? 'Error' : 'Success', msg);
      return;
    }
    alert(msg);
  }

  $(document).on('click', '.js-copy-url', function () {
    const url = $(this).data('url');
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(url).then(function () {
        toast('success', 'Portal URL copied');
      }).catch(function () {
        window.prompt('Copy URL', url);
      });
    } else {
      window.prompt('Copy URL', url);
    }
  });

  $(document).on('click', '.js-tenant-status', function () {
    const $btn = $(this);
    const id = $btn.data('id');
    const status = $btn.data('status');
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
        beforeSend: function (req) {
          req.setRequestHeader(csrfHeader, csrfHash);
        },
        success: function (res) {
          if (res.status === 'success') {
            toast('success', res.response.msg || 'Updated');
            window.location.reload();
          } else {
            toast('error', (res.response && res.response.msg) || res.response || 'Failed');
          }
        },
        error: function (xhr) {
          const res = xhr.responseJSON;
          toast('error', (res && res.response) || 'Request failed');
        }
      });
    });
  });
})();
</script>
<?= $this->endSection('script'); ?>
