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
                    <span class="lp-hero__title-line lp-lang lp-lang--en">Run your entire ISP from one dashboard</span>
                    <span class="lp-hero__title-line lp-lang lp-lang--bn" hidden>আপনার পুরো ISP চালান এক ড্যাশবোর্ডে</span>
                    <span class="lp-hero__title-accent lp-lang lp-lang--en">Billing, MikroTik &amp; bKash on autopilot</span>
                    <span class="lp-hero__title-accent lp-lang lp-lang--bn" hidden>বিলিং, মাইক্রোটিক আর বিকাশ/নগদ অটোমেশনে</span>
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
                    <a href="#lp-product" class="lp-hero__secondary-link">
                        <i class="fas fa-play-circle" aria-hidden="true"></i>
                        <span class="lp-lang lp-lang--en">Watch 2-min demo</span>
                        <span class="lp-lang lp-lang--bn" hidden>২ মিনিটের ডেমো দেখুন</span>
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
                <div class="lp-hero__device-stack">
                    <div class="lp-hero__laptop">
                        <div class="lp-browser-frame">
                            <div class="lp-browser-frame__bar">
                                <span></span><span></span><span></span>
                            </div>
                            <img src="<?= esc($dashboardImg ?? base_url('assets/img/icon/laptop-screen.webp') . '?v=4', 'attr') ?>" alt="ISP Pay BD Dashboard" width="560" height="350" loading="eager" fetchpriority="high" decoding="async">
                        </div>
                    </div>
                    <div class="lp-hero__phone">
                        <div class="lp-phone-frame">
                            <div class="lp-phone-frame__notch"></div>
                            <div class="lp-phone-frame__screen">
                                <?php
                                $_phoneLogo = resolvePublicBrandLogoUrl($tenant ?? null, $brandUserId ?? null);
                                $_phoneLogoFull = brandLogoIsFull($_phoneLogo, $brandUserId ?? null);
                                ?>
                                <img src="<?= esc($_phoneLogo, 'attr') ?>" alt="<?= esc($appName ?? resolveBrandTitle($tenant ?? null, $brandUserId ?? null)) ?>" class="lp-phone-frame__app-icon<?= $_phoneLogoFull ? '' : ' lp-phone-frame__app-icon--mark' ?>" loading="lazy" decoding="async">
                                <strong><?= esc($appName ?? 'ISP Pay BD') ?></strong>
                                <span>Customer App</span>
                                <em>Pay Bill · View Usage</em>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
