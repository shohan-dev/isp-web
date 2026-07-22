<?php
$lpActiveUsers = (int) ($active_users ?? 0);
$lpActiveAdmins = (int) ($active_admins ?? 0);
$lpRegistrationUrl = route_to('route.auth.registration');
?>
<section class="lp-hero" id="lp-hero" data-lp-section>
    <div class="lp-hero__bg" aria-hidden="true">
        <div class="lp-hero__orb lp-hero__orb--1"></div>
        <div class="lp-hero__orb lp-hero__orb--2"></div>
        <div class="lp-hero__grid-pattern"></div>
    </div>
    <div class="lp-container">
        <div class="lp-hero__grid">
            <div class="lp-hero__content lp-reveal">
                <div class="lp-hero__badge">
                    <span class="lp-hero__badge-dot"></span>
                    Trusted by <?= (int) ($trusted_isps ?? 0) ?>+ ISPs across Bangladesh
                </div>
                <h1 class="lp-hero__title">
                    <span class="lp-hero__title-line lp-lang lp-lang--en">Run your whole ISP from one operator's console</span>
                    <span class="lp-hero__title-line lp-lang lp-lang--bn" hidden>পুরো ISP চালান একটি অপারেটর কনসোল থেকে</span>
                    <span class="lp-hero__title-accent lp-lang lp-lang--en">Billing, MikroTik sync, and every bKash &amp; Nagad payment — reconciled automatically.</span>
                    <span class="lp-hero__title-accent lp-lang lp-lang--bn" hidden>বিলিং, মাইক্রোটিক সিঙ্ক, এবং প্রতিটি বিকাশ ও নগদ পেমেন্ট — স্বয়ংক্রিয়ভাবে মিলিয়ে নেওয়া।</span>
                </h1>
                <p class="lp-hero__desc">
                    <span class="lp-lang lp-lang--en">ISP Pay BD auto-reconciles every bKash/Nagad payment to the right customer, syncs MikroTik PPPoE/hotspot in real time, disconnects on expiry and reconnects on payment, and gives every subscriber a branded Bangla app. Fixed monthly plan, or a low per-customer Pay-As-You-Go rate — no tier caps.</span>
                    <span class="lp-lang lp-lang--bn" hidden>ISP Pay BD স্বয়ংক্রিয়ভাবে বিকাশ/নগদ পেমেন্ট মিলিয়ে নেয়, মাইক্রোটিক সিঙ্ক করে, মেয়াদ শেষে ডিসকানেক্ট ও পেমেন্টে রিকানেক্ট করে, এবং প্রতিটি গ্রাহককে ব্র্যান্ডেড বাংলা অ্যাপ দেয়। ফিক্সড প্ল্যান, অথবা প্রতি গ্রাহকের জন্য স্বল্প খরচে পে-অ্যাজ-ইউ-গো — কোনো টিয়ার সীমা নেই।</span>
                </p>
                <div class="lp-hero__ctas">
                    <a href="<?= $lpRegistrationUrl ?>" class="lp-btn lp-btn--primary lp-btn--lg lp-btn--shimmer">
                        <i class="fas fa-rocket"></i>
                        <span class="lp-lang lp-lang--en">Start Free Trial — no card required</span>
                        <span class="lp-lang lp-lang--bn" hidden>ফ্রি ট্রায়াল শুরু করুন</span>
                    </a>
                    <a href="#lp-auto-reconcile" class="lp-hero__secondary-link">
                        <i class="fas fa-play-circle" aria-hidden="true"></i>
                        <span class="lp-lang lp-lang--en">See it reconcile</span>
                        <span class="lp-lang lp-lang--bn" hidden>মিলিয়ে নেওয়া দেখুন</span>
                    </a>
                </div>
                <div class="lp-hero__trust-row">
                    <div class="lp-hero__trust-item"><i class="fas fa-check-circle"></i> Set up in minutes</div>
                    <div class="lp-hero__trust-item"><i class="fas fa-check-circle"></i> No credit card</div>
                    <div class="lp-hero__trust-item"><i class="fas fa-check-circle"></i> Cancel anytime</div>
                </div>
                <div class="lp-hero__pills">
                    <span class="lp-hero__pill">Auto-Reconciliation</span>
                    <span class="lp-hero__pill">Mikrotik Sync</span>
                    <span class="lp-hero__pill">Pay-As-You-Go</span>
                    <span class="lp-hero__pill">Bangla App</span>
                </div>
            </div>

            <div class="lp-hero__visual lp-reveal lp-reveal-delay-2">
                <?php
                /* Orbital integrations diagram — the Bangladesh ISP stack the platform
                   wires into. Two counter-rotating rings; labels counter-spin to stay
                   upright. Angles in deg (0 = top, clockwise); radius from the ring var. */
                $lpHeroOrbit = [
                    ['icon' => 'fa-server',          'label' => 'MikroTik',  'desc' => 'Real-time PPPoE & hotspot sync',              'ring' => 2, 'a' => 0],
                    ['icon' => 'fa-tower-broadcast', 'label' => 'Cisco',     'desc' => 'Enterprise router & switch control',          'ring' => 2, 'a' => 72],
                    ['icon' => 'fa-sitemap',         'label' => 'vBNG',      'desc' => 'Virtual BNG session management',              'ring' => 2, 'a' => 144],
                    ['icon' => 'fa-sun',             'label' => 'OLT / ONU', 'desc' => 'GPON optical power & ONU status',             'ring' => 2, 'a' => 216],
                    ['icon' => 'fa-shield-halved',   'label' => 'RADIUS',    'desc' => 'AAA auth, accounting & CoA',                  'ring' => 2, 'a' => 288],
                    ['icon' => 'fa-comment-dots',    'label' => 'SMS',       'desc' => 'Bulk SMS across local gateways',              'ring' => 1, 'a' => 36],
                    ['icon' => 'fa-credit-card',     'label' => 'Payment',   'desc' => 'bKash, Nagad & card reconciliation',          'ring' => 1, 'a' => 108],
                    ['icon' => 'fa-wifi',            'label' => 'PPPoE',     'desc' => 'Disconnect on expiry, reconnect on payment',  'ring' => 1, 'a' => 180],
                    ['icon' => 'fa-code',            'label' => 'API',       'desc' => 'REST API for every resource',                 'ring' => 1, 'a' => 252],
                    ['icon' => 'fa-globe',           'label' => 'IPv6',      'desc' => 'Dual-stack IPv4 / IPv6 delivery',             'ring' => 1, 'a' => 324],
                ];
                $lpOrbitNode = static function (array $n): string {
                    $rVar = $n['ring'] === 1 ? 'var(--lp-orbit-r1)' : 'var(--lp-orbit-r2)';
                    ob_start(); ?>
                    <div class="lp-orbit__node" style="--a: <?= (int) $n['a'] ?>deg; --r: <?= $rVar ?>;" data-label="<?= esc($n['label'], 'attr') ?>" data-desc="<?= esc($n['desc'], 'attr') ?>" data-icon="<?= esc($n['icon'], 'attr') ?>">
                        <div class="lp-orbit__node-inner lp-orbit__node-inner--<?= (int) $n['ring'] ?>">
                            <span class="lp-orbit__ic"><i class="fas <?= esc($n['icon'], 'attr') ?>"></i></span>
                            <span class="lp-orbit__label"><?= esc($n['label']) ?></span>
                        </div>
                    </div>
                    <?php return ob_get_clean();
                };
                ?>
                <div class="lp-hero__orbit-wrap">
                    <div class="lp-orbit" aria-hidden="true">
                        <div class="lp-orbit__glow"></div>
                        <div class="lp-orbit__ring lp-orbit__ring--1"></div>
                        <div class="lp-orbit__ring lp-orbit__ring--2"></div>
                        <div class="lp-orbit__spin lp-orbit__spin--1">
                            <?php foreach ($lpHeroOrbit as $n) { if ($n['ring'] === 1) echo $lpOrbitNode($n); } ?>
                        </div>
                        <div class="lp-orbit__spin lp-orbit__spin--2">
                            <?php foreach ($lpHeroOrbit as $n) { if ($n['ring'] === 2) echo $lpOrbitNode($n); } ?>
                        </div>
                        <div class="lp-orbit__core">
                            <span class="lp-orbit__core-num">50+</span>
                            <span class="lp-orbit__core-label">Integrations</span>
                        </div>
                    </div>
                    <div class="lp-orbit__detail is-in" id="lp-orbit-detail" aria-live="polite">
                        <span class="lp-orbit__detail-ic"><i class="fas <?= esc($lpHeroOrbit[0]['icon'], 'attr') ?>" id="lp-orbit-detail-icon" aria-hidden="true"></i></span>
                        <span class="lp-orbit__detail-body">
                            <span class="lp-orbit__detail-label" id="lp-orbit-detail-label"><?= esc($lpHeroOrbit[0]['label']) ?></span>
                            <span class="lp-orbit__detail-desc" id="lp-orbit-detail-desc"><?= esc($lpHeroOrbit[0]['desc']) ?></span>
                        </span>
                    </div>
                </div>
                <script>
                /* Solar-system spotlight: highlight each integration one at a time,
                   surface its detail, then loop. Pauses on hover; off for reduced motion. */
                (function () {
                    var orbit = document.querySelector('#lp-hero .lp-orbit');
                    if (!orbit) return;
                    var nodes = Array.prototype.slice.call(orbit.querySelectorAll('.lp-orbit__node'));
                    if (!nodes.length) return;
                    var elDetail = document.getElementById('lp-orbit-detail');
                    var elIcon = document.getElementById('lp-orbit-detail-icon');
                    var elLabel = document.getElementById('lp-orbit-detail-label');
                    var elDesc = document.getElementById('lp-orbit-detail-desc');
                    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                    var idx = 0, timer = null;
                    function show(i) {
                        for (var k = 0; k < nodes.length; k++) {
                            nodes[k].classList.toggle('is-active', k === i);
                        }
                        var n = nodes[i];
                        if (elIcon) elIcon.className = 'fas ' + (n.getAttribute('data-icon') || '');
                        if (elLabel) elLabel.textContent = n.getAttribute('data-label') || '';
                        if (elDesc) elDesc.textContent = n.getAttribute('data-desc') || '';
                        if (elDetail) {
                            elDetail.classList.remove('is-in');
                            void elDetail.offsetWidth; /* restart the card's fade-in */
                            elDetail.classList.add('is-in');
                        }
                    }
                    function next() { idx = (idx + 1) % nodes.length; show(idx); }
                    show(0);
                    function start() { if (!reduce && !timer) timer = setInterval(next, 2600); }
                    function stop() { if (timer) { clearInterval(timer); timer = null; } }
                    start();
                    orbit.addEventListener('mouseenter', function () { stop(); orbit.classList.add('is-paused'); });
                    orbit.addEventListener('mouseleave', function () { orbit.classList.remove('is-paused'); start(); });
                    /* Let a visitor click a node to pin its detail. */
                    orbit.addEventListener('click', function (e) {
                        var node = e.target.closest('.lp-orbit__node');
                        if (!node) return;
                        var i = nodes.indexOf(node);
                        if (i > -1) { idx = i; show(i); }
                    });
                })();
                </script>
            </div>
        </div>
    </div>
</section>
