<?= $this->extend('layout/main-layout'); ?>
<?= $this->section('content'); ?>
<div class="content-wrapper">
    <section class="content ipb-saas-list">
        
    <?= $this->include('components/page-header', [
      'title' => 'Bandwidth Daily Bill All Daily Bills',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Bandwidth Daily Bill All Daily Bills'],
      ],
    ]); ?>

<div class="box box-primary">
            <div class="box-header with-border ipb-box-toolbar">
        <div class="ipb-list-toolbar">
          <div class="ipb-list-toolbar-filters">
            <span class="ipb-filter-label"><i class="fa fa-list" aria-hidden="true"></i> Records</span>
          </div>
          <div class="ipb-list-toolbar-actions">
<button id="addReceiveBillBtn" class="btn btn-primary">
                        <i class="fa fa-plus"></i> Receive Bill
                    </button>
          </div>
        </div>
      </div>

            <!-- Filter Form -->
            <div class="box-header with-border">
                <form id="filterForm" class="row g-3 mb-3">
                    <div class="col-md-2">
                        <label>POP</label>
                        <select name="pop" class="form-control ipb-filter-select">
                              <option value="">-- Select POP --</option>
                            <?php foreach ($clients as $client): ?>
                                <option value="<?= esc($client['id']) ?>">
                                    <?= esc($client['id']) ?> ( <?= esc($client['customer_name']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>From Date</label>
                        <input type="date" name="from_date" class="form-control ipb-filter-date" value="<?= date('Y-m-d'); ?>">
                    </div>
                    <div class="col-md-2">
                        <label>To Date</label>
                        <input type="date" name="to_date" class="form-control ipb-filter-date" value="<?= date('Y-m-d'); ?>">
                    </div>
                    <div class="col-md-2">
                        <label>Received By</label>
                        <input type="text" name="received_by" class="form-control ipb-filter-text" value="">

                    </div>
                    <div class="col-md-2">
                        <label>Created By</label>
                        <input type="text" name="created_by" class="form-control ipb-filter-text" value="">

                    </div>
                    <div class="col-md-2">
                        <label>Transaction Status</label>
                        <select name="status" class="form-control ipb-filter-select">
                            <option value="">select</option>
                            <option value="paid">Approved</option>
                            <option value="due">Pending</option>
                        </select>
                    </div>
                    <div class="pull-right" style="margin-top: 15px; margin-right: 15px;">
                        <button type="button" id="clearFilter" class="btn btn-danger">Clear Filter</button>
                        <button type="submit" class="btn btn-info">Apply Filter</button>
                    </div>

                </form>
            </div>
            <!-- Table -->
            <div class="box-header with-border">
                <div class="table-responsive">
                    <table class="table table-bordered" id="dailyBillTable">
                        <caption class="sr-only">Bandwidth daily bills</caption>
                        <thead>
                            <tr>
                                <th style="width: 200px; " scope="col">Invoice Date</th>
                                <th scope="col">CompanyName</th>
                                <th scope="col">ContactPerson</th>
                                <th scope="col">Mobile No.</th>
                                <th scope="col">InvoiceNo.</th>
                                <th scope="col">Bill.Month</th>
                                <th scope="col">BillAmount</th>
                                <th scope="col">Received</th>
                                <th scope="col">Discount</th>
                                <th scope="col">BalanceDue</th>
                                <th scope="col">ReceivedBy</th>
                                <th scope="col">CreatedBy</th>
                                <th scope="col">CreatedOn</th>
                                <th scope="col">Note/Remarks</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <?php
                          $dailyBillSkeletonCols = 15;
                        ?>
                        <?= view('components/skeleton-table', ['cols' => $dailyBillSkeletonCols, 'rows' => 8]) ?>
                    </table>
                </div>
            </div>

        </div>
    </section>
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


<style>
    #dailyBillTable th:first-child,
    #dailyBillTable td:first-child {
        width: 250px;
        overflow: hidden;
        text-overflow: ellipsis;
        /* nice for overflow text */
        white-space: nowrap;
    }
</style>

<?= $this->endSection(); ?>
<?= $this->section('script'); ?>

<!-- AJAX Script -->
<script>
    $(document).ready(function() {
        // Rehydrate filters across a reload (sessionStorage), before the
        // DataTable's first ajax load reads them via the submit handler.
        if (window.IpbFilters) {
            IpbFilters.restore({ storageKey: 'ipb_filters_bandwidth_daily_bill', root: '#filterForm' });
        }

        let dailyBillTable = $('#dailyBillTable').DataTable({
            processing: false,
            serverSide: false, // You can make it true if you want CI to handle paging/filtering
            ajax: {
                url: "<?= route_to('bandwidth.dailyBillData'); ?>",
                type: "GET",
                dataSrc: ""
            },
            columns: [{
                    data: 'requisition_date',
                    defaultContent: '',

                },
                {
                    data: 'vendor_suggestion_name',
                    defaultContent: ''
                },
                {
                    data: 'vendor_suggestion_contact_person',
                    defaultContent: ''
                },
                {
                    data: 'vendor_suggestion_mobile',
                    defaultContent: ''
                },
                {
                    data: 'requisition_id',
                    defaultContent: ''
                },
                {
                    data: 'bill_month',
                    defaultContent: ''
                },
                {
                    data: 'total_amount',
                    defaultContent: '0.00'
                },
                {
                    data: 'received_amount',
                    defaultContent: '0.00'
                },
                {
                    data: 'discount',
                    defaultContent: '0.00'
                },
                {
                    data: 'due',
                    defaultContent: '0.00'
                },
                {
                    data: 'received_by',
                    defaultContent: ''
                },
                {
                    data: 'created_by',
                    defaultContent: ''
                },
                {
                    data: 'created_at',
                    defaultContent: ''
                },
                {
                    data: 'remarks',
                    defaultContent: ''
                },
                {
                    data: null,
                    render: function() {
                        return `<button class="btn btn-sm btn-primary">View</button>`;
                    }
                }
            ],
            columnDefs: [{
                    width: '250px',
                    targets: 0
                } // first column only
            ],
            scrollX: true,
            scrollY: "55vh",
            scrollCollapse: true,
            language: {
                searchPlaceholder: "Search...",
                lengthMenu: "SHOW _MENU_ ENTRIES"
            },
            order: [
                [4, 'desc']
            ]
        });



        // Example: reload table when filters change
        $('#filterForm').on('submit', function(e) {
            e.preventDefault();
            let filters = $(this).serializeArray();
            let params = {};
            filters.forEach(f => params[f.name] = f.value);
            if (window.sessionStorage) {
                try { sessionStorage.setItem('ipb_filters_bandwidth_daily_bill', JSON.stringify(params)); } catch (e) { /* quota / private mode */ }
            }
            dailyBillTable.ajax.url("<?= route_to('bandwidth.dailyBillData'); ?>?" + $.param(params)).load();
        });

        // Clear Filter
        $("#clearFilter").on("click", function() {
            $("#filterForm")[0].reset();
            if (window.sessionStorage) {
                try { sessionStorage.removeItem('ipb_filters_bandwidth_daily_bill'); } catch (e) { /* ignore */ }
            }
            // loadDailyBills() was called here but never defined anywhere in
            // this file (pre-existing bug — Clear Filter silently did
            // nothing beyond resetting the form). Reset the ajax URL back to
            // its unfiltered base and reload, matching the submit handler above.
            dailyBillTable.ajax.url("<?= route_to('bandwidth.dailyBillData'); ?>").load();
        });

    });
</script>
<?= $this->endSection(); ?>