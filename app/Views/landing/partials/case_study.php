<?php
$lpCaseStudy = $lpCaseStudy ?? null;
if (empty($lpCaseStudy) || empty($lpCaseStudy['name'])) {
    return;
}
$name = (string) ($lpCaseStudy['name'] ?? '');
$company = (string) ($lpCaseStudy['company'] ?? '');
$metric = (string) ($lpCaseStudy['metric'] ?? '');
$quote = (string) ($lpCaseStudy['quote'] ?? '');
$logoUrl = (string) ($lpCaseStudy['logo_url'] ?? '');
$screenshotUrl = (string) ($lpCaseStudy['screenshot_url'] ?? ($dashboardImg ?? ''));
?>
<section class="lp-section lp-section--light" id="lp-case-study" data-lp-section>
    <div class="lp-container">
        <div class="lp-section__header lp-reveal">
            <span class="lp-section__label">Case Study</span>
            <h2 class="lp-section__title">How <?= esc($company !== '' ? $company : $name) ?> Runs on ISP Pay BD</h2>
        </div>
        <div class="lp-case-study lp-reveal">
            <div class="lp-case-study__copy">
                <?php if ($logoUrl !== ''): ?>
                    <img src="<?= esc($logoUrl, 'attr') ?>" alt="<?= esc($company !== '' ? $company : $name) ?> logo" class="lp-case-study__logo" loading="lazy">
                <?php endif; ?>
                <?php if ($metric !== ''): ?>
                    <p class="lp-case-study__metric"><?= esc($metric) ?></p>
                <?php endif; ?>
                <?php if ($quote !== ''): ?>
                    <blockquote class="lp-case-study__quote">&ldquo;<?= esc($quote) ?>&rdquo;</blockquote>
                <?php endif; ?>
                <p class="lp-case-study__author"><strong><?= esc($name) ?></strong><?= $company !== '' ? ' · ' . esc($company) : '' ?></p>
            </div>
            <?php if ($screenshotUrl !== ''): ?>
            <div class="lp-case-study__visual">
                <img src="<?= esc($screenshotUrl, 'attr') ?>" alt="Dashboard screenshot from <?= esc($company !== '' ? $company : $name) ?>" loading="lazy" decoding="async">
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>
