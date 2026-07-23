<?php
$fundBalance = function_exists('getfund') ? (getfund() ?? 0) : 0;
$clientsRunning = (int) ($users_active ?? 0);
$clientsDisabled = (int) ($users_inactive ?? 0) + (int) ($expired_inactive ?? 0);
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

    <div class="ipb-dash fade-in" data-ipb-dashboard="reseller">
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
        <a href="<?= route_to('route.customer') . '?status=active'; ?>" class="ipb-kpi tone-brand">
          <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-users"></i></span></div>
          <div class="ipb-kpi-value" data-target="<?= $clientsRunning; ?>"><?= $clientsRunning; ?></div>
          <div class="ipb-kpi-label">Clients Running</div>
          <div class="ipb-kpi-cta">View details <i class="fa fa-chevron-right"></i></div>
        </a>
        <a href="<?= route_to('route.customer') . '?status=active'; ?>" class="ipb-kpi tone-success">
          <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-circle-check"></i></span></div>
          <div class="ipb-kpi-value" data-target="<?= (int) ($users_active ?? 0); ?>"><?= (int) ($users_active ?? 0); ?></div>
          <div class="ipb-kpi-label">Clients Enabled</div>
          <div class="ipb-kpi-cta">View details <i class="fa fa-chevron-right"></i></div>
        </a>
        <a href="<?= route_to('route.inactive_index'); ?>" class="ipb-kpi tone-error">
          <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-circle-xmark"></i></span></div>
          <div class="ipb-kpi-value" data-target="<?= $clientsDisabled; ?>"><?= $clientsDisabled; ?></div>
          <div class="ipb-kpi-label">Clients Disabled</div>
          <div class="ipb-kpi-cta">View details <i class="fa fa-chevron-right"></i></div>
        </a>
        <a href="<?= route_to('route.reseller.funding'); ?>" class="ipb-kpi tone-navy">
          <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-wallet"></i></span></div>
          <div class="ipb-kpi-value">৳<?= number_format((float) $fundBalance, 0); ?></div>
          <div class="ipb-kpi-label">Remaining Fund</div>
          <div class="ipb-kpi-cta">View details <i class="fa fa-chevron-right"></i></div>
        </a>
      </div>
      </div>

      <div class="ipb-widget" data-widget-id="customersBilling" data-size="full" data-title="Customers & Billing" data-icon="fa-solid fa-users">
      <div>
      <div class="ipb-section-label">Customers &amp; billing</div>
      <div class="ipb-dash-mini">
        <a href="<?= route_to('route.new_customer'); ?>" class="ipb-kpi tone-success compact">
          <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-user-plus"></i></span></div>
          <div class="ipb-kpi-value" data-target="<?= (int) ($users_new ?? 0); ?>"><?= (int) ($users_new ?? 0); ?></div>
          <div class="ipb-kpi-label">New Customers</div>
        </a>
        <a href="<?= route_to('route.expired_customer'); ?>" class="ipb-kpi tone-warning compact">
          <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-user-clock"></i></span></div>
          <div class="ipb-kpi-value" data-target="<?= (int) ($expired_inactive ?? 0); ?>"><?= (int) ($expired_inactive ?? 0); ?></div>
          <div class="ipb-kpi-label">Expired</div>
        </a>
        <a href="<?= route_to('route.customer.payment') . '?status=successful'; ?>" class="ipb-kpi tone-success compact">
          <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-money-bill-trend-up"></i></span></div>
          <div class="ipb-kpi-value" data-target="<?= (float) ($customers_payment_received ?? 0); ?>" data-count="<?= (int) ($customers_payment_received_count ?? 0); ?>">0</div>
          <div class="ipb-kpi-label">Payment Received</div>
        </a>
        <a href="<?= route_to('route.customer') . '?status=due'; ?>" class="ipb-kpi tone-warning compact">
          <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-file-invoice-dollar"></i></span></div>
          <div class="ipb-kpi-value" data-target="<?= (float) ($customers_Expayment_total ?? 0); ?>" data-count="<?= (int) ($customers_Expayment_count ?? 0); ?>">0</div>
          <div class="ipb-kpi-label">Payment Due</div>
        </a>
        <a href="<?= route_to('route.area'); ?>" class="ipb-kpi tone-info compact">
          <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-map-location-dot"></i></span></div>
          <div class="ipb-kpi-value" data-target="<?= (int) ($total_area ?? 0); ?>"><?= (int) ($total_area ?? 0); ?></div>
          <div class="ipb-kpi-label">Service Areas</div>
        </a>
        <div class="ipb-kpi tone-brand compact">
          <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-coins"></i></span></div>
          <div class="ipb-kpi-value" style="font-size:16px;text-transform:capitalize"><?= esc($billing_type ?? 'postpaid'); ?></div>
          <div class="ipb-kpi-label">Billing Mode</div>
        </div>
      </div>
      </div>
      </div>

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
                <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-power-off"></i></span></div>
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

      <div class="ipb-widget" data-widget-id="paymentReport" data-size="half" data-title="Customer Payment Report" data-icon="fa-solid fa-receipt">
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
      </div>
    </div>
  </section>
</div>
<?= $this->endSection('content'); ?>

<?= $this->section('script'); ?>

<script>
  // Counter Animation
  const counters = document.querySelectorAll(".ipb-kpi-value[data-target]");

  counters.forEach(counter => {
    const target = +counter.getAttribute("data-target"); // main total
    const countTarget = counter.getAttribute("data-count"); // may be null
    const countNum = countTarget ? +countTarget : null;

    let current = 0;
    let currentCount = 0;

    const updateCounter = () => {
      const increment = target / 80; // speed for total
      const countIncrement = countNum ? countNum / 80 : 0; // speed for count

      if (current < target || (countNum && currentCount < countNum)) {
        if (current < target) current += increment;
        if (countNum && currentCount < countNum) currentCount += countIncrement;

        if (countNum) {
          counter.innerText =
            `${Math.ceil(Math.min(current, target))} (${Math.ceil(Math.min(currentCount, countNum))})`;
        } else {
          counter.innerText = `${Math.ceil(Math.min(current, target))}`;
        }

        setTimeout(updateCounter, 30);
      } else {
        if (countNum) {
          counter.innerText = `${target} (${countNum})`;
        } else {
          counter.innerText = `${target}`;
        }
      }
    };

    updateCounter();
  });




  const userRole = '<?= session()->get('user_role') ?>';
  $(document).ready(function () {

    // Fetch data for each router on page load with a staggered delay to prevent Mikrotik concurrency lockups
    <?php $index = 0; foreach ($routers as $router): ?>
      // console.log('Dropdown element ');
      setTimeout(function() {
        loadRouterData('', <?= $router->id; ?>);
      }, <?= $index * 500; ?>);
    <?php $index++; endforeach; ?>

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

    function loadRouterData(interface = "", routerId) {
      console.log('Dropdown routerId ', routerId);
      
      const statusEl = document.getElementById(`status_${routerId}`);
      const statusDot = statusEl ? statusEl.querySelector('.status-dot') : null;
      const statusText = statusEl ? statusEl.querySelector('.status-label') : null;
      const lastUpdatedEl = document.getElementById(`last_updated_${routerId}`);

      let baseUrl = "<?= base_url('/routers/load-traffic'); ?>";
      let url = `${baseUrl}/${routerId}?interface=${interface}`; // Append routerId to the URL
      $.ajax({
        url: url,
        method: 'GET',
        dataType: 'json',
        timeout: 20000,
        success: function (response) {
          const result = response.response || response;
          
          if (!result || !result.data) {
            if (statusEl) {
              statusDot.className = 'status-dot error';
              statusEl.className = 'status-indicator error';
              statusText.textContent = 'Data Error';
              if (lastUpdatedEl) lastUpdatedEl.textContent = `Last try: ${new Date().toLocaleTimeString()}`;
            }
            return;
          }

          // Determine user count based on role
          let totalUsers = 0;
          let activeUsers = 0;
          if (userRole !== 'admin') {
            totalUsers = Array.isArray(result.data.allusers) ? result.data.allusers.length : 0;
            activeUsers = Array.isArray(result.data.activeusers) ? result.data.activeusers.length : 0;
          } else {
            totalUsers = result.data.users;
            activeUsers = result.data.active || 0;
          }

          const inactiveUsers = totalUsers - activeUsers;

          // Animate to real values
          const totalEl = document.getElementById(`total_user_count_${routerId}`);
          const activeEl = document.getElementById(`active_user_count_${routerId}`);
          const inactiveEl = document.getElementById(`inactive_user_count_${routerId}`);

          if (totalEl) { animateRouterCounter(totalEl, totalUsers); totalEl.setAttribute('data-cached', totalUsers); }
          if (activeEl) { animateRouterCounter(activeEl, activeUsers); activeEl.setAttribute('data-cached', activeUsers); }
          if (inactiveEl) { animateRouterCounter(inactiveEl, inactiveUsers); inactiveEl.setAttribute('data-cached', inactiveUsers); }
          
          if (statusEl) {
            statusDot.className = 'status-dot online';
            statusEl.className = 'status-indicator online';
            statusText.textContent = 'Online';
            if (lastUpdatedEl) lastUpdatedEl.textContent = `Updated: ${new Date().toLocaleTimeString()}`;
          }
        },
        error: function (xhr, status, error) {
          console.error('Error loading data:', error);
          if (statusEl) {
            statusDot.className = 'status-dot error';
            statusEl.className = 'status-indicator error';
            
            let serverMessage = null;
            try {
              const json = JSON.parse(xhr.responseText);
              serverMessage = json.message || null;
            } catch (e) { }

            if (status === 'timeout') {
              statusText.textContent = 'Timeout';
            } else if (serverMessage) {
              statusText.textContent = serverMessage.length > 40
                ? serverMessage.substring(0, 37) + '...'
                : serverMessage;
            } else {
              statusText.textContent = 'Connection Failed';
            }
            // Keep cached values visible on error (don't reset to 0)
            if (lastUpdatedEl) lastUpdatedEl.textContent = `Last try: ${new Date().toLocaleTimeString()}`;
          }
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
  });



  document.addEventListener("DOMContentLoaded", () => {
    const p = window.IpbTheme.chartPalette();

    //customer payment resport chart
    const customerPaymentReportChart = new ApexCharts(document.querySelector("#customerPaymentReportChart"), {
      series: [{
        name: 'Successful',
        data: [<?= '"' . implode('","', $customer_payment_statistics["successful"]) . '"' ?>],
      },
      {
        name: 'Pending',
        data: [<?= '"' . implode('","', $customer_payment_statistics["pending"]) . '"' ?>]
      },
      {
        name: 'Failed',
        data: [<?= '"' . implode('","', $customer_payment_statistics["failed"]) . '"' ?>]
      }
      ],
      chart: {
        height: 300,
        type: 'bar',
        toolbar: {
          show: false
        },
      },
      markers: {
        size: 4
      },
      colors: ['#63ED7A', '#ffc107', '#FC544B'],
      dataLabels: {
        enabled: false
      },
      legend: { labels: { colors: p.ink } },
      grid: { borderColor: p.grid },
      tooltip: {
        shared: true,
        intersect: false,
        y: {
          formatter: function (val) {
            return "৳ " + val + " BDT"
          }
        }
      },
      xaxis: {
        categories: [<?= '"' . implode('","', $customer_payment_statistics["months"]) . '"' ?>],
        title: {
          text: 'Months',
          style: { color: p.ink }
        },
        labels: { style: { colors: p.axis } }
      },
      yaxis: {
        title: {
          text: 'Transaction Amount (৳)',
          style: { color: p.ink }
        },
        labels: { style: { colors: p.axis } }
      },
      // Phone: axis titles off, legend under the plot, money axis compacted.
      responsive: window.IpbUI
        ? window.IpbUI.chartResponsive('bar', { yaxis: { labels: { formatter: window.IpbUI.compactNumber } } })
        : []
    });
    window.IpbTheme.registerChart(customerPaymentReportChart);
    customerPaymentReportChart.render();

    //employee payment chart
    const employeePaymentReportChart = new ApexCharts(document.querySelector("#employeePaymentReportChart"), {
      series: [{
        name: 'Successful',
        data: [<?= '"' . implode('","', $employee_payment_statistics["successful"]) . '"' ?>],
      },
      {
        name: 'Pending',
        data: [<?= '"' . implode('","', $employee_payment_statistics["pending"]) . '"' ?>]
      },
      {
        name: 'Failed',
        data: [<?= '"' . implode('","', $employee_payment_statistics["failed"]) . '"' ?>]
      }
      ],
      chart: {
        height: 300,
        type: 'bar',
        toolbar: {
          show: false
        },
      },
      markers: {
        size: 4
      },
      colors: ['#63ED7A', '#ffc107', '#FC544B'],
      dataLabels: {
        enabled: false
      },
      legend: { labels: { colors: p.ink } },
      grid: { borderColor: p.grid },
      tooltip: {
        shared: true,
        intersect: false,
        y: {
          formatter: function (val) {
            return "৳ " + val + " BDT"
          }
        }
      },
      xaxis: {
        categories: [<?= '"' . implode('","', $employee_payment_statistics["months"]) . '"' ?>],
        title: {
          text: 'Months',
          style: { color: p.ink }
        },
        labels: { style: { colors: p.axis } }
      },
      yaxis: {
        title: {
          text: 'Transaction Amount (৳)',
          style: { color: p.ink }
        },
        labels: { style: { colors: p.axis } }
      },
      responsive: window.IpbUI
        ? window.IpbUI.chartResponsive('bar', { yaxis: { labels: { formatter: window.IpbUI.compactNumber } } })
        : []
    });
    window.IpbTheme.registerChart(employeePaymentReportChart);
    employeePaymentReportChart.render();

  });
</script>

<?php if (session()->has('original_user')): ?>
  <script>
    // Prevent leaving impersonated reseller dashboard via browser back button.
    (function () {
      history.pushState(null, '', window.location.href);
      window.addEventListener('popstate', function () {
        history.pushState(null, '', window.location.href);
      });
    })();
  </script>
<?php endif; ?>

<?= $this->endSection('script'); ?>

