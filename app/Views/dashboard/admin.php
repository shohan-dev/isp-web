<?= $this->extend('layout/main-layout'); ?>

<?= $this->section('css'); ?>
<?= saas_css('dashboard.css') ?>
<?= $this->endSection('css'); ?>

<?= $this->section('content'); ?>
<?php
$revenueTotalAll = (float) ($revenue_total_all ?? 0);
$revenueThisMonth = (float) ($revenue_this_month ?? 0);
$revenueLastMonth = (float) ($revenue_last_month ?? 0);
$revenuePending = (float) ($revenue_pending ?? 0);
$revenueMonthly = is_array($revenue_monthly ?? null) ? $revenue_monthly : [];
$packageUsers = is_array($package_users ?? null) ? $package_users : [];
$fmt = static function ($n) {
  return '৳' . number_format((float) $n, 0);
};
?>
<div class="content-wrapper">
  <section class="content">
    <?= $this->include('components/page-header', [
      'title' => 'Dashboard',
      'breadcrumb' => [
        ['label' => 'Home', 'url' => route_to('route.dashboard')],
        ['label' => 'Dashboard'],
      ],
      'subtitle' => 'Platform control panel',
    ]); ?>

    <div class="ipb-dash fade-in" data-ipb-dashboard="admin">
      <div class="ipb-dash-toolbar">
        <a href="<?= route_to('route.Admin.revenue'); ?>" class="ipb-btn-outline">
          <i class="fa fa-chart-line" aria-hidden="true"></i> Full revenue report
        </a>
        <button type="button" class="ipb-btn-outline" data-ipb-open-theme>
          <i class="fa fa-palette" aria-hidden="true"></i> Theme Studio
        </button>
        <button type="button" class="ipb-btn-outline" data-ipb-open-customize>
          <i class="fa fa-sliders" aria-hidden="true"></i> <span data-label>Customize</span>
        </button>
      </div>
      <div class="ipb-dash-empty" data-ipb-dash-empty>
        <div class="ipb-dash-empty-icon"><i class="fa fa-eye-slash" aria-hidden="true"></i></div>
        <h3>Every widget is hidden</h3>
        <p>Open Customize to bring sections back.</p>
        <button type="button" class="ipb-btn-outline" data-ipb-open-customize-empty>
          <i class="fa fa-sliders" aria-hidden="true"></i> Customize Dashboard
        </button>
      </div>
      <div class="ipb-dash-grid" data-ipb-dash-grid>
        <div class="ipb-widget" data-widget-id="kpi" data-size="full" data-title="Key metrics" data-icon="fa-solid fa-table-cells">
          <div class="ipb-dash-kpi">
            <a href="<?= route_to('route.Admin'); ?>" class="ipb-kpi tone-navy">
              <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-users-gear"></i></span></div>
              <div class="ipb-kpi-value"><?= (int) $total_users; ?></div>
              <div class="ipb-kpi-label">Total Admins</div>
              <div class="ipb-kpi-cta">View details <i class="fa fa-chevron-right"></i></div>
            </a>

            <a href="<?= route_to('route.Admin') . '?status=active'; ?>" class="ipb-kpi tone-success">
              <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-user-check"></i></span></div>
              <div class="ipb-kpi-value"><?= (int) $users_active; ?></div>
              <div class="ipb-kpi-label">Active Admins</div>
              <div class="ipb-kpi-cta">View details <i class="fa fa-chevron-right"></i></div>
            </a>

            <a href="<?= route_to('route.Admin') . '?status=inactive'; ?>" class="ipb-kpi tone-error">
              <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-user-slash"></i></span></div>
              <div class="ipb-kpi-value"><?= (int) $users_inactive; ?></div>
              <div class="ipb-kpi-label">Inactive Admins</div>
              <div class="ipb-kpi-cta">View details <i class="fa fa-chevron-right"></i></div>
            </a>

            <a href="<?= route_to('Admin.packages'); ?>" class="ipb-kpi tone-brand">
              <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-box-open"></i></span></div>
              <div class="ipb-kpi-value"><?= (int) $total_packages; ?></div>
              <div class="ipb-kpi-label">Active Packages</div>
              <div class="ipb-kpi-cta">View details <i class="fa fa-chevron-right"></i></div>
            </a>
          </div>
        </div>

        <div class="ipb-widget" data-widget-id="revenue" data-size="full" data-title="Platform revenue" data-icon="fa-solid fa-chart-line">
          <div class="ipb-section-label">Platform revenue (Second Admin payments)</div>
          <div class="ipb-dash-kpi">
            <a href="<?= route_to('route.Admin.revenue'); ?>" class="ipb-kpi tone-success">
              <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-wallet"></i></span></div>
              <div class="ipb-kpi-value" style="font-size:22px"><?= esc($fmt($revenueTotalAll)); ?></div>
              <div class="ipb-kpi-label">Total revenue</div>
              <div class="ipb-kpi-cta has-meta"><?= (int) ($revenue_total_count ?? 0); ?> payments · View report <i class="fa fa-chevron-right"></i></div>
            </a>
            <a href="<?= route_to('route.Admin.revenue'); ?>?month=<?= urlencode(date('F')); ?>&year=<?= (int) date('Y'); ?>&status=successful" class="ipb-kpi tone-brand">
              <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-calendar-day"></i></span></div>
              <div class="ipb-kpi-value" style="font-size:22px"><?= esc($fmt($revenueThisMonth)); ?></div>
              <div class="ipb-kpi-label">This month (<?= esc($revenue_this_month_label ?? date('F Y')); ?>)</div>
              <div class="ipb-kpi-cta has-meta"><?= (int) ($revenue_this_month_count ?? 0); ?> payments · Filter <i class="fa fa-chevron-right"></i></div>
            </a>
            <a href="<?= route_to('route.Admin.revenue'); ?>?month=<?= urlencode(date('F', strtotime('first day of last month'))); ?>&year=<?= (int) date('Y', strtotime('first day of last month')); ?>&status=successful" class="ipb-kpi tone-navy">
              <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-calendar"></i></span></div>
              <div class="ipb-kpi-value" style="font-size:22px"><?= esc($fmt($revenueLastMonth)); ?></div>
              <div class="ipb-kpi-label">Last month (<?= esc($revenue_last_month_label ?? ''); ?>)</div>
              <div class="ipb-kpi-cta"><?= (int) ($revenue_last_month_count ?? 0); ?> payments · Filter <i class="fa fa-chevron-right"></i></div>
            </a>
            <a href="<?= route_to('route.Admin.revenue'); ?>?status=pending" class="ipb-kpi tone-warning">
              <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-clock"></i></span></div>
              <div class="ipb-kpi-value" style="font-size:22px"><?= esc($fmt($revenuePending)); ?></div>
              <div class="ipb-kpi-label">Pending revenue</div>
              <div class="ipb-kpi-cta"><?= (int) ($revenue_pending_count ?? 0); ?> payments · Review <i class="fa fa-chevron-right"></i></div>
            </a>
          </div>
        </div>

        <div class="ipb-widget" data-widget-id="monthlyRevenue" data-size="half" data-title="Monthly revenue" data-icon="fa-solid fa-chart-column">
          <div class="ipb-card">
            <div class="ipb-card-head">
              <div>
                <div class="ipb-card-title">Last 6 months</div>
                <div class="ipb-card-sub">Successful Second Admin payments</div>
              </div>
              <a href="<?= route_to('route.Admin.revenue'); ?>" class="btn btn-default btn-sm">View all</a>
            </div>
            <div class="ipb-admin-month-list">
              <?php if (empty($revenueMonthly)): ?>
                <div class="text-muted" style="padding:12px 0">No monthly data yet.</div>
              <?php else: ?>
                <?php
                $maxMonth = max(array_map(static fn($r) => (float) ($r['amount'] ?? 0), $revenueMonthly) ?: [1]);
                foreach ($revenueMonthly as $row):
                  $amt = (float) ($row['amount'] ?? 0);
                  $pct = $maxMonth > 0 ? max(3, ($amt / $maxMonth) * 100) : 0;
                ?>
                  <div class="ipb-admin-month-row">
                    <div class="ipb-admin-month-top">
                      <strong><?= esc($row['label'] ?? ''); ?></strong>
                      <span><?= esc($fmt($amt)); ?></span>
                    </div>
                    <div class="ipb-admin-month-bar"><span style="width:<?= number_format($pct, 1); ?>%"></span></div>
                    <div class="ipb-admin-month-meta"><?= (int) ($row['count'] ?? 0); ?> payments</div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div class="ipb-widget" data-widget-id="packageUsers" data-size="half" data-title="Admins by package" data-icon="fa-solid fa-box-open">
          <div class="ipb-card">
            <div class="ipb-card-head">
              <div>
                <div class="ipb-card-title">Admins by package</div>
                <div class="ipb-card-sub">How many Second Admins use each package</div>
              </div>
              <a href="<?= route_to('Admin.packages'); ?>" class="btn btn-default btn-sm">Packages</a>
            </div>
            <div class="ipb-admin-pkg-list">
              <?php if (empty($packageUsers)): ?>
                <div class="text-muted" style="padding:12px 0">No package assignments yet.</div>
              <?php else: ?>
                <?php foreach ($packageUsers as $pkg): ?>
                  <div class="ipb-admin-pkg-row">
                    <span><i class="fa fa-box" aria-hidden="true"></i> <?= esc($pkg['package_name'] ?? 'No package'); ?></span>
                    <strong><?= (int) ($pkg['user_count'] ?? 0); ?> admins</strong>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div class="ipb-widget is-hidden" data-widget-id="insights" data-size="full" data-title="Platform overview" data-icon="fa-solid fa-bolt" data-default-hidden="1">
          <div class="ipb-insights">
            <div class="ipb-insights-head">
              <span class="ipb-insights-mark"><i class="fa-solid fa-bolt"></i></span>
              <span class="ipb-insights-title">Platform overview</span>
              <span class="ipb-badge ipb-badge-brand">Admin</span>
            </div>
            <div class="ipb-insights-row">
              <span class="ipb-insights-ic success"><i class="fa-solid fa-user-check"></i></span>
              <span><strong><?= (int) $users_active; ?></strong> active admins managing ISP tenants on the platform.</span>
            </div>
            <div class="ipb-insights-row">
              <span class="ipb-insights-ic warning"><i class="fa-solid fa-user-slash"></i></span>
              <span><strong><?= (int) $users_inactive; ?></strong> inactive admin accounts may need review.</span>
            </div>
            <div class="ipb-insights-row">
              <span class="ipb-insights-ic info"><i class="fa-solid fa-box-open"></i></span>
              <span><strong><?= (int) $total_packages; ?></strong> active admin packages available for subscription.</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
<?= $this->endSection('content'); ?>
