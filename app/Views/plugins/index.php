<?= $this->extend('layout/main-layout'); ?>

<?= $this->section('css'); ?>
<style>
    :root {
        --plp-radius-md: 12px;
        --plp-ease: cubic-bezier(0.16, 1, 0.3, 1);
    }

    @media (prefers-reduced-motion: reduce) {
        .plugins-lp *, .plugins-lp *::before, .plugins-lp *::after {
            animation-duration: 0.01ms !important;
            transition-duration: 0.01ms !important;
        }
    }

    .plugins-lp {
        font-family: var(--font-sans);
        color: var(--text-primary);
        font-size: var(--text-base);
    }

    /* ── Layout ── */
    .plp-layout {
        display: grid;
        grid-template-columns: 240px minmax(0, 1fr);
        gap: 20px;
        align-items: start;
    }
    @media (max-width: 900px) {
        .plp-layout { grid-template-columns: 1fr; }
    }

    /* ── Sidebar ── */
    .plp-sidebar__card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--plp-radius-md);
        box-shadow: var(--shadow-1);
        padding: 16px;
        position: sticky;
        top: 16px;
    }
    .plp-sidebar__head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
    }
    .plp-sidebar__head h2 {
        font-size: var(--text-xs);
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: var(--text-muted);
        margin: 0;
    }

    .plp-cat-list {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .plp-cat-link {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
        padding: 10px 12px;
        border-radius: 8px;
        color: var(--text-secondary) !important;
        text-decoration: none;
        font-weight: 600;
        font-size: var(--text-md);
        transition: background 0.15s var(--plp-ease), color 0.15s var(--plp-ease);
    }
    .plp-cat-link:hover { background: var(--surface-2); color: var(--text-primary) !important; text-decoration: none; }
    .plp-cat-link.is-active {
        background: var(--primary-500);
        color: #fff !important;
    }
    .plp-cat-badge {
        font-size: var(--text-xs);
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 999px;
        background: var(--surface-2);
        color: var(--text-secondary);
        flex-shrink: 0;
    }
    .plp-cat-link.is-active .plp-cat-badge {
        background: rgba(255, 255, 255, 0.22);
        color: #fff;
    }
    .plp-cat-link i {
        width: 18px;
        text-align: center;
        color: var(--text-muted);
        flex-shrink: 0;
        font-size: var(--text-base);
    }
    .plp-cat-link.is-active i,
    .plp-cat-link:hover i {
        color: inherit;
    }
    .plp-cat-link span.plp-cat-name {
        display: flex;
        align-items: center;
        gap: 8px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        min-width: 0;
    }

    @media (max-width: 900px) {
        .plp-sidebar__card { position: static; padding: 14px; }
        .plp-cat-list {
            flex-direction: row;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            gap: 6px;
            padding-bottom: 2px;
            scrollbar-width: thin;
        }
        .plp-cat-link {
            flex-shrink: 0;
            border-radius: 999px;
            border: 1px solid var(--border);
            padding: 8px 14px;
            font-size: var(--text-base);
        }
        .plp-cat-link.is-active { border-color: var(--primary-500); }
    }

    /* ── Main column: toolbar + card grid ── */
    .plp-main {
        display: flex;
        flex-direction: column;
        gap: 14px;
        min-width: 0;
    }

    .plp-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }
    .plp-search {
        position: relative;
        flex: 1 1 320px;
        max-width: 480px;
    }
    .plp-search i {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-muted);
        font-size: var(--text-base);
        pointer-events: none;
    }
    .plp-search input {
        width: 100%;
        padding: 11px 14px 11px 40px;
        border-radius: var(--radius, 10px);
        border: 1px solid var(--border);
        background: var(--surface);
        color: var(--text-primary);
        font-size: var(--text-input);
        transition: border-color 0.2s var(--plp-ease), box-shadow 0.2s var(--plp-ease);
    }
    .plp-search input::placeholder { color: var(--text-muted); }
    .plp-search input:focus {
        outline: none;
        border-color: var(--primary-500);
        box-shadow: var(--shadow-focus, 0 0 0 3px rgba(var(--primary-rgb), 0.15));
    }
    .plp-result-count {
        font-size: var(--text-sm);
        font-weight: 600;
        color: var(--text-muted);
        white-space: nowrap;
    }

    /*
     * 2-column card grid (minmax 380px → ~2 wide cards on desktop).
     * Type uses design-system px tokens — rem looked zoomed-out under AdminLTE.
     */
    .plp-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(min(100%, 380px), 1fr));
        gap: 16px;
    }
    .plp-card.is-hidden { display: none; }

    @keyframes plp-fade-up {
        from { opacity: 0; transform: translateY(6px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .plp-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--plp-radius-md);
        padding: 18px;
        display: flex;
        flex-direction: column;
        gap: 12px;
        min-height: 0;
        overflow: hidden;
        isolation: isolate;
        transition: box-shadow 0.2s var(--plp-ease), border-color 0.2s var(--plp-ease);
        animation: plp-fade-up 0.3s var(--plp-ease) both;
    }
    .plp-card:hover {
        box-shadow: var(--shadow-2);
        border-color: var(--primary-300, var(--border));
    }

    .plp-card__top {
        display: flex;
        align-items: flex-start;
        gap: 14px;
        min-width: 0;
    }

    .plp-card__media {
        width: 56px;
        height: 56px;
        flex: 0 0 56px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--surface-2);
        border: 1px solid var(--border);
        border-radius: 12px;
        overflow: hidden;
        padding: 8px;
    }
    .plp-card__media img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        object-position: center;
    }
    .plp-card__media i {
        width: 100%;
        height: 100%;
        display: grid;
        place-items: center;
        border-radius: 8px;
        background: color-mix(in srgb, var(--tone-color, var(--primary-500)) 12%, transparent);
        color: var(--tone-color, var(--primary-500));
        font-size: 22px;
        line-height: 1;
    }
    .plp-card__media[data-tone="mobile"]   { --tone-color: var(--data-4); }
    .plp-card__media[data-tone="vas"]      { --tone-color: var(--data-2); }
    .plp-card__media[data-tone="network"]  { --tone-color: var(--data-5); }
    .plp-card__media[data-tone="sms"]      { --tone-color: var(--data-6); }
    .plp-card__media[data-tone="payment"]  { --tone-color: var(--data-1); }
    .plp-card__media[data-tone="hardware"] { --tone-color: var(--data-8); }

    .plp-card__heading {
        flex: 1 1 auto;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 4px;
        padding-top: 2px;
    }

    .plp-card .plp-card__title {
        font-size: var(--text-lg);
        font-weight: 700;
        color: var(--text-primary);
        line-height: 1.3;
        margin: 0;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .plp-card__category {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        max-width: 100%;
        font-size: var(--text-sm);
        font-weight: 600;
        color: var(--tone-color, var(--primary-600));
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .plp-card__category i { font-size: var(--text-xs); opacity: 0.85; }
    .plp-card__category[data-tone="mobile"]   { --tone-color: var(--data-4); }
    .plp-card__category[data-tone="vas"]      { --tone-color: var(--data-2); }
    .plp-card__category[data-tone="network"]  { --tone-color: var(--data-5); }
    .plp-card__category[data-tone="sms"]      { --tone-color: var(--data-6); }
    .plp-card__category[data-tone="payment"]  { --tone-color: var(--data-1); }
    .plp-card__category[data-tone="hardware"] { --tone-color: var(--data-8); }

    .plp-card__badge {
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        padding: 5px 10px;
        border-radius: 6px;
        font-size: var(--text-xs);
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        line-height: 1.2;
        margin-top: 2px;
    }
    .plp-card__badge--free { background: var(--success-50); color: var(--success-600); }
    .plp-card__badge--paid { background: var(--primary-50); color: var(--primary-600); }

    .plp-card__desc {
        font-size: var(--text-md);
        color: var(--text-secondary);
        line-height: 1.5;
        margin: 0;
        flex: 1 1 auto;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .plp-card__footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-top: auto;
        padding-top: 12px;
        border-top: 1px solid var(--border);
    }

    .plp-card__price {
        display: flex;
        align-items: baseline;
        flex-wrap: wrap;
        gap: 6px;
        font-size: var(--text-lg);
        font-weight: 700;
        color: var(--text-primary);
        line-height: 1.2;
        min-width: 0;
    }
    .plp-card__price-cycle {
        font-size: var(--text-sm);
        font-weight: 600;
        color: var(--text-muted);
    }

    .plp-card__actions {
        display: flex;
        gap: 6px;
        flex-shrink: 0;
    }
    .plp-icon-btn {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        border: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        color: #fff;
        cursor: pointer;
        box-shadow: var(--shadow-1);
        transition: transform 0.15s var(--plp-ease);
    }
    .plp-icon-btn:hover { transform: scale(1.06); }
    .plp-icon-btn--edit { background: var(--info-500); }
    .plp-icon-btn--delete { background: var(--error-500); }

    @media (max-width: 767px) {
        .plp-grid {
            grid-template-columns: 1fr;
            gap: 12px;
        }
        .plp-card { padding: 16px; }
        .plp-card__media { width: 52px; height: 52px; flex-basis: 52px; }
        .plp-icon-btn { width: 40px; height: 40px; }
    }

    /* ── Modal polish (Bootstrap 3 modal, behavior untouched) ── */
    #addPluginModal .modal-content,
    #editPluginModal .modal-content {
        border-radius: var(--plp-radius-md);
        overflow: hidden;
        border: none;
        box-shadow: var(--shadow-2);
        background: var(--surface);
        color: var(--text-primary);
    }
    #addPluginModal .modal-header {
        background: var(--primary-500);
        border-bottom: none;
        padding: 18px 24px;
    }
    #editPluginModal .modal-header {
        background: var(--info-500);
        border-bottom: none;
        padding: 18px 24px;
    }
    #addPluginModal .modal-title,
    #editPluginModal .modal-title {
        font-weight: 700;
    }
    #addPluginModal label,
    #editPluginModal label {
        color: var(--text-secondary);
    }
    #addPluginModal .form-control,
    #editPluginModal .form-control {
        border-radius: 8px;
        border: 1px solid var(--border);
        background: var(--surface-2);
        color: var(--text-primary);
        box-shadow: none;
    }
    #addPluginModal .form-control:focus,
    #editPluginModal .form-control:focus {
        border-color: var(--primary-500);
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.15);
    }
    #addPluginModal .btn-primary {
        background: var(--primary-500);
        border-color: var(--primary-500);
    }
    #addPluginModal .btn-primary:hover {
        background: var(--primary-600);
        border-color: var(--primary-600);
    }
    #editPluginModal .btn-info {
        background: var(--info-500);
        border-color: var(--info-500);
    }
    #editPluginModal .btn-info:hover {
        background: var(--info-600);
        border-color: var(--info-600);
    }
</style>
<?= $this->endSection(); ?>

<?= $this->section('content'); ?>
<div class="content-wrapper">
    <section class="content plugins-lp">

        <?php
            // Not $this->include('components/page-header', [...]) — that method's
            // 2nd param is View::render()'s $options (cache settings), not view
            // data; the array here would be silently discarded and title/breadcrumb/
            // actions would never reach page-header.php. Rendering the same markup
            // directly guarantees the values actually apply.
        ?>
        <div class="ipb-page-header fade-in">
            <div class="ipb-page-header-main">
                <nav class="ipb-breadcrumb" aria-label="Breadcrumb">
                    <ol>
                        <li><a href="<?= esc(route_to('route.dashboard'), 'attr') ?>">Dashboard</a></li>
                        <li aria-current="page"><span>Plugins &amp; Addons</span></li>
                    </ol>
                </nav>
                <h1>Plugins &amp; Addons <small>Enable, price and manage the integrations available to ISPs</small></h1>
            </div>
            <?php if (getSession('user_role') === 'super_admin'): ?>
            <div class="ipb-page-actions">
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addPluginModal">
                    <i class="fa fa-plus" aria-hidden="true"></i> Add Plugin
                </button>
            </div>
            <?php endif; ?>
        </div>

        <?php
            // Category → sidebar icon + card-placeholder/chip tone. Keys must match
            // the category strings in Plugins::buildCatalog() exactly. Anything not
            // listed (a category typed into a future migration, or none yet) falls
            // back to a neutral puzzle-piece icon / brand tint rather than erroring.
            $categoryIcons = [
                'All addons'             => 'fa-th-large',
                "Mobile Application's"   => 'fa-mobile-screen',
                'VAS'                     => 'fa-star',
                'Network & Monitoring'   => 'fa-network-wired',
                'SMS Gateway API'        => 'fa-comment-dots',
                'Payment Gateway (API)'  => 'fa-credit-card',
                'Hardware Integration'  => 'fa-microchip',
            ];
            $categoryTones = [
                "Mobile Application's"   => 'mobile',
                'VAS'                     => 'vas',
                'Network & Monitoring'   => 'network',
                'SMS Gateway API'        => 'sms',
                'Payment Gateway (API)'  => 'payment',
                'Hardware Integration'  => 'hardware',
            ];
        ?>
        <div class="plp-layout">
            <!-- Sidebar Categories -->
            <aside class="plp-sidebar">
                <div class="plp-sidebar__card">
                    <div class="plp-sidebar__head">
                        <h2>Categories</h2>
                    </div>
                    <nav class="plp-cat-list" aria-label="Plugin categories">
                        <?php foreach($category_counts as $name => $count): ?>
                            <?php $isActive = $active_category === $name; ?>
                            <a href="<?= esc(route_to('route.plugins.admin'), 'attr') ?>?category=<?= urlencode($name) ?>"
                               class="plp-cat-link <?= $isActive ? 'is-active' : '' ?>"
                               <?= $isActive ? 'aria-current="page"' : '' ?>>
                                <span class="plp-cat-name">
                                    <i class="fa <?= esc($categoryIcons[$name] ?? 'fa-puzzle-piece', 'attr') ?>" aria-hidden="true"></i>
                                    <?= esc($name) ?>
                                </span>
                                <span class="plp-cat-badge"><?= (int) $count ?></span>
                            </a>
                        <?php endforeach; ?>
                    </nav>
                </div>
            </aside>

            <div class="plp-main">
                <!-- Search + result count -->
                <div class="plp-toolbar">
                    <div class="plp-search">
                        <i class="fa fa-magnifying-glass" aria-hidden="true"></i>
                        <input type="search" id="pluginSearch" placeholder="Search plugins by name or description&hellip;" aria-label="Search plugins">
                    </div>
                    <span class="plp-result-count" id="pluginCount" aria-live="polite">
                        <?= count($plugins) ?> plugin<?= count($plugins) === 1 ? '' : 's' ?>
                    </span>
                </div>

                <!-- Plugin Cards -->
                <div class="plp-grid" id="pluginGrid">
                    <?php if(empty($plugins)): ?>
                        <?php
                            // Inlined rather than view('components/empty-state', [...]): that
                            // helper always resolves the SHARED CodeIgniter\View\View instance
                            // (Services::renderer()), and setData() merges into its tempData
                            // buffer without isolating nested calls. Since this file is itself
                            // mid-render inside $this->extend('layout/main-layout'), a nested
                            // call passing 'title' overwrites the outer $title CI4 reads for
                            // <title> right after — confirmed live: it silently retitled the
                            // browser tab to this block's title on every load. Same markup as
                            // components/empty-state.php's .ipb-empty contract, just inline.
                        ?>
                        <div class="ipb-empty">
                            <div class="ipb-empty-icon"><i class="fa <?= esc($categoryIcons[$active_category] ?? 'fa-puzzle-piece', 'attr') ?>" aria-hidden="true"></i></div>
                            <div class="ipb-empty-title">No plugins in this category</div>
                            <div class="ipb-empty-sub">Pick another category from the left, or add the first plugin here.</div>
                            <?php if (getSession('user_role') === 'super_admin'): ?>
                                <div class="ipb-empty-action">
                                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addPluginModal">
                                        <i class="fa fa-plus" aria-hidden="true"></i> Add Plugin
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <?php $i = 0; foreach($plugins as $plugin): $i++; ?>
                            <?php
                                $priceType = (string) ($plugin['price_type'] ?? '');
                                $isFree = strtolower($priceType) === 'free';
                                $imgPath = $plugin['image'] ?? null;
                                if ($imgPath && !str_starts_with($imgPath, 'assets/')) {
                                    $imgPath = 'assets/img/plugins_images/' . $imgPath;
                                }
                                $descRaw = (string) ($plugin['description'] ?? '');
                                if (mb_strlen($descRaw) > 140) {
                                    $descCut = mb_substr($descRaw, 0, 140);
                                    $lastSpace = mb_strrpos($descCut, ' ');
                                    $descShort = ($lastSpace !== false && $lastSpace > 0)
                                        ? mb_substr($descCut, 0, $lastSpace) . '...'
                                        : $descCut . '...';
                                } else {
                                    $descShort = $descRaw;
                                }
                                $priceAmount = $plugin['price'] ?? null;
                                $amountFormatted = ($priceAmount !== null && $priceAmount !== '' && (float) $priceAmount > 0)
                                    ? '৳' . rtrim(rtrim(number_format((float) $priceAmount, 2), '0'), '.')
                                    : null;
                                $cycleText = trim((string) ($plugin['billing_cycle'] ?? ''));
                                // Price line: amount when set, else Free/Paid label. Cycle as muted suffix.
                                if ($amountFormatted !== null) {
                                    $priceLabel = $amountFormatted;
                                } elseif ($isFree) {
                                    $priceLabel = 'Free';
                                } else {
                                    $priceLabel = $priceType !== '' ? $priceType : '—';
                                }
                                $categoryName = (string) ($plugin['category'] ?? '');
                                $catIcon = $categoryIcons[$categoryName] ?? 'fa-puzzle-piece';
                                $catTone = $categoryTones[$categoryName] ?? '';
                                $searchIndex = mb_strtolower($plugin['title'] . ' ' . $categoryName . ' ' . $descRaw);
                            ?>
                            <div class="plugin-card plp-card" style="animation-delay: <?= min($i * 0.05, 0.4) ?>s" data-search="<?= esc($searchIndex, 'attr') ?>">
                                <div class="plp-card__top">
                                    <div class="plp-card__media" data-tone="<?= esc($catTone, 'attr') ?>">
                                        <?php if($imgPath && file_exists(FCPATH . $imgPath)): ?>
                                            <img src="<?= esc(base_url($imgPath), 'attr') ?>" alt="<?= esc($plugin['title'] ?? '') ?>">
                                        <?php else: ?>
                                            <i class="fa <?= esc($catIcon, 'attr') ?>" aria-hidden="true"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="plp-card__heading">
                                        <h3 class="plp-card__title"><?= esc($plugin['title'] ?? '') ?></h3>
                                        <?php if ($categoryName !== ''): ?>
                                            <span class="plp-card__category" data-tone="<?= esc($catTone, 'attr') ?>">
                                                <i class="fa <?= esc($catIcon, 'attr') ?>" aria-hidden="true"></i>
                                                <?= esc($categoryName) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <span class="plp-card__badge <?= $isFree ? 'plp-card__badge--free' : 'plp-card__badge--paid' ?>"><?= esc($priceType) ?></span>
                                </div>

                                <p class="plp-card__desc"><?= esc($descShort) ?></p>

                                <div class="plp-card__footer">
                                    <div class="plp-card__price">
                                        <span><?= esc($priceLabel) ?></span>
                                        <?php if ($cycleText !== ''): ?>
                                            <span class="plp-card__price-cycle"><?= esc($cycleText) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (getSession('user_role') === 'super_admin'): ?>
                                    <div class="plp-card__actions">
                                        <button class="plp-icon-btn plp-icon-btn--edit edit-plugin-btn"
                                                data-id="<?= $plugin['id'] ?>"
                                                data-title="<?= htmlspecialchars($plugin['title']) ?>"
                                                data-category="<?= htmlspecialchars($plugin['category']) ?>"
                                                data-desc="<?= htmlspecialchars($plugin['description']) ?>"
                                                data-price="<?= htmlspecialchars($plugin['price_type']) ?>"
                                                data-amount="<?= htmlspecialchars((string) ($plugin['price'] ?? '')) ?>"
                                                data-cycle="<?= htmlspecialchars($plugin['billing_cycle']) ?>"
                                                aria-label="Edit plugin">
                                            <i class="fa fa-edit"></i>
                                        </button>
                                        <button class="plp-icon-btn plp-icon-btn--delete delete-plugin-btn" data-id="<?= $plugin['id'] ?>" aria-label="Delete plugin">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- Client-side "no search results" state — hidden unless a search query matches
                         nothing. Renders unconditionally on every load (JS just toggles display),
                         so this MUST NOT be a nested view() call — see the comment on the other
                         .ipb-empty block above for why that clobbers the page's <title>. -->
                    <div class="plp-search-empty" id="pluginSearchEmpty" style="display:none; grid-column: 1 / -1;">
                        <div class="ipb-empty">
                            <div class="ipb-empty-icon"><i class="fa fa-magnifying-glass" aria-hidden="true"></i></div>
                            <div class="ipb-empty-title">No plugins match your search</div>
                            <div class="ipb-empty-sub">Try a different name or clear the search box.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Add Plugin Modal -->
<div class="modal fade" id="addPluginModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header text-white">
                <h4 class="modal-title">Add New Plugin</h4>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form action="<?= route_to('route.plugins.store') ?>" method="POST" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Plugin Title</label>
                                <input type="text" name="title" class="form-control" placeholder="e.g. Bongo OTT" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Category</label>
                                <select name="category" class="form-control" required>
                                    <option value="Mobile Application's">Mobile Application's</option>
                                    <option value="VAS">VAS</option>
                                    <option value="Network & Monitoring">Network & Monitoring</option>
                                    <option value="SMS Gateway API">SMS Gateway API</option>
                                    <option value="Payment Gateway (API)">Payment Gateway (API)</option>
                                    <option value="Hardware Integration">Hardware Integration</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Brief description of the plugin..."></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Price Type</label>
                                <select name="price_type" class="form-control" required>
                                    <option value="Free">Free</option>
                                    <option value="Paid">Paid</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Price Amount (&#2547;)</label>
                                <input type="number" name="price" class="form-control" min="0" step="0.01" placeholder="e.g. 299">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Billing Cycle</label>
                                <input type="text" name="billing_cycle" class="form-control" placeholder="e.g. Monthly / One-time" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Plugin Image</label>
                        <input type="file" name="image" class="form-control-file" accept="image/*">
                        <small class="text-muted">Recommended size: 300x200px</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Plugin</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Plugin Modal -->
<div class="modal fade" id="editPluginModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header text-white">
                <h4 class="modal-title">Edit Plugin</h4>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="editPluginForm" method="POST" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Plugin Title</label>
                                <input type="text" name="title" id="edit_title" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Category</label>
                                <select name="category" id="edit_category" class="form-control" required>
                                    <option value="Mobile Application's">Mobile Application's</option>
                                    <option value="VAS">VAS</option>
                                    <option value="Network & Monitoring">Network & Monitoring</option>
                                    <option value="SMS Gateway API">SMS Gateway API</option>
                                    <option value="Payment Gateway (API)">Payment Gateway (API)</option>
                                    <option value="Hardware Integration">Hardware Integration</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Price Type</label>
                                <select name="price_type" id="edit_price_type" class="form-control" required>
                                    <option value="Free">Free</option>
                                    <option value="Paid">Paid</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Price Amount (&#2547;)</label>
                                <input type="number" name="price" id="edit_price" class="form-control" min="0" step="0.01" placeholder="e.g. 299">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Billing Cycle</label>
                                <input type="text" name="billing_cycle" id="edit_billing_cycle" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Plugin Image (Leave empty to keep current)</label>
                        <input type="file" name="image" class="form-control-file" accept="image/*">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-info">Update Plugin</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection(); ?>

<?= $this->section('script'); ?>
<script>
    $(document).ready(function() {
        // Edit Plugin
        $('.edit-plugin-btn').on('click', function() {
            const id = $(this).data('id');
            const title = $(this).data('title');
            const category = $(this).data('category');
            const desc = $(this).data('desc');
            const price = $(this).data('price');
            const amount = $(this).data('amount');
            const cycle = $(this).data('cycle');

            $('#edit_title').val(title);
            $('#edit_category').val(category);
            $('#edit_description').val(desc);
            $('#edit_price_type').val(price);
            $('#edit_price').val(amount);
            $('#edit_billing_cycle').val(cycle);

            $('#editPluginForm').attr('action', '<?= base_url('admin/plugins/update') ?>/' + id);
            $('#editPluginModal').modal('show');
        });

        // Delete Plugin
        $('.delete-plugin-btn').on('click', function() {
            const id = $(this).data('id');
            const card = $(this).closest('.plugin-card');

            swal({
                title: "Are you sure?",
                text: "Once deleted, you will not be able to recover this plugin!",
                icon: "warning",
                buttons: true,
                dangerMode: true,
            })
            .then((willDelete) => {
                if (willDelete) {
                    $.ajax({
                        url: '<?= base_url('admin/plugins/delete') ?>/' + id,
                        type: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            if (response.success) {
                                card.fadeOut(300, function() {
                                    $(this).remove();
                                    const idx = pluginCards.indexOf(card[0]);
                                    if (idx !== -1) pluginCards.splice(idx, 1);
                                    applyPluginSearch();
                                });
                                tata.success('Plugin deleted', response.message);
                            } else {
                                tata.error("Couldn't delete plugin", response.message);
                            }
                        }
                    });
                }
            });
        });

        // Search / filter — client-side, the catalogue is small enough that a
        // round-trip to the server for every keystroke would be pure overhead.
        const pluginSearchInput = document.getElementById('pluginSearch');
        const pluginGrid = document.getElementById('pluginGrid');
        const pluginCountEl = document.getElementById('pluginCount');
        const pluginSearchEmptyEl = document.getElementById('pluginSearchEmpty');
        const pluginCards = pluginGrid ? Array.from(pluginGrid.querySelectorAll('.plp-card')) : [];

        function applyPluginSearch() {
            const query = (pluginSearchInput ? pluginSearchInput.value : '').trim().toLowerCase();
            let visible = 0;
            pluginCards.forEach(function (card) {
                const isMatch = query === '' || (card.dataset.search || '').includes(query);
                card.classList.toggle('is-hidden', !isMatch);
                if (isMatch) visible++;
            });
            if (pluginCountEl) {
                pluginCountEl.textContent = visible + ' plugin' + (visible === 1 ? '' : 's');
            }
            if (pluginSearchEmptyEl) {
                pluginSearchEmptyEl.style.display = (query !== '' && visible === 0) ? '' : 'none';
            }
        }

        if (pluginSearchInput && pluginCards.length) {
            pluginSearchInput.addEventListener('input', applyPluginSearch);
        }

        // Tata notifications for flash data
        <?php if(session()->getFlashdata('success')): ?>
            tata.success('Success', '<?= session()->getFlashdata('success') ?>');
        <?php endif; ?>
        <?php if(session()->getFlashdata('error')): ?>
            tata.error('Error', '<?= session()->getFlashdata('error') ?>');
        <?php endif; ?>
    });
</script>
<?= $this->endSection(); ?>
