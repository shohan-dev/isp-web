<?php
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
// Two copies for a seamless marquee loop (same technique as trust.php).
$partnerLogosMarquee = array_merge($partnerLogos, $partnerLogos);
?>
<section class="lp-section lp-section--dark" id="lp-our-partners" data-lp-section>
    <div class="lp-container">
        <div class="lp-partners__header lp-reveal">
            <h2 class="lp-partners__title">Our Partners</h2>
        </div>
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
</section>
