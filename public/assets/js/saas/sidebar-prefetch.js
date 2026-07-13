/* Hover-to-prefetch for sidebar navigation. This is a traditional
   full-page-reload app (every sidebar click is a real browser navigation,
   not an SPA route swap) — that's an architecture choice, not a bug, and
   changing it is a much bigger project. This is the safe, additive win:
   start loading the destination page as soon as the user's cursor rests on
   a link, so by click time the response is often already in the browser's
   HTTP cache and the "reload" feels instant instead of a fresh round trip. */
(function () {
  "use strict";

  function ready(fn) {
    if (document.readyState !== "loading") fn();
    else document.addEventListener("DOMContentLoaded", fn);
  }

  ready(function () {
    if (!document.body.classList.contains("ipb")) return;

    // Respect data-saver / slow connections — don't burn their bandwidth
    // pre-loading pages they may never visit.
    var conn = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
    if (conn && (conn.saveData || /(^|-)2g$/.test(conn.effectiveType || ""))) return;

    var menu = document.querySelector(".sidebar-menu");
    if (!menu) return;

    var prefetched = new Set();
    var timers = new WeakMap();
    var DWELL_MS = 80; // ignore links the cursor just sweeps past

    function prefetch(href) {
      if (prefetched.has(href)) return;
      prefetched.add(href);
      var link = document.createElement("link");
      link.rel = "prefetch";
      link.href = href;
      document.head.appendChild(link);
    }

    function eligible(a) {
      if (a.hasAttribute("data-no-pin")) return false; // Logout — never prefetch, never GET-safe to warm
      var href = a.getAttribute("href") || "";
      if (!href || href === "#" || href.indexOf("javascript:") === 0) return false;
      if (/^([a-z]+:)?\/\//i.test(href)) return false; // off-site / absolute — nothing to warm locally
      return true;
    }

    menu.addEventListener(
      "mouseover",
      function (e) {
        var a = e.target.closest("a[href]");
        if (!a || !menu.contains(a) || !eligible(a)) return;
        if (timers.has(a)) return;
        var t = setTimeout(function () {
          timers.delete(a);
          prefetch(a.getAttribute("href"));
        }, DWELL_MS);
        timers.set(a, t);
      },
      true
    );

    menu.addEventListener(
      "mouseout",
      function (e) {
        var a = e.target.closest("a[href]");
        if (!a || !timers.has(a)) return;
        clearTimeout(timers.get(a));
        timers.delete(a);
      },
      true
    );

    // Touch devices have no hover — warm on the earliest touch signal instead,
    // well before the click actually fires.
    menu.addEventListener(
      "touchstart",
      function (e) {
        var a = e.target.closest("a[href]");
        if (a && eligible(a)) prefetch(a.getAttribute("href"));
      },
      { passive: true, capture: true }
    );
  });
})();
