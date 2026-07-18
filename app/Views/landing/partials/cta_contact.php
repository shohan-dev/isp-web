<section class="lp-cta-band lp-reveal" id="lp-cta">
    <div class="lp-container lp-cta-band__inner">
        <h2 class="lp-cta-band__title">Stop reconciling payments by hand.</h2>
        <p class="lp-cta-band__desc">Connect your MikroTik, switch on bKash &amp; Nagad auto-matching, and bill your whole reseller tree from one panel — set up in an afternoon.</p>
        <div class="lp-cta-band__actions">
            <a href="<?= route_to('route.auth.registration') ?>" class="lp-btn lp-btn--primary lp-btn--lg lp-btn--shimmer">Start free — 14 days, no card</a>
            <a href="https://wa.me/8801781808231?text=Hi%2C%20I%27d%20like%20to%20book%20an%20ISP%20Pay%20BD%20demo" class="lp-btn lp-btn--outline lp-btn--sm" target="_blank" rel="noopener noreferrer" data-lp-inquiry="Demo Request">
                <i class="fab fa-whatsapp"></i> Book a live demo on WhatsApp
            </a>
            <a href="#lp-contact" class="lp-cta-band__link" data-lp-inquiry="Other Message">Contact sales</a>
        </div>
    </div>
</section>

<section class="lp-section lp-section--light" id="lp-contact" data-lp-section>
    <div class="lp-container">
        <div class="lp-section__header lp-reveal">
            <span class="lp-section__label">Contact</span>
            <h2 class="lp-section__title">Talk to people who run ISPs.</h2>
            <p class="lp-section__desc">Trial it yourself, or get a 20-minute walkthrough from someone who knows MikroTik and bKash reconciliation.</p>
        </div>
        <div class="lp-contact-funnel lp-reveal">
            <a href="<?= route_to('route.auth.registration') ?>" class="lp-contact-funnel__card lp-contact-funnel__card--primary">
                <i class="fas fa-rocket"></i>
                <strong>Start free</strong>
                <span>Full panel, 14 days, no card</span>
            </a>
            <a href="https://wa.me/8801781808231?text=Hi%2C%20I%27d%20like%20to%20book%20an%20ISP%20Pay%20BD%20demo" class="lp-contact-funnel__card" target="_blank" rel="noopener noreferrer" data-lp-inquiry="Demo Request">
                <i class="fab fa-whatsapp"></i>
                <strong>Book a demo</strong>
                <span>WhatsApp — we reply within the hour</span>
            </a>
            <a href="#lp-contact-form" class="lp-contact-funnel__card" data-lp-inquiry="Other Message">
                <i class="fas fa-building"></i>
                <strong>Running 10k+ subscribers?</strong>
                <span>Custom SLA, migration &amp; priority NOC support</span>
            </a>
        </div>
        <div class="lp-contact lp-reveal" id="lp-contact-form">
            <div class="lp-contact__info" data-nosnippet>
                <div class="lp-contact__card">
                    <div class="lp-contact__card-icon"><i class="fas fa-map-marker-alt"></i></div>
                    <div>
                        <h3>Our Address</h3>
                        <p>841 Badda Link Road, Dhaka 1212</p>
                    </div>
                </div>
                <div class="lp-contact__card">
                    <div class="lp-contact__card-icon"><i class="fas fa-phone-alt"></i></div>
                    <div>
                        <h3>Phone</h3>
                        <p>+8801781-808231<br>+8801628-856735<br>+8801610-585100</p>
                    </div>
                </div>
                <div class="lp-contact__card">
                    <div class="lp-contact__card-icon"><i class="fas fa-envelope"></i></div>
                    <div>
                        <h3>Email</h3>
                        <p><a href="mailto:info@isppaybd.com" style="color:inherit;">info@isppaybd.com</a></p>
                    </div>
                </div>
                <div class="lp-contact__card">
                    <div class="lp-contact__card-icon"><i class="fas fa-clock"></i></div>
                    <div>
                        <h3>Working Hours</h3>
                        <p>Saturday – Thursday: 10:00 AM – 8:00 PM<br>Friday: Closed</p>
                    </div>
                </div>
            </div>

            <div class="lp-contact__form" data-nosnippet>
                <h3>Tell us about your network</h3>
                <?php if (session()->getFlashdata('success')): ?>
                    <div class="lp-alert lp-alert--success" role="alert"><?= esc(session()->getFlashdata('success')) ?></div>
                <?php elseif (session()->getFlashdata('error')): ?>
                    <div class="lp-alert lp-alert--error" role="alert"><?= esc(is_array(session()->getFlashdata('error')) ? implode(' ', session()->getFlashdata('error')) : session()->getFlashdata('error')) ?></div>
                <?php endif; ?>

                <form id="contactForm" action="<?= route_to('route.auth.store') ?>" method="POST">
                    <?= csrf_field() ?>
                    <div class="lp-form-group">
                        <label for="name">Full Name *</label>
                        <input type="text" id="name" name="name" placeholder="Abdul Karim" required autocomplete="name">
                    </div>
                    <div class="lp-form-group">
                        <label for="phone">Phone Number *</label>
                        <input type="tel" id="phone" name="phone" placeholder="01XXXXXXXXX" required autocomplete="tel">
                    </div>
                    <div class="lp-form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" placeholder="karim@example.com" required autocomplete="email">
                    </div>
                    <div class="lp-form-group">
                        <label>Inquiry Type *</label>
                        <div class="lp-form-radios">
                            <label><input type="radio" name="inquiryType" value="Demo Request" required checked> Demo Request</label>
                            <label><input type="radio" name="inquiryType" value="Feature Update Request"> Feature Update Request</label>
                            <label><input type="radio" name="inquiryType" value="Other Message"> Enterprise / Other</label>
                        </div>
                    </div>
                    <div class="lp-form-group">
                        <label for="message">Your Message *</label>
                        <textarea id="message" name="message" placeholder="How many subscribers, which routers, and what's eating your billing time?" required></textarea>
                    </div>
                    <div class="lp-form-group">
                        <div class="g-recaptcha" data-sitekey="<?= esc(env('recaptcha.siteKey', ''), 'attr') ?>"></div>
                    </div>
                    <button type="submit" class="lp-btn lp-btn--primary lp-btn--block">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>
