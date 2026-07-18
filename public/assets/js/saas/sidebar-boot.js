/**
 * Sidebar pre-paint boot.
 *
 * This is a MPA: every navigation reparses the shell, and the sidebar ships from
 * PHP fully closed (every `<ul class="treeview-menu" style="display:none">`) and
 * scrolled to the top. saas.js used to open the active section and scroll it into
 * view from its DOMContentLoaded handler — i.e. AFTER the browser had already
 * painted the closed, top-scrolled sidebar. That repaint is the "flip" you see on
 * every page change.
 *
 * So this file runs as a parser-blocking <script> placed immediately after the
 * sidebar markup (layout/sidebar.php). At that point the sidebar nodes exist but
 * nothing has been painted yet, so opening the active section and restoring the
 * scroll position here costs one extra layout and zero flicker.
 *
 * No jQuery: it runs long before the vendor bundles at the end of <body>.
 * saas.js reuses sync() rather than re-deriving the active item — re-deriving is
 * what made the section visibly collapse and re-open.
 */
(function (window, document) {
  "use strict";

  var SCROLL_KEY = "ipb_sidebar_scroll";

  function normalizeMenuPath(href) {
    try {
      var u = new URL(href, window.location.origin);
      var path = (u.pathname || "")
        .replace(/\/index\.php/gi, "")
        .replace(/\/+$/, "");
      return (path || "/").toLowerCase();
    } catch (e) {
      return "";
    }
  }

  function sectionMenu(li) {
    return li ? li.querySelector(":scope > .treeview-menu") : null;
  }

  function isSectionLi(li) {
    return !!sectionMenu(li);
  }

  function openSection(li) {
    if (!li || !isSectionLi(li)) return;
    li.classList.add("menu-open");
    var menu = sectionMenu(li);
    // Clears the server-rendered inline `display:none`; CSS owns it from here.
    if (menu) menu.style.removeProperty("display");
  }

  function openAncestorSections(li) {
    var node = li;
    while (node && node !== document.body) {
      if (node.matches && node.matches("li") && isSectionLi(node)) openSection(node);
      node = node.parentElement ? node.parentElement.closest("li") : null;
    }
  }

  /** The <li> one hop below `.sidebar-menu` itself — for a top-level link this
   *  IS the active li; for a nested treeview-menu link it's the enclosing
   *  section (e.g. "Customers"). Both the sliding nav-indicator and the
   *  quiet-tint active style key off `.sidebar-menu > li.active`, so a nested
   *  active page needs its top-level ancestor marked too, or neither ever
   *  fires for it. */
  function findTopLevelLi(menuRoot, li) {
    var node = li;
    while (node && node.parentElement && node.parentElement !== menuRoot) {
      node = node.parentElement.closest("li");
    }
    return node;
  }

  /** Mark the active item for the current URL and open its section(s).
   *  Idempotent, and only touches what actually changes — never strips the
   *  classes first, because removing then re-adding them IS a repaint.
   *  @returns {Element|null} the active <li>, if any. */
  function sync() {
    var menuRoot = document.querySelector(".sidebar-menu");
    if (!menuRoot) return null;

    var current = normalizeMenuPath(window.location.href);
    var best = null;
    var bestLen = -1;

    var links = menuRoot.querySelectorAll("a[href]");
    for (var i = 0; i < links.length; i++) {
      var href = links[i].getAttribute("href") || "";
      if (!href || href === "#" || href.indexOf("javascript:") === 0) continue;

      var path = normalizeMenuPath(href);
      if (!path || path === "/") continue;

      // Longest matching prefix wins: /customers/expired beats /customers.
      if ((current === path || current.indexOf(path + "/") === 0) && path.length > bestLen) {
        bestLen = path.length;
        best = links[i];
      }
    }

    // No URL match (a page reachable only from a non-menu link) — fall back to
    // whatever PHP marked active.
    var activeLi = best ? best.closest("li") : menuRoot.querySelector("li.active");
    if (!activeLi) return null;

    var topLi = findTopLevelLi(menuRoot, activeLi);

    if (best) {
      menuRoot.querySelectorAll("li.active").forEach(function (li) {
        if (li !== activeLi && li !== topLi) li.classList.remove("active");
      });
      activeLi.classList.add("active");
    }
    // Bug fix: this used to sit inside `if (best)`, so a page reached only via
    // a non-menu link (best === null, activeLi falls back to whatever PHP
    // already marked) never got its top-level ancestor tagged "active" — the
    // section header silently missed the quiet tint even though its nested
    // leaf was correctly marked. Additive only (never removes a class), so
    // it's safe to run in both branches.
    if (topLi) topLi.classList.add("active");

    openAncestorSections(activeLi);
    return activeLi;
  }

  function initScrollMemory(activeLi) {
    var scroller = document.querySelector(".main-sidebar .sidebar");
    if (!scroller) return;

    var saved = null;
    try {
      saved = sessionStorage.getItem(SCROLL_KEY);
    } catch (e) {}

    if (saved !== null) {
      // Restore where the user actually left the menu. Without this, every
      // navigation reset the sidebar to the top and saas.js then SMOOTH-scrolled
      // back down to the active item — an animated scroll on every page load.
      scroller.scrollTop = parseInt(saved, 10) || 0;
    } else if (activeLi && activeLi.scrollIntoView) {
      // First visit of the session: bring the active item into view instantly.
      activeLi.scrollIntoView({ block: "nearest" });
    }

    var ticking = false;
    scroller.addEventListener(
      "scroll",
      function () {
        if (ticking) return;
        ticking = true;
        window.requestAnimationFrame(function () {
          ticking = false;
          try {
            sessionStorage.setItem(SCROLL_KEY, String(scroller.scrollTop));
          } catch (e) {}
        });
      },
      { passive: true }
    );
  }

  var activeLi = sync();
  initScrollMemory(activeLi);

  window.IpbSidebarBoot = { sync: sync, openAncestorSections: openAncestorSections };
})(window, document);
