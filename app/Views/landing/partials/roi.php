<section class="lp-section lp-section--dark" id="lp-roi" data-lp-section>
    <div class="lp-container">
        <div class="lp-section__header lp-reveal">
            <span class="lp-section__label">ROI Calculator</span>
            <h2 class="lp-section__title">Calculate Your Savings</h2>
            <p class="lp-section__desc">See how much you could save by switching to ISP Pay BD.</p>
        </div>
        <div class="lp-roi lp-reveal">
            <div class="lp-roi__field">
                <label for="lp-roi-subs">Number of subscribers</label>
                <input type="range" id="lp-roi-subs" class="lp-custom-pricing__slider" min="100" max="10000" step="100" value="2500" aria-label="Number of subscribers" aria-valuetext="2,500 subscribers">
                <div class="lp-roi__value" id="lp-roi-subs-display">2,500</div>
            </div>
            <div class="lp-roi__field">
                <label for="lp-roi-cost">Current monthly software cost (৳)</label>
                <input type="number" id="lp-roi-cost" placeholder="e.g. 5000" min="0" step="100" value="5000">
            </div>
            <button type="button" class="lp-btn lp-btn--primary lp-btn--block" id="lp-roi-calc">
                <i class="fas fa-calculator"></i> Calculate Savings
            </button>
            <div class="lp-roi__result" id="lp-roi-result">
                <p>You could save</p>
                <div class="lp-roi__savings" id="lp-roi-savings">৳0/year</div>
                <p id="lp-roi-monthly" style="margin-top:8px;color:var(--lp-text-muted);font-size:0.9375rem;"></p>
            </div>
        </div>
    </div>
</section>
