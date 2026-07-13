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
                    'brandHref' => route_to('route.auth.home'),
                ]); ?>
                <p>The complete ISP billing and management platform — built for Bangladesh ISPs.</p>
            </div>
            <div class="lp-footer__col">
                <h4>Product</h4>
                <a href="#lp-features">Features</a>
                <a href="#lp-pricing">Pricing</a>
                <a href="#lp-integrations">Integrations</a>
                <a href="<?= route_to('route.plugins.index') ?>">Plugins</a>
            </div>
            <div class="lp-footer__col">
                <h4>Company</h4>
                <a href="#lp-about">About Us</a>
                <a href="#lp-contact">Contact</a>
                <a href="#lp-faq">FAQ</a>
                <a href="<?= route_to('route.auth.registration') ?>">Free Trial</a>
            </div>
            <div class="lp-footer__col">
                <h4>Support</h4>
                <a href="tel:+8801781808231">+8801781-808231</a>
                <a href="mailto:info@isppaybd.com">info@isppaybd.com</a>
                <a href="#lp-contact" data-lp-inquiry="Demo Request">Book Demo</a>
                <?php if (!empty(getSession('user_id'))): ?>
                    <a href="<?= route_to('route.dashboard') ?>">Dashboard</a>
                <?php else: ?>
                    <a href="<?= route_to('route.auth.login') ?>">Login</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="lp-footer__bottom">
            <span>© <?= date('Y') ?> <?= esc($appName ?? 'ISP Pay BD') ?>. All rights reserved.</span>
            <div class="lp-footer__legal">
                <a href="<?= route_to('route.auth.home') ?>#lp-faq">Terms</a>
                <a href="<?= route_to('route.auth.home') ?>#lp-faq">Privacy</a>
                <a href="<?= route_to('route.auth.home') ?>#lp-pricing">Refund Policy</a>
            </div>
            <div class="lp-footer__social">
                <a href="https://www.facebook.com/isppaybdofficial" target="_blank" rel="noopener noreferrer" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
            </div>
        </div>
    </div>
</footer>
