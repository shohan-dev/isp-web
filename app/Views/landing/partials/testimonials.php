<?php
$lpTestimonials = $lpTestimonials ?? [];
if (empty($lpTestimonials)) {
    return;
}
?>
<section class="lp-section lp-section--dark" id="lp-testimonials" data-lp-section>
    <div class="lp-container">
        <div class="lp-section__header lp-reveal">
            <span class="lp-section__label">Testimonials</span>
            <h2 class="lp-section__title">Trusted by ISP Operators</h2>
            <p class="lp-section__desc">Real feedback from Internet Service Providers using ISP Pay BD daily.</p>
        </div>
        <div class="lp-testimonials" id="lp-testimonials-track">
            <?php foreach ($lpTestimonials as $i => $item): ?>
                <?php
                $rating = max(1, min(5, (int) ($item['rating'] ?? 5)));
                $initials = trim((string) ($item['avatar_initials'] ?? ''));
                if ($initials === '') {
                    $parts = preg_split('/\s+/', trim((string) ($item['name'] ?? '')), 2);
                    $initials = strtoupper(substr($parts[0] ?? 'I', 0, 1) . substr($parts[1] ?? 'P', 0, 1));
                }
                $roleLine = trim((string) ($item['role'] ?? ''));
                $company = trim((string) ($item['company'] ?? ''));
                if ($roleLine !== '' && $company !== '') {
                    $roleLine .= ', ' . $company;
                } elseif ($company !== '') {
                    $roleLine = $company;
                }
                $delayClass = $i > 0 ? ' lp-reveal-delay-' . min($i, 3) : '';
                ?>
                <div class="lp-testimonial lp-reveal<?= esc($delayClass) ?>">
                    <div class="lp-testimonial__stars" aria-label="<?= $rating ?> out of 5 stars">
                        <?php for ($s = 0; $s < $rating; $s++): ?><i class="fas fa-star"></i><?php endfor; ?>
                    </div>
                    <p class="lp-testimonial__quote">&ldquo;<?= esc($item['quote'] ?? '') ?>&rdquo;</p>
                    <div class="lp-testimonial__author">
                        <div class="lp-testimonial__avatar"><?= esc($initials) ?></div>
                        <div>
                            <div class="lp-testimonial__name"><?= esc($item['name'] ?? '') ?></div>
                            <?php if ($roleLine !== ''): ?>
                                <div class="lp-testimonial__role"><?= esc($roleLine) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if (count($lpTestimonials) > 1): ?>
        <div class="lp-testimonials-dots" id="lp-testimonials-dots">
            <?php foreach ($lpTestimonials as $i => $item): ?>
                <?php /* .lp-testimonials-dots button is 8x8px (CSS) with no padding — only
                   shown in the mobile scroll-snap slider (@media max-width:768px), so on a
                   phone this is a real swipe-dot nav control with a hit area far below the
                   ~40-44px floor. box-sizing:content-box + background-clip:content-box keep
                   the painted dot at its designed 8x8px while padding (offset by an equal
                   negative margin, so surrounding layout/gap is unaffected) grows the actual
                   tappable box to ~36x36px. */ ?>
                <button type="button" class="<?= $i === 0 ? 'is-active' : '' ?>" data-slide="<?= (int) $i ?>" aria-label="Testimonial <?= (int) $i + 1 ?>" style="box-sizing:content-box;background-clip:content-box;padding:14px;margin:-14px;"></button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>
