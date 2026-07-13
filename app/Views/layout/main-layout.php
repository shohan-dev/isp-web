<?php
// Place these headers at the very top of the view file.
// Guarded: under PHPUnit CLI (e.g. a test rendering this view outside a real
// HTTP dispatch), output may already be flushed by the test runner itself,
// which would otherwise throw "headers already sent" — never true this early
// in a real request, so the guard changes nothing in production.
if (!headers_sent()) {
    header("Expires: Tue, 01 Jan 2000 00:00:00 GMT");
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
}
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
  <link rel="stylesheet" href="<?= base_url('assets/vendor/datatable/dataTables.min.css?v=1') ?>">

  <!-- Select2 -->
  <link rel="stylesheet" href="<?= base_url('assets/vendor/select2/select2.min.css'); ?>">

  <!-- AdminLTE structure only (no skin) -->
  <link rel="stylesheet" href="<?= asset_url('assets/css/style.css'); ?>">

  <!-- SaaS design system — loads last, owns all visuals.
       08 §5 — cache-bust via saas_css()/filemtime(), not a hand-picked ?v=N.
       This durably fixes the version drift the hand-incremented scheme kept
       producing (verified: base.css was v19/v18/v20/v22 across four loaders
       before this). Edit a file, its version bumps itself, every shell agrees. -->
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




  <meta name="csrf-token" content="<?= csrf_hash() ?>">
  <meta name="csrf-header" content="<?= csrf_header() ?>">

  <?php if (isset($isPublic) && $isPublic): ?>
    <style>
      body.layout-top-nav {
        background-color: #f4f6f9 !important;
      }

      .wrapper {
        background-color: #f4f6f9 !important;
      }

      .main-footer {
        margin-left: 0 !important;
        background: #fff !important;
        padding: 15px !important;
        color: #444 !important;
        border-top: 1px solid #dee2e6 !important;
        width: 100% !important;
        position: relative !important;
      }

      body.layout-top-nav .content-wrapper,
      body.layout-top-nav .subscription-wrapper {
        margin-left: auto !important;
        margin-right: auto !important;
        margin-top: 20px !important;
        margin-bottom: 20px !important;
        max-width: 95%;
        /* Take 95% of screen width */
        padding: 0 20px;
        background-color: #f4f6f9 !important;
        min-height: calc(100vh - 180px);
        float: none !important;
        display: block !important;
      }

      @media (max-width: 768px) {

        body.layout-top-nav .content-wrapper,
        body.layout-top-nav .subscription-wrapper {
          margin: 10px auto !important;
        }
      }
    </style>
  <?php endif; ?>
</head>

<body
  class="ipb hold-transition <?= (isset($isPublic) && $isPublic) ? 'layout-top-nav' : 'sidebar-mini fixed' ?>"
  data-theme="light">
  <!-- 08 §7 — skip-link: must be the first focusable element. Today Tab from
       page load walks the whole sidebar before reaching content; this jumps
       straight to <main id="ipb-main"> below. Visually hidden until focused. -->
  <a class="ipb-skip-link" href="#ipb-main">Skip to main content</a>
  <?php /* Dark mode + sidebar-collapse + the FULL brand ramp, applied before the
           first paint. Must stay parser-blocking and stay here, above the sidebar.

           This replaces an inline approximation that set 5 CSS variables where the
           real theme code (customize.js) sets ~25 — it never set --secondary-800,
           which is half the sidebar's background gradient, and it derived
           --secondary-900 from the raw hex rather than the ramp. So the sidebar
           painted in the DEFAULT navy and then switched to the brand colour once
           customize.js ran, on every single navigation. One implementation now:
           brand-boot.js, which customize.js binds to instead of copying. */ ?>
  <?= saas_js('brand-boot.js') ?>

  <script>
    (function () {
      /* Pre-hide dashboard widgets from saved prefs before paint (no flicker) */
      try {
        var WIDGET_PREFIX = "ipb_dash_widgets_";
        var DEFAULT_HIDDEN = { admin: ["insights"] };
        var css = "";
        var seen = {};
        for (var i = 0; i < localStorage.length; i++) {
          var key = localStorage.key(i);
          if (!key || key.indexOf(WIDGET_PREFIX) !== 0) continue;
          var dashId = key.slice(WIDGET_PREFIX.length);
          seen[dashId] = true;
          var list = JSON.parse(localStorage.getItem(key) || "[]");
          if (!Array.isArray(list)) continue;
          for (var j = 0; j < list.length; j++) {
            var w = list[j];
            if (!w || !w.id || w.visible !== false) continue;
            css +=
              '[data-ipb-dashboard="' +
              dashId +
              '"] .ipb-widget[data-widget-id="' +
              w.id +
              '"]{display:none!important}';
          }
        }
        Object.keys(DEFAULT_HIDDEN).forEach(function (dashId) {
          if (seen[dashId]) return;
          DEFAULT_HIDDEN[dashId].forEach(function (id) {
            css +=
              '[data-ipb-dashboard="' +
              dashId +
              '"] .ipb-widget[data-widget-id="' +
              id +
              '"]{display:none!important}';
          });
        });
        if (css) {
          var style = document.createElement("style");
          style.setAttribute("data-ipb-dash-boot", "1");
          style.textContent = css;
          document.head.appendChild(style);
        }
      } catch (e2) {}
    })();
  </script>
  <div class="wrapper">

    <?php if (!isset($isPublic) || !$isPublic): ?>
      <div class="ipb-sidebar-backdrop" aria-hidden="true"></div>
      <?= $this->include('layout/header'); ?>
      <?php /* 06 §1 — data-status-url intentionally omitted: no /system/status route
               exists yet (verified — see docs/uiux-overhaul-2026-07/06-NAVIGATION-AND-POLISH.md
               §1). route_to() THROWS RouterException for an undefined route name, so
               calling it here would fatal every authenticated page load. The JS's own
               "if (!url) return" guard keeps the rail neutral and still working as a
               load-progress bar until a real status endpoint is wired up. */ ?>
      <div class="ipb-netpulse" id="ipbNetPulse" data-status="unknown"
           role="status" aria-label="Network status" title="Network status"></div>
      <?= $this->include('layout/sidebar'); ?>
    <?php else: ?>
      <?= $this->include('layout/public_navbar'); ?>
    <?php endif; ?>

    <main id="ipb-main" tabindex="-1">
      <?= $this->renderSection('content'); ?>
    </main>

    <?= $this->include('layout/footer'); ?>

  </div>
  <?php if (!isset($isPublic) || !$isPublic): ?>
    <?= $this->include('components/command-palette'); ?>
    <?= $this->include('components/customize-drawers'); ?>
  <?php endif; ?>
  <!-- ./wrapper -->

  <!-- jQuery -->
  <script src="<?= base_url('assets/vendor/jquery/jquery.min.js'); ?>"></script>

  <!-- Bootstrap -->
  <script src="<?= base_url('assets/vendor/bootstrap/js/bootstrap.min.js'); ?>"></script>

  <!-- Datatable -->
  <script src="<?= base_url('assets/vendor/datatable/dataTables.min.js?v=1') ?>"></script>

  <script src="<?= base_url('assets/vendor/datatable/dataTables.bootstrap.min.js'); ?>"></script>

  <!-- TataJs + premium toast theme -->
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
  <?= saas_js('saas.js') ?>
  <?= saas_js('sidebar-pins.js') ?>
  <?= saas_js('sidebar-prefetch.js') ?>
  <?= saas_js('list-filters.js') ?>
  <?= saas_js('customize.js') ?>
  <?= saas_js('skeleton-swap.js') ?>

  <script type="application/ld+json"><?= json_encode([
      '@context' => 'https://schema.org',
      '@type' => 'Organization',
      'name' => 'ISP Pay BD',
      'url' => 'https://isppaybd.com',
      'logo' => getBrandFaviconUrl(),
      'contactPoint' => [
          '@type' => 'ContactPoint',
          'telephone' => '+8801781-808231',
          'contactType' => 'customer service',
      ],
  ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
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
    document.addEventListener("DOMContentLoaded", function () {
      var targetElement = document.querySelector("#google_translate_element");
      if (targetElement && targetElement.textContent.includes("Powered by ")) {
        targetElement.style.display = "none";
      }
      // Migrate legacy theme key once
      if (!localStorage.getItem("ipb_theme") && localStorage.getItem("theme")) {
        localStorage.setItem("ipb_theme", localStorage.getItem("theme") === "dark" ? "dark" : "light");
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


  <script>
    function clearAllData() {
      console.log(google.translate.TranslateElement.prototype);
      // Show local storage data before clearing
      // console.log('Local Storage before clearing:', JSON.stringify(localStorage));

      // Clear local storage
      // localStorage.clear();
      // console.log('Local Storage cleared.');

      // Show session storage data before clearing
      // console.log('Session Storage before clearing:', JSON.stringify(sessionStorage));

      // Clear session storage
      sessionStorage.clear();
      // console.log('Session Storage c/leared.');

      // Show cookies before clearing
      const cookies = document.cookie.split(";").map(cookie => cookie.trim());
      // console.log('Cookies before clearing:', cookies);

      // Delete all cookies
      document.cookie.split(";").forEach((cookie) => {
        document.cookie = cookie
          .replace(/^ +/, "")
          .replace(/=.*/, `=;expires=${new Date(0).toUTCString()};path=/`);
      });
      console.log('Cookies cleared.');

      // Show cache data before clearing
      if ('caches' in window) {
        caches.keys().then((names) => {
          console.log('Cache before clearing:', names);

          // Clear cache
          names.forEach((name) => caches.delete(name));
          console.log('Cache cleared.');
        });
      }

      // alert("All cache, cookies, and storage data have been cleared!");
    }

    // window.onload = clearAllData; // Disabled - clearing data on every load can break sessions and CSRF
  </script>




  <script>
    // Initialize Bootstrap tooltips
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));

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
  <script type="text/javascript"
    src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>


  <script>
    function adjustFontSizeForBangla() {
      // 06 §4 — was a latent bomb: element.style.fontSize = "80px !important"
      // silently no-ops (CSSOM rejects the malformed !important-in-value string),
      // which is exactly why nobody noticed it. The day someone "cleans up" that
      // string to a bare "80px", every page jumps to 80px body text under Bangla
      // translation. Bangla doesn't need a bigger size — it needs a proper font
      // stack + leading (base.css .ipb-lang-bn); class-toggle instead of an inline size.
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
    $(document).ready(function () {

      // Sidebar section expand/collapse is owned by saas.js (menu-open + URL active).
      // Do not bind .dropdown-toggle here — it closed sections and fought active state.

      // AJAX navigation script
      $('.ajax-link').on('click', function (e) {
        e.preventDefault(); // Prevent default link behavior (page reload)

        var url = $(this).attr('href'); // Get the URL from the clicked link

        // AJAX call to load content from the URL
        $.ajax({
          url: url,
          type: 'GET',
          dataType: 'html',
          success: function (response) {
            $('#content').html(response); // Update the content container
          },
          error: function (xhr, status, error) {
            console.error('Error loading page:', error);
          }
        });
      });
    });
  </script>


  <?php if (userHasPermission('ai_chat', 'chat')): ?>
    <!-- AI Chat FAB and Container -->
    <button type="button" class="ai-chat-fab" id="aiChatFab" title="AI Assistant" aria-label="Open AI assistant" aria-haspopup="dialog">
      <i class="fa-solid fa-robot" aria-hidden="true"></i>
    </button>

    <div class="ai-chat-container" id="aiChatContainer">
      <div class="ai-chat-header">
        <div class="ai-chat-header-info">
          <div class="ai-chat-avatar-container">
            <div class="ai-chat-avatar">
              <i class="fa-solid fa-robot"></i>
            </div>
            <div class="ai-chat-status"></div>
          </div>
          <div>
            <h4 class="ai-chat-title">ISP AI Assistant</h4>
            <p class="ai-chat-subtitle">Online &bull; Ask me anything</p>
          </div>
        </div>
        <div class="ai-chat-header-actions">
          <div class="ai-lang-toggle-container" id="aiLangToggle" title="Switch Language">
            <span class="lang-option lang-en">EN</span>
            <span class="lang-option lang-bn">বাংলা</span>
          </div>
          <button type="button" class="ai-chat-close-btn" id="aiChatClose" aria-label="Close">&times;</button>
        </div>
      </div>

      <div class="ai-chat-body" id="aiChatBody">
        <!-- Chat history will be loaded here -->
      </div>

      <div class="ai-chat-suggestions" id="aiChatSuggestions">
        <!-- Suggestions loaded dynamically based on language -->
      </div>

      <div class="ai-chat-footer">
        <form class="ai-chat-form" id="aiChatForm" autocomplete="off">
          <div class="ai-chat-input-wrapper">
            <input type="text" class="ai-chat-input" id="aiChatInput" placeholder="Type your message..." required>
          </div>
          <button type="submit" class="ai-chat-send-btn" id="aiChatSend" title="Send">
            <i class="fa-solid fa-paper-plane"></i>
          </button>
        </form>
      </div>
    </div>

    <script>
      $(document).ready(function() {
        const $fab = $('#aiChatFab');
        const $container = $('#aiChatContainer');
        const $closeBtn = $('#aiChatClose');
        const $form = $('#aiChatForm');
        const $input = $('#aiChatInput');
        const $chatBody = $('#aiChatBody');

        // Dynamic base path construction to avoid port/baseURL mismatches
        const scriptName = '<?= $_SERVER['SCRIPT_NAME'] ?>';
        const appBasePath = scriptName.substring(0, scriptName.lastIndexOf('/'));
        const apiUrl = window.location.origin + appBasePath + '/ai-chat';

        // Language selection (sessionStorage or default isBangla system locale check)
        let currentLang = sessionStorage.getItem('ai_chat_lang');
        if (!currentLang) {
          const isSystemBn = ($('html[lang="bn"]').length > 0 || 
                              (document.documentElement.lang && document.documentElement.lang.toLowerCase().indexOf('bn') !== -1) ||
                              '<?= getSession('lang') ?>' === 'bn');
          currentLang = isSystemBn ? 'BN' : 'EN';
        }

        // Load session and chat history from sessionStorage
        let sessionId = sessionStorage.getItem('ai_chat_session_id') || null;
        let chatHistory = [];
        try {
          const storedHistory = sessionStorage.getItem('ai_chat_history');
          if (storedHistory) {
            chatHistory = JSON.parse(storedHistory);
          }
        } catch (e) {
          console.error('Error parsing chat history', e);
        }

        // Suggestions in both languages
        const suggestionsBn = [
          'DHCP কনফিগারেশন',
          'ব্যান্ড ম্যানেজমেন্ট',
          'চ্যানেল অপটিমাইজেশন',
          'মুভি সার্ভার তালিকা'
        ];

        const suggestionsEn = [
          'DHCP Configuration',
          'Bandwidth Management',
          'Channel Optimization',
          'Movie Server List'
        ];

        function renderSuggestions() {
          const $suggestionsContainer = $('#aiChatSuggestions');
          $suggestionsContainer.empty();
          const list = (currentLang === 'BN') ? suggestionsBn : suggestionsEn;
          list.forEach(item => {
            $suggestionsContainer.append(`<a href="javascript:void(0)" class="ai-suggestion-btn">${item}</a>`);
          });
        }

        function updateLangToggleUI() {
          const $toggle = $('#aiLangToggle');
          $toggle.find('.lang-option').removeClass('active');
          if (currentLang === 'BN') {
            $toggle.addClass('bn-active');
            $toggle.find('.lang-bn').addClass('active');
          } else {
            $toggle.removeClass('bn-active');
            $toggle.find('.lang-en').addClass('active');
          }
        }

        // Initialize Chat Widget
        function initChat() {
          $chatBody.empty();
          
          // Update language toggle UI highlighting
          updateLangToggleUI();

          if (chatHistory.length === 0) {
            // Add default welcome message
            const welcomeText = currentLang === 'BN' 
              ? 'হ্যালো! ISPpayBD-তে স্বাগতম! আমি আপনার এআই ইন্টারনেট সাপোর্ট অ্যাসিস্ট্যান্ট। ইন্টারনেট সংযোগ, বিল পেমেন্ট, স্পিড সমস্যা অথবা অন্য যেকোনো সহায়তা—আমি কীভাবে আপনাকে সাহায্য করতে পারি?' 
              : 'Hello! Welcome to ISPpayBD! I am your AI Internet Support Assistant. How can I help you today with connection, billing, or speed issues?';
            
            const welcomeMsg = {
              sender: 'bot',
              text: welcomeText,
              time: formatTime(new Date())
            };
            chatHistory.push(welcomeMsg);
            saveHistory();
          }
          
          chatHistory.forEach(msg => {
            appendMessageHTML(msg.sender, msg.text, msg.time);
          });
          
          renderSuggestions();
          scrollToBottom();
        }

        function saveHistory() {
          sessionStorage.setItem('ai_chat_history', JSON.stringify(chatHistory));
        }

        function formatTime(date) {
          return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }

        function appendMessageHTML(sender, text, time) {
          const isBot = sender === 'bot';
          const msgClass = isBot ? 'ai-msg-bot' : 'ai-msg-user';
          const safeText = isBot ? text : $('<div>').text(text).html();

          const html = `
            <div class="ai-msg ${msgClass}">
              <div class="ai-msg-text">${safeText}</div>
              <div class="ai-msg-time">${time}</div>
            </div>
          `;
          $chatBody.append(html);
        }

        function scrollToBottom() {
          if ($chatBody[0]) {
            $chatBody.scrollTop($chatBody[0].scrollHeight);
          }
        }

        function showTypingIndicator() {
          const html = `
            <div class="ai-typing-indicator" id="aiTypingIndicator">
              <span></span>
              <span></span>
              <span></span>
            </div>
          `;
          $chatBody.append(html);
          scrollToBottom();
        }

        function removeTypingIndicator() {
          $('#aiTypingIndicator').remove();
        }

        // Toggle Chat Widget. 06 §1 — the FAB no longer pulses (the Network
        // Pulse strip now owns the shell's one ambient animation), so this
        // no longer needs to add/remove pulse-active.
        $fab.on('click', function() {
          $container.toggleClass('active');
          if ($container.hasClass('active')) {
            scrollToBottom();
            $input.focus();
          }
        });

        $closeBtn.on('click', function() {
          $container.removeClass('active');
        });

        // Close chatbot when clicking outside on mobile
        $(document).on('click', function(event) {
          if ($(window).width() <= 576) {
            if (!$(event.target).closest('#aiChatContainer').length &&
                !$(event.target).closest('#aiChatFab').length &&
                $container.hasClass('active')) {
              $container.removeClass('active');
            }
          }
        });

        // Click handler for suggestion buttons
        $(document).on('click', '.ai-suggestion-btn', function() {
          const text = $(this).text();
          $input.val(text);
          $form.trigger('submit');
        });

        // Language Toggle Click Event
        $(document).on('click', '#aiLangToggle', function(e) {
          e.preventDefault();
          const $target = $(e.target).closest('.lang-option');
          if ($target.length) {
            const newLang = $target.hasClass('lang-bn') ? 'BN' : 'EN';
            if (newLang === currentLang) return;
            currentLang = newLang;
          } else {
            currentLang = (currentLang === 'BN') ? 'EN' : 'BN';
          }
          
          sessionStorage.setItem('ai_chat_lang', currentLang);
          updateLangToggleUI();
          
          // Re-render suggestions
          renderSuggestions();

          // If the only message in history is the welcome message, translate it!
          if (chatHistory.length === 1 && chatHistory[0].sender === 'bot') {
            const welcomeText = currentLang === 'BN'
              ? 'হ্যালো! ISPpayBD-তে স্বাগতম! আমি আপনার এআই ইন্টারনেট সাপোর্ট অ্যাসিস্ট্যান্ট। ইন্টারনেট সংযোগ, বিল পেমেন্ট, স্পিড সমস্যা অথবা অন্য যেকোনো সহায়তা—আমি কীভাবে আপনাকে সাহায্য করতে পারি?' 
              : 'Hello! Welcome to ISPpayBD! I am your AI Internet Support Assistant. How can I help you today with connection, billing, or speed issues?';
            
            chatHistory[0].text = welcomeText;
            saveHistory();
            
            // Re-render chat body
            $chatBody.empty();
            appendMessageHTML(chatHistory[0].sender, chatHistory[0].text, chatHistory[0].time);
          }
        });

        // Submit message
        $form.on('submit', function(e) {
          e.preventDefault();
          const messageText = $.trim($input.val());
          if (!messageText) return;

          // Add user message
          const userMsg = {
            sender: 'user',
            text: messageText,
            time: formatTime(new Date())
          };
          chatHistory.push(userMsg);
          saveHistory();
          appendMessageHTML('user', messageText, userMsg.time);
          scrollToBottom();

          // Clear input and disable form
          $input.val('');
          $input.prop('disabled', true);
          const $sendBtn = $form.find('button[type="submit"]');
          $sendBtn.prop('disabled', true);

          // Show typing indicator
          showTypingIndicator();

          // Prepare payload
          const payload = {
            message: messageText,
            session_id: sessionId,
            language: currentLang
          };

          // Make AJAX request
          $.ajax({
            url: apiUrl,
            type: 'POST',
            dataType: 'json',
            contentType: 'application/json',
            data: JSON.stringify(payload),
            success: function(response) {
              removeTypingIndicator();
              $input.prop('disabled', false);
              $sendBtn.prop('disabled', false);
              $input.focus();

              let replyText = '';
              if (response.response) {
                replyText = response.response;
              } else if (response.message) {
                replyText = response.message;
              } else if (response.reply) {
                replyText = response.reply;
              } else {
                replyText = 'No response content returned from AI server.';
              }

              if (response.session_id) {
                sessionId = response.session_id;
                sessionStorage.setItem('ai_chat_session_id', sessionId);
              }

              const botMsg = {
                sender: 'bot',
                text: replyText,
                time: formatTime(new Date())
              };
              chatHistory.push(botMsg);
              saveHistory();
              appendMessageHTML('bot', replyText, botMsg.time);
              scrollToBottom();
            },
            error: function(xhr, status, error) {
              removeTypingIndicator();
              $input.prop('disabled', false);
              $sendBtn.prop('disabled', false);
              $input.focus();

              let errorMsg = 'Could not connect to AI Assistant. Please check connection.';
              try {
                if (xhr.responseJSON && xhr.responseJSON.response) {
                  errorMsg = xhr.responseJSON.response;
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                  errorMsg = xhr.responseJSON.message;
                }
              } catch(e) {}

              const botMsg = {
                sender: 'bot',
                text: errorMsg,
                time: formatTime(new Date())
              };
              chatHistory.push(botMsg);
              saveHistory();
              appendMessageHTML('bot', errorMsg, botMsg.time);
              scrollToBottom();
            }
          });
        });

        // Initialize Chat
        initChat();
      });
    </script>
  <?php endif; ?>

  <?= $this->renderSection('script'); ?>
</body>

</html>
