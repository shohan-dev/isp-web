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

    <div class="ipb-dash fade-in" data-ipb-dashboard="employee">
      <div class="ipb-dash-toolbar">
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
        <?php if (userHasPermission('customer', 'read')): ?>
          <a href="<?= route_to('route.customer'); ?>" class="ipb-kpi tone-brand">
            <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-users"></i></span></div>
            <div class="ipb-kpi-value"><?= (int) $total_area_customers_active; ?></div>
            <div class="ipb-kpi-label">Customers in area</div>
            <div class="ipb-kpi-cta">View details <i class="fa fa-chevron-right"></i></div>
          </a>
          <a href="<?= route_to('route.expired_customer'); ?>" class="ipb-kpi tone-error">
            <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-user-slash"></i></span></div>
            <div class="ipb-kpi-value"><?= (int) $total_area_customers_inactive; ?></div>
            <div class="ipb-kpi-label">Inactive / expired</div>
            <div class="ipb-kpi-cta">View details <i class="fa fa-chevron-right"></i></div>
          </a>
        <?php else: ?>
          <div class="ipb-kpi tone-brand">
            <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-users"></i></span></div>
            <div class="ipb-kpi-value"><?= (int) $total_area_customers_active; ?></div>
            <div class="ipb-kpi-label">Customers in area</div>
          </div>
          <div class="ipb-kpi tone-error">
            <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-user-slash"></i></span></div>
            <div class="ipb-kpi-value"><?= (int) $total_area_customers_inactive; ?></div>
            <div class="ipb-kpi-label">Inactive / expired</div>
          </div>
        <?php endif; ?>

        <?php if (userHasPermission('payment', 'read') || userHasPermission('employee_payment', 'read')): ?>
          <a href="<?= route_to('route.employee.payment'); ?>" class="ipb-kpi tone-success">
            <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-wallet"></i></span></div>
            <div class="ipb-kpi-value">৳<?= number_format((float) $payment_received); ?></div>
            <div class="ipb-kpi-label">Payment received</div>
            <div class="ipb-kpi-cta">View details <i class="fa fa-chevron-right"></i></div>
          </a>
          <a href="<?= route_to('route.employee.payment'); ?>" class="ipb-kpi tone-warning">
            <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-clock"></i></span></div>
            <div class="ipb-kpi-value">৳<?= number_format((float) $payment_pending); ?></div>
            <div class="ipb-kpi-label">Payment pending</div>
            <div class="ipb-kpi-cta">View details <i class="fa fa-chevron-right"></i></div>
          </a>
        <?php else: ?>
          <div class="ipb-kpi tone-success">
            <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-wallet"></i></span></div>
            <div class="ipb-kpi-value">৳<?= number_format((float) $payment_received); ?></div>
            <div class="ipb-kpi-label">Payment received</div>
          </div>
          <div class="ipb-kpi tone-warning">
            <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-clock"></i></span></div>
            <div class="ipb-kpi-value">৳<?= number_format((float) $payment_pending); ?></div>
            <div class="ipb-kpi-label">Payment pending</div>
          </div>
        <?php endif; ?>
      </div>
      </div>

      <div class="ipb-widget" data-widget-id="visits" data-size="half" data-title="Today's visits" data-icon="fa-solid fa-clipboard-list">
      <div class="ipb-card">
        <div class="ipb-card-head">
          <div>
            <div class="ipb-card-title">Today's visits</div>
            <div class="ipb-card-sub">Field installation and repair tasks</div>
          </div>
        </div>
        <?= $this->include('components/empty-state', [
          'icon' => 'fa fa-clipboard-list',
          'title' => 'No field visits scheduled',
          'subtitle' => 'New installation and repair tasks assigned to you will appear here.',
        ]); ?>
      </div>
      </div>

      <div class="ipb-widget" data-widget-id="paymentReport" data-size="half" data-title="Payment Report" data-icon="fa-solid fa-chart-column">
      <div class="ipb-card">
        <div class="ipb-card-head">
          <div>
            <div class="ipb-card-title">Payment Report</div>
            <div class="ipb-card-sub">Jan – <?= date('M Y'); ?></div>
          </div>
        </div>
        <div id="payment_chart"></div>
      </div>
      </div>
      </div>
    </div>
  </section>
</div>
<?= $this->endSection('content'); ?>

<?= $this->section('script'); ?>
<script>
document.addEventListener("DOMContentLoaded", () => {
  const p = window.IpbTheme.chartPalette();
  const paymentChart = new ApexCharts(document.querySelector("#payment_chart"), {
    series: [{
        name: 'Successful',
        data: [<?= '"' . implode('","', $statistics["successful"]) . '"' ?>],
      },
      {
        name: 'Pending',
        data: [<?= '"' . implode('","', $statistics["pending"]) . '"' ?>]
      },
      {
        name: 'Failed',
        data: [<?= '"' . implode('","', $statistics["failed"]) . '"' ?>]
      }
    ],
    chart: {
      height: 300,
      type: 'bar',
      toolbar: { show: false },
      fontFamily: 'Satoshi, sans-serif',
    },
    colors: ['#16a34a', '#d97706', '#dc2626'],
    dataLabels: { enabled: false },
    plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
    legend: { position: 'top', horizontalAlign: 'right', labels: { colors: p.ink } },
    tooltip: {
      shared: true,
      intersect: false,
      y: { formatter: function (val) { return "৳ " + val + " BDT"; } }
    },
    xaxis: {
      categories: [<?= '"' . implode('","', $statistics["months"]) . '"' ?>],
      axisBorder: { show: false },
      labels: { style: { colors: p.axis } },
    },
    yaxis: {
      labels: {
        formatter: function (val) { return parseFloat(val).toFixed(0); },
        style: { colors: p.axis }
      }
    },
    grid: { borderColor: p.grid, strokeDashArray: 4 },
    // Phone: axis titles off, legend under the plot, money axis compacted.
    responsive: window.IpbUI
      ? window.IpbUI.chartResponsive('bar', { yaxis: { labels: { formatter: window.IpbUI.compactNumber } } })
      : []
  });
  window.IpbTheme.registerChart(paymentChart);
  paymentChart.render();
});
</script>
<?= $this->endSection('script'); ?>
