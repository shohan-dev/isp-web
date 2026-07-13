<?php
helper(['utility', 'tenant']);
$brandUserId = function_exists('tenantBrandingUserId') ? tenantBrandingUserId() : 2;
$tenant = function_exists('currentTenant') ? currentTenant() : null;
$isTenantPortal = function_exists('isTenantRequest') && isTenantRequest() && $tenant;
$seoBrandImageUrl = resolvePublicBrandLogoUrl($tenant, $brandUserId);
$brandTitle = resolveBrandTitle($tenant, $brandUserId);

// Tenant portals must not claim isppaybd.com as canonical — derive from the request host.
if ($isTenantPortal) {
    $requestUri = service('request')->getUri();
    $seoCanonicalUrl = $requestUri->getScheme() . '://' . $requestUri->getHost() . '/';
    $seoTitle = $brandTitle . ' | ISP Billing & Management';
    $seoSiteName = $brandTitle;
} else {
    $seoCanonicalUrl = 'https://isppaybd.com/';
    $seoTitle = 'ISP PAY BD | Complete ISP Billing & Management Platform';
    $seoSiteName = 'ISP Pay BD';
}
$seoDescription = 'ISP PAY BD is the premier ISP Billing and Management Software in Bangladesh. Automate billing, Mikrotik, CRM, and customer management for growing ISPs.';
$organizationSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'Organization',
    'name' => $seoSiteName,
    'url' => $seoCanonicalUrl,
    'logo' => $seoBrandImageUrl,
    'contactPoint' => [
        '@type' => 'ContactPoint',
        'telephone' => '+8801781-808231',
        'contactType' => 'customer service',
    ],
];
$softwareSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'SoftwareApplication',
    'name' => $seoSiteName,
    'url' => $seoCanonicalUrl,
    'image' => $seoBrandImageUrl,
    'publisher' => ['@type' => 'Organization', 'name' => $seoSiteName, 'logo' => $seoBrandImageUrl],
    'applicationCategory' => 'BusinessApplication',
    'operatingSystem' => 'Web-based',
    'description' => $seoDescription,
];

// Single source of truth for pricing — loaded from admin_packages in AuthController::home
// with hardcoded fallbacks when the DB has no public plans yet.
$lpPricing = $lpPricing ?? [
    'tiers' => [
        'basic'      => ['id' => null, 'price' => 999,   'cap' => 500,   'name' => 'Basic'],
        'standard'   => ['id' => null, 'price' => 2499,  'cap' => 2000,  'name' => 'Standard'],
        'premium'    => ['id' => null, 'price' => 4999,  'cap' => 5000,  'name' => 'Premium'],
        'business'   => ['id' => null, 'price' => 8499,  'cap' => 10000, 'name' => 'Business'],
        'enterprise' => ['id' => null, 'price' => 14999, 'cap' => 20000, 'name' => 'Enterprise'],
        'ultimate'   => ['id' => null, 'price' => 24999, 'cap' => 40000, 'name' => 'Ultimate'],
    ],
    'payg' => ['platform' => 500, 'perUser' => 1.5, 'minWallet' => 750],
    'addons' => [],
];
$lpTierOrder = ['basic', 'standard', 'premium', 'business', 'enterprise', 'ultimate'];
$lpFixedCards = [];
foreach ($lpTierOrder as $tierKey) {
    if (!empty($lpPricing['tiers'][$tierKey])) {
        $lpFixedCards[] = array_merge($lpPricing['tiers'][$tierKey], ['key' => $tierKey]);
    }
}
if (empty($lpFixedCards) && !empty($lpFixedPlans)) {
    $tierKeys = $lpTierOrder;
    foreach ($lpFixedPlans as $i => $pkg) {
        $row = is_object($pkg) ? (array) $pkg : $pkg;
        $lpFixedCards[] = [
            'key'   => $tierKeys[$i] ?? ('tier' . $i),
            'id'    => (int) ($row['id'] ?? 0),
            'price' => (float) ($row['price'] ?? 0),
            'cap'   => (int) ($row['duration'] ?? 0),
            'name'  => (string) ($row['package_name'] ?? 'Plan'),
        ];
    }
}

// Single source of truth for the FAQ — rendered by faq.php and emitted as FAQPage JSON-LD.
$lpFaqs = [
    ['q' => 'How does the Pay-As-You-Go wallet work?', 'a' => 'You add a prepaid balance to your ISP Pay BD wallet (minimum ৳750). Each month, we automatically deduct your usage fee based on active subscribers: ৳500 platform fee + ৳1.50 per active user. If your balance runs low, you\'ll get alerts to top up — no surprise invoices.'],
    ['q' => 'What\'s the difference between fixed plans and pay-as-you-go?', 'a' => '<strong>Fixed plans</strong> (Basic, Standard, Premium) give you a set user limit and fixed monthly price — best if your subscriber count is stable. <strong>Pay-as-you-go</strong> has no tier limits; you prepay wallet balance and pay only for actual active users each month. Ideal for growing ISPs or seasonal fluctuations.'],
    ['q' => 'What happens when my wallet balance runs out?', 'a' => 'We send email and SMS alerts when your balance covers less than 7 days. You have a 3-day grace period to top up. Your data is never deleted — service resumes immediately once balance is restored.'],
    ['q' => 'How long does setup take?', 'a' => 'Most ISPs go live within 2–5 business days including account setup, data migration, Mikrotik configuration, and team training. Pay-as-you-go accounts can start immediately after wallet top-up.'],
    ['q' => 'Can you migrate my existing subscriber data?', 'a' => 'Yes — free migration for Standard, Premium, and Pay-As-You-Go accounts. We transfer subscribers, packages, billing history, and customer records from your current system.'],
    ['q' => 'Do you support Mikrotik routers?', 'a' => 'Yes. Unlimited Mikrotik routers via API — PPPoE, hotspot management, and real-time online/offline sync included on all plans.'],
    ['q' => 'Is there a free trial?', 'a' => 'Every account gets a 14-day free trial with full feature access. No wallet top-up required to start. After trial, choose a fixed plan or add wallet balance for pay-as-you-go.'],
    ['q' => 'What payment methods are accepted?', 'a' => 'Wallet top-ups and subscriptions accept bKash, Nagad, bank transfer, and SSLCommerz. Your end-customers can also pay bills via bKash, Nagad, and SSLCommerz through the platform.'],
    ['q' => 'Can I switch between fixed plans and pay-as-you-go?', 'a' => 'Yes, anytime. Switch from a fixed plan to pay-as-you-go (or vice versa) from your dashboard. Unused wallet balance carries over. No lock-in contracts.'],
    ['q' => 'Can I use my own domain and branding?', 'a' => 'White-label branding (your logo, colors, custom domain) is included on Premium and available as a +৳500/mo add-on for Pay-As-You-Go accounts.'],
    ['q' => 'Is my billing and customer data secure?', 'a' => 'Yes. Data is encrypted in transit, hosted on secured infrastructure, and backed up daily. We follow least-privilege access for our team and never sell your subscriber data.'],
    ['q' => 'Do I own my data? Can I export it?', 'a' => 'You own your data. Export subscribers, billing history, and reports from the dashboard at any time — no lock-in.'],
    ['q' => 'Will connecting my MikroTik affect my live network?', 'a' => 'No. API sync is read/write only for PPPoE/hotspot profiles you manage through ISP Pay BD. We configure during a scheduled window and test on a single router first if you prefer.'],
    ['q' => 'Are there long-term contracts?', 'a' => 'No lock-in. Monthly or PAYG wallet billing — cancel anytime. Your data remains exportable after cancellation.'],
];
$faqSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => array_map(static fn ($f) => [
        '@type' => 'Question',
        'name' => $f['q'],
        'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags($f['a'])],
    ], $lpFaqs),
];

$lpData = [
    'active_admins' => (int) ($active_admins ?? 0),
    'active_users'  => (int) ($active_users ?? 0),
    // One shared figure so every section quotes the same count — the real
    // count, never floored to a fake minimum (that was the invented-metric
    // problem this item was built to remove).
    'trusted_isps'  => (int) ($active_admins ?? 0),
    'seoBrandImageUrl' => $seoBrandImageUrl,
    'seoDescription' => $seoDescription,
    'seoCanonicalUrl' => $seoCanonicalUrl,
    'appName' => $brandTitle,
    'logoUrl' => resolvePublicBrandLogoUrl($tenant, $brandUserId),
    'iconUrl' => resolvePublicBrandLogoUrl($tenant, $brandUserId),
    'brandUserId' => $brandUserId,
    'tenant' => $tenant,
    'isTenantPortal' => $isTenantPortal,
    'dashboardImg' => base_url('assets/img/icon/laptop-screen.webp') . '?v=4',
    'lpPricing' => $lpPricing,
    'lpFixedCards' => $lpFixedCards,
    'lpFaqs' => $lpFaqs,
    'lpTestimonials' => $lpTestimonials ?? [],
    'lpCaseStudy' => $lpCaseStudy ?? null,
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= csrf_hash() ?>">
    <title><?= esc($seoTitle) ?></title>
    <meta name="description" content="<?= esc($seoDescription) ?>">
    <meta name="keywords" content="ISP Billing Software Bangladesh, ISP Management System, ISP Pay BD, Mikrotik Billing, Broadband Management">
    <meta name="author" content="<?= esc($seoSiteName) ?>">
    <link rel="canonical" href="<?= esc($seoCanonicalUrl) ?>">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= esc($seoSiteName) ?>">
    <meta property="og:url" content="<?= esc($seoCanonicalUrl) ?>">
    <meta property="og:title" content="<?= esc($seoTitle) ?>">
    <meta property="og:description" content="<?= esc($seoDescription) ?>">
    <meta property="og:image" content="<?= esc($seoBrandImageUrl) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= esc($seoTitle) ?>">
    <meta name="twitter:description" content="<?= esc($seoDescription) ?>">
    <meta name="twitter:image" content="<?= esc($seoBrandImageUrl) ?>">
    <?= renderBrandFaviconTags() ?>
    <link rel="preload" as="image" href="<?= esc($lpData['dashboardImg'], 'attr') ?>" fetchpriority="high">
    <?php /* Self-hosted fonts + Font Awesome — no googleapis/cdnjs on the landing page. */ ?>
    <link rel="preload" href="<?= base_url('assets/fonts/plus-jakarta-latin.woff2') ?>" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="<?= base_url('assets/css/landing/fonts.css') ?>?v=1">
    <link rel="stylesheet" href="<?= base_url('assets/vendor/fontawesome/all.min.css') ?>?v=6.7.2-1">
    <link rel="stylesheet" href="<?= base_url('assets/css/landing/landing.css') ?>?v=4.3">
    <script type="application/ld+json"><?= json_encode($organizationSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
    <script type="application/ld+json"><?= json_encode($softwareSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
    <script type="application/ld+json"><?= json_encode($faqSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
</head>
<body class="lp-body">

<a href="#lp-main" class="lp-skip-link">Skip to content</a>

<?= view('landing/partials/nav', $lpData) ?>

<main id="lp-main">
    <?php
    // Section order: pricing moved up next to features (the #1 question for this
    // audience); why_choose + partners removed — their content duplicated benefits
    // and the trust marquee (see docs/landing-review/03-content-copy.md §6).
    ?>
    <?= view('landing/partials/hero', $lpData) ?>
    <?= view('landing/partials/auto_reconciliation', $lpData) ?>
    <?= view('landing/partials/benefits', $lpData) ?>
    <?= view('landing/partials/product_preview', $lpData) ?>
    <?= view('landing/partials/features', $lpData) ?>
    <?= view('landing/partials/reseller_hierarchy', $lpData) ?>
    <?= view('landing/partials/pricing', $lpData) ?>
    <?= view('landing/partials/mobile_app', $lpData) ?>
    <?= view('landing/partials/how_it_works', $lpData) ?>
    <?= view('landing/partials/comparison', $lpData) ?>
    <?= view('landing/partials/integrations', $lpData) ?>
    <?= view('landing/partials/proof_band', $lpData) ?>
    <?= view('landing/partials/case_study', $lpData) ?>
    <?= view('landing/partials/testimonials', $lpData) ?>
    <?= view('landing/partials/faq', $lpData) ?>
    <?= view('landing/partials/cta_contact', $lpData) ?>
</main>

<?= view('landing/partials/footer', $lpData) ?>

<div class="lp-mobile-cta" id="lp-mobile-cta">
    <a href="<?= route_to('route.auth.registration') ?>" class="lp-btn lp-btn--primary lp-btn--block">Start Free Trial</a>
</div>

<script>
// Single source of truth for pricing — consumed by landing.js (calculator, ROI, toggle).
window.LP_PRICING = <?= json_encode($lpPricing, JSON_UNESCAPED_UNICODE) ?>;
</script>
<?php /* reCAPTCHA is lazy-loaded by landing.js when the contact section approaches. */ ?>
<script src="<?= base_url('assets/js/landing/landing.js') ?>?v=3.8"></script>
</body>
</html>
