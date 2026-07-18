<?php
    // Same reasoning as landing/partials/nav.php: keep bare `#lp-x` for an
    // instant same-page scroll when the footer renders on the homepage itself,
    // and only prefix the real homepage path when rendered elsewhere (e.g. the
    // /plugins page), so the link actually lands on the right section instead
    // of no-op'ing against whatever page it's on.
    $lpfHomeHref = route_to('route.home');
    $lpfOnHome = rtrim(parse_url(current_url(), PHP_URL_PATH) ?? '', '/') === rtrim(parse_url($lpfHomeHref, PHP_URL_PATH) ?? '', '/');
    $lpfAnchor = static fn (string $hash) => $lpfOnHome ? $hash : ($lpfHomeHref . $hash);
    // The head-office block below (address/phones/fax/map) is ISP Pay BD's own
    // company identity, not a tenant's — suppress it on white-labeled tenant
    // portals so a reseller's customers aren't shown ISP Pay BD's contact
    // details under what's supposed to be the reseller's own branded page.
    $lpfShowOffice = empty($isTenantPortal);
?>
<footer class="lp-footer">
    <div class="lp-container">
        <div class="lp-footer__grid">
            <div class="lp-footer__brand">
                <?= view('landing/partials/_brand_logo', [
                    'logoUrl' => $logoUrl ?? null,
                    'appName' => $appName ?? null,
                    'brandUserId' => $brandUserId ?? null,
                    'tenant' => $tenant ?? null,
                    'wrapClass' => 'lp-footer__brand-link',
                    'brandHref' => $lpfHomeHref,
                ]); ?>
                <p>Billing, MikroTik provisioning, and bKash/Nagad reconciliation for Bangladesh ISPs — one panel, whole reseller tree.</p>
            </div>
            <div class="lp-footer__col">
                <h4>Product</h4>
                <a href="<?= $lpfAnchor('#lp-features') ?>">Features</a>
                <a href="<?= $lpfAnchor('#lp-pricing') ?>">Pricing</a>
                <a href="<?= $lpfAnchor('#lp-integrations') ?>">Integrations</a>
                <a href="<?= route_to('route.plugins.index') ?>">Plugins</a>
            </div>
            <div class="lp-footer__col">
                <h4>Company</h4>
                <a href="<?= $lpfAnchor('#lp-about') ?>">About Us</a>
                <a href="<?= $lpfAnchor('#lp-contact') ?>">Contact</a>
                <a href="<?= $lpfAnchor('#lp-faq') ?>">FAQ</a>
                <a href="<?= route_to('route.auth.registration') ?>">Free Trial</a>
            </div>
            <div class="lp-footer__col">
                <h4>Support</h4>
                <a href="tel:+8801781808231">+8801781-808231</a>
                <a href="mailto:info@isppaybd.com">info@isppaybd.com</a>
                <a href="<?= $lpfAnchor('#lp-contact') ?>" data-lp-inquiry="Demo Request">Book Demo</a>
                <?php if (!empty(getSession('user_id'))): ?>
                    <a href="<?= route_to('route.dashboard') ?>">Dashboard</a>
                <?php else: ?>
                    <a href="<?= route_to('route.auth.login') ?>">Login</a>
                <?php endif; ?>
            </div>
            <div class="lp-footer__col">
                <h4>Important Links</h4>
                <a href="<?= $lpfHomeHref ?>">Home</a>
                <a href="<?= $lpfAnchor('#lp-contact') ?>">NOC</a>
                <a href="mailto:info@isppaybd.com?subject=Career%20Inquiry">Career at ISP Pay BD</a>
                <a href="mailto:info@isppaybd.com?subject=Tender%20%2F%20Procurement%20Inquiry">Tender</a>
                <a href="<?= $lpfAnchor('#lp-faq') ?>">Notice</a>
                <a href="<?= $lpfAnchor('#lp-case-study') ?>">News &amp; Events</a>
                <a href="<?= $lpfAnchor('#lp-stats') ?>">Achievements</a>
            </div>
        </div>

        <?php if ($lpfShowOffice): ?>
        <div class="lp-footer__office" data-nosnippet>
            <div class="lp-footer__office-info">
                <h4><?= esc($appName ?? 'ISP Pay BD') ?> Head Office</h4>
                <p><i class="fas fa-map-marker-alt" aria-hidden="true"></i> 841 Badda Link Road, Dhaka 1212, Bangladesh</p>
                <p><i class="fas fa-phone-alt" aria-hidden="true"></i> <a href="tel:+8801781808231">+8801781-808231</a> &middot; <a href="tel:+8801610585100">+8801610-585100</a> &middot; <a href="tel:+8801628856735">+8801628-856735</a></p>
                <p><i class="fas fa-fax" aria-hidden="true"></i> Fax: 09638411110</p>
                <p><i class="fas fa-envelope" aria-hidden="true"></i> <a href="mailto:info@isppaybd.com">info@isppaybd.com</a></p>
                <p><i class="fas fa-globe" aria-hidden="true"></i> <a href="https://isppaybd.com/" target="_blank" rel="noopener noreferrer">www.isppaybd.com</a></p>
            </div>
            <div class="lp-footer__office-map">
                <iframe
                    title="<?= esc($appName ?? 'ISP Pay BD') ?> Head Office on Google Maps"
                    src="https://maps.google.com/maps?q=841%20Badda%20Link%20Road%2C%20Dhaka%201212%2C%20Bangladesh&z=15&output=embed"
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    allowfullscreen></iframe>
            </div>
        </div>
        <?php endif; ?>

        <div class="lp-footer__bottom">
            <span>© <?= date('Y') ?> <?= esc($appName ?? 'ISP Pay BD') ?>. All rights reserved.</span>
            <div class="lp-footer__legal">
                <a href="<?= $lpfAnchor('#lp-faq') ?>">Terms</a>
                <a href="<?= $lpfAnchor('#lp-faq') ?>">Privacy</a>
                <a href="<?= $lpfAnchor('#lp-pricing') ?>">Refund Policy</a>
            </div>
            <div class="lp-footer__social">
                <a href="https://www.facebook.com/isppaybdofficial" target="_blank" rel="noopener noreferrer" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
            </div>
        </div>
    </div>
</footer>
