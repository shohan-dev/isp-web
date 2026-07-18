/**
 * Dashboard Customize + Theme Studio (JSX parity)
 * Presentation-only; preferences in localStorage.
 * Shared contract with Flutter: docs/ui-ux-migration/SAAS-DESIGN-CONTRACT.md
 * Storage key `ipb_brand_theme` (hex primary/secondary, radius, density, fontScale, reduceMotion).
 */
(function () {
  "use strict";

  /* ── Theme core: bound, NOT copied ────────────────────────────────────────
     These all live in brand-boot.js, which runs before first paint (this file
     loads at the end of <body>, far too late to colour the sidebar). They used to
     be defined HERE, with main-layout.php carrying its own cut-down copy inline to
     cover the pre-paint window — and the two drifted: the inline copy set 5 CSS
     variables to applyBrandTheme()'s ~25, so every page painted the sidebar in the
     default navy and then switched it to the brand colour. Bindings, so there is
     exactly one implementation and it cannot drift again. */
  var IpbBrand = window.IpbBrand || {};
  var DEFAULT_THEME = IpbBrand.DEFAULT_THEME;
  var DARK_KEY = IpbBrand.DARK_KEY;
  var generateRamp = IpbBrand.generateRamp;
  var normalizeTheme = IpbBrand.normalizeTheme;
  var loadTheme = IpbBrand.loadTheme;
  var saveTheme = IpbBrand.saveTheme;
  var applyBrandTheme = IpbBrand.applyBrandTheme;

  var WIDGET_KEY_PREFIX = "ipb_dash_widgets_";
  /** Default-hidden widgets when user has no saved prefs for that dashboard */
  var DEFAULT_HIDDEN_BY_DASH = {
    admin: ["insights"],
  };

  var SIZE_SPAN = { third: 4, half: 6, twoThird: 8, full: 12 };
  var SIZE_LABEL = {
    third: "1/3 width",
    half: "1/2 width",
    twoThird: "2/3 width",
    full: "Full width",
  };
  var SIZE_ORDER = ["third", "half", "twoThird", "full"];

  var THEME_PRESETS = [
    { name: "Signal Orange", primary: "#f75803", secondary: "#001f55", radius: "12" },
    { name: "Corporate Blue", primary: "#2563eb", secondary: "#0f172a", radius: "10" },
    { name: "Emerald Business", primary: "#059669", secondary: "#0f2e26", radius: "10" },
    { name: "Purple AI", primary: "#7c3aed", secondary: "#1e1b3a", radius: "14" },
    { name: "Sunset", primary: "#f43f5e", secondary: "#3f1d2b", radius: "16" },
    { name: "Midnight", primary: "#38bdf8", secondary: "#0b1220", radius: "8" },
    { name: "Minimal Gray", primary: "#334155", secondary: "#0f172a", radius: "6" },
    { name: "Executive Navy", primary: "#1d4ed8", secondary: "#001233", radius: "8" },
    { name: "Cyber", primary: "#22d3ee", secondary: "#111827", radius: "6" },
  ];

  /** Active dashboard controller (one per page) */
  var activeDash = null;
  /** Sync all Theme Studio UIs (drawer + page) */
  var themeSyncUi = null;

  function bodyEl() {
    return document.body;
  }

  function toast(msg, title) {
    title = title || "Dashboard";
    try {
      if (window.tata && typeof window.tata.success === "function") {
        window.tata.success(title, msg, { position: "tr", duration: 2200 });
        return;
      }
      if (window.tata && typeof window.tata.text === "function") {
        window.tata.text(title, msg, { position: "tr", duration: 2200 });
      }
    } catch (e) {}
  }

  function isDarkMode() {
    var el = bodyEl();
    if (!el) return false;
    return el.getAttribute("data-theme") === "dark" || el.classList.contains("dark-mode");
  }

  function setDarkMode(on) {
    var el = bodyEl();
    if (!el) return;
    var mode = on ? "dark" : "light";
    try {
      localStorage.setItem(DARK_KEY, mode);
    } catch (e) {}
    el.setAttribute("data-theme", mode);
    el.classList.toggle("dark-mode", !!on);
    var btn = document.getElementById("darkToggle");
    if (btn) {
      btn.innerHTML = on
        ? '<i class="fa-solid fa-sun" aria-hidden="true"></i>'
        : '<i class="fa-solid fa-moon" aria-hidden="true"></i>';
      btn.setAttribute("aria-label", on ? "Switch to light mode" : "Switch to dark mode");
      btn.setAttribute("title", on ? "Light mode" : "Dark mode");
    }
  }

  var drawerReturnFocus = null; // 08 §7 — E4: restore focus to the trigger on close

  /* 08 §7 — E4: Tab/Shift+Tab cycles among the open drawer's own focusable
     elements only, never leaking to the page behind. Queried fresh on every
     keydown since a drawer's contents (Theme Studio, dashboard customize)
     can change while open. */
  function drawerFocusTrapHandler(e) {
    if (e.key !== "Tab") return;
    // Also traps the Areas page's own off-canvas tree drawer (.ipb-areas-tree-panel,
    // driven independently by areas.js) — same off-canvas-overlay shape as
    // .ipb-drawer-panel, just a separate implementation predating this component.
    var panel = document.querySelector(".ipb-drawer-panel.open, .ipb-areas-tree-panel.is-open");
    if (!panel) return;
    var focusable = panel.querySelectorAll(
      'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
    );
    if (!focusable.length) return;
    var first = focusable[0], last = focusable[focusable.length - 1];
    if (e.shiftKey && document.activeElement === first) {
      e.preventDefault();
      last.focus();
    } else if (!e.shiftKey && document.activeElement === last) {
      e.preventDefault();
      first.focus();
    }
  }
  document.addEventListener("keydown", drawerFocusTrapHandler);

  function openDrawer(panel) {
    var overlay = document.getElementById("ipbDrawerOverlay");
    if (!overlay || !panel) return;
    drawerReturnFocus = document.activeElement;
    document.querySelectorAll(".ipb-drawer-panel.open").forEach(function (p) {
      if (p !== panel) {
        p.classList.remove("open");
        p.setAttribute("aria-hidden", "true");
      }
    });
    overlay.classList.add("show");
    overlay.setAttribute("aria-hidden", "false");
    panel.classList.add("open");
    bodyEl().classList.add("ipb-drawer-open");
    panel.setAttribute("aria-hidden", "false");
    panel.setAttribute("aria-modal", "true");
    if (!panel.hasAttribute("role")) panel.setAttribute("role", "dialog");
    setTimeout(function () {
      var focusEl =
        panel.querySelector("[data-ipb-drawer-close]") ||
        panel.querySelector("button, input, [href]");
      if (focusEl && focusEl.focus) focusEl.focus();
    }, 200);
  }

  function closeDrawers() {
    var overlay = document.getElementById("ipbDrawerOverlay");
    if (overlay) {
      overlay.classList.remove("show");
      overlay.setAttribute("aria-hidden", "true");
    }
    document.querySelectorAll(".ipb-drawer-panel.open").forEach(function (p) {
      p.classList.remove("open");
      p.setAttribute("aria-hidden", "true");
    });
    bodyEl().classList.remove("ipb-drawer-open");
    if (drawerReturnFocus && typeof drawerReturnFocus.focus === "function") {
      drawerReturnFocus.focus();
    }
    drawerReturnFocus = null;
  }

  function readWidgetsFromDom(root) {
    var dashId = root.getAttribute("data-ipb-dashboard") || "default";
    var defaultHidden = DEFAULT_HIDDEN_BY_DASH[dashId] || [];
    var nodes = root.querySelectorAll("[data-ipb-dash-grid] > .ipb-widget[data-widget-id]");
    var list = [];
    nodes.forEach(function (node) {
      var size = node.getAttribute("data-size") || "full";
      if (SIZE_ORDER.indexOf(size) < 0) size = "full";
      var id = node.getAttribute("data-widget-id");
      var hiddenByDefault =
        node.classList.contains("is-hidden") ||
        node.getAttribute("data-default-hidden") === "1" ||
        defaultHidden.indexOf(id) >= 0;
      list.push({
        id: id,
        title: node.getAttribute("data-title") || id,
        icon: node.getAttribute("data-icon") || "fa fa-circle",
        size: size,
        visible: !hiddenByDefault,
      });
    });
    return list;
  }

  function cloneDefaults(defaults) {
    return defaults.map(function (w) {
      return Object.assign({}, w);
    });
  }

  function loadWidgets(dashId, defaults) {
    try {
      var raw = localStorage.getItem(WIDGET_KEY_PREFIX + dashId);
      if (!raw) {
        return cloneDefaults(defaults);
      }
      var saved = JSON.parse(raw);
      if (!Array.isArray(saved) || !saved.length) {
        return cloneDefaults(defaults);
      }
      var byId = {};
      defaults.forEach(function (w) {
        byId[w.id] = Object.assign({}, w);
      });
      var ordered = [];
      saved.forEach(function (s) {
        if (!byId[s.id]) return;
        ordered.push({
          id: s.id,
          title: byId[s.id].title,
          icon: byId[s.id].icon,
          size: SIZE_ORDER.indexOf(s.size) >= 0 ? s.size : byId[s.id].size,
          visible: s.visible !== false,
        });
        delete byId[s.id];
      });
      Object.keys(byId).forEach(function (id) {
        ordered.push(byId[id]);
      });
      return ordered;
    } catch (e) {
      return cloneDefaults(defaults);
    }
  }

  function clearUserDashboardPrefs() {
    try {
      var keys = [];
      for (var i = 0; i < localStorage.length; i++) {
        var key = localStorage.key(i);
        if (key && key.indexOf(WIDGET_KEY_PREFIX) === 0) {
          keys.push(key);
        }
      }
      keys.forEach(function (key) {
        localStorage.removeItem(key);
      });
    } catch (e) {}

    document.querySelectorAll("style[data-ipb-dash-boot]").forEach(function (el) {
      el.remove();
    });
  }

  function markDashReady(root) {
    if (!root) return;
    root.classList.add("ipb-dash-ready");
    document.querySelectorAll("style[data-ipb-dash-boot]").forEach(function (el) {
      el.remove();
    });
  }

  function saveWidgets(dashId, widgets) {
    try {
      localStorage.setItem(
        WIDGET_KEY_PREFIX + dashId,
        JSON.stringify(
          widgets.map(function (w) {
            return { id: w.id, size: w.size, visible: !!w.visible };
          })
        )
      );
    } catch (e) {}
  }

  function resizeCharts() {
    function tick() {
      try {
        window.dispatchEvent(new Event("resize"));
      } catch (e) {}
      try {
        if (window.Apex && window.Apex._chartInstances) {
          window.Apex._chartInstances.forEach(function (inst) {
            if (!inst) return;
            if (typeof inst.windowResizeHandler === "function") inst.windowResizeHandler();
            else if (typeof inst.resize === "function") inst.resize();
          });
        }
      } catch (e) {}
    }
    setTimeout(tick, 50);
    setTimeout(tick, 250);
  }

  function updateCustomizeButton(root, widgets) {
    var btn = root.querySelector("[data-ipb-open-customize]");
    if (!btn) return;
    var hidden = widgets.filter(function (w) {
      return !w.visible;
    }).length;
    var label = btn.querySelector("[data-label]");
    if (label) {
      label.textContent = hidden > 0 ? "Customize (" + hidden + " hidden)" : "Customize";
    }
  }

  function applyWidgets(root, widgets) {
    var grid = root.querySelector("[data-ipb-dash-grid]");
    if (!grid) {
      markDashReady(root);
      return;
    }
    var map = {};
    grid.querySelectorAll(".ipb-widget[data-widget-id]").forEach(function (node) {
      map[node.getAttribute("data-widget-id")] = node;
    });
    widgets.forEach(function (w) {
      var node = map[w.id];
      if (!node) return;
      var size = SIZE_ORDER.indexOf(w.size) >= 0 ? w.size : "full";
      var span = SIZE_SPAN[size] || 12;
      node.setAttribute("data-size", size);
      node.style.gridColumn = "span " + span;
      node.classList.toggle("is-hidden", !w.visible);
      grid.appendChild(node);
    });
    var visibleCount = widgets.filter(function (w) {
      return w.visible;
    }).length;
    var empty = root.querySelector("[data-ipb-dash-empty]");
    if (empty) empty.classList.toggle("show", visibleCount === 0);
    grid.style.display = visibleCount === 0 ? "none" : "";
    updateCustomizeButton(root, widgets);
    markDashReady(root);
    resizeCharts();
  }

  function renderCustomizeList(panel, widgets, onChange) {
    var body = panel.querySelector("[data-customize-list]");
    if (!body) return;
    var visible = widgets.filter(function (w) {
      return w.visible;
    });
    var hidden = widgets.filter(function (w) {
      return !w.visible;
    });

    function rowHtml(w, isVisible, idx, totalVisible) {
      return (
        '<div class="ipb-widget-row' +
        (isVisible ? "" : " is-hidden") +
        '" data-id="' +
        w.id +
        '">' +
        '<div class="ipb-widget-row-icon"><i class="' +
        w.icon +
        '" aria-hidden="true"></i></div>' +
        '<div class="ipb-widget-row-meta">' +
        '<div class="ipb-widget-row-title">' +
        w.title +
        "</div>" +
        (isVisible
          ? '<button type="button" class="ipb-widget-row-size" data-action="size">' +
            (SIZE_LABEL[w.size] || SIZE_LABEL.full) +
            " · tap to resize</button>"
          : "") +
        "</div>" +
        (isVisible
          ? '<div class="ipb-widget-stepper">' +
            '<button type="button" data-action="up" title="Move up" aria-label="Move up"' +
            (idx === 0 ? " disabled" : "") +
            '><i class="fa fa-chevron-up" aria-hidden="true"></i></button>' +
            '<div class="sep" aria-hidden="true"></div>' +
            '<button type="button" data-action="down" title="Move down" aria-label="Move down"' +
            (idx >= totalVisible - 1 ? " disabled" : "") +
            '><i class="fa fa-chevron-down" aria-hidden="true"></i></button>' +
            "</div>"
          : "") +
        '<label class="ipb-toggle" title="Show / hide">' +
        '<input type="checkbox" data-action="toggle"' +
        (w.visible ? " checked" : "") +
        ' aria-label="Toggle ' +
        w.title +
        '" />' +
        "<span></span>" +
        "</label>" +
        "</div>"
      );
    }

    var html = visible
      .map(function (w, i) {
        return rowHtml(w, true, i, visible.length);
      })
      .join("");

    if (hidden.length) {
      html +=
        '<div class="ipb-customize-section"><div class="ipb-customize-section-label">Hidden · ' +
        hidden.length +
        "</div>" +
        hidden
          .map(function (w) {
            return rowHtml(w, false, 0, 0);
          })
          .join("") +
        "</div>";
    }

    if (!visible.length && !hidden.length) {
      html =
        '<div class="text-mut" style="text-align:center;padding:24px 8px;font-size:13px">No widgets on this dashboard.</div>';
    }

    body.innerHTML = html;

    body.querySelectorAll(".ipb-widget-row").forEach(function (row) {
      var id = row.getAttribute("data-id");
      row.querySelectorAll("[data-action]").forEach(function (el) {
        var action = el.getAttribute("data-action");
        if (el.tagName === "INPUT") {
          el.addEventListener("change", function () {
            onChange(id, action, !!el.checked);
          });
        } else {
          el.addEventListener("click", function (e) {
            e.preventDefault();
            onChange(id, action);
          });
        }
      });
    });
  }

  function mutateWidgets(widgets, id, action, checked) {
    if (action === "toggle") {
      return widgets.map(function (w) {
        if (w.id !== id) return w;
        return Object.assign({}, w, {
          visible: typeof checked === "boolean" ? checked : !w.visible,
        });
      });
    }
    if (action === "size") {
      return widgets.map(function (w) {
        if (w.id !== id) return w;
        var idx = SIZE_ORDER.indexOf(w.size);
        if (idx < 0) idx = 3;
        var next = SIZE_ORDER[(idx + 1) % SIZE_ORDER.length];
        return Object.assign({}, w, { size: next });
      });
    }
    if (action === "up" || action === "down") {
      var dir = action === "up" ? -1 : 1;
      var visibleIds = widgets
        .filter(function (w) {
          return w.visible;
        })
        .map(function (w) {
          return w.id;
        });
      var vIdx = visibleIds.indexOf(id);
      var swapWith = visibleIds[vIdx + dir];
      if (!swapWith) return widgets;
      var a = widgets.findIndex(function (w) {
        return w.id === id;
      });
      var b = widgets.findIndex(function (w) {
        return w.id === swapWith;
      });
      var next = widgets.slice();
      var tmp = next[a];
      next[a] = next[b];
      next[b] = tmp;
      return next;
    }
    return widgets;
  }

  function initDashboard(root) {
    var dashId = root.getAttribute("data-ipb-dashboard") || "default";
    var defaults = readWidgetsFromDom(root);
    if (!defaults.length) {
      markDashReady(root);
      return;
    }

    var widgets = loadWidgets(dashId, defaults);
    applyWidgets(root, widgets);

    var panel = document.getElementById("ipbCustomizeDrawer");

    function refreshPanel() {
      if (!panel) return;
      renderCustomizeList(panel, widgets, function (id, action, checked) {
        widgets = mutateWidgets(widgets, id, action, checked);
        saveWidgets(dashId, widgets);
        applyWidgets(root, widgets);
        refreshPanel();
      });
    }

    function openCustomize() {
      refreshPanel();
      openDrawer(panel);
    }

    function reset() {
      widgets = cloneDefaults(defaults);
      try {
        localStorage.removeItem(WIDGET_KEY_PREFIX + dashId);
      } catch (e) {}
      applyWidgets(root, widgets);
      refreshPanel();
      toast("Dashboard reset to default", "Dashboard");
    }

    activeDash = {
      root: root,
      openCustomize: openCustomize,
      reset: reset,
      refreshPanel: refreshPanel,
    };

    var openBtn = root.querySelector("[data-ipb-open-customize]");
    var emptyBtn = root.querySelector("[data-ipb-open-customize-empty]");
    if (openBtn) openBtn.addEventListener("click", openCustomize);
    if (emptyBtn) emptyBtn.addEventListener("click", openCustomize);
  }

  function rampHtml(hex) {
    var ramp = generateRamp(hex, false);
    return Object.keys(ramp)
      .map(function (k) {
        return (
          '<span style="background:' +
          ramp[k] +
          '" title="' +
          k +
          ": " +
          ramp[k] +
          '"></span>'
        );
      })
      .join("");
  }

  function initThemeStudio() {
    var drawer = document.getElementById("ipbThemeDrawer");
    var roots = Array.prototype.slice.call(
      document.querySelectorAll("[data-theme-studio-root]")
    );
    if (!roots.length && !drawer) return;

    var theme = loadTheme();

    function syncRoot(root) {
      if (!root) return;
      var primaryColor = root.querySelector("[data-theme-primary-color]");
      var primaryHex = root.querySelector("[data-theme-primary-hex]");
      var secondaryColor = root.querySelector("[data-theme-secondary-color]");
      var secondaryHex = root.querySelector("[data-theme-secondary-hex]");
      var primaryRamp = root.querySelector("[data-theme-primary-ramp]");
      var secondaryRamp = root.querySelector("[data-theme-secondary-ramp]");
      var densityOpts = root.querySelector("[data-density-opts]");
      var tableDensityOpts = root.querySelector("[data-table-density-opts]");
      var fontOpts = root.querySelector("[data-font-opts]");
      var darkMode = root.querySelector("[data-theme-dark-mode]");
      var colorfulCards = root.querySelector("[data-theme-colorful-cards]");
      var sidebarCompact = root.querySelector("[data-theme-sidebar-compact]");
      var reduceMotion = root.querySelector("[data-theme-reduce-motion]");
      var presetsEl = root.querySelector("[data-theme-presets]");

      if (primaryColor) primaryColor.value = theme.primary;
      if (primaryHex) primaryHex.value = theme.primary;
      if (secondaryColor) secondaryColor.value = theme.secondary;
      if (secondaryHex) secondaryHex.value = theme.secondary;
      if (primaryRamp) primaryRamp.innerHTML = rampHtml(theme.primary);
      if (secondaryRamp) secondaryRamp.innerHTML = rampHtml(theme.secondary);

      root.querySelectorAll("[data-radius]").forEach(function (btn) {
        btn.classList.toggle("active", btn.getAttribute("data-radius") === String(theme.radius));
      });
      if (densityOpts) {
        densityOpts.querySelectorAll("[data-density]").forEach(function (btn) {
          btn.classList.toggle("active", btn.getAttribute("data-density") === theme.density);
        });
      }
      if (tableDensityOpts) {
        tableDensityOpts.querySelectorAll("[data-table-density]").forEach(function (btn) {
          btn.classList.toggle(
            "active",
            btn.getAttribute("data-table-density") === theme.tableDensity
          );
        });
      }
      if (fontOpts) {
        fontOpts.querySelectorAll("[data-font-scale]").forEach(function (btn) {
          btn.classList.toggle("active", btn.getAttribute("data-font-scale") === theme.fontScale);
        });
      }
      if (darkMode) darkMode.checked = isDarkMode();
      if (colorfulCards) colorfulCards.checked = theme.cardStyle !== "plain";
      if (sidebarCompact) sidebarCompact.checked = !!theme.sidebarCompact;
      if (reduceMotion) reduceMotion.checked = !!theme.reduceMotion;
      if (presetsEl) {
        presetsEl.querySelectorAll(".ipb-theme-preset").forEach(function (btn) {
          var active =
            btn.getAttribute("data-primary") === theme.primary &&
            btn.getAttribute("data-secondary") === theme.secondary;
          btn.classList.toggle("active", active);
          var badge = btn.querySelector(".ipb-theme-preset-active");
          if (badge) badge.style.display = active ? "" : "none";
        });
      }
    }

    function syncUi() {
      roots.forEach(syncRoot);
    }
    themeSyncUi = syncUi;

    function commit(next, msg) {
      theme = normalizeTheme(next);
      saveTheme(theme);
      applyBrandTheme(theme);
      syncUi();
      if (msg) toast(msg, "Theme Studio");
    }

    function bindColor(colorInput, hexInput, key) {
      if (colorInput) {
        colorInput.addEventListener("input", function () {
          var t = Object.assign({}, theme);
          t[key] = colorInput.value;
          commit(t);
        });
      }
      if (hexInput) {
        hexInput.addEventListener("input", function () {
          var v = hexInput.value.trim();
          if (!/^#[0-9a-fA-F]{0,6}$/.test(v)) return;
          if (v.length === 7) {
            var t = Object.assign({}, theme);
            t[key] = v;
            commit(t);
          }
        });
        hexInput.addEventListener("blur", function () {
          var v = hexInput.value.trim();
          if (/^#[0-9a-fA-F]{6}$/.test(v)) return;
          hexInput.value = theme[key];
        });
      }
    }

    function bindRoot(root) {
      if (!root || root.getAttribute("data-theme-bound") === "1") return;
      root.setAttribute("data-theme-bound", "1");

      bindColor(
        root.querySelector("[data-theme-primary-color]"),
        root.querySelector("[data-theme-primary-hex]"),
        "primary"
      );
      bindColor(
        root.querySelector("[data-theme-secondary-color]"),
        root.querySelector("[data-theme-secondary-hex]"),
        "secondary"
      );

      root.querySelectorAll("[data-radius]").forEach(function (btn) {
        btn.addEventListener("click", function () {
          commit(Object.assign({}, theme, { radius: btn.getAttribute("data-radius") }));
        });
      });

      var densityOpts = root.querySelector("[data-density-opts]");
      if (densityOpts) {
        densityOpts.querySelectorAll("[data-density]").forEach(function (btn) {
          btn.addEventListener("click", function () {
            commit(Object.assign({}, theme, { density: btn.getAttribute("data-density") }));
          });
        });
      }

      var tableDensityOpts = root.querySelector("[data-table-density-opts]");
      if (tableDensityOpts) {
        tableDensityOpts.querySelectorAll("[data-table-density]").forEach(function (btn) {
          btn.addEventListener("click", function () {
            commit(
              Object.assign({}, theme, {
                tableDensity: btn.getAttribute("data-table-density"),
              })
            );
          });
        });
      }

      var fontOpts = root.querySelector("[data-font-opts]");
      if (fontOpts) {
        fontOpts.querySelectorAll("[data-font-scale]").forEach(function (btn) {
          btn.addEventListener("click", function () {
            commit(Object.assign({}, theme, { fontScale: btn.getAttribute("data-font-scale") }));
          });
        });
      }

      var darkMode = root.querySelector("[data-theme-dark-mode]");
      if (darkMode) {
        darkMode.addEventListener("change", function () {
          setDarkMode(!!darkMode.checked);
          applyBrandTheme(theme);
          syncUi();
          toast(darkMode.checked ? "Dark mode on" : "Light mode on", "Theme Studio");
        });
      }

      var colorfulCards = root.querySelector("[data-theme-colorful-cards]");
      if (colorfulCards) {
        colorfulCards.addEventListener("change", function () {
          commit(
            Object.assign({}, theme, {
              cardStyle: colorfulCards.checked ? "tinted" : "plain",
            }),
            colorfulCards.checked ? "Colorful cards on" : "Colorful cards off"
          );
        });
      }

      var sidebarCompact = root.querySelector("[data-theme-sidebar-compact]");
      if (sidebarCompact) {
        sidebarCompact.addEventListener("change", function () {
          commit(Object.assign({}, theme, { sidebarCompact: !!sidebarCompact.checked }));
        });
      }

      var reduceMotion = root.querySelector("[data-theme-reduce-motion]");
      if (reduceMotion) {
        reduceMotion.addEventListener("change", function () {
          commit(Object.assign({}, theme, { reduceMotion: !!reduceMotion.checked }));
        });
      }

      function themeJsonString() {
        return JSON.stringify(theme, null, 2);
      }

      function applyThemeJson(raw, okMsg) {
        try {
          commit(normalizeTheme(JSON.parse(raw)), okMsg || "Theme imported");
          return true;
        } catch (e) {
          toast("Invalid theme JSON", "Theme Studio");
          return false;
        }
      }

      function downloadThemeFile() {
        var json = themeJsonString();
        var blob = new Blob([json], { type: "application/json;charset=utf-8" });
        var url = URL.createObjectURL(blob);
        var a = document.createElement("a");
        var stamp = new Date().toISOString().slice(0, 10);
        a.href = url;
        a.download = "ipb-brand-theme-" + stamp + ".json";
        document.body.appendChild(a);
        a.click();
        a.remove();
        setTimeout(function () {
          URL.revokeObjectURL(url);
        }, 500);
        toast("Theme JSON file downloaded", "Theme Studio");
      }

      function copyThemeJson() {
        var json = themeJsonString();
        if (navigator.clipboard && navigator.clipboard.writeText) {
          navigator.clipboard.writeText(json).then(
            function () {
              toast("Theme JSON copied", "Theme Studio");
            },
            function () {
              window.prompt("Copy theme JSON:", json);
            }
          );
        } else {
          window.prompt("Copy theme JSON:", json);
        }
      }

      var exportFileBtn = root.querySelector("[data-theme-export-file]");
      if (exportFileBtn) {
        exportFileBtn.addEventListener("click", downloadThemeFile);
      }

      // Back-compat: old data-theme-export also downloads the file
      var exportBtn = root.querySelector("[data-theme-export]");
      if (exportBtn) {
        exportBtn.addEventListener("click", downloadThemeFile);
      }

      var copyBtn = root.querySelector("[data-theme-copy]");
      if (copyBtn) {
        copyBtn.addEventListener("click", copyThemeJson);
      }

      var fileInput = root.querySelector("[data-theme-file-input]");
      var importFileBtn = root.querySelector("[data-theme-import-file]");
      if (importFileBtn && fileInput) {
        importFileBtn.addEventListener("click", function () {
          fileInput.value = "";
          fileInput.click();
        });
        fileInput.addEventListener("change", function () {
          var file = fileInput.files && fileInput.files[0];
          if (!file) return;
          var reader = new FileReader();
          reader.onload = function () {
            applyThemeJson(String(reader.result || ""), "Theme imported from file");
          };
          reader.onerror = function () {
            toast("Could not read file", "Theme Studio");
          };
          reader.readAsText(file);
        });
      }

      // Optional paste fallback if present in older markup
      var importBtn = root.querySelector("[data-theme-import]");
      if (importBtn) {
        importBtn.addEventListener("click", function () {
          var raw = window.prompt("Paste theme JSON:");
          if (!raw) return;
          applyThemeJson(raw, "Theme imported");
        });
      }

      var presetsEl = root.querySelector("[data-theme-presets]");
      if (presetsEl) {
        presetsEl.innerHTML = THEME_PRESETS.map(function (p) {
          return (
            '<button type="button" class="ipb-theme-preset" data-primary="' +
            p.primary +
            '" data-secondary="' +
            p.secondary +
            '" data-radius="' +
            p.radius +
            '">' +
            '<div class="ipb-theme-preset-swatches">' +
            '<i style="background:' +
            p.primary +
            '"></i>' +
            '<i style="background:' +
            p.secondary +
            '"></i>' +
            "</div>" +
            '<div class="ipb-theme-preset-name">' +
            p.name +
            "</div>" +
            '<span class="ipb-theme-preset-active" style="display:none">Active</span>' +
            "</button>"
          );
        }).join("");
        presetsEl.querySelectorAll(".ipb-theme-preset").forEach(function (btn) {
          btn.addEventListener("click", function () {
            var nameEl = btn.querySelector(".ipb-theme-preset-name");
            commit(
              Object.assign({}, theme, {
                primary: btn.getAttribute("data-primary"),
                secondary: btn.getAttribute("data-secondary"),
                radius: btn.getAttribute("data-radius"),
              }),
              'Applied "' + (nameEl ? nameEl.textContent : "theme") + '"'
            );
          });
        });
      }

      root.querySelectorAll("[data-theme-reset]").forEach(function (btn) {
        btn.addEventListener("click", function () {
          commit(Object.assign({}, DEFAULT_THEME), "Reset to default brand theme");
        });
      });
    }

    roots.forEach(bindRoot);

    /* Reset buttons outside panel roots (drawer footer, settings intro) */
    document.querySelectorAll("[data-theme-reset]").forEach(function (btn) {
      if (btn.closest("[data-theme-studio-root]")) return;
      if (btn.getAttribute("data-theme-bound") === "1") return;
      btn.setAttribute("data-theme-bound", "1");
      btn.addEventListener("click", function () {
        commit(Object.assign({}, DEFAULT_THEME), "Reset to default brand theme");
      });
    });

    document.querySelectorAll("[data-ipb-open-theme]").forEach(function (btn) {
      btn.addEventListener("click", function (e) {
        e.preventDefault();
        e.stopPropagation();
        syncUi();
        if (drawer) openDrawer(drawer);
      });
    });

    syncUi();
  }

  function isLogoutHref(href) {
    if (!href) return false;
    return /\/logout(?:\/|\?|#|$)/i.test(href) || /route\.logout/i.test(href);
  }

  function bindLogoutPrefClear() {
    document.addEventListener(
      "click",
      function (e) {
        var link = e.target && e.target.closest ? e.target.closest("a[href]") : null;
        if (!link) return;
        if (!isLogoutHref(link.getAttribute("href") || "")) return;
        clearUserDashboardPrefs();
      },
      true
    );
  }

  function initChrome() {
    var overlay = document.getElementById("ipbDrawerOverlay");
    if (overlay) {
      overlay.addEventListener("click", closeDrawers);
    }
    document.querySelectorAll("[data-ipb-drawer-close]").forEach(function (btn) {
      btn.addEventListener("click", function (e) {
        e.preventDefault();
        closeDrawers();
      });
    });

    var customizeReset = document.querySelector("[data-customize-reset]");
    if (customizeReset) {
      customizeReset.addEventListener("click", function (e) {
        e.preventDefault();
        if (activeDash && typeof activeDash.reset === "function") activeDash.reset();
      });
    }

    bindLogoutPrefClear();

    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape") closeDrawers();
    });

    var darkBtn = document.getElementById("darkToggle");
    if (darkBtn) {
      darkBtn.addEventListener("click", function () {
        setTimeout(function () {
          applyBrandTheme(loadTheme());
          if (typeof themeSyncUi === "function") themeSyncUi();
        }, 0);
      });
    }
  }

  function boot() {
    if (!bodyEl() || !bodyEl().classList.contains("ipb")) return;
    applyBrandTheme(loadTheme());
    initChrome();
    initThemeStudio();
    document.querySelectorAll("[data-ipb-dashboard]").forEach(initDashboard);

    /* Safety: never leave dashboard invisible if init fails */
    setTimeout(function () {
      document.querySelectorAll("[data-ipb-dashboard]:not(.ipb-dash-ready)").forEach(markDashReady);
    }, 1500);
  }

  applyBrandTheme(loadTheme());

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", boot);
  } else {
    boot();
  }

  function openThemeStudio() {
    var panel = document.getElementById("ipbThemeDrawer");
    if (!panel) return false;
    var openBtn = document.querySelector("[data-ipb-open-theme]");
    if (openBtn) openBtn.click();
    else openDrawer(panel);
    return true;
  }

  function openDashboardCustomize() {
    if (activeDash && typeof activeDash.openCustomize === "function") {
      activeDash.openCustomize();
      return true;
    }
    var btn =
      document.querySelector("[data-ipb-open-customize]") ||
      document.querySelector("[data-ipb-open-customize-empty]");
    if (btn) {
      btn.click();
      return true;
    }
    toast("Open a dashboard to customize widgets", "Dashboard");
    return false;
  }

  window.IpbCustomize = {
    applyBrandTheme: applyBrandTheme,
    loadTheme: loadTheme,
    openDrawer: openDrawer,
    closeDrawers: closeDrawers,
    openThemeStudio: openThemeStudio,
    openDashboardCustomize: openDashboardCustomize,
    clearUserDashboardPrefs: clearUserDashboardPrefs,
  };
})();

