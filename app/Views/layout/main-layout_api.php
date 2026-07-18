<?php
// Place these headers at the very top of the view file
header("Expires: Tue, 01 Jan 2000 00:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
?>

<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta content="width=device-width, initial-scale=1, viewport-fit=cover" name="viewport">

  <title>
    <?= (!empty($title) ? $title . ' | ' : null) . getSetting('app_name') . ' Dashboard Panel'; ?>
  </title>

  <?= renderBrandFaviconTags(); ?>

  <!-- Bootstrap -->
  <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap/css/bootstrap.min.css'); ?>">

  <!-- Font Awesome — 08 §10 / 07 F3 self-hosted (was cdnjs) -->
  <link rel="stylesheet" href="<?= base_url('assets/vendor/fontawesome/all.min.css'); ?>">

  <!-- Datatable -->
  <link rel="stylesheet" href="<?= base_url('assets/vendor/datatable/dataTables.min.css'); ?>">

  <!-- Select2 -->
  <link rel="stylesheet" href="<?= base_url('assets/vendor/select2/select2.min.css'); ?>">

  <!-- 08 §1 — stylesheet parity with main-layout.php: this shell used to load
       only 4 of the 11 saas stylesheets (no layout/overrides/ux/list-pages/
       customize/responsive/ai-chat), so any page rendered here had no
       sidebar/topbar styling, no responsive rules, no Select2 z-fix, no
       list-page polish — a visibly different product. Also re-pins
       components.css off its stale, independently-drifted ?v (was v16 here
       vs v20 in the main shell) via the 08 §5 filemtime() helper. -->
  <?= saas_css('tokens.css') ?>
  <?= saas_css('base.css') ?>
  <?= saas_css('components.css') ?>
  <?= saas_css('layout.css') ?>
  <?= saas_css('overrides.css') ?>
  <?= saas_css('ux.css') ?>
  <?= saas_css('list-pages.css') ?>
  <?= saas_css('customize.css') ?>
  <?= saas_css('responsive.css') ?>
  <?= saas_css('toast.css') ?>
  <?= saas_css('ai-chat.css') ?>

  <?= $this->renderSection('css'); ?>


  <style>
    .toggle-btn {
      background: rgba(255, 255, 255, 0.2);
      border: none;
      color: white;
      padding: 8px 14px;
      border-radius: 8px;
      cursor: pointer;
      font-size: 14px;
      transition: background 0.2s;
    }

    .toggle-btn:hover {
      background: rgba(255, 255, 255, 0.4);
    }

    .logo:hover {
      display: block;
      /* Makes the logo white */
      background-color: #282a35 !important;
    }

    .main-sidebar {
      height: 100vh;
      /* Full height of the viewport */
      position: fixed;
      top: 0;
      left: 0;
      overflow: hidden;
    }

    .tooltip {
      background-color: red;
      margin-top: -8px !important;
      margin-bottom: 1vw !important;
    }

    .bs-tooltip-top .tooltip-arrow {
      bottom: -4px !important;
    }


    .sidebar {
      height: 100%;
      overflow-y: auto !important;
      scrollbar-width: none;
    }

    .sidebar::-webkit-scrollbar {
      display: none;
      /* For Chrome, Safari, and Edge */
    }

    .d-flex {
      display: flex !important;
      flex-direction: row !important;
      gap: 10px !important;
    }

    .treeview a {
      color: black;
      display: block;
    }

    .treeview a:hover {
      color: white;
      background-color: black;
    }

    .treeview-menu a {
      color: black;
      background-color: whitesmoke;
    }

    .treeview-menu a:hover {
      color: white;
      background-color: black;
    }

    .treeview.active>a {
      color: black;
      background-color: #e9ecef;
    }

    .dropdown-toggle .fa-angle-down {
      transition: transform 0.3s;
    }

    .treeview.active>a .fa-angle-down {
      transform: rotate(180deg);
    }


    .VIpgJd-ZVi9od-ORHb-OEVmcd {
      display: none !important
    }

    /* .goog-te-gadget span.VIpgJd-ZVi9od-l4eHX-hSRGPd {
    display: none !important;
} */
    /* .skiptranslate {
  display: none !important;
} */


    .goog-te-gadget span {
      display: none !important;

    }

    #google_translate_element img {
      display: none;
      /* Hide the original image */
    }

    #google_translate_element .goog-te-gadget-simple {
      background-image: url('<?= base_url('assets/img/icon/Google_Translate.png'); ?>');
      /* Use PHP to insert the image URL */
      background-size: contain;
      /* Adjust to contain so the image fits without cropping */
      background-position: center;
      /* Center the image */
      background-repeat: no-repeat;
      /* Prevent the image from repeating */
      padding-left: 30px;
      /* Space for text */
      height: 28px;
      /* Adjust height to fit the image */
      margin-right: 5px;
      display: flex;
      align-items: center;
      border: none;
      background-color: transparent;
      /* Set background to transparent */
    }



    /* Container for the toggle */
    .toggle-switch {
      position: relative;
      display: inline-block;
      width: 44px;
      /* Increased width */
      height: 20px;
      /* Height stays the same */
      vertical-align: middle;
      margin: auto;
    }

    /* Hide default checkbox */
    .toggle-switch input {
      opacity: 0;
      width: 0;
      height: 0;
    }

    /* The track */
    .slider {
      position: absolute;
      cursor: pointer;
      background-color: #ccc;
      border-radius: 20px;
      width: 100%;
      height: 100%;
      transition: background-color 0.3s;
    }

    /* The circular knob */
    .slider::before {
      content: "";
      position: absolute;
      height: 16px;
      width: 16px;
      left: 2px;
      bottom: 2px;
      background-color: white;
      border-radius: 50%;
      transition: transform 0.3s;
    }

    /* Toggled state */
    .toggle-switch input:checked+.slider {
      background-color: #4caf50;
    }

    .toggle-switch input:checked+.slider::before {
      transform: translateX(24px);
      /* Adjusted to new width */
    }


    .navbar-role-display {
      position: absolute;
      top: 50%;
      padding-left: 40px;
      transform: translate(0%, -50%);
      font-weight: bold;
      font-size: 16px;
      color: #fff;
      white-space: nowrap;
      pointer-events: none;
      z-index: 0;
    }

    @media (max-width: 1024px) {
      .navbar-role-display {
        left: calc(10px);
        font-size: 14px;
      }
    }

    @media (max-width: 800px) {
      .navbar-role-display {
        left: calc(10px);
        font-size: 12px;
      }
    }

    @media (max-width: 600px) {
      .navbar-role-display {
        display: none;
      }
    }
  </style>


  <meta name="csrf-token" content="<?= csrf_hash() ?>">
  <meta name="csrf-header" content="<?= csrf_header() ?>">

</head>

<body class="ipb" data-theme="light">
  <div class="wrapper" style="margin:0">




    <?= $this->renderSection('content'); ?>


  </div>
  <!-- ./wrapper -->

  <!-- jQuery -->
  <script src="<?= base_url('assets/vendor/jquery/jquery.min.js'); ?>"></script>

  <!-- Bootstrap -->
  <script src="<?= base_url('assets/vendor/bootstrap/js/bootstrap.min.js'); ?>"></script>

  <!-- Datatable -->
  <script src="<?= base_url('assets/vendor/datatable/dataTables.min.js'); ?>"></script>
  <script src="<?= base_url('assets/vendor/datatable/dataTables.bootstrap.min.js'); ?>"></script>

  <!-- TataJs -->
  <script src="<?= base_url('assets/vendor/tatajs/tata.js'); ?>"></script>
  <?= saas_js('toast.js') ?>

  <!-- Select2 -->
  <script src="<?= base_url('assets/vendor/select2/select2.full.min.js'); ?>"></script>

  <!-- ChartJS -->
  <script src="<?= base_url('assets/vendor/apexcharts/apexcharts.min.js'); ?>"></script>

  <!-- Sweet Alert -->
  <script src="<?= base_url('assets/vendor/sweetalert/sweetalert.min.js'); ?>"></script>

  <!-- Custom JS -->
  <script src="<?= asset_url('assets/js/script.js'); ?>"></script>


  <!-- Google tag (gtag.js) -->
  <script async src="https://www.googletagmanager.com/gtag/js?id=G-NW5FTYQ3BS"></script>
  <script>
    window.dataLayer = window.dataLayer || [];

    function gtag() {
      dataLayer.push(arguments);
    }
    gtag('js', new Date());

    gtag('config', 'G-NW5FTYQ3BS');
  </script>


  <script>
    const toggleBtn = document.getElementById("darkToggle");
    const colorPicker = document.getElementById("colorPicker");
    let pressTimer;

    // --- Apply theme from storage on load ---
    window.addEventListener("DOMContentLoaded", () => {
      const savedTheme = localStorage.getItem("theme");
      const savedColor = localStorage.getItem("customColor");

      if (savedTheme === "dark") {
        applyDarkMode(savedColor || "black");
      } else if (savedTheme === "light") {
        applyLightMode();
      }
    });

    if (toggleBtn) {
      // --- Click: toggle dark/light ---
      toggleBtn.addEventListener("click", () => {
        const isDark = document.body.classList.contains("dark-mode");

        if (!isDark) {
          applyDarkMode("black");
          localStorage.setItem("theme", "dark");
          localStorage.removeItem("customColor");
        } else {
          applyLightMode();
          localStorage.setItem("theme", "light");
          localStorage.removeItem("customColor");
        }
      });

      // --- Long press: open color picker ---
      toggleBtn.addEventListener("mousedown", () => {
        pressTimer = setTimeout(() => {
          colorPicker.click();
        }, 800); // hold 0.8s
      });

      toggleBtn.addEventListener("mouseup", () => clearTimeout(pressTimer));
      toggleBtn.addEventListener("mouseleave", () => clearTimeout(pressTimer));

      // --- Apply custom color ---
      colorPicker.addEventListener("input", (e) => {
        const customColor = e.target.value;
        applyDarkMode(customColor);
        localStorage.setItem("theme", "dark");
        localStorage.setItem("customColor", customColor);
      });
    }

    // --- Helper: Dark mode ---
    function applyDarkMode(bgColor) {
      console.log("Dark mode activated with color:", bgColor);

      document.body.classList.add("dark-mode");

      // Set background colors
      document.querySelector(".wrapper")?.style.setProperty("background-color", bgColor, "important");
      document.querySelector(".content-wrapper")?.style.setProperty("background-color", bgColor, "important");

      // Update page header text color dynamically
      const contentHeader = document.querySelector(".ipb-page-header, .content-header");
      if (contentHeader) {
        if (bgColor === 'black') {
          contentHeader.querySelectorAll("*").forEach(el => {
            el.style.setProperty("color", "white", "important");
          });
        } else {
          contentHeader.querySelectorAll("*").forEach(el => {
            el.style.removeProperty("color");
          });
        }
      }

      toggleBtn.innerHTML = '<i class="fa-solid fa-sun"></i>';
    }


    // --- Helper: Light mode ---
    function applyLightMode() {
      console.log("Light mode activated");
      document.body.classList.remove("dark-mode");
      document.querySelector(".wrapper")?.style.removeProperty("background-color");
      document.querySelector(".content-wrapper")?.style.removeProperty("background-color");
      // document.body.style.removeProperty("color");
      const contentHeader = document.querySelector(".ipb-page-header, .content-header");
      if (contentHeader) {
        contentHeader.querySelectorAll("*").forEach(el => {
          el.style.setProperty("color", "black", "important");
        });
      }
      
      toggleBtn.innerHTML = '<i class="fa-solid fa-moon"></i>';
    }





    document.addEventListener("DOMContentLoaded", function() {
      // Select the element by ID
      var targetElement = document.querySelector("#google_translate_element");

      // Check if the element exists
      if (targetElement && targetElement.textContent.includes("Powered by ")) {
        targetElement.style.display = "none"; // Hides the element
      }
    });
  </script>

  <!-- <script>
  // Detect if the device is NOT a touch device
  const isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0;

  // Prevent context menu only on non-touch devices (i.e., desktops)
  if (!isTouchDevice) {
    document.addEventListener('contextmenu', function (e) {
      e.preventDefault();
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === "F12") e.preventDefault();

      if (e.ctrlKey && e.shiftKey && ['I', 'J', 'C'].includes(e.key.toUpperCase())) {
        e.preventDefault();
      }

      if (e.ctrlKey && e.key.toUpperCase() === 'U') {
        e.preventDefault();
      }
    });
  }
</script> -->


  <!-- 08 §1 — clearAllData() DELETED. It ran on window.onload on EVERY page
       load in this shell, wiping sessionStorage/cookies/cache — defeating
       the filter-persistence (IpbFilters sessionStorage) and theme-memory
       the rest of the app relies on. It also referenced
       google.translate.TranslateElement.prototype unconditionally, which
       throws before the async Google Translate script has loaded. Nuking a
       user's cookies/session on every load of a billing-panel page was never
       intentional production behavior — removed outright, not scoped to a
       logout route, since no legitimate caller depended on it. -->
  <script>
    // (clearAllData intentionally removed — see comment above)
  </script>




  <script>
    // Initialize Bootstrap tooltips. This app bundles Bootstrap 3.4.1, which
    // never defines a global `bootstrap` object — `new bootstrap.Tooltip(...)`
    // is Bootstrap 5 API and threw "bootstrap is not defined" on every page
    // load. Bootstrap 3's tooltip is a jQuery plugin instead.
    $('[data-bs-toggle="tooltip"]').tooltip();

    function toggleTranslation() {
      const currentUrl = window.location.href;

      // Check if the current URL already includes a translation to Bangla
      if (currentUrl.includes('translate.google.com') && currentUrl.includes('tl=bn')) {
        // Redirect back to the original page (assumes the original URL is stored as a referrer)
        if (document.referrer) {
          window.location.href = document.referrer;
        } else {
          // Fallback: strip the Google Translate prefix from the URL
          const originalUrl = currentUrl.split('u=')[1];
          window.location.href = decodeURIComponent(originalUrl);
        }
      } else {
        // Translate the current page to Bangla
        const translatedUrl = `https://translate.google.com/translate?hl=bn&sl=auto&tl=bn&u=${encodeURIComponent(currentUrl)}`;
        window.location.href = translatedUrl;
      }
    }
  </script>


  <script type="text/javascript">
    function googleTranslateElementInit() {

      new google.translate.TranslateElement({
        pageLanguage: 'en', // Change to your default language code  
        includedLanguages: 'es,en,bn,fr,de,it', // Specify languages to include  
        layout: google.translate.TranslateElement.InlineLayout.SIMPLE
      }, 'google_translate_element');
    }
  </script>
  <script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>


  <script>
    function adjustFontSizeForBangla() {
      // 06 §4 — same latent-bomb fix as main-layout.php; see that file's
      // comment for the full mechanism. Class-toggle instead of an inline size.
      const lang = (document.documentElement.lang || "").toLowerCase();
      const bn = lang.indexOf("bn") === 0;
      document.body.classList.toggle("ipb-lang-bn", bn);
      document.body.style.fontSize = ""; // never inline a size
    }

    // Observe changes in the <html> tag's language attribute
    const observer = new MutationObserver(() => {
      adjustFontSizeForBangla();
    });

    observer.observe(document.documentElement, {
      attributes: true,
      attributeFilter: ["lang"]
    });

    // Initial check for language when the script loads
    adjustFontSizeForBangla();
  </script>




  <script>
    $(document).ready(function() {

      // Sidebar sections: keep active route open; toggle menu-open (not active)
      function ipbOpenSidebarSection($li) {
        $li.addClass('menu-open');
        $li.children('.treeview-menu').css('display', '');
      }
      function ipbCloseSidebarSection($li) {
        if ($li.find('.treeview-menu li.active').length) {
          ipbOpenSidebarSection($li);
          return;
        }
        $li.removeClass('menu-open');
        $li.children('.treeview-menu').css('display', '');
      }
      $('.sidebar-menu > li').each(function () {
        var $li = $(this);
        if ($li.find('> .treeview-menu li.active').length || $li.hasClass('active')) {
          ipbOpenSidebarSection($li);
        }
      });
      $('.sidebar-menu > li > a.dropdown-toggle, .sidebar-menu > li.treeview > a').on('click', function (e) {
        var $a = $(this);
        var href = $a.attr('href') || '';
        if (href && href !== '#' && href.indexOf('javascript:') !== 0) return;
        e.preventDefault();
        var $li = $a.parent('li');
        if (!$li.children('.treeview-menu').length) return;
        var wasOpen = $li.hasClass('menu-open');
        $('.sidebar-menu > li.menu-open').each(function () {
          if (this !== $li[0]) ipbCloseSidebarSection($(this));
        });
        if (wasOpen) ipbCloseSidebarSection($li);
        else ipbOpenSidebarSection($li);
      });

      // AJAX navigation script
      $('.ajax-link').on('click', function(e) {
        e.preventDefault(); // Prevent default link behavior (page reload)

        var url = $(this).attr('href'); // Get the URL from the clicked link

        // AJAX call to load content from the URL
        $.ajax({
          url: url,
          type: 'GET',
          dataType: 'html',
          success: function(response) {
            $('#content').html(response); // Update the content container
          },
          error: function(xhr, status, error) {
            console.error('Error loading page:', error);
          }
        });
      });
    });
  </script>


  <?= $this->renderSection('script'); ?>
</body>

</html>