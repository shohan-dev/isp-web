<?php
    // The plugins table has duplicate title rows for at least one demo tenant —
    // dedupe defensively here so the marketplace teaser never shows the same
    // plugin twice, regardless of what's actually seeded.
    $lpPluginsUnique = [];
    foreach ($lpPlugins ?? [] as $lpPlugin) {
        $lpPluginKey = mb_strtolower(trim((string) ($lpPlugin['title'] ?? '')));
        if ($lpPluginKey === '' || isset($lpPluginsUnique[$lpPluginKey])) {
            continue;
        }
        $lpPluginsUnique[$lpPluginKey] = $lpPlugin;
    }
    $lpPlugins = array_values($lpPluginsUnique);
?>
<?php if (!empty($lpPlugins)): ?>
<section class="lp-section lp-section--light" id="lp-plugins" data-lp-section>
    <div class="lp-container">
        <div class="lp-section__header lp-reveal">
            <span class="lp-section__label">Plugins &amp; Addons</span>
            <h2 class="lp-section__title">Extend Your Platform With Plugins &amp; Addons</h2>
            <p class="lp-section__desc">Optional add-ons you can turn on anytime — payment gateways, OTT bundles, hardware catalogs, and more.</p>
        </div>
        <div class="lp-plugins lp-stagger-children lp-reveal">
            <?php foreach ($lpPlugins as $plugin): ?>
                <?php
                    $imgPath = $plugin['image'] ?? null;
                    if ($imgPath && !str_starts_with($imgPath, 'assets/')) {
                        $imgPath = 'assets/img/plugins_images/' . $imgPath;
                    }
                    $hasImage = $imgPath && file_exists(FCPATH . $imgPath);
                    $desc = (string) ($plugin['description'] ?? '');
                    $descShort = mb_strlen($desc) > 110 ? mb_substr($desc, 0, 110) . '...' : $desc;
                ?>
                <div class="lp-plugin lp-reveal-child">
                    <?php if (!empty($plugin['price_type'])): ?>
                        <span class="lp-plugin__badge"><?= esc($plugin['price_type']) ?></span>
                    <?php endif; ?>
                    <div class="lp-plugin__image">
                        <?php if ($hasImage): ?>
                            <img src="<?= esc(base_url($imgPath), 'attr') ?>" alt="<?= esc($plugin['title'] ?? '') ?>" loading="lazy">
                        <?php else: ?>
                            <i class="fas fa-puzzle-piece" aria-hidden="true"></i>
                        <?php endif; ?>
                    </div>
                    <h3 class="lp-plugin__title"><?= esc($plugin['title'] ?? '') ?></h3>
                    <p class="lp-plugin__desc"><?= esc($descShort) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="lp-plugins__cta lp-reveal">
            <a href="<?= route_to('route.plugins.index') ?>" class="lp-btn lp-btn--primary">View All Plugins &amp; Addons</a>
        </div>
    </div>
</section>
<?php endif; ?>
