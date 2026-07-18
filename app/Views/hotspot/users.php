<?= $this->extend('layout/main-layout'); ?>
<?= $this->section('content'); ?>

<div class="content-wrapper">
    <section class="content ipb-saas-list">
        
    <?= view('components/page-header', [
      'title' => 'Hotspot Users',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Hotspot Users'],
      ],
    ]); ?>

        <div class="row">
            <div class="col-md-12">
                <div class="box box-warning">
                    <div class="box-header with-border ipb-box-toolbar">
                        <div class="ipb-list-toolbar">
                          <div class="ipb-list-toolbar-filters">
                            <span class="ipb-filter-label"><i class="fa fa-users" aria-hidden="true"></i> Hotspot users</span>
                            <button type="button" class="btn btn-default" onclick="$('#routerContextModal').modal('show')">
                              <i class="fa fa-network-wired" aria-hidden="true"></i>
                              <span id="activeRouterName">Select Router</span>
                            </button>
                          </div>
                          <div class="ipb-list-toolbar-actions">
                            <a href="<?= route_to('route.hotspot.users'); ?>" class="btn btn-default">
                                <i class="fa fa-users" aria-hidden="true"></i> Users
                            </a>
                            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addUserModal">
                                <i class="fa fa-plus" aria-hidden="true"></i> Add
                            </button>
                            <button type="button" class="btn btn-info" data-toggle="modal" data-target="#generateUserModal">
                                <i class="fa fa-magic" aria-hidden="true"></i> Generate
                            </button>
                            <button type="button" class="btn btn-primary" onclick="printUsers()">
                                <i class="fa fa-print" aria-hidden="true"></i> Print
                            </button>
                            <button type="button" class="btn btn-success" onclick="generateQRForAllUsers()">
                                <i class="fa fa-qrcode" aria-hidden="true"></i> QR
                            </button>
                            <button type="button" class="btn btn-default" onclick="toggleCompactView()">
                                <i class="fa fa-compress" aria-hidden="true"></i> Small
                            </button>
                          </div>
                        </div>
                    </div>
                    <div class="box-body">
                        <form class="ipb-filter-form" data-ipb-manual="1" onsubmit="return false;" style="margin-bottom: 16px;">
                          <div class="ipb-filter-field">
                            <label for="profileFilter">Profile</label>
                            <select class="form-control" id="profileFilter" aria-label="Profile">
                                <option value="">All Profiles</option>
                            </select>
                          </div>
                          <div class="ipb-filter-field">
                            <label for="serverFilter">Server</label>
                            <select class="form-control" id="serverFilter" aria-label="Server">
                                <option value="">All Servers</option>
                            </select>
                          </div>
                          <div class="ipb-filter-field" style="flex: 1 1 200px;">
                            <label for="commentFilter">Comment</label>
                            <input type="text" class="form-control" id="commentFilter" placeholder="Search comment..." aria-label="Comment">
                          </div>
                          <div class="ipb-filter-actions">
                            <button type="button" class="btn btn-primary" id="searchBtn">
                                <i class="fa fa-search" aria-hidden="true"></i> Search
                            </button>
                            <button type="button" class="ipb-filter-reset" id="resetBtn" title="Reset Filters">
                              <i class="fa fa-times" aria-hidden="true"></i> Reset
                            </button>
                          </div>
                        </form>

                        <?php /* The label used to sit INSIDE .table-responsive, so it
                                 scrolled sideways with the table, and the table itself was
                                 nested one level deeper in #printSection — which
                                 responsive.css ALSO gives overflow-x:auto, so the page had
                                 two nested horizontal scrollers. That extra level further
                                 broke the sticky first/last column pinning, whose
                                 selectors require a direct child (`.table-responsive >
                                 .table`). Lift the label out and merge the two wrappers
                                 into one, restoring the direct-child relationship. */ ?>
                        <div class="ipb-filter-label" style="margin-bottom: 8px;">
                          <i class="fa fa-list" aria-hidden="true"></i> Users list
                          <span id="userCount" class="ipb-pay-badge is-info" style="margin-left: 8px;"></span>
                        </div>
                        <div class="table-responsive" id="printSection">
                                <table class="table table-bordered table-hover" id="usersTable" width="100%">
                                    <caption class="sr-only">Hotspot users list</caption>
                                    <thead>
                                        <tr>
                                            <th width="50" scope="col">#</th>
                                            <th scope="col">Server</th>
                                            <th scope="col">Name</th>
                                            <th scope="col">Password</th>
                                            <th width="80" scope="col">Actions</th>
                                            <th scope="col">Profile</th>
                                            <th scope="col">Mac Address</th>
                                            <th scope="col">Uptime</th>
                                            <th scope="col">Bytes In</th>
                                            <th scope="col">Bytes Out</th>
                                            <th scope="col">Comment</th>
                                        </tr>
                                    </thead>
                                    <?= view('components/skeleton-table', ['cols' => 11, 'rows' => 8]) ?>
                                </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="addUserForm" action="<?= route_to('route.user.store'); ?>" method="POST">
                <?= csrf_field(); ?>
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><i class="fa fa-plus"></i> Add Hotspot User</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="add_server">Server</label>
                        <select class="form-control" id="add_server" name="server" required>
                            <option value="all">all</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="add_name">Username</label>
                        <input type="text" class="form-control" id="add_name" name="name" placeholder="Enter username" required>
                    </div>
                    <div class="form-group">
                        <label for="add_password">Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="add_password" name="password" placeholder="Enter password" required>
                            <div class="input-group-btn">
                                <button type="button" class="btn btn-default" onclick="togglePassword('add_password')" aria-label="Show password">
                                    <i class="fa fa-eye" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="add_profile">Profile</label>
                        <select class="form-control" id="add_profile" name="profile" required>
                            <option value="default">default</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="add_mac_address">MAC Address</label>
                        <input type="text" class="form-control" id="add_mac_address" name="mac_address" placeholder="00:00:00:00:00:00">
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="add_time_limit">Time Limit</label>
                                <input type="text" class="form-control" id="add_time_limit" name="timelimit" placeholder="e.g., 30d, 1h">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="add_data_limit">Data Limit (MB)</label>
                                <input type="number" class="form-control" id="add_data_limit" name="datalimit" placeholder="MB">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="add_comment">Comment</label>
                        <input type="text" class="form-control" id="add_comment" name="comment" placeholder="Enter comment">
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

<!-- Generate Users Modal -->
<div class="modal fade" id="generateUserModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="generateUserForm" method="POST">
                <?= csrf_field(); ?>
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><i class="fa fa-magic"></i> Generate Users</h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Qty</label>
                                <input type="number" class="form-control" id="generate_qty" name="qty" value="1" min="1" max="1000" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Server</label>
                                <select class="form-control" id="generate_server" name="server">
                                    <option value="all">all</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Profile</label>
                                <select class="form-control" id="generate_profile" name="profile">
                                    <option value="default">default</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>User Mode</label>
                        <div>
                            <label class="radio-inline">
                                <input type="radio" name="user_mode" value="username_password" checked> Username & Password
                            </label>
                            <label class="radio-inline">
                                <input type="radio" name="user_mode" value="username_only"> Username Only
                            </label>
                            <label class="radio-inline">
                                <input type="radio" name="user_mode" value="password_only"> Password Only
                            </label>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Name Length</label>
                                <input type="number" class="form-control" id="name_length" name="name_length" value="4" min="3" max="20" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Password Length</label>
                                <input type="number" class="form-control" id="password_length" name="password_length" value="4" min="3" max="20" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Prefix Type</label>
                        <div>
                            <label class="radio-inline">
                                <input type="radio" name="prefix_type" value="character" checked> Character
                            </label>
                            <label class="radio-inline">
                                <input type="radio" name="prefix_type" value="number"> Number
                            </label>
                            <label class="radio-inline">
                                <input type="radio" name="prefix_type" value="alphanumeric"> Alphanumeric
                            </label>
                            <label class="radio-inline">
                                <input type="radio" name="prefix_type" value="random"> Random
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Prefix Characters</label>
                        <input type="text" class="form-control" id="prefix_value" name="prefix_value" placeholder="Enter prefix characters" value="abcd">
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Time Limit</label>
                                <input type="text" class="form-control" id="generate_time_limit" name="time_limit" placeholder="e.g., 30d, 1h">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Data Limit (MB)</label>
                                <input type="number" class="form-control" id="generate_data_limit" name="data_limit" placeholder="MB">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Comment</label>
                        <input type="text" class="form-control" id="generate_comment" name="comment" placeholder="Enter comment">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Generate</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- QR Code Modal -->
<div class="modal fade" id="qrModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><i class="fa fa-qrcode"></i> Hotspot Vouchers</h4>
            </div>
            <div class="modal-body" id="qrContent" style="padding: 20px;">
                <!-- QR vouchers will be generated here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="printQR()">
                    <i class="fa fa-print"></i> Print QR Codes
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="editUserForm" method="POST">
                <?= csrf_field(); ?>
                <input type="hidden" id="edit_user_id" name="id">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><i class="fa fa-edit"></i> Edit User</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_server">Server</label>
                        <select class="form-control" id="edit_server" name="server" required>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_name">Username</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_password">Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="edit_password" name="password" required>
                            <div class="input-group-btn">
                                <button type="button" class="btn btn-default" onclick="togglePassword('edit_password')" aria-label="Show password">
                                    <i class="fa fa-eye" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="edit_profile">Profile</label>
                        <select class="form-control" id="edit_profile" name="profile" required>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_mac_address">MAC Address</label>
                        <input type="text" class="form-control" id="edit_mac_address" name="mac_address">
                    </div>
                    <div class="form-group">
                        <label for="edit_comment">Comment</label>
                        <input type="text" class="form-control" id="edit_comment" name="comment">
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
<?php /* 08 §8.1 — the class bug: every <script> below used to render inside
   section('content'), which main-layout.php places at line ~206 — BEFORE
   jQuery loads at line ~243. Every "$(...)" call in this 1,800-line file
   (the largest view in the repo) threw "$ is not defined" at parse time,
   killing the whole block: the router picker never filled, Add/Edit/
   Generate/Delete never bound. Moved to section('script'), which renders at
   line ~829 — after jQuery, saas.js and list-filters.js are all loaded. */ ?>
<?= $this->section('script'); ?>

<!-- Include QR Code library.
     01 §4.8 / 08 §10 — was loaded TWICE (pinned qrcode@1.5.3 + unpinned) —
     voucher printing failed if jsdelivr was unreachable even on a retry,
     since both requests hit the same CDN. Kept the pinned copy only.
     TODO(08 §10): still CDN — no self-hosted qrcode.min.js vendored yet;
     downloading and vendoring the library file is outside what this pass
     can do (no external-fetch capability), flagged for a follow-up. -->
<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>

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
        fetchUsers(); // 01 §4.8 / 08 §8 — was fetchProfiles(), which doesn't exist here
        // (copy-paste from elsewhere) — after picking a router the table was stuck on
        // the loading spinner forever. fetchUsers() is the function actually defined below.
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
        // Rehydrate the free-text filter before the first fetch (sessionStorage).
        // profileFilter/serverFilter are restored inside populateFilterDropdowns()
        // instead — their <option>s don't exist yet this early, so setting
        // .value here would silently no-op.
        if (window.sessionStorage) {
            try {
                var storedHotspotFilters = sessionStorage.getItem('ipb_filters_hotspot_users');
                if (storedHotspotFilters) {
                    var parsedHotspotFilters = JSON.parse(storedHotspotFilters);
                    if (parsedHotspotFilters.commentFilter) document.getElementById('commentFilter').value = parsedHotspotFilters.commentFilter;
                }
            } catch (e) { /* corrupt/absent storage — ignore */ }
        }

        populateRouterDropdown();

        const ctx = getCtx();

        if (!ctx || ctx.user_id !== HOTSPOT_USER_ID) {
            $('#routerContextModal').modal('show');
        } else {
            updateCtxBadge(ctx);
            fetchUsers();
        }

        // When user clicks badge to change router
        $('#activeRouterName').parent().click(() => {
            populateRouterDropdown();
            $('#routerContextModal').modal('show');
        });
    });
</script>

<script>
    // Store ALL users globally (for QR generation)
    let allUsersGlobal = [];
    let currentFilteredUsers = [];
    let allProfiles = [];
    let allServers = [];
    let isCompactView = false;
    let baseUrl = window.location.origin;

    function fetchUsers() {
        const tbody = document.querySelector("#usersTable tbody");
        tbody.innerHTML = ipbSkeletonRowsHtml(11, 8);

        const profileFilter = document.getElementById('profileFilter').value;
        const serverFilter = document.getElementById('serverFilter').value;
        const commentFilter = document.getElementById('commentFilter').value;

        // Persist filter values across a reload (sessionStorage; not DataTables-
        // driven so IpbFilters.bind() doesn't apply — this screen fetches via a
        // custom pipeline, restored below on document ready instead).
        if (window.sessionStorage) {
            try {
                sessionStorage.setItem('ipb_filters_hotspot_users', JSON.stringify({
                    profileFilter: profileFilter, serverFilter: serverFilter, commentFilter: commentFilter
                }));
            } catch (e) { /* quota / private mode */ }
        }

        // Build query parameters - FIXED: This is the key fix!
        let url = "<?= route_to('route.user.get'); ?>";
        let params = [];

        if (profileFilter && profileFilter !== '') {
            params.push(`profile=${encodeURIComponent(profileFilter)}`);
        }
        if (serverFilter && serverFilter !== '') {
            params.push(`server=${encodeURIComponent(serverFilter)}`);
        }
        if (commentFilter && commentFilter !== '') {
            params.push(`comment=${encodeURIComponent(commentFilter)}`);
        }

        if (params.length > 0) {
            url += '?' + params.join('&');
        }
        const ctx = getCtx(); // get saved hotspot context
        const routerId = ctx?.router_id || ''; // default to empty if not set

        url += (params.length > 0 ? '&' : '?') + 'router_id=' + encodeURIComponent(routerId);
        console.log('Fetching users with URL:', url); // Debug log

        fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => {
                if (!res.ok) {
                    throw new Error(`HTTP error! status: ${res.status}`);
                }
                return res.json();
            })
            .then(data => {
                console.log('Received data:', data); // Debug log
                tbody.innerHTML = '';

                if (!data) {
                    tbody.innerHTML = `<tr><td colspan="11">${ipbErrorStateHtml({
                        title: 'No data received',
                        subtitle: 'The server did not return a response. Try again.',
                        retry: 'fetchUsers()'
                    })}</td></tr>`;
                    return;
                }

                if (data.error || !data.success) {
                    tbody.innerHTML = `<tr><td colspan="11">${ipbErrorStateHtml({
                        title: 'Could not load users',
                        subtitle: data.error || 'Failed to load users.',
                        retry: 'fetchUsers()'
                    })}</td></tr>`;
                    return;
                }

                // Store profiles and servers globally
                if (data.profiles && Array.isArray(data.profiles)) {
                    allProfiles = data.profiles;
                }
                if (data.servers && Array.isArray(data.servers)) {
                    allServers = data.servers;
                }

                // Populate filter dropdowns with available options
                populateFilterDropdowns();

                // ADD THIS LINE: Populate modal dropdowns too
                populateModalDropdowns();

                // Store filtered users for display
                currentFilteredUsers = data.users || [];

                // Update user count
                document.getElementById('userCount').textContent = currentFilteredUsers.length;

                if (currentFilteredUsers.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="11">${ipbEmptyStateHtml({
                        icon: 'fa fa-users-slash',
                        title: 'No users found',
                        subtitle: 'Try adjusting the filters, or add a new hotspot user.'
                    })}</td></tr>`;
                    return;
                }

                let counter = 1;
                currentFilteredUsers.forEach(user => {
                    const tr = document.createElement('tr');

                    // Format bytes properly
                    const bytesIn = user['bytes-in'] || '0';
                    const bytesOut = user['bytes-out'] || '0';

                    // Generate QR data for user
                    const dnsName = user.server || 'hotspot.local';
                    const uname = encodeURIComponent(user.name || '');
                    const upass = encodeURIComponent(user.password || '');
                    const loginUrl = `http://${dnsName}/login?username=${uname}&password=${upass}`;

                    tr.innerHTML = `
                        <td>${counter++}</td>
                        <td>${user.server || 'all'}</td>
                        <td>${user.name || '-'}</td>
                        <td>${user.password || '-'}</td>
                        <td>
                            <button class="btn btn-xs btn-info print-user-btn" 
                                data-username="${user.name || ''}"
                                data-password="${user.password || ''}"
                                data-profile="${user.profile || 'default'}"
                                title="Print User">
                                <i class="fa fa-print"></i>
                            </button>
                            <button class="btn btn-xs btn-success qr-user-btn"
                                data-dnsname="${dnsName}"
                                data-username="${user.name || ''}"
                                data-password="${user.password || ''}"
                                data-profile="${user.profile || 'default'}"
                                title="Generate QR">
                                <i class="fa fa-qrcode"></i>
                            </button>
                            <button class="btn btn-xs btn-primary edit-user-btn" 
                                data-toggle="modal" 
                                data-target="#editUserModal"
                                data-id="${user.id}"
                                data-server="${user.server || 'all'}"
                                data-name="${user.name || ''}"
                                data-password="${user.password || ''}"
                                data-profile="${user.profile || 'default'}"
                                data-mac-address="${user['mac-address'] || ''}"
                                data-comment="${user.comment || ''}">
                                <i class="fa fa-edit"></i>
                            </button>
                            <button class="btn btn-xs btn-danger delete-user-btn" 
                                data-id="${user.id}"
                                data-name="${user.name || ''}">
                                <i class="fa fa-trash"></i>
                            </button>
                        </td>
                        <td>${user.profile || 'default'}</td>
                        <td>${user['mac-address'] || '-'}</td>
                        <td>${user.uptime || '00:00:00'}</td>
                        <td>${formatBytes(bytesIn)}</td>
                        <td>${formatBytes(bytesOut)}</td>
                        <td>${user.comment || '-'}</td>
                    `;
                    tbody.appendChild(tr);
                });

                // Add event listeners
                setTimeout(() => {
                    document.querySelectorAll('.edit-user-btn').forEach(btn => {
                        btn.addEventListener('click', populateEditModal);
                    });

                    document.querySelectorAll('.print-user-btn').forEach(btn => {
                        btn.addEventListener('click', printSingleUser);
                    });

                    document.querySelectorAll('.qr-user-btn').forEach(btn => {
                        btn.addEventListener('click', generateSingleQR);
                    });
                }, 100);
            })
            .catch(err => {
                console.error('Error fetching users:', err);
                tbody.innerHTML = `<tr><td colspan="11">${ipbErrorStateHtml({
                    title: 'Could not load users',
                    subtitle: err.message,
                    retry: 'fetchUsers()'
                })}</td></tr>`;
            });
    }

    // NEW FUNCTION: Populate modal dropdowns
    function populateModalDropdowns() {
        // Populate Add User Modal dropdowns
        populateDropdown('add_server', allServers, 'all');
        populateDropdown('add_profile', allProfiles, 'default');

        // Populate Generate User Modal dropdowns
        populateDropdown('generate_server', allServers, 'all');
        populateDropdown('generate_profile', allProfiles, 'default');
    }

    // Function to populate filter dropdowns only
    function populateFilterDropdowns() {
        const profileFilter = document.getElementById('profileFilter');
        const serverFilter = document.getElementById('serverFilter');

        // Store current values, falling back to a sessionStorage-persisted
        // value on first load (before any option exists to hold a selection).
        var storedHotspotFilters = null;
        if (window.sessionStorage) {
            try {
                var rawStoredHotspotFilters = sessionStorage.getItem('ipb_filters_hotspot_users');
                if (rawStoredHotspotFilters) storedHotspotFilters = JSON.parse(rawStoredHotspotFilters);
            } catch (e) { /* corrupt/absent storage — ignore */ }
        }
        const currentProfile = profileFilter.value || (storedHotspotFilters && storedHotspotFilters.profileFilter) || '';
        const currentServer = serverFilter.value || (storedHotspotFilters && storedHotspotFilters.serverFilter) || '';

        // Clear and populate profile filter
        if (profileFilter) {
            profileFilter.innerHTML = '<option value="">All Profiles</option>';
            if (allProfiles && allProfiles.length > 0) {
                allProfiles.forEach(profile => {
                    const option = document.createElement('option');
                    option.value = profile.name || profile;
                    option.textContent = profile.name || profile;
                    profileFilter.appendChild(option);
                });
            }
            // Restore selection if still valid
            if (currentProfile && Array.from(profileFilter.options).some(opt => opt.value === currentProfile)) {
                profileFilter.value = currentProfile;
            }
        }

        // Clear and populate server filter
        if (serverFilter) {
            serverFilter.innerHTML = '<option value="">All Servers</option>';
            if (allServers && allServers.length > 0) {
                allServers.forEach(server => {
                    const option = document.createElement('option');
                    option.value = server.name || server;
                    option.textContent = server.name || server;
                    serverFilter.appendChild(option);
                });
            }
            // Restore selection if still valid
            if (currentServer && Array.from(serverFilter.options).some(opt => opt.value === currentServer)) {
                serverFilter.value = currentServer;
            }
        }
    }


    // Function to populate other dropdowns (for modals)
    function populateOtherDropdowns() {
        // Populate Add User Modal
        populateDropdown('add_server', allServers, 'all');
        populateDropdown('add_profile', allProfiles, 'default');

        // Populate Generate User Modal
        populateDropdown('generate_server', allServers, 'all');
        populateDropdown('generate_profile', allProfiles, 'default');

        // Populate Edit User Modal
        populateDropdown('edit_server', allServers, '');
        populateDropdown('edit_profile', allProfiles, '');
    }

    function populateDropdown(elementId, data, defaultValue) {
        const select = document.getElementById(elementId);
        if (!select) return;

        // Store current value before clearing
        const currentValue = select.value;

        // Clear existing options
        select.innerHTML = '';

        // Add default option if specified
        if (defaultValue !== undefined && defaultValue !== null) {
            const defaultOption = document.createElement('option');
            defaultOption.value = defaultValue;
            defaultOption.textContent = defaultValue;
            select.appendChild(defaultOption);
        }

        // Add data options
        if (data && data.length > 0) {
            // Remove duplicates by using a Set
            const uniqueItems = new Set();
            data.forEach(item => {
                let value, text;

                // Handle both string and object formats
                if (typeof item === 'object') {
                    value = item.name || item.id || item;
                    text = item.name || item.id || item;
                } else {
                    value = item;
                    text = item;
                }

                // Add only if not already added and not empty
                if (value && !uniqueItems.has(value)) {
                    uniqueItems.add(value);
                    const option = document.createElement('option');
                    option.value = value;
                    option.textContent = text;
                    select.appendChild(option);
                }
            });
        }

        // Try to restore previous value
        if (currentValue && Array.from(select.options).some(opt => opt.value === currentValue)) {
            select.value = currentValue;
        } else if (defaultValue !== undefined && defaultValue !== null) {
            select.value = defaultValue;
        }
    }

    function formatBytes(bytes) {
        if (bytes === undefined || bytes === null) return '0 Byte';
        const bytesNum = typeof bytes === 'string' ? parseInt(bytes) || 0 : Number(bytes) || 0;
        if (bytesNum === 0) return '0 Byte';
        const k = 1024;
        const sizes = ['Byte', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytesNum) / Math.log(k));
        return parseFloat((bytesNum / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    function printUsers() {
        if (!currentFilteredUsers || currentFilteredUsers.length === 0) {
            alert('No users to print.');
            return;
        }

        const profileFilter = document.getElementById('profileFilter').value;
        const serverFilter = document.getElementById('serverFilter').value;
        const commentFilter = document.getElementById('commentFilter').value;

        let filterInfo = 'All Users';
        if (profileFilter) filterInfo = `Profile: ${profileFilter}`;
        if (serverFilter) filterInfo = `Server: ${serverFilter}`;
        if (commentFilter) filterInfo = `Comment: ${commentFilter}`;
        if (profileFilter && serverFilter) filterInfo = `Profile: ${profileFilter}, Server: ${serverFilter}`;
        if (profileFilter && serverFilter && commentFilter) filterInfo = `Profile: ${profileFilter}, Server: ${serverFilter}, Comment: ${commentFilter}`;

        // Create print content
        let tableContent = `
            <table style="width:100%; border-collapse:collapse; margin-top:20px;">
                <thead>
                    <tr style="background-color:#2f363d; color:#fff;">
                        <th style="padding:8px; border:1px solid #ddd;">#</th>
                        <th style="padding:8px; border:1px solid #ddd;">Server</th>
                        <th style="padding:8px; border:1px solid #ddd;">Name</th>
                        <th style="padding:8px; border:1px solid #ddd;">Password</th>
                        <th style="padding:8px; border:1px solid #ddd;">Profile</th>
                        <th style="padding:8px; border:1px solid #ddd;">MAC Address</th>
                        <th style="padding:8px; border:1px solid #ddd;">Uptime</th>
                        <th style="padding:8px; border:1px solid #ddd;">Bytes In</th>
                        <th style="padding:8px; border:1px solid #ddd;">Bytes Out</th>
                        <th style="padding:8px; border:1px solid #ddd;">Comment</th>
                    </tr>
                </thead>
                <tbody>
        `;

        currentFilteredUsers.forEach((user, index) => {
            tableContent += `
                <tr>
                    <td style="padding:8px; border:1px solid #ddd;">${index + 1}</td>
                    <td style="padding:8px; border:1px solid #ddd;">${user.server || 'all'}</td>
                    <td style="padding:8px; border:1px solid #ddd;">${user.name || '-'}</td>
                    <td style="padding:8px; border:1px solid #ddd;">${user.password || '-'}</td>
                    <td style="padding:8px; border:1px solid #ddd;">${user.profile || 'default'}</td>
                    <td style="padding:8px; border:1px solid #ddd;">${user['mac-address'] || '-'}</td>
                    <td style="padding:8px; border:1px solid #ddd;">${user.uptime || '00:00:00'}</td>
                    <td style="padding:8px; border:1px solid #ddd;">${formatBytes(user['bytes-in'] || '0')}</td>
                    <td style="padding:8px; border:1px solid #ddd;">${formatBytes(user['bytes-out'] || '0')}</td>
                    <td style="padding:8px; border:1px solid #ddd;">${user.comment || '-'}</td>
                </tr>
            `;
        });

        tableContent += '</tbody></table>';

        const printContent = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Hotspot Users Report</title>
                <style>
                    body { font-family: Arial, sans-serif; padding: 20px; }
                    .header { text-align: center; margin-bottom: 30px; }
                    .header h1 { margin-bottom: 5px; }
                    .header .filter-info { color: #666; margin-bottom: 10px; }
                    .header .meta { color: #888; font-size: 14px; }
                    table { width: 100%; border-collapse: collapse; }
                    th { background-color: #2f363d; color: white; padding: 10px; text-align: left; }
                    td { padding: 8px; border: 1px solid #ddd; }
                    .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #666; }
                    @media print {
                        @page { margin: 0.5in; }
                        body { padding: 0; }
                    }
                </style>
            </head>
            <body>
                <div class="header">
                    <h1>Hotspot Users Report</h1>
                    <div class="filter-info"><strong>Filter:</strong> ${filterInfo}</div>
                    <div class="meta">
                        <p><strong>Total Users:</strong> ${currentFilteredUsers.length}</p>
                        <p><strong>Generated:</strong> ${new Date().toLocaleString()}</p>
                    </div>
                </div>
                ${tableContent}
                <div class="footer">
                    <p>© <?= date('Y') ?> - Hotspot Management System</p>
                    <p>Page 1 of 1</p>
                </div>
            </body>
            </html>
        `;

        const printWindow = window.open('', '_blank');
        printWindow.document.write(printContent);
        printWindow.document.close();
        setTimeout(() => {
            printWindow.print();
        }, 500);
    }

    function populateEditModal(event) {
        const btn = event.currentTarget;

        const userId = btn.dataset.id;
        const userServer = btn.dataset.server || 'all';
        const userName = btn.dataset.name || '';
        const userPassword = btn.dataset.password || '';
        const userProfile = btn.dataset.profile || 'default';
        const userMacAddress = btn.dataset.macAddress || '';
        const userComment = btn.dataset.comment || '';

        document.getElementById('edit_user_id').value = userId;
        document.getElementById('edit_name').value = userName;
        document.getElementById('edit_password').value = userPassword;
        document.getElementById('edit_mac_address').value = userMacAddress;
        document.getElementById('edit_comment').value = userComment;

        // Set dropdown values
        const serverSelect = document.getElementById('edit_server');
        const profileSelect = document.getElementById('edit_profile');

        // First populate the dropdowns if needed
        if (allServers.length > 0 && serverSelect.options.length <= 1) {
            populateDropdown('edit_server', allServers, '');
        }
        if (allProfiles.length > 0 && profileSelect.options.length <= 1) {
            populateDropdown('edit_profile', allProfiles, '');
        }

        if (serverSelect) serverSelect.value = userServer;
        if (profileSelect) profileSelect.value = userProfile;
    }

    function togglePassword(inputId) {
        const input = document.getElementById(inputId);
        const icon = input.parentNode.querySelector('button i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    function printSingleUser(event) {
        const btn = event.currentTarget;
        const username = btn.dataset.username;
        const password = btn.dataset.password;
        const profile = btn.dataset.profile;

        const printContent = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>User Voucher - ${username}</title>
                <style>
                    body { font-family: Arial, sans-serif; text-align: center; padding: 20px; }
                    .voucher { border: 2px dashed #333; padding: 30px; max-width: 400px; margin: 0 auto; }
                    .username { font-size: 28px; font-weight: bold; margin: 15px 0; }
                    .password { font-size: 24px; margin: 15px 0; }
                    .profile { color: #666; margin: 10px 0; font-size: 18px; }
                    .instructions { font-size: 14px; margin-top: 25px; line-height: 1.5; }
                    .logo { font-size: 20px; font-weight: bold; margin-bottom: 20px; }
                </style>
            </head>
            <body>
                <div class="voucher">
                    <div class="logo">Hotspot Voucher</div>
                    <div class="profile">Profile: ${profile}</div>
                    <div class="username">Username: ${username}</div>
                    <div class="password">Password: ${password}</div>
                    <div class="instructions">
                        <p><strong>Instructions:</strong></p>
                        <p>1. Connect to WiFi network: "Hotspot"</p>
                        <p>2. Open browser and go to: http://hotspot.local</p>
                        <p>3. Enter username and password above</p>
                        <p>Generated: ${new Date().toLocaleDateString()}</p>
                    </div>
                </div>
            </body>
            </html>
        `;

        const printWindow = window.open('', '_blank');
        printWindow.document.write(printContent);
        printWindow.document.close();
        printWindow.print();
    }

    function generateSingleQR(event) {
        const btn = event.currentTarget;
        const dnsName = btn.dataset.dnsname || 'hotspot.local';
        const username = btn.dataset.username || '';
        const password = btn.dataset.password || '';
        const profile = btn.dataset.profile || 'default';

        const qrContent = document.getElementById('qrContent');
        qrContent.innerHTML = '<div class="text-center"><i class="fa fa-spinner fa-spin"></i> Generating QR voucher...</div>';

        $('#qrModal').modal('show');

        setTimeout(() => {
            // Generate hotspot login URL
            const uname = encodeURIComponent(username);
            const upass = encodeURIComponent(password);
            const loginUrl = `http://${dnsName}/login?username=${uname}&password=${upass}`;

            qrContent.innerHTML = `
                <div class="text-center">
                    <div style="max-width: 400px; margin: 0 auto;">
                        <div style="background: #5cb85c; color: white; padding: 15px; border-radius: 5px 5px 0 0; font-weight: bold; font-size: 18px;">
                            Hotspot Voucher
                        </div>
                        <div style="border: 2px dashed #ddd; padding: 30px; border-radius: 0 0 5px 5px;">
                            <h3 style="margin-top: 0;">${username}</h3>
                            <p><strong>Profile:</strong> ${profile}</p>
                            <p><strong>Server:</strong> ${dnsName}</p>
                            <canvas id="singleQR" style="max-width: 200px; margin: 20px auto; display: block;"></canvas>
                            <div style="margin-top: 20px; background: var(--surface-2, #f5f5f5); padding: 15px; border-radius: 5px;">
                                <h4 style="margin-top: 0;">Connection Details</h4>
                                <p><strong>Username:</strong> ${username}</p>
                                <p><strong>Password:</strong> ${password}</p>
                                <p><strong>Login URL:</strong> ${loginUrl}</p>
                                <p style="margin-top: 15px; color: var(--text-secondary, #666); font-size: 12px;">
                                    Scan QR code or enter credentials to connect
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Generate QR code with login URL
            QRCode.toCanvas(document.getElementById('singleQR'), loginUrl, {
                width: 200,
                margin: 2,
                color: {
                    dark: '#000000',
                    light: '#FFFFFF'
                }
            });
        }, 500);
    }

    function toggleCompactView() {
        isCompactView = !isCompactView;
        const table = document.getElementById('usersTable');
        const btn = document.querySelector('button[onclick="toggleCompactView()"] i');

        if (isCompactView) {
            table.classList.add('compact-view');
            btn.classList.remove('fa-compress');
            btn.classList.add('fa-expand');
        } else {
            table.classList.remove('compact-view');
            btn.classList.remove('fa-expand');
            btn.classList.add('fa-compress');
        }
    }

    // Fetch users on page load
    document.addEventListener('DOMContentLoaded', function() {

        const ctx = getCtx();

        if (!ctx || ctx.user_id !== HOTSPOT_USER_ID) {
            localStorage.removeItem(HOTSPOT_CTX_KEY);
            $('#routerContextModal').modal('show');
            return;
        }

        updateCtxBadge(ctx);
        // fetchUsers(); // uses ctx.router_id automatically


        // Initial fetch of users (this will populate filters too)
        fetchUsers();

        // Search button
        document.getElementById('searchBtn').addEventListener('click', fetchUsers);

        // Reset filters button
        document.getElementById('resetBtn').addEventListener('click', function() {
            document.getElementById('profileFilter').value = '';
            document.getElementById('serverFilter').value = '';
            document.getElementById('commentFilter').value = '';
            fetchUsers();
        });

        // Auto-search on filter change
        document.getElementById('profileFilter').addEventListener('change', fetchUsers);
        document.getElementById('serverFilter').addEventListener('change', fetchUsers);
        document.getElementById('commentFilter').addEventListener('input', function() {
            clearTimeout(this.searchTimer);
            this.searchTimer = setTimeout(fetchUsers, 500);
        });

        // Initialize prefix radio buttons
        document.querySelectorAll('input[name="prefix_type"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const prefixValue = document.getElementById('prefix_value');
                switch (this.value) {
                    case 'character':
                        prefixValue.value = 'abcd';
                        break;
                    case 'number':
                        prefixValue.value = '0123456789';
                        break;
                    case 'alphanumeric':
                        prefixValue.value = 'abcdefghijklmnopqrstuvwxyz0123456789';
                        break;
                    case 'random':
                        prefixValue.value = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
                        break;
                }
            });
        });

        // Clear edit modal when closed
        $('#editUserModal').on('hidden.bs.modal', function() {
            document.getElementById('editUserForm').reset();
        });
    });
</script>

<script>
    $(document).ready(function() {
        // Add User Form
        $('#addUserForm').on('submit', function(e) {
            e.preventDefault();

            const form = $(this);
            const submitBtn = form.find('button[type="submit"]');

            submitBtn
                .prop('disabled', true)
                .html('<i class="fa fa-spinner fa-spin"></i> Saving...');

            const ctx = getCtx(); // saved hotspot context
            const routerId = ctx?.router_id || '';

            const baseUrl = form.attr('action'); // original action
            const finalUrl = baseUrl + '?router_id=' + encodeURIComponent(routerId);

            $.ajax({
                url: finalUrl,
                method: 'POST',
                data: form.serialize(),
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        $('#addUserModal').modal('hide');
                        form[0].reset();
                        alert('User added successfully!');
                        fetchUsers();
                    } else {
                        alert(res.error || 'Failed to add user.');
                    }
                },
                error: function(xhr) {
                    alert('An error occurred while adding the user.');
                },
                complete: function() {
                    submitBtn.prop('disabled', false).html('Save');
                }
            });
        });

        // Edit User Form
        $('#editUserForm').on('submit', function(e) {
            e.preventDefault();

            const form = $(this);
            const submitBtn = form.find('button[type="submit"]');

            submitBtn
                .prop('disabled', true)
                .html('<i class="fa fa-spinner fa-spin"></i> Updating...');

            const ctx = getCtx(); // saved hotspot context
            const routerId = ctx?.router_id || '';

            $.ajax({
                url:  "<?= route_to('route.user.update'); ?>" + "?router_id=" + encodeURIComponent(routerId),
                method: 'POST',
                data: form.serialize(),
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        $('#editUserModal').modal('hide');
                        form[0].reset();
                        alert('User updated successfully!');
                        fetchUsers();
                    } else {
                        alert(res.error || 'Failed to update user.');
                    }
                },
                error: function(xhr) {
                    alert('An error occurred while updating the user.');
                },
                complete: function() {
                    submitBtn.prop('disabled', false).html('Update');
                }
            });
        });

        // Generate Users Form
        $('#generateUserForm').on('submit', function(e) {
            e.preventDefault();
            const form = $(this);
            const submitBtn = form.find('button[type="submit"]');

            submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Generating...');

            const ctx = getCtx(); // get saved hotspot context
            const routerId = ctx?.router_id || ''; // default to empty if not set

            $.ajax({
                url:  "<?= route_to('route.user.generate'); ?>" + "?router_id=" + encodeURIComponent(routerId),
                method: 'POST',
                data: form.serialize(),
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        $('#generateUserModal').modal('hide');
                        form[0].reset();
                        alert(`${res.count || 0} users generated successfully!`);
                        fetchUsers();
                    } else {
                        alert(res.error || 'Failed to generate users.');
                    }
                },
                error: function(xhr) {
                    alert('An error occurred while generating users.');
                },
                complete: function() {
                    submitBtn.prop('disabled', false).html('Generate');
                }
            });
        });

        // Delete User
        $(document).on('click', '.delete-user-btn', function() {
            const userId = $(this).data('id');
            const userName = $(this).data('name');

            if (!confirm(`Are you sure you want to delete user "${userName}"?`)) {
                return;
            }

            const ctx = getCtx(); // get saved hotspot context
            const routerId = ctx?.router_id || ''; // default to empty if not set
            const url = "<?= route_to('route.user.delete'); ?>" + "?router_id=" + encodeURIComponent(routerId);

            $.ajax({
                url: url,
                method: 'POST',
                data: {
                    id: userId,
                    <?= csrf_token() ?>: "<?= csrf_hash() ?>"
                },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        alert('User deleted successfully');
                        fetchUsers();
                    } else {
                        alert(res.error || 'Delete failed');
                    }
                },
                error: function(xhr) {
                    alert('Server error while deleting user');
                }
            });
        });
    });

    // Function to generate QR codes for ALL filtered users
    function generateQRForAllUsers() {
        if (!currentFilteredUsers || currentFilteredUsers.length === 0) {
            alert('No users to generate QR codes for.');
            return;
        }

        const qrContent = document.getElementById('qrContent');
        qrContent.innerHTML = `
        <div class="text-center">
            <i class="fa fa-spinner fa-spin fa-3x"></i>
            <h3>Generating QR Codes...</h3>
            <p>Please wait while we generate QR codes for ${currentFilteredUsers.length} users</p>
        </div>
    `;

        // Show the modal immediately with loading state
        $('#qrModal').modal('show');

        // Give a small delay for the modal to show, then generate QR codes
        setTimeout(() => {
            generateAllQRVouchers();
        }, 500);
    }

    // Function to generate vouchers for all users
    function generateAllQRVouchers() {
        const qrContent = document.getElementById('qrContent');
        const users = currentFilteredUsers;

        if (!users || users.length === 0) {
            qrContent.innerHTML = `
            <div class="alert alert-warning text-center">
                No users found.
            </div>`;
            return;
        }

        let vouchersHTML = `
        <div style="font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto;">
            <div style="text-align: center; margin-bottom: 20px;">
                <h3 style="color: var(--text-secondary, #555); margin-top: 5px;">Hotspot Vouchers</h3>
            </div>
            
            <div style="display: flex; flex-wrap: wrap; gap: 20px; justify-content: center;">
        `;

        users.forEach((user, index) => {
            const qrId = `qr_canvas_${index}`;
            const dns = user.server || 'hotspot.local';
            const loginUrl = `http://${dns}/login`;

            vouchersHTML += `
            <div style="
                width: 250px;
                border: 1px solid #ddd;
                border-radius: 10px;
                padding: 20px;
                background: linear-gradient(135deg, #f5f5f5 0%, #ffffff 100%);
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                text-align: center;
                position: relative;
                overflow: hidden;
            ">
                <!-- Header -->
                <div style="
                    background: #2c3e50;
                    color: white;
                    padding: 10px;
                    margin: -20px -20px 15px -20px;
                    border-radius: 10px 10px 0 0;
                    font-weight: bold;
                    letter-spacing: 1px;
                ">
                    ${user.server || 'badda'}
                </div>
                
                <!-- Username Section -->
                <div style="margin: 15px 0;">
                    <div style="
                        font-size: 12px;
                        color: var(--text-secondary, #666);
                        text-transform: uppercase;
                        letter-spacing: 1px;
                        margin-bottom: 5px;
                    ">Username</div>
                    <div style="
                        font-size: 24px;
                        font-weight: bold;
                        color: var(--text-primary, #2c3e50);
                        letter-spacing: 2px;
                        padding: 8px;
                        background: #f8f9fa;
                        border-radius: 5px;
                        border: 1px dashed #ccc;
                    ">${user.name || 'ysim'}</div>
                </div>
                
                <!-- Password Section -->
                <div style="margin: 15px 0;">
                    <div style="
                        font-size: 12px;
                        color: var(--text-secondary, #666);
                        text-transform: uppercase;
                        letter-spacing: 1px;
                        margin-bottom: 5px;
                    ">Password</div>
                    <div style="
                        font-size: 20px;
                        font-weight: bold;
                        color: var(--error-500, #e74c3c);
                        letter-spacing: 1px;
                        padding: 8px;
                        background: var(--surface, #fff);
                        border-radius: 5px;
                        border: 1px solid #ffcccc;
                    ">${user.password || '4692'}</div>
                </div>
                
                <!-- QR Code Container -->
                <div style="margin: 15px 0;">
                    <div style="
                        font-size: 12px;
                        color: var(--text-secondary, #666);
                        text-transform: uppercase;
                        letter-spacing: 1px;
                        margin-bottom: 10px;
                    ">Scan to Connect</div>
                    <canvas id="${qrId}" width="150" height="150" style="
                        display: block;
                        margin: 0 auto;
                        border: 1px solid #eee;
                        padding: 5px;
                        background: white;
                    "></canvas>
                </div>
                
                <!-- Validity Information -->
                <div style="margin: 15px 0;">
                    <div style="
                        font-size: 14px;
                        color: #27ae60;
                        font-weight: bold;
                        padding: 8px;
                        background: #e8f6ef;
                        border-radius: 5px;
                        border: 1px dashed #2ecc71;
                    ">
                        <i class="far fa-clock" style="margin-right: 5px;"></i>
                        ${user['time-limit'] || '30d'} 
                        <span style="color: var(--text-secondary, #666); font-weight: normal; margin-left: 5px;">
                            ${user['data-limit'] ? `| ${user['data-limit']} MB` : ''}
                        </span>
                    </div>
                </div>
                
                <!-- Login URL -->
                <div style="
                    font-size: 11px;
                    color: var(--text-secondary, #666);
                    background: #f8f9fa;
                    padding: 8px;
                    border-radius: 5px;
                    margin-top: 10px;
                    word-break: break-all;
                ">
                    <i class="fa fa-link" style="margin-right: 5px;"></i>
                    Login: ${loginUrl}
                </div>
                
                <!-- Footer Note -->
                <div style="
                    font-size: 10px;
                    color: var(--text-muted, #888);
                    margin-top: 15px;
                    padding-top: 10px;
                    border-top: 1px dashed #ddd;
                ">
                    Generated on: ${new Date().toLocaleDateString()}
                </div>
            </div>
            `;
        });

        vouchersHTML += `
            </div>
            
            <div style="
                margin-top: 30px;
                padding: 15px;
                background: #f8f9fa;
                border-radius: 8px;
                text-align: center;
                border-left: 4px solid #3498db;
            ">
                <h4 style="margin: 0 0 10px 0; color: var(--text-primary, #2c3e50);">
                    <i class="fa fa-info-circle" style="margin-right: 8px;"></i>
                    Connection Instructions
                </h4>
                <div style="display: flex; justify-content: center; gap: 30px; flex-wrap: wrap;">
                    <div style="text-align: left;">
                        <p style="margin: 5px 0;"><strong>1.</strong> Connect to WiFi: <strong>${users[0]?.server || 'hotspot'}</strong></p>
                        <p style="margin: 5px 0;"><strong>2.</strong> Open any browser</p>
                    </div>
                    <div style="text-align: left;">
                        <p style="margin: 5px 0;"><strong>3.</strong> Login with credentials above</p>
                        <p style="margin: 5px 0;"><strong>4.</strong> Or scan the QR code</p>
                    </div>
                </div>
            </div>
        </div>
        `;

        qrContent.innerHTML = vouchersHTML;

        // Generate QR codes
        users.forEach((user, index) => {
            const canvas = document.getElementById(`qr_canvas_${index}`);
            if (!canvas) return;

            const dns = user.server || 'hotspot.local';
            const uname = encodeURIComponent(user.name || '');
            const upass = encodeURIComponent(user.password || '');
            const loginUrl = `http://${dns}/login?username=${uname}&password=${upass}`;

            QRCode.toCanvas(canvas, loginUrl, {
                width: 150,
                margin: 1,
                color: {
                    dark: '#000000',
                    light: '#FFFFFF'
                }
            }, function(error) {
                if (error) {
                    console.error('QR error:', error);
                    canvas.outerHTML = '<div style="padding: 20px; color: var(--error-500, #e74c3c);">QR Code Error</div>';
                }
            });
        });
    }



    // Function to print all QR codes
    // Function to print all QR codes
    // Function to print all QR codes
    function printQR() {
        const users = currentFilteredUsers;

        if (!users || users.length === 0) {
            alert('No users to print QR codes for.');
            return;
        }

        const qrPromises = users.map(user => {
            return new Promise(resolve => {
                const dns = user.server || 'badda.in';
                const uname = encodeURIComponent(user.name || '');
                const upass = encodeURIComponent(user.password || '');
                const loginUrl = `http://${dns}/login?username=${uname}&password=${upass}`;

                const canvas = document.createElement('canvas');
                canvas.width = 80;
                canvas.height = 80;

                // Find the profile data for this user
                const userProfile = user.profile ?
                    allProfiles.find(p => p.name === user.profile) :
                    null;

                console.log('User Profile Data:', userProfile);

                let price = '0.00';
                let validity = user['time-limit'] || '-';
                let sellingPrice = '0.00';

                // Extract price and validity from profile's on-login script
                if (userProfile && userProfile['on-login']) {
                    try {
                        let script = userProfile['on-login'];
                        // Remove surrounding quotes if present
                        script = script.replace(/^["']|["']$/g, '');

                        // Extract parameters from parentheses
                        const parts = script.match(/\(([^)]+)\)/);
                        if (parts && parts[1]) {
                            const arr = parts[1].split(',').map(s => s.trim());
                            console.log('Extracted parameters:', arr);

                            // Get values from array (adjust indexes as needed for your script format)
                            price = arr[2] || '0.00';
                            validity = arr[3] || user['time-limit'] || '-';
                            sellingPrice = arr[4] || '0.00';
                        }
                    } catch (error) {
                        console.error('Error parsing profile script:', error);
                    }
                }

                console.log(`User ${user.name}: Price=${price}, Validity=${validity}`);

                QRCode.toCanvas(canvas, loginUrl, {
                    width: 80,
                    margin: 1
                }, () => {
                    resolve({
                        username: user.name,
                        password: user.password,
                        qr: canvas.toDataURL("image/png"),
                        server: dns.replace('.in', ''),
                        price: price,
                        validity: validity || user['time-limit'] || '30d'
                    });
                });
            });
        });

        Promise.all(qrPromises).then(list => {
            // Rest of your print HTML code remains the same...
            let html = `
<!DOCTYPE html>
<html>
<head>
<style>
@page { size: A4; margin: 10mm; }
body { font-family: Arial, sans-serif; }

.container {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
}

.voucher {
    width: 70mm;
    height: 45mm;
    border: 2px solid #000;
    padding: 6px;
    box-sizing: border-box;
    position: relative;
}

.header {
    font-weight: bold;
    font-size: 18px;
}

.header span {
    font-size: 14px;
    margin-left: 5px;
}

.qr {
    position: absolute;
    top: 6px;
    right: 6px;
    width: 80px;
    height: 80px;
}

.label {
    font-size: 12px;
    margin-top: 6px;
}

.box {
    border: 1px solid #000;
    padding: 3px;
    font-weight: bold;
    text-align: center;
    margin-bottom: 4px;
}

.price {
    font-size: 14px;
    font-weight: bold;
    text-align: center;
    margin-top: 4px;
}

.login {
    font-size: 11px;
    text-align: center;
    margin-top: 3px;
}
</style>
</head>
<body>

<div class="container">
`;

            list.forEach(u => {
                html += `
<div class="voucher">
    <div class="header">MIKHM0N <span>${u.server}</span></div>

    <div class="label">Username</div>
    <div class="box">${u.username}</div>

    <div class="label">Password</div>
    <div class="box">${u.password}</div>

    <div class="price">${u.validity} bdt ${u.price}</div>

    <div class="login">Login: http://${u.server}.in</div>

    <img src="${u.qr}" class="qr">
</div>
`;
            });

            html += `
</div>
</body>
</html>`;

            const w = window.open('', '_blank');
            w.document.write(html);
            w.document.close();
            w.onload = () => w.print();
        });
    }


    // Update the existing QR modal to include a better close button
    $('#qrModal').on('hidden.bs.modal', function() {
        // Reset QR content when modal is closed
        setTimeout(() => {
            document.getElementById('qrContent').innerHTML = '';
        }, 300);
    });
</script>
<style>
    /* Add these styles to your existing CSS */
    .qr-voucher-item {
        page-break-inside: avoid;
        margin-bottom: 20px;
    }

    .qr-voucher {
        transition: transform 0.2s;
        border: 1px solid #e0e0e0;
        border-radius: 10px;
        overflow: hidden;
        background: white;
    }

    .voucher-header {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
        padding: 15px;
        text-align: center;
        font-weight: bold;
        font-size: 16px;
        letter-spacing: 1px;
    }

    .voucher-username {
        font-size: 24px;
        font-weight: bold;
        color: #2c3e50;
        text-align: center;
        padding: 10px;
        background: #f8f9fa;
        margin: 10px;
        border-radius: 5px;
        border: 2px dashed #bdc3c7;
    }

    .voucher-password {
        font-size: 20px;
        font-weight: bold;
        color: #e74c3c;
        text-align: center;
        padding: 10px;
        background: #fff;
        margin: 10px;
        border-radius: 5px;
        border: 2px solid #ffcccc;
    }

    .voucher-validity {
        background: #e8f6ef;
        color: #27ae60;
        padding: 10px;
        margin: 10px;
        border-radius: 5px;
        text-align: center;
        font-weight: bold;
        border: 1px dashed #2ecc71;
    }

    .voucher-qr {
        padding: 15px;
        text-align: center;
        background: white;
    }

    .voucher-login-url {
        font-size: 12px;
        color: #666;
        background: #f8f9fa;
        padding: 8px;
        margin: 10px;
        border-radius: 5px;
        text-align: center;
        word-break: break-all;
    }

    .voucher-footer {
        font-size: 10px;
        color: #888;
        text-align: center;
        padding: 8px;
        border-top: 1px dashed #ddd;
        background: #fafafa;
    }

    @media print {

        .btn-group,
        .modal,
        .box-tools,
        .no-print {
            display: none !important;
        }

        .qr-voucher {
            border: 1px solid #000 !important;
            box-shadow: none !important;
            margin: 5px;
            page-break-inside: avoid;
        }

        body {
            background: white !important;
            -webkit-print-color-adjust: exact;
        }
    }
</style>


<?= $this->endSection(); ?>