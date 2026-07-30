<?= $this->extend('layout/main-layout'); ?>

<?= $this->section('css'); ?>
<style>
  /* Themed to the saas tokens (was hardcoded #fff/#f8f9fc/#e2e8f0 — unreadable
     in dark mode). Everything routes through the panel's own palette now. */
  .ps-image-panel {
    background: var(--surface-2, #f8f9fc);
    border: 1px solid var(--border, #e2e8f0);
    border-radius: 12px;
    padding: 16px;
    margin: 6px 0 12px;
  }

  .ps-image-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(168px, 1fr));
    gap: 14px;
    margin-bottom: 14px;
  }

  .ps-image-thumb {
    background: var(--surface, #fff);
    border: 1px solid var(--border, #e2e8f0);
    border-radius: 10px;
    padding: 10px;
    transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
  }

  .ps-image-thumb:hover {
    border-color: color-mix(in srgb, var(--primary-500, #6c5ce7) 45%, transparent);
    box-shadow: var(--shadow-1, 0 4px 12px rgba(15, 23, 42, .08));
    transform: translateY(-2px);
  }

  .ps-image-thumb img {
    width: 100%;
    height: 108px;
    object-fit: cover;
    border-radius: 8px;
    margin-bottom: 8px;
    background: var(--surface-2, #eef1f6);
  }

  .ps-image-thumb input {
    margin-bottom: 8px;
  }

  .ps-add-image-form {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px;
    padding-top: 4px;
  }

  /* Icon-only action buttons — a custom class (NOT Bootstrap .btn) so the theme's
     button decoration (the stray circle behind the icon) never applies. */
  .ps-actions {
    display: inline-flex;
    gap: 8px;
    align-items: center;
  }
  .ps-act-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    padding: 0;
    border-radius: 9px;
    border: 1px solid var(--border, #e2e8f0);
    background: var(--surface, #fff);
    color: var(--text-secondary, #64748b);
    font-size: 14px;
    line-height: 1;
    cursor: pointer;
    transition: color .2s ease, border-color .2s ease, background .2s ease, transform .15s ease;
  }
  .ps-act-btn i { background: none; box-shadow: none; }
  .ps-act-btn:hover { transform: translateY(-1px); }
  .ps-toggle-images-btn.ps-act-btn:hover {
    color: var(--primary-600, #6c5ce7);
    border-color: color-mix(in srgb, var(--primary-500, #6c5ce7) 55%, transparent);
    background: color-mix(in srgb, var(--primary-500, #6c5ce7) 9%, var(--surface, #fff));
  }
  .ps-act-btn--edit { color: var(--info-600, #2563eb); }
  .ps-act-btn--edit:hover {
    border-color: color-mix(in srgb, var(--info-500, #3b82f6) 55%, transparent);
    background: color-mix(in srgb, var(--info-500, #3b82f6) 9%, var(--surface, #fff));
  }
  .ps-act-btn--danger { color: var(--error-600, #dc2626); }
  .ps-act-btn--danger:hover {
    border-color: color-mix(in srgb, var(--error-500, #ef4444) 55%, transparent);
    background: color-mix(in srgb, var(--error-500, #ef4444) 9%, var(--surface, #fff));
  }

  /* The admin theme forces table-cell .btn-sm/.btn-xs icon buttons into a 32px
     circle with font-size:0 (an icon-only row-action style). The image-manager
     buttons below carry labels, so opt them back into a normal labelled shape —
     scoped + higher-specificity so it beats the theme's !important without
     touching the global rule that other pages rely on. */
  body.ipb .ps-image-panel .btn-sm:has(i),
  body.ipb .ps-image-panel .btn-xs:has(i) {
    width: auto !important;
    height: auto !important;
    min-width: 0 !important;
    min-height: 0 !important;
    padding: 6px 12px !important;
    border-radius: 8px !important;
    font-size: 13px !important;
  }
  body.ipb .ps-image-panel .btn-sm:has(i) i,
  body.ipb .ps-image-panel .btn-xs:has(i) i {
    width: auto !important;
    height: auto !important;
    margin: 0 6px 0 0 !important;
    font-size: 13px !important;
    background: none !important;
    border: none !important;
    box-shadow: none !important;
    color: inherit !important;
  }

  .ps-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
  }
  .ps-status::before {
    content: '';
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: currentColor;
  }
  .ps-status.active {
    background: var(--success-50, rgba(34, 197, 94, 0.14));
    color: var(--success-600, #16a34a);
  }
  .ps-status.inactive {
    background: var(--error-50, rgba(239, 68, 68, 0.14));
    color: var(--error-600, #dc2626);
  }
  .ps-int-logo {
    width: 52px;
    height: 40px;
    object-fit: contain;
    background: var(--surface, #fff);
    border: 1px solid var(--border, #e2e8f0);
    border-radius: 8px;
    padding: 4px;
  }
  .ps-int-logo-fallback {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 52px;
    height: 40px;
    border-radius: 8px;
    border: 1px solid var(--border, #e2e8f0);
    background: var(--surface-2, #f8f9fc);
    color: var(--text-secondary, #64748b);
  }
  .ps-tier-pill {
    display: inline-flex;
    align-items: center;
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .04em;
    text-transform: uppercase;
  }
  .ps-tier-pill.is-core {
    background: color-mix(in srgb, var(--info-500, #3b82f6) 14%, transparent);
    color: var(--info-600, #2563eb);
  }
  .ps-tier-pill.is-also {
    background: var(--surface-2, #eef2f7);
    color: var(--text-secondary, #64748b);
  }
  .ps-int-preview {
    max-width: 120px;
    max-height: 64px;
    object-fit: contain;
    border: 1px solid var(--border, #e2e8f0);
    border-radius: 8px;
    padding: 6px;
    background: #fff;
    margin-top: 8px;
  }
</style>
<?= $this->endSection('css'); ?>

<?= $this->section('content'); ?>
<div class="content-wrapper">
  <section class="content ipb-saas-list">

    <?= $this->include('components/page-header', [
      'title' => 'Product Showcase',
      'subtitle' => 'Landing page galleries & integration logos (website, mobile, Integrations section)',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Product Showcase'],
      ],
    ]); ?>

    <div id="ps-feedback"></div>

    <?php if (!empty($loadError)): ?>
      <div class="alert alert-danger">
        <i class="fa fa-exclamation-triangle" aria-hidden="true"></i>
        <?= esc($loadError); ?>
      </div>
    <?php endif; ?>

    <?php
    $psSections = [
      'website' => ['label' => 'Website Categories', 'icon' => 'fa-desktop', 'categories' => $websiteCategories ?? []],
      'mobile'  => ['label' => 'Mobile Categories',  'icon' => 'fa-mobile-alt', 'categories' => $mobileCategories ?? []],
    ];
    ?>

    <?php foreach ($psSections as $target => $section): ?>
      <div class="box box-primary">
        <div class="box-header with-border ipb-box-toolbar">
          <div class="ipb-list-toolbar">
            <div class="ipb-list-toolbar-filters">
              <span class="ipb-filter-label"><i class="fa <?= esc($section['icon']); ?>" aria-hidden="true"></i> <?= esc($section['label']); ?></span>
            </div>
            <div class="ipb-list-toolbar-actions">
              <button type="button" class="btn btn-primary ps-add-category-btn" data-target-type="<?= esc($target, 'attr'); ?>">
                <i class="fa fa-plus" aria-hidden="true"></i> Add Category
              </button>
            </div>
          </div>
        </div>
        <div class="box-body table-responsive">
          <table class="table table-bordered table-striped">
            <thead>
              <tr>
                <th>#</th>
                <th>Name</th>
                <th>Slug</th>
                <th>Images</th>
                <th>Sort</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($section['categories'])): $psRow = 0; ?>
                <?php foreach ($section['categories'] as $category): $psRow++; ?>
                  <?php
                  $images = $category['images'] ?? [];
                  $bulletsText = '';
                  $bulletsArr = json_decode((string) ($category['bullets'] ?? ''), true);
                  if (is_array($bulletsArr)) {
                    $bulletsText = implode("\n", $bulletsArr);
                  }
                  ?>
                  <tr>
                    <?php /* Running serial per table (1,2,3…) — was the raw DB id, so
                             the Mobile table started at #3 instead of #1. */ ?>
                    <td><?= $psRow; ?></td>
                    <td><?= esc($category['name']); ?></td>
                    <td><code><?= esc($category['slug']); ?></code></td>
                    <td><?= count($images); ?></td>
                    <td><?= (int) $category['sort_order']; ?></td>
                    <td><span class="ps-status <?= esc($category['status']); ?>"><?= esc(ucfirst($category['status'])); ?></span></td>
                    <td>
                      <div class="ps-actions">
                        <button type="button" class="ps-act-btn ps-toggle-images-btn" data-id="<?= (int) $category['id']; ?>" title="Images (<?= count($images); ?>)" aria-label="Manage images">
                          <i class="fa fa-images" aria-hidden="true"></i>
                        </button>
                        <button type="button" class="ps-act-btn ps-act-btn--edit ps-edit-category-btn"
                          data-id="<?= (int) $category['id']; ?>"
                          data-name="<?= esc($category['name'], 'attr'); ?>"
                          data-target-type="<?= esc($category['target'], 'attr'); ?>"
                          data-bullets="<?= esc($bulletsText, 'attr'); ?>"
                          data-sort-order="<?= (int) $category['sort_order']; ?>"
                          data-status="<?= esc($category['status'], 'attr'); ?>"
                          title="Edit" aria-label="Edit category">
                          <i class="fa fa-pen" aria-hidden="true"></i>
                        </button>
                        <button type="button" class="ps-act-btn ps-act-btn--danger ps-delete-category-btn" data-id="<?= (int) $category['id']; ?>" title="Delete" aria-label="Delete category">
                          <i class="fa fa-trash" aria-hidden="true"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                  <tr class="ps-images-row" id="ps-images-row-<?= (int) $category['id']; ?>" style="display:none">
                    <td colspan="7">
                      <div class="ps-image-panel" data-category-id="<?= (int) $category['id']; ?>">
                        <div class="ps-image-grid" id="ps-image-grid-<?= (int) $category['id']; ?>">
                          <?php foreach ($images as $image): ?>
                            <div class="ps-image-thumb" data-image-id="<?= (int) $image['id']; ?>">
                              <img src="<?= base_url(ltrim((string) $image['image_path'], '/')); ?>" alt="">
                              <input type="text" class="form-control input-sm ps-caption-input" value="<?= esc($image['caption'] ?? '', 'attr'); ?>" placeholder="Caption">
                              <input type="number" class="form-control input-sm ps-sort-input" value="<?= (int) $image['sort_order']; ?>">
                              <button type="button" class="btn btn-xs btn-danger ps-delete-image-btn" data-id="<?= (int) $image['id']; ?>">
                                <i class="fa fa-trash" aria-hidden="true"></i> Delete
                              </button>
                            </div>
                          <?php endforeach; ?>
                        </div>
                        <button type="button" class="btn btn-sm btn-success ps-save-order-btn" data-category-id="<?= (int) $category['id']; ?>">
                          <i class="fa fa-sort" aria-hidden="true"></i> Save order
                        </button>
                        <hr>
                        <form class="ps-add-image-form" data-category-id="<?= (int) $category['id']; ?>" enctype="multipart/form-data">
                          <?= csrf_field(); ?>
                          <input type="file" name="image" class="form-control" accept="image/png,image/jpeg,image/gif,image/webp" required style="width:auto">
                          <input type="text" name="caption" class="form-control" placeholder="Caption (optional)" style="width:220px">
                          <button type="submit" class="btn btn-sm btn-primary">
                            <i class="fa fa-upload" aria-hidden="true"></i> Add Image
                          </button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="7">No categories yet.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endforeach; ?>

    <?php $integrations = $integrations ?? []; ?>
    <div class="box box-primary">
      <div class="box-header with-border ipb-box-toolbar">
        <div class="ipb-list-toolbar">
          <div class="ipb-list-toolbar-filters">
            <span class="ipb-filter-label"><i class="fa fa-plug" aria-hidden="true"></i> Landing Integrations</span>
          </div>
          <div class="ipb-list-toolbar-actions">
            <button type="button" class="btn btn-primary" id="psAddIntegrationBtn">
              <i class="fa fa-plus" aria-hidden="true"></i> Add Integration
            </button>
          </div>
        </div>
      </div>
      <div class="box-body table-responsive">
        <p class="text-muted" style="margin-top:0">
          Logos shown on the public landing <strong>Integrations</strong> section.
          <em>Core Rail</em> = large featured cards · <em>Also connects</em> = smaller logo grid.
        </p>
        <table class="table table-bordered table-striped">
          <thead>
            <tr>
              <th>#</th>
              <th>Logo</th>
              <th>Name</th>
              <th>Type</th>
              <th>Sort</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($integrations)): $psIntRow = 0; ?>
              <?php foreach ($integrations as $integration): $psIntRow++; ?>
                <?php
                  $logoPath = ltrim((string) ($integration['logo_path'] ?? ''), '/');
                  $logoUrl = $logoPath !== '' ? base_url($logoPath) : '';
                  $hasLogoFile = $logoPath !== '' && is_file(FCPATH . $logoPath);
                ?>
                <tr>
                  <td><?= $psIntRow; ?></td>
                  <td>
                    <?php if ($hasLogoFile): ?>
                      <img class="ps-int-logo" src="<?= esc($logoUrl, 'attr'); ?>" alt="">
                    <?php elseif (!empty($integration['icon_class'])): ?>
                      <span class="ps-int-logo-fallback" title="<?= esc($integration['icon_class'], 'attr'); ?>">
                        <i class="<?= esc($integration['icon_class'], 'attr'); ?>" aria-hidden="true"></i>
                      </span>
                    <?php else: ?>
                      <span class="text-muted">—</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <strong><?= esc($integration['name']); ?></strong>
                    <?php if (!empty($integration['description'])): ?>
                      <div class="text-muted" style="font-size:12px;max-width:360px"><?= esc(mb_strimwidth((string) $integration['description'], 0, 90, '…')); ?></div>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="ps-tier-pill <?= ($integration['tier'] ?? '') === 'core' ? 'is-core' : 'is-also'; ?>">
                      <?= ($integration['tier'] ?? '') === 'core' ? 'Core Rail' : 'Also connects'; ?>
                    </span>
                  </td>
                  <td><?= (int) ($integration['sort_order'] ?? 0); ?></td>
                  <td><span class="ps-status <?= esc($integration['status'] ?? 'inactive'); ?>"><?= esc(ucfirst((string) ($integration['status'] ?? 'inactive'))); ?></span></td>
                  <td>
                    <div class="ps-actions">
                      <button type="button" class="ps-act-btn ps-act-btn--edit ps-edit-integration-btn"
                        data-id="<?= (int) $integration['id']; ?>"
                        data-name="<?= esc($integration['name'], 'attr'); ?>"
                        data-description="<?= esc((string) ($integration['description'] ?? ''), 'attr'); ?>"
                        data-tier="<?= esc((string) ($integration['tier'] ?? 'also'), 'attr'); ?>"
                        data-icon-class="<?= esc((string) ($integration['icon_class'] ?? ''), 'attr'); ?>"
                        data-sort-order="<?= (int) ($integration['sort_order'] ?? 0); ?>"
                        data-status="<?= esc((string) ($integration['status'] ?? 'active'), 'attr'); ?>"
                        data-logo-url="<?= $hasLogoFile ? esc($logoUrl, 'attr') : ''; ?>"
                        title="Edit" aria-label="Edit integration">
                        <i class="fa fa-pen" aria-hidden="true"></i>
                      </button>
                      <button type="button" class="ps-act-btn ps-act-btn--danger ps-delete-integration-btn" data-id="<?= (int) $integration['id']; ?>" title="Delete" aria-label="Delete integration">
                        <i class="fa fa-trash" aria-hidden="true"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="7">No integrations yet. Click <strong>Add Integration</strong> or run <code>php spark migrate</code> to seed defaults.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Add Category Modal -->
    <div class="modal fade" id="psAddCategoryModal" tabindex="-1" role="dialog" aria-labelledby="psAddCategoryModalLabel">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
            <h4 class="modal-title" id="psAddCategoryModalLabel">Add Category</h4>
          </div>
          <div class="modal-body">
            <form id="psAddCategoryForm">
              <input type="hidden" name="target" id="psAddTarget">
              <div class="form-group">
                <label for="psAddName">Category Name</label>
                <input type="text" class="form-control" id="psAddName" name="name" required>
                <small class="text-danger" id="psAddName-error"></small>
              </div>
              <div class="form-group">
                <label for="psAddBullets">Bullets (one per line)</label>
                <textarea class="form-control" id="psAddBullets" name="bullets" rows="4" placeholder="Auto invoices, SMS reminders, and bKash/Nagad collection"></textarea>
                <small class="text-muted">One bullet per line. Shown as feature points under this category on the landing page.</small>
              </div>
              <div class="form-group">
                <label for="psAddSortOrder">Sort Order</label>
                <input type="number" class="form-control" id="psAddSortOrder" name="sort_order" value="0">
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary" id="psSaveAddCategoryBtn">Save Category</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Category Modal -->
    <div class="modal fade" id="psEditCategoryModal" tabindex="-1" role="dialog" aria-labelledby="psEditCategoryModalLabel">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
            <h4 class="modal-title" id="psEditCategoryModalLabel">Edit Category</h4>
          </div>
          <div class="modal-body">
            <form id="psEditCategoryForm">
              <input type="hidden" id="psEditId">
              <input type="hidden" name="target" id="psEditTarget">
              <div class="form-group">
                <label for="psEditName">Category Name</label>
                <input type="text" class="form-control" id="psEditName" name="name" required>
                <small class="text-danger" id="psEditName-error"></small>
              </div>
              <div class="form-group">
                <label for="psEditBullets">Bullets (one per line)</label>
                <textarea class="form-control" id="psEditBullets" name="bullets" rows="4"></textarea>
                <small class="text-muted">One bullet per line. Shown as feature points under this category on the landing page.</small>
              </div>
              <div class="form-group">
                <label for="psEditSortOrder">Sort Order</label>
                <input type="number" class="form-control" id="psEditSortOrder" name="sort_order">
              </div>
              <div class="form-group">
                <label for="psEditStatus">Status</label>
                <select class="form-control" id="psEditStatus" name="status">
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                </select>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary" id="psSaveEditCategoryBtn">Update Category</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Add Integration Modal -->
    <div class="modal fade" id="psAddIntegrationModal" tabindex="-1" role="dialog" aria-labelledby="psAddIntegrationModalLabel">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
            <h4 class="modal-title" id="psAddIntegrationModalLabel">Add Integration</h4>
          </div>
          <div class="modal-body">
            <form id="psAddIntegrationForm" enctype="multipart/form-data">
              <?= csrf_field(); ?>
              <div class="form-group">
                <label for="psIntAddName">Name</label>
                <input type="text" class="form-control" id="psIntAddName" name="name" required>
                <small class="text-danger" id="psIntAddName-error"></small>
              </div>
              <div class="form-group">
                <label for="psIntAddTier">Type</label>
                <select class="form-control" id="psIntAddTier" name="tier">
                  <option value="also">Also connects</option>
                  <option value="core">Core Rail</option>
                </select>
              </div>
              <div class="form-group">
                <label for="psIntAddDescription">Description <span class="text-muted">(Core Rail)</span></label>
                <textarea class="form-control" id="psIntAddDescription" name="description" rows="3" placeholder="Short benefit line shown on Core Rail cards"></textarea>
              </div>
              <div class="form-group">
                <label for="psIntAddLogo">Logo image</label>
                <input type="file" class="form-control" id="psIntAddLogo" name="logo" accept="image/png,image/jpeg,image/gif,image/webp,image/svg+xml,.svg">
                <small class="text-muted">PNG, JPG, GIF, WEBP, or SVG. Max 2MB.</small>
                <small class="text-danger" id="psIntAddLogo-error"></small>
              </div>
              <div class="form-group">
                <label for="psIntAddIcon">Icon class <span class="text-muted">(optional fallback)</span></label>
                <input type="text" class="form-control" id="psIntAddIcon" name="icon_class" placeholder="fas fa-sms">
                <small class="text-muted">Used when no logo is uploaded (Font Awesome class).</small>
              </div>
              <div class="form-group">
                <label for="psIntAddSort">Sort Order</label>
                <input type="number" class="form-control" id="psIntAddSort" name="sort_order" value="0">
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary" id="psSaveAddIntegrationBtn">Save Integration</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Integration Modal -->
    <div class="modal fade" id="psEditIntegrationModal" tabindex="-1" role="dialog" aria-labelledby="psEditIntegrationModalLabel">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
            <h4 class="modal-title" id="psEditIntegrationModalLabel">Edit Integration</h4>
          </div>
          <div class="modal-body">
            <form id="psEditIntegrationForm" enctype="multipart/form-data">
              <?= csrf_field(); ?>
              <input type="hidden" id="psIntEditId">
              <div class="form-group">
                <label for="psIntEditName">Name</label>
                <input type="text" class="form-control" id="psIntEditName" name="name" required>
                <small class="text-danger" id="psIntEditName-error"></small>
              </div>
              <div class="form-group">
                <label for="psIntEditTier">Type</label>
                <select class="form-control" id="psIntEditTier" name="tier">
                  <option value="also">Also connects</option>
                  <option value="core">Core Rail</option>
                </select>
              </div>
              <div class="form-group">
                <label for="psIntEditDescription">Description <span class="text-muted">(Core Rail)</span></label>
                <textarea class="form-control" id="psIntEditDescription" name="description" rows="3"></textarea>
              </div>
              <div class="form-group">
                <label>Current logo</label>
                <div id="psIntEditLogoPreviewWrap" style="display:none">
                  <img id="psIntEditLogoPreview" class="ps-int-preview" alt="">
                </div>
                <p id="psIntEditLogoNone" class="text-muted" style="margin:0">No logo on file — using icon fallback if set.</p>
              </div>
              <div class="form-group">
                <label for="psIntEditLogo">Replace logo</label>
                <input type="file" class="form-control" id="psIntEditLogo" name="logo" accept="image/png,image/jpeg,image/gif,image/webp,image/svg+xml,.svg">
                <small class="text-muted">Leave empty to keep the current logo.</small>
                <small class="text-danger" id="psIntEditLogo-error"></small>
              </div>
              <div class="form-group">
                <label for="psIntEditIcon">Icon class <span class="text-muted">(optional fallback)</span></label>
                <input type="text" class="form-control" id="psIntEditIcon" name="icon_class" placeholder="fas fa-sms">
              </div>
              <div class="form-group">
                <label for="psIntEditSort">Sort Order</label>
                <input type="number" class="form-control" id="psIntEditSort" name="sort_order" value="0">
              </div>
              <div class="form-group">
                <label for="psIntEditStatus">Status</label>
                <select class="form-control" id="psIntEditStatus" name="status">
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                </select>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary" id="psSaveEditIntegrationBtn">Update Integration</button>
          </div>
        </div>
      </div>
    </div>

  </section>
</div>
<?= $this->endSection('content'); ?>

<?= $this->section('script'); ?>
<script>
  (function () {
    const csrfHeader = '<?= csrf_header(); ?>';
    const csrfHash = '<?= csrf_hash(); ?>';
    // Parameterized routes: base path + '/' + id, matching how the sibling
    // Admin Packages screen builds e.g. '/admins/updatePackage/' + id.
    const updateCategoryBase = '<?= rtrim(base_url('product-showcase/update-category'), '/'); ?>/';
    const deleteCategoryBase = '<?= rtrim(base_url('product-showcase/delete-category'), '/'); ?>/';
    const storeImageBase = '<?= rtrim(base_url('product-showcase/store-image'), '/'); ?>/';
    const deleteImageBase = '<?= rtrim(base_url('product-showcase/delete-image'), '/'); ?>/';
    const updateIntegrationBase = '<?= rtrim(base_url('product-showcase/update-integration'), '/'); ?>/';
    const deleteIntegrationBase = '<?= rtrim(base_url('product-showcase/delete-integration'), '/'); ?>/';

    function showFeedback(type, message) {
      if (window.tata) {
        if (type === 'success') {
          tata.success('Product Showcase', message);
        } else {
          tata.error('Product Showcase', message);
        }
        return;
      }
      $('#ps-feedback').html('<div class="alert alert-' + (type === 'success' ? 'success' : 'danger') + '">' + message + '</div>');
    }

    function clearErrors($form) {
      $form.find('small.text-danger').text('');
    }

    // Only the "name" field has a dedicated <small class="text-danger"> slot
    // in these two forms; every other rule (target, sort_order) is reported
    // via the toast alone.
    function showValidationErrors($form, errors) {
      if (!errors || typeof errors !== 'object') return;
      if (errors.name) {
        $form.find('[id$="Name-error"]').text(errors.name);
      }
    }

    // ---- Add category ----
    $(document).on('click', '.ps-add-category-btn', function () {
      $('#psAddCategoryForm')[0].reset();
      clearErrors($('#psAddCategoryForm'));
      $('#psAddTarget').val($(this).data('target-type'));
      $('#psAddCategoryModal').modal('show');
    });

    $('#psSaveAddCategoryBtn').on('click', function () {
      var $btn = $(this).prop('disabled', true);
      var $form = $('#psAddCategoryForm');
      clearErrors($form);

      $.ajax({
        url: '<?= route_to('route.productShowcase.storeCategory'); ?>',
        type: 'POST',
        data: $form.serialize(),
        beforeSend: function (req) { req.setRequestHeader(csrfHeader, csrfHash); },
        success: function (res) {
          if (res.status === 'success') {
            showFeedback('success', (res.response && res.response.msg) || 'Category created.');
            $('#psAddCategoryModal').modal('hide');
            setTimeout(function () { location.reload(); }, 600);
            return;
          }
          if (res.status === 'validation-error') {
            showValidationErrors($form, res.response);
            var errMsg = res.response && typeof res.response === 'object' ? Object.values(res.response).join(' ') : 'Please fix the highlighted fields.';
            showFeedback('error', errMsg);
          } else {
            showFeedback('error', res.response || 'Failed to create category.');
          }
        },
        error: function (xhr) {
          var res = xhr.responseJSON;
          showFeedback('error', (res && res.response) || 'Request failed.');
        },
        complete: function () { $btn.prop('disabled', false); }
      });
    });

    // ---- Edit category ----
    $(document).on('click', '.ps-edit-category-btn', function () {
      var $btn = $(this);
      $('#psEditId').val($btn.data('id'));
      $('#psEditTarget').val($btn.data('target-type'));
      $('#psEditName').val($btn.data('name'));
      $('#psEditBullets').val($btn.data('bullets'));
      $('#psEditSortOrder').val($btn.data('sort-order'));
      $('#psEditStatus').val($btn.data('status'));
      clearErrors($('#psEditCategoryForm'));
      $('#psEditCategoryModal').modal('show');
    });

    $('#psSaveEditCategoryBtn').on('click', function () {
      var $btn = $(this).prop('disabled', true);
      var $form = $('#psEditCategoryForm');
      clearErrors($form);
      var id = $('#psEditId').val();

      $.ajax({
        url: updateCategoryBase + encodeURIComponent(id),
        type: 'POST',
        data: $form.serialize(),
        beforeSend: function (req) { req.setRequestHeader(csrfHeader, csrfHash); },
        success: function (res) {
          if (res.status === 'success') {
            showFeedback('success', (res.response && res.response.msg) || 'Category updated.');
            $('#psEditCategoryModal').modal('hide');
            setTimeout(function () { location.reload(); }, 600);
            return;
          }
          if (res.status === 'validation-error') {
            showValidationErrors($form, res.response);
            var errMsg = res.response && typeof res.response === 'object' ? Object.values(res.response).join(' ') : 'Please fix the highlighted fields.';
            showFeedback('error', errMsg);
          } else {
            showFeedback('error', res.response || 'Failed to update category.');
          }
        },
        error: function (xhr) {
          var res = xhr.responseJSON;
          showFeedback('error', (res && res.response) || 'Request failed.');
        },
        complete: function () { $btn.prop('disabled', false); }
      });
    });

    // ---- Delete category ----
    $(document).on('click', '.ps-delete-category-btn', function () {
      if (!confirm('Delete this category and all of its images? This cannot be undone.')) return;
      var id = $(this).data('id');

      $.ajax({
        url: deleteCategoryBase + encodeURIComponent(id),
        type: 'POST',
        beforeSend: function (req) { req.setRequestHeader(csrfHeader, csrfHash); },
        success: function (res) {
          if (res.status === 'success') {
            showFeedback('success', res.response || 'Category deleted.');
            setTimeout(function () { location.reload(); }, 600);
          } else {
            showFeedback('error', res.response || 'Failed to delete category.');
          }
        },
        error: function (xhr) {
          var res = xhr.responseJSON;
          showFeedback('error', (res && res.response) || 'Request failed.');
        }
      });
    });

    // ---- Toggle image panel ----
    $(document).on('click', '.ps-toggle-images-btn', function () {
      $('#ps-images-row-' + $(this).data('id')).toggle();
    });

    // ---- Delete image ----
    $(document).on('click', '.ps-delete-image-btn', function () {
      if (!confirm('Delete this image?')) return;
      var $thumb = $(this).closest('.ps-image-thumb');
      var id = $(this).data('id');

      $.ajax({
        url: deleteImageBase + encodeURIComponent(id),
        type: 'POST',
        beforeSend: function (req) { req.setRequestHeader(csrfHeader, csrfHash); },
        success: function (res) {
          if (res.status === 'success') {
            $thumb.remove();
            showFeedback('success', res.response || 'Image deleted.');
          } else {
            showFeedback('error', res.response || 'Failed to delete image.');
          }
        },
        error: function (xhr) {
          var res = xhr.responseJSON;
          showFeedback('error', (res && res.response) || 'Request failed.');
        }
      });
    });

    // ---- Save order ----
    $(document).on('click', '.ps-save-order-btn', function () {
      var $btn = $(this).prop('disabled', true);
      var categoryId = $(this).data('category-id');
      var order = {};

      $('#ps-image-grid-' + categoryId + ' .ps-image-thumb').each(function () {
        var imageId = $(this).data('image-id');
        var sortOrder = $(this).find('.ps-sort-input').val();
        order[imageId] = sortOrder;
      });

      $.ajax({
        url: '<?= route_to('route.productShowcase.reorderImages'); ?>',
        type: 'POST',
        data: { order: order },
        beforeSend: function (req) { req.setRequestHeader(csrfHeader, csrfHash); },
        success: function (res) {
          if (res.status === 'success') {
            showFeedback('success', res.response || 'Order saved.');
          } else {
            showFeedback('error', res.response || 'Failed to save order.');
          }
        },
        error: function (xhr) {
          var res = xhr.responseJSON;
          showFeedback('error', (res && res.response) || 'Request failed.');
        },
        complete: function () { $btn.prop('disabled', false); }
      });
    });

    // ---- Add image (appends thumbnail without a full reload) ----
    $(document).on('submit', '.ps-add-image-form', function (e) {
      e.preventDefault();
      var $form = $(this);
      var categoryId = $form.data('category-id');
      var $btn = $form.find('button[type="submit"]').prop('disabled', true);
      var fd = new FormData(this);

      $.ajax({
        url: storeImageBase + encodeURIComponent(categoryId),
        type: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        beforeSend: function (req) { req.setRequestHeader(csrfHeader, csrfHash); },
        success: function (res) {
          if (res.status === 'success') {
            var img = res.response.image;
            // Built with jQuery attr()/val() setters rather than HTML-string
            // concatenation — img.caption/img.url are untrusted (they round-trip
            // through the server, but nothing here HTML-escapes them), so string
            // concatenation would let a caption containing a `"` or `<` break out
            // and inject markup into this admin's own session.
            var $thumb = $('<div class="ps-image-thumb"></div>').attr('data-image-id', img.id);
            $('<img alt="">').attr('src', img.url).appendTo($thumb);
            $('<input type="text" class="form-control input-sm ps-caption-input" placeholder="Caption">')
              .val(img.caption || '').appendTo($thumb);
            $('<input type="number" class="form-control input-sm ps-sort-input">')
              .val(img.sort_order).appendTo($thumb);
            $('<button type="button" class="btn btn-xs btn-danger ps-delete-image-btn"><i class="fa fa-trash" aria-hidden="true"></i> Delete</button>')
              .attr('data-id', img.id).appendTo($thumb);
            $('#ps-image-grid-' + categoryId).append($thumb);
            $form[0].reset();
            showFeedback('success', res.response.msg || 'Image uploaded.');
          } else if (res.status === 'validation-error') {
            var msg = res.response ? Object.values(res.response).join(' ') : 'Please fix the highlighted fields.';
            showFeedback('error', msg);
          } else {
            showFeedback('error', res.response || 'Failed to upload image.');
          }
        },
        error: function (xhr) {
          var res = xhr.responseJSON;
          showFeedback('error', (res && res.response) || 'Request failed.');
        },
        complete: function () { $btn.prop('disabled', false); }
      });
    });

    // ---- Integrations ----
    function showIntValidationErrors($form, errors) {
      if (!errors || typeof errors !== 'object') return;
      if (errors.name) {
        $form.find('[id$="Name-error"]').text(errors.name);
      }
      if (errors.logo) {
        $form.find('[id$="Logo-error"]').text(errors.logo);
      }
    }

    $('#psAddIntegrationBtn').on('click', function () {
      $('#psAddIntegrationForm')[0].reset();
      clearErrors($('#psAddIntegrationForm'));
      $('#psAddIntegrationModal').modal('show');
    });

    $('#psSaveAddIntegrationBtn').on('click', function () {
      var $btn = $(this).prop('disabled', true);
      var $form = $('#psAddIntegrationForm');
      clearErrors($form);
      var fd = new FormData($form[0]);

      $.ajax({
        url: '<?= route_to('route.productShowcase.storeIntegration'); ?>',
        type: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        beforeSend: function (req) { req.setRequestHeader(csrfHeader, csrfHash); },
        success: function (res) {
          if (res.status === 'success') {
            showFeedback('success', (res.response && res.response.msg) || 'Integration created.');
            $('#psAddIntegrationModal').modal('hide');
            setTimeout(function () { location.reload(); }, 600);
            return;
          }
          if (res.status === 'validation-error') {
            showIntValidationErrors($form, res.response);
            var errMsg = res.response && typeof res.response === 'object' ? Object.values(res.response).join(' ') : 'Please fix the highlighted fields.';
            showFeedback('error', errMsg);
          } else {
            showFeedback('error', res.response || 'Failed to create integration.');
          }
        },
        error: function (xhr) {
          var res = xhr.responseJSON;
          if (res && res.status === 'validation-error') {
            showIntValidationErrors($form, res.response);
          }
          showFeedback('error', (res && (typeof res.response === 'string' ? res.response : Object.values(res.response || {}).join(' '))) || 'Request failed.');
        },
        complete: function () { $btn.prop('disabled', false); }
      });
    });

    $(document).on('click', '.ps-edit-integration-btn', function () {
      var $btn = $(this);
      $('#psIntEditId').val($btn.data('id'));
      $('#psIntEditName').val($btn.data('name'));
      $('#psIntEditDescription').val($btn.data('description'));
      $('#psIntEditTier').val($btn.data('tier'));
      $('#psIntEditIcon').val($btn.data('icon-class'));
      $('#psIntEditSort').val($btn.data('sort-order'));
      $('#psIntEditStatus').val($btn.data('status'));
      $('#psIntEditLogo').val('');
      clearErrors($('#psEditIntegrationForm'));

      var logoUrl = $btn.attr('data-logo-url') || '';
      if (logoUrl) {
        $('#psIntEditLogoPreview').attr('src', logoUrl);
        $('#psIntEditLogoPreviewWrap').show();
        $('#psIntEditLogoNone').hide();
      } else {
        $('#psIntEditLogoPreview').attr('src', '');
        $('#psIntEditLogoPreviewWrap').hide();
        $('#psIntEditLogoNone').show();
      }

      $('#psEditIntegrationModal').modal('show');
    });

    $('#psSaveEditIntegrationBtn').on('click', function () {
      var $btn = $(this).prop('disabled', true);
      var $form = $('#psEditIntegrationForm');
      clearErrors($form);
      var id = $('#psIntEditId').val();
      var fd = new FormData($form[0]);

      $.ajax({
        url: updateIntegrationBase + encodeURIComponent(id),
        type: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        beforeSend: function (req) { req.setRequestHeader(csrfHeader, csrfHash); },
        success: function (res) {
          if (res.status === 'success') {
            showFeedback('success', (res.response && res.response.msg) || 'Integration updated.');
            $('#psEditIntegrationModal').modal('hide');
            setTimeout(function () { location.reload(); }, 600);
            return;
          }
          if (res.status === 'validation-error') {
            showIntValidationErrors($form, res.response);
            var errMsg = res.response && typeof res.response === 'object' ? Object.values(res.response).join(' ') : 'Please fix the highlighted fields.';
            showFeedback('error', errMsg);
          } else {
            showFeedback('error', res.response || 'Failed to update integration.');
          }
        },
        error: function (xhr) {
          var res = xhr.responseJSON;
          if (res && res.status === 'validation-error') {
            showIntValidationErrors($form, res.response);
          }
          showFeedback('error', (res && (typeof res.response === 'string' ? res.response : Object.values(res.response || {}).join(' '))) || 'Request failed.');
        },
        complete: function () { $btn.prop('disabled', false); }
      });
    });

    $(document).on('click', '.ps-delete-integration-btn', function () {
      if (!confirm('Delete this integration from the landing page?')) return;
      var id = $(this).data('id');

      $.ajax({
        url: deleteIntegrationBase + encodeURIComponent(id),
        type: 'POST',
        beforeSend: function (req) { req.setRequestHeader(csrfHeader, csrfHash); },
        success: function (res) {
          if (res.status === 'success') {
            showFeedback('success', res.response || 'Integration deleted.');
            setTimeout(function () { location.reload(); }, 600);
          } else {
            showFeedback('error', res.response || 'Failed to delete integration.');
          }
        },
        error: function (xhr) {
          var res = xhr.responseJSON;
          showFeedback('error', (res && res.response) || 'Request failed.');
        }
      });
    });
  })();
</script>
<?= $this->endSection('script'); ?>
