<?= $this->extend('layout/main-layout'); ?>

<?= $this->section('css'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/saas/dashboard.css?v=21'); ?>">
<?= $this->endSection('css'); ?>

<?= $this->section('content'); ?>

<?php
  // Show create UI if user has create, or module access (route still enforces create).
  $canCreate = userHasPermission('support_ticket', 'create')
    || userHasPermission('support_ticket');
  $canDelete = userHasPermission('support_ticket', 'delete');
  $showUser = getSession('user_role') != 'user';
  // Staff can transfer tickets to employees (same as legacy toolbar).
  $canTransfer = in_array(getSession('user_role'), ['admin', 'super_admin', 'resellerAdmin', 'employee'], true);
  $newTicketUrl = route_to('route.ticket.new');
  $hasBulkActions = $canTransfer || $canDelete;
?>

<div class="content-wrapper">
  <section class="content ipb-saas-list ipb-tickets-page">

    <?= $this->include('components/page-header', [
      'title' => 'Support Tickets',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Support Tickets'],
      ],
    ]); ?>

    <div class="ipb-dash-mini ipb-ticket-stats" id="ipbTicketStats">
      <div class="ipb-kpi tone-brand compact">
        <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-ticket"></i></span></div>
        <div class="ipb-kpi-value" id="ipbStatTotal">0</div>
        <div class="ipb-kpi-label">Total</div>
      </div>
      <div class="ipb-kpi tone-success compact">
        <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-envelope-open-text"></i></span></div>
        <div class="ipb-kpi-value" id="ipbStatOpened">0</div>
        <div class="ipb-kpi-label">Open</div>
      </div>
      <div class="ipb-kpi tone-warning compact">
        <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-arrows-rotate"></i></span></div>
        <div class="ipb-kpi-value" id="ipbStatProcessing">0</div>
        <div class="ipb-kpi-label">Processing</div>
      </div>
      <div class="ipb-kpi tone-navy compact">
        <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-box-archive"></i></span></div>
        <div class="ipb-kpi-value" id="ipbStatClosed">0</div>
        <div class="ipb-kpi-label">Closed</div>
      </div>
      <div class="ipb-kpi tone-error compact">
        <div class="ipb-kpi-top"><span class="ipb-kpi-icon"><i class="fa-solid fa-envelope-circle-check"></i></span></div>
        <div class="ipb-kpi-value" id="ipbStatUnread">0</div>
        <div class="ipb-kpi-label">Unread</div>
      </div>
    </div>

    <div class="ipb-ticket-inbox card" id="ipbTicketInbox">
      <div class="ipb-ticket-inbox-list">
        <div class="ipb-ticket-inbox-list-head">
          <div class="ipb-ticket-inbox-search">
            <i class="fa fa-search" aria-hidden="true"></i>
            <input type="search" id="ipbTicketSearch" placeholder="Search tickets..." autocomplete="off" aria-label="Search tickets">
          </div>
          <div class="ipb-ticket-toolbar-actions">
            <?php if ($canCreate): ?>
              <a class="btn btn-primary ipb-ticket-new-btn" href="<?= $newTicketUrl; ?>" title="Create a new ticket">
                <i class="fa fa-plus" aria-hidden="true"></i>
                <span class="ipb-ticket-new-label">New</span>
              </a>
            <?php endif; ?>
            <?php if ($hasBulkActions): ?>
              <div class="ipb-ticket-more" id="ipbTicketMore">
                <button type="button" class="ipb-ticket-more-btn" id="ipbTicketMoreBtn" aria-haspopup="true" aria-expanded="false" aria-label="More actions" title="More actions">
                  <i class="fa fa-ellipsis-v" aria-hidden="true"></i>
                </button>
                <div class="ipb-ticket-more-menu" id="ipbTicketMoreMenu" hidden>
                  <label class="ipb-ticket-more-item ipb-ticket-select-all" title="Select all tickets">
                    <input type="checkbox" class="form-check-input" id="select_all">
                    <span>Select all</span>
                  </label>
                  <?php if ($canTransfer): ?>
                    <button type="button" class="ipb-ticket-more-item customer-transfer-button">
                      <i class="fa fa-exchange" aria-hidden="true"></i>
                      <span>Transfer</span>
                    </button>
                  <?php endif; ?>
                  <?php if ($canDelete): ?>
                    <button type="button" class="ipb-ticket-more-item is-danger delete-btn">
                      <i class="far fa-trash-can" aria-hidden="true"></i>
                      <span>Delete</span>
                    </button>
                  <?php endif; ?>
                </div>
              </div>
            <?php endif; ?>
          </div>
        </div>
        <div class="ipb-ticket-filter-tabs" id="ipbTicketFilterTabs" role="tablist" aria-label="Filter tickets by status">
          <button type="button" class="ipb-ticket-filter-tab is-active" data-filter="all" role="tab" aria-selected="true">All</button>
          <button type="button" class="ipb-ticket-filter-tab" data-filter="opened" role="tab" aria-selected="false">Open</button>
          <button type="button" class="ipb-ticket-filter-tab" data-filter="processing" role="tab" aria-selected="false">Processing</button>
          <button type="button" class="ipb-ticket-filter-tab" data-filter="closed" role="tab" aria-selected="false">Closed</button>
        </div>
        <div class="ipb-ticket-inbox-items" id="ipbTicketList" role="listbox" aria-label="Ticket list">
          <div class="ipb-ticket-inbox-loading">Loading tickets…</div>
        </div>
      </div>
      <div class="ipb-ticket-inbox-pane" id="ipbTicketPane">
        <div class="ipb-ticket-inbox-empty" id="ipbTicketEmpty">
          <div class="ipb-ticket-inbox-empty-icon" aria-hidden="true"><i class="fa fa-life-ring"></i></div>
          <div class="ipb-ticket-inbox-empty-title">Select a ticket</div>
          <div class="ipb-ticket-inbox-empty-sub">Choose a conversation from the list to view the full thread.</div>
        </div>
      </div>
    </div>

  </section>
</div>

<?= $this->endSection('content'); ?>

<?= $this->section('script'); ?>

<script>
(function ($) {
  'use strict';

  var inboxUrl = <?= json_encode(route_to('route.ticket.inbox')); ?>;
  var detailsUrlTpl = <?= json_encode(route_to('route.ticket.details', 0)); ?>;
  var quickStatusUrlTpl = <?= json_encode(route_to('route.ticket.quickstatus', 0)); ?>;
  var avatarUrl = <?= json_encode(base_url('assets/img/icon/avatar.png')); ?>;
  var csrfHeader = <?= json_encode(csrf_header()); ?>;
  var csrfHash = <?= json_encode(csrf_hash()); ?>;
  var showUser = <?= $showUser ? 'true' : 'false'; ?>;
  var canCreate = <?= $canCreate ? 'true' : 'false'; ?>;
  var canDelete = <?= $canDelete ? 'true' : 'false'; ?>;
  var canTransfer = <?= $canTransfer ? 'true' : 'false'; ?>;
  var newTicketUrl = <?= json_encode($newTicketUrl); ?>;
  var tickets = [];
  var activeId = null;
  var statusFilter = 'all';
  var initialId = (function () {
    try {
      var q = new URLSearchParams(window.location.search).get('id');
      if (q) return parseInt(q, 10) || null;
      var h = (window.location.hash || '').replace(/^#/, '');
      if (h && /^\d+$/.test(h)) return parseInt(h, 10);
    } catch (e) {}
    return null;
  })();

  function esc(s) {
    return $('<div>').text(s == null ? '' : String(s)).html();
  }

  function priorityBadge(p) {
    var tone = p === 'high' ? 'is-danger' : (p === 'medium' ? 'is-warning' : 'is-info');
    return '<span class="ipb-pay-badge ' + tone + '">' + esc(p.charAt(0).toUpperCase() + p.slice(1)) + '</span>';
  }

  function statusBadge(s) {
    var tone = 'is-neutral';
    var label = 'Closed';
    if (s === 'opened') { tone = 'is-success'; label = 'Opened'; }
    else if (s === 'processing' || s === 'pending') { tone = 'is-warning'; label = s === 'pending' ? 'Pending' : 'Processing'; }
    return '<span class="ipb-pay-badge ' + tone + '">' + esc(label) + '</span>';
  }

  function normalizedStatus(s) {
    // 'pending' is a legacy alias seen in older rows — treat it as 'processing' for filtering/stats.
    return s === 'pending' ? 'processing' : (s || 'closed');
  }

  function updateStats() {
    var total = tickets.length;
    var opened = 0, processing = 0, closed = 0, unread = 0;
    tickets.forEach(function (t) {
      var st = normalizedStatus(t.status);
      if (st === 'opened') opened++;
      else if (st === 'processing') processing++;
      else closed++;
      if (!t.viewed) unread++;
    });
    $('#ipbStatTotal').text(total);
    $('#ipbStatOpened').text(opened);
    $('#ipbStatProcessing').text(processing);
    $('#ipbStatClosed').text(closed);
    $('#ipbStatUnread').text(unread);
  }

  function renderList(filter) {
    var $list = $('#ipbTicketList');
    var q = (filter || '').trim().toLowerCase();
    var rows = tickets.filter(function (t) {
      if (statusFilter !== 'all' && normalizedStatus(t.status) !== statusFilter) return false;
      if (!q) return true;
      return (t.subject + ' ' + t.user + ' ' + t.category + ' ' + t.status + ' ' + t.priority)
        .toLowerCase().indexOf(q) !== -1;
    });

    if (!rows.length) {
      var html;
      if (!tickets.length) {
        // Truly no tickets exist yet — the original "get started" empty state.
        var emptyCta = canCreate
          ? '<a class="btn btn-primary ipb-ticket-empty-cta" href="' + newTicketUrl + '">' +
              '<i class="fa fa-plus" aria-hidden="true"></i> Create your first ticket</a>'
          : '';
        html =
          '<div class="ipb-ticket-list-empty">' +
            '<div class="ipb-ticket-list-empty-icon" aria-hidden="true"><i class="fa fa-ticket"></i></div>' +
            '<div class="ipb-ticket-list-empty-title">No tickets yet.</div>' +
            emptyCta +
          '</div>';
      } else if (statusFilter === 'opened' && !q) {
        // 06 §9 — "Inbox zero": every ticket is accounted for, none are open.
        html =
          '<div class="ipb-ticket-list-empty">' +
            '<div class="ipb-ticket-list-empty-icon" aria-hidden="true"><i class="fa fa-ticket"></i></div>' +
            '<div class="ipb-ticket-list-empty-title">Inbox zero</div>' +
            '<div class="ipb-ticket-list-empty-sub">No open support tickets right now — nice and quiet.</div>' +
            '<button type="button" class="btn btn-primary ipb-ticket-empty-cta ipb-ticket-goto-closed">View closed tickets</button>' +
          '</div>';
      } else {
        // 06 §9 — "Nothing matches these filters": search text and/or a status tab hides everything.
        html =
          '<div class="ipb-ticket-list-empty">' +
            '<div class="ipb-ticket-list-empty-icon" aria-hidden="true"><i class="fa fa-filter"></i></div>' +
            '<div class="ipb-ticket-list-empty-title">Nothing matches these filters</div>' +
            '<div class="ipb-ticket-list-empty-sub">Your filters are hiding everything. Clear them to see the full list.</div>' +
            '<button type="button" class="btn btn-primary ipb-ticket-empty-cta ipb-ticket-clear-filters">Clear filters</button>' +
          '</div>';
      }
      $list.html(html);
      return;
    }

    var html = rows.map(function (t) {
      var active = activeId === t.id ? ' is-active' : '';
      var unread = !t.viewed ? '<span class="ipb-ticket-unread" title="Unread"></span>' : '';
      var meta = showUser
        ? esc(t.user) + ' · ' + esc(t.category)
        : esc(t.category) + (t.datetime ? ' · ' + esc(t.datetime) : '');
      var check = (canDelete || canTransfer)
        ? '<input type="checkbox" class="form-check-input input-check-selected" value="' + t.id + '" aria-label="Select ticket">'
        : '';
      return (
        '<div class="ipb-ticket-inbox-item' + active + '" role="option" tabindex="0" data-id="' + t.id + '" aria-selected="' + (activeId === t.id ? 'true' : 'false') + '">' +
          '<div class="ipb-ticket-inbox-item-top">' +
            (check ? '<span class="ipb-ticket-inbox-check">' + check + '</span>' : '') +
            '<span class="ipb-ticket-inbox-subject">' + esc(t.subject) + '</span>' +
            unread +
          '</div>' +
          '<div class="ipb-ticket-inbox-meta">' + meta + '</div>' +
          '<div class="ipb-ticket-inbox-badges">' +
            priorityBadge(t.priority || 'low') +
            statusBadge(t.status || 'closed') +
          '</div>' +
        '</div>'
      );
    }).join('');

    $list.html(html);
  }

  function setActiveItem(id) {
    activeId = id;
    tickets.forEach(function (t) {
      if (t.id === id) t.viewed = true;
    });
    $('#ipbTicketList .ipb-ticket-inbox-item').each(function () {
      var on = parseInt($(this).data('id'), 10) === id;
      $(this).toggleClass('is-active', on).attr('aria-selected', on ? 'true' : 'false');
      if (on) $(this).find('.ipb-ticket-unread').remove();
    });
    updateStats();
  }

  function isPhoneInbox() {
    return window.matchMedia && window.matchMedia('(max-width: 767px)').matches;
  }

  function setThreadOpen(open) {
    var on = !!open;
    $('#ipbTicketInbox').toggleClass('is-thread-open', on);
    $('body').toggleClass('ipb-ticket-mobile-chat', on && isPhoneInbox());
    if (on && isPhoneInbox()) {
      try {
        window.scrollTo({ top: 0, behavior: 'instant' in window ? 'instant' : 'auto' });
      } catch (e) {
        window.scrollTo(0, 0);
      }
    }
  }

  function showEmptyPane() {
    setThreadOpen(false);
    activeId = null;
    $('#ipbTicketPane').html(
      '<div class="ipb-ticket-inbox-empty" id="ipbTicketEmpty">' +
        '<div class="ipb-ticket-inbox-empty-icon" aria-hidden="true"><i class="fa fa-life-ring"></i></div>' +
        '<div class="ipb-ticket-inbox-empty-title">Select a ticket</div>' +
        '<div class="ipb-ticket-inbox-empty-sub">Choose a conversation from the list to view the full thread.</div>' +
      '</div>'
    );
  }

  function scrollMessages() {
    var el = $('#ipbTicketPane .ipb-ticket-messages')[0];
    if (el) el.scrollTop = el.scrollHeight;
  }

  function loadTicket(id) {
    if (!id) return;
    setActiveItem(id);
    setThreadOpen(true);
    try {
      history.replaceState(null, '', '?id=' + id);
    } catch (e) {}

    var $pane = $('#ipbTicketPane');
    $pane.html('<div class="ipb-ticket-inbox-loading">Loading conversation…</div>');

    $.ajax({
      url: detailsUrlTpl.replace(/\/0(\/?$)/, '/' + id + '$1'),
      type: 'GET',
      data: { partial: 1 },
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      success: function (html) {
        $pane.html(html);
        // Ensure mobile chat panel is active after paint
        setThreadOpen(true);
        window.requestAnimationFrame(function () {
          scrollMessages();
        });
      },
      error: function () {
        setThreadOpen(false);
        $pane.html('<div class="ipb-ticket-inbox-empty"><div class="ipb-ticket-inbox-empty-title">Could not load ticket</div><div class="ipb-ticket-inbox-empty-sub">Try again or open the ticket in a new page.</div></div>');
      }
    });
  }

  function loadInbox(selectId) {
    $('#ipbTicketList').html('<div class="ipb-ticket-inbox-loading">Loading tickets…</div>');
    $.ajax({
      url: inboxUrl,
      type: 'GET',
      dataType: 'json',
      success: function (res) {
        tickets = (res && res.data) ? res.data : [];
        renderList($('#ipbTicketSearch').val());
        updateStats();
        // Phone: show list first unless deep-linked. Desktop/tablet: open first ticket.
        var pick = selectId || initialId || null;
        if (!pick && !isPhoneInbox() && tickets[0]) {
          pick = tickets[0].id;
        }
        if (pick) loadTicket(pick);
        else showEmptyPane();
      },
      error: function () {
        $('#ipbTicketList').html('<div class="ipb-ticket-inbox-loading">Failed to load tickets.</div>');
        showEmptyPane();
      }
    });
  }

  $(document).ready(function () {
    loadInbox();

    // ⋮ overflow menus (list toolbar + chat thread, including AJAX-loaded thread)
    function closeAllMoreMenus(except) {
      $('.ipb-ticket-more').each(function () {
        if (except && this === except) return;
        var $wrap = $(this);
        $wrap.removeClass('is-open');
        $wrap.find('.ipb-ticket-more-menu').prop('hidden', true);
        $wrap.find('.ipb-ticket-more-btn').attr('aria-expanded', 'false');
      });
    }

    $(document).on('click', '.ipb-ticket-more-btn', function (e) {
      e.preventDefault();
      e.stopPropagation();
      var $wrap = $(this).closest('.ipb-ticket-more');
      var $menu = $wrap.find('.ipb-ticket-more-menu');
      var willOpen = $menu.prop('hidden');
      closeAllMoreMenus(willOpen ? $wrap[0] : null);
      $menu.prop('hidden', !willOpen);
      $wrap.toggleClass('is-open', willOpen);
      $(this).attr('aria-expanded', willOpen ? 'true' : 'false');
    });

    $(document).on('click', '.ipb-ticket-more-menu', function (e) {
      if ($(e.target).closest('.customer-transfer-button, .delete-btn, a.ipb-ticket-more-item').length) {
        closeAllMoreMenus();
      }
    });

    // Inline status dropdown (custom menu — native <select> can't be styled once open)
    function closeAllStatusMenus(except) {
      $('.ipb-ticket-status-dd').each(function () {
        if (except && this === except) return;
        var $wrap = $(this);
        $wrap.find('.ipb-ticket-status-menu').prop('hidden', true);
        $wrap.find('.ipb-ticket-status-trigger').attr('aria-expanded', 'false');
      });
    }

    $(document).on('click', '.ipb-ticket-status-trigger', function (e) {
      e.preventDefault();
      e.stopPropagation();
      var $wrap = $(this).closest('.ipb-ticket-status-dd');
      var $menu = $wrap.find('.ipb-ticket-status-menu');
      var willOpen = $menu.prop('hidden');
      closeAllMoreMenus();
      closeAllStatusMenus(willOpen ? $wrap[0] : null);
      $menu.prop('hidden', !willOpen);
      $(this).attr('aria-expanded', willOpen ? 'true' : 'false');
    });

    $(document).on('click', '.ipb-ticket-status-option', function () {
      var $option = $(this);
      var $wrap = $option.closest('.ipb-ticket-status-dd');
      var ticketId = $wrap.data('ticket-id');
      var newStatus = $option.data('value');
      var toneMap = { opened: 'is-success', processing: 'is-warning', closed: 'is-neutral' };
      var labelMap = { opened: 'Opened', processing: 'Processing', closed: 'Closed' };
      var $trigger = $wrap.find('.ipb-ticket-status-trigger');

      closeAllStatusMenus();
      $trigger.prop('disabled', true);

      $.ajax({
        url: quickStatusUrlTpl.replace(/\/0(\/?$)/, '/' + ticketId + '$1'),
        type: 'POST',
        data: { status: newStatus },
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        beforeSend: function (req) {
          req.setRequestHeader(csrfHeader, csrfHash);
        },
        success: function (result) {
          $trigger.prop('disabled', false);
          $trigger.removeClass('is-success is-warning is-neutral').addClass(toneMap[newStatus] || 'is-neutral');
          $trigger.find('.ipb-ticket-status-trigger-label').text(labelMap[newStatus] || newStatus);
          tata.success('Status updated', (result && result.response) || 'Status updated');
          loadInbox(activeId);
        },
        error: function (response) {
          $trigger.prop('disabled', false);
          try {
            tata.error("Couldn't update status", jQuery.parseJSON(response.responseText).response);
          } catch (e) {
            tata.error("Couldn't update status", 'Could not update status');
          }
        }
      });
    });

    $(document).on('click', function (e) {
      if (!$(e.target).closest('.ipb-ticket-more').length) closeAllMoreMenus();
      if (!$(e.target).closest('.ipb-ticket-status-dd').length) closeAllStatusMenus();
    });

    $(document).on('keydown', function (e) {
      if (e.key === 'Escape') {
        closeAllMoreMenus();
        closeAllStatusMenus();
      }
    });

    $(document).on('click', '.ipb-ticket-back', function (e) {
      e.preventDefault();
      showEmptyPane();
      renderList($('#ipbTicketSearch').val());
      try {
        var url = new URL(window.location.href);
        url.searchParams.delete('id');
        history.replaceState(null, '', url.pathname + url.search + url.hash);
      } catch (err) {}
      if (isPhoneInbox()) {
        var list = document.querySelector('.ipb-ticket-inbox-list');
        if (list) list.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });

    // Keep mobile chat class in sync on rotate; open first ticket when leaving phone list-only.
    var resizeTimer = null;
    $(window).on('resize', function () {
      window.clearTimeout(resizeTimer);
      resizeTimer = window.setTimeout(function () {
        var phone = isPhoneInbox();
        $('body').toggleClass('ipb-ticket-mobile-chat', phone && !!activeId);
        $('#ipbTicketInbox').toggleClass('is-thread-open', !!activeId);
        if (!phone && !activeId && tickets.length) {
          loadTicket(tickets[0].id);
        }
      }, 150);
    });

    $('#ipbTicketSearch').on('input', function () {
      renderList($(this).val());
    });

    $(document).on('click', '.ipb-ticket-filter-tab', function () {
      statusFilter = $(this).data('filter');
      $('.ipb-ticket-filter-tab').removeClass('is-active').attr('aria-selected', 'false');
      $(this).addClass('is-active').attr('aria-selected', 'true');
      renderList($('#ipbTicketSearch').val());
    });

    // 06 §9 — "Inbox zero" action: jump to the Closed tab.
    $(document).on('click', '.ipb-ticket-goto-closed', function () {
      statusFilter = 'closed';
      $('.ipb-ticket-filter-tab').removeClass('is-active').attr('aria-selected', 'false');
      $('.ipb-ticket-filter-tab[data-filter="closed"]').addClass('is-active').attr('aria-selected', 'true');
      renderList($('#ipbTicketSearch').val());
    });

    // 06 §9 — "Nothing matches these filters" action: back to the All tab, empty search.
    $(document).on('click', '.ipb-ticket-clear-filters', function () {
      statusFilter = 'all';
      $('#ipbTicketSearch').val('');
      $('.ipb-ticket-filter-tab').removeClass('is-active').attr('aria-selected', 'false');
      $('.ipb-ticket-filter-tab[data-filter="all"]').addClass('is-active').attr('aria-selected', 'true');
      renderList('');
    });

    $(document).on('click', '.ipb-ticket-inbox-item', function (e) {
      if ($(e.target).is('input, label')) return;
      loadTicket(parseInt($(this).data('id'), 10));
    });

    $(document).on('keydown', '.ipb-ticket-inbox-item', function (e) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        loadTicket(parseInt($(this).data('id'), 10));
      }
    });

    $(document).on('click', '.ipb-ticket-inbox-check', function (e) {
      e.stopPropagation();
    });

    $(document).on('submit', '#ipbTicketReplyForm', function (event) {
      event.preventDefault();
      var form = this;
      var $btn = $(form).find('button[type="submit"]');
      var action = $(form).attr('action');

      $.ajax({
        url: action,
        type: 'POST',
        data: new FormData(form),
        contentType: false,
        cache: false,
        processData: false,
        beforeSend: function (req) {
          req.setRequestHeader(csrfHeader, csrfHash);
          $btn.html("<i class='fas fa-spinner fa-spin' aria-hidden='true'></i> Please wait");
          $btn.attr('disabled', 'true');
        },
        success: function (result) {
          $btn.html("<i class='fa fa-paper-plane' aria-hidden='true'></i> Send");
          $btn.removeAttr('disabled');
          $(form).trigger('reset');

          var msg = (result.response && result.response.msg) ? result.response.msg : '';
          var sender = (result.response && result.response.sender) ? result.response.sender : '';
          var datetime = (result.response && result.response.datetime) ? result.response.datetime : '';

          var $msgs = $('#ipbTicketPane .ipb-ticket-messages');
          $msgs.find('.ipb-ticket-empty').remove();
          $msgs.append(
            '<div class="ipb-ticket-msg is-mine">' +
              '<img class="ipb-ticket-avatar" src="' + esc(avatarUrl) + '" alt="">' +
              '<div class="ipb-ticket-bubble-wrap">' +
                '<div class="ipb-ticket-bubble"></div>' +
                '<div class="ipb-ticket-msg-meta"></div>' +
              '</div>' +
            '</div>'
          );
          var $last = $msgs.find('.ipb-ticket-msg').last();
          $last.find('.ipb-ticket-bubble').text(msg);
          $last.find('.ipb-ticket-msg-meta').text(sender + ' · ' + datetime);
          scrollMessages();
        },
        error: function (response) {
          $btn.html("<i class='fa fa-paper-plane' aria-hidden='true'></i> Send");
          $btn.removeAttr('disabled');
          try {
            var result = jQuery.parseJSON(response.responseText);
            tata.error("Couldn't send message", result.response);
          } catch (e) {
            tata.error("Couldn't send message", 'Could not send message');
          }
        }
      });
    });

    <?php if ($canDelete || $canTransfer): ?>
    $("#select_all").on('click', function () {
      $(".input-check-selected").prop('checked', this.checked);
    });

    $(document).on("click", ".input-check-selected:checkbox", function () {
      $("#select_all").prop(
        "checked",
        $(".input-check-selected:checkbox:checked").length === $(".input-check-selected:checkbox").length &&
          $(".input-check-selected:checkbox").length > 0
      );
    });
    <?php endif; ?>

    <?php if ($canDelete): ?>
    $(document).on('click', '.delete-btn', function () {
      var ids = $('.input-check-selected:checkbox:checked').map(function () {
        return $(this).val();
      }).get();

      if (!ids.length) {
        tata.error('Select a ticket', 'Select at least one ticket.');
        return;
      }

      swal({
        title: "Confirmation",
        text: "Are you sure you want to delete the selected records?",
        dangerMode: true,
        icon: 'warning',
        buttons: ["No", { text: "Yes", closeModal: false }],
      }).then(function (willDelete) {
        if (!willDelete) return;
        $.ajax({
          url: <?= json_encode(route_to('route.ticket.delete')); ?>,
          type: 'DELETE',
          data: { ids: ids },
          headers: { <?= json_encode(csrf_header()); ?>: <?= json_encode(csrf_hash()); ?> },
          success: function (result) {
            swal.close();
            tata.success('Tickets deleted', result.response);
            activeId = null;
            loadInbox();
          },
          error: function (response) {
            swal.close();
            try {
              tata.error("Couldn't delete tickets", jQuery.parseJSON(response.responseText).response);
            } catch (e) {
              tata.error("Couldn't delete tickets", 'Delete failed');
            }
          }
        });
      });
    });
    <?php endif; ?>

    <?php if ($canTransfer): ?>
    $(document).on('click', '.customer-transfer-button', function () {
      var ticketIds = $('.input-check-selected:checkbox:checked').map(function () {
        return $(this).val();
      }).get();
      if (!ticketIds.length && activeId) ticketIds = [String(activeId)];
      if (!ticketIds.length) {
        tata.error('Select a ticket', 'Open or select a ticket to transfer.');
        return;
      }

      <?php if (empty($resellers)): ?>
      tata.error('No employees available', 'No employees available to transfer to.');
      return;
      <?php endif; ?>

      var employeeDropdown = `<div class="employee-dropdown-container" style="max-height: 180px; overflow-y: auto; border: 1px solid var(--border); padding: 8px; width: 100%; border-radius: 8px; text-align: left;">
      <?php foreach ($resellers as $employee): ?>
          <label style="display: flex; align-items: center; gap: 8px; padding: 6px 4px; cursor: pointer;">
              <input type="checkbox" class="employee-checkbox" value="<?= (int) $employee->id ?>">
              <span><?= esc($employee->name); ?></span>
          </label>
      <?php endforeach; ?>
      </div>`;

      swal({
        title: "Transfer Ticket",
        text: ticketIds.length > 1
          ? ("Transfer " + ticketIds.length + " tickets to employee(s):")
          : "Select employee(s) to transfer this ticket:",
        content: { element: "div", attributes: { innerHTML: employeeDropdown } },
        buttons: {
          cancel: "Cancel",
          confirm: { text: "Transfer", closeModal: false },
        },
      }).then(function (confirmed) {
        if (!confirmed) return;
        var selectedEmployees = [];
        $('.employee-checkbox:checked').each(function () {
          selectedEmployees.push($(this).val());
        });
        if (!selectedEmployees.length) {
          swal("Error", "Please select at least one employee.", "error");
          return;
        }
        $.ajax({
          url: <?= json_encode(route_to('route.ticket.transfer')); ?>,
          type: 'POST',
          data: { tickets: ticketIds, employees: selectedEmployees },
          headers: { <?= json_encode(csrf_header()); ?>: <?= json_encode(csrf_hash()); ?> },
          success: function (result) {
            swal.close();
            tata.success('Ticket transferred', result.response);
            loadInbox(activeId);
          },
          error: function (response) {
            swal.close();
            try {
              tata.error("Couldn't transfer ticket", jQuery.parseJSON(response.responseText).response);
            } catch (e) {
              tata.error("Couldn't transfer ticket", 'Transfer failed');
            }
          }
        });
      });
    });
    <?php endif; ?>
  });
})(jQuery);
</script>

<?= $this->endSection('script'); ?>
