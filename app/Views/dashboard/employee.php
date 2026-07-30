<?= $this->extend('layout/main-layout'); ?>
<?php $this->section('needsApexCharts'); ?>1<?php $this->endSection(); ?>

<?= $this->section('css'); ?>
<?= saas_css('dashboard.css') ?>
<style>
  .loc-widget {
    background: linear-gradient(135deg, #0f766e, #14b8a6);
    color: #fff;
    border-radius: 14px;
    padding: 16px 22px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 14px;
    margin-bottom: 20px;
  }
  .loc-widget.overdue { background: linear-gradient(135deg, #dc2626, #f97316); }
  .loc-info { display: flex; align-items: center; gap: 14px; }
  .loc-icon { font-size: 28px; }
  .loc-info .loc-label { font-size: 13px; opacity: 0.85; margin: 0; }
  .loc-info .loc-countdown {
    font-size: 26px;
    font-weight: 800;
    margin: 0;
    letter-spacing: 1px;
    font-variant-numeric: tabular-nums;
  }
  .loc-info .loc-countdown.overdue-txt { color: #fde68a; }
  .btn-loc {
    background: #fff;
    color: #0f766e;
    border: none;
    padding: 10px 22px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .btn-loc:disabled { opacity: 0.6; cursor: not-allowed; }
  .portal-panel {
    background: var(--surface, #fff);
    border-radius: 14px;
    box-shadow: var(--shadow-1, 0 5px 12px rgba(0,0,0,0.08));
    padding: 20px;
    margin-bottom: 0;
    height: 100%;
  }
  .portal-panel .panel-title {
    font-size: 16px;
    font-weight: 700;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 10px;
    border-bottom: 1px solid rgba(0,0,0,0.06);
    padding-bottom: 12px;
  }
  .attendance-table { width: 100%; border-collapse: separate; border-spacing: 0; }
  .attendance-table th {
    background: var(--brand-600, #4f46e5);
    color: #fff;
    padding: 10px 14px;
    font-size: 13px;
    text-align: left;
  }
  .attendance-table td {
    padding: 10px 14px;
    font-size: 13px;
    border-bottom: 1px solid rgba(0,0,0,0.06);
  }
  .badge-present {
    background: #dcfce7;
    color: #16a34a;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
  }
  .checkin-area { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; margin-bottom: 16px; }
  .btn-checkin, .btn-checkout, .btn-apply {
    border: none;
    padding: 10px 22px;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    color: #fff;
    display: inline-flex;
    align-items: center;
    gap: 8px;
  }
  .btn-checkin { background: linear-gradient(135deg, #16a34a, #22c55e); }
  .btn-checkout { background: linear-gradient(135deg, #dc2626, #ef4444); }
  .btn-apply { background: linear-gradient(135deg, #4f46e5, #6366f1); margin-top: 10px; }
  .btn-apply:disabled { opacity: 0.6; cursor: not-allowed; }
  .checkin-status-bar {
    padding: 12px 18px;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 16px;
  }
  .status-in { background: #dcfce7; color: #16a34a; }
  .status-out { background: #fee2e2; color: #dc2626; }
  .status-notyet { background: #fef9c3; color: #ca8a04; }
  .dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }
  .dot-green { background: #22c55e; }
  .dot-red { background: #ef4444; }
  .dot-yellow { background: #eab308; }
  .advance-form { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
  @media (max-width: 640px) { .advance-form { grid-template-columns: 1fr; } }
  .form-field label { font-size: 13px; font-weight: 600; margin-bottom: 5px; display: block; }
  .form-field input, .form-field textarea {
    width: 100%;
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    padding: 9px 13px;
    font-size: 14px;
  }
</style>
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

    <?php
    $locationRequired = isset($employee_details) && isset($employee_details->location_required) ? (int) $employee_details->location_required : 1;
    $locationInterval = isset($employee_details) && isset($employee_details->location_interval) ? (int) $employee_details->location_interval : 60;
    $lastLocUpdate    = isset($employee_details) && !empty($employee_details->last_location_update) ? strtotime($employee_details->last_location_update) : 0;
    $secondsSinceLoc  = $lastLocUpdate ? (time() - $lastLocUpdate) : ($locationInterval * 60);
    $secondsRemaining = max(0, ($locationInterval * 60) - $secondsSinceLoc);
    $locOverdue       = $locationRequired && ($secondsRemaining <= 0);
    ?>
    <?php if ($locationRequired): ?>
    <div id="loc-widget" class="loc-widget<?= $locOverdue ? ' overdue' : '' ?>">
      <div class="loc-info">
        <span class="loc-icon"><i class="fa fa-satellite-dish"></i></span>
        <div>
          <p class="loc-label"><?= $locOverdue ? 'Location update overdue! Sharing now…' : 'Next location update in'; ?></p>
          <p class="loc-countdown<?= $locOverdue ? ' overdue-txt' : '' ?>" id="loc-countdown">
            <?= $locOverdue ? '00:00' : sprintf('%02d:%02d', floor($secondsRemaining / 60), $secondsRemaining % 60); ?>
          </p>
        </div>
      </div>
      <button class="btn-loc" id="btn-update-location" onclick="submitLocation(this)">
        <i class="fa fa-location-crosshairs"></i> Share Now
      </button>
    </div>
    <?php endif; ?>

    <div class="ipb-dash fade-in" data-ipb-dashboard="employee">
      <div class="ipb-dash-toolbar">
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
            <div class="ipb-kpi-value">৳<?= number_format((float) ($salary_received ?? $payment_received ?? 0)); ?></div>
            <div class="ipb-kpi-label">Salary received</div>
            <div class="ipb-kpi-cta">View details <i class="fa fa-chevron-right"></i></div>
          </a>
          <a href="<?= route_to('route.employee.payment'); ?>" class="ipb-kpi tone-warning">
            <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-clock"></i></span></div>
            <div class="ipb-kpi-value">৳<?= number_format((float) ($salary_pending ?? $payment_pending ?? 0)); ?></div>
            <div class="ipb-kpi-label">Salary pending</div>
            <div class="ipb-kpi-cta">View details <i class="fa fa-chevron-right"></i></div>
          </a>
        <?php else: ?>
          <div class="ipb-kpi tone-success">
            <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-wallet"></i></span></div>
            <div class="ipb-kpi-value">৳<?= number_format((float) ($salary_received ?? $payment_received ?? 0)); ?></div>
            <div class="ipb-kpi-label">Salary received</div>
          </div>
          <div class="ipb-kpi tone-warning">
            <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-clock"></i></span></div>
            <div class="ipb-kpi-value">৳<?= number_format((float) ($salary_pending ?? $payment_pending ?? 0)); ?></div>
            <div class="ipb-kpi-label">Salary pending</div>
          </div>
        <?php endif; ?>

        <a href="<?= route_to('route.employee.advance_salary'); ?>" class="ipb-kpi tone-info">
          <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-hand-holding-dollar"></i></span></div>
          <div class="ipb-kpi-value"><?= (int) ($advance_salary_pending ?? 0); ?></div>
          <div class="ipb-kpi-label">Advance salary pending</div>
          <div class="ipb-kpi-cta">Apply / View <i class="fa fa-chevron-right"></i></div>
        </a>
        <div class="ipb-kpi tone-neutral">
          <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-calendar-check"></i></span></div>
          <div class="ipb-kpi-value"><?= count($attendance_records ?? []); ?></div>
          <div class="ipb-kpi-label">Days present (<?= date('M'); ?>)</div>
        </div>
      </div>
      </div>

      <div class="ipb-widget" data-widget-id="attendance" data-size="half" data-title="Attendance" data-icon="fa-solid fa-calendar-check">
      <div class="portal-panel">
        <div class="panel-title">
          <i class="fa fa-calendar-check"></i> Attendance — <?= date('F Y'); ?>
        </div>
        <?php
        $checked_in  = !empty($today_attendance) && !empty($today_attendance->check_in);
        $checked_out = !empty($today_attendance) && !empty($today_attendance->check_out);
        ?>
        <?php if (!$checked_in): ?>
          <div class="checkin-status-bar status-notyet">
            <span class="dot dot-yellow"></span> You haven't checked in today yet.
          </div>
        <?php elseif ($checked_in && !$checked_out): ?>
          <div class="checkin-status-bar status-in">
            <span class="dot dot-green"></span>
            Checked in at <?= date('h:i A', strtotime($today_attendance->check_in)); ?> — You are active.
          </div>
        <?php else: ?>
          <div class="checkin-status-bar status-out">
            <span class="dot dot-red"></span>
            Checked in <?= date('h:i A', strtotime($today_attendance->check_in)); ?> → Checked out <?= date('h:i A', strtotime($today_attendance->check_out)); ?>
          </div>
        <?php endif; ?>

        <div class="checkin-area">
          <?php if (!$checked_in): ?>
            <button class="btn-checkin" onclick="doCheckIn(this)">
              <i class="fa fa-sign-in-alt"></i> Check In
            </button>
          <?php elseif ($checked_in && !$checked_out): ?>
            <button class="btn-checkout" onclick="doCheckOut(this)">
              <i class="fa fa-sign-out-alt"></i> Check Out
            </button>
          <?php else: ?>
            <span style="color:#16a34a;font-weight:600;"><i class="fa fa-check-circle"></i> Attendance complete for today.</span>
          <?php endif; ?>
        </div>

        <div style="overflow-x:auto;">
          <table class="attendance-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Date</th>
                <th>Check In</th>
                <th>Check Out</th>
                <th>Duration</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($attendance_records)): ?>
                <?php foreach ($attendance_records as $i => $rec): ?>
                  <?php
                    $duration = '—';
                    if (!empty($rec->check_in) && !empty($rec->check_out)) {
                      $diff = strtotime($rec->check_out) - strtotime($rec->check_in);
                      $h = floor($diff / 3600);
                      $m = floor(($diff % 3600) / 60);
                      $duration = "{$h}h {$m}m";
                    }
                  ?>
                  <tr>
                    <td><?= $i + 1; ?></td>
                    <td><?= date('D, d M Y', strtotime($rec->date)); ?></td>
                    <td><?= !empty($rec->check_in) ? date('h:i A', strtotime($rec->check_in)) : '—'; ?></td>
                    <td><?= !empty($rec->check_out) ? date('h:i A', strtotime($rec->check_out)) : '—'; ?></td>
                    <td><?= $duration; ?></td>
                    <td><span class="badge-present">Present</span></td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="6" style="text-align:center;color:#999;padding:30px;">No attendance records for this month yet.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      </div>

      <div class="ipb-widget" data-widget-id="advanceSalary" data-size="half" data-title="Advance Salary" data-icon="fa-solid fa-hand-holding-dollar">
      <div class="portal-panel">
        <div class="panel-title">
          <i class="fa fa-hand-holding-dollar"></i> Apply for Advance Salary
        </div>
        <div id="advance-salary-feedback" style="margin-bottom:12px;"></div>
        <form id="advance-salary-form">
          <div class="advance-form">
            <div class="form-field">
              <label>Amount (৳)</label>
              <input type="number" id="as-amount" name="amount" placeholder="Enter amount" min="1" required />
            </div>
            <div class="form-field">
              <label>Reason</label>
              <textarea id="as-reason" name="reason" rows="2" placeholder="Reason for advance salary (optional)"></textarea>
            </div>
          </div>
          <button type="submit" class="btn-apply" id="btn-apply-advance">
            <i class="fa fa-paper-plane"></i> Submit Request
          </button>
        </form>
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
  function doCheckIn(btn) {
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Checking in…';
    fetch('<?= route_to('route.employee.check_in'); ?>', {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/json' },
      body: JSON.stringify({ csrf_test_name: '<?= csrf_hash(); ?>' })
    })
    .then(r => r.json())
    .then(d => {
      if (d.status === 'success') { tata.success('Checked In', d.response, { onClose: () => location.reload() }); }
      else { tata.error('Error', d.response); btn.disabled = false; btn.innerHTML = '<i class="fa fa-sign-in-alt"></i> Check In'; }
    })
    .catch(() => { tata.error('Error', 'Network error.'); btn.disabled = false; btn.innerHTML = '<i class="fa fa-sign-in-alt"></i> Check In'; });
  }

  function doCheckOut(btn) {
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Checking out…';
    fetch('<?= route_to('route.employee.check_out'); ?>', {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/json' },
      body: JSON.stringify({ csrf_test_name: '<?= csrf_hash(); ?>' })
    })
    .then(r => r.json())
    .then(d => {
      if (d.status === 'success') { tata.success('Checked Out', d.response, { onClose: () => location.reload() }); }
      else { tata.error('Error', d.response); btn.disabled = false; btn.innerHTML = '<i class="fa fa-sign-out-alt"></i> Check Out'; }
    })
    .catch(() => { tata.error('Error', 'Network error.'); btn.disabled = false; btn.innerHTML = '<i class="fa fa-sign-out-alt"></i> Check Out'; });
  }

  <?php if ($locationRequired): ?>
  const locIntervalSecs = <?= $locationInterval * 60; ?>;
  let   locSecondsLeft  = <?= max(0, $secondsRemaining); ?>;
  let   locAutoSharing  = false;
  const locCountdownEl  = document.getElementById('loc-countdown');
  const locWidgetEl     = document.getElementById('loc-widget');
  const locLabelEl      = locWidgetEl ? locWidgetEl.querySelector('.loc-label') : null;

  function padTwo(n) { return String(n).padStart(2, '0'); }

  function resetLocWidget() {
    locAutoSharing = false;
    if (locWidgetEl) {
      locWidgetEl.classList.remove('overdue');
      locWidgetEl.style.background = '';
    }
    if (locLabelEl) locLabelEl.textContent = 'Next location update in';
    if (locCountdownEl) locCountdownEl.classList.remove('overdue-txt');
  }
  <?php else: ?>
  const locIntervalSecs = 0;
  let   locSecondsLeft  = 0;
  let   locAutoSharing  = false;
  const locCountdownEl  = null;
  function padTwo(n) { return String(n).padStart(2, '0'); }
  function resetLocWidget() {}
  <?php endif; ?>

  function submitLocation(btn, silent = false) {
    if (!navigator.geolocation) {
      if (!silent) tata.error('Error', 'Geolocation is not supported by your browser.');
      return;
    }
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Getting…'; }
    navigator.geolocation.getCurrentPosition(
      pos => {
        fetch('<?= route_to('route.employee.update_location'); ?>', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          body: JSON.stringify({ latitude: pos.coords.latitude, longitude: pos.coords.longitude })
        })
        .then(r => r.json())
        .then(d => {
          if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fa fa-location-crosshairs"></i> Share Now'; }
          if (d.status === 'success') {
            if (!silent) tata.success('Location Updated', 'Your location has been shared.');
            locSecondsLeft = locIntervalSecs;
            resetLocWidget();
            if (locCountdownEl) {
              locCountdownEl.textContent = padTwo(Math.floor(locIntervalSecs / 60)) + ':' + padTwo(locIntervalSecs % 60);
            }
          } else {
            if (!silent) tata.error('Error', d.response);
            locAutoSharing = false;
          }
        })
        .catch(() => {
          if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fa fa-location-crosshairs"></i> Share Now'; }
          locAutoSharing = false;
        });
      },
      err => {
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fa fa-location-crosshairs"></i> Share Now'; }
        if (!silent) tata.error('Location Error', 'Could not get your location: ' + err.message);
        locAutoSharing = false;
      },
      { enableHighAccuracy: true, timeout: 15000 }
    );
  }

  <?php if ($locationRequired): ?>
  function tickLocation() {
    if (locSecondsLeft > 0) {
      locSecondsLeft--;
      const m = Math.floor(locSecondsLeft / 60);
      const s = locSecondsLeft % 60;
      if (locCountdownEl) locCountdownEl.textContent = padTwo(m) + ':' + padTwo(s);
      if (locWidgetEl) {
        locWidgetEl.style.background = (locSecondsLeft <= 60)
          ? 'linear-gradient(135deg,#f97316,#ef4444)'
          : '';
      }
    } else {
      if (locCountdownEl) { locCountdownEl.textContent = '00:00'; locCountdownEl.classList.add('overdue-txt'); }
      if (locWidgetEl) { locWidgetEl.classList.add('overdue'); locWidgetEl.style.background = ''; }
      if (locLabelEl) { locLabelEl.textContent = 'Overdue! Sharing your location…'; }
      if (!locAutoSharing) {
        locAutoSharing = true;
        submitLocation(document.getElementById('btn-update-location'), true);
      }
    }
  }
  setInterval(tickLocation, 1000);
  <?php if ($locOverdue): ?>
  (function () {
    locAutoSharing = true;
    submitLocation(document.getElementById('btn-update-location'), true);
  })();
  <?php endif; ?>
  <?php endif; ?>

  document.getElementById('advance-salary-form').addEventListener('submit', function (e) {
    e.preventDefault();
    const btn = document.getElementById('btn-apply-advance');
    const fb = document.getElementById('advance-salary-feedback');
    const amount = document.getElementById('as-amount').value;
    const reason = document.getElementById('as-reason').value;
    if (!amount || amount <= 0) { fb.innerHTML = '<div class="alert alert-danger">Please enter a valid amount.</div>'; return; }
    btn.disabled = true; btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Submitting…';
    const fd = new FormData();
    fd.append('amount', amount);
    fd.append('reason', reason);
    fetch('<?= route_to('route.employee.advance_salary.apply'); ?>', {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      body: fd
    })
    .then(r => r.json())
    .then(d => {
      btn.disabled = false; btn.innerHTML = '<i class="fa fa-paper-plane"></i> Submit Request';
      if (d.status === 'success') {
        fb.innerHTML = '<div class="alert alert-success">' + d.response + '</div>';
        document.getElementById('advance-salary-form').reset();
      } else {
        fb.innerHTML = '<div class="alert alert-danger">' + d.response + '</div>';
      }
    })
    .catch(() => {
      btn.disabled = false; btn.innerHTML = '<i class="fa fa-paper-plane"></i> Submit Request';
      fb.innerHTML = '<div class="alert alert-danger">Network error. Please try again.</div>';
    });
  });

(window.IpbReady || function (fn) {
  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", fn);
  else fn();
})(function () {
  if (typeof ApexCharts === "undefined" || !window.IpbTheme) return;
  if (!document.querySelector("#payment_chart")) return;
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
    responsive: window.IpbUI
      ? window.IpbUI.chartResponsive('bar', { yaxis: { labels: { formatter: window.IpbUI.compactNumber } } })
      : []
  });
  window.IpbTheme.registerChart(paymentChart);
  paymentChart.render();
});
</script>
<?= $this->endSection('script'); ?>
