<?= $this->extend('layout/main-layout'); ?>

<?= $this->section('css'); ?>
<style>
    body {
        background-color: var(--bg);
    }
    .content-wrapper {
        background-color: var(--bg) !important;
    }
    .plugin-sidebar {
        background: transparent;
        padding: 0;
        box-shadow: none;
        margin-bottom: 20px;
    }
    .plugin-sidebar h2 {
        font-size: 32px; /* Even bigger */
        font-weight: 900;
        color: var(--text-primary);
        margin-bottom: 30px;
    }
    .plugin-sidebar .nav-link {
        color: var(--text-secondary);
        padding: 18px 25px; /* Larger padding */
        border-radius: 12px;
        margin-bottom: 8px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: all 0.2s;
        text-decoration: none;
        font-weight: 700;
        font-size: 18px; /* Bigger font */
    }
    .plugin-sidebar .badge {
        font-size: 15px; /* Bigger badge */
        background: var(--surface-2);
        color: var(--text-secondary);
        border-radius: 15px;
        padding: 6px 12px;
        font-weight: 800;
    }
    
    /* Grid System Override */
    .plugins-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(min(100%, 320px), 1fr));
        gap: 24px;
        width: 100%;
        max-width: 100%;
    }

    .plugin-card {
        background: var(--surface);
        border-radius: 20px;
        overflow: hidden;
        box-shadow: var(--shadow-1);
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
        border: 1px solid var(--border);
        position: relative;
        padding: 40px;
        height: 100%;
        text-align: left;
        min-width: 0;
    }
    .plugin-image-wrapper {
        height: 280px;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 35px;
        background: var(--surface-2);
        border-radius: 15px;
        overflow: hidden;
        padding: 20px;
    }
    .plugin-image-wrapper img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
        display: block;
    }
    .price-badge {
        position: absolute;
        top: 20px;
        right: 20px;
        background: #3ac7c9;
        color: white;
        padding: 8px 18px;
        border-radius: 30px;
        font-size: 14px;
        font-weight: 800;
        z-index: 5;
    }
    .plugin-body {
        padding: 0;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
    }
    .plugin-price-cycle {
        color: #3ac7c9;
        font-size: 18px;
        font-weight: 700;
        margin-bottom: 15px;
    }
    .plugin-title {
        font-size: 28px;
        font-weight: 900;
        margin-bottom: 15px;
        color: var(--text-primary);
        line-height: 1.2;
    }
    .plugin-desc {
        font-size: 17px;
        color: var(--text-secondary);
        line-height: 1.7;
        margin: 0;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
        min-height: 90px;
    }
    .plugin-actions {
        position: absolute;
        top: 20px;
        left: 20px;
        display: flex;
        gap: 5px;
        opacity: 0;
        transition: opacity 0.3s;
        z-index: 10;
    }
    .plugin-card:hover .plugin-actions,
    .plugin-card:focus-within .plugin-actions {
        opacity: 1;
    }
    @media (hover: none), (max-width: 1024px) {
        .plugin-actions,
        .edit-overlay {
            opacity: 1 !important;
        }
    }
    @media (max-width: 1024px) {
        .plugins-grid {
            grid-template-columns: repeat(auto-fill, minmax(min(100%, 280px), 1fr));
            gap: 18px;
        }
        .plugin-card { padding: 24px; }
        .plugin-image-wrapper { height: 200px; margin-bottom: 20px; }
        .plugin-title { font-size: 22px; }
        .plugin-desc { font-size: 15px; min-height: 0; }
        .plugin-sidebar h2 { font-size: 24px; margin-bottom: 16px; }
        .plugin-sidebar .nav-link { font-size: 15px; padding: 12px 16px; }
        /* .btn-action resolves to 32x32 (the later duplicate rule below wins
           the cascade) and, via the hover:none/1024px rule above, is forced
           permanently visible — not hover-revealed — on every touch/tablet
           screen. 32px is under the ~40-44px tap-target floor for a button
           a finger has to hit directly with no hover to aim first. */
        .btn-action {
            width: 40px;
            height: 40px;
            font-size: 14px;
        }
    }
    @media (max-width: 767px) {
        .plugins-grid {
            grid-template-columns: 1fr;
            gap: 14px;
        }
        .plugin-card { padding: 18px; border-radius: 14px; }
        .plugin-image-wrapper { height: 160px; margin-bottom: 14px; padding: 12px; }
        .plugin-title { font-size: 18px; margin-bottom: 8px; }
        .plugin-desc { font-size: 14px; line-height: 1.5; -webkit-line-clamp: 4; }
        .plugin-sidebar h2 { font-size: 20px; }
        .btn-add-plugin,
        .plugin-card .btn {
            width: 100%;
            min-height: 44px;
        }
        .btn-action {
            width: 44px;
            height: 44px;
            font-size: 15px;
        }
        .price-badge {
            top: 12px;
            right: 12px;
            font-size: 12px;
            padding: 6px 12px;
        }
    }
    .btn-action {
        width: 28px;
        height: 28px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        border: none;
        cursor: pointer;
        color: white;
    }
    .btn-edit { background: #4f46e5; }
    .btn-delete { background: #ef4444; }

    .btn-add-plugin {
        background: #3ac7c9;
        color: white;
        border: none;
        transition: background 0.3s;
    }
    .btn-add-plugin:hover {
        background: #2eaeb1;
        color: white;
    }
    
    /* Overlay for Edit Button */
    .edit-overlay {
        position: absolute;
        bottom: 15px;
        right: 15px;
        opacity: 0;
        transition: opacity 0.3s;
    }
    .plugin-card:hover .edit-overlay {
        opacity: 1;
    }
    .plugin-price {
        font-weight: 700;
        color: var(--text-primary);
    }
    .plugin-cycle {
        font-size: 11px;
        color: var(--text-muted);
        display: block;
    }
    
    .btn-add-plugin {
        background: linear-gradient(135deg, #4f46e5, #06b6d4);
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 10px;
        font-weight: 600;
        box-shadow: var(--shadow-brand, 0 4px 12px rgba(79, 70, 229, 0.3));
    }
    .btn-add-plugin:hover {
        color: white;
        transform: scale(1.02);
    }

    .plugin-actions {
        position: absolute;
        top: 10px;
        right: 10px;
        display: flex;
        gap: 5px;
        z-index: 10;
    }
    .btn-action {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        border: none;
        cursor: pointer;
        box-shadow: var(--shadow-2, 0 2px 5px rgba(0,0,0,0.2));
        transition: all 0.2s;
    }
    .btn-action:hover {
        transform: scale(1.1);
    }
    .btn-edit { background: #4f46e5; color: #fff; }
    .btn-delete { background: #ef4444; color: #fff; }
</style>
<?= $this->endSection(); ?>

<?= $this->section('content'); ?>
<div class="content-wrapper">
    <section class="content" style="padding: 40px 20px;">
        <div class="row">
            <!-- Sidebar Categories -->
            <div class="col-md-3">
                <div class="plugin-sidebar">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="mb-0">Categories</h2>
                        <?php if (getSession('user_role') === 'super_admin'): ?>
                        <button class="btn btn-add-plugin btn-sm" data-toggle="modal" data-target="#addPluginModal" style="padding: 5px 12px; font-size: 12px; border-radius: 6px;">
                            <i class="fa fa-plus"></i> Add
                        </button>
                        <?php endif; ?>
                    </div>
                    <div class="nav flex-column">
                        <?php foreach($category_counts as $name => $count): ?>
                            <a href="<?= route_to('route.plugins.index') ?>?category=<?= urlencode($name) ?>" 
                               class="nav-link <?= $active_category === $name ? 'active' : '' ?>">
                                <span><?= $name ?></span>
                                <span class="badge <?= $active_category === $name ? 'badge-light text-dark' : 'badge-primary' ?>"><?= $count ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Plugin Cards -->
            <div class="col-md-9" style="padding-left: 50px;">
                <div class="plugins-grid">
                    <?php if(empty($plugins)): ?>
                        <div class="text-center py-5" style="grid-column: span 3;">
                            <img src="<?= base_url('assets/img/no-data.svg') ?>" style="width: 200px; opacity: 0.5;">
                            <h3 class="mt-4 text-muted">No plugins found in this category</h3>
                        </div>
                    <?php else: ?>
                        <?php foreach($plugins as $plugin): ?>
                            <div class="plugin-card">
                                <?php if (getSession('user_role') === 'super_admin'): ?>
                                <div class="plugin-actions">
                                    <button class="btn-action btn-edit edit-plugin-btn" 
                                            data-id="<?= $plugin['id'] ?>"
                                            data-title="<?= htmlspecialchars($plugin['title']) ?>"
                                            data-category="<?= htmlspecialchars($plugin['category']) ?>"
                                            data-desc="<?= htmlspecialchars($plugin['description']) ?>"
                                            data-price="<?= htmlspecialchars($plugin['price_type']) ?>"
                                            data-cycle="<?= htmlspecialchars($plugin['billing_cycle']) ?>">
                                        <i class="fa fa-edit"></i>
                                    </button>
                                    <button class="btn-action btn-delete delete-plugin-btn" data-id="<?= $plugin['id'] ?>">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                                <?php endif; ?>
                                
                                <div class="price-badge"><?= $plugin['price_type'] ?></div>

                                <div class="plugin-image-wrapper">
                                    <?php 
                                        $imgPath = $plugin['image'];
                                        if ($imgPath && !str_starts_with($imgPath, 'assets/')) {
                                            $imgPath = 'assets/img/plugins_images/' . $imgPath;
                                        }
                                    ?>
                                    <?php if($imgPath && file_exists(FCPATH . $imgPath)): ?>
                                        <img src="<?= base_url($imgPath) ?>" alt="<?= $plugin['title'] ?>">
                                    <?php else: ?>
                                        <i class="fa fa-puzzle-piece fa-4x text-muted opacity-20"></i>
                                    <?php endif; ?>
                                </div>

                                <div class="plugin-body">
                                    <div class="plugin-price-cycle">
                                        <?= $plugin['price_type'] ?> (<?= strtolower($plugin['billing_cycle']) ?>)
                                    </div>
                                    <h3 class="plugin-title"><?= $plugin['title'] ?></h3>
                                    <p class="plugin-desc">
                                        <?= strlen($plugin['description']) > 110 ? substr($plugin['description'], 0, 110).'...' : $plugin['description'] ?>
                                    </p>
                                </div>

                                <?php if (getSession('user_role') === 'super_admin'): ?>
                                <div class="edit-overlay">
                                    <button class="btn btn-xs btn-primary rounded-pill px-3 edit-plugin-btn"
                                        data-id="<?= $plugin['id'] ?>"
                                        data-title="<?= htmlspecialchars($plugin['title']) ?>"
                                        data-category="<?= htmlspecialchars($plugin['category']) ?>"
                                        data-desc="<?= htmlspecialchars($plugin['description']) ?>"
                                        data-price="<?= htmlspecialchars($plugin['price_type']) ?>"
                                        data-cycle="<?= htmlspecialchars($plugin['billing_cycle']) ?>">
                                        <i class="fa fa-pencil"></i>
                                    </button>
                                </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Add Plugin Modal -->
<div class="modal fade" id="addPluginModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" style="border-radius: 15px; overflow: hidden;">
            <div class="modal-header bg-primary text-white">
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
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Price Type</label>
                                <select name="price_type" class="form-control" required>
                                    <option value="Free">Free</option>
                                    <option value="Paid">Paid</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
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
        <div class="modal-content" style="border-radius: 15px; overflow: hidden;">
            <div class="modal-header bg-info text-white">
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
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Price Type</label>
                                <select name="price_type" id="edit_price_type" class="form-control" required>
                                    <option value="Free">Free</option>
                                    <option value="Paid">Paid</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
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
            const cycle = $(this).data('cycle');
            
            $('#edit_title').val(title);
            $('#edit_category').val(category);
            $('#edit_description').val(desc);
            $('#edit_price_type').val(price);
            $('#edit_billing_cycle').val(cycle);
            
            $('#editPluginForm').attr('action', '<?= base_url('plugins/update') ?>/' + id);
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
                        url: '<?= base_url('plugins/delete') ?>/' + id,
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
