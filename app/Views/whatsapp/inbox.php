<?= $this->extend('layout/main-layout') ?>

<?= $this->section('css') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/saas/whatsapp-pages.css?v=1') ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="content-wrapper">
  <section class="content ipb-saas-list ipb-wa-page">

    <?= $this->include('components/page-header', [
      'title' => 'WhatsApp Inbox',
      'subtitle' => 'Service conversations from Meta Cloud API',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'WhatsApp Business', 'url' => route_to('route.whatsapp.settings')],
        ['label' => 'Inbox'],
      ],
    ]) ?>

    <?php if (!empty($is_platform) && !empty($tenant_options)): ?>
    <div class="ipb-wa-toolbar">
      <div class="ipb-wa-field">
        <label class="ipb-wa-label" for="wa-tenant-switch">View inbox for</label>
        <select id="wa-tenant-switch" class="form-control">
          <option value="">My platform account</option>
          <?php foreach ($tenant_options as $opt): ?>
            <option value="<?= (int) $opt['admin_user_id'] ?>"
              <?= ((int) ($selected_tenant ?? 0) === (int) $opt['admin_user_id']) ? 'selected' : '' ?>>
              <?= esc($opt['name'] ?: ('Admin #' . $opt['admin_user_id'])) ?>
              <?= !empty($opt['display_phone']) ? ' — ' . esc($opt['display_phone']) : '' ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <?php endif; ?>

    <div class="ipb-wa-inbox">
      <div class="ipb-wa-card box ipb-wa-inbox-list">
        <div class="ipb-wa-panel-head">
          <div>
            <h3>Conversations</h3>
            <p class="ipb-wa-panel-sub">Select a thread to reply</p>
          </div>
        </div>
        <div class="ipb-wa-conv-scroll" id="wa-conv-list">
          <div class="ipb-wa-empty-pad">
            <p class="text-muted" style="margin:0;text-align:center;">Loading…</p>
          </div>
        </div>
      </div>

      <div class="ipb-wa-card box ipb-wa-inbox-thread">
        <div class="ipb-wa-panel-head">
          <div>
            <h3 id="wa-thread-title">Select a conversation</h3>
            <p class="ipb-wa-panel-sub" id="wa-thread-sub">Messages appear here</p>
          </div>
        </div>
        <div class="ipb-wa-thread-scroll" id="wa-thread">
          <div class="ipb-wa-empty-pad">
            <?= $this->include('components/empty-state', [
              'title' => 'No conversation selected',
              'subtitle' => 'Pick a chat from the left to view messages.',
              'icon' => 'fa fa-whatsapp',
            ]) ?>
          </div>
        </div>
        <?php if (!empty($can_send)): ?>
        <form id="wa-send-form" class="ipb-wa-composer">
          <input type="hidden" id="wa-to" value="">
          <input type="text" id="wa-text" class="form-control" placeholder="Type a reply…" autocomplete="off" disabled>
          <button class="btn btn-primary" type="submit" disabled id="wa-send-btn">Send</button>
        </form>
        <?php endif; ?>
      </div>
    </div>

  </section>
</div>
<script>
(function () {
  var listEl = document.getElementById('wa-conv-list');
  var threadEl = document.getElementById('wa-thread');
  var titleEl = document.getElementById('wa-thread-title');
  var subEl = document.getElementById('wa-thread-sub');
  var toEl = document.getElementById('wa-to');
  var textEl = document.getElementById('wa-text');
  var sendBtn = document.getElementById('wa-send-btn');
  var tenantSwitch = document.getElementById('wa-tenant-switch');
  var selectedTenantId = <?= (int) ($selected_tenant ?? 0) ?>;
  var myAdminId = <?= (int) (session()->get('user_id') ?? 0) ?>;

  function tenantQuery() {
    if (!tenantSwitch) return '';
    var v = tenantSwitch.value;
    if (!v) return '';
    return '?tenant_admin_id=' + encodeURIComponent(v);
  }

  function activeTenantId() {
    if (tenantSwitch && tenantSwitch.value) {
      return parseInt(tenantSwitch.value, 10) || 0;
    }
    return selectedTenantId || myAdminId;
  }

  if (tenantSwitch) {
    tenantSwitch.addEventListener('change', function () {
      var v = tenantSwitch.value;
      var url = '<?= site_url('whatsapp/inbox') ?>';
      if (v) url += '?tenant_admin_id=' + encodeURIComponent(v);
      window.location.href = url;
    });
  }

  function esc(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function setComposerEnabled(on) {
    if (textEl) textEl.disabled = !on;
    if (sendBtn) sendBtn.disabled = !on;
  }

  function loadList() {
    fetch('<?= site_url('whatsapp/conversations') ?>' + tenantQuery(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        var items = (j.conversations || j.data || j.result || []);
        if (!Array.isArray(items) || !items.length) {
          listEl.innerHTML = '<div class="ipb-wa-empty-pad"><div class="ipb-empty"><div class="ipb-empty-icon"><i class="fa fa-inbox" aria-hidden="true"></i></div><div class="ipb-empty-title">No conversations yet</div><div class="ipb-empty-sub">Inbound WhatsApp chats will show up here.</div></div></div>';
          return;
        }
        listEl.innerHTML = items.map(function (c) {
          var id = c.conversation_id || c.id;
          var phone = c.wa_phone || c.user_id || 'unknown';
          return '<a href="#" class="ipb-wa-conv" data-id="' + esc(id) + '" data-phone="' + esc(phone) + '">' +
            '<strong>' + esc(phone) + '</strong>' +
            '<small>' + esc(c.updated_at || '') + '</small></a>';
        }).join('');
      })
      .catch(function () {
        listEl.innerHTML = '<div class="ipb-wa-empty-pad"><div class="ipb-empty is-error" role="alert"><div class="ipb-empty-icon"><i class="fa fa-triangle-exclamation" aria-hidden="true"></i></div><div class="ipb-empty-title">Failed to load</div><div class="ipb-empty-sub">Check that the AI Chatbot is running and try again.</div></div></div>';
      });
  }

  listEl.addEventListener('click', function (e) {
    var a = e.target.closest('.ipb-wa-conv');
    if (!a) return;
    e.preventDefault();
    listEl.querySelectorAll('.ipb-wa-conv').forEach(function (el) { el.classList.remove('is-active'); });
    a.classList.add('is-active');

    var id = a.getAttribute('data-id');
    var phone = a.getAttribute('data-phone');
    if (toEl) toEl.value = phone;
    titleEl.textContent = phone;
    if (subEl) subEl.textContent = 'Conversation thread';
    setComposerEnabled(true);
    threadEl.innerHTML = '<div class="ipb-wa-empty-pad"><p class="text-muted" style="margin:0;text-align:center;">Loading…</p></div>';

    fetch('<?= site_url('whatsapp/conversations') ?>/' + encodeURIComponent(id) + tenantQuery(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        var msgs = j.messages || j.data || [];
        if (!msgs || !msgs.length) {
          threadEl.innerHTML = '<div class="ipb-wa-empty-pad"><p class="text-muted" style="margin:0;text-align:center;">Empty thread</p></div>';
          return;
        }
        threadEl.innerHTML = msgs.map(function (m) {
          var role = m.role || m.sender || 'user';
          var isOut = role === 'assistant' || role === 'bot' || role === 'agent' || role === 'business';
          return '<div class="ipb-wa-bubble-row ' + (isOut ? 'is-out' : 'is-in') + '">' +
            '<div class="ipb-wa-bubble">' +
            '<span class="ipb-wa-bubble__meta">' + esc(role) + '</span>' +
            '<div class="ipb-wa-bubble__text">' + esc(m.content || m.text || '') + '</div>' +
            '</div></div>';
        }).join('');
        threadEl.scrollTop = threadEl.scrollHeight;
      })
      .catch(function () {
        threadEl.innerHTML = '<div class="ipb-wa-empty-pad"><p class="text-danger" style="margin:0;text-align:center;">Failed to load messages.</p></div>';
      });
  });

  var form = document.getElementById('wa-send-form');
  if (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var to = toEl.value;
      var text = textEl.value.trim();
      if (!to || !text) return alert('Select a conversation and enter text');
      sendBtn.disabled = true;
      var payload = { to: to, text: text };
      var tid = activeTenantId();
      if (tid) payload.tenant_admin_id = tid;
      fetch('<?= site_url('whatsapp/send') ?>' + tenantQuery(), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify(payload)
      }).then(function (r) { return r.json(); }).then(function (j) {
        alert(j.message || 'Done');
        textEl.value = '';
        sendBtn.disabled = false;
      }).catch(function () {
        alert('Send failed');
        sendBtn.disabled = false;
      });
    });
  }

  loadList();
})();
</script>
<?= $this->endSection() ?>
