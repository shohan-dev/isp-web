<section class="lp-section lp-section--light" id="lp-faq" data-lp-section>
    <div class="lp-container">
        <div class="lp-section__header lp-reveal">
            <span class="lp-section__label">FAQ</span>
            <h2 class="lp-section__title">Answers before you ask sales.</h2>
            <p class="lp-section__desc">How billing, MikroTik sync, bKash/Nagad reconciliation, and reseller payouts actually work on ISP Pay BD.</p>
        </div>
        <?php
            // Two balanced columns (was one long list): first half left, second half
            // right, so the eye scans down each column instead of one tall stack.
            $lpFaqList  = $lpFaqs ?? [];
            $lpFaqSplit = (int) ceil(count($lpFaqList) / 2);
            $lpFaqLeft  = array_slice($lpFaqList, 0, $lpFaqSplit, true);
            $lpFaqRight = array_slice($lpFaqList, $lpFaqSplit, null, true);
            $lpRenderFaq = static function (int $i, array $faq): void { ?>
                <div class="lp-faq__item">
                    <button class="lp-faq__question" id="lp-faq-q-<?= $i ?>" aria-expanded="false" aria-controls="lp-faq-answer-<?= $i ?>">
                        <span class="lp-faq__question-text"><?= esc($faq['q']) ?></span>
                        <i class="fas fa-chevron-down" aria-hidden="true"></i>
                    </button>
                    <div class="lp-faq__answer" id="lp-faq-answer-<?= $i ?>"><div><p><?= $faq['a'] ?></p></div></div>
                </div>
            <?php };
        ?>
        <div class="lp-faq lp-faq--cols lp-reveal">
            <div class="lp-faq__col">
                <?php foreach ($lpFaqLeft as $i => $faq) $lpRenderFaq($i, $faq); ?>
            </div>
            <div class="lp-faq__col">
                <?php foreach ($lpFaqRight as $i => $faq) $lpRenderFaq($i, $faq); ?>
            </div>
        </div>
    </div>
</section>
