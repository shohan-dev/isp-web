<?= $this->extend('layout/main-layout'); ?>

<?= $this->section('content'); ?>

<?php
$routerName = (string) ($details->name ?? 'Router');
?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <!-- Main content -->
  <section class="content ipb-saas-list ipb-router-details">

    <?= $this->include('components/page-header', [
      'title' => $routerName . ' — Router Details',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Mikrotik Routers', 'url' => route_to('route.routers')],
        ['label' => 'Router Details'],
      ],
    ]); ?>

    <div class="box box-primary">
      <div class="box-header with-border ipb-box-toolbar">
        <div class="ipb-list-toolbar">
          <div class="ipb-list-toolbar-filters">
            <span class="ipb-filter-label"><i class="fa fa-chart-line" aria-hidden="true"></i> Router Traffic</span>
          </div>
          <div class="ipb-list-toolbar-actions">
            <a href="<?= route_to('route.routers'); ?>" class="btn btn-default">
              <i class="fa fa-arrow-left" aria-hidden="true"></i> Back
            </a>
            <?php if (userHasPermission('routers', 'update')) : ?>
              <a href="<?= route_to('route.routers.edit', $details->id); ?>" class="btn btn-primary">
                <i class="fa fa-pen" aria-hidden="true"></i> Edit
              </a>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <div class="box-body">

        <div class="ipb-router-offline" id="ipbRouterOffline" hidden>
          <?= $this->include('components/empty-state', [
            'variant' => 'error',
            'icon' => 'fa fa-plug-circle-xmark',
            'title' => "Can't reach this router",
            'subtitle' => "The MikroTik didn't answer. Confirm it's online, then try again.",
            'action' => '<button type="button" class="btn btn-primary btn-sm" id="ipbRouterRetryBtn"><i class="fa fa-rotate-right" aria-hidden="true"></i> Retry</button>',
          ]) ?>
        </div>

        <div id="ipbRouterLive">
        <div class="row ipb-router-charts">
          <div class="col-lg-7">
            <div class="ipb-router-chart-card">
              <div class="ipb-router-chart-head">
                <strong>Bandwidth</strong>
                <div class="ipb-router-interface">
                  <label for="interface">Interface</label>
                  <?php
                  $data = [];

                  if (!empty($interfaces) && is_array($interfaces)) {
                    foreach ($interfaces as $interface) {
                      $data[$interface['name']] = ucwords($interface['name']);
                    }
                  } else {
                    $data[''] = 'No interface found!';
                  }

                  echo form_dropdown('package_id', $data, "", 'class="form-control" id="interface"');
                  ?>
                </div>
              </div>
              <div id="bandwidth_chart"></div>
            </div>
          </div>

          <div class="col-lg-5">
            <div class="ipb-router-chart-card">
              <div class="ipb-router-chart-head">
                <strong>CPU Load</strong>
              </div>
              <div id="cpu_chart"></div>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-lg-6">
            <ul class="list-group ipb-router-meta">
              <li class="list-group-item">
                <span class="meta-label">Router Uptime</span>
                <span id="router_uptime" class="meta-value">—</span>
              </li>
              <li class="list-group-item">
                <span class="meta-label">Time Update</span>
                <span id="time_updated" class="meta-value">—</span>
              </li>
              <li class="list-group-item">
                <span class="meta-label">OS Build Time</span>
                <span id="build_time" class="meta-value">—</span>
              </li>
              <li class="list-group-item">
                <span class="meta-label">Free Memory</span>
                <span id="free_memory" class="meta-value">—</span>
              </li>
              <li class="list-group-item">
                <span class="meta-label">Free HDD</span>
                <span id="free_hdd" class="meta-value">—</span>
              </li>
              <li class="list-group-item">
                <span class="meta-label">Brand / Model</span>
                <span id="board_name" class="meta-value">—</span>
              </li>
              <li class="list-group-item">
                <span class="meta-label">Mikrotik Version</span>
                <span id="router_version" class="meta-value">—</span>
              </li>
              <li class="list-group-item">
                <span class="meta-label">Processor</span>
                <span id="router_cpu" class="meta-value">—</span>
              </li>
              <li class="list-group-item">
                <span class="meta-label">CPU Frequency</span>
                <span id="cpu_frequency" class="meta-value">—</span>
              </li>
              <li class="list-group-item">
                <span class="meta-label">Serial Number</span>
                <span id="router_serial" class="meta-value">—</span>
              </li>
              <li class="list-group-item">
                <span class="meta-label">Software ID</span>
                <span id="software_id" class="meta-value">—</span>
              </li>
              <li class="list-group-item">
                <span class="meta-label">Firmware</span>
                <span id="router_firmware" class="meta-value">—</span>
              </li>
            </ul>
          </div>

          <div class="col-lg-6">
            <div class="info-box">
              <span class="info-box-icon">
                <i class="fa fa-users" aria-hidden="true"></i>
              </span>
              <div class="info-box-content">
                <span class="info-box-text">Total Users</span>
                <span class="info-box-number" id="total_user_count">—</span>
              </div>
            </div>

            <div class="info-box">
              <span class="info-box-icon is-success">
                <i class="fa fa-users" aria-hidden="true"></i>
              </span>
              <div class="info-box-content">
                <span class="info-box-text">Users Online</span>
                <span class="info-box-number" id="active_user_count">—</span>
              </div>
            </div>
          </div>
        </div>
        </div>

      </div>
    </div>

    <div class="box box-warning">
      <div class="box-header with-border">
        <h3 class="box-title">Router Logs</h3>
      </div>
      <div class="box-body ipb-router-logs" id="logs">
        <?php if (!empty($logs) && is_array($logs)): ?>
          <?php foreach ($logs as $log): ?>
            <p class="ipb-router-log-line">
              <i class="fa fa-chevron-right fa-xs" aria-hidden="true"></i>
              <?= esc($log["message"] ?? '') ?>
            </p>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="ipb-router-log-empty">No logs available</p>
        <?php endif; ?>
      </div>
    </div>
  </section>
  <!-- /.content -->
</div>

<?= $this->endSection('content'); ?>

<?= $this->section('script'); ?>

<style>
  .ipb-router-details .ipb-router-offline {
    background: var(--surface, #fff);
    border: 1px solid var(--border, #e6eaf0);
    border-radius: 12px;
    margin-bottom: 20px;
  }

  .ipb-router-details .ipb-router-charts {
    margin-bottom: 20px;
  }

  .ipb-router-details .ipb-router-chart-card {
    background: var(--surface, #fff);
    border: 1px solid var(--border, #e6eaf0);
    border-radius: 12px;
    padding: 14px 16px 8px;
    margin-bottom: 16px;
    min-height: 100%;
  }

  .ipb-router-details .ipb-router-chart-head {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 10px;
  }

  .ipb-router-details .ipb-router-chart-head strong {
    color: var(--text-primary, #0f172a);
    font-size: 14px;
    font-weight: 700;
  }

  .ipb-router-details .ipb-router-interface {
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 180px;
  }

  .ipb-router-details .ipb-router-interface label {
    margin: 0;
    white-space: nowrap;
    color: var(--text-secondary, #64748b);
    font-size: 12px;
    font-weight: 700;
  }

  .ipb-router-details .ipb-router-interface .form-control {
    min-width: 140px;
  }

  .ipb-router-details .ipb-router-meta {
    margin: 0;
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid var(--border, #e6eaf0);
  }

  .ipb-router-details .ipb-router-meta .list-group-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    border-color: var(--border, #e6eaf0);
    background: var(--surface, #fff);
    color: var(--text-primary, #0f172a);
    padding: 12px 14px;
  }

  .ipb-router-details .ipb-router-meta .meta-label {
    color: var(--text-secondary, #64748b);
    font-size: 12.5px;
    font-weight: 700;
  }

  .ipb-router-details .ipb-router-meta .meta-value {
    color: var(--text-primary, #0f172a);
    font-size: 13px;
    font-weight: 600;
    text-align: right;
    word-break: break-word;
  }

  .ipb-router-details .info-box-icon.is-success {
    background: #ecfdf5 !important;
    color: #059669 !important;
  }

  .ipb-router-details .ipb-router-logs {
    background: #0b1220;
    max-height: 400px;
    overflow: auto;
    border-radius: 0 0 10px 10px;
    padding: 14px 16px;
  }

  .ipb-router-details .ipb-router-log-line {
    margin: 0 0 8px;
    color: #e2e8f0;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 12.5px;
    line-height: 1.45;
  }

  .ipb-router-details .ipb-router-log-line i {
    color: #38bdf8;
    margin-right: 6px;
  }

  .ipb-router-details .ipb-router-log-empty {
    margin: 0;
    color: #94a3b8;
  }

  body.ipb[data-theme="dark"] .ipb-router-details .info-box-icon.is-success {
    background: rgba(16, 185, 129, 0.15) !important;
    color: #34d399 !important;
  }
</style>

<script>
  $(document).ready(function () {

    let cpuChart = false;
    let bandwidthChart = false;
    let trafficTimer = null;
    const rxArray = [0];
    const txArray = [0];
    const categoryArray = ["<?= gmdate(DATE_ISO8601); ?>"];

    // 06 §9 — "Can't reach this router" offline state, toggled around the live traffic AJAX.
    function showRouterOffline(show) {
      $('#ipbRouterOffline').prop('hidden', !show);
      $('#ipbRouterLive').prop('hidden', show);
    }

    function loadTrafic(interface = "") {
      $.ajax({
        url: `<?= route_to('route.routers.load_traffic', $details->id); ?>?interface=${encodeURIComponent(interface || '')}`,
        type: 'GET',
        success: function (response) {

          const result = response.response;
          if (!result || !result.data) {
            return;
          }

          showRouterOffline(false);

          //cpu load chart
          if (cpuChart == false) {

            let cpuChartOptions = {
              series: [100 - parseInt(result.data.resource["cpu-load"]), parseInt(result.data.resource["cpu-load"])],
              chart: {
                width: 420,
                type: 'pie',
              },
              dataLabels: {
                enabled: true,
                formatter: function (val) {
                  return val + "%"
                },
              },
              labels: ["Cpu Free", "Cpu Used"],
              colors: ["#435ebe", "#55c6e8"],
              /* ApexCharts' own breakpoint (independent of our CSS media queries) only
                 covered ≤480 ("small phone"), so the fixed 420px pie stayed at full width
                 across 481-767 ("phone" in this codebase's ladder) — wider than
                 .ipb-router-chart-card on most phones, causing horizontal overflow.
                 Added the 767 tier so the whole phone range gets a card-width chart. */
              responsive: [{
                breakpoint: 767,
                options: {
                  chart: {
                    width: 260
                  },
                }
              }, {
                breakpoint: 480,
                options: {
                  chart: {
                    width: 200
                  },
                }
              }],
              legend: {
                position: 'bottom',
                labels: {
                  colors: '#64748b'
                }
              }
            };

            cpuChart = new ApexCharts(document.querySelector("#cpu_chart"), cpuChartOptions);
            cpuChart.render();

          } else {

            cpuChart.updateSeries([100 - parseInt(result.data.resource["cpu-load"]), parseInt(result.data.resource["cpu-load"])]);
          }

          //bandwidth chart
          if (bandwidthChart == false) {

            let options = {
              series: [{
                name: 'RX-Byte',
                data: [0]
              },
              {
                name: 'TX-Byte',
                data: [0]
              }
              ],
              chart: {
                height: 350,
                type: 'area'
              },
              dataLabels: {
                enabled: false
              },
              stroke: {
                curve: 'smooth'
              },
              xaxis: {
                type: "datetime",
                categories: ["<?= gmdate(DATE_ISO8601); ?>"],
                labels: {
                  formatter: function (value) {

                    const d = new Date(value);

                    return d.toLocaleTimeString();
                  },
                  style: {
                    colors: '#64748b'
                  }
                },
              },
              yaxis: {
                labels: {
                  formatter: function (value) {

                    //convert it into kbps if it is less than 1
                    if (value < 0.5) {

                      return `${Math.round((value * 1000))} Kbps`;
                    }

                    return `${value} Mbps`;
                  },
                  style: {
                    colors: '#64748b'
                  }
                },
              },
              legend: {
                labels: {
                  colors: '#64748b'
                }
              },
              tooltip: {
                x: {
                  format: "dd/MM/yy HH:mm",
                },
              },
            };

            bandwidthChart = new ApexCharts(document.querySelector("#bandwidth_chart"), options);
            bandwidthChart.render();

          } else {

            rxArray.push(result.data.traffic.rxbyte);
            txArray.push(result.data.traffic.txbyte);
            categoryArray.push(result.data.traffic.date);

            if (categoryArray.length > 15) {
              rxArray.shift();
              txArray.shift();
              categoryArray.shift();
            }

            bandwidthChart.updateSeries([{
              data: rxArray,
            },
            {
              data: txArray,
            },
            ]);
            bandwidthChart.updateOptions({
              xaxis: {
                categories: categoryArray
              }
            });

          }

          $('#router_uptime').text(result.data.resource['up-time'] || '—');
          $('#time_updated').text(`${result.data.clock.date} at ${result.data.clock.time}`);
          $('#build_time').text(result.data.resource['build-time'] || '—');
          $('#free_memory').text(`${result.data.resource['free-memory']} / ${result.data.resource['total-memory']}`);
          $('#free_hdd').text(`${result.data.resource['free-hdd-space']} / ${result.data.resource['total-hdd-space']}`);
          $('#board_name').text(result.data.resource['board-name'] || '—');
          $('#router_version').text(result.data.resource['version'] || '—');
          $('#router_cpu').text(`${result.data.resource['cpu']} (${result.data.resource['cpu-count']} Cores) - ${result.data.resource['architecture-name']}`);
          $('#cpu_frequency').text((result.data.resource['cpu-frequency'] || '—') + (result.data.resource['cpu-frequency'] ? ' MHz' : ''));
          $('#router_serial').text(result.data.resource['serial-number'] || '—');
          $('#software_id').text(result.data.resource['software-id'] || '—');
          $('#router_firmware').text(result.data.resource['firmware'] || '—');
          $('#active_user_count').text(result.data.active ?? '—');
          $('#total_user_count').text(result.data.users ?? '—');

          trafficTimer = setTimeout(() => loadTrafic($('#interface').val()), 1000);
        },
        error: function (xhr) {
          let message = 'Failed to load router traffic';
          try {
            const result = JSON.parse(xhr.responseText);
            message = result.response || message;
          } catch (e) {}
          tata.error("Can't reach this router", message);
          showRouterOffline(true);
        }
      });
    }

    $(document).on('click', '#ipbRouterRetryBtn', function () {
      if (trafficTimer) {
        clearTimeout(trafficTimer);
      }
      showRouterOffline(false);
      loadTrafic($('#interface').val());
    });

    $('#interface').on('change', function () {
      if (trafficTimer) {
        clearTimeout(trafficTimer);
      }
      loadTrafic($(this).val());
    });

    loadTrafic();

    //scroll to bottom
    if ($('#logs').length) {
      $('#logs').scrollTop($('#logs')[0].scrollHeight);
    }
  });
</script>

<?= $this->endSection('script'); ?>
