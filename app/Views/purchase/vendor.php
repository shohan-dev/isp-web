<?= $this->extend('layout/main-layout'); ?>
<?= $this->section('content'); ?>
<div class="content-wrapper">
    <section class="content ipb-saas-list">
        
    <?= $this->include('components/page-header', [
      'title' => 'Vendor Management',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Vendor Management'],
      ],
    ]); ?>

<div class="box box-primary">
            <div class="box-header with-border ipb-box-toolbar">
        <div class="ipb-list-toolbar">
          <div class="ipb-list-toolbar-filters">
            <span class="ipb-filter-label"><i class="fa fa-list" aria-hidden="true"></i> Records</span>
          </div>
          <div class="ipb-list-toolbar-actions">
<!-- <button class="btn btn-success">
                        <i class="fa fa-download"></i> Export
                    </button> -->
                    <button class="btn btn-primary" data-toggle="modal" data-target="#addProviderModal">
                        <i class="fa fa-plus"></i> Add vendor
                    </button>
                    <button id="bulkDeleteBtn" class="btn btn-danger"><i class="fa fa-trash"></i> Delete
                        Selected</button>
          </div>
        </div>
      </div>

            <div class="box-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <caption class="sr-only">Vendor list</caption>
                        <thead>
                            <tr>
                                <th width="50" scope="col"><input type="checkbox" class="form-check-input" id="select_all"></th>
                                <th scope="col">Logo</th>
                                <th scope="col">vendor Name</th>
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
                                        <!-- If no logo, show the default image from another folder -->
                                        <img src="<?= isset($provider['image']) && !empty($provider['image']) ? base_url('assets/img/company_logo/' . esc($provider['image'])) : base_url('assets/img/icon/image.png') ?>"
                                            alt="Company Logo" style="max-width: 50px; max-height: 50px;">
                                    </td>



                                    <td><?= !empty($provider['company_name']) ? esc($provider['company_name']) : '--' ?>
                                    </td>
                                    <td><?= !empty($provider['contact_person']) ? esc($provider['contact_person']) : '--' ?>
                                    </td>
                                    <td><?= !empty($provider['mobile_number']) ? esc($provider['mobile_number']) : '--' ?>
                                    </td>
                                    <td><?= !empty($provider['email']) ? esc($provider['email']) : '--' ?></td>
                                    <td><?= !empty($provider['address']) ? esc($provider['address']) : '--' ?></td>


                                    <td>
                                        <button class="btn btn-xs btn-primary editProviderBtn"
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
                                            data-image="<?= esc($provider['image']) ?>" data-bs-toggle="tooltip"
                                            data-bs-placement="bottom" title="Edit" aria-label="Edit vendor">
                                            <i class="fa fa-edit" aria-hidden="true"></i>
                                        </button>


                                        <button class="btn btn-xs btn-info viewProviderBtn" data-id="<?= $provider['id'] ?>"
                                            data-company_name="<?= esc($provider['company_name']) ?>"
                                            data-contact_person="<?= esc($provider['contact_person']) ?>"
                                            data-email="<?= esc($provider['email']) ?>"
                                            data-phone_number="<?= esc($provider['phone_number']) ?>"
                                            data-mobile_number="<?= esc($provider['mobile_number']) ?>"
                                            data-facebook_url="<?= esc($provider['facebook_url']) ?>"
                                            data-skype_id="<?= esc($provider['skype_id']) ?>"
                                            data-website="<?= esc($provider['website']) ?>"
                                            data-address="<?= esc($provider['address']) ?>"
                                            data-image="<?= esc($provider['image']) ?>" data-bs-toggle="tooltip"
                                            data-bs-placement="bottom" title="Details" aria-label="View vendor details">
                                            <i class="fa fa-book" aria-hidden="true"></i>
                                        </button>


                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($providers)): ?>
                                <tr>
                                    <td colspan="8" class="text-center">No vendors found.</td>
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
                <h5 class="modal-title">Add Vendor</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span style="font-size: 30px;">&times;</span>
                </button>
            </div>
            <form id="addProviderForm" action="<?= route_to('bandwidth.vendor_store') ?>" method="POST"
                enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row">
                        <!-- Image Section -->
                        <div class="col-md-3 text-center">
                            <div class="position-relative">
                                <!-- Image Preview -->
                                <img id="providerLogoPreview" src="..." class="img-thumbnail"
     style="max-width: 150px; max-height: 150px; width: auto; height: auto;" alt="Provider Logo">


                                <!-- Custom Upload Button -->
                                <label for="providerLogoInput" class="position-absolute"
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

    $(document).on('click', '.viewProviderBtn', function () {
        const button = $(this);

        // Set form fields
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
        $('#fileNameDisplay').text('Existing file used').addClass('text-muted');

        // Disable all form fields
        $('#addProviderForm input, #addProviderForm textarea').prop('disabled', true);
        $('#providerLogoInput').prop('disabled', true);

        // Hide Save and Clear buttons, keep Close only
        $('#addProviderForm .modal-footer .btn-primary, #addProviderForm .modal-footer [type="reset"]').hide();

        // Show modal with updated title
        $('#addProviderModal .modal-title').text('Provider Details');
        $('#addProviderModal').modal('show');
    });


    $(document).on('click', '.editProviderBtn', function () {
        const button = $(this);

        $('#addProviderModal').modal('show');
        $('#addProviderModal .modal-title').text('Edit Provider');

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
        $('#fileNameDisplay').text('Existing file used').addClass('text-muted');
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
                    tata.success('Vendor saved', response.message);
                    location.reload();
                } else {
                    tata.error("Couldn't save vendor", response.message);
                }
                location.reload();
            },
            error: function (xhr) {
                tata.error("Couldn't save vendor", xhr.responseJSON?.message || 'Something went wrong');
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
                url: '<?= route_to("bandwidth.vendor_delete") ?>',
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