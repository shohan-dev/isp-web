<?php
// Prices come from the shared $lpPricing config (home.php / AuthController) so the
// cards, the JS calculator, and the ROI widget can never drift apart.
$lpTiers = $lpPricing['tiers'] ?? [
    'basic'      => ['price' => 999,   'cap' => 500,   'name' => 'Basic',      'id' => null],
    'standard'   => ['price' => 2499,  'cap' => 2000,  'name' => 'Standard',   'id' => null],
    'premium'    => ['price' => 4999,  'cap' => 5000,  'name' => 'Premium',    'id' => null],
    'business'   => ['price' => 8499,  'cap' => 10000, 'name' => 'Business',   'id' => null],
    'enterprise' => ['price' => 14999, 'cap' => 20000, 'name' => 'Enterprise', 'id' => null],
    'ultimate'   => ['price' => 24999, 'cap' => 40000, 'name' => 'Ultimate',   'id' => null],
];
$lpFixedCards = $lpFixedCards ?? [];
if (empty($lpFixedCards)) {
    foreach (['basic', 'standard', 'premium', 'business', 'enterprise', 'ultimate'] as $tierKey) {
        if (!empty($lpTiers[$tierKey])) {
            $lpFixedCards[] = array_merge($lpTiers[$tierKey], ['key' => $tierKey]);
        }
    }
}
$lpPayg = $lpPricing['payg'] ?? ['platform' => 500, 'perUser' => 1.5, 'minWallet' => 750];
$lpAddons = $lpPricing['addons'] ?? [
    'sms' => ['key' => 'sms', 'label' => 'SMS Credits', 'price' => 200],
    'whitelabel' => ['key' => 'whitelabel', 'label' => 'White Label', 'price' => 500],
    'backup' => ['key' => 'backup', 'label' => 'Extra Backups', 'price' => 150],
    'whatsapp' => ['key' => 'whatsapp', 'label' => 'WhatsApp Alerts', 'price' => 100],
];
$lpRegistrationUrl = route_to('route.auth.registration');
$tierFeatures = [
    'basic' => [
        'Billing & Invoicing', 'Customer CRM', 'Unlimited Mikrotik Routers', 'Email Support',
        ['no' => 'SMS Automation'],
    ],
    'standard' => [
        'Everything in Basic', 'SMS Automation', 'Auto Backup & Analytics', 'Priority Support', 'Free Data Migration',
    ],
    'premium' => [
        'Everything in Standard', 'White Label Branding', 'API & Multi-Branch', '24/7 Support', 'Advanced Reports',
    ],
    'business' => [
        'Everything in Premium', 'MAC Reseller Portal & App', 'Bandwidth Sale', 'Bulk SMS & Mailing System',
    ],
    'enterprise' => [
        'Everything in Business', 'OLT Integration for ONU', 'Client Portal (Android & Web)', 'Dedicated Account Manager',
    ],
    'ultimate' => [
        'Everything in Enterprise', 'Multi-Region Deployment', 'Dedicated Success Manager', 'Priority Feature Requests',
    ],
];
// Yearly-discount badge value — super-admin editable (SecondAdmin/package.php),
// read platform-wide via AdminPackage::landingPricingPayload(). Display-only,
// never used for real billing math.
$lpYearlyDiscountMonths = (int) ($lpPricing['yearlyDiscountMonths'] ?? 2);
?>
<section class="lp-section lp-section--dark" id="lp-pricing" data-lp-section>
    <div class="lp-container">
        <div class="lp-section__header lp-reveal">
            <span class="lp-section__label">Pricing</span>
            <h2 class="lp-section__title">Priced per subscriber, not per promise.</h2>
            <p class="lp-section__desc">Lock a fixed monthly plan for predictable billing, or go pay-as-you-go: one low <strong>per-customer rate</strong>, wallet-funded, no tier ceiling to outgrow.</p>
        </div>

        <!-- Pricing model switch -->
        <div class="lp-pricing-model lp-reveal" role="tablist" aria-label="Pricing model">
            <button type="button" class="lp-pricing-model__btn is-active" id="lp-model-fixed" role="tab" aria-selected="true" aria-controls="lp-panel-fixed">
                <i class="fas fa-layer-group"></i>
                <span>Fixed Monthly Plans</span>
                <em>Predictable billing</em>
            </button>
            <button type="button" class="lp-pricing-model__btn" id="lp-model-payg" role="tab" aria-selected="false" aria-controls="lp-panel-payg">
                <i class="fas fa-wallet"></i>
                <span>Pay-As-You-Go Wallet</span>
                <em>Flexible · Scale freely</em>
            </button>
        </div>

        <!-- ═══ FIXED PLANS PANEL ═══ -->
        <div id="lp-panel-fixed" class="lp-pricing-panel is-active" role="tabpanel" aria-labelledby="lp-model-fixed">

            <div class="lp-pricing-toggle lp-reveal">
                <span class="lp-pricing-toggle__label is-active" id="lp-label-monthly">Monthly</span>
                <button type="button" class="lp-pricing-toggle__switch" id="lp-pricing-toggle" aria-label="Toggle yearly pricing"></button>
                <span class="lp-pricing-toggle__label" id="lp-label-yearly">Yearly</span>
                <span class="lp-pricing-toggle__badge">Save <?= $lpYearlyDiscountMonths ?> month<?= $lpYearlyDiscountMonths === 1 ? '' : 's' ?></span>
            </div>

            <?php
            $lpMainCards = array_slice($lpFixedCards, 0, 3);
            $lpExtraCards = array_slice($lpFixedCards, 3, null, true);
            ?>
            <div class="lp-pricing-grid">
                <?php foreach ($lpMainCards as $cardIndex => $card):
                    $tierKey = $card['key'] ?? ('tier' . $cardIndex);
                    $planId = !empty($card['id']) ? (int) $card['id'] : $tierKey;
                    $isFeatured = $cardIndex === 1 || $tierKey === 'standard';
                    // Prefer this package row's own admin_packages.features (set via the
                    // super-admin package screen) — fall back to the curated hardcoded
                    // list only when the row has never had its features column touched.
                    $features = !empty($card['features']) ? $card['features'] : ($tierFeatures[$tierKey] ?? $tierFeatures['basic']);
                    $cap = (int) ($card['cap'] ?? 0);
                    $price = (float) ($card['price'] ?? 0);
                    $name = esc($card['name'] ?? ucfirst($tierKey));
                ?>
                <div class="lp-pricing-card<?= $isFeatured ? ' lp-pricing-card--featured' : '' ?> lp-reveal<?= $cardIndex > 0 ? ' lp-reveal-delay-' . min($cardIndex, 3) : '' ?>">
                    <?php if ($isFeatured): ?><span class="lp-pricing-card__badge">Most ISPs · 500–2,000 subs</span><?php endif; ?>
                    <h3 class="lp-pricing-card__name"><?= $name ?></h3>
                    <p class="lp-pricing-card__users">Up to <?= number_format(max(1, $cap)) ?> active users</p>
                    <div class="lp-pricing-card__price"><span data-plan-price="<?= esc($tierKey, 'attr') ?>">৳<?= number_format($price) ?></span> <span class="lp-pricing-card__unit">/mo</span></div>
                    <p class="lp-pricing-card__note" data-plan-note="<?= esc($tierKey, 'attr') ?>">~৳<?= number_format($price / max(1, $cap), 2) ?> per user</p>
                    <ul class="lp-pricing-card__features">
                        <?php foreach ($features as $feat): ?>
                            <?php if (is_array($feat) && isset($feat['no'])): ?>
                                <li><i class="fas fa-times lp-no"></i> <?= esc($feat['no']) ?></li>
                            <?php else: ?>
                                <li><i class="fas fa-check lp-yes"></i> <?= esc($feat) ?></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                    <a href="<?= $lpRegistrationUrl ?>?plan=<?= esc((string) $planId, 'attr') ?>" class="lp-btn <?= $isFeatured ? 'lp-btn--primary' : 'lp-btn--dark' ?> lp-btn--block" data-plan-cta="<?= esc($tierKey, 'attr') ?>">Start Free Trial</a>
                </div>
                <?php endforeach; ?>
                <div class="lp-pricing-card lp-reveal lp-reveal-delay-3">
                    <h3 class="lp-pricing-card__name">Advanced / Enterprise</h3>
                    <p class="lp-pricing-card__users"><?= number_format(max(array_column($lpTiers, 'cap')) + 1) ?>+ active users</p>
                    <div class="lp-pricing-card__price">Custom</div>
                    <p class="lp-pricing-card__note">Enterprise SLA &amp; dedicated infrastructure</p>
                    <ul class="lp-pricing-card__features">
                        <li><i class="fas fa-check lp-yes"></i> Dedicated Infrastructure</li>
                        <li><i class="fas fa-check lp-yes"></i> Custom SLA &amp; Security</li>
                        <li><i class="fas fa-check lp-yes"></i> Onboarding &amp; Training</li>
                        <li><i class="fas fa-check lp-yes"></i> Priority Engineering</li>
                        <li><i class="fas fa-check lp-yes"></i> Custom Integrations</li>
                    </ul>
                    <a href="#lp-contact" class="lp-btn lp-btn--outline lp-btn--block" data-lp-inquiry="Other Message">Contact Sales</a>
                </div>
            </div>

            <?php if (!empty($lpExtraCards)): ?>
            <button type="button" class="lp-pricing-showmore" id="lp-pricing-showmore" aria-expanded="false" aria-controls="lp-pricing-extra">
                <span>Show <?= count($lpExtraCards) ?> more plan<?= count($lpExtraCards) === 1 ? '' : 's' ?> for larger ISPs</span>
                <i class="fas fa-chevron-down"></i>
            </button>
            <div class="lp-pricing-grid lp-pricing-grid--extra" id="lp-pricing-extra" hidden>
                <?php foreach ($lpExtraCards as $cardIndex => $card):
                    $tierKey = $card['key'] ?? ('tier' . $cardIndex);
                    $planId = !empty($card['id']) ? (int) $card['id'] : $tierKey;
                    $features = !empty($card['features']) ? $card['features'] : ($tierFeatures[$tierKey] ?? $tierFeatures['basic']);
                    $cap = (int) ($card['cap'] ?? 0);
                    $price = (float) ($card['price'] ?? 0);
                    $name = esc($card['name'] ?? ucfirst($tierKey));
                ?>
                <div class="lp-pricing-card">
                    <h3 class="lp-pricing-card__name"><?= $name ?></h3>
                    <p class="lp-pricing-card__users">Up to <?= number_format(max(1, $cap)) ?> active users</p>
                    <div class="lp-pricing-card__price"><span data-plan-price="<?= esc($tierKey, 'attr') ?>">৳<?= number_format($price) ?></span> <span class="lp-pricing-card__unit">/mo</span></div>
                    <p class="lp-pricing-card__note" data-plan-note="<?= esc($tierKey, 'attr') ?>">~৳<?= number_format($price / max(1, $cap), 2) ?> per user</p>
                    <ul class="lp-pricing-card__features">
                        <?php foreach ($features as $feat): ?>
                            <?php if (is_array($feat) && isset($feat['no'])): ?>
                                <li><i class="fas fa-times lp-no"></i> <?= esc($feat['no']) ?></li>
                            <?php else: ?>
                                <li><i class="fas fa-check lp-yes"></i> <?= esc($feat) ?></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                    <a href="<?= $lpRegistrationUrl ?>?plan=<?= esc((string) $planId, 'attr') ?>" class="lp-btn lp-btn--dark lp-btn--block" data-plan-cta="<?= esc($tierKey, 'attr') ?>">Start Free Trial</a>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <p class="lp-pricing-footnote lp-reveal">
                <i class="fas fa-check-circle"></i>
                All plans include a 14-day free trial · No payment needed to start · Cancel anytime
            </p>
            <div class="lp-pricing-transparency lp-reveal">
                <p><strong>Pricing transparency:</strong> Prices shown exclude VAT (if applicable). Refunds follow our standard SaaS policy — contact support within 7 days of an accidental charge. <strong>Active subscriber</strong> = a customer with an enabled connection during the billing cycle. SMS quantities are platform credits, not per-message guarantees. Staff accounts are unlimited on all tiers; Mikrotik routers are unlimited on every plan.</p>
            </div>
            <p class="lp-pricing-footnote lp-reveal">
                <i class="fas fa-info-circle"></i>
                Growing fast or seasonal? <a href="#lp-pricing" id="lp-switch-to-payg">Try our Pay-As-You-Go wallet</a> — no tier limits, pay a low per-customer rate each month.
            </p>
        </div>

        <!-- ═══ PAY-AS-YOU-GO WALLET PANEL ═══ -->
        <div id="lp-panel-payg" class="lp-pricing-panel" role="tabpanel" aria-labelledby="lp-model-payg" hidden>

            <div class="lp-payg-headline lp-reveal">
                <h3>Pay a low rate per customer — no fixed tier caps, ever.</h3>
                <p>Example: 2,000 customers on your account this month → you pay ৳<?= number_format($lpPayg['platform']) ?> + 2,000 × ৳<?= number_format($lpPayg['perUser'], 2) ?> = <strong>৳<?= number_format($lpPayg['platform'] + 2000 * $lpPayg['perUser']) ?></strong>. Billed on every customer on your account — active, disabled, or expired.</p>
            </div>

            <div class="lp-payg lp-reveal">
                <!-- How it works strip -->
                <div class="lp-payg__steps">
                    <div class="lp-payg__step">
                        <div class="lp-payg__step-num">1</div>
                        <div>
                            <strong>Add Wallet Balance</strong>
                            <span>Top up via bKash, Nagad, or bank transfer</span>
                        </div>
                    </div>
                    <div class="lp-payg__step-arrow"><i class="fas fa-arrow-right"></i></div>
                    <div class="lp-payg__step">
                        <div class="lp-payg__step-num">2</div>
                        <div>
                            <strong>Use the Platform</strong>
                            <span>Full access — billing, Mikrotik, CRM, app</span>
                        </div>
                    </div>
                    <div class="lp-payg__step-arrow"><i class="fas fa-arrow-right"></i></div>
                    <div class="lp-payg__step">
                        <div class="lp-payg__step-num">3</div>
                        <div>
                            <strong>Auto-Deduct Monthly</strong>
                            <span>Charged based on active users each cycle</span>
                        </div>
                    </div>
                    <div class="lp-payg__step-arrow"><i class="fas fa-arrow-right"></i></div>
                    <div class="lp-payg__step">
                        <div class="lp-payg__step-num">4</div>
                        <div>
                            <strong>Top Up When Low</strong>
                            <span>Alert at 7 days remaining balance</span>
                        </div>
                    </div>
                </div>

                <div class="lp-payg__grid">
                    <!-- Left: Configurator -->
                    <div class="lp-payg__config">
                        <div class="lp-payg__config-header">
                            <h3>Configure Your Usage</h3>
                            <p>Estimate your monthly cost based on your customer count. Price adjusts automatically as you grow or shrink.</p>
                        </div>

                        <?php /* Months-to-cover picker — promoted to the TOP of the configurator
                                 as a one-tap segmented control (was a dropdown buried at the bottom).
                                 Backed by a visually-hidden <select id="lp-topup-months"> so the wallet
                                 math in landing.js reads it unchanged. Default: 1 month. */ ?>
                        <div class="lp-payg__topup">
                            <div class="lp-payg__topup-head">
                                <label id="lp-topup-label">Wallet top-up covers</label>
                                <span class="lp-payg__topup-hint">Tap to set how many months of balance to add</span>
                            </div>
                            <div class="lp-payg__topup-seg" role="radiogroup" aria-labelledby="lp-topup-label">
                                <button type="button" class="lp-payg__topup-opt is-active" data-months="1" role="radio" aria-checked="true">1 <span>mo</span></button>
                                <button type="button" class="lp-payg__topup-opt" data-months="2" role="radio" aria-checked="false">2 <span>mo</span></button>
                                <button type="button" class="lp-payg__topup-opt" data-months="3" role="radio" aria-checked="false">3 <span>mo</span></button>
                                <button type="button" class="lp-payg__topup-opt" data-months="6" role="radio" aria-checked="false">6 <span>mo</span></button>
                                <button type="button" class="lp-payg__topup-opt" data-months="12" role="radio" aria-checked="false">12 <span>mo</span></button>
                            </div>
                            <select id="lp-topup-months" class="lp-visually-hidden" aria-hidden="true" tabindex="-1">
                                <option value="1" selected>1 month</option>
                                <option value="2">2 months</option>
                                <option value="3">3 months</option>
                                <option value="6">6 months</option>
                                <option value="12">12 months</option>
                            </select>
                        </div>

                        <div class="lp-payg__field">
                            <div class="lp-payg__field-top">
                                <label for="lp-user-slider">Total customers</label>
                                <span id="lp-user-count">600 users</span>
                            </div>
                            <input type="range" class="lp-custom-pricing__slider" id="lp-user-slider" min="100" max="10000" step="50" value="600" aria-label="Number of active users" aria-valuetext="600 users">
                            <div class="lp-payg__slider-labels"><span>100</span><span>10,000</span></div>
                        </div>

                        <div class="lp-payg__rate-card">
                            <div class="lp-payg__rate-row">
                                <span>Platform fee</span>
                                <strong>৳<?= number_format($lpPayg['platform']) ?><span>/mo</span></strong>
                            </div>
                            <div class="lp-payg__rate-row">
                                <span>Per customer</span>
                                <strong>৳<?= number_format($lpPayg['perUser'], 2) ?><span>/customer/mo</span></strong>
                            </div>
                            <div class="lp-payg__rate-row lp-payg__rate-row--total">
                                <span>Estimated monthly usage</span>
                                <strong id="lp-payg-monthly">৳1,400</strong>
                            </div>
                            <p class="lp-payg__formula" id="lp-price-formula">৳500 + 600 users × ৳1.50 = ৳1,400/mo</p>
                            <p class="lp-payg__hint" id="lp-payg-hint" hidden></p>
                            <p class="lp-payg__unit-note">Billed per customer on your account each cycle — includes active, disabled, and expired connections.</p>
                            <div class="lp-payg__savings-cta" id="lp-payg-savings-cta" hidden>
                                <p>At your usage, ISP Pay BD estimates <strong id="lp-payg-savings-amount">৳0/year</strong> vs rigid tier pricing.</p>
                                <a href="<?= $lpRegistrationUrl ?>?plan=payg" class="lp-btn lp-btn--primary lp-btn--block">Start free — keep this rate</a>
                            </div>
                        </div>

                        <div class="lp-custom-pricing__addons">
                            <p class="lp-payg__addons-label">Optional add-ons (added to monthly deduction)</p>
                            <?php foreach ($lpAddons as $addon): ?>
                            <label class="lp-custom-pricing__addon">
                                <input type="checkbox" value="<?= esc($addon['key'], 'attr') ?>" data-addon-label="<?= esc($addon['label'], 'attr') ?>">
                                <?= esc($addon['label']) ?> <span>+৳<?= number_format((float) $addon['price']) ?>/mo</span>
                            </label>
                            <?php endforeach; ?>
                        </div>

                    </div>

                    <!-- Right: Wallet preview -->
                    <div class="lp-payg__wallet">
                        <div class="lp-wallet-card">
                            <div class="lp-wallet-card__header">
                                <div>
                                    <span class="lp-wallet-card__label">ISP Pay BD Wallet</span>
                                    <div class="lp-wallet-card__balance" id="lp-wallet-balance">৳2,800</div>
                                </div>
                                <div class="lp-wallet-card__icon"><i class="fas fa-wallet"></i></div>
                            </div>

                            <div class="lp-wallet-card__body">
                                <div class="lp-wallet-card__row">
                                    <span>Minimum top-up</span>
                                    <strong id="lp-wallet-min">৳<?= number_format($lpPayg['minWallet']) ?></strong>
                                </div>
                                <div class="lp-wallet-card__row">
                                    <span>Your top-up amount</span>
                                    <strong id="lp-wallet-topup" class="lp-wallet-card__highlight">৳2,800</strong>
                                </div>
                                <div class="lp-wallet-card__row">
                                    <span>Monthly auto-deduct</span>
                                    <strong id="lp-wallet-deduct" class="lp-wallet-card__deduct">− ৳1,400</strong>
                                </div>
                                <div class="lp-wallet-card__row">
                                    <span>Balance after 1 month</span>
                                    <strong id="lp-wallet-after1">৳1,400</strong>
                                </div>
                                <div class="lp-wallet-card__row">
                                    <span id="lp-wallet-after2-label">Balance after 2 months</span>
                                    <strong id="lp-wallet-after2">৳0</strong>
                                </div>
                            </div>

                            <div class="lp-wallet-card__progress">
                                <div class="lp-wallet-card__progress-bar" id="lp-wallet-progress" style="width:100%"></div>
                            </div>
                            <p class="lp-wallet-card__status" id="lp-wallet-status">
                                <i class="fas fa-check-circle"></i> Covers <strong>2 months</strong> at current usage
                            </p>

                            <div class="lp-wallet-card__deduct-preview" id="lp-wallet-animation" aria-hidden="true">
                                <div class="lp-wallet-deduct-line">
                                    <span>Month 1</span>
                                    <span class="lp-wallet-deduct-amount">−৳1,400</span>
                                </div>
                                <div class="lp-wallet-deduct-line">
                                    <span>Month 2</span>
                                    <span class="lp-wallet-deduct-amount">−৳1,400</span>
                                </div>
                            </div>
                        </div>

                        <ul class="lp-payg__benefits">
                            <li><i class="fas fa-check"></i> No fixed tier — scale users up or down anytime</li>
                            <li><i class="fas fa-check"></i> Only charged for <strong>active</strong> subscribers each month</li>
                            <li><i class="fas fa-check"></i> Low-balance email & SMS alert before suspension</li>
                            <li><i class="fas fa-check"></i> Top up anytime via bKash, Nagad, or bank transfer</li>
                        </ul>

                        <a href="<?= $lpRegistrationUrl ?>?plan=payg" class="lp-btn lp-btn--primary lp-btn--lg lp-btn--block lp-btn--shimmer" id="lp-payg-cta">
                            <i class="fas fa-rocket"></i> Start Free Trial — then top up <span id="lp-payg-cta-amount">৳2,800</span>
                        </a>
                        <p class="lp-payg__cta-note">14-day free trial first — no payment to start · Minimum wallet ৳<?= number_format($lpPayg['minWallet']) ?> · Billing begins after trial ends</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
