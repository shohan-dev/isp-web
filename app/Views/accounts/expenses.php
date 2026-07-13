<?= $this->extend('layout/main-layout'); ?>

<?= $this->section('css'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/saas/accounts-pages.css?v=2'); ?>">
<?= $this->endSection('css'); ?>

<?= $this->section('content'); ?>

<div class="content-wrapper">
    <section class="content ipb-saas-list ipb-acc-page">

    <?= $this->include('components/page-header', [
      'title' => 'Expenses',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Accounting'],
        ['label' => 'Expenses'],
      ],
    ]); ?>

<div class="box box-primary">
            <div class="box-header with-border ipb-box-toolbar">
        <?php
          ob_start();
        ?>
                    <button type="button" onclick="showExpenseTypeModal()" class="btn btn-default">
                        <i class="fa fa-tags" aria-hidden="true"></i> Types
                    </button>
                    <button type="button" onclick="showExpenseModal()" class="btn btn-primary">
                        <i class="fa fa-plus" aria-hidden="true"></i> New expense
                    </button>
        <?php
          $expenseToolbarActionsHtml = ob_get_clean();
        ?>
        <?= view('components/list-toolbar', [
          'filters' => [],
          'actionsHtml' => $expenseToolbarActionsHtml,
          'filterLabel' => 'Expense records',
          'showReset' => false,
          'showCount' => false,
        ]); ?>
      </div>

            <!-- ================= Expense List Table ================= -->
            <div class="box-body table-responsive">
                <table id="expenseTable" class="table table-bordered table-striped">
                    <caption class="sr-only">Expense list</caption>
                    <thead>
                        <tr>
                            <th scope="col" style="width: 20px;">
                                <input type="checkbox" id="selectAll">
                            </th>
                            <th scope="col">ID</th>
                            <th scope="col">Status</th>
                            <th scope="col">Name</th>
                            <th scope="col">Expense Head</th>
                            <th scope="col">Employee</th>
                            <th scope="col">Invoice Number</th>
                            <th scope="col">Date</th>
                            <th scope="col">Amount</th>
                            <th scope="col">Bank Account</th>
                            <th scope="col">Documents</th>
                            <th scope="col">Description</th>
                            <th scope="col">Create By</th>
                            <th scope="col" style="width: 120px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($expenses)) : ?>
                            <?php foreach ($expenses as $key => $row) : ?>
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
                                    <td><?= esc($row['expense_head']) ?></td>
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
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= esc($row['description']) ?></td>
                                    <td><?= esc($row['created_by']) ?></td>
                                    <td class="ipb-acc-actions">
                                        <div class="ipb-row-actions">
                                        <?php if ($row['status'] != 'approved') : ?>
                                            <button type="button" class="ipb-row-btn tone-success" title="Approve" onclick="approveExpense(<?= (int) $row['id'] ?>)">
                                                <i class="fa fa-check" aria-hidden="true"></i><span class="sr-only">Approve</span>
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($row['status'] != 'rejected') : ?>
                                            <button type="button" class="ipb-row-btn tone-danger" title="Reject" onclick="rejectExpense(<?= (int) $row['id'] ?>)">
                                                <i class="fa fa-times" aria-hidden="true"></i><span class="sr-only">Reject</span>
                                            </button>
                                        <?php endif; ?>
                                            <button type="button" class="ipb-row-btn tone-brand" title="Edit" onclick="editExpense(<?= (int) $row['id'] ?>)">
                                                <i class="fa fa-edit" aria-hidden="true"></i><span class="sr-only">Edit</span>
                                            </button>
                                            <button type="button" class="ipb-row-btn tone-danger" title="Delete" onclick="deleteExpense(<?= (int) $row['id'] ?>)">
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

        <!-- ================= Expense Type Management Modal ================= -->
        <div class="modal fade" id="expenseTypeModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title">Manage Expense Types</h4>
                    </div>
                    <div class="modal-body">
                        <!-- Add New Expense Type Form -->
                        <div class="box box-info">
                            <div class="box-header">
                                <h3 class="box-title">Add New Expense Type</h3>
                            </div>
                            <div class="box-body">
                                <form id="expenseTypeForm">
                                    <?= csrf_field(); ?>
                                    <input type="hidden" name="type_id" id="expense_type_id">
                                    <div class="form-group">
                                        <label>Name *</label>
                                        <input type="text" name="name" id="expense_type_name" class="form-control" placeholder="e.g. Office Rent, Utilities" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary" id="saveTypeBtn">
                                        <i class="fa fa-save"></i> Save Type
                                    </button>
                                    <button type="button" class="btn btn-default" onclick="resetExpenseTypeForm()">Reset</button>
                                </form>
                            </div>
                        </div>

                        <!-- Expense Types List -->
                        <div class="box">
                            <div class="box-header">
                                <h3 class="box-title">Existing Expense Types</h3>
                            </div>
                            <div class="box-body table-responsive">
                                <table class="table table-bordered table-striped" id="expenseTypeTable">
                                    <caption class="sr-only">Expense types</caption>
                                    <thead>
                                        <tr>
                                            <th scope="col">ID</th>
                                            <th scope="col">Name</th>
                                            <th scope="col">Created At</th>
                                            <th scope="col">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($expense_types)) : ?>
                                            <?php foreach ($expense_types as $type) : ?>
                                                <tr>
                                                    <td><?= esc($type['id']) ?></td>
                                                    <td><?= esc($type['name']) ?></td>
                                                    <td><?= date('d/m/Y H:i', strtotime($type['created_at'] ?? date('Y-m-d H:i:s'))) ?></td>
                                                    <td>
                                                        <div class="ipb-row-actions">
                                                        <button type="button" class="ipb-row-btn tone-brand" title="Edit" aria-label="Edit expense type" onclick="editExpenseType(<?= (int) $type['id'] ?>, '<?= esc($type['name'], 'js') ?>')">
                                                            <i class="fa fa-edit" aria-hidden="true"></i>
                                                        </button>
                                                        <button type="button" class="ipb-row-btn tone-danger" title="Delete" aria-label="Delete expense type" onclick="deleteExpenseType(<?= (int) $type['id'] ?>)">
                                                            <i class="fa fa-trash" aria-hidden="true"></i>
                                                        </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td></td>
                                                <td>No expense types found</td>
                                                <td></td>
                                                <td></td>
                                            </tr>
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

        <!-- ================= Add/Edit Expense Modal ================= -->
        <div class="modal fade" id="expenseModal" tabindex="-1" role="dialog">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title">Add New Expense</h4>
                    </div>
                    <div class="modal-body">
                        <form id="expenseForm" enctype="multipart/form-data">
                            <?= csrf_field(); ?>
                            <input type="hidden" name="expense_id" id="expense_id">

                            <div class="form-group">
                                <label>Expense Title *</label>
                                <input type="text" name="name" id="expense_name" class="form-control" placeholder="Enter Name" required>
                            </div>

                            <div class="form-group">
                                <label>Expense Head *</label>
                                <select name="expense_head" id="expense_head" class="form-control" required>
                                    <option value="">Select Head</option>
                                    <?php foreach ($expense_types ?? [] as $type): ?>
                                        <option value="<?= esc($type['name']) ?>">
                                            <?= esc($type['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small><a href="javascript:void(0)" onclick="showExpenseTypeModal()">+ Add New Expense Type</a></small>
                            </div>

                            <div class="form-group">
                                <label>Employee *</label>
                                <select name="employee" id="employee" class="form-control" required>
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
                                        <input type="date" name="date" id="expense_date" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Amount *</label>
                                        <input type="number" step="0.01" name="amount" id="expense_amount" class="form-control" placeholder="Enter Amount" required>
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
                                        <option value="Cash">💰 Cash</option>
                                    </optgroup>
                                    <optgroup label="Mobile Banking">
                                        <option value="bKash">📱 bKash</option>
                                        <option value="Rocket">🚀 Rocket</option>
                                        <option value="Nagad">💳 Nagad</option>
                                        <option value="Upay">📲 Upay</option>
                                        <option value="Tap">📱 Tap</option>
                                    </optgroup>
                                    <optgroup label="Banks">
                                        <option value="Dutch Bangla Bank">🏦 Dutch Bangla Bank</option>
                                        <option value="Sonali Bank">🏦 Sonali Bank</option>
                                        <option value="Janata Bank">🏦 Janata Bank</option>
                                        <option value="Agrani Bank">🏦 Agrani Bank</option>
                                        <option value="Rupali Bank">🏦 Rupali Bank</option>
                                        <option value="Islami Bank">🏦 Islami Bank</option>
                                        <option value="City Bank">🏦 City Bank</option>
                                        <option value="Eastern Bank">🏦 Eastern Bank</option>
                                        <option value="Prime Bank">🏦 Prime Bank</option>
                                        <option value="UCBL">🏦 UCBL</option>
                                        <option value="Trust Bank">🏦 Trust Bank</option>
                                        <option value="Standard Chartered">🏦 Standard Chartered</option>
                                        <option value="HSBC">🏦 HSBC</option>
                                    </optgroup>
                                    <optgroup label="Payment Gateways">
                                        <option value="SSLCommerz">🔒 SSLCommerz</option>
                                        <option value="ShurjoPay">💳 ShurjoPay</option>
                                        <option value="Aamarpay">💵 Aamarpay</option>
                                        <option value="PortWallet">👝 PortWallet</option>
                                    </optgroup>
                                    <optgroup label="Other">
                                        <option value="Cheque">📝 Cheque</option>
                                        <option value="Credit Card">💳 Credit Card</option>
                                        <option value="Debit Card">💳 Debit Card</option>
                                        <option value="Other">🔄 Other</option>
                                    </optgroup>
                                </select>
                                <small class="text-muted">
                                    <i class="fa fa-info-circle"></i>
                                    Select the payment method used for this expense
                                </small>
                            </div>

                            <div class="form-group">
                                <label>Description</label>
                                <textarea name="description" id="expense_description" class="form-control" rows="3" placeholder="Type Description"></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-success" onclick="saveExpense()">
                            <i class="fa fa-save"></i> Save Expense
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================= View Expense Details Modal ================= -->
        <div class="modal fade" id="viewExpenseModal" tabindex="-1" role="dialog">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title">Expense Details</h4>
                    </div>
                    <div class="modal-body" id="expenseDetailsContent">
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
    // Check if jQuery is loaded
    console.log('jQuery loaded:', typeof jQuery !== 'undefined');

    $(document).ready(function() {
        console.log('Document ready - initializing');

        // Initialize DataTable for main expense table
        $('#expenseTable').DataTable({
            "pageLength": 100,
            "lengthMenu": [
                [10, 25, 50, 100, 200],
                [10, 25, 50, 100, 200]
            ],
            "ordering": true,
            "responsive": true,
            "autoWidth": true
        });

        // Initialize DataTable for expense type table
        if ($('#expenseTypeTable').length) {
            $('#expenseTypeTable').DataTable({
                "pageLength": 10,
                "lengthMenu": [
                    [10, 25, 50, -1],
                    [10, 25, 50, "All"]
                ],
                "ordering": true,
                "autoWidth": false,
                "responsive": true,
                "columnDefs": [{
                        "orderable": false,
                        "targets": 3
                    },
                    {
                        "width": "15%",
                        "targets": 0
                    },
                    {
                        "width": "35%",
                        "targets": 1
                    },
                    {
                        "width": "30%",
                        "targets": 2
                    },
                    {
                        "width": "20%",
                        "targets": 3
                    }
                ],
                "language": {
                    "lengthMenu": "Show _MENU_ entries",
                    "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                    "infoEmpty": "Showing 0 to 0 of 0 entries",
                    "infoFiltered": "(filtered from _MAX_ total entries)",
                    "search": "Search:",
                    "paginate": {
                        "first": "First",
                        "last": "Last",
                        "next": "Next",
                        "previous": "Previous"
                    }
                }
            });
        }

        /* ================= Expense Type AJAX ================= */
        $('#expenseTypeForm').on('submit', function(e) {
            e.preventDefault();
            saveExpenseType();
        });

        // Select All functionality
        $('#selectAll').on('click', function() {
            $('.rowCheckbox').prop('checked', this.checked);
        });

        $(document).on('click', '.rowCheckbox', function() {
            if ($('.rowCheckbox:checked').length != $('.rowCheckbox').length) {
                $('#selectAll').prop('checked', false);
            }
        });
    });

    // ================= File Viewing Function =================
    function viewFile(filename) {
        if (!filename) {
            alert('No file available');
            return;
        }

        // Construct the full file URL - using assets/expenses/ as per your save path
        const fileUrl = '<?= base_url("assets/expenses/") ?>' + filename;

        // Open in new tab/window
        window.open(fileUrl, '_blank');
    }

    // ================= Expense Type Functions =================
    function showExpenseTypeModal() {
        resetExpenseTypeForm();
        $('#expenseTypeModal').modal('show');
    }

    function saveExpenseType() {
        const form = $('#expenseTypeForm');
        const submitBtn = $('#saveTypeBtn');
        const formData = form.serialize();
        const typeId = $('#expense_type_id').val();

        // Use base_url() for the URLs
        let url = typeId ? '<?= base_url("accounts/expense-type/update") ?>' : '<?= base_url("accounts/expense-type/save") ?>';

        $.ajax({
            url: url,
            type: "POST",
            data: formData,
            dataType: "json",
            beforeSend: function() {
                submitBtn.html('<i class="fa fa-spinner fa-spin"></i> Saving...').prop('disabled', true);
            },
            success: function(response) {
                if (response.status === 'success') {
                    alert(response.message);
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr) {
                console.error(xhr.responseText);
                alert('Database Error: Could not save. Check console for details.');
            },
            complete: function() {
                submitBtn.html('<i class="fa fa-save"></i> Save Type').prop('disabled', false);
            }
        });
    }

    function editExpenseType(id, name) {
        $('#expense_type_id').val(id);
        $('#expense_type_name').val(name);
        $('#saveTypeBtn').html('<i class="fa fa-pencil"></i> Update Type');
        $('#expenseTypeModal').modal('show');
    }

    function deleteExpenseType(id) {
        if (confirm('Are you sure you want to delete this expense type?')) {
            $.ajax({
                url: '<?= base_url("accounts/expense-type/delete") ?>',
                type: "POST",
                data: {
                    id: id,
                    '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
                },
                dataType: "json",
                success: function(response) {
                    if (response.status === 'success') {
                        alert(response.message);
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function(xhr) {
                    console.error(xhr.responseText);
                    alert('Error deleting expense type');
                }
            });
        }
    }

    function resetExpenseTypeForm() {
        $('#expense_type_id').val('');
        $('#expense_type_name').val('');
        $('#saveTypeBtn').html('<i class="fa fa-save"></i> Save Type');
    }

    // ================= Expense Functions =================
    function showExpenseModal() {
        resetExpenseForm();
        $('#expenseModal .modal-title').text('Add New Expense');
        $('#expenseModal').modal('show');
    }

    function resetExpenseForm() {
        $('#expenseForm')[0].reset();
        $('#expense_id').val('');
        $('#current_document').hide();
        $('#existing_document_name').val('');
    }

    function saveExpense() {
        let formData = new FormData(document.getElementById('expenseForm'));
        const expenseId = $('#expense_id').val();

        // Use base_url() for the URLs
        let url = expenseId ? '<?= base_url("accounts/expense/update") ?>' : '<?= base_url("accounts/expense/save") ?>';

        $.ajax({
            url: url,
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $('#expenseModal .btn-success').html('<i class="fa fa-spinner fa-spin"></i> Saving...').prop('disabled', true);
            },
            success: function(response) {
                console.log('Expense success:', response);
                alert(response.message);
                $('#expenseModal').modal('hide');
                setTimeout(function() {
                    location.reload();
                }, 1000);
            },
            error: function(xhr, status, error) {
                console.error('Expense error:', error);
                console.error('Response:', xhr.responseText);
                alert('Error saving expense: ' + error);
            },
            complete: function() {
                $('#expenseModal .btn-success').html('<i class="fa fa-save"></i> Save Expense').prop('disabled', false);
            }
        });
    }

    function editExpense(id) {
        $.ajax({
            url: '<?= base_url("accounts/expense/get") ?>/' + id,
            type: "GET",
            dataType: "json",
            success: function(response) {
                if (response.status === 'success') {
                    const data = response.data;

                    // Format date for input field (YYYY-MM-DD)
                    let formattedDate = '';
                    if (data.date) {
                        const dateObj = new Date(data.date);
                        const year = dateObj.getFullYear();
                        const month = String(dateObj.getMonth() + 1).padStart(2, '0');
                        const day = String(dateObj.getDate()).padStart(2, '0');
                        formattedDate = `${year}-${month}-${day}`;
                    }

                    $('#expense_id').val(data.id);
                    $('#expense_name').val(data.name);

                    // Fix: Set dropdown values properly
                    $('#expense_head').val(data.expense_head).trigger('change');
                    $('#employee').val(data.employee).trigger('change');
                    $('#bank_account').val(data.bank_account).trigger('change');

                    $('#invoice_no').val(data.invoice_no);
                    $('#expense_date').val(formattedDate);
                    $('#expense_amount').val(data.amount);
                    $('#expense_description').val(data.description);

                    // Handle document display
                    if (data.document) {
                        $('#current_document').show();
                        $('#existing_document_name').val(data.document);

                        // Update the view button
                        $('#current_document button').attr('onclick', 'viewFile("' + data.document + '")');
                    } else {
                        $('#current_document').hide();
                        $('#existing_document_name').val('');
                    }

                    $('#expenseModal .modal-title').text('Edit Expense');
                    $('#expenseModal').modal('show');
                } else {
                    alert('Error loading expense data');
                }
            },
            error: function(xhr) {
                console.error(xhr.responseText);
                alert('Error loading expense data');
            }
        });
    }

    function deleteExpense(id) {
        if (confirm('Are you sure you want to delete this expense?')) {
            window.location.href = '<?= base_url("accounts/expense/delete") ?>/' + id;
        }
    }

    // ================= Approval Functions =================
    function approveExpense(id) {
        if (confirm('Are you sure you want to approve this expense?')) {
            $.ajax({
                url: '<?= base_url("accounts/expense/approve") ?>',
                type: "POST",
                data: {
                    id: id,
                    '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
                },
                dataType: "json",
                beforeSend: function() {
                    // Optional: Show loading indicator
                    $('body').css('cursor', 'wait');
                },
                success: function(response) {
                    if (response.status === 'success') {
                        alert(response.message);
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Approve error:', error);
                    console.error('Response:', xhr.responseText);
                    alert('Error approving expense. Please try again.');
                },
                complete: function() {
                    $('body').css('cursor', 'default');
                }
            });
        }
    }

    function rejectExpense(id) {
        if (confirm('Are you sure you want to reject this expense?')) {
            $.ajax({
                url: '<?= base_url("accounts/expense/reject") ?>',
                type: "POST",
                data: {
                    id: id,
                    // reason: reason,
                    '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
                },
                dataType: "json",
                beforeSend: function() {
                    // Optional: Show loading indicator
                    $('body').css('cursor', 'wait');
                },
                success: function(response) {
                    if (response.status === 'success') {
                        alert(response.message);
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Reject error:', error);
                    console.error('Response:', xhr.responseText);
                    alert('Error rejecting expense. Please try again.');
                },
                complete: function() {
                    $('body').css('cursor', 'default');
                }
            });
        }
    }
</script>

<?= $this->endSection(); ?>