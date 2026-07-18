<?= $this->extend('layout/main-layout'); ?>

<?= $this->section('css'); ?>
<style>
    :root {
        --plp-radius-sm: 8px;
        --plp-radius-md: 16px;
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
    }

    /* ── Layout ── */
    .plp-layout {
        display: grid;
        grid-template-columns: 272px 1fr;
        gap: 32px;
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
        padding: 24px;
        position: sticky;
        top: 24px;
    }
    .plp-sidebar__head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 18px;
    }
    .plp-sidebar__head h2 {
        font-size: 1.1875rem;
        font-weight: 800;
        color: var(--text-primary);
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
        gap: 10px;
        padding: 12px 14px;
        border-radius: 10px;
        color: var(--text-secondary) !important;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.9375rem;
        transition: all 0.2s var(--plp-ease);
    }
    .plp-cat-link:hover { background: var(--surface-2); color: var(--text-primary) !important; text-decoration: none; }
    .plp-cat-link.is-active {
        background: var(--primary-500);
        color: #fff !important;
    }
    .plp-cat-badge {
        font-size: 0.75rem;
        font-weight: 700;
        padding: 2px 9px;
        border-radius: 999px;
        background: var(--surface-2);
        color: var(--text-secondary);
        flex-shrink: 0;
    }
    .plp-cat-link.is-active .plp-cat-badge {
        background: rgba(255, 255, 255, 0.22);
        color: #fff;
    }

    @media (max-width: 900px) {
        .plp-sidebar__card { position: static; padding: 16px; }
        .plp-cat-list {
            flex-direction: row;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            gap: 8px;
            padding-bottom: 4px;
            scrollbar-width: thin;
        }
        .plp-cat-link {
            flex-shrink: 0;
            border-radius: 999px;
            border: 1px solid var(--border);
            padding: 9px 16px;
        }
        .plp-cat-link.is-active { border-color: var(--primary-500); }
    }

    /* ── Cards grid ── */
    .plp-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(min(100%, 340px), 1fr));
        gap: 24px;
    }

    @keyframes plp-fade-up {
        from { opacity: 0; transform: translateY(14px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .plp-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--plp-radius-md);
        padding: 28px;
        position: relative;
        display: flex;
        flex-direction: column;
        transition: transform 0.25s var(--plp-ease), box-shadow 0.25s var(--plp-ease), border-color 0.25s var(--plp-ease);
        animation: plp-fade-up 0.4s var(--plp-ease) both;
    }
    .plp-card:hover {
        transform: translateY(-3px);
        box-shadow: var(--shadow-2);
        border-color: var(--primary-300, var(--border));
    }
    .plp-card__badge {
        position: absolute;
        top: 20px;
        right: 20px;
        z-index: 2;
        padding: 5px 13px;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.02em;
    }
    .plp-card__badge--free { background: var(--success-50); color: var(--success-600); }
    .plp-card__badge--paid { background: var(--primary-50); color: var(--primary-600); }

    .plp-card__actions {
        position: absolute;
        top: 14px;
        left: 14px;
        z-index: 3;
        display: flex;
        gap: 6px;
        opacity: 0;
        transition: opacity 0.25s var(--plp-ease);
    }
    .plp-card:hover .plp-card__actions,
    .plp-card:focus-within .plp-card__actions {
        opacity: 1;
    }
    .plp-icon-btn {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        border: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        color: #fff;
        cursor: pointer;
        box-shadow: var(--shadow-1);
        transition: transform 0.2s var(--plp-ease);
    }
    .plp-icon-btn:hover { transform: scale(1.08); }
    .plp-icon-btn--edit { background: var(--info-500); }
    .plp-icon-btn--delete { background: var(--error-500); }

    .plp-card__image {
        height: 138px;
        display: flex;
        align-items: center;
        justify-content: center;
        background:
            radial-gradient(130% 110% at 50% 0%, rgba(var(--primary-rgb), 0.10), transparent 60%),
            var(--surface-2);
        border-radius: var(--plp-radius-sm);
        margin-bottom: 18px;
        overflow: hidden;
        padding: 14px;
    }
    .plp-card__image img { width: 100%; height: 100%; object-fit: contain; object-position: center; }
    /* Empty placeholder: a branded icon chip so a plugin without artwork still
       reads as intentional, not a small glyph lost in a big grey slab. */
    .plp-card__image i {
        width: 56px;
        height: 56px;
        display: grid;
        place-items: center;
        border-radius: 16px;
        background: rgba(var(--primary-rgb), 0.10);
        border: 1px solid rgba(var(--primary-rgb), 0.22);
        color: var(--primary-500);
        font-size: 1.5rem;
        opacity: 1;
    }

    .plp-card__cycle {
        font-size: 0.875rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        color: var(--primary-600);
        margin-bottom: 10px;
    }
    .plp-card .plp-card__title {
        font-size: 1.3125rem;
        font-weight: 800;
        color: var(--text-primary);
        line-height: 1.3;
        margin-bottom: 10px;
    }
    .plp-card__desc {
        font-size: 0.9375rem;
        color: var(--text-secondary);
        line-height: 1.6;
        margin: 0;
        flex-grow: 1;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    /* Touch devices: action icons must stay visible (no hover to reveal them),
       and at a comfortable 40-44px tap target, not the 32px hover-only size. */
    @media (hover: none), (max-width: 1024px) {
        .plp-card__actions { opacity: 1; }
        .plp-icon-btn { width: 40px; height: 40px; font-size: 15px; }
    }

    @media (max-width: 767px) {
        .plp-grid { gap: 16px; }
        .plp-card { padding: 18px; border-radius: 14px; }
        .plp-card__image { height: 130px; margin-bottom: 14px; }
        .plp-card__title { font-size: 1.0625rem; }
        .plp-icon-btn { width: 44px; height: 44px; }
    }

    .plp-empty {
        grid-column: 1 / -1;
        text-align: center;
        padding: 56px 24px;
        background: var(--surface);
        border: 1px dashed var(--border);
        border-radius: var(--plp-radius-md);
    }
    .plp-empty img { width: 140px; opacity: 0.5; margin-bottom: 16px; }
    .plp-empty h3 {
        font-weight: 700;
        color: var(--text-secondary);
        margin: 0;
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
                <h1>Plugins &amp; Addons</h1>
            </div>
            <?php if (getSession('user_role') === 'super_admin'): ?>
            <div class="ipb-page-actions">
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addPluginModal">
                    <i class="fa fa-plus" aria-hidden="true"></i> Add Plugin
                </button>
            </div>
            <?php endif; ?>
        </div>

        <div class="plp-layout">
            <!-- Sidebar Categories -->
            <aside class="plp-sidebar">
                <div class="plp-sidebar__card">
                    <div class="plp-sidebar__head">
                        <h2>Categories</h2>
                    </div>
                    <nav class="plp-cat-list">
                        <?php foreach($category_counts as $name => $count): ?>
                            <a href="<?= route_to('route.plugins.admin') ?>?category=<?= urlencode($name) ?>"
                               class="plp-cat-link <?= $active_category === $name ? 'is-active' : '' ?>">
                                <span><?= $name ?></span>
                                <span class="plp-cat-badge"><?= $count ?></span>
                            </a>
                        <?php endforeach; ?>
                    </nav>
                </div>
            </aside>

            <!-- Plugin Cards -->
            <div class="plp-grid">
                <?php if(empty($plugins)): ?>
                    <div class="plp-empty">
                        <img src="<?= base_url('assets/img/no-data.svg') ?>" alt="">
                        <h3>No plugins found in this category</h3>
                    </div>
                <?php else: ?>
                    <?php $i = 0; foreach($plugins as $plugin): $i++; ?>
                        <div class="plugin-card plp-card" style="animation-delay: <?= min($i * 0.05, 0.4) ?>s">
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

                            <?php
                                $priceType = (string) ($plugin['price_type'] ?? '');
                                $imgPath = $plugin['image'] ?? null;
                                if ($imgPath && !str_starts_with($imgPath, 'assets/')) {
                                    $imgPath = 'assets/img/plugins_images/' . $imgPath;
                                }
                                $descRaw = (string) ($plugin['description'] ?? '');
                                if (mb_strlen($descRaw) > 110) {
                                    $descCut = mb_substr($descRaw, 0, 110);
                                    $lastSpace = mb_strrpos($descCut, ' ');
                                    $descShort = ($lastSpace !== false && $lastSpace > 0)
                                        ? mb_substr($descCut, 0, $lastSpace) . '...'
                                        : $descCut . '...';
                                } else {
                                    $descShort = $descRaw;
                                }
                                $priceAmount = $plugin['price'] ?? null;
                                $amountFormatted = ($priceAmount !== null && $priceAmount !== '')
                                    ? '৳' . rtrim(rtrim(number_format((float) $priceAmount, 2), '0'), '.')
                                    : null;
                                $priceLineLabel = strtolower($priceType) === 'free' ? 'Free' : ($amountFormatted ?? $priceType);
                                $cycleText = strtolower((string) ($plugin['billing_cycle'] ?? ''));
                            ?>
                            <div class="plp-card__badge <?= strtolower($priceType) === 'free' ? 'plp-card__badge--free' : 'plp-card__badge--paid' ?>"><?= esc($priceType) ?></div>

                            <div class="plp-card__image">
                                <?php if($imgPath && file_exists(FCPATH . $imgPath)): ?>
                                    <img src="<?= esc(base_url($imgPath), 'attr') ?>" alt="<?= esc($plugin['title'] ?? '') ?>">
                                <?php else: ?>
                                    <i class="fa fa-puzzle-piece" aria-hidden="true"></i>
                                <?php endif; ?>
                            </div>

                            <div class="plp-card__cycle">
                                <?= esc($priceLineLabel) ?><?= $cycleText !== '' ? ' &middot; ' . esc($cycleText) : '' ?>
                            </div>
                            <h3 class="plp-card__title"><?= esc($plugin['title'] ?? '') ?></h3>
                            <p class="plp-card__desc"><?= esc($descShort) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
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
