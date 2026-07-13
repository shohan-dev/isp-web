<?= $this->extend('layout/main-layout'); ?>


<?= $this->section('content'); ?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Main content -->
    <section class="content ipb-saas-list">
        
    <?= $this->include('components/page-header', [
      'title' => 'Add Mac POPs',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Add Mac POPs'],
      ],
    ]); ?>
<div id="success-message" class="alert alert-success" role="alert" style="display: none;">
            POPs created successful!
        </div>
        <div id="feedback"></div>


        <div class="container mt-5">

            <form id="reseller-form" action="<?= route_to('route.Reseller.submit') ?>" method="post">
                <?= csrf_field() ?>

                <!-- Personal Info Section -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h3>Personal Information</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label for="admin_name"> Name *</label>
                                <input type="text" class="form-control" id="admin_name" name="admin_name" required>
                                <div class="text-danger" id="admin_name-error"></div>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="mobile">Mobile *</label>
                                <input type="text" class="form-control" id="mobile" name="mobile" required>
                                <div class="text-danger" id="mobile-error"></div>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="email">E-mail *</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                                <div class="text-danger" id="email-error"></div>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="password">Password *</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <div class="text-danger" id="password-error"></div>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="confirm_password">Confirm Password *</label>
                                <input type="password" class="form-control" id="confirm_password"
                                    name="confirm_password" required>
                                <div class="text-danger" id="confirm_password-error"></div>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="nationalid">National Id *</label>
                                <input type="text" class="form-control" id="nationalid" name="nationalid" required>
                                <div class="text-danger" id="nationalid-error"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Business Info Section -->
                <div class="card mb-5">
                    <div class="card-header">
                        <h3>Business Information</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- <div class="form-group col-md-6">
                                <label for="organization_name">Organization Name *</label>
                                <input type="text" class="form-control" id="organization_name" name="organization_name" required>
                                <div class="text-danger" id="organization_name-error"></div>
                            </div> -->
                            <div class="form-group col-md-6">
                                <label for="division">Division *</label>
                                <select class="form-control" name="division" id="division" required>
                                    <option value="">Select Division</option>
                                    <option value="Barishal">Barishal</option>
                                    <option value="Chattogram">Chattogram</option>
                                    <option value="Dhaka">Dhaka</option>
                                    <option value="Khulna">Khulna</option>
                                    <option value="Rajshahi">Rajshahi</option>
                                    <option value="Rangpur">Rangpur</option>
                                    <option value="Sylhet">Sylhet</option>
                                    <option value="Mymensingh">Mymensingh</option>
                                </select>
                                <div class="text-danger" id="division-error"></div>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="district">District *</label>
                                <select class="form-control" name="district" id="district" required>
                                    <option value="">Select District</option>
                                </select>
                                <div class="text-danger" id="district-error"></div>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="upazilla">Upazilla/Thana *</label>
                                <input type="text" class="form-control" id="upazilla" name="upazilla" required>
                                <div class="text-danger" id="upazilla-error"></div>
                            </div>

                            <div class="form-group col-md-6">
                                <label for="reference_name">Reference Name</label>
                                <input type="text" class="form-control" id="reference_name" name="reference_name">
                                <div class="text-danger" id="reference_name-error"></div>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="reference_mobile">Reference Mobile</label>
                                <input type="text" class="form-control" id="reference_mobile" name="reference_mobile">
                                <div class="text-danger" id="reference_mobile-error"></div>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="address">Address *</label>
                                <input type="text" class="form-control" id="address" name="address" required>
                                <div class="text-danger" id="address-error"></div>
                            </div>
                            <!-- <div class="form-group col-md-6">
                                <label for="address">Discount *</label>
                                <input type="text" class="form-control" id="discount" name="discount" required>
                                <div class="text-danger" id="discount-error"></div>
                            </div> -->
                            <!-- <div class="form-group col-md-6">
                                <label for="package">Select Your Preferable Package *</label>
                                <select class="form-control" id="package" name="package" required>
                                    <option value="">Select Package</option>
                                    <?php if (!empty($packages) && is_array($packages)): ?>
                                        <?php foreach ($packages as $package): ?>
                                            <option value="<?= $package['id'] ?>"
                                                data-bandwidth="<?= $package['bandwidth'] ?>"
                                                data-package-details="<?= $package['price'] ?>">
                                                <?= $package['package_name'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6">No packages found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </select>
                                <div class="text-danger" id="package-error"></div>
                            </div> -->

                            <div class="form-group col-lg-6">
                                <label>Mikrotik Router</label>

                                <?php $data = array();

                                if (empty($routers)):

                                    $data[''] = 'No router found!';

                                else:

                                    $data = ['' => '--Select--'];

                                    foreach ($routers as $router) {
                                        $data[$router->id] = $router->name;
                                    }

                                endif;

                                echo form_dropdown('router_id', $data, "", 'class="form-control"'); ?>

                                <small id="router_id-error" class="error text-danger"></small>
                            </div>

                            <div class="form-group col-md-6" id="billing_type_group">
                                <label for="billing_type">Billing Type *</label>
                                <select class="form-control" name="billing_type" id="billing_type" required>
                                    <option value="postpaid" selected>Postpaid</option>
                                    <option value="prepaid">Prepaid</option>
                                </select>
                                <div class="text-danger" id="billing_type-error"></div>
                            </div>

                            <div class="form-group col-md-6" id="validity_periods_group">
                                <label>Allowed Validity Periods (Days)</label>
                                <input type="text" class="form-control" name="reseller_validity_periods"
                                    value="3,5,7,30" placeholder="e.g. 3,5,7,15,30">
                                <small class="text-muted">Enter days separated by commas. Example: <code>3,5,7,30</code></small>
                                <div class="text-danger" id="reseller_validity_periods-error"></div>
                            </div>
                            <div class="form-group col-md-6">
                                <label>Customer Type</label>
                                <div class="row">
                                    <div class="form-check mr-3 col-md-2 p-0">
                                        <input class="form-check-input" type="checkbox" name="customer_type[]"
                                            id="pppoe" value="PPPOE">
                                        <label class="form-check-label" for="pppoe">PPPOE</label>
                                    </div>
                                    <div class="form-check mr-3 col-md-2">
                                        <input class="form-check-input" type="checkbox" name="customer_type[]"
                                            id="static" value="Static">
                                        <label class="form-check-label" for="static">Static</label>
                                    </div>
                                    <div class="form-check col-md-2">
                                        <input class="form-check-input" type="checkbox" name="customer_type[]"
                                            id="hotspot" value="Hotspot">
                                        <label class="form-check-label" for="hotspot">Hotspot</label>
                                    </div>
                                </div>
                                <div class="text-danger" id="customer_type-error"></div>
                            </div>
                            <!-- <div class="form-group col-md-6">
                                <label for="package">Select Your Preferable Package *</label>
                                <button type="button" id="package-info" class="btn btn-primary col-md-12"
                                    disabled>Customers: Sign Up Fee: Month Fee:</button>
                            </div> -->
                        </div>
                    </div>
                </div>

                <div class="" style="margin-top: 5vh; display:flex ;justify-content: center; gap: 2vw;">
                    <button type="submit" class="btn btn-primary" style="padding: 10px;">Register</button>
                    <button type="reset" class="btn btn-info" style="padding: 10px;">Cancel</button>
                </div>
            </form>
        </div>
    </section>
    <!-- /.content -->
</div>

<?= $this->endSection('content'); ?>

<?= $this->section('script'); ?>

<script>
    const districts = {
        "Barishal": [
            { id: 34, name: 'Barguna' },
            { id: 35, name: 'Barishal' },
            { id: 36, name: 'Bhola' },
            { id: 37, name: 'Jhalokati' },
            { id: 38, name: 'Patuakhali' },
            { id: 39, name: 'Pirojpur' }
        ],
        "Chattogram": [
            { id: 40, name: 'Bandarban' },
            { id: 41, name: 'Brahmanbaria' },
            { id: 42, name: 'Chandpur' },
            { id: 43, name: 'Chattogram' },
            { id: 44, name: 'Cumilla' },
            { id: 45, name: "Cox's Bazar" },
            { id: 46, name: 'Feni' },
            { id: 47, name: 'Khagrachari' },
            { id: 48, name: 'Lakshmipur' },
            { id: 49, name: 'Noakhali' },
            { id: 50, name: 'Rangamati' }
        ],
        "Dhaka": [
            { id: 1, name: 'Dhaka' },
            { id: 2, name: 'Faridpur' },
            { id: 3, name: 'Gazipur' },
            { id: 4, name: 'Gopalganj' },
            { id: 6, name: 'Kishoreganj' },
            { id: 7, name: 'Madaripur' },
            { id: 8, name: 'Manikganj' },
            { id: 9, name: 'Munshiganj' },
            { id: 11, name: 'Narayanganj' },
            { id: 12, name: 'Narsingdi' },
            { id: 14, name: 'Rajbari' },
            { id: 15, name: 'Shariatpur' },
            { id: 17, name: 'Tangail' }
        ],
        "Khulna": [
            { id: 55, name: 'Bagerhat' },
            { id: 56, name: 'Chuadanga' },
            { id: 57, name: 'Jessore' },
            { id: 58, name: 'Jhenaidah' },
            { id: 59, name: 'Khulna' },
            { id: 60, name: 'Kushtia' },
            { id: 61, name: 'Magura' },
            { id: 62, name: 'Meherpur' },
            { id: 63, name: 'Narail' },
            { id: 64, name: 'Satkhira' }
        ],
        "Rajshahi": [
            { id: 18, name: 'Bogura' },
            { id: 19, name: 'Joypurhat' },
            { id: 20, name: 'Naogaon' },
            { id: 21, name: 'Natore' },
            { id: 22, name: 'Nawabganj' },
            { id: 23, name: 'Pabna' },
            { id: 24, name: 'Rajshahi' },
            { id: 25, name: 'Sirajganj' }
        ],
        "Rangpur": [
            { id: 26, name: 'Dinajpur' },
            { id: 27, name: 'Gaibandha' },
            { id: 28, name: 'Kurigram' },
            { id: 29, name: 'Lalmonirhat' },
            { id: 30, name: 'Nilphamari' },
            { id: 31, name: 'Panchagarh' },
            { id: 32, name: 'Rangpur' },
            { id: 33, name: 'Thakurgaon' }
        ],
        "Sylhet": [
            { id: 51, name: 'Habiganj' },
            { id: 52, name: 'Moulvibazar' },
            { id: 53, name: 'Sunamganj' },
            { id: 54, name: 'Sylhet' }
        ],
        "Mymensingh": [
            { id: 5, name: 'Jamalpur' },
            { id: 10, name: 'Mymensingh' },
            { id: 13, name: 'Netrokona' },
            { id: 16, name: 'Sherpur' }
        ]
    };

    $(document).ready(function () {
        $('#division').change(function () {
            const selectedDivision = $(this).val();
            const districtSelect = $('#district');

            districtSelect.empty().append('<option value="">Select District</option>');

            if (selectedDivision && districts[selectedDivision]) {
                districts[selectedDivision].forEach(function (district) {
                    districtSelect.append('<option value="' + district.id + '">' + district.name + '</option>');
                });
            }
        });

        function toggleValidityPeriods() {
            if ($('#billing_type').val() === 'prepaid') {
                $('#validity_periods_group').hide();
            } else {
                $('#validity_periods_group').show();
            }
        }
        $('#billing_type').on('change', toggleValidityPeriods);
        toggleValidityPeriods();

        $('#package').on('change', function () {
            const selectedOption = $(this).find('option:selected');
            const bandwidth = selectedOption.data('bandwidth');
            const packageDetails = selectedOption.data('package-details');

            $('#package-info').text('Bandwidth Allocation: ' + bandwidth + ', Package Details: ' + packageDetails);
        });

        $('#reseller-form').on('submit', function (e) {
            e.preventDefault();
            const form = this;
            $.ajax({
                type: $(form).attr('method'),
                url: $(form).attr('action'),
                data: $(form).serialize(),
                beforeSend: function () {
                    $(form).find('.error').html("");
                    $(form).find('#feedback').html("");
                    $(form).find('button[type="submit"]').html("<i class='fas fa-spinner fa-spin'></i> Please wait").attr('disabled', true);
                },
                success: function (result) {
                    $(form).find('button[type="submit"]').html('Register').removeAttr('disabled');
                    if (result.status === 'validation-error') {
                        $.each(result.errors, function (prefix, val) {
                            $(form).find('#' + prefix + '-error').text(val);
                        });
                    } else if (result.status === 'success') {
                        $(form).trigger('reset');
                        tata.success('Reseller registered', result.message, {
                            onClose: () => {
                                location.href = '<?= route_to("route.reseller"); ?>';
                            }
                        });
                    } else {
                        tata.error("Couldn't register reseller", result.message);
                    }
                },
                error: function ({ responseText }) {
                    const result = JSON.parse(responseText);
                    $(form).find('button[type="submit"]').html('Register').removeAttr('disabled');
                    if (result.status === 'validation-error') {
                        $.each(result.errors, function (prefix, val) {
                            $(form).find('#' + prefix + '-error').text(val);
                        });
                    } else {
                        tata.error("Couldn't register reseller", result.message);
                    }
                }
            });
        });
    });



</script>

<?= $this->endSection('script'); ?>