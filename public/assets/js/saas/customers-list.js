/**
 * Customers list helpers — column picker, copy link, tooltips
 */
(function (window, $) {
  "use strict";

  // Keep the default set narrow so Action (Update) stays in the first viewport.
  // Address / Router remain available via Columns.
  var DEFAULT_VISIBLE = {
    select: true,
    id: true,
    name: true,
    package: true,
    area_name: true,
    mobile: true,
    address: false,
    router_name: false,
    pppoe_secret: true,
    router_password: true,
    payment: true,
    conn_status: true,
    acc_status: false,
    action: true,
  };

  // v4 — PPPoE Secret / Password now show by default; bump key so old
  // saved prefs (which had them hidden) don't mask the new default.
  var STORAGE_KEY = "ipb_customer_columns_v4";

  function loadPrefs() {
    try {
      var raw = localStorage.getItem(STORAGE_KEY);
      if (!raw) return Object.assign({}, DEFAULT_VISIBLE);
      return Object.assign({}, DEFAULT_VISIBLE, JSON.parse(raw));
    } catch (e) {
      return Object.assign({}, DEFAULT_VISIBLE);
    }
  }

  function savePrefs(prefs) {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(prefs));
    } catch (e) {}
  }

  function colVisible(key, prefs) {
    prefs = prefs || loadPrefs();
    return prefs[key] !== false;
  }

  /**
   * Keep length/filter/paginate outside the horizontal scroller.
   * DataTables nests the whole wrapper inside .ipb-customers-table-wrap, so
   * overflowing the wrap also dragged the controls sideways. Isolate the
   * <table> into .ipb-customers-table-scroll instead.
   */
  function refreshTableScrollHint($scroller) {
    var el = $scroller && $scroller[0];
    if (!el) return;
    var wide = el.scrollWidth > el.clientWidth + 8;
    el.classList.toggle("is-scrollable", wide);
    var wrap = el.closest(".ipb-customers-table-wrap");
    if (wrap) wrap.classList.toggle("is-scrollable", wide);
  }

  function isolateTableScroll(api) {
    if (!api || !$) return;
    var table = api.table().node();
    if (!table) return;
    var $table = $(table);
    var $existing = $table.parent(".ipb-customers-table-scroll");
    if ($existing.length) {
      refreshTableScrollHint($existing);
      return;
    }
    var $wrap = $table.closest(".ipb-customers-table-wrap");
    if (!$wrap.length) return;

    var $scroller = $(
      '<div class="ipb-customers-table-scroll" role="region" aria-label="Customers table" tabindex="0"></div>'
    );
    $table.before($scroller);
    $scroller.append($table);

    $scroller.off("scroll.ipbCustScroll").on("scroll.ipbCustScroll", function () {
      if (this.scrollLeft > 8) {
        this.classList.remove("is-scrollable");
        var wrap = this.closest(".ipb-customers-table-wrap");
        if (wrap) wrap.classList.remove("is-scrollable");
      }
    });

    window.requestAnimationFrame(function () {
      refreshTableScrollHint($scroller);
      if (api && api.columns) api.columns.adjust();
    });
  }

  function initColumnPicker(api) {
    if (!api || !$.fn || !$.fn.dataTable) return loadPrefs();

    isolateTableScroll(api);

    var prefs = loadPrefs();
    var $heads = $(api.table().header()).find("th[data-col]");
    var columnsMeta = [];

    $heads.each(function (idx) {
      var $th = $(this);
      var key = $th.attr("data-col");
      if (!key) return;
      columnsMeta.push({
        index: idx,
        key: key,
        label: $th.attr("data-col-label") || $th.text().trim() || key,
        locked: $th.attr("data-col-locked") === "1",
      });
    });

    function renderList(current) {
      var $list = $("#ipbColPickerList");
      if (!$list.length) return;
      $list.empty();
      columnsMeta.forEach(function (col) {
        if (col.key === "select") return;
        var checked = col.locked ? true : current[col.key] !== false;
        $list.append(
          $(
            '<label class="ipb-col-picker-item' +
              (col.locked ? " is-locked" : "") +
              '">' +
              '<input type="checkbox" data-col-key="' +
              col.key +
              '"' +
              (checked ? " checked" : "") +
              (col.locked ? " disabled" : "") +
              " />" +
              "<span>" +
              col.label +
              "</span>" +
              "</label>"
          )
        );
      });
    }

    function applyPrefs(current) {
      prefs = current;
      columnsMeta.forEach(function (col) {
        var visible = col.locked ? true : current[col.key] !== false;
        api.column(col.index).visible(visible, false);
      });
      api.columns.adjust().draw(false);
      renderList(current);
      window.requestAnimationFrame(function () {
        refreshTableScrollHint($(api.table().node()).parent(".ipb-customers-table-scroll"));
      });
    }

    renderList(prefs);

    var $btn = $("#ipbColPickerBtn");
    var $panel = $("#ipbColPickerPanel");
    if (!$btn.length) return prefs;

    $btn.off("click.ipbCols").on("click.ipbCols", function (e) {
      e.preventDefault();
      e.stopPropagation();
      var open = $panel.prop("hidden");
      $panel.prop("hidden", !open);
      $btn.attr("aria-expanded", open ? "true" : "false");
    });

    $(document)
      .off("click.ipbCols")
      .on("click.ipbCols", function (e) {
        if (!$(e.target).closest("#ipbColPicker").length) {
          $panel.prop("hidden", true);
          $btn.attr("aria-expanded", "false");
        }
      });

    $("#ipbColPickerList")
      .off("change.ipbCols")
      .on("change.ipbCols", "input[data-col-key]", function () {
        var key = $(this).attr("data-col-key");
        prefs[key] = $(this).is(":checked");
        savePrefs(prefs);
        applyPrefs(prefs);
      });

    $("#ipbColPickerReset")
      .off("click.ipbCols")
      .on("click.ipbCols", function (e) {
        e.preventDefault();
        prefs = Object.assign({}, DEFAULT_VISIBLE);
        savePrefs(prefs);
        applyPrefs(prefs);
      });

    return prefs;
  }

  function bindRowTooltips(api) {
    try {
      var $tips = $('[data-toggle="tooltip"]', api.table().body());
      $tips.tooltip("destroy");
      $tips.tooltip({ container: "body", trigger: "hover", placement: "top" });
    } catch (e) {}
  }

  function initCopyLink() {
    $(document)
      .off("click.ipbCopySub")
      .on("click.ipbCopySub", ".ipb-copy-sub-link", function (e) {
        e.preventDefault();
        var link = $(this).attr("data-link") || "";
        if (!link) return;
        var done = function () {
          if (window.tata && tata.success) tata.success("Copied", "Subscription link copied");
          else alert("Subscription link copied!");
        };
        if (navigator.clipboard && navigator.clipboard.writeText) {
          navigator.clipboard.writeText(link).then(done).catch(function () {
            window.prompt("Copy link:", link);
          });
        } else {
          window.prompt("Copy link:", link);
        }
      });
  }

  function initMoreMenu() {
    function closeAll($except) {
      $(".ipb-cust-more").each(function () {
        if ($except && this === $except[0]) return;
        var $wrap = $(this);
        $wrap.removeClass("is-open");
        $wrap.find(".ipb-cust-more-menu").prop("hidden", true);
        $wrap.find(".ipb-cust-more-btn").attr("aria-expanded", "false");
      });
    }

    $(document)
      .off("click.ipbCustMore click.ipbCustMoreDoc keydown.ipbCustMore")
      .on("click.ipbCustMore", ".ipb-cust-more-btn", function (e) {
        e.preventDefault();
        e.stopPropagation();
        var $wrap = $(this).closest(".ipb-cust-more");
        var $menu = $wrap.find(".ipb-cust-more-menu");
        var willOpen = $menu.prop("hidden");
        closeAll(willOpen ? $wrap : null);
        $menu.prop("hidden", !willOpen);
        $wrap.toggleClass("is-open", willOpen);
        $(this).attr("aria-expanded", willOpen ? "true" : "false");
      })
      .on("click.ipbCustMore", ".ipb-cust-more-item", function () {
        // Only close the overflow menu. Do not stopPropagation here —
        // Transfer/Delete bind on document and must still receive this click.
        // Their own handlers defer swal() so SweetAlert does not auto-dismiss.
        setTimeout(closeAll, 0);
      })
      .on("click.ipbCustMoreDoc", function (e) {
        if (!$(e.target).closest(".ipb-cust-more").length) closeAll();
      })
      .on("keydown.ipbCustMore", function (e) {
        if (e.key === "Escape") closeAll();
      });
  }

  window.IpbCustomersList = {
    loadPrefs: loadPrefs,
    colVisible: colVisible,
    initColumnPicker: initColumnPicker,
    isolateTableScroll: isolateTableScroll,
    refreshTableScrollHint: refreshTableScrollHint,
    bindRowTooltips: bindRowTooltips,
    initCopyLink: initCopyLink,
    initMoreMenu: initMoreMenu,
    defaults: DEFAULT_VISIBLE,
  };

  $(function () {
    initCopyLink();
    initMoreMenu();
  });
})(window, jQuery);
