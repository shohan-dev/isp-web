<?php
$seoTitle = 'Plugins & Addons | ' . ($appName ?? 'ISP Pay BD');
$seoDescription = 'Browse payment gateways, OTT bundles, SMS APIs, and hardware integrations you can turn on for your ISP Pay BD platform.';
$seoCanonicalUrl = base_url('plugins');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= csrf_hash() ?>">
    <title><?= esc($seoTitle) ?></title>
    <meta name="description" content="<?= esc($seoDescription) ?>">
    <link rel="canonical" href="<?= esc($seoCanonicalUrl) ?>">
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= esc($seoTitle) ?>">
    <meta property="og:description" content="<?= esc($seoDescription) ?>">
    <?= renderBrandFaviconTags() ?>
    <?php /* Self-hosted fonts + Font Awesome — same shell as the homepage, no googleapis/cdnjs. */ ?>
    <link rel="preload" href="<?= base_url('assets/fonts/plus-jakarta-latin.woff2') ?>" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="<?= base_url('assets/css/landing/fonts.css') ?>?v=1">
    <link rel="stylesheet" href="<?= base_url('assets/vendor/fontawesome/all.min.css') ?>?v=6.7.2-1">
    <link rel="stylesheet" href="<?= base_url('assets/css/landing/landing.css') ?>?v=5.0">
    <style>
        .lp-plugins-hero {
            padding: calc(var(--lp-nav-h) + 64px) 0 72px;
            background:
                radial-gradient(ellipse 70% 70% at 80% 20%, rgba(6, 182, 212, 0.16) 0%, transparent 60%),
                radial-gradient(ellipse 60% 60% at 15% 90%, rgba(247, 88, 3, 0.12) 0%, transparent 55%),
                linear-gradient(180deg, var(--lp-primary-900) 0%, var(--lp-primary-800) 60%, var(--lp-primary-850) 100%);
        }
        .lp-plugins-hero__header { max-width: 640px; }
        .lp-plugins-page__layout {
            display: grid;
            grid-template-columns: 264px 1fr;
            gap: 32px;
            align-items: start;
        }
        .lp-plugins-sidebar {
            position: sticky;
            top: calc(var(--lp-nav-h) + 24px);
            background: var(--lp-surface);
            border: 1px solid var(--lp-border-light);
            border-radius: var(--lp-radius-md);
            box-shadow: var(--lp-shadow);
            padding: 24px;
        }
        .lp-plugins-sidebar__head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
        .lp-plugins-sidebar h2 {
            font-family: var(--lp-font-display);
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--lp-text-dark);
            margin: 0;
        }
        .lp-cat-list { display: flex; flex-direction: column; gap: 4px; }
        .lp-cat-link {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            padding: 11px 14px;
            border-radius: 10px;
            color: var(--lp-text-secondary) !important;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9375rem;
            transition: all 0.2s var(--lp-ease);
        }
        .lp-cat-link:hover { background: rgba(255, 255, 255, 0.05); color: var(--lp-text-dark) !important; }
        .lp-cat-link.is-active { background: var(--lp-accent); color: #fff !important; }
        .lp-cat-badge {
            font-size: 0.75rem;
            font-weight: 700;
            padding: 2px 9px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.06);
            color: var(--lp-text-secondary);
            flex-shrink: 0;
        }
        .lp-cat-link.is-active .lp-cat-badge { background: rgba(255, 255, 255, 0.22); color: #fff; }
        .lp-plugins-empty {
            grid-column: 1 / -1;
            text-align: center;
            padding: 64px 24px;
            background: var(--lp-surface);
            border: 1px dashed var(--lp-border-light);
            border-radius: var(--lp-radius-md);
        }
        .lp-plugins-empty img { width: 140px; opacity: 0.5; margin-bottom: 16px; }
        .lp-plugins-empty h3 { font-family: var(--lp-font-display); color: var(--lp-text-secondary); margin: 0; }
        .lp-plugins-page .lp-plugins { grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); }
        .lp-plugins-page .lp-plugin__image { height: 150px; }
        .lp-plugin__cycle {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--lp-accent-text);
            margin-bottom: 6px;
        }
        @media (max-width: 900px) {
            .lp-plugins-page__layout { grid-template-columns: 1fr; }
            .lp-plugins-sidebar { position: static; padding: 16px; }
            .lp-plugins-sidebar h2 { display: none; }
            .lp-cat-list {
                flex-direction: row;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                gap: 8px;
            }
            .lp-cat-link { flex-shrink: 0; border-radius: 999px; border: 1px solid var(--lp-border-light); padding: 9px 16px; }
        }
    </style>
</head>
<body class="lp-body">

<a href="#lp-main" class="lp-skip-link">Skip to content</a>

<?= view('landing/partials/nav', ['logoUrl' => $logoUrl ?? null, 'appName' => $appName ?? null, 'brandUserId' => $brandUserId ?? null, 'tenant' => $tenant ?? null]) ?>

<main id="lp-main">
    <section class="lp-plugins-hero">
        <div class="lp-container">
            <div class="lp-plugins-hero__header lp-reveal">
                <span class="lp-section__label">Plugins &amp; Addons</span>
                <h1 class="lp-section__title">Extend Your Platform With Powerful Add-ons</h1>
                <p class="lp-section__desc">Payment gateways, OTT bundles, SMS APIs, hardware integrations and more — turn on what your ISP needs, whenever you need it.</p>
            </div>
        </div>
    </section>

    <section class="lp-section lp-section--light lp-plugins-page" id="lp-plugins-browse" data-lp-section>
        <div class="lp-container">
            <div class="lp-plugins-page__layout">
                <aside class="lp-plugins-sidebar lp-reveal">
                    <div class="lp-plugins-sidebar__head">
                        <h2>Categories</h2>
                    </div>
                    <nav class="lp-cat-list">
                        <?php foreach ($category_counts as $name => $count): ?>
                            <a href="<?= route_to('route.plugins.index') ?>?category=<?= urlencode($name) ?>"
                               class="lp-cat-link <?= $active_category === $name ? 'is-active' : '' ?>">
                                <span><?= esc($name) ?></span>
                                <span class="lp-cat-badge"><?= (int) $count ?></span>
                            </a>
                        <?php endforeach; ?>
                    </nav>
                </aside>

                <div class="lp-plugins lp-stagger-children lp-reveal">
                    <?php if (empty($plugins)): ?>
                        <div class="lp-plugins-empty">
                            <img src="<?= base_url('assets/img/no-data.svg') ?>" alt="">
                            <h3>No plugins found in this category</h3>
                        </div>
                    <?php else: ?>
                        <?php foreach ($plugins as $plugin): ?>
                            <?php
                                $imgPath = $plugin['image'] ?? null;
                                if ($imgPath && !str_starts_with($imgPath, 'assets/')) {
                                    $imgPath = 'assets/img/plugins_images/' . $imgPath;
                                }
                                $hasImage = $imgPath && file_exists(FCPATH . $imgPath);
                                $desc = (string) ($plugin['description'] ?? '');
                                $descShort = mb_strlen($desc) > 110 ? mb_substr($desc, 0, 110) . '...' : $desc;
                                $priceType = (string) ($plugin['price_type'] ?? '');
                                $priceAmount = $plugin['price'] ?? null;
                                $amountFormatted = ($priceAmount !== null && $priceAmount !== '')
                                    ? '৳' . rtrim(rtrim(number_format((float) $priceAmount, 2), '0'), '.')
                                    : null;
                                $priceLineLabel = strtolower($priceType) === 'free' ? 'Free' : ($amountFormatted ?? $priceType);
                                $cycleText = strtolower((string) ($plugin['billing_cycle'] ?? ''));
                            ?>
                            <div class="lp-plugin lp-reveal-child">
                                <?php if ($priceType !== ''): ?>
                                    <span class="lp-plugin__badge"><?= esc($priceType) ?></span>
                                <?php endif; ?>
                                <div class="lp-plugin__image">
                                    <?php if ($hasImage): ?>
                                        <img src="<?= esc(base_url($imgPath), 'attr') ?>" alt="<?= esc($plugin['title'] ?? '') ?>" loading="lazy">
                                    <?php else: ?>
                                        <i class="fas fa-puzzle-piece" aria-hidden="true"></i>
                                    <?php endif; ?>
                                </div>
                                <?php if ($priceLineLabel !== '' || $cycleText !== ''): ?>
                                    <div class="lp-plugin__cycle">
                                        <?= esc($priceLineLabel) ?><?= $cycleText !== '' ? ' &middot; ' . esc($cycleText) : '' ?>
                                    </div>
                                <?php endif; ?>
                                <h3 class="lp-plugin__title"><?= esc($plugin['title'] ?? '') ?></h3>
                                <p class="lp-plugin__desc"><?= esc($descShort) ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <?= view('landing/partials/cta_contact', ['logoUrl' => $logoUrl ?? null, 'appName' => $appName ?? null]) ?>
</main>

<?= view('landing/partials/footer', ['logoUrl' => $logoUrl ?? null, 'appName' => $appName ?? null, 'brandUserId' => $brandUserId ?? null, 'tenant' => $tenant ?? null]) ?>

<div class="lp-mobile-cta" id="lp-mobile-cta">
    <a href="<?= route_to('route.auth.registration') ?>" class="lp-btn lp-btn--primary lp-btn--block">Start Free Trial</a>
</div>

<script src="<?= base_url('assets/js/landing/landing.js') ?>?v=3.9"></script>
</body>
</html>
