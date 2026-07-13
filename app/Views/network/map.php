<?= $this->extend('layout/main-layout'); ?>

<?= $this->section('css'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/saas/network-pages.css?v=1'); ?>">
<link href="<?= base_url('assets/map/leaflet.css'); ?>" rel="stylesheet">
<?= $this->endSection('css'); ?>

<?= $this->section('content'); ?>

<div class="content-wrapper">
  <section class="content ipb-saas-list ipb-net-page">
    <?= $this->include('components/page-header', [
      'title' => 'Network Mapping',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Network'],
        ['label' => 'Mapping'],
      ],
    ]); ?>

    <div class="ipb-net-canvas-card">
      <div class="ipb-net-canvas-head">
        <h3><i class="fa fa-map-location-dot" aria-hidden="true"></i> Live map</h3>
        <div class="ipb-net-legend" aria-label="Marker levels">
          <span class="ipb-net-legend-item"><span class="ipb-net-dot is-red"></span> Root</span>
          <span class="ipb-net-legend-item"><span class="ipb-net-dot is-blue"></span> L1</span>
          <span class="ipb-net-legend-item"><span class="ipb-net-dot is-green"></span> L2</span>
          <span class="ipb-net-legend-item"><span class="ipb-net-dot is-orange"></span> L3</span>
          <span class="ipb-net-legend-item"><span class="ipb-net-dot is-purple"></span> L4</span>
          <span class="ipb-net-legend-item"><span class="ipb-net-dot is-cyan"></span> L5+</span>
        </div>
      </div>

      <?php if (empty($locations)): ?>
        <div class="ipb-net-empty">
          <i class="fa fa-map" aria-hidden="true"></i>
          <strong>No mapped nodes</strong>
          <p>Add nodes with latitude and longitude on the Diagram page to see them here.</p>
        </div>
      <?php else: ?>
        <div id="map" role="application" aria-label="Network map"></div>
      <?php endif; ?>
    </div>
  </section>
</div>

<?= $this->endSection('content'); ?>

<?= $this->section('script'); ?>
<?php if (!empty($locations)): ?>
<script src="<?= base_url('assets/map/leaflet.js'); ?>"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const map = L.map('map').setView([23.685, 90.3563], 7);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 18,
      attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    const nodes = <?= json_encode($locations ?? []) ?>;
    const markers = {};
    const childrenMap = {};
    let highlightedLayers = [];

    const baseUrl = "<?= base_url('assets/map/images/'); ?>";
    const iconColors = {
      red: baseUrl + 'marker-icon-red.png',
      blue: baseUrl + 'marker-icon-blue.png',
      green: baseUrl + 'marker-icon-green.png',
      orange: baseUrl + 'marker-icon-orange.png',
      purple: baseUrl + 'marker-icon-purple.png',
      cyan: baseUrl + 'marker-icon-cyan.png'
    };
    const levelColors = Object.keys(iconColors);

    function randomOffset() {
      return (Math.random() - 0.5) * 0.002;
    }

    function popupHtml(title, metaLines) {
      return `<b>${title}</b><div class="ipb-net-popup-meta">${metaLines.map(l => `<span>${l}</span>`).join('')}</div>`;
    }

    nodes.forEach(node => {
      const parentId = node.parent_id ? parseInt(node.parent_id) : 0;
      if (!childrenMap[parentId]) childrenMap[parentId] = [];
      childrenMap[parentId].push(parseInt(node.id));
    });

    function createAndConnect(parentId = 0, level = 0) {
      const color = levelColors[level % levelColors.length];
      const iconUrl = iconColors[color];
      const icon = L.icon({
        iconUrl: iconUrl,
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowUrl: 'https://unpkg.com/leaflet@1.9.3/dist/images/marker-shadow.png'
      });

      if (!childrenMap[parentId]) return;

      childrenMap[parentId].forEach(childId => {
        const childNode = nodes.find(n => parseInt(n.id) === childId);
        if (!childNode) return;

        let lat = parseFloat(childNode.lat);
        let lng = parseFloat(childNode.lng);
        if (isNaN(lat) || isNaN(lng)) return;

        lat += randomOffset();
        lng += randomOffset();

        const marker = L.marker([lat, lng], { icon }).addTo(map).bindPopup(
          popupHtml(childNode.name, [
            `ID: ${childNode.id}`,
            `Parent: ${childNode.parent_id ?? 'none'}`,
            `Lat: ${lat.toFixed(6)}`,
            `Lng: ${lng.toFixed(6)}`
          ])
        );

        markers[childId] = marker;

        const parentMarker = markers[parentId];
        if (parentMarker) {
          L.polyline([parentMarker.getLatLng(), marker.getLatLng()], {
            color: color,
            weight: 3,
            opacity: 0.8
          }).addTo(map);
        }

        marker.on('click', () => highlightImmediateChildren(childId));
        createAndConnect(childId, level + 1);
      });
    }

    const rootNode = nodes.find(n => !n.parent_id || n.parent_id === 'null' || n.parent_id === null);
    if (rootNode) {
      const rootIcon = L.icon({
        iconUrl: iconColors.red,
        iconSize: [28, 45],
        iconAnchor: [14, 45],
        shadowUrl: 'https://unpkg.com/leaflet@1.9.3/dist/images/marker-shadow.png'
      });

      const rootMarker = L.marker(
        [parseFloat(rootNode.lat), parseFloat(rootNode.lng)],
        { icon: rootIcon }
      ).addTo(map).bindPopup(
        popupHtml(rootNode.name, [`ID: ${rootNode.id}`, 'Root node'])
      );

      markers[rootNode.id] = rootMarker;
      rootMarker.on('click', () => highlightImmediateChildren(rootNode.id));
    }

    createAndConnect(rootNode ? parseInt(rootNode.id) : 0, 1);

    const allLatLngs = Object.values(markers).map(m => m.getLatLng());
    if (allLatLngs.length) map.fitBounds(allLatLngs, { padding: [28, 28] });

    function highlightImmediateChildren(parentId) {
      highlightedLayers.forEach(layer => map.removeLayer(layer));
      highlightedLayers = [];
      if (!childrenMap[parentId]) return;

      childrenMap[parentId].forEach(childId => {
        const parentMarker = markers[parentId];
        const childMarker = markers[childId];
        if (!parentMarker || !childMarker) return;

        const line = L.polyline(
          [parentMarker.getLatLng(), childMarker.getLatLng()],
          { color: '#f75803', weight: 5, opacity: 0.9, dashArray: '6,6' }
        ).addTo(map);

        const circle = L.circleMarker(childMarker.getLatLng(), {
          radius: 15,
          color: '#f75803',
          weight: 3,
          fillColor: '#ffedd5',
          fillOpacity: 0.45
        }).addTo(map);

        highlightedLayers.push(line, circle);
      });
    }
  });
</script>
<?php endif; ?>
<?= $this->endSection('script'); ?>
