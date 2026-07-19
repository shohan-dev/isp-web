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
 * Anything that doesn't fit this path — forms, non-GET, off-site links, new
 * tab, downloads, logout, or any response that isn't the partial shape we
 * expect (session expired -> redirected to the login page, a 404/500, etc.)
 * — falls back to a real `location.href` navigation, i.e. exactly today's
 * behavior. That fallback is the safety net: nothing here can leave the user
 * stuck on a broken page.
 */
(function (window, document) {
  "use strict";

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

  /** Scripts arriving via fetch/DOMParser/importNode are inert by spec (never
   *  auto-execute). Re-creating each one with createElement + a copy of its
   *  attributes/text, then appending, is the standard, minimal way to force
   *  the browser to actually run it. */
  function execScripts(root) {
    if (!root) return;
    root.querySelectorAll("script").forEach(function (old) {
      var s = document.createElement("script");
      for (var i = 0; i < old.attributes.length; i++) {
        s.setAttribute(old.attributes[i].name, old.attributes[i].value);
      }
      s.text = old.textContent;
      document.body.appendChild(s);
    });
  }

  /** Adds any page-specific <link>/<style> the destination page carries in
   *  its own css section, skipping ones already present (by href / text) so
   *  repeat visits to the same page don't pile up duplicate tags. */
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

  /** @returns {boolean} true if `html` was our expected partial shape and got
   *  applied; false means the caller should fall back to a hard navigation.
   *
   *  `href`/`push` are threaded through so the URL is updated (pushState)
   *  BEFORE afterContentSwap() runs — IpbSidebarBoot.sync() and
   *  syncSidebarActiveFromUrl() both figure out "which link is active" by
   *  reading window.location.href, so if that still pointed at the page the
   *  user clicked FROM, the highlight/indicator never moved to the new item. */
  function applyPartial(html, href, push) {
    var mainEl = document.getElementById("ipb-main");
    if (!mainEl) return false;

    var doc = new DOMParser().parseFromString(html, "text/html");
    var contentTpl = doc.getElementById("ipb-nav-content");
    if (!contentTpl || !contentTpl.content) return false;

    mergeCss(doc.getElementById("ipb-nav-css") && doc.getElementById("ipb-nav-css").content);

    mainEl.innerHTML = "";
    mainEl.appendChild(document.importNode(contentTpl.content, true));

    var titleTpl = doc.getElementById("ipb-nav-title");
    var newTitle = titleTpl && titleTpl.getAttribute("data-title");
    if (newTitle) document.title = newTitle;

    var scriptTpl = doc.getElementById("ipb-nav-script");
    execScripts(scriptTpl && scriptTpl.content);
    execScripts(mainEl); // defensive: a stray <script> inside the content section itself

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

    fetch(href, {
      headers: { "X-IPB-Nav": "1" },
      credentials: "same-origin",
    })
      .then(function (resp) {
        if (!resp.ok) throw new Error("ipb-nav: non-OK response");
        return resp.text();
      })
      .then(function (html) {
        if (!applyPartial(html, href, push)) throw new Error("ipb-nav: unexpected response shape");
        if (window.IpbNetPulse && typeof window.IpbNetPulse.done === "function") {
          window.IpbNetPulse.done();
        }
      })
      .catch(function () {
        if (window.IpbNetPulse && typeof window.IpbNetPulse.done === "function") {
          window.IpbNetPulse.done();
        }
        // Safety net: session expiry, permission errors, a dropped connection,
        // or any shape we didn't expect all land here as a normal hard nav.
        window.location.href = href;
      });
  }

  ready(function () {
    if (!document.body.classList.contains("ipb")) return;
    if (!document.getElementById("ipb-main")) return;
    if (!window.fetch || !window.DOMParser || !window.history || !history.pushState) return;

    document.addEventListener("click", function (e) {
      if (e.defaultPrevented || e.button !== 0) return;
      if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return; // let "open in new tab" through

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
