<?php
/**
 * Super Admin dashboard — JSX design system layout.
 * Backend variables and live-update scripts preserved.
 *
 * @var int $users_active
 * @var int $users_new
 * @var int $users_inactive
 * @var int $expired_inactive
 * @var int|float $customers_payment_total
 * @var int|float $customers_Expayment_total
 * @var int|float $customers_Expayment_count
 * @var int|float $customers_payment_received
 * @var int|float $customers_payment_received_count
 * @var int|float $customers_payment_pending
 * @var int $total_packages
 * @var int $total_area
 * @var int $employee_active
 * @var int $employee_inactive
 * @var int|float $employee_payment_received
 * @var int|float $employees_payment_pending
 * @var int $router_active
 * @var int $router_inactive
 * @var int $all_resellers
 * @var array $routers
 * @var array $package_distribution
 * @var array $payment_methods
 * @var array $geo_revenue
 * @var array $customer_payment_statistics
 * @var array $employee_payment_statistics
 * @var array $weekly_collections
 * @var array $revenue_overview
 * @var array $growth_churn
 * @var int|float $ticket_solving_rate
 * @var int|float $efficiency_rate
 * @var int|float $retention_rate
 * @var int|float $weekly_growth
 * @var int|float $total_data_gb
 * @var array $ticket_stats
 */
$ticketOpen = (int) ($ticket_stats['open'] ?? 0);
$ticketOngoing = (int) ($ticket_stats['ongoing'] ?? 0);
$ticketSolved = (int) ($ticket_stats['solved'] ?? 0);
$ticketClosed = (int) ($ticket_stats['closed'] ?? 0);
$ticketTotal = max(1, $ticketOpen + $ticketOngoing + $ticketSolved + $ticketClosed);
$ticketSolvedPct = (int) round((($ticket_stats['solved'] ?? 0) / $ticketTotal) * 100);
?>
<?= $this->extend('layout/main-layout'); ?>
<?php $this->section('needsApexCharts'); ?>1<?php $this->endSection(); ?>

<?= $this->section('css'); ?>
<?= saas_css('dashboard.css') ?>
<?= $this->endSection('css'); ?>

<?= $this->section('content'); ?>
<div class="content-wrapper">
  <section class="content">

    <?= $this->include('components/page-header', [
      'title' => 'Dashboard',
      'breadcrumb' => [
        ['label' => 'Home', 'url' => route_to('route.dashboard')],
        ['label' => 'Dashboard'],
      ],
    ]); ?>

    <?= $this->include('components/trial-banner', ['trialUser' => $trialUser ?? null]); ?>

    <div class="ipb-dash fade-in" data-ipb-dashboard="admin">

      <div class="ipb-dash-toolbar">
        <button type="button" class="ipb-btn-outline" data-ipb-metrics-group-toggle aria-pressed="false" title="Group all metric sections together">
          <i class="fa-solid fa-layer-group" aria-hidden="true"></i> <span class="ipb-metrics-group-btn-label">Group Metrics</span>
        </button>
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

      <!-- KPI row (JSX primary metrics) -->
      <div class="ipb-widget" data-widget-id="kpi" data-size="full" data-title="Key metrics" data-icon="fa-solid fa-table-cells">
      <div class="ipb-dash-kpi">
        <a href="<?= route_to('route.customer') . '?status=active'; ?>" class="ipb-kpi tone-brand">
          <div class="ipb-kpi-top">
            <span class="ipb-kpi-icon"><i class="fa-solid fa-users"></i></span>
          </div>
          <div class="ipb-kpi-value" id="card_users_active" data-target="<?= (int) $users_active; ?>">0</div>
          <div class="ipb-kpi-label">Active Customers</div>
          <div class="ipb-kpi-cta">View details <i class="fa fa-chevron-right"></i></div>
        </a>

        <a href="<?= route_to('route.customer.payment') . '?status=successful'; ?>" class="ipb-kpi tone-success">
          <div class="ipb-kpi-top">
            <span class="ipb-kpi-icon"><i class="fa-solid fa-wallet"></i></span>
          </div>
          <div class="ipb-kpi-value" id="card_customers_payment_received" data-target="<?= (float) $customers_payment_received; ?>" data-count="<?= (int) $customers_payment_received_count; ?>">0 (0)</div>
          <div class="ipb-kpi-label">Payment Received</div>
          <div class="ipb-kpi-cta">View details <i class="fa fa-chevron-right"></i></div>
        </a>

        <a href="<?= route_to('route.customer') . '?status=due'; ?>" class="ipb-kpi tone-warning">
          <div class="ipb-kpi-top">
            <span class="ipb-kpi-icon"><i class="fa-solid fa-clock"></i></span>
          </div>
          <div class="ipb-kpi-value" id="card_customers_Expayment_total" data-target="<?= (float) $customers_Expayment_total; ?>" data-count="<?= (int) $customers_Expayment_count; ?>">0 (0)</div>
          <div class="ipb-kpi-label">Payment Due</div>
          <div class="ipb-kpi-cta">View details <i class="fa fa-chevron-right"></i></div>
        </a>

        <a href="<?= route_to('route.ticket'); ?>" class="ipb-kpi tone-error">
          <div class="ipb-kpi-top">
            <span class="ipb-kpi-icon"><i class="fa-solid fa-life-ring"></i></span>
          </div>
          <div class="ipb-kpi-value" id="ticket_open_kpi"><?= $ticketOpen; ?></div>
          <div class="ipb-kpi-label">Open Tickets</div>
          <div class="ipb-kpi-cta">View details <i class="fa fa-chevron-right"></i></div>
        </a>

        <?php
        $quota = $customer_quota ?? ['used' => 0, 'limit' => 0, 'is_unlimited' => true, 'percent' => 0];
        $quotaUsed = (int) ($quota['used'] ?? 0);
        $quotaLimit = (int) ($quota['limit'] ?? 0);
        $quotaUnlimited = !empty($quota['is_unlimited']);
        $quotaPct = (float) ($quota['percent'] ?? 0);
        ?>
        <a href="<?= route_to('route.customer'); ?>" class="ipb-kpi tone-brand"
          data-bs-toggle="tooltip"
          title="Customer limit comes from your active package. A pending package change does not affect this until paid.">
          <div class="ipb-kpi-top">
            <span class="ipb-kpi-icon"><i class="fa-solid fa-user-plus"></i></span>
          </div>
          <div class="ipb-kpi-value" id="card_customer_quota">
            <?= $quotaUnlimited ? esc($quotaUsed) . ' / ∞' : esc($quotaUsed) . ' / ' . esc($quotaLimit); ?>
          </div>
          <div class="ipb-kpi-label">Customer quota</div>
          <?php if (!$quotaUnlimited && $quotaLimit > 0): ?>
            <div class="progress" style="height:5px;margin-top:8px">
              <div class="progress-bar bg-primary" id="card_customer_quota_bar" style="width:<?= min(100, $quotaPct); ?>%"></div>
            </div>
          <?php endif; ?>
          <div class="ipb-kpi-cta">Manage customers <i class="fa fa-chevron-right"></i></div>
        </a>
      </div>
      </div>

      <!-- Insights panel (same metrics as cards; updated via sadmin-data AJAX) -->
      <div class="ipb-widget" data-widget-id="insights" data-size="full" data-title="AI Insights" data-icon="fa-solid fa-bolt">
      <div class="ipb-insights">
        <div class="ipb-insights-head">
          <span class="ipb-insights-mark"><i class="fa-solid fa-bolt"></i></span>
          <span class="ipb-insights-title">AI Insights</span>
          <span class="ipb-badge ipb-badge-brand">Live metrics</span>
        </div>
        <div class="ipb-insights-row">
          <span class="ipb-insights-ic success"><i class="fa-solid fa-arrow-trend-up"></i></span>
          <span>Payment received: <strong>৳<span id="insight_payment_received"><?= number_format((float) $customers_payment_received); ?></span></strong> across <span id="insight_payment_received_count"><?= (int) $customers_payment_received_count; ?></span> transactions.</span>
        </div>
        <div class="ipb-insights-row">
          <span class="ipb-insights-ic warning"><i class="fa-solid fa-triangle-exclamation"></i></span>
          <span><strong id="insight_expired_inactive"><?= (int) $expired_inactive; ?></strong> customers expired — consider an auto-reminder SMS to reduce churn risk.</span>
        </div>
        <div class="ipb-insights-row">
          <span class="ipb-insights-ic info"><i class="fa-solid fa-wifi"></i></span>
          <span><strong id="insight_router_active"><?= (int) $router_active; ?></strong> routers active · <strong id="insight_all_resellers"><?= (int) $all_resellers; ?></strong> POP resellers on the platform.</span>
        </div>
      </div>
      </div>

      <!-- Customer metrics -->
      <!-- Customer metrics also hosts the grouped-mode merged grid (below), so
           that merged view inherits THIS widget's position under customize.js's
           reordering instead of sitting in a fixed DOM slot that customize.js
           never touches — a fixed slot rose to the top of the page because
           applyWidgets() re-appends every OTHER tracked widget to the end of the
           grid on load/reorder while never moving this untracked node. -->
      <div class="ipb-widget ipb-metrics-section ipb-metrics-host" data-widget-id="customerMetrics" data-size="full" data-title="Customer Metrics" data-icon="fa-solid fa-users">
      <div>
        <div class="ipb-section-label">Customer metrics</div>
        <div class="ipb-dash-mini">
          <a href="<?= route_to('route.new_customer'); ?>" class="ipb-kpi tone-success compact">
            <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-user-plus"></i></span></div>
            <div class="ipb-kpi-value" id="card_users_new" data-target="<?= (int) $users_new; ?>">0</div>
            <div class="ipb-kpi-label">New Customers</div>
            <div class="ipb-kpi-cta">View details <i class="fa fa-chevron-right"></i></div>
          </a>
          <a href="<?= route_to('route.inactive_index'); ?>" class="ipb-kpi tone-error compact">
            <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-user-slash"></i></span></div>
            <div class="ipb-kpi-value" id="card_users_inactive" data-target="<?= (int) $users_inactive; ?>">0</div>
            <div class="ipb-kpi-label">Inactive Customers</div>
            <div class="ipb-kpi-cta">View details <i class="fa fa-chevron-right"></i></div>
          </a>
          <a href="<?= route_to('route.expired_customer'); ?>" class="ipb-kpi tone-warning compact">
            <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-user-clock"></i></span></div>
            <div class="ipb-kpi-value" id="card_expired_inactive" data-target="<?= (int) $expired_inactive; ?>">0</div>
            <div class="ipb-kpi-label">Expired Customers</div>
            <div class="ipb-kpi-cta">View details <i class="fa fa-chevron-right"></i></div>
          </a>
          <a href="<?= route_to('route.customer.payment'); ?>" class="ipb-kpi tone-navy compact">
            <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-sack-dollar"></i></span></div>
            <div class="ipb-kpi-value" id="card_customers_payment_total" data-target="<?= (float) $customers_payment_total; ?>">0</div>
            <div class="ipb-kpi-label">Customer Payment Total</div>
            <div class="ipb-kpi-cta">View payments <i class="fa fa-chevron-right"></i></div>
          </a>
          <a href="<?= route_to('route.customer.payment') . '?status=pending'; ?>" class="ipb-kpi tone-warning compact">
            <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-hourglass-half"></i></span></div>
            <div class="ipb-kpi-value" id="card_customers_payment_pending" data-target="<?= (float) $customers_payment_pending; ?>">0</div>
            <div class="ipb-kpi-label">Customer Payment Pending</div>
            <div class="ipb-kpi-cta">View pending <i class="fa fa-chevron-right"></i></div>
          </a>
        </div>
      </div>

      <!-- Grouped-mode host: when "Group Metrics" is active, cards from all four
           sections are physically moved here (see script below) so they flow as
           one continuous grid — four separate grids can never share a row, so
           hiding headers/margins alone still left visible seams at each section
           boundary. Lives inside this widget (not a separate DOM slot) so it
           inherits Customer Metrics' own tracked position. -->
      <div class="ipb-dash-mini ipb-metrics-merged" data-ipb-metrics-merged hidden aria-hidden="true"></div>
      </div>

      <!-- Package & service metrics -->
      <div class="ipb-widget ipb-metrics-section" data-widget-id="packageServiceMetrics" data-size="full" data-title="Package & Service Metrics" data-icon="fa-solid fa-box-open">
      <div>
        <div class="ipb-section-label">Package &amp; service metrics</div>
        <div class="ipb-dash-mini">
          <a href="<?= route_to('route.packages'); ?>" class="ipb-kpi tone-info compact">
            <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-box-open"></i></span></div>
            <div class="ipb-kpi-value" id="card_total_packages" data-target="<?= (int) $total_packages; ?>">0</div>
            <div class="ipb-kpi-label">Active Packages</div>
            <div class="ipb-kpi-cta">View packages <i class="fa fa-chevron-right"></i></div>
          </a>
          <a href="<?= route_to('route.area'); ?>" class="ipb-kpi tone-brand compact">
            <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-map-location-dot"></i></span></div>
            <div class="ipb-kpi-value" id="card_total_area" data-target="<?= (int) $total_area; ?>">0</div>
            <div class="ipb-kpi-label">Active Service Areas</div>
            <div class="ipb-kpi-cta">View areas <i class="fa fa-chevron-right"></i></div>
          </a>
        </div>
      </div>
      </div>

      <!-- Employee metrics -->
      <div class="ipb-widget ipb-metrics-section" data-widget-id="employeeMetrics" data-size="full" data-title="Employee Metrics" data-icon="fa-solid fa-user-tie">
      <div>
        <div class="ipb-section-label">Employee metrics</div>
        <div class="ipb-dash-mini">
          <a href="<?= route_to('route.employee'); ?>" class="ipb-kpi tone-success compact">
            <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-user-tie"></i></span></div>
            <div class="ipb-kpi-value" id="card_employee_active" data-target="<?= (int) $employee_active; ?>">0</div>
            <div class="ipb-kpi-label">Active Employees</div>
            <div class="ipb-kpi-cta">View employees <i class="fa fa-chevron-right"></i></div>
          </a>
          <a href="<?= route_to('route.employee'); ?>" class="ipb-kpi tone-error compact">
            <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-user-xmark"></i></span></div>
            <div class="ipb-kpi-value" id="card_employee_inactive" data-target="<?= (int) $employee_inactive; ?>">0</div>
            <div class="ipb-kpi-label">Inactive Employees</div>
            <div class="ipb-kpi-cta">View employees <i class="fa fa-chevron-right"></i></div>
          </a>
          <a href="<?= route_to('route.employee.payment'); ?>" class="ipb-kpi tone-navy compact">
            <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-money-check-dollar"></i></span></div>
            <div class="ipb-kpi-value" id="card_employee_payment_received" data-target="<?= (float) $employee_payment_received; ?>">0</div>
            <div class="ipb-kpi-label">Employee Payment Received</div>
            <div class="ipb-kpi-cta">View payments <i class="fa fa-chevron-right"></i></div>
          </a>
          <a href="<?= route_to('route.employee.payment') . '?status=pending'; ?>" class="ipb-kpi tone-warning compact">
            <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-money-bill-wave"></i></span></div>
            <div class="ipb-kpi-value" id="card_employees_payment_pending" data-target="<?= (float) $employees_payment_pending; ?>">0</div>
            <div class="ipb-kpi-label">Employee Payment Pending</div>
            <div class="ipb-kpi-cta">View pending <i class="fa fa-chevron-right"></i></div>
          </a>
        </div>
      </div>
      </div>

      <!-- Network & router metrics -->
      <div class="ipb-widget ipb-metrics-section" data-widget-id="networkRouterMetrics" data-size="full" data-title="Network & Router Metrics" data-icon="fa-solid fa-server">
      <div>
        <div class="ipb-section-label">Network &amp; router metrics</div>
        <div class="ipb-dash-mini">
          <a href="<?= route_to('route.routers'); ?>" class="ipb-kpi tone-brand compact">
            <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-server"></i></span></div>
            <div class="ipb-kpi-value" id="card_router_active" data-target="<?= (int) $router_active; ?>">0</div>
            <div class="ipb-kpi-label">Active Routers</div>
            <div class="ipb-kpi-cta">View routers <i class="fa fa-chevron-right"></i></div>
          </a>
          <a href="<?= route_to('route.routers'); ?>" class="ipb-kpi tone-error compact">
            <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-plug-circle-xmark"></i></span></div>
            <div class="ipb-kpi-value" id="card_router_inactive" data-target="<?= (int) $router_inactive; ?>">0</div>
            <div class="ipb-kpi-label">Inactive Routers</div>
            <div class="ipb-kpi-cta">View routers <i class="fa fa-chevron-right"></i></div>
          </a>
          <a href="<?= route_to('route.reseller'); ?>" class="ipb-kpi tone-navy compact">
            <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-building"></i></span></div>
            <div class="ipb-kpi-value" id="card_all_resellers" data-target="<?= (int) $all_resellers; ?>">0</div>
            <div class="ipb-kpi-label">POP Resellers</div>
            <div class="ipb-kpi-cta">View resellers <i class="fa fa-chevron-right"></i></div>
          </a>
        </div>
      </div>
      </div>

      <!-- Router / POP live sessions -->
      <div class="ipb-widget" data-widget-id="pop" data-size="full" data-title="POP Live Status" data-icon="fa-solid fa-wifi">
      <?php if (!empty($routers)): ?>
        <?php foreach ($routers as $router): ?>
          <?php
            $statusClass = 'online';
            $statusText = 'Online';
            if (isset($router->cached_status) && $router->cached_status === 'error') {
              $statusClass = 'error';
              $statusText = 'Connection Failed';
            }
            $lastUpdated = !empty($router->cached_last_updated) ? 'Updated: ' . $router->cached_last_updated : 'Live PPPoE session summary';
          ?>
          <div class="ipb-card ipb-router-card">
            <div class="ipb-card-head">
              <div>
                <div class="ipb-card-title"><?= esc($router->name ?? 'Router'); ?></div>
                <div class="ipb-card-sub" id="last_updated_<?= (int) $router->id; ?>"><?= esc($lastUpdated); ?></div>
              </div>
              <div class="ipb-status-pill <?= $statusClass; ?>" id="status_<?= (int) $router->id; ?>">
                <span class="status-dot <?= $statusClass; ?>"></span>
                <span class="status-label"><?= esc($statusText); ?></span>
              </div>
            </div>
            <div class="ipb-dash-mini">
              <div class="ipb-kpi tone-brand compact">
                <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-users"></i></span></div>
                <div class="ipb-kpi-value" id="total_user_count_<?= (int) $router->id; ?>" data-cached="<?= (int) ($router->cached_total ?? 0); ?>">0</div>
                <div class="ipb-kpi-label">Total Users</div>
                <a href="#" class="ipb-kpi-cta view-all-users" data-router-id="<?= (int) $router->id; ?>">View details <i class="fa fa-chevron-right"></i></a>
              </div>
              <div class="ipb-kpi tone-success compact">
                <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-wifi"></i></span></div>
                <div class="ipb-kpi-value" id="active_user_count_<?= (int) $router->id; ?>" data-cached="<?= (int) ($router->cached_active ?? 0); ?>">0</div>
                <div class="ipb-kpi-label">Users Online</div>
                <a href="#" class="ipb-kpi-cta view-active-users" data-router-id="<?= (int) $router->id; ?>">View details <i class="fa fa-chevron-right"></i></a>
              </div>
              <div class="ipb-kpi tone-error compact">
                <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-circle-xmark"></i></span></div>
                <div class="ipb-kpi-value" id="inactive_user_count_<?= (int) $router->id; ?>" data-cached="<?= (int) (($router->cached_total ?? 0) - ($router->cached_active ?? 0)); ?>">0</div>
                <div class="ipb-kpi-label">Users Offline</div>
                <a href="#" class="ipb-kpi-cta view-inactive-users" data-router-id="<?= (int) $router->id; ?>">View details <i class="fa fa-chevron-right"></i></a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="ipb-card">
          <div class="ipb-card-head">
            <div>
              <div class="ipb-card-title">POP Live Status</div>
              <div class="ipb-card-sub">No routers configured yet</div>
            </div>
          </div>
        </div>
      <?php endif; ?>
      </div>

      <div class="ipb-widget" data-widget-id="paymentReport" data-size="twoThird" data-title="Customer Payment Report" data-icon="fa-solid fa-receipt">
          <div class="ipb-card">
            <div class="ipb-card-head">
              <div>
                <div class="ipb-card-title">Customer Payment Report</div>
                <div class="ipb-card-sub">Jan – <?= date('M Y'); ?></div>
              </div>
            </div>
            <div id="customerPaymentReportChart"></div>
          </div>
      </div>

      <div class="ipb-widget" data-widget-id="packageDist" data-size="third" data-title="Package Distribution" data-icon="fa-solid fa-box-open">
          <div class="ipb-card">
            <div class="ipb-card-head">
              <div>
                <div class="ipb-card-title">Package Distribution</div>
                <div class="ipb-card-sub" id="efficiencyRateBadge">Efficiency: <?= (float) ($efficiency_rate ?? 0); ?>%</div>
              </div>
            </div>
            <div id="packageDistributionChart"></div>
            <div id="packageDistributionList" class="ipb-list-grid ipb-skel-swap">
              <div class="ipb-skeleton ipb-skeleton-list">
                <?php for ($i = 0; $i < 4; $i++): ?>
                  <div class="ipb-skeleton-list-row">
                    <span class="ipb-skeleton ipb-skeleton-text is-sm" style="width:55%"></span>
                    <span class="ipb-skeleton ipb-skeleton-text is-sm" style="width:12%"></span>
                  </div>
                <?php endfor; ?>
              </div>
            </div>
          </div>
      </div>

      <div class="ipb-widget" data-widget-id="weeklyRevenue" data-size="third" data-title="Weekly Revenue" data-icon="fa-solid fa-wallet">
          <div class="ipb-card">
            <div class="ipb-card-head">
              <div>
                <div class="ipb-card-title">Weekly Revenue</div>
                <div class="ipb-card-sub">This week</div>
              </div>
              <div id="weeklyGrowthBadge" class="ipb-growth <?= (($weekly_growth ?? 0) >= 0 ? 'up' : 'down'); ?>">
                <i class="fa fa-arrow-<?= (($weekly_growth ?? 0) >= 0 ? 'up' : 'down'); ?>"></i>
                <?= (($weekly_growth ?? 0) >= 0 ? '+' : '') . ($weekly_growth ?? 0); ?>%
              </div>
            </div>
            <div id="weeklyCollectionChart"></div>
          </div>
      </div>

      <div class="ipb-widget" data-widget-id="paymentMethod" data-size="third" data-title="Payment Method Mix" data-icon="fa-solid fa-credit-card">
          <div class="ipb-card">
            <div class="ipb-card-head">
              <div>
                <div class="ipb-card-title">Payment Method Mix</div>
              </div>
            </div>
            <div id="paymentMethodChart"></div>
            <div id="paymentMethodList" class="ipb-list-grid ipb-skel-swap">
              <div class="ipb-skeleton ipb-skeleton-list">
                <?php for ($i = 0; $i < 3; $i++): ?>
                  <div class="ipb-skeleton-list-row">
                    <span class="ipb-skeleton ipb-skeleton-text is-sm" style="width:40%"></span>
                    <span class="ipb-skeleton ipb-skeleton-text is-sm" style="width:12%"></span>
                  </div>
                <?php endfor; ?>
              </div>
            </div>
          </div>
      </div>

      <div class="ipb-widget" data-widget-id="collectionSupport" data-size="third" data-title="Collection & Support" data-icon="fa-solid fa-life-ring">
          <div class="ipb-card">
            <div class="ipb-card-head">
              <div>
                <div class="ipb-card-title">Collection &amp; Support</div>
              </div>
            </div>
            <div class="ipb-support-wrap">
              <!-- The radial chart below was rendered with display:none while a 90px CSS
                   conic-gradient ring stood in for it — two sources of truth for one number,
                   and the visible one was a third the size of every other chart on the page.
                   The chart is the chart now; it reads like the Payment Method Mix donut. -->
              <div id="collectionRateRadialChart" class="ipb-support-chart"></div>
              <div class="ipb-support-legend">
                <div class="ipb-legend-row">
                  <span class="ipb-legend-dot" style="--dot: var(--warning-500)"></span>
                  <span class="ipb-legend-name">Open</span>
                  <span class="ipb-legend-val" id="ticket_open"><?= $ticketOpen; ?></span>
                </div>
                <div class="ipb-legend-row">
                  <span class="ipb-legend-dot" style="--dot: var(--info-500)"></span>
                  <span class="ipb-legend-name">Ongoing</span>
                  <span class="ipb-legend-val" id="ticket_ongoing"><?= $ticketOngoing; ?></span>
                </div>
                <div class="ipb-legend-row">
                  <span class="ipb-legend-dot" style="--dot: var(--success-500)"></span>
                  <span class="ipb-legend-name">Solved</span>
                  <span class="ipb-legend-val" id="ticket_solved"><?= $ticketSolved; ?></span>
                </div>
                <div class="ipb-legend-row">
                  <span class="ipb-legend-dot" style="--dot: var(--neutral-400)"></span>
                  <span class="ipb-legend-name">Closed</span>
                  <span class="ipb-legend-val" id="ticket_closed"><?= $ticketClosed; ?></span>
                </div>
              </div>
            </div>
          </div>
      </div>

      <div class="ipb-widget" data-widget-id="growth" data-size="half" data-title="Consolidated Growth" data-icon="fa-solid fa-chart-line">
          <div class="ipb-card">
            <div class="ipb-card-head">
              <div>
                <div class="ipb-card-title">Consolidated Growth</div>
                <div class="ipb-card-sub">Revenue vs collection</div>
              </div>
            </div>
            <div id="revenueOverviewChart"></div>
          </div>
      </div>

      <div class="ipb-widget" data-widget-id="churn" data-size="half" data-title="Subscriber Health & Churn" data-icon="fa-solid fa-chart-column">
          <div class="ipb-card">
            <div class="ipb-card-head">
              <div>
                <div class="ipb-card-title">Subscriber Health &amp; Churn</div>
                <div class="ipb-card-sub" id="retentionRateBadge">Retention: <?= (float) ($retention_rate ?? 0); ?>%</div>
              </div>
            </div>
            <div id="growthChurnChart"></div>
          </div>
      </div>

      <div class="ipb-widget" data-widget-id="employeePayment" data-size="half" data-title="Employees Payment Report" data-icon="fa-solid fa-user-tie">
          <div class="ipb-card">
            <div class="ipb-card-head">
              <div>
                <div class="ipb-card-title">Employees Payment Report</div>
                <div class="ipb-card-sub">Jan – <?= date('M Y'); ?></div>
              </div>
            </div>
            <div id="employeePaymentReportChart"></div>
          </div>
      </div>

      <div class="ipb-widget" data-widget-id="bandwidth" data-size="half" data-title="Daily Data Consumption" data-icon="fa-solid fa-gauge-high">
          <div class="ipb-card">
            <div class="ipb-card-head">
              <div>
                <div class="ipb-card-title">Daily Data Consumption</div>
                <div class="ipb-card-sub" id="liveThroughput"><span class="text-mut">Live: --</span></div>
              </div>
              <div class="ipb-growth warn">
                Total: <span id="totalDataGB"><?= number_format((float) ($total_data_gb ?? 0), 1); ?></span> GB
              </div>
            </div>
            <div class="ipb-router-select">
              <select id="routerSelect" class="form-control">
                <option value="all">All Routers</option>
                <?php foreach ($routers as $router): ?>
                  <option value="<?= (int) $router->id; ?>"><?= esc($router->name); ?> (<?= esc($router->host); ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>
            <div id="bandwidthUsageChart"></div>
          </div>
      </div>

      <div class="ipb-widget" data-widget-id="geoRevenue" data-size="full" data-title="Geo Revenue" data-icon="fa-solid fa-map-location-dot">
          <div class="ipb-card ipb-geo-card">
            <div class="ipb-card-head ipb-geo-head">
              <div>
                <div class="ipb-card-title">Geo Revenue</div>
                <div class="ipb-card-sub">Successful payments by service area</div>
              </div>
              <div class="ipb-geo-summary" id="geoRevenueSummary" aria-live="polite">
                <div class="ipb-geo-stat">
                  <span class="ipb-geo-stat-label">Total</span>
                  <strong id="geoTotalRevenue">—</strong>
                </div>
                <div class="ipb-geo-stat">
                  <span class="ipb-geo-stat-label">Areas</span>
                  <strong id="geoAreaCount">—</strong>
                </div>
                <div class="ipb-geo-stat">
                  <span class="ipb-geo-stat-label">Customers</span>
                  <strong id="geoPersonCount">—</strong>
                </div>
              </div>
            </div>

            <div class="ipb-geo-toolbar">
              <label class="ipb-geo-search" for="geoRevenueSearch">
                <i class="fa fa-search" aria-hidden="true"></i>
                <input type="search" id="geoRevenueSearch" class="form-control ipb-geo-search-input" placeholder="Search area by name…" autocomplete="off" disabled>
              </label>
              <span class="ipb-geo-hint" id="geoRevenueHint">Loading areas…</span>
            </div>

            <div id="geoRevenueList" class="ipb-geo-list ipb-skel-swap" role="list" aria-label="Revenue by service area">
              <div class="ipb-skeleton ipb-skeleton-list">
                <?php for ($i = 0; $i < 4; $i++): ?>
                  <div class="ipb-skeleton-list-row">
                    <span class="ipb-skeleton ipb-skeleton-text is-sm" style="width:45%"></span>
                    <span class="ipb-skeleton ipb-skeleton-text is-sm" style="width:16%"></span>
                  </div>
                <?php endfor; ?>
              </div>
            </div>
          </div>
      </div>

      </div><!-- /data-ipb-dash-grid -->

    </div>
  </section>
</div>
<?= $this->endSection('content'); ?>

<?= $this->section('script'); ?>
<script>
  // Group/separate toggle for the metric sections (Customer/Package/Employee/Network) —
  // shared state across all four section headers, persisted so it survives reloads.
  //
  // "Grouped" doesn't just hide headers — four separate .ipb-dash-mini grids can
  // never share a row (a partial last row in one can't be filled by the next),
  // so that alone still left visible seams. Instead the actual .ipb-kpi cards are
  // physically moved into one shared grid (.ipb-metrics-merged) so they flow as a
  // single continuous list, then moved back to their original section on toggle-off.
  (function () {
    var STORAGE_KEY = 'ipbMetricsGrouped';
    var grid = document.querySelector('[data-ipb-dash-grid]');
    var merged = document.querySelector('[data-ipb-metrics-merged]');
    if (!grid) return;

    var minis = merged ? Array.prototype.slice.call(document.querySelectorAll('.ipb-metrics-section .ipb-dash-mini:not(.ipb-metrics-merged)')) : [];
    minis.forEach(function (mini, i) {
      if (!mini.id) mini.id = 'ipbMetricsMini' + i;
    });

    function mergeCards() {
      if (!merged) return;
      minis.forEach(function (mini) {
        Array.prototype.slice.call(mini.children).forEach(function (card) {
          card.setAttribute('data-origin-mini', mini.id);
          merged.appendChild(card);
        });
      });
    }

    function restoreCards() {
      if (!merged) return;
      Array.prototype.slice.call(merged.children).forEach(function (card) {
        var origin = document.getElementById(card.getAttribute('data-origin-mini') || '');
        if (origin) origin.appendChild(card);
      });
    }

    function applyState(grouped) {
      grid.classList.toggle('is-metrics-grouped', grouped);
      if (grouped) mergeCards(); else restoreCards();
      if (merged) merged.hidden = !grouped;
      document.querySelectorAll('[data-ipb-metrics-group-toggle]').forEach(function (btn) {
        btn.setAttribute('aria-pressed', grouped ? 'true' : 'false');
        btn.classList.toggle('is-active', grouped);
        var label = btn.querySelector('.ipb-metrics-group-btn-label');
        if (label) label.textContent = grouped ? 'Separate Metrics' : 'Group Metrics';
        btn.title = grouped ? 'Show metric sections separately' : 'Group all metric sections together';
      });
    }

    var stored = null;
    try { stored = window.localStorage.getItem(STORAGE_KEY); } catch (e) {}
    applyState(stored === '1');

    document.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-ipb-metrics-group-toggle]');
      if (!btn) return;
      var grouped = !grid.classList.contains('is-metrics-grouped');
      applyState(grouped);
      try { window.localStorage.setItem(STORAGE_KEY, grouped ? '1' : '0'); } catch (e) {}
    });
  })();

  // Dark Mode Toggle

  // Reusable Counter Animation function
  function animateCounter(counter, startVal = null) {
    const target = +counter.getAttribute("data-target"); // main total
    const countTarget = counter.getAttribute("data-count"); // may be null
    const countNum = countTarget !== null ? +countTarget : null;

    let start = 0;
    let startCount = 0;

    if (startVal !== null) {
      start = startVal;
    } else {
      // Parse current values from the element's existing text (e.g. "61100 (117)")
      const txt = counter.innerText || "";
      const matches = txt.match(/([\d\.]+)\s*(?:\(([\d\.]+)\))?/);
      if (matches) {
        start = parseFloat(matches[1]) || 0;
        startCount = matches[2] ? (parseFloat(matches[2]) || 0) : 0;
      }
    }

    let current = start;
    let currentCount = startCount;

    if (target === current && (countNum === null || countNum === currentCount)) {
      // If values haven't changed, don't animate to avoid visual flicker/jitter
      return;
    }

    if (target === 0 && (countNum === null || countNum === 0)) {
      if (countNum !== null) {
        counter.innerText = "0 (0)";
      } else {
        counter.innerText = "0";
      }
      return;
    }

    const step = 80;
    const increment = (target - start) / step;
    const countIncrement = countNum !== null ? (countNum - startCount) / step : 0;

    let iterations = 0;

    const updateCounter = () => {
      iterations++;
      if (iterations <= step) {
        current += increment;
        if (countNum !== null) currentCount += countIncrement;

        if (countNum !== null) {
          counter.innerText =
            `${Math.round(current)} (${Math.round(currentCount)})`;
        } else {
          counter.innerText = `${Math.round(current)}`;
        }

        setTimeout(updateCounter, 30);
      } else {
        if (countNum !== null) {
          counter.innerText = `${target} (${countNum})`;
        } else {
          counter.innerText = `${target}`;
        }
      }
    };

    updateCounter();
  }

  // Global ApexCharts instances
  let customerPaymentReportChart, employeePaymentReportChart, weeklyCollectionChart, packageDistributionChart, paymentMethodChart, revenueOverviewChart, growthChurnChart, collectionRateRadialChart;

  let geoRevenueData = [];

  function formatTaka(amount) {
    const n = Number(amount) || 0;
    return '৳' + n.toLocaleString('en-US', { maximumFractionDigits: 0 });
  }

  function escapeHtml(str) {
    return String(str == null ? '' : str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  // Skeleton -> content crossfade (components.css §05.8 contract): the real
  // content lives in a .ipb-real sibling of the skeleton, .is-ready fades the
  // skeleton out and the real content in, then the skeleton node is removed
  // once its fade-out transition finishes so it stops occupying layout space.
  function ensureSkeletonRealContainer(wrapperEl) {
    var real = wrapperEl.querySelector(':scope > .ipb-real');
    if (!real) {
      real = document.createElement('div');
      real.className = 'ipb-real';
      wrapperEl.appendChild(real);
    }
    return real;
  }

  function revealFromSkeleton(wrapperEl) {
    if (!wrapperEl || !wrapperEl.classList.contains('ipb-skel-swap') || wrapperEl.classList.contains('is-ready')) return;
    wrapperEl.classList.add('is-ready');
    setTimeout(function () {
      var sk = wrapperEl.querySelector(':scope > .ipb-skeleton');
      if (sk) sk.remove();
    }, 220);
  }

  function swapSkeletonHtml(elId, html) {
    var wrapperEl = document.getElementById(elId);
    if (!wrapperEl) return;
    ensureSkeletonRealContainer(wrapperEl).innerHTML = html;
    revealFromSkeleton(wrapperEl);
  }

  function renderGeoRevenue(rows) {
    geoRevenueData = Array.isArray(rows) ? rows.slice() : [];

    const listEl = document.getElementById('geoRevenueList');
    const searchEl = document.getElementById('geoRevenueSearch');
    const hintEl = document.getElementById('geoRevenueHint');
    const totalEl = document.getElementById('geoTotalRevenue');
    const areaCountEl = document.getElementById('geoAreaCount');
    const personCountEl = document.getElementById('geoPersonCount');

    if (!listEl) return;

    const totalRevenue = geoRevenueData.reduce(function (sum, area) {
      return sum + (parseFloat(area.revenue) || 0);
    }, 0);
    const totalPersons = geoRevenueData.reduce(function (sum, area) {
      return sum + (parseInt(area.persons, 10) || 0);
    }, 0);

    if (totalEl) totalEl.textContent = formatTaka(totalRevenue);
    if (areaCountEl) areaCountEl.textContent = String(geoRevenueData.length);
    if (personCountEl) personCountEl.textContent = totalPersons.toLocaleString('en-US');

    if (searchEl) {
      searchEl.disabled = geoRevenueData.length === 0;
      if (!searchEl.dataset.bound) {
        searchEl.dataset.bound = '1';
        searchEl.addEventListener('input', function () {
          paintGeoRevenueList(searchEl.value);
        });
      }
    }

    paintGeoRevenueList(searchEl ? searchEl.value : '');
    revealFromSkeleton(listEl);

    if (hintEl) {
      hintEl.textContent = geoRevenueData.length
        ? 'Ranked by revenue · share of total'
        : 'No successful payments by area yet';
    }
  }

  function paintGeoRevenueList(query) {
    const wrapperEl = document.getElementById('geoRevenueList');
    const hintEl = document.getElementById('geoRevenueHint');
    if (!wrapperEl) return;
    const listEl = ensureSkeletonRealContainer(wrapperEl);

    const q = String(query || '').trim().toLowerCase();
    const totalRevenue = geoRevenueData.reduce(function (sum, area) {
      return sum + (parseFloat(area.revenue) || 0);
    }, 0);
    const maxRev = Math.max.apply(null, geoRevenueData.map(function (area) {
      return parseFloat(area.revenue) || 0;
    }).concat([1]));

    const filtered = geoRevenueData.filter(function (area) {
      const name = String(area.area_name || 'Unassigned').toLowerCase();
      return !q || name.indexOf(q) !== -1;
    });

    if (!geoRevenueData.length) {
      listEl.innerHTML =
        '<div class="ipb-geo-empty">' +
          '<i class="fa fa-map-location-dot" aria-hidden="true"></i>' +
          '<p>No geo revenue yet</p>' +
          '<span>Successful payments will appear here by service area.</span>' +
        '</div>';
      return;
    }

    if (!filtered.length) {
      listEl.innerHTML =
        '<div class="ipb-geo-empty">' +
          '<i class="fa fa-search" aria-hidden="true"></i>' +
          '<p>No areas match “' + escapeHtml(query) + '”</p>' +
          '<span>Try another area name.</span>' +
        '</div>';
      if (hintEl) hintEl.textContent = '0 of ' + geoRevenueData.length + ' areas shown';
      return;
    }

    if (hintEl) {
      hintEl.textContent = (q ? filtered.length + ' of ' + geoRevenueData.length + ' areas' : 'Ranked by revenue · share of total');
    }

    listEl.innerHTML = filtered.map(function (area) {
      const rev = parseFloat(area.revenue) || 0;
      const persons = parseInt(area.persons, 10) || 0;
      const barPct = Math.max(2, (rev / maxRev) * 100);
      const sharePct = totalRevenue > 0 ? (rev / totalRevenue) * 100 : 0;
      const rank = geoRevenueData.indexOf(area) + 1;
      const name = escapeHtml(area.area_name || 'Unassigned');
      const tone = rank === 1 ? 'is-top' : (rank <= 3 ? 'is-high' : '');

      return (
        '<article class="ipb-geo-row ' + tone + '" role="listitem" title="' + name + '">' +
          '<div class="ipb-geo-rank" aria-label="Rank ' + rank + '">#' + rank + '</div>' +
          '<div class="ipb-geo-main">' +
            '<div class="ipb-geo-row-top">' +
              '<div class="ipb-geo-name">' +
                '<i class="fa fa-location-dot" aria-hidden="true"></i>' +
                '<span>' + name + '</span>' +
              '</div>' +
              '<div class="ipb-geo-amount">' + formatTaka(rev) + '</div>' +
            '</div>' +
            '<div class="ipb-geo-bar" aria-hidden="true">' +
              '<span style="width:' + barPct.toFixed(1) + '%"></span>' +
            '</div>' +
            '<div class="ipb-geo-meta">' +
              '<span><i class="fa fa-users" aria-hidden="true"></i> ' + persons.toLocaleString('en-US') + ' customers</span>' +
              '<span><i class="fa fa-chart-pie" aria-hidden="true"></i> ' + sharePct.toFixed(1) + '% of total</span>' +
            '</div>' +
          '</div>' +
        '</article>'
      );
    }).join('');
  }

  $(document).ready(function () {

    // Animate a counter element from its current displayed value to a new target
    function animateRouterCounter(el, newTarget) {
      const current = parseInt(el.textContent) || 0;
      if (current === newTarget) return;
      const duration = 800; // ms
      const steps = 40;
      const increment = (newTarget - current) / steps;
      let step = 0;
      const timer = setInterval(() => {
        step++;
        const val = Math.round(current + increment * step);
        el.textContent = step >= steps ? newTarget : val;
        if (step >= steps) clearInterval(timer);
      }, duration / steps);
    }

    // On page load: immediately animate from 0 â†’ cached value for each router card
    document.querySelectorAll('[data-cached]').forEach(el => {
      const cachedVal = parseInt(el.getAttribute('data-cached')) || 0;
      if (cachedVal > 0) {
        animateRouterCounter(el, cachedVal);
      }
    });

    // 05 §4.2 — KPI cascade entrance (opacity/translateY stagger, reduced-motion aware)
    if (window.IpbUI && window.IpbUI.cascade) window.IpbUI.cascade('.ipb-dash-kpi .ipb-kpi');

    // On page load: immediately animate from 0 â†’ cached PHP value for card metrics
    const cardIds = [
      'card_users_active', 'card_users_new', 'card_users_inactive', 'card_expired_inactive',
      'card_customers_payment_total', 'card_customers_Expayment_total', 'card_customers_payment_received',
      'card_customers_payment_pending', 'card_total_packages', 'card_total_area',
      'card_employee_active', 'card_employee_inactive', 'card_employee_payment_received',
      'card_employees_payment_pending', 'card_router_active', 'card_router_inactive', 'card_all_resellers'
    ];
    cardIds.forEach(id => {
      const el = document.getElementById(id);
      if (el) {
        animateCounter(el, 0);
      }
    });

    function loadRouterData(iface, routerId) {
      iface = iface || "";
      const statusEl = document.getElementById(`status_${routerId}`);
      const statusDot = statusEl.querySelector('.status-dot');
      const statusText = statusEl.querySelector('.status-label');
      const lastUpdatedEl = document.getElementById(`last_updated_${routerId}`);

      let baseUrl = "<?= base_url('routers/load-traffic'); ?>";
      let url = `${baseUrl}/${routerId}?interface=${iface}`;

      $.ajax({
        url: url,
        method: 'GET',
        dataType: 'json',
        timeout: 20000, // 20 second timeout (Mikrotik connections can be slow)
        success: function (response) {
          const result = response.response || response;
          // Guard: server may return empty [] on soft failures (e.g. read timeout)
          if (!result || !result.data) {
            statusDot.className = 'status-dot error';
            statusEl.className = 'status-indicator error';
            statusText.textContent = 'Data Error';
            lastUpdatedEl.textContent = `Last try: ${new Date().toLocaleTimeString()}`;
            console.warn('Router ' + routerId + ': unexpected empty response from server');
            return;
          }

          // Update counts with smooth animation
          const online = parseInt(result.data.active) || 0;
          const total = parseInt(result.data.users) || 0;
          const offline = total - online;

          const totalEl = document.getElementById(`total_user_count_${routerId}`);
          const activeEl = document.getElementById(`active_user_count_${routerId}`);
          const inactiveEl = document.getElementById(`inactive_user_count_${routerId}`);

          if (totalEl) { animateRouterCounter(totalEl, total); totalEl.setAttribute('data-cached', total); }
          if (activeEl) { animateRouterCounter(activeEl, online); activeEl.setAttribute('data-cached', online); }
          if (inactiveEl) { animateRouterCounter(inactiveEl, offline); inactiveEl.setAttribute('data-cached', offline); }

          // Update Status to Online
          statusDot.className = 'status-dot online';
          statusEl.className = 'status-indicator online';
          statusText.textContent = 'Online';
          lastUpdatedEl.textContent = `Updated: ${new Date().toLocaleTimeString()}`;
        },
        error: function (xhr, status, error) {
          // Try to parse the error message from the server's JSON body
          let serverMessage = null;
          try {
            const json = JSON.parse(xhr.responseText);
            serverMessage = json.message || null;
          } catch (e) { }

          console.error('Error loading data for router ' + routerId + ':', serverMessage || error);

          // Update status to error — keep showing cached values (don't reset to 0)
          statusDot.className = 'status-dot error';
          statusEl.className = 'status-indicator error';

          if (status === 'timeout') {
            statusText.textContent = 'Timeout';
          } else if (serverMessage) {
            // Show a clean version of the server error (truncated if too long)
            statusText.textContent = serverMessage.length > 40
              ? serverMessage.substring(0, 37) + '...'
              : serverMessage;
          } else {
            statusText.textContent = 'Connection Failed';
          }
          lastUpdatedEl.textContent = `Last try: ${new Date().toLocaleTimeString()}`;
        }
      });
    }

    // Event listeners for viewing all users and active users
    $('.view-all-users').on('click', function (e) {
      e.preventDefault();
      const routerId = $(this).data('router-id');
      // Define the action for viewing all users for the selected router
      // Redirect to the allusers route with the routerId as a query parameter
      window.location.href = `<?= route_to('route.routers.allusers'); ?>?routerId=${routerId}`;
    });

    $('.view-active-users').on('click', function (e) {
      e.preventDefault();
      const routerId = $(this).data('router-id');
      // Define the action for viewing active users for the selected router
      window.location.href = `<?= route_to('route.routers.activeusers'); ?>?routerId=${routerId}`;

    });
    $('.view-inactive-users').on('click', function (e) {
      e.preventDefault();
      const routerId = $(this).data('router-id');
      // Define the action for viewing active users for the selected router
      window.location.href = `<?= route_to('route.routers.inactiveusers'); ?>?routerId=${routerId}`;

    });

    // 1. Fetch dashboard card metrics immediately
    $.ajax({
      url: "<?= base_url('api/dashboard/sadmin-data') ?>",
      method: "GET",
      dataType: "json",
      timeout: 45000,
      success: function(response) {
        if (response.status !== 'success') {
          console.error('Failed to load Super Admin cards:', response.message);
          return;
        }

        const cardMappings = {
          'card_users_active': response.users_active,
          'card_users_new': response.users_new,
          'card_users_inactive': response.users_inactive,
          'card_expired_inactive': response.expired_inactive,
          'card_customers_payment_total': response.customers_payment_total,
          'card_customers_payment_pending': response.customers_payment_pending,
          'card_total_packages': response.total_packages,
          'card_total_area': response.total_area,
          'card_employee_active': response.employee_active,
          'card_employee_inactive': response.employee_inactive,
          'card_employee_payment_received': response.employee_payment_received,
          'card_employees_payment_pending': response.employees_payment_pending,
          'card_router_active': response.router_active,
          'card_router_inactive': response.router_inactive,
          'card_all_resellers': response.all_resellers
        };

        for (const [id, val] of Object.entries(cardMappings)) {
          const el = document.getElementById(id);
          if (el) {
            el.setAttribute('data-target', val);
            animateCounter(el);
          }
        }

        if (response.customer_quota) {
          const q = response.customer_quota;
          const quotaEl = document.getElementById('card_customer_quota');
          if (quotaEl) {
            quotaEl.textContent = q.is_unlimited
              ? `${q.used} / ∞`
              : `${q.used} / ${q.limit}`;
          }
          const bar = document.getElementById('card_customer_quota_bar');
          if (bar && !q.is_unlimited) {
            bar.style.width = Math.min(100, q.percent || 0) + '%';
          }
        }

        const cardExpayment = document.getElementById('card_customers_Expayment_total');
        if (cardExpayment) {
          cardExpayment.setAttribute('data-target', response.customers_Expayment_total);
          cardExpayment.setAttribute('data-count', response.customers_Expayment_count);
          animateCounter(cardExpayment);
        }

        const cardReceived = document.getElementById('card_customers_payment_received');
        if (cardReceived) {
          cardReceived.setAttribute('data-target', response.customers_payment_received);
          cardReceived.setAttribute('data-count', response.customers_payment_received_count);
          animateCounter(cardReceived);
        }

        // Keep AI Insights in sync with the same live card metrics
        const paymentReceived = Number(response.customers_payment_received) || 0;
        const paymentReceivedCount = Number(response.customers_payment_received_count) || 0;
        const expiredInactive = Number(response.expired_inactive) || 0;
        const routerActive = Number(response.router_active) || 0;
        const allResellers = Number(response.all_resellers) || 0;

        const insightPayment = document.getElementById('insight_payment_received');
        if (insightPayment) {
          insightPayment.textContent = paymentReceived.toLocaleString('en-US');
        }
        const insightPaymentCount = document.getElementById('insight_payment_received_count');
        if (insightPaymentCount) {
          insightPaymentCount.textContent = String(paymentReceivedCount);
        }
        const insightExpired = document.getElementById('insight_expired_inactive');
        if (insightExpired) {
          insightExpired.textContent = String(expiredInactive);
        }
        const insightRouters = document.getElementById('insight_router_active');
        if (insightRouters) {
          insightRouters.textContent = String(routerActive);
        }
        const insightResellers = document.getElementById('insight_all_resellers');
        if (insightResellers) {
          insightResellers.textContent = String(allResellers);
        }

        // Fetch data for each router after card metrics are loaded with a staggered delay
        <?php $index = 0; foreach ($routers as $router): ?>
          setTimeout(function() {
            loadRouterData('', <?= $router->id; ?>);
          }, <?= $index * 500; ?>);
        <?php $index++; endforeach; ?>
      },
      error: function(xhr, status, error) {
        console.error('Error fetching Super Admin card metrics:', error);
        var failedText = status === 'parsererror'
          ? 'Session expired — refresh'
          : (status === 'timeout' ? 'Timed out' : '—');
        [
          'card_users_active', 'card_users_new', 'card_users_inactive', 'card_expired_inactive',
          'card_customers_payment_total', 'card_customers_payment_pending', 'card_total_packages',
          'card_total_area', 'card_employee_active', 'card_employee_inactive',
          'card_employee_payment_received', 'card_employees_payment_pending',
          'card_router_active', 'card_router_inactive', 'card_all_resellers',
          'card_customers_payment_received', 'card_customers_Expayment_total'
        ].forEach(function(id) {
          var el = document.getElementById(id);
          if (el && (el.textContent === '0' || el.textContent === '' || el.classList.contains('is-loading'))) {
            el.textContent = failedText;
          }
        });
        if (window.tata) {
          tata.error('Dashboard', status === 'timeout'
            ? 'Card metrics timed out. Refresh the page.'
            : 'Could not load card metrics.');
        }
      }
    });
  });

  // Charts load independently of the card metrics above. They used to be
  // chained inside that request's success callback (100ms setTimeout), so
  // any failure there — session expiry, a network blip, a slow response —
  // silently and permanently left every chart on this page with no data
  // and no retry, with only a console.error nobody would ever see.
  function loadSuperAdminCharts(isRetry) {
    $.ajax({
      url: "<?= base_url('api/dashboard/sadmin-charts-data') ?>",
      method: "GET",
      dataType: "json",
      success: function(response) {
        if (response.status !== 'success') {
          console.error('Failed to load Super Admin charts:', response.message);
          renderGeoRevenue([]);
          swapSkeletonHtml('packageDistributionList', '<div class="py-2 text-muted text-center">No package data</div>');
          swapSkeletonHtml('paymentMethodList', '<div class="py-2 text-muted text-center">No payment method data</div>');
          return;
        }

        // Update Ticket Stats
        if (response.ticket_stats) {
          $('#ticket_open').text(response.ticket_stats.open || 0);
          $('#ticket_open_kpi').text(response.ticket_stats.open || 0);
          $('#ticket_ongoing').text(response.ticket_stats.ongoing || 0);
          $('#ticket_solved').text(response.ticket_stats.solved || 0);
          $('#ticket_closed').text(response.ticket_stats.closed || 0);
          // The solved rate is drawn by collectionRateRadialChart (updated below).
          // It used to ALSO be pushed into a CSS ring's --pct and a text node — two
          // renderings of one number, which could disagree if either update failed.
        }

        // Update Badges
        $('#efficiencyRateBadge').text(`Efficiency: ${response.efficiency_rate || 0}%`);
        $('#retentionRateBadge').text(`Retention: ${response.retention_rate || 0}%`);

        const weeklyGrowth = parseFloat(response.weekly_growth || 0);
        const growthArrow = weeklyGrowth >= 0 ? 'up' : 'down';
        $('#weeklyGrowthBadge')
          .removeClass('up down')
          .addClass(growthArrow)
          .html(`<i class="fa fa-arrow-${growthArrow}"></i> ${weeklyGrowth >= 0 ? '+' : ''}${weeklyGrowth}%`);

        // This total is platform-wide. The bandwidth card scopes it to the selected
        // router, so only let the poll write it while the card is on "All Routers" —
        // otherwise every refresh silently reverted the router's total to the global one.
        if (($('#routerSelect').val() || 'all') === 'all') {
          $('#totalDataGB').text(parseFloat(response.total_data_gb || 0).toFixed(1));
        }

        // Package Distribution List
        const pkgColors = ['#007bff', '#28a745', '#ffc107', '#6f42c1', '#dc3545', '#fd7e14', '#20c997', '#0d6efd'];
        let pkgHtml = '';
        if (response.package_distribution && response.package_distribution.length > 0) {
          response.package_distribution.forEach((pkg, index) => {
            const color = pkgColors[index % pkgColors.length];
            const empty = Number(pkg.count) > 0 ? '' : ' is-zero';
            pkgHtml += `<div class="ipb-legend-row${empty}">`
              + `<span class="ipb-legend-dot" style="--dot:${color}"></span>`
              + `<span class="ipb-legend-name">${escapeHtml(pkg.package_name || 'Unknown')}</span>`
              + `<span class="ipb-legend-val">${pkg.count}</span></div>`;
          });
        } else {
          pkgHtml = '<div class="py-2 text-muted text-center">No package data</div>';
        }
        swapSkeletonHtml('packageDistributionList', pkgHtml);

        // Payment Method Mix List
        const payColors = ['#e83e8c', '#28a745', '#fd7e14', '#6f42c1', '#007bff', '#20c997', '#ffc107'];
        const paidViaTotal = response.payment_methods ? response.payment_methods.reduce((sum, item) => sum + parseFloat(item.total || 0), 0) : 0;
        let payHtml = '';
        if (response.payment_methods && response.payment_methods.length > 0) {
          // Rails carrying nothing ("0%") still belong in a *mix* — they say the
          // method is wired up — but they were listed in table order, so six dead
          // rows outranked the two that carry the money. Biggest share first, and
          // the empty ones dimmed (.is-zero) rather than dropped.
          const payRows = response.payment_methods
            .map((pay, index) => ({
              color: payColors[index % payColors.length],
              label: pay.paid_via.charAt(0).toUpperCase() + pay.paid_via.slice(1),
              total: parseFloat(pay.total || 0),
            }))
            .sort((a, b) => b.total - a.total);

          payRows.forEach((row) => {
            const percent = paidViaTotal > 0 ? ((row.total / paidViaTotal) * 100).toFixed(0) : '0';
            const empty = row.total > 0 ? '' : ' is-zero';
            payHtml += `<div class="ipb-legend-row${empty}">`
              + `<span class="ipb-legend-dot" style="--dot:${row.color}"></span>`
              + `<span class="ipb-legend-name">${escapeHtml(row.label)}</span>`
              + `<span class="ipb-legend-val">${percent}%</span></div>`;
          });
        } else {
          payHtml = '<div class="py-2 text-muted text-center">No payment method data</div>';
        }
        swapSkeletonHtml('paymentMethodList', payHtml);

        // Geo Revenue
        renderGeoRevenue(response.geo_revenue || []);

        // Update Charts
        if (customerPaymentReportChart && response.customer_payment_statistics) {
          customerPaymentReportChart.updateSeries([
            { name: 'Successful', data: response.customer_payment_statistics.successful },
            { name: 'Pending', data: response.customer_payment_statistics.pending },
            { name: 'Failed', data: response.customer_payment_statistics.failed }
          ]);
          customerPaymentReportChart.updateOptions({ xaxis: { categories: response.customer_payment_statistics.months } });
        }
        if (employeePaymentReportChart && response.employee_payment_statistics) {
          employeePaymentReportChart.updateSeries([
            { name: 'Successful', data: response.employee_payment_statistics.successful },
            { name: 'Pending', data: response.employee_payment_statistics.pending },
            { name: 'Failed', data: response.employee_payment_statistics.failed }
          ]);
          employeePaymentReportChart.updateOptions({ xaxis: { categories: response.employee_payment_statistics.months } });
        }
        if (weeklyCollectionChart && response.weekly_collections) {
          weeklyCollectionChart.updateSeries([{ name: 'Collection', data: response.weekly_collections.map(i => i.amount) }]);
          weeklyCollectionChart.updateOptions({ xaxis: { categories: response.weekly_collections.map(i => i.day) } });
        }
        if (packageDistributionChart && response.package_distribution) {
          const pkgCounts = response.package_distribution.map(item => parseInt(item.count || 0));
          const pkgLabels = response.package_distribution.map(item => item.package_name || 'Unknown');
          packageDistributionChart.updateOptions({ series: pkgCounts.length > 0 ? pkgCounts : [0], labels: pkgLabels.length > 0 ? pkgLabels : ['None'] });
          if (pkgCounts.length === 0) {
            packageDistributionChart.updateOptions({ noData: { text: 'No data yet' } });
          }
        }
        if (paymentMethodChart && response.payment_methods) {
          const payTotals = response.payment_methods.map(item => parseFloat(item.total || 0));
          const payLabels = response.payment_methods.map(item => item.paid_via.charAt(0).toUpperCase() + item.paid_via.slice(1));
          paymentMethodChart.updateOptions({ series: payTotals.length > 0 ? payTotals : [0], labels: payLabels.length > 0 ? payLabels : ['None'] });
          if (payTotals.length === 0) {
            paymentMethodChart.updateOptions({ noData: { text: 'No data yet' } });
          }
        }
        if (revenueOverviewChart && response.revenue_overview) {
          revenueOverviewChart.updateSeries([
            { name: 'Revenue', data: response.revenue_overview.map(i => i.revenue) },
            { name: 'Collection', data: response.revenue_overview.map(i => i.collection) },
            { name: 'Expense', data: response.revenue_overview.map(i => i.expense) }
          ]);
          revenueOverviewChart.updateOptions({ xaxis: { categories: response.revenue_overview.map(i => i.month) } });
        }
        if (growthChurnChart && response.growth_churn) {
          growthChurnChart.updateSeries([
            { name: 'New Customers', data: response.growth_churn.map(i => i.new) },
            { name: 'Churn', data: response.growth_churn.map(i => i.churn * -1) }
          ]);
          growthChurnChart.updateOptions({ xaxis: { categories: response.growth_churn.map(i => i.month) } });
        }
        if (collectionRateRadialChart) {
          collectionRateRadialChart.updateSeries([response.ticket_solving_rate || 0]);
        }
      },
      error: function(xhr, status, error) {
        console.error('Error fetching Super Admin charts data:', error);

        // A parsererror here almost always means the session expired and
        // AuthCheck redirected to the login page's HTML instead of
        // returning JSON — retrying against a dead session just repeats
        // the same failure, so only auto-retry genuinely transient
        // failures (timeout / network / 5xx), once.
        if (!isRetry && status !== 'parsererror') {
          setTimeout(function() { loadSuperAdminCharts(true); }, 3000);
          return;
        }

        renderGeoRevenue([]);
        swapSkeletonHtml('packageDistributionList', '<div class="py-2 text-muted text-center">No package data</div>');
        swapSkeletonHtml('paymentMethodList', '<div class="py-2 text-muted text-center">No payment method data</div>');

        var failedText = status === 'parsererror' ? 'Session expired — please refresh' : 'Failed to load';
        [customerPaymentReportChart, employeePaymentReportChart, weeklyCollectionChart,
          packageDistributionChart, paymentMethodChart, revenueOverviewChart,
          growthChurnChart, collectionRateRadialChart].forEach(function(chart) {
          if (chart) chart.updateOptions({ noData: { text: failedText } });
        });
      }
    });
  }

  $(document).ready(function () {
    loadSuperAdminCharts();
  });


  document.addEventListener("DOMContentLoaded", () => {
    const p = window.IpbTheme.chartPalette();

    //customer payment resport chart
    customerPaymentReportChart = new ApexCharts(document.querySelector("#customerPaymentReportChart"), {
      series: [{
        name: 'Successful',
        data: [],
      },
      {
        name: 'Pending',
        data: []
      },
      {
        name: 'Failed',
        data: []
      }
      ],
      chart: {
        height: 300,
        type: 'bar',
        toolbar: {
          show: false
        },
        animations: window.IpbUI ? window.IpbUI.chartMotion() : undefined, // 05 §4.4
      },
      markers: {
        size: 4
      },
      colors: ['#16a34a', '#d97706', '#dc2626'],
      dataLabels: {
        enabled: false
      },
      legend: { position: 'top', horizontalAlign: 'right', fontFamily: 'Satoshi, sans-serif', labels: { colors: p.ink } },
      plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
      tooltip: {
        shared: true,
        intersect: false,
        y: {
          formatter: function (val) {
            return "৳ " + val + " BDT"
          }
        }
      },
      grid: { borderColor: p.grid },
      xaxis: {
        categories: [],
        axisBorder: { show: false },
        labels: { style: { colors: p.axis } }
      },
      yaxis: {
        title: {
          text: 'Transaction Amount ',
          style: { color: p.ink }
        },
        labels: {
          style: { colors: p.axis },
          formatter: function (val) {
            return parseFloat(val).toFixed(2);
          }
        }
      },
      // Phone: drop the rotated axis title, shrink the labels, move the legend
      // below the plot, and compact this money axis ("60000.00" -> "60k").
      responsive: window.IpbUI
        ? window.IpbUI.chartResponsive('bar', { yaxis: { labels: { formatter: window.IpbUI.compactNumber } } })
        : []
    });
    window.IpbTheme.registerChart(customerPaymentReportChart);
    customerPaymentReportChart.render();

    //employee payment chart
    employeePaymentReportChart = new ApexCharts(document.querySelector("#employeePaymentReportChart"), {
      series: [{
        name: 'Successful',
        data: [],
      },
      {
        name: 'Pending',
        data: []
      },
      {
        name: 'Failed',
        data: []
      }
      ],
      chart: {
        height: 300,
        type: 'bar',
        toolbar: {
          show: false
        },
      },
      colors: ['#0e7220ff', '#f59e0b', '#ff0080'],
      dataLabels: {
        enabled: false
      },
      legend: { position: 'top', horizontalAlign: 'right', fontFamily: 'Satoshi, sans-serif', labels: { colors: p.ink } },
      plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
      grid: { borderColor: p.grid },
      xaxis: {
        categories: [],
        axisBorder: { show: false },
        labels: { style: { colors: p.axis } }
      },
      yaxis: {
        title: {
          text: 'Transaction Amount ',
          style: { color: p.ink }
        },
        labels: {
          style: { colors: p.axis },
          formatter: function (val) {
            return parseFloat(val).toFixed(2);
          }
        }
      },
      responsive: window.IpbUI
        ? window.IpbUI.chartResponsive('bar', { yaxis: { labels: { formatter: window.IpbUI.compactNumber } } })
        : []
    });
    window.IpbTheme.registerChart(employeePaymentReportChart);
    employeePaymentReportChart.render();

    // Weekly Collection Chart
    weeklyCollectionChart = new ApexCharts(document.querySelector("#weeklyCollectionChart"), {
      series: [{ name: 'Collection', data: [] }],
      chart: { type: 'bar', height: 250, toolbar: { show: false } },
      plotOptions: { bar: { borderRadius: 8, columnWidth: '45%' } },
      colors: ['#4f46e5'],
      grid: { borderColor: p.grid },
      xaxis: {
        categories: [],
        labels: { style: { colors: p.axis } }
      },
      yaxis: { labels: { style: { colors: p.axis }, formatter: (val) => parseFloat(val).toFixed(2) + 'k' } },
      // Series is already in thousands, so keep the 'k' unit — just drop the two
      // decimals ("0.80k" -> "1k") that were padding the axis out on a phone.
      responsive: window.IpbUI
        ? window.IpbUI.chartResponsive('bar', {
            yaxis: { labels: { formatter: (val) => parseFloat(val).toFixed(0) + 'k' } }
          })
        : []
    });
    window.IpbTheme.registerChart(weeklyCollectionChart);
    weeklyCollectionChart.render();

    // Package Distribution Chart
    packageDistributionChart = new ApexCharts(document.querySelector("#packageDistributionChart"), {
      series: [],
      chart: {
        type: 'donut',
        height: 300,
        toolbar: { show: false },
        background: 'transparent'
      },
      labels: [],
      colors: ['#007bff', '#28a745', '#ffc107', '#6f42c1', '#dc3545', '#fd7e14', '#20c997'],
      legend: { show: false, labels: { colors: p.ink } },
      dataLabels: {
        enabled: true,
        formatter: (val) => val.toFixed(0) + "%",
        style: { fontSize: '11px', fontWeight: '700', colors: ['#fff'] },
        dropShadow: { enabled: false }
      },
      stroke: { width: 3, colors: [p.surface] },
      plotOptions: { pie: { donut: { size: '60%' } } },
      responsive: window.IpbUI
        ? window.IpbUI.chartResponsive('donut')
        : [{ breakpoint: 480, options: { chart: { height: 220 } } }]
    });
    window.IpbTheme.registerChart(packageDistributionChart);
    packageDistributionChart.render();

    // Payment Method Chart
    paymentMethodChart = new ApexCharts(document.querySelector("#paymentMethodChart"), {
      series: [],
      chart: {
        type: 'donut',
        height: 300,
        toolbar: { show: false },
        background: 'transparent'
      },
      labels: [],
      colors: ['#e83e8c', '#28a745', '#fd7e14', '#6f42c1', '#007bff', '#20c997'],
      legend: { show: false, labels: { colors: p.ink } },
      dataLabels: {
        enabled: true,
        formatter: (val) => val.toFixed(0) + "%",
        style: { fontSize: '11px', fontWeight: '700', colors: ['#fff'] },
        dropShadow: { enabled: false }
      },
      stroke: { width: 3, colors: [p.surface] },
      plotOptions: { pie: { donut: { size: '60%' } } },
      responsive: window.IpbUI
        ? window.IpbUI.chartResponsive('donut')
        : [{ breakpoint: 480, options: { chart: { height: 220 } } }]
    });
    window.IpbTheme.registerChart(paymentMethodChart);
    paymentMethodChart.render();

    // Revenue Overview Chart
    revenueOverviewChart = new ApexCharts(document.querySelector("#revenueOverviewChart"), {
      series: [
        { name: 'Revenue', data: [] },
        { name: 'Collection', data: [] },
        { name: 'Expense', data: [] }
      ],
      chart: { type: 'area', height: 300, toolbar: { show: false } },
      stroke: { curve: 'smooth', width: 3 },
      fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.45, opacityTo: 0.05 } },
      colors: ['#00f2fe', '#10b981', '#ff0080'],
      legend: { labels: { colors: p.ink } },
      grid: { borderColor: p.grid },
      yaxis: {
        labels: {
          style: { colors: p.axis },
          formatter: function (val) {
            return parseFloat(val).toFixed(2);
          }
        }
      },
      xaxis: { categories: [], labels: { style: { colors: p.axis } } },
      responsive: window.IpbUI
        ? window.IpbUI.chartResponsive('area', { yaxis: { labels: { formatter: window.IpbUI.compactNumber } } })
        : []
    });
    window.IpbTheme.registerChart(revenueOverviewChart);
    revenueOverviewChart.render();

    // Growth & Churn Chart
    growthChurnChart = new ApexCharts(document.querySelector("#growthChurnChart"), {
      series: [
        { name: 'New Customers', data: [] },
        { name: 'Churn', data: [] }
      ],
      chart: { type: 'bar', height: 300, stacked: true, toolbar: { show: false } },
      plotOptions: { bar: { borderRadius: 6, columnWidth: '50%' } },
      colors: ['#00f2fe', '#ff0080'],
      legend: { labels: { colors: p.ink } },
      grid: { borderColor: p.grid },
      yaxis: { labels: { style: { colors: p.axis } } },
      xaxis: { categories: [], labels: { style: { colors: p.axis } } },
      responsive: window.IpbUI ? window.IpbUI.chartResponsive('bar') : undefined
    });
    window.IpbTheme.registerChart(growthChurnChart);
    growthChurnChart.render();

    // Bandwidth Usage Chart (daily consumption by router)
    let bwRequestId = 0;
    let liveInterval = null;
    let activeBwRouterId = 'all';
    const bwCache = Object.create(null);   // routerId -> { data, labels, total_gb }
    let bwRendered = '';                   // signature of the series currently drawn

    const bwPalette = window.IpbTheme.chartPalette();

    window.bwChart = new ApexCharts(document.querySelector("#bandwidthUsageChart"), {
      series: [
        { name: 'Total Usage', data: [] }
      ],
      chart: { type: 'area', height: 280, toolbar: { show: false }, zoom: { enabled: false } },
      dataLabels: { enabled: false },
      stroke: { curve: 'smooth', width: 2 },
      colors: ['#3b82f6'],
      fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.45, opacityTo: 0.05 } },
      xaxis: {
        type: 'category',
        categories: [],
        labels: {
          style: { colors: bwPalette.axis }
        }
      },
      yaxis: {
        labels: {
          style: { colors: bwPalette.axis },
          formatter: (val) => val < 1024 ? val.toFixed(2) + ' MB' : (val / 1024).toFixed(2) + ' GB'
        }
      },
      // Keep the MB/GB unit (an unlabelled bandwidth axis is meaningless) but
      // drop the two decimals — "1024.00 MB" is nine characters of axis gutter.
      responsive: window.IpbUI
        ? window.IpbUI.chartResponsive('area', {
            yaxis: {
              labels: {
                formatter: (val) => (val < 1024 ? Math.round(val) + ' MB' : (val / 1024).toFixed(1) + ' GB')
              }
            }
          })
        : []
    });
    window.IpbTheme.registerChart(window.bwChart);
    bwChart.render();

    function setBandwidthChart(data, labels) {
      const seriesData = Array.isArray(data) ? data.map(function (v) { return Number(v) || 0; }) : [];
      const cats = Array.isArray(labels) ? labels : [];

      /* Same numbers already on screen? Don't redraw. Without this, painting from
         cache and then again from the (identical) network answer replays the whole
         draw animation twice, which reads as a stutter. */
      const sig = JSON.stringify([seriesData, cats]);
      if (sig === bwRendered) return;
      bwRendered = sig;
      /* Read fresh, not the palette captured at init — a data refresh can land
         after the user has flipped the theme, and this call would otherwise
         reinstate the light-mode axis color. */
      const p = window.IpbTheme.chartPalette();

      bwChart.updateOptions({
        series: [{ name: 'Total Usage', data: seriesData }],
        xaxis: {
          type: 'category',
          categories: cats,
          labels: { style: { colors: p.axis } }
        },
        noData: {
          text: seriesData.length ? undefined : 'No usage data',
          style: { color: p.axis, fontSize: '13px' }
        }
      }, true, true);

      setTimeout(function () {
        try {
          if (bwChart && typeof bwChart.resize === 'function') bwChart.resize();
        } catch (e) {}
      }, 50);
    }

    function stopLiveTraffic() {
      if (liveInterval) {
        clearInterval(liveInterval);
        liveInterval = null;
      }
    }

    // Navigating away via the sidebar swaps #ipb-main instead of reloading the
    // document — without this the 3s router poll would keep running forever.
    (window.IpbPageTeardown = window.IpbPageTeardown || []).push(stopLiveTraffic);

    function updateBandwidthChart(routerId) {
      routerId = String(routerId || 'all');
      activeBwRouterId = routerId;
      const requestId = ++bwRequestId;

      stopLiveTraffic();

      const chartEl = document.getElementById('bandwidthUsageChart');
      const totalEl = document.getElementById('totalDataGB');
      const liveEl = document.getElementById('liveThroughput');

      const cached = bwCache[routerId];
      if (cached) {
        /* Paint the last answer for this router immediately — the request below only
           confirms it. Flipping back to a router you have already opened is instant. */
        setBandwidthChart(cached.data, cached.labels);
        if (totalEl) totalEl.innerText = cached.total_gb.toFixed(1);
        if (chartEl) chartEl.classList.remove('is-bw-loading');
      } else {
        /* Nothing cached yet: dim the card while it loads. This used to blank the chart
           to an empty grid the moment you switched (setBandwidthChart([], [])), so the
           whole wait was spent looking at "No usage data" — that empty frame is what
           read as the delay, whatever the request actually cost. */
        if (chartEl) chartEl.classList.add('is-bw-loading');
        if (totalEl) totalEl.innerText = '…';
      }

      if (liveEl) {
        liveEl.innerHTML = routerId === 'all'
          ? '<span class="text-muted">Live: All routers summary</span>'
          : '<span class="text-muted">Live: Loading…</span>';
      }

      fetch('<?= base_url("api/dashboard/bandwidth-usage") ?>/' + encodeURIComponent(routerId), {
        credentials: 'same-origin',
        headers: { 'Accept': 'application/json' }
      })
        .then(function (res) { return res.json(); })
        .then(function (res) {
          if (requestId !== bwRequestId || activeBwRouterId !== routerId) {
            return;
          }
          if (chartEl) chartEl.classList.remove('is-bw-loading');

          if (res.status === 'success' && Array.isArray(res.data)) {
            const totalGB = parseFloat(res.total_gb) || 0;
            bwCache[routerId] = { data: res.data, labels: res.labels || [], total_gb: totalGB };
            if (totalEl) totalEl.innerText = totalGB.toFixed(1);
            setBandwidthChart(res.data, res.labels || []);

            if (routerId === 'all') {
              if (liveEl) liveEl.innerHTML = '<span class="text-muted">Live: All routers summary</span>';
            } else {
              startLiveTraffic(routerId);
            }
          } else {
            setBandwidthChart([], []);
            if (totalEl) totalEl.innerText = '0.0';
            if (liveEl) liveEl.innerHTML = '<span class="text-danger">Live: Data unavailable</span>';
          }
        })
        .catch(function () {
          if (requestId !== bwRequestId || activeBwRouterId !== routerId) {
            return;
          }
          if (chartEl) chartEl.classList.remove('is-bw-loading');
          setBandwidthChart([], []);
          if (totalEl) totalEl.innerText = '0.0';
          if (liveEl) liveEl.innerHTML = '<span class="text-danger">Live: Router Offline</span>';
        });
    }

    function startLiveTraffic(routerId) {
      const liveEl = document.getElementById('liveThroughput');
      if (!liveEl) return;

      stopLiveTraffic();

      function pollLive() {
        if (activeBwRouterId !== String(routerId)) {
          stopLiveTraffic();
          return;
        }

        fetch('<?= base_url("routers/load-traffic") ?>/' + encodeURIComponent(routerId) + '?interface=WAN', {
          credentials: 'same-origin',
          headers: { 'Accept': 'application/json' }
        })
          .then(function (res) { return res.json(); })
          .then(function (res) {
            if (activeBwRouterId !== String(routerId)) return;

            const result = res.response || res;
            const traffic = result.data && result.data.traffic;
            if (!traffic) {
              throw new Error('Traffic data missing');
            }
            // Live subtitle only — do not overwrite the daily consumption chart
            liveEl.innerHTML = '<span class="text-primary" style="font-weight:600">Live: ' +
              traffic.rxbyte + ' ↓ ' + traffic.txbyte + ' ↑</span>';
          })
          .catch(function () {
            if (activeBwRouterId !== String(routerId)) return;
            liveEl.innerHTML = '<span class="text-danger">Live: Router Offline</span>';
          });
      }

      pollLive();
      liveInterval = setInterval(pollLive, 3000);
    }

    /* Bind with jQuery, NOT addEventListener: script.js select2-ifies every
       `select[class="form-control"]` on every page, and #routerSelect matches it
       exactly. select2 hides the native <select> and announces a pick by
       TRIGGERING a jQuery change event — jQuery's .trigger() never dispatches a
       DOM event, so a native addEventListener('change') handler is never called.
       That is why picking a router did nothing here. A jQuery handler receives
       both the synthetic (select2) and a real native change. */
    if ($('#routerSelect').length) {
      $('#routerSelect').on('change', function () {
        updateBandwidthChart(this.value);
      });
    }

    updateBandwidthChart('all');

    // Support Resolution (Single Radial)
    collectionRateRadialChart = new ApexCharts(document.querySelector("#collectionRateRadialChart"), {
      // Seed from the server-rendered rate: the AJAX refresh below overwrites it, but
      // starting at 0 made the (now visible) chart animate up from zero on every load.
      series: [<?= (int) ($ticket_solving_rate ?? $ticketSolvedPct); ?>],
      chart: { height: 280, type: 'radialBar' },
      plotOptions: {
        radialBar: {
          hollow: { size: '65%', background: 'transparent' },
          track: { background: p.grid, strokeWidth: '100%' },
          dataLabels: {
            name: { show: true, fontSize: '14px', color: p.axis, offsetY: -10 },
            value: {
              show: true,
              fontSize: '22px',
              fontWeight: 'bold',
              color: '#f59e0b',
              offsetY: 5,
              formatter: (val) => val + '%'
            }
          }
        }
      },
      labels: ['Solved'],   // the value IS the solved rate — "Support" named the card, not the number
      colors: ['#f59e0b'],
      stroke: { lineCap: 'round' },
      responsive: window.IpbUI ? window.IpbUI.chartResponsive('radial') : undefined
    });
    window.IpbTheme.registerChart(collectionRateRadialChart);
    collectionRateRadialChart.render();

  });
</script>

<?= $this->endSection('script'); ?>

