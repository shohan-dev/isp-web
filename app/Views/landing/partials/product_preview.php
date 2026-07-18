<?php
$dashboardImg = esc($dashboardImg ?? base_url('assets/img/icon/laptop-screen.webp') . '?v=4', 'attr');

/* Legacy hardcoded tabs — FALLBACK ONLY, used when no super-admin has
   configured any website showcase categories yet ($lpProductShowcase['website']
   empty). Keep this array exactly as it always was. */
$productTabs = [
    [
        'id' => 'billing',
        'label' => 'Billing',
        'image' => $dashboardImg,
        'bullets' => [
            'Auto invoices, SMS reminders, and bKash/Nagad collection',
            'Payment reconciliation matched to the right subscriber',
            'Expiry disconnect and paid reconnect on autopilot',
        ],
    ],
    [
        'id' => 'mikrotik',
        'label' => 'MikroTik Sync',
        'image' => $dashboardImg,
        'bullets' => [
            'PPPoE and hotspot user sync in real time',
            'Online/offline status per customer',
            'Unlimited routers on every plan',
        ],
    ],
    [
        'id' => 'invoices',
        'label' => 'Invoices',
        'image' => $dashboardImg,
        'bullets' => [
            'Printable invoices and payment history',
            'Partial payments and due tracking',
            'Reseller commission splits built in',
        ],
    ],
    [
        'id' => 'reports',
        'label' => 'Reports',
        'image' => $dashboardImg,
        'bullets' => [
            'Revenue, collection, and subscriber growth dashboards',
            'BTRC-ready exports where applicable',
            'Area, package, and reseller performance views',
        ],
    ],
];

/* $lpProductShowcase comes in via $lpData from home.php — see
   App\Models\ProductShowcaseCategory::landingShowcasePayload(). Always has
   both keys, either of which may be an empty array. */
$lpProductShowcase = $lpProductShowcase ?? ['website' => [], 'mobile' => []];

if (!empty($lpProductShowcase['website'])) {
    // Already in the categories-with-images shape from the shared contract.
    $websiteCategories = $lpProductShowcase['website'];
} else {
    // Synthesize the same shape from the legacy tabs so today's single-image
    // behavior is preserved untouched when nothing has been configured yet.
    $websiteCategories = array_map(static function ($tab) {
        return [
            'id' => $tab['id'],
            'slug' => $tab['id'],
            'name' => $tab['label'],
            'bullets' => $tab['bullets'],
            'images' => [
                ['id' => 0, 'url' => $tab['image'], 'caption' => ''],
            ],
        ];
    }, $productTabs);
}

// No legacy mobile-tour content to preserve — an empty array is a valid,
// expected state (super-admin simply hasn't configured mobile screenshots).
$mobileCategories = $lpProductShowcase['mobile'] ?? [];

/* Prepend an "All" tab that aggregates every screenshot across categories, in
   the same serial order they already arrive in (category sort, then image sort).
   It carries the merged images in its own data-images, so the existing carousel
   JS pages through the whole set with a correct "01 / NN" serial — no JS change.
   Only added when there is more than one image to actually page through. */
$lpBuildAllTab = static function (array $categories): array {
    $all = [];
    foreach ($categories as $cat) {
        foreach (($cat['images'] ?? []) as $img) {
            $all[] = $img;
        }
    }
    if (count($all) < 2) {
        return $categories;
    }
    array_unshift($categories, [
        'id'      => 'all',
        'slug'    => 'all',
        'name'    => 'All',
        'bullets' => [
            'Every screen — billing, MikroTik, invoices, reports, and the subscriber app',
            'Real screenshots from a live operator dashboard',
            'Page through the full product in one view',
        ],
        'images'  => $all,
    ]);
    return $categories;
};
$websiteCategories = $lpBuildAllTab($websiteCategories);
$mobileCategories  = $lpBuildAllTab($mobileCategories);

/* Builds the "01 / 05 · Caption text" mono caption line — PHP and JS produce
   this identically so the initial server render never mismatches the first
   client-side update. */
$lpFormatShowcaseCaption = static function (array $images, int $index): string {
    $count = count($images);
    if ($count === 0) {
        return '';
    }
    $n = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
    $m = str_pad((string) $count, 2, '0', STR_PAD_LEFT);
    $caption = $images[$index]['caption'] ?? '';
    return $n . ' / ' . $m . ($caption !== '' ? ' · ' . $caption : '');
};

$firstWebsiteImages = $websiteCategories[0]['images'] ?? [];
$firstWebsiteImage = $firstWebsiteImages[0]['url'] ?? $dashboardImg;
$hasMultiWebsiteImages = count($firstWebsiteImages) > 1;

$firstMobileImages = $mobileCategories[0]['images'] ?? [];
$firstMobileImage = $firstMobileImages[0]['url'] ?? '';
$hasMultiMobileImages = count($firstMobileImages) > 1;
?>
<section class="lp-section lp-section--dark" id="lp-product" data-lp-section>
  <div class="lp-container">
    <div class="lp-section__header lp-reveal">
      <span class="lp-section__label">Product Tour</span>
      <h2 class="lp-section__title">See It In Action</h2>
      <p class="lp-section__desc">Browse real screens from the operator dashboard — billing, MikroTik sync, invoices, reports — and the subscriber mobile app.</p>
    </div>

    <?php if (!empty($mobileCategories)): ?>
    <!-- Website / Mobile switch -->
    <div class="lp-showcase-model lp-reveal" role="tablist" aria-label="Product tour view">
        <button type="button" class="lp-showcase-model__btn is-active" id="lp-showcase-model-website" role="tab" aria-selected="true" aria-controls="lp-showcase-panel-website">
            <i class="fas fa-desktop"></i>
            <span>Website</span>
            <em>Admin &amp; reseller portal</em>
        </button>
        <button type="button" class="lp-showcase-model__btn" id="lp-showcase-model-mobile" role="tab" aria-selected="false" aria-controls="lp-showcase-panel-mobile">
            <i class="fas fa-mobile-screen-button"></i>
            <span>Mobile App</span>
            <em>Customer &amp; field app</em>
        </button>
    </div>
    <?php endif; ?>

    <!-- ═══ WEBSITE PANEL ═══ -->
    <div id="lp-showcase-panel-website" class="lp-showcase-panel is-active"<?= !empty($mobileCategories) ? ' role="tabpanel" aria-labelledby="lp-showcase-model-website"' : '' ?>>
        <div class="lp-product lp-reveal">
            <div class="lp-product__tabs" role="tablist" aria-label="Website tour">
                <?php foreach ($websiteCategories as $i => $cat):
                    $catImages = array_map(static function ($img) {
                        return ['url' => $img['url'] ?? '', 'caption' => $img['caption'] ?? ''];
                    }, $cat['images'] ?? []);
                ?>
                    <button type="button"
                        class="lp-product__tab<?= $i === 0 ? ' is-active' : '' ?>"
                        role="tab"
                        aria-selected="<?= $i === 0 ? 'true' : 'false' ?>"
                        data-category-index="<?= (int) $i ?>"
                        data-images="<?= esc(json_encode($catImages, JSON_UNESCAPED_UNICODE), 'attr') ?>"
                        data-bullets="<?= esc(json_encode($cat['bullets'] ?? [], JSON_UNESCAPED_UNICODE), 'attr') ?>">
                        <?= esc($cat['name'] ?? '') ?>
                    </button>
                <?php endforeach; ?>
            </div>
            <div class="lp-product__preview">
                <div class="lp-browser-frame">
                    <div class="lp-browser-frame__bar">
                        <span></span><span></span><span></span>
                        <div class="lp-browser-frame__url">app.isppaybd.com</div>
                    </div>
                    <img id="lp-product-preview-website" src="<?= esc($firstWebsiteImage, 'attr') ?>" alt="ISP Pay BD product screenshot — <?= esc($websiteCategories[0]['name'] ?? '', 'attr') ?>" loading="lazy" decoding="async">
                </div>
            </div>
            <div class="lp-showcase-nav<?= $hasMultiWebsiteImages ? '' : ' is-hidden' ?>" id="lp-showcase-nav-website">
                <button type="button" class="lp-showcase-nav__btn" data-dir="prev" aria-label="Previous screenshot" disabled>
                    <i class="fas fa-chevron-left" aria-hidden="true"></i>
                </button>
                <span class="lp-showcase-nav__caption" id="lp-showcase-caption-website"><?= esc($lpFormatShowcaseCaption($firstWebsiteImages, 0)) ?></span>
                <button type="button" class="lp-showcase-nav__btn" data-dir="next" aria-label="Next screenshot"<?= $hasMultiWebsiteImages ? '' : ' disabled' ?>>
                    <i class="fas fa-chevron-right" aria-hidden="true"></i>
                </button>
            </div>
            <div class="lp-product__bullets" id="lp-product-bullets-website">
                <?php foreach (($websiteCategories[0]['bullets'] ?? []) as $point): ?>
                    <div class="lp-product__bullet"><i class="fas fa-check"></i><span><?= esc($point) ?></span></div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <?php if (!empty($mobileCategories)): ?>
    <!-- ═══ MOBILE PANEL ═══ -->
    <div id="lp-showcase-panel-mobile" class="lp-showcase-panel" hidden role="tabpanel" aria-labelledby="lp-showcase-model-mobile">
        <div class="lp-product lp-reveal">
            <div class="lp-product__tabs" role="tablist" aria-label="Mobile app tour">
                <?php foreach ($mobileCategories as $i => $cat):
                    $catImages = array_map(static function ($img) {
                        return ['url' => $img['url'] ?? '', 'caption' => $img['caption'] ?? ''];
                    }, $cat['images'] ?? []);
                ?>
                    <button type="button"
                        class="lp-product__tab<?= $i === 0 ? ' is-active' : '' ?>"
                        role="tab"
                        aria-selected="<?= $i === 0 ? 'true' : 'false' ?>"
                        data-category-index="<?= (int) $i ?>"
                        data-images="<?= esc(json_encode($catImages, JSON_UNESCAPED_UNICODE), 'attr') ?>"
                        data-bullets="<?= esc(json_encode($cat['bullets'] ?? [], JSON_UNESCAPED_UNICODE), 'attr') ?>">
                        <?= esc($cat['name'] ?? '') ?>
                    </button>
                <?php endforeach; ?>
            </div>
            <div class="lp-product__preview">
                <div class="lp-phone-frame lp-phone-frame--lg">
                    <div class="lp-phone-frame__notch"></div>
                    <div class="lp-phone-frame__screen">
                        <img id="lp-product-preview-mobile" src="<?= esc($firstMobileImage, 'attr') ?>" alt="ISP Pay BD mobile app screenshot — <?= esc($mobileCategories[0]['name'] ?? '', 'attr') ?>" loading="lazy" decoding="async">
                    </div>
                </div>
            </div>
            <div class="lp-showcase-nav<?= $hasMultiMobileImages ? '' : ' is-hidden' ?>" id="lp-showcase-nav-mobile">
                <button type="button" class="lp-showcase-nav__btn" data-dir="prev" aria-label="Previous screenshot" disabled>
                    <i class="fas fa-chevron-left" aria-hidden="true"></i>
                </button>
                <span class="lp-showcase-nav__caption" id="lp-showcase-caption-mobile"><?= esc($lpFormatShowcaseCaption($firstMobileImages, 0)) ?></span>
                <button type="button" class="lp-showcase-nav__btn" data-dir="next" aria-label="Next screenshot"<?= $hasMultiMobileImages ? '' : ' disabled' ?>>
                    <i class="fas fa-chevron-right" aria-hidden="true"></i>
                </button>
            </div>
            <div class="lp-product__bullets" id="lp-product-bullets-mobile">
                <?php foreach (($mobileCategories[0]['bullets'] ?? []) as $point): ?>
                    <div class="lp-product__bullet"><i class="fas fa-check"></i><span><?= esc($point) ?></span></div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
  </div>
</section>
