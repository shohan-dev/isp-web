<?php
// Assuming $details is an instance of a specific class, e.g., ResellerDetails
/** @var \App\Entities\ResellerDetails $details */

// If $details is a standard class object without a specific class, you can use:
/** @var object $details */
/** @var object $rdetails */
?>

<?= $this->extend('layout/main-layout'); ?>

<?= $this->section('css'); ?>


<style>
  .list-group-item-heading {
    font-size: 1.6rem;
    font-weight: bold;
  }

  .list-group-item-text {
    font-size: 1.2rem;
  }
</style>

<?= $this->endSection('css'); ?>

<?= $this->section('content'); ?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <!-- Main content -->
  <section class="content ipb-saas-list">

    
    <?= $this->include('components/page-header', [
      'title' => 'My Subscription',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'My Subscription'],
      ],
    ]); ?>
<div class="box box-warning">
      <div class="box-header with-border ipb-box-toolbar">
        <div class="ipb-list-toolbar">
          <div class="ipb-list-toolbar-filters">
            <span class="ipb-filter-label"><i class="fa fa-pen" aria-hidden="true"></i> Details</span>
          </div>
        </div>
      </div>

      <div class="box-body">

        <div class="row">

          <div class="col-md-7 col-lg-6">

            <div class="box-body">

              <div class="list-group">

                <span class="list-group-item">
                  <h4 class="list-group-item-heading">
                    Current Package :
                    <?php
                    $package = getUserPackage($details->id);
                    if (is_object($package)) {
                      echo $package->package_name ?? '--';
                    } elseif (is_array($package)) {
                      echo $package['package_name'] ?? '--';
                    } else {
                      echo '--';
                    }
                    ?>
                  </h4>
                  <p class="list-group-item-text">
                    <?php
                    if (is_object($package)) {
                      echo $package->bandwidth ?? '--';
                    } elseif (is_array($package)) {
                      echo $package['bandwidth'] ?? '--';
                    } else {
                      echo '--';
                    }
                    ?>
                  </p>
                </span>


                <span class="list-group-item">
                  <h4 class="list-group-item-heading">
                    Pricing :
                    <?php
                    $package = getUserPackage($details->id);
                    if ($package) {
                      if (is_array($package)) {
                        // Handle as an array
                        $price = $package['price'] ?? '--'; // Default value if not set
                        $pricingType = $package['pricing_type'] ?? '--'; // Default value if not set
                        echo $price . '৳ - ' . ucwords($pricingType);
                      } else {
                        // Handle as an object
                        $price = $package->price ?? '--'; // Default value if not set
                        $pricingType = $package->pricing_type ?? '--'; // Default value if not set
                        echo $price . '৳ - ' . ucwords($pricingType);
                      }
                    } else {
                      echo '--';
                    }
                    ?>
                  </h4>
                  <p class="list-group-item-text text-primary">
                    Pricing are subjects to change
                  </p>
                </span>


                <span class="list-group-item">
                  <h4 class="list-group-item-heading">Last Renewed</h4>
                  <p class="list-group-item-text">
                    <?= date('d.m.Y, h:i a', strtotime($details->last_renewed)); ?>
                  </p>
                </span>

                <span class="list-group-item" style="overflow: auto">
                  <h4 class="list-group-item-heading">Expire Date :
                    <?= date('d.m.Y, h:i a', strtotime($details->will_expire)); ?></h4>

                  <?php if (userHasPermission('subscription', 'renew') && getSession('user_role') === 'resellerAdmin'): ?>
                    <button class="pull-right btn btn-warning btn-sm" id="renew-btn" style="margin-left: 5px;">
                      <i class="fa fa-repeat"></i> Renew
                    </button>
                  <?php endif; ?>

                  <p class="list-group-item-text text-primary">
                    We'll send you notification before subscription expires
                  </p>
                </span>

              </div>

            </div>


          </div>

          <div class="col-md-5 col-lg-6">

            <div class="box-body">

              <?php

              $currentDate = strtotime(date("Y-m-d H:i:s"));

              $renewDate = strtotime($details->last_renewed);

              $expiredDate = strtotime($details->will_expire);

              $dateDifference = $expiredDate - $renewDate;

              $daysUsed = $currentDate - $renewDate;

              $usedPecentage = ($dateDifference > 0) ? round((($daysUsed / $dateDifference) * 100), 1) : 0;

              $usedPecentage = $usedPecentage < 100 ? $usedPecentage : 100;
              ?>

              <?php if ($usedPecentage < 50): ?>

                <div class='alert alert-success' style="display: flex; align-items: center;">
                  <i class='far fa-circle-check' style="margin-right: 15px; font-size: 20px;"></i>
                  <span>Your subscription is up to date</span>
                </div>

              <?php elseif ($usedPecentage > 50 && $usedPecentage <= 85): ?>

                <div class='alert alert-warning' style="display: flex; align-items: center;">
                  <i class='fa-solid fa-circle-exclamation' style="margin-right: 15px; font-size: 20px;"></i>
                  <span>Your subscription need to be renewed</span>
                </div>

              <?php elseif ($usedPecentage > 85 && $usedPecentage < 100): ?>

                <div class='alert alert-warning' style="display: flex; align-items: center;">
                  <i class='fa-solid fa-circle-exclamation' style="margin-right: 15px; font-size: 20px;"></i>
                  <span>Renew your subscription or your internet connection will be disconnected</span>
                </div>

              <?php else: ?>

                <div class='alert alert-danger' style="display: flex; align-items: center;">
                  <i class='fa fa-triangle-exclamation' style="margin-right: 15px; font-size: 20px;"></i>
                  <span>Your subscription has expired! Your internet connection has been disconnected. Renew your
                    subscription or contact us to restore your internet connection</span>
                </div>

              <?php endif; ?>

              <div class="progress progress-striped active">
                <div class="progress-bar progress-bar-info" role="progressbar"
                  aria-valuenow="<?= (100 - $usedPecentage); ?>" aria-valuemin="0" aria-valuemax="100"
                  style="width: <?= (100 - $usedPecentage); ?>%">
                  <span><?= (100 - $usedPecentage); ?>% Remains</span>
                </div>
              </div>

              <?php if ($usedPecentage < 100): ?>

                <p class="text-start mt-2">You have <?= round(($expiredDate - $currentDate) / (60 * 60 * 24)); ?> days
                  left until your subscription expires</p>

              <?php endif; ?>

            </div>

          </div>

        </div>

      </div>
    </div>
  </section>
  <!-- /.content -->
</div>

<?= $this->endSection('content'); ?>

<?= $this->section('script'); ?>

<?php if (!empty(session()->getFlashdata('pay-success'))): ?>

  <script>
    tata.success('Payment recorded', '<?= session()->getFlashdata('pay-success'); ?>', {
      duration: 3000,
    });
  </script>

<?php elseif (!empty(session()->getFlashdata('pay-error'))): ?>

  <script>
    tata.error('Payment failed', '<?= session()->getFlashdata('pay-success'); ?>', {
      duration: 3000,
    });
  </script>

<?php endif; ?>

<?php if (userHasPermission('subscription', 'renew')): ?>
  <script>
    $("#renew-btn").click(function (e) {

      const self = this;

      $.ajax({
        url: '<?= route_to('route.resellersubscription.renew'); ?>',
        type: 'POST',
        data: {
          customer: '<?= $details->id; ?>'
        },
        headers: {
          '<?= csrf_header() ?>': '<?= csrf_hash() ?>',
        },

        beforeSend: function () {

          $(self).html("<i class='fas fa-spinner fa-spin'></i> Please wait");

          $(self).attr('disabled', 'true');
        },

        success: function (result) {

          $(self).html('<i class="fa fa-repeat"></i> Renew');

          $(self).removeAttr('disabled');

          swal({
            closeOnClickOutside: false,
            closeOnEsc: false,
            icon: 'success',
            title: "Success",
            text: result.response.msg,
            buttons: [
              "Close",
              {
                text: "Pay",
                closeModal: false,
              }
            ],
          }).then((willPay) => {

            if (willPay) {

              location.href = result.response.payment_url;
            }
          });
        },

        error: function ({
          responseText
        }) {

          const result = JSON.parse(responseText);

          $(self).html('<i class="fa fa-repeat"></i> Renew');

          $(self).removeAttr('disabled');

          tata.error("Couldn't renew subscription", result.response);

        }
      });

      e.preventDefault();
    });
  </script>
<?php endif; ?>

<?= $this->endSection('script'); ?>