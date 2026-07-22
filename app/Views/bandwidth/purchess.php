<?= $this->extend('layout/main-layout'); ?>
<?php $this->section('needsDataTable'); ?>1<?php $this->endSection(); ?>

<?= $this->section('css'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/saas/bandwidth-pages.css?v=1'); ?>">
<?= $this->endSection('css'); ?>

<?= $this->section('content'); ?>

<div class="content-wrapper">
    <section class="content ipb-saas-list ipb-bw-page">

    <?= $this->include('components/page-header', [
      'title' => 'Purchase Bills',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Bandwidth Buy'],
        ['label' => 'Purchase Bills'],
      ],
    ]); ?>

<div class="box box-warning">

            <div class="box-header with-border ipb-box-toolbar">
        <?php
          ob_start();
        ?>
<?php if (userHasPermission('customer_payment', 'create')): ?>
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#billModal">
                            <i class="fa fa-plus" aria-hidden="true"></i> New Bill
                        </button>
                    <?php endif; ?>
                    <?php if (userHasPermission('customer_payment', 'delete')): ?>
                        <button type="button" class="btn btn-danger delete-btn">
                            <i class="far fa-trash-can" aria-hidden="true"></i> Delete
                        </button>
                    <?php endif; ?>
        <?php
          $bwPurchessActionsHtml = ob_get_clean();

          echo view('components/list-toolbar', [
            'filters' => [],
            'actionsHtml' => $bwPurchessActionsHtml,
            'filterLabel' => 'Bills',
            'showReset' => false,
            'showCount' => false,
            'manualBind' => true,
          ]);
        ?>
      </div>

            <form id="filterForm" method="post" class="ipb-bw-filters">
                <div class="ipb-bw-field">
                    <label for="reseller">Provider</label>
                    <select class="form-control ipb-filter-select" id="reseller" name="reseller">
                        <option value="">All providers</option>
                        <?php foreach ($providers as $reseller): ?>
                            <option value="<?= $reseller['id'] ?>"><?= esc($reseller['company_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="ipb-bw-field">
                    <label for="status">Status</label>
                    <select class="form-control ipb-filter-select" id="status" name="status">
                        <option value="">All status</option>
                        <option value="Due">Pending</option>
                        <option value="Paid">Completed</option>
                    </select>
                </div>
                <div class="ipb-bw-field">
                    <label for="fromDate">From date</label>
                    <input type="date" class="form-control ipb-filter-date" id="fromDate" name="fromDate">
                </div>
                <div class="ipb-bw-field">
                    <label for="toDate">To date</label>
                    <input type="date" class="form-control ipb-filter-date" id="toDate" name="toDate">
                </div>
                <div class="ipb-bw-filter-actions">
                    <button type="button" id="clearFilter" class="btn btn-default">Clear</button>
                    <button type="button" id="applyFilter" class="btn btn-primary">Apply</button>
                    <span class="ipb-filter-count" id="bwPurchessCount" style="display:none;" aria-live="polite"></span>
                </div>
            </form>

            <div class="box-body">
                <div class="loading-overlay" style="display: none;">
                    <div class="spinner"></div>
                </div>
                <div class="table-responsive">
                <table class="table table-bordered table-striped datatable">
                    <caption class="sr-only">Purchase bills</caption>
                    <thead class="text-nowrap">
                        <tr>
                            <?php if (userHasPermission('customer_payment', 'delete')): ?>
                                <th data-data="select" scope="col">
                                    <input type="checkbox" class="form-check-input" id="select_all">
                                </th>
                            <?php endif; ?>

                            <th data-data="serial" scope="col">#</th>
                            <th data-data="provider" scope="col">Provider</th>
                            <th data-data="invoice" scope="col">Invoice ID</th>
                            <th data-data="amount" scope="col">Amount (৳)</th>
                            <th data-data="received" scope="col">Received</th>
                            <th data-data="discount" scope="col">Discount</th>
                            <th data-data="due" scope="col">Due</th>
                            <th data-data="created_at" scope="col">Paid At</th>
                            <th data-data="comments" scope="col">Comments</th>
                            <th data-data="status" scope="col">Status</th>

                            <?php if (userHasPermission('customer_payment', 'update')): ?>
                                <th data-data="action" scope="col">Action</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <?php
                      // Zero-blank-frame first paint: skeleton rows show before
                      // JS/DataTables boots; DataTables replaces this <tbody> on its first draw.
                      $bwPurchessSkeletonCols = 10
                        + (userHasPermission('customer_payment', 'delete') ? 1 : 0)
                        + (userHasPermission('customer_payment', 'update') ? 1 : 0);
                    ?>
                    <?= view('components/skeleton-table', ['cols' => $bwPurchessSkeletonCols, 'rows' => 8]) ?>
                </table>
                </div>
            </div>
        </div>
    </section>
    <!-- /.content -->
</div>
<div class="modal fade" id="billModal" tabindex="-1" role="dialog" aria-labelledby="billModalLabel">
    <div class="modal-dialog modal-xl" role="document" style="max-width: 100vw; width: 84%; margin: 0 auto;">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="billModalLabel">Create New Bill</h4>
            </div>
            <form id="billForm" action="<?= route_to('bandwidth.purchess_save') ?>" method="POST"
                enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row">
                        <input type="hidden" name="id" id="purchase_id">
                        <div class="col-md-3 col-sm-6">
                            <div class="form-group">
                                <label>PROVIDER</label>
                                <select class="form-control" id="provider" name="provider_id">
                                    <option value="">Select Provider</option>
                                    <?php foreach ($providers as $reseller): ?>
                                        <option value="<?= $reseller["id"] ?>"><?= $reseller["company_name"] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-3 col-sm-6">
                            <div class="form-group">
                                <label>PAYMENT STATUS</label>
                                <select class="form-control" name="payment_status">
                                    <option value="Paid">Paid</option>
                                    <option value="Due" selected>Due</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-3 col-sm-6">
                            <div class="form-group">
                                <label>BILLING DATE</label>
                                <input type="date" class="form-control" name="billing_date"
                                    value="<?= date('Y-m-d') ?>">
                            </div>
                        </div>

                        <div class="col-md-3 col-sm-6">
                            <div class="form-group">
                                <label>INVOICE NUMBER</label>
                                <input type="text" class="form-control" name="invoice_number" placeholder="Hcj59k68...">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="itemsTable">
                                    <caption class="sr-only">Bill items</caption>
                                    <thead>
                                        <tr>
                                            <th style="min-width: 200px;" scope="col">Item</th>
                                            <th scope="col">Description</th>
                                            <th style="min-width: 100px;" scope="col">Unit</th>
                                            <th style="min-width: 80px;" scope="col">Qty</th>
                                            <th style="min-width: 120px;" scope="col">Rate</th>
                                            <th style="min-width: 100px;" scope="col">VAT</th>
                                            <th style="min-width: 120px;" scope="col">From Date</th>
                                            <th style="min-width: 120px;" scope="col">To Date</th>
                                            <th style="min-width: 150px;" scope="col">Total</th>
                                            <th style="min-width: 60px;" scope="col">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="item-row">
                                            <td>
                                                <select class="form-control item-select" name="items[0][item]">
                                                    <option value="">Select item</option>
                                                    <?php foreach ($items as $item): ?>
                                                        <option value="<?= htmlspecialchars($item['item_name']) ?>"
                                                            data-description="<?= htmlspecialchars($item['description']) ?>"
                                                            data-unit="<?= htmlspecialchars($item['unit']) ?>"
                                                            data-vat="<?= htmlspecialchars($item['vat']) ?>">
                                                            <?= htmlspecialchars($item['item_name']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                            <td><input type="text" class="form-control" name="items[0][description]"
                                                    placeholder="Description"></td>
                                            <td><input type="text" class="form-control" name="items[0][unit]"
                                                    placeholder="Unit"></td>
                                            <td><input type="number" class="form-control qty" name="items[0][qty]"
                                                    value="1" min="1"></td>
                                            <td><input type="number" class="form-control rate" name="items[0][rate]"
                                                    step="0.01" placeholder="0.00"></td>
                                            <td><input type="number" class="form-control vat" name="items[0][vat]"
                                                    step="0.01" placeholder="0.00"></td>
                                            <td><input type="date" class="form-control from-date"
                                                    name="items[0][from_date]"></td>
                                            <td><input type="date" class="form-control to-date"
                                                    name="items[0][to_date]"></td>
                                            <td><input type="text" class="form-control total" name="items[0][total]"
                                                    readonly></td>
                                            <td><button type="button" class="btn btn-danger btn-sm remove-row" aria-label="Remove item"><i
                                                        class="fa fa-times" aria-hidden="true"></i></button></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 col-sm-12">
                            <div class="form-group">
                                <label>REMARKS</label>
                                <textarea class="form-control" name="remarks" rows="3"
                                    placeholder="Add notes or comments"></textarea>
                            </div>
                            <div class="form-group" id="imagePreview" style="display: none;">
                                <label>ATTACHED DOCUMENT</label><br>
                                <img src="" alt="Bill Image" style="max-width: 200px; margin-top: 10px;"
                                    id="uploadedImagePreview">
                            </div>
                            <div class="form-group">
                                <label>ATTACH DOCUMENT</label>
                                <div class="input-group">
                                    <input type="file" class="form-control" name="image">
                                    <span class="input-group-btn">
                                        <button class="btn btn-info" type="button" aria-label="Attach document"><i
                                                class="fa fa-paperclip" aria-hidden="true"></i></button>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-sm-12">
                            <div class="row">
                                <div class="col-md-6">
                                    <button type="button" class="btn btn-success btn-block btn-add-item">
                                        <i class="fa fa-plus-circle"></i> Add Item
                                    </button>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group total-section">
                                        <label>GRAND TOTAL</label>
                                        <div class="input-group">
                                            <span class="input-group-addon">৳</span>
                                            <input type="text" class="form-control grand-total" name="grand_total"
                                                value="0.00" readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>


                            <!-- New Payment Details Section -->
                            <div class="row payment-details">
                                <div class="col-md-12">
                                    <h4 class="payment-section-title"
                                        style="margin-top: 15px; margin-bottom: 20px; color: black; border-bottom: 2px solid #eee; padding-bottom: 5px;">
                                        Payment Details (৳)
                                    </h4>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-group">
                                        <label>Paid (৳)</label>
                                        <input type="number" class="form-control paid_number" name="paid_number"
                                            value="0" step="1" min="0">
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-group">
                                        <label>DISCOUNT (৳)</label>
                                        <input type="number" class="form-control discount" name="discount" value="0"
                                            step="1" min="0">
                                    </div>
                                </div>

                                <div class="col-md-3 col-sm-6">
                                    <div class="form-group">
                                        <label>PAYMENT METHOD</label>
                                        <select class="form-control" name="payment_method">
                                            <option value="bKash">bKash</option>
                                            <option value="Nagad">Nagad</option>
                                            <option value="Rocket">Rocket</option>
                                            <option value="Upay">Upay</option>
                                            <option value="SSLCommerz">SSLCommerz</option>
                                            <option value="Cash">Cash</option>
                                            <option value="Bank Transfer">Bank Transfer</option>
                                            <option value="Cheque">Cheque</option>
                                        </select>
                                    </div>
                                </div>




                                <div class="col-md-3 col-sm-6">
                                    <div class="form-group">
                                        <label>PAID BY</label>
                                        <select class="form-control" name="paid_by">
                                            <option value="">Select Payer</option>
                                            <?php foreach ($providers as $receiver): ?>
                                                <option value="<?= $receiver['id'] ?>">
                                                    <?= $receiver['company_name'] ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-group">
                                        <label>RECEIVED BY</label>
                                        <input type="text" class="form-control" name="received_by"
                                            placeholder="Enter payer's name">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Bill</button>
                </div>
            </form>
        </div>
    </div>
</div>


<?= $this->endSection('content'); ?>

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
    $(document).ready(function () {
        $(document).on('click', '.edit-bill', function () {
            const btn = $(this);
            const items = JSON.parse(btn.attr('data-items').replace(/&quot;/g, '"'));

            const providerId = btn.data('provider'); // Get provider ID from data attribute
            const payment_status = btn.data('status'); // Get payment status from data attribute
            // Set the selected option
            // Set basic fields
            $('#purchase_id').val(btn.data('id'));
            $('#provider').val(providerId).trigger('change');
            $('select[name="payment_status"]').val(payment_status).trigger('change');
            // $('select[name="payment_status"]').val(btn.data('status'));
            $('input[name="billing_date"]').val(btn.data('date'));
            $('input[name="invoice_number"]').val(btn.data('invoice'));
            $('textarea[name="remarks"]').val(btn.data('remarks'));

            // Set payment-related fields
            // Payment fields
            $('input[name="discount"]').val(btn.data('discount'));
            $('select[name="payment_method"]').val(btn.data('payment-method'));
            $('select[name="paid_by"]').val(btn.data('paid-by'));
            $('input[name="received_by"]').val(btn.data('received-by'));
            $('input[name="paid_number"]').val(btn.data('paid-number'));

            // Handle image preview
            const image = btn.data('image');
            if (image) {
                $('#imagePreview').show();
                $('#uploadedImagePreview').attr('src', 'assets/img/purchase_bill/' + image);
            } else {
                $('#imagePreview').hide();
            }

            // Clear existing items
            $('#itemsTable tbody').empty();

            // Populate items
            items.forEach((item, index) => {
                const newRow = `
        <tr class="item-row">
            <td>
                <select class="form-control item-select" name="items[${index}][item]">
                    ${generateItemOptions(item.item)}
                </select>
            </td>
            <td><input type="text" class="form-control" name="items[${index}][description]" value="${item.description || ''}"></td>
            <td><input type="text" class="form-control" name="items[${index}][unit]" value="${item.unit || ''}"></td>
            <td><input type="number" class="form-control qty" name="items[${index}][qty]" value="${item.qty || 1}" min="1"></td>
            <td><input type="number" class="form-control rate" name="items[${index}][rate]" value="${item.rate || 0}" step="0.01"></td>
            <td><input type="number" class="form-control vat" name="items[${index}][vat]" value="${item.vat || 0}" step="0.01"></td>
            <td><input type="date" class="form-control from-date" name="items[${index}][from_date]" value="${item.from_date || ''}"></td>
            <td><input type="date" class="form-control to-date" name="items[${index}][to_date]" value="${item.to_date || ''}"></td>
            <td><input type="text" class="form-control total" name="items[${index}][total]" value="${item.total || 0}" readonly></td>
            <td><button type="button" class="btn btn-danger btn-sm remove-row"><i class="fa fa-times"></i></button></td>
        </tr>`;
                $('#itemsTable tbody').append(newRow);
            });

            // Update grand total
            updateGrandTotal();

            // Change modal title and action
            $('#billModalLabel').text('Edit Bill');
            $('#billForm').attr('action', '<?= route_to("bandwidth.purchess_save") ?>');
            $('#billModal').modal('show');
        });
        // Helper function to generate item options
        function generateItemOptions(selectedItem) {
            let options = '<option value="">Select item</option>';
            <?php foreach ($items as $item): ?>
                options += `<option value="<?= $item['item_name'] ?>" 
            ${'<?= $item['item_name'] ?>' === selectedItem ? 'selected' : ''}
            data-description="<?= $item['description'] ?>"
            data-unit="<?= $item['unit'] ?>"
            data-vat="<?= $item['vat'] ?>">
            <?= $item['item_name'] ?>
        </option>`;
            <?php endforeach; ?>
            return options;
        }

        // Reset modal when closed
        // Reset modal when closed
        $('#billModal').on('hidden.bs.modal', function () {
            // Reset form fields
            $('#billForm')[0].reset();

            // Clear specific fields
            $('#purchase_id').val('');
            $('input[name="image"]').val(''); // Clear file input
            $('.grand-total').val('0.00');

            // Reset provider select
            $('#provider').val('').trigger('change');

            // Reset items table to initial state
            $('#itemsTable tbody').html(`
        <tr class="item-row">
            <td>
                <select class="form-control item-select" name="items[0][item]">
                    <option value="">Select item</option>
                    <?php foreach ($items as $item): ?>
                        <option value="<?= htmlspecialchars($item['item_name']) ?>"
                            data-description="<?= htmlspecialchars($item['description']) ?>"
                            data-unit="<?= htmlspecialchars($item['unit']) ?>"
                            data-vat="<?= htmlspecialchars($item['vat']) ?>">
                            <?= htmlspecialchars($item['item_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td><input type="text" class="form-control" name="items[0][description]" placeholder="Description"></td>
            <td><input type="text" class="form-control" name="items[0][unit]" placeholder="Unit"></td>
            <td><input type="number" class="form-control qty" name="items[0][qty]" value="1" min="1"></td>
            <td><input type="number" class="form-control rate" name="items[0][rate]" step="0.01" placeholder="0.00"></td>
            <td><input type="number" class="form-control vat" name="items[0][vat]" step="0.01" placeholder="0.00"></td>
            <td><input type="date" class="form-control from-date" name="items[0][from_date]"></td>
            <td><input type="date" class="form-control to-date" name="items[0][to_date]"></td>
            <td><input type="text" class="form-control total" name="items[0][total]" readonly></td>
            <td><button type="button" class="btn btn-danger btn-sm remove-row"><i class="fa fa-times"></i></button></td>
        </tr>
    `);

            // Reset image preview
            $('#imagePreview').hide();
            $('#uploadedImagePreview').attr('src', '');

            // Reset modal title and action
            $('#billModalLabel').text('Create New Bill');
            $('#billForm').attr('action', '<?= route_to("bandwidth.purchess_save") ?>');

            // Reset row index counter
            rowIndex = 0;

            // Reset any error states
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').remove();
        });

        // Update description and unit when item is selected
        $(document).on('change', '.item-select', function () {
            const selectedOption = $(this).find('option:selected');
            const description = selectedOption.data('description') || '';
            const unit = selectedOption.data('unit') || '';
            const vat = selectedOption.data('vat') || 0;

            const row = $(this).closest('tr');
            row.find('input[name$="[description]"]').val(description);
            row.find('input[name$="[unit]"]').val(unit);
            row.find('input[name$="[vat]"]').val(vat);

            // Trigger calculation if rate is already set
            if (row.find('.rate').val()) {
                row.find('.qty').trigger('input');
            }
        });



        // Add new item row
        let rowIndex = 0;

        $('.btn-add-item').click(function () {
            rowIndex++;
            const newRow = `
    <tr class="item-row">
        <td>
            <select class="form-control item-select" name="items[${rowIndex}][item]">
                ${$('.item-select').first().html()}
            </select>
        </td>
        <td><input type="text" class="form-control" name="items[${rowIndex}][description]" placeholder="Description"></td>
        <td><input type="text" class="form-control" name="items[${rowIndex}][unit]" placeholder="Unit"></td>
        <td><input type="number" class="form-control qty" name="items[${rowIndex}][qty]" value="1" min="1"></td>
        <td><input type="number" class="form-control rate" name="items[${rowIndex}][rate]" step="0.01" placeholder="0.00"></td>
        <td><input type="number" class="form-control vat" name="items[${rowIndex}][vat]" step="0.01" placeholder="0.00"></td>
        <td><input type="date" class="form-control from-date" name="items[${rowIndex}][from_date]"></td>
        <td><input type="date" class="form-control to-date" name="items[${rowIndex}][to_date]"></td>
        <td><input type="text" class="form-control total" name="items[${rowIndex}][total]" readonly></td>
        <td><button type="button" class="btn btn-danger btn-sm remove-row"><i class="fa fa-times"></i></button></td>
    </tr>`;
            $('#itemsTable tbody').append(newRow);
        });

        // Remove row
        $(document).on('click', '.remove-row', function () {
            if ($('#itemsTable tbody tr').length > 1) {
                $(this).closest('tr').remove();
            }
        });

        // Auto-calculate totals
        $(document).on('input', '.qty, .rate, .vat', function () {
            const row = $(this).closest('tr');
            const qty = parseFloat(row.find('.qty').val()) || 0;
            const rate = parseFloat(row.find('.rate').val()) || 0;
            const vat = parseFloat(row.find('.vat').val()) || 0;

            const subtotal = qty * rate;
            const total = subtotal + (subtotal * (vat / 100));
            row.find('.total').val(total.toFixed(2));

            updateGrandTotal();
        });

        function updateGrandTotal() {
            let grandTotal = 0;
            $('.total').each(function () {
                grandTotal += parseFloat($(this).val()) || 0;
            });
            $('.grand-total').val(grandTotal.toFixed(2));
        }
        $('#billForm').submit(function (e) {
            e.preventDefault();

            let formData = new FormData(this);
            console.log('--- FormData being sent ---');
            for (let [key, value] of formData.entries()) {
                if (value instanceof File) {
                    console.log(`${key}: [File] name=${value.name}, size=${value.size}`);
                } else {
                    console.log(`${key}: ${value}`);
                }
            }
            console.log('---------------------------');

            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    '<?= csrf_header() ?>': '<?= csrf_hash() ?>',
                },
                success: function (response) {
                    $('#billModal').modal('hide');
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                    if (response.success) {
                        tata.success('Purchase saved', response.message);
                        // Reload after 1.5 seconds
                    } else {
                        tata.error("Couldn't save purchase", response.message);
                    }
                },
                error: function (xhr) {
                    tata.error("Couldn't save purchase", xhr.responseJSON?.message || 'Something went wrong');
                }
            });
        });



        if (window.IpbFilters) {
            IpbFilters.restore({ storageKey: 'ipb_filters_bandwidth_purchess', root: '#filterForm' });
        }

        var table = $('.datatable').DataTable({
            ajax: {
                url: '<?= route_to("route.purchess.fetch"); ?>',
                type: 'post',
                data: function (d) {
                    // Pass filter data
                    d.reseller = $('#reseller').val();
                    d.status = $('#status').val();
                    d.fromDate = $('#fromDate').val();
                    d.toDate = $('#toDate').val();
                    d.<?= csrf_token() ?> = '<?= csrf_hash() ?>';
                },

            },
            columnDefs: [
                {
                    "targets": "_all",
                    "defaultContent": "-"
                }
            ],
        });

        var ipbBwPurchessFilters = null;
        if (window.IpbFilters) {
            ipbBwPurchessFilters = IpbFilters.bind(table, {
                storageKey: 'ipb_filters_bandwidth_purchess',
                root: '#filterForm',
                resetBtn: '#clearFilter',
                countBadge: '#bwPurchessCount',
            });
        }

        // Apply filter button click event
        $('#applyFilter').click(function () {
            table.ajax.reload();
            if (ipbBwPurchessFilters) ipbBwPurchessFilters.updateBadge();
        });

        // Clear filter button click event
        $('#clearFilter').click(function () {
            $('#filterForm')[0].reset();  // Reset form fields
            table.ajax.reload();  // Reload DataTable without filters
        });

        <?php if (userHasPermission('customer_payment', 'delete')): ?>
            //check all checkbox function
            $("#select_all").click(function () {

                if (this.checked) {

                    $("input:checkbox").each(function () {
                        this.checked = true;
                    });

                } else {

                    $("input:checkbox").each(function () {
                        this.checked = false;
                    });
                }
            });

            $(document).on("click", ".input-check-selected:checkbox", function () {

                if ($(".input-check-selected:checkbox:checked").length === $(".input-check-selected:checkbox").length) {

                    $("#select_all").prop("checked", true);

                } else {

                    $("#select_all").prop("checked", false);
                }
            });

            //Function for delete packages
            $(document).on('click', '.delete-btn', function () {

                swal({
                    title: "Confirmation",
                    text: "Are you sure you want to delete the selected records?",
                    dangerMode: true,
                    icon: 'warning',
                    buttons: ["No", {
                        text: "Yes",
                        closeModal: false,
                    }],
                }).then((willDelete) => {

                    if (willDelete) {

                        const selectedIds = $('.input-check-selected:checkbox:checked');

                        const ids = [];

                        $(selectedIds).each(function () {
                            ids.push($(this).val());
                        });

                        $.ajax({
                            url: '<?= route_to("bandwidth.purchess_delete"); ?>',
                            type: 'post',
                            data: {
                                ids
                            },
                            headers: {
                                '<?= csrf_header() ?>': '<?= csrf_hash() ?>',
                            },
                            success: function (result) {
                                swal.close();

                                if (result.status === 'success') {
                                    tata.success('Purchase records deleted', result.message);
                                } else {
                                    tata.error("Couldn't delete purchase records", result.message);
                                }

                                $('.datatable').DataTable().ajax.reload(null, false);
                            },

                            error: function (response) {
                                const result = jQuery.parseJSON(response.responseText);

                                swal.close();
                                tata.error("Couldn't delete purchase records", result.message);
                            }

                        });
                    }
                });
            });
        <?php endif; ?>

    })
</script>

<?= $this->endSection('script'); ?>"