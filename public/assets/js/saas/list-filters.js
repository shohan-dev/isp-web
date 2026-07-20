/**
 * ISP Pay BD — shared list filter helper (IpbFilters)
 * Persistence (sessionStorage), reset, and result-count badge for DataTables.
 */
(function (window, $) {
  "use strict";

  if (!$) return;

  var SELECTOR = ".ipb-filter-select, .ipb-filter-date, .ipb-filter-text";
  var RESET_SELECTOR = ".ipb-filter-reset";
  var COUNT_SELECTOR = ".ipb-filter-count";

  function resolveRoot(root) {
    if (!root) return $(document);
    return typeof root === "string" ? $(root) : $(root);
  }

  function filterEls($root, filterSelector) {
    var sel = filterSelector || SELECTOR;
    return $root.find(sel).add($root.filter(sel));
  }

  function readStorage(storageKey) {
    if (!storageKey || !window.sessionStorage) return null;
    try {
      var raw = sessionStorage.getItem(storageKey);
      return raw ? JSON.parse(raw) : null;
    } catch (e) {
      return null;
    }
  }

  function writeStorage(storageKey, data) {
    if (!storageKey || !window.sessionStorage) return;
    try {
      if (!data || !Object.keys(data).length) {
        sessionStorage.removeItem(storageKey);
      } else {
        sessionStorage.setItem(storageKey, JSON.stringify(data));
      }
    } catch (e) {
      /* quota / private mode */
    }
  }

  function collectValues($root, filterSelector) {
    var values = {};
    filterEls($root, filterSelector).each(function () {
      var el = this;
      var key = el.name || el.id;
      if (!key) return;
      values[key] = $(el).val() || "";
    });
    return values;
  }

  function applyValues($root, values, filterSelector) {
    if (!values) return;
    filterEls($root, filterSelector).each(function () {
      var el = this;
      var key = el.name || el.id;
      if (!key || !Object.prototype.hasOwnProperty.call(values, key)) return;
      $(el).val(values[key] || "");
    });
  }

  function hasActiveFilter($root, filterSelector) {
    var active = false;
    filterEls($root, filterSelector).each(function () {
      if (($(this).val() || "") !== "") {
        active = true;
        return false;
      }
    });
    return active;
  }

  function defaultUpdateBadge(api, $root, $badge) {
    if (!$badge || !$badge.length) return;
    try {
      var json = api.ajax && api.ajax.json ? api.ajax.json() : null;
      var total = json && json.recordsTotal != null ? json.recordsTotal : 0;
      var filtered = json && json.recordsFiltered != null ? json.recordsFiltered : "?";
      $badge.text(filtered + " of " + total + " shown").show();
    } catch (e) {
      $badge.hide();
    }
  }

  function toggleReset($root, filterSelector, $reset) {
    if (!$reset || !$reset.length) return;
    $reset.toggle(hasActiveFilter($root, filterSelector));
  }

  /**
   * Rehydrate filter controls from sessionStorage — call BEFORE DataTable init.
   */
  function restore(options) {
    options = options || {};
    var storageKey = options.storageKey;
    var $root = resolveRoot(options.root);
    var filterSelector = options.filterSelector;
    var stored = readStorage(storageKey);
    if (stored) applyValues($root, stored, filterSelector);
    return stored;
  }

  /**
   * Bind filter UI to a DataTable API instance.
   * Returns { updateBadge, reset, persist }.
   */
  function bind(tableApi, options) {
    options = options || {};
    var storageKey = options.storageKey;
    var $root = resolveRoot(options.root);
    var filterSelector = options.filterSelector || SELECTOR;
    var $reset = options.resetBtn ? $(options.resetBtn) : $root.find(RESET_SELECTOR).first();
    var $badge = options.countBadge ? $(options.countBadge) : $root.find(COUNT_SELECTOR).first();
    var onUpdateBadge = typeof options.onUpdateBadge === "function" ? options.onUpdateBadge : null;
    var tableSelector = options.tableSelector || ".datatable";

    function getApi() {
      if (tableApi && tableApi.draw) return tableApi;
      if (tableApi && $(tableApi).DataTable) {
        try {
          return $(tableApi).DataTable();
        } catch (e) {
          return null;
        }
      }
      try {
        return $(tableSelector).DataTable();
      } catch (e) {
        return null;
      }
    }

    function persist() {
      writeStorage(storageKey, collectValues($root, filterSelector));
    }

    function updateBadge() {
      var api = getApi();
      if (!api) return;
      if (!hasActiveFilter($root, filterSelector)) {
        if ($badge && $badge.length) $badge.hide();
        toggleReset($root, filterSelector, $reset);
        return;
      }
      toggleReset($root, filterSelector, $reset);
      if (onUpdateBadge) {
        onUpdateBadge(api, $root, $badge);
      } else {
        defaultUpdateBadge(api, $root, $badge);
      }
    }

    function applyFilters() {
      persist();
      var api = getApi();
      if (api) api.draw();
      else updateBadge();
    }

    function resetFilters() {
      filterEls($root, filterSelector).each(function () {
        $(this).val("");
      });
      $root.find(".ipb-date-range-clear").each(function () {
        /* no-op; dates cleared above */
      });
      writeStorage(storageKey, null);
      toggleReset($root, filterSelector, $reset);
      if ($badge && $badge.length) $badge.hide();
      var api = getApi();
      if (api) {
        setResetBusy(true);
        api.draw();
      } else {
        setResetBusy(false);
      }
    }

    var api = getApi();
    var $tableNode = api ? $(api.table().node()) : $();
    var $wrap = $tableNode.closest(".dataTables_wrapper");
    if (!$wrap.length) $wrap = $tableNode.closest(".table-responsive, .box-body, .box");

    // ── loading feedback: 150ms delay before showing, 400ms minimum once shown (04 §5) ──
    // skeletonOnly: customer lists — skeleton / existing rows only; never the centred spinner.
    var LOAD_DELAY = 150, LOAD_MIN = 400;
    var showTimer = null, hideTimer = null, shownAt = 0;
    var skeletonOnly = !!(opts && opts.skeletonOnly);

    function injectSkeletonIfEmpty() {
      // Only for a first/empty load — if real rows are present we just dim them.
      var $tbody = $tableNode.children("tbody");
      if (!$tbody.length) return;
      var empty = $tbody.find("tr").length === 0 || $tbody.find("td.dataTables_empty").length > 0;
      if (!empty) return;
      // Prefer keeping server-rendered skeleton rows if already present.
      if ($tbody.find("tr.ipb-skeleton-row, .ipb-skeleton").length) return;
      var cols = $tableNode.find("thead th").length || 5;
      var html = "";
      for (var r = 0; r < 8; r++) {
        var tds = "";
        for (var c = 0; c < cols; c++) {
          tds += '<td><span class="ipb-skeleton ipb-skeleton-text" style="width:' + (50 + ((r + c) * 13) % 40) + '%"></span></td>';
        }
        html += '<tr class="ipb-skeleton-row" aria-hidden="true">' + tds + "</tr>";
      }
      $tbody.html(html);
    }
    function reallyShow() {
      showTimer = null;
      shownAt = Date.now();
      injectSkeletonIfEmpty();
      // Never add .is-loading when skeletonOnly — that class paints the ::after spinner.
      $wrap.attr("aria-busy", "true");
      if (!skeletonOnly) $wrap.addClass("is-loading");
    }
    function reallyHide() {
      hideTimer = null;
      $wrap.removeClass("is-loading").attr("aria-busy", "false");
    }
    function setLoading(on) {
      if (skeletonOnly) {
        // No delay/min spinner chrome — keep skeleton or current rows as the only cue.
        if (on) {
          injectSkeletonIfEmpty();
          $wrap.attr("aria-busy", "true");
        } else {
          $wrap.attr("aria-busy", "false");
        }
        return;
      }
      if (on) {
        if (showTimer || $wrap.hasClass("is-loading")) return;
        if (hideTimer) { clearTimeout(hideTimer); hideTimer = null; }
        showTimer = setTimeout(reallyShow, LOAD_DELAY);
      } else {
        // response landed INSIDE the 150ms window -> the loader never showed, so just cancel
        if (showTimer) { clearTimeout(showTimer); showTimer = null; return; }
        if (!$wrap.hasClass("is-loading")) return;
        // it did show -> hold it for the remainder of the 400ms minimum, then drop
        var wait = Math.max(0, LOAD_MIN - (Date.now() - shownAt));
        clearTimeout(hideTimer);
        hideTimer = setTimeout(reallyHide, wait);
      }
    }

    // spinner on the Reset button, only while a reset-triggered reload is in flight
    function setResetBusy(busy) {
      if (!$reset || !$reset.length) return;
      if (busy) {
        if ($reset.data("ipbBusy")) return;
        $reset.data("ipbBusy", 1).data("ipbLabel", $reset.html())
          .prop("disabled", true).attr("aria-busy", "true")
          .html('<span class="ipb-spinner ipb-spinner--sm" aria-hidden="true"></span>');
      } else {
        if (!$reset.data("ipbBusy")) return;
        var lbl = $reset.data("ipbLabel");
        $reset.prop("disabled", false).removeAttr("aria-busy").removeData("ipbBusy");
        if (lbl != null) $reset.html(lbl);
      }
    }

    // animate the count-badge number instead of snapping it
    function tweenBadge() {
      if (!$badge || !$badge.length) return;
      var txt = $badge.text();
      var m = txt.match(/^([\d,]+)/);
      if (!m) return;
      var target = parseInt(m[1].replace(/,/g, ""), 10);
      var prev = $badge.data("ipbNum");
      if (prev == null || prev === target ||
          (window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches)) {
        $badge.data("ipbNum", target);
        return;
      }
      var t0 = null, dur = 380;
      if ($badge.data("ipbRaf")) cancelAnimationFrame($badge.data("ipbRaf"));
      function step(ts) {
        if (t0 === null) t0 = ts;
        var p = Math.min(1, (ts - t0) / dur);
        var eased = 0.5 - Math.cos(Math.PI * p) / 2;               // easeInOutSine
        var val = Math.round(prev + (target - prev) * eased);
        $badge.text(txt.replace(/^[\d,]+/, val.toLocaleString()));
        if (p < 1) $badge.data("ipbRaf", requestAnimationFrame(step));
        else $badge.data("ipbNum", target);
      }
      $badge.data("ipbRaf", requestAnimationFrame(step));
    }

    if (api) {
      // DataTables namespaces its events '.dt'. Binding '<evt>.dt.ipbFilters' fires on
      // the '.dt' trigger AND lets us .off() only our own handlers.
      $tableNode
        .off("preXhr.dt.ipbFilters xhr.dt.ipbFilters error.dt.ipbFilters draw.dt.ipbFilters")
        .on("preXhr.dt.ipbFilters", function () { setLoading(true); })
        .on("xhr.dt.ipbFilters",    function () { setLoading(false); setResetBusy(false); })
        .on("error.dt.ipbFilters",  function () { setLoading(false); setResetBusy(false); })
        .on("draw.dt.ipbFilters",   function () { updateBadge(); tweenBadge(); setResetBusy(false); });
    }

    // selects + dates fire immediately; free text debounces at 300ms (04 §5)
    var textDebounce = null;
    filterEls($root, filterSelector).off("change.ipbFilters input.ipbFilters");
    filterEls($root, filterSelector).filter(".ipb-filter-select, .ipb-filter-date")
      .on("change.ipbFilters", applyFilters);
    filterEls($root, filterSelector).filter(".ipb-filter-text")
      .on("input.ipbFilters", function () {
        clearTimeout(textDebounce);
        textDebounce = setTimeout(applyFilters, 300);
      })
      .on("change.ipbFilters", function () { clearTimeout(textDebounce); applyFilters(); });

    $root.find(".ipb-date-range-apply").off("click.ipbFilters").on("click.ipbFilters", function (e) {
      e.preventDefault();
      applyFilters();
    });

    $root.find(".ipb-date-range-clear").off("click.ipbFilters").on("click.ipbFilters", function (e) {
      e.preventDefault();
      var $range = $(this).closest(".ipb-date-range");
      $range.find(".ipb-filter-date").val("");
      applyFilters();
    });

    $reset.off("click.ipbFilters").on("click.ipbFilters", function (e) {
      e.preventDefault();
      resetFilters();
    });

    toggleReset($root, filterSelector, $reset);
    if (hasActiveFilter($root, filterSelector)) {
      updateBadge();
    }

    return {
      updateBadge: updateBadge,
      reset: resetFilters,
      persist: persist,
      apply: applyFilters,
    };
  }

  function upgradeToolbar($toolbar) {
    var $filters = $toolbar.find(".ipb-list-toolbar-filters").first();
    if (!$filters.length) return false;

    $filters.find("select").not(".ipb-filter-select").addClass("ipb-filter-select").each(function () {
      if (!this.name && this.id) this.name = this.id;
    });

    $filters.find('input[type="date"]').not(".ipb-filter-date").addClass("ipb-filter-date").each(function () {
      if (!this.name && this.id) this.name = this.id;
    });

    if ($filters.find(RESET_SELECTOR).length === 0) {
      $filters.append(
        '<button type="button" class="ipb-filter-reset" style="display:none;" aria-label="Clear filters">' +
          '<i class="fa fa-times" aria-hidden="true"></i> Reset' +
          "</button>"
      );
    }

    if ($filters.find(COUNT_SELECTOR).length === 0) {
      $filters.append('<span class="ipb-filter-count" style="display:none;" aria-live="polite"></span>');
    }

    return filterEls($toolbar).length > 0;
  }

  function storageKeyFor($toolbar) {
    var explicit = $toolbar.attr("data-ipb-storage-key");
    if (explicit) return explicit;
    var path = (window.location.pathname || "list").replace(/\/+/g, "/").replace(/^\//, "").replace(/\//g, "_");
    var suffix = $toolbar.attr("id") || "";
    return "ipb_filters_" + (path || "list") + (suffix ? "_" + suffix : "");
  }

  function findToolbarForTable(api) {
    var $table = $(api.table().node());
    var $scope = $table.closest(".content-wrapper, .box, section.content, .ipb-saas-list").first();
    var $toolbar = $scope.find(".ipb-list-toolbar").first();
    if (!$toolbar.length) {
      $toolbar = $table.closest(".box").prevAll(".box-header").find(".ipb-list-toolbar").first();
    }
    return $toolbar;
  }

  function autoBindTable(api) {
    if (!api || !api.table) return;
    var $toolbar = findToolbarForTable(api);
    if (!$toolbar.length || $toolbar.attr("data-ipb-manual") === "1") return;
    if ($toolbar.attr("data-ipb-bound") === "1") return;
    if (!upgradeToolbar($toolbar)) return;

    var storageKey = storageKeyFor($toolbar);
    var $root = $toolbar.find(".ipb-list-toolbar-filters").first();
    if (!$root.length) $root = $toolbar;

    restore({ storageKey: storageKey, root: $root });
    bind(api, { storageKey: storageKey, root: $root, tableSelector: $(api.table().node()) });
    $toolbar.attr("data-ipb-bound", "1");
  }

  function preRestoreAll() {
    $(".ipb-list-toolbar").each(function () {
      var $toolbar = $(this);
      if ($toolbar.attr("data-ipb-manual") === "1") return;
      if (!upgradeToolbar($toolbar)) return;
      var storageKey = storageKeyFor($toolbar);
      var $root = $toolbar.find(".ipb-list-toolbar-filters").first();
      if (!$root.length) $root = $toolbar;
      restore({ storageKey: storageKey, root: $root });
    });
  }

  function autoInit() {
    if (!window.jQuery || !$.fn.dataTable) return;

    preRestoreAll();

    $(document).off("init.dt.ipbFilters").on("init.dt.ipbFilters", function (_e, settings) {
      try {
        autoBindTable(new $.fn.dataTable.Api(settings));
      } catch (err) {
        /* non-fatal */
      }
    });

    $(".datatable").each(function () {
      if ($.fn.DataTable.isDataTable(this)) {
        try {
          autoBindTable($(this).DataTable());
        } catch (err) {
          /* non-fatal */
        }
      }
    });
  }

  window.IpbFilters = {
    restore: restore,
    bind: bind,
    autoInit: autoInit,
    defaultUpdateBadge: defaultUpdateBadge,
    hasActiveFilter: hasActiveFilter,
  };

  $(function () {
    if (document.body && document.body.classList.contains("ipb")) {
      autoInit();
    }
  });
})(window, window.jQuery);
