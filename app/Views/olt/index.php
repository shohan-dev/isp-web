<?= $this->extend('layout/main-layout'); ?>
<?= $this->section('content'); ?>

<div class="content-wrapper">
  <section class="content ipb-saas-list">

    <?= $this->include('components/page-header', [
      'title' => 'OLT Nodes',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => "OLT's"],
      ],
    ]); ?>

    <div class="box box-warning">
      <div class="box-header with-border ipb-box-toolbar">
        <div class="ipb-list-toolbar">
          <div class="ipb-list-toolbar-filters">
            <span class="ipb-filter-label">
              <i class="fa fa-list" aria-hidden="true"></i> Records
            </span>
            <span class="ipb-pay-badge is-info"><?= (int) count($olts) ?> nodes</span>
          </div>
          <div class="ipb-list-toolbar-actions">
            <button type="button" class="btn btn-primary" id="btnShowRegister">
              <i class="fa fa-plus" aria-hidden="true"></i> Onboard New OLT
            </button>
            <button type="button" class="btn btn-default" style="display: none;" id="btnHideRegister">
              <i class="fa fa-times" aria-hidden="true"></i> Close form
            </button>
          </div>
        </div>
      </div>

      <div class="box-body">

        <div id="registrationWrapper" class="ipb-olt-form-wrap" style="display: none;">
          <div class="ipb-olt-form-head">
            <strong id="formTitle">Register New OLT Node</strong>
          </div>
          <form action="<?= route_to('olt.store') ?>" method="post" id="oltForm">
            <?= csrf_field() ?>
            <input type="hidden" name="olt_id" id="olt_id" value="">

            <div class="row">
              <div class="form-group col-md-3">
                <label for="olt_name">OLT Name</label>
                <input type="text" name="olt_name" id="olt_name" class="form-control" placeholder="Downtown_OLT_01" required>
              </div>
              <div class="form-group col-md-3">
                <label for="brand">Brand / Vendor</label>
                <select name="brand" id="brand" class="form-control select2" required>
                  <option value="ATOP">ATOP</option>
                  <option value="Avies">Avies</option>
                  <option value="BDCOM">BDCOM</option>
                  <option value="C_data">C data</option>
                  <option value="Corelink">Corelink</option>
                  <option value="DBC">DBC</option>
                  <option value="Ecom">Ecom</option>
                  <option value="Fucascom">Fucascom</option>
                  <option value="Hsgq">Hsgq</option>
                  <option value="Tbs_pothon">Tbs pothon</option>
                  <option value="V_sol">V sol</option>
                </select>
              </div>
              <div class="form-group col-md-3">
                <label for="ip">IP Address</label>
                <input type="text" name="ip" id="ip" class="form-control" placeholder="10.10.20.15" required>
              </div>
              <div class="form-group col-md-3">
                <label for="port">Port</label>
                <input type="number" name="port" id="port" class="form-control" placeholder="23" required>
              </div>
            </div>
            <div class="row">
              <div class="form-group col-md-3">
                <label for="protocol">Protocol</label>
                <select name="protocol" id="protocol" class="form-control">
                  <option value="telnet">Telnet</option>
                  <option value="http">HTTP</option>
                  <option value="https">HTTPS</option>
                </select>
              </div>
              <div class="form-group col-md-3">
                <label for="username">Username</label>
                <input type="text" name="username" id="username" class="form-control" placeholder="admin" required>
              </div>
              <div class="form-group col-md-3">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="Leave blank to keep current">
              </div>
              <div class="form-group col-md-3">
                <label for="login_key">Login Key (optional)</label>
                <input type="text" name="login_key" id="login_key" class="form-control" placeholder="Optional auth key">
              </div>
            </div>
            <div class="ipb-olt-form-actions">
              <button type="submit" class="btn btn-primary" id="btnSubmitForm">
                <i class="fa fa-save" aria-hidden="true"></i> Save OLT Configuration
              </button>
            </div>
          </form>
        </div>

        <?php if (!empty($olts)): ?>
          <?php
            $oltActionAttrs = static function (array $olt): string {
              return ' data-id="' . (int) $olt['id'] . '"'
                . ' data-name="' . esc($olt['olt_name'], 'attr') . '"'
                . ' data-brand="' . esc($olt['brand'], 'attr') . '"'
                . ' data-ip="' . esc($olt['ip'], 'attr') . '"'
                . ' data-port="' . esc($olt['port'], 'attr') . '"'
                . ' data-protocol="' . esc($olt['protocol'], 'attr') . '"'
                . ' data-username="' . esc($olt['username'], 'attr') . '"'
                . ' data-key="' . esc($olt['login_key'] ?? '', 'attr') . '"';
            };
          ?>

          <!-- Desktop / tablet table -->
          <div class="table-responsive ipb-olt-table-wrap">
            <table class="table table-bordered table-striped" width="100%" id="oltNodesTable">
              <caption class="sr-only">OLT nodes list</caption>
              <thead class="text-nowrap">
                <tr>
                  <th scope="col">#</th>
                  <th scope="col">Name</th>
                  <th scope="col">Brand</th>
                  <th scope="col">Host</th>
                  <th scope="col">Protocol</th>
                  <th scope="col">Username</th>
                  <th scope="col">Status</th>
                  <th scope="col">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php $i = 1; foreach ($olts as $olt): ?>
                  <tr>
                    <td><?= $i++ ?></td>
                    <td><strong><?= esc($olt['olt_name']) ?></strong></td>
                    <td><span class="ipb-pay-badge is-info"><?= esc($olt['brand']) ?></span></td>
                    <td><code class="ipb-mono"><?= esc($olt['ip']) ?>:<?= esc($olt['port']) ?></code></td>
                    <td><span class="ipb-pay-badge is-neutral"><?= esc(strtoupper((string) $olt['protocol'])) ?></span></td>
                    <td><?= esc($olt['username']) ?></td>
                    <td>
                      <?php if (!empty($olt['status'])): ?>
                        <span class="ipb-pay-badge is-success">Active</span>
                      <?php else: ?>
                        <span class="ipb-pay-badge is-neutral">Disabled</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <div class="ipb-row-actions">
                        <button type="button" class="ipb-row-btn tone-info btn-connect" title="Diagnostics" data-toggle="tooltip"<?= $oltActionAttrs($olt) ?>>
                          <i class="fa fa-bolt" aria-hidden="true"></i><span class="sr-only">Diagnostics</span>
                        </button>
                        <button type="button" class="ipb-row-btn tone-brand btn-edit" title="Edit" data-toggle="tooltip"<?= $oltActionAttrs($olt) ?>>
                          <i class="far fa-pen-to-square" aria-hidden="true"></i><span class="sr-only">Edit</span>
                        </button>
                        <button type="button" class="ipb-row-btn tone-danger btn-delete" title="Delete" data-toggle="tooltip"<?= $oltActionAttrs($olt) ?>>
                          <i class="far fa-trash-can" aria-hidden="true"></i><span class="sr-only">Delete</span>
                        </button>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <!-- Phone cards (no horizontal scroll / hidden columns) -->
          <div class="ipb-olt-cards" aria-label="OLT nodes">
            <?php foreach ($olts as $olt): ?>
              <article class="ipb-olt-card">
                <div class="ipb-olt-card-top">
                  <div>
                    <h3 class="ipb-olt-card-title"><?= esc($olt['olt_name']) ?></h3>
                    <div class="ipb-olt-card-meta">
                      <span class="ipb-pay-badge is-info"><?= esc($olt['brand']) ?></span>
                      <span class="ipb-pay-badge is-neutral"><?= esc(strtoupper((string) $olt['protocol'])) ?></span>
                      <?php if (!empty($olt['status'])): ?>
                        <span class="ipb-pay-badge is-success">Active</span>
                      <?php else: ?>
                        <span class="ipb-pay-badge is-neutral">Disabled</span>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
                <dl class="ipb-olt-card-fields">
                  <div>
                    <dt>Host</dt>
                    <dd><code class="ipb-mono"><?= esc($olt['ip']) ?>:<?= esc($olt['port']) ?></code></dd>
                  </div>
                  <div>
                    <dt>Username</dt>
                    <dd><?= esc($olt['username']) ?></dd>
                  </div>
                </dl>
                <div class="ipb-olt-card-actions">
                  <button type="button" class="btn btn-primary btn-connect"<?= $oltActionAttrs($olt) ?>>
                    <i class="fa fa-bolt" aria-hidden="true"></i> Diagnostics
                  </button>
                  <button type="button" class="btn btn-default btn-edit"<?= $oltActionAttrs($olt) ?>>
                    <i class="far fa-pen-to-square" aria-hidden="true"></i> Edit
                  </button>
                  <button type="button" class="btn btn-default btn-delete"<?= $oltActionAttrs($olt) ?>>
                    <i class="far fa-trash-can" aria-hidden="true"></i> Delete
                  </button>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="ipb-olt-empty text-muted">No OLT devices registered.</div>
        <?php endif; ?>
      </div>
    </div>

  </section>
</div>

<div class="ipb-olt-modal-overlay" id="oltResultModal" aria-hidden="true">
  <div class="ipb-olt-modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
    <div class="ipb-olt-modal-head">
      <h5 id="modalTitle">OLT Diagnostics</h5>
      <button type="button" class="ipb-olt-modal-close" onclick="closeOltModal()" aria-label="Close">
        <i class="fa fa-times" aria-hidden="true"></i>
      </button>
    </div>
    <div class="ipb-olt-modal-body">
      <div id="summaryCards" class="ipb-olt-metrics"></div>
      <div id="portSummaryWrapper"></div>
      <div class="ipb-olt-panel">
        <div class="ipb-olt-panel-head">
          <strong>ONU Port Distribution &amp; Signals</strong>
          <div class="ipb-olt-search">
            <i class="fa fa-search" aria-hidden="true"></i>
            <input type="search" id="onuSearchInput" class="form-control" placeholder="Search ONUs..." autocomplete="off">
          </div>
        </div>
        <div class="table-responsive ipb-onu-table-wrap">
          <table class="table table-bordered table-striped" id="onuTable" width="100%">
            <caption class="sr-only">ONU port distribution</caption>
            <thead class="text-nowrap">
              <tr>
                <th scope="col">ONU ID</th>
                <th scope="col">MAC Address</th>
                <th scope="col">Customer / User</th>
                <th scope="col">Status</th>
                <th scope="col">RX Power</th>
                <th scope="col">Deregister Reason</th>
                <th scope="col">Last Active / Seen</th>
              </tr>
            </thead>
            <tbody id="oltDataBody"></tbody>
          </table>
        </div>
        <div class="ipb-onu-cards" id="oltDataCards" aria-label="ONU list"></div>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection('content'); ?>

<?= $this->section('css'); ?>
<style>
  .ipb-olt-form-wrap {
    margin-bottom: 18px;
    padding: 16px;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    background: var(--surface-2);
  }
  .ipb-olt-form-head {
    margin-bottom: 12px;
    font-size: 14px;
    color: var(--text-primary);
  }
  .ipb-olt-form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    margin-top: 4px;
  }
  .ipb-mono {
    font-family: var(--font-mono, ui-monospace, monospace);
    font-size: 12.5px;
    background: var(--surface-2);
    border: 1px solid var(--border);
    border-radius: 6px;
    padding: 2px 8px;
    word-break: break-all;
  }
  .ipb-olt-cards { display: none; }
  .ipb-olt-empty {
    text-align: center;
    padding: 28px 12px;
    font-weight: 600;
  }
  .ipb-olt-card {
    border: 1px solid var(--border);
    border-radius: var(--radius);
    background: var(--surface);
    padding: 14px;
    margin-bottom: 10px;
  }
  .ipb-olt-card-title {
    margin: 0 0 8px;
    font-size: 15px;
    font-weight: 800;
    color: var(--text-primary);
    line-height: 1.3;
    word-break: break-word;
  }
  .ipb-olt-card-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
  }
  .ipb-olt-card-fields {
    margin: 12px 0 0;
  }
  .ipb-olt-card-fields > div {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 10px;
    padding: 8px 0;
    border-top: 1px solid var(--border);
  }
  .ipb-olt-card-fields dt {
    margin: 0;
    font-size: 12px;
    font-weight: 700;
    color: var(--text-muted);
    flex-shrink: 0;
  }
  .ipb-olt-card-fields dd {
    margin: 0;
    text-align: right;
    font-size: 13px;
    font-weight: 600;
    color: var(--text-primary);
    word-break: break-word;
  }
  .ipb-olt-card-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    margin-top: 12px;
  }
  .ipb-olt-card-actions .btn-connect {
    grid-column: 1 / -1;
  }
  .ipb-olt-card-actions .btn {
    min-height: 44px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
  }

  /* Diagnostics ONU rows as stacked cards on phone */
  .ipb-onu-cards { display: none; }
  .ipb-onu-card {
    border: 1px solid var(--border);
    border-radius: var(--radius);
    background: var(--surface);
    padding: 12px;
    margin-bottom: 10px;
  }
  .ipb-onu-card-title {
    font-weight: 800;
    font-size: 13.5px;
    margin-bottom: 8px;
    color: var(--text-primary);
    word-break: break-word;
  }
  .ipb-onu-card-row {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    padding: 6px 0;
    border-top: 1px solid var(--border);
    font-size: 12.5px;
  }
  .ipb-onu-card-row span:first-child {
    color: var(--text-muted);
    font-weight: 700;
    flex-shrink: 0;
  }
  .ipb-onu-card-row span:last-child {
    text-align: right;
    font-weight: 600;
    color: var(--text-primary);
    word-break: break-word;
  }

  /* Mounted on body — must ignore AdminLTE .wrapper / content-wrapper offsets */
  body > .ipb-olt-modal-overlay,
  .ipb-olt-modal-overlay {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    max-width: none !important;
    max-height: none !important;
    margin: 0 !important;
    padding: 16px !important;
    box-sizing: border-box !important;
    background: rgba(15, 23, 42, 0.45);
    display: none;
    align-items: center !important;
    justify-content: center !important;
    z-index: var(--z-overlay, 1095) !important;
    opacity: 1;
    pointer-events: none;
    transform: none !important;
  }
  body > .ipb-olt-modal-overlay.show,
  .ipb-olt-modal-overlay.show {
    display: flex !important;
    pointer-events: auto !important;
  }
  .ipb-olt-modal {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-3);
    width: min(1450px, calc(100vw - 32px));
    height: min(92vh, 920px);
    max-width: calc(100vw - 32px);
    max-height: calc(100vh - 32px);
    margin: 0 auto !important;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    position: relative !important;
    left: auto !important;
    right: auto !important;
    top: auto !important;
    transform: none !important;
    flex-shrink: 0;
  }
  .ipb-olt-modal-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 12px 16px;
    border-bottom: 1px solid var(--border);
    flex-shrink: 0;
  }
  .ipb-olt-modal-head h5 {
    margin: 0;
    font-size: 16px;
    font-weight: 800;
    color: var(--text-primary);
  }
  .ipb-olt-modal-close {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    border: 1px solid var(--border);
    background: var(--surface-2);
    color: var(--text-secondary);
    cursor: pointer;
  }
  .ipb-olt-modal-close:hover {
    border-color: var(--primary-500);
    color: var(--primary-600);
  }
  .ipb-olt-modal-body {
    padding: 14px;
    overflow: auto;
    background: var(--bg);
    flex: 1 1 auto;
  }
  .ipb-olt-metrics {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 10px;
    margin-bottom: 12px;
  }
  .ipb-olt-metric {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 10px 12px;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
  }
  .ipb-olt-metric-label {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    font-weight: 700;
    color: var(--text-secondary);
    text-transform: uppercase;
  }
  .ipb-olt-metric-value {
    margin: 0;
    font-size: 18px;
    font-weight: 800;
    color: var(--text-primary);
  }
  .ipb-olt-metric-value.is-success { color: var(--success-500); }
  .ipb-olt-metric-value.is-warning { color: var(--warning-500); }
  .ipb-olt-metric-value.is-danger { color: var(--error-500); }
  .ipb-olt-ports {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
    gap: 10px;
    margin-bottom: 12px;
  }
  .ipb-olt-port-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 10px;
  }
  .ipb-olt-port-card-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
    padding-bottom: 6px;
    border-bottom: 1px solid var(--border);
    font-size: 12.5px;
    font-weight: 700;
    color: var(--text-primary);
  }
  .ipb-olt-port-row {
    display: flex;
    justify-content: space-between;
    font-size: 11.5px;
    font-weight: 600;
    color: var(--text-secondary);
    margin-top: 4px;
  }
  .ipb-olt-panel {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
  }
  .ipb-olt-panel-head {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 12px 14px;
    border-bottom: 1px solid var(--border);
  }
  .ipb-olt-search {
    position: relative;
    min-width: min(280px, 100%);
  }
  .ipb-olt-search i {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-muted);
    font-size: 12px;
    pointer-events: none;
  }
  .ipb-olt-search .form-control {
    padding-left: 30px;
  }

  @media (max-width: 767px) {
    .ipb-olt-table-wrap,
    .ipb-onu-table-wrap {
      display: none !important;
    }
    .ipb-olt-cards,
    .ipb-onu-cards {
      display: block !important;
    }
    .ipb-olt-form-wrap {
      padding: 12px;
    }
    .ipb-olt-form-actions {
      justify-content: stretch;
    }
    .ipb-olt-form-actions .btn {
      width: 100%;
      min-height: 44px;
    }
    body.ipb .ipb-list-toolbar-actions .btn {
      width: 100%;
      min-height: 44px;
    }
    .ipb-olt-modal-overlay {
      padding: 0 !important;
    }
    .ipb-olt-modal {
      width: 100vw !important;
      height: 100vh !important;
      max-width: 100vw !important;
      max-height: 100vh !important;
      border-radius: 0 !important;
      border: 0 !important;
    }
    .ipb-olt-modal-head {
      position: sticky;
      top: 0;
      background: var(--surface);
      z-index: 2;
      padding: 12px;
    }
    .ipb-olt-modal-head h5 {
      font-size: 14px;
      line-height: 1.3;
      padding-right: 8px;
    }
    /* Modal goes full-screen at this breakpoint (above), so this close button
       is the only way out on phone — its desktop 36x36px box is under the
       ~40-44px touch target minimum here. */
    .ipb-olt-modal-close {
      width: 44px;
      height: 44px;
      flex-shrink: 0;
    }
    .ipb-olt-modal-body {
      padding: 10px;
    }
    .ipb-olt-metrics {
      grid-template-columns: 1fr;
      gap: 8px;
    }
    .ipb-olt-ports {
      grid-template-columns: 1fr 1fr;
      gap: 8px;
    }
    .ipb-olt-panel-head {
      flex-direction: column;
      align-items: stretch;
    }
    .ipb-olt-search {
      min-width: 0;
      width: 100%;
    }
  }

  @media (max-width: 420px) {
    .ipb-olt-ports {
      grid-template-columns: 1fr;
    }
  }
</style>
<?= $this->endSection('css'); ?>

<?= $this->section('script'); ?>
<script>
(function ($) {
  'use strict';

  $(function () {
    var storeUrl = <?= json_encode(route_to('olt.store')); ?>;
    var updateUrl = <?= json_encode(base_url('olts/update')); ?>;

    // Escape AdminLTE .wrapper / content-wrapper so fixed centering uses the viewport
    var $oltModal = $('#oltResultModal');
    if ($oltModal.length) {
      $oltModal.appendTo(document.body);
    }

    $('#btnShowRegister').on('click', function () {
      $('#formTitle').text('Onboard New OLT Node');
      $('#oltForm').attr('action', storeUrl);
      $('#olt_id').val('');
      $('#oltForm')[0].reset();
      $('#registrationWrapper').slideDown(300);
      $(this).hide();
      $('#btnHideRegister').show();
    });

    $(document).on('click', '.btn-edit', function () {
      var data = $(this).data();
      $('#formTitle').text('Edit OLT: ' + data.name);
      $('#oltForm').attr('action', updateUrl);
      $('#olt_id').val(data.id);
      $('#olt_name').val(data.name);
      $('#brand').val(data.brand).trigger('change');
      $('#ip').val(data.ip);
      $('#port').val(data.port);
      $('#protocol').val(data.protocol).trigger('change');
      $('#username').val(data.username);
      $('#login_key').val(data.key);
      $('#password').val('');
      $('#registrationWrapper').slideDown(300);
      $('#btnShowRegister').hide();
      $('#btnHideRegister').show();
      $('html, body').animate({
        scrollTop: $('#registrationWrapper').offset().top - 100
      }, 400);
    });

    $('#btnHideRegister').on('click', function () {
      $('#registrationWrapper').slideUp(250);
      $(this).hide();
      $('#btnShowRegister').show();
      $('#oltForm')[0].reset();
    });

    $(document).on('click', '.btn-delete', function () {
      var id = $(this).data('id');
      var name = $(this).data('name');
      var btn = $(this);
      if (!window.confirm('Delete OLT node "' + name + '"? Configuration for this node will be removed.')) {
        return;
      }
      btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin" aria-hidden="true"></i>');
      $.ajax({
        url: <?= json_encode(base_url('olts/delete')); ?> + '/' + id,
        method: 'POST',
        data: { <?= json_encode(csrf_token()); ?>: <?= json_encode(csrf_hash()); ?> },
        success: function () {
          location.reload();
        },
        error: function () {
          if (window.tata) tata.error("Couldn't delete OLT", 'Error deleting OLT configuration.');
          else alert('Error deleting OLT configuration.');
          btn.prop('disabled', false).html('<i class="far fa-trash-can" aria-hidden="true"></i>');
        }
      });
    });

    $(document).on('click', '.btn-connect', function () {
      var oltId = $(this).data('id');
      var oltName = $(this).data('name');
      var btn = $(this);
      var original = btn.html();
      btn.html('<i class="fa fa-spinner fa-spin" aria-hidden="true"></i> Querying...').attr('disabled', true);

      $.ajax({
        url: <?= json_encode(base_url('olts/run')); ?> + '/' + oltId,
        method: 'POST',
        dataType: 'json',
        data: { <?= json_encode(csrf_token()); ?>: <?= json_encode(csrf_hash()); ?> },
        success: function (response) {
          if (response.status === 'success') {
            var data = response.result;
            if (typeof data !== 'object' || data === null) {
              try { data = JSON.parse(response.result); } catch (e) { data = {}; }
            }
            renderOltModal(oltName, data || {});
          } else {
            var errorMsg = 'Failed to fetch data';
            try {
              var errData = typeof response.result === 'object' ? response.result : JSON.parse(response.result);
              errorMsg = errData.error || errorMsg;
            } catch (e) {}
            if (window.tata) tata.error("Couldn't run diagnostics", errorMsg);
            else alert(errorMsg);
          }
        },
        error: function (xhr, status, error) {
          var msg = 'Diagnostics query failed: ' + status + ' / ' + error;
          if (window.tata) tata.error("Couldn't run diagnostics", msg);
          else alert(msg);
        },
        complete: function () {
          btn.html(original).attr('disabled', false);
        }
      });
    });

    $('#onuSearchInput').on('keyup', function () {
      var value = $(this).val().toLowerCase();
      $('#oltDataBody tr').filter(function () {
        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
      });
      $('#oltDataCards .ipb-onu-card').filter(function () {
        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
      });
    });

    function renderOltModal(name, data) {
      $('#onuSearchInput').val('');
      $('#modalTitle').text(name + ' — Device Diagnostics');

      var onlineCount = (data.summary && data.summary.online != null) ? data.summary.online : (data.online || 0);
      var wireDownCount = (data.summary && data.summary.offline_wire_down != null) ? data.summary.offline_wire_down : (data.wire_down || 0);
      var powerOffCount = (data.summary && data.summary.offline_power_off != null) ? data.summary.offline_power_off : (data.offline || 0);

      $('#summaryCards').html(
        '<div class="ipb-olt-metric"><span class="ipb-olt-metric-label"><i class="fa fa-signal" aria-hidden="true"></i> Online ONUs</span><p class="ipb-olt-metric-value is-success">' + onlineCount + '</p></div>' +
        '<div class="ipb-olt-metric"><span class="ipb-olt-metric-label"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i> Wire Down</span><p class="ipb-olt-metric-value is-warning">' + wireDownCount + '</p></div>' +
        '<div class="ipb-olt-metric"><span class="ipb-olt-metric-label"><i class="fa fa-power-off" aria-hidden="true"></i> Power Off / Offline</span><p class="ipb-olt-metric-value is-danger">' + powerOffCount + '</p></div>'
      );

      var portSummary = {};
      if (data.onu_id && Array.isArray(data.onu_id)) {
        data.onu_id.forEach(function (id, index) {
          var status = (data.status && data.status[index] != null) ? data.status[index] : 'Offline';
          var portName = String(id || '').split(':')[0] || 'Unknown';
          if (!portSummary[portName]) portSummary[portName] = { total: 0, active: 0, inactive: 0 };
          portSummary[portName].total++;
          var statusLower = String(status).toLowerCase();
          if (statusLower === 'online' || statusLower === 'up' || statusLower === 'active') {
            portSummary[portName].active++;
          } else {
            portSummary[portName].inactive++;
          }
        });
      }

      var portsList = Object.keys(portSummary);
      var portSummaryHtml = '';
      if (portsList.length) {
        portSummaryHtml += '<div class="ipb-olt-ports">';
        portsList.forEach(function (port) {
          var p = portSummary[port];
          portSummaryHtml +=
            '<div class="ipb-olt-port-card">' +
              '<div class="ipb-olt-port-card-head"><span><i class="fa fa-plug" aria-hidden="true"></i> ' + port + '</span><span class="ipb-pay-badge is-neutral">' + p.total + ' total</span></div>' +
              '<div class="ipb-olt-port-row"><span>Active</span><strong class="ipb-olt-metric-value is-success" style="font-size:13px">' + p.active + '</strong></div>' +
              '<div class="ipb-olt-port-row"><span>Inactive</span><strong class="ipb-olt-metric-value is-danger" style="font-size:13px">' + p.inactive + '</strong></div>' +
            '</div>';
        });
        portSummaryHtml += '</div>';
      }
      $('#portSummaryWrapper').html(portSummaryHtml);

      var rows = '';
      var cards = '';
      if (data.onu_id && Array.isArray(data.onu_id)) {
        data.onu_id.forEach(function (id, index) {
          var status = (data.status && data.status[index] != null) ? data.status[index] : 'Offline';
          var statusLower = String(status).toLowerCase();
          var isActive = statusLower === 'online' || statusLower === 'up' || statusLower === 'active';
          var mac = (data.mac && data.mac[index] != null) ? data.mac[index] : 'N/A';
          var rxPower = (data.rx && data.rx[index] != null) ? data.rx[index]
            : ((data.rx_power && data.rx_power[index] != null) ? data.rx_power[index] : '--');
          var reason = (data.reason && data.reason[index] != null) ? data.reason[index] : 'N/A';
          var lastSeen = (data.last_deregister && data.last_deregister[index] != null) ? data.last_deregister[index]
            : ((data.last_seen && data.last_seen[index] != null) ? data.last_seen[index]
              : ((data.last_register && data.last_register[index] != null) ? data.last_register[index] : 'N/A'));
          var description = (data.des && data.des[index] != null) ? data.des[index] : '';
          var cleanMacUpper = String(mac || '').toUpperCase().trim();
          var cleanMacNoColons = cleanMacUpper.replace(/:/g, '');
          var customerUser = (data.mac_to_user && (data.mac_to_user[cleanMacUpper] || data.mac_to_user[cleanMacNoColons]))
            ? (data.mac_to_user[cleanMacUpper] || data.mac_to_user[cleanMacNoColons])
            : '<span class="text-muted">Unbound / Free</span>';
          var statusBadge = '<span class="ipb-pay-badge ' + (isActive ? 'is-success' : 'is-danger') + '">' + status + '</span>';

          rows +=
            '<tr>' +
              '<td><strong>' + id + '</strong>' + (description ? '<br><small class="text-muted">' + description + '</small>' : '') + '</td>' +
              '<td><code class="ipb-mono">' + mac + '</code></td>' +
              '<td><strong>' + customerUser + '</strong></td>' +
              '<td>' + statusBadge + '</td>' +
              '<td><strong>' + rxPower + ' dBm</strong></td>' +
              '<td><span class="ipb-pay-badge is-neutral">' + reason + '</span></td>' +
              '<td><small>' + lastSeen + '</small></td>' +
            '</tr>';

          cards +=
            '<article class="ipb-onu-card">' +
              '<div class="ipb-onu-card-title">' + id + (description ? ' · ' + description : '') + '</div>' +
              '<div class="ipb-onu-card-row"><span>MAC</span><span><code class="ipb-mono">' + mac + '</code></span></div>' +
              '<div class="ipb-onu-card-row"><span>Customer</span><span>' + customerUser + '</span></div>' +
              '<div class="ipb-onu-card-row"><span>Status</span><span>' + statusBadge + '</span></div>' +
              '<div class="ipb-onu-card-row"><span>RX Power</span><span><strong>' + rxPower + ' dBm</strong></span></div>' +
              '<div class="ipb-onu-card-row"><span>Reason</span><span>' + reason + '</span></div>' +
              '<div class="ipb-onu-card-row"><span>Last seen</span><span>' + lastSeen + '</span></div>' +
            '</article>';
        });
      } else {
        rows = '<tr><td colspan="7" class="text-center text-muted" style="padding:20px">No ONU data found.</td></tr>';
        cards = '<div class="ipb-olt-empty text-muted">No ONU data found.</div>';
      }

      $('#oltDataBody').html(rows);
      $('#oltDataCards').html(cards);
      $('#oltResultModal').addClass('show').attr('aria-hidden', 'false');
      try {
        document.body.style.overflow = 'hidden';
      } catch (e) {}
    }

    window.closeOltModal = function () {
      $('#oltResultModal').removeClass('show').attr('aria-hidden', 'true');
      try {
        document.body.style.overflow = '';
      } catch (e) {}
    };

    $('#oltResultModal').on('click', function (e) {
      if (e.target === this) closeOltModal();
    });
  });
})(jQuery);
</script>
<?= $this->endSection('script'); ?>
