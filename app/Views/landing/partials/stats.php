<section class="lp-section lp-section--dark" id="lp-stats" data-lp-section>
    <div class="lp-container">
        <div class="lp-section__header lp-reveal" style="margin-bottom:40px;">
            <span class="lp-section__label">By the Numbers</span>
            <h2 class="lp-section__title">Proven at Scale</h2>
        </div>
        <div class="lp-stats">
            <div class="lp-stat lp-reveal">
                <div class="lp-stat__icon"><i class="fas fa-building"></i></div>
                <div class="lp-stat__value" data-count="<?= (int) ($trusted_isps ?? 0) ?>" data-suffix="+">0</div>
                <div class="lp-stat__label">Active ISPs</div>
            </div>
            <div class="lp-stat lp-reveal lp-reveal-delay-1">
                <div class="lp-stat__icon"><i class="fas fa-users"></i></div>
                <div class="lp-stat__value" data-count="<?= (int) ($active_users ?? 0) ?>" data-suffix="+">0</div>
                <div class="lp-stat__label">Subscribers Managed</div>
            </div>
            <div class="lp-stat lp-reveal lp-reveal-delay-2">
                <div class="lp-stat__icon"><i class="fas fa-server"></i></div>
                <div class="lp-stat__value">Built for Uptime</div>
                <div class="lp-stat__label">Auto-Failover Ready</div>
            </div>
            <div class="lp-stat lp-reveal lp-reveal-delay-3">
                <div class="lp-stat__icon"><i class="fas fa-calendar-check"></i></div>
                <div class="lp-stat__value" data-count="<?= max(1, (int) date('Y') - 2020) ?>" data-suffix="+">0</div>
                <div class="lp-stat__label">Years Experience</div>
            </div>
        </div>
    </div>
</section>
