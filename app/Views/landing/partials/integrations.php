<?php
    $lpiLogoBase = base_url('assets/img/landing/logos');
    // Real brand logos (SVG where available, PNG where the vendor has no
    // public SVG) for actual companies we integrate with; generic FontAwesome
    // marks for category-level items with no single brand (SMS Gateway covers
    // several providers, OLT Devices covers many vendors). Logos have mixed
    // aspect ratios (square marks vs. wide wordmarks) — .lp-integration__icon
    // sizes them with object-fit: contain rather than forcing a square.
    $lpiIntegrations = [
        ['name' => 'Mikrotik',    'logo' => $lpiLogoBase . '/mikrotik.svg'],
        ['name' => 'bKash',       'logo' => $lpiLogoBase . '/bkash.svg'],
        ['name' => 'Nagad',       'logo' => $lpiLogoBase . '/nagad.svg'],
        ['name' => 'SSLCommerz',  'logo' => $lpiLogoBase . '/sslcommerz.png'],
        ['name' => 'aamarPay',    'logo' => $lpiLogoBase . '/aamarpay.png'],
        ['name' => 'WhatsApp',    'logo' => $lpiLogoBase . '/whatsapp.svg'],
        ['name' => 'Telegram',    'logo' => $lpiLogoBase . '/telegram.svg'],
        ['name' => 'Google Maps', 'logo' => $lpiLogoBase . '/googlemaps.svg'],
        ['name' => 'SMS Gateway', 'icon' => 'fas fa-sms'],
        ['name' => 'OLT Devices', 'icon' => 'fas fa-broadcast-tower'],
    ];
?>
<section class="lp-section lp-section--light" id="lp-integrations" data-lp-section>
    <div class="lp-container">
        <div class="lp-section__header lp-reveal">
            <span class="lp-section__label">Integrations</span>
            <h2 class="lp-section__title">Connects With Tools You Already Use</h2>
            <p class="lp-section__desc">Payment gateways, MikroTik, SMS, and OLT — with real Bangladesh payment rails built in.</p>
        </div>

        <div class="lp-integrations lp-stagger-children lp-reveal">
            <?php foreach ($lpiIntegrations as $lpiItem): ?>
                <div class="lp-integration lp-reveal-child">
                    <div class="lp-integration__icon">
                        <?php if (isset($lpiItem['logo'])): ?>
                            <img src="<?= esc($lpiItem['logo'], 'attr') ?>" alt="" loading="lazy" decoding="async">
                        <?php else: ?>
                            <i class="<?= esc($lpiItem['icon'], 'attr') ?>" aria-hidden="true"></i>
                        <?php endif; ?>
                    </div>
                    <span class="lp-integration__name"><?= esc($lpiItem['name']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <!-- space  -->
    <div class="lp-space" style="height: 100px;"></div>
    <div class="lp-container">

        <img src="<?= base_url('assets/img/icon/ssl_pay1.webp') ?>" alt="Accepted payment methods: bKash, Nagad, cards and more via SSLCommerz" loading="lazy" decoding="async">
    </div>
    
</section>
