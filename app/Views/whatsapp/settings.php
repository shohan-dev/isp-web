<?= $this->extend('layout/main-layout') ?>

<?= $this->section('css') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/saas/whatsapp-pages.css?v=3') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php $a = $account ?? []; ?>
<div class="content-wrapper">
  <section class="content ipb-saas-list ipb-wa-page">

    <?= $this->include('components/page-header', [
      'title' => 'WhatsApp Settings',
      'subtitle' => 'Meta Cloud API credentials and messaging features',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'WhatsApp Business', 'url' => route_to('route.whatsapp.settings')],
        ['label' => 'Settings'],
      ],
    ]) ?>

    <div class="ipb-wa-callout">
      <div style="min-width:0;flex:1;">
        <span class="ipb-wa-callout__label">Webhook URL</span>
        <p class="ipb-wa-callout__body">Paste this in Meta App → WhatsApp → Configuration.</p>
        <div class="ipb-wa-webhook" style="margin-top:8px;">
          <code id="wa-webhook-url"><?= esc($webhook_url) ?></code>
          <button type="button" class="btn btn-default btn-sm" id="wa-copy-webhook">
            <i class="fa fa-copy" aria-hidden="true"></i> Copy
          </button>
        </div>
      </div>
    </div>

    <div class="ipb-wa-callout is-warn">
      <p class="ipb-wa-callout__body" style="margin:0;">
        <strong>Billing note:</strong>
        Utility / Authentication / Marketing messages are billable per Meta.
        Service AI replies follow Meta customer-care window rules.
      </p>
    </div>

    <?php if (!empty($is_platform)): ?>
    <div class="ipb-wa-card box" id="wa-entitlements-panel">
      <div class="ipb-wa-panel-head">
        <div>
          <h3>Who can use WhatsApp</h3>
          <p class="ipb-wa-panel-sub">Grant Second Admins access. Each entitled admin configures their own Meta Business API and inbox.</p>
        </div>
      </div>
      <div class="box-body ipb-wa-table-wrap">
        <?php if (!empty($entitlements)): ?>
          <div class="ipb-wa-entitlements-toolbar" id="wa-entitlements-toolbar">
            <div class="ipb-wa-field is-search">
              <label class="ipb-wa-label" for="wa-ent-search">Search</label>
              <input type="search" id="wa-ent-search" class="form-control"
                placeholder="Name, mobile, email, or ID…"
                autocomplete="off">
            </div>
            <div class="ipb-wa-field">
              <label class="ipb-wa-label" for="wa-ent-filter-access">Access</label>
              <select id="wa-ent-filter-access" class="form-control">
                <option value="all">All</option>
                <option value="enabled">Enabled</option>
                <option value="disabled">Disabled</option>
              </select>
            </div>
            <div class="ipb-wa-field">
              <label class="ipb-wa-label" for="wa-ent-filter-account">Account</label>
              <select id="wa-ent-filter-account" class="form-control">
                <option value="all">All</option>
                <option value="configured">Configured</option>
                <option value="missing">Not configured</option>
              </select>
            </div>
          </div>
          <p class="ipb-wa-entitlements-meta" id="wa-ent-count" aria-live="polite"></p>
        <?php endif; ?>

        <div class="table-responsive">
          <table class="table table-hover" id="wa-entitlements-table">
            <thead>
              <tr>
                <th>Second Admin</th>
                <th>Contact</th>
                <th>Status</th>
                <th>Account</th>
                <th>Access</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($entitlements)): ?>
                <tr>
                  <td colspan="5" class="text-muted">No Second Admins found.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($entitlements as $row): ?>
                  <?php
                    $adminId = (int) $row['admin_user_id'];
                    $enabled = !empty($row['enabled']);
                    $hasAccount = !empty($row['has_account']);
                    $searchBlob = strtolower(trim(implode(' ', array_filter([
                      (string) ($row['name'] ?? ''),
                      (string) ($row['mobile'] ?? ''),
                      (string) ($row['email'] ?? ''),
                      (string) $adminId,
                      (string) ($row['display_phone'] ?? ''),
                    ]))));
                  ?>
                  <tr
                    class="wa-ent-row"
                    data-admin-id="<?= $adminId ?>"
                    data-enabled="<?= $enabled ? '1' : '0' ?>"
                    data-has-account="<?= $hasAccount ? '1' : '0' ?>"
                    data-search="<?= esc($searchBlob, 'attr') ?>"
                  >
                    <td>
                      <strong><?= esc($row['name'] ?: ('Admin #' . $adminId)) ?></strong>
                      <div class="ipb-wa-hint">ID <?= $adminId ?></div>
                    </td>
                    <td>
                      <?= esc($row['mobile'] ?: '—') ?>
                      <?php if (!empty($row['email'])): ?>
                        <div class="ipb-wa-hint"><?= esc($row['email']) ?></div>
                      <?php endif; ?>
                    </td>
                    <td class="wa-ent-status">
                      <?php if ($enabled): ?>
                        <span class="label label-success">Enabled</span>
                      <?php else: ?>
                        <span class="label label-default">Disabled</span>
                      <?php endif; ?>
                      <?php if (!empty($row['granted_at'])): ?>
                        <div class="ipb-wa-hint wa-ent-granted-at"><?= esc($row['granted_at']) ?></div>
                      <?php else: ?>
                        <div class="ipb-wa-hint wa-ent-granted-at" hidden></div>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($hasAccount): ?>
                        <span class="label <?= !empty($row['is_active']) ? 'label-success' : 'label-warning' ?>">
                          <?= !empty($row['is_active']) ? 'Active' : 'Inactive' ?>
                        </span>
                        <?php if (!empty($row['display_phone'])): ?>
                          <div class="ipb-wa-hint"><?= esc($row['display_phone']) ?></div>
                        <?php endif; ?>
                      <?php else: ?>
                        <span class="text-muted">Not configured</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <div class="ipb-wa-entitlement-cell">
                        <label class="ipb-switch" for="wa-ent-<?= $adminId ?>"
                          title="<?= $enabled ? 'Disable access' : 'Enable access' ?>">
                          <input type="checkbox"
                            class="wa-entitlement-toggle"
                            id="wa-ent-<?= $adminId ?>"
                            data-admin-id="<?= $adminId ?>"
                            <?= $enabled ? 'checked' : '' ?>
                            aria-label="Toggle WhatsApp access for <?= esc($row['name'] ?: ('Admin #' . $adminId), 'attr') ?>">
                          <span class="ipb-switch-track" aria-hidden="true"></span>
                          <span class="ipb-switch-thumb" aria-hidden="true"></span>
                        </label>
                        <a class="ipb-wa-inbox-link wa-ent-inbox-link"
                           href="<?= site_url('whatsapp/inbox?tenant_admin_id=' . $adminId) ?>"
                           <?= $enabled ? '' : 'hidden' ?>>
                          <i class="fa fa-inbox" aria-hidden="true"></i>
                          <span>View inbox</span>
                          <i class="fa fa-arrow-right" aria-hidden="true"></i>
                        </a>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
                <tr id="wa-ent-empty-filter" hidden>
                  <td colspan="5" class="text-muted">No admins match your search or filters.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <?= form_open(current_url(), 'class="form" id="wa-settings-form"') ?>
    <div class="ipb-wa-layout">

      <div class="ipb-wa-card box">
        <div class="ipb-wa-panel-head">
          <div>
            <h3>API credentials</h3>
            <p class="ipb-wa-panel-sub">From Meta Developer → WhatsApp → API Setup</p>
          </div>
        </div>
        <div class="box-body">
          <div class="ipb-wa-form-grid">
            <div class="ipb-wa-field">
              <label class="ipb-wa-label" for="wa-phone-number-id">Phone Number ID</label>
              <input id="wa-phone-number-id" type="text" name="phone_number_id" class="form-control"
                placeholder="e.g. 123456789012345"
                value="<?= esc($a['phone_number_id'] ?? '') ?>"
                <?= empty($can_update) ? 'readonly' : '' ?> required>
            </div>
            <div class="ipb-wa-field">
              <label class="ipb-wa-label" for="wa-display-phone">Display phone (E.164)</label>
              <input id="wa-display-phone" type="text" name="display_phone" class="form-control"
                placeholder="8801XXXXXXXXX"
                value="<?= esc($a['display_phone'] ?? '') ?>"
                <?= empty($can_update) ? 'readonly' : '' ?>>
            </div>
            <div class="ipb-wa-field">
              <label class="ipb-wa-label" for="wa-waba-id">WABA ID</label>
              <input id="wa-waba-id" type="text" name="waba_id" class="form-control"
                placeholder="WhatsApp Business Account ID"
                value="<?= esc($a['waba_id'] ?? '') ?>"
                <?= empty($can_update) ? 'readonly' : '' ?>>
            </div>
            <div class="ipb-wa-field">
              <label class="ipb-wa-label" for="wa-verify-token">Verify Token</label>
              <input id="wa-verify-token" type="text" name="verify_token" class="form-control"
                placeholder="Long random string (same value in Meta)"
                value="<?= esc($a['verify_token'] ?? '') ?>"
                <?= empty($can_update) ? 'readonly' : '' ?>>
            </div>
            <div class="ipb-wa-field is-wide">
              <label class="ipb-wa-label" for="wa-access-token">Access Token</label>
              <input id="wa-access-token" type="password" name="access_token" class="form-control"
                autocomplete="new-password"
                placeholder="<?= !empty($a['access_token_masked']) ? 'Leave blank to keep ' . esc($a['access_token_masked'], 'attr') : 'Permanent System User token' ?>"
                <?= empty($can_update) ? 'readonly' : '' ?>>
              <?php if (!empty($a['access_token_masked'])): ?>
                <span class="ipb-wa-hint">Currently saved: <?= esc($a['access_token_masked']) ?></span>
              <?php endif; ?>
            </div>
            <div class="ipb-wa-field is-wide">
              <label class="ipb-wa-label" for="wa-app-secret">App Secret</label>
              <input id="wa-app-secret" type="password" name="app_secret" class="form-control"
                autocomplete="new-password"
                placeholder="<?= !empty($a['has_app_secret']) ? 'Leave blank to keep existing' : 'Meta App → Settings → Basic' ?>"
                <?= empty($can_update) ? 'readonly' : '' ?>>
              <?php if (!empty($a['has_app_secret'])): ?>
                <span class="ipb-wa-hint">An app secret is already stored.</span>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <div class="ipb-wa-card box">
        <div class="ipb-wa-panel-head">
          <div>
            <h3>Features</h3>
            <p class="ipb-wa-panel-sub">Enable only what this tenant needs</p>
          </div>
        </div>
        <div class="box-body">
          <div class="ipb-wa-toggles">
            <label class="ipb-wa-toggle" for="wa-feat-active">
              <span class="ipb-wa-toggle__text">
                <strong>Active (master)</strong>
                <span>Turns WhatsApp messaging on for this account</span>
              </span>
              <span class="ipb-switch">
                <input id="wa-feat-active" type="checkbox" name="is_active" value="1"
                  <?= !empty($a['is_active']) ? 'checked' : '' ?>
                  <?= empty($can_update) ? 'disabled' : '' ?>>
                <span class="ipb-switch-track" aria-hidden="true"></span>
                <span class="ipb-switch-thumb" aria-hidden="true"></span>
              </span>
            </label>
            <label class="ipb-wa-toggle" for="wa-feat-ai">
              <span class="ipb-wa-toggle__text">
                <strong>Service AI auto-reply</strong>
                <span><?= empty($can_ai) ? 'Permission required: AI' : 'AI replies inside the customer-care window' ?></span>
              </span>
              <span class="ipb-switch">
                <input id="wa-feat-ai" type="checkbox" name="ai_enabled" value="1"
                  <?= !empty($a['ai_enabled']) ? 'checked' : '' ?>
                  <?= (empty($can_update) || empty($can_ai)) ? 'disabled' : '' ?>>
                <span class="ipb-switch-track" aria-hidden="true"></span>
                <span class="ipb-switch-thumb" aria-hidden="true"></span>
              </span>
            </label>
            <label class="ipb-wa-toggle" for="wa-feat-utility">
              <span class="ipb-wa-toggle__text">
                <strong>Utility templates</strong>
                <span>Invoices, payment receipts, and similar notices</span>
              </span>
              <span class="ipb-switch">
                <input id="wa-feat-utility" type="checkbox" name="utility_enabled" value="1"
                  <?= !empty($a['utility_enabled']) ? 'checked' : '' ?>
                  <?= (empty($can_update) || empty($can_utility)) ? 'disabled' : '' ?>>
                <span class="ipb-switch-track" aria-hidden="true"></span>
                <span class="ipb-switch-thumb" aria-hidden="true"></span>
              </span>
            </label>
            <label class="ipb-wa-toggle" for="wa-feat-auth">
              <span class="ipb-wa-toggle__text">
                <strong>Authentication OTP</strong>
                <span>Password reset / login codes via WhatsApp</span>
              </span>
              <span class="ipb-switch">
                <input id="wa-feat-auth" type="checkbox" name="authentication_enabled" value="1"
                  <?= !empty($a['authentication_enabled']) ? 'checked' : '' ?>
                  <?= (empty($can_update) || empty($can_auth)) ? 'disabled' : '' ?>>
                <span class="ipb-switch-track" aria-hidden="true"></span>
                <span class="ipb-switch-thumb" aria-hidden="true"></span>
              </span>
            </label>
            <label class="ipb-wa-toggle" for="wa-feat-marketing">
              <span class="ipb-wa-toggle__text">
                <strong>Marketing campaigns</strong>
                <span>Opt-in broadcast campaigns (billable)</span>
              </span>
              <span class="ipb-switch">
                <input id="wa-feat-marketing" type="checkbox" name="marketing_enabled" value="1"
                  <?= !empty($a['marketing_enabled']) ? 'checked' : '' ?>
                  <?= (empty($can_update) || empty($can_marketing)) ? 'disabled' : '' ?>>
                <span class="ipb-switch-track" aria-hidden="true"></span>
                <span class="ipb-switch-thumb" aria-hidden="true"></span>
              </span>
            </label>
          </div>

          <div class="ipb-wa-field" style="margin-top:16px;">
            <label class="ipb-wa-label" for="wa-otp-channel">OTP channel</label>
            <select id="wa-otp-channel" name="otp_channel" class="form-control" <?= (empty($can_update) || empty($can_auth)) ? 'disabled' : '' ?>>
              <?php $ch = $a['otp_channel'] ?? 'sms'; ?>
              <option value="sms" <?= $ch === 'sms' ? 'selected' : '' ?>>SMS only</option>
              <option value="whatsapp" <?= $ch === 'whatsapp' ? 'selected' : '' ?>>WhatsApp only</option>
              <option value="whatsapp_then_sms" <?= $ch === 'whatsapp_then_sms' ? 'selected' : '' ?>>WhatsApp then SMS fallback</option>
              <option value="sms_then_whatsapp" <?= $ch === 'sms_then_whatsapp' ? 'selected' : '' ?>>SMS then WhatsApp fallback</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <?php if (!empty($can_update)): ?>
      <div class="ipb-wa-actions">
        <button type="submit" class="btn btn-primary">
          <i class="fa fa-save" aria-hidden="true"></i> Save settings
        </button>
      </div>
    <?php endif; ?>
    <?= form_close() ?>

  </section>
</div>
<script>
(function () {
  var copyBtn = document.getElementById('wa-copy-webhook');
  if (copyBtn) {
    copyBtn.addEventListener('click', function () {
      var el = document.getElementById('wa-webhook-url');
      var text = el ? el.textContent : '';
      if (!text) return;
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(function () {
          copyBtn.innerHTML = '<i class="fa fa-check" aria-hidden="true"></i> Copied';
          setTimeout(function () {
            copyBtn.innerHTML = '<i class="fa fa-copy" aria-hidden="true"></i> Copy';
          }, 1600);
        });
      }
    });
  }

  var form = document.getElementById('wa-settings-form');
  if (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var fd = new FormData(form);
      fetch(form.action, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { return r.json(); })
        .then(function (j) {
          alert(j.message || (j.status === 'succes' || j.status === 'success' ? 'Saved' : 'Error'));
          if (j.status === 'succes' || j.status === 'success') location.reload();
        })
        .catch(function () { alert('Request failed'); });
    });
  }

  function applyEntitlementFilters() {
    var searchEl = document.getElementById('wa-ent-search');
    var accessEl = document.getElementById('wa-ent-filter-access');
    var accountEl = document.getElementById('wa-ent-filter-account');
    var countEl = document.getElementById('wa-ent-count');
    var emptyEl = document.getElementById('wa-ent-empty-filter');
    var rows = document.querySelectorAll('#wa-entitlements-table tbody tr.wa-ent-row');
    if (!rows.length) return;

    var q = (searchEl && searchEl.value ? searchEl.value : '').trim().toLowerCase();
    var access = accessEl ? accessEl.value : 'all';
    var account = accountEl ? accountEl.value : 'all';
    var visible = 0;

    rows.forEach(function (row) {
      var hay = row.getAttribute('data-search') || '';
      var enabled = row.getAttribute('data-enabled') === '1';
      var hasAccount = row.getAttribute('data-has-account') === '1';
      var matchSearch = !q || hay.indexOf(q) !== -1;
      var matchAccess = access === 'all'
        || (access === 'enabled' && enabled)
        || (access === 'disabled' && !enabled);
      var matchAccount = account === 'all'
        || (account === 'configured' && hasAccount)
        || (account === 'missing' && !hasAccount);
      var show = matchSearch && matchAccess && matchAccount;
      row.classList.toggle('is-filtered-out', !show);
      if (show) visible += 1;
    });

    if (emptyEl) emptyEl.hidden = visible !== 0;
    if (countEl) {
      countEl.innerHTML = 'Showing <strong>' + visible + '</strong> of <strong>' + rows.length + '</strong>';
    }
  }

  function setRowEnabledUi(row, enabled) {
    row.setAttribute('data-enabled', enabled ? '1' : '0');
    var statusCell = row.querySelector('.wa-ent-status');
    if (statusCell) {
      var label = statusCell.querySelector('.label');
      if (label) {
        label.className = 'label ' + (enabled ? 'label-success' : 'label-default');
        label.textContent = enabled ? 'Enabled' : 'Disabled';
      }
      var granted = statusCell.querySelector('.wa-ent-granted-at');
      if (granted && enabled && !granted.textContent.trim()) {
        granted.hidden = false;
        granted.textContent = new Date().toISOString().slice(0, 19).replace('T', ' ');
      }
    }
    var inbox = row.querySelector('.wa-ent-inbox-link');
    if (inbox) inbox.hidden = !enabled;
    var sw = row.querySelector('.ipb-switch');
    if (sw) sw.setAttribute('title', enabled ? 'Disable access' : 'Enable access');
    applyEntitlementFilters();
  }

  ['wa-ent-search', 'wa-ent-filter-access', 'wa-ent-filter-account'].forEach(function (id) {
    var el = document.getElementById(id);
    if (!el) return;
    el.addEventListener(id === 'wa-ent-search' ? 'input' : 'change', applyEntitlementFilters);
  });
  applyEntitlementFilters();

  document.querySelectorAll('.wa-entitlement-toggle').forEach(function (input) {
    input.addEventListener('change', function () {
      var adminId = input.getAttribute('data-admin-id');
      var enabled = input.checked ? 1 : 0;
      var row = input.closest('tr.wa-ent-row');
      var sw = input.closest('.ipb-switch');
      input.disabled = true;
      if (sw) sw.classList.add('is-busy');
      var fd = new FormData();
      fd.append('admin_user_id', adminId);
      fd.append('enabled', String(enabled));
      fd.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
      fetch('<?= site_url('whatsapp/entitlements') ?>', {
        method: 'POST',
        body: fd,
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          '<?= csrf_header() ?>': '<?= csrf_hash() ?>'
        }
      })
        .then(function (r) {
          return r.json().then(function (j) {
            return { ok: r.ok, status: r.status, body: j };
          });
        })
        .then(function (res) {
          input.disabled = false;
          if (sw) sw.classList.remove('is-busy');
          var j = res.body || {};
          if (res.ok && (j.status === 'succes' || j.status === 'success')) {
            if (row) setRowEnabledUi(row, !!enabled);
            return;
          }
          input.checked = !input.checked;
          if (row) setRowEnabledUi(row, input.checked);
          var msg = (typeof j.response === 'string' ? j.response : null)
            || j.message
            || (res.status === 403 ? 'Session expired — refresh and try again' : 'Could not update access');
          alert(msg);
        })
        .catch(function () {
          input.disabled = false;
          if (sw) sw.classList.remove('is-busy');
          input.checked = !input.checked;
          if (row) setRowEnabledUi(row, input.checked);
          alert('Request failed');
        });
    });
  });
})();
</script>
<?= $this->endSection() ?>
