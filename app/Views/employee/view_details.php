<?= $this->extend('layout/main-layout'); ?>
<?= $this->section('css'); ?>
<style>
  .profile-card { background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 4px 15px rgba(0,0,0,0.06); text-align: center; margin-bottom: 24px; }
  .profile-card img { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid #4f46e5; margin-bottom: 15px; }
  .profile-card h3 { margin: 0 0 5px; font-weight: 700; color: #1e293b; }
  .profile-card p { margin: 0 0 15px; color: #64748b; font-size: 14px; }
  .info-list { list-style: none; padding: 0; margin: 0; text-align: left; }
  .info-list li { padding: 10px 0; border-bottom: 1px solid #f1f5f9; font-size: 13.5px; display: flex; justify-content: space-between; color: #334155; }
  .info-list li span:first-child { font-weight: 600; color: #64748b; }
  
  .details-tab-container { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.06); min-height: 400px; }
  .nav-tabs-custom { border: none; box-shadow: none; margin-bottom: 0; }
  .nav-tabs-custom > .nav-tabs { border-bottom: 2px solid #f1f5f9; }
  .nav-tabs-custom > .nav-tabs > li > a { border: none; font-weight: 600; color: #64748b; padding: 12px 18px; border-radius: 0; }
  .nav-tabs-custom > .nav-tabs > li.active > a { border-bottom: 3px solid #4f46e5; color: #4f46e5; background: transparent; }
  
  .table-custom { width: 100%; border-collapse: separate; border-spacing: 0; }
  .table-custom th { background: #f8fafc; color: #475569; padding: 12px 16px; font-size: 13px; font-weight: 600; border-bottom: 2px solid #e2e8f0; text-align: left; }
  .table-custom td { padding: 12px 16px; font-size: 13px; color: #334155; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
  .table-custom tr:hover td { background: #f8fafc; }
  
  .badge-status { padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-block; }
  .badge-approved { background: #dcfce7; color: #16a34a; }
  .badge-pending { background: #fef9c3; color: #ca8a04; }
  .badge-rejected { background: #fee2e2; color: #dc2626; }
</style>
<?= $this->endSection('css'); ?>

<?= $this->section('content'); ?>
<div class="content-wrapper">
  <section class="content-header">
    <h1>
      Employee Activity Details
      <small><?= esc($employee->name); ?></small>
    </h1>
    <ol class="breadcrumb">
      <li><a href="<?= route_to('route.dashboard'); ?>"><i class="fa fa-dashboard"></i> Home</a></li>
      <li><a href="<?= route_to('route.employee'); ?>">Employees</a></li>
      <li class="active">View Activity</li>
    </ol>
  </section>

  <section class="content">
    <div class="row">
      <!-- Profile Card & Details -->
      <div class="col-md-4">
        <div class="profile-card">
          <img src="<?= base_url('public/assets/img/avatar.png'); ?>" onerror="this.src='https://cdn-icons-png.flaticon.com/512/3135/3135715.png'" alt="Employee Profile">
          <h3><?= esc($employee->name); ?></h3>
          <p><?= esc(ucwords($employee->designation ?? 'Employee')); ?></p>
          
          <ul class="info-list">
            <li>
              <span>Mobile</span>
              <span><?= esc($employee->mobile); ?></span>
            </li>
            <li>
              <span>Email</span>
              <span><?= esc($employee->email); ?></span>
            </li>
            <li>
              <span>Location Tracking</span>
              <span><?= $employee->location_required ? 'Enabled' : 'Disabled'; ?></span>
            </li>
            <li>
              <span>Tracking Interval</span>
              <span><?= esc($employee->location_interval); ?> Mins</span>
            </li>
            <li>
              <span>Last Location Update</span>
              <span class="text-nowrap"><?= $employee->last_location_update ? date('d-m-Y, h:i A', strtotime($employee->last_location_update)) : 'Never'; ?></span>
            </li>
            <li>
              <span>Status</span>
              <span>
                <span class="badge <?= $employee->status === 'active' ? 'label-success' : 'label-danger' ?>">
                  <?= esc(ucfirst($employee->status)); ?>
                </span>
              </span>
            </li>
          </ul>
        </div>
      </div>

      <!-- Activity Logs, Attendance, and Advance Salary Requests -->
      <div class="col-md-8">
        <div class="details-tab-container">
          <div class="nav-tabs-custom">
            <ul class="nav nav-tabs">
              <li class="active"><a href="#attendance" data-toggle="tab"><i class="fa fa-calendar-check margin-r-5"></i> Attendance Logs</a></li>
              <li><a href="#locations" data-toggle="tab"><i class="fa fa-map-location-dot margin-r-5"></i> Location History</a></li>
              <li><a href="#advance_salary" data-toggle="tab"><i class="fa fa-hand-holding-dollar margin-r-5"></i> Advance Salaries</a></li>
            </ul>
            <div class="tab-content" style="padding-top: 20px;">
              
              <!-- Attendance Tab -->
              <div class="tab-pane active" id="attendance">
                <div style="overflow-x:auto;">
                  <table class="table-custom">
                    <thead>
                      <tr>
                        <th>Date</th>
                        <th>Check In</th>
                        <th>Check Out</th>
                        <th>Work Duration</th>
                        <th>Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (!empty($attendance)): ?>
                        <?php foreach ($attendance as $att): ?>
                          <?php
                            $duration = '—';
                            if (!empty($att->check_in) && !empty($att->check_out)) {
                              $diff = strtotime($att->check_out) - strtotime($att->check_in);
                              $h = floor($diff / 3600);
                              $m = floor(($diff % 3600) / 60);
                              $duration = "{$h}h {$m}m";
                            }
                          ?>
                          <tr>
                            <td><?= date('D, d M Y', strtotime($att->date)); ?></td>
                            <td><?= !empty($att->check_in) ? date('h:i A', strtotime($att->check_in)) : '—'; ?></td>
                            <td><?= !empty($att->check_out) ? date('h:i A', strtotime($att->check_out)) : '—'; ?></td>
                            <td><?= $duration; ?></td>
                            <td><span class="badge-status badge-approved">Present</span></td>
                          </tr>
                        <?php endforeach; ?>
                      <?php else: ?>
                        <tr>
                          <td colspan="5" style="text-align:center;color:#999;padding:30px;">No attendance logs found.</td>
                        </tr>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>

              <!-- Location History Tab -->
              <div class="tab-pane" id="locations">
                <div style="overflow-x:auto;">
                  <table class="table-custom">
                    <thead>
                      <tr>
                        <th>Time Shared</th>
                        <th>Coordinates</th>
                        <th>Address</th>
                        <th>Map Link</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (!empty($locations)): ?>
                        <?php foreach ($locations as $loc): ?>
                          <tr>
                            <td><?= date('d-m-Y, h:i A', strtotime($loc->created_at)); ?></td>
                            <td><?= esc($loc->latitude); ?>, <?= esc($loc->longitude); ?></td>
                            <td><?= !empty($loc->address) ? esc($loc->address) : '—'; ?></td>
                            <td>
                              <a href="https://www.google.com/maps?q=<?= $loc->latitude; ?>,<?= $loc->longitude; ?>" target="_blank" class="btn btn-default btn-xs">
                                <i class="fa fa-map-marker-alt" style="color:#ef4444;"></i> Open Map
                              </a>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      <?php else: ?>
                        <tr>
                          <td colspan="4" style="text-align:center;color:#999;padding:30px;">No location logs found.</td>
                        </tr>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>

              <!-- Advance Salary Tab -->
              <div class="tab-pane" id="advance_salary">
                <div style="overflow-x:auto;">
                  <table class="table-custom">
                    <thead>
                      <tr>
                        <th>Date Requested</th>
                        <th>Amount</th>
                        <th>Reason</th>
                        <th>Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (!empty($advance_salaries)): ?>
                        <?php foreach ($advance_salaries as $adv): ?>
                          <tr>
                            <td><?= date('d-m-Y, h:i A', strtotime($adv->created_at)); ?></td>
                            <td><strong>৳ <?= number_format($adv->amount, 2); ?></strong></td>
                            <td><?= esc($adv->reason ?? '—'); ?></td>
                            <td>
                              <span class="badge-status badge-<?= $adv->status; ?>">
                                <?= ucfirst($adv->status); ?>
                              </span>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      <?php else: ?>
                        <tr>
                          <td colspan="4" style="text-align:center;color:#999;padding:30px;">No advance salary requests found.</td>
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
    </div>
  </section>
</div>
<?= $this->endSection('content'); ?>
