<?php
    // Merged proof section: live stat counters + partner marquee + (optional)
    // case study + (optional) testimonials. Keeps #lp-proof (nav), and #lp-stats
    // / #lp-case-study so the footer's "Achievements" / "News" anchors resolve.
    $lpTrustedIsps = (int) ($trusted_isps ?? 0);
    $lpProofUsers  = (int) ($active_users ?? 0);

    $partnerLogoBase = base_url('assets/img/partners/');
    $partnerLogos = [
        ['file' => 'img4.jpg', 'alt' => 'The Angel Network'],
        ['file' => 'img5.jpg', 'alt' => 'Sterlink BD'],
        ['file' => 'img6.jpg', 'alt' => 'BD Link Technologies'],
        ['file' => 'img7.jpg', 'alt' => 'Mango Teleservices'],
        ['file' => 'img8.jpg', 'alt' => 'Zen Link Internet Service'],
        ['file' => 'active-broadband-internet.jpeg', 'alt' => 'Active Broadband Internet'],
        ['file' => 'telnet.jpeg', 'alt' => 'Telnet'],
        ['file' => 'img1.jpg', 'alt' => 'Rocket Internet'],
        ['file' => 'img2.jpg', 'alt' => 'BIT Network'],
        ['file' => 'img3.jpg', 'alt' => 'Triangle'],
    ];
    // Two copies for a seamless marquee loop.
    $partnerLogosMarquee = array_merge($partnerLogos, $partnerLogos);

    $lpCase = $lpCaseStudy ?? null;
    $lpHasCase = !empty($lpCase) && !empty($lpCase['name']);

    $lpProofTestimonials = $lpTestimonials ?? [];
?>
<section class="lp-section lp-section--dark" id="lp-proof" data-lp-section>
    <div class="lp-container">
        <div class="lp-section__header lp-reveal" style="margin-bottom:40px;">
            <span class="lp-section__label">Trusted By</span>
            <h2 class="lp-section__title">From Dhaka to Rangpur, operators run on one reconciled ledger.</h2>
            <p class="lp-section__desc"><?= $lpTrustedIsps ?>+ operators run billing, MikroTik provisioning, and bKash/Nagad reconciliation on one panel — live since 2020.</p>
        </div>

        <div class="lp-stats" id="lp-stats">
            <div class="lp-stat lp-reveal">
                <div class="lp-stat__icon"><i class="fas fa-building"></i></div>
                <div class="lp-stat__value" data-count="<?= $lpTrustedIsps ?>" data-suffix="+">0</div>
                <div class="lp-stat__label">Operators live</div>
            </div>
            <div class="lp-stat lp-reveal lp-reveal-delay-1">
                <div class="lp-stat__icon"><i class="fas fa-users"></i></div>
                <div class="lp-stat__value" data-count="<?= $lpProofUsers ?>" data-suffix="+">0</div>
                <div class="lp-stat__label">Subscribers billed</div>
            </div>
            <div class="lp-stat lp-reveal lp-reveal-delay-2">
                <div class="lp-stat__icon"><i class="fas fa-network-wired"></i></div>
                <div class="lp-stat__value">Unlimited</div>
                <div class="lp-stat__label">MikroTik Routers</div>
            </div>
            <div class="lp-stat lp-reveal lp-reveal-delay-3">
                <div class="lp-stat__icon"><i class="fas fa-calendar-check"></i></div>
                <div class="lp-stat__value" data-count="<?= max(1, (int) date('Y') - 2020) ?>" data-suffix="+">0</div>
                <div class="lp-stat__label">Years running</div>
            </div>
        </div>
    </div>

    <hr class="lp-subdivider">

    <div class="lp-container">
        <p class="lp-subsection-label">Trusted on the ground by ISPs in 30+ districts</p>
    </div>
    <div class="lp-marquee lp-reveal">
        <div class="lp-marquee__inner">
            <?php foreach ($partnerLogosMarquee as $logo): ?>
                <div class="lp-marquee__item lp-marquee__item--partner">
                    <img src="<?= esc($partnerLogoBase . rawurlencode($logo['file']), 'attr') ?>" alt="<?= esc($logo['alt']) ?>">
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if ($lpHasCase): ?>
        <?php
        $csName = (string) ($lpCase['name'] ?? '');
        $csCompany = (string) ($lpCase['company'] ?? '');
        $csMetric = (string) ($lpCase['metric'] ?? '');
        $csQuote = (string) ($lpCase['quote'] ?? '');
        $csLogo = (string) ($lpCase['logo_url'] ?? '');
        $csShot = (string) ($lpCase['screenshot_url'] ?? ($dashboardImg ?? ''));
        $csTitle = $csCompany !== '' ? $csCompany : $csName;
        ?>
        <div class="lp-container" id="lp-case-study">
            <hr class="lp-subdivider">
            <p class="lp-subsection-label">Field report — how <?= esc($csTitle) ?> runs on ISP Pay BD</p>
            <div class="lp-case-study lp-reveal">
                <div class="lp-case-study__copy">
                    <?php if ($csLogo !== ''): ?>
                        <img src="<?= esc($csLogo, 'attr') ?>" alt="<?= esc($csTitle) ?> logo" class="lp-case-study__logo" loading="lazy">
                    <?php endif; ?>
                    <?php if ($csMetric !== ''): ?>
                        <p class="lp-case-study__metric"><?= esc($csMetric) ?></p>
                    <?php endif; ?>
                    <?php if ($csQuote !== ''): ?>
                        <blockquote class="lp-case-study__quote">&ldquo;<?= esc($csQuote) ?>&rdquo;</blockquote>
                    <?php endif; ?>
                    <p class="lp-case-study__author"><strong><?= esc($csName) ?></strong><?= $csCompany !== '' ? ' · ' . esc($csCompany) : '' ?></p>
                </div>
                <?php if ($csShot !== ''): ?>
                <div class="lp-case-study__visual">
                    <img src="<?= esc($csShot, 'attr') ?>" alt="Dashboard screenshot from <?= esc($csTitle) ?>" loading="lazy" decoding="async">
                </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($lpProofTestimonials)): ?>
        <div class="lp-container">
            <hr class="lp-subdivider">
            <p class="lp-subsection-label">From operators who stopped chasing payments</p>
            <div class="lp-testimonials" id="lp-testimonials-track">
                <?php foreach ($lpProofTestimonials as $i => $item): ?>
                    <?php
                    $rating = max(1, min(5, (int) ($item['rating'] ?? 5)));
                    $initials = trim((string) ($item['avatar_initials'] ?? ''));
                    if ($initials === '') {
                        $parts = preg_split('/\s+/', trim((string) ($item['name'] ?? '')), 2);
                        $initials = strtoupper(substr($parts[0] ?? 'I', 0, 1) . substr($parts[1] ?? 'P', 0, 1));
                    }
                    $roleLine = trim((string) ($item['role'] ?? ''));
                    $company = trim((string) ($item['company'] ?? ''));
                    if ($roleLine !== '' && $company !== '') {
                        $roleLine .= ', ' . $company;
                    } elseif ($company !== '') {
                        $roleLine = $company;
                    }
                    $delayClass = $i > 0 ? ' lp-reveal-delay-' . min($i, 3) : '';
                    ?>
                    <div class="lp-testimonial lp-reveal<?= esc($delayClass) ?>">
                        <div class="lp-testimonial__stars" aria-label="<?= $rating ?> out of 5 stars">
                            <?php for ($s = 0; $s < $rating; $s++): ?><i class="fas fa-star"></i><?php endfor; ?>
                        </div>
                        <p class="lp-testimonial__quote">&ldquo;<?= esc($item['quote'] ?? '') ?>&rdquo;</p>
                        <div class="lp-testimonial__author">
                            <div class="lp-testimonial__avatar"><?= esc($initials) ?></div>
                            <div>
                                <div class="lp-testimonial__name"><?= esc($item['name'] ?? '') ?></div>
                                <?php if ($roleLine !== ''): ?>
                                    <div class="lp-testimonial__role"><?= esc($roleLine) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if (count($lpProofTestimonials) > 1): ?>
            <div class="lp-testimonials-dots" id="lp-testimonials-dots">
                <?php foreach ($lpProofTestimonials as $i => $item): ?>
                    <button type="button" class="<?= $i === 0 ? 'is-active' : '' ?>" data-slide="<?= (int) $i ?>" aria-label="Testimonial <?= (int) $i + 1 ?>" style="box-sizing:content-box;background-clip:content-box;padding:14px;margin:-14px;"></button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>
