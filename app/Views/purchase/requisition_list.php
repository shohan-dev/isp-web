<?= $this->extend('layout/main-layout'); ?>
<?= $this->section('content'); ?>
<div class="content-wrapper">
    <section class="content ipb-saas-list">
        
    <?= $this->include('components/page-header', [
      'title' => 'Requisitions',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Requisitions'],
      ],
    ]); ?>

<div class="box box-primary">
            <div class="box-header with-border ipb-box-toolbar">
        <?php
          ob_start();
        ?>
<button id="addRequisitionBtn" class="btn btn-primary">
                        <i class="fa fa-plus"></i> Requisition
                    </button>
        <?php
          $requisitionActionsHtml = ob_get_clean();
        ?>
        <?= view('components/list-toolbar', [
          'filters' => [],
          'actionsHtml' => $requisitionActionsHtml,
          'filterLabel' => 'Records',
          'showReset' => false,
          'showCount' => false,
        ]) ?>
      </div>
            <div class="box-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="requisitionTable">
                        <caption class="sr-only">Purchase requisitions</caption>
                        <thead style="background-color: var(--surface-2); color: var(--text-muted);">
                            <tr>
                                <th scope="col" style="min-width: 10px;">RequisitionID</th>
                                <th scope="col" style="min-width: 10px;">Items Purchased</th>
                                <th scope="col" style="min-width: 10px;">Vendors Suggestion</th>
                                <th scope="col" style="min-width: 10px;">Total Amount</th>
                                <th scope="col" style="min-width: 10px;">Requisition Date</th>
                                <th scope="col" style="min-width: 10px;">Requisition By</th>
                                <th scope="col" style="min-width: 10px;">Deadline</th>
                                <th scope="col" style="min-width: 10px;">Approved By</th>
                                <th scope="col" style="min-width: 10px;">Approved Date</th>
                                <th scope="col" style="min-width: 10px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requisitions as $req): ?>
                                <tr>
                                    <td><?= esc($req['requisition_id']) ?></td>
                                    <td><?= esc($req['item_count']) ?></td>
                                    <td><?= esc(implode(', ', $req['vendor_suggestions'])) ?></td>
                                    <td><?= esc($req['total']) ?></td>
                                    <td><?= esc($req['requisition_date']) ?></td>
                                    <td><?= esc($req['requisition_by']) ?></td>
                                    <td><?= esc($req['deadline']) ?></td>
                                    <td><?= esc($req['approved_by']) ?></td>
                                    <td><?= esc($req['approved_date']) ?></td>
                                    <td>
                                        <button class="btn btn-xs btn-primary editRequisitionBtn"
                                            data-id="<?= $req['requisition_id'] ?>"
                                            data-view="true" aria-label="View requisition"><i class="fa fa-eye" aria-hidden="true"></i></button>
                                        <button class="btn btn-xs btn-success editRequisitionBtn"
                                            data-id="<?= $req['requisition_id'] ?>" aria-label="Edit requisition"><i class="fa fa-edit" aria-hidden="true"></i></button>
                                        <a href="<?= route_to('purchase.requisition_delete', $req['id']) ?>"
                                            class="btn btn-xs btn-danger"
                                            onclick="return confirm('Delete this requisition?')" aria-label="Delete requisition"><i
                                                class="fa fa-trash" aria-hidden="true"></i></a>
                                        <?php if ($req['approved_by']): ?>
                                            <button class="btn btn-xs btn-success" aria-label="Approved"><i class="fa fa-check" aria-hidden="true"></i></button>
                                        <?php else: ?>
                                            <button
                                                class="btn btn-xs btn-primary editRequisitionBtn"
                                                data-id="<?= $req['requisition_id'] ?>"
                                                data-approved="true"
                                                aria-label="Approve requisition">
                                                <i class="fa fa-cart-plus" aria-hidden="true"></i>
                                            </button>

                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr style="border: 1px solid var(--border);">
                                <th colspan="3" style="text-align:right;border: 1px solid var(--border);">Total:</th>
                                <th id="totalAmountFooter" style="border: 1px solid var(--border);"></th>
                                <th colspan="6" style="border: 1px solid var(--border);"></th>
                            </tr>
                        </tfoot>

                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Requisition Modal -->
<div class="modal fade" id="requisitionModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document" style="max-width: 100vw; width: 84%; margin: 0 auto;">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="requisitionModalLabel">Add/Edit Requisition</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body" id="requisitionModalBody">
                <form id="requisitionForm" action="<?= route_to('purchase.requisition_create') ?>" method="post">
                    <?= csrf_field() ?>
                    <!-- Hidden field for requisition ID (used in edit mode) -->
                    <input type="hidden" name="requisition_id" id="requisitionId">
                    <!-- Hidden field to track edit mode -->
                    <input type="hidden" name="is_edit" id="isEdit" value="0">
                    <input type="hidden" name="id" id="id">
                    <input type="hidden" name="is_approved_mode" id="isApprovedMode" value="0">

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>REQUISITION ID</label>
                            <input type="text" class="form-control" id="displayRequisitionId" value="Auto Generated"
                                disabled>
                        </div>

                        <div class="form-group col-md-4">
                            <label>REQUISITION DATE</label>
                            <input type="date" class="form-control" name="requisition_date" id="requisitionDate"
                                value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="form-group col-md-4">
                            <label>REQUISITION NEED BY DATE</label>
                            <input type="date" class="form-control" name="deadline" id="deadline"
                                value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                    <div class="table-responsive">
                    <table class="table table-bordered">
                        <caption class="sr-only">Requisition items</caption>
                        <thead style="background-color: var(--surface-2); color: var(--text-muted);">
                            <tr>
                                <th scope="col">Item</th>
                                <th scope="col">Vendor Suggestion</th>
                                <th scope="col">Description</th>
                                <th scope="col">Unit Name</th>
                                <th scope="col">Quantity</th>
                                <th scope="col">Rate</th>
                                <th scope="col">Total</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody id="itemRows">
                            <tr>
                                <td>
                                    <select class="form-control item-select" name="items[0][item]">
                                        <option value="">Select item</option>
                                        <?php foreach ($items as $item): ?>
                                            <option value="<?= esc($item['item_name']) ?>"
                                                data-description="<?= esc($item['description']) ?>"
                                                data-unit="<?= esc($item['unit']) ?>"
                                                data-id_id="<?= esc($item['category_id']) ?>"
                                                data-category="<?= esc($item['category_id']) ?>"
                                                data-subcategory="<?= esc($item['subcategory_id']) ?>">
                                                <?= esc($item['item_name']) ?>
                                            </option>


                                        <?php endforeach; ?>
                                    </select>

                                </td>
                                <td>
                                    <select name="items[0][vendor_id]" class="form-control vendor-select">
                                        <option value="">Select</option>
                                        <?php foreach ($vendors as $vendor): ?>
                                            <option value="<?= esc($vendor['id']) ?>"><?= esc($vendor['company_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <input type="text" class="form-control" name="items[0][description]"
                                        placeholder="Description">
                                </td>
                                <td style="min-width: 200px;">
                                    <select name="items[0][unit_id]" class="form-control unit-select">
                                        <option value="">Select</option>
                                        <?php foreach ($units as $unit): ?>
                                            <option value="<?= esc($unit['id']) ?>"><?= esc($unit['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td><input type="number" class="form-control qty" name="items[0][qty]" value="0"></td>
                                <td><input type="number" class="form-control rate" name="items[0][rate]" value="0"></td>
                                <td><input type="hidden" name="items[0][category_id]" class="category-id"></td>
                                <td><input type="hidden" name="items[0][subcategory_id]" class="subcategory-id"></td>
                                <td><input type="hidden" name="items[0][id_id]" class="id_id"></td>
                                <td><input type="hidden" name="items[${index}][id]" value="${itemData ? itemData.id : ''}"></td>
                                <td><input type="number" class="form-control row-total" value="0.00" readonly></td>
                                <td><button class="btn btn-xs btn-danger removeRowBtn" aria-label="Remove item"><i
                                            class="fa fa-trash" aria-hidden="true"></i></button></td>
                            </tr>
                        </tbody>

                    </table>
                    </div>
                    <div class="text-right">
                        <button type="button" class="btn btn-outline-primary" id="addRowBtn">
                            <i class="fa fa-plus"></i> Add New
                        </button>
                    </div>

                    <div class="form-group mt-3">
                        <label>Total</label>
                        <input type="text" id="grandTotal" class="form-control" value="0" readonly>
                    </div>

                    <div class="form-group">
                        <label>REMARKS/NOTE</label>
                        <textarea name="remarks" id="remarks" class="form-control" rows="4"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Save</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection(); ?>

<?= $this->section('script'); ?>
<style>
    @media (max-width: 1400px) {
        .modal-xl {
            width: 95%;
            margin: 10px auto;
        }

        .table-responsive {
            border: none;
        }

        .item-row td {
            padding: 8px;
        }

        .btn-block {
            margin-bottom: 10px;
        }
    }

    @media (max-width: 768px) {
        .modal-xl {
            width: 95%;
            margin: 10px auto;
        }

        .table-responsive {
            border: none;
        }

        .item-row td {
            padding: 8px;
        }

        .btn-block {
            margin-bottom: 10px;
        }
    }
</style>


<script>
    const items = <?= json_encode($items, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    console.log('Items from PHP:', items);

    $(document).ready(function() {
        // Initialize DataTable
        $('#requisitionTable').DataTable({
            scrollX: true,
            scrollY: "55vh",
            scrollCollapse: true,
            language: {
                searchPlaceholder: "Search...",
                lengthMenu: "SHOW _MENU_ ENTRIES"
            },
            order: [
                [4, 'desc']
            ],
            dom: '<"row"<"col-sm-6"l><"col-sm-6 text-right"f>>rt<"row"<"col-sm-6"i><"col-sm-6 text-right"p>>',
            footerCallback: function(row, data, start, end, display) {
                var api = this.api();

                // Calculate the total over all pages
                var total = api
                    .column(3) // 3 is the index of "Total Amount" column
                    .data()
                    .reduce(function(a, b) {
                        var x = typeof a === 'string' ? parseFloat(a.replace(/,/g, '')) || 0 : a;
                        var y = typeof b === 'string' ? parseFloat(b.replace(/,/g, '')) || 0 : b;
                        return x + y;
                    }, 0);
                console.log('Total:', total);
                // Update footer
                $(api.column(3).footer()).html(total.toLocaleString());
            }
        });



        // Add Requisition Button Click
        $('#addRequisitionBtn').click(function() {
            resetForm();
            $('#requisitionModalLabel').text('Add Requisition');
            $('#isEdit').val('0');
            $('#requisitionModal').modal('show');
        });

        // Edit Requisition Button Click
        $(document).on('click', '.editRequisitionBtn', function() {
            const id = $(this).data('id');
            const isApproved = $(this).data('approved') === true || $(this).data('approved') === "true"; // convert string "true" to boolean
            const isView = $(this).data('view') === true || $(this).data('view') === "true"; // convert string "true" to boolean

            if (isView) {
                $('#requisitionModalLabel').text('View Requisition');
            } else if (isApproved) {
                $('#requisitionModalLabel').text('Approve Requisition');
            } else {
                $('#requisitionModalLabel').text('Edit Requisition');
            }
            //$('#requisitionModalLabel').text('Edit Requisition');
            $('#isEdit').val('1');
            $('#isApprovedMode').val(isApproved ? '1' : '0');

            // AJAX call to fetch requisition data
            $.ajax({
                url: "<?= route_to('purchase.requisition_get') ?>",
                type: 'GET',
                data: {
                    id: id
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        console.log('Requisition data:', response.data);
                        populateForm(response.data);

                        if (isApproved) {
                            // $('#requisitionForm input, #requisitionForm select, #requisitionForm textarea').prop('disabled', true);
                            $('#requisitionModal form button[type=submit]').show();
                            $('#requisitionForm .btn-success').text('Approve');
                        } else if (isView) {
                            $('#requisitionForm input, #requisitionForm select, #requisitionForm textarea').prop('disabled', true);
                            $('#requisitionModal form button[type=submit]').hide();
                        } else {
                            $('#requisitionForm input, #requisitionForm select, #requisitionForm textarea').prop('disabled', false);
                            $('#requisitionModal form button[type=submit]').show();
                            $('#requisitionForm .btn-success').text('Save');
                        }
                        $('#requisitionModal').modal('show');
                    } else {
                        alert('Error loading requisition: ' + response.message);
                    }
                },
                error: function() {
                    alert('Error loading requisition data.');
                }
            });
        });

        // Function to reset form to initial state
        function resetForm() {
            $('#requisitionForm')[0].reset();
            $('#itemRows').empty();
            $('#requisitionId').val('');
            $('#displayRequisitionId').val('Auto Generated');
            $('#requisitionDate').val('<?= date('Y-m-d') ?>');
            $('#deadline').val('<?= date('Y-m-d') ?>');
            $('#grandTotal').val('0');
            $('#remarks').val('');

            // Add one empty row
            addNewRow(0);
        }

        // Function to populate form with data
        function populateForm(data) {
            console.log('Populating form with data:', data);
            $('#id').val(data.items[0].id);
            $('#id').val(data.items[0].id);

            $('#requisitionId').val(data.requisition_id);
            $('#displayRequisitionId').val(data.requisition_id);
            $('#requisitionDate').val(data.requisition_date);
            $('#deadline').val(data.deadline);
            $('#remarks').val(data.remarks);

            // Clear existing rows
            $('#itemRows').empty();

            // Add rows for each item
            let index = 0;
            data.items.forEach(item => {
                addNewRow(index, item);
                index++;
            });

            // Update grand total
            $('#grandTotal').val(data.total);
        }

        // Function to add a new row (with optional data for editing)
        function addNewRow(index, itemData = null) {
            let row = $(`
        <tr>
            <td>
                <select class="form-control item-select" name="items[${index}][item]">
                    <option value="">Select item</option>
                    <?php foreach ($items as $item): ?>
                        <option value="<?= htmlspecialchars($item['item_name']) ?>"
                            data-description="<?= htmlspecialchars($item['description'] ?? '') ?>"
                            data-unit="<?= htmlspecialchars($item['unit'] ?? '') ?>"
                            data-vat="<?= htmlspecialchars($item['vat'] ?? '') ?>"
                            data-id_id="<?= htmlspecialchars($item['id'] ?? '') ?>"
                            data-category="<?= htmlspecialchars($item['category_id'] ?? '') ?>"
                            data-subcategory="<?= htmlspecialchars($item['subcategory_id'] ?? '') ?>">
                            <?= htmlspecialchars($item['item_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="items[${index}][category_id]" class="category-id">
                
                <input type="hidden" name="items[${index}][id]" value="${itemData ? itemData.id : ''}">
                <input type="hidden" name="items[${index}][subcategory_id]" class="subcategory-id">
            </td>
            <td>
                <select class="form-control vendor-select" name="items[${index}][vendor_id]">
                    <option value="">Select</option>
                    <?php foreach ($vendors as $vendor): ?>
                        <option value="<?= esc($vendor['id']) ?>"><?= esc($vendor['company_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td>
                <input type="text" class="form-control" name="items[${index}][description]" placeholder="Description" value="${itemData ? itemData.description : ''}">
            </td>
            <td style="min-width: 200px;">
                <select class="form-control unit-select" name="items[${index}][unit_id]">
                    <option value="">Select</option>
                    <?php foreach ($units as $unit): ?>
                        <option value="<?= esc($unit['id']) ?>"><?= esc($unit['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td><input type="number" class="form-control qty" name="items[${index}][qty]" value="${itemData ? itemData.quantity : 0}"></td>
            <td><input type="number" class="form-control rate" name="items[${index}][rate]" value="${itemData ? itemData.rate : 0}"></td>
           
            <td><input type="number" class="form-control row-total" value="${itemData ? (itemData.quantity * itemData.rate).toFixed(2) : '0.00'}" readonly></td>
            <td><button type="button" class="btn btn-xs btn-danger removeRowBtn"><i class="fa fa-trash"></i></button></td>
        </tr>
    `);

            $('#itemRows').append(row);

            // Set selected values after appending

            if (itemData) {
                row.find('.item-select').val(itemData.item).trigger('change');
                row.find('.vendor-select').val(itemData.vendor_id).trigger('change');
                row.find('.unit-select').val(itemData.unit_id).trigger('change');
            }

        }


        // Add new row button
        $('#addRowBtn').click(function() {
            const rowCount = $('#itemRows tr').length;
            addNewRow(rowCount);
        });

        // Remove row
        $(document).on('click', '.removeRowBtn', function() {
            if ($('#itemRows tr').length > 1) {
                $(this).closest('tr').remove();
                reindexRows();
                calculateGrandTotal();
            } else {
                alert('At least one item is required!');
            }
        });

        // Reindex rows after deletion
        function reindexRows() {
            $('#itemRows tr').each(function(index) {
                $(this).find('select, input').each(function() {
                    let name = $(this).attr('name');
                    if (name) {
                        name = name.replace(/\[\d+\]/, `[${index}]`);
                        $(this).attr('name', name);
                    }
                });
            });
        }

        // Item selection change

        $(document).on('change', '.item-select', function() {
            const selectedOption = $(this).find('option:selected');
            console.log('Selected option:', selectedOption);
            const description = selectedOption.data('description') || '';
            const unit = selectedOption.data('unit') || '';
            const id_id = selectedOption.data('id') || '';
            const category = selectedOption.data('category') || '';
            const subcategory = selectedOption.data('subcategory') || '';
            // console.log({ category, subcategory });
            console.log('Selected item data:', {
                description,
                unit,
                id_id,
                category,
                subcategory
            });

            // console.log(selectedOption.data());


            const row = $(this).closest('tr');

            row.find('input[name*="[description]"]').val(description);
            // No price/rate is available on the item catalog option (only
            // description/unit/category) — the Rate cell is user-entered.
            // Previously this line overwrote Rate with the unit string
            // (e.g. "pcs"), so calculateRowTotal()'s parseFloat() silently
            // read 0 and the Grand Total was always wrong.
            row.find('input[name*="[category_id]"]').val(category);
            row.find('input[name*="[id_id]"]').val(category);
            row.find('input[name*="[subcategory_id]"]').val(subcategory);

            if (row.find('.rate').val()) {
                row.find('.qty').trigger('input');
            }
        });

        // Calculate row total
        function calculateRowTotal(row) {
            const qty = parseFloat(row.find('.qty').val()) || 0;
            const rate = parseFloat(row.find('.rate').val()) || 0;
            const total = qty * rate;
            row.find('.row-total').val(total.toFixed(2));
            calculateGrandTotal();
        }

        // Calculate grand total
        function calculateGrandTotal() {
            let grandTotal = 0;
            $('.row-total').each(function() {
                grandTotal += parseFloat($(this).val()) || 0;
            });
            $('#grandTotal').val(grandTotal.toFixed(2));
        }

        // Trigger calculations on quantity/rate change
        $(document).on('input', '.qty, .rate', function() {
            calculateRowTotal($(this).closest('tr'));
        });

        // Form submission handling
        $(document).on('submit', '#requisitionForm', function(e) {
            e.preventDefault();
            const form = $(this);
            const isEdit = $('#isEdit').val() === '1';
            const formData = form.serialize();
            // log_message('info', 'Submitting requisition form with data: ' + formData);
            $.ajax({
                url: isEdit ? "<?= route_to('purchase.requisition_update') ?>" : form.attr('action'),
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        alert(isEdit ? 'Requisition updated successfully!' : 'Requisition created successfully!');
                        $('#requisitionModal').modal('hide');
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function(xhr) {
                    try {
                        const json = JSON.parse(xhr.responseText);
                        alert('Error: ' + json.message);
                    } catch (e) {
                        alert('Unexpected error occurred. Please check the console.');
                        console.error(xhr.responseText);
                    }
                }

            });
        });

        // Initialize form with one empty row
        resetForm();
    });
</script>

<?= $this->endSection(); ?>