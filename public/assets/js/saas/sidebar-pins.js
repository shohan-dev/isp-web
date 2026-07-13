/* Sidebar quick-access pins. Adds a pin toggle to every real link inside
   .sidebar-menu (top-level and nested treeview items), keeps a "Pinned"
   section at the top of the menu in sync, and persists via
   route.sidebar.pins.toggle. Self-contained — no changes needed to the
   large, role-gated sidebar.php template to make an item pinnable. */
(function () {
  "use strict";

  function ready(fn) {
    if (document.readyState !== "loading") fn();
    else document.addEventListener("DOMContentLoaded", fn);
  }

  ready(function () {
    if (!document.body.classList.contains("ipb")) return;
    if (!window.jQuery) return;

    var menu = document.querySelector(".sidebar-menu");
    var dataEl = document.getElementById("ipbSidebarPinData");
    if (!menu || !dataEl) return;

    var data = { toggleUrl: "", pinnedKeys: [], maxPins: 12 };
    try {
      var parsed = JSON.parse(dataEl.textContent || "{}");
      if (parsed && typeof parsed === "object") {
        data.toggleUrl = parsed.toggleUrl || "";
        data.pinnedKeys = Array.isArray(parsed.pinnedKeys) ? parsed.pinnedKeys : [];
        data.maxPins = parsed.maxPins || 12;
      }
    } catch (e) {
      return;
    }
    if (!data.toggleUrl) return; // not logged in — feature inert

    var pinned = new Set(data.pinnedKeys);

    function pinKeyFromHref(href) {
      try {
        return new URL(href, window.location.origin).pathname.replace(/\/+$/, "") || "/";
      } catch (e) {
        return href;
      }
    }

    function extractLabel(anchor) {
      var span = anchor.querySelector(":scope > span");
      if (span && span.textContent.trim()) return span.textContent.trim();
      var clone = anchor.cloneNode(true);
      clone.querySelectorAll("i, .pull-right-container, .ipb-pin-btn").forEach(function (n) {
        n.remove();
      });
      return (clone.textContent || "").trim();
    }

    function extractIcon(anchor) {
      var icon = anchor.querySelector(":scope > i");
      if (!icon) return "";
      for (var i = 0; i < icon.classList.length; i++) {
        var c = icon.classList[i];
        if (/^fa-/.test(c) && c !== "fa-angle-down" && c !== "fa-angle-left") return c;
      }
      return "";
    }

    function makePinButton(pinKey) {
      var btn = document.createElement("button");
      btn.type = "button";
      btn.className = "ipb-pin-btn";
      var isPinned = pinned.has(pinKey);
      btn.classList.toggle("is-pinned", isPinned);
      btn.setAttribute("aria-pressed", isPinned ? "true" : "false");
      btn.title = isPinned ? "Unpin from sidebar" : "Pin to sidebar";

      var icon = document.createElement("i");
      icon.className = "fa fa-thumbtack";
      icon.setAttribute("aria-hidden", "true");
      btn.appendChild(icon);

      return btn;
    }

    function enhanceRow(li) {
      if (!li || li.hasAttribute("data-no-pin") || li.classList.contains("ipb-nav-section-li")) return;
      var anchor = li.querySelector(":scope > a[href]");
      if (!anchor) return;
      var href = anchor.getAttribute("href") || "";
      if (!href || href === "#") return; // treeview toggles, not real destinations
      if (anchor.querySelector(".ipb-pin-btn")) return; // already enhanced

      var pinKey = li.getAttribute("data-pin-key") || pinKeyFromHref(href);
      li.setAttribute("data-pin-key", pinKey);

      anchor.setAttribute("data-pin-href", href);
      anchor.setAttribute("data-pin-label", extractLabel(anchor));
      anchor.setAttribute("data-pin-icon", extractIcon(anchor));

      anchor.appendChild(makePinButton(pinKey));
    }

    menu.querySelectorAll("li:not([data-no-pin]) > a[href]").forEach(function (a) {
      enhanceRow(a.parentElement);
    });

    function buildPinnedRow(pinKey, label, icon, href) {
      var li = document.createElement("li");
      li.className = "ipb-pinned-item";
      li.setAttribute("data-pin-key", pinKey);

      var a = document.createElement("a");
      a.setAttribute("href", href);

      var i = document.createElement("i");
      i.className = "fa " + (icon || "fa-thumbtack");
      a.appendChild(i);

      var span = document.createElement("span");
      span.textContent = label;
      a.appendChild(span);

      li.appendChild(a);
      enhanceRow(li);
      return li;
    }

    function ensurePinnedSection() {
      var header = menu.querySelector(".ipb-nav-section-li[data-pin-header]");
      if (header) return header;

      header = document.createElement("li");
      header.className = "ipb-nav-section-li";
      header.setAttribute("aria-hidden", "true");
      header.setAttribute("data-pin-header", "1");

      var span = document.createElement("span");
      span.className = "ipb-nav-section";
      span.textContent = "Pinned";
      header.appendChild(span);

      var firstLi = menu.querySelector("li"); // Dashboard
      if (firstLi) firstLi.insertAdjacentElement("afterend", header);
      else menu.insertBefore(header, menu.firstChild);

      return header;
    }

    function removePinnedSectionIfEmpty() {
      var header = menu.querySelector(".ipb-nav-section-li[data-pin-header]");
      if (!header) return;
      var next = header.nextElementSibling;
      if (!next || !next.classList.contains("ipb-pinned-item")) header.remove();
    }

    function pinnedRowsFor(pinKey) {
      return Array.prototype.filter.call(menu.querySelectorAll(".ipb-pinned-item"), function (row) {
        return row.getAttribute("data-pin-key") === pinKey;
      });
    }

    function setPinnedState(pinKey, isPinned) {
      if (isPinned) pinned.add(pinKey);
      else pinned.delete(pinKey);

      Array.prototype.forEach.call(menu.querySelectorAll("li[data-pin-key]"), function (li) {
        if (li.getAttribute("data-pin-key") !== pinKey) return;
        var btn = li.querySelector(".ipb-pin-btn");
        if (!btn) return;
        btn.classList.toggle("is-pinned", isPinned);
        btn.setAttribute("aria-pressed", isPinned ? "true" : "false");
        btn.title = isPinned ? "Unpin from sidebar" : "Pin to sidebar";
      });
    }

    /* Capture phase: saas.js attaches its own click listener directly to
       every top-level ".sidebar-menu > li > a" (to close the mobile drawer
       on navigation). Since the pin button lives inside that <a>, a
       bubble-phase delegated listener here would fire AFTER that closer
       listener already ran. Capturing on the ancestor runs first and
       stopPropagation() there keeps the click from ever reaching it. */
    menu.addEventListener("click", function (e) {
      var btn = e.target.closest(".ipb-pin-btn");
      if (!btn) return;
      e.preventDefault();
      e.stopPropagation();
      if (btn.disabled) return;

      var li = btn.closest("li");
      var anchor = btn.closest("a");
      var pinKey = li.getAttribute("data-pin-key");
      var wasPinned = pinned.has(pinKey);
      var label = anchor.getAttribute("data-pin-label") || "";
      var icon = anchor.getAttribute("data-pin-icon") || "";
      var href = anchor.getAttribute("data-pin-href") || anchor.getAttribute("href");

      btn.disabled = true;

      jQuery
        .ajax({
          url: data.toggleUrl,
          type: "POST",
          dataType: "json",
          data: { pin_key: pinKey, label: label, icon: icon, href: href },
        })
        .done(function (json) {
          btn.disabled = false;
          if (!json || json.status !== "success") {
            var msg = (json && typeof json.response === "string" && json.response) || "Could not update pin.";
            if (window.tata) tata.error("Pin", msg);
            return;
          }

          var isPinned = !!(json.response && json.response.pinned);
          setPinnedState(pinKey, isPinned);

          if (isPinned && !wasPinned) {
            ensurePinnedSection();
            if (pinnedRowsFor(pinKey).length === 0) {
              var row = buildPinnedRow(pinKey, label, icon, href);
              menu.querySelector(".ipb-nav-section-li[data-pin-header]").insertAdjacentElement("afterend", row);
            }
          } else if (!isPinned && wasPinned) {
            pinnedRowsFor(pinKey).forEach(function (row) {
              row.remove();
            });
            removePinnedSectionIfEmpty();
          }
        })
        .fail(function (xhr) {
          btn.disabled = false;
          var msg = (xhr.responseJSON && typeof xhr.responseJSON.response === "string" && xhr.responseJSON.response)
            || "Could not update pin. Please try again.";
          if (window.tata) tata.error("Pin", msg);
        });
    }, true);
  });
})();
