<?php
/** @var object $details */
/** @var array $pppoe */
/** @var string $router */
/** @var string $router_name */
/** @var array $usage */
/** @var bool $mikrotik_pending */

$pppoe = $pppoe ?? [];
$router = $router ?? '--';
$router_name = $router_name ?? '--';
$usage = $usage ?? [];
$mikrotik_pending = !empty($mikrotik_pending);

$pppoeData = is_array($pppoe) ? $pppoe : [];
$pppoeName = $pppoeData['name'] ?? '--';
$pppoePassword = $pppoeData['password'] ?? '--';

// Prefer unmasked password from local DB when RouterOS would have obfuscated it
$routerPassData = function_exists('getRouterPassById') ? getRouterPassById($details->id) : null;
if (is_array($routerPassData) && !empty($routerPassData['router_password']) && !preg_match('/^\*+$/', $routerPassData['router_password'])) {
    $pppoePassword = $routerPassData['router_password'];
}

$pppoeService = $pppoeData['service'] ?? '--';
$pppoeProfile = $pppoeData['profile'] ?? '--';
$pppoeDisabled = $pppoeData['disabled'] ?? null;

?>


<?= $this->extend('layout/main-layout'); ?>
<?php $this->section('needsApexCharts'); ?>1<?php $this->endSection(); ?>

<?= $this->section('css'); ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<link rel="stylesheet" href="<?= base_url('assets/css/saas/customers-details.css?v=19'); ?>">
<?= $this->endSection('css'); ?>

<?= $this->section('content'); ?>

<?php
$isOffline = ($details->conn_status === 'disconn');
$userPkgTop = $user_package ?? (function_exists('getUserPackage') ? getUserPackage($details->id) : null);
$pkgArrayTop = is_object($userPkgTop) ? get_object_vars($userPkgTop) : (array) $userPkgTop;
$packageNameTop = $pkgArrayTop['package_name'] ?? '--';
$displayPriceTop = !empty($pkgArrayTop['selling_price']) ? $pkgArrayTop['selling_price'] : (!empty($pkgArrayTop['price']) ? $pkgArrayTop['price'] : null);
$areaTop = $user_area ?? null;
$areaLabel = $areaTop ? esc($areaTop->area_name) . ' (' . esc($areaTop->area_code) . ')' : '--';
$subAreaLabel = '--';
$subAreaObj = $user_sub_area ?? null;
if ($subAreaObj) {
  $subAreaLabel = esc($subAreaObj->area_name) . ' (' . esc($subAreaObj->area_code) . ')';
} elseif (!empty($ConnDetails) && isset($ConnDetails[0]['sub_area_id'])) {
  $subArea = getUserSubArea($ConnDetails[0]['sub_area_id']);
  $subAreaLabel = !empty($subArea) ? esc($subArea->area_name) . ' (' . esc($subArea->area_code) . ')' : '--';
}
$macText = 'Checking…';
$macBound = false;
$subLink = base_url('subscription/' . $details->id);
$expireDate = !empty($details->will_expire) ? new DateTime($details->will_expire) : null;
$today = new DateTime();
$diff = $expireDate ? $today->diff($expireDate) : null;
$daysLeft = $diff ? (int) $diff->format('%r%a') : null;
$daysLeftLabel = $daysLeft === null ? '--' : ($daysLeft . 'd');
$daysLeftClass = $daysLeft === null ? '' : ($daysLeft < 0 ? 'is-danger' : ($daysLeft <= 5 ? 'is-warn' : 'is-ok'));
$sessionLive = !empty($active_session);
$role = getSession('user_role');
$canRecharge = in_array($role, ['admin', 'resellerAdmin', 'employee'], true);

$connFields = [
  'connection_type' => 'Connection Type',
  'cable_requirement' => 'Cable Requirement',
  'fiber_code' => 'Fiber Code',
  'number_of_core' => 'Number of Core',
  'core_color' => 'Core Color',
  'client_type' => 'Client Type',
  'billing_status' => 'Billing Status',
  'otc' => 'OTC',
];
$hasConnData = false;
foreach ($connFields as $key => $label) {
  if (!empty($ConnDetails[0][$key])) {
    $hasConnData = true;
    break;
  }
}

$headerActions = '';
if (userHasPermission('customer', 'update')) {
  $headerActions .= '<a class="btn btn-default" href="' . route_to('route.customer.edit', $details->id) . '"><i class="fa fa-user-edit"></i> Edit</a>';
}
if ($canRecharge) {
  $headerActions .= '<a class="btn btn-primary" href="' . route_to('route.customer.subscription', $details->id) . '"><i class="fa fa-bolt"></i> Recharge</a>';
}
$headerActions .= '<a class="btn btn-default" href="' . route_to('route.customer') . '"><i class="fa fa-arrow-left"></i> Back</a>';
?>

<div class="content-wrapper ipb-customer-details">
  <section class="content">
    <?= $this->include('components/page-header', [
      'title' => 'Customer Details',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Customers', 'url' => route_to('route.customer')],
        ['label' => 'Customer Details'],
      ],
      'actions' => $headerActions,
    ]); ?>

    <div class="cd-hero">
      <div class="cd-hero-main">
        <div class="cd-avatar">
          <img src="<?= base_url('assets/img/icon/avatar.png'); ?>" alt="">
          <span class="cd-avatar-dot <?= $isOffline ? '' : 'is-online'; ?>"></span>
        </div>
        <div class="cd-hero-text">
          <h1>
            <?= esc($details->name); ?>
            <span class="cd-pill <?= $isOffline ? 'is-offline' : 'is-online'; ?>">
              <?= $isOffline ? 'Offline' : 'Online'; ?>
            </span>
            <?php if ($sessionLive): ?>
              <span class="cd-pill is-live">Live session</span>
            <?php endif; ?>
          </h1>
          <div class="cd-chips">
            <span class="cd-chip">ID <?= (int) $details->id; ?></span>
            <?php if (!empty($details->mobile)): ?>
              <span class="cd-chip"><i class="bi bi-phone"></i> <?= esc($details->mobile); ?></span>
            <?php endif; ?>
            <span class="cd-chip"><i class="bi bi-geo-alt"></i> <?= $areaTop ? esc($areaTop->area_name) : '--'; ?></span>
            <span class="cd-chip"><i class="bi bi-box-seam"></i> <?= esc($packageNameTop); ?></span>
          </div>
          <span id="macStatusBadge" class="cd-mac is-loading">
            <i class="bi bi-hourglass-split"></i>
            MAC: Checking…
          </span>
        </div>
      </div>

      <div class="cd-hero-actions">
        <?php if ($canRecharge): ?>
          <a href="<?= route_to('route.customer.subscription', $details->id); ?>" class="cd-btn cd-btn-primary">
            <i class="bi bi-lightning-charge"></i> Recharge
          </a>
        <?php endif; ?>
        <?php if (userHasPermission('customer', 'update')): ?>
          <a href="<?= route_to('route.customer.edit', $details->id); ?>" class="cd-btn cd-btn-ghost">
            <i class="bi bi-pencil"></i> Edit
          </a>
        <?php endif; ?>
        <div class="cd-more">
          <button type="button" class="cd-btn cd-btn-ghost" id="cdMoreBtn" aria-haspopup="true" aria-expanded="false">
            <i class="bi bi-three-dots"></i> More
          </button>
          <div class="cd-more-menu" id="cdMoreMenu" hidden>
            <button type="button" class="cd-more-item" onclick="macAction('bind')">
              <i class="bi bi-link"></i> Bind MAC
            </button>
            <button type="button" class="cd-more-item is-danger" onclick="macAction('unbind')">
              <i class="bi bi-unlock"></i> Unbind MAC
            </button>
            <a href="<?= route_to('route.audit.index'); ?>?id=<?= urlencode($details->id); ?>&pppoe_name=<?= urlencode($pppoeName); ?>" class="cd-more-item">
              <i class="bi bi-clipboard-data"></i> Audit logs
            </a>
            <button type="button" class="cd-more-item ipb-copy-sub-link" data-link="<?= esc($subLink, 'attr'); ?>">
              <i class="bi bi-link-45deg"></i> Copy subscription link
            </button>
            <a href="<?= route_to('route.customer.kick', $details->id); ?>"
               class="cd-more-item"
               onclick="return confirm('Refresh this user session?')">
              <i class="bi bi-arrow-clockwise"></i> Refresh session
            </a>
          </div>
        </div>
      </div>
    </div>

    <div class="cd-facts">
      <div class="cd-fact <?= $isOffline ? 'is-danger' : 'is-ok'; ?>">
        <span>Connection</span>
        <strong><?= $isOffline ? 'Offline' : 'Online'; ?></strong>
        <em><?= $sessionLive ? 'Active PPPoE session' : 'No live session'; ?></em>
      </div>
      <div class="cd-fact">
        <span>Package</span>
        <strong><?= esc($packageNameTop); ?></strong>
        <em><?= $areaTop ? esc($areaTop->area_name) : 'No area'; ?></em>
      </div>
      <div class="cd-fact <?= $daysLeftClass; ?>">
        <span>Expires</span>
        <strong><?= esc($daysLeftLabel); ?></strong>
        <em><?= !empty($details->will_expire) ? date('d M Y', strtotime($details->will_expire)) : 'No due date'; ?></em>
      </div>
      <div class="cd-fact <?= $macBound ? 'is-ok' : 'is-warn'; ?>">
        <span>MAC bind</span>
        <strong><?= $macBound ? 'Bound' : 'Unbound'; ?></strong>
        <em><?= esc($macText); ?></em>
      </div>
    </div>

    <div class="cd-layout">
      <div class="cd-stack">
        <div class="cd-card">
          <div class="cd-card-head">
            <h3><i class="bi bi-person-vcard"></i> Account</h3>
          </div>
          <div class="cd-card-body">
            <ul class="cd-rows">
              <li><span class="k">Full name</span><span class="v"><?= esc($details->name); ?></span></li>
              <li><span class="k">Mobile</span><span class="v"><?= esc($details->mobile); ?></span></li>
              <li><span class="k">Email</span><span class="v"><?= esc($details->email ?: '--'); ?></span></li>
              <li><span class="k">Service area</span><span class="v"><?= $areaLabel; ?></span></li>
              <li><span class="k">Sub-area</span><span class="v"><?= $subAreaLabel; ?></span></li>
              <li><span class="k">Joined</span><span class="v"><?= date('d M Y', strtotime($details->created_at)); ?></span></li>
              <li><span class="k">NID</span><span class="v"><?= esc($details->nid_number ?: '--'); ?></span></li>
              <li><span class="k">Code</span><span class="v"><?= esc($details->code ?: '--'); ?></span></li>
            </ul>
            <?php if (!empty($details->address)): ?>
              <div class="cd-address">
                <h4>Address</h4>
                <p><?= esc($details->address); ?></p>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <div class="cd-card">
          <div class="cd-card-head">
            <h3><i class="bi bi-diagram-3"></i> Connection details</h3>
          </div>
          <div class="cd-card-body">
            <?php if ($hasConnData): ?>
              <ul class="cd-rows">
                <?php foreach ($connFields as $key => $label):
                  if (empty($ConnDetails[0][$key])) continue;
                  $val = $ConnDetails[0][$key];
                  if ($key === 'cable_requirement') $val .= ' meter';
                ?>
                  <li><span class="k"><?= esc($label); ?></span><span class="v"><?= esc($val); ?></span></li>
                <?php endforeach; ?>
              </ul>
            <?php else: ?>
              <div class="cd-empty"><i class="bi bi-inbox"></i> No connection information available.</div>
            <?php endif; ?>
          </div>
        </div>

        <div class="cd-card">
          <div class="cd-card-head">
            <h3><i class="bi bi-activity"></i> Live traffic</h3>
          </div>
          <div class="cd-card-body">
            <div class="cd-traffic" id="bandwidth_info">
              <div>Download <strong id="rx_value">0 Kbps</strong></div>
              <div>Upload <strong id="tx_value">0 Kbps</strong></div>
            </div>
            <div id="bandwidth_chart"></div>
          </div>
        </div>
      </div>

      <div class="cd-stack">
        <div class="cd-card cd-session">
          <div class="cd-card-head">
            <h3><i class="bi bi-wifi"></i> Live session</h3>
            <a href="<?= route_to('route.customer.kick', $details->id); ?>"
               class="small-action-btn"
               title="Refresh session"
               onclick="return confirm('Refresh this user session?')">
              <i class="bi bi-arrow-clockwise"></i> Kick
            </a>
          </div>
          <div class="cd-session-banner <?= $sessionLive ? 'is-online' : 'is-offline'; ?>" id="cdSessionBanner">
            <div>
              <h4 id="cdSessionTitle"><?= $mikrotik_pending ? 'Checking session…' : ($sessionLive ? 'Session active' : 'No active session'); ?></h4>
              <p id="cdSessionSub"><?= $mikrotik_pending ? 'Loading live status from MikroTik.' : ($sessionLive ? 'Customer is connected on PPPoE right now.' : 'Customer is not connected at the moment.'); ?></p>
            </div>
            <span class="cd-pill <?= $sessionLive ? 'is-online' : 'is-offline'; ?>" id="cdSessionPill">
              <?= $mikrotik_pending ? 'Loading' : ($sessionLive ? 'Connected' : 'Disconnected'); ?>
            </span>
          </div>
          <div class="cd-session-metrics">
            <div class="cd-session-metric">
              <span>Duration</span>
              <strong id="cdSessionUptime"><?= !empty($active_session['uptime']) ? esc($active_session['uptime']) : ($mikrotik_pending ? '…' : '--'); ?></strong>
            </div>
            <div class="cd-session-metric">
              <span>Optical signal</span>
              <strong id="health_olt_rx"><i class="fa fa-spinner fa-spin"></i> Loading...</strong>
            </div>
            <div class="cd-session-metric">
              <span>Last logout</span>
              <strong id="cdLastLogout"><?= !empty($pppoe['last-logged-out']) ? esc($pppoe['last-logged-out']) : ($mikrotik_pending ? '…' : '--'); ?></strong>
            </div>
            <div class="cd-session-metric">
              <span>Last MAC</span>
              <strong id="cdLastMac"><?= !empty($pppoe['last-caller-id']) ? esc($pppoe['last-caller-id']) : ($mikrotik_pending ? '…' : '--'); ?></strong>
            </div>
            <div class="cd-session-metric">
              <span>OLT last seen</span>
              <strong id="health_olt_last_seen">--</strong>
            </div>
            <div class="cd-session-metric">
              <span>Disconnect reason</span>
              <strong id="health_olt_reason">--</strong>
            </div>
          </div>
        </div>

        <div class="cd-card">
          <div class="cd-card-head">
            <h3><i class="bi bi-box-seam"></i> Active plan</h3>
          </div>
          <div class="cd-card-body">
            <div class="cd-plan-grid">
              <div class="cd-mini">
                <span>Package</span>
                <strong><?= esc($packageNameTop); ?></strong>
                <em><?= !empty($displayPriceTop) ? esc($displayPriceTop) . '৳ / month' : '--'; ?></em>
              </div>
              <div class="cd-mini <?= $daysLeftClass; ?>">
                <span>Expires in</span>
                <strong><?= $daysLeft === null ? '--' : $daysLeft; ?> days</strong>
                <em>Due <?= !empty($details->will_expire) ? date('d M Y', strtotime($details->will_expire)) : '--'; ?></em>
              </div>
            </div>
          </div>
        </div>

        <div class="cd-card">
          <div class="cd-card-head">
            <h3><i class="bi bi-router"></i> Mikrotik PPPoE</h3>
            <button type="button" class="small-action-btn" onclick="checkPing()">Check ping</button>
          </div>
          <div class="cd-card-body">
            <div class="cd-info-grid">
              <div class="cd-mini"><span>Router</span><strong><?= esc($router); ?></strong></div>
              <div class="cd-mini"><span>Router name</span><strong id="cdRouterVendor"><?= esc($router_name); ?></strong></div>
              <div class="cd-mini"><span>PPPoE secret</span><strong id="cdPppoeName"><?= esc($pppoeName); ?></strong></div>
              <div class="cd-mini"><span>Password</span><strong id="cdPppoePass"><?= esc($pppoePassword); ?></strong></div>
              <div class="cd-mini"><span>Profile</span><strong id="cdPppoeProfile"><?= esc($pppoeProfile); ?></strong></div>
              <div class="cd-mini"><span>Service</span><strong id="cdPppoeService"><?= esc($pppoeService); ?></strong></div>
            </div>

            <div id="ping-results-box" class="ping-results-box">
              <span id="pingResult-<?= esc($pppoeName, 'attr'); ?>" class="ping-result-text">Not checked</span>
              <small id="pingStats-<?= esc($pppoeName, 'attr'); ?>" class="ping-stats-text"></small>
            </div>

            <?php if (userHasPermission('customer', 'update_conn')): ?>
              <div class="ipb-conn-control-title">Connection control</div>
              <div class="ipb-conn-control" id="cdConnControl">
                <span class="ipb-conn-control-label">Enable / disable internet access</span>
                <?php if ($mikrotik_pending): ?>
                  <span class="ipb-offline-pill" id="cdConnPending"><i class="fa fa-spinner fa-spin"></i> Loading…</span>
                <?php elseif ($pppoeDisabled !== null):
                  $status = ($pppoeDisabled === 'false') ? 'active' : 'inactive';
                  $is_checked = ($pppoeDisabled === 'false') ? 'checked="checked"' : null;
                  echo '<div class="ipb-switch material-switch">'
                    . '<input id="conn_status_' . $details->id . '" name="conn_status" type="checkbox" value="enable" data-status="' . $status . '" ' . $is_checked . ' />'
                    . '<label for="conn_status_' . $details->id . '" class="label-success"></label>'
                    . '</div>';
                else:
                  echo '<span class="ipb-offline-pill">Router offline</span>';
                endif; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <div class="cd-card">
          <div class="cd-card-head">
            <h3><i class="bi bi-broadcast-pin"></i> OLT / ONU</h3>
            <button type="button" id="refreshOltBtn" class="small-action-btn" onclick="refreshOltData()">
              <i class="fa fa-refresh"></i> Refresh
            </button>
          </div>
          <div class="cd-card-body">
            <div class="cd-info-grid">
              <div class="cd-mini"><span>OLT name</span><strong id="olt_name"><i class="fa fa-spinner fa-spin"></i></strong></div>
              <div class="cd-mini"><span>ONU ID</span><strong id="olt_onu_id"><i class="fa fa-spinner fa-spin"></i></strong></div>
              <div class="cd-mini"><span>Status</span><strong id="olt_status"><i class="fa fa-spinner fa-spin"></i></strong></div>
              <div class="cd-mini is-ok"><span>RX power</span><strong id="olt_rx"><i class="fa fa-spinner fa-spin"></i></strong></div>
              <div class="cd-mini"><span>MAC address</span><strong id="olt_mac"><i class="fa fa-spinner fa-spin"></i></strong></div>
              <div class="cd-mini"><span>Call ID</span><strong id="olt_call_id"><i class="fa fa-spinner fa-spin"></i></strong></div>
              <div class="cd-mini"><span>Matched ID</span><strong id="olt_matched_id"><i class="fa fa-spinner fa-spin"></i></strong></div>
              <div class="cd-mini"><span>Description</span><strong id="olt_desc"><i class="fa fa-spinner fa-spin"></i></strong></div>
            </div>
          </div>
        </div>

        <div class="cd-card">
          <div class="cd-card-head">
            <h3><i class="bi bi-bar-chart"></i> Bandwidth usage</h3>
          </div>
          <div class="cd-card-body">
            <div class="cd-bw-grid">
              <div class="cd-mini"><span>Download (today)</span><strong id="download-today">0 MB</strong></div>
              <div class="cd-mini"><span>Upload (today)</span><strong id="upload-today">0 MB</strong></div>
              <div class="cd-mini"><span>Total usage</span><strong id="total-usage">0 MB</strong></div>
              <div class="cd-mini"><span>Peak usage</span><strong id="peak-usage">0 MB</strong></div>
            </div>
            <div class="historical-chart-container" style="margin-top:14px">
              <div id="bandwidth_bar_chart" style="height: 320px; min-width: 100%;"></div>
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
  const userId = <?= $details->id ?>;

  function macAction(action) {
    const badge = document.getElementById('macStatusBadge');
    if (!badge) return;

    const originalContent = badge.innerHTML;
    const originalClass = badge.className;
    badge.className = 'cd-mac is-loading';
    badge.innerHTML = '<i class="bi bi-hourglass-split"></i> Loading...';

    fetch(`<?= base_url('customers/mac_ajax') ?>/${userId}/${action}`)
      .then(res => res.json())
      .then(data => {
        if (data.status === true || data.status === 'true') {
          badge.className = 'cd-mac is-bound';
          badge.innerHTML = `<i class="bi bi-link-45deg"></i> MAC: ${data.mac || 'Bound'}`;
        } else {
          badge.className = 'cd-mac is-unbound';
          badge.innerHTML = `<i class="bi bi-unlink"></i> MAC: Not Bound`;
        }
      })
      .catch(err => {
        badge.className = originalClass;
        badge.innerHTML = originalContent;
        showToast('Error performing MAC action', 'error');
      });
  }

  document.addEventListener('click', function (e) {
    var moreBtn = e.target.closest('#cdMoreBtn');
    var moreMenu = document.getElementById('cdMoreMenu');
    if (moreBtn && moreMenu) {
      e.preventDefault();
      var open = moreMenu.hasAttribute('hidden');
      if (open) moreMenu.removeAttribute('hidden');
      else moreMenu.setAttribute('hidden', '');
      moreBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
      return;
    }
    if (moreMenu && !e.target.closest('.cd-more')) {
      moreMenu.setAttribute('hidden', '');
      var btn = document.getElementById('cdMoreBtn');
      if (btn) btn.setAttribute('aria-expanded', 'false');
    }

    var copyBtn = e.target.closest('.ipb-copy-sub-link');
    if (!copyBtn) return;
    e.preventDefault();
    var link = copyBtn.getAttribute('data-link') || '';
    if (!link) return;
    var done = function () {
      if (window.tata && tata.success) tata.success('Copied', 'Subscription link copied');
      else showToast('Subscription link copied', 'success');
    };
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(link).then(done).catch(function () { window.prompt('Copy link:', link); });
    } else {
      window.prompt('Copy link:', link);
    }
  });

  function showToast(message, type = 'info') {
    if (typeof window.ipbToast === 'function') {
      window.ipbToast(message, type);
      return;
    }
    if (window.tata && typeof window.tata[type === 'error' ? 'error' : type === 'success' ? 'success' : 'info'] === 'function') {
      const method = type === 'error' ? 'error' : type === 'success' ? 'success' : 'info';
      window.tata[method](type === 'success' ? 'Success' : type === 'error' ? 'Error' : 'Notice', message);
      return;
    }
  }

  let userName = <?= json_encode($pppoeName) ?>;
  let callerid = <?= json_encode($callerid ?? '') ?>;
  const mikrotikPending = <?= $mikrotik_pending ? 'true' : 'false' ?>;
  const canUpdateConn = <?= userHasPermission('customer', 'update_conn') ? 'true' : 'false' ?>;

  function setText(id, value) {
    const el = document.getElementById(id);
    if (el) el.textContent = value == null || value === '' ? '--' : String(value);
  }

  function applyMikrotikInfo(data) {
    const online = !!(data && data.online);
    const pppoe = (data && data.pppoe) || {};
    const session = (data && data.active_session) || null;

    const banner = document.getElementById('cdSessionBanner');
    const pill = document.getElementById('cdSessionPill');
    if (banner) {
      banner.className = 'cd-session-banner ' + (online ? 'is-online' : 'is-offline');
    }
    setText('cdSessionTitle', online ? 'Session active' : (data && data.offline ? 'Router offline' : 'No active session'));
    setText('cdSessionSub', online
      ? 'Customer is connected on PPPoE right now.'
      : (data && data.offline ? (data.error || 'Unable to reach MikroTik.') : 'Customer is not connected at the moment.'));
    if (pill) {
      pill.className = 'cd-pill ' + (online ? 'is-online' : 'is-offline');
      pill.textContent = online ? 'Connected' : (data && data.offline ? 'Offline' : 'Disconnected');
    }
    setText('cdSessionUptime', session && session.uptime ? session.uptime : '--');
    setText('cdLastLogout', pppoe['last-logged-out'] || '--');
    setText('cdLastMac', pppoe['last-caller-id'] || (session && session['caller-id']) || '--');

    if (pppoe.name) {
      userName = pppoe.name;
      setText('cdPppoeName', pppoe.name);
      const audit = document.querySelector('a.cd-more-item[href*="pppoe_name="]');
      if (audit) {
        try {
          const u = new URL(audit.href, window.location.origin);
          u.searchParams.set('pppoe_name', pppoe.name);
          audit.href = u.pathname + u.search;
        } catch (e) {}
      }
    }
    if (pppoe.password && !/^\*+$/.test(pppoe.password)) setText('cdPppoePass', pppoe.password);
    if (pppoe.profile) setText('cdPppoeProfile', pppoe.profile);
    if (pppoe.service) setText('cdPppoeService', pppoe.service);

    if (data && data.callerid) callerid = data.callerid;

    if (canUpdateConn) {
      const slot = document.getElementById('cdConnControl');
      if (slot) {
        const pending = document.getElementById('cdConnPending');
        if (pending) pending.remove();
        const existing = slot.querySelector('.ipb-switch, .ipb-offline-pill');
        if (existing) existing.remove();
        if (data && data.ok && pppoe.disabled != null) {
          const status = pppoe.disabled === 'false' ? 'active' : 'inactive';
          const checked = pppoe.disabled === 'false' ? ' checked' : '';
          const wrap = document.createElement('div');
          wrap.className = 'ipb-switch material-switch';
          wrap.innerHTML = '<input id="conn_status_' + userId + '" name="conn_status" type="checkbox" value="enable" data-status="' + status + '"' + checked + ' />'
            + '<label for="conn_status_' + userId + '" class="label-success"></label>';
          slot.appendChild(wrap);
        } else {
          const pillOff = document.createElement('span');
          pillOff.className = 'ipb-offline-pill';
          pillOff.textContent = 'Router offline';
          slot.appendChild(pillOff);
        }
      }
    }
  }

  function loadMikrotikInfo() {
    return fetch('<?= route_to('route.customer.getMikrotikInfo'); ?>?user_id=' + encodeURIComponent(userId), {
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json' }
    })
      .then(function (res) { return res.json(); })
      .then(applyMikrotikInfo)
      .catch(function () {
        applyMikrotikInfo({ ok: false, offline: true, error: 'Failed to load MikroTik status', online: false });
      });
  }

  document.addEventListener('DOMContentLoaded', () => {
    // Parallel: MAC check + MikroTik live + OLT (OLT kicked from jQuery ready below too)
    macAction('check');
    if (mikrotikPending) loadMikrotikInfo();
  });

  const routerId = "<?= $details->router_id ?>";
  const resultId = 'pingResult-' + userName;

  function refreshOltData() {
    const btn = $('#refreshOltBtn');
    btn.html('<i class="fa fa-spinner fa-spin"></i> Loading...');
    btn.prop('disabled', true);
    const startTime = performance.now();

    $.ajax({
      url: '<?= route_to("route.customer.refreshOltData"); ?>',
      type: 'GET',
      data: { user_id: '<?= $details->id ?>', callerid: callerid },
      dataType: 'json',
      success: function (response) {
        const timeTaken = ((performance.now() - startTime) / 1000).toFixed(2);
        if (response.status === 'success') {
          $('#olt_name').text(response.data.name || '--');
          $('#olt_onu_id').text(response.data.onu_id || '--');
          $('#olt_mac').text(response.data.mac || '--');
          $('#olt_call_id').text(response.data.call_id || '--');
          $('#olt_matched_id').text(response.data.matched_id || '--');
          $('#olt_status').text(response.data.status || '--');
          $('#olt_rx').text(response.data.rx || '--');
          $('#olt_desc').text(response.data.description || '--');

          $('#health_olt_rx').text(response.data.rx || '--');
          $('#health_olt_last_seen').text(response.data.last_seen || '--');
          $('#health_olt_reason').text(response.data.reason || '--');

          tata.success('OLT data refreshed', `OLT data refreshed (${timeTaken}s)`);
        } else {
          tata.error("Couldn't refresh OLT data", 'Failed to fetch OLT data');
        }
        btn.html('<i class="fa fa-refresh"></i> Refresh');
        btn.prop('disabled', false);
      },
      error: function () {
        tata.error("Couldn't refresh OLT data", 'Request failed');
        btn.html('<i class="fa fa-refresh"></i> Refresh');
        btn.prop('disabled', false);
      }
    });
  }

  function checkPing() {
    const statsId = 'pingStats-' + userName;
    const resultEl = document.getElementById(resultId);
    const statsEl = document.getElementById(statsId);
    let countdown = 30;
    if (statsEl) statsEl.innerHTML = '';
    resultEl.innerHTML = `Checking... ${countdown}`;
    const timer = setInterval(() => {
      countdown--;
      if (countdown > 0) resultEl.innerHTML = `Checking... ${countdown}`;
      else { clearInterval(timer); resultEl.innerHTML = `Still checking...`; }
    }, 1000);

    fetch(`<?= route_to('route.customer.pingUserApi') ?>?router_id=${routerId}&name=${encodeURIComponent(userName)}`)
      .then(res => res.json())
      .then(data => {
        clearInterval(timer);
        if (data.status === 'success') {
          resultEl.innerHTML = `Average Latency: ${data.average_latency || 'OK'}`;
          if (data.packets) {
            statsEl.innerHTML = `Sent: ${data.packets.sent}, Received: ${data.packets.received}, Loss: ${data.packets.loss}`;
          }
        } else {
          resultEl.innerHTML = `<span style="color:var(--error-500,#dc2626);">${data.message}</span>`;
          if (statsEl) statsEl.innerHTML = '';
        }
      })
      .catch(err => {
        clearInterval(timer);
        resultEl.innerHTML = `<span style="color:var(--error-500,#dc2626);">Error</span>`;
        if (statsEl) statsEl.innerHTML = '';
      });
  }

  function updateBandwidthSummary() {
    const usage = <?= json_encode($usage) ?>;
    if (!usage || usage.length === 0) { setZeroSummary(); return; }
    const formatDate = d => d.toISOString().split('T')[0];
    const today = new Date();
    const todayStr = formatDate(today);
    const yesterday = new Date();
    yesterday.setDate(today.getDate() - 1);
    const yesterdayStr = formatDate(yesterday);
    let selectedDate = usage.some(u => u.date === todayStr) ? todayStr : (usage.some(u => u.date === yesterdayStr) ? yesterdayStr : null);
    if (!selectedDate) { setZeroSummary(); return; }
    const filteredUsage = usage.filter(u => u.date === selectedDate);
    let totalDownloadToday = 0, totalUploadToday = 0, totalUsage = 0, peakUsage = 0;
    filteredUsage.forEach(u => {
      const download = parseFloat(u.rx_today) || 0, upload = parseFloat(u.tx_today) || 0, dayTotal = download + upload;
      totalDownloadToday += download; totalUploadToday += upload; totalUsage += dayTotal;
      if (dayTotal > peakUsage) peakUsage = dayTotal;
    });
    document.getElementById('download-today').textContent = (totalDownloadToday / 1024 > 1) ? (totalDownloadToday / 1024).toFixed(1) + ' GB' : Math.round(totalDownloadToday) + ' MB';
    document.getElementById('upload-today').textContent = (totalUploadToday / 1024 > 1) ? (totalUploadToday / 1024).toFixed(1) + ' GB' : Math.round(totalUploadToday) + ' MB';
    document.getElementById('total-usage').textContent = (totalUsage / 1024 > 1) ? (totalUsage / 1024).toFixed(1) + ' GB' : Math.round(totalUsage) + ' MB';
    document.getElementById('peak-usage').textContent = peakUsage.toFixed(1) + ' Mbps';
  }

  function setZeroSummary() {
    document.getElementById('download-today').textContent = '0 MB';
    document.getElementById('upload-today').textContent = '0 MB';
    document.getElementById('total-usage').textContent = '0 MB';
    document.getElementById('peak-usage').textContent = '0 MB';
  }

  function initHistoricalChart() {
    const usage = <?= json_encode($usage ?? []) ?>;
    if (!usage || !Array.isArray(usage) || usage.length === 0) return;

    const chartEl = document.querySelector("#bandwidth_bar_chart");
    const containerWidth = chartEl.parentElement.offsetWidth;
    const calculatedWidth = usage.length * 80;
    chartEl.style.width = Math.max(containerWidth, calculatedWidth) + "px";

    const dates = usage.map(u => u.date);
    const rxData = usage.map(u => parseFloat(u.rx_today) || 0);
    const txData = usage.map(u => parseFloat(u.tx_today) || 0);

    const p = window.IpbTheme.chartPalette();
    const options = {
      series: [{ name: 'Download', data: rxData }, { name: 'Upload', data: txData }],
      chart: {
        type: 'bar',
        height: 300,
        width: '100%',
        toolbar: { show: false },
        background: 'transparent',
        animations: { enabled: true }
      },
      plotOptions: { bar: { horizontal: false, columnWidth: '55%', borderRadius: 6 } },
      dataLabels: { enabled: false },
      stroke: { show: true, width: 2, colors: ['transparent'] },
      xaxis: {
        categories: dates,
        labels: {
          rotate: -45,
          style: { fontSize: '12px', colors: p.axis }
        }
      },
      yaxis: { labels: { formatter: val => val.toFixed(0) + " MB", style: { colors: p.axis } } },
      fill: { opacity: 1 },
      colors: ['#f75803', '#16a34a'],
      legend: { position: 'top', labels: { colors: p.ink } },
      grid: { borderColor: p.grid }
    };
    const chart = new ApexCharts(chartEl, options);
    window.IpbTheme.registerChart(chart);
    chart.render().then(() => {
      const container = document.querySelector(".historical-chart-container");
      if (container) {
        container.scrollLeft = container.scrollWidth;
      }
    });
  }

  $(document).ready(function () {
    updateBandwidthSummary();
    initHistoricalChart();

    let bandwidthChart = false;
    let trafficPollStopped = false;
    const rxArray = [0], txArray = [0], categoryArray = ["<?= gmdate('c'); ?>"];

    // Navigating away via the sidebar swaps #ipb-main instead of reloading the
    // document — without this flag the 2s live-traffic poll below would keep
    // running (and hammering the router) forever.
    (window.IpbPageTeardown = window.IpbPageTeardown || []).push(function () {
      trafficPollStopped = true;
    });

    function loadTrafic(interface = "") {
      if (trafficPollStopped) return;
      $.ajax({
        url: `<?= route_to('route.routers.Usersload_Traffic', $details->router_id); ?>?interface=${interface}&pppoe_name=<?= rawurlencode($pppoeName); ?>`,
        type: 'GET',
        dataType: 'json',
        success: function (response) {
          const result = response.response || response;
          const rx = result.data?.traffic?.rxbyte ?? 0;
          const tx = result.data?.traffic?.txbyte ?? 0;
          function formatBandwidth(value) { return value < 0.5 ? `${Math.round(value * 1000)} Kbps` : `${value} Mbps`; }
          $("#rx_value").text(formatBandwidth(rx));
          $("#tx_value").text(formatBandwidth(tx));

          if (!bandwidthChart) {
            let options = {
              series: [{ name: 'Download', data: [0] }, { name: 'Upload', data: [0] }],
              chart: { height: 280, type: 'area', backgroundColor: 'transparent', toolbar: { show: false } },
              dataLabels: { enabled: false },
              stroke: { curve: 'smooth', width: 2 },
              xaxis: {
                type: "datetime",
                categories: ["<?= gmdate('c'); ?>"],
                labels: { formatter: value => new Date(value).toLocaleTimeString() }
              },
              yaxis: { labels: { formatter: value => value < 0.5 ? `${Math.round(value * 1000)} Kbps` : `${value} Mbps` } },
              colors: ['#16a34a', '#f75803'],
              fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.1 } }
            };
            bandwidthChart = new ApexCharts(document.querySelector("#bandwidth_chart"), options);
            window.IpbTheme.registerChart(bandwidthChart);
            bandwidthChart.render();
          } else if (result.data?.traffic) {
            rxArray.push(result.data.traffic.rxbyte);
            txArray.push(result.data.traffic.txbyte);
            categoryArray.push(result.data.traffic.date);
            if (categoryArray.length > 20) { rxArray.shift(); txArray.shift(); categoryArray.shift(); }
            bandwidthChart.updateSeries([{ data: rxArray }, { data: txArray }]);
            bandwidthChart.updateOptions({ xaxis: { categories: categoryArray } });
          }
          if (!trafficPollStopped) setTimeout(() => loadTrafic(), 2000);
        },
        error: function () { if (!trafficPollStopped) setTimeout(() => loadTrafic(), 5000); }
      });
    }
    loadTrafic();

    <?php if (userHasPermission('customer', 'update_conn')): ?>
      $(document).on('change', 'input[name="conn_status"]', function () {
        let status = $(this).data('status');
        const user = '<?= $details->id; ?>';
        status = status === 'active' ? 'inactive' : 'active';
        $.ajax({
          url: '<?= route_to("route.customer.update_conn_status"); ?>',
          type: 'POST',
          data: { status, user },
          headers: { '<?= csrf_header() ?>': '<?= csrf_hash() ?>' },
          success: function (result) {
            tata.success('Connection status updated', result.response, { onClose: () => { location.reload(); } });
          },
          error: function (response) {
            const result = jQuery.parseJSON(response.responseText);
            tata.error("Couldn't update connection status", result.response, { onClose: () => { location.reload(); } });
          }
        });
      });
    <?php endif; ?>

    refreshOltData();
  });
</script>

<?= $this->endSection('script'); ?>
