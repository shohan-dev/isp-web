<?php
// Assuming $details is an instance of a specific class, e.g., ResellerDetails
/** @var \App\Entities\ResellerDetails $details */

// If $details is a standard class object without a specific class, you can use:
/** @var object $details */
/** @var object $rdetails */
?>

<?= $this->extend('layout/main-layout'); ?>


<?= $this->section('content'); ?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <!-- Main content -->
  <section class="content ipb-saas-list">
    
    <?= $this->include('components/page-header', [
      'title' => 'POP Details',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'POP'],
        ['label' => 'POP Details'],
      ],
    ]); ?>
<div class="row">
      <div class="col-md-5 col-lg-4">

        <div class="box box-warning">
          <div class="box-body box-profile">

            <img class="profile-user-img img-responsive img-circle"
              src="<?= base_url('assets/img/icon/avatar.png'); ?>">

            <h3 class="profile-username text-center">
              <?= $details->name; ?>
            </h3>

            <p class="text-muted text-center text-uppercase">
              Customer Id : <?= $details->id; ?>
            </p>

            <ul class="list-group list-group-unbordered">

              <!-- <li class="list-group-item">
                <b>Package</b>
                <span class="pull-right">
                  <?= getResellersPackage($details->id)['package_name'] ?? '--'; ?>
                </span>

              </li> -->

              <li class="list-group-item">
                <b>Reg. At</b>
                <span class="pull-right">
                  <?= date('d M Y, h:i a', strtotime($details->created_at)); ?>
                </span>
              </li>

              <li class="list-group-item">
                <b>Updated At</b>
                <span class="pull-right">
                  <?= date('d M Y, h:i a', strtotime($details->updated_at)); ?>
                </span>
              </li>

            </ul>
          </div>
        </div>

        <div class="box box-warning">
          <div class="box-header with-border">
            <h3 class="box-title">Organization Details</h3>
          </div>

          <div class="box-body">

            <ul class="list-group list-group-unbordered">

              <!-- <li class="list-group-item">
                <b>Organization Name</b>
                <span class="pull-right">
                <?= $rdetails['organization_name'] ?? 'N/A' ?>

                </span>
              </li> -->

              <li class="list-group-item">
                <b>Admin's Name</b>
                <span class="pull-right">
                  <?= $rdetails['admin_name']?? 'N/A'  ?>
                </span>
              </li>
              <li class="list-group-item">
                <b>commission</b>
                <span class="pull-right">
                <?= isset($rdetails['discount']) ? $rdetails['discount'] . '%' : 'N/A' ?>

                </span>
              </li>

              <li class="list-group-item">
                <b>National ID</b>
                <span class="pull-right">
                  <?= $rdetails['nationalid'] ?? '--' ?>
                </span>
              </li>
             
              <li class="list-group-item">
                <b>District</b>
                <span class="pull-right">
                  <?= $rdetails['district']?? 'N/A'  ?>
                </span>
              </li>




              <li class="list-group-item">
    <b>Customer Type</b>
    <span class="pull-right">
        <?php 
        $customerTypes = !empty($rdetails['customer_type']) ? json_decode($rdetails['customer_type'], true) : []; 
        if (!empty($customerTypes)) : 
        ?>
        <div class="d-flex flex-row" style="gap: 10px;">
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="customer_type[]" id="pppoe" value="PPPOE"
                    <?= in_array('PPPOE', $customerTypes) ? 'checked' : '' ?>>
                <label class="form-check-label" for="pppoe">PPPOE</label>
            </div>

            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="customer_type[]" id="static" value="Static"
                    <?= in_array('Static', $customerTypes) ? 'checked' : '' ?>>
                <label class="form-check-label" for="static">Static</label>
            </div>

            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="customer_type[]" id="hotspot" value="Hotspot"
                    <?= in_array('Hotspot', $customerTypes) ? 'checked' : '' ?>>
                <label class="form-check-label" for="hotspot">Hotspot</label>
            </div>
        </div>
        <?php else: ?>
            <span>N/A</span>
        <?php endif; ?>
    </span>
</li>









            </ul>
          </div>
        </div>

      </div>

      <div class="col-md-7 col-lg-8">

        <div class="box box-warning">
          <div class="box-header with-border">
            <h3 class="box-title">Account Info</h3>
          </div>

          <div class="box-body">

            <ul class="list-group list-group-unbordered">

              <li class="list-group-item">
                <b>Customer Name</b>
                <span class="pull-right">
                <?= $details->name; ?>
                </span>
              </li>

              <li class="list-group-item">
                <b>Mobile Number</b>
                <span class="pull-right">
                  <?= $details->mobile; ?>
                </span>
              </li>

              <li class="list-group-item">
                <b>Email Id</b>
                <span class="pull-right">
                  <?= $details->email; ?>
                </span>
              </li>

              <li class="list-group-item">
                <b>Service Area</b>
                <span class="pull-right">
                  <?= !empty(getUserArea($details->id)) ? getUserArea($details->id)->area_name . ' (' . getUserArea($details->id)->area_code . ')' : '--'; ?>
                </span>
              </li>

              <li class="list-group-item">
                <b>Address</b>
                <span class="pull-right">
                  <?= $details->address; ?>
                </span>
              </li>

              <li class="list-group-item">
                <b>Acc. Status</b>
                <span class="pull-right">
                  <?= $details->status === 'active' ? '<span class="ipb-pay-badge is-success">Active</span>' : '<span class="ipb-pay-badge is-danger">Inactive</span>'; ?>
                </span>
              </li>
              <li class="list-group-item">
                <b>Code</b>
                <span class="pull-right">
                  <?= $details->code; ?>
                </span>
              </li>
            </ul>
          </div>
        </div>

        <div class="box box-warning">
          <div class="box-header with-border">
            <h3 class="box-title">Package Info</h3>
          </div>

          <div class="box-body">

            <ul class="list-group list-group-unbordered">


              

              <li class="list-group-item">
                <b>Last Renewed</b>
                <span class="pull-right">
                  <?= date('d M Y, h:i a', strtotime($details->last_renewed)); ?>
                </span>
              </li>

              <li class="list-group-item">
                <b>Expire Date</b>
                <span class="pull-right">
                <?= !empty($details->will_expire) ? date('d M Y, h:i a', strtotime($details->will_expire)) : 'N/A'; ?>

                </span>
              </li>
            </ul>
          </div>
        </div>

      </div>

    </div>
  </section>
  <!-- /.content -->
</div>

<?= $this->endSection('content'); ?>

<?= $this->section('script'); ?>

<?php if (userHasPermission('customer', 'update_conn')): ?>

  <script>

    //Function for enable disable connection
    $(document).on('change', 'input[name="conn_status"]', function () {

      let status = $(this).data('status');
      let user = '<?= $details->id; ?>';

      status = status === 'active' ? 'inactive' : 'active';

      $.ajax({
        url: '<?= route_to("route.customer.update_conn_status"); ?>',
        type: 'POST',
        data: {
          status,
          user
        },
        headers: {
          '<?= csrf_header() ?>': '<?= csrf_hash() ?>',
        },
        beforeSend: function () { },
        success: function (result) {
          tata.success('Connection status updated', result.response, {
            onClose: () => {
              location.reload();
            },
          });
        },
        error: function (response) {
          const result = jQuery.parseJSON(response.responseText);
          tata.error("Couldn't update connection status", result.response, {
            onClose: () => {
              location.reload();
            },
          });
        }
      });
    });
  </script>
<?php endif; ?>

<?= $this->endSection('script'); ?>