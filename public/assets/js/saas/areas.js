/**
 * Service Area — unified tree + detail workspace.
 * Follows the module pattern of customers-list.js / list-filters.js.
 */
(function (window, $) {
  "use strict";

  if (!$) return;

  var cfg = window.IpbAreasConfig || {};
  var canUpdate = !!cfg.canUpdate;
  var canDelete = !!cfg.canDelete;
  var canCreate = !!cfg.canCreate;
  var csrfName = cfg.csrfName || "";
  var csrfHash = cfg.csrfHash || "";
  var csrfHeader = cfg.csrfHeader || "";
  var urls = cfg.urls || {};

  var state = {
    areas: [],
    stats: {},
    selectedId: null,
    // cache: areaId -> { area, subAreas } — populated on first lazy-load so
    // re-collapsing/re-expanding never re-fetches.
    subCache: {},
    expanded: {},
    search: "",
    statusFilter: "",
  };

  function reduceMotion() {
    return !!(window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches);
  }

  // Untrusted server strings (area_name/area_code) — always routed through
  // this before ever touching innerHTML/.html(); jQuery .text() is used
  // wherever a plain text node will do, this is only for the html()-built rows.
  function escapeHtml(str) {
    return String(str == null ? "" : str).replace(/[&<>"']/g, function (ch) {
      return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[ch];
    });
  }

  function csrfData(d) {
    d = d || {};
    if (csrfName) d[csrfName] = csrfHash;
    return d;
  }
  function csrfBeforeSend(req) {
    if (csrfHeader) req.setRequestHeader(csrfHeader, csrfHash);
  }

  function buildUrl(tpl, id) {
    return String(tpl).replace("__ID__", encodeURIComponent(id));
  }

  /* ── Rendering helpers ────────────────────────────────────────────────── */

  function statusDot(status) {
    var active = status === "active";
    return (
      '<span class="ipb-status-dot' +
      (active ? " is-active" : "") +
      '" aria-hidden="true"></span><span class="sr-only">' +
      (active ? "Active" : "Inactive") +
      "</span>"
    );
  }

  function filteredAreas() {
    var q = state.search.trim().toLowerCase();
    var status = state.statusFilter;
    return state.areas.filter(function (a) {
      if (status && a.status !== status) return false;
      if (!q) return true;
      var name = String(a.area_name || "").toLowerCase();
      var code = String(a.area_code || "").toLowerCase();
      return name.indexOf(q) !== -1 || code.indexOf(q) !== -1;
    });
  }

  function renderTree() {
    var $scroll = $("#areasTreeScroll");
    var list = filteredAreas();

    if (!state.areas.length) {
      $scroll.html(
        '<div class="ipb-detail-panel-empty">' +
          $("#areasEmptyStateTpl").html() +
          "</div>"
      );
      return;
    }

    if (!list.length) {
      $scroll.html(
        '<div class="ipb-detail-panel-empty">' +
          '<div class="ipb-empty"><div class="ipb-empty-icon"><i class="fa fa-magnifying-glass" aria-hidden="true"></i></div>' +
          '<div class="ipb-empty-title">No matching areas</div>' +
          '<div class="ipb-empty-sub">Try a different name, code, or status filter.</div></div>' +
          "</div>"
      );
      return;
    }

    var $ul = $('<ul class="ipb-area-tree" role="list"></ul>');
    list.forEach(function (area) {
      $ul.append(renderAreaNode(area));
    });
    $scroll.empty().append($ul);

    // Re-apply expanded state for any nodes that were open before a re-render
    // (e.g. after a create/update refresh) — reads cache, no re-fetch.
    Object.keys(state.expanded).forEach(function (id) {
      if (state.expanded[id] && state.subCache[id]) {
        paintSubPanel(id, state.subCache[id].subAreas);
        setToggleExpanded(id, true);
      }
    });
  }

  function renderAreaNode(area) {
    var id = area.id;
    var subCount = Number(area.sub_area_count || 0);
    var isSelected = state.selectedId === id;

    var $li = $('<li class="ipb-area-node' + (isSelected ? " is-selected" : "") + '" data-area-id="' + id + '"></li>');

    var $row = $('<div class="ipb-area-row"></div>');

    var $toggle = $(
      '<button type="button" class="ipb-area-toggle' +
        (subCount === 0 ? " is-spacer" : "") +
        '" aria-expanded="false" aria-controls="sub-panel-' +
        id +
        '" title="Expand sub-areas" aria-label="Expand sub-areas">' +
        '<i class="fa fa-chevron-right" aria-hidden="true"></i></button>'
    );
    if (subCount === 0) {
      $toggle.prop("disabled", true).removeAttr("aria-controls");
    }
    $row.append($toggle);

    var $selectBtn = $('<button type="button" class="ipb-area-select-btn"></button>');
    $selectBtn.append(statusDot(area.status));
    $selectBtn.append($('<span class="ipb-area-name"></span>').text(area.area_name));
    $selectBtn.append($('<span class="ipb-area-code-badge"></span>').text(area.area_code));
    $selectBtn.append(
      $('<span class="ipb-sub-count-badge"></span>').text(
        subCount === 1 ? "1 sub-area" : subCount + " sub-areas"
      )
    );
    $row.append($selectBtn);
    $li.append($row);

    var $panel = $(
      '<div class="ipb-subarea-panel" id="sub-panel-' + id + '" aria-hidden="true"></div>'
    );
    $li.append($panel);

    return $li;
  }

  function setToggleExpanded(areaId, expanded) {
    var $node = $('.ipb-area-node[data-area-id="' + areaId + '"]');
    var label = expanded ? "Collapse sub-areas" : "Expand sub-areas";
    $node
      .find("> .ipb-area-row .ipb-area-toggle")
      .attr("aria-expanded", expanded ? "true" : "false")
      .attr("title", label)
      .attr("aria-label", label);
    $node.find("> .ipb-subarea-panel").attr("aria-hidden", expanded ? "false" : "true");
  }

  function paintSubPanel(areaId, subAreas) {
    var $panel = $("#sub-panel-" + areaId);
    if (!$panel.length) return;
    if (!subAreas.length) {
      $panel.html('<div class="ipb-subarea-panel-empty">No sub-areas yet.</div>');
      return;
    }
    var $ul = $("<ul></ul>");
    subAreas.forEach(function (sub) {
      var $li = $('<li></li>');
      var $row = $(
        '<div class="ipb-subarea-mini-row" data-subarea-id="' +
          sub.id +
          '" role="button" tabindex="0" aria-label="View ' +
          escapeHtml(sub.area_name) +
          '"></div>'
      );
      $row.append(statusDot(sub.status));
      $row.append($('<span class="ipb-area-name"></span>').text(sub.area_name));
      $row.append($('<span class="ipb-area-code-badge"></span>').text(sub.area_code));
      $li.append($row);
      $ul.append($li);
    });
    $panel.empty().append($ul);
  }

  function paintSubPanelLoading(areaId) {
    var $panel = $("#sub-panel-" + areaId);
    if (!$panel.length) return;
    $panel.html(
      '<div class="ipb-subarea-panel-loading"><span class="ipb-skeleton ipb-skeleton-text" style="width:70%"></span>' +
        '<span class="ipb-skeleton ipb-skeleton-text" style="width:55%"></span></div>'
    );
  }

  /* ── Lazy load sub-areas (shared by chevron toggle + row select) ───────── */

  function loadSubAreas(areaId, cb) {
    if (state.subCache[areaId]) {
      cb(state.subCache[areaId]);
      return;
    }
    $.ajax({
      url: buildUrl(urls.subtree, areaId),
      type: "POST",
      data: csrfData({}),
      beforeSend: csrfBeforeSend,
      success: function (result) {
        state.subCache[areaId] = result;
        cb(result);
      },
      error: function () {
        if (window.tata && tata.error) tata.error("Couldn't load", "Failed to load sub-areas");
        cb(null);
      },
    });
  }

  function toggleExpand(areaId) {
    var willExpand = !state.expanded[areaId];
    state.expanded[areaId] = willExpand;
    setToggleExpanded(areaId, willExpand);
    if (!willExpand) return;

    if (state.subCache[areaId]) {
      paintSubPanel(areaId, state.subCache[areaId].subAreas);
      return;
    }
    paintSubPanelLoading(areaId);
    loadSubAreas(areaId, function (result) {
      if (!result) return;
      paintSubPanel(areaId, result.subAreas);
    });
  }

  /* ── Detail panel ─────────────────────────────────────────────────────── */

  function renderDetailLoading() {
    $("#areasDetailPanel").html(
      '<div class="ipb-detail-skeleton" aria-busy="true">' +
        '<div class="ipb-detail-info-card">' +
        '<div style="flex:1"><span class="ipb-skeleton ipb-skeleton-text is-lg" style="width:40%"></span>' +
        '<span class="ipb-skeleton ipb-skeleton-text" style="width:25%"></span></div>' +
        "</div>" +
        '<span class="ipb-skeleton ipb-skeleton-text" style="width:30%"></span>' +
        '<span class="ipb-skeleton ipb-skeleton-text" style="width:90%"></span>' +
        '<span class="ipb-skeleton ipb-skeleton-text" style="width:80%"></span>' +
        "</div>"
    );
  }

  function renderDetailPlaceholder() {
    $("#areasDetailPanel").html(
      '<div class="ipb-detail-placeholder"><i class="fa fa-diagram-project" aria-hidden="true"></i>' +
        "<p>Select an area from the list to view its details and sub-areas.</p></div>"
    );
  }

  function rowActionsHtml(kind, item) {
    var html = '<div class="ipb-row-actions">';
    if (canUpdate) {
      html +=
        '<button type="button" class="ipb-row-btn tone-brand js-edit-' +
        kind +
        '" data-id="' +
        item.id +
        '" title="Edit ' +
        kind +
        '" aria-label="Edit ' +
        escapeHtml(item.area_name) +
        '">' +
        '<i class="far fa-pen-to-square" aria-hidden="true"></i><span class="sr-only">Edit</span></button>';
    }
    if (canDelete) {
      html +=
        '<button type="button" class="ipb-row-btn tone-danger js-delete-' +
        kind +
        '" data-id="' +
        item.id +
        '" title="Delete ' +
        kind +
        '" aria-label="Delete ' +
        escapeHtml(item.area_name) +
        '">' +
        '<i class="far fa-trash-can" aria-hidden="true"></i><span class="sr-only">Delete</span></button>';
    }
    html += "</div>";
    return html;
  }

  function renderDetail(area, subAreas) {
    var $panel = $("#areasDetailPanel");
    var $wrap = $('<div></div>');

    var $info = $('<div class="ipb-detail-info-card"></div>');
    var $main = $('<div class="ipb-detail-info-main"></div>');
    var $name = $('<h1 class="ipb-detail-name"></h1>').text(area.area_name);
    $name.append('<span class="ipb-area-code-badge">' + escapeHtml(area.area_code) + "</span>");
    $main.append($name);
    var $meta = $('<div class="ipb-detail-meta"></div>');
    $meta.append(
      '<span class="status ' +
        (area.status === "active" ? "active" : "inactive") +
        '">' +
        (area.status === "active" ? "Active" : "Inactive") +
        "</span>"
    );
    var customerCount = Number(area.customer_count || 0);
    $meta.append(
      $('<span class="ipb-sub-count-badge ipb-customer-count-badge"></span>').text(
        customerCount === 1 ? "1 customer" : customerCount + " customers"
      )
    );
    $main.append($meta);
    $info.append($main);
    $info.append($('<div class="ipb-detail-actions"></div>').html(rowActionsHtml("area", area)));
    $wrap.append($info);

    var $head = $('<div class="ipb-subarea-section-head"></div>');
    $head.append($("<h2></h2>").text("Sub-areas"));
    if (canCreate && subAreas.length) {
      $head.append(
        '<button type="button" class="btn btn-primary btn-sm js-new-subarea" data-area-id="' +
          area.id +
          '"><i class="fa fa-plus" aria-hidden="true"></i> New Sub-area</button>'
      );
    }
    $wrap.append($head);

    if (!subAreas.length) {
      var $empty = $('<div class="ipb-detail-panel-empty"></div>');
      var actionHtml = canCreate
        ? '<button type="button" class="btn btn-primary btn-sm js-new-subarea" data-area-id="' +
          area.id +
          '"><i class="fa fa-plus" aria-hidden="true"></i> New Sub-area</button>'
        : "";
      $empty.html(
        '<div class="ipb-empty"><div class="ipb-empty-icon"><i class="fa fa-sitemap" aria-hidden="true"></i></div>' +
          '<div class="ipb-empty-title">No sub-areas yet</div>' +
          '<div class="ipb-empty-sub">Add the first sub-area under ' +
          escapeHtml(area.area_name) +
          ".</div>" +
          '<div class="ipb-empty-action">' +
          actionHtml +
          "</div></div>"
      );
      $wrap.append($empty);
    } else {
      var $list = $('<div class="ipb-subarea-list"></div>');
      subAreas.forEach(function (sub) {
        var $row = $('<div class="ipb-subarea-list-row"></div>');
        $row.append($('<span class="ipb-area-name"></span>').text(sub.area_name));
        $row.append('<span class="ipb-area-code-badge">' + escapeHtml(sub.area_code) + "</span>");
        $row.append(
          '<span class="status ' +
            (sub.status === "active" ? "active" : "inactive") +
            '">' +
            (sub.status === "active" ? "Active" : "Inactive") +
            "</span>"
        );
        $row.append(rowActionsHtml("subarea", sub));
        $list.append($row);
      });
      $wrap.append($list);
    }

    $panel.empty().append($wrap);
  }

  function selectArea(areaId, opts) {
    opts = opts || {};
    state.selectedId = areaId;
    $(".ipb-area-node").removeClass("is-selected");
    $('.ipb-area-node[data-area-id="' + areaId + '"]').addClass("is-selected");

    renderDetailLoading();

    // Also expand the tree node so the sidebar reflects the selection.
    if (!state.expanded[areaId]) {
      state.expanded[areaId] = true;
      setToggleExpanded(areaId, true);
      if (state.subCache[areaId]) {
        paintSubPanel(areaId, state.subCache[areaId].subAreas);
      } else {
        paintSubPanelLoading(areaId);
      }
    }

    loadSubAreas(areaId, function (result) {
      if (!result) {
        renderDetailPlaceholder();
        return;
      }
      paintSubPanel(areaId, result.subAreas);
      if (state.selectedId === areaId) {
        renderDetail(result.area, result.subAreas);
      }
    });

    if (opts.closeDrawerOnMobile) closeTreeDrawer();
  }

  /* ── Toolbar: search + status filter ────────────────────────────────── */

  function bindToolbar() {
    $("#filter-area-search").on("input", function () {
      state.search = $(this).val() || "";
      renderTree();
    });
    $("#filter-area-status").on("change", function () {
      state.statusFilter = $(this).val() || "";
      renderTree();
    });
  }

  /* ── Off-canvas tree drawer (tablet/phone) ──────────────────────────── */

  function openTreeDrawer() {
    $("#areasTreePanel").addClass("is-open");
    $("#areasTreeDrawerTrigger").attr("aria-expanded", "true");
    $("#areasTreeBackdrop").addClass("show");
    document.body.classList.add("ipb-drawer-open");
    setTimeout(
      function () {
        var focusEl = document.querySelector("#areasTreePanel button, #areasTreePanel input");
        if (focusEl && focusEl.focus) focusEl.focus();
      },
      reduceMotion() ? 0 : 200
    );
  }
  function closeTreeDrawer() {
    if (window.innerWidth > 1024) return;
    if (!$("#areasTreePanel").hasClass("is-open")) return;
    $("#areasTreePanel").removeClass("is-open");
    $("#areasTreeDrawerTrigger").attr("aria-expanded", "false").trigger("focus");
    $("#areasTreeBackdrop").removeClass("show");
    document.body.classList.remove("ipb-drawer-open");
  }

  function bindTreeDrawer() {
    $("#areasTreeDrawerTrigger").on("click", function () {
      var open = $("#areasTreePanel").hasClass("is-open");
      if (open) closeTreeDrawer();
      else openTreeDrawer();
    });
    $("#areasTreeCloseBtn").on("click", closeTreeDrawer);
    $("#areasTreeBackdrop").on("click", closeTreeDrawer);
    $(document).on("keydown", function (e) {
      if (e.key === "Escape") closeTreeDrawer();
    });
  }

  /* ── Tree click delegation ───────────────────────────────────────────── */

  function bindTreeEvents() {
    $("#areasTreeScroll")
      .on("click", ".ipb-area-toggle:not(:disabled)", function (e) {
        e.stopPropagation();
        var areaId = $(this).closest(".ipb-area-node").data("area-id");
        toggleExpand(areaId);
      })
      .on("click", ".ipb-area-select-btn", function () {
        var areaId = $(this).closest(".ipb-area-node").data("area-id");
        selectArea(areaId, { closeDrawerOnMobile: true });
      })
      .on("click", ".ipb-subarea-mini-row", function () {
        var areaId = $(this).closest(".ipb-area-node").data("area-id");
        selectArea(areaId, { closeDrawerOnMobile: true });
      })
      .on("keydown", ".ipb-subarea-mini-row", function (e) {
        if (e.key !== "Enter" && e.key !== " " && e.key !== "Spacebar") return;
        e.preventDefault();
        var areaId = $(this).closest(".ipb-area-node").data("area-id");
        selectArea(areaId, { closeDrawerOnMobile: true });
      });
  }

  /* ── Refresh tree from server (after create/update/delete) ─────────── */

  function refreshTree(cb) {
    $.ajax({
      url: urls.tree,
      type: "POST",
      data: csrfData({}),
      beforeSend: csrfBeforeSend,
      success: function (result) {
        state.areas = result.areas || [];
        state.stats = result.stats || {};
        renderStats();
        renderTree();
        renderTreeCountBadge();
        if (typeof cb === "function") cb();
      },
    });
  }

  function refreshSelectedDetail() {
    var id = state.selectedId;
    if (!id) return;
    delete state.subCache[id];
    loadSubAreas(id, function (result) {
      if (!result) return;
      paintSubPanel(id, result.subAreas);
      if (state.selectedId === id) renderDetail(result.area, result.subAreas);
    });
  }

  function setStatCard(wrapperId, value, warnWhenPositive) {
    var $wrapper = $("#" + wrapperId);
    $wrapper.find(".ipb-stat-value").text(value);
    if (warnWhenPositive) {
      $wrapper.find(".ipb-stat-card").toggleClass("tone-warning", value > 0);
    }
  }

  function renderStats() {
    var s = state.stats;
    setStatCard("statCardTotalAreas", s.total_areas || 0);
    setStatCard("statCardActiveAreas", s.active_areas || 0);
    setStatCard("statCardTotalSubareas", s.total_sub_areas || 0);
    setStatCard("statCardAreasWithoutSubareas", s.areas_without_subareas || 0, true);
  }

  function renderTreeCountBadge() {
    $("#areasTreeDrawerCount").text(state.areas.length);
  }

  /* ── Drawer (create/edit forms) ──────────────────────────────────────── */

  function openFormDrawer(panelId) {
    var panel = document.getElementById(panelId);
    if (!panel) return;

    // Route through customize.js's own openDrawer() so it captures
    // document.activeElement into its drawerReturnFocus — the same
    // mechanism the shared X-close button and global Escape handler
    // (both wired to IpbCustomize.closeDrawers()) use to restore focus
    // to the row's Edit/New trigger on close.
    if (window.IpbCustomize && typeof window.IpbCustomize.openDrawer === "function") {
      window.IpbCustomize.openDrawer(panel);
      return;
    }

    var overlay = document.getElementById("ipbDrawerOverlay");
    if (!overlay) return;
    document.querySelectorAll(".ipb-drawer-panel.open").forEach(function (p) {
      if (p !== panel) {
        p.classList.remove("open");
        p.setAttribute("aria-hidden", "true");
      }
    });
    overlay.classList.add("show");
    overlay.setAttribute("aria-hidden", "false");
    panel.classList.add("open");
    panel.setAttribute("aria-hidden", "false");
    document.body.classList.add("ipb-drawer-open");
    setTimeout(function () {
      var focusEl = panel.querySelector("input, select, textarea, button");
      if (focusEl && focusEl.focus) focusEl.focus();
    }, reduceMotion() ? 0 : 200);
  }

  function closeFormDrawers() {
    if (window.IpbCustomize && typeof window.IpbCustomize.closeDrawers === "function") {
      window.IpbCustomize.closeDrawers();
    } else {
      document.querySelectorAll(".ipb-drawer-panel.open").forEach(function (p) {
        p.classList.remove("open");
        p.setAttribute("aria-hidden", "true");
      });
      var overlay = document.getElementById("ipbDrawerOverlay");
      if (overlay) {
        overlay.classList.remove("show");
        overlay.setAttribute("aria-hidden", "true");
      }
      document.body.classList.remove("ipb-drawer-open");
    }
  }

  function resetForm($form) {
    $form.trigger("reset");
    $form.find(".error").text("");
  }

  function submitForm($form, url, onSuccess) {
    var formEl = $form[0];
    var fd = new FormData(formEl);
    var extraId = $form.data("extra-id");
    if (extraId != null) fd.append("id", extraId);
    fd.append(csrfName, csrfHash);

    var $btn = $form.find('button[type="submit"]');
    var originalLabel = $btn.html();

    $.ajax({
      url: url,
      type: "POST",
      data: fd,
      contentType: false,
      cache: false,
      processData: false,
      beforeSend: function (req) {
        $form.find(".error").text("");
        $btn.html('<i class="fas fa-spinner fa-spin"></i> Please wait').prop("disabled", true);
        csrfBeforeSend(req);
      },
      success: function (result) {
        $btn.html(originalLabel).prop("disabled", false);
        resetForm($form);
        closeFormDrawers();
        if (window.tata && tata.success) tata.success("Success", result.response);
        if (typeof onSuccess === "function") onSuccess();
      },
      error: function (xhr) {
        $btn.html(originalLabel).prop("disabled", false);
        var result = {};
        try {
          result = JSON.parse(xhr.responseText);
        } catch (e) {}
        if (result.status === "validation-error") {
          $.each(result.response || {}, function (prefix, val) {
            $form.find("#" + prefix + "-error").text(val);
          });
        } else if (window.tata && tata.error) {
          tata.error("Couldn't save", result.response || "Something went wrong");
        }
      },
    });
  }

  function bindDrawers() {
    // New Area
    $("#btnNewArea").on("click", function () {
      var $form = $("#formAreaCreate");
      resetForm($form);
      openFormDrawer("drawerAreaCreate");
    });
    $("#formAreaCreate").on("submit", function (e) {
      e.preventDefault();
      submitForm($(this), urls.areaCreate, function () {
        refreshTree();
      });
    });

    // Edit Area (delegated — rows are re-rendered)
    $("#areasDetailPanel").on("click", ".js-edit-area", function () {
      var id = $(this).data("id");
      var area = state.areas.filter(function (a) {
        return String(a.id) === String(id);
      })[0];
      if (!area) return;
      var $form = $("#formAreaEdit");
      $form.data("extra-id", null);
      $form.attr("action", buildUrl(urls.areaUpdate, id));
      $form.find('[name="area_name"]').val(area.area_name);
      $form.find('[name="area_code"]').val(area.area_code);
      $form.find('[name="status"][value="' + area.status + '"]').prop("checked", true);
      $form.find(".error").text("");
      openFormDrawer("drawerAreaEdit");
    });
    $("#formAreaEdit").on("submit", function (e) {
      e.preventDefault();
      var $form = $(this);
      submitForm($form, $form.attr("action"), function () {
        refreshTree();
      });
    });

    // New Sub-area (from detail panel button)
    $("#areasDetailPanel").on("click", ".js-new-subarea", function () {
      var areaId = $(this).data("area-id");
      var $form = $("#formSubareaCreate");
      resetForm($form);
      $form.data("extra-id", areaId);
      openFormDrawer("drawerSubareaCreate");
    });
    $("#formSubareaCreate").on("submit", function (e) {
      e.preventDefault();
      var $form = $(this);
      submitForm($form, urls.subareaCreate, function () {
        refreshTree(function () {
          refreshSelectedDetail();
        });
      });
    });

    // Edit Sub-area
    $("#areasDetailPanel").on("click", ".js-edit-subarea", function () {
      var id = $(this).data("id");
      var cached = state.subCache[state.selectedId];
      var sub = cached
        ? cached.subAreas.filter(function (s) {
            return String(s.id) === String(id);
          })[0]
        : null;
      if (!sub) return;
      var $form = $("#formSubareaEdit");
      $form.attr("action", buildUrl(urls.subareaUpdate, id));
      $form.find('[name="area_name"]').val(sub.area_name);
      $form.find('[name="area_code"]').val(sub.area_code);
      $form.find('[name="status"][value="' + sub.status + '"]').prop("checked", true);
      $form.find(".error").text("");
      openFormDrawer("drawerSubareaEdit");
    });
    $("#formSubareaEdit").on("submit", function (e) {
      e.preventDefault();
      var $form = $(this);
      submitForm($form, $form.attr("action"), function () {
        refreshTree(function () {
          refreshSelectedDetail();
        });
      });
    });
  }

  /* ── Delete (swal confirm + DELETE ajax, same idiom as the old views) ─── */

  function bindDeletes() {
    $("#areasDetailPanel").on("click", ".js-delete-area", function () {
      var id = $(this).data("id");
      window.swal({
        title: "Confirmation",
        text: "Are you sure you want to delete this area? Its sub-areas will remain orphaned.",
        dangerMode: true,
        icon: "warning",
        buttons: ["No", { text: "Yes", closeModal: false }],
      }).then(function (willDelete) {
        if (!willDelete) return;
        $.ajax({
          url: urls.areaDelete,
          type: "DELETE",
          data: { ids: [id] },
          headers: (function () {
            var h = {};
            h[csrfHeader] = csrfHash;
            return h;
          })(),
          success: function (result) {
            window.swal.close();
            if (window.tata && tata.success) tata.success("Area deleted", result.response);
            var wasSelected = state.selectedId === id;
            if (wasSelected) {
              state.selectedId = null;
              renderDetailPlaceholder();
            }
            refreshTree();
          },
          error: function (response) {
            var result = {};
            try {
              result = JSON.parse(response.responseText);
            } catch (e) {}
            window.swal.close();
            if (window.tata && tata.error) tata.error("Couldn't delete area", result.response);
          },
        });
      });
    });

    $("#areasDetailPanel").on("click", ".js-delete-subarea", function () {
      var id = $(this).data("id");
      window.swal({
        title: "Confirmation",
        text: "Are you sure you want to delete this sub-area?",
        dangerMode: true,
        icon: "warning",
        buttons: ["No", { text: "Yes", closeModal: false }],
      }).then(function (willDelete) {
        if (!willDelete) return;
        $.ajax({
          url: urls.subareaDelete,
          type: "DELETE",
          data: { ids: [id] },
          headers: (function () {
            var h = {};
            h[csrfHeader] = csrfHash;
            return h;
          })(),
          success: function (result) {
            window.swal.close();
            if (window.tata && tata.success) tata.success("Sub-area deleted", result.response);
            refreshTree(function () {
              refreshSelectedDetail();
            });
          },
          error: function (response) {
            var result = {};
            try {
              result = JSON.parse(response.responseText);
            } catch (e) {}
            window.swal.close();
            if (window.tata && tata.error) tata.error("Couldn't delete sub-area", result.response);
          },
        });
      });
    });
  }

  /* ── Boot ────────────────────────────────────────────────────────────── */

  function init() {
    // Re-read window.IpbAreasConfig here rather than at module-parse time:
    // areas.js is loaded via a blocking <script src> that executes before the
    // later inline <script> which defines window.IpbAreasConfig, so capturing
    // cfg at the top of this file always sees {}. $(init) fires on DOM-ready,
    // which is guaranteed to run after that inline script, so it's safe here.
    cfg = window.IpbAreasConfig || {};
    canUpdate = !!cfg.canUpdate;
    canDelete = !!cfg.canDelete;
    canCreate = !!cfg.canCreate;
    csrfName = cfg.csrfName || "";
    csrfHash = cfg.csrfHash || "";
    csrfHeader = cfg.csrfHeader || "";
    urls = cfg.urls || {};

    state.areas = cfg.areas || [];
    state.stats = cfg.stats || {};

    bindToolbar();
    bindTreeDrawer();
    bindTreeEvents();
    bindDrawers();
    bindDeletes();

    renderTree();
    renderTreeCountBadge();

    var preselect = cfg.preselectAreaId;
    if (preselect) {
      selectArea(preselect);
    } else {
      renderDetailPlaceholder();
    }
  }

  $(init);
})(window, jQuery);
