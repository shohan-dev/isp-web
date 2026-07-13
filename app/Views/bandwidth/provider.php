<?= $this->extend('layout/main-layout'); ?>

<?= $this->section('css'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/saas/bandwidth-pages.css?v=1'); ?>">
<?= $this->endSection('css'); ?>

<?= $this->section('content'); ?>
<div class="content-wrapper">
    <section class="content ipb-saas-list ipb-bw-page">

    <?= $this->include('components/page-header', [
      'title' => 'Providers',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Bandwidth Buy'],
        ['label' => 'Providers'],
      ],
    ]); ?>

<div class="box box-primary">
            <div class="box-header with-border ipb-box-toolbar">
        <div class="ipb-list-toolbar">
          <div class="ipb-list-toolbar-filters">
            <span class="ipb-filter-label"><i class="fa fa-handshake" aria-hidden="true"></i> Provider list</span>
          </div>
          <div class="ipb-list-toolbar-actions">
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addProviderModal">
                        <i class="fa fa-plus" aria-hidden="true"></i> New Provider
                    </button>
                    <button type="button" id="bulkDeleteBtn" class="btn btn-danger">
                        <i class="far fa-trash-can" aria-hidden="true"></i> Delete
                    </button>
          </div>
        </div>
      </div>

            <div class="box-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <caption class="sr-only">Provider list</caption>
                        <thead>
                            <tr>
                                <th width="50" scope="col"><input type="checkbox" class="form-check-input" id="select_all"></th>
                                <th scope="col">Logo</th>
                                <th scope="col">Provider Name</th>
                                <th scope="col">Contact Person</th>
                                <th scope="col">Contact</th>
                                <th scope="col">Email</th>
                                <th scope="col">Address</th>
                                <th width="150" scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($providers as $provider): ?>
                                <tr>
                                    <td><input type="checkbox" class="input-check-selected" value="<?= $provider['id'] ?>">
                                    </td>
                                    <td>
                                        <img class="ipb-bw-logo" src="<?= !empty($provider['image']) ? base_url('assets/img/company_logo/' . esc($provider['image'])) : base_url('assets/img/icon/image.png') ?>"
                                            alt="">
                                    </td>
                                    <td><strong><?= !empty($provider['company_name']) ? esc($provider['company_name']) : '--' ?></strong></td>
                                    <td><?= !empty($provider['contact_person']) ? esc($provider['contact_person']) : '--' ?></td>
                                    <td><?= !empty($provider['mobile_number']) ? esc($provider['mobile_number']) : '--' ?></td>
                                    <td><?= !empty($provider['email']) ? esc($provider['email']) : '--' ?></td>
                                    <td><?= !empty($provider['address']) ? esc($provider['address']) : '--' ?></td>
                                    <td>
                                        <div class="ipb-row-actions">
                                        <button type="button" class="ipb-row-btn tone-info viewProviderBtn" title="Details"
                                            data-id="<?= $provider['id'] ?>"
                                            data-company_name="<?= esc($provider['company_name']) ?>"
                                            data-contact_person="<?= esc($provider['contact_person']) ?>"
                                            data-email="<?= esc($provider['email']) ?>"
                                            data-phone_number="<?= esc($provider['phone_number']) ?>"
                                            data-mobile_number="<?= esc($provider['mobile_number']) ?>"
                                            data-facebook_url="<?= esc($provider['facebook_url']) ?>"
                                            data-skype_id="<?= esc($provider['skype_id']) ?>"
                                            data-website="<?= esc($provider['website']) ?>"
                                            data-address="<?= esc($provider['address']) ?>"
                                            data-image="<?= esc($provider['image']) ?>">
                                            <i class="fa fa-eye" aria-hidden="true"></i><span class="sr-only">Details</span>
                                        </button>
                                        <button type="button" class="ipb-row-btn tone-brand editProviderBtn" title="Edit"
                                            data-id="<?= $provider['id'] ?>"
                                            data-company_name="<?= esc($provider['company_name']) ?>"
                                            data-contact_person="<?= esc($provider['contact_person']) ?>"
                                            data-email="<?= esc($provider['email']) ?>"
                                            data-phone_number="<?= esc($provider['phone_number']) ?>"
                                            data-mobile_number="<?= esc($provider['mobile_number']) ?>"
                                            data-facebook_url="<?= esc($provider['facebook_url']) ?>"
                                            data-skype_id="<?= esc($provider['skype_id']) ?>"
                                            data-website="<?= esc($provider['website']) ?>"
                                            data-address="<?= esc($provider['address']) ?>"
                                            data-image="<?= esc($provider['image']) ?>">
                                            <i class="fa fa-edit" aria-hidden="true"></i><span class="sr-only">Edit</span>
                                        </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($providers)): ?>
                                <tr>
                                    <td colspan="8" class="ipb-bw-empty">No providers found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Add Provider Modal -->
<!-- Add Provider Modal -->
<div class="modal fade" id="addProviderModal" tabindex="-1" role="dialog" aria-labelledby="addProviderModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <!-- <div class="modal-dialog" role="document" style="max-width: 80%;"> -->
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Provider</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span style="font-size: 30px;">&times;</span>
                </button>
            </div>
            <form id="addProviderForm" action="<?= route_to('bandwidth.provider_store') ?>" method="POST"
                enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row">
                        <!-- Image Section -->
                        <div class="col-md-3 text-center">
                            <div class="position-relative">
                                <!-- Image Preview -->
                                <img id="providerLogoPreview" src="..." class="img-thumbnail"
     style="max-width: 150px; max-height: 150px; width: auto; height: auto;" alt="Provider Logo">


                                <!-- Logo upload — only visible in add/edit mode -->
                                <label for="providerLogoInput" id="providerLogoEditBtn" class="position-absolute"
                                    style="top: 5px; right: 5px; cursor: pointer; background: white; padding: 5px; border-radius: 50%;">
                                    <i class="fa fa-pencil-alt"></i>
                                </label>

                                <!-- Hidden File Input -->
                                <input type="file" name="logo" id="providerLogoInput" class="d-none" accept="image/*"
                                    onchange="updateFileName(this)">
                            </div>

                            <!-- Filename Display (centered below image) -->
                            <div id="fileNameDisplay" class="small text-muted mt-2 text-center"
                                style="min-height: 20px; width: 100%;">
                                No file selected
                            </div>
                        </div>

                        <!-- Form Inputs -->
                        <div class="col-md-9">
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label>Company Name*</label>
                                    <input type="text" name="company_name" class="form-control" required>
                                </div>

                                <input type="hidden" name="id" id="providerId">
                                <div class="form-group col-md-6">
                                    <label>Contact Person*</label>
                                    <input type="text" name="contact_person" class="form-control" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Email*</label>
                                    <input type="email" name="email" class="form-control" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Phone Number</label>
                                    <input type="text" name="phone_number" class="form-control">
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Mobile Number*</label>
                                    <input type="text" name="mobile_number" class="form-control" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Facebook URL</label>
                                    <input type="url" name="facebook_url" class="form-control">
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Skype ID</label>
                                    <input type="text" name="skype_id" class="form-control">
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Website</label>
                                    <input type="url" name="website" class="form-control">
                                </div>
                                <div class="form-group col-md-12">
                                    <label>Address</label>
                                    <textarea name="address" class="form-control" rows="2"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Buttons -->
                <div class="modal-footer">
                    <button type="reset" class="btn btn-danger">Clear</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                    <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
                </div>
            </form>
        </div>
    </div>
</div>


<?= $this->endSection(); ?>
<?= $this->section('script'); ?>
<script>
    function updateFileName(input) {
        const file = input.files[0];
        const fileNameDisplay = document.getElementById('fileNameDisplay');

        if (file) {
            // Update image preview
            const preview = document.getElementById('providerLogoPreview');
            preview.src = URL.createObjectURL(file);

            // Update filename display (center below image)
            fileNameDisplay.textContent = file.name;
            fileNameDisplay.classList.remove('text-muted');
        } else {
            fileNameDisplay.textContent = 'No file selected';
            fileNameDisplay.classList.add('text-muted');
        }

        // This completely hides the default file input behavior
        input.blur();
    }
</script>

<style>
    /* Additional CSS to ensure no default input styling leaks through */
    input[type="file"] {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border: 0;
    }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.forEach(function (tooltipTriggerEl) {
            new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>

<script>

    function setProviderModalMode(mode) {
        const isView = mode === 'view';
        const isEdit = mode === 'edit';

        $('#addProviderForm input, #addProviderForm textarea').prop('disabled', isView);
        $('#providerLogoInput').prop('disabled', isView);
        $('#providerLogoEditBtn').toggle(!isView);

        $('#addProviderForm .modal-footer .btn-primary, #addProviderForm .modal-footer [type="reset"]').toggle(!isView);

        if (mode === 'add') {
            $('#addProviderModal .modal-title').text('Add Provider');
        } else if (isEdit) {
            $('#addProviderModal .modal-title').text('Edit Provider');
        } else {
            $('#addProviderModal .modal-title').text('Provider Details');
        }
    }

    function fillProviderForm(button) {
        $('#providerId').val(button.data('id'));
        $('[name="company_name"]').val(button.data('company_name'));
        $('[name="contact_person"]').val(button.data('contact_person'));
        $('[name="email"]').val(button.data('email'));
        $('[name="phone_number"]').val(button.data('phone_number'));
        $('[name="mobile_number"]').val(button.data('mobile_number'));
        $('[name="facebook_url"]').val(button.data('facebook_url'));
        $('[name="skype_id"]').val(button.data('skype_id'));
        $('[name="website"]').val(button.data('website'));
        $('[name="address"]').val(button.data('address'));

        const imagePath = button.data('image') ?
            `<?= base_url('assets/img/company_logo/') ?>/${button.data('image')}` :
            `<?= base_url('assets/img/icon/image.png') ?>`;

        $('#providerLogoPreview').attr('src', imagePath);
        $('#fileNameDisplay').text(button.data('image') ? 'Existing file used' : 'No file selected').addClass('text-muted');
        $('#providerLogoInput').val('');
    }

    $(document).on('click', '.viewProviderBtn', function () {
        fillProviderForm($(this));
        setProviderModalMode('view');
        $('#addProviderModal').modal('show');
    });

    $(document).on('click', '.editProviderBtn', function () {
        fillProviderForm($(this));
        setProviderModalMode('edit');
        $('#addProviderModal').modal('show');
    });

    // New provider: editable form + logo pencil
    $('[data-target="#addProviderModal"]').on('click', function () {
        $('#addProviderForm')[0].reset();
        $('#providerId').val('');
        $('#providerLogoPreview').attr('src', '<?= base_url('assets/img/icon/image.png') ?>');
        $('#fileNameDisplay').text('No file selected').addClass('text-muted');
        setProviderModalMode('add');
    });

    $('#addProviderModal').on('hidden.bs.modal', function () {
        setProviderModalMode('add');
        $('#addProviderForm')[0].reset();
        $('#providerId').val('');
    });



    // Handle form submission for adding provider
    // Preview providerimage
    $('#providerLogoInput').on('change', function (e) {
        const reader = new FileReader();
        reader.onload = function (e) {
            $('#providerLogoPreview').attr('src', e.target.result);
        };
        reader.readAsDataURL(this.files[0]);
    });

    // AJAX form submission
    $('#addProviderForm').submit(function (e) {
        e.preventDefault();

        let formData = new FormData(this);

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
                $('#addProviderModal').modal('hide');
                console.log(response);
                if (response.status === 'success') {
                    tata.success('Provider saved', response.message);
                    location.reload();
                } else {
                    tata.error("Couldn't save provider", response.message);
                }
                location.reload();
            },
            error: function (xhr) {
                tata.error("Couldn't save provider", xhr.responseJSON?.message || 'Something went wrong');
                location.reload();
            }
        });
    });

</script>
<script>
    $(document).ready(function () {
        // Select All Toggle
        $('#select_all').on('change', function () {
            $('.input-check-selected').prop('checked', $(this).is(':checked'));
        });

        // Uncheck "select all" if one is unchecked, check if all are checked
        $('.input-check-selected').on('change', function () {
            const all = $('.input-check-selected').length;
            const checked = $('.input-check-selected:checked').length;
            $('#select_all').prop('checked', all === checked);
        });

        // Bulk delete button logic
        $('#bulkDeleteBtn').on('click', function () {
            let selectedIds = $('.input-check-selected:checked')
                .map(function () {
                    return $(this).val();
                }).get();

            if (selectedIds.length === 0) {
                alert('Please select at least one provider to delete.');
                return;
            }

            // Optional: Confirm deletion
            if (!confirm('Are you sure you want to delete the selected providers?')) {
                return;
            }

            // Send selectedIds to backend via AJAX or form submission
            // console.log('Selected IDs:', selectedIds);

            // Example AJAX call (adjust route and method as needed)
            $.ajax({
                url: '<?= route_to("bandwidth.provider_delete") ?>',
                type: 'POST',
                data: {
                    selected_ids: selectedIds,
                    '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
                },
                success: function (response) {
                    // Refresh or update the table
                    location.reload();
                },
                error: function () {
                    alert('An error occurred while deleting providers.');
                }
            });
        });
    });

    $('#addProviderModal').on('hidden.bs.modal', function () {
        // Reset form fields
        $('#addProviderForm')[0].reset();

        // Reset the file input manually (some browsers don't do it on .reset())
        $('#providerLogoInput').val('');

        // Reset image preview
        $('#providerLogoPreview').attr('src', '<?= base_url("assets/img/icon/image.png") ?>');

        // Reset file name display
        $('#fileNameDisplay').text('No file selected').addClass('text-muted');

        // Clear hidden ID for safety
        $('#providerId').val('');

        // Optional: Reset modal title to default
        $('#addProviderModal .modal-title').text('Add Provider');

        // Optional: Remove validation errors (if you use client-side validation)
        $('#addProviderForm .is-invalid').removeClass('is-invalid');
        $('#addProviderForm .invalid-feedback').remove();
        $('#addProviderForm input, #addProviderForm textarea').prop('disabled', false);
        $('#providerLogoInput').prop('disabled', false);
        $('#addProviderForm .modal-footer .btn-primary, #addProviderForm .modal-footer [type="reset"]').show();
    });


</script>

<?= $this->endsection('script'); ?>