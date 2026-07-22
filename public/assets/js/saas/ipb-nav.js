/**
 * Sidebar partial navigation.
 *
 * Sidebar/header/footer render exactly once and stay alive in the DOM for the
 * whole session. Clicking a sidebar link no longer triggers a real browser
 * navigation (full document unload/reparse/repaint — "the flick"); instead
 * this fetches the destination with an X-IPB-Nav header, the server hands
 * back just that page's title/css/content/script sections (see the top of
 * layout/main-layout.php), and this module swaps only <main id="ipb-main">,
 * then re-runs the sidebar sync (active item, open section, rail position,
 * breadcrumbs) so only "the indication" moves — never the sidebar itself.
 *
 * Every route goes through this path, including /dashboard and /customers*.
 * Those used to be hard-excluded (full reload only) to dodge a `php spark
 * serve` (single-threaded dev server) wedge on abort — but that server model
 * isn't what runs in production (nginx + php-fpm, many workers), and the
 * exclusion meant the two most-visited menu items always paid for a full
 * document reload, i.e. exactly the sidebar "flick" this file exists to
 * remove. teardownMain() below now aborts stale requests AND runs each page's
 * registered IpbPageTeardown callbacks (dashboard's live-traffic poll, etc.),
 * so pages with recurring timers/AJAX can join partial nav safely instead of
 * being carved out wholesale.
 *
 * Anything that doesn't fit this path — forms, non-GET, off-site links, new
 * tab, downloads, logout, or any response that isn't the partial shape we
 * expect (session expired -> redirected to the login page, a 404/500, etc.)
 * — falls back to a real `location.href` navigation, i.e. exactly today's
 * behavior. That fallback is the safety net: nothing here can leave the user
 * stuck on a broken page.
 */
(function (window, document) {
  "use strict";

  /** In-flight jQuery XHRs started while a page is mounted. On partial nav we
   *  abort only the *previous* generation so a just-started request for the
   *  new page is never killed by a second teardown. */
  var ajaxPool = [];
  var navGeneration = 0;

  /** Convention for pages with state that outlives a single request (a
   *  setInterval/recursive setTimeout poll, an ApexCharts instance holding a
   *  window resize listener, ...): push a cleanup callback here and it runs
   *  once, right before this page's content is torn down for the next one.
   *    (window.IpbPageTeardown = window.IpbPageTeardown || []).push(fn);
   *  Reset to [] after every swap — see teardownMain(). */
  window.IpbPageTeardown = window.IpbPageTeardown || [];

  function ready(fn) {
    if (document.readyState !== "loading") fn();
    else document.addEventListener("DOMContentLoaded", fn);
  }

  function sameOrigin(href) {
    try {
      return new URL(href, window.location.href).origin === window.location.origin;
    } catch (e) {
      return false;
    }
  }

  function eligible(a) {
    if (!a) return false;
    if (a.closest("[data-full-reload]")) return false;
    if (a.target && a.target !== "" && a.target !== "_self") return false;
    if (a.hasAttribute("download")) return false;
    var href = a.getAttribute("href") || "";
    if (!href || href === "#") return false;
    if (href.indexOf("javascript:") === 0 || href.indexOf("mailto:") === 0 || href.indexOf("tel:") === 0) return false;
    if (!sameOrigin(href)) return false;
    return true;
  }

  function trackAjax() {
    if (!window.jQuery || window.IpbAjaxPoolBound) return;
    window.IpbAjaxPoolBound = true;
    window.jQuery.ajaxPrefilter(function (_options, _original, jqXHR) {
      jqXHR._ipbNavGen = navGeneration;
      ajaxPool.push(jqXHR);
      jqXHR.always(function () {
        var i = ajaxPool.indexOf(jqXHR);
        if (i !== -1) ajaxPool.splice(i, 1);
      });
    });
  }

  /**
   * Run any page-registered cleanup (recurring setInterval/setTimeout polls —
   * see window.IpbPageTeardown above), destroy every ApexCharts instance the
   * outgoing page registered via window.IpbTheme.registerChart(), abort only
   * XHRs from generations older than the new one, then destroy DataTables
   * still living under #ipb-main. Called once per swap.
   */
  function teardownMain() {
    var callbacks = window.IpbPageTeardown || [];
    window.IpbPageTeardown = [];
    callbacks.forEach(function (fn) {
      try {
        fn();
      } catch (e) {}
    });

    if (window.IpbTheme && typeof window.IpbTheme.destroyCharts === "function") {
      window.IpbTheme.destroyCharts();
    }

    var dyingGen = navGeneration;
    navGeneration += 1;

    var kept = [];
    while (ajaxPool.length) {
      var xhr = ajaxPool.pop();
      if (xhr && xhr._ipbNavGen === dyingGen) {
        try {
          xhr.abort();
        } catch (e) {}
      } else if (xhr) {
        kept.push(xhr);
      }
    }
    ajaxPool = kept;

    if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.dataTable) return;
    var $ = window.jQuery;
    var $main = $("#ipb-main");
    if (!$main.length) return;

    $main.find("table").each(function () {
      if ($.fn.dataTable.isDataTable(this)) {
        try {
          $(this).DataTable().clear().destroy(true);
        } catch (e) {
          try {
            $(this).DataTable().destroy(true);
          } catch (e2) {}
        }
      }
    });
  }

  function execScripts(root) {
    if (!root) return;
    root.querySelectorAll("script").forEach(function (old) {
      var s = document.createElement("script");
      for (var i = 0; i < old.attributes.length; i++) {
        s.setAttribute(old.attributes[i].name, old.attributes[i].value);
      }
      s.text = old.textContent;
      s.setAttribute("data-ipb-nav-script", "1");
      document.body.appendChild(s);
    });
  }

  function pruneStaleScripts() {
    document.querySelectorAll("script[data-ipb-nav-script='1']").forEach(function (s) {
      if (s.parentNode) s.parentNode.removeChild(s);
    });
  }

  function mergeCss(fragment) {
    if (!fragment) return;
    fragment.querySelectorAll("link[rel=stylesheet], style").forEach(function (node) {
      var isLink = node.tagName === "LINK";
      var key = isLink ? node.getAttribute("href") : node.textContent;
      var already = Array.prototype.some.call(
        document.head.querySelectorAll(isLink ? "link[rel=stylesheet]" : "style"),
        function (existing) {
          return (isLink ? existing.getAttribute("href") : existing.textContent) === key;
        }
      );
      if (!already) document.head.appendChild(document.importNode(node, true));
    });
  }

  /**
   * Page-load-performance audit Part 2 (A3): DataTables/ApexCharts now load
   * only on pages that declare they need them (see main-layout.php's
   * needsDataTable/needsApexCharts sections) — but the shell (and those
   * <script> tags) render exactly ONCE per browser session; a page reached
   * later purely via this partial-nav path never re-runs that shell. If the
   * session's first full load didn't need a library, a page that DOES need
   * it — reached only by clicking a sidebar link — would otherwise find the
   * library missing. This loads it on demand, once, before the swapped-in
   * page's own script (e.g. a .DataTable() call) runs.
   */
  function loadScript(src) {
    return new Promise(function (resolve, reject) {
      if (!src) { resolve(); return; }
      var s = document.createElement("script");
      s.src = src;
      s.async = false; // preserve order relative to any script queued after it
      s.onload = function () { resolve(); };
      s.onerror = function () { reject(new Error("ipb-nav: failed to load " + src)); };
      document.body.appendChild(s);
    });
  }

  function loadStyle(href) {
    return new Promise(function (resolve) {
      if (!href) { resolve(); return; }
      var already = Array.prototype.some.call(
        document.head.querySelectorAll("link[rel=stylesheet]"),
        function (existing) { return existing.getAttribute("href") === href; }
      );
      if (already) { resolve(); return; }
      var l = document.createElement("link");
      l.rel = "stylesheet";
      l.href = href;
      // A stylesheet failing to load shouldn't block the navigation — the
      // table would just be unstyled, not broken.
      l.onload = function () { resolve(); };
      l.onerror = function () { resolve(); };
      document.head.appendChild(l);
    });
  }

  function ensureAssetsLoaded(doc) {
    var tpl = doc.getElementById("ipb-nav-assets");
    if (!tpl) return Promise.resolve();

    var tasks = [];

    if (
      tpl.getAttribute("data-datatable") === "1" &&
      !(window.jQuery && window.jQuery.fn && window.jQuery.fn.dataTable)
    ) {
      tasks.push(loadStyle(tpl.getAttribute("data-datatable-css")));
      tasks.push(
        loadScript(tpl.getAttribute("data-datatable-js")).then(function () {
          return loadScript(tpl.getAttribute("data-datatable-js2"));
        })
      );
    }

    if (tpl.getAttribute("data-apexcharts") === "1" && !window.ApexCharts) {
      tasks.push(loadScript(tpl.getAttribute("data-apexcharts-js")));
    }

    if (!tasks.length) return Promise.resolve();

    // Best-effort: if a library genuinely fails to load, still swap the page —
    // the page's own script may then throw, but that beats never navigating.
    return Promise.all(tasks).catch(function () {});
  }

  function applyPartial(html, href, push) {
    var mainEl = document.getElementById("ipb-main");
    if (!mainEl) return false;

    var doc = new DOMParser().parseFromString(html, "text/html");
    var contentTpl = doc.getElementById("ipb-nav-content");
    if (!contentTpl || !contentTpl.content) return false;

    // Single teardown per swap (before wipe) — avoids racing a just-started XHR.
    teardownMain();
    pruneStaleScripts();

    mergeCss(doc.getElementById("ipb-nav-css") && doc.getElementById("ipb-nav-css").content);

    mainEl.innerHTML = "";
    mainEl.appendChild(document.importNode(contentTpl.content, true));

    var titleTpl = doc.getElementById("ipb-nav-title");
    var newTitle = titleTpl && titleTpl.getAttribute("data-title");
    if (newTitle) document.title = newTitle;

    var scriptTpl = doc.getElementById("ipb-nav-script");
    execScripts(scriptTpl && scriptTpl.content);
    execScripts(mainEl);

    mainEl.scrollTop = 0;
    window.scrollTo(0, 0);

    if (push) history.pushState({ ipbNav: true }, "", href);

    if (window.IpbSaas && typeof window.IpbSaas.afterContentSwap === "function") {
      window.IpbSaas.afterContentSwap();
    }

    return true;
  }

  function navigateTo(href, push) {
    if (window.IpbNetPulse && typeof window.IpbNetPulse.start === "function") {
      window.IpbNetPulse.start();
    }

    // Do not teardown here — wait until applyPartial so we only abort once,
    // right before swapping content (prevents double-abort races).
    fetch(href, {
      headers: { "X-IPB-Nav": "1" },
      credentials: "same-origin",
    })
      .then(function (resp) {
        if (!resp.ok) throw new Error("ipb-nav: non-OK response");
        return resp.text();
      })
      .then(function (html) {
        var doc = new DOMParser().parseFromString(html, "text/html");
        return ensureAssetsLoaded(doc).then(function () {
          if (!applyPartial(html, href, push)) throw new Error("ipb-nav: unexpected response shape");
          if (window.IpbNetPulse && typeof window.IpbNetPulse.done === "function") {
            window.IpbNetPulse.done();
          }
        });
      })
      .catch(function () {
        if (window.IpbNetPulse && typeof window.IpbNetPulse.done === "function") {
          window.IpbNetPulse.done();
        }
        window.location.href = href;
      });
  }

  ready(function () {
    if (!document.body.classList.contains("ipb")) return;
    if (!document.getElementById("ipb-main")) return;
    if (!window.fetch || !window.DOMParser || !window.history || !history.pushState) return;

    trackAjax();

    document.addEventListener("click", function (e) {
      if (e.defaultPrevented || e.button !== 0) return;
      if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

      var a = e.target.closest && e.target.closest(".sidebar-menu a[href]");
      if (!a || !eligible(a)) return;

      e.preventDefault();
      navigateTo(a.getAttribute("href"), true);
    });

    window.addEventListener("popstate", function () {
      navigateTo(window.location.href, false);
    });
  });
})(window, document);
