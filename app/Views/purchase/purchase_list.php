<?= $this->extend('layout/main-layout'); ?>
<?php $this->section('needsDataTable'); ?>1<?php $this->endSection(); ?>

<?= $this->section('css'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/saas/bandwidth-pages.css?v=7'); ?>">
<?= $this->endSection('css'); ?>

<?= $this->section('content'); ?>
<div class="content-wrapper">
    <section class="content ipb-saas-list ipb-bw-page">

    <?= $this->include('components/page-header', [
      'title' => 'Purchase Bill',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Purchase'],
        ['label' => 'Purchase Bill'],
      ],
    ]); ?>

<div class="box box-primary">
            <div class="box-header with-border ipb-box-toolbar">
        <div class="ipb-list-toolbar">
          <div class="ipb-list-toolbar-filters">
            <span class="ipb-filter-label"><i class="fa fa-file-invoice" aria-hidden="true"></i> Bills</span>
          </div>
          <div class="ipb-list-toolbar-actions">
                    <button type="button" id="addRequisitionBtn" class="btn btn-primary">
                        <i class="fa fa-plus" aria-hidden="true"></i> New purchase
                    </button>
          </div>
        </div>
      </div>
            <div class="box-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="requisitionTable">
                        <caption class="sr-only">Purchase bill requisitions</caption>
                        <thead>
                            <tr>
                                <th scope="col">Requisition ID</th>
                                <th scope="col">Items</th>
                                <th scope="col">Vendor suggestion</th>
                                <th scope="col">Total amount</th>
                                <th scope="col">Requisition date</th>
                                <th scope="col">Requisition by</th>
                                <th scope="col">Deadline</th>
                                <th scope="col">Approved by</th>
                                <th scope="col">Approved date</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requisitions as $req): ?>
                                <tr>
                                    <td><strong><?= esc($req['requisition_id']) ?></strong></td>
                                    <td><?= esc($req['item_count']) ?></td>
                                    <td><?= esc(implode(', ', $req['vendor_suggestions'])) ?></td>
                                    <td><?= esc($req['total']) ?></td>
                                    <td><?= esc($req['requisition_date']) ?></td>
                                    <td><?= esc($req['requisition_by']) ?></td>
                                    <td><?= esc($req['deadline']) ?></td>
                                    <td><?= esc($req['approved_by']) ?></td>
                                    <td><?= esc($req['approved_date']) ?></td>
                                    <td>
                                        <div class="ipb-row-actions">
                                            <button type="button" class="ipb-row-btn tone-info editRequisitionBtn" title="View"
                                                data-id="<?= esc($req['requisition_id'], 'attr') ?>"
                                                data-view="true"><i class="fa fa-eye" aria-hidden="true"></i><span class="sr-only">View</span></button>
                                            <button type="button" class="ipb-row-btn tone-brand editRequisitionBtn" title="Edit"
                                                data-id="<?= esc($req['requisition_id'], 'attr') ?>"><i class="fa fa-edit" aria-hidden="true"></i><span class="sr-only">Edit</span></button>
                                            <a href="<?= route_to('purchase_bill.requisition_delete', $req['id']) ?>"
                                                class="ipb-row-btn tone-danger" title="Delete"
                                                onclick="return confirm('Delete this requisition?')"><i
                                                    class="fa fa-trash" aria-hidden="true"></i><span class="sr-only">Delete</span></a>
                                            <?php if ($req['approved_by']): ?>
                                                <span class="ipb-row-btn tone-success" title="Approved"><i class="fa fa-check" aria-hidden="true"></i><span class="sr-only">Approved</span></span>
                                            <?php else: ?>
                                                <span class="ipb-row-btn tone-violet" title="Pending approval"><i class="fa fa-cart-plus" aria-hidden="true"></i><span class="sr-only">Pending</span></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" style="text-align:right;">Total:</th>
                                <th id="totalAmountFooter"><?= esc(number_format((float) $grandTotal, 2)) ?></th>
                                <th colspan="6"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Requisition Modal -->
<div class="modal fade" id="requisitionModal" tabindex="-1" role="dialog" aria-labelledby="requisitionModalLabel">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="requisitionModalLabel">Add purchase</h5>
                <button type="button" class="close modal-close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <form id="requisitionForm" action="<?= route_to('purchase_bill.requisition_create') ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="requisition_id" id="requisitionId">
                <input type="hidden" name="is_edit" id="isEdit" value="0">
                <input type="hidden" name="id" id="id">

                <div class="modal-body" id="requisitionModalBody">
                    <div class="ipb-req-grid is-3">
                        <div class="ipb-req-field">
                            <label for="displayRequisitionId">Requisition ID</label>
                            <input type="text" class="form-control" id="displayRequisitionId" value="Auto generated" disabled>
                        </div>
                        <div class="ipb-req-field">
                            <label for="requisitionDate">Requisition date</label>
                            <input type="date" class="form-control" name="requisition_date" id="requisitionDate"
                                value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="ipb-req-field">
                            <label for="deadline">Need by date</label>
                            <input type="date" class="form-control" name="deadline" id="deadline"
                                value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>

                    <div class="ipb-req-items-wrap">
                        <div class="ipb-req-items-head">
                            <h4>Line items</h4>
                            <button type="button" class="btn btn-primary btn-sm" id="addRowBtn">
                                <i class="fa fa-plus" aria-hidden="true"></i> Add item
                            </button>
                        </div>
                        <div class="ipb-req-table-scroll">
                            <table class="table table-bordered" id="itemRowsTable">
                                <caption class="sr-only">Purchase line items</caption>
                                <thead>
                                    <tr>
                                        <th scope="col">Item</th>
                                        <th scope="col">Vendor</th>
                                        <th scope="col">Description</th>
                                        <th scope="col">Unit</th>
                                        <th scope="col">Qty</th>
                                        <th scope="col">Rate</th>
                                        <th scope="col">Total</th>
                                        <th scope="col">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="itemRows"></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="ipb-req-meta">
                        <div class="ipb-req-field">
                            <label for="grandTotal">Grand total</label>
                            <input type="text" id="grandTotal" class="form-control" value="0" readonly>
                        </div>
                        <div class="ipb-req-field">
                            <label for="remarks">Remarks / note</label>
                            <textarea name="remarks" id="remarks" class="form-control" rows="3" placeholder="Optional notes"></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="requisitionSaveBtn">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection('content'); ?>

<?= $this->section('script'); ?>
<script>
    $(document).ready(function () {
        $('#requisitionTable').DataTable({
            scrollX: true,
            scrollY: '55vh',
            scrollCollapse: true,
            language: {
                searchPlaceholder: 'Search...',
                lengthMenu: 'Show _MENU_ entries'
            },
            order: [[4, 'desc']],
            dom: '<"row"<"col-sm-6"l><"col-sm-6 text-right"f>>rt<"row"<"col-sm-6"i><"col-sm-6 text-right"p>>'
            // Total amount footer is server-rendered from SQL SUM(total) (see purchase_list.php
            // controller / $grandTotal) so it stays correct regardless of search/paging/filtering
            // and is not recomputed here from currently-visible/loaded rows.
        });

        function setPurchaseModalMode(mode) {
            var isView = mode === 'view';
            $('#requisitionForm input, #requisitionForm select, #requisitionForm textarea').prop('disabled', isView);
            $('#displayRequisitionId').prop('disabled', true);
            $('#grandTotal').prop('disabled', true);
            $('#requisitionSaveBtn').toggle(!isView);
            $('#addRowBtn').toggle(!isView);
            $('#requisitionModal').toggleClass('is-view-mode', isView);
        }

        $('#addRequisitionBtn').click(function () {
            resetForm();
            $('#requisitionModalLabel').text('Add purchase');
            $('#isEdit').val('0');
            setPurchaseModalMode('edit');
            $('#requisitionModal').modal('show');
        });

        $(document).on('click', '.editRequisitionBtn', function () {
            var id = $(this).data('id');
            var isView = $(this).data('view') === true || $(this).data('view') === 'true';

            $('#requisitionModalLabel').text(isView ? 'View purchase' : 'Edit purchase');
            $('#isEdit').val('1');

            $.ajax({
                url: "<?= route_to('purchase_bill.requisition_get') ?>",
                type: 'GET',
                data: { id: id },
                dataType: 'json',
                success: function (response) {
                    if (response.status === 'success') {
                        populateForm(response.data);
                        setPurchaseModalMode(isView ? 'view' : 'edit');
                        $('#requisitionModal').modal('show');
                    } else {
                        alert('Error loading requisition: ' + response.message);
                    }
                },
                error: function () {
                    alert('Error loading requisition data.');
                }
            });
        });

        function resetForm() {
            $('#requisitionForm')[0].reset();
            $('#itemRows').empty();
            $('#requisitionId').val('');
            $('#id').val('');
            $('#displayRequisitionId').val('Auto generated');
            $('#requisitionDate').val('<?= date('Y-m-d') ?>');
            $('#deadline').val('<?= date('Y-m-d') ?>');
            $('#grandTotal').val('0');
            $('#remarks').val('');
            addNewRow(0);
            setPurchaseModalMode('edit');
        }

        function populateForm(data) {
            if (data.items && data.items[0]) {
                $('#id').val(data.items[0].id || '');
            }
            $('#requisitionId').val(data.requisition_id || '');
            $('#displayRequisitionId').val(data.requisition_id || '');
            $('#requisitionDate').val(data.requisition_date || '');
            $('#deadline').val(data.deadline || '');
            $('#remarks').val(data.remarks || '');
            $('#itemRows').empty();

            var items = data.items || [];
            if (!items.length) {
                addNewRow(0);
            } else {
                items.forEach(function (item, index) {
                    addNewRow(index, item);
                });
            }
            $('#grandTotal').val(data.total || '0');
        }

        function addNewRow(index, itemData) {
            itemData = itemData || null;
            var itemId = itemData && itemData.id ? itemData.id : '';
            var description = itemData && itemData.description ? itemData.description : '';
            var qty = itemData && itemData.quantity != null ? itemData.quantity : 0;
            var rate = itemData && itemData.rate != null ? itemData.rate : 0;
            var total = (parseFloat(qty) * parseFloat(rate)) || 0;

            var row = $(`
        <tr class="item-row">
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
                <input type="hidden" name="items[${index}][subcategory_id]" class="subcategory-id">
                <input type="hidden" name="items[${index}][id_id]" class="id_id">
                <input type="hidden" name="items[${index}][id]" class="item-row-id" value="${itemId}">
            </td>
            <td>
                <select class="form-control vendor-select" name="items[${index}][vendor_id]">
                    <option value="">Select vendor</option>
                    <?php foreach ($vendors as $vendor): ?>
                        <option value="<?= esc($vendor['id']) ?>"><?= esc($vendor['company_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td>
                <input type="text" class="form-control" name="items[${index}][description]" placeholder="Description" value="${description}">
            </td>
            <td>
                <select class="form-control unit-select" name="items[${index}][unit_id]">
                    <option value="">Select</option>
                    <?php foreach ($units as $unit): ?>
                        <option value="<?= esc($unit['id']) ?>"><?= esc($unit['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td><input type="number" class="form-control qty" name="items[${index}][qty]" value="${qty}" step="0.01"></td>
            <td><input type="number" class="form-control rate" name="items[${index}][rate]" value="${rate}" step="0.01"></td>
            <td><input type="number" class="form-control row-total" value="${total.toFixed(2)}" readonly></td>
            <td>
                <button type="button" class="ipb-row-btn tone-danger removeRowBtn" title="Remove item">
                    <i class="fa fa-trash" aria-hidden="true"></i><span class="sr-only">Remove</span>
                </button>
            </td>
        </tr>
            `);

            $('#itemRows').append(row);

            if (itemData) {
                row.find('.item-select').val(itemData.item).trigger('change');
                row.find('.vendor-select').val(itemData.vendor_id);
                row.find('.unit-select').val(itemData.unit_id);
                row.find('.qty').val(qty);
                row.find('.rate').val(rate);
                row.find('.row-total').val(total.toFixed(2));
            }
        }

        $('#addRowBtn').click(function () {
            addNewRow($('#itemRows tr').length);
        });

        $(document).on('click', '.removeRowBtn', function () {
            if ($('#itemRows tr').length > 1) {
                $(this).closest('tr').remove();
                reindexRows();
                calculateGrandTotal();
            } else {
                alert('At least one item is required!');
            }
        });

        function reindexRows() {
            $('#itemRows tr').each(function (index) {
                $(this).find('select, input').each(function () {
                    var name = $(this).attr('name');
                    if (name) {
                        $(this).attr('name', name.replace(/\[\d+\]/, '[' + index + ']'));
                    }
                });
            });
        }

        $(document).on('change', '.item-select', function () {
            var selectedOption = $(this).find('option:selected');
            var description = selectedOption.data('description') || '';
            var unit = selectedOption.data('unit') || '';
            var id_id = selectedOption.data('id_id') || '';
            var category = selectedOption.data('category') || '';
            var subcategory = selectedOption.data('subcategory') || '';
            var row = $(this).closest('tr');

            row.find('input[name*="[description]"]').val(description);
            row.find('input[name*="[rate]"]').val(unit);
            row.find('input[name*="[category_id]"]').val(category);
            row.find('input[name*="[id_id]"]').val(id_id);
            row.find('input[name*="[subcategory_id]"]').val(subcategory);

            if (row.find('.rate').val()) {
                row.find('.qty').trigger('input');
            }
        });

        function calculateRowTotal(row) {
            var qty = parseFloat(row.find('.qty').val()) || 0;
            var rate = parseFloat(row.find('.rate').val()) || 0;
            row.find('.row-total').val((qty * rate).toFixed(2));
            calculateGrandTotal();
        }

        function calculateGrandTotal() {
            var grandTotal = 0;
            $('.row-total').each(function () {
                grandTotal += parseFloat($(this).val()) || 0;
            });
            $('#grandTotal').val(grandTotal.toFixed(2));
        }

        $(document).on('input', '.qty, .rate', function () {
            calculateRowTotal($(this).closest('tr'));
        });

        $(document).on('submit', '#requisitionForm', function (e) {
            e.preventDefault();
            var form = $(this);
            var isEdit = $('#isEdit').val() === '1';
            $.ajax({
                url: isEdit ? "<?= route_to('purchase_bill.requisition_update') ?>" : form.attr('action'),
                type: 'POST',
                data: form.serialize(),
                dataType: 'json',
                success: function (response) {
                    if (response.status === 'success') {
                        alert(isEdit ? 'Purchase updated successfully!' : 'Purchase created successfully!');
                        $('#requisitionModal').modal('hide');
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function (xhr) {
                    try {
                        var json = JSON.parse(xhr.responseText);
                        alert('Error: ' + json.message);
                    } catch (err) {
                        alert('Unexpected error occurred.');
                        console.error(xhr.responseText);
                    }
                }
            });
        });

        resetForm();
    });
</script>
<?= $this->endSection('script'); ?>
