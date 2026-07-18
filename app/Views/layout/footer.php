<?php if (!empty($isPublic)): ?>
<style>
    .plp-public-footer {
        margin-top: 48px;
        padding: 40px 3rem 24px;
        background: linear-gradient(135deg, #001033 0%, #001F57 100%);
        color: rgba(255, 255, 255, 0.75);
        font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif;
        font-size: 0.875rem;
    }
    .plp-public-footer__grid {
        display: grid;
        grid-template-columns: 1.4fr 1fr 1fr;
        gap: 32px;
        max-width: 1280px;
        margin: 0 auto 28px;
        padding-bottom: 28px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    .plp-public-footer__brand {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #fff !important;
        text-decoration: none;
        font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
        font-weight: 800;
        font-size: 1.0625rem;
        margin-bottom: 10px;
    }
    .plp-public-footer__brand img {
        height: 32px;
        width: auto;
        border-radius: 6px;
    }
    .plp-public-footer__col h4 {
        color: #fff;
        font-size: 0.8125rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        margin-bottom: 14px;
    }
    .plp-public-footer__col a {
        display: block;
        color: rgba(255, 255, 255, 0.68) !important;
        text-decoration: none;
        padding: 5px 0;
        transition: color 0.2s ease;
    }
    .plp-public-footer__col a:hover { color: #F75803 !important; }
    .plp-public-footer__bottom {
        max-width: 1280px;
        margin: 0 auto;
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 8px;
    }
    @media (max-width: 768px) {
        .plp-public-footer { padding: 32px 1.5rem 20px; }
        .plp-public-footer__grid { grid-template-columns: 1fr; gap: 24px; }
    }
</style>
<footer class="plp-public-footer">
    <div class="plp-public-footer__grid">
        <div>
            <a href="<?= base_url('/') ?>" class="plp-public-footer__brand">
                <img src="<?= base_url('assets/img/logo/' . getSetting('app_logo')); ?>" alt="<?= esc(getSetting('app_name')); ?>">
                <?= esc(getSetting('app_name')); ?>
            </a>
            <p style="margin:0;max-width:320px;">The complete ISP billing and management platform — built for Bangladesh ISPs.</p>
        </div>
        <div class="plp-public-footer__col">
            <h4>Product</h4>
            <a href="<?= base_url('/#lp-features') ?>">Features</a>
            <a href="<?= base_url('/#lp-pricing') ?>">Pricing</a>
            <a href="<?= route_to('route.plugins.index') ?>">Plugins</a>
        </div>
        <div class="plp-public-footer__col">
            <h4>Support</h4>
            <a href="tel:+8801781808231">+8801781-808231</a>
            <a href="mailto:info@isppaybd.com">info@isppaybd.com</a>
            <a href="<?= route_to('route.auth.login'); ?>">Login</a>
        </div>
    </div>
    <div class="plp-public-footer__bottom">
        <span>&copy; <?= date('Y'); ?> <?= esc(getSetting('app_name')); ?>. All rights reserved.</span>
        <span>Version <?= APP_VERSION; ?></span>
    </div>
</footer>
<?php else: ?>
<footer class="main-footer">
  <div class="pull-right hidden-xs">
    <b>Version</b> <?= APP_VERSION; ?>
  </div>
  Copyright &copy; <?= date('Y'); ?> by
  <strong><a href="<?= base_url(); ?>"><?= esc(getSetting('app_name')); ?></a></strong>.
  All rights reserved.
</footer>
<?php endif; ?>
