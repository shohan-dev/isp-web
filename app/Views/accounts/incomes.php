<?= $this->extend('layout/main-layout'); ?>
<?php $this->section('needsDataTable'); ?>1<?php $this->endSection(); ?>

<?= $this->section('css'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/saas/accounts-pages.css?v=2'); ?>">
<?= $this->endSection('css'); ?>

<?= $this->section('content'); ?>

<div class="content-wrapper">
    <section class="content ipb-saas-list ipb-acc-page">

    <?= $this->include('components/page-header', [
      'title' => 'Incomes',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Accounting'],
        ['label' => 'Incomes'],
      ],
    ]); ?>

<div class="box box-primary">
            <div class="box-header with-border ipb-box-toolbar">
        <?php
          ob_start();
        ?>
                    <button type="button" onclick="showIncomeCategoryModal()" class="btn btn-default">
                        <i class="fa fa-tags" aria-hidden="true"></i> Categories
                    </button>
                    <button type="button" onclick="showIncomeModal()" class="btn btn-primary">
                        <i class="fa fa-plus" aria-hidden="true"></i> New income
                    </button>
        <?php
          $incomeToolbarActionsHtml = ob_get_clean();
        ?>
        <?= view('components/list-toolbar', [
          'filters' => [],
          'actionsHtml' => $incomeToolbarActionsHtml,
          'filterLabel' => 'Income records',
          'showReset' => false,
          'showCount' => false,
        ]); ?>
      </div>

            <!-- ================= Income List Table ================= -->
            <div class="box-body table-responsive">
                <table id="incomeTable" class="table table-bordered table-striped">
                    <caption class="sr-only">Income list</caption>
                    <thead>
                        <tr>
                            <th scope="col" style="width: 35px;">
                                <input type="checkbox" id="selectAll">
                            </th>
                            <th scope="col" style="width: 50px;">ID</th>
                            <th scope="col" style="width: 80px;">Status</th>
                            <th scope="col" style="width: 100px;">Name</th>
                            <th scope="col" style="width: 120px;">Income Category</th>
                            <th scope="col" style="width: 100px;">Employee</th>
                            <th scope="col" style="width: 100px;">Invoice #</th>
                            <th scope="col" style="width: 85px;">Date</th>
                            <th scope="col" style="width: 95px;">Amount</th>
                            <th scope="col" style="width: 120px;">Bank Account</th>
                            <th scope="col" style="width: 85px;">Docs</th>
                            <th scope="col" style="width: 150px;">Description</th>
                            <th scope="col" style="width: 90px;">Created By</th>
                            <th scope="col" style="width: 90px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($incomes)) : ?>
                            <?php foreach ($incomes as $key => $row) : ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" class="rowCheckbox" value="<?= esc($row['id']) ?>">
                                    </td>
                                    <td><?= esc($row['id']) ?></td>
                                    <td class="ipb-acc-status">
                                        <?php if ($row['status'] == 'approved') : ?>
                                            <span class="ipb-pay-badge is-success">Approved</span>
                                        <?php elseif ($row['status'] == 'pending') : ?>
                                            <span class="ipb-pay-badge is-warning">Pending</span>
                                        <?php elseif ($row['status'] == 'rejected') : ?>
                                            <span class="ipb-pay-badge is-danger">Rejected</span>
                                        <?php else : ?>
                                            <span class="ipb-pay-badge is-info"><?= esc($row['status']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= esc($row['name']) ?></td>
                                    <td><?= esc($row['income_category']) ?></td>
                                    <td><?= esc($row['employee'] ?? '--') ?></td>
                                    <td><?= esc($row['invoice_no'] ?? '--') ?></td>
                                    <td class="ipb-acc-nowrap"><?= date('d/m/Y', strtotime($row['date'])) ?></td>
                                    <td class="ipb-acc-nowrap"><?= number_format($row['amount'], 2) ?></td>
                                    <td><?= esc($row['bank_account'] ?? '--') ?></td>
                                    <td>
                                        <?php if (!empty($row['document'])) : ?>
                                            <button type="button" class="ipb-row-btn tone-info" title="View file" onclick="viewFile('<?= esc($row['document'], 'attr') ?>')">
                                                <i class="fa fa-file" aria-hidden="true"></i><span class="sr-only">View file</span>
                                            </button>
                                        <?php else : ?>
                                            <span class="text-muted">â€”</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= esc($row['description']) ?></td>
                                    <td><?= esc($row['created_by']) ?></td>
                                    <td class="ipb-acc-actions">
                                        <div class="ipb-row-actions">
                                        <?php if ($row['status'] != 'approved') : ?>
                                            <button type="button" class="ipb-row-btn tone-success" title="Approve" onclick="approveIncome(<?= (int) $row['id'] ?>)">
                                                <i class="fa fa-check" aria-hidden="true"></i><span class="sr-only">Approve</span>
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($row['status'] != 'rejected') : ?>
                                            <button type="button" class="ipb-row-btn tone-danger" title="Reject" onclick="rejectIncome(<?= (int) $row['id'] ?>)">
                                                <i class="fa fa-times" aria-hidden="true"></i><span class="sr-only">Reject</span>
                                            </button>
                                        <?php endif; ?>
                                            <button type="button" class="ipb-row-btn tone-brand" title="Edit" onclick="editIncome(<?= (int) $row['id'] ?>)">
                                                <i class="fa fa-edit" aria-hidden="true"></i><span class="sr-only">Edit</span>
                                            </button>
                                            <button type="button" class="ipb-row-btn tone-danger" title="Delete" onclick="deleteIncome(<?= (int) $row['id'] ?>)">
                                                <i class="fa fa-trash" aria-hidden="true"></i><span class="sr-only">Delete</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ================= Income Category Management Modal ================= -->
        <div class="modal fade" id="incomeCategoryModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title">Manage Income Categories</h4>
                    </div>
                    <div class="modal-body">
                        <!-- Add New Income Category Form -->
                        <div class="box box-info">
                            <div class="box-header">
                                <h3 class="box-title">Add New Income Category</h3>
                            </div>
                            <div class="box-body">
                                <form id="incomeCategoryForm">
                                    <?= csrf_field(); ?>
                                    <input type="hidden" name="category_id" id="income_category_id">
                                    <div class="form-group">
                                        <label>Name *</label>
                                        <input type="text" name="name" id="income_category_name" class="form-control" placeholder="e.g. Sales, Commission, Salary" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary" id="saveCategoryBtn">
                                        <i class="fa fa-save"></i> Save Category
                                    </button>
                                    <button type="button" class="btn btn-default" onclick="resetIncomeCategoryForm()">Reset</button>
                                </form>
                            </div>
                        </div>

                        <!-- Income Categories List -->
                        <div class="box">
                            <div class="box-header">
                                <h3 class="box-title">Existing Income Categories</h3>
                            </div>
                            <div class="box-body table-responsive">
                                <table class="table table-bordered table-striped" id="incomeCategoryTable">
                                    <caption class="sr-only">Income categories</caption>
                                    <thead>
                                        <tr>
                                            <th scope="col">ID</th>
                                            <th scope="col">Name</th>
                                            <th scope="col">Created At</th>
                                            <th scope="col">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($income_categories)) : ?>
                                            <?php foreach ($income_categories as $category) : ?>
                                                <tr>
                                                    <td><?= esc($category['id']) ?></td>
                                                    <td><?= esc($category['name']) ?></td>
                                                    <td><?= date('d/m/Y H:i', strtotime($category['created_at'] ?? date('Y-m-d H:i:s'))) ?></td>
                                                    <td>
                                                        <div class="ipb-row-actions">
                                                        <button type="button" class="ipb-row-btn tone-brand" title="Edit" aria-label="Edit income category" onclick="editIncomeCategory(<?= (int) $category['id'] ?>, '<?= esc($category['name'], 'js') ?>')">
                                                            <i class="fa fa-edit" aria-hidden="true"></i>
                                                        </button>
                                                        <button type="button" class="ipb-row-btn tone-danger" title="Delete" aria-label="Delete income category" onclick="deleteIncomeCategory(<?= (int) $category['id'] ?>)">
                                                            <i class="fa fa-trash" aria-hidden="true"></i>
                                                        </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================= Add/Edit Income Modal ================= -->
        <div class="modal fade" id="incomeModal" tabindex="-1" role="dialog">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title">Add New Income</h4>
                    </div>
                    <div class="modal-body">
                        <form id="incomeForm" enctype="multipart/form-data">
                            <?= csrf_field(); ?>
                            <input type="hidden" name="income_id" id="income_id">

                            <div class="form-group">
                                <label>Income Title *</label>
                                <input type="text" name="name" id="income_name" class="form-control" placeholder="Enter Name" required>
                            </div>

                            <div class="form-group">
                                <label>Income Category *</label>
                                <select name="income_category" id="income_category" class="form-control" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($income_categories ?? [] as $category): ?>
                                        <option value="<?= esc($category['name']) ?>">
                                            <?= esc($category['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small><a href="javascript:void(0)" onclick="showIncomeCategoryModal()">+ Add New Income Category</a></small>
                            </div>

                            <div class="form-group">
                                <label>Employee</label>
                                <select name="employee" id="employee" class="form-control">
                                    <option value="">Select Employee</option>
                                    <?php foreach ($employees ?? [] as $employee): ?>
                                        <option value="<?= esc($employee->id) ?>">
                                            <?= esc($employee->name) ?> (<?= esc($employee->mobile ?? '') ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Invoice Number</label>
                                <input type="text" name="invoice_no" id="invoice_no" class="form-control" placeholder="Enter Invoice Number">
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Date *</label>
                                        <input type="date" name="date" id="income_date" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Amount *</label>
                                        <input type="number" step="0.01" name="amount" id="income_amount" class="form-control" placeholder="Enter Amount" required>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Attach Document</label>
                                <input type="file" name="document" id="document" class="form-control">
                                <div id="current_document" style="display:none; margin-top:5px;">
                                    <label>Current Document:</label>
                                    <button type="button" class="btn btn-xs btn-info" onclick="viewFile($('#existing_document_name').val())">
                                        <i class="fa fa-eye"></i> View Current Document
                                    </button>
                                    <input type="hidden" id="existing_document_name" value="">
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Payment Method *</label>
                                <select name="bank_account" id="bank_account" class="form-control" required>
                                    <option value="">Select Payment Method</option>
                                    <optgroup label="Cash">
                                        <option value="Cash">ðŸ’° Cash</option>
                                    </optgroup>
                                    <optgroup label="Mobile Banking">
                                        <option value="bKash">ðŸ“± bKash</option>
                                        <option value="Rocket">ðŸš€ Rocket</option>
                                        <option value="Nagad">ðŸ’³ Nagad</option>
                                        <option value="Upay">ðŸ“² Upay</option>
                                        <option value="Tap">ðŸ“± Tap</option>
                                    </optgroup>
                                    <optgroup label="Banks">
                                        <option value="Dutch Bangla Bank">ðŸ¦ Dutch Bangla Bank</option>
                                        <option value="Sonali Bank">ðŸ¦ Sonali Bank</option>
                                        <option value="Janata Bank">ðŸ¦ Janata Bank</option>
                                        <option value="Agrani Bank">ðŸ¦ Agrani Bank</option>
                                        <option value="Rupali Bank">ðŸ¦ Rupali Bank</option>
                                        <option value="Islami Bank">ðŸ¦ Islami Bank</option>
                                        <option value="City Bank">ðŸ¦ City Bank</option>
                                        <option value="Eastern Bank">ðŸ¦ Eastern Bank</option>
                                        <option value="Prime Bank">ðŸ¦ Prime Bank</option>
                                        <option value="UCBL">ðŸ¦ UCBL</option>
                                        <option value="Trust Bank">ðŸ¦ Trust Bank</option>
                                        <option value="Standard Chartered">ðŸ¦ Standard Chartered</option>
                                        <option value="HSBC">ðŸ¦ HSBC</option>
                                    </optgroup>
                                    <optgroup label="Payment Gateways">
                                        <option value="SSLCommerz">ðŸ”’ SSLCommerz</option>
                                        <option value="ShurjoPay">ðŸ’³ ShurjoPay</option>
                                        <option value="Aamarpay">ðŸ’µ Aamarpay</option>
                                        <option value="PortWallet">ðŸ‘ PortWallet</option>
                                    </optgroup>
                                    <optgroup label="Other">
                                        <option value="Cheque">ðŸ“ Cheque</option>
                                        <option value="Credit Card">ðŸ’³ Credit Card</option>
                                        <option value="Debit Card">ðŸ’³ Debit Card</option>
                                        <option value="Other">ðŸ”„ Other</option>
                                    </optgroup>
                                </select>
                                <small class="text-muted">
                                    <i class="fa fa-info-circle"></i>
                                    Select the payment method used for this income
                                </small>
                            </div>

                            <div class="form-group">
                                <label>Description</label>
                                <textarea name="description" id="income_description" class="form-control" rows="3" placeholder="Type Description"></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-success" onclick="saveIncome()">
                            <i class="fa fa-save"></i> Save Income
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================= View Income Details Modal ================= -->
        <div class="modal fade" id="viewIncomeModal" tabindex="-1" role="dialog">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title">Income Details</h4>
                    </div>
                    <div class="modal-body" id="incomeDetailsContent">
                        <!-- Details will be loaded here via AJAX -->
                        <div class="text-center">
                            <i class="fa fa-spinner fa-spin fa-3x"></i>
                            <p>Loading details...</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

    </section>
</div>

<?= $this->endSection(); ?>
<?= $this->section('script'); ?>

<script>
    /**
     * Handlers are attached to window so they survive SPA partial-nav:
     * ipb-nav wraps page scripts in an IIFE, which would otherwise hide
     * function declarations from inline onclick="..." attributes.
     */
    (function ($) {
        function csrfPayload(extra) {
            var data = extra || {};
            data['<?= csrf_token() ?>'] = '<?= csrf_hash() ?>';
            return data;
        }

        function initIncomeTables() {
            if ($.fn.DataTable.isDataTable('#incomeCategoryTable')) {
                $('#incomeCategoryTable').DataTable().destroy();
            }
            if ($.fn.DataTable.isDataTable('#incomeTable')) {
                $('#incomeTable').DataTable().destroy();
            }

            $('#incomeTable').DataTable({
                pageLength: 100,
                lengthMenu: [
                    [10, 25, 50, 100, 200],
                    [10, 25, 50, 100, 200]
                ],
                ordering: true,
                responsive: false,
                scrollX: true,
                autoWidth: true,
                columnDefs: [{
                    targets: [0, 13],
                    orderable: false
                }]
            });

            if ($('#incomeCategoryTable').length) {
                $('#incomeCategoryTable').DataTable({
                    pageLength: 10,
                    lengthMenu: [
                        [10, 25, 50, -1],
                        [10, 25, 50, 'All']
                    ],
                    ordering: true,
                    autoWidth: false,
                    responsive: true,
                    columnDefs: [{
                        targets: 3,
                        orderable: false
                    }],
                    language: {
                        emptyTable: 'No income categories found',
                        zeroRecords: 'No matching records found'
                    }
                });
            }
        }

        window.viewFile = function (filename) {
            if (!filename) {
                tata.error('No file available', 'There is no document attached to this income.');
                return;
            }
            window.open('<?= base_url('assets/incomes/') ?>' + filename, '_blank');
        };

        window.showIncomeCategoryModal = function () {
            window.resetIncomeCategoryForm();
            $('#incomeCategoryModal').modal('show');
        };

        window.resetIncomeCategoryForm = function () {
            $('#income_category_id').val('');
            $('#income_category_name').val('');
            $('#saveCategoryBtn').html('<i class="fa fa-save"></i> Save Category');
        };

        window.saveIncomeCategory = function () {
            var form = $('#incomeCategoryForm');
            var submitBtn = $('#saveCategoryBtn');
            var categoryId = $('#income_category_id').val();
            var url = categoryId
                ? '<?= base_url('accounts/income-category/update') ?>'
                : '<?= base_url('accounts/income-category/save') ?>';

            $.ajax({
                url: url,
                type: 'POST',
                data: form.serialize(),
                dataType: 'json',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                beforeSend: function () {
                    submitBtn.html('<i class="fa fa-spinner fa-spin"></i> Saving...').prop('disabled', true);
                },
                success: function (response) {
                    if (response && response.status === 'success') {
                        tata.success('Category saved', response.message);
                        setTimeout(function () { location.reload(); }, 800);
                    } else {
                        tata.error("Couldn't save category", (response && response.message) || 'Save failed');
                    }
                },
                error: function (xhr) {
                    var msg = 'Could not save. Check console for details.';
                    try {
                        var parsed = JSON.parse(xhr.responseText);
                        if (parsed && parsed.message) msg = parsed.message;
                    } catch (e) {}
                    tata.error("Couldn't save category", msg);
                },
                complete: function () {
                    submitBtn.html('<i class="fa fa-save"></i> Save Category').prop('disabled', false);
                }
            });
        };

        window.editIncomeCategory = function (id, name) {
            $('#income_category_id').val(id);
            $('#income_category_name').val(name);
            $('#saveCategoryBtn').html('<i class="fa fa-pencil"></i> Update Category');
            $('#incomeCategoryModal').modal('show');
        };

        window.deleteIncomeCategory = function (id) {
            swal({
                title: 'Delete category?',
                text: 'Are you sure you want to delete this income category?',
                icon: 'warning',
                dangerMode: true,
                buttons: ['Cancel', 'Delete category'],
            }).then(function (willDelete) {
                if (!willDelete) return;
                $.ajax({
                    url: '<?= base_url('accounts/income-category/delete') ?>',
                    type: 'POST',
                    data: csrfPayload({ id: id }),
                    dataType: 'json',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    success: function (response) {
                        if (response && response.status === 'success') {
                            tata.success('Category deleted', response.message);
                            setTimeout(function () { location.reload(); }, 800);
                        } else {
                            tata.error("Couldn't delete category", (response && response.message) || 'Delete failed');
                        }
                    },
                    error: function () {
                        tata.error("Couldn't delete category", 'Error deleting income category.');
                    }
                });
            });
        };

        window.showIncomeModal = function () {
            window.resetIncomeForm();
            $('#incomeModal .modal-title').text('Add New Income');
            $('#incomeModal').modal('show');
        };

        window.resetIncomeForm = function () {
            var form = document.getElementById('incomeForm');
            if (form) form.reset();
            $('#income_id').val('');
            $('#current_document').hide();
            $('#existing_document_name').val('');
        };

        window.saveIncome = function () {
            var formEl = document.getElementById('incomeForm');
            if (!formEl) return;

            if (typeof formEl.reportValidity === 'function' && !formEl.reportValidity()) {
                return;
            }

            var formData = new FormData(formEl);
            var incomeId = $('#income_id').val();
            var url = incomeId
                ? '<?= base_url('accounts/income/update') ?>'
                : '<?= base_url('accounts/income/save') ?>';
            var $btn = $('#incomeModal .btn-success');

            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                beforeSend: function () {
                    $btn.html('<i class="fa fa-spinner fa-spin"></i> Saving...').prop('disabled', true);
                },
                success: function (response) {
                    if (response && response.status === 'success') {
                        tata.success(incomeId ? 'Income updated' : 'Income added', response.message);
                        $('#incomeModal').modal('hide');
                        setTimeout(function () { location.reload(); }, 800);
                    } else {
                        tata.error(
                            incomeId ? "Couldn't update income" : "Couldn't add income",
                            (response && response.message) || 'Save failed'
                        );
                    }
                },
                error: function (xhr) {
                    var msg = 'Error saving income';
                    try {
                        var parsed = JSON.parse(xhr.responseText);
                        if (parsed && parsed.message) msg = parsed.message;
                    } catch (e) {}
                    tata.error(incomeId ? "Couldn't update income" : "Couldn't add income", msg);
                },
                complete: function () {
                    $btn.html('<i class="fa fa-save"></i> Save Income').prop('disabled', false);
                }
            });
        };

        window.editIncome = function (id) {
            $.ajax({
                url: '<?= base_url('accounts/income/get') ?>/' + id,
                type: 'GET',
                dataType: 'json',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function (response) {
                    if (!(response && response.status === 'success')) {
                        tata.error("Couldn't load income", 'Error loading income data.');
                        return;
                    }
                    var data = response.data;
                    var formattedDate = '';
                    if (data.date) {
                        var dateObj = new Date(data.date);
                        if (!isNaN(dateObj.getTime())) {
                            var year = dateObj.getFullYear();
                            var month = String(dateObj.getMonth() + 1).padStart(2, '0');
                            var day = String(dateObj.getDate()).padStart(2, '0');
                            formattedDate = year + '-' + month + '-' + day;
                        } else if (typeof data.date === 'string' && data.date.length >= 10) {
                            formattedDate = data.date.substring(0, 10);
                        }
                    }

                    $('#income_id').val(data.id);
                    $('#income_name').val(data.name);
                    $('#income_category').val(data.income_category).trigger('change');
                    $('#employee').val(data.employee).trigger('change');
                    $('#bank_account').val(data.bank_account).trigger('change');
                    $('#invoice_no').val(data.invoice_no);
                    $('#income_date').val(formattedDate);
                    $('#income_amount').val(data.amount);
                    $('#income_description').val(data.description);

                    if (data.document) {
                        $('#current_document').show();
                        $('#existing_document_name').val(data.document);
                        $('#current_document button').attr('onclick', 'viewFile("' + data.document + '")');
                    } else {
                        $('#current_document').hide();
                        $('#existing_document_name').val('');
                    }

                    $('#incomeModal .modal-title').text('Edit Income');
                    $('#incomeModal').modal('show');
                },
                error: function () {
                    tata.error("Couldn't load income", 'Error loading income data.');
                }
            });
        };

        window.deleteIncome = function (id) {
            swal({
                title: 'Delete income?',
                text: 'Are you sure you want to delete this income?',
                icon: 'warning',
                dangerMode: true,
                buttons: ['Cancel', 'Delete income'],
            }).then(function (willDelete) {
                if (willDelete) {
                    window.location.href = '<?= base_url('accounts/income/delete') ?>/' + id;
                }
            });
        };

        window.approveIncome = function (id) {
            swal({
                title: 'Approve income?',
                text: 'Are you sure you want to approve this income?',
                icon: 'warning',
                buttons: ['Cancel', 'Approve'],
            }).then(function (willApprove) {
                if (!willApprove) return;
                $.ajax({
                    url: '<?= base_url('accounts/income/approve') ?>',
                    type: 'POST',
                    data: csrfPayload({ id: id }),
                    dataType: 'json',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    success: function (response) {
                        if (response && response.status === 'success') {
                            tata.success('Income approved', response.message);
                            setTimeout(function () { location.reload(); }, 800);
                        } else {
                            tata.error("Couldn't approve income", (response && response.message) || 'Approve failed');
                        }
                    },
                    error: function () {
                        tata.error("Couldn't approve income", 'Error approving income. Please try again.');
                    }
                });
            });
        };

        window.rejectIncome = function (id) {
            swal({
                title: 'Reject income?',
                text: 'Are you sure you want to reject this income?',
                icon: 'warning',
                dangerMode: true,
                buttons: ['Cancel', 'Reject'],
            }).then(function (willReject) {
                if (!willReject) return;
                $.ajax({
                    url: '<?= base_url('accounts/income/reject') ?>',
                    type: 'POST',
                    data: csrfPayload({ id: id }),
                    dataType: 'json',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    success: function (response) {
                        if (response && response.status === 'success') {
                            tata.success('Income rejected', response.message);
                            setTimeout(function () { location.reload(); }, 800);
                        } else {
                            tata.error("Couldn't reject income", (response && response.message) || 'Reject failed');
                        }
                    },
                    error: function () {
                        tata.error("Couldn't reject income", 'Error rejecting income. Please try again.');
                    }
                });
            });
        };

        $(function () {
            initIncomeTables();

            $('#incomeCategoryForm').off('submit.incomeCat').on('submit.incomeCat', function (e) {
                e.preventDefault();
                window.saveIncomeCategory();
            });

            $('#selectAll').off('click.incomeSelect').on('click.incomeSelect', function () {
                $('.rowCheckbox').prop('checked', this.checked);
            });

            $(document).off('click.incomeRow', '.rowCheckbox').on('click.incomeRow', '.rowCheckbox', function () {
                if ($('.rowCheckbox:checked').length !== $('.rowCheckbox').length) {
                    $('#selectAll').prop('checked', false);
                }
            });
        });
    })(jQuery);
</script>
<?= $this->endSection(); ?>
