<?= $this->extend('layout/main-layout'); ?>
<?php $this->section('needsDataTable'); ?>1<?php $this->endSection(); ?>

<?= $this->section('css'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/saas/dashboard.css?v=22'); ?>">
<style>
  .ipb-rev-box .box-body {
    padding: 0;
  }
  .ipb-rev-tab-pane {
    display: none;
    padding: 24px 24px 28px;
  }
  .ipb-rev-tab-pane.is-active { display: block; }
  .ipb-rev-section {
    margin-bottom: 20px;
  }
  .ipb-rev-section:last-child {
    margin-bottom: 0;
  }
  .ipb-rev-section-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 14px;
  }
  .ipb-rev-section-title {
    margin: 0;
    font-size: 13px;
    font-weight: 800;
    letter-spacing: .04em;
    text-transform: uppercase;
    color: var(--text-secondary, #64748b);
  }
  .ipb-rev-section-title i {
    margin-right: 6px;
    color: var(--primary-500, #f75803);
  }
  .ipb-rev-filters {
    display: flex;
    flex-direction: column;
    gap: 14px;
    padding: 18px 20px;
    border: 1px solid var(--border, #e2e8f0);
    border-radius: 12px;
    background: var(--surface-2, #f8fafc);
    margin: 0;
  }
  .ipb-rev-filter-row {
    display: grid;
    gap: 14px 16px;
    align-items: end;
  }
  .ipb-rev-filter-row-top {
    grid-template-columns: minmax(220px, 2fr) minmax(160px, 1fr) minmax(160px, 1fr);
  }
  .ipb-rev-filter-row-bottom {
    grid-template-columns: minmax(120px, 1fr) minmax(100px, 1fr) minmax(120px, 1fr) auto;
  }
  .ipb-rev-filters label {
    display: block;
    margin: 0 0 6px;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: var(--text-muted, #94a3b8);
  }
  .ipb-rev-filters .form-control {
    min-height: 38px;
  }
  .ipb-rev-filter-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    justify-content: flex-end;
    flex-wrap: nowrap;
  }
  .ipb-rev-filter-actions .btn {
    min-height: 38px;
    min-width: 96px;
    white-space: nowrap;
  }
  .ipb-rev-table-wrap {
    overflow: auto;
    border: 1px solid var(--border, #e2e8f0);
    border-radius: 12px;
    background: var(--surface, #fff);
    padding: 4px;
  }
  .ipb-rev-table-wrap table { margin: 0; }
  .ipb-rev-table-wrap th {
    white-space: nowrap;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: .03em;
    color: var(--text-secondary, #64748b);
    background: var(--surface-2, #f8fafc);
  }
  .ipb-rev-table-wrap td { vertical-align: middle; }
  .ipb-rev-totals {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14px;
    margin: 0;
  }
  .ipb-rev-total-chip {
    padding: 16px 18px;
    border: 1px solid var(--border, #e2e8f0);
    border-radius: 12px;
    background: var(--surface, #fff);
    min-height: 78px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 6px;
  }
  .ipb-rev-total-chip span {
    display: block;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: var(--text-muted, #94a3b8);
  }
  .ipb-rev-total-chip strong {
    font-size: 20px;
    line-height: 1.2;
    color: var(--text-primary, #0f172a);
  }
  .ipb-pkg-filter {
    display: flex;
    flex-wrap: wrap;
    gap: 14px 16px;
    align-items: flex-end;
    padding: 18px 20px;
    margin: 0;
    border: 1px solid var(--border, #e2e8f0);
    border-radius: 12px;
    background: var(--surface-2, #f8fafc);
  }
  .ipb-pkg-filter > div:not(.ipb-pkg-filter-actions) {
    flex: 1 1 150px;
    min-width: 140px;
    max-width: 220px;
  }
  .ipb-pkg-filter label {
    display: block;
    margin: 0 0 6px;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: var(--text-muted, #94a3b8);
  }
  .ipb-pkg-filter .form-control {
    min-height: 38px;
  }
  .ipb-pkg-filter-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex: 0 0 auto;
    margin-left: auto;
  }
  .ipb-pkg-filter-actions .btn {
    min-height: 38px;
    min-width: 96px;
    white-space: nowrap;
  }
  .ipb-pkg-period-label {
    font-size: 13px;
    font-weight: 600;
    line-height: 1.5;
    color: var(--text-secondary, #64748b);
    margin: 0 0 16px;
    padding: 12px 16px;
    border-radius: 10px;
    background: rgba(59, 130, 246, .08);
    border: 1px solid rgba(59, 130, 246, .15);
  }
  .ipb-pkg-period-label i {
    color: #2563eb;
    margin-right: 6px;
  }
  .ipb-pkg-range-fields { display: none; }
  .ipb-rev-tabs {
    margin-bottom: 0;
    border-bottom: 1px solid var(--border, #e2e8f0);
    padding: 0 24px;
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    background: var(--surface-2, #f8fafc);
  }
  .ipb-rev-tab {
    appearance: none;
    border: none;
    background: transparent;
    padding: 16px 20px;
    font-size: 14px;
    font-weight: 800;
    color: var(--text-secondary, #64748b);
    cursor: pointer;
    border-bottom: 3px solid transparent;
    margin-bottom: -1px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    transition: color .15s ease, border-color .15s ease, background .15s ease;
    border-radius: 10px 10px 0 0;
  }
  .ipb-rev-tab:hover {
    color: var(--primary-600, #ea580c);
    background: rgba(255, 255, 255, .6);
  }
  .ipb-rev-tab.is-active {
    color: var(--primary-600, #ea580c);
    border-bottom-color: var(--primary-500, #f75803);
    background: var(--surface, #fff);
  }
  .ipb-rev-tab-badge {
    font-size: 11px;
    font-weight: 800;
    padding: 3px 10px;
    border-radius: 999px;
    background: var(--surface-2, #eef2f7);
    color: var(--text-muted, #94a3b8);
    white-space: nowrap;
  }
  .ipb-rev-tab.is-active .ipb-rev-tab-badge {
    background: rgba(247, 88, 3, .12);
    color: var(--primary-600, #ea580c);
  }
  #revenueTable_wrapper,
  #packageStatsTable_wrapper {
    padding: 0;
  }
  #revenueTable_wrapper .ipb-dt-top,
  #revenueTable_wrapper .ipb-dt-bottom,
  #packageStatsTable_wrapper .ipb-dt-top,
  #packageStatsTable_wrapper .ipb-dt-bottom {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px 16px;
    padding: 0 4px 12px;
  }
  #revenueTable_wrapper .ipb-dt-bottom,
  #packageStatsTable_wrapper .ipb-dt-bottom {
    padding: 12px 4px 0;
    border-top: 1px solid var(--border, #e2e8f0);
    margin-top: 4px;
  }
  #revenueTable_wrapper .dataTables_length,
  #revenueTable_wrapper .dataTables_info,
  #revenueTable_wrapper .dataTables_paginate,
  #packageStatsTable_wrapper .dataTables_length,
  #packageStatsTable_wrapper .dataTables_info,
  #packageStatsTable_wrapper .dataTables_paginate {
    padding: 0;
    font-size: 13px;
    float: none;
    text-align: inherit;
  }
  #revenueTable_wrapper .dataTables_length label,
  #packageStatsTable_wrapper .dataTables_length label {
    margin: 0;
    font-weight: 600;
    color: var(--text-secondary, #64748b);
  }
  #revenueTable_wrapper .dataTables_paginate .pagination,
  #packageStatsTable_wrapper .dataTables_paginate .pagination {
    margin: 0;
  }
  #revenueTable_wrapper .dataTables_processing,
  #packageStatsTable_wrapper .dataTables_processing {
    background: rgba(255, 255, 255, .95);
    border: 1px solid var(--border, #e2e8f0);
    border-radius: 8px;
    padding: 10px 18px;
    font-weight: 700;
    color: var(--text-secondary, #64748b);
    box-shadow: 0 4px 16px rgba(15, 23, 42, .08);
  }
  @media (max-width: 1024px) {
    .ipb-rev-filter-row-top,
    .ipb-rev-filter-row-bottom {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .ipb-rev-filter-row-bottom .ipb-rev-filter-actions {
      grid-column: 1 / -1;
      justify-content: flex-start;
    }
    .ipb-pkg-filter > div:not(.ipb-pkg-filter-actions) {
      max-width: none;
    }
    .ipb-pkg-filter-actions {
      width: 100%;
      margin-left: 0;
      justify-content: flex-start;
    }
    .ipb-rev-totals {
      grid-template-columns: 1fr;
    }
  }
  @media (max-width: 575px) {
    .ipb-rev-tab-pane {
      padding: 16px 14px 20px;
    }
    .ipb-rev-tabs {
      padding: 0 12px;
    }
    .ipb-rev-tab {
      flex: 1 1 100%;
      justify-content: flex-start;
      border-radius: 10px;
      margin-bottom: 4px;
    }
    .ipb-rev-filter-row-top,
    .ipb-rev-filter-row-bottom {
      grid-template-columns: 1fr;
    }
    .ipb-rev-filter-actions,
    .ipb-pkg-filter-actions {
      width: 100%;
      justify-content: stretch;
    }
    .ipb-rev-filter-actions .btn,
    .ipb-pkg-filter-actions .btn {
      flex: 1;
    }
    .ipb-pkg-filter > div:not(.ipb-pkg-filter-actions) {
      min-width: 100%;
    }
  }
</style>
<?= $this->endSection('css'); ?>

<?= $this->section('content'); ?>
<?php
$summary = is_array($summary ?? null) ? $summary : [];
$packages = is_array($packages ?? null) ? $packages : [];
$sAdmins = is_array($sAdmins ?? null) ? $sAdmins : [];
$months = is_array($months ?? null) ? $months : [];
$years = is_array($years ?? null) ? $years : [];
$preMonth = (string) (service('request')->getGet('month') ?? '');
$preYear = (string) (service('request')->getGet('year') ?? '');
$preStatus = (string) (service('request')->getGet('status') ?? 'successful');
$prePackage = (string) (service('request')->getGet('package_id') ?? '');
$preAdmin = (string) (service('request')->getGet('admin_id') ?? '');
$preName = (string) (service('request')->getGet('name') ?? '');
$packageStats = is_array($packageStats ?? null) ? $packageStats : [];
$pkgPeriodType = (string) ($packageStats['period_type'] ?? 'single');
$pkgPeriodLabel = (string) ($packageStats['period_label'] ?? date('F Y'));
$pkgRows = is_array($packageStats['rows'] ?? null) ? $packageStats['rows'] : [];
$pkgTotals = is_array($packageStats['totals'] ?? null) ? $packageStats['totals'] : [];
$fmt = static function ($n) {
  return '৳' . number_format((float) $n, 0);
};
?>
<div class="content-wrapper">
  <section class="content ipb-saas-list">
    <?= $this->include('components/page-header', [
      'title' => 'Platform Revenue',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Platform Revenue'],
      ],
      'subtitle' => 'Second Admin subscription payments',
    ]); ?>

    <div class="ipb-dash-kpi" style="margin-bottom:16px">
      <div class="ipb-kpi tone-success">
        <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-wallet"></i></span></div>
        <div class="ipb-kpi-value" style="font-size:22px" id="sumTotalAll"><?= esc($fmt($summary['total_all'] ?? 0)); ?></div>
        <div class="ipb-kpi-label">Total revenue</div>
        <div class="ipb-kpi-cta"><span id="sumTotalAllCount"><?= (int) ($summary['total_all_count'] ?? 0); ?></span> payments</div>
      </div>
      <div class="ipb-kpi tone-brand">
        <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-calendar-day"></i></span></div>
        <div class="ipb-kpi-value" style="font-size:22px" id="sumThisMonth"><?= esc($fmt($summary['this_month'] ?? 0)); ?></div>
        <div class="ipb-kpi-label">This month</div>
        <div class="ipb-kpi-cta"><?= esc($summary['this_month_label'] ?? date('F Y')); ?> · <span id="sumThisMonthCount"><?= (int) ($summary['this_month_count'] ?? 0); ?></span></div>
      </div>
      <div class="ipb-kpi tone-navy">
        <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-calendar"></i></span></div>
        <div class="ipb-kpi-value" style="font-size:22px" id="sumLastMonth"><?= esc($fmt($summary['last_month'] ?? 0)); ?></div>
        <div class="ipb-kpi-label">Last month</div>
        <div class="ipb-kpi-cta"><?= esc($summary['last_month_label'] ?? ''); ?> · <span id="sumLastMonthCount"><?= (int) ($summary['last_month_count'] ?? 0); ?></span></div>
      </div>
      <div class="ipb-kpi tone-warning">
        <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-clock"></i></span></div>
        <div class="ipb-kpi-value" style="font-size:22px" id="sumPending"><?= esc($fmt($summary['pending'] ?? 0)); ?></div>
        <div class="ipb-kpi-label">Pending</div>
        <div class="ipb-kpi-cta"><span id="sumPendingCount"><?= (int) ($summary['pending_count'] ?? 0); ?></span> payments</div>
      </div>
    </div>

    <div class="box box-warning ipb-rev-box">
      <div class="ipb-rev-tabs" role="tablist" aria-label="Platform revenue sections">
        <button type="button" class="ipb-rev-tab is-active" role="tab" id="tab-payments" aria-selected="true" aria-controls="pane-payments" data-tab="payments">
          <i class="fa fa-list" aria-hidden="true"></i>
          Payment records
          <span class="ipb-rev-tab-badge" id="tabBadgePayments">—</span>
        </button>
        <button type="button" class="ipb-rev-tab" role="tab" id="tab-packages" aria-selected="false" aria-controls="pane-packages" data-tab="packages">
          <i class="fa fa-box" aria-hidden="true"></i>
          Admins by package
          <span class="ipb-rev-tab-badge" id="tabBadgePackages"><?= (int) ($pkgTotals['occupied'] ?? 0); ?> admins</span>
        </button>
      </div>

      <div class="box-body">
        <!-- Tab: Payment records -->
        <div class="ipb-rev-tab-pane is-active" id="pane-payments" role="tabpanel" aria-labelledby="tab-payments">
          <div class="ipb-rev-section">
            <div class="ipb-rev-section-head">
              <h3 class="ipb-rev-section-title"><i class="fa fa-filter" aria-hidden="true"></i> Filter payments</h3>
            </div>
            <form id="revenueFilterForm" class="ipb-rev-filters" onsubmit="return false;">
              <div class="ipb-rev-filter-row ipb-rev-filter-row-top">
                <div class="ipb-rev-field">
                  <label for="filter_name">Name / email / invoice</label>
                  <input type="text" id="filter_name" name="name" class="form-control ipb-filter-text" value="<?= esc($preName); ?>" placeholder="Search admin or invoice">
                </div>
                <div class="ipb-rev-field">
                  <label for="filter_admin_id">Second Admin</label>
                  <select id="filter_admin_id" name="admin_id" class="form-control ipb-filter-select">
                    <option value="">All admins</option>
                    <?php foreach ($sAdmins as $admin): ?>
                      <option value="<?= (int) $admin->id; ?>" <?= ((string) $preAdmin === (string) $admin->id) ? 'selected' : ''; ?>>
                        <?= esc($admin->name); ?> (<?= esc($admin->email); ?>)
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="ipb-rev-field">
                  <label for="filter_package_id">Package</label>
                  <select id="filter_package_id" name="package_id" class="form-control ipb-filter-select">
                    <option value="">All packages</option>
                    <?php foreach ($packages as $pkg): ?>
                      <?php
                        $pkgId = is_array($pkg) ? ($pkg['id'] ?? 0) : (int) ($pkg->id ?? 0);
                        $pkgName = is_array($pkg) ? ($pkg['package_name'] ?? '') : (string) ($pkg->package_name ?? '');
                      ?>
                      <option value="<?= (int) $pkgId; ?>" <?= ((string) $prePackage === (string) $pkgId) ? 'selected' : ''; ?>>
                        <?= esc($pkgName); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="ipb-rev-filter-row ipb-rev-filter-row-bottom">
                <div class="ipb-rev-field">
                  <label for="filter_month">Month</label>
                  <select id="filter_month" name="month" class="form-control ipb-filter-select">
                    <option value="">All months</option>
                    <?php foreach ($months as $month): ?>
                      <option value="<?= esc($month); ?>" <?= ($preMonth === $month) ? 'selected' : ''; ?>><?= esc($month); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="ipb-rev-field">
                  <label for="filter_year">Year</label>
                  <select id="filter_year" name="year" class="form-control ipb-filter-select">
                    <option value="">All years</option>
                    <?php foreach ($years as $year): ?>
                      <option value="<?= (int) $year; ?>" <?= ((string) $preYear === (string) $year) ? 'selected' : ''; ?>><?= (int) $year; ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="ipb-rev-field">
                  <label for="filter_status">Status</label>
                  <select id="filter_status" name="status" class="form-control ipb-filter-select">
                    <option value="successful" <?= ($preStatus === 'successful') ? 'selected' : ''; ?>>Successful</option>
                    <option value="pending" <?= ($preStatus === 'pending') ? 'selected' : ''; ?>>Pending</option>
                    <option value="all" <?= ($preStatus === 'all') ? 'selected' : ''; ?>>All</option>
                  </select>
                </div>
                <div class="ipb-rev-filter-actions">
                  <button type="button" id="applyRevenueFilter" class="btn btn-primary">
                    <i class="fa fa-search" aria-hidden="true"></i> Apply
                  </button>
                  <button type="button" id="clearRevenueFilter" class="btn btn-default">Clear</button>
                </div>
              </div>
            </form>
          </div>

          <div class="ipb-rev-section">
            <div class="ipb-rev-section-head">
              <h3 class="ipb-rev-section-title"><i class="fa fa-chart-bar" aria-hidden="true"></i> Filtered results</h3>
            </div>
            <div class="ipb-rev-totals" id="filteredTotals">
              <div class="ipb-rev-total-chip">
                <span>Filtered total</span>
                <strong id="filteredAmount">৳0</strong>
              </div>
              <div class="ipb-rev-total-chip">
                <span>Payments</span>
                <strong id="filteredCount">0</strong>
              </div>
              <div class="ipb-rev-total-chip">
                <span>Admins</span>
                <strong id="filteredAdmins">0</strong>
              </div>
            </div>
          </div>

          <div class="ipb-rev-section ipb-rev-section-table">
            <div class="ipb-rev-section-head">
              <h3 class="ipb-rev-section-title"><i class="fa fa-table" aria-hidden="true"></i> Payment list</h3>
            </div>
            <div class="ipb-rev-table-wrap table-responsive">
            <table class="table table-bordered table-striped" id="revenueTable" width="100%">
              <caption class="sr-only">Payment records</caption>
              <thead>
                <tr>
                  <th scope="col">#</th>
                  <th scope="col">Invoice</th>
                  <th scope="col">Admin name</th>
                  <th scope="col">Email</th>
                  <th scope="col">Package</th>
                  <th scope="col">Month</th>
                  <th scope="col">Amount</th>
                  <th scope="col">Paid via</th>
                  <th scope="col">Status</th>
                  <th scope="col">Paid at</th>
                </tr>
              </thead>
              <?php
                // Zero-blank-frame first paint: skeleton rows show before
                // JS/DataTables boots; DataTables replaces this <tbody> on its first draw.
                $revenueSkeletonCols = 10;
              ?>
              <?= view('components/skeleton-table', ['cols' => $revenueSkeletonCols, 'rows' => 8]) ?>
            </table>
            </div>
          </div>
        </div>

        <!-- Tab: Admins by package -->
        <div class="ipb-rev-tab-pane" id="pane-packages" role="tabpanel" aria-labelledby="tab-packages" hidden>
          <div class="ipb-rev-section">
            <p class="ipb-pkg-period-label" id="pkgPeriodSummary">
              <i class="fa fa-info-circle" aria-hidden="true"></i>
              Occupied now = current plan assignment · Period = subscription payments in selected dates
              · <strong><?= esc($pkgPeriodLabel); ?></strong>
            </p>

            <div class="ipb-rev-section-head">
              <h3 class="ipb-rev-section-title"><i class="fa fa-filter" aria-hidden="true"></i> Filter by period</h3>
            </div>
            <form id="packageStatsFilter" class="ipb-pkg-filter" onsubmit="return false;">
            <div>
              <label for="pkg_period_type">Period</label>
              <select id="pkg_period_type" name="period_type" class="form-control">
                <option value="single" <?= $pkgPeriodType === 'single' ? 'selected' : ''; ?>>Single month</option>
                <option value="range" <?= $pkgPeriodType === 'range' ? 'selected' : ''; ?>>Month range</option>
                <option value="all" <?= $pkgPeriodType === 'all' ? 'selected' : ''; ?>>All time</option>
              </select>
            </div>
            <div class="ipb-pkg-single-fields">
              <label for="pkg_month">Month</label>
              <select id="pkg_month" name="month" class="form-control">
                <option value="">All months</option>
                <?php foreach ($months as $month): ?>
                  <option value="<?= esc($month); ?>" <?= ($month === date('F')) ? 'selected' : ''; ?>><?= esc($month); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="ipb-pkg-single-fields">
              <label for="pkg_year">Year</label>
              <select id="pkg_year" name="year" class="form-control">
                <?php foreach ($years as $year): ?>
                  <option value="<?= (int) $year; ?>" <?= ((int) $year === (int) date('Y')) ? 'selected' : ''; ?>><?= (int) $year; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="ipb-pkg-range-fields">
              <label for="pkg_from_month">From month</label>
              <select id="pkg_from_month" name="from_month" class="form-control">
                <option value="">Month</option>
                <?php foreach ($months as $month): ?>
                  <option value="<?= esc($month); ?>"><?= esc($month); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="ipb-pkg-range-fields">
              <label for="pkg_from_year">From year</label>
              <select id="pkg_from_year" name="from_year" class="form-control">
                <option value="">Year</option>
                <?php foreach ($years as $year): ?>
                  <option value="<?= (int) $year; ?>"><?= (int) $year; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="ipb-pkg-range-fields">
              <label for="pkg_to_month">To month</label>
              <select id="pkg_to_month" name="to_month" class="form-control">
                <option value="">Month</option>
                <?php foreach ($months as $month): ?>
                  <option value="<?= esc($month); ?>"><?= esc($month); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="ipb-pkg-range-fields">
              <label for="pkg_to_year">To year</label>
              <select id="pkg_to_year" name="to_year" class="form-control">
                <option value="">Year</option>
                <?php foreach ($years as $year): ?>
                  <option value="<?= (int) $year; ?>"><?= (int) $year; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label for="pkg_status">Payment status</label>
              <select id="pkg_status" name="status" class="form-control">
                <option value="successful">Successful</option>
                <option value="pending">Pending</option>
                <option value="all">All</option>
              </select>
            </div>
            <div class="ipb-pkg-filter-actions">
              <button type="button" class="btn btn-primary" id="applyPackageStats">
                <i class="fa fa-search" aria-hidden="true"></i> Apply
              </button>
              <button type="button" class="btn btn-default" id="resetPackageStats">This month</button>
            </div>
          </form>
          </div>

          <div class="ipb-rev-section">
            <div class="ipb-rev-section-head">
              <h3 class="ipb-rev-section-title"><i class="fa fa-chart-bar" aria-hidden="true"></i> Period summary</h3>
            </div>
            <div class="ipb-rev-totals" id="pkgTotalsBar">
              <div class="ipb-rev-total-chip">
                <span>Occupied now</span>
                <strong id="pkgTotalOccupied"><?= (int) ($pkgTotals['occupied'] ?? 0); ?></strong>
              </div>
              <div class="ipb-rev-total-chip">
                <span>Payments in period</span>
                <strong id="pkgTotalPayments"><?= (int) ($pkgTotals['payment_count'] ?? 0); ?></strong>
              </div>
              <div class="ipb-rev-total-chip">
                <span>Period revenue</span>
                <strong id="pkgTotalAmount"><?= esc($fmt($pkgTotals['total_amount'] ?? 0)); ?></strong>
              </div>
            </div>
          </div>

          <div class="ipb-rev-section ipb-rev-section-table">
            <div class="ipb-rev-section-head">
              <h3 class="ipb-rev-section-title"><i class="fa fa-table" aria-hidden="true"></i> Package breakdown</h3>
            </div>
            <div class="ipb-rev-table-wrap table-responsive">
            <table class="table table-bordered table-striped" id="packageStatsTable" width="100%">
              <caption class="sr-only">Package breakdown</caption>
              <thead>
                <tr>
                  <th scope="col">#</th>
                  <th scope="col">Package</th>
                  <th scope="col">Occupied now</th>
                  <th scope="col">Period payments</th>
                  <th scope="col">Admins paid</th>
                  <th scope="col">Period amount</th>
                </tr>
              </thead>
              <tbody id="packageStatsTableBody">
                <?php if (empty($pkgRows)): ?>
                  <tr><td colspan="6" class="text-center text-muted">No package data for this period.</td></tr>
                <?php else: ?>
                  <?php foreach ($pkgRows as $i => $pkg): ?>
                    <tr>
                      <td><?= (int) ($i + 1); ?></td>
                      <td><strong><?= esc($pkg['package_name'] ?? 'No package'); ?></strong></td>
                      <td><?= (int) ($pkg['occupied_count'] ?? 0); ?></td>
                      <td><?= (int) ($pkg['payment_count'] ?? 0); ?></td>
                      <td><?= (int) ($pkg['admin_count'] ?? 0); ?></td>
                      <td><strong><?= esc($fmt($pkg['total_amount'] ?? 0)); ?></strong></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
              <?php if (!empty($pkgRows)): ?>
                <tfoot>
                  <tr style="font-weight:800;background:var(--surface-2,#f8fafc)">
                    <td colspan="2">Total</td>
                    <td id="pkgFootOccupied"><?= (int) ($pkgTotals['occupied'] ?? 0); ?></td>
                    <td id="pkgFootPayments"><?= (int) ($pkgTotals['payment_count'] ?? 0); ?></td>
                    <td id="pkgFootAdmins"><?= (int) ($pkgTotals['admin_count'] ?? 0); ?></td>
                    <td id="pkgFootAmount"><?= esc($fmt($pkgTotals['total_amount'] ?? 0)); ?></td>
                  </tr>
                </tfoot>
              <?php endif; ?>
            </table>
            </div>
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
  function formatTaka(amount) {
    return '৳' + (Number(amount) || 0).toLocaleString('en-US', { maximumFractionDigits: 0 });
  }

  function escapeHtml(str) {
    return String(str == null ? '' : str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function statusBadge(status) {
    var s = String(status || '').toLowerCase();
    if (s === 'successful') return '<span class="ipb-pay-badge is-success">Successful</span>';
    if (s === 'pending') return '<span class="ipb-pay-badge is-warning">Pending</span>';
    return '<span class="ipb-pay-badge">' + escapeHtml(status || '--') + '</span>';
  }

  function applyRevenueSummary(json) {
    var totals = (json && json.totals) || {};
    var summary = (json && json.summary) || {};

    $('#filteredAmount').text(formatTaka(totals.total_amount || 0));
    $('#filteredCount').text(String(totals.total_count || 0));
    $('#filteredAdmins').text(String(totals.admin_count || 0));
    $('#tabBadgePayments').text(String(totals.total_count || 0) + ' rows');

    if (summary.total_all != null) $('#sumTotalAll').text(formatTaka(summary.total_all));
    if (summary.total_all_count != null) $('#sumTotalAllCount').text(summary.total_all_count);
    if (summary.this_month != null) $('#sumThisMonth').text(formatTaka(summary.this_month));
    if (summary.this_month_count != null) $('#sumThisMonthCount').text(summary.this_month_count);
    if (summary.last_month != null) $('#sumLastMonth').text(formatTaka(summary.last_month));
    if (summary.last_month_count != null) $('#sumLastMonthCount').text(summary.last_month_count);
    if (summary.pending != null) $('#sumPending').text(formatTaka(summary.pending));
    if (summary.pending_count != null) $('#sumPendingCount').text(summary.pending_count);
  }

  function getFilters() {
    return {
      name: $('#filter_name').val() || '',
      admin_id: $('#filter_admin_id').val() || '',
      package_id: $('#filter_package_id').val() || '',
      month: $('#filter_month').val() || '',
      year: $('#filter_year').val() || '',
      status: $('#filter_status').val() || 'successful'
    };
  }

  var revenueTable = null;

  function initRevenueTable() {
    if ($.fn.DataTable.isDataTable('#revenueTable')) {
      return revenueTable;
    }

    revenueTable = $('#revenueTable').DataTable({
      serverSide: true,
      processing: false,
      searching: false,
      pageLength: 25,
      lengthMenu: [[25, 50, 100, 250, 500], [25, 50, 100, 250, 500]],
      order: [[0, 'desc']],
      dom: '<"ipb-dt-top"lf>rt<"ipb-dt-bottom"ip>',
      language: {
        lengthMenu: 'Show _MENU_',
        info: 'Showing _START_ to _END_ of _TOTAL_ payments',
        infoEmpty: 'No payments match these filters',
        infoFiltered: '(filtered from _MAX_ total)',
        paginate: { previous: 'Prev', next: 'Next' },
        processing: 'Loading payments…',
        zeroRecords: 'No payments match these filters'
      },
      ajax: {
        url: '<?= route_to("route.Admin.revenue.fetch"); ?>',
        type: 'POST',
        data: function (d) {
          var filters = getFilters();
          d.name = filters.name;
          d.admin_id = filters.admin_id;
          d.package_id = filters.package_id;
          d.month = filters.month;
          d.year = filters.year;
          d.status = filters.status;
          d.<?= csrf_token() ?> = '<?= csrf_hash() ?>';
        },
        beforeSend: function (req) {
          req.setRequestHeader('<?= csrf_header() ?>', '<?= csrf_hash() ?>');
        },
        dataSrc: function (json) {
          applyRevenueSummary(json);
          return json.data || [];
        }
      },
      columns: [
        {
          data: 'id',
          orderable: true,
          render: function (data, type, row, meta) {
            return meta.settings._iDisplayStart + meta.row + 1;
          }
        },
        { data: 'invoice', defaultContent: '--', render: function (d) { return escapeHtml(d || '--'); } },
        { data: 'admin_name', defaultContent: '--', render: function (d) { return escapeHtml(d || '--'); } },
        { data: 'admin_email', defaultContent: '--', render: function (d) { return escapeHtml(d || '--'); } },
        { data: 'package_name', defaultContent: 'No package', render: function (d) { return escapeHtml(d || 'No package'); } },
        { data: 'month', defaultContent: '--', render: function (d) { return escapeHtml(d || '--'); } },
        {
          data: 'amount',
          render: function (d) { return '<strong>' + formatTaka(d) + '</strong>'; }
        },
        { data: 'paid_via', defaultContent: '--', render: function (d) { return escapeHtml(d || '--'); } },
        { data: 'status', orderable: true, render: function (d) { return statusBadge(d); } },
        {
          data: 'paid_at',
          render: function (d, type, row) {
            var paidAt = d || row.created_at || '';
            return escapeHtml(paidAt || '--');
          }
        }
      ]
    });

    return revenueTable;
  }

  function reloadRevenueTable() {
    if (!revenueTable) {
      initRevenueTable();
    } else {
      revenueTable.ajax.reload();
    }
  }

  $('#applyRevenueFilter').on('click', reloadRevenueTable);
  $('#clearRevenueFilter').on('click', function () {
    $('#filter_name').val('');
    $('#filter_admin_id').val('');
    $('#filter_package_id').val('');
    $('#filter_month').val('');
    $('#filter_year').val('');
    $('#filter_status').val('successful');
    reloadRevenueTable();
  });

  $('#revenueFilterForm').on('keydown', function (e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      reloadRevenueTable();
    }
  });

  if (window.IpbFilters) {
    IpbFilters.restore({ storageKey: 'ipb_filters_revenue_payments', root: '#revenueFilterForm' });
  }

  initRevenueTable();

  if (window.IpbFilters) {
    IpbFilters.bind(revenueTable, {
      storageKey: 'ipb_filters_revenue_payments',
      root: '#revenueFilterForm',
      resetBtn: '#clearRevenueFilter',
    });
  }

  function switchTab(tabId) {
    var id = tabId === 'packages' ? 'packages' : 'payments';
    $('.ipb-rev-tab').each(function () {
      var active = $(this).data('tab') === id;
      $(this).toggleClass('is-active', active).attr('aria-selected', active ? 'true' : 'false');
    });
    $('#pane-payments').toggleClass('is-active', id === 'payments').prop('hidden', id !== 'payments');
    $('#pane-packages').toggleClass('is-active', id === 'packages').prop('hidden', id !== 'packages');
    try { sessionStorage.setItem('ipbRevenueTab', id); } catch (e) {}
    if (window.location.hash !== '#' + id) {
      history.replaceState(null, '', '#' + id);
    }
    if (id === 'payments' && revenueTable) {
      revenueTable.columns.adjust();
    }
  }

  $('.ipb-rev-tab').on('click', function () {
    switchTab($(this).data('tab'));
  });

  (function initTabFromHash() {
    var hash = (window.location.hash || '').replace('#', '');
    var stored = '';
    try { stored = sessionStorage.getItem('ipbRevenueTab') || ''; } catch (e) {}
    if (hash === 'packages' || hash === 'payments') {
      switchTab(hash);
    } else if (stored === 'packages') {
      switchTab('packages');
    }
  })();

  function togglePackagePeriodFields() {
    var type = $('#pkg_period_type').val();
    $('.ipb-pkg-single-fields').toggle(type === 'single');
    $('.ipb-pkg-range-fields').toggle(type === 'range');
  }

  // Rehydrate package-stats filters across a reload (sessionStorage) — not
  // IpbFilters.bind()-driven since this table is destroyed/recreated on
  // every fetch (renderPackageStats()), not a persistent DataTables .draw()
  // target. Only sets values a stored session actually provided, so the
  // server-rendered "current month" defaults still apply on a first visit.
  (function restorePackageFilters() {
    if (!window.sessionStorage) return;
    try {
      var storedPkgFilters = sessionStorage.getItem('ipb_filters_revenue_packages');
      if (!storedPkgFilters) return;
      var parsedPkgFilters = JSON.parse(storedPkgFilters);
      if (parsedPkgFilters.period_type) $('#pkg_period_type').val(parsedPkgFilters.period_type);
      if (parsedPkgFilters.month) $('#pkg_month').val(parsedPkgFilters.month);
      if (parsedPkgFilters.year) $('#pkg_year').val(parsedPkgFilters.year);
      if (parsedPkgFilters.from_month) $('#pkg_from_month').val(parsedPkgFilters.from_month);
      if (parsedPkgFilters.from_year) $('#pkg_from_year').val(parsedPkgFilters.from_year);
      if (parsedPkgFilters.to_month) $('#pkg_to_month').val(parsedPkgFilters.to_month);
      if (parsedPkgFilters.to_year) $('#pkg_to_year').val(parsedPkgFilters.to_year);
      if (parsedPkgFilters.status) $('#pkg_status').val(parsedPkgFilters.status);
      togglePackagePeriodFields();
    } catch (e) { /* corrupt/absent storage — ignore */ }
  })();

  function getPackageFilters() {
    return {
      period_type: $('#pkg_period_type').val() || 'single',
      month: $('#pkg_month').val() || '',
      year: $('#pkg_year').val() || '',
      from_month: $('#pkg_from_month').val() || '',
      from_year: $('#pkg_from_year').val() || '',
      to_month: $('#pkg_to_month').val() || '',
      to_year: $('#pkg_to_year').val() || '',
      status: $('#pkg_status').val() || 'successful'
    };
  }

  var packageStatsTable = null;

  function initPackageStatsTable() {
    if ($.fn.DataTable.isDataTable('#packageStatsTable')) {
      packageStatsTable.destroy();
      packageStatsTable = null;
    }

    packageStatsTable = $('#packageStatsTable').DataTable({
      paging: true,
      searching: false,
      pageLength: 10,
      lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'All']],
      order: [[2, 'desc']],
      dom: '<"ipb-dt-top"lf>rt<"ipb-dt-bottom"ip>',
      info: true,
      language: {
        lengthMenu: 'Show _MENU_ packages',
        info: 'Showing _START_ to _END_ of _TOTAL_ packages',
        infoEmpty: 'No package data for this period',
        paginate: { previous: 'Prev', next: 'Next' },
        zeroRecords: 'No package data for this period'
      },
      columnDefs: [
        { orderable: false, targets: 0 }
      ]
    });
  }

  function renderPackageStats(stats) {
    if (!stats) return;

    if ($.fn.DataTable.isDataTable('#packageStatsTable')) {
      $('#packageStatsTable').DataTable().destroy();
      packageStatsTable = null;
    }

    var rows = stats.rows || [];
    var totals = stats.totals || {};
    $('#pkgPeriodSummary').html(
      '<i class="fa fa-info-circle" aria-hidden="true"></i> ' +
      'Occupied now = current plan assignment · Period = subscription payments in selected dates · ' +
      '<strong>' + escapeHtml(stats.period_label || '—') + '</strong>'
    );
    $('#pkgTotalOccupied').text(String(totals.occupied || 0));
    $('#pkgTotalPayments').text(String(totals.payment_count || 0));
    $('#pkgTotalAmount').text(formatTaka(totals.total_amount || 0));
    $('#tabBadgePackages').text(String(totals.occupied || 0) + ' admins');

    if (!rows.length) {
      $('#packageStatsTableBody').html('<tr><td colspan="6" class="text-center text-muted">No package data for this period.</td></tr>');
      $('#packageStatsTable tfoot').remove();
      return;
    }

    var bodyHtml = rows.map(function (pkg, idx) {
      return '<tr>' +
        '<td>' + (idx + 1) + '</td>' +
        '<td><strong>' + escapeHtml(pkg.package_name || 'No package') + '</strong></td>' +
        '<td>' + (parseInt(pkg.occupied_count, 10) || 0) + '</td>' +
        '<td>' + (parseInt(pkg.payment_count, 10) || 0) + '</td>' +
        '<td>' + (parseInt(pkg.admin_count, 10) || 0) + '</td>' +
        '<td><strong>' + formatTaka(pkg.total_amount || 0) + '</strong></td>' +
      '</tr>';
    }).join('');

    $('#packageStatsTableBody').html(bodyHtml);

    var footHtml = '<tfoot><tr style="font-weight:800;background:var(--surface-2,#f8fafc)">' +
      '<td colspan="2">Total</td>' +
      '<td id="pkgFootOccupied">' + (totals.occupied || 0) + '</td>' +
      '<td id="pkgFootPayments">' + (totals.payment_count || 0) + '</td>' +
      '<td id="pkgFootAdmins">' + (totals.admin_count || 0) + '</td>' +
      '<td id="pkgFootAmount">' + formatTaka(totals.total_amount || 0) + '</td>' +
      '</tr></tfoot>';
    var $table = $('#packageStatsTable');
    $table.find('tfoot').remove();
    $table.append(footHtml);

    initPackageStatsTable();
  }

  function loadPackageStats() {
    $('#packageStatsTableBody').html('<tr><td colspan="6" class="text-center text-muted">Loading…</td></tr>');
    $('#packageStatsTable tfoot').remove();
    var pkgFiltersForPersist = getPackageFilters();
    if (window.sessionStorage) {
      try { sessionStorage.setItem('ipb_filters_revenue_packages', JSON.stringify(pkgFiltersForPersist)); } catch (e) { /* quota / private mode */ }
    }
    $.ajax({
      url: '<?= route_to("route.Admin.revenue.packageStats"); ?>',
      type: 'POST',
      data: getPackageFilters(),
      headers: { '<?= csrf_header() ?>': '<?= csrf_hash() ?>' },
      success: function (res) {
        if (res && res.status === 'success' && res.package_stats) {
          renderPackageStats(res.package_stats);
        } else {
          $('#packageStatsTableBody').html('<tr><td colspan="6" class="text-center text-danger">Failed to load</td></tr>');
        }
      },
      error: function () {
        $('#packageStatsTableBody').html('<tr><td colspan="6" class="text-center text-danger">Failed to load</td></tr>');
      }
    });
  }

  $('#pkg_period_type').on('change', togglePackagePeriodFields);
  $('#applyPackageStats').on('click', loadPackageStats);
  $('#resetPackageStats').on('click', function () {
    $('#pkg_period_type').val('single');
    $('#pkg_month').val('<?= esc(date('F')); ?>');
    $('#pkg_year').val('<?= (int) date('Y'); ?>');
    $('#pkg_from_month, #pkg_to_month, #pkg_from_year, #pkg_to_year').val('');
    $('#pkg_status').val('successful');
    togglePackagePeriodFields();
    loadPackageStats();
  });

  togglePackagePeriodFields();
  initPackageStatsTable();
})();
</script>
<?= $this->endSection('script'); ?>
