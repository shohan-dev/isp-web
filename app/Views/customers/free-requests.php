<?= $this->extend('layout/main-layout'); ?>
<?= $this->section('content'); ?>

<div class="content-wrapper">
    <section class="content ipb-saas-list">
        
    <?= $this->include('components/page-header', [
      'title' => 'Free User Requests',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Free User Requests'],
      ],
    ]); ?>

<div class="box box-warning">
            <div class="box-header with-border">
                <h3 class="box-title">Pending Free User Requests</h3>
            </div>
            
            <div class="box-body table-responsive">
                <table class="table table-bordered table-striped" id="requests-table">
                    <caption class="sr-only">Free user requests</caption>
                    <thead>
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Customer Name</th>
                            <th scope="col">Customer Email</th>
                            <th scope="col">Reseller Name</th>
                            <th scope="col">Temporary Expiry</th>
                            <th scope="col">Request Date</th>
                            <th scope="col">Status</th>
                            <th scope="col">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $req): ?>
                            <tr>
                                <td><?= $req['id']; ?></td>
                                <td><strong><?= esc($req['customer_name']); ?></strong></td>
                                <td><?= esc($req['customer_email']); ?></td>
                                <td><?= esc($req['reseller_name']); ?></td>
                                <td><span class="label label-info"><?= date('d M, Y H:i', strtotime($req['customer_expiry'])); ?></span></td>
                                <td><?= date('d M, Y H:i', strtotime($req['created_at'])); ?></td>
                                <td>
                                    <?php if ($req['status'] === 'pending'): ?>
                                        <span class="label label-warning">Pending Approval</span>
                                    <?php elseif ($req['status'] === 'approved'): ?>
                                        <span class="label label-success">Approved</span>
                                    <?php else: ?>
                                        <span class="label label-danger">Rejected</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($req['status'] === 'pending'): ?>
                                        <button class="btn btn-xs btn-success btn-action" data-action="approve" data-id="<?= $req['id']; ?>">
                                            <i class="fa fa-check"></i> Approve
                                        </button>
                                        <button class="btn btn-xs btn-danger btn-action" data-action="reject" data-id="<?= $req['id']; ?>">
                                            <i class="fa fa-times"></i> Reject
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted">Processed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

<?= $this->endSection('content'); ?>

<?= $this->section('script'); ?>
<script>
$(document).ready(function() {
    if ($.fn.DataTable.isDataTable('#requests-table')) {
        $('#requests-table').DataTable().destroy();
    }
    $('#requests-table').DataTable({
        "destroy": true,
        "order": [[0, "desc"]],
        "columns": [
            { "orderable": true },  // ID
            { "orderable": true },  // Customer Name
            { "orderable": true },  // Customer Email
            { "orderable": true },  // Reseller Name
            { "orderable": true },  // Temporary Expiry
            { "orderable": true },  // Request Date
            { "orderable": true },  // Status
            { "orderable": false }, // Action
        ],
        "language": {
            "emptyTable": "No free user requests found."
        }
    });

    $(document).on('click', '.btn-action', function() {
        const btn = $(this);
        const action = btn.data('action');
        const id = btn.data('id');
        const url = action === 'approve' ? '<?= route_to("route.customer.free_requests.approve"); ?>' : '<?= route_to("route.customer.free_requests.reject"); ?>';
        
        swal({
            title: `Are you sure?`,
            text: `Do you want to ${action} this free user request?`,
            icon: "warning",
            buttons: true,
            dangerMode: action === 'reject',
        }).then((willProcess) => {
            if (willProcess) {
                btn.attr('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
                
                $.ajax({
                    url: url,
                    type: 'POST',
                    data: { id: id },
                    headers: {
                        '<?= csrf_header() ?>': '<?= csrf_hash() ?>',
                    },
                    success: function(result) {
                        tata.success(action === 'approve' ? 'Request approved' : 'Request rejected', result.response, {
                            onClose: () => {
                                location.reload();
                            }
                        });
                    },
                    error: function({ responseText }) {
                        btn.removeAttr('disabled').html(action === 'approve' ? '<i class="fa fa-check"></i> Approve' : '<i class="fa fa-times"></i> Reject');
                        const result = JSON.parse(responseText || '{}');
                        tata.error(action === 'approve' ? "Couldn't approve request" : "Couldn't reject request", result.response || 'Something went wrong. Please try again.');
                    }
                });
            }
        });
    });
});
</script>
<?= $this->endSection('script'); ?>
