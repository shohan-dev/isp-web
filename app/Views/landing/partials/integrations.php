<section class="lp-section lp-section--light" id="lp-integrations" data-lp-section>
    <div class="lp-container">
        <div class="lp-section__header lp-reveal">
            <span class="lp-section__label">Integrations</span>
            <h2 class="lp-section__title">Connects With Tools You Already Use</h2>
            <p class="lp-section__desc">Payment gateways, MikroTik, SMS, and OLT — with real Bangladesh payment rails built in.</p>
        </div>
        <?php
        $logoBase = base_url('assets/img/landing/logos');
        $brandLogos = [
            ['src' => $logoBase . '/bkash.svg', 'alt' => 'bKash'],
            ['src' => $logoBase . '/nagad.svg', 'alt' => 'Nagad'],
            ['src' => $logoBase . '/sslcommerz.svg', 'alt' => 'SSLCommerz'],
            ['src' => $logoBase . '/mikrotik.svg', 'alt' => 'MikroTik'],
        ];
        ?>
        <div class="lp-integration-logos lp-reveal">
            <?php foreach ($brandLogos as $logo): ?>
                <img src="<?= esc($logo['src'], 'attr') ?>" alt="<?= esc($logo['alt']) ?>" loading="lazy" width="120" height="48">
            <?php endforeach; ?>
        </div>
        <div class="lp-integrations">
            <div class="lp-integration lp-reveal"><div class="lp-integration__icon"><i class="fas fa-router"></i></div><span class="lp-integration__name">Mikrotik</span></div>
            <div class="lp-integration lp-reveal"><div class="lp-integration__icon"><i class="fas fa-mobile-alt"></i></div><span class="lp-integration__name">bKash</span></div>
            <div class="lp-integration lp-reveal"><div class="lp-integration__icon"><i class="fas fa-wallet"></i></div><span class="lp-integration__name">Nagad</span></div>
            <div class="lp-integration lp-reveal"><div class="lp-integration__icon"><i class="fas fa-lock"></i></div><span class="lp-integration__name">SSLCommerz</span></div>
            <div class="lp-integration lp-reveal"><div class="lp-integration__icon"><i class="fas fa-money-check"></i></div><span class="lp-integration__name">aamarPay</span></div>
            <div class="lp-integration lp-reveal"><div class="lp-integration__icon"><i class="fab fa-whatsapp"></i></div><span class="lp-integration__name">WhatsApp</span></div>
            <div class="lp-integration lp-reveal"><div class="lp-integration__icon"><i class="fab fa-telegram"></i></div><span class="lp-integration__name">Telegram</span></div>
            <div class="lp-integration lp-reveal"><div class="lp-integration__icon"><i class="fas fa-map-marked-alt"></i></div><span class="lp-integration__name">Google Maps</span></div>
            <div class="lp-integration lp-reveal"><div class="lp-integration__icon"><i class="fas fa-sms"></i></div><span class="lp-integration__name">SMS Gateway</span></div>
            <div class="lp-integration lp-reveal"><div class="lp-integration__icon"><i class="fas fa-broadcast-tower"></i></div><span class="lp-integration__name">OLT Devices</span></div>
        </div>
        <div class="lp-pay-with lp-reveal" style="margin-top:40px;">
            <h3 class="lp-section__title" style="font-size:1.25rem;margin-bottom:16px;">Pay With</h3>
            <img src="<?= base_url('assets/img/icon/ssl_pay1.webp') ?>" alt="Accepted payment methods: bKash, Nagad, cards and more via SSLCommerz" loading="lazy" decoding="async">
        </div>
    </div>
</section>
