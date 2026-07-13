<?= $this->extend('layout/main-layout'); ?>

<?= $this->section('css'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/saas/network-pages.css?v=1'); ?>">
<link href="<?= base_url('assets/vis/vis-network.min.css'); ?>" rel="stylesheet">
<?= $this->endSection('css'); ?>

<?= $this->section('content'); ?>

<div class="content-wrapper">
  <section class="content ipb-saas-list ipb-net-page">
    <?= $this->include('components/page-header', [
      'title' => 'Network Diagram',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Network'],
        ['label' => 'Diagram'],
      ],
    ]); ?>

    <div class="ipb-net-toolbar">
      <div class="ipb-net-field">
        <label for="parentNode">Parent node</label>
        <select id="parentNode" class="form-control">
          <option value="0">Select parent (optional)</option>
        </select>
      </div>

      <div class="ipb-net-field">
        <label for="childNode">Node name</label>
        <input type="text" id="childNode" class="form-control" placeholder="e.g. Switch, ONU, Customer">
      </div>

      <div class="ipb-net-field">
        <label for="latitude">Latitude</label>
        <input type="text" id="latitude" class="form-control" placeholder="24.331559">
      </div>

      <div class="ipb-net-field">
        <label for="longitude">Longitude</label>
        <input type="text" id="longitude" class="form-control" placeholder="90.949754">
      </div>

      <div class="ipb-net-actions">
        <?php if (userHasPermission('network', 'create')): ?>
          <button type="button" id="addNodeBtn" class="btn btn-primary">
            <i class="fa fa-plus" aria-hidden="true"></i> Add node
          </button>
        <?php endif; ?>
        <?php if (userHasPermission('network', 'update')): ?>
          <button type="button" id="editNodeBtn" class="btn btn-default" style="display:none;">
            <i class="fa fa-pen" aria-hidden="true"></i> Update
          </button>
        <?php endif; ?>
        <?php if (userHasPermission('network', 'delete')): ?>
          <button type="button" id="deleteNodeBtn" class="btn btn-danger" style="display:none;">
            <i class="fa fa-trash" aria-hidden="true"></i> Delete
          </button>
        <?php endif; ?>
      </div>
    </div>

    <div class="ipb-net-canvas-card">
      <div class="ipb-net-canvas-head">
        <h3><i class="fa fa-diagram-project" aria-hidden="true"></i> Topology</h3>
        <p class="ipb-net-hint" id="ipbNetHint">Click a node to edit or delete it.</p>
      </div>
      <div id="network" role="img" aria-label="Network topology diagram"></div>
    </div>
  </section>
</div>

<?= $this->endSection('content'); ?>

<?= $this->section('script'); ?>
<script src="<?= base_url('assets/vis/vis-network.min.js'); ?>"></script>
<script>
  let selectedNodeId = null;
  let nodesArray = [];

  const nodes = new vis.DataSet();
  const edges = new vis.DataSet();
  const container = document.getElementById('network');
  let data = { nodes, edges };
  let network = null;

  const options = {
    layout: {
      hierarchical: {
        enabled: true,
        direction: 'LR',
        nodeSpacing: 200,
        levelSeparation: 150,
        sortMethod: 'directed'
      }
    },
    edges: {
      smooth: {
        type: 'cubicBezier',
        forceDirection: 'horizontal',
        roundness: 0.5
      },
      color: { color: '#cbd5e1', highlight: '#f75803' },
      width: 1.5
    },
    nodes: {
      shape: 'box',
      margin: 10,
      borderWidth: 1,
      font: {
        color: '#0f172a',
        face: 'Satoshi, system-ui, sans-serif',
        size: 13
      },
      shadow: {
        enabled: true,
        color: 'rgba(15,23,42,0.08)',
        size: 6,
        x: 0,
        y: 2
      }
    },
    interaction: {
      hover: true,
      tooltipDelay: 120
    },
    physics: false
  };

  function setHint(text, active) {
    const el = document.getElementById('ipbNetHint');
    if (!el) return;
    el.textContent = text;
    el.classList.toggle('is-active', !!active);
  }

  function renderNetwork() {
    if (network) network.destroy();
    network = new vis.Network(container, data, options);
    bindClickEvents();
  }

  function getRandomColor() {
    const hue = Math.floor(Math.random() * 360);
    return `hsl(${hue}, 70%, 75%)`;
  }

  function calculateLevels(array) {
    const levels = {};
    function setLevel(id, currentLevel) {
      levels[id] = currentLevel;
      array.filter(n => n.parent_id == id)
        .forEach(child => setLevel(child.id, currentLevel + 1));
    }
    array.filter(n => !n.parent_id || n.parent_id == 0)
      .forEach(root => setLevel(root.id, 0));
    return levels;
  }

  function fetchNetworkData() {
    $.ajax({
      url: '<?= route_to("network.index"); ?>',
      method: 'GET',
      dataType: 'json',
      beforeSend: function (req) {
        req.setRequestHeader('<?= csrf_header() ?>', '<?= csrf_hash() ?>');
      },
      success: function (dataRes) {
        nodes.clear();
        edges.clear();
        $('#parentNode').html('<option value="0">Select parent (optional)</option>');

        nodesArray = dataRes;
        const levels = calculateLevels(nodesArray);

        nodesArray.forEach(n => {
          nodes.add({
            id: parseInt(n.id),
            label: n.label,
            shape: 'box',
            widthConstraint: { maximum: 120 },
            color: {
              background: n.color,
              border: '#94a3b8',
              highlight: { background: '#ffedd5', border: '#f75803' },
              hover: { background: n.color, border: '#f75803' }
            },
            font: { color: '#0f172a', size: 13 },
            parent_id: n.parent_id,
            level: levels[n.id] ?? 0,
            lat: n.latitude,
            lng: n.longitude
          });

          if (n.parent_id && n.id !== n.parent_id) {
            edges.add({ from: parseInt(n.parent_id), to: parseInt(n.id) });
          }

          $('#parentNode').append($('<option>', { value: n.id, text: n.label }));
        });

        renderNetwork();
        setHint(nodesArray.length ? 'Click a node to edit or delete it.' : 'No nodes yet. Add the first node above.', false);
      },
      error: () => alert('Error loading nodes')
    });
  }

  function bindClickEvents() {
    network.on('click', function (params) {
      if (params.nodes.length === 1) {
        selectedNodeId = params.nodes[0];
        const node = nodes.get(selectedNodeId);

        $('#editNodeBtn').show();
        $('#deleteNodeBtn').show();
        $('#childNode').val(node.label);
        $('#latitude').val(node.lat || '');
        $('#longitude').val(node.lng || '');
        $('#parentNode').val(node.id || '0');
        setHint('Selected: ' + (node.label || 'node') + ' — update fields then save.', true);
      } else {
        selectedNodeId = null;
        $('#editNodeBtn').hide();
        $('#deleteNodeBtn').hide();
        $('#childNode, #latitude, #longitude').val('');
        $('#parentNode').val('0');
        setHint('Click a node to edit or delete it.', false);
      }
    });
  }

  $('#addNodeBtn').on('click', function () {
    const parent = $('#parentNode').val();
    const label = $('#childNode').val().trim();
    const lat = $('#latitude').val().trim();
    const lng = $('#longitude').val().trim();

    if (!label) return alert('Please enter a node name');
    if (!lat || !lng) return alert('Please enter latitude and longitude');

    const color = getRandomColor();

    $.post('<?= route_to("network.addNode"); ?>', {
      parent_id: parent !== '0' ? parent : null,
      label: label,
      color: color,
      latitude: lat,
      longitude: lng,
      '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
    }, function (res) {
      if (res.status === 'success') {
        fetchNetworkData();
      } else {
        alert('Failed to add node');
      }
    }).fail(() => alert('Error adding node'));
  });

  $('#editNodeBtn').on('click', function () {
    if (!selectedNodeId) return alert('No node selected.');
    const newLabel = $('#childNode').val().trim();
    const lat = $('#latitude').val().trim();
    const lng = $('#longitude').val().trim();

    if (!newLabel) return alert('Please enter a node name.');
    if (!lat || !lng) return alert('Please enter latitude and longitude.');
    if (!confirm('Are you sure you want to update this node?')) return;

    $.post('<?= route_to("network.editNode"); ?>', {
      id: selectedNodeId,
      label: newLabel,
      latitude: lat,
      longitude: lng,
      '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
    }, function (res) {
      if (res.status === 'success') {
        fetchNetworkData();
        alert('Node updated.');
      } else {
        alert('Failed to update node');
      }
    }).fail(() => alert('Error updating node'));
  });

  $('#deleteNodeBtn').on('click', function () {
    if (!selectedNodeId) return alert('No node selected.');
    if (!confirm('Are you sure you want to delete this node and all its children?')) return;

    $.post('<?= route_to("network.deleteNode"); ?>', {
      id: selectedNodeId,
      '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
    }, function (res) {
      if (res.status === 'success') {
        fetchNetworkData();
        alert('Node and its subnodes deleted.');
      } else {
        alert('Failed to delete node');
      }
    }).fail(() => alert('Error deleting node'));
  });

  fetchNetworkData();
</script>
<?= $this->endSection('script'); ?>
