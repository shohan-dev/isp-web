<?php
$dashboardImg = esc($dashboardImg ?? base_url('assets/img/icon/laptop-screen.webp') . '?v=4', 'attr');
$productTabs = [
    [
        'id' => 'billing',
        'label' => 'Billing',
        'image' => $dashboardImg,
        'bullets' => [
            'Auto invoices, SMS reminders, and bKash/Nagad collection',
            'Payment reconciliation matched to the right subscriber',
            'Expiry disconnect and paid reconnect on autopilot',
        ],
    ],
    [
        'id' => 'mikrotik',
        'label' => 'MikroTik Sync',
        'image' => $dashboardImg,
        'bullets' => [
            'PPPoE and hotspot user sync in real time',
            'Online/offline status per customer',
            'Unlimited routers on every plan',
        ],
    ],
    [
        'id' => 'invoices',
        'label' => 'Invoices',
        'image' => $dashboardImg,
        'bullets' => [
            'Printable invoices and payment history',
            'Partial payments and due tracking',
            'Reseller commission splits built in',
        ],
    ],
    [
        'id' => 'reports',
        'label' => 'Reports',
        'image' => $dashboardImg,
        'bullets' => [
            'Revenue, collection, and subscriber growth dashboards',
            'BTRC-ready exports where applicable',
            'Area, package, and reseller performance views',
        ],
    ],
];
$firstTab = $productTabs[0];
$firstBulletsJson = json_encode($firstTab['bullets'], JSON_UNESCAPED_UNICODE);
?>
<section class="lp-section lp-section--dark" id="lp-product" data-lp-section>
    <div class="lp-container">
        <div class="lp-section__header lp-reveal">
            <span class="lp-section__label">Product Tour</span>
            <h2 class="lp-section__title">See It In Action</h2>
            <p class="lp-section__desc">Explore the dashboard areas operators use every day — billing, MikroTik, invoices, and reports.</p>
        </div>
        <div class="lp-product lp-reveal">
            <div class="lp-product__tabs" role="tablist" aria-label="Product tour">
                <?php foreach ($productTabs as $i => $tab): ?>
                    <button type="button"
                        class="lp-product__tab<?= $i === 0 ? ' is-active' : '' ?>"
                        role="tab"
                        aria-selected="<?= $i === 0 ? 'true' : 'false' ?>"
                        data-image="<?= $tab['image'] ?>"
                        data-bullets="<?= esc(json_encode($tab['bullets'], JSON_UNESCAPED_UNICODE), 'attr') ?>">
                        <?= esc($tab['label']) ?>
                    </button>
                <?php endforeach; ?>
            </div>
            <div class="lp-product__preview">
                <div class="lp-browser-frame">
                    <div class="lp-browser-frame__bar">
                        <span></span><span></span><span></span>
                        <div class="lp-browser-frame__url">app.isppaybd.com</div>
                    </div>
                    <img id="lp-product-preview" src="<?= $firstTab['image'] ?>" alt="ISP Pay BD product screenshot — <?= esc($firstTab['label'], 'attr') ?>" loading="lazy" decoding="async">
                </div>
            </div>
            <div class="lp-product__bullets" id="lp-product-bullets">
                <?php foreach ($firstTab['bullets'] as $point): ?>
                    <div class="lp-product__bullet"><i class="fas fa-check"></i><span><?= esc($point) ?></span></div>
                <?php endforeach; ?>
            </div>
            <p class="lp-product__asset-note">Screenshots show the live ISP Pay BD dashboard. Replace with your own product captures when available.</p>
        </div>
    </div>
    <div class="lp-section-wave lp-section-wave--to-light" aria-hidden="true"></div>
</section>
