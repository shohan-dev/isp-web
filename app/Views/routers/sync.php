<?= $this->extend('layout/main-layout'); ?>


<?= $this->section('content'); ?>

<div class="content-wrapper">
    <section class="content ipb-saas-list">

        
    <?= $this->include('components/page-header', [
      'title' => ($details->name ?? 'Router') . ' — User Sync',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Mikrotik Routers'],
        ['label' => 'User Sync'],
      ],
    ]); ?>
<div class="box box-warning">

            <?= form_open('', 'id="form"'); ?>

            <div class="box-header with-border ipb-box-toolbar">
                <div class="ipb-list-toolbar">
                    <div class="ipb-list-toolbar-actions">
                    <?= form_button([
                        "id"       => "submit-btn",
                        "content"  => "Import Users",
                        "class"    => "btn btn-warning btn-sm",
                        "type"     => "submit",
                        "disabled" => "disabled",
                        "style"    => "height:32px; padding: 2px 10px; font-size:12px;"
                    ]); ?>
                    <span id="row-count-badge" class="badge" style="background:#6c757d; color:#fff; font-size:13px; padding:5px 12px; border-radius:20px; display:none;"></span>

                    <span style="font-size:12px; color:#555; font-weight:bold; margin-left:4px;">Bulk Actions:</span>

                    <?php
                    $pkgData = empty($packages) ? ['' => 'No package found!'] : ['' => '--Select Package--'];
                    foreach ($packages as $package) { $pkgData[$package->id] = $package->package_name; }
                    echo form_dropdown('bulk_package_id', $pkgData, "", 'id="bulk_package_id" class="form-control input-sm" style="width: 125px !important; height: 32px; font-size: 11px; display: inline-block; padding: 2px 6px;"');
                    ?>

                    <?php
                    $areaData = empty($areas) ? ['' => 'No service area found!'] : ['' => '--Select Area--'];
                    foreach ($areas as $area) { $areaData[$area->id] = $area->area_name; }
                    echo form_dropdown('bulk_area_id', $areaData, "", 'id="bulk_area_id" class="form-control input-sm" style="width: 125px !important; height: 32px; font-size: 11px; display: inline-block; padding: 2px 6px;"');
                    ?>

                    <input type="date" id="bulk_will_expire" class="form-control input-sm" style="width: 125px !important; height: 32px; font-size: 11px; display: inline-block; padding: 2px 6px;" title="Bulk Expire Date">

                    <button type="button" id="btn-copy-username" class="btn btn-default btn-sm" style="height: 32px; font-size: 11px; padding: 2px 8px;" title="Use Username as Name">
                        <i class="fa fa-copy"></i> Username as Name
                    </button>

                    <button type="button" id="btn-auto-fill" class="btn btn-info btn-sm" style="height: 32px; font-size: 11px; padding: 2px 8px;" title="Auto-Fill Selected">
                        <i class="fa fa-magic"></i> Auto-Fill
                    </button>

                    <button type="button" id="btn-export-excel" class="btn btn-success btn-sm" style="height: 32px; font-size: 11px; padding: 2px 8px;" title="Export to Excel (CSV)">
                        <i class="fa fa-file-excel-o"></i> Export Excel
                    </button>
                    </div>
                    <div class="ipb-list-toolbar-filters" style="position:relative; flex:1 1 200px; min-width:0;">
                    <i class="fa fa-search" aria-hidden="true" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-muted); pointer-events:none; z-index:1;"></i>
                    <input
                        type="search"
                        id="syncSearch"
                        class="form-control"
                        placeholder="Search by username or profile..."
                        style="padding-left:34px;"
                        autocomplete="off"
                        aria-label="Search sync users"
                    >
                    </div>
                </div>
            </div>

            <div class="box-body table-responsive">

                <?php if (!empty($mikrotik_pending)): ?>

                <!-- Loading state: shown until get_router_sync_secrets() answers (see script section below) -->
                <div id="ipbSyncLoading" style="text-align:center; padding:60px 20px;">
                    <i class="fa fa-spinner fa-spin" style="font-size:32px; color:var(--info-500, #4f46e5); margin-bottom:16px; display:block;"></i>
                    <p style="color:var(--text-muted, #888); font-size:14px; margin:0;">Loading PPPoE users from <strong><?= esc($details->name) ?></strong>&hellip;</p>
                </div>

                <table class="table table-bordered table-striped" id="syncTable" hidden>
                    <caption class="sr-only">PPPoE user sync</caption>
                    <thead class="text-nowrap">
                        <tr>
                            <th scope="col"><input type="checkbox" class="form-check-input" id="checkAll"></th>
                            <th scope="col">#</th>
                            <th scope="col">PPPoE Username</th>
                            <th scope="col">PPPoE Password</th>
                            <th scope="col">Service</th>
                            <th scope="col">Profile</th>
                            <th scope="col">Name</th>
                            <th scope="col">Package</th>
                            <th scope="col">Area</th>
                            <th scope="col">Expire Date</th>
                        </tr>
                    </thead>
                    <tbody id="syncTableBody">
                    </tbody>
                </table>

                <!-- Shown when search produces zero matches -->
                <div id="noSearchResults" style="display:none; text-align:center; padding:40px 20px;">
                    <i class="fa fa-search" style="font-size:40px; color:#ccc; margin-bottom:12px; display:block;"></i>
                    <p style="color:var(--text-muted, #888); font-size:15px; margin:0;">No results match your search.</p>
                </div>

                <!-- Empty / Connection-Error state, filled in by script section once the AJAX call answers -->
                <div id="ipbSyncEmptyState" style="display:none; text-align:center; padding:60px 20px;">
                    <div style="width:80px; height:80px; border-radius:50%; background:var(--info-50, #f0f4ff); display:flex; align-items:center; justify-content:center; margin:0 auto 20px;">
                        <i id="ipbSyncEmptyIcon" class="fa fa-check-circle" style="font-size:36px; color:var(--info-500, #4f46e5);"></i>
                    </div>
                    <h4 id="ipbSyncEmptyTitle" style="color:var(--text-primary); margin-bottom:8px;"></h4>
                    <p id="ipbSyncEmptyBody" style="color:var(--text-muted); font-size:14px; max-width:420px; margin:0 auto;"></p>
                    <a id="ipbSyncEmptyAction" href="<?= route_to('route.dashboard'); ?>" class="btn btn-default" style="margin-top:24px;">
                        <i class="fa fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>

                <?php else: ?>

                <?php
                // Determine if there are any un-synced rows
                $hasRows = false;
                $canConnect = !empty($secrets) && !empty($secrets[0]['.id']);
                if ($canConnect) {
                    foreach ($secrets as $s) {
                        if (empty(getUserByPPPoEId($s['.id'], $details->id))) {
                            $hasRows = true;
                            break;
                        }
                    }
                }
                ?>

                <?php if ($hasRows): ?>

                <table class="table table-bordered table-striped" id="syncTable">
                    <caption class="sr-only">PPPoE user sync</caption>
                    <thead class="text-nowrap">
                        <tr>
                            <th scope="col"><input type="checkbox" class="form-check-input" id="checkAll"></th>
                            <th scope="col">#</th>
                            <th scope="col">PPPoE Username</th>
                            <th scope="col">PPPoE Password</th>
                            <th scope="col">Service</th>
                            <th scope="col">Profile</th>
                            <th scope="col">Name</th>
                            <th scope="col">Package</th>
                            <th scope="col">Area</th>
                            <th scope="col">Expire Date</th>
                        </tr>
                    </thead>
                    <tbody id="syncTableBody">
                        <?php
                        $i = 0;
                        foreach ($secrets as $count => $secret):
                            $user = getUserByPPPoEId($secret['.id'], $details->id);
                            if (!empty($user)) continue;
                        ?>
                            <tr data-username="<?= strtolower(esc($secret['name'])) ?>" data-profile="<?= strtolower(esc($secret['profile'] ?? '')) ?>">
                                <td>
                                    <input type="checkbox" class="form-check-input input-check-selected" value="<?= $i; ?>">
                                </td>
                                <td>
                                    <input type="hidden" name="id" value="<?= $secret['.id']; ?>" />
                                    <?= ++$count; ?>
                                </td>
                                <td>
                                    <input type="hidden" name="username" value="<?= $secret['name']; ?>" />
                                    <?= $secret['name']; ?>
                                </td>
                                <td>
                                    <input type="hidden" name="password" value="<?= $secret['password']; ?>" />
                                    <?= $secret['password']; ?>
                                </td>
                                <td><?= $secret['service']; ?></td>
                                <td><?= $secret['profile'] ?? ''; ?></td>
                                <td>
                                    <?= form_input([
                                        'name'  => 'name',
                                        'class' => 'form-control',
                                    ]); ?>
                                </td>
                                <td>
                                    <?php
                                    $pkgData = empty($packages) ? ['' => 'No package found!'] : ['' => '--Select--'];
                                    foreach ($packages as $package) { $pkgData[$package->id] = $package->package_name; }
                                    echo form_dropdown('package_id', $pkgData, "", 'class="form-control package-select" style="min-width: 220px"');
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $areaData = empty($areas) ? ['' => 'No service area found!'] : ['' => '--Select--'];
                                    foreach ($areas as $area) { $areaData[$area->id] = $area->area_name; }
                                    echo form_dropdown('area_id', $areaData, "", 'class="form-control area-select" style="min-width: 220px"');
                                    ?>
                                </td>
                                <td>
                                    <?= form_input([
                                        'name'  => 'will_expire',
                                        'type'  => 'date',
                                        'class' => 'form-control expire-input',
                                        'style' => 'min-width: 150px',
                                        'value' => date('Y-m-d', strtotime('+30 days')),
                                    ]); ?>
                                </td>
                            </tr>
                        <?php $i++; endforeach; ?>
                    </tbody>
                </table>

                <!-- Shown when search produces zero matches -->
                <div id="noSearchResults" style="display:none; text-align:center; padding:40px 20px;">
                    <i class="fa fa-search" style="font-size:40px; color:#ccc; margin-bottom:12px; display:block;"></i>
                    <p style="color:var(--text-muted, #888); font-size:15px; margin:0;">No results match your search.</p>
                </div>

                <?php else: ?>

                <!-- Empty / Connection-Error State -->
                <div style="text-align:center; padding:60px 20px;">
                    <div style="width:80px; height:80px; border-radius:50%; background:var(--info-50, #f0f4ff); display:flex; align-items:center; justify-content:center; margin:0 auto 20px;">
                        <?php if (!$canConnect): ?>
                            <i class="fa fa-plug" style="font-size:36px; color:var(--error-500, #ef4444);"></i>
                        <?php else: ?>
                            <i class="fa fa-check-circle" style="font-size:36px; color:var(--info-500, #4f46e5);"></i>
                        <?php endif; ?>
                    </div>
                    <?php if (!$canConnect): ?>
                        <h4 style="color:var(--error-500, #ef4444); margin-bottom:8px;">Router Not Reachable</h4>
                        <p style="color:var(--text-muted, #888); font-size:14px; max-width:420px; margin:0 auto;">
                            Could not connect to router <strong><?= esc($details->name) ?></strong>.<br>
                            Please verify the IP address, port, username and password in the router settings.
                        </p>
                        <a href="<?= route_to('route.routers'); ?>" class="btn btn-default" style="margin-top:24px;">
                            <i class="fa fa-cog"></i> Router Settings
                        </a>
                    <?php else: ?>
                        <h4 style="color:var(--text-primary); margin-bottom:8px;">All Synced Up!</h4>
                        <p style="color:var(--text-muted); font-size:14px; max-width:420px; margin:0 auto;">
                            All PPPoE users from <strong><?= esc($details->name) ?></strong> are already imported into the system.
                        </p>
                        <a href="<?= route_to('route.dashboard'); ?>" class="btn btn-default" style="margin-top:24px;">
                            <i class="fa fa-arrow-left"></i> Back to Dashboard
                        </a>
                    <?php endif; ?>
                </div>

                <?php endif; ?>

                <?php endif; ?>

            </div>

            <?= form_close(); ?>
        </div>
    </section>
</div>

<?= $this->endSection('content'); ?>

<?= $this->section('script'); ?>
<script>
    // Row count badge — factored out of the old immediately-invoked function so
    // it can also be re-run after rows are injected by the AJAX call below.
    function ipbUpdateRowCountBadge() {
        var rows  = document.querySelectorAll('#syncTableBody tr');
        var badge = document.getElementById('row-count-badge');
        if (badge) {
            if (rows.length > 0) {
                badge.textContent      = rows.length + ' user' + (rows.length !== 1 ? 's' : '') + ' to import';
                badge.style.background = '#6c757d';
                badge.style.display    = 'inline-block';
            } else {
                badge.style.display = 'none';
            }
        }
    }
    ipbUpdateRowCountBadge();

    // Live search
    document.addEventListener('DOMContentLoaded', function () {
        var searchInput = document.getElementById('syncSearch');
        if (!searchInput) return;

        searchInput.addEventListener('input', function () {
            var q    = this.value.toLowerCase().trim();
            var rows = document.querySelectorAll('#syncTableBody tr');
            var visible = 0;

            rows.forEach(function (row) {
                var username = row.getAttribute('data-username') || '';
                var profile  = row.getAttribute('data-profile')  || '';
                var matches  = !q || username.includes(q) || profile.includes(q);
                row.style.display = matches ? '' : 'none';
                if (matches) visible++;
            });

            var noResults = document.getElementById('noSearchResults');
            if (noResults) noResults.style.display = (visible === 0 && q) ? 'block' : 'none';

            var badge = document.getElementById('row-count-badge');
            if (badge && rows.length > 0) {
                if (q && visible < rows.length) {
                    badge.textContent      = visible + ' of ' + rows.length + ' shown';
                    badge.style.background = '#f59e0b';
                } else {
                    badge.textContent      = rows.length + ' user' + (rows.length !== 1 ? 's' : '') + ' to import';
                    badge.style.background = '#6c757d';
                }
            }
        });
    });

    // Select All
    $("#checkAll").change(function () {
        var isChecked = this.checked;
        var bulkPkg = $("#bulk_package_id").val();
        var bulkArea = $("#bulk_area_id").val();
        var bulkExpire = $("#bulk_will_expire").val();

        if (isChecked) {
            $(".input-check-selected:visible").each(function () {
                this.checked = true;
                var $row = $(this).closest('tr');
                $row.find('.form-control').attr('required', 'required');

                // Apply active bulk actions on check
                if (bulkPkg !== "") $row.find('.package-select').val(bulkPkg);
                if (bulkArea !== "") $row.find('.area-select').val(bulkArea);
                if (bulkExpire !== "") $row.find('.expire-input').val(bulkExpire);
            });
            $("#submit-btn").removeAttr("disabled");
        } else {
            $(".input-check-selected").each(function () {
                this.checked = false;
                $(this).closest('tr').find('.form-control').removeAttr('required');
            });
            $("#submit-btn").attr("disabled", "disabled");
        }
    });

    // Individual checkbox — delegated (rows may be injected after page load
    // by the mikrotik_pending AJAX fetch below, so a direct .click() bind
    // taken at script-load time would miss them).
    $(document).on('click', '.input-check-selected', function () {
        var isChecked = $(this).is(":checked");
        var $row = $(this).closest('tr');

        if (isChecked) {
            $row.find('.form-control').attr('required', 'required');
            $("#submit-btn").removeAttr("disabled");

            // Apply active bulk actions on check
            var bulkPkg = $("#bulk_package_id").val();
            var bulkArea = $("#bulk_area_id").val();
            var bulkExpire = $("#bulk_will_expire").val();

            if (bulkPkg !== "") $row.find('.package-select').val(bulkPkg);
            if (bulkArea !== "") $row.find('.area-select').val(bulkArea);
            if (bulkExpire !== "") $row.find('.expire-input').val(bulkExpire);

            var allChecked = true;
            $(".input-check-selected:visible").each(function () { if (!this.checked) allChecked = false; });
            if (allChecked) $("#checkAll").prop("checked", true);
        } else {
            $("#checkAll").prop("checked", false);
            $row.find('.form-control').removeAttr('required');
            if ($('.input-check-selected:checked').length === 0) {
                $("#submit-btn").attr("disabled", "disabled");
            }
        }
    });

    // Bulk Package Selection
    $("#bulk_package_id").change(function () {
        var selectedVal = $(this).val();
        if (selectedVal !== "") {
            $(".input-check-selected:checked").each(function () {
                $(this).closest('tr').find('.package-select').val(selectedVal);
            });
        }
    });

    // Bulk Area Selection
    $("#bulk_area_id").change(function () {
        var selectedVal = $(this).val();
        if (selectedVal !== "") {
            $(".input-check-selected:checked").each(function () {
                $(this).closest('tr').find('.area-select').val(selectedVal);
            });
        }
    });

    // Bulk Expiration Date Selection
    $("#bulk_will_expire").change(function () {
        var selectedVal = $(this).val();
        if (selectedVal !== "") {
            $(".input-check-selected:checked").each(function () {
                $(this).closest('tr').find('.expire-input').val(selectedVal);
            });
        }
    });

    // Use Username as Name
    $("#btn-copy-username").click(function () {
        $(".input-check-selected:checked").each(function () {
            var $row = $(this).closest('tr');
            var username = $row.find('input[name="username"]').val();
            $row.find('input[name="name"]').val(username);
        });
    });

    // Auto-Fill Selected Rows
    $("#btn-auto-fill").click(function () {
        var packages = [];
        $(".package-select").first().find('option').each(function() {
            var val = $(this).val();
            if (val !== "") {
                packages.push({ id: val, name: $(this).text().toLowerCase() });
            }
        });
        var firstAreaVal = $(".area-select").first().find('option').eq(1).val() || "";

        var $checked = $(".input-check-selected:checked");
        if ($checked.length === 0) {
            tata.error('Error', 'Please select at least one user to auto-fill.');
            return;
        }

        $checked.each(function () {
            var $row = $(this).closest('tr');
            
            // 1. Copy username to name
            var username = $row.find('input[name="username"]').val();
            $row.find('input[name="name"]').val(username);

            // 2. Match profile to package
            var profile = $row.data('profile') || '';
            var matchedPkgId = "";
            if (profile !== "") {
                var cleanProfile = profile.replace(/[^a-zA-Z0-9]/g, '').toLowerCase();
                for (var i = 0; i < packages.length; i++) {
                    var cleanPkgName = packages[i].name.replace(/[^a-zA-Z0-9]/g, '');
                    if (cleanPkgName.indexOf(cleanProfile) !== -1 || cleanProfile.indexOf(cleanPkgName) !== -1) {
                        matchedPkgId = packages[i].id;
                        break;
                    }
                }
            }
            if (matchedPkgId !== "") {
                $row.find('.package-select').val(matchedPkgId);
            } else if (packages.length > 0) {
                $row.find('.package-select').val(packages[0].id);
            }

            // 3. Select first area
            if (firstAreaVal !== "") {
                $row.find('.area-select').val(firstAreaVal);
            }

            // 4. Set expire date to today + 30 days
            var todayPlus30 = new Date();
            todayPlus30.setDate(todayPlus30.getDate() + 30);
            var dateString = todayPlus30.toISOString().split('T')[0];
            $row.find('.expire-input').val(dateString);
        });
    });

    // Export Selected Rows to Excel (CSV)
    $("#btn-export-excel").click(function () {
        var csv = [];
        csv.push("PPPoE Username,PPPoE Password,Service,Profile,Name,Package,Area,Expire Date");

        var $checkedRows = $(".input-check-selected:checked");
        if ($checkedRows.length === 0) {
            tata.error('Error', 'Please select at least one user to export.');
            return;
        }

        $checkedRows.each(function () {
            var $row = $(this).closest('tr');
            var username = $row.find('input[name="username"]').val() || "";
            var password = $row.find('input[name="password"]').val() || "";
            var service = $row.find('td').eq(4).text().trim() || "";
            var profile = $row.find('td').eq(5).text().trim() || "";
            var name = $row.find('input[name="name"]').val() || "";
            var packageText = $row.find('.package-select option:selected').text() || "";
            var areaText = $row.find('.area-select option:selected').text() || "";
            var willExpire = $row.find('.expire-input').val() || "";

            var rowData = [
                '"' + username.replace(/"/g, '""') + '"',
                '"' + password.replace(/"/g, '""') + '"',
                '"' + service.replace(/"/g, '""') + '"',
                '"' + profile.replace(/"/g, '""') + '"',
                '"' + name.replace(/"/g, '""') + '"',
                '"' + packageText.replace(/"/g, '""') + '"',
                '"' + areaText.replace(/"/g, '""') + '"',
                '"' + willExpire.replace(/"/g, '""') + '"'
            ];
            csv.push(rowData.join(","));
        });

        var csvString = csv.join("\n");
        var blob = new Blob([csvString], { type: 'text/csv;charset=utf-8;' });
        var link = document.createElement("a");
        var url = URL.createObjectURL(blob);
        link.setAttribute("href", url);
        link.setAttribute("download", "router_users_export_" + new Date().toISOString().slice(0,10) + ".csv");
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });

    // Form submit
    $("#form").submit(function (e) {

        var ids         = $('input[name="id"]');
        var usernames   = $('input[name="username"]');
        var passwords   = $('input[name="password"]');
        var names       = $('input[name="name"]');
        var emails      = $('input[name="email"]');
        var addresses   = $('input[name="address"]');
        var packageIds  = $('.package-select');
        var areaIds     = $('.area-select');
        var mobiles     = $('input[name="mobile"]');
        var willExpires = $('.expire-input');

        var id = [], username = [], password = [], name = [], email = [],
            address = [], package_id = [], area_id = [], mobile = [], will_expire = [], profile = [];

        $('.input-check-selected:checked').each(function () {
            var idx = $(this).val();
            var $row = $(this).closest('tr');
            id.push($(ids[idx]).val());
            username.push($(usernames[idx]).val());
            password.push($(passwords[idx]).val());
            name.push($(names[idx]).val());
            email.push($(emails[idx]).val());
            address.push($(addresses[idx]).val());
            package_id.push($(packageIds[idx]).val());
            area_id.push($(areaIds[idx]).val());
            mobile.push($(mobiles[idx]).val());
            will_expire.push($(willExpires[idx]).val());
            profile.push($row.data('profile') || '');
        });

        if (id.length > 0) {
            var form = this;

            $.ajax({
                url:  '<?= route_to('route.routers.import', $details->id); ?>',
                type: 'POST',
                data: { id, username, password, name, email, address, package_id, area_id, mobile, will_expire, profile },
                headers: { '<?= csrf_header() ?>': '<?= csrf_hash() ?>' },
                beforeSend: function () {
                    $(form).find('button[type="submit"]').html("<i class='fas fa-spinner fa-spin'></i> Please wait").attr('disabled', true);
                },
                success: function (result) {
                    $(form).find('button[type="submit"]').html('Import Users').removeAttr('disabled');
                    tata.success('Users imported', result.response, {
                        onClose: function () {
                            location.href = '<?= route_to("route.routers.sync", $details->id); ?>';
                        }
                    });
                },
                error: function (xhr) {
                    try {
                        var result  = JSON.parse(xhr.responseText);
                        var message = result.message || 'Unexpected error occurred.';
                        tata.error("Couldn't import users", message);
                    } catch (e) {
                        tata.error("Couldn't import users", 'Unexpected server error.');
                    }
                    $(form).find('button[type="submit"]').html('Import Users').removeAttr('disabled');
                }
            });

            e.preventDefault();
        }
    });

    <?php if (!empty($mikrotik_pending)): ?>
    // Page-load-performance audit (Axis1 #5): the full PPPoE secret list used
    // to be pulled from MikroTik synchronously before this page could render
    // at all, and the whole page was replaced by an error view if the router
    // happened to be slow/offline at that instant. The shell above already
    // renders immediately with a loading state; this fills in the live table
    // once the router answers, without blocking first paint.
    (function () {
        var packages = <?= json_encode(array_map(static function ($p) {
            return ['id' => $p->id, 'name' => $p->package_name];
        }, $packages ?? [])) ?>;
        var areas = <?= json_encode(array_map(static function ($a) {
            return ['id' => $a->id, 'name' => $a->area_name];
        }, $areas ?? [])) ?>;
        var routerName = <?= json_encode((string) ($details->name ?? 'Router')) ?>;
        var routerSettingsUrl = <?= json_encode(route_to('route.routers')) ?>;
        var dashboardUrl = <?= json_encode(route_to('route.dashboard')) ?>;

        function escapeHtml(value) {
            return $('<div>').text(value == null ? '' : String(value)).html();
        }

        function buildSelect(name, options, emptyLabel, extraClass) {
            var $select = $('<select>', { name: name, class: 'form-control' + (extraClass ? ' ' + extraClass : '') });
            if (!options.length) {
                $select.append($('<option>', { value: '', text: emptyLabel }));
                return $select;
            }
            $select.append($('<option>', { value: '', text: '--Select--' }));
            options.forEach(function (opt) {
                $select.append($('<option>', { value: opt.id, text: opt.name }));
            });
            return $select;
        }

        function showEmptyState(canConnect) {
            $('#ipbSyncLoading').hide();
            $('#syncTable').hide();
            var $state = $('#ipbSyncEmptyState');
            var $icon = $('#ipbSyncEmptyIcon');
            var $title = $('#ipbSyncEmptyTitle');
            var $body = $('#ipbSyncEmptyBody');
            var $action = $('#ipbSyncEmptyAction');

            if (!canConnect) {
                $icon.attr('class', 'fa fa-plug').css('color', 'var(--error-500, #ef4444)');
                $title.css('color', 'var(--error-500, #ef4444)').text('Router Not Reachable');
                $body.html('Could not connect to router <strong>' + escapeHtml(routerName) + '</strong>.<br>Please verify the IP address, port, username and password in the router settings.');
                $action.attr('href', routerSettingsUrl).html('<i class="fa fa-cog"></i> Router Settings');
            } else {
                $icon.attr('class', 'fa fa-check-circle').css('color', 'var(--info-500, #4f46e5)');
                $title.css('color', 'var(--text-primary)').text('All Synced Up!');
                $body.html('All PPPoE users from <strong>' + escapeHtml(routerName) + '</strong> are already imported into the system.');
                $action.attr('href', dashboardUrl).html('<i class="fa fa-arrow-left"></i> Back to Dashboard');
            }
            $state.show();
        }

        $.ajax({
            url: '<?= route_to('route.routers.getSyncSecrets', $details->id); ?>',
            type: 'GET',
            dataType: 'json'
        }).done(function (resp) {
            if (!resp || !resp.ok) {
                showEmptyState(false);
                return;
            }

            var secrets = resp.secrets || [];
            var syncedIds = resp.synced_ids || [];
            var canConnect = secrets.length > 0 && !!secrets[0]['.id'];

            var $tbody = $('#syncTableBody');
            var i = 0;

            secrets.forEach(function (secret) {
                var pppoeId = secret['.id'];
                if (!pppoeId || syncedIds.indexOf(pppoeId) !== -1) return;

                var username = secret.name || '';
                var profile = secret.profile || '';

                var $row = $('<tr>', {
                    'data-username': username.toLowerCase(),
                    'data-profile': profile.toLowerCase()
                });

                $row.append($('<td>').append($('<input>', { type: 'checkbox', class: 'form-check-input input-check-selected', value: i })));
                $row.append(
                    $('<td>')
                        .append($('<input>', { type: 'hidden', name: 'id', value: pppoeId }))
                        .append(document.createTextNode(i + 1))
                );
                $row.append(
                    $('<td>')
                        .append($('<input>', { type: 'hidden', name: 'username', value: username }))
                        .append(document.createTextNode(username))
                );
                $row.append(
                    $('<td>')
                        .append($('<input>', { type: 'hidden', name: 'password', value: secret.password || '' }))
                        .append(document.createTextNode(secret.password || ''))
                );
                $row.append($('<td>', { text: secret.service || '' }));
                $row.append($('<td>', { text: profile }));
                $row.append($('<td>').append($('<input>', { type: 'text', name: 'name', class: 'form-control' })));
                $row.append($('<td>').append(buildSelect('package_id', packages, 'No package found!', 'package-select').css({ 'min-width': '220px' })));
                $row.append($('<td>').append(buildSelect('area_id', areas, 'No service area found!', 'area-select').css({ 'min-width': '220px' })));
                var defaultExpire = new Date();
                defaultExpire.setDate(defaultExpire.getDate() + 30);
                var expireVal = defaultExpire.toISOString().slice(0, 10);
                $row.append($('<td>').append($('<input>', {
                    type: 'date',
                    name: 'will_expire',
                    class: 'form-control expire-input',
                    value: expireVal,
                    css: { 'min-width': '150px' }
                })));

                $tbody.append($row);
                i++;
            });

            $('#ipbSyncLoading').hide();

            if (i === 0) {
                showEmptyState(canConnect);
                return;
            }

            $('#syncTable').prop('hidden', false).show();
            ipbUpdateRowCountBadge();
        }).fail(function () {
            showEmptyState(false);
        });
    })();
    <?php endif; ?>
</script>

<?= $this->endSection('script'); ?>