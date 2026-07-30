<?= $this->extend('layout/main-layout'); ?>

<?= $this->section('css'); ?>

<style>
  #loader {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.72);
    z-index: var(--z-overlay, 1095);
    transition: all 0.3s ease-in;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
  }

  #loader.d-none {
    display: none;
  }

  .method-logo {
    max-width: 120px;
    width: 100%;
    margin: auto;
  }

  .info {
    margin: 15px 0;
    color: #fff !important;
  }

  .bg-bkash {
    background: #d62267;
    color: #fff !important;
  }

  .border-bkash {
    border-color: #d62267;
  }

  .color-bkash {
    color: #d62267;
  }

  .bg-nagad {
    background: #c90008;
    color: #fff !important;
  }

  .border-nagad {
    border-color: #c90008;
  }

  .color-nagad {
    color: #c90008;
  }

  .bg-sslcommerz {
    background: #245cad;
    color: #fff !important;
  }

  .border-sslcommerz {
    border-color: #245cad;
  }

  .color-sslcommerz {
    color: #245cad;
  }

  .bg-eps {
    background: #12a19a;
    color: #fff !important;
  }

  .border-eps {
    border-color: #12a19a;
  }

  .color-eps {
    color: #12a19a;
  }

  .bg-shurjopay {
    background: #ee6123;
    color: #fff !important;
  }

  .border-shurjopay {
    border-color: #ee6123;
  }

  .color-shurjopay {
    color: #ee6123;
  }

  .bg-paystation {
    background: #2d3e91;
    color: #fff !important;
  }

  .border-paystation {
    border-color: #2d3e91;
  }

  .color-paystation {
    color: #2d3e91;
  }
</style>
<?= $this->endSection('css'); ?>

<?= $this->section('content'); ?>

<div id="loader" class="d-none">
  <i class="fa-solid fa-circle-notch fa-spin fa-2xl" id="loader-icon"></i>
</div>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper <?= (isset($isPublic) && $isPublic) ? 'm-0' : '' ?>">
  <!-- Main content -->
  <section class="content ipb-saas-list">

    
    <?= $this->include('components/page-header', [
      'title' => 'Payment Gateway',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'My Payment'],
        ['label' => 'Payment Gateway'],
      ],
    ]); ?>
<div class="row">

      <?php 
        $bkashEnabled = getSetting('enable_bkashpg', 'no', $userIdContext ?? null) == 'yes';
        $bkashConfigured = !empty(getSetting('bkashpg_app_key', '', $userIdContext ?? null));
        if ($bkashEnabled && $bkashConfigured) : 
      ?>

        <div class="col-lg-4 col-md-6">
          <div class="box border-bkash">
            <div class="box-header without-border text-center">
              <img src="<?= base_url('assets/img/methods/bkash.png'); ?>" class="method-logo">
            </div>
            <div class="box-body bg-bkash">

              <div class="table-responsive">
              <table class="table table-bordered">
                <caption class="sr-only">bKash payment breakdown</caption>
                <tr>
                  <th scope="row">Amount</th>
                  <td><?= $details->amount; ?>৳</td>
                </tr>
                <tr>
                  <th scope="row">Charge</th>
                  <td><?= $bkash_charge; ?>৳ (<?= getSetting('bkashpg_charge', 0, $userIdContext ?? null); ?>%)</td>
                </tr>
                <tr>
                  <th scope="row">Total</th>
                  <td><?= round((floatval($details->amount) + $bkash_charge), 2); ?>৳</td>

                </tr>
              </table>
              </div>

              <p class="text-center info">
                Click the button below to pay the amount <?= round((floatval($details->amount) + $bkash_charge), 2); ?>৳ using bKash
              </p>
            </div>
            <div class="box-footer">
              <button class="btn bg-bkash btn-block" id="bKash_button">Pay With bKash</button>
            </div>
          </div>
        </div>

      <?php endif; ?>

      <?php 
        $nagadEnabled = getSetting('enable_nagadpg', 'no', $userIdContext ?? null) == 'yes';
        $nagadConfigured = !empty(getSetting('nagadpg_merchant_id', '', $userIdContext ?? null));
        if ($nagadEnabled && $nagadConfigured) : 
      ?>

        <div class="col-lg-4 col-md-6">
          <div class="box border-nagad">
            <div class="box-header with-border text-center">
              <img src="<?= base_url('assets/img/methods/nagad.png'); ?>" class="method-logo">
            </div>
            <div class="box-body bg-nagad">

              <div class="table-responsive">
              <table class="table table-bordered">
                <caption class="sr-only">Nagad payment breakdown</caption>
                <tr>
                  <th scope="row">Amount</th>
                  <td><?= $details->amount; ?>৳</td>
                </tr>
                <tr>
                  <th scope="row">Charge</th>
                  <td><?= $nagad_charge; ?>৳ (<?= getSetting('nagadpg_charge', 0, $userIdContext ?? null); ?>%)</td>
                </tr>
                <tr>
                  <th scope="row">Total</th>
                  <td><?= round((floatval($details->amount) + $nagad_charge), 2); ?>৳</td>
                </tr>
              </table>
              </div>

              <p class="text-center info">
                Click the button below to pay the amount <?= round((floatval($details->amount) + $nagad_charge), 2); ?>৳ using Nagad
              </p>
            </div>
            <div class="box-footer">
              <button class="btn bg-nagad btn-block" id="nagad_btn">Pay With Nagad</button>
            </div>
          </div>
        </div>

      <?php endif; ?>

      <?php 
        $sslEnabled = getSetting('enable_sslcommerz', 'no', $userIdContext ?? null) == 'yes';
        $sslConfigured = !empty(getSetting('sslcommerz_store_id', '', $userIdContext ?? null));
        if ($sslEnabled && $sslConfigured) : 
      ?>

        <div class="col-lg-4 col-md-6">
          <div class="box border-sslcommerz">
            <div class="box-header with-border text-center">
              <img src="<?= base_url('assets/img/methods/sslcommerz.png'); ?>" class="method-logo">
            </div>
            <div class="box-body bg-sslcommerz">

              <div class="table-responsive">
              <table class="table table-bordered">
                <caption class="sr-only">SSLCommerz payment breakdown</caption>
                <tr>
                  <th scope="row">Amount</th>
                  <td><?= floatval($details->amount); ?>৳</td>
                </tr>
                <tr>
                  <th scope="row">Charge</th>
                  <td><?= $sslcommerz_charge; ?>৳ (<?= getSetting('sslcommerz_charge', 0, $userIdContext ?? null); ?>%)</td>
                </tr>
                <tr>
                  <th scope="row">Total</th>
                  <td><?= round((floatval($details->amount) + $sslcommerz_charge), 2); ?>৳</td>
                </tr>
              </table>
              </div>

              <p class="text-center info">
                Click the button below to pay the amount <?= round((floatval($details->amount) + $sslcommerz_charge), 2); ?>৳ using SSLCommerz
              </p>
            </div>
            <div class="box-footer">
              <button class="btn bg-sslcommerz btn-block" id="sslcommerz_btn">Pay With SSLCommerz</button>
            </div>
          </div>
        </div>

      <?php endif; ?>

      <?php
        $epsEnabled = getSetting('enable_eps', 'no', $userIdContext ?? null) == 'yes';
        $epsConfigured = !empty(getSetting('eps_merchant_id', '', $userIdContext ?? null));
        if ($epsEnabled && $epsConfigured) :
      ?>

        <div class="col-lg-4 col-md-6">
          <div class="box border-eps">
            <div class="box-header with-border text-center">
              <img src="<?= base_url('assets/img/methods/eps.svg'); ?>" class="method-logo">
            </div>
            <div class="box-body bg-eps">

              <div class="table-responsive">
              <table class="table table-bordered">
                <caption class="sr-only">EPS payment breakdown</caption>
                <tr>
                  <th scope="row">Amount</th>
                  <td><?= floatval($details->amount); ?>৳</td>
                </tr>
                <tr>
                  <th scope="row">Charge</th>
                  <td><?= $eps_charge; ?>৳ (<?= getSetting('eps_charge', 0, $userIdContext ?? null); ?>%)</td>
                </tr>
                <tr>
                  <th scope="row">Total</th>
                  <td><?= round((floatval($details->amount) + $eps_charge), 2); ?>৳</td>
                </tr>
              </table>
              </div>

              <p class="text-center info">
                Click the button below to pay the amount <?= round((floatval($details->amount) + $eps_charge), 2); ?>৳ using EPS
              </p>
            </div>
            <div class="box-footer">
              <button class="btn bg-eps btn-block" id="eps_btn">Pay With EPS</button>
            </div>
          </div>
        </div>

      <?php endif; ?>

      <?php
        $shurjopayEnabled = getSetting('enable_shurjopay', 'no', $userIdContext ?? null) == 'yes';
        $shurjopayConfigured = !empty(getSetting('shurjopay_username', '', $userIdContext ?? null));
        if ($shurjopayEnabled && $shurjopayConfigured) :
      ?>

        <div class="col-lg-4 col-md-6">
          <div class="box border-shurjopay">
            <div class="box-header with-border text-center">
              <img src="<?= base_url('assets/img/methods/shurjopay.svg'); ?>" class="method-logo">
            </div>
            <div class="box-body bg-shurjopay">

              <div class="table-responsive">
              <table class="table table-bordered">
                <caption class="sr-only">shurjoPay payment breakdown</caption>
                <tr>
                  <th scope="row">Amount</th>
                  <td><?= floatval($details->amount); ?>৳</td>
                </tr>
                <tr>
                  <th scope="row">Charge</th>
                  <td><?= $shurjopay_charge; ?>৳ (<?= getSetting('shurjopay_charge', 0, $userIdContext ?? null); ?>%)</td>
                </tr>
                <tr>
                  <th scope="row">Total</th>
                  <td><?= round((floatval($details->amount) + $shurjopay_charge), 2); ?>৳</td>
                </tr>
              </table>
              </div>

              <p class="text-center info">
                Click the button below to pay the amount <?= round((floatval($details->amount) + $shurjopay_charge), 2); ?>৳ using shurjoPay
              </p>
            </div>
            <div class="box-footer">
              <button class="btn bg-shurjopay btn-block" id="shurjopay_btn">Pay With shurjoPay</button>
            </div>
          </div>
        </div>

      <?php endif; ?>

      <?php
        $paystationEnabled = getSetting('enable_paystation', 'no', $userIdContext ?? null) == 'yes';
        $paystationConfigured = !empty(getSetting('paystation_merchant_id', '', $userIdContext ?? null));
        if ($paystationEnabled && $paystationConfigured) :
      ?>

        <div class="col-lg-4 col-md-6">
          <div class="box border-paystation">
            <div class="box-header with-border text-center">
              <img src="<?= base_url('assets/img/methods/paystation.svg'); ?>" class="method-logo">
            </div>
            <div class="box-body bg-paystation">

              <div class="table-responsive">
              <table class="table table-bordered">
                <caption class="sr-only">PayStation payment breakdown</caption>
                <tr>
                  <th scope="row">Amount</th>
                  <td><?= floatval($details->amount); ?>৳</td>
                </tr>
                <tr>
                  <th scope="row">Charge</th>
                  <td><?= $paystation_charge; ?>৳ (<?= getSetting('paystation_charge', 0, $userIdContext ?? null); ?>%)</td>
                </tr>
                <tr>
                  <th scope="row">Total</th>
                  <td><?= round((floatval($details->amount) + $paystation_charge), 2); ?>৳</td>
                </tr>
              </table>
              </div>

              <p class="text-center info">
                Click the button below to pay the amount <?= round((floatval($details->amount) + $paystation_charge), 2); ?>৳ using PayStation
              </p>
            </div>
            <div class="box-footer">
              <button class="btn bg-paystation btn-block" id="paystation_btn">Pay With PayStation</button>
            </div>
          </div>
        </div>

      <?php endif; ?>

    </div>
  </section>
  <!-- /.content -->
</div>

<?= $this->endSection('content'); ?>

<?= $this->section('script'); ?>

<?php if ($bkashEnabled && $bkashConfigured) : ?>
  <script>
    $(document).on('click', '#bKash_button', function(e) {

      $.ajax({
        url: '<?= route_to("route.payment.gateway.bkash.geturl"); ?>',
        type: 'POST',
        data: {
          payment_id: '<?= $details->id; ?>'
        },
        headers: {
          '<?= csrf_header() ?>': '<?= csrf_hash() ?>',
        },
        beforeSend: function() {
          $('#loader #loader-icon').addClass('color-bkash');
          $('#loader').removeClass('d-none');
        },
        success: function(result) {

          $('#loader #loader-icon').removeClass('color-bkash');
          $('#loader').addClass('d-none');

          location.href = result.response; //go to bkash payment page
        },
        error: function(response) {

          $('#loader #loader-icon').removeClass('color-bkash');

          $('#loader').addClass('d-none');

          const result = JSON.parse(response.responseText);

          tata.error("Couldn't start payment", result.response);
        }
      });

      e.preventDefault();
    });
  </script>
<?php endif; ?>

<?php if ($nagadEnabled && $nagadConfigured) : ?>
  <script>
    $('#nagad_btn').click(function() {

      $.ajax({
        url: '<?= route_to("route.payment.gateway.nagad.geturl"); ?>',
        type: 'POST',
        data: {
          payment_id: '<?= $details->id; ?>',
        },
        headers: {
          '<?= csrf_header() ?>': '<?= csrf_hash() ?>',
        },
        beforeSend: function() {

          $('#loader #loader-icon').addClass('color-nagad');
          $('#loader').removeClass('d-none');
        },
        success: function(result) {

          $('#loader #loader-icon').removeClass('color-nagad');
          $('#loader').addClass('d-none');

          if (typeof result.response === 'object') {

            tata.error("Couldn't start payment", result.response.message);

          } else {

            location.href = result.response;
          }
        },
        error: function(response) {

          $('#loader #loader-icon').removeClass('color-nagad');
          $('#loader').addClass('d-none');

          const result = JSON.parse(response.responseText);

          tata.error("Couldn't start payment", result.response);
        }
      });
    });
  </script>
<?php endif; ?>

<?php if ($sslEnabled && $sslConfigured) : ?>
  <script>
    $('#sslcommerz_btn').click(function() {

      $.ajax({
        url: '<?= route_to("route.payment.gateway.sslcommerz.geturl"); ?>',
        type: 'POST',
        data: {
          payment_id: '<?= $details->id; ?>',
        },
        headers: {
          '<?= csrf_header() ?>': '<?= csrf_hash() ?>',
        },
        beforeSend: function() {

          $('#loader #loader-icon').addClass('color-sslcommerz');
          $('#loader').removeClass('d-none');
        },
        success: function(result) {

          $('#loader #loader-icon').removeClass('color-sslcommerz');
          $('#loader').addClass('d-none');

          location.href = result.response;
        },
        error: function(response) {

          $('#loader #loader-icon').removeClass('color-sslcommerz');
          $('#loader').addClass('d-none');

          const result = JSON.parse(response.responseText);

          tata.error("Couldn't start payment", result.response);
        }
      });
    });
  </script>
<?php endif; ?>

<?php if ($epsEnabled && $epsConfigured) : ?>
  <script>
    $('#eps_btn').click(function() {

      $.ajax({
        url: '<?= route_to("route.payment.gateway.eps.geturl"); ?>',
        type: 'POST',
        data: {
          payment_id: '<?= $details->id; ?>',
        },
        headers: {
          '<?= csrf_header() ?>': '<?= csrf_hash() ?>',
        },
        beforeSend: function() {
          $('#loader #loader-icon').addClass('color-eps');
          $('#loader').removeClass('d-none');
        },
        success: function(result) {
          $('#loader #loader-icon').removeClass('color-eps');
          $('#loader').addClass('d-none');

          if (result.status === 'success') {
            location.href = result.response;
          } else {
            tata.error("Couldn't start payment", result.response);
          }
        },
        error: function(response) {
          $('#loader #loader-icon').removeClass('color-eps');
          $('#loader').addClass('d-none');

          const result = JSON.parse(response.responseText);
          tata.error("Couldn't start payment", result.response);
        }
      });
    });
  </script>
<?php endif; ?>

<?php if ($shurjopayEnabled && $shurjopayConfigured) : ?>
  <script>
    $('#shurjopay_btn').click(function() {

      $.ajax({
        url: '<?= route_to("route.payment.gateway.shurjopay.geturl"); ?>',
        type: 'POST',
        data: {
          payment_id: '<?= $details->id; ?>',
        },
        headers: {
          '<?= csrf_header() ?>': '<?= csrf_hash() ?>',
        },
        beforeSend: function() {
          $('#loader #loader-icon').addClass('color-shurjopay');
          $('#loader').removeClass('d-none');
        },
        success: function(result) {
          $('#loader #loader-icon').removeClass('color-shurjopay');
          $('#loader').addClass('d-none');

          if (result.status === 'success') {
            location.href = result.response;
          } else {
            tata.error("Couldn't start payment", result.response);
          }
        },
        error: function(response) {
          $('#loader #loader-icon').removeClass('color-shurjopay');
          $('#loader').addClass('d-none');

          const result = JSON.parse(response.responseText);
          tata.error("Couldn't start payment", result.response);
        }
      });
    });
  </script>
<?php endif; ?>

<?php if ($paystationEnabled && $paystationConfigured) : ?>
  <script>
    $('#paystation_btn').click(function() {

      $.ajax({
        url: '<?= route_to("route.payment.gateway.paystation.geturl"); ?>',
        type: 'POST',
        data: {
          payment_id: '<?= $details->id; ?>',
        },
        headers: {
          '<?= csrf_header() ?>': '<?= csrf_hash() ?>',
        },
        beforeSend: function() {
          $('#loader #loader-icon').addClass('color-paystation');
          $('#loader').removeClass('d-none');
        },
        success: function(result) {
          $('#loader #loader-icon').removeClass('color-paystation');
          $('#loader').addClass('d-none');

          if (result.status === 'success') {
            location.href = result.response;
          } else {
            tata.error("Couldn't start payment", result.response);
          }
        },
        error: function(response) {
          $('#loader #loader-icon').removeClass('color-paystation');
          $('#loader').addClass('d-none');

          const result = JSON.parse(response.responseText);
          tata.error("Couldn't start payment", result.response);
        }
      });
    });
  </script>
<?php endif; ?>

<?= $this->endSection('script'); ?>