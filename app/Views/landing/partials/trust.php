<?php
$logoBase = base_url('assets/img/landing/logos');
$integrationLogos = [
    ['src' => $logoBase . '/bkash.svg', 'alt' => 'bKash'],
    ['src' => $logoBase . '/nagad.svg', 'alt' => 'Nagad'],
    ['src' => $logoBase . '/sslcommerz.svg', 'alt' => 'SSLCommerz'],
    ['src' => $logoBase . '/mikrotik.svg', 'alt' => 'MikroTik'],
];
// Two copies per half for a seamless marquee loop.
$marqueeLogos = array_merge($integrationLogos, $integrationLogos);
?>
<section class="lp-trust lp-reveal" aria-label="Integrated with leading platforms">
    <p class="lp-trust__label">Integrated with bKash, Nagad, SSLCommerz &amp; MikroTik</p>
    <div class="lp-marquee">
        <div class="lp-marquee__inner">
            <?php for ($r = 0; $r < 2; $r++): ?>
                <?php foreach ($marqueeLogos as $logo): ?>
                    <div class="lp-marquee__item lp-marquee__item--integration">
                        <img src="<?= esc($logo['src'], 'attr') ?>" alt="<?= esc($logo['alt']) ?>" loading="lazy" width="120" height="48">
                    </div>
                <?php endforeach; ?>
            <?php endfor; ?>
        </div>
    </div>
</section>
