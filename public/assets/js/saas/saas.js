/**
 * ISP Pay BD SaaS — presentation behaviors
 * Theme, sidebar, command palette. Does not alter business logic.
 */
(function () {
  "use strict";

  var THEME_KEY = "ipb_theme";
  var COLLAPSE_KEY = "ipb_sidebar_collapsed";

  function bodyEl() {
    return document.body;
  }

  /* 05 §10 — checks BOTH reduced-motion signals (CSS guards alone don't stop
     requestAnimationFrame / behavior:'smooth'). Every JS animation in the
     motion system calls this and jumps straight to the final state when true. */
  window.IpbMotion = window.IpbMotion || {};
  window.IpbMotion.reduced = function () {
    return (window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches) ||
      !!(document.body && document.body.classList.contains("ipb-reduce-motion"));
  };

  function applyTheme(theme) {
    var el = bodyEl();
    if (!el) return;
    el.setAttribute("data-theme", theme === "dark" ? "dark" : "light");
    el.classList.toggle("dark-mode", theme === "dark");
    var btn = document.getElementById("darkToggle");
    if (btn) {
      btn.innerHTML =
        theme === "dark"
          ? '<i class="fa-solid fa-sun" aria-hidden="true"></i>'
          : '<i class="fa-solid fa-moon" aria-hidden="true"></i>';
      btn.setAttribute("aria-label", theme === "dark" ? "Switch to light mode" : "Switch to dark mode");
      btn.setAttribute("title", theme === "dark" ? "Light mode" : "Dark mode");
    }
  }

  function initTheme() {
    var saved = localStorage.getItem(THEME_KEY) || "light";
    applyTheme(saved);
    var btn = document.getElementById("darkToggle");
    if (!btn) return;
    btn.addEventListener("click", function (e) {
      e.preventDefault();
      var next = bodyEl().getAttribute("data-theme") === "dark" ? "light" : "dark";
      localStorage.setItem(THEME_KEY, next);
      applyTheme(next);
    });
  }

  /* Design-token bridge for canvas charts.
     ApexCharts and vis-network take colors as plain JS strings, so they cannot
     consume the CSS custom properties in tokens.css and used to hardcode a light
     palette — which failed WCAG AA on axis labels and stayed light in dark mode.
     Read the tokens off the computed style instead. */
  function cssToken(name, fallback) {
    try {
      var v = getComputedStyle(bodyEl()).getPropertyValue(name);
      return (v && v.trim()) || fallback;
    } catch (e) {
      return fallback;
    }
  }

  /* 06 §5 — color harmony: orange is brand-surface + interactive, never a
     data-viz series (three colliding roles made nothing legible as any one
     thing). Charts now pick series from --data-1..8; the "accent" slot for a
     single-series highlight also comes from the data-viz set, not brand orange. */
  function chartPalette() {
    return {
      axis: cssToken("--text-muted", "#5b6a85"),
      grid: cssToken("--border", "#d7dee7"),
      ink: cssToken("--text-primary", "#0f172a"),
      surface: cssToken("--surface", "#ffffff"),
      series: ["--data-1", "--data-2", "--data-3", "--data-4", "--data-5", "--data-6", "--data-7", "--data-8"]
        .map(function (n) { return cssToken(n, "#2563eb"); }),
      accent: cssToken("--data-1", "#2563eb"),   // was --primary-500; brand orange is no longer a series
    };
  }

  /* Register axis-based charts only (line/area/bar). Donut and pie charts have no
     xaxis/yaxis and would be handed options they cannot apply. */
  var themedCharts = [];

  function registerChart(chart) {
    if (chart && typeof chart.updateOptions === "function") themedCharts.push(chart);
    return chart;
  }

  /* Called by ipb-nav.js before swapping #ipb-main to a new page — every
     ApexCharts instance the outgoing page registered (dashboards, the
     customer usage chart) holds a window resize listener that otherwise
     outlives the DOM it was drawn into. */
  function destroyCharts() {
    themedCharts.forEach(function (chart) {
      try {
        if (chart && typeof chart.destroy === "function") chart.destroy();
      } catch (e) {}
    });
    themedCharts.length = 0;
  }

  function restyleCharts() {
    var p = chartPalette();
    themedCharts.forEach(function (chart) {
      try {
        chart.updateOptions(
          {
            grid: { borderColor: p.grid },
            xaxis: { labels: { style: { colors: p.axis } } },
            yaxis: { labels: { style: { colors: p.axis } } },
            noData: { style: { color: p.axis } },
          },
          false,
          false
        );
      } catch (e) {
        /* chart already destroyed — drop it silently rather than break the toggle */
      }
    });
  }

  /* The toggle swaps data-theme live with no reload, and it is flipped from two
     independent places (initTheme above and customize.js). Watch the attribute
     rather than hooking each toggle, so neither path can miss a restyle. */
  function initThemeObserver() {
    var el = bodyEl();
    if (!el || typeof MutationObserver !== "function") return;
    var last = el.getAttribute("data-theme");
    new MutationObserver(function () {
      var next = el.getAttribute("data-theme");
      if (next === last) return;
      last = next;
      restyleCharts();
      document.dispatchEvent(new CustomEvent("ipb:themechange", { detail: { theme: next } }));
    }).observe(el, { attributes: true, attributeFilter: ["data-theme"] });
  }

  window.IpbTheme = {
    token: cssToken,
    chartPalette: chartPalette,
    registerChart: registerChart,
    destroyCharts: destroyCharts,
  };

  function setCollapsed(collapsed) {
    bodyEl().classList.toggle("ipb-sidebar-collapsed", collapsed);
    bodyEl().classList.toggle("sidebar-collapse", collapsed);
    localStorage.setItem(COLLAPSE_KEY, collapsed ? "1" : "0");
    var btn = document.getElementById("ipbSidebarCollapse");
    if (btn) {
      btn.setAttribute("aria-label", collapsed ? "Expand sidebar" : "Collapse sidebar");
      btn.innerHTML = collapsed
        ? '<i class="fa-solid fa-chevron-right" aria-hidden="true"></i>'
        : '<i class="fa-solid fa-chevron-left" aria-hidden="true"></i>';
    }
  }

  function closeMobileSidebar() {
    setMobileSidebarOpen(false);
  }

  /* normalizeMenuPath() lived here and matched the current URL against the menu.
     It now lives in sidebar-boot.js, which needs it before this file is even
     loaded — one copy, in the file that runs first. */

  function isSectionLi(li) {
    return !!(li && li.querySelector && li.querySelector(":scope > .treeview-menu"));
  }

  function sectionMenu(li) {
    return li ? li.querySelector(":scope > .treeview-menu") : null;
  }

  function openSection(li) {
    if (!li || !isSectionLi(li)) return;
    li.classList.add("menu-open");
    var menu = sectionMenu(li);
    if (menu) menu.style.removeProperty("display");
  }

  function closeSection(li) {
    if (!li || !isSectionLi(li)) return;
    // Never collapse the section that owns the current page
    if (li.querySelector(".treeview-menu li.active, :scope > .treeview-menu > li.active")) {
      openSection(li);
      return;
    }
    li.classList.remove("menu-open");
    var menu = sectionMenu(li);
    if (menu) menu.style.removeProperty("display");
  }

  function openAncestorSections(li) {
    var node = li;
    while (node && node !== document.body) {
      if (node.matches && node.matches("li") && isSectionLi(node)) {
        openSection(node);
      }
      node = node.parentElement ? node.parentElement.closest("li") : null;
    }
  }

  /** Active item + open sections. sidebar-boot.js already settled this BEFORE the
   *  sidebar was painted (it is a parser-blocking script sitting right under the
   *  menu markup) — so all this has to do now is read the state it left and build
   *  the crumbs from it.
   *
   *  This function used to re-derive everything from the URL on DOMContentLoAded:
   *  it stripped every li.active and li.menu-open, re-added them, then SMOOTH-
   *  scrolled the active link into view on a 50ms timer. All of that ran after
   *  first paint, so on every single navigation the menu visibly collapsed, popped
   *  back open, and animated its scroll. That was the flip. */
  function syncSidebarActiveFromUrl() {
    var menuRoot = document.querySelector(".sidebar-menu");
    if (!menuRoot) return;

    var activeLi = null;
    if (window.IpbSidebarBoot && typeof window.IpbSidebarBoot.sync === "function") {
      activeLi = window.IpbSidebarBoot.sync();   // idempotent: a no-op on the boot pass
    }

    if (!activeLi) {
      // sidebar-boot.js absent (a shell that does not include it) — keep the PHP
      // active markers and just make sure their sections are open.
      activeLi = menuRoot.querySelector("li.active");
      if (activeLi) {
        openAncestorSections(activeLi);
        if (isSectionLi(activeLi)) openSection(activeLi);
      }
    }

    buildTopCrumbs(activeLi);
  }

  /** 06 §2.2 — derive breadcrumbs from the sidebar's own active path instead
   *  of hand-authoring them on 200 pages. Only shows for 2+ levels
   *  (section > page); pages reachable only via a non-sidebar link fall back
   *  to no breadcrumb (acceptable — better than today's none). */
  function buildTopCrumbs(activeLi) {
    var host = document.getElementById("ipbTopCrumbs");
    if (!host || !activeLi) return;
    var trail = [], node = activeLi;
    while (node && node.matches && node.matches("li")) {
      var a = node.querySelector(":scope > a");
      var span = a && a.querySelector("span");
      var label = ((span ? span.textContent : (a ? a.textContent : "")) || "").replace(/\s+/g, " ").trim();
      var href = a ? a.getAttribute("href") : null;
      if (label) trail.unshift({ label: label, href: (href && href !== "#" && href.indexOf("javascript:") !== 0) ? href : null });
      node = node.parentElement ? node.parentElement.closest("li") : null;
    }
    if (trail.length < 2) { host.hidden = true; return; }
    host.hidden = false;
    host.innerHTML = trail.map(function (c, i) {
      var last = i === trail.length - 1;
      var el = (c.href && !last) ? ('<a href="' + c.href + '">' + c.label + '</a>') : ('<span>' + c.label + '</span>');
      return el + (last ? '' : '<i class="fa fa-angle-right" aria-hidden="true"></i>');
    }).join("");
  }

  function openActiveTreeviews() {
    syncSidebarActiveFromUrl();
  }

  function setMobileSidebarOpen(open) {
    bodyEl().classList.toggle("ipb-sidebar-open", open);
    bodyEl().classList.toggle("sidebar-open", open);
    var toggle = document.getElementById("ipbMenuToggle");
    if (toggle) toggle.setAttribute("aria-expanded", open ? "true" : "false");
  }

  function hideZeroBadges() {
    document.querySelectorAll(".navbar-custom-menu .label").forEach(function (el) {
      var n = parseInt((el.textContent || "").trim(), 10);
      if (!n) el.classList.add("is-zero");
      else el.classList.remove("is-zero");
    });
  }

  /* 06 §2.1(b) — moves the sliding accent rail to the active (or hovered)
     sidebar item — the EXACT row for the current page, not just its parent
     category. Call after openActiveTreeviews() so li.active is already set.

     Bug fix: this used to read `:scope > li.active` only — a direct child of
     `.sidebar-menu`. sidebar-boot.js's sync() marks BOTH the real active leaf
     (e.g. "All Customers" inside the Customers submenu) AND its top-level
     ancestor ("Customers") as `.active`, so the rail needs to prefer the
     nested leaf when one exists; otherwise it always snapped to the parent
     header and never pointed at the page you're actually on. */
  function initNavIndicator() {
    var menu = document.querySelector(".main-sidebar .sidebar-menu");
    if (!menu) return;
    var bar = menu.querySelector(".ipb-nav-indicator");
    if (!bar) {
      bar = document.createElement("span");
      bar.className = "ipb-nav-indicator";
      menu.appendChild(bar);
    }

    function rowAnchor(li) {
      return li.querySelector(":scope > a") || li;
    }
    function moveTo(li) {
      if (!li) return;
      var a = rowAnchor(li);
      // offsetTop is relative to the anchor's offsetParent, which for a NESTED
      // submenu row (e.g. Product Showcase under Platform Admin) is the open
      // treeview-menu — NOT the .sidebar-menu the rail lives in — so the rail
      // used to land near the top (the Dashboard row) for every nested page.
      // Measure against the menu itself so it's correct at any nesting depth.
      var menuRect = menu.getBoundingClientRect();
      var aRect = a.getBoundingClientRect();
      var top = aRect.top - menuRect.top + (menu.scrollTop || 0);
      bar.style.height = aRect.height + "px";
      bar.style.transform = "translateY(" + top + "px)";
      bar.classList.add("is-ready");
    }
    // Prefer the nested leaf (the actual current page) over its top-level
    // section ancestor; fall back to the top-level li for non-nested pages.
    function activeLi() {
      return menu.querySelector(".treeview-menu li.active") || menu.querySelector(":scope > li.active");
    }

    moveTo(activeLi());

    /* Commit that first placement with NO transition, then enable it. The rail is
       created fresh on every page load, and moveTo() reads offsetTop — a forced
       style flush that locks in the height:0 / translateY(0) starting state. With
       the transition on the base rule, the rail then animated from the top of the
       menu down to the active item on every single navigation. Reading offsetHeight
       here flushes the placement first, so only later (hover) moves animate. */
    void bar.offsetHeight;
    bar.classList.add("is-animatable");

    // Follow hover on every row — top-level AND nested submenu items — so the
    // rail never sits still next to a row you're not pointing at.
    menu.querySelectorAll("li > a").forEach(function (a) {
      var li = a.parentElement;
      a.addEventListener("mouseenter", function () { moveTo(li); });
    });
    menu.addEventListener("mouseleave", function () { moveTo(activeLi()); });
    window.addEventListener("resize", function () { moveTo(activeLi()); });

    // Exposed for ipb-nav.js: after a partial-nav content swap, slide the rail
    // to the new active row instead of a fresh (jump-cut) placement.
    window.IpbNavIndicator = { sync: function () { moveTo(activeLi()); } };
  }

  function initSidebar() {
    var collapsed = localStorage.getItem(COLLAPSE_KEY) === "1";
    if (window.innerWidth > 1024) setCollapsed(collapsed);   /* was 900 — 03 §1.3 ladder */
    else closeMobileSidebar();

    openActiveTreeviews();
    initNavIndicator();
    hideZeroBadges();

    var collapseBtn = document.getElementById("ipbSidebarCollapse");
    if (collapseBtn) {
      collapseBtn.addEventListener("click", function (e) {
        e.preventDefault();
        setCollapsed(!bodyEl().classList.contains("ipb-sidebar-collapsed"));
      });
    }

    var toggleBtns = document.querySelectorAll(".sidebar-toggle, #ipbMenuToggle");
    toggleBtns.forEach(function (btn) {
      btn.addEventListener("click", function (e) {
        e.preventDefault();
        e.stopPropagation();
        if (window.innerWidth <= 1024) {   /* was 900 — 03 §1.3 ladder */
          setMobileSidebarOpen(!bodyEl().classList.contains("ipb-sidebar-open"));
        } else {
          setCollapsed(!bodyEl().classList.contains("ipb-sidebar-collapsed"));
        }
      });
    });
    var menuToggle = document.getElementById("ipbMenuToggle");
    if (menuToggle) menuToggle.setAttribute("aria-expanded", "false");

    var backdrop = document.querySelector(".ipb-sidebar-backdrop");
    if (backdrop) {
      backdrop.addEventListener("click", closeMobileSidebar);
    }

    // Close mobile drawer after navigating
    document.querySelectorAll(".sidebar-menu a[href]").forEach(function (a) {
      a.addEventListener("click", function () {
        var href = a.getAttribute("href") || "";
        if (!href || href === "#" || href.indexOf("javascript:") === 0) return;
        if (window.innerWidth <= 1024) closeMobileSidebar();   /* was 900 — 03 §1.3 ladder */
      });
    });

    // Section open/close — any parent with a .treeview-menu (not only .treeview)
    document.querySelectorAll(".sidebar-menu > li > a").forEach(function (a) {
      var li = a.parentElement;
      if (!isSectionLi(li)) return;
      a.addEventListener("click", function (e) {
        var href = a.getAttribute("href") || "";
        if (href && href !== "#" && href.indexOf("javascript:") !== 0) return;
        e.preventDefault();
        e.stopPropagation();
        var wasOpen = li.classList.contains("menu-open");
        document.querySelectorAll(".sidebar-menu > li.menu-open").forEach(function (other) {
          if (other !== li) closeSection(other);
        });
        if (wasOpen) closeSection(li);
        else openSection(li);
      });
    });

    // Sidebar search filter + empty state
    var search = document.getElementById("ipbSidebarSearch");
    var empty = document.getElementById("ipbSidebarEmpty");
    if (search) {
      search.addEventListener("input", function () {
        var q = search.value.trim().toLowerCase();
        var visible = 0;
        document.querySelectorAll(".sidebar-menu > li").forEach(function (li) {
          if (li.classList.contains("ipb-nav-section-li")) return;
          var text = (li.textContent || "").toLowerCase();
          var show = !q || text.indexOf(q) !== -1;
          li.style.display = show ? "" : "none";
          if (show) {
            visible++;
            if (q && li.classList.contains("treeview")) li.classList.add("menu-open");
          }
        });
        if (empty) empty.classList.toggle("show", !!q && visible === 0);
      });

      search.addEventListener("keydown", function (e) {
        if (e.key === "Escape") {
          search.value = "";
          search.dispatchEvent(new Event("input"));
          search.blur();
        }
      });
    }

    // Escape closes mobile sidebar
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape") closeMobileSidebar();
    });

    // Reset collapse state when resizing across breakpoint
    var lastMobile = window.innerWidth <= 1024;   /* was 900 — 03 §1.3 ladder */
    window.addEventListener("resize", function () {
      var mobile = window.innerWidth <= 1024;   /* was 900 — 03 §1.3 ladder */
      if (mobile !== lastMobile) {
        lastMobile = mobile;
        if (mobile) {
          bodyEl().classList.remove("ipb-sidebar-collapsed", "sidebar-collapse");
          closeMobileSidebar();
        } else {
          closeMobileSidebar();
          setCollapsed(localStorage.getItem(COLLAPSE_KEY) === "1");
        }
      }
    });
  }

  function collectPaletteItems() {
    var items = [];

    // Core customization actions (JSX Theme Studio parity)
    items.push({
      label: "Theme Studio",
      icon: "fa fa-palette",
      group: "Customize",
      action: "theme",
    });
    items.push({
      label: "Customize Dashboard",
      icon: "fa fa-sliders",
      group: "Customize",
      action: "dashboard",
    });
    items.push({
      label: "Toggle dark mode",
      icon: "fa fa-moon",
      group: "Customize",
      action: "dark",
    });

    document.querySelectorAll(".sidebar-menu a[href]").forEach(function (a) {
      var href = a.getAttribute("href") || "";
      if (!href || href === "#" || href.indexOf("javascript:") === 0) return;
      var label =
        a.getAttribute("data-palette-label") ||
        (a.querySelector("span") && a.querySelector("span").textContent) ||
        a.textContent;
      label = (label || "").replace(/\s+/g, " ").trim();
      if (!label) return;
      var iconEl = a.querySelector("i");
      items.push({
        label: label,
        href: href,
        icon: iconEl ? iconEl.className : "fa fa-circle",
        group: a.getAttribute("data-palette-group") || "",
      });
    });
    // de-dupe by href (actions have no href)
    var seen = {};
    return items.filter(function (it) {
      if (it.action) return true;
      if (!it.href || seen[it.href]) return false;
      seen[it.href] = true;
      return true;
    });
  }

  function initPalette() {
    var overlay = document.getElementById("ipbPalette");
    if (!overlay) return;
    var input = document.getElementById("ipbPaletteInput");
    var list = document.getElementById("ipbPaletteList");
    var activeIdx = 0;
    var results = [];

    var paletteReturnFocus = null; // 08 §7 — E4: restore focus to the trigger on close

    function close() {
      overlay.classList.remove("open");
      overlay.setAttribute("aria-hidden", "true");
      bodyEl().classList.remove("ipb-palette-open");
      if (input) input.value = "";
      if (paletteReturnFocus && typeof paletteReturnFocus.focus === "function") {
        paletteReturnFocus.focus();
      }
      paletteReturnFocus = null;
    }

    function open() {
      paletteReturnFocus = document.activeElement;
      overlay.classList.add("open");
      overlay.setAttribute("aria-hidden", "false");
      bodyEl().classList.add("ipb-palette-open");
      render("");
      setTimeout(function () {
        if (input) input.focus();
      }, 30);
    }

    /* 08 §7 — E4: Tab must cycle inside the dialog, never leak to the page
       behind. The palette's list items are non-focusable divs (only the
       search input is a real tab stop today), so trapping means keeping
       focus pinned to that one control rather than letting it walk off the
       overlay onto whatever the page happens to render next in DOM order. */
    overlay.addEventListener("keydown", function (e) {
      if (e.key !== "Tab") return;
      e.preventDefault();
      if (input) input.focus();
    });

    /* 06 §2.3 — persist the last-run item so an empty query can lead with
       Recent, instead of an arbitrary first-ten. */
    function pushRecent(item) {
      if (!item || !item.href) return;
      try {
        var k = "ipb_palette_recent";
        var arr = JSON.parse(localStorage.getItem(k) || "[]").filter(function (r) { return r.href !== item.href; });
        arr.unshift({ label: item.label, href: item.href, icon: item.icon });
        localStorage.setItem(k, JSON.stringify(arr.slice(0, 6)));
      } catch (e) { /* private mode / quota */ }
    }

    function render(q) {
      var all = collectPaletteItems();
      if (!q) {
        var recents = [];
        try { recents = JSON.parse(localStorage.getItem("ipb_palette_recent") || "[]"); } catch (e) { /* ignore */ }
        recents.forEach(function (r) { r.group = "Recent"; });
        var suggested = all.filter(function (i) {
          return /dashboard|customer|payment|ticket/i.test(i.label);
        }).slice(0, 4).map(function (i) { return Object.assign({}, i, { group: "Suggested" }); });
        results = recents.concat(suggested).concat(all).filter(function (it, i, self) {
          return it.action || !self.slice(0, i).some(function (p) { return p.href === it.href; });
        }).slice(0, 12);
      } else {
        results = all.filter(function (i) {
          return i.label.toLowerCase().indexOf(q.toLowerCase()) !== -1;
        });
      }
      activeIdx = 0;
      if (!list) return;
      if (!results.length) {
        list.innerHTML =
          '<div class="text-mut" style="text-align:center;padding:24px 0;font-size:13px">No matches</div>';
        return;
      }
      list.innerHTML = results
        .map(function (item, idx) {
          return (
            '<div class="ipb-palette-item' +
            (idx === 0 ? " active" : "") +
            '" data-idx="' +
            idx +
            '" role="option">' +
            '<i class="' +
            item.icon +
            '" aria-hidden="true"></i>' +
            "<span>" +
            item.label +
            "</span>" +
            (item.group ? '<span class="hint">' + item.group + "</span>" : "") +
            "</div>"
          );
        })
        .join("");
      list.querySelectorAll(".ipb-palette-item").forEach(function (el) {
        el.addEventListener("mouseenter", function () {
          activeIdx = parseInt(el.getAttribute("data-idx"), 10);
          highlight();
        });
        el.addEventListener("click", function () {
          run(results[parseInt(el.getAttribute("data-idx"), 10)]);
        });
      });
    }

    function highlight() {
      list.querySelectorAll(".ipb-palette-item").forEach(function (el, i) {
        el.classList.toggle("active", i === activeIdx);
      });
    }

    function run(item) {
      if (!item) return;
      pushRecent(item);
      close();
      if (item.action === "theme") {
        if (window.IpbCustomize && typeof window.IpbCustomize.openThemeStudio === "function") {
          window.IpbCustomize.openThemeStudio();
        } else {
          var themeBtn = document.querySelector("[data-ipb-open-theme]");
          if (themeBtn) themeBtn.click();
        }
        return;
      }
      if (item.action === "dashboard") {
        if (window.IpbCustomize && typeof window.IpbCustomize.openDashboardCustomize === "function") {
          window.IpbCustomize.openDashboardCustomize();
        }
        return;
      }
      if (item.action === "dark") {
        var darkBtn = document.getElementById("darkToggle");
        if (darkBtn) darkBtn.click();
        return;
      }
      if (item.href) window.location.href = item.href;
    }

    if (input) {
      input.addEventListener("input", function () {
        render(input.value.trim());
      });
      input.addEventListener("keydown", function (e) {
        if (e.key === "ArrowDown") {
          e.preventDefault();
          activeIdx = Math.min(activeIdx + 1, results.length - 1);
          highlight();
        } else if (e.key === "ArrowUp") {
          e.preventDefault();
          activeIdx = Math.max(activeIdx - 1, 0);
          highlight();
        } else if (e.key === "Enter") {
          e.preventDefault();
          run(results[activeIdx]);
        } else if (e.key === "Escape") {
          close();
        }
      });
    }

    overlay.addEventListener("click", function (e) {
      if (e.target === overlay) close();
    });

    document.querySelectorAll("[data-ipb-palette]").forEach(function (btn) {
      btn.addEventListener("click", function (e) {
        e.preventDefault();
        open();
      });
    });

    window.addEventListener("keydown", function (e) {
      if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === "k") {
        e.preventDefault();
        if (overlay.classList.contains("open")) close();
        else open();
      }
      if (e.key === "Escape" && overlay.classList.contains("open")) close();
    });
  }

  function enhancePaletteLabels() {
    document.querySelectorAll(".sidebar-menu a[href]").forEach(function (a) {
      if (a.getAttribute("data-palette-label")) return;
      var span = a.querySelector("span");
      var label = (span ? span.textContent : a.textContent) || "";
      label = label.replace(/\s+/g, " ").trim();
      if (label) a.setAttribute("data-palette-label", label);
    });
  }

  /**
   * Wrap bare tables so every list/form screen gets swipe + sticky columns.
   * Skips tiny key-value tables (≤3 columns) and print-only blocks.
   */
  function wrapBareTables() {
    var tables = document.querySelectorAll(
      ".content .box-body > table.table, .content form > table.table, .modal-body > table.table, .modal-body .box-body > table.table"
    );
    tables.forEach(function (table) {
      if (table.closest(".table-responsive, #printSection, [data-no-wrap]")) return;
      if (table.closest(".ipb-ticket-inbox, .ipb-auth")) return;
      var cols = table.querySelectorAll("thead th, tr:first-child th, tr:first-child td").length;
      if (cols > 0 && cols <= 3) return;
      var wrap = document.createElement("div");
      wrap.className = "table-responsive";
      table.parentNode.insertBefore(wrap, table);
      wrap.appendChild(table);
    });
  }

  /** Mark wide tables so mobile can show a swipe affordance. */
  function initTableScrollHints() {
    wrapBareTables();

    var nodes = document.querySelectorAll(
      ".content .table-responsive, .content .dataTables_wrapper, .content .box-body.table-responsive"
    );
    if (!nodes.length) return;

    /* 05 §9 — read-then-write split: compute every node's "wide" verdict first
       (all reads), THEN apply every classList.toggle (all writes). Interleaving
       read/write per-node forces a synchronous layout recalc between each one. */
    function updateAll(list) {
      var verdicts = [];
      list.forEach(function (el) {
        var scroller =
          el.classList.contains("dataTables_wrapper")
            ? el.querySelector(".dataTables_scrollBody") || el
            : el;
        verdicts.push(scroller.scrollWidth > scroller.clientWidth + 8);
      });
      list.forEach(function (el, i) {
        el.classList.toggle("is-scrollable", verdicts[i]);
      });
    }

    function refreshAll() {
      nodes = document.querySelectorAll(
        ".content .table-responsive, .content .dataTables_wrapper, .content .box-body.table-responsive"
      );
      nodes.forEach(function (el) {
        if (el.getAttribute("data-ipb-scroll-bound")) return;
        el.setAttribute("data-ipb-scroll-bound", "1");
        el.addEventListener(
          "scroll",
          function () {
            if (el.scrollLeft > 8) el.classList.remove("is-scrollable");
          },
          { passive: true }
        );
      });
      updateAll(nodes);
    }

    /* rAF-throttle the resize handler — was firing updateAll() unthrottled on
       every resize tick (layout thrash on drag/rotate, 05 §9). */
    var resizeQueued = false;
    function onResize() {
      if (resizeQueued) return;
      resizeQueued = true;
      window.requestAnimationFrame(function () {
        resizeQueued = false;
        refreshAll();
      });
    }

    refreshAll();
    window.addEventListener("resize", onResize);
    if (window.jQuery) {
      window.jQuery(document).on("draw.dt", function () {
        window.setTimeout(function () {
          wrapBareTables();
          refreshAll();
        }, 30);
      });
    }
    window.setTimeout(refreshAll, 200);
    window.setTimeout(refreshAll, 800);
  }

  /** Global DataTables defaults — loading / empty / error / search copy for all list screens. */
  function initDataTableDefaults() {
    if (!window.jQuery || !jQuery.fn.dataTable) return;

    var emptyHtml =
      '<div class="ipb-empty ipb-dt-empty">' +
      '<div class="ipb-empty-icon"><i class="fa fa-inbox" aria-hidden="true"></i></div>' +
      '<div class="ipb-empty-title">No records found</div>' +
      '<div class="ipb-empty-sub">Try adjusting filters or search.</div>' +
      "</div>";

    var loadingHtml =
      '<div class="ipb-dt-loading" role="status" aria-live="polite">' +
      '<span class="ipb-spinner ipb-spinner--sm" aria-hidden="true"></span>' +
      "<span>Loading…</span>" +
      "</div>";

    jQuery.extend(true, jQuery.fn.dataTable.defaults, {
      processing: false, // reverted per user request — the global .ipb-dt-loading /
      // dataTables_processing box was popping up on every table, every page,
      // on every sort/page/filter, and the user does not want it at all.
      language: {
        processing: loadingHtml,
        emptyTable: emptyHtml,
        zeroRecords: emptyHtml,
        search: "Search:",
        searchPlaceholder: "Search…",
        lengthMenu: "Show _MENU_",
        info: "Showing _START_ to _END_ of _TOTAL_",
        infoEmpty: "No records to show",
        infoFiltered: "(filtered from _MAX_ total)",
        paginate: {
          previous: "Prev",
          next: "Next",
        },
      },
      columnDefs: [
        {
          targets: "_all",
          defaultContent: "-",
        },
      ],
      /* NOTE: never put `ajax` in $.fn.dataTable.defaults. A truthy default `ajax`
         forces EVERY table into Ajax-source mode — so DOM-sourced tables (all the
         accounts + inventory lists render rows server-side) fired an XHR at the
         page URL, got HTML, and failed with "Could not load table data." Genuine
         serverSide tables pass their own `ajax:{url, error}` at init instead. */
    });
  }

  /** 04 §7a — window.IpbUI.btnLoading($btn, true|false): spinner + disable on any button. */
  function btnLoading($btn, on) {
    $btn = window.jQuery($btn);
    if (!$btn.length) return;
    if (on) {
      $btn.css("min-width", $btn.outerWidth() + "px") // lock width BEFORE hiding text
        .addClass("is-loading").attr("aria-busy", "true").prop("disabled", true);
    } else {
      $btn.removeClass("is-loading").removeAttr("aria-busy")
        .prop("disabled", false).css("min-width", "");
    }
  }
  window.IpbUI = window.IpbUI || {};
  window.IpbUI.btnLoading = btnLoading;

  /** 04 §9 — window.IpbUI.optimisticDelete($row, { url, token, label }): fade the row out
   *  immediately, defer the real DELETE behind a 5s undo window, restore via replaceWith(snapshot)
   *  if the deferred ajax call fails. */
  function optimisticDelete($row, opts) {
    var $ = window.jQuery;
    $row = $($row);
    var committed = false;
    var snapshot = $row.clone(true); // keep a copy to restore on undo
    $row.stop(true, true).fadeOut(160, function () { $(this).addClass('d-none'); });

    var timer = setTimeout(function () {
      committed = true;
      $.ajax({ url: opts.url, type: 'POST', data: opts.token || {} })
        .done(function () { $row.remove(); })
        .fail(function () {
          $row.replaceWith(snapshot); // server refused → put it back
          window.tata && tata.error('Failed', 'Could not delete ' + (opts.label || 'item') + '.');
        });
    }, 5000);

    // tata supports an action button; if your build doesn't, render a small inline "Undo" toast
    window.tata && tata.success('Deleted', (opts.label || 'Item') + ' removed. Undo?', {
      duration: 5000,
      onClick: function () {
        if (committed) return;
        clearTimeout(timer);
        $row.removeClass('d-none').stop(true, true).fadeIn(160);
      }
    });
  }
  window.IpbUI.optimisticDelete = optimisticDelete;

  /** 04 §7b — double-submit guard: any <form data-ipb-loading> spins + disables its submit
   *  button. Ajax forms call IpbUI.btnLoading($btn, false) in their always()/complete(). */
  if (window.jQuery) {
    jQuery(document).on("submit", "form[data-ipb-loading]", function () {
      var $btn = jQuery(this).find('[type="submit"]').first();
      if ($btn.data("ipbSubmitting")) return false; // block the second submit
      $btn.data("ipbSubmitting", 1);
      btnLoading($btn, true);
    });
  }

  /* ── 05 §4 — Tier 2 entrance choreography ──────────────────────────────── */

  /** Staggered row reveal (30-50ms/item). Call from DataTables drawCallback,
   *  or after any list render. Caps at 20 so a 500-row page doesn't cascade
   *  for 20 seconds. */
  function ipbStaggerRows(scope) {
    if (window.IpbMotion.reduced()) return;
    var rows = (scope || document).querySelectorAll(".ipb-stagger > tbody > tr");
    Array.prototype.forEach.call(rows, function (row, i) {
      if (i >= 20) { row.style.removeProperty("--i"); row.classList.remove("ipb-reveal"); return; }
      row.style.setProperty("--i", i);
      row.classList.add("ipb-reveal");
    });
  }

  /** KPI cascade on dashboard load — same mechanism, DOM-order index. */
  function ipbCascade(selector) {
    if (window.IpbMotion.reduced()) return;
    document.querySelectorAll(selector).forEach(function (el, i) {
      el.style.setProperty("--i", Math.min(i, 12));
      el.classList.add("ipb-reveal");
    });
  }

  /** KPI number count-up (reduced-motion jumps straight to final).
   *  <span data-count-to="182450" data-count-prefix="৳ " data-count-decimals="2">. */
  function ipbCountUp(el) {
    var target = parseFloat(el.getAttribute("data-count-to"));
    if (isNaN(target)) return;
    var dp = parseInt(el.getAttribute("data-count-decimals") || "0", 10);
    var pre = el.getAttribute("data-count-prefix") || "";
    var post = el.getAttribute("data-count-suffix") || "";
    var fmt = function (v) {
      return pre + v.toLocaleString("en-US", { minimumFractionDigits: dp, maximumFractionDigits: dp }) + post;
    };
    if (window.IpbMotion.reduced()) { el.textContent = fmt(target); return; }
    var dur = 900, start = null;
    function step(ts) {
      if (start === null) start = ts;
      var p = Math.min((ts - start) / dur, 1);
      el.textContent = fmt(target * (1 - Math.pow(1 - p, 3))); // easeOutCubic
      if (p < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  }

  /** ApexCharts animation config sourced from the motion tokens; gated on
   *  reduced-motion. Use as: chart: { ..., animations: ipbChartMotion() }. */
  function ipbChartMotion() {
    if (window.IpbMotion.reduced()) return { enabled: false };
    var ms = function (name, fb) {
      var v = getComputedStyle(document.body).getPropertyValue(name).trim(); // ".28s"
      return v ? parseFloat(v) * 1000 : fb;
    };
    return {
      enabled: true, easing: "easeout",
      speed: ms("--dur-slow", 280),
      animateGradually: { enabled: true, delay: 40 },
      dynamicAnimation: { enabled: true, speed: ms("--dur", 180) },
    };
  }

  /** Short form of a number for a phone axis: 60000 -> "60k", 1250000 -> "1.3M".
   *  Dashboard money axes were formatted `parseFloat(val).toFixed(2)`, so a
   *  revenue axis read "60000.00" — six characters plus decimals of pure width,
   *  which on a ~400px card is a third of the plot. */
  function ipbCompactNumber(val) {
    var n = parseFloat(val);
    if (!isFinite(n)) return val;
    var abs = Math.abs(n);
    if (abs >= 1e6) return (n / 1e6).toFixed(abs >= 1e7 ? 0 : 1).replace(/\.0$/, "") + "M";
    if (abs >= 1e3) return (n / 1e3).toFixed(abs >= 1e4 ? 0 : 1).replace(/\.0$/, "") + "k";
    return String(Math.round(n * 100) / 100);
  }

  function ipbMergeDeep(base, extra) {
    var out = {}, k;
    for (k in base) if (Object.prototype.hasOwnProperty.call(base, k)) out[k] = base[k];
    for (k in extra) {
      if (!Object.prototype.hasOwnProperty.call(extra, k)) continue;
      var a = out[k], b = extra[k];
      var plain = function (v) { return v && typeof v === "object" && !Array.isArray(v); };
      out[k] = plain(a) && plain(b) ? ipbMergeDeep(a, b) : b;
    }
    return out;
  }

  /** Phone preset for an ApexCharts config — pass it as the chart's `responsive`.
   *
   *  Every dashboard chart was rendering its desktop config verbatim at 400px:
   *  a rotated y-axis title ("Transaction Amount") and 12px axis labels ate the
   *  card's width, and the top-right legend sat over the plot. Applied per chart
   *  rather than as a global window.Apex override so each chart keeps its own
   *  semantics — notably the y-axis formatter, which is NOT overridden here
   *  because several axes carry units (MB/GB, Kbps/Mbps, "k"). Charts whose
   *  formatter is the useless toFixed(2) opt into IpbUI.compactNumber by passing
   *  it in `overrides`.
   *
   *  kind: 'bar' | 'area' | 'line' (cartesian, default) | 'donut' | 'radial'.
   */
  function ipbChartResponsive(kind, overrides) {
    var opts;

    if (kind === "donut" || kind === "pie") {
      opts = {
        chart: { height: 230 },
        plotOptions: { pie: { donut: { size: "62%" } } },
        dataLabels: { style: { fontSize: "10px" } },
      };
    } else if (kind === "radial" || kind === "radialBar") {
      opts = {
        chart: { height: 210 },
        plotOptions: {
          radialBar: {
            dataLabels: { name: { fontSize: "12px" }, value: { fontSize: "19px" } },
          },
        },
      };
    } else {
      opts = {
        chart: { height: 230 },
        dataLabels: { enabled: false },
        stroke: { width: 2 },
        plotOptions: { bar: { columnWidth: "62%" } },
        grid: { padding: { left: 2, right: 8, top: 0, bottom: 0 } },
        legend: {
          position: "bottom",
          horizontalAlign: "center",
          fontSize: "11px",
          offsetY: 2,
          markers: { width: 8, height: 8 },
          itemMargin: { horizontal: 8, vertical: 0 },
        },
        xaxis: {
          title: { text: "" },   // "Months" under a phone-width axis is not worth the row
          labels: {
            rotate: -45,
            rotateAlways: false,
            hideOverlappingLabels: true,
            trim: true,
            style: { fontSize: "10px" },
          },
        },
        yaxis: {
          title: { text: "" },   // the rotated axis title is pure width on a phone
          tickAmount: 4,
          labels: { style: { fontSize: "10px" } },
        },
      };
    }

    return [{ breakpoint: 768, options: overrides ? ipbMergeDeep(opts, overrides) : opts }];
  }

  window.IpbUI.staggerRows = ipbStaggerRows;
  window.IpbUI.cascade = ipbCascade;
  window.IpbUI.countUp = ipbCountUp;
  window.IpbUI.chartMotion = ipbChartMotion;
  window.IpbUI.chartResponsive = ipbChartResponsive;
  window.IpbUI.compactNumber = ipbCompactNumber;

  /* ── 05 §7.2 — toast FLIP: when a toast closes, tata.js repositions the
     rest and they jump. Intercept the jump and turn it into a slide. Purely
     additive — no tata.js edits, observes .tata removal on <body>. */
  (function initToastFlip() {
    var body = document.body;
    if (!body || !window.MutationObserver) return;
    function snapshot() {
      var m = new Map();
      document.querySelectorAll(".tata").forEach(function (t) { m.set(t, t.getBoundingClientRect().top); });
      return m;
    }
    var before = snapshot();
    new MutationObserver(function (muts) {
      var removed = muts.some(function (mu) {
        return Array.prototype.some.call(mu.removedNodes, function (n) {
          return n.nodeType === 1 && n.classList && n.classList.contains("tata");
        });
      });
      if (!removed || window.IpbMotion.reduced()) { before = snapshot(); return; }
      document.querySelectorAll(".tata").forEach(function (t) { // "last" positions
        var prev = before.get(t); if (prev == null) return;
        var dy = prev - t.getBoundingClientRect().top; // invert
        if (!dy) return;
        t.style.transition = "none";
        t.style.transform = "translateY(" + dy + "px)";
        requestAnimationFrame(function () { // play
          t.style.transition = "transform var(--dur-fast) var(--ease-out)";
          t.style.transform = "";
        });
      });
      before = snapshot();
    }).observe(body, { childList: true });
  })();

  /* 05 §6.1 — optional polish: the modal appears to grow out of the button
     that opened it, by setting the dialog's transform-origin to the
     trigger's centre before the CSS scale/fade transition plays. */
  document.addEventListener("click", function (e) {
    var t = e.target.closest && e.target.closest('[data-toggle="modal"],[data-bs-toggle="modal"]');
    if (!t) return;
    var sel = t.getAttribute("data-target") || t.getAttribute("data-bs-target");
    if (!sel) return;
    var dlg = document.querySelector(sel + " .modal-dialog");
    if (!dlg) return;
    var r = t.getBoundingClientRect(), d = dlg.getBoundingClientRect();
    dlg.style.transformOrigin = (r.left + r.width / 2 - d.left) + "px " + (r.top + r.height / 2 - d.top) + "px";
  });

  /* 06 §1 — Network Pulse strip: ambient fleet-status poll + doubles as the
     page-load/AJAX progress bar. Degrades gracefully if /system/status
     doesn't exist yet (it doesn't, today) — stays a neutral-colored rail
     that still works as a load bar. */
  function initNetPulse() {
    var el = document.getElementById("ipbNetPulse");
    if (!el) return;

    /* --- ambient status poll (optional endpoint) --- */
    function poll() {
      var url = el.getAttribute("data-status-url");
      if (!window.fetch || !url) return;
      fetch(url, { headers: { Accept: "application/json" } })
        .then(function (r) { return r.ok ? r.json() : null; })
        .then(function (j) {
          if (!j) return;
          var s = (j.status || "unknown").toLowerCase();
          if (["up", "degraded", "down"].indexOf(s) === -1) s = "unknown";
          el.setAttribute("data-status", s);
          el.setAttribute("title", "Network: " + s);
        })
        .catch(function () { /* no endpoint yet — stay neutral */ });
    }
    poll();
    setInterval(poll, 60000);

    /* --- progress mode: fires on same-origin nav + jQuery AJAX --- */
    var timer = null, stuckGuard = null, p = 0;
    function start() {
      el.classList.add("is-loading"); p = 8;
      el.style.setProperty("--np-progress", p + "%");
      clearInterval(timer);
      timer = setInterval(function () {
        p = Math.min(p + (90 - p) * 0.12, 90);
        el.style.setProperty("--np-progress", p + "%");
      }, 200);

      /* Bug fix: `start()` fires on `beforeunload`, which — on a same-tab MPA
         nav — is immediately followed by the next page's own fresh load (this
         whole document, timers included, is torn down). But modern Chrome/
         Firefox/Safari can also park an unloading page in the back/forward
         cache (bfcache) instead of destroying it, freezing execution mid-
         "is-loading". Hit Back and the browser hands you that exact frozen
         DOM back — rail stuck at ~90% forever, looking permanently loading.
         The `pageshow`/`persisted` listener below is what actually detects
         and fixes that case, instantly. This timeout is only a last-resort
         backstop for whatever it doesn't catch, so it has to be long enough
         to never fire during an ordinary, still-in-flight navigation: the old
         page stays fully alive and rendered (timers running) until the new
         page's response arrives — this app has DataTables/report pages that
         can legitimately take several seconds — so a short guard here would
         hide the bar and read as "stopped loading" while the browser is still
         waiting on the real navigation. Kept bounded (rather than removed
         outright) only so a page that never gets its pageshow event for some
         other reason still self-heals eventually instead of staying stuck. */
      clearTimeout(stuckGuard);
      stuckGuard = setTimeout(done, 30000);
    }
    function done() {
      clearInterval(timer);
      clearTimeout(stuckGuard);
      el.style.setProperty("--np-progress", "100%");
      setTimeout(function () {
        el.classList.remove("is-loading");
        el.style.setProperty("--np-progress", "0%");
      }, 260);
    }
    window.addEventListener("beforeunload", start);
    /* The actual root cause: when this page IS restored from bfcache instead
       of reloaded fresh, `pageshow` fires with `event.persisted === true` —
       the one reliable signal that we're looking at the frozen "is-loading"
       DOM from just before the user navigated away, not a fresh load. Clear
       it immediately rather than waiting out the safety-net timeout above. */
    window.addEventListener("pageshow", function (e) {
      if (e.persisted) done();
    });
    if (window.jQuery) {
      window.jQuery(document).ajaxStart(start).ajaxStop(done);
    }
    window.IpbNetPulse = { start: start, done: done, poll: poll };
  }

  /* Defensive CSRF header on every same-origin mutating ajax request.
     Config/Security.php enforces CSRF globally (except api/*, the payment
     gateway callbacks and ai-chat), and CI4 accepts the token via the
     X-CSRF-TOKEN header when it is absent from the POST body. Most views
     already inline the token into their ajax data; this covers the ones that
     forget, which would otherwise fail with a bare 403 and no message.
     Restricted to same-origin so the session token is never leaked to a
     third-party host, and skipped for GET/HEAD, which CI4 does not check. */
  function initAjaxCsrf() {
    if (!window.jQuery) return;
    var meta = document.querySelector('meta[name="csrf-token"]');
    var headerMeta = document.querySelector('meta[name="csrf-header"]');
    if (!meta) return;
    var headerName = (headerMeta && headerMeta.getAttribute("content")) || "X-CSRF-TOKEN";

    function sameOrigin(url) {
      if (!url) return true; // relative to the current page
      if (/^https?:\/\//i.test(url)) {
        return url.indexOf(window.location.origin + "/") === 0 ||
               url === window.location.origin;
      }
      return url.indexOf("//") !== 0; // reject protocol-relative //evil.com
    }

    jQuery(document).ajaxSend(function (e, xhr, settings) {
      if (/^(GET|HEAD|OPTIONS|TRACE)$/i.test(settings.type || "GET")) return;
      if (!sameOrigin(settings.url)) return;
      xhr.setRequestHeader(headerName, meta.getAttribute("content") || "");
    });
  }

  if (window.jQuery) {
    jQuery(initDataTableDefaults);
    initAjaxCsrf();
  }

  /* Exposed for ipb-nav.js: after a partial-nav content swap, re-derive the
     sidebar active item/open sections/breadcrumbs and the rail position, and
     re-scan the freshly-swapped content for wide tables. Deliberately does
     NOT touch theme/palette/net-pulse — those are page-independent globals,
     already correct, and re-running them would just be wasted work (or, for
     the rail, an unwanted extra animation). */
  window.IpbSaas = window.IpbSaas || {};
  window.IpbSaas.afterContentSwap = function () {
    syncSidebarActiveFromUrl();
    if (window.IpbNavIndicator && typeof window.IpbNavIndicator.sync === "function") {
      window.IpbNavIndicator.sync();
    }
    initTableScrollHints();
  };

  document.addEventListener("DOMContentLoaded", function () {
    if (!bodyEl() || !bodyEl().classList.contains("ipb")) return;
    initDataTableDefaults();
    initTheme();
    initThemeObserver();
    initSidebar();
    enhancePaletteLabels();
    initPalette();
    initTableScrollHints();
    initNetPulse();
  });
})();
