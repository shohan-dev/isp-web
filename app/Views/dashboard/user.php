<?php
$isActive = ($details->subscription_status ?? 'inactive') === 'active';
$packageName = $package->package_name ?? 'No Package';
$packagePrice = $package->price ?? 0;
$daysLeft = '--';
$daysLeftNum = null;
if (!empty($details->will_expire)) {
  $diff = (int) floor((strtotime($details->will_expire) - time()) / 86400);
  $daysLeftNum = $diff >= 0 ? $diff : 0;
  $daysLeft = $daysLeftNum;
}
$bandwidth = $package->bandwidth ?? ($package->speed ?? '—');
$whatsapp = !empty($admin_details->whatsapp_number) ? $admin_details->whatsapp_number : ($admin_details->mobile ?? '');
$whatsappClean = preg_replace('/\D+/', '', (string) $whatsapp);
$expireLabel = !empty($details->will_expire) ? date('d M Y', strtotime($details->will_expire)) : '—';
$isExpiringSoon = is_numeric($daysLeftNum) && $daysLeftNum <= 7 && $daysLeftNum > 0;
$userId = (int) ($details->id ?? $details['id'] ?? 0);
?>
<?= $this->extend('layout/main-layout'); ?>

<?= $this->section('css'); ?>
<?= saas_css('dashboard.css') ?>
<style>
  .ipb-udash-hero {
    display: grid;
    grid-template-columns: 1.25fr 0.95fr;
    gap: 16px;
  }

  .ipb-udash-plan {
    position: relative;
    overflow: hidden;
    padding: 24px;
    border-radius: var(--radius-lg, 14px);
    background:
      radial-gradient(circle at 100% 0%, rgba(255, 255, 255, 0.14), transparent 42%),
      linear-gradient(145deg, var(--secondary-700), var(--secondary-900));
    color: #fff;
    box-shadow: var(--shadow-2);
    height: 100%;
  }

  .ipb-udash-plan::before {
    content: "";
    position: absolute;
    left: 0;
    top: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--primary-500), #ffb38a);
  }

  .ipb-udash-plan-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
  }

  .ipb-udash-plan-label {
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    opacity: 0.72;
  }

  .ipb-udash-plan-name {
    margin-top: 4px;
    font-size: clamp(22px, 3vw, 28px);
    font-weight: 800;
    letter-spacing: -0.03em;
    line-height: 1.15;
  }

  .ipb-udash-plan-welcome {
    margin-top: 8px;
    font-size: 13px;
    font-weight: 600;
    opacity: 0.82;
  }

  .ipb-udash-status {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 6px 12px;
    border-radius: 999px;
    font-size: 11.5px;
    font-weight: 800;
    background: rgba(255, 255, 255, 0.14);
    border: 1px solid rgba(255, 255, 255, 0.18);
    white-space: nowrap;
  }

  .ipb-udash-status .dot {
    width: 8px;
    height: 8px;
    border-radius: 999px;
    background: #4ade80;
  }

  .ipb-udash-status.is-off .dot {
    background: #f87171;
  }

  .ipb-udash-meta {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
    margin-top: 22px;
  }

  .ipb-udash-meta-item {
    padding: 12px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.1);
    min-width: 0;
  }

  .ipb-udash-meta-item span {
    display: block;
    font-size: 11px;
    font-weight: 700;
    opacity: 0.7;
    text-transform: uppercase;
    letter-spacing: 0.04em;
  }

  .ipb-udash-meta-item strong {
    display: block;
    margin-top: 4px;
    font-size: 16px;
    font-weight: 800;
    line-height: 1.2;
  }

  .ipb-udash-meta-item strong.is-warn {
    color: #fde68a;
  }

  .ipb-udash-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 20px;
  }

  .ipb-udash-pay {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 44px;
    padding: 0 18px;
    border-radius: 10px;
    background: var(--primary-500);
    color: #fff !important;
    font-weight: 800;
    text-decoration: none !important;
    box-shadow: var(--shadow-brand);
  }

  .ipb-udash-pay:hover {
    background: var(--primary-600);
    color: #fff !important;
  }

  .ipb-udash-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 44px;
    padding: 0 16px;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.18);
    color: #fff !important;
    font-weight: 700;
    text-decoration: none !important;
  }

  .ipb-udash-link:hover {
    background: rgba(255, 255, 255, 0.16);
    color: #fff !important;
  }

  .ipb-udash-usage {
    height: 100%;
  }

  .ipb-udash-usage #bandwidth_chart {
    min-height: 240px;
  }

  .ipb-udash-notice,
  .ipb-udash-support-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid var(--border);
  }

  .ipb-udash-notice:last-child,
  .ipb-udash-support-item:last-child {
    border-bottom: 0;
  }

  .ipb-udash-ic {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    background: var(--primary-50);
    color: var(--primary-600);
  }

  .ipb-udash-notice-title {
    font-weight: 800;
    font-size: 13.5px;
    color: var(--text-primary);
  }

  .ipb-udash-notice-date {
    margin-top: 2px;
    font-size: 12px;
    font-weight: 600;
    color: var(--text-muted);
  }

  .ipb-udash-support-item a {
    font-weight: 700;
    color: var(--text-primary);
    text-decoration: none;
  }

  .ipb-udash-support-item a:hover {
    color: var(--primary-600);
  }

  .ipb-udash-support-item .wa {
    color: #16a34a;
    background: #ecfdf3;
  }

  .ipb-udash-more {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-top: 8px;
    font-size: 12.5px;
    font-weight: 800;
    color: var(--primary-600);
    text-decoration: none;
  }

  .ipb-udash-more:hover {
    text-decoration: underline;
  }

  .whatsapp-float {
    position: fixed;
    bottom: max(20px, env(safe-area-inset-bottom));
    right: max(16px, env(safe-area-inset-right));
    z-index: var(--z-fab, 990);
    width: 52px;
    height: 52px;
    border-radius: 999px;
    background: #25d366;
    color: #fff !important;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 26px;
    box-shadow: var(--shadow-2);
    text-decoration: none !important;
  }

  @media (max-width: 1024px) {
    .ipb-udash-hero {
      grid-template-columns: 1fr;
    }
  }

  @media (max-width: 767px) {
    .ipb-udash-meta {
      grid-template-columns: 1fr;
    }

    .ipb-udash-actions {
      flex-direction: column;
    }

    .ipb-udash-pay,
    .ipb-udash-link {
      width: 100%;
    }

    .ipb-udash-plan {
      padding: 18px;
    }
  }
</style>
<?= $this->endSection('css'); ?>

<?= $this->section('content'); ?>
<div class="content-wrapper">
  <section class="content">
    <?= $this->include('components/page-header', [
      'title' => 'Dashboard',
      'subtitle' => 'Welcome back, ' . esc($details->name ?? 'Customer'),
      'breadcrumb' => [
        ['label' => 'Home', 'url' => route_to('route.dashboard')],
        ['label' => 'Dashboard'],
      ],
    ]); ?>

    <div class="ipb-dash fade-in" data-ipb-dashboard="user">
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

        <div class="ipb-widget" data-widget-id="plan" data-size="full" data-title="Current plan & usage" data-icon="fa-solid fa-crown">
          <div class="ipb-udash-hero">
            <div class="ipb-udash-plan">
              <div class="ipb-udash-plan-top">
                <div>
                  <div class="ipb-udash-plan-label">Current plan</div>
                  <div class="ipb-udash-plan-name"><?= esc($packageName); ?></div>
                  <div class="ipb-udash-plan-welcome">
                    ID <?= $userId; ?>
                    <?php if ($expireLabel !== '—'): ?>
                      · Expires <?= esc($expireLabel); ?>
                    <?php endif; ?>
                  </div>
                </div>
                <span class="ipb-udash-status<?= $isActive ? '' : ' is-off'; ?>">
                  <span class="dot" aria-hidden="true"></span>
                  <?= $isActive ? 'Active' : 'Inactive'; ?>
                </span>
              </div>

              <div class="ipb-udash-meta">
                <div class="ipb-udash-meta-item">
                  <span>Speed</span>
                  <strong><?= esc($bandwidth); ?><?= is_numeric($bandwidth) ? ' Mbps' : ''; ?></strong>
                </div>
                <div class="ipb-udash-meta-item">
                  <span>Monthly fee</span>
                  <strong>৳<?= esc($packagePrice); ?></strong>
                </div>
                <div class="ipb-udash-meta-item">
                  <span>Expires in</span>
                  <strong class="<?= $isExpiringSoon ? 'is-warn' : ''; ?>">
                    <?= esc((string) $daysLeft); ?><?= is_numeric($daysLeft) ? ' days' : ''; ?>
                  </strong>
                </div>
              </div>

              <div class="ipb-udash-actions">
                <a href="<?= route_to('route.subscription.id', $userId); ?>" class="ipb-udash-pay">
                  <i class="fa fa-credit-card" aria-hidden="true"></i>
                  Pay now — ৳<?= esc($packagePrice); ?>
                </a>
                <a href="<?= route_to('route.subscription.id', $userId); ?>" class="ipb-udash-link">
                  <i class="fa fa-id-card" aria-hidden="true"></i>
                  My subscription
                </a>
              </div>
            </div>

            <div class="ipb-card ipb-udash-usage">
              <div class="ipb-card-head">
                <div>
                  <div class="ipb-card-title">Live usage</div>
                  <div class="ipb-card-sub" id="connectionBox">
                    <?= !empty($pppoe) ? 'PPPoE connection' : 'Broadband connection'; ?>
                  </div>
                </div>
              </div>
              <div id="bandwidth_chart"></div>
            </div>
          </div>
        </div>

        <div class="ipb-widget" data-widget-id="kpi" data-size="full" data-title="Key metrics" data-icon="fa-solid fa-table-cells">
          <div class="ipb-dash-kpi">
            <a href="<?= route_to('route.payment'); ?>" class="ipb-kpi tone-brand">
              <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-credit-card"></i></span></div>
              <div class="ipb-kpi-value">৳<?= number_format((float) $packagePrice); ?></div>
              <div class="ipb-kpi-label">Next bill</div>
              <div class="ipb-kpi-cta">View payments <i class="fa fa-chevron-right"></i></div>
            </a>
            <div class="ipb-kpi tone-success">
              <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-wifi"></i></span></div>
              <div class="ipb-kpi-value" style="font-size:22px"><?= $isActive ? 'Online' : 'Offline'; ?></div>
              <div class="ipb-kpi-label">Connection</div>
            </div>
            <a href="<?= route_to('route.ticket'); ?>" class="ipb-kpi tone-warning">
              <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-life-ring"></i></span></div>
              <div class="ipb-kpi-value"><?= (int) $total_support_ticket; ?></div>
              <div class="ipb-kpi-label">Open tickets</div>
              <div class="ipb-kpi-cta">View tickets <i class="fa fa-chevron-right"></i></div>
            </a>
            <a href="<?= route_to('route.payment'); ?>" class="ipb-kpi tone-info">
              <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-wallet"></i></span></div>
              <div class="ipb-kpi-value">৳<?= number_format((float) $payment_received); ?></div>
              <div class="ipb-kpi-label">Paid successfully</div>
              <div class="ipb-kpi-cta">View payments <i class="fa fa-chevron-right"></i></div>
            </a>
          </div>
        </div>

        <div class="ipb-widget" data-widget-id="paymentReport" data-size="half" data-title="Payment Report" data-icon="fa-solid fa-chart-column">
          <div class="ipb-card">
            <div class="ipb-card-head">
              <div>
                <div class="ipb-card-title">Payment report</div>
                <div class="ipb-card-sub">Jan – <?= date('M Y'); ?></div>
              </div>
            </div>
            <div id="payment_chart"></div>
          </div>
        </div>

        <div class="ipb-widget" data-widget-id="notices" data-size="half" data-title="Notices & Support" data-icon="fa-solid fa-bell">
          <div class="ipb-card" style="margin-bottom:16px">
            <div class="ipb-card-head">
              <div>
                <div class="ipb-card-title">Recent notices</div>
                <div class="ipb-card-sub">Latest provider announcements</div>
              </div>
            </div>
            <?php if (!empty($notices)): ?>
              <?php foreach ($notices as $notice): ?>
                <div class="ipb-udash-notice">
                  <span class="ipb-udash-ic" aria-hidden="true"><i class="fa fa-bullhorn"></i></span>
                  <div>
                    <div class="ipb-udash-notice-title"><?= esc($notice->name); ?></div>
                    <div class="ipb-udash-notice-date"><?= date('d M Y', strtotime($notice->created_at)); ?></div>
                  </div>
                </div>
              <?php endforeach; ?>
              <a class="ipb-udash-more" href="<?= route_to('route.news'); ?>">
                View all notices <i class="fa fa-chevron-right"></i>
              </a>
            <?php else: ?>
              <?= $this->include('components/empty-state', [
                'icon' => 'fa fa-bell-slash',
                'title' => 'No notices',
                'subtitle' => 'You have no recent notices.',
              ]); ?>
            <?php endif; ?>
          </div>

          <div class="ipb-card">
            <div class="ipb-card-head">
              <div>
                <div class="ipb-card-title">Emergency support</div>
                <div class="ipb-card-sub">Reach your provider anytime</div>
              </div>
            </div>
            <div class="ipb-udash-support-item">
              <span class="ipb-udash-ic" aria-hidden="true"><i class="fa fa-phone"></i></span>
              <a href="tel:<?= esc($admin_details->mobile ?? '', 'attr'); ?>">
                <?= esc($admin_details->mobile ?? '01700-000000'); ?>
              </a>
            </div>
            <?php if (!empty($whatsappClean)): ?>
              <div class="ipb-udash-support-item">
                <span class="ipb-udash-ic wa" aria-hidden="true"><i class="fa-brands fa-whatsapp"></i></span>
                <a href="https://wa.me/<?= esc($whatsappClean, 'attr'); ?>" target="_blank" rel="noopener">
                  <?= esc($whatsapp); ?> (WhatsApp)
                </a>
              </div>
            <?php endif; ?>
            <div class="ipb-udash-support-item">
              <span class="ipb-udash-ic" aria-hidden="true"><i class="fa fa-envelope-open-text"></i></span>
              <a href="mailto:<?= esc($admin_details->email ?? '', 'attr'); ?>">
                <?= esc($admin_details->email ?? 'support@example.com'); ?>
              </a>
            </div>
            <a class="ipb-udash-more" href="<?= route_to('route.ticket'); ?>">
              Open support tickets <i class="fa fa-chevron-right"></i>
            </a>
          </div>
        </div>

      </div>
    </div>

    <?php if (!empty($whatsappClean)): ?>
      <a href="https://wa.me/<?= esc($whatsappClean, 'attr'); ?>" target="_blank" rel="noopener" class="whatsapp-float" aria-label="WhatsApp support">
        <i class="fa-brands fa-whatsapp"></i>
      </a>
    <?php endif; ?>
  </section>
</div>
<?= $this->endSection('content'); ?>

<?= $this->section('script'); ?>
<?php
$router_id = is_array($details) ? $details['router_id'] : $details->router_id;
?>
<script>
  $(document).ready(function () {
    $pppoe = <?= json_encode($pppoe); ?>;
    let bandwidthChart = false;
    const rxArray = [0];
    const txArray = [0];
    const categoryArray = ["<?= gmdate(DATE_ISO8601); ?>"];

    function loadTrafic(interface = "") {
      $.ajax({
        url: `<?= route_to('route.routers.Usersload_Traffic', $router_id); ?>?interface=${interface}&pppoe_name=<?= $pppoe ?? '--'; ?>`,
        type: 'GET',
        success: function (response) {
          const result = response.response;
          if (!bandwidthChart) {
            const p = window.IpbTheme.chartPalette();
            let options = {
              series: [
                { name: 'RX-Byte', data: [0] },
                { name: 'TX-Byte', data: [0] }
              ],
              chart: { height: 260, type: 'area', toolbar: { show: false }, fontFamily: 'Satoshi, sans-serif' },
              colors: ['#f75803', '#2563eb'],
              dataLabels: { enabled: false },
              stroke: { curve: 'smooth', width: 2 },
              fill: {
                type: 'gradient',
                gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.05 }
              },
              xaxis: {
                type: "datetime",
                categories: ["<?= gmdate(DATE_ISO8601); ?>"],
                labels: {
                  formatter: function (value) {
                    const d = new Date(value);
                    return d.toLocaleTimeString();
                  },
                  style: { colors: p.axis }
                },
                axisBorder: { show: false }
              },
              yaxis: {
                labels: {
                  formatter: function (value) {
                    return value < 0.5 ? `${Math.round(value * 1000)} Kbps` : `${value} Mbps`;
                  },
                  style: { colors: p.axis }
                }
              },
              grid: { borderColor: p.grid, strokeDashArray: 4 },
              tooltip: { x: { format: "dd/MM/yy HH:mm" } },
              // Keep the Kbps/Mbps unit (an unlabelled throughput axis is
              // meaningless) — just round it so the axis stops eating the plot.
              responsive: window.IpbUI
                ? window.IpbUI.chartResponsive('area', {
                    yaxis: {
                      labels: {
                        formatter: function (value) {
                          return value < 0.5 ? `${Math.round(value * 1000)} Kbps` : `${Math.round(value * 10) / 10} Mbps`;
                        }
                      }
                    }
                  })
                : []
            };
            bandwidthChart = new ApexCharts(document.querySelector("#bandwidth_chart"), options);
            window.IpbTheme.registerChart(bandwidthChart);
            bandwidthChart.render();
          } else if (result && result.data && result.data.traffic) {
            rxArray.push(result.data.traffic.rxbyte);
            txArray.push(result.data.traffic.txbyte);
            categoryArray.push(result.data.traffic.date);
            if (categoryArray.length > 15) {
              rxArray.shift();
              txArray.shift();
              categoryArray.shift();
            }
            bandwidthChart.updateSeries([{ data: rxArray }, { data: txArray }]);
            bandwidthChart.updateOptions({ xaxis: { categories: categoryArray } });
          }
          setTimeout(() => loadTrafic($('#interface').val()), 1000);
        },
        error: function ({ responseText }) {
          try {
            const result = JSON.parse(responseText);
            if (window.tata) tata.error("Couldn't load traffic data", result.response);
          } catch (e) {}
        }
      });
    }
    loadTrafic();
  });
</script>
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
      }],
      chart: { height: 300, type: 'bar', toolbar: { show: false }, fontFamily: 'Satoshi, sans-serif' },
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
        labels: { style: { colors: p.axis } }
      },
      yaxis: { labels: { style: { colors: p.axis } } },
      grid: { borderColor: p.grid, strokeDashArray: 4 },
      responsive: window.IpbUI
        ? window.IpbUI.chartResponsive('bar', { yaxis: { labels: { formatter: window.IpbUI.compactNumber } } })
        : []
    });
    window.IpbTheme.registerChart(paymentChart);
    paymentChart.render();
  });
</script>
<?= $this->endSection('script'); ?>
