<?= $this->extend('layout/main-layout'); ?>
<?= $this->section('css'); ?>
<?= saas_css('employee-activity.css') ?>
<?= $this->endSection('css'); ?>

<?= $this->section('content'); ?>
<?php
$isActive = (($employee->status ?? '') === 'active');
$attendanceCount = is_countable($attendance ?? null) ? count($attendance) : 0;
$locationCount = is_countable($locations ?? null) ? count($locations) : 0;
$advanceCount = is_countable($advance_salaries ?? null) ? count($advance_salaries) : 0;
$trackingOn = !empty($employee->location_required);
$lastLoc = !empty($employee->last_location_update)
  ? date('d M Y, h:i A', strtotime($employee->last_location_update))
  : 'Never';
$avatarUrl = base_url('assets/img/avatar.png');
?>
<div class="content-wrapper">
  <section class="content ipb-emp-activity">

    <?= $this->include('components/page-header', [
      'title' => 'Employee Activity',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Employees', 'url' => route_to('route.employee')],
        ['label' => $employee->name],
      ],
    ]); ?>

    <!-- Hero -->
    <header class="ea-hero">
      <div class="ea-hero-main">
        <div class="ea-avatar">
          <img
            src="<?= esc($avatarUrl); ?>"
            alt="<?= esc($employee->name); ?>"
            onerror="this.onerror=null;this.src='https://cdn-icons-png.flaticon.com/512/3135/3135715.png'">
          <span class="ea-avatar-dot <?= $isActive ? 'is-online' : ''; ?>" aria-hidden="true"></span>
        </div>
        <div class="ea-hero-text">
          <h1>
            <?= esc($employee->name); ?>
            <span class="ea-pill <?= $isActive ? 'is-active' : 'is-inactive'; ?>">
              <?= esc(ucfirst($employee->status ?? 'unknown')); ?>
            </span>
          </h1>
          <p class="ea-role"><?= esc(ucwords($employee->designation ?? 'Employee')); ?></p>
        </div>
      </div>
      <div class="ea-hero-actions">
        <a href="<?= route_to('route.employee'); ?>" class="btn btn-default">
          <i class="fa fa-arrow-left" aria-hidden="true"></i> Back to list
        </a>
        <?php if (userHasPermission('employee', 'update')): ?>
          <a href="<?= route_to('route.employee.edit', $employee->id); ?>" class="btn btn-primary">
            <i class="far fa-pen-to-square" aria-hidden="true"></i> Edit employee
          </a>
        <?php endif; ?>
      </div>
    </header>

    <!-- Snapshot facts -->
    <div class="ea-facts" role="group" aria-label="Activity summary">
      <div class="ea-fact">
        <span>Attendance logs</span>
        <strong><?= (int) $attendanceCount; ?></strong>
        <em>Check-in records</em>
      </div>
      <div class="ea-fact">
        <span>Location pings</span>
        <strong><?= (int) $locationCount; ?></strong>
        <em>Last 100 shown</em>
      </div>
      <div class="ea-fact">
        <span>Advance requests</span>
        <strong><?= (int) $advanceCount; ?></strong>
        <em>Salary advances</em>
      </div>
      <div class="ea-fact">
        <span>Location tracking</span>
        <strong><?= $trackingOn ? 'On' : 'Off'; ?></strong>
        <em><?= $trackingOn ? ((int) ($employee->location_interval ?? 0) . ' min interval') : 'Not required'; ?></em>
      </div>
    </div>

    <div class="ea-layout">
      <!-- Profile meta -->
      <aside class="ea-panel" aria-label="Employee details">
        <div class="ea-panel-head">Profile details</div>
        <ul class="ea-meta-list">
          <li>
            <span class="ea-k">Mobile</span>
            <span class="ea-v"><?= esc($employee->mobile ?? '—'); ?></span>
          </li>
          <li>
            <span class="ea-k">Email</span>
            <span class="ea-v"><?= esc($employee->email ?? '—'); ?></span>
          </li>
          <li>
            <span class="ea-k">Tracking</span>
            <span class="ea-v"><?= $trackingOn ? 'Enabled' : 'Disabled'; ?></span>
          </li>
          <li>
            <span class="ea-k">Interval</span>
            <span class="ea-v"><?= $trackingOn ? esc(($employee->location_interval ?? 0) . ' mins') : '—'; ?></span>
          </li>
          <li>
            <span class="ea-k">Last location</span>
            <span class="ea-v"><?= esc($lastLoc); ?></span>
          </li>
          <li>
            <span class="ea-k">Status</span>
            <span class="ea-v">
              <span class="ipb-pay-badge <?= $isActive ? 'is-success' : 'is-danger'; ?>">
                <?= esc(ucfirst($employee->status ?? 'unknown')); ?>
              </span>
            </span>
          </li>
        </ul>
      </aside>

      <!-- Activity tabs -->
      <div class="ea-panel">
        <div class="nav-tabs-custom" style="margin:0;box-shadow:none;border:0;">
          <ul class="nav nav-tabs ea-tabs" role="tablist">
            <li class="active" role="presentation">
              <a href="#attendance" data-toggle="tab" role="tab" aria-controls="attendance">
                <i class="fa fa-calendar-check" aria-hidden="true"></i>
                Attendance
                <span class="ea-tab-count"><?= (int) $attendanceCount; ?></span>
              </a>
            </li>
            <li role="presentation">
              <a href="#locations" data-toggle="tab" role="tab" aria-controls="locations">
                <i class="fa fa-map-location-dot" aria-hidden="true"></i>
                Locations
                <span class="ea-tab-count"><?= (int) $locationCount; ?></span>
              </a>
            </li>
            <li role="presentation">
              <a href="#advance_salary" data-toggle="tab" role="tab" aria-controls="advance_salary">
                <i class="fa fa-hand-holding-dollar" aria-hidden="true"></i>
                Advances
                <span class="ea-tab-count"><?= (int) $advanceCount; ?></span>
              </a>
            </li>
          </ul>

          <div class="tab-content ea-tab-body">
            <!-- Attendance -->
            <div class="tab-pane active" id="attendance" role="tabpanel">
              <div class="ea-table-wrap">
                <table class="ea-table">
                  <thead>
                    <tr>
                      <th scope="col">Date</th>
                      <th scope="col">Check in</th>
                      <th scope="col">Check out</th>
                      <th scope="col">Duration</th>
                      <th scope="col">Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (!empty($attendance)): ?>
                      <?php foreach ($attendance as $att): ?>
                        <?php
                          $duration = '—';
                          if (!empty($att->check_in) && !empty($att->check_out)) {
                            $diff = max(0, strtotime($att->check_out) - strtotime($att->check_in));
                            $totalMins = (int) round($diff / 60);
                            $h = intdiv($totalMins, 60);
                            $m = $totalMins % 60;
                            $duration = $h . 'h ' . $m . 'm';
                          }
                        ?>
                        <tr>
                          <td><?= date('D, d M Y', strtotime($att->date)); ?></td>
                          <td class="ea-mono"><?= !empty($att->check_in) ? date('h:i A', strtotime($att->check_in)) : '<span class="ea-muted">—</span>'; ?></td>
                          <td class="ea-mono"><?= !empty($att->check_out) ? date('h:i A', strtotime($att->check_out)) : '<span class="ea-muted">—</span>'; ?></td>
                          <td class="ea-mono"><?= esc($duration); ?></td>
                          <td><span class="ipb-pay-badge is-success">Present</span></td>
                        </tr>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <tr>
                        <td colspan="5">
                          <div class="ea-empty">
                            <i class="fa fa-calendar-xmark" aria-hidden="true"></i>
                            <strong>No attendance logs</strong>
                            <span>Check-ins for this employee will show up here.</span>
                          </div>
                        </td>
                      </tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Locations -->
            <div class="tab-pane" id="locations" role="tabpanel">
              <div class="ea-table-wrap">
                <table class="ea-table">
                  <thead>
                    <tr>
                      <th scope="col">Time shared</th>
                      <th scope="col">Coordinates</th>
                      <th scope="col">Address</th>
                      <th scope="col">Map</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (!empty($locations)): ?>
                      <?php foreach ($locations as $loc): ?>
                        <tr>
                          <td class="ea-mono"><?= date('d M Y, h:i A', strtotime($loc->created_at)); ?></td>
                          <td class="ea-mono"><?= esc($loc->latitude); ?>, <?= esc($loc->longitude); ?></td>
                          <td><?= !empty($loc->address) ? esc($loc->address) : '<span class="ea-muted">—</span>'; ?></td>
                          <td>
                            <a
                              href="https://www.google.com/maps?q=<?= rawurlencode($loc->latitude . ',' . $loc->longitude); ?>"
                              target="_blank"
                              rel="noopener noreferrer"
                              class="btn btn-default btn-xs">
                              <i class="fa fa-map-marker-alt" aria-hidden="true"></i> Open map
                            </a>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <tr>
                        <td colspan="4">
                          <div class="ea-empty">
                            <i class="fa fa-location-dot" aria-hidden="true"></i>
                            <strong>No location history</strong>
                            <span>GPS pings will appear when tracking is active.</span>
                          </div>
                        </td>
                      </tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Advance salary -->
            <div class="tab-pane" id="advance_salary" role="tabpanel">
              <div class="ea-table-wrap">
                <table class="ea-table">
                  <thead>
                    <tr>
                      <th scope="col">Requested</th>
                      <th scope="col">Amount</th>
                      <th scope="col">Reason</th>
                      <th scope="col">Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (!empty($advance_salaries)): ?>
                      <?php foreach ($advance_salaries as $adv): ?>
                        <?php
                          $st = strtolower((string) ($adv->status ?? 'pending'));
                          $badge = match ($st) {
                            'approved' => 'is-success',
                            'rejected' => 'is-danger',
                            default => 'is-warning',
                          };
                        ?>
                        <tr>
                          <td class="ea-mono"><?= date('d M Y, h:i A', strtotime($adv->created_at)); ?></td>
                          <td><strong>৳ <?= number_format((float) $adv->amount, 2); ?></strong></td>
                          <td><?= esc($adv->reason ?? '—'); ?></td>
                          <td>
                            <span class="ipb-pay-badge <?= $badge; ?>">
                              <?= esc(ucfirst($st)); ?>
                            </span>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <tr>
                        <td colspan="4">
                          <div class="ea-empty">
                            <i class="fa fa-hand-holding-dollar" aria-hidden="true"></i>
                            <strong>No advance requests</strong>
                            <span>Salary advance history for this employee is empty.</span>
                          </div>
                        </td>
                      </tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

  </section>
</div>
<?= $this->endSection('content'); ?>
