<section class="lp-section lp-section--light" id="lp-mobile-app" data-lp-section>
    <div class="lp-container">
        <div class="lp-split lp-split--center">
            <div class="lp-split__content lp-reveal">
                <span class="lp-section__label">Mobile App</span>
                <h2 class="lp-split__title">Your Customers Stay Connected</h2>
                <p class="lp-split__desc">
                    Give subscribers a branded self-service app for payments, bill history, support tickets, and real-time notifications.
                </p>
                <ul class="lp-check-list">
                    <li><i class="fas fa-check-circle"></i> Self-payment via bKash & Nagad</li>
                    <li><i class="fas fa-check-circle"></i> Bill history & download invoices</li>
                    <li><i class="fas fa-check-circle"></i> Support tickets & push alerts</li>
                </ul>
                <a href="<?= route_to('route.auth.registration') ?>" class="lp-btn lp-btn--primary lp-btn--lg">Get the App with Your ISP</a>
            </div>
            <div class="lp-split__visual lp-reveal lp-reveal-delay-2">
                <div class="lp-phone-showcase">
                    <div class="lp-phone-frame lp-phone-frame--lg">
                        <div class="lp-phone-frame__notch"></div>
                        <div class="lp-phone-frame__screen lp-phone-frame__screen--app">
                            <?php
                            $_phoneLogo = resolvePublicBrandLogoUrl($tenant ?? null, $brandUserId ?? null);
                            $_phoneLogoFull = brandLogoIsFull($_phoneLogo, $brandUserId ?? null);
                            ?>
                            <img src="<?= esc($_phoneLogo, 'attr') ?>" alt="<?= esc($appName ?? resolveBrandTitle($tenant ?? null, $brandUserId ?? null)) ?>" class="lp-phone-frame__app-icon<?= $_phoneLogoFull ? '' : ' lp-phone-frame__app-icon--mark' ?>" loading="lazy" decoding="async">
                            <strong><?= esc($appName ?? 'ISP Pay BD') ?></strong>
                            <span>Customer Portal</span>
                            <div class="lp-phone-frame__cta">Pay Bill Now</div>
                            <div class="lp-phone-frame__cards">
                                <div></div><div></div><div></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
