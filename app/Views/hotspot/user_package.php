<?= $this->extend('layout/main-layout'); ?>
<?= $this->section('content'); ?>

<?php $profiles = $profiles ?? []; ?>
<?php $queues   = $queues ?? []; ?>
<?php $pools    = $pools ?? []; ?>

<div class="content-wrapper">
    <section class="content ipb-saas-list">
        
    <?= view('components/page-header', [
      'title' => 'Hotspot Packages',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Hotspot Packages'],
      ],
    ]); ?>

<div class="box box-warning">
            <div class="box-header with-border ipb-box-toolbar">
                <div class="ipb-list-toolbar">
                  <div class="ipb-list-toolbar-filters">
                    <span class="ipb-filter-label"><i class="fa fa-users" aria-hidden="true"></i> User profiles</span>
                    <button type="button" class="btn btn-default btn-sm" onclick="$('#routerContextModal').modal('show')">
                      <i class="fa fa-network-wired" aria-hidden="true"></i>
                      <span id="activeRouterName">Select Router</span>
                    </button>
                  </div>
                  <div class="ipb-list-toolbar-actions">
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addUserProfileModal">
                        <i class="fa fa-plus" aria-hidden="true"></i> Add
                    </button>
                  </div>
                </div>
            </div>

            <div class="box-body table-responsive">
                <table class="table table-bordered table-hover text-center" id="profilesTable">
                    <caption class="sr-only">Hotspot user profiles</caption>
                    <thead style="background:#2f363d;color:#fff;">
                        <tr>
                            <th width="80" scope="col">Action</th>
                            <th scope="col">Name</th>
                            <th scope="col">Shared Users</th>
                            <th scope="col">Rate Limit</th>
                            <th scope="col">Address Pool</th>
                            <th scope="col">Parent Queue</th>
                            <th scope="col">Expired Mode</th>
                            <th scope="col">Validity</th>
                            <th scope="col">Price (BDT)</th>
                            <th scope="col">Selling Price (BDT)</th>
                            <th scope="col">Lock User</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <?= view('components/skeleton-table', ['cols' => 12, 'rows' => 6]) ?>
                </table>
            </div>
        </div>
    </section>
</div>

<!-- Add User Profile Modal -->
<div class="modal fade" id="addUserProfileModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="addUserProfileForm" action="<?= route_to('route.user.profile.store'); ?>" method="POST">
                <?= csrf_field(); ?>
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><i class="fa fa-plus"></i> Add User Profile</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" class="form-control" id="name" name="name" placeholder="Enter profile name" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="address_pool">Address Pool</label>
                            <select class="form-control" id="address_pool" name="address_pool">
                                <option value="none">none</option>
                                <?php foreach ($pools as $pool): ?>
                                    <option value="<?= htmlspecialchars($pool['name']) ?>"><?= htmlspecialchars($pool['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="shared_users">Shared Users</label>
                            <input type="number" class="form-control" id="shared_users" name="shared_users" value="1" min="1" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="rate_limit">Rate limit [up/down]</label>
                        <input type="text" class="form-control" id="rate_limit" name="rate_limit" placeholder="512k/1M">
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="expired_mode">Expired Mode</label>
                            <select class="form-control" id="expired_mode" name="expired_mode">
                                <option value="rem">Remove</option>
                                <option value="ntf">Notify</option>
                                <option value="remc" selected>Remove & Record</option>
                                <option value="ntfc">Notify & Record</option>
                                <option value="0">No Expiration</option>
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="validity">Validity</label>
                            <input type="text" class="form-control" id="validity" name="validity" placeholder="30d">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="price_bdt">Price (BDT)</label>
                            <input type="number" class="form-control" id="price_bdt" name="price_bdt" step="0.01" value="0.00">
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="selling_price_bdt">Selling Price (BDT)</label>
                            <input type="number" class="form-control" id="selling_price_bdt" name="selling_price_bdt" step="0.01" value="0.00">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="lock_user">Lock User</label>
                            <select class="form-control" id="lock_user" name="lock_user">
                                <option value="Disable">Disable</option>
                                <option value="Enable">Enable</option>
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="parent_queue">Parent Queue</label>
                            <select class="form-control" id="parent_queue" name="parent_queue">
                                <option value="none">none</option>
                                <?php foreach ($queues as $queue): ?>
                                    <option value="<?= htmlspecialchars($queue['name']) ?>"><?= htmlspecialchars($queue['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editUserProfileModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="editUserProfileForm" action="<?= route_to('route.user.profile.update'); ?>" method="POST">
                <?= csrf_field(); ?>
                <!-- <input type="hidden" name="_method" value="PUT"> -->
                <input type="hidden" id="edit_profile_id" name="id">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><i class="fa fa-edit"></i> Edit User Profile</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_name">Name</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="edit_address_pool">Address Pool</label>
                            <select class="form-control" id="edit_address_pool" name="address_pool">
                                <option value="none">none</option>
                                <?php foreach ($pools as $pool): ?>
                                    <option value="<?= htmlspecialchars($pool['name']) ?>"><?= htmlspecialchars($pool['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="edit_shared_users">Shared Users</label>
                            <input type="number" class="form-control" id="edit_shared_users" name="shared_users" min="1" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="edit_rate_limit">Rate limit [up/down]</label>
                        <input type="text" class="form-control" id="edit_rate_limit" name="rate_limit" placeholder="512k/1M">
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="edit_expired_mode">Expired Mode</label>
                            <select class="form-control" id="edit_expired_mode" name="expired_mode">
                                <option value="rem">Remove</option>
                                <option value="ntf">Notify</option>
                                <option value="remc">Remove & Record</option>
                                <option value="ntfc">Notify & Record</option>
                                <option value="0">No Expiration</option>
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="edit_validity">Validity</label>
                            <input type="text" class="form-control" id="edit_validity" name="validity" placeholder="30d">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="edit_price_bdt">Price (BDT)</label>
                            <input type="number" class="form-control" id="edit_price_bdt" name="price_bdt" step="0.01" min="0">
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="edit_selling_price_bdt">Selling Price (BDT)</label>
                            <input type="number" class="form-control" id="edit_selling_price_bdt" name="selling_price_bdt" step="0.01" min="0">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="edit_lock_user">Lock User</label>
                            <select class="form-control" id="edit_lock_user" name="lock_user">
                                <option value="Disable">Disable</option>
                                <option value="Enable">Enable</option>
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="edit_parent_queue">Parent Queue</label>
                            <select class="form-control" id="edit_parent_queue" name="parent_queue">
                                <option value="none">none</option>
                                <?php foreach ($queues as $queue): ?>
                                    <option value="<?= htmlspecialchars($queue['name']) ?>"><?= htmlspecialchars($queue['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="routerContextModal" data-backdrop="static">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Select Router</h5>
                <!-- Default close button -->
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <select id="ctx_router" class="form-control mb-2"></select>
            </div>

            <div class="modal-footer">
                <button class="btn btn-primary btn-block" onclick="saveHotspotCtx()">Save</button>
            </div>

        </div>
    </div>
</div>

<?= $this->endSection(); ?>
<?= $this->section('script'); ?>

<script>
    const HOTSPOT_USER_ID = <?= getSession('user_id') ?? 0 ?>;
</script>
<script>
    const HOTSPOT_CTX_KEY = 'hotspot_ctx';

    // Get saved context
    function getCtx() {
        try {
            return JSON.parse(localStorage.getItem(HOTSPOT_CTX_KEY));
        } catch {
            return null;
        }
    }

    // Save selected router/hotspot to localStorage
    function saveHotspotCtx() {
        const ctx = {
            user_id: HOTSPOT_USER_ID,
            router_id: $('#ctx_router').val(),
            router_name: $('#ctx_router option:selected').text(),
        };

        localStorage.setItem(HOTSPOT_CTX_KEY, JSON.stringify(ctx));
        $('#routerContextModal').modal('hide');
        updateCtxBadge(ctx);
        fetchProfiles(); // Refresh table
    }

    // Show selected router beside Add button
    function updateCtxBadge(ctx) {
        $('#activeRouterName').text(ctx.router_name || 'Select Router');
    }

    // Populate router dropdown
    function populateRouterDropdown() {
        const routers = <?= json_encode($routers); ?>;
        const select = $('#ctx_router');
        select.empty();
        routers.forEach(r => select.append(new Option(r.name, r.id)));
    }

    // On page load
    $(document).ready(function() {
        populateRouterDropdown();

        const ctx = getCtx();

        if (!ctx || ctx.user_id !== HOTSPOT_USER_ID) {
            $('#routerContextModal').modal('show');
        } else {
            updateCtxBadge(ctx);
            fetchProfiles();
        }

        $('#ctx_router').change(function() {
            const routerId = $(this).val();
            const hotspots = getHotspotsForRouter(routerId); // implement function to return hotspots
            hotspotSelect.empty();
            hotspots.forEach(h => hotspotSelect.append(new Option(h.name, h.id)));
        });

        // When user clicks badge to change router
        $('#activeRouterName').parent().click(() => {
            populateRouterDropdown();
            $('#routerContextModal').modal('show');
        });
    });
</script>

<script>
    function fetchProfiles() {
        const tbody = document.querySelector("#profilesTable tbody");
        tbody.innerHTML = ipbSkeletonRowsHtml(12, 6);

        const ctx = getCtx(); // get saved hotspot context
        const routerId = ctx?.router_id || ''; // default to empty if not set


        fetch("<?= route_to('route.user.profile.get'); ?>?router_id=" + encodeURIComponent(routerId), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                tbody.innerHTML = '';

                if (!data || data.error) {
                    tbody.innerHTML = `<tr><td colspan="12">${ipbErrorStateHtml({
                        title: 'Could not load profiles',
                        subtitle: data?.error || 'Failed to load profiles.',
                        retry: 'fetchProfiles()'
                    })}</td></tr>`;
                    return;
                }

                const profiles = Array.isArray(data.profiles) ? data.profiles : [];
                window._queues = Array.isArray(data.queues) ? data.queues : [];
                window._pools = Array.isArray(data.pools) ? data.pools : [];

                // Populate Add modal dropdowns dynamically
                populateAddModalDropdowns();

                if (profiles.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="12">${ipbEmptyStateHtml({
                        icon: 'fa fa-box-open',
                        title: 'No profiles found',
                        subtitle: 'Add a hotspot user profile to get started.'
                    })}</td></tr>`;
                    return;
                }

                profiles.forEach(profile => {
                    // Parse script data
                    let expiredMode = 'remc';
                    let validity = '-';
                    let lockUser = 'Disable';
                    let price = '0.00';
                    let sellingPrice = '0.00';

                    if (profile['on-login']) {
                        let script = profile['on-login'];
                        script = script.replace(/^["']|["']$/g, '');
                        const parts = script.match(/\(([^)]+)\)/);
                        if (parts && parts[1]) {
                            const arr = parts[1].split(',').map(s => s.trim());
                            expiredMode = arr[1] || 'remc';
                            price = arr[2] || '0.00';
                            validity = arr[3] || '-';
                            sellingPrice = arr[4] || '0.00';
                            lockUser = arr[6] || 'Disable';
                        }
                    }

                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                <td>
                    <button class="btn btn-xs btn-primary edit-profile-btn"
                        data-toggle="modal"
                        data-target="#editUserProfileModal"
                        data-id="${profile['.id'] || ''}"
                        data-name="${profile.name || ''}"
                        data-shared-users="${profile['shared-users'] || 1}"
                        data-rate-limit="${profile['rate-limit'] || ''}"
                        data-address-pool="${profile['address-pool'] || 'none'}"
                        data-parent-queue="${profile['parent-queue'] || 'none'}"
                        data-expired-mode="${expiredMode}"
                        data-validity="${validity}"
                        data-price-bdt="${price}"
                        data-selling-price-bdt="${sellingPrice}"
                        data-lock-user="${lockUser}"
                        aria-label="Edit profile">
                        <i class="fa fa-edit" aria-hidden="true"></i>
                    </button>
                    <button class="btn btn-xs btn-danger delete-profile-btn"
                        data-id="${profile['.id']}"
                        data-name="${profile.name}"
                        aria-label="Delete profile">
                        <i class="fa fa-trash" aria-hidden="true"></i>
                    </button>
                </td>
                <td>${profile.name || '-'}</td>
                <td>${profile['shared-users'] || '-'}</td>
                <td>${profile['rate-limit'] || '-'}</td>
                <td>${profile['address-pool'] || '-'}</td>
                <td>${profile['parent-queue'] || '-'}</td>
                <td>${expiredMode}</td>
                <td>${validity}</td>
                <td>${price}</td>
                <td>${sellingPrice}</td>
                <td>${lockUser}</td>
                <td>${profile.default ? 'Enabled' : 'Disabled'}</td>
            `;
                    tbody.appendChild(tr);
                });

                // Add click event listeners for edit buttons
                document.querySelectorAll('.edit-profile-btn').forEach(btn => {
                    btn.addEventListener('click', populateEditModal);
                });
            })
            .catch(err => {
                tbody.innerHTML = `<tr><td colspan="12">${ipbErrorStateHtml({
                    title: 'Could not load profiles',
                    subtitle: err.message,
                    retry: 'fetchProfiles()'
                })}</td></tr>`;
            });
    }

    function populateAddModalDropdowns() {
        const addPoolSelect = document.getElementById('address_pool');
        const addQueueSelect = document.getElementById('parent_queue');
        const addExpiredModeSelect = document.getElementById('expired_mode');

        // Clear old options
        addPoolSelect.innerHTML = '';
        addQueueSelect.innerHTML = '';
        addExpiredModeSelect.innerHTML = '';

        // Pools
        addPoolSelect.appendChild(new Option('none', 'none'));
        (window._pools || []).forEach(pool => {
            addPoolSelect.appendChild(new Option(pool.name, pool.name));
        });

        // Queues
        addQueueSelect.appendChild(new Option('none', 'none'));
        (window._queues || []).forEach(queue => {
            addQueueSelect.appendChild(new Option(queue.name, queue.name));
        });

        // Expired Mode options (keep your original ones)
        const expiredModes = [{
                text: 'Remove',
                value: 'rem'
            },
            {
                text: 'Notify',
                value: 'ntf'
            },
            {
                text: 'Remove & Record',
                value: 'remc',
                selected: true
            },
            {
                text: 'Notify & Record',
                value: 'ntfc'
            },
            {
                text: 'No Expiration',
                value: '0'
            }
        ];
        expiredModes.forEach(mode => {
            const opt = new Option(mode.text, mode.value);
            if (mode.selected) opt.selected = true;
            addExpiredModeSelect.appendChild(opt);
        });
    }


    function populateEditModal(event) {
        const btn = event.currentTarget;

        const data = {
            id: btn.dataset.id || '',
            name: btn.dataset.name || '',
            sharedUsers: btn.dataset.sharedUsers || '1',
            rateLimit: btn.dataset.rateLimit || '',
            addressPool: btn.dataset.addressPool || 'none',
            parentQueue: btn.dataset.parentQueue || 'none',
            expiredMode: btn.dataset.expiredMode || 'remc',
            validity: btn.dataset.validity || '',
            priceBdt: btn.dataset.priceBdt || '0.00',
            sellingPriceBdt: btn.dataset.sellingPriceBdt || '0.00',
            lockUser: btn.dataset.lockUser || 'Disable'
        };

        document.getElementById('edit_profile_id').value = data.id;
        document.getElementById('edit_name').value = data.name;
        document.getElementById('edit_shared_users').value = data.sharedUsers;
        document.getElementById('edit_rate_limit').value = data.rateLimit;
        document.getElementById('edit_validity').value = data.validity;
        document.getElementById('edit_price_bdt').value =
            isNaN(parseFloat(data.priceBdt)) ? '0.00' : parseFloat(data.priceBdt).toFixed(2);

        document.getElementById('edit_selling_price_bdt').value =
            isNaN(parseFloat(data.sellingPriceBdt)) ? '0.00' : parseFloat(data.sellingPriceBdt).toFixed(2);


        const addressPoolSelect = document.getElementById('edit_address_pool');
        const parentQueueSelect = document.getElementById('edit_parent_queue');
        const expiredModeSelect = document.getElementById('edit_expired_mode');
        const lockUserSelect = document.getElementById('edit_lock_user');
        lockUserSelect.innerHTML = '';
        ['Disable', 'Enable'].forEach(val => {
            const opt = new Option(val, val);
            if (val === data.lockUser) opt.selected = true;
            lockUserSelect.appendChild(opt);
        });



        // Clear current options
        addressPoolSelect.innerHTML = '';
        parentQueueSelect.innerHTML = '';
        expiredModeSelect.innerHTML = '';

        // Populate pools
        addressPoolSelect.appendChild(new Option('none', 'none'));
        (window._pools || []).forEach(pool => {
            const opt = new Option(pool.name, pool.name);
            if (pool.name === data.addressPool) opt.selected = true;
            addressPoolSelect.appendChild(opt);
        });

        // Populate queues
        parentQueueSelect.appendChild(new Option('none', 'none'));
        (window._queues || []).forEach(queue => {
            const opt = new Option(queue.name, queue.name);
            if (queue.name === data.parentQueue) opt.selected = true;
            parentQueueSelect.appendChild(opt);
        });

        // Expired Modes
        const expiredModes = [{
                text: 'Remove',
                value: 'rem'
            },
            {
                text: 'Notify',
                value: 'ntf'
            },
            {
                text: 'Remove & Record',
                value: 'remc'
            },
            {
                text: 'Notify & Record',
                value: 'ntfc'
            },
            {
                text: 'No Expiration',
                value: '0'
            }
        ];
        expiredModes.forEach(mode => {
            const opt = new Option(mode.text, mode.value);
            if (mode.value === data.expiredMode) opt.selected = true;
            expiredModeSelect.appendChild(opt);
        });


    }



    // Fetch profiles on page load
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = getCtx();

        if (!ctx || ctx.user_id !== HOTSPOT_USER_ID) {
            localStorage.removeItem(HOTSPOT_CTX_KEY);
            $('#routerContextModal').modal('show');
            return;
        }

        updateCtxBadge(ctx);
        // fetchUsers(); // uses ctx.router_id automatically



        fetchProfiles();

        // Clear edit modal when hidden
        $('#editUserProfileModal').on('hidden.bs.modal', function() {
            // Reset form fields
            document.getElementById('editUserProfileForm').reset();
        });
    });

    // Form submission handling (optional)
    document.getElementById('addUserProfileForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        // Add your form submission logic here
    });

    document.getElementById('editUserProfileForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        // Add your form submission logic here
    });
</script>

<script>
    $(document).ready(function() {
        $('#addUserProfileForm').on('submit', function(e) {
            e.preventDefault();

            const ctx = getCtx(); // get saved hotspot context
            const routerId = ctx?.router_id || ''; // default to empty if not set



            const form = $(this);
            const baseUrl = form.attr('action');
            const finalUrl = baseUrl + '?router_id=' + encodeURIComponent(routerId);
            const submitBtn = form.find('button[type="submit"]');

            // Disable button & show loading
            submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');

            $.ajax({
                url: finalUrl,
                method: 'POST',
                data: form.serialize(),
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        // Close modal
                        $('#addUserProfileModal').modal('hide');

                        // Reset form
                        form[0].reset();

                        // Show success toast / alert (optional)
                        alert('User profile added successfully!');

                        // Refresh profiles table
                        fetchProfiles();
                    } else {
                        alert(res.error || 'Failed to add profile.');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', xhr.responseText || error);
                    alert('An error occurred while adding the profile.');
                },
                complete: function() {
                    // Re-enable button
                    submitBtn.prop('disabled', false).html('Save');
                }
            });
        });

        $('#editUserProfileForm').on('submit', function(e) {
            e.preventDefault();
            console.log('Edit form submitted');

            const ctx = getCtx(); // saved hotspot context
            const routerId = ctx?.router_id || '';

            const form = $(this);
            const baseUrl = form.attr('action'); // original action URL

            // ✅ create new URL (do NOT reassign const)
            const finalUrl = baseUrl + '?router_id=' + encodeURIComponent(routerId);

            const submitBtn = form.find('button[type="submit"]');

            // Disable button & show loading
            submitBtn
                .prop('disabled', true)
                .html('<i class="fa fa-spinner fa-spin"></i> Saving...');

            $.ajax({
                url: finalUrl,
                method: 'POST',
                data: form.serialize(),
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        // Close modal
                        $('#editUserProfileModal').modal('hide');

                        // Reset form
                        form[0].reset();

                        // Show success toast / alert (optional)
                        alert('User profile Updated successfully!');

                        // Refresh profiles table
                        fetchProfiles();
                    } else {
                        alert(res.error || 'Failed to Update profile.');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', xhr.responseText || error);
                    alert('An error occurred while Update the profile.');
                },
                complete: function() {
                    // Re-enable button
                    submitBtn.prop('disabled', false).html('Save');
                }
            });
        });

    });
</script>
<script>
    $(document).on('click', '.delete-profile-btn', function() {
        const profileId = $(this).data('id');
        const profileName = $(this).data('name');

        if (!profileId) {
            alert('Invalid profile ID');
            return;
        }

        if (!confirm(`Are you sure you want to delete profile "${profileName}"?`)) {
            return;
        }
        const ctx = getCtx(); // get saved hotspot context
        const routerId = ctx?.router_id || '';

        $.ajax({
            url: "<?= route_to('route.user.profile.delete'); ?>" + "?router_id=" + encodeURIComponent(routerId),
            method: "POST",
            data: {
                id: profileId,
                <?= csrf_token() ?>: "<?= csrf_hash() ?>"
            },
            dataType: "json",
            success: function(res) {
                if (res.success) {
                    alert('Profile deleted successfully');
                    fetchProfiles(); // reload table
                } else {
                    alert(res.error || 'Delete failed');
                }
            },
            error: function(xhr) {
                console.error(xhr.responseText);
                alert('Server error while deleting profile');
            }
        });
    });
</script>



<?= $this->endSection(); ?>