<?php
// Prefer a real screenshot from the super-admin-managed Product Showcase
// (Product Showcase > Mobile) over the hand-drawn placeholder UI below —
// first image of the first mobile category, in sort order. Falls back to
// the synthesized mockup when no mobile images have been uploaded yet, so
// this section never regresses to empty on a fresh install.
$_mobileShowcaseImage = null;
foreach (($lpProductShowcase['mobile'] ?? []) as $_mobileCat) {
    if (!empty($_mobileCat['images'][0]['url'])) {
        $_mobileShowcaseImage = $_mobileCat['images'][0]['url'];
        break;
    }
}
?>
<section class="lp-section lp-section--light" id="lp-mobile-app" data-lp-section>
    <div class="lp-container">
        <div class="lp-split lp-split--center">
            <div class="lp-split__content lp-reveal">
                <span class="lp-section__label">Customer App</span>
                <h2 class="lp-split__title">Your subscribers pay themselves. You stop chasing bills.</h2>
                <p class="lp-split__desc">
                    A self-service app under <strong>your</strong> name and logo — subscribers pay via bKash or Nagad, see every invoice, and open tickets, so your desk phone stops ringing on the 1st of the month.
                </p>
                <ul class="lp-check-list">
                    <li><i class="fas fa-check-circle"></i> Pay via bKash/Nagad — auto-reconciled to the right subscriber</li>
                    <li><i class="fas fa-check-circle"></i> Full bill history &amp; downloadable ৳ invoices</li>
                    <li><i class="fas fa-check-circle"></i> Tickets + push alerts for due, paid, and disconnect</li>
                </ul>
                <a href="<?= route_to('route.auth.registration') ?>" class="lp-btn lp-btn--primary lp-btn--lg">Launch it under your brand</a>
            </div>
            <div class="lp-split__visual lp-reveal lp-reveal-delay-2">
                <div class="lp-phone-showcase">
                    <div class="lp-phone-frame lp-phone-frame--lg">
                        <div class="lp-phone-frame__notch"></div>
                        <?php if ($_mobileShowcaseImage): ?>
                        <div class="lp-phone-frame__screen lp-phone-frame__screen--shot">
                            <img src="<?= esc($_mobileShowcaseImage, 'attr') ?>" alt="<?= esc($appName ?? 'ISP Pay BD') ?> customer app screenshot" loading="lazy" decoding="async">
                        </div>
                        <?php else: ?>
                        <div class="lp-phone-frame__screen lp-phone-frame__screen--app">
                            <?php $_phoneLogo = resolvePublicBrandLogoUrl($tenant ?? null, $brandUserId ?? null); ?>
                            <div class="lp-appui" aria-hidden="true">
                                <div class="lp-appui__head">
                                    <img src="<?= esc($_phoneLogo, 'attr') ?>" alt="" class="lp-appui__logo" loading="lazy" decoding="async">
                                    <div>
                                        <strong><?= esc($appName ?? 'ISP Pay BD') ?></strong>
                                        <span>Customer portal</span>
                                    </div>
                                </div>
                                <div class="lp-appui__balance">
                                    <span class="lp-appui__bal-label">Current due</span>
                                    <span class="lp-appui__bal-amt">৳1,240</span>
                                    <span class="lp-appui__bal-meta">Due 25 Jul · 10&nbsp;Mbps · #SUB-4182</span>
                                </div>
                                <div class="lp-appui__pay"><i class="fas fa-bolt" aria-hidden="true"></i> Pay with bKash / Nagad</div>
                                <div class="lp-appui__hist">
                                    <div class="lp-appui__row"><span>Jun · ৳1,240</span><span class="lp-appui__chip">PAID</span></div>
                                    <div class="lp-appui__row"><span>May · ৳1,240</span><span class="lp-appui__chip">PAID</span></div>
                                    <div class="lp-appui__row"><span>Apr · ৳1,240</span><span class="lp-appui__chip">PAID</span></div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
