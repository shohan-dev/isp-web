/**
 * DataTables serverSide load safety for partial-nav pages.
 *
 * Without this, an aborted / hung XHR leaves the skeleton tbody forever
 * (processing:false + error==='abort' early return). On php spark serve a
 * single busy worker makes that the common case after rapid sidebar clicks.
 *
 * Important: do NOT treat bare drawCallback as success — DataTables can draw
 * skeleton/placeholder rows before ajax.json() exists, which used to clear the
 * watchdog and leave the grey bars forever.
 *
 * Usage:
 *   var load = IpbDtLoad.bind({ ... });
 *   // ajax.error → load.onError
 *   // ajax dataSrc or xhr.dt → load.onAjaxOk
 *   // drawCallback → load.onDraw(api)  (safe; ignores pre-ajax draws)
 *   // after init → load.watch(table)
 */
(function (window) {
  "use strict";

  var DEFAULT_WATCHDOG_MS = 8000;

  function escHtml(s) {
    return String(s == null ? "" : s)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function failHtml(cols, msg, retryId) {
    return (
      '<tr class="odd"><td valign="top" colspan="' +
      cols +
      '" class="dataTables_empty">' +
      '<div class="ipb-empty ipb-dt-empty">' +
      '<div class="ipb-empty-icon"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i></div>' +
      '<div class="ipb-empty-title">Load failed</div>' +
      '<div class="ipb-empty-sub">' +
      escHtml(msg) +
      "</div>" +
      '<div class="ipb-empty-action"><button type="button" class="btn btn-primary btn-sm" id="' +
      escHtml(retryId) +
      '">Retry</button></div>' +
      "</div></td></tr>"
    );
  }

  function stillSkeleton($, tableSelector) {
    if (!$) return false;
    var $tb = $(tableSelector + " tbody");
    if (!$tb.length) return false;
    if ($tb.hasClass("ipb-skeleton-tbody")) return true;
    return $tb.find(".ipb-skeleton").length > 0;
  }

  function bind(opts) {
    opts = opts || {};
    var tableSelector = opts.tableSelector || ".datatable";
    var retryId = opts.retryId || "ipb-dt-retry";
    var failTitle = opts.failTitle || "Could not load data. Please retry.";
    var timeoutTitle =
      opts.timeoutTitle || "Request timed out. Check your connection and retry.";
    var watchdogMs =
      typeof opts.watchdogMs === "number" ? opts.watchdogMs : DEFAULT_WATCHDOG_MS;

    var pageAlive = true;
    var loadedOnce = false;
    var softRetried = false;
    var watchdogTimer = null;
    var tableApi = null;
    var $ = window.jQuery;

    function clearWatchdog() {
      if (watchdogTimer) {
        clearTimeout(watchdogTimer);
        watchdogTimer = null;
      }
    }

    function colCount() {
      if (!$) return 14;
      var n = $(tableSelector + " thead th").length;
      return n || 14;
    }

    function showFail(msg) {
      if (!pageAlive || !$) return;
      clearWatchdog();
      $(tableSelector + " tbody").html(failHtml(colCount(), msg, retryId));
      if (window.tata) {
        try {
          tata.error("Load failed", msg);
        } catch (e) {}
      }
    }

    function abortInFlight() {
      if (!tableApi) return;
      try {
        var settings = tableApi.settings && tableApi.settings()[0];
        if (settings && settings.jqXHR && typeof settings.jqXHR.abort === "function") {
          settings.jqXHR.abort();
        }
      } catch (e) {}
    }

    function softReload() {
      if (!tableApi) return false;
      try {
        abortInFlight();
        tableApi.ajax.reload(null, false);
        armWatchdog();
        return true;
      } catch (e) {
        return false;
      }
    }

    function armWatchdog() {
      clearWatchdog();
      if (!pageAlive || watchdogMs <= 0) return;
      watchdogTimer = setTimeout(function () {
        watchdogTimer = null;
        if (!pageAlive || loadedOnce) return;
        // Still showing skeleton with no successful ajax → recover.
        if (!softRetried) {
          softRetried = true;
          if (softReload()) return;
        }
        showFail(timeoutTitle);
      }, watchdogMs);
    }

    /** Mark success only after a real serverSide ajax payload arrived. */
    function onAjaxOk() {
      if (!pageAlive) return;
      loadedOnce = true;
      clearWatchdog();
    }

    /**
     * Safe draw hook: ignore draws that happen before ajax.json() exists or
     * while skeleton rows are still in the tbody.
     */
    function onDraw(api) {
      if (!pageAlive) return;
      var dt = api || tableApi;
      if (!dt || !dt.ajax || typeof dt.ajax.json !== "function") return;
      var json = null;
      try {
        json = dt.ajax.json();
      } catch (e) {
        return;
      }
      if (!json || typeof json.draw === "undefined") return;
      if (stillSkeleton($, tableSelector)) return;
      onAjaxOk();
    }

    function onError(_xhr, error) {
      if (!pageAlive) return;

      if (error === "abort") {
        // Navigated away is handled via pageAlive=false in teardown.
        // Abort while still on this page → soft retry once, then Retry UI.
        if (!softRetried) {
          softRetried = true;
          if (softReload()) return;
        }
        showFail(failTitle);
        return;
      }

      var msg = error === "timeout" ? timeoutTitle : failTitle;
      if (!softRetried && error === "timeout") {
        softRetried = true;
        if (softReload()) return;
      }
      showFail(msg);
    }

    function watch(api) {
      tableApi = api;
      if (api && api.on) {
        // xhr fires after a successful ajax response is available.
        api.on("xhr.dt", function (_e, _settings, json) {
          if (json) onAjaxOk();
        });
      }
      armWatchdog();
    }

    function teardown() {
      pageAlive = false;
      clearWatchdog();
      abortInFlight();
      tableApi = null;
    }

    (window.IpbPageTeardown = window.IpbPageTeardown || []).push(teardown);

    return {
      onDraw: onDraw,
      onAjaxOk: onAjaxOk,
      onError: onError,
      watch: watch,
      teardown: teardown,
      isAlive: function () {
        return pageAlive;
      },
    };
  }

  window.IpbDtLoad = { bind: bind };
})(window);
