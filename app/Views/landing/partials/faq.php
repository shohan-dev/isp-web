<section class="lp-section lp-section--light" id="lp-faq" data-lp-section>
    <div class="lp-container">
        <div class="lp-section__header lp-reveal">
            <span class="lp-section__label">FAQ</span>
            <h2 class="lp-section__title">Frequently Asked Questions</h2>
            <p class="lp-section__desc">Clear answers about plans, pay-as-you-go wallet, setup, and support.</p>
        </div>
        <div class="lp-faq lp-reveal">
            <?php foreach (($lpFaqs ?? []) as $i => $faq): ?>
                <div class="lp-faq__item">
                    <button class="lp-faq__question" id="lp-faq-q-<?= $i ?>" aria-expanded="false" aria-controls="lp-faq-answer-<?= $i ?>">
                        <?= esc($faq['q']) ?>
                        <i class="fas fa-chevron-down" aria-hidden="true"></i>
                    </button>
                    <div class="lp-faq__answer" id="lp-faq-answer-<?= $i ?>"><div><p><?= $faq['a'] ?></p></div></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
