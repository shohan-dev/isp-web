<?= $this->extend('layout/main-layout'); ?>

<?= $this->section('css'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/saas/bandwidth-pages.css?v=1'); ?>">
<?= $this->endSection('css'); ?>

<?= $this->section('content'); ?>
<div class="content-wrapper">
    <section class="content ipb-saas-list ipb-bw-page">

    <?= $this->include('components/page-header', [
      'title' => 'Bandwidth Items',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Bandwidth Buy'],
        ['label' => 'Items'],
      ],
    ]); ?>

<div class="box box-primary">
            <div class="box-header with-border ipb-box-toolbar">
        <div class="ipb-list-toolbar">
          <div class="ipb-list-toolbar-filters">
            <span class="ipb-filter-label"><i class="fa fa-sitemap" aria-hidden="true"></i> Item catalog</span>
          </div>
          <div class="ipb-list-toolbar-actions">
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addItemModal">
                        <i class="fa fa-plus" aria-hidden="true"></i> New Item
                    </button>
                    <?php if (userHasPermission('item_category', 'delete')): ?>
                        <button type="button" class="btn btn-danger delete-btn">
                            <i class="far fa-trash-can" aria-hidden="true"></i> Delete
                        </button>
                    <?php endif; ?>
          </div>
        </div>
      </div>
            <div class="box-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <caption class="sr-only">Bandwidth items</caption>
                        <thead>
                            <tr>
                                <?php if (userHasPermission('item_category', 'delete')): ?>
                                    <th scope="col" width="50"><input type="checkbox" class="form-check-input" id="select_all"></th>
                                <?php endif; ?>
                                <th scope="col">Category / Subcategory / Item Name</th>
                                <th scope="col">Unit Price</th>
                                <th scope="col">% Vat </th>
                                <th scope="col" width="150">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($parent_categories as $category): ?>
                                <!-- Parent Category -->
                                <tr data-id="<?= $category['id'] ?>" class="category-parent">
                                    <?php if (userHasPermission('item_category', 'delete')): ?>
                                        <td><input type="checkbox" class="input-check-selected" value="<?= $category['id'] ?>">
                                        </td>
                                    <?php endif; ?>
                                    <td>
                                        <span class="toggle-subcategories" data-id="<?= $category['id'] ?>"
                                            style="cursor: pointer; margin-right: 5px;">
                                            <i class="fa fa-plus-square "></i>
                                        </span>
                                        <strong><?= $category['item_category_name'] ?></strong>
                                    </td>
                                    <td></td>
                                    <td></td>
                                    <!-- <td>
                                        <button class="btn btn-xs btn-primary"><i class="fa fa-edit"></i> Edit</button>
                                        <button class="btn btn-xs btn-danger"><i class="fa fa-trash"></i> Delete</button>
                                    </td> -->
                                </tr>

                                <!-- Items directly under Category (category items) -->
                                <?php if (!empty($category['items'])): ?>
                                    <?php
                                    $catItems = json_decode($category['items'], true);
                                    if (json_last_error() === JSON_ERROR_NONE && is_array($catItems)):
                                        foreach ($catItems as $mindex => $catItem):
                                            if (isset($catItem['item_name']) && isset($catItem['unit']) && isset($catItem['vat'])):
                                                ?>
                                                <tr data-parent="<?= $category['id'] ?>" class="category-item" style="display:none;">
                                                    <?php if (userHasPermission('item_category', 'delete')): ?>
                                                        <td><input type="checkbox" class="input-check-selected"
                                                                value="<?= $category['id'] ?>-<?= htmlspecialchars($catItem['item_name']) ?>">
                                                        </td>
                                                    <?php endif; ?>

                                                    <td style="padding-left: 35px; margin-top: -20px; ">
                                                        <span
                                                            style=" margin-right: 5px; margin-left: 2px; border-left: 2px solid black; padding-top: 18px;">---</span>


                                                        <i class="fa fa-angle-right" style="margin-right: 5px; margin-left: -8px;"></i>
                                                        <?= htmlspecialchars($catItem['item_name']) ?>
                                                    </td>
                                                    <td><?= htmlspecialchars($catItem['unit']) ?></td>
                                                    <td><?= htmlspecialchars($catItem['vat']) ?></td>
                                                    <td>
                                                        <div class="ipb-row-actions">
                                                        <button type="button" class="ipb-row-btn tone-brand edit-item" title="Edit"
                                                            data-item='<?= htmlspecialchars(json_encode($catItem), ENT_QUOTES, 'UTF-8', true) ?>'
                                                            data-category-id="<?= $category['id'] ?>" data-item-index="<?= $mindex ?>"
                                                            data-item-type="category">
                                                            <i class="fa fa-edit" aria-hidden="true"></i><span class="sr-only">Edit</span>
                                                        </button>
                                                        <button type="button" class="ipb-row-btn tone-danger delete-item" title="Delete" data-item='<?= htmlspecialchars(json_encode([
                                                            'item_name' => $catItem['item_name'],
                                                            'unit' => $catItem['unit']
                                                        ]), ENT_QUOTES, 'UTF-8') ?>'
                                                            data-category-id="<?= $category['id'] ?>" data-item-index="<?= $mindex ?>"
                                                            data-item-type="category">
                                                            <i class="fa fa-trash" aria-hidden="true"></i><span class="sr-only">Delete</span>
                                                        </button>
                                                        </div>
                                                    </td>
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
                                    <?php foreach ($subcategory_items as $subcategoryindex => $subcategory): ?>
                                        <tr data-parent="<?= htmlspecialchars($category['id']) ?>"
                                            data-subcat-id="<?= htmlspecialchars($subcategory['subcategory_name']) ?>"
                                            class="subcategory" style="display:none;">
                                            <?php if (userHasPermission('item_category', 'delete')): ?>
                                                <td><input type="checkbox" class="input-check-selected"
                                                        value="<?= htmlspecialchars($subcategory['id']) ?>"></td>
                                            <?php endif; ?>
                                            <td style="padding-left: 25px;">
                                                <span class="toggle-items"
                                                    data-id="<?= htmlspecialchars($subcategory['subcategory_name']) ?>"
                                                    style="cursor: pointer; margin-right: 5px;">
                                                    <i class="fa fa-plus-square "></i>
                                                </span>
                                                <em><?= htmlspecialchars($subcategory['subcategory_name']) ?></em>
                                            </td>
                                            <td></td>
                                            <td></td>
                                            <!-- <td>
                                                <button class="btn btn-xs btn-primary"><i class="fa fa-edit"></i> Edit</button>
                                                <button class="btn btn-xs btn-danger"><i class="fa fa-trash"></i> Delete</button>
                                            </td> -->
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
                                            <?php foreach ($subItems as $index => $subItem): ?>
                                                <?php if (isset($subItem['item_name'], $subItem['unit'], $subItem['vat'])): ?>
                                                    <tr data-subcat-parent="<?= htmlspecialchars($subcategory['subcategory_name']) ?>"
                                                        class="subcategory-item" style="display:none;">
                                                        <?php if (userHasPermission('item_category', 'delete')): ?>
                                                            <td><input type="checkbox" class="input-check-selected"
                                                                    value="<?= htmlspecialchars($subcategory['subcategory_name']) ?>-<?= htmlspecialchars($subItem['item_name']) ?>">
                                                            </td>

                                                        <?php endif; ?>
                                                        <td style="padding-left: 53px; margin-top: -20px; ">
                                                            <span
                                                                style=" margin-right: 5px; margin-left: 2px; border-left: 2px solid black; padding-top: 18px;">---</span>


                                                            <i class="fa fa-angle-right" style="margin-right: 5px; margin-left: -8px;"></i>
                                                            <?= htmlspecialchars($subItem['item_name']) ?>
                                                        </td>


                                                        <td><?= htmlspecialchars($subItem['unit']) ?></td>
                                                        <td><?= htmlspecialchars($subItem['vat']) ?></td>
                                                        <td>
                                                            <div class="ipb-row-actions">
                                                            <button type="button" class="ipb-row-btn tone-brand edit-item" title="Edit"
                                                                data-item='<?= htmlspecialchars(json_encode($subItem), ENT_QUOTES, 'UTF-8', true) ?>'
                                                                data-category-id="<?= $category['id'] ?>"
                                                                data-subcategory-name="<?= htmlspecialchars($subcategory['subcategory_name']) ?>"
                                                                data-item-index="<?= $index ?>"
                                                                data-subcategory-index="<?= $subcategoryindex ?>" data-item-type="subcategory">
                                                                <i class="fa fa-edit" aria-hidden="true"></i><span class="sr-only">Edit</span>
                                                            </button>
                                                            <button type="button" class="ipb-row-btn tone-danger delete-item" title="Delete" data-item='<?= htmlspecialchars(json_encode([
                                                                'item_name' => $subItem['item_name'],
                                                                'unit' => $subItem['unit']
                                                            ]), ENT_QUOTES, 'UTF-8') ?>' data-category-id="<?= $category['id'] ?>"
                                                                data-subcategory-name="<?= htmlspecialchars($subcategory['subcategory_name']) ?>"
                                                                data-item-index="<?= $index ?>"
                                                                data-subcategory-index="<?= $subcategoryindex ?>" data-item-type="subcategory">
                                                                <i class="fa fa-trash" aria-hidden="true"></i><span class="sr-only">Delete</span>
                                                            </button>
                                                            </div>
                                                        </td>
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

        </div>
    </section>

    <!-- Add Category Modal -->
    <div class="modal fade" id="addItemModal" tabindex="-1" role="dialog" aria-labelledby="addItemModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" id="addItemModalLabel">Add Item</h4>
                </div>
                <form id="addItemForm" action="<?= route_to('bandwidth.item_store'); ?>" method="POST">
                    <?= csrf_field() ?>
                    <div class="modal-body">
                        <!-- <input type="hidden" id="item_index" name="item_index">
                        <input type="hidden" id="subcategoryindex" name="subcategoryindex"> -->

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="item_name">ITEM NAME *</label>
                                    <input type="text" class="form-control" id="item_name" name="item_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="unit">UNIT</label>
                                    <input type="text" class="form-control" id="unit" name="unit">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>CATEGORY *</label>
                                    <select class="form-control" id="category" name="category" required>
                                        <option value="">Select Category</option>
                                        <?php foreach ($parent_categories as $category): ?>
                                            <option value="<?= $category['id']; ?>"><?= $category['item_category_name']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>SUBCATEGORY</label>
                                    <select class="form-control" id="subcategory" name="subcategory" disabled>
                                        <option value="">Select Subcategory</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="status">STATUS</label>
                                    <select class="form-control" id="status" name="status">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="vat">VAT(%)</label>
                                    <input type="number" class="form-control" id="vat" name="vat" step="1">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="area">AREA</label>
                                    <input type="text" class="form-control" id="area" name="area">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="income_account">INCOME ACCOUNT</label>
                                    <input type="text" class="form-control" id="income_account" name="income_account"
                                        placeholder="Enter income account">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="expense_account">EXPENSE ACCOUNT</label>
                                    <input type="text" class="form-control" id="expense_account" name="expense_account"
                                        placeholder="Enter expense account">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description">DESCRIPTION (OPTIONAL)</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
                        <button type="reset" class="btn btn-default">Clear</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection(); ?>

<?= $this->section('script'); ?>
<script>
    $(document).on('click', '.edit-item', function () {
        try {
            // Clear any existing hidden fields and reset form action
            $('#addItemForm').find('[name="item_index"], [name="subcategory_index"], [name="subcategory_name"]').remove();
            $('#addItemForm').attr('action', '<?= route_to("bandwidth.item_update") ?>');

            // Get all data attributes
            const itemData = JSON.parse($(this).attr('data-item'));
            const categoryId = $(this).data('category-id');
            const itemType = $(this).data('item-type');

            // Get indexes - properly handle all cases including 0
            let itemIndex = $(this).data('item-index');
            if (typeof itemIndex === 'undefined') itemIndex = '';

            let subcategoryIndex = $(this).data('subcategory-index');
            if (typeof subcategoryIndex === 'undefined') subcategoryIndex = '';

            const subcategoryName = $(this).data('subcategory-name') || '';

            // Debug output
            // console.log('--- EDIT ITEM DEBUG ---');
            // console.log('Raw Data Attributes:', {
            //     'data-item-index': $(this).attr('data-item-index'),
            //     'data-subcategory-index': $(this).attr('data-subcategory-index'),
            //     'data-subcategory-name': $(this).attr('data-subcategory-name')
            // });
            console.log('Processed Values:', {
                itemIndex,
                subcategoryIndex,
                subcategoryName,
                itemType
            });

            // Add hidden fields to form
            if (itemIndex !== '') {
                $('#addItemForm').append(`<input type="hidden" name="item_index" value="${itemIndex}">`);
            }
            if (subcategoryIndex !== '') {
                $('#addItemForm').append(`<input type="hidden" name="subcategory_index" value="${subcategoryIndex}">`);
            }
            if (subcategoryName) {
                $('#addItemForm').append(`<input type="hidden" name="subcategory_name" value="${subcategoryName}">`);
            }

            // Populate form fields
            $('#addItemModalLabel').text('Edit Item');
            $('#item_name').val(itemData.item_name || '');
            $('#unit').val(itemData.unit || '');
            $('#vat').val(itemData.vat || '');
            $('#status').val(itemData.status || 'active');
            $('#income_account').val(itemData.income_account || '');
            $('#expense_account').val(itemData.expense_account || '');
            $('#description').val(itemData.description || '');
            $('#area').val(itemData.area || '');

            // Set category and trigger subcategory load
            $('#category').val(categoryId).trigger('change');

            // After a short delay, set the subcategory if it exists
            if (itemData.subcategory_id) {
                setTimeout(() => {
                    $('#subcategory').val(itemData.subcategory_id);
                }, 300);
            }
            

            // Show the modal
            $('#addItemModal').modal('show');

        } catch (e) {
            console.error('Edit Item Error:', e);
            tata.error("Couldn't load item", 'Failed to load item data for editing');
        }
    });

    // Reset handler for modal
    $('#addItemModal').on('hidden.bs.modal', function () {
        $('#addItemForm')[0].reset();
        $('#addItemForm').attr('action', '<?= route_to("bandwidth.item_store"); ?>');
        $('#addItemForm').find('[name="item_index"], [name="subcategory_index"], [name="subcategory_name"]').remove();
        $('#addItemModalLabel').text('Add Item');
        $('#subcategory').html('<option value="">Select Subcategory</option>').prop('disabled', true);
    });

    $(document).ready(function () {
        // Toggle subcategories
        $('.toggle-subcategories').click(function () {
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

        // Toggle items of subcategory
        $('.toggle-items').click(function () {
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
        $('#select_all').on('change', function () {
            $('.input-check-selected').prop('checked', this.checked);
        });


        // Remove item row
        $(document).on('click', '.remove-item', function () {
            $(this).closest('.item-row').remove();
        });

        $(document).on('click', '.delete-item', function() {
    const btn = $(this);
    const itemData = JSON.parse(btn.attr('data-item'));
    const categoryId = btn.data('category-id');
    const itemIndex = btn.data('item-index');
    const subcategoryIndex = btn.data('subcategory-index');
    const subcategoryName = btn.data('subcategory-name');
    const itemType = btn.data('item-type');

    swal({
        title: "Confirm Deletion",
        text: `Delete ${itemData.item_name} (${itemData.unit})?`,
        icon: "warning",
        buttons: ["Cancel", "Delete"],
        dangerMode: true,
    }).then((willDelete) => {
        if (willDelete) {
            btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');
            
            $.ajax({
                url: '<?= route_to("bandwidth.item_delete") ?>',
                type: 'POST',
                data: {
                    category_id: categoryId,
                    item_index: itemIndex,
                    subcategory_index: subcategoryIndex,
                    subcategory_name: subcategoryName,
                    item_type: itemType
                },
                headers: {
                    '<?= csrf_header() ?>': '<?= csrf_hash() ?>'
                },
                success: function(response) {
                    if (response.status === 'success') {
                        tata.success('Item deleted', response.message);
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        tata.error("Couldn't delete item", response.message);
                        btn.prop('disabled', false).html('<i class="fa fa-trash"></i>');
                    }
                },
                error: function(xhr) {
                    tata.error("Couldn't delete item", xhr.responseJSON?.message || 'Deletion failed');
                    btn.prop('disabled', false).html('<i class="fa fa-trash"></i>');
                }
            });
        }
    });
});
        // Fetch subcategories when category changes
        $('#category').change(function () {
            const categoryId = $(this).val();
            const subcategorySelect = $('#subcategory');

            if (categoryId) {
                $.ajax({
                    url: '<?= route_to("bandwidth.getSubcategories"); ?>',
                    type: 'GET',
                    data: { category_id: categoryId },
                    headers: {
                        '<?= csrf_header() ?>': '<?= csrf_hash() ?>',
                    },
                    beforeSend: function () {
                        subcategorySelect.prop('disabled', true);
                        subcategorySelect.html('<option value="">Loading...</option>');
                    },
                    success: function (response) {
                        if (response.status === 'success' && response.subcategories.length > 0) {
                            let options = '<option value="">Select Subcategory</option>';
                            response.subcategories.forEach(function (subcategory) {
                                options += `<option value="${subcategory.subcategory_name}">${subcategory.subcategory_name}</option>`;
                            });
                            subcategorySelect.html(options);
                            subcategorySelect.prop('disabled', false);
                        } else {
                            subcategorySelect.html('<option value="">No subcategories found</option>');
                            subcategorySelect.prop('disabled', false);
                        }
                    },
                    error: function (xhr) {
                        subcategorySelect.html('<option value="">Error loading subcategories</option>');
                        subcategorySelect.prop('disabled', false);
                        tata.error("Couldn't load subcategories", xhr.responseJSON?.message || 'Failed to load subcategories');
                    }
                });
            } else {
                subcategorySelect.html('<option value="">Select Subcategory</option>');
                subcategorySelect.prop('disabled', true);
            }
        });
        // Handle form submission
        $('#addItemForm').submit(function (e) {
            e.preventDefault();
            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: $(this).serialize(),
                headers: {
                    '<?= csrf_header() ?>': '<?= csrf_hash() ?>',
                },
                success: function (response) {
                    $('#addItemModal').modal('hide');
                    if (response.status === 'success') {
                        tata.success('Item saved', response.message);
                        location.reload();
                    } else {
                        tata.error("Couldn't save item", response.message);
                    }
                },
                error: function (xhr) {
                    tata.error("Couldn't save item", xhr.responseJSON?.message || 'An error occurred');
                }
            });
        });
    });
</script>
<?= $this->endSection(); ?>