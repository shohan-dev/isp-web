(function () {
  'use strict';

  var prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* Single source of truth — emitted by home.php. Fallbacks keep the page
     functional if the inline config script ever fails to load. */
  var PRICING = window.LP_PRICING || {};
  var TIERS = PRICING.tiers || {
    basic:      { price: 999,   cap: 500 },
    standard:   { price: 2499,  cap: 2000 },
    premium:    { price: 4999,  cap: 5000 },
    business:   { price: 8499,  cap: 10000 },
    enterprise: { price: 14999, cap: 20000 },
    ultimate:   { price: 24999, cap: 40000 }
  };
  var PAYG = PRICING.payg || { platform: 500, perUser: 1.5, minWallet: 750 };
  var ADDON_PRICES = PRICING.addons || {};
  // Display-only "Save N months" yearly-discount config — super-admin editable,
  // never used for real billing math. 0-11 (12+ would mean free forever).
  var YEARLY_DISCOUNT_MONTHS = typeof PRICING.yearlyDiscountMonths === 'number' ? PRICING.yearlyDiscountMonths : 2;

  function easeOutExpo(t) {
    return t === 1 ? 1 : 1 - Math.pow(2, -10 * t);
  }

  /* Shared rAF number tween — used by counters and the pricing toggle. */
  function tweenNumber(el, from, to, duration, format) {
    if (prefersReducedMotion || duration === 0) {
      el.textContent = format(to);
      return;
    }
    var startTime = null;
    function step(timestamp) {
      if (!startTime) startTime = timestamp;
      var progress = Math.min((timestamp - startTime) / duration, 1);
      var eased = easeOutExpo(progress);
      el.textContent = format(Math.round(from + (to - from) * eased));
      if (progress < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  }

  /* Locale pinned so JS matches PHP's number_format() grouping. */
  function groupNum(n) { return Math.round(n).toLocaleString('en-US'); }
  function fmt(n) { return '৳' + groupNum(n); }

  /* Drives the gradient track-fill on .lp-custom-pricing__slider (WebKit has
     no ::-webkit-slider-progress, so the fill is faked via this custom prop). */
  function syncRangeFill(input) {
    if (!input) return;
    var min = parseFloat(input.min) || 0;
    var max = parseFloat(input.max) || 100;
    var val = parseFloat(input.value) || min;
    var pct = max > min ? ((val - min) / (max - min)) * 100 : 0;
    input.style.setProperty('--lp-fill', pct + '%');
  }

  /* ── Nav scroll state + mobile menu ── */
  function initNav() {
    var nav = document.getElementById('lp-nav');
    if (!nav) return;

    var toggle = document.getElementById('lp-nav-toggle');
    var mobileMenu = document.getElementById('lp-mobile-menu');
    var closeBtn = document.getElementById('lp-mobile-close');
    var mobileCta = document.getElementById('lp-mobile-cta');

    function onScroll() {
      nav.classList.toggle('is-scrolled', window.scrollY > 40);
      if (mobileCta) {
        mobileCta.classList.toggle('is-visible', window.scrollY > window.innerHeight * 0.5);
      }
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    function openMobileMenu() {
      mobileMenu.classList.add('is-open');
      toggle.setAttribute('aria-expanded', 'true');
      document.body.style.overflow = 'hidden';
      var firstLink = mobileMenu.querySelector('a');
      if (firstLink) firstLink.focus();
    }
    function closeMobileMenu() {
      mobileMenu.classList.remove('is-open');
      if (toggle) {
        toggle.setAttribute('aria-expanded', 'false');
        toggle.focus();
      }
      document.body.style.overflow = '';
    }

    if (toggle && mobileMenu) {
      toggle.addEventListener('click', openMobileMenu);
    }
    if (closeBtn && mobileMenu) {
      closeBtn.addEventListener('click', closeMobileMenu);
      mobileMenu.querySelectorAll('a').forEach(function (link) {
        link.addEventListener('click', closeMobileMenu);
      });
      // Escape closes; Tab wraps within the dialog.
      mobileMenu.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
          closeMobileMenu();
          return;
        }
        if (e.key !== 'Tab') return;
        var focusables = mobileMenu.querySelectorAll('button, a[href]');
        if (!focusables.length) return;
        var first = focusables[0];
        var last = focusables[focusables.length - 1];
        if (e.shiftKey && document.activeElement === first) {
          e.preventDefault();
          last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
          e.preventDefault();
          first.focus();
        }
      });
    }

    var sections = document.querySelectorAll('[data-lp-section]');
    var navLinks = nav.querySelectorAll('.lp-nav__links a[href^="#"]');
    if (sections.length && navLinks.length) {
      var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            var id = entry.target.id;
            navLinks.forEach(function (link) {
              link.classList.toggle('is-active', link.getAttribute('href') === '#' + id);
            });
          }
        });
      }, { rootMargin: '-35% 0px -55% 0px' });
      sections.forEach(function (s) { observer.observe(s); });
    }
  }

  /* ── Scroll reveal ── */
  function initReveal() {
    if (prefersReducedMotion) {
      document.querySelectorAll('.lp-reveal, .lp-stagger-children').forEach(function (el) {
        el.classList.add('is-visible');
      });
      return;
    }
    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

    document.querySelectorAll('.lp-reveal, .lp-stagger-children').forEach(function (el) {
      observer.observe(el);
    });
  }

  /* ── Animated counters ── */
  function initCounters() {
    function animateCounter(el) {
      var target = parseInt(el.getAttribute('data-count'), 10);
      var suffix = el.getAttribute('data-suffix') || '';
      var prefix = el.getAttribute('data-prefix') || '';
      tweenNumber(el, 0, target, prefersReducedMotion ? 0 : 2200, function (n) {
        return prefix + n.toLocaleString('en-US') + suffix;
      });
    }

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          animateCounter(entry.target);
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.5 });
    document.querySelectorAll('[data-count]').forEach(function (el) {
      observer.observe(el);
    });
  }

  /* ── Product tabs with load-aware crossfade ──
     Each panel (.lp-showcase-panel) owns its own tab row, preview image,
     gallery nav, and bullets list — the same crossfade-after-decode technique
     as before, generalized to swap an arbitrary URL and to page through a
     category's image list instead of a single fixed image. */
  function swapLpImage(imgEl, src, onDone) {
    if (!imgEl || !src) { if (onDone) onDone(); return; }
    imgEl.classList.add('is-swapping');
    // Half the CSS transition (0.35s), then swap only after decode so the
    // fade-in never reveals a half-loaded image.
    setTimeout(function () {
      var next = new Image();
      next.src = src;
      var ready = next.decode ? next.decode().catch(function () {}) : Promise.resolve();
      ready.then(function () {
        imgEl.src = src;
        imgEl.classList.remove('is-swapping');
        if (onDone) onDone();
      });
    }, prefersReducedMotion ? 0 : 175);
  }

  function swapLpBullets(bulletsEl, points) {
    if (!bulletsEl) return;
    bulletsEl.classList.add('is-swapping');
    setTimeout(function () {
      while (bulletsEl.firstChild) { bulletsEl.removeChild(bulletsEl.firstChild); }
      points.forEach(function (p) {
        var row = document.createElement('div');
        row.className = 'lp-product__bullet';
        var icon = document.createElement('i');
        icon.className = 'fas fa-check';
        var span = document.createElement('span');
        span.textContent = p;
        row.appendChild(icon);
        row.appendChild(span);
        bulletsEl.appendChild(row);
      });
      bulletsEl.classList.remove('is-swapping');
    }, prefersReducedMotion ? 0 : 150);
  }

  function padTwo(n) {
    var s = String(n);
    return s.length < 2 ? '0' + s : s;
  }

  function initProductShowcasePanel(panel) {
    var tabs = panel.querySelectorAll('.lp-product__tab');
    var preview = panel.querySelector('.lp-product__preview img');
    var bullets = panel.querySelector('.lp-product__bullets');
    var navWrap = panel.querySelector('.lp-showcase-nav');
    var prevBtn = navWrap ? navWrap.querySelector('[data-dir="prev"]') : null;
    var nextBtn = navWrap ? navWrap.querySelector('[data-dir="next"]') : null;
    var captionEl = navWrap ? navWrap.querySelector('.lp-showcase-nav__caption') : null;
    if (!tabs.length || !preview) return;

    var currentImages = [];
    var currentIndex = 0;

    var preloaded = false;
    function preloadAll() {
      if (preloaded) return;
      preloaded = true;
      tabs.forEach(function (t) {
        try {
          var imgs = JSON.parse(t.getAttribute('data-images') || '[]');
          imgs.forEach(function (im) { if (im && im.url) { new Image().src = im.url; } });
        } catch (e) { /* malformed data-images — skip preload for this tab */ }
      });
    }

    function updateNav() {
      if (!navWrap) return;
      var multi = currentImages.length > 1;
      navWrap.classList.toggle('is-hidden', !multi);
      if (prevBtn) prevBtn.disabled = currentIndex <= 0;
      if (nextBtn) nextBtn.disabled = currentIndex >= currentImages.length - 1;
      if (captionEl) {
        if (!currentImages.length) {
          captionEl.textContent = '';
        } else {
          var current = currentImages[currentIndex];
          var caption = current && current.caption ? ' · ' + current.caption : '';
          captionEl.textContent = padTwo(currentIndex + 1) + ' / ' + padTwo(currentImages.length) + caption;
        }
      }
    }

    function goToImage(index) {
      if (!currentImages.length) return;
      index = Math.max(0, Math.min(index, currentImages.length - 1));
      if (index === currentIndex) return;
      currentIndex = index;
      swapLpImage(preview, currentImages[currentIndex].url, updateNav);
    }

    function activateTab(tab) {
      tabs.forEach(function (t) {
        t.classList.remove('is-active');
        t.setAttribute('aria-selected', 'false');
      });
      tab.classList.add('is-active');
      tab.setAttribute('aria-selected', 'true');

      var imgs;
      try {
        imgs = JSON.parse(tab.getAttribute('data-images') || '[]');
        if (!Array.isArray(imgs)) { imgs = []; }
      } catch (e) {
        imgs = [];
      }
      currentImages = imgs;
      currentIndex = 0;
      if (imgs.length) {
        swapLpImage(preview, imgs[0].url, updateNav);
      } else {
        updateNav();
      }

      var points = tab.getAttribute('data-bullets');
      if (bullets && points) {
        var pointList;
        try {
          pointList = JSON.parse(points);
          if (!Array.isArray(pointList)) { pointList = []; }
        } catch (e) {
          pointList = [];
        }
        swapLpBullets(bullets, pointList);
      }
    }

    tabs.forEach(function (tab) {
      tab.addEventListener('click', function () {
        preloadAll();
        activateTab(tab);
      });
    });

    if (prevBtn) {
      prevBtn.addEventListener('click', function () {
        if (!prevBtn.disabled) goToImage(currentIndex - 1);
      });
    }
    if (nextBtn) {
      nextBtn.addEventListener('click', function () {
        if (!nextBtn.disabled) goToImage(currentIndex + 1);
      });
    }

    // Seed nav state from whichever tab the server already rendered active —
    // the image/bullets themselves are already correct in the markup.
    var activeTab = panel.querySelector('.lp-product__tab.is-active') || tabs[0];
    if (activeTab) {
      try {
        currentImages = JSON.parse(activeTab.getAttribute('data-images') || '[]');
        if (!Array.isArray(currentImages)) { currentImages = []; }
      } catch (e) {
        currentImages = [];
      }
      currentIndex = 0;
      updateNav();
    }
  }

  /* ── Website / Mobile showcase switch — mirrors initPricing()'s fixed/PAYG
     model-switch mechanism exactly (show/hide via class + aria-selected +
     roving tabindex). Absent entirely when there is no mobile content. */
  function initShowcaseModelToggle() {
    var btnWebsite = document.getElementById('lp-showcase-model-website');
    var btnMobile = document.getElementById('lp-showcase-model-mobile');
    if (!btnWebsite || !btnMobile) return;
    var panelWebsite = document.getElementById('lp-showcase-panel-website');
    var panelMobile = document.getElementById('lp-showcase-panel-mobile');
    var modelButtons = [btnWebsite, btnMobile];

    function showPanel(mode) {
      var isMobile = mode === 'mobile';
      btnWebsite.classList.toggle('is-active', !isMobile);
      btnWebsite.setAttribute('aria-selected', !isMobile ? 'true' : 'false');
      btnWebsite.setAttribute('tabindex', !isMobile ? '0' : '-1');
      btnMobile.classList.toggle('is-active', isMobile);
      btnMobile.setAttribute('aria-selected', isMobile ? 'true' : 'false');
      btnMobile.setAttribute('tabindex', isMobile ? '0' : '-1');
      if (panelWebsite) {
        panelWebsite.classList.toggle('is-active', !isMobile);
        panelWebsite.hidden = isMobile;
      }
      if (panelMobile) {
        panelMobile.classList.toggle('is-active', isMobile);
        panelMobile.hidden = !isMobile;
      }
    }

    btnWebsite.addEventListener('click', function () { showPanel('website'); });
    btnMobile.addEventListener('click', function () { showPanel('mobile'); });

    // role="tab" advertises arrow-key semantics — honor them.
    modelButtons.forEach(function (btn, idx) {
      btn.addEventListener('keydown', function (e) {
        if (e.key !== 'ArrowLeft' && e.key !== 'ArrowRight') return;
        e.preventDefault();
        var next = modelButtons[(idx + (e.key === 'ArrowRight' ? 1 : modelButtons.length - 1)) % modelButtons.length];
        next.focus();
        next.click();
      });
    });
  }

  function initProductTabs() {
    document.querySelectorAll('.lp-showcase-panel').forEach(initProductShowcasePanel);
    initShowcaseModelToggle();
  }

  /* ── Feature search (fade-out filtering) + show-more for the extra 6 ── */
  function initFeatures() {
    var input = document.getElementById('lp-feature-search');
    var showMoreBtn = document.getElementById('lp-features-showmore');
    var extraGrid = document.getElementById('lp-features-extra');
    var label = showMoreBtn ? showMoreBtn.querySelector('span') : null;
    var collapsedText = label ? label.textContent : '';
    var expandedByUser = false;

    function expandExtra() {
      if (!extraGrid) return;
      extraGrid.hidden = false;
      // Play the reveal fade immediately instead of waiting on the scroll-linked
      // IntersectionObserver in initReveal() — a click is already deliberate,
      // so it shouldn't matter whether the grid happens to be in view yet.
      extraGrid.classList.add('is-visible');
      if (showMoreBtn) {
        showMoreBtn.setAttribute('aria-expanded', 'true');
        showMoreBtn.classList.add('is-expanded');
      }
      if (label) label.textContent = 'Show fewer features';
    }
    function collapseExtra() {
      if (!extraGrid) return;
      extraGrid.hidden = true;
      if (showMoreBtn) {
        showMoreBtn.setAttribute('aria-expanded', 'false');
        showMoreBtn.classList.remove('is-expanded');
      }
      if (label) label.textContent = collapsedText;
    }

    if (showMoreBtn && extraGrid) {
      showMoreBtn.addEventListener('click', function () {
        if (extraGrid.hidden) {
          expandExtra();
          expandedByUser = true;
        } else {
          collapseExtra();
          expandedByUser = false;
        }
      });
    }

    if (!input) return;
    input.addEventListener('input', function () {
      var q = input.value.toLowerCase().trim();
      // A search query can match cards in the collapsed group — reveal it so
      // results aren't silently hidden; restore the collapsed state once the
      // query clears, unless the user had explicitly expanded it themselves.
      if (extraGrid) {
        if (q.length > 0 && extraGrid.hidden) {
          extraGrid.hidden = false;
          extraGrid.classList.add('is-visible');
        } else if (q.length === 0 && !expandedByUser && !extraGrid.hidden) {
          collapseExtra();
        }
      }
      document.querySelectorAll('.lp-feature').forEach(function (card) {
        var matches = q.length === 0 || card.textContent.toLowerCase().indexOf(q) !== -1;
        if (card._lpHideTimer) {
          clearTimeout(card._lpHideTimer);
          card._lpHideTimer = null;
        }
        if (matches) {
          card.classList.remove('is-hidden');
          // Force reflow so the fade-in transition plays from the hidden state.
          void card.offsetWidth;
          card.classList.remove('is-fading');
        } else {
          card.classList.add('is-fading');
          card._lpHideTimer = setTimeout(function () {
            card.classList.add('is-hidden');
          }, prefersReducedMotion ? 0 : 200);
        }
      });
    });
  }

  /* ── FAQ accordion ── */
  function initFaq() {
    document.querySelectorAll('.lp-faq__question').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var item = btn.closest('.lp-faq__item');
        var isOpen = item.classList.contains('is-open');
        document.querySelectorAll('.lp-faq__item').forEach(function (i) {
          i.classList.remove('is-open');
          i.querySelector('.lp-faq__question').setAttribute('aria-expanded', 'false');
        });
        if (!isOpen) {
          item.classList.add('is-open');
          btn.setAttribute('aria-expanded', 'true');
        }
      });
    });
  }

  /* ── Pricing model tabs + fixed plans + PAYG wallet ── */
  function initPricing() {
    var btnFixed = document.getElementById('lp-model-fixed');
    var btnPayg = document.getElementById('lp-model-payg');
    var panelFixed = document.getElementById('lp-panel-fixed');
    var panelPayg = document.getElementById('lp-panel-payg');
    var switchLink = document.getElementById('lp-switch-to-payg');
    var modelButtons = [btnFixed, btnPayg].filter(Boolean);

    function showPanel(mode) {
      var isPayg = mode === 'payg';
      if (btnFixed) {
        btnFixed.classList.toggle('is-active', !isPayg);
        btnFixed.setAttribute('aria-selected', !isPayg ? 'true' : 'false');
        btnFixed.setAttribute('tabindex', !isPayg ? '0' : '-1');
      }
      if (btnPayg) {
        btnPayg.classList.toggle('is-active', isPayg);
        btnPayg.setAttribute('aria-selected', isPayg ? 'true' : 'false');
        btnPayg.setAttribute('tabindex', isPayg ? '0' : '-1');
      }
      if (panelFixed) {
        panelFixed.classList.toggle('is-active', !isPayg);
        panelFixed.hidden = isPayg;
      }
      if (panelPayg) {
        panelPayg.classList.toggle('is-active', isPayg);
        panelPayg.hidden = !isPayg;
      }
    }

    if (btnFixed) btnFixed.addEventListener('click', function () { showPanel('fixed'); });
    if (btnPayg) btnPayg.addEventListener('click', function () { showPanel('payg'); });

    // role="tab" advertises arrow-key semantics — honor them.
    modelButtons.forEach(function (btn, idx) {
      btn.addEventListener('keydown', function (e) {
        if (e.key !== 'ArrowLeft' && e.key !== 'ArrowRight') return;
        e.preventDefault();
        var next = modelButtons[(idx + (e.key === 'ArrowRight' ? 1 : modelButtons.length - 1)) % modelButtons.length];
        next.focus();
        next.click();
      });
    });

    if (switchLink) {
      switchLink.addEventListener('click', function (e) {
        e.preventDefault();
        showPanel('payg');
        var pricing = document.getElementById('lp-pricing');
        if (pricing) pricing.scrollIntoView({ behavior: prefersReducedMotion ? 'auto' : 'smooth' });
      });
    }

    /* Fixed-plan monthly/yearly toggle */
    var toggle = document.getElementById('lp-pricing-toggle');
    var monthlyLabel = document.getElementById('lp-label-monthly');
    var yearlyLabel = document.getElementById('lp-label-yearly');
    var isYearly = false;

    function updateFixedPrices() {
      Object.keys(TIERS).forEach(function (plan) {
        var el = document.querySelector('[data-plan-price="' + plan + '"]');
        if (!el) return;
        var base = TIERS[plan].price;
        var current = parseInt(el.textContent.replace(/[^\d]/g, ''), 10) || base;
        // Yearly = (12 - YEARLY_DISCOUNT_MONTHS) billed months, shown as effective per-month cost.
        var billedMonths = 12 - YEARLY_DISCOUNT_MONTHS;
        var target = isYearly ? Math.round(base * billedMonths / 12) : base;
        var priceBlock = el.parentElement;
        priceBlock.classList.add('is-updating');
        setTimeout(function () { priceBlock.classList.remove('is-updating'); }, 450);
        tweenNumber(el, current, target, 450, function (n) { return '৳' + groupNum(n); });

        var unitEl = priceBlock.querySelector('.lp-pricing-card__unit');
        if (unitEl) unitEl.textContent = '/mo';

        var noteEl = document.querySelector('[data-plan-note="' + plan + '"]');
        if (noteEl) {
          var savePercent = Math.round(YEARLY_DISCOUNT_MONTHS / 12 * 100);
          noteEl.textContent = isYearly
            ? fmt(base * billedMonths) + ' billed yearly · save ' + savePercent + '%'
            : '~৳' + (base / TIERS[plan].cap).toFixed(2) + ' per user';
        }

        var cta = document.querySelector('[data-plan-cta="' + plan + '"]');
        if (cta) {
          cta.href = cta.href.split('?')[0] + '?plan=' + plan + '&cycle=' + (isYearly ? 'yearly' : 'monthly');
        }
      });
    }

    if (toggle) {
      toggle.addEventListener('click', function () {
        isYearly = !isYearly;
        toggle.classList.toggle('is-yearly', isYearly);
        if (monthlyLabel) monthlyLabel.classList.toggle('is-active', !isYearly);
        if (yearlyLabel) yearlyLabel.classList.toggle('is-active', isYearly);
        updateFixedPrices();
      });
    }

    /* Fixed-plan "show more" — reveals Business/Enterprise tiers for larger ISPs */
    var showMoreBtn = document.getElementById('lp-pricing-showmore');
    var extraGrid = document.getElementById('lp-pricing-extra');
    if (showMoreBtn && extraGrid) {
      var showMoreLabel = showMoreBtn.querySelector('span');
      var showMoreCollapsedText = showMoreLabel ? showMoreLabel.textContent : '';
      showMoreBtn.addEventListener('click', function () {
        var expand = extraGrid.hidden;
        extraGrid.hidden = !expand;
        showMoreBtn.setAttribute('aria-expanded', expand ? 'true' : 'false');
        showMoreBtn.classList.toggle('is-expanded', expand);
        if (showMoreLabel) showMoreLabel.textContent = expand ? 'Show fewer plans' : showMoreCollapsedText;
      });
    }

    /* PAYG wallet calculator */
    var slider = document.getElementById('lp-user-slider');
    var topupSelect = document.getElementById('lp-topup-months');
    var addons = {};
    Object.keys(ADDON_PRICES).forEach(function (key) {
      addons[key] = ADDON_PRICES[key].price || 0;
    });
    if (!Object.keys(addons).length) {
      addons = { sms: 200, whitelabel: 500, backup: 150, whatsapp: 100 };
    }
    var balancePulseTimer = null;

    function fixedTierFor(users) {
      if (users <= TIERS.basic.cap) return { name: 'Basic', price: TIERS.basic.price };
      if (users <= TIERS.standard.cap) return { name: 'Standard', price: TIERS.standard.price };
      if (users <= TIERS.premium.cap) return { name: 'Premium', price: TIERS.premium.price };
      if (users <= TIERS.business.cap) return { name: 'Business', price: TIERS.business.price };
      if (users <= TIERS.enterprise.cap) return { name: 'Enterprise', price: TIERS.enterprise.price };
      if (users <= TIERS.ultimate.cap) return { name: 'Ultimate', price: TIERS.ultimate.price };
      return null; // beyond Ultimate → custom plan territory
    }

    function getMonthlyCost() {
      var users = slider ? parseInt(slider.value, 10) : 600;
      var total = PAYG.platform + users * PAYG.perUser;
      document.querySelectorAll('.lp-custom-pricing__addon input:checked').forEach(function (cb) {
        total += addons[cb.value] || 0;
      });
      return { users: users, total: total };
    }

    var countPulseTimer = null;
    var monthlyPulseTimer = null;

    function updatePaygWallet() {
      syncRangeFill(slider);
      var cost = getMonthlyCost();
      var months = topupSelect ? parseInt(topupSelect.value, 10) : 2;
      var topup = Math.max(PAYG.minWallet, cost.total * months);
      var after1 = Math.max(0, topup - cost.total);
      var afterN = Math.max(0, topup - cost.total * months);

      var els = {
        count: document.getElementById('lp-user-count'),
        formula: document.getElementById('lp-price-formula'),
        monthly: document.getElementById('lp-payg-monthly'),
        balance: document.getElementById('lp-wallet-balance'),
        min: document.getElementById('lp-wallet-min'),
        topup: document.getElementById('lp-wallet-topup'),
        deduct: document.getElementById('lp-wallet-deduct'),
        after1: document.getElementById('lp-wallet-after1'),
        after2: document.getElementById('lp-wallet-after2'),
        after2Label: document.getElementById('lp-wallet-after2-label'),
        progress: document.getElementById('lp-wallet-progress'),
        status: document.getElementById('lp-wallet-status'),
        hint: document.getElementById('lp-payg-hint'),
        cta: document.getElementById('lp-payg-cta'),
        ctaAmount: document.getElementById('lp-payg-cta-amount')
      };

      if (els.count) {
        els.count.textContent = groupNum(cost.users) + ' users';
        els.count.classList.add('is-updating');
        if (countPulseTimer) clearTimeout(countPulseTimer);
        countPulseTimer = setTimeout(function () { els.count.classList.remove('is-updating'); }, 200);
      }
      if (slider) slider.setAttribute('aria-valuetext', groupNum(cost.users) + ' users');
      if (els.formula) {
        els.formula.textContent = fmt(PAYG.platform) + ' + ' + groupNum(cost.users) +
          ' users × ৳' + PAYG.perUser.toFixed(2) + ' = ' + fmt(cost.total) + '/mo';
      }
      if (els.monthly) {
        els.monthly.textContent = fmt(cost.total);
        els.monthly.classList.add('is-updating');
        if (monthlyPulseTimer) clearTimeout(monthlyPulseTimer);
        monthlyPulseTimer = setTimeout(function () { els.monthly.classList.remove('is-updating'); }, 200);
      }
      if (els.balance) {
        els.balance.textContent = fmt(topup);
        els.balance.classList.add('is-updating');
        if (balancePulseTimer) clearTimeout(balancePulseTimer);
        balancePulseTimer = setTimeout(function () { els.balance.classList.remove('is-updating'); }, 350);
      }
      if (els.min) els.min.textContent = fmt(PAYG.minWallet);
      if (els.topup) els.topup.textContent = fmt(topup);
      if (els.deduct) els.deduct.textContent = '− ' + fmt(cost.total);
      if (els.after1) els.after1.textContent = fmt(after1);
      if (els.after2) els.after2.textContent = fmt(afterN);
      if (els.after2Label) {
        els.after2Label.textContent = 'Balance after ' + months + ' month' + (months > 1 ? 's' : '');
      }
      if (els.ctaAmount) els.ctaAmount.textContent = fmt(topup);
      if (els.cta) {
        // plan=payg + addons= are understood by the registration gate preselect.
        var checkedAddons = [];
        document.querySelectorAll('.lp-custom-pricing__addon input:checked').forEach(function (cb) {
          checkedAddons.push(cb.value);
        });
        els.cta.href = els.cta.href.split('?')[0] +
          '?plan=payg&users=' + cost.users + '&topup=' + Math.round(topup) +
          (checkedAddons.length ? '&addons=' + checkedAddons.join(',') : '');
      }
      if (els.progress) {
        var ratio = topup > 0 ? Math.min(1, after1 / topup) : 0;
        els.progress.style.transform = 'scaleX(' + ratio.toFixed(4) + ')';
      }

      // Wallet deduct preview lines follow the live monthly cost.
      document.querySelectorAll('#lp-wallet-animation .lp-wallet-deduct-amount').forEach(function (line) {
        line.textContent = '−' + fmt(cost.total);
      });

      if (els.status) {
        els.status.className = 'lp-wallet-card__status';
        if (months >= 3) {
          els.status.innerHTML = '<i class="fas fa-check-circle"></i> Covers <strong>' + months + ' months</strong> — best value';
        } else if (months >= 2) {
          els.status.innerHTML = '<i class="fas fa-check-circle"></i> Covers <strong>' + months + ' months</strong> at current usage';
        } else {
          els.status.classList.add('is-warning');
          els.status.innerHTML = '<i class="fas fa-exclamation-circle"></i> Minimum top-up — consider <strong>2+ months</strong>';
        }
      }

      // Honest breakeven guidance: recommend the fixed plan when it is cheaper.
      if (els.hint) {
        var tier = fixedTierFor(cost.users);
        if (tier && tier.price < cost.total) {
          els.hint.innerHTML = '<i class="fas fa-lightbulb"></i> At ' + groupNum(cost.users) +
            ' users the <strong>' + tier.name + ' plan (' + fmt(tier.price) +
            '/mo)</strong> is cheaper — best if your subscriber count is stable.';
          els.hint.hidden = false;
        } else {
          els.hint.hidden = true;
        }
      }

      var savingsCta = document.getElementById('lp-payg-savings-cta');
      var savingsAmt = document.getElementById('lp-payg-savings-amount');
      if (savingsCta && savingsAmt) {
        var tierForSave = fixedTierFor(cost.users);
        if (tierForSave && tierForSave.price > cost.total) {
          var yearlySave = (tierForSave.price - cost.total) * 12;
          savingsAmt.textContent = fmt(yearlySave) + '/year';
          savingsCta.hidden = false;
        } else {
          savingsCta.hidden = true;
        }
      }
    }

    if (slider) {
      slider.addEventListener('input', updatePaygWallet);
      document.querySelectorAll('.lp-custom-pricing__addon input').forEach(function (cb) {
        cb.addEventListener('change', updatePaygWallet);
      });
    }
    if (topupSelect) topupSelect.addEventListener('change', updatePaygWallet);

    /* Segmented months picker drives the (visually-hidden) <select> that the
       wallet math reads — one tap, all options visible, no dropdown to open. */
    var topupSeg = document.querySelector('.lp-payg__topup-seg');
    if (topupSeg && topupSelect) {
      topupSeg.addEventListener('click', function (e) {
        var btn = e.target.closest('.lp-payg__topup-opt');
        if (!btn) return;
        topupSeg.querySelectorAll('.lp-payg__topup-opt').forEach(function (b) {
          var on = b === btn;
          b.classList.toggle('is-active', on);
          b.setAttribute('aria-checked', on ? 'true' : 'false');
        });
        topupSelect.value = btn.getAttribute('data-months');
        updatePaygWallet();
      });
    }
    updatePaygWallet();
  }

  /* ── ROI calculator ── */
  function initRoi() {
    var btn = document.getElementById('lp-roi-calc');
    if (!btn) return;

    var subSlider = document.getElementById('lp-roi-subs');
    var subDisplay = document.getElementById('lp-roi-subs-display');
    var costInput = document.getElementById('lp-roi-cost');
    var result = document.getElementById('lp-roi-result');
    var savingsEl = document.getElementById('lp-roi-savings');
    var monthlyEl = document.getElementById('lp-roi-monthly');

    if (subSlider && subDisplay) {
      syncRangeFill(subSlider);
      subSlider.addEventListener('input', function () {
        var subs = parseInt(subSlider.value, 10);
        subDisplay.textContent = groupNum(subs);
        subSlider.setAttribute('aria-valuetext', groupNum(subs) + ' subscribers');
        syncRangeFill(subSlider);
      });
    }

    btn.addEventListener('click', function () {
      var subs = parseInt(subSlider ? subSlider.value : 500, 10);
      var currentCost = parseFloat(costInput ? costInput.value : 0) || 0;
      // Best price we offer at this subscriber count: PAYG capped by the fixed tier.
      var ourCost = PAYG.platform + subs * PAYG.perUser;
      if (subs <= TIERS.basic.cap) ourCost = Math.min(ourCost, TIERS.basic.price);
      else if (subs <= TIERS.standard.cap) ourCost = Math.min(ourCost, TIERS.standard.price);
      else if (subs <= TIERS.premium.cap) ourCost = Math.min(ourCost, TIERS.premium.price);
      else if (subs <= TIERS.business.cap) ourCost = Math.min(ourCost, TIERS.business.price);
      else if (subs <= TIERS.enterprise.cap) ourCost = Math.min(ourCost, TIERS.enterprise.price);
      else if (subs <= TIERS.ultimate.cap) ourCost = Math.min(ourCost, TIERS.ultimate.price);

      var monthlySaving = Math.max(0, currentCost - ourCost);
      var yearlySaving = monthlySaving * 12;

      if (savingsEl) savingsEl.textContent = fmt(yearlySaving) + '/year';
      if (monthlyEl) monthlyEl.textContent = 'That\'s ' + fmt(monthlySaving) + '/month back in your business';
      if (result) {
        result.classList.remove('is-visible');
        void result.offsetWidth;
        result.classList.add('is-visible');
      }
    });
  }

  /* ── Testimonial slider: dots + polite autoplay ── */
  function initTestimonialSlider() {
    var track = document.getElementById('lp-testimonials-track');
    var dots = document.getElementById('lp-testimonials-dots');
    if (!track || !dots) return;

    var cards = track.querySelectorAll('.lp-testimonial');
    if (cards.length <= 1) {
      if (dots) dots.style.display = 'none';
      return;
    }
    var dotBtns = dots.querySelectorAll('button');
    var current = 0;
    var autoplayTimer = null;
    var resumeTimer = null;
    var trackVisible = false;
    var mobileQuery = window.matchMedia('(max-width: 768px)');

    function setActiveDot(index) {
      current = index;
      dotBtns.forEach(function (d, i) { d.classList.toggle('is-active', i === index); });
    }

    function scrollToSlide(index) {
      var card = cards[index];
      if (!card) return;
      track.scrollTo({ left: card.offsetLeft - track.offsetLeft, behavior: prefersReducedMotion ? 'auto' : 'smooth' });
      setActiveDot(index);
    }

    dotBtns.forEach(function (btn) {
      btn.addEventListener('click', function () {
        pauseAutoplay();
        scrollToSlide(parseInt(btn.getAttribute('data-slide'), 10));
      });
    });

    // Keep dots in sync with manual swipes (rAF-debounced scroll listener).
    var syncPending = false;
    track.addEventListener('scroll', function () {
      if (syncPending) return;
      syncPending = true;
      requestAnimationFrame(function () {
        syncPending = false;
        var mid = track.scrollLeft + track.clientWidth / 2;
        cards.forEach(function (card, i) {
          if (card.offsetLeft - track.offsetLeft <= mid && mid < card.offsetLeft - track.offsetLeft + card.offsetWidth) {
            if (i !== current) setActiveDot(i);
          }
        });
      });
    }, { passive: true });

    function startAutoplay() {
      if (autoplayTimer || prefersReducedMotion || !mobileQuery.matches || !trackVisible) return;
      autoplayTimer = setInterval(function () {
        scrollToSlide((current + 1) % cards.length);
      }, 5000);
    }
    function stopAutoplay() {
      if (autoplayTimer) {
        clearInterval(autoplayTimer);
        autoplayTimer = null;
      }
    }
    function pauseAutoplay() {
      stopAutoplay();
      if (resumeTimer) clearTimeout(resumeTimer);
      resumeTimer = setTimeout(startAutoplay, 8000);
    }

    ['pointerdown', 'touchstart'].forEach(function (evt) {
      track.addEventListener(evt, pauseAutoplay, { passive: true });
    });

    // Only autoplay while the section is on screen.
    new IntersectionObserver(function (entries) {
      trackVisible = entries[0].isIntersecting;
      if (trackVisible) startAutoplay();
      else stopAutoplay();
    }, { threshold: 0.3 }).observe(track);

    // React to viewport changes instead of a one-time innerWidth check.
    var onMediaChange = function () {
      stopAutoplay();
      startAutoplay();
    };
    if (mobileQuery.addEventListener) mobileQuery.addEventListener('change', onMediaChange);
    else if (mobileQuery.addListener) mobileQuery.addListener(onMediaChange);
  }

  /* Anchor navigation relies on CSS `scroll-behavior: smooth` + `scroll-padding-top`
     (landing.css) — no JS scroll hijack. Move focus for keyboard/AT users. */
  function initAnchorFocus() {
    document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
      anchor.addEventListener('click', function () {
        var href = anchor.getAttribute('href');
        if (!href || href === '#') return;
        var target = document.querySelector(href);
        if (!target) return;
        target.setAttribute('tabindex', '-1');
        target.focus({ preventScroll: true });
      });
    });
  }

  /* ── Hero parallax: rAF + lerp, stopped when the hero is off-screen ── */
  function initParallax() {
    if (prefersReducedMotion || window.innerWidth < 1024) return;
    var stack = document.querySelector('.lp-hero__console-wrap');
    var hero = document.getElementById('lp-hero');
    if (!stack || !hero) return;

    var tx = 0, ty = 0, cx = 0, cy = 0, running = false;

    function loop() {
      cx += (tx - cx) * 0.08;
      cy += (ty - cy) * 0.08;
      stack.style.transform = 'translate3d(' + cx.toFixed(2) + 'px,' + cy.toFixed(2) + 'px,0)';
      if (running) requestAnimationFrame(loop);
    }

    document.addEventListener('mousemove', function (e) {
      tx = (e.clientX / window.innerWidth - 0.5) * 20;
      ty = (e.clientY / window.innerHeight - 0.5) * 12;
    }, { passive: true });

    new IntersectionObserver(function (entries) {
      var visible = entries[0].isIntersecting;
      if (visible && !running) {
        running = true;
        requestAnimationFrame(loop);
      } else if (!visible) {
        running = false;
      }
    }, { threshold: 0 }).observe(hero);
  }

  /* ── Lazy-load reCAPTCHA when the contact section approaches ── */
  function initLazyRecaptcha() {
    var contact = document.getElementById('lp-contact');
    if (!contact || !contact.querySelector('.g-recaptcha')) return;
    var loaded = false;
    function load() {
      if (loaded) return;
      loaded = true;
      var s = document.createElement('script');
      s.src = 'https://www.google.com/recaptcha/api.js';
      s.async = true;
      s.defer = true;
      document.head.appendChild(s);
    }
    if (!('IntersectionObserver' in window)) {
      load();
      return;
    }
    var io = new IntersectionObserver(function (entries) {
      if (entries[0].isIntersecting) {
        load();
        io.disconnect();
      }
    }, { rootMargin: '600px' });
    io.observe(contact);
  }

  /* ── Language toggle (EN / BN) ── */
  function initLangToggle() {
    var stored = null;
    try { stored = localStorage.getItem('lp_lang'); } catch (e) { /* private mode */ }
    var lang = stored === 'bn' ? 'bn' : 'en';

    function applyLang(next) {
      lang = next === 'bn' ? 'bn' : 'en';
      document.documentElement.lang = lang === 'bn' ? 'bn' : 'en';
      document.querySelectorAll('.lp-lang--en').forEach(function (el) {
        el.hidden = lang !== 'en';
      });
      document.querySelectorAll('.lp-lang--bn').forEach(function (el) {
        el.hidden = lang !== 'bn';
      });
      document.querySelectorAll('[data-lp-lang]').forEach(function (btn) {
        var active = btn.getAttribute('data-lp-lang') === lang;
        btn.classList.toggle('is-active', active);
        btn.setAttribute('aria-pressed', active ? 'true' : 'false');
      });
      try { localStorage.setItem('lp_lang', lang); } catch (e) { /* noop */ }
    }

    document.querySelectorAll('[data-lp-lang]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        applyLang(btn.getAttribute('data-lp-lang'));
      });
    });
    applyLang(lang);
  }

  /* ── Pre-select inquiry type from CTA clicks ── */
  function initInquiryPreselect() {
    document.querySelectorAll('[data-lp-inquiry]').forEach(function (el) {
      el.addEventListener('click', function () {
        var value = el.getAttribute('data-lp-inquiry');
        if (!value) return;
        var radio = document.querySelector('input[name="inquiryType"][value="' + value + '"]');
        if (radio) radio.checked = true;
      });
    });
  }

  /* ── Hero reconciliation feed: payments streaming in and reconnecting ──
     Illustrative demo (example ৳ amounts + subscriber IDs, not live metrics).
     A new row enters as "matching" (amber) then settles to "reconnected"
     (green). Gated on visibility; reduced-motion leaves the static rows as-is. */
  function initReconFeed() {
    var feed = document.getElementById('lp-recon-feed');
    if (!feed) return;
    if (prefersReducedMotion) return; // keep the settled static rows

    var TX = [
      { ch: 'bKash · Send Money', amt: '৳1,200', sub: '#SUB-4182' },
      { ch: 'Nagad · Send Money', amt: '৳800', sub: '#SUB-2290' },
      { ch: 'bKash · Payment', amt: '৳1,500', sub: '#SUB-0917' },
      { ch: 'Nagad · Send Money', amt: '৳600', sub: '#SUB-3355' },
      { ch: 'bKash · Send Money', amt: '৳950', sub: '#SUB-1174' },
      { ch: 'bKash · Payment', amt: '৳2,300', sub: '#SUB-5061' },
      { ch: 'Nagad · Send Money', amt: '৳700', sub: '#SUB-8420' },
      { ch: 'bKash · Send Money', amt: '৳1,050', sub: '#SUB-6293' }
    ];
    var idx = 4; // static rows already used the first four
    var MAX = 4;

    function settle(row) {
      row.classList.remove('is-matching');
      row.classList.add('is-online');
      var st = row.querySelector('.lp-recon__state');
      if (st) { st.className = 'lp-recon__state is-online'; st.textContent = 'reconnected'; }
      var sub = row.querySelector('.lp-recon__sub');
      if (sub) sub.textContent = sub.textContent.replace('match →', 'matched →');
    }

    function push() {
      var tx = TX[idx % TX.length];
      idx++;
      var row = document.createElement('div');
      row.className = 'lp-recon__row is-matching is-entering';
      row.innerHTML =
        '<span class="lp-recon__ev"><i class="fas fa-bolt"></i>' + tx.ch + '</span>' +
        '<span class="lp-recon__amt">' + tx.amt + '</span>' +
        '<span class="lp-recon__link"><span class="lp-recon__sub">match → ' + tx.sub + '</span>' +
        '<span class="lp-recon__state is-matching">matching</span></span>';
      feed.insertBefore(row, feed.firstChild);
      while (feed.children.length > MAX) feed.removeChild(feed.lastChild);
      setTimeout(function () { settle(row); }, 1100);
    }

    // Animate the initial "matching" row to reconnected shortly after load.
    var firstMatching = feed.querySelector('.lp-recon__row.is-matching');
    if (firstMatching) setTimeout(function () { settle(firstMatching); }, 1300);

    var running = false, timer = null;
    function start() { if (running) return; running = true; timer = setInterval(push, 2600); }
    function stop() { running = false; if (timer) { clearInterval(timer); timer = null; } }

    if ('IntersectionObserver' in window) {
      new IntersectionObserver(function (entries) {
        if (entries[0].isIntersecting) start(); else stop();
      }, { threshold: 0.2 }).observe(feed);
    } else {
      start();
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    initNav();
    initLangToggle();
    initInquiryPreselect();
    initReveal();
    initCounters();
    initProductTabs();
    initFeatures();
    initFaq();
    initPricing();
    initRoi();
    initTestimonialSlider();
    initAnchorFocus();
    initParallax();
    initReconFeed();
    initLazyRecaptcha();
  });
})();
