<?= $this->extend('layout/main-layout'); ?>

<?= $this->section('css'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/saas/dashboard.css?v=18'); ?>">
<?= $this->endSection('css'); ?>

<?= $this->section('content'); ?>

<div class="content-wrapper">
  <section class="content ipb-saas-list">
    <?= view('components/page-header', [
      'title' => 'Hotspot Dashboard',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Hotspot Dashboard'],
      ],
      'actions' => '<button type="button" class="btn btn-default" id="hotspotRouterBtn"><i class="fa fa-server" aria-hidden="true"></i> Change router</button>',
    ]); ?>

    <p class="text-mut" style="margin:-8px 0 16px;font-weight:600" id="routerBadge">Select a router to load live stats</p>

    <div class="ipb-dash fade-in">
      <div class="ipb-dash-kpi">
        <div class="ipb-kpi tone-info compact">
          <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa fa-calendar" aria-hidden="true"></i></span></div>
          <div class="ipb-kpi-value" id="sysTime" style="font-size:18px">--</div>
          <div class="ipb-kpi-label">System date &amp; time</div>
          <div class="text-mut" style="font-size:12px;margin-top:6px;font-weight:600" id="uptime">Uptime: --</div>
        </div>
        <div class="ipb-kpi tone-navy compact">
          <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa fa-info-circle" aria-hidden="true"></i></span></div>
          <div class="ipb-kpi-value" id="boardName" style="font-size:18px">--</div>
          <div class="ipb-kpi-label">Router board</div>
          <div class="text-mut" style="font-size:12px;margin-top:6px;font-weight:600" id="routerOS">RouterOS: --</div>
        </div>
        <div class="ipb-kpi tone-warning compact">
          <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa fa-server" aria-hidden="true"></i></span></div>
          <div class="ipb-kpi-label" style="margin-top:8px">System resource</div>
          <div class="text-mut" style="font-size:12.5px;margin-top:8px;line-height:1.55;font-weight:600">
            CPU: <b id="cpuLoad">--</b><br>
            Free RAM: <b id="freeRam">--</b><br>
            Free HDD: <b id="freeHdd">--</b>
          </div>
        </div>
      </div>

      <div class="ipb-dash-kpi">
        <a href="<?= route_to('route.hotspot.users'); ?>" class="ipb-kpi tone-success">
          <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa fa-signal" aria-hidden="true"></i></span></div>
          <div class="ipb-kpi-value" id="hotspotactive">0</div>
          <div class="ipb-kpi-label">Hotspot active</div>
          <div class="ipb-kpi-cta">View details <i class="fa fa-chevron-right" aria-hidden="true"></i></div>
        </a>
        <a href="<?= route_to('route.hotspot.users'); ?>" class="ipb-kpi tone-brand">
          <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa fa-users" aria-hidden="true"></i></span></div>
          <div class="ipb-kpi-value" id="hotspotusers">0</div>
          <div class="ipb-kpi-label">Hotspot users</div>
          <div class="ipb-kpi-cta">Manage users <i class="fa fa-chevron-right" aria-hidden="true"></i></div>
        </a>
        <a href="<?= route_to('route.hotspot.users'); ?>" class="ipb-kpi tone-info">
          <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa fa-user-plus" aria-hidden="true"></i></span></div>
          <div class="ipb-kpi-value">+</div>
          <div class="ipb-kpi-label">Add hotspot user</div>
          <div class="ipb-kpi-cta">Add user <i class="fa fa-chevron-right" aria-hidden="true"></i></div>
        </a>
        <a href="<?= route_to('route.hotspot.users'); ?>" class="ipb-kpi tone-navy">
          <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa fa-qrcode" aria-hidden="true"></i></span></div>
          <div class="ipb-kpi-value" style="font-size:22px">QR</div>
          <div class="ipb-kpi-label">Generate voucher</div>
          <div class="ipb-kpi-cta">Generate <i class="fa fa-chevron-right" aria-hidden="true"></i></div>
        </a>
      </div>

      <div class="ipb-dash-kpi">
        <a href="<?= route_to('hotspot.report'); ?>" class="ipb-kpi tone-warning">
          <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa fa-money-bill" aria-hidden="true"></i></span></div>
          <div class="ipb-kpi-value" id="todayIncome">৳0.00</div>
          <div class="ipb-kpi-label">Today's income</div>
          <div class="ipb-kpi-cta">View report <i class="fa fa-chevron-right" aria-hidden="true"></i></div>
        </a>
        <a href="<?= route_to('hotspot.report'); ?>" class="ipb-kpi tone-brand">
          <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa fa-chart-line" aria-hidden="true"></i></span></div>
          <div class="ipb-kpi-value" id="monthIncome">৳0.00</div>
          <div class="ipb-kpi-label">Monthly income</div>
          <div class="ipb-kpi-cta">View report <i class="fa fa-chevron-right" aria-hidden="true"></i></div>
        </a>
      </div>

      <div class="box box-solid">
        <div class="box-header with-border">
          <h3 class="box-title"><i class="fa fa-list" aria-hidden="true"></i> Recent activity</h3>
        </div>
        <div class="box-body ipb-skel-swap" id="hotspotLog">
          <div class="ipb-skeleton ipb-skeleton-list">
            <?php for ($i = 0; $i < 6; $i++): ?>
              <div class="ipb-skeleton-list-row">
                <span class="ipb-skeleton ipb-skeleton-text is-sm" style="width:65%"></span>
                <span class="ipb-skeleton ipb-skeleton-text is-sm" style="width:15%"></span>
              </div>
            <?php endfor; ?>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<div class="modal fade" id="routerContextModal" data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="routerContextTitle">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="routerContextTitle">Select router</h4>
      </div>
      <div class="modal-body">
        <label class="control-label" for="ctx_router">Router</label>
        <select id="ctx_router" class="form-control" aria-label="Router"></select>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="ctx_router_save">Use router</button>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection('content'); ?>

<?= $this->section('script'); ?>
<script>
(function () {
  var USER_ID = <?= (int) (getSession('user_id') ?? 0); ?>;
  var HOTSPOT_CTX_KEY = 'hotspot_ctx';
  var routers = <?= json_encode($routers ?? []); ?>;

  function getCtx() {
    try {
      return JSON.parse(localStorage.getItem(HOTSPOT_CTX_KEY));
    } catch (e) {
      return null;
    }
  }

  function saveCtx() {
    var $sel = $('#ctx_router');
    var ctx = {
      user_id: USER_ID,
      router_id: $sel.val(),
      router_name: $sel.find('option:selected').text()
    };
    localStorage.setItem(HOTSPOT_CTX_KEY, JSON.stringify(ctx));
    $('#routerContextModal').modal('hide');
    $('#routerBadge').text('Router: ' + ctx.router_name);
    loadDashboard();
  }

  function populateRouters() {
    var $select = $('#ctx_router');
    $select.empty();
    (routers || []).forEach(function (r) {
      $select.append(new Option(r.name, r.id));
    });
  }

  function escHtml(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function loadDashboard() {
    var ctx = getCtx();
    if (!ctx || !ctx.router_id) return;

    $.getJSON("<?= route_to('route.user.dashboard.get'); ?>", {
      router_id: ctx.router_id
    }).done(function (res) {
      if (!res) return;
      if (res.system) {
        $('#sysTime').text(res.system.time || '--');
        $('#uptime').text('Uptime: ' + (res.system.uptime || '--'));
      }
      if (res.board) {
        $('#boardName').text(res.board.name || '--');
        $('#routerOS').text('RouterOS: ' + (res.board.os || '--'));
      }
      if (res.resource) {
        $('#cpuLoad').text((res.resource.cpu != null ? res.resource.cpu : '--') + '%');
        $('#freeRam').text(res.resource.ram || '--');
        $('#freeHdd').text(res.resource.hdd || '--');
      }
      if (res.hotspot) {
        $('#hotspotactive').text(res.hotspot.active != null ? res.hotspot.active : '0');
        $('#hotspotusers').text(res.hotspot.users != null ? res.hotspot.users : '0');
      }
      if (res.income) {
        $('#todayIncome').text('৳' + (res.income.today != null ? res.income.today : '0.00'));
        $('#monthIncome').text('৳' + (res.income.month != null ? res.income.month : '0.00'));
      }

      var items = (res.logs && res.logs.items) ? res.logs.items : [];
      var logHtml = '<ul class="ipb-hotspot-log">';
      if (!items.length) {
        logHtml += '<li class="text-mut">No hotspot activity found</li>';
      } else {
        items.forEach(function (l) {
          var msg = (l.message || '').toLowerCase();
          var tone = msg.indexOf('log in') !== -1 ? 'is-ok' : (msg.indexOf('logged out') !== -1 ? 'is-err' : 'is-mut');
          logHtml +=
            '<li class="' + tone + '">' +
              '<span class="ipb-hotspot-log-time">' + escHtml(l.time) + '</span>' +
              '<span class="ipb-hotspot-log-msg">' + escHtml(l.message) + '</span>' +
            '</li>';
        });
      }
      logHtml += '</ul>';
      swapSkeletonHtml('hotspotLog', logHtml);
    }).fail(function () {
      swapSkeletonHtml('hotspotLog', ipbErrorStateHtml({
        title: 'Could not load hotspot stats',
        subtitle: 'We could not reach this router. Check the router context and try again.',
        retry: 'loadDashboard()'
      }));
    });
  }

  $(function () {
    populateRouters();
    $('#ctx_router_save').on('click', saveCtx);
    $('#hotspotRouterBtn').on('click', function () {
      $('#routerContextModal').modal('show');
    });

    var ctx = getCtx();
    if (!ctx || ctx.user_id !== USER_ID) {
      $('#routerContextModal').modal('show');
    } else {
      $('#routerBadge').text('Router: ' + (ctx.router_name || ''));
      loadDashboard();
    }
    setInterval(loadDashboard, 30000);
  });
})();
</script>
<?= $this->endSection('script'); ?>
