/**
 * Brand theme boot — the ONLY place the theme is turned into CSS variables.
 *
 * Runs as a parser-blocking <script> immediately after <body> opens, before the
 * sidebar (or anything else) is painted.
 *
 * Why it exists: the tenant's brand lives in localStorage, so only JS can apply
 * it. customize.js — which loads at the END of <body> — used to be the thing that
 * applied it, painting every page in the DEFAULT navy first and then switching to
 * the brand colour once it ran. main-layout.php carried an inline "pre-paint"
 * approximation to cover that, but it set 5 CSS variables where customize.js sets
 * ~25: it never set --secondary-800 (half the sidebar's gradient) and it computed
 * --secondary-900 from the raw hex instead of the ramp, so it produced a DIFFERENT
 * colour than the real one. The sidebar therefore changed colour after paint on
 * every single navigation, whatever the inline script did.
 *
 * So: one implementation, here, running before paint. customize.js binds to these
 * functions (window.IpbBrand) instead of keeping a second copy — a second copy is
 * exactly how the two drifted apart in the first place.
 *
 * No dependencies: this runs before jQuery and before every other bundle.
 */
(function (window, document) {
  "use strict";

  var BRAND_KEY = "ipb_brand_theme";
  var DARK_KEY = "ipb_theme";
  var COLLAPSE_KEY = "ipb_sidebar_collapsed";

  var DEFAULT_THEME = {
    primary: "#f75803",
    secondary: "#1a0b38",
    radius: "12",
    density: "comfortable",
    tableDensity: "comfortable",
    fontScale: "md",
    sidebarCompact: false,
    reduceMotion: false,
    // Stat cards tinted with their category color at rest, not just on
    // hover — on by default per product decision; Theme Studio can flip
    // this to "plain" to restore the quieter hover-only accent.
    cardStyle: "tinted",
  };

  var LIGHT_STOPS = { 50: 96, 100: 91, 200: 82, 300: 70, 400: 58, 500: 47, 600: 39, 700: 32, 800: 26, 900: 21 };
  var DARK_STOPS = { 50: 17, 100: 22, 200: 29, 300: 37, 400: 46, 500: 56, 600: 66, 700: 75, 800: 84, 900: 91 };

  function bodyEl() {
    return document.body;
  }

  function hexToHsl(hex) {
    var h = (hex || "").replace("#", "");
    if (h.length === 3) h = h[0] + h[0] + h[1] + h[1] + h[2] + h[2];
    var r = parseInt(h.substring(0, 2), 16) / 255;
    var g = parseInt(h.substring(2, 4), 16) / 255;
    var b = parseInt(h.substring(4, 6), 16) / 255;
    var max = Math.max(r, g, b);
    var min = Math.min(r, g, b);
    var hh = 0;
    var s = 0;
    var l = (max + min) / 2;
    if (max !== min) {
      var d = max - min;
      s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
      if (max === r) hh = (g - b) / d + (g < b ? 6 : 0);
      else if (max === g) hh = (b - r) / d + 2;
      else hh = (r - g) / d + 4;
      hh /= 6;
    }
    return [hh * 360, s * 100, l * 100];
  }

  function hslToHex(h, s, l) {
    s /= 100;
    l /= 100;
    var k = function (n) {
      return (n + h / 30) % 12;
    };
    var a = s * Math.min(l, 1 - l);
    var f = function (n) {
      return l - a * Math.max(-1, Math.min(k(n) - 3, Math.min(9 - k(n), 1)));
    };
    var toHex = function (x) {
      return Math.round(255 * x)
        .toString(16)
        .padStart(2, "0");
    };
    return "#" + toHex(f(0)) + toHex(f(8)) + toHex(f(4));
  }

  function hexToRgbString(hex) {
    var h = (hex || "#f75803").replace("#", "");
    if (h.length === 3) h = h[0] + h[0] + h[1] + h[1] + h[2] + h[2];
    var r = parseInt(h.substring(0, 2), 16) || 0;
    var g = parseInt(h.substring(2, 4), 16) || 0;
    var b = parseInt(h.substring(4, 6), 16) || 0;
    return r + ", " + g + ", " + b;
  }

  function generateRamp(hex, dark) {
    var h = 20;
    var s = 90;
    try {
      var hsl = hexToHsl(hex);
      h = hsl[0];
      s = hsl[1];
    } catch (e) {}
    s = Math.min(Math.max(s, 40), 90);
    var stops = dark ? DARK_STOPS : LIGHT_STOPS;
    var ramp = {};
    Object.keys(stops).forEach(function (k) {
      ramp[k] = hslToHex(h, s, stops[k]);
    });
    return ramp;
  }

  function normalizeTheme(t) {
    t = t || {};
    var density = t.density === "compact" ? "compact" : "comfortable";
    var tableDensity = t.tableDensity === "compact" ? "compact" : "comfortable";
    var fontScale = t.fontScale === "sm" || t.fontScale === "lg" ? t.fontScale : "md";
    var cardStyle = t.cardStyle === "plain" ? "plain" : "tinted";
    return {
      primary: t.primary || DEFAULT_THEME.primary,
      secondary: t.secondary || DEFAULT_THEME.secondary,
      radius: String(t.radius || DEFAULT_THEME.radius),
      density: density,
      tableDensity: tableDensity,
      fontScale: fontScale,
      sidebarCompact: !!t.sidebarCompact,
      reduceMotion: !!t.reduceMotion,
      cardStyle: cardStyle,
    };
  }

  function loadTheme() {
    try {
      var raw = localStorage.getItem(BRAND_KEY);
      if (!raw) return Object.assign({}, DEFAULT_THEME);
      return normalizeTheme(JSON.parse(raw));
    } catch (e) {
      return Object.assign({}, DEFAULT_THEME);
    }
  }

  function saveTheme(theme) {
    try {
      localStorage.setItem(BRAND_KEY, JSON.stringify(theme));
    } catch (e) {}
  }

  function applyBrandTheme(theme) {
    var el = bodyEl();
    if (!el) return;
    theme = theme || loadTheme();
    var isDark = el.getAttribute("data-theme") === "dark" || el.classList.contains("dark-mode");
    var pL = generateRamp(theme.primary, false);
    var pD = generateRamp(theme.primary, true);
    var sL = generateRamp(theme.secondary, false);
    var sD = generateRamp(theme.secondary, true);
    var p = isDark ? pD : pL;
    var s = isDark ? sD : sL;
    var radius = parseInt(theme.radius || "12", 10) || 12;

    var set = function (k, v) {
      el.style.setProperty(k, v);
    };

    // The full ramp. --secondary-800 lives in here: it is half the sidebar's
    // background gradient, and the old inline pre-paint script never set it.
    [50, 100, 200, 300, 400, 500, 600, 700, 800, 900].forEach(function (stop) {
      set("--primary-" + stop, p[stop]);
      set("--secondary-" + stop, s[stop]);
    });

    /* Brand anchors — exact picks, not ramp midpoints */
    set("--primary-500", theme.primary);
    set("--primary-rgb", hexToRgbString(theme.primary));
    if (!isDark) {
      set("--secondary-700", theme.secondary);
      set("--secondary-900", sL[900] || theme.secondary);
      set("--shadow-brand", "0 1px 2px rgba(var(--primary-rgb), .15), 0 12px 24px -8px rgba(var(--primary-rgb), .35)");
      set("--focus-glow", "rgba(var(--primary-rgb), .16)");
    } else {
      set("--secondary-700", sD[700] || theme.secondary);
      set("--secondary-900", theme.secondary);
      set("--shadow-brand", "0 1px 2px rgba(var(--primary-rgb), .25), 0 14px 28px -8px rgba(var(--primary-rgb), .45)");
      set("--focus-glow", "rgba(var(--primary-rgb), .35)");
    }
    set("--radius", radius + "px");
    set("--radius-sm", Math.max(4, radius - 5) + "px");
    set("--radius-lg", radius + 5 + "px");
    set("--ipb-brand", theme.primary);

    el.setAttribute("data-density", theme.density || "comfortable");
    el.setAttribute("data-table-density", theme.tableDensity || "comfortable");
    el.setAttribute("data-font-scale", theme.fontScale || "md");
    el.setAttribute("data-card-style", theme.cardStyle || "tinted");
    el.classList.toggle("ipb-sidebar-pref-compact", !!theme.sidebarCompact);
    el.classList.toggle("ipb-reduce-motion", !!theme.reduceMotion);
  }

  /* ── Pre-paint restore ────────────────────────────────────────────────────
     Order matters: dark mode first, because applyBrandTheme() picks the light or
     dark ramp from the body's data-theme. */
  try {
    if (localStorage.getItem(DARK_KEY) === "dark") {
      document.body.setAttribute("data-theme", "dark");
      document.body.classList.add("dark-mode");
    }

    // Collapse only applies on desktop; mobile uses a separate drawer.
    if (window.innerWidth > 1024 && localStorage.getItem(COLLAPSE_KEY) === "1") {
      document.body.classList.add("ipb-sidebar-collapsed", "sidebar-collapse");
    }

    applyBrandTheme(loadTheme());
  } catch (e) {}

  window.IpbBrand = {
    BRAND_KEY: BRAND_KEY,
    DARK_KEY: DARK_KEY,
    DEFAULT_THEME: DEFAULT_THEME,
    generateRamp: generateRamp,
    normalizeTheme: normalizeTheme,
    loadTheme: loadTheme,
    saveTheme: saveTheme,
    applyBrandTheme: applyBrandTheme,
    hexToRgbString: hexToRgbString,
  };
})(window, document);
