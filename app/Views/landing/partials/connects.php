<?php
    $lpiLogoBase = base_url('assets/img/landing/logos');
    // Real brand logos (SVG where available, PNG where the vendor has no public
    // SVG) for companies we integrate with; generic FA marks for category-level
    // items with no single brand. Mixed aspect ratios are handled by object-fit.
    // Tiered: the 3 rails the product is actually built on get hero treatment;
    // everything else is a supporting integration, not equal-weight clip art.
    $lpiCoreRails = [
        ['name' => 'MikroTik', 'logo' => $lpiLogoBase . '/mikrotik.svg', 'desc' => 'Real-time PPPoE + hotspot sync. Disconnect on expiry, reconnect on payment — nobody SSHes in.'],
        ['name' => 'bKash',    'logo' => $lpiLogoBase . '/bkash.svg',    'desc' => 'Every Send Money auto-matched to the right subscriber in under a second.'],
        ['name' => 'Nagad',    'logo' => $lpiLogoBase . '/nagad.svg',    'desc' => 'Same auto-reconciliation, same speed — no manual SMS matching, ever.'],
    ];
    $lpiIntegrations = [
        ['name' => 'SSLCommerz',  'logo' => $lpiLogoBase . '/sslcommerz.png'],
        ['name' => 'aamarPay',    'logo' => $lpiLogoBase . '/aamarpay.png'],
        ['name' => 'WhatsApp',    'logo' => $lpiLogoBase . '/whatsapp.svg'],
        ['name' => 'Telegram',    'logo' => $lpiLogoBase . '/telegram.svg'],
        ['name' => 'Google Maps', 'logo' => $lpiLogoBase . '/googlemaps.svg'],
        ['name' => 'SMS Gateway', 'icon' => 'fas fa-sms'],
        ['name' => 'OLT Devices', 'icon' => 'fas fa-broadcast-tower'],
    ];

    // Dedupe plugins defensively — the table has duplicate title rows for at
    // least one demo tenant, so the marketplace teaser never shows a dupe.
    $lpPluginsUnique = [];
    foreach ($lpPlugins ?? [] as $lpPlugin) {
        $lpPluginKey = mb_strtolower(trim((string) ($lpPlugin['title'] ?? '')));
        if ($lpPluginKey === '' || isset($lpPluginsUnique[$lpPluginKey])) {
            continue;
        }
        $lpPluginsUnique[$lpPluginKey] = $lpPlugin;
    }
    $lpPluginsList = array_values($lpPluginsUnique);
?>
<section class="lp-section lp-section--light" id="lp-integrations" data-lp-section>
    <div class="lp-container">
        <div class="lp-section__header lp-reveal">
            <span class="lp-section__label">Integrations</span>
            <h2 class="lp-section__title">Wired into the rails your business already runs on.</h2>
            <p class="lp-section__desc">Native MikroTik control, bKash &amp; Nagad send-money auto-reconciliation, SMS and OLT — the Bangladesh stack, not bolted-on plugins.</p>
        </div>

        <div class="lp-rails lp-stagger-children lp-reveal">
            <?php foreach ($lpiCoreRails as $rail): ?>
                <div class="lp-rail lp-reveal-child">
                    <span class="lp-rail__tag">Core rail</span>
                    <div class="lp-rail__icon"><img src="<?= esc($rail['logo'], 'attr') ?>" alt="" loading="lazy" decoding="async"></div>
                    <h3 class="lp-rail__name"><?= esc($rail['name']) ?></h3>
                    <p class="lp-rail__desc"><?= esc($rail['desc']) ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <p class="lp-integrations__label">+ also connects to</p>
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


        <?php if (!empty($lpPluginsList)): ?>
        <hr class="lp-subdivider">
        <div class="lp-connect-plugins">
            <div class="lp-section__header lp-reveal" style="margin-bottom:32px;">
                <span class="lp-section__label">Plugins &amp; Addons</span>
                <h2 class="lp-section__title">Turn on only what you need, when you need it.</h2>
                <p class="lp-section__desc">Extra gateways, OTT bundles, hardware catalog, HR/payroll — one switch each, billed only when you enable them.</p>
            </div>
            <div class="lp-plugins lp-stagger-children lp-reveal">
                <?php foreach ($lpPluginsList as $plugin): ?>
                    <?php
                        $imgPath = $plugin['image'] ?? null;
                        if ($imgPath && !str_starts_with($imgPath, 'assets/')) {
                            $imgPath = 'assets/img/plugins_images/' . $imgPath;
                        }
                        $hasImage = $imgPath && file_exists(FCPATH . $imgPath);
                        $desc = (string) ($plugin['description'] ?? '');
                        $descShort = mb_strlen($desc) > 110 ? mb_substr($desc, 0, 110) . '...' : $desc;
                    ?>
                    <div class="lp-plugin lp-reveal-child">
                        <?php if (!empty($plugin['price_type'])): ?>
                            <span class="lp-plugin__badge"><?= esc($plugin['price_type']) ?></span>
                        <?php endif; ?>
                        <div class="lp-plugin__image">
                            <?php if ($hasImage): ?>
                                <img src="<?= esc(base_url($imgPath), 'attr') ?>" alt="<?= esc($plugin['title'] ?? '') ?>" loading="lazy">
                            <?php else: ?>
                                <i class="fas fa-puzzle-piece" aria-hidden="true"></i>
                            <?php endif; ?>
                        </div>
                        <h3 class="lp-plugin__title"><?= esc($plugin['title'] ?? '') ?></h3>
                        <p class="lp-plugin__desc"><?= esc($descShort) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="lp-plugins__cta lp-reveal">
                <a href="<?= route_to('route.plugins.index') ?>" class="lp-btn lp-btn--primary">Browse the add-on marketplace</a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>
