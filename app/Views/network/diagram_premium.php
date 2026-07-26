<?= $this->extend('layout/main-layout'); ?>

<?= $this->section('css'); ?>
<?= saas_css('network-pages.css') ?>
<link rel="stylesheet" href="<?= base_url('assets/map/leaflet.css'); ?>">
<?= $this->endSection('css'); ?>

<?= $this->section('content'); ?>
<?php
$olts = is_array($olts ?? null) ? $olts : [];
$areas = is_array($areas ?? null) ? $areas : [];
$hasOlts = count($olts) > 0;
?>
<div class="content-wrapper">
  <section class="content ipb-net-page ipb-premium-net">
    <?= $this->include('components/page-header', [
      'title' => 'Premium Network Diagram',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Network'],
        ['label' => 'Premium Diagram'],
      ],
      'subtitle' => 'Live OLT → PON → splitter → ONU topology',
    ]); ?>

    <div class="ipb-premium-kpi">
      <div class="ipb-premium-stat">
        <span class="ipb-premium-stat-icon is-olt"><i class="fa fa-server" aria-hidden="true"></i></span>
        <div>
          <div class="ipb-premium-stat-label">Total OLT</div>
          <div class="ipb-premium-stat-value" id="stat-total-olts"><?= count($olts); ?></div>
        </div>
      </div>
      <div class="ipb-premium-stat">
        <span class="ipb-premium-stat-icon is-onu"><i class="fa fa-network-wired" aria-hidden="true"></i></span>
        <div>
          <div class="ipb-premium-stat-label">Total ONU</div>
          <div class="ipb-premium-stat-value" id="stat-total-onus">0</div>
        </div>
      </div>
      <div class="ipb-premium-stat">
        <span class="ipb-premium-stat-icon is-online"><i class="fa fa-circle-check" aria-hidden="true"></i></span>
        <div>
          <div class="ipb-premium-stat-label">Online ONU</div>
          <div class="ipb-premium-stat-value is-online" id="stat-online-onus">0</div>
        </div>
      </div>
      <div class="ipb-premium-stat">
        <span class="ipb-premium-stat-icon is-offline"><i class="fa fa-circle-xmark" aria-hidden="true"></i></span>
        <div>
          <div class="ipb-premium-stat-label">Offline ONU</div>
          <div class="ipb-premium-stat-value is-offline" id="stat-offline-onus">0</div>
        </div>
      </div>
    </div>

    <div class="ipb-premium-toolbar">
      <div class="ipb-premium-field">
        <label for="filter-olt">OLT</label>
        <select id="filter-olt" class="form-control">
          <?php if (!$hasOlts): ?>
            <option value="">No OLT configured</option>
          <?php else: ?>
            <?php foreach ($olts as $o): ?>
              <option value="<?= (int) ($o['id'] ?? 0); ?>"><?= esc(($o['olt_name'] ?? 'OLT') . ' (' . strtoupper((string) ($o['brand'] ?? '')) . ')'); ?></option>
            <?php endforeach; ?>
          <?php endif; ?>
        </select>
      </div>
      <div class="ipb-premium-field">
        <label for="filter-pon">PON Port</label>
        <select id="filter-pon" class="form-control">
          <option value="All">All Ports</option>
        </select>
      </div>
      <div class="ipb-premium-field">
        <label for="filter-zone">Zone</label>
        <select id="filter-zone" class="form-control">
          <option value="All">All Zones</option>
          <?php foreach ($areas as $a): ?>
            <?php
              $areaId = is_object($a) ? ($a->id ?? '') : ($a['id'] ?? '');
              $areaName = is_object($a) ? ($a->area_name ?? '') : ($a['area_name'] ?? '');
            ?>
            <option value="<?= esc((string) $areaId); ?>"><?= esc((string) $areaName); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="ipb-premium-field">
        <label for="filter-search">Search ONU</label>
        <input type="text" id="filter-search" class="form-control" placeholder="MAC, description…">
      </div>
      <div class="ipb-premium-actions">
        <button type="button" id="btn-sync" class="btn btn-primary" <?= $hasOlts ? '' : 'disabled'; ?>>
          <i class="fa fa-sync-alt" aria-hidden="true"></i> Refresh Sync
        </button>
        <button type="button" id="btn-auto-refresh" class="btn btn-default" <?= $hasOlts ? '' : 'disabled'; ?>>
          <i class="fa fa-clock" aria-hidden="true"></i> Auto Refresh
        </button>
        <button type="button" id="btn-view-diagram" class="btn btn-dark">
          <i class="fa fa-sitemap" aria-hidden="true"></i> Diagram
        </button>
        <button type="button" id="btn-view-map" class="btn btn-default">
          <i class="fa fa-map" aria-hidden="true"></i> Map View
        </button>
        <a href="<?= route_to('network.diagram'); ?>" class="btn btn-default">
          <i class="fa fa-pen" aria-hidden="true"></i> Manual Diagram
        </a>
      </div>
    </div>

    <div class="ipb-premium-layout">
      <div class="ipb-premium-canvas" id="canvas-outer">
        <div class="ipb-premium-zoom zoom-controls">
          <button type="button" class="btn btn-default zoom-btn" id="zoom-in" title="Zoom In"><i class="fa fa-plus" aria-hidden="true"></i></button>
          <button type="button" class="btn btn-default zoom-btn" id="zoom-out" title="Zoom Out"><i class="fa fa-minus" aria-hidden="true"></i></button>
          <button type="button" class="btn btn-default zoom-btn" id="zoom-fit" title="Fit to Screen"><i class="fa fa-expand" aria-hidden="true"></i></button>
          <button type="button" class="btn btn-default zoom-btn" id="btn-expand-all" title="Expand All"><i class="fa fa-sitemap" aria-hidden="true"></i></button>
        </div>

        <div class="ipb-premium-canvas-inner canvas-inner" id="canvas-inner">
          <div id="diagram-scale-wrapper">
            <div id="tree-root-container">
              <?php if (!$hasOlts): ?>
                <div class="loading-state">
                  <i class="fa fa-server fa-2x" aria-hidden="true"></i>
                  <div><strong>No OLT devices found</strong></div>
                  <div>Add an OLT first, then sync topology here.</div>
                </div>
              <?php else: ?>
                <div class="loading-state">
                  <div class="spinner" aria-hidden="true"></div>
                  <div>Loading network topology…</div>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div id="map-view-container">
          <div id="main-map" role="img" aria-label="Network map view"></div>
        </div>

        <div class="ipb-premium-legend diagram-legend">
          <span class="ipb-premium-legend-item"><span class="ipb-premium-legend-dot" style="background:#2563eb"></span> OLT</span>
          <span class="ipb-premium-legend-item"><span class="ipb-premium-legend-dot" style="background:#7c3aed"></span> PON Port</span>
          <span class="ipb-premium-legend-item"><span class="ipb-premium-legend-dot" style="background:#d97706"></span> Splitter</span>
          <span class="ipb-premium-legend-item"><span class="ipb-premium-legend-dot" style="background:#16a34a"></span> Online ONU</span>
          <span class="ipb-premium-legend-item"><span class="ipb-premium-legend-dot" style="background:#dc2626"></span> Offline ONU</span>
          <span id="zoom-level-label">100%</span>
        </div>
      </div>

      <aside class="ipb-premium-details details-sidebar" id="node-details-sidebar" aria-live="polite">
        <div class="ipb-premium-details-head sidebar-title">
          <span id="sidebar-type-label"><i class="fa fa-info-circle" aria-hidden="true"></i> Details</span>
          <button type="button" class="btn btn-default btn-sm" id="btn-close-details" aria-label="Close details">✕</button>
        </div>
        <div id="sidebar-content"></div>
      </aside>
    </div>
  </section>
</div>
<?= $this->endSection('content'); ?>

<?= $this->section('script'); ?>
<script src="<?= base_url('assets/map/leaflet.js'); ?>"></script>
<script>
(function () {
    function bootPremiumDiagram() {
        if (typeof window.jQuery === 'undefined') {
            setTimeout(bootPremiumDiagram, 40);
            return;
        }

    function initApp($) {
        function escapeHtml(str) {
            return String(str == null ? '' : str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        let mapInstance = null;
        let autoRefreshInterval = null;
        let currentOltId = $('#filter-olt').val();
        let topologyCache = null;
        let zoomLevel = 1;

        (window.IpbPageTeardown = window.IpbPageTeardown || []).push(function () {
            if (autoRefreshInterval) {
                clearInterval(autoRefreshInterval);
                autoRefreshInterval = null;
            }
            $(document).off('click.tree click.splitter click.user');
            if (mapInstance) {
                try { mapInstance.remove(); } catch (e) {}
                mapInstance = null;
            }
        });

        // ---
        if (currentOltId) {
            loadPonPorts(currentOltId);
            loadTopology(currentOltId);
        }

        // ---
        $('#filter-olt').on('change', function() {
            currentOltId = $(this).val();
            loadPonPorts(currentOltId);
            loadTopology(currentOltId);   // load cached data immediately
            doSync();                     // sync fresh data in background
        });

        // ---
        $('#filter-pon, #filter-zone').on('change', function() {
            if (topologyCache) renderTopology(topologyCache);
        });
        $('#filter-search').on('keyup', function() {
            if (topologyCache) renderTopology(topologyCache);
        });

        // ---
        function doSync(showFeedback) {
            if (!currentOltId) return;
            var syncUrl = '<?= base_url('network_sync/') ?>' + currentOltId;
            $.ajax({
                type: 'POST',
                url: syncUrl,
                data: {},
                dataType: 'json',
                timeout: 90000,
                success: function(res) {
                    if (res.status === 'success') {
                        if (showFeedback) tata.success('Synced!', res.message || 'Data synchronized.', {duration: 3000});
                        loadTopology(currentOltId);
                    } else {
                        if (showFeedback) tata.error('Sync Failed', res.message || 'OLT returned an error.', {duration: 5000});
                    }
                },
                error: function(xhr, status) {
                    if (showFeedback) {
                        var msg = status === 'timeout' ? 'OLT timed out.' : (xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Failed to connect to OLT.');
                        tata.error('Sync Error', msg, {duration: 6000});
                    }
                }
            });
        }

        // ---
        $('#btn-sync').on('click', function() {
            if (!currentOltId) return;
            const btn = $(this);
            btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Syncing...');
            var syncUrl = '<?= base_url('network_sync/') ?>' + currentOltId;
            $.ajax({
                type: 'POST',
                url: syncUrl,
                data: {},
                dataType: 'json',
                timeout: 90000,
                success: function(res) {
                    btn.prop('disabled', false).html('<i class="fa fa-sync-alt"></i> Refresh Sync');
                    if (res.status === 'success') {
                        tata.success('Synced!', res.message, {duration: 3000});
                        loadTopology(currentOltId);
                    } else {
                        tata.error('Sync Failed', res.message, {duration: 5000});
                    }
                },
                error: function(xhr, status) {
                    btn.prop('disabled', false).html('<i class="fa fa-sync-alt"></i> Refresh Sync');
                    var msg = status === 'timeout' ? 'Request timed out. OLT may be slow or unreachable.' : (xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Connection to OLT failed.');
                    tata.error('Sync Error', msg, {duration: 5000});
                }
            });
        });

        // ---
        $('#btn-auto-refresh').on('click', function() {
            const btn = $(this);
            if (autoRefreshInterval) {
                clearInterval(autoRefreshInterval);
                autoRefreshInterval = null;
                btn.removeClass('btn-success').addClass('btn-outline-secondary').html('<i class="fa fa-clock"></i> Auto Refresh');
            } else {
                doSync(false); // sync immediately when enabling auto
                autoRefreshInterval = setInterval(function() {
                    doSync(false);
                }, 60000); // every 60 seconds
                btn.removeClass('btn-outline-secondary').addClass('btn-success').html('<i class="fa fa-clock"></i> Auto: ON');
            }
        });


        // ---
        $('#btn-close-details').on('click', function () {
            $('#node-details-sidebar').hide();
        });

        $('#btn-view-diagram').on('click', function() {
            $('#canvas-inner').show();
            $('.zoom-controls, .diagram-legend').show();
            $('#map-view-container').hide();
            $(this).addClass('btn-dark').removeClass('btn-outline-secondary');
            $('#btn-view-map').removeClass('btn-dark').addClass('btn-outline-secondary');
        });
        $('#btn-view-map').on('click', function() {
            $('#canvas-inner').hide();
            $('.zoom-controls, .diagram-legend').hide();
            $('#map-view-container').show();
            $(this).addClass('btn-dark').removeClass('btn-outline-secondary');
            $('#btn-view-diagram').removeClass('btn-dark').addClass('btn-outline-secondary');
            initMapView();
        });

        // ---
        function applyZoom(level) {
            zoomLevel = Math.min(Math.max(level, 0.3), 2);
            $('#diagram-scale-wrapper').css('transform', 'scale(' + zoomLevel + ')');
            $('#zoom-level-label').text(Math.round(zoomLevel * 100) + '%');
        }
        $('#zoom-in').on('click', function() { applyZoom(zoomLevel + 0.15); });
        $('#zoom-out').on('click', function() { applyZoom(zoomLevel - 0.15); });
        $('#zoom-fit').on('click', function() { applyZoom(1); });
        $('#btn-expand-all').on('click', function() {
            $('.tree-children.collapsed').removeClass('collapsed');
            $('.node-card').addClass('expanded');
        });

        // Mouse wheel zoom on canvas
        $('#canvas-outer').on('wheel', function(e) {
            if (e.originalEvent.ctrlKey) {
                e.preventDefault();
                const delta = e.originalEvent.deltaY > 0 ? -0.1 : 0.1;
                applyZoom(zoomLevel + delta);
            }
        });

        // ---
        function loadPonPorts(oltId) {
            $.get('<?= route_to("network.ports", 0); ?>'.replace('/0', '/' + oltId), function(res) {
                if (res.status === 'success') {
                    let html = '<option value="All">All Ports</option>';
                    res.ports.forEach(function(port) {
                        html += '<option value="' + port + '">' + port + '</option>';
                    });
                    $('#filter-pon').html(html);
                }
            });
        }

        // ---
        function loadTopology(oltId) {
            const pon = $('#filter-pon').val() || 'All';
            const zone = $('#filter-zone').val() || 'All';
            const search = $('#filter-search').val() || '';

            $('#tree-root-container').html('<div class="loading-state"><div class="spinner"></div><div>Loading network topology...</div></div>');
            $('#node-details-sidebar').hide();

            $.get('<?= route_to("network.topology", 0); ?>'.replace('/0', '/' + oltId), {
                pon_port: pon, zone: zone, search: search
            }, function(res) {
                if (res.status === 'success') {
                    topologyCache = res;
                    $('#stat-total-onus').text(res.stats.total_onus);
                    $('#stat-online-onus').text(res.stats.online);
                    $('#stat-offline-onus').text(res.stats.offline);
                    renderTopology(res);
                } else {
                    $('#tree-root-container').html('<div class="loading-state"><i class="fa fa-triangle-exclamation fa-2x text-warning"></i><div>' + (res.message || 'No data found') + '</div></div>');
                }
            }).fail(function() {
                $('#tree-root-container').html('<div class="loading-state"><i class="fa fa-triangle-exclamation fa-2x text-danger"></i><div>Failed to load topology.</div></div>');
            });
        }

        // ---
        function renderTopology(res) {
            if (!res || !res.tree || res.tree.length === 0) {
                $('#tree-root-container').html('<div class="loading-state"><i class="fa fa-diagram-project fa-2x" style="color:#cbd5e1;"></i><div style="color:#94a3b8;">No data. Click <b>Refresh Sync</b> to load from OLT.</div></div>');
                return;
            }

            const selPon = $('#filter-pon').val() || 'All';
            const olt = res.olt;

            // -- OLT Card --
            let html = `
            <div class="tree-row">
                <div class="tree-node" style="position:relative;">
                    <div class="node-card olt-card expanded" data-type="olt" data-toggle-target="olt-children">
                        <div class="card-header">
                            <div class="card-icon c-blue"><i class="fa fa-server"></i></div>
                            <span class="card-tag tag-blue">${escapeHtml(olt.brand)}</span>
                            <span class="toggle-arrow">â–¶</span>
                        </div>
                        <div class="card-name">${escapeHtml(olt.name)}</div>
                        <div class="card-meta">IP: ${escapeHtml(olt.ip)}:${escapeHtml(olt.port)}</div>
                        <div class="card-meta">PON Ports: ${olt.total_pon}</div>
                        <div class="card-stats">
                            <span class="s-online"><i class="fa fa-circle" style="font-size:8px;"></i> ${res.stats.online} Online</span>
                            <span class="s-offline"><i class="fa fa-circle" style="font-size:8px;"></i> ${res.stats.offline} Offline</span>
                        </div>
                    </div>
                </div>

                <div class="tree-children" id="olt-children">`;

            res.tree.forEach(function(pon, pi) {
                if (selPon !== 'All' && pon.name !== selPon) return;

                const ponId = 'pon-' + pi;
                html += `
                    <div class="tree-node">
                        <div class="node-card pon-card" data-type="pon" data-toggle-target="${ponId}" data-pon="${escapeHtml(pon.name)}">
                            <div class="card-header">
                                <div class="card-icon c-purple"><i class="fa fa-plug-circle-plus"></i></div>
                                <span class="card-tag tag-purple">Port</span>
                                <span class="toggle-arrow">â–¶</span>
                            </div>
                            <div class="card-name">${escapeHtml(pon.name)}</div>
                            <div class="card-meta">Capacity: ${escapeHtml(pon.capacity)}</div>
                            <div class="card-meta">ONUs: ${pon.total}</div>
                            <div class="card-stats">
                                <span class="s-online"><i class="fa fa-circle" style="font-size:8px;"></i> ${pon.online}</span>
                                <span class="s-offline"><i class="fa fa-circle" style="font-size:8px;"></i> ${pon.offline}</span>
                            </div>
                        </div>
                        <div class="tree-children collapsed" id="${ponId}">`;

                Object.values(pon.splitters).forEach(function(splitter, si) {
                    const spId = ponId + '-sp-' + si;
                    html += `
                            <div class="tree-node">
                                <div class="node-card splitter-card" data-type="splitter" data-toggle-target="${spId}" data-pon="${escapeHtml(pon.name)}" data-name="${escapeHtml(splitter.name)}">
                                    <div class="card-header">
                                        <div class="card-icon c-orange"><i class="fa fa-share-nodes"></i></div>
                                        <span class="card-tag tag-orange">Splitter</span>
                                        <span class="toggle-arrow">â–¶</span>
                                    </div>
                                    <div class="card-name">${escapeHtml(splitter.name)}</div>
                                    <div class="card-meta">Users: ${splitter.total}</div>
                                    <div class="card-stats">
                                        <span class="s-online"><i class="fa fa-circle" style="font-size:8px;"></i> ${splitter.online}</span>
                                        <span class="s-offline"><i class="fa fa-circle" style="font-size:8px;"></i> ${splitter.offline}</span>
                                    </div>
                                </div>
                                <div class="tree-children collapsed" id="${spId}">`;

                    splitter.users.forEach(function(user, ui) {
                        const isOnline = user.status.toLowerCase() === 'online';
                        const rx = parseFloat(user.rx_power) || 0;
                        let signalClass = 'good';
                        if (rx < -28) signalClass = 'medium';
                        if (rx < -31) signalClass = 'poor';

                        html += `
                                    <div class="tree-node">
                                        <div class="node-card user-card ${isOnline ? '' : 'offline'}" data-type="user"
                                            data-id="${user.id}" data-mac="${escapeHtml(user.mac)}"
                                            data-status="${escapeHtml(user.status)}" data-rx="${escapeHtml(user.rx_power)}"
                                            data-index="${escapeHtml(user.onu_index)}" data-label="${escapeHtml(user.label)}"
                                            data-pon="${escapeHtml(pon.name)}" data-splitter="${escapeHtml(splitter.name)}"
                                            data-company_name="${escapeHtml(user.company_name)}"
                                            data-customer_name="${escapeHtml(user.customer_name)}"
                                            data-address="${escapeHtml(user.address)}"
                                            data-mobile="${escapeHtml(user.mobile)}"
                                            data-pppoe_id="${escapeHtml(user.pppoe_id)}"
                                            data-distance="${user.distance}"
                                            data-voltage="${user.voltage}"
                                            data-temp="${user.temp}"
                                            data-vendor="${escapeHtml(user.vendor)}"
                                            data-bias="${user.bias}"
                                            data-tx_power="${user.tx_power}">
                                            <div class="card-header">
                                                <div class="card-icon ${isOnline ? 'c-green' : 'c-red'}"><i class="fa fa-user"></i></div>
                                                <span class="card-tag ${isOnline ? 'tag-green' : 'tag-red'}">${escapeHtml(user.status)}</span>
                                            </div>
                                            <div class="card-name" style="font-size:12px;">${escapeHtml(user.label || user.mac)}</div>
                                            <div class="card-meta">ONU: ${escapeHtml(user.onu_index)}</div>
                                            <div class="card-meta">Rx: <span class="${isOnline ? 's-online' : 's-offline'}">${escapeHtml(user.rx_power)} dBm</span></div>
                                        </div>
                                    </div>`;
                    });

                    html += `
                                </div><!-- end splitter children -->
                            </div><!-- end splitter node -->`;
                });

                html += `
                        </div><!-- end pon children -->
                    </div><!-- end pon node -->`;
            });

            html += `
                </div><!-- end olt-children -->
            </div><!-- end tree-row -->`;

            $('#tree-root-container').html(html);
            bindCardEvents();
        }

        // ---
        function bindCardEvents() {
            // Toggle collapse for PON and OLT cards
            $(document).off('click.tree').on('click.tree', '.node-card[data-toggle-target]:not([data-type="splitter"])', function(e) {
                e.stopPropagation();
                const targetId = $(this).data('toggle-target');
                const $children = $('#' + targetId);
                const isCollapsed = $children.hasClass('collapsed');
                if (isCollapsed) {
                    $children.removeClass('collapsed');
                    $(this).addClass('expanded');
                } else {
                    $children.addClass('collapsed');
                    $(this).removeClass('expanded');
                }
            });

            // Splitter: toggle children AND show details sidebar
            $(document).off('click.splitter').on('click.splitter', '.node-card[data-type="splitter"]', function(e) {
                e.stopPropagation();
                const targetId = $(this).data('toggle-target');
                const $children = $('#' + targetId);
                const isCollapsed = $children.hasClass('collapsed');
                if (isCollapsed) {
                    $children.removeClass('collapsed');
                    $(this).addClass('expanded');
                } else {
                    $children.addClass('collapsed');
                    $(this).removeClass('expanded');
                }
                // Also show splitter details in sidebar
                $('.node-card').removeClass('selected');
                $(this).addClass('selected');
                const name = $(this).data('name');
                const pon = $(this).data('pon');
                showSplitterDetails(name, pon);
            });

            // Show details on user card click
            $(document).off('click.user').on('click.user', '.node-card[data-type="user"]', function(e) {
                e.stopPropagation();
                $('.node-card').removeClass('selected');
                $(this).addClass('selected');
                showUserDetails($(this).data());
            });
        }

        // ---
        function showUserDetails(d) {
            const isOnline = d.status.toLowerCase() === 'online';
            const rx = parseFloat(d.rx) || 0;
            let signalClass = 'good', signalLevel = 4;
            if (rx < -25) { signalClass = 'medium'; signalLevel = 3; }
            if (rx < -28) { signalClass = 'medium'; signalLevel = 2; }
            if (rx < -31) { signalClass = 'poor'; signalLevel = 1; }

            const bars = [1,2,3,4].map(i =>
                `<span style="height:${i*4+4}px;${i <= signalLevel ? 'background:'+(isOnline?'#10b981':'#ef4444') : ''}"></span>`
            ).join('');

            $('#sidebar-type-label').html('<i class="fa fa-user"></i> ONU Details');
            $('#sidebar-content').html(`
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
                    <div style="width:40px;height:40px;border-radius:10px;background:${isOnline?'rgba(16,185,129,0.1)':'rgba(239,68,68,0.1)'};display:flex;align-items:center;justify-content:center;font-size:18px;color:${isOnline?'#10b981':'#ef4444'};">
                        <i class="fa fa-user"></i>
                    </div>
                    <div>
                        <div style="font-weight:700;font-size:14px;">${escapeHtml(d.customer_name || d.label || d.mac)}</div>
                        <div style="font-size:11px;color:${isOnline?'#10b981':'#ef4444'};">
                            <i class="fa fa-circle" style="font-size:8px;"></i> ${escapeHtml(d.status)}
                        </div>
                    </div>
                </div>
                ${d.company_name ? `<div class="detail-row"><span class="lbl">Company / Reseller</span><span class="val" style="font-weight:600;">${escapeHtml(d.company_name)}</span></div>` : ''}
                ${d.pppoe_id ? `<div class="detail-row"><span class="lbl">PPPoE ID</span><span class="val" style="font-weight:600;">${escapeHtml(d.pppoe_id)}</span></div>` : ''}
                ${d.mobile ? `<div class="detail-row"><span class="lbl">Mobile</span><span class="val">${escapeHtml(d.mobile)}</span></div>` : ''}
                ${d.address ? `<div class="detail-row"><span class="lbl">House / Address</span><span class="val">${escapeHtml(d.address)}</span></div>` : ''}
                <div class="detail-row"><span class="lbl">ONU Index</span><span class="val">${escapeHtml(d.index)}</span></div>
                <div class="detail-row"><span class="lbl">MAC Address</span><span class="val" style="font-size:11px;">${escapeHtml(d.mac)}</span></div>
                <div class="detail-row"><span class="lbl">PON Port</span><span class="val">${escapeHtml(d.pon)}</span></div>
                <div class="detail-row"><span class="lbl">Splitter</span><span class="val">${escapeHtml(d.splitter)}</span></div>
                <div class="detail-row"><span class="lbl">Distance</span><span class="val">${d.distance || 0} meters</span></div>
                ${d.vendor ? `<div class="detail-row"><span class="lbl">Vendor</span><span class="val">${escapeHtml(d.vendor)}</span></div>` : ''}
                ${d.voltage ? `<div class="detail-row"><span class="lbl">Operating Voltage</span><span class="val">${escapeHtml(d.voltage)} V</span></div>` : ''}
                ${d.temp ? `<div class="detail-row"><span class="lbl">Temperature</span><span class="val">${escapeHtml(d.temp)} °C</span></div>` : ''}
                ${(d.bias || d.tx_power || d.txPower) ? `<div class="detail-row"><span class="lbl">Transmit Bias</span><span class="val">${escapeHtml(d.bias || '')} mA</span></div>` : ''}
                ${(d.tx_power || d.txPower) ? `<div class="detail-row"><span class="lbl">Transmit Power</span><span class="val">${escapeHtml(d.tx_power || d.txPower)} dBm</span></div>` : ''}
                <div class="detail-row">
                    <span class="lbl">Rx Power</span>
                    <span class="val ${isOnline ? 's-online' : 's-offline'}">${escapeHtml(d.rx)} dBm</span>
                </div>
                <div class="detail-row">
                    <span class="lbl">Signal</span>
                    <span class="val">
                        <span class="signal-bar ${signalClass}">${bars}</span>
                    </span>
                </div>
            `);
            $('#node-details-sidebar').css('display','flex');
        }

        // ---
        function showSplitterDetails(name, pon) {
            if (!topologyCache) return;
            const ponData = topologyCache.tree.find(p => p.name === pon);
            if (!ponData) return;
            const splitter = ponData.splitters[name] || Object.values(ponData.splitters).find(s => s.name === name);
            if (!splitter) return;

            const utilPct = splitter.total > 0 ? Math.round((splitter.online / splitter.total) * 100) : 0;

            $('#sidebar-type-label').html('<i class="fa fa-share-nodes"></i> Splitter Details');
            $('#sidebar-content').html(`
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
                    <div style="width:40px;height:40px;border-radius:10px;background:rgba(245,158,11,0.1);display:flex;align-items:center;justify-content:center;font-size:18px;color:#f59e0b;">
                        <i class="fa fa-share-nodes"></i>
                    </div>
                    <div>
                        <div style="font-weight:700;font-size:14px;">${escapeHtml(name)}</div>
                        <div style="font-size:11px;color:var(--text-muted);">PON: ${escapeHtml(pon)}</div>
                    </div>
                </div>
                <div class="detail-row"><span class="lbl">PON Port</span><span class="val">${pon}</span></div>
                <div class="detail-row"><span class="lbl">Total Users</span><span class="val">${splitter.total}</span></div>
                <div class="detail-row"><span class="lbl">Online</span><span class="val s-online">${splitter.online}</span></div>
                <div class="detail-row"><span class="lbl">Offline</span><span class="val s-offline">${splitter.offline}</span></div>
                <div class="detail-row"><span class="lbl">Online Rate</span><span class="val">${utilPct}%</span></div>
                <div style="margin-top:10px;">
                    <div style="font-size:11px;color:var(--text-muted);margin-bottom:5px;">Utilization</div>
                    <div style="background:#e2e8f0;border-radius:5px;height:8px;overflow:hidden;">
                        <div style="width:${utilPct}%;background:#10b981;height:100%;transition:width 0.4s;"></div>
                    </div>
                </div>
            `);
            $('#node-details-sidebar').css('display','flex');
        }

        // ---
        function initMapView() {
            if (mapInstance) { mapInstance.invalidateSize(); return; }
            if (typeof L === 'undefined') return;
            mapInstance = L.map('main-map').setView([23.810331, 90.412487], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(mapInstance);
        }

    } // end initApp


        initApp(window.jQuery);
    }

    if (window.IpbReady) window.IpbReady(bootPremiumDiagram);
    else if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', bootPremiumDiagram);
    else bootPremiumDiagram();
})();
</script>
<?= $this->endSection('script'); ?>
