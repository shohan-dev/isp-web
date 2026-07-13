<?= $this->extend('layout/main-layout'); ?>
<?= $this->section('content'); ?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <!-- Main content -->
  <section class="content ipb-saas-list">

    
    <?= $this->include('components/page-header', [
      'title' => 'Offline Users',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Offline Users'],
      ],
    ]); ?>

<div class="box box-warning">
      <!-- <div class="box-header with-border ipb-box-toolbar">
        <div class="ipb-list-toolbar">
          <div class="ipb-list-toolbar-filters">
            <span class="ipb-filter-label"><i class="fa fa-list" aria-hidden="true"></i> Records</span>
          </div>
          <div class="ipb-list-toolbar-actions">
<?php if (userHasPermission('customer', 'create')) : ?>
            <a class="btn btn-primary" href="<?= route_to('route.customer.new'); ?>">
              <i class="fa fa-plus"></i> New Customer
            </a>
          <?php endif; ?>
          <?php if (userHasPermission('customer', 'delete')) : ?>
            <button class="btn btn-danger delete-btn">
              <i class="far fa-trash-can"></i> Delete Selected
            </button>
          <?php endif; ?>
          </div>
        </div>
      </div> -->

      <div class="box-body">
        <div class="table-responsive">
          <table class="table table-bordered table-striped datatable" width="100%">
          <caption class="sr-only">Offline users</caption>
          <thead>
            <tr>
              <!-- <th><input type="checkbox" id="select_all"></th> -->
              <th scope="col">#</th>
              <th scope="col">PPPOE Name</th>
              <th scope="col">Customer Name</th>
              <th scope="col">Service</th>
              <th scope="col">Last Caller ID</th>
              <th scope="col">Last Logged Out</th>
              <th scope="col">Logout reason</th>
              <th scope="col">profile</th>

            </tr>
          </thead>
          <tbody id="customer-data">
            <!-- Data will be populated here by AJAX -->
          </tbody>
        </table>
        </div>
      </div>
    </div>
  </section>
</div>

<?= $this->endSection('content'); ?>

<?= $this->section('script'); ?>

<script>
  $(document).ready(function() {

    const loadTraffic = () => {

      var routerId = "<?= esc($routerId) ?>";

      // Log the routerId to the browser's console
      console.log("Routers ID:", routerId);

      let baseUrl = "<?= base_url('/routers/load-traffic'); ?>";
      let url = `${baseUrl}/${routerId}`;


      $.ajax({
        url: url,
        type: 'GET',
        success: function(response) {
          const result = response.response;

          // Extract users
          const allUsers = result.data.allusers || []; // Fallback to empty array
          const activeUsers = result.data.activeusers || [];

          // Create a Set of active user names for quick lookup
          const activeUserNames = new Set(activeUsers.map(user => user['name']));

          // Filter only inactive users (not present in activeUsers)
          const inactiveUsers = allUsers.filter(user => !activeUserNames.has(user['name']));

          let userRows = '';
          let allUsernames = [];

          inactiveUsers.forEach((user, index) => {
          console.log("Inactive User:", user); // Log each inactive user
            allUsernames.push(user['name']); // collect username
            userRows += `<tr>
            <td>${index + 1}</td>
            <td><a href="#" class="user-name" data-name="${user['name']}">${user['name']}</a></td>
            <td>${user['customer_name'] || 'N/A'}</td>
            <td>${user['service']}</td>
            <td>${user['last-caller-id']}</td>
            <td>${user['last-logged-out']}</td>
            <td>${user['last-disconnect-reason']}</td>
            <td>${user['profile']}</td>
        </tr>`;
          });

          $('#customer-data').html(userRows);

          $.ajax({
            url: "<?= route_to('route.user.profile'); ?>",
            type: "POST",
            data: {
              names: allUsernames
            },
            headers: {
              '<?= csrf_header() ?>': '<?= csrf_hash() ?>',
            },
            success: function(response) {
              if (response.status === "success") {
                // console.log("User Profiles:", response.response);
                const profiles = response.response;

                // Map usernames -> customer names
                const customerMap = {};
                profiles.forEach(p => {
                  customerMap[p.pppoe_secret] = p.customer_name;
                });

                // Update table rows dynamically
                $("#customer-data tr").each(function() {
                  let username = $(this).find(".user-name").data("name");
                  if (customerMap[username]) {
                    // Update Customer Name cell
                    $(this).find("td").eq(2).text(customerMap[username]);
                  }
                });

                // 🔹 Tell DataTables to redraw with new content
                $('.datatable').DataTable().rows().invalidate().draw(false);
              }
            },

            error: function(xhr) {
              console.error("Error fetching profiles:", xhr.responseText);
            }
          });

          // Reinitialize DataTable
          $('.datatable').DataTable({
            "destroy": false, // Allow reinitialization
            "pageLength": 100,
            "lengthChange": true,
            "columnDefs": [{
              "targets": "_all",
              "defaultContent": "-"
            }],
            'lengthMenu': [
              [25, 50, 100, 250, 500, 1000],
              [25, 50, 100, 250, 500, "All"]
            ],
          });
        },
        error: function({
          responseText
        }) {
          const result = JSON.parse(responseText);
          tata.error("Couldn't load traffic data", result.response);
        }
      });
    }

    loadTraffic(); // Load users on page load

    // Check all checkbox function
    $('#select_all').click(function() {
      $('.input-check-selected').prop('checked', this.checked);
    });

    $(document).on('click', '.delete-btn', function() {
      const selectedIds = $('.input-check-selected:checkbox:checked').map(function() {
        return $(this).val();
      }).get();

      if (selectedIds.length > 0) {
        // Confirm deletion
        swal({
          title: "Confirmation",
          text: "Are you sure you want to delete the selected customers? This will also delete these PPPoE users from your MikroTik router.",
          dangerMode: true,
          icon: 'warning',
          buttons: ["No", {
            text: "Yes",
            closeModal: false,
          }],
        }).then((willDelete) => {
          if (willDelete) {
            $.ajax({
              url: '<?= route_to("route.customer.delete"); ?>',
              type: 'DELETE',
              data: {
                ids: selectedIds
              },
              headers: {
                '<?= csrf_header() ?>': '<?= csrf_hash() ?>',
              },
              success: function(result) {
                swal.close();
                tata.success('Customers deleted', result.response);
                $('.datatable').DataTable().ajax.reload(null, false);
                loadTraffic(); // Refresh the data after deletion
              },
              error: function(response) {
                const result = jQuery.parseJSON(response.responseText);
                swal.close();
                tata.error("Couldn't delete customers", result.response);
              }
            });
          }
        });
      } else {
        tata.error('Select customers', 'No customers selected for deletion.');
      }
    });
  });
</script>

<?= $this->endSection('script'); ?>