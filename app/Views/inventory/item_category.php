<?= $this->extend('layout/main-layout'); ?>
<?= $this->section('content'); ?>
<div class="content-wrapper">
    <section class="content ipb-saas-list">
        
    <?= $this->include('components/page-header', [
      'title' => 'Inventory Item Category',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Inventory Item Category'],
      ],
    ]); ?>

<div class="box box-primary">
            <div class="box-header with-border ipb-box-toolbar">
        <div class="ipb-list-toolbar">
          <div class="ipb-list-toolbar-filters">
            <span class="ipb-filter-label"><i class="fa fa-list" aria-hidden="true"></i> Records</span>
          </div>
          <div class="ipb-list-toolbar-actions">
<!-- <button class="btn btn-success">
                        <i class="fa fa-download"></i> Export
                    </button> -->
                    <?php if (userHasPermission('inventory_purchess', 'create')) : ?>
                        <button class="btn btn-primary" data-toggle="modal" data-target="#addCategoryModal">
                            <i class="fa fa-plus"></i> New Category
                        </button>
                    <?php endif; ?>
          </div>
        </div>
      </div>
            <div class="box-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <caption class="sr-only">Inventory item categories</caption>
                        <thead>
                            <tr>
                                <?php if (userHasPermission('inventory_purchess', 'delete')): ?>
                                    <th scope="col" width="50"><input type="checkbox" class="form-check-input" id="select_all"></th>
                                <?php endif; ?>
                                <th scope="col">Category / Subcategory / Item Name</th>
                                <th scope="col">Price</th>
                                <th scope="col">Area</th>
                                <th scope="col" width="150">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($parent_categories as $category): ?>
                                <!-- Parent Category -->
                                <tr data-id="<?= $category['id'] ?>" class="category-parent">
                                    <?php if (userHasPermission('inventory_purchess', 'delete')): ?>
                                        <td><input type="checkbox" class="input-check-selected" value="<?= $category['id'] ?>">
                                        </td>
                                    <?php endif; ?>
                                    <td>
                                        <span class="toggle-subcategories" data-id="<?= $category['id'] ?>"
                                            style="cursor: pointer; margin-right: 5px;">
                                            <i class="fa fa-plus-square"></i>
                                        </span>
                                        <strong><?= $category['item_category_name'] ?></strong>
                                    </td>
                                    <td></td>
                                    <td></td>
                                    <td>

                                        <?php if (userHasPermission('inventory_purchess', 'update')) : ?>
                                            <button class="btn btn-xs btn-primary edit-item" data-type="category"
                                                data-id="<?= $category['id'] ?>"
                                                data-name="<?= htmlspecialchars($category['item_category_name']) ?>"
                                                data-parent="<?= $category['sub_category_of'] ?? '' ?>"
                                                data-status="<?= $category['item_category_status'] ?>"
                                                data-description="<?= htmlspecialchars($category['short_description'] ?? '') ?>">
                                                <i class="fa fa-edit"></i> Edit
                                            </button>
                                        <?php endif; ?>
                                        <?php if (userHasPermission('inventory_purchess', 'delete')) : ?>
                                            <button class="btn btn-xs btn-danger delete-item" data-id="<?= $category['id'] ?>"
                                                data-type="category">
                                                <i class="fa fa-trash"></i> Delete
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>

                                <!-- Items directly under Category (category items) -->
                                <?php if (!empty($category['items'])): ?>
                                    <?php
                                    $catItems = json_decode($category['items'], true);
                                    if (json_last_error() === JSON_ERROR_NONE && is_array($catItems)):
                                        foreach ($catItems as $catItem):
                                            if (isset($catItem['item_name']) && isset($catItem['unit']) && isset($catItem['vat'])):
                                    ?>
                                                <tr data-parent="<?= $category['id'] ?>" class="category-item" style="display:none;">
                                                   <td></td>

                                                    <td style="padding-left: 35px; margin-top: -20px; ">
                                                        <span
                                                            style=" margin-right: 5px; margin-left: 2px; border-left: 2px solid black; padding-top: 18px;">---</span>


                                                        <i class="fa fa-angle-right" style="margin-right: 5px; margin-left: -8px;"></i>
                                                        <?= htmlspecialchars($catItem['item_name']) ?>
                                                    </td>
                                                    <td><?= htmlspecialchars($catItem['unit']) ?></td>
                                                    <td><?= htmlspecialchars($catItem['vat']) ?></td>
                                                    <!-- <td>
                                                        <button class="btn btn-xs btn-primary edit-item">
                            <i class="fa fa-edit"></i> Edit
                        </button>
                                                        <button class="btn btn-xs btn-danger"><i class="fa fa-trash"></i> Delete</button>
                                                    </td> -->
                                                </tr>
                                    <?php
                                            endif;
                                        endforeach;
                                    endif;
                                    ?>
                                <?php endif; ?>

                                <!-- Subcategories -->
                                <!-- Subcategories -->
                                <?php
                                $subcategory_items = [];
                                if (!empty($category['subcategory_items'])) {
                                    $subcategory_items = json_decode($category['subcategory_items'], true);
                                }
                                ?>

                                <?php if (!empty($subcategory_items) && is_array($subcategory_items)): ?>
                                    <?php foreach ($subcategory_items as $index => $subcategory): ?>
                                        <tr data-parent="<?= htmlspecialchars($category['id']) ?>"
                                            data-subcat-id="<?= htmlspecialchars($subcategory['subcategory_name']) ?>"
                                            class="subcategory" style="display:none;">
                                            <td></td>
                                            <td style="padding-left: 25px;">
                                                <span class="toggle-items"
                                                    data-id="<?= htmlspecialchars($subcategory['subcategory_name']) ?>"
                                                    style="cursor: pointer; margin-right: 5px;">
                                                    <i class="fa fa-plus-square"></i>
                                                </span>
                                                <em><?= htmlspecialchars($subcategory['subcategory_name']) ?></em>
                                            </td>
                                            <td></td>
                                            <td></td>
                                            <td>
                                                <?php if (userHasPermission('inventory_purchess', 'update')) : ?>

                                                    <button class="btn btn-xs btn-primary edit-item" data-type="subcategory"
                                                        data-category-id="<?= $category['id'] ?>" data-subcategory-index="<?= $index ?>"
                                                        data-name="<?= htmlspecialchars($subcategory['subcategory_name']) ?>"
                                                        data-status="<?= $subcategory['item_category_status'] ?? 'active' ?>"
                                                        data-description="<?= htmlspecialchars($subcategory['short_description'] ?? '') ?>">
                                                        <i class="fa fa-edit"></i> Edit
                                                    </button>
                                                <?php endif; ?>
                                                <?php if (userHasPermission('inventory_purchess', 'delete')) : ?>

                                                    <button class="btn btn-xs btn-danger delete-item"
                                                        data-category-id="<?= $category['id'] ?>" data-subcategory-index="<?= $index ?>"
                                                        data-type="subcategory">
                                                        <i class="fa fa-trash"></i> Delete
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>

                                        <!-- Items under Subcategory -->
                                        <?php
                                        $subItems = [];
                                        if (!empty($subcategory['items'])) {
                                            if (is_string($subcategory['items'])) {
                                                $subItems = json_decode($subcategory['items'], true);
                                            } elseif (is_array($subcategory['items'])) {
                                                $subItems = $subcategory['items'];
                                            }
                                        }
                                        ?>

                                        <?php if (!empty($subItems) && is_array($subItems)): ?>
                                            <?php foreach ($subItems as $subItem): ?>
                                                <?php if (isset($subItem['item_name'], $subItem['unit'], $subItem['vat'])): ?>
                                                    <tr data-subcat-parent="<?= htmlspecialchars($subcategory['subcategory_name']) ?>"
                                                        class="subcategory-item" style="display:none;">
                                                        <td></td>
                                                        <td style="padding-left: 53px; margin-top: -20px; ">
                                                            <span
                                                                style=" margin-right: 5px; margin-left: 2px; border-left: 2px solid black; padding-top: 18px;">---</span>


                                                            <i class="fa fa-angle-right" style="margin-right: 5px; margin-left: -8px;"></i>
                                                            <?= htmlspecialchars($subItem['item_name']) ?>
                                                        </td>


                                                        <td><?= htmlspecialchars($subItem['unit']) ?></td>
                                                        <td><?= htmlspecialchars($subItem['vat']) ?></td>
                                                        <!-- <td>
                                                            <button class="btn btn-xs btn-primary"><i class="fa fa-edit"></i> Edit</button>
                                                            <button class="btn btn-xs btn-danger"><i class="fa fa-trash"></i> Delete</button>
                                                        </td> -->
                                                    </tr>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        <?php endif; ?>

                                    <?php endforeach; ?>
                                <?php endif; ?>

                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="box-footer">
                <div class="pull-right">
                    <?php if (userHasPermission('item_category', 'delete')): ?>
                        <button class="btn btn-danger delete-btn">
                            <i class="far fa-trash-can"></i> Delete Selected
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" role="dialog" aria-labelledby="addCategoryModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="addCategoryModalLabel">Add New Category</h4>
            </div>
            <form id="addCategoryForm" action="<?= route_to('inventory_item_category.store'); ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="item_category_name">Category Name *</label>
                        <input type="text" class="form-control" id="item_category_name" name="item_category_name"
                            required>
                    </div>

                    <!-- In your modal form -->
                    <div class="form-group">
                        <label for="sub_category_of">Subcategory Of</label>
                        <select class="form-control" id="sub_category_of" name="sub_category_of">
                            <option value="">-- Select Parent Category --</option>
                            <?php foreach ($parent_categories as $category): ?>
                                <!-- Exclude current category from parent options -->
                                <?php if (!isset($currentCategory) || $category['id'] != $currentCategory['id']): ?>
                                    <option value="<?= $category['id'] ?>"><?= $category['item_category_name'] ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>

                    </div>

                    <div class="form-group">
                        <label for="item_category_status">Status</label>
                        <select class="form-control" id="item_category_status" name="item_category_status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="short_description">Short Description</label>
                        <textarea class="form-control" id="short_description" name="short_description"
                            rows="3"></textarea>
                    </div>

                    <!-- Items Section -->
                    <!-- <div class="form-group">
                        <label>Items</label>
                        <div id="items-container">
                            <div class="item-row row" style="margin-bottom: 10px;">
                                <div class="col-md-4">
                                    <input type="text" class="form-control" name="items[0][name]" placeholder="Name"
                                        required>
                                </div>
                                <div class="col-md-3">
                                    <input type="text" class="form-control" name="items[0][price]" placeholder="Price"
                                        required>
                                </div>
                                <div class="col-md-3">
                                    <input type="text" class="form-control" name="items[0][area]" placeholder="Area"
                                        required>
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-danger btn-xs remove-item"><i
                                            class="fa fa-times"></i></button>
                                </div>
                            </div>
                        </div>
                        <button type="button" id="add-item" class="btn btn-success btn-xs"><i class="fa fa-plus"></i>
                            Add Item</button>
                    </div> -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection(); ?>

<?= $this->section('script'); ?>
<style>
    .toggle-subcategories i,
    .toggle-items i {
        font-size: 1.6em;
        vertical-align: middle;
        margin-right: 8px;
    }

    /* For the angle icons */
    .fa-angle-right {
        font-size: 1.3em;
        vertical-align: middle;
    }
</style>
<script>
    $(document).ready(function() {
        // Toggle subcategories
        $('.toggle-subcategories').click(function() {
            const catId = $(this).data('id');
            const icon = $(this).find('i');
            const isExpanded = icon.hasClass('fa-minus-square');

            if (isExpanded) {
                icon.removeClass('fa-minus-square').addClass('fa-plus-square');
                // Hide subcategories & category items
                $(`tr.subcategory[data-parent="${catId}"]`).hide();
                $(`tr.category-item[data-parent="${catId}"]`).hide();
                // Also hide subcategory items
                $(`tr.subcategory-item`).hide();
                // Reset subcategory toggles icon to plus
                $(`tr.subcategory[data-parent="${catId}"] .toggle-items i`).removeClass('fa-minus-square').addClass('fa-plus-square');
            } else {
                icon.removeClass('fa-plus-square').addClass('fa-minus-square');
                $(`tr.subcategory[data-parent="${catId}"]`).show();
                $(`tr.category-item[data-parent="${catId}"]`).show();
            }
        });
        // Edit subcategory handler
        // Edit item handler (works for both categories and subcategories)
        $(document).on('click', '.edit-item', function() {
            const type = $(this).data('type');
            const name = $(this).data('name');
            const status = $(this).data('status');
            const description = $(this).data('description');

            // Set modal title
            $('#addCategoryModalLabel').text(type === 'category' ? 'Edit Category' : 'Edit Subcategory');

            // Populate form fields
            $('#item_category_name').val(name);
            $('#item_category_status').val(status);
            $('#short_description').val(description);

            // Handle parent category dropdown
            if (type === 'category') {
                const parentId = $(this).data('parent');
                $('#sub_category_of').val(parentId || '');
                $('#sub_category_of').prop('disabled', false);
            } else {
                const categoryId = $(this).data('category-id');
                $('#sub_category_of').val(categoryId);
                $('#sub_category_of').prop('disabled', true); // Disable for subcategories
            }

            // Set form action and hidden fields
            $('#addCategoryForm').attr('action', '<?= route_to("inventory.catagory_update") ?>')
                .find('input[name="_method"], input[name="type"], input[name="id"], input[name="category_id"], input[name="subcategory_index"]').remove();

            if (type === 'category') {
                $('#addCategoryForm')
                    .append('<input type="hidden" name="type" value="category">')
                    .append('<input type="hidden" name="id" value="' + $(this).data('id') + '">');
            } else {
                $('#addCategoryForm')
                    .append('<input type="hidden" name="type" value="subcategory">')
                    .append('<input type="hidden" name="category_id" value="' + $(this).data('category-id') + '">')
                    .append('<input type="hidden" name="subcategory_index" value="' + $(this).data('subcategory-index') + '">');
            }

            $('#addCategoryForm').append('<input type="hidden" name="_method" value="PUT">');

            // Show the modal
            $('#addCategoryModal').modal('show');
        });

        // Reset modal when closed
        $('#addCategoryModal').on('hidden.bs.modal', function() {
            $('#addCategoryForm')[0].reset();
            $('#addCategoryForm').attr('action', '<?= route_to("inventory_item_category.store") ?>');
            $('#addCategoryForm').find('input[name="_method"], input[name="type"], input[name="id"], input[name="category_id"], input[name="subcategory_index"]').remove();
            $('#sub_category_of').prop('disabled', false);
            $('#addCategoryModalLabel').text('Add New Category');
        });



        // Toggle items of subcategory
        $('.toggle-items').click(function() {
            const subcatId = $(this).data('id');
            const icon = $(this).find('i');
            const isExpanded = icon.hasClass('fa-minus-square');

            if (isExpanded) {
                icon.removeClass('fa-minus-square').addClass('fa-plus-square');
                $(`tr.subcategory-item[data-subcat-parent="${subcatId}"]`).hide();
            } else {
                icon.removeClass('fa-plus-square').addClass('fa-minus-square');
                $(`tr.subcategory-item[data-subcat-parent="${subcatId}"]`).show();
            }
        });

        // Select all checkbox logic (optional, if implemented)
        $('#select_all').on('change', function() {
            $('.input-check-selected').prop('checked', this.checked);
        });

        // Add item row
        let itemCount = 1;
        $('#add-item').click(function() {
            const newRow = `
                <div class="item-row row" style="margin-bottom: 10px;">
                    <div class="col-md-4">
                        <input type="text" class="form-control" name="items[${itemCount}][name]" placeholder="Name" required>
                    </div>
                    <div class="col-md-3">
                        <input type="text" class="form-control" name="items[${itemCount}][price]" placeholder="Price" required>
                    </div>
                    <div class="col-md-3">
                        <input type="text" class="form-control" name="items[${itemCount}][area]" placeholder="Area" required>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger btn-xs remove-item"><i class="fa fa-times"></i></button>
                    </div>
                </div>
            `;
            $('#items-container').append(newRow);
            itemCount++;
        });

        // Remove item row
        $(document).on('click', '.remove-item', function() {
            $(this).closest('.item-row').remove();
        });



        // Handle individual delete buttons
        $(document).on('click', '.delete-item', function() {
            const type = $(this).data('type');
            const data = {
                items: [{
                    id: type === 'category' ? $(this).data('id') : null,
                    categoryId: type === 'subcategory' ? $(this).data('category-id') : null,
                    subcategoryIndex: type === 'subcategory' ? $(this).data('subcategory-index') : null,
                    isSubcategory: type === 'subcategory'
                }]
            };

            swal({
                title: "Confirmation",
                text: "Are you sure you want to delete this " + type + "?",
                dangerMode: true,
                icon: 'warning',
                buttons: ["No", {
                    text: "Yes",
                    closeModal: false,
                }],
            }).then((willDelete) => {
                if (willDelete) {
                    $.ajax({
                        url: '<?= route_to("inventory_item_category.delete"); ?>',
                        type: 'POST',
                        data: data,
                        headers: {
                            '<?= csrf_header() ?>': '<?= csrf_hash() ?>',
                        },
                        success: function(response) {
                            swal.close();
                            tata.success('Category deleted', response.message);
                            location.reload();
                        },
                        error: function(xhr) {
                            swal.close();
                            tata.error("Couldn't delete category", xhr.responseJSON?.message || 'An error occurred');
                        }
                    });
                }
            });
        });

        // Handle form submission
        $('#addCategoryForm').submit(function(e) {
            e.preventDefault();
            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: $(this).serialize(),
                headers: {
                    '<?= csrf_header() ?>': '<?= csrf_hash() ?>',
                },
                success: function(response) {
                    $('#addCategoryModal').modal('hide');
                    if (response.status === 'success') {
                        tata.success('Category saved', response.message);
                        location.reload();
                    } else {
                        tata.error("Couldn't save category", response.message);
                    }
                },
                error: function(xhr) {
                    tata.error("Couldn't save category", xhr.responseJSON?.message || 'An error occurred');
                }
            });
        });
    });
</script>
<?= $this->endSection(); ?>