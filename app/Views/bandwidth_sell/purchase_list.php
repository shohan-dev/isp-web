<?= $this->extend('layout/main-layout'); ?>

<?= $this->section('css'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/saas/bandwidth-pages.css?v=6'); ?>">
<?= $this->endSection('css'); ?>

<?= $this->section('content'); ?>
<div class="content-wrapper">
    <section class="content ipb-saas-list ipb-bw-page">

    <?= $this->include('components/page-header', [
      'title' => 'Sales Invoices',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Bandwidth Sell'],
        ['label' => 'Sales Invoices'],
      ],
    ]); ?>

<div class="box box-primary">

            <div class="box-header with-border ipb-box-toolbar">
        <div class="ipb-list-toolbar">
          <div class="ipb-list-toolbar-filters">
            <span class="ipb-filter-label"><i class="fa fa-file-invoice-dollar" aria-hidden="true"></i> Invoices</span>
          </div>
          <div class="ipb-list-toolbar-actions">
                    <button type="button" id="addRequisitionBtn" class="btn btn-primary">
                        <i class="fa fa-plus" aria-hidden="true"></i> New sale
                    </button>
          </div>
        </div>
      </div>

            <div class="ipb-bw-stats">
                <div class="ipb-bw-stat is-success">
                    <span>Total amount</span>
                    <strong id="totalAmount">৳0.00</strong>
                    <em>All invoices</em>
                </div>
                <div class="ipb-bw-stat is-info">
                    <span>Today</span>
                    <strong id="todayAmount">৳0.00</strong>
                    <em>Today&rsquo;s payments</em>
                </div>
                <div class="ipb-bw-stat is-brand">
                    <span>Filtered</span>
                    <strong id="filteredAmount">৳0.00</strong>
                    <em>Current filter total</em>
                </div>
            </div>

            <form id="filterForm" method="post" class="ipb-bw-filters is-dates">
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
                </div>
            </form>

            <div class="box-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="requisitionTable">
                        <caption class="sr-only">Sales invoices</caption>
                        <thead>
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Customer</th>
                                <th scope="col">Items</th>
                                <th scope="col">Requisition date</th>
                                <th scope="col">Deadline</th>
                                <th scope="col">Total</th>
                                <th scope="col">Received</th>
                                <th scope="col">Due</th>
                                <th scope="col">Status</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requisitions as $req): ?>
                                <tr>
                                    <td><?= esc($req['id']) ?></td>
                                    <td><strong><?= esc($req['vendor_suggestion']) ?></strong></td>
                                    <td><?= esc($req['item_count']) ?></td>
                                    <td><?= esc($req['requisition_date']) ?></td>
                                    <td><?= esc($req['deadline']) ?></td>
                                    <td><?= esc($req['total_amount']) ?></td>
                                    <td><?= esc($req['received_amount']) ?></td>
                                    <td><?= esc($req['due']) ?></td>
                                    <td>
                                        <?php if ($req['total_amount'] <= $req['received_amount']): ?>
                                            <span class="ipb-pay-badge is-success">Paid</span>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-primary btn-sm paymentBtn"
                                                data-id="<?= $req['id'] ?>"
                                                data-paid_by="<?= esc($req['paid_by']) ?>"
                                                data-received_by="<?= esc($req['received_by']) ?>"
                                                data-payment_method="<?= esc($req['payment_method']) ?>"
                                                data-remarks="<?= esc($req['remarks']) ?>"
                                                data-total="<?= $req['total_amount'] ?>"
                                                data-received="<?= $req['received_amount'] ?>"
                                                data-due="<?= $req['due'] ?>">
                                                Pay
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="ipb-row-actions">
                                            <button type="button" class="ipb-row-btn tone-info editRequisitionBtn" title="View"
                                                data-id="<?= $req['requisition_id'] ?>"
                                                data-view="true"><i class="fa fa-eye" aria-hidden="true"></i><span class="sr-only">View</span></button>
                                            <button type="button" class="ipb-row-btn tone-brand editRequisitionBtn" title="Edit"
                                                data-id="<?= $req['requisition_id'] ?>"><i class="fa fa-edit" aria-hidden="true"></i><span class="sr-only">Edit</span></button>
                                            <a href="<?= route_to('bandwidth_sell.requisition_delete', $req['requisition_id']) ?>"
                                                class="ipb-row-btn tone-danger"
                                                title="Delete"
                                                onclick="return confirm('Delete this requisition?')"><i
                                                    class="fa fa-trash" aria-hidden="true"></i><span class="sr-only">Delete</span></a>
                                            <?php if ($req['approved_by']): ?>
                                                <span class="ipb-row-btn tone-success" title="Approved"><i class="fa fa-check" aria-hidden="true"></i><span class="sr-only">Approved</span></span>
                                            <?php else: ?>
                                                <a href="<?= route_to('bandwidth.sell.purchase_list_invoice') ?>?id=<?= $req['requisition_id'] ?>" class="ipb-row-btn tone-violet" title="Invoice">
                                                    <i class="fa fa-cart-plus" aria-hidden="true"></i><span class="sr-only">Invoice</span>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="5" style="text-align:right;">Total:</th>
                                <th id="totalAmountFooter"></th>
                                <th colspan="4"></th>
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
                <h5 class="modal-title" id="requisitionModalLabel">Add requisition</h5>
                <button type="button" class="close modal-close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <form id="requisitionForm" action="<?= route_to('bandwidth_sell.requisition_create') ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="requisition_id" id="requisitionId">
                <input type="hidden" name="is_edit" id="isEdit" value="0">
                <input type="hidden" name="id" id="id">

                <div class="modal-body" id="requisitionModalBody">
                    <div class="ipb-req-grid">
                        <div class="ipb-req-field">
                            <label for="vendor_id">Customer</label>
                            <select name="vendor_id" id="vendor_id" class="form-control vendor-select">
                                <option value="">Select customer</option>
                                <?php foreach ($vendors as $vendor): ?>
                                    <option value="<?= esc($vendor['id']) ?>"><?= esc($vendor['customer_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
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
                                <caption class="sr-only">Requisition line items</caption>
                                <thead>
                                    <tr>
                                        <th scope="col">Item</th>
                                        <th scope="col">Description</th>
                                        <th scope="col">Unit</th>
                                        <th scope="col">Qty</th>
                                        <th scope="col">Rate</th>
                                        <th scope="col">From</th>
                                        <th scope="col">To</th>
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

<div class="modal fade" id="payModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document" style="max-width: 50vw; width: 100%; margin: 0 auto;">
        <div class="modal-content">
            <form id="paymentForm">
                <?= csrf_field() ?>
                <input type="hidden" id="requisition_id" name="requisition_id">
                <div class="modal-body p-5">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="payment_date">Payment Date</label>
                            <input type="date" class="form-control" id="payment_date" name="payment_date">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="total_amount">Total Amount</label>
                            <input type="number" class="form-control" id="total_amount" name="total_amount" readonly>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="vat_amount">VAT Amount</label>
                            <div class="form-check mb-2">
                                <input type="checkbox" class="form-check-input" id="vat_check">
                                <label class="form-check-label" for="vat_check">Do you want to apply VAT?</label>
                            </div>
                            <input type="number" class="form-control" id="vat_amount" name="vat_amount" value="0" disabled>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="received_amount">Received Amount</label>
                            <input type="number" class="form-control" id="received_amount" name="received_amount" step="0.01">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="net_amount">Net Due Amount</label>
                            <input type="text" class="form-control" id="net_amount" name="net_amount" readonly>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="discount">Discount</label>
                            <input type="number" class="form-control" id="discount" name="discount" value="0" step="0.01">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="payment_method">Payment Method</label>
                            <select class="form-control" id="payment_method" name="payment_method">
                                <option value="">Select</option>
                                <option value="cash">Cash</option>
                                <option value="bkash">bKash</option>
                                <option value="nagad">Nagad</option>
                                <option value="rocket">Rocket</option>
                                <option value="ssl">SSL</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="paid_by">Paid By</label>
                            <input type="text" class="form-control" id="paid_by" name="paid_by">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="received_by">Received By</label>
                            <input type="text" class="form-control" id="received_by" name="received_by">
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>


<?= $this->endSection(); ?>

<?= $this->section('script'); ?>
<style>
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        max-width: 100%;
    }

    #requisitionTable {
        min-width: 900px;
        table-layout: auto;
    }

    #requisitionTable th,
    #requisitionTable td {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    @media (max-width: 767px) {
        #requisitionTable {
            min-width: 720px;
        }
    }

    @media (max-width: 1400px) {
        .modal-xl {
            width: min(95%, calc(100vw - 16px));
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
    let filteredData = []; // Store filtered data globally
    let isFilterApplied = false; // Track filter state

    function calculateTodayAmount(data) {
        const today = new Date().toISOString().slice(0, 10);
        let todayTotal = 0;
        let total = 0;
        data.forEach(row => {
            const reqDate = row.requisition_date;
            total += parseFloat(row.total_amount || 0);
            if (reqDate === today) {
                todayTotal += parseFloat(row.total_amount || 0);
            }
        });

        // Update UI
        $('#todayAmount').text('৳' + todayTotal.toFixed(2));
        $('#totalAmount').text('৳' + total.toFixed(2));
    }

    // Function to reset form to initial state
    function resetForm() {
        $('#requisitionForm')[0].reset();
        $('#itemRows').empty();
        $('#requisitionId').val('');
        $('#id').val('');
        $('#displayRequisitionId').val('Auto Generated');
        $('#requisitionDate').val('<?= date('Y-m-d') ?>');
        $('#deadline').val('<?= date('Y-m-d') ?>');
        $('#grandTotal').val('0');
        $('#remarks').val('');
        $('#vendor_id').val('').trigger('change');

        // Enable all form fields (keep auto ID read-only)
        $('#requisitionForm input, #requisitionForm select, #requisitionForm textarea').prop('disabled', false);
        $('#displayRequisitionId').prop('disabled', true);
        $('#grandTotal').prop('disabled', true);
        $('#requisitionSaveBtn').show();
        $('#addRowBtn').show();
        $('#requisitionModal').removeClass('is-view-mode');

        // Add one empty row
        addNewRow(0);
    }

    // Function to add a new row (with optional data for editing)
    // Function to add a new row (with optional data for editing)
    function addNewRow(index, itemData = null) {
        const itemId = itemData && itemData.id ? itemData.id : '';

        let row = $(`
    <tr class="item-row">
        <td>
            <select class="form-control item-select" name="items[${index}][item]">
                <option value="">Select item</option>
                <?php foreach ($items as $item): ?>
                    <option value="<?= htmlspecialchars($item['item_name']) ?>"
                        data-description="<?= htmlspecialchars($item['description'] ?? '') ?>"
                        data-unit="<?= htmlspecialchars($item['unit'] ?? '') ?>"
                        data-vat="<?= htmlspecialchars($item['vat'] ?? '') ?>"
                        data-id_id="<?= htmlspecialchars($item['id_id'] ?? $item['id'] ?? '') ?>"
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
            <input type="text" class="form-control" name="items[${index}][description]" placeholder="Description" value="${itemData && itemData.description ? itemData.description : ''}">
        </td>
        <td>
            <input type="text" class="form-control unit-text" name="items[${index}][unit]" placeholder="Unit" value="${itemData && itemData.unit ? itemData.unit : ''}">
        </td>
        <td><input type="number" class="form-control qty" name="items[${index}][qty]" value="${itemData && itemData.quantity ? itemData.quantity : 0}" step="0.01"></td>
        <td><input type="number" class="form-control rate" name="items[${index}][rate]" value="${itemData && itemData.rate ? itemData.rate : 0}" step="0.01"></td>
        <td><input type="date" class="form-control from-date" name="items[${index}][from_date]" value="${itemData && itemData.from_date ? itemData.from_date : ''}"></td>
        <td><input type="date" class="form-control to-date" name="items[${index}][to_date]" value="${itemData && itemData.to_date ? itemData.to_date : ''}"></td>
        <td><input type="number" class="form-control row-total" value="${itemData && itemData.quantity && itemData.rate ? (itemData.quantity * itemData.rate).toFixed(2) : '0.00'}" readonly></td>
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
        }
    }
    // Function to reindex rows after deletion
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

    // Function to calculate row total
    function calculateRowTotal(row) {
        const qty = parseFloat(row.find('.qty').val()) || 0;
        const rate = parseFloat(row.find('.rate').val()) || 0;
        const total = qty * rate;
        row.find('.row-total').val(total.toFixed(2));
        calculateGrandTotal();
    }

    // Function to calculate grand total
    function calculateGrandTotal() {
        let grandTotal = 0;
        $('.row-total').each(function() {
            grandTotal += parseFloat($(this).val()) || 0;
        });
        $('#grandTotal').val(grandTotal.toFixed(2));
    }

    // Function to populate form with data
    function populateForm(data) {
        console.log('Populating form with data:', data);

        $('#id').val(data.id || '');
        $('#vendor_id').val(data.vendor_id).trigger('change');
        $('#requisitionId').val(data.requisition_id);
        $('#displayRequisitionId').val(data.requisition_id);
        $('#requisitionDate').val(data.requisition_date);
        $('#deadline').val(data.deadline);
        $('#remarks').val(data.remarks || '');

        // Clear existing rows
        $('#itemRows').empty();

        // Add rows for each item
        if (data.items && data.items.length > 0) {
            data.items.forEach((item, index) => {
                addNewRow(index, item);
            });
        } else {
            // Add one empty row if no items
            addNewRow(0);
        }

        // Update grand total
        $('#grandTotal').val(data.total || '0');
    }

    $(document).ready(function() {
        // Rehydrate the date filters across a reload (sessionStorage). The
        // Apply click below is NOT auto-triggered here — restoring only the
        // input values, not re-running the filtered fetch, matches this
        // screen's existing "must click Apply" behavior exactly.
        if (window.sessionStorage) {
            try {
                var storedBwSellFilters = sessionStorage.getItem('ipb_filters_bandwidth_sell_purchase_list');
                if (storedBwSellFilters) {
                    var parsedBwSellFilters = JSON.parse(storedBwSellFilters);
                    if (parsedBwSellFilters.fromDate) $('#fromDate').val(parsedBwSellFilters.fromDate);
                    if (parsedBwSellFilters.toDate) $('#toDate').val(parsedBwSellFilters.toDate);
                }
            } catch (e) { /* corrupt/absent storage — ignore */ }
        }

        // Initialize DataTable with the original data
        const tableData = [];
        $('#requisitionTable tbody tr').each(function() {
            const row = {
                id: $(this).find('td:eq(0)').text().trim(),
                vendor_suggestion: $(this).find('td:eq(1)').text().trim(),
                item_count: $(this).find('td:eq(2)').text().trim(),
                requisition_date: $(this).find('td:eq(3)').text().trim(),
                deadline: $(this).find('td:eq(4)').text().trim(),
                total_amount: $(this).find('td:eq(5)').text().trim(),
                received_amount: $(this).find('td:eq(6)').text().trim(),
                due: $(this).find('td:eq(7)').text().trim(),
                requisition_id: $(this).find('.editRequisitionBtn').first().data('id'),
                paid_by: $(this).find('.paymentBtn').data('paid_by') || '',
                received_by: $(this).find('.paymentBtn').data('received_by') || '',
                payment_method: $(this).find('.paymentBtn').data('payment_method') || '',
                remarks: $(this).find('.paymentBtn').data('remarks') || '',
                approved_by: $(this).find('.btn-success .fa-check').length > 0
            };
            tableData.push(row);
        });

        // Initialize with original data
        filteredData = [...tableData];
        calculateTodayAmount(filteredData);

        const initDataTable = (data) => {
            calculateTodayAmount(data);

            if ($.fn.DataTable.isDataTable('#requisitionTable')) {
                $('#requisitionTable').DataTable().destroy();
            }

            return $('#requisitionTable').DataTable({
                data: data,
                processing: false,
                serverSide: false,
                columns: [{
                        data: 'id'
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            if (isFilterApplied && row.vendor_suggestion_name) {
                                return row.vendor_suggestion_name;
                            } else if (row.vendor_suggestion) {
                                return row.vendor_suggestion;
                            }
                            return '';
                        }
                    },
                    {
                        data: 'item_count'
                    },
                    {
                        data: 'requisition_date'
                    },
                    {
                        data: 'deadline'
                    },
                    {
                        data: 'total_amount'
                    },
                    {
                        data: 'received_amount'
                    },
                    {
                        data: 'due'
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            if (parseFloat(row.total_amount) <= parseFloat(row.received_amount)) {
                                return '<span class="ipb-pay-badge is-success">Paid</span>';
                            }
                            return `<button type="button" class="btn btn-primary btn-sm paymentBtn"
                                data-id="${row.requisition_id || row.id}"
                                data-paid_by="${row.paid_by || ''}"
                                data-received_by="${row.received_by || ''}"
                                data-payment_method="${row.payment_method || ''}"
                                data-remarks="${row.remarks || ''}"
                                data-total="${row.total_amount}"
                                data-received="${row.received_amount}"
                                data-due="${row.due}">Pay</button>`;
                        }
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            const id = row.requisition_id || row.id;
                            const deleteUrl = `<?= base_url() ?>bandwidth_sell/delete/${id}`;
                            const invoiceUrl = `<?= route_to('bandwidth.sell.purchase_list_invoice') ?>?id=${id}`;
                            const extra = row.approved_by
                                ? '<span class="ipb-row-btn tone-success" title="Approved"><i class="fa fa-check" aria-hidden="true"></i></span>'
                                : `<a href="${invoiceUrl}" class="ipb-row-btn tone-violet" title="Invoice"><i class="fa fa-cart-plus" aria-hidden="true"></i></a>`;
                            return `<div class="ipb-row-actions">
                            <button type="button" class="ipb-row-btn tone-info editRequisitionBtn" title="View" data-id="${id}" data-view="true"><i class="fa fa-eye" aria-hidden="true"></i></button>
                            <button type="button" class="ipb-row-btn tone-brand editRequisitionBtn" title="Edit" data-id="${id}"><i class="fa fa-edit" aria-hidden="true"></i></button>
                            <a href="${deleteUrl}" class="ipb-row-btn tone-danger" title="Delete" onclick="return confirm('Delete this requisition?')"><i class="fa fa-trash" aria-hidden="true"></i></a>
                            ${extra}
                            </div>`;
                        }
                    }
                ],
                scrollX: true,
                scrollY: "55vh",
                scrollCollapse: true,
                language: {
                    searchPlaceholder: "Search...",
                    lengthMenu: "SHOW _MENU_ ENTRIES"
                },
                order: [
                    [3, 'desc']
                ],
                dom: '<"row"<"col-sm-6"l><"col-sm-6 text-right"f>>rt<"row"<"col-sm-6"i><"col-sm-6 text-right"p>>',
                footerCallback: function(row, data, start, end, display) {
                    var api = this.api();
                    var total = api
                        .column(5, {
                            search: 'applied'
                        })
                        .data()
                        .reduce(function(a, b) {
                            return parseFloat(a) + parseFloat(b);
                        }, 0);
                    $(api.column(5).footer()).html(total.toFixed(2));
                }
            });
        };

        let dataTable = initDataTable(filteredData);

        // Update filter function
        $('#applyFilter').click(function() {
            const fromDate = $('#fromDate').val();
            const toDate = $('#toDate').val();

            if (window.sessionStorage) {
                try {
                    sessionStorage.setItem('ipb_filters_bandwidth_sell_purchase_list', JSON.stringify({ fromDate: fromDate, toDate: toDate }));
                } catch (e) { /* quota / private mode */ }
            }

            var $applyBtn = $(this);
            var $tableNode = $('#requisitionTable');
            var $wrap = $tableNode.closest('.dataTables_wrapper');
            if (!$wrap.length) $wrap = $tableNode.closest('.table-responsive, .box-body, .box');

            $.ajax({
                url: "<?= route_to('bandwidth_sell.filter_invoices') ?>",
                type: 'POST',
                data: {
                    fromDate: fromDate,
                    toDate: toDate,
                    <?= csrf_token() ?>: "<?= csrf_hash() ?>"
                },
                dataType: 'json',
                beforeSend: function() {
                    $wrap.addClass('is-loading');
                    $applyBtn.prop('disabled', true);
                },
                success: function(response) {
                    if (response.status === 'success') {
                        filteredData = response.data;
                        isFilterApplied = true;
                        dataTable.destroy();
                        dataTable = initDataTable(filteredData);
                        calculateTodayAmount(filteredData);
                        $('#totalAmount').text('৳' + response.totalAmount);
                        $('#filteredAmount').text('৳' + response.totalAmount);
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('Error loading filtered data');
                },
                complete: function() {
                    $wrap.removeClass('is-loading');
                    $applyBtn.prop('disabled', false);
                }
            });
        });

        // Clear filter function
        $('#clearFilter').click(function() {
            $('#fromDate').val('');
            $('#toDate').val('');
            if (window.sessionStorage) {
                try { sessionStorage.removeItem('ipb_filters_bandwidth_sell_purchase_list'); } catch (e) { /* ignore */ }
            }
            isFilterApplied = false;
            filteredData = [...tableData];
            calculateTodayAmount(filteredData);
            dataTable.destroy();
            dataTable = initDataTable(filteredData);

            const initialTotal = <?= array_sum(array_column($requisitions, 'total_amount')) ?>;
            $('#totalAmount').text('৳' + initialTotal.toFixed(2));
            $('#filteredAmount').text('৳' + initialTotal.toFixed(2));
        });

        // Add Requisition Button Click (Purchess button)
        $('#addRequisitionBtn').click(function() {
            resetForm();
            $('#requisitionModalLabel').text('Add requisition');
            $('#isEdit').val('0');
            $('#requisitionModal').removeClass('is-view-mode');
            $('#requisitionModal').modal('show');
        });

        // Edit / View requisition
        $(document).on('click', '.editRequisitionBtn', function(e) {
            e.preventDefault();
            const id = $(this).data('id');
            const isView = $(this).data('view') === true || $(this).data('view') === "true";

            $('#requisitionModalLabel').text(isView ? 'View requisition' : 'Edit requisition');
            $('#isEdit').val('1');

            $.ajax({
                url: "<?= route_to('bandwidth_sell.requisition_get') ?>",
                type: 'GET',
                data: {
                    id: id
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        populateForm(response.data);

                        if (isView) {
                            $('#requisitionForm input, #requisitionForm select, #requisitionForm textarea').prop('disabled', true);
                            $('#requisitionSaveBtn').hide();
                            $('#addRowBtn').hide();
                            $('#requisitionModal').addClass('is-view-mode');
                        } else {
                            $('#requisitionForm input, #requisitionForm select, #requisitionForm textarea').prop('disabled', false);
                            $('#displayRequisitionId').prop('disabled', true);
                            $('#grandTotal').prop('disabled', true);
                            $('#requisitionSaveBtn').show();
                            $('#addRowBtn').show();
                            $('#requisitionModal').removeClass('is-view-mode');
                        }

                        $('#requisitionModal').modal('show');
                    } else {
                        alert('Error loading requisition: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    console.error('Response:', xhr.responseText);
                    alert('Error loading requisition data. Please check the console for details.');
                }
            });
        });

        // Payment button click handler
        // Payment button click handler
        $(document).on('click', '.paymentBtn', function() {
            const requisitionId = $(this).data('id'); // This is the requisition_id
            const total = $(this).data('total');
            const received = $(this).data('received');
            const due = total - received || 0;
            const paidBy = $(this).data('paid_by') || '';
            const receivedBy = $(this).data('received_by') || '';
            const paymentMethod = $(this).data('payment_method') || '';
            const remarks = $(this).data('remarks') || '';

            console.log('Payment button clicked - Requisition ID:', requisitionId);

            // Set values in modal
            $('#requisition_id').val(requisitionId);
            $('#payment_date').val(new Date().toISOString().split('T')[0]);
            $('#received_amount').val(received);
            $('#total_amount').val(total);
            $('#net_amount').val(due.toFixed(2));
            $('#paid_by').val(paidBy);
            $('#received_by').val(receivedBy);
            $('#payment_method').val(paymentMethod);
            $('#description').val(remarks);

            $('#payModal').modal('show');
        });

        // Calculate net amount for payment
        function calculateNetAmount() {
            const received = parseFloat($('#received_amount').val()) || 0;
            const vat = parseFloat($('#vat_amount').val()) || 0;
            const discount = parseFloat($('#discount').val()) || 0;
            const total = parseFloat($('#total_amount').val()) || 0;
            const net = total - received + vat - discount;
            $('#net_amount').val(net.toFixed(2));
        }

        // Bind calculation events
        $('#received_amount, #vat_amount, #discount').on('input', calculateNetAmount);

        // VAT checkbox handler
        $('#vat_check').change(function() {
            if ($(this).is(':checked')) {
                $('#vat_amount').prop('disabled', false);
            } else {
                $('#vat_amount').val(0).prop('disabled', true);
            }
            calculateNetAmount();
        });

        // Payment form submission
        // Payment form submission
        $(document).on('submit', '#paymentForm', function(e) {
            e.preventDefault();

            const formData = $(this).serialize();
            const received = parseFloat($('#received_amount').val());
            const total = parseFloat($('#total_amount').val());
            const requisitionId = $('#requisition_id').val();

            console.log('Submitting payment for requisition_id:', requisitionId);
            console.log('Form data:', formData);

            if (received > total) {
                alert('Received amount cannot exceed total amount!');
                return;
            }

            $.ajax({
                url: "<?= route_to('bandwidth_sell_payment_update.requisition_update') ?>",
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    console.log('Payment update response:', response);
                    if (response.status === 'success') {
                        alert('Payment saved successfully!');
                        $('#payModal').modal('hide');
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function(xhr) {
                    console.error('Payment update error:', xhr.responseText);
                    alert('Error processing payment');
                }
            });
        });

        // Item selection change
        $(document).on('change', '.item-select', function() {
            const selectedOption = $(this).find('option:selected');
            const description = selectedOption.data('description') || '';
            const unit = selectedOption.data('unit') || '';
            const id_id = selectedOption.data('id_id') || '';
            const category = selectedOption.data('category') || '';
            const subcategory = selectedOption.data('subcategory') || '';

            const row = $(this).closest('tr');
            row.find('input[name*="[description]"]').val(description);
            row.find('.unit-text').val(unit);
            row.find('input[name*="[category_id]"]').val(category);
            row.find('input[name*="[id_id]"]').val(id_id);
            row.find('input[name*="[subcategory_id]"]').val(subcategory);
        });

        // Trigger calculations on quantity/rate change
        $(document).on('input', '.qty, .rate', function() {
            calculateRowTotal($(this).closest('tr'));
        });

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

        // Form submission handling
        $(document).on('submit', '#requisitionForm', function(e) {
            e.preventDefault();
            const form = $(this);
            const isEdit = $('#isEdit').val() === '1';
            const formData = form.serialize();

            $.ajax({
                url: isEdit ? "<?= route_to('bandwidth_sell.requisition_update') ?>" : form.attr('action'),
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
                        alert('Error: ' + (json.message || 'Unknown error'));
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