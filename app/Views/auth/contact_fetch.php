<?= $this->extend('layout/main-layout'); ?>
<?= $this->section('content'); ?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <section class="content ipb-saas-list">
        
    <?= $this->include('components/page-header', [
      'title' => 'Inquiries',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Inquiries'],
      ],
    ]); ?>

<div class="box box-primary">
            <div class="box-header with-border ipb-box-toolbar">
        <div class="ipb-list-toolbar">
          <div class="ipb-list-toolbar-filters">
            <span class="ipb-filter-label"><i class="fa fa-list" aria-hidden="true"></i> Records</span>
          </div>
          <div class="ipb-list-toolbar-actions">
<?php if (userHasPermission('inquiries', 'delete') || getSession('user_role') === 'super_admin'): ?>
                        <button class="btn btn-danger delete-btn">
                            <i class="far fa-trash-can"></i> Delete Selected
                        </button>
                    <?php endif; ?>
          </div>
        </div>
      </div>

            <div class="box-body">
                <div class="table-responsive">
          <table class="table table-bordered table-striped datatable" width="100%">
                    <caption class="sr-only">Inquiries</caption>
                    <thead>
                        <tr>
                            <th data-data="select" scope="col">
                                <input type="checkbox" class="form-check-input" id="select_all">
                            </th>
                            <th data-data="serial" scope="col">#</th>
                            <th data-data="name" scope="col">Name</th>
                            <th data-data="phone" scope="col">Phone</th>
                            <th data-data="email" scope="col">Email</th>
                            <th data-data="message" scope="col">Message</th>
                            <th data-data="inquiry_type" scope="col">Inquiry Type</th>
                            <th data-data="created_at" scope="col">Created At</th>
                            <th data-data="updated_at" scope="col">Updated At</th>
                            <th data-data="action" scope="col">Action</th>
                        </tr>
                    </thead>
                </table>
        </div>
            </div>
        </div>
    </section>
</div>

<!-- Redesigned Professional Inquiry Details Modal -->
<!-- Inquiry Details Modal -->
<div id="inquiryModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="inquiryModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow rounded-3">

            <!-- Modal Header -->
            <div class="modal-header bg-primary text-white rounded-top">
                <h5 class="modal-title" id="inquiryModalLabel">
                    <i class="fa fa-envelope-open-text mr-2"></i> Inquiry Details
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body bg-light px-4 py-3">
                <div class="row mb-3">
                    <div class="col-md-6 mb-2">
                        <label class="text-muted small">Phone</label>
                        <div class="font-weight-semibold text-dark" id="inquiry-phone">--</div>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="text-muted small">Email</label>
                        <div class="font-weight-semibold text-dark" id="inquiry-email">--</div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="text-muted small">Message</label>
                    <div class="border rounded p-3 bg-white text-secondary" id="inquiry-message"
                        style="min-height: 100px;">
                        --
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer bg-white border-top-0">
                <button type="button" class="btn btn-sm" data-dismiss="modal"
                    style="background:var(--surface, #fff);color:var(--text-primary, #0f172a);border:1.5px solid #e6eaf0;">
                    <i class="fa fa-times mr-1"></i> Close
                </button>
            </div>

        </div>
    </div>
</div>


<?= $this->endSection('content'); ?>

<?= $this->section('script'); ?>
<script>
    $(document).ready(function () {
        const table = $('.datatable').DataTable({
            ajax: {
                url: "<?= route_to('route.contact.fetchall'); ?>",
                type: 'POST',
                beforeSend: function (req) {
                    req.setRequestHeader('<?= csrf_header() ?>', '<?= csrf_hash() ?>');
                },
            },
            columnDefs: [
                {
                    targets: "_all",
                    defaultContent: "--"
                }
            ]
        });

        // View Inquiry Details
        $(document).on("click", ".view-inquiry", function () {
            const rowData = table.row($(this).closest("tr")).data();

            $("#inquiry-phone").text(rowData.phone || "--");
            $("#inquiry-email").text(rowData.email || "--");
            $("#inquiry-message").text(rowData.message || "--");

            $("#inquiryModal").modal("show");
        });

        // Select all checkboxes
        <?php if (getSession('user_role') === 'super_admin'): ?>
            $('#select_all').on('click', function () {
                $('.input-check-selected').prop('checked', this.checked);
            });

            $(document).on("click", ".input-check-selected", function () {
                $('#select_all').prop('checked', $(".input-check-selected:checked").length === $(".input-check-selected").length);
            });

            $(document).on('click', '.delete-btn', function () {
                swal({
                    title: "Confirmation",
                    text: "Are you sure you want to delete selected inquiries?",
                    icon: "warning",
                    buttons: ["No", { text: "Yes", closeModal: false }],
                    dangerMode: true,
                }).then((willDelete) => {
                    if (willDelete) {
                        let ids = $('.input-check-selected:checked').map(function () {
                            return $(this).val();
                        }).get();

                        $.ajax({
                            url: '<?= route_to("route.contactdelete"); ?>',
                            type: 'DELETE',
                            data: { ids },
                            headers: { '<?= csrf_header() ?>': '<?= csrf_hash() ?>' },
                            success: function (result) {
                                console.log(result);
                                swal.close();
                                if (result.error) {
                                    tata.error("Couldn't delete inquiries", result.error);
                                } else {
                                    tata.success('Inquiries deleted', result.success);
                                }
                                $('.datatable').DataTable().ajax.reload(null, false);
                            },
                            error: function (response) {
                                const result = JSON.parse(response.responseText);
                                swal.close();
                                tata.error("Couldn't delete inquiries", result.response);
                            }
                        });
                    }
                });
            });
        <?php endif; ?>
    });
</script>
<?= $this->endSection('script'); ?>