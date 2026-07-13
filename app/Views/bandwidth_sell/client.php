<?= $this->extend('layout/main-layout'); ?>

<?= $this->section('css'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/saas/bandwidth-pages.css?v=5'); ?>">
<?= $this->endSection('css'); ?>

<?= $this->section('content'); ?>

<div class="content-wrapper">
    <section class="content ipb-saas-list ipb-bw-page">

    <?= $this->include('components/page-header', [
      'title' => 'Sell Clients',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Bandwidth Sell'],
        ['label' => 'Clients'],
      ],
    ]); ?>

<div class="box box-warning">
            <div class="box-header with-border ipb-box-toolbar">
        <div class="ipb-list-toolbar">
          <div class="ipb-list-toolbar-filters">
            <span class="ipb-filter-label"><i class="fa fa-users" aria-hidden="true"></i> Clients</span>
          </div>
          <div class="ipb-list-toolbar-actions">
                    <button type="button" id="openModalBtn" class="btn btn-primary">
                        <i class="fa fa-plus" aria-hidden="true"></i> New client
                    </button>
                    <?php if (userHasPermission('customers', 'delete')) : ?>
                        <button type="button" class="btn btn-danger delete-btn">
                            <i class="far fa-trash-can" aria-hidden="true"></i> Delete
                        </button>
                    <?php endif; ?>
          </div>
        </div>
      </div>

            <div class="box-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <caption class="sr-only">Bandwidth sell clients</caption>
                        <thead>
                            <tr>
                                <th scope="col">Customer</th>
                                <th scope="col">Contact person</th>
                                <th scope="col">Email</th>
                                <th scope="col">Mobile</th>
                                <th scope="col">Balance due</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody id="customerTableBody">
                            <?php if (!empty($clients)) : ?>
                                <?php foreach ($clients as $client) : ?>
                                    <tr>
                                        <td><strong><?= esc($client['customer_name']); ?></strong></td>
                                        <td><?= esc($client['contact_person']); ?></td>
                                        <td><?= esc($client['email']); ?></td>
                                        <td><?= esc($client['mobile_number']); ?></td>
                                        <td><?= esc($client['customer_name'] ?? '0'); ?></td>
                                        <td>
                                            <div class="ipb-row-actions">
                                                <a href="<?= route_to('route.customer.payment.new', $client['id']); ?>"
                                                    class="ipb-row-btn tone-success" title="Payment">
                                                    <i class="fa fa-wallet" aria-hidden="true"></i>
                                                    <span class="sr-only">Payment</span>
                                                </a>
                                                <button type="button"
                                                    class="ipb-row-btn tone-info view-btn"
                                                    title="View"
                                                    data-id="<?= (int) $client['id']; ?>">
                                                    <i class="fa fa-eye" aria-hidden="true"></i>
                                                    <span class="sr-only">View</span>
                                                </button>
                                                <button type="button"
                                                    class="ipb-row-btn tone-brand edit-btn"
                                                    title="Edit"
                                                    data-id="<?= (int) $client['id']; ?>">
                                                    <i class="fa fa-edit" aria-hidden="true"></i>
                                                    <span class="sr-only">Edit</span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="6" class="ipb-bw-empty">No clients found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Client modal -->
<div id="addCustomerModal" class="custom-modal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="customerModalTitle">Create new client</h5>
                <button type="button" id="closeModalBtn" class="modal-close" aria-label="Close">&times;</button>
            </div>
            <div class="modal-body">
                <form id="customerForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="customer_id" id="customer_id">

                    <div class="ipb-client-steps" id="clientStepIndicator">
                        <div class="ipb-client-step active step-item" data-step="1">
                            <i class="fa fa-user" aria-hidden="true"></i>
                            <span>Customer info</span>
                        </div>
                        <div class="ipb-client-step step-item" data-step="2">
                            <i class="fa fa-network-wired" aria-hidden="true"></i>
                            <span>Transmission</span>
                        </div>
                        <div class="ipb-client-step step-item" data-step="3">
                            <i class="fa fa-lock" aria-hidden="true"></i>
                            <span>Login</span>
                        </div>
                    </div>
                    <div class="ipb-client-progress"><span id="clientProgressBar"></span></div>

                    <div class="step step-1 active">
                        <div class="ipb-client-grid">
                            <div class="ipb-client-field">
                                <label>Customer name *</label>
                                <input type="text" class="form-control" name="customer_name" required>
                            </div>
                            <div class="ipb-client-field">
                                <label>Customer code</label>
                                <input type="text" class="form-control" name="customer_code" value="0098">
                            </div>
                            <div class="ipb-client-field">
                                <label>Contact person *</label>
                                <input type="text" class="form-control" name="contact_person" required>
                            </div>
                            <div class="ipb-client-field">
                                <label>Email</label>
                                <input type="email" class="form-control" name="email">
                            </div>
                            <div class="ipb-client-field">
                                <label>Mobile number *</label>
                                <input type="text" class="form-control" name="mobile_number" required>
                            </div>
                            <div class="ipb-client-field">
                                <label>Phone number</label>
                                <input type="text" class="form-control" name="phone_number">
                            </div>
                            <div class="ipb-client-field">
                                <label>POP status *</label>
                                <select class="form-control" name="pop_status" required>
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                            </div>
                            <div class="ipb-client-field">
                                <label>Reference by</label>
                                <input type="text" class="form-control" name="reference_by">
                            </div>
                            <div class="ipb-client-field is-full">
                                <label>Address</label>
                                <textarea class="form-control" name="address" rows="3"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="step step-2">
                        <div class="ipb-client-grid">
                            <div class="ipb-client-field">
                                <label>NTTN info</label>
                                <input type="text" class="form-control" name="nttn_info">
                            </div>
                            <div class="ipb-client-field">
                                <label>SCR or link ID</label>
                                <input type="text" class="form-control" name="scr_id">
                            </div>
                            <div class="ipb-client-field">
                                <label>Activation date *</label>
                                <input type="date" class="form-control" name="activation_date" required>
                            </div>
                            <div class="ipb-client-field">
                                <label>POP name (last mile)</label>
                                <input type="text" class="form-control" name="pop_name">
                            </div>
                            <div class="ipb-client-field is-full">
                                <label>VLAN info</label>
                                <div id="vlanContainer" class="ipb-client-repeat">
                                    <div class="ipb-client-repeat-row vlan-group">
                                        <input type="text" name="vlan_name[]" class="form-control" placeholder="VLAN name">
                                        <input type="text" name="vlan_ip[]" class="form-control" placeholder="VLAN IP">
                                        <button type="button" class="btn btn-success add-vlan">+</button>
                                    </div>
                                </div>
                            </div>
                            <div class="ipb-client-field is-full">
                                <label>IP address</label>
                                <div id="ipContainer" class="ipb-client-repeat">
                                    <div class="ipb-client-repeat-row is-ip ip-group">
                                        <input type="text" name="ip_address[]" class="form-control" placeholder="IP address">
                                        <button type="button" class="btn btn-success add-ip">+</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="step step-3">
                        <div class="ipb-client-grid">
                            <div class="ipb-client-field">
                                <label>Username *</label>
                                <input type="text" class="form-control" name="username" required>
                            </div>
                            <div class="ipb-client-field ipb-pass-wrap">
                                <label>Password *</label>
                                <input type="password" class="form-control" name="password" id="password" required>
                                <i class="fa fa-eye-slash ipb-pass-toggle" id="togglePasswordIcon" onclick="togglePassword()" aria-hidden="true"></i>
                            </div>
                            <div class="ipb-client-field">
                                <label>Confirm password *</label>
                                <input type="password" class="form-control" name="confirm_password" required>
                            </div>
                            <div class="ipb-client-field">
                                <label>Activity status</label>
                                <select class="form-control" name="activity_status">
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="ipb-client-nav">
                        <button type="button" class="btn btn-default" id="prevBtn" disabled>Previous</button>
                        <button type="button" class="btn btn-primary" id="nextBtn">Next</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<script>
    function togglePassword() {
        const password = document.getElementById("password");
        const icon = document.getElementById("togglePasswordIcon");

        if (password.type === "password") {
            password.type = "text";
            icon.classList.remove("fa-eye-slash");
            icon.classList.add("fa-eye");
        } else {
            password.type = "password";
            icon.classList.remove("fa-eye");
            icon.classList.add("fa-eye-slash");
        }
    }

    // Modal functionality
    document.addEventListener('DOMContentLoaded', function() {


        document.getElementById('vlanContainer').addEventListener('click', function(e) {
            if (e.target.classList.contains('add-vlan')) {
                const vlanGroup = e.target.closest('.vlan-group');
                const clone = vlanGroup.cloneNode(true);
                clone.querySelectorAll('input').forEach(input => input.value = '');
                clone.querySelector('.add-vlan').classList.remove('add-vlan');
                clone.querySelector('.btn').classList.replace('btn-success', 'btn-danger');
                clone.querySelector('.btn').textContent = '–';
                clone.querySelector('.btn').classList.add('remove-vlan');
                vlanGroup.parentNode.appendChild(clone);
            } else if (e.target.classList.contains('remove-vlan')) {
                e.target.closest('.vlan-group').remove();
            }
        });

        // IP Address Logic
        document.getElementById('ipContainer').addEventListener('click', function(e) {
            if (e.target.classList.contains('add-ip')) {
                const ipGroup = e.target.closest('.ip-group');
                const clone = ipGroup.cloneNode(true);
                clone.querySelector('input').value = '';
                clone.querySelector('.add-ip').classList.remove('add-ip');
                clone.querySelector('.btn').classList.replace('btn-success', 'btn-danger');
                clone.querySelector('.btn').textContent = '–';
                clone.querySelector('.btn').classList.add('remove-ip');
                ipGroup.parentNode.appendChild(clone);
            } else if (e.target.classList.contains('remove-ip')) {
                e.target.closest('.ip-group').remove();
            }
        });


        const modal = document.getElementById('addCustomerModal');
        const openBtn = document.getElementById('openModalBtn');
        const closeBtn = document.getElementById('closeModalBtn');
        const modalTitle = document.getElementById('customerModalTitle');
        let customerModalMode = 'add'; // add | edit | view

        function setCustomerFormReadonly(readonly) {
            const form = document.getElementById('customerForm');
            form.querySelectorAll('input, select, textarea, button.add-vlan, button.remove-vlan, button.add-ip, button.remove-ip')
                .forEach(el => {
                    if (el.type === 'hidden') return;
                    el.disabled = readonly;
                });
        }

        function setCustomerModalMode(mode) {
            customerModalMode = mode;
            const isView = mode === 'view';
            setCustomerFormReadonly(isView);
            modal.classList.toggle('is-view-mode', isView);

            if (mode === 'view') {
                modalTitle.textContent = 'Client details';
            } else if (mode === 'edit') {
                modalTitle.textContent = 'Edit client';
            } else {
                modalTitle.textContent = 'Create new client';
            }

            if (currentStep === 3) {
                nextBtn.textContent = isView ? 'Close' : 'Submit';
            } else {
                nextBtn.textContent = 'Next';
            }
        }

        function openCustomerModal() {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function fillCustomerForm(customer) {
            document.getElementById('customer_id').value = customer.id || '';

            const setVal = (sel, val) => {
                const el = document.querySelector(sel);
                if (el) el.value = val ?? '';
            };

            setVal('input[name="customer_name"]', customer.customer_name);
            setVal('input[name="customer_code"]', customer.customer_code);
            setVal('input[name="contact_person"]', customer.contact_person);
            setVal('input[name="email"]', customer.email);
            setVal('input[name="mobile_number"]', customer.mobile_number);
            setVal('input[name="phone_number"]', customer.phone_number);
            setVal('select[name="pop_status"]', customer.pop_status);
            setVal('input[name="reference_by"]', customer.reference_by);
            setVal('textarea[name="address"]', customer.address);
            setVal('input[name="nttn_info"]', customer.nttn_info);
            setVal('input[name="scr_id"]', customer.scr_id);
            setVal('input[name="activation_date"]', customer.activation_date);
            setVal('input[name="pop_name"]', customer.pop_name);
            setVal('input[name="username"]', customer.username);
            setVal('input[name="password"]', customerModalMode === 'view' ? '••••••••' : '');
            setVal('input[name="confirm_password"]', '');
            setVal('select[name="activity_status"]', customer.activity_status);

            document.getElementById('vlanContainer').innerHTML = '';
            let vlans = [];
            try { vlans = JSON.parse(customer.vlan_info || '[]'); } catch (e) { vlans = []; }
            if (!Array.isArray(vlans) || vlans.length === 0) {
                vlans = [{ name: '', ip: '' }];
            }
            vlans.forEach((vlan, index) => {
                const vlanHTML = `
                    <div class="ipb-client-repeat-row vlan-group">
                        <input type="text" name="vlan_name[]" class="form-control" placeholder="VLAN name" value="${vlan.name || ''}">
                        <input type="text" name="vlan_ip[]" class="form-control" placeholder="VLAN IP" value="${vlan.ip || ''}">
                        <button type="button" class="btn ${index === 0 ? 'btn-success add-vlan' : 'btn-danger remove-vlan'}">${index === 0 ? '+' : '–'}</button>
                    </div>`;
                document.getElementById('vlanContainer').insertAdjacentHTML('beforeend', vlanHTML);
            });

            document.getElementById('ipContainer').innerHTML = '';
            let ips = [];
            try { ips = JSON.parse(customer.ip_addresses || '[]'); } catch (e) { ips = []; }
            if (!Array.isArray(ips) || ips.length === 0) {
                ips = [''];
            }
            ips.forEach((ip, index) => {
                const ipHTML = `
                    <div class="ipb-client-repeat-row is-ip ip-group">
                        <input type="text" name="ip_address[]" class="form-control" placeholder="IP address" value="${ip || ''}">
                        <button type="button" class="btn ${index === 0 ? 'btn-success add-ip' : 'btn-danger remove-ip'}">${index === 0 ? '+' : '–'}</button>
                    </div>`;
                document.getElementById('ipContainer').insertAdjacentHTML('beforeend', ipHTML);
            });
        }

        function loadCustomer(customerId, mode) {
            fetch(`<?= route_to('bandwidth_sell_client.edit', 0) ?>`.replace('/0', `/${customerId}`))
                .then(res => res.json())
                .then(data => {
                    if (data.status !== 'success') {
                        alert(data.message || 'Failed to load client data.');
                        return;
                    }

                    currentStep = 1;
                    steps.forEach((step, i) => step.classList.toggle('active', i === 0));
                    updateStepIndicator(1);
                    prevBtn.disabled = true;

                    setCustomerModalMode(mode);
                    fillCustomerForm(data.customer);
                    setCustomerFormReadonly(mode === 'view');
                    nextBtn.textContent = 'Next';
                    openCustomerModal();
                })
                .catch(err => {
                    console.error(err);
                    tata.error("Couldn't load client", "The client details didn't load. Refresh and try again.");
                });
        }

        document.addEventListener('click', function(e) {
            const viewBtn = e.target.closest('.view-btn');
            const editBtn = e.target.closest('.edit-btn');
            if (viewBtn) {
                e.preventDefault();
                loadCustomer(viewBtn.dataset.id, 'view');
            } else if (editBtn) {
                e.preventDefault();
                loadCustomer(editBtn.dataset.id, 'edit');
            }
        });

        function seedRepeatRows() {
            document.getElementById('vlanContainer').innerHTML = `
                <div class="ipb-client-repeat-row vlan-group">
                    <input type="text" name="vlan_name[]" class="form-control" placeholder="VLAN name">
                    <input type="text" name="vlan_ip[]" class="form-control" placeholder="VLAN IP">
                    <button type="button" class="btn btn-success add-vlan">+</button>
                </div>`;
            document.getElementById('ipContainer').innerHTML = `
                <div class="ipb-client-repeat-row is-ip ip-group">
                    <input type="text" name="ip_address[]" class="form-control" placeholder="IP address">
                    <button type="button" class="btn btn-success add-ip">+</button>
                </div>`;
        }

        // Open modal for new client
        openBtn.addEventListener('click', function() {
            document.getElementById('customerForm').reset();
            document.getElementById('customer_id').value = '';
            seedRepeatRows();
            currentStep = 1;
            steps.forEach((step, index) => step.classList.toggle('active', index === 0));
            updateStepIndicator(1);
            prevBtn.disabled = true;
            setCustomerModalMode('add');
            openCustomerModal();
        });

        function resetCustomerForm() {
            document.getElementById('customerForm').reset();
            document.getElementById('customer_id').value = '';
            currentStep = 1;

            steps.forEach((step, index) => {
                step.classList.toggle('active', index === 0);
            });

            updateStepIndicator(1);
            nextBtn.textContent = 'Next';
            prevBtn.disabled = true;
            setCustomerFormReadonly(false);
            customerModalMode = 'add';
            modalTitle.textContent = 'Create new client';
            modal.classList.remove('is-view-mode');

            modal.classList.remove('active');
            document.body.style.overflow = '';

            seedRepeatRows();
        }

        // Close modal
        closeBtn.addEventListener('click', function() {
            resetCustomerForm();
        });


        // Close modal when clicking outside
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                resetCustomerForm();
            }
        });


        // Step navigation
        let currentStep = 1;
        const steps = document.querySelectorAll('.step');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');

        const stepIndicators = document.querySelectorAll('.step-item');
        const progressBar = document.getElementById('clientProgressBar');

        function updateStepIndicator(step) {
            stepIndicators.forEach((el, idx) => {
                const n = idx + 1;
                el.classList.toggle('active', n === step);
                el.classList.toggle('done', n < step);
            });
            if (progressBar) {
                progressBar.style.width = ((step / 3) * 100) + '%';
            }
        }

        // Initialize steps
        steps.forEach((step, index) => {
            if (index !== 0) step.classList.remove('active');
        });


        // Next button functionality
        nextBtn.addEventListener('click', function() {
            if (currentStep < 3) {
                // Hide current step
                document.querySelector(`.step-${currentStep}`).classList.remove('active');
                currentStep++;
                document.querySelector(`.step-${currentStep}`).classList.add('active');
                updateStepIndicator(currentStep);


                // Enable previous button
                prevBtn.disabled = false;

                // Change button text on last step
                if (currentStep === 3) {
                    nextBtn.textContent = customerModalMode === 'view' ? 'Close' : 'Submit';
                }
            } else if (customerModalMode === 'view') {
                resetCustomerForm();
            } else {
                // Collect form data
                const formData = new FormData(document.getElementById('customerForm'));

                const customerId = document.getElementById('customer_id').value;

                let url = "<?= route_to('bandwidth_sell_client.save') ?>";
                if (customerId) {
                    url = `<?= route_to('bandwidth_sell_client.update', 0) ?>`.replace('/0', `/${customerId}`);
                }


                // Send AJAX request
                fetch(url, {
                        method: "POST",
                        body: formData,
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            alert("Customer saved successfully!");
                            document.getElementById('customerForm').reset();
                            modal.classList.remove('active');
                            document.body.style.overflow = '';
                            // Optionally refresh customer list or reload
                        } else {
                            if (typeof data.message === 'object') {
                                let errorMessages = '';
                                for (const field in data.message) {
                                    if (data.message.hasOwnProperty(field)) {
                                        errorMessages += `• ${data.message[field]}\n`;
                                    }
                                }
                                alert("Validation Error:\n" + errorMessages);
                            } else {
                                alert("Error: " + data.message);
                            }

                        }
                    })
                    .catch(error => {
                        console.error("Error:", error);
                        alert("An unexpected error occurred.");
                    });

            }
        });

        // Previous button functionality
        prevBtn.addEventListener('click', function() {
            if (currentStep > 1) {
                // Hide current step
                document.querySelector(`.step-${currentStep}`).classList.remove('active');
                currentStep--;
                document.querySelector(`.step-${currentStep}`).classList.add('active');
                updateStepIndicator(currentStep);

                nextBtn.textContent = 'Next';

                if (currentStep === 1) {
                    prevBtn.disabled = true;
                }
            }
        });
    });
</script>

<?= $this->endSection(); ?>