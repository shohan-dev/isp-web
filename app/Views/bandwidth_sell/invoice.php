<?= $this->extend('layout/main-layout'); ?>

<?= $this->section('content'); ?>
<style>
  @media print { .table-responsive{overflow:visible} }
  .content-wrapper {
    background: #f4f4f4;
    padding: 30px;
  }
  .invoice-container {
    max-width: 850px;
    margin: auto;
    background: #fff;
    border-radius: 5px;
    box-shadow: 0 0 12px rgba(0,0,0,0.1);
    padding: 25px 35px;
    overflow-x: auto;
  }
  .invoice-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    flex-wrap: wrap;
    gap: 12px;
  }
  @media (max-width: 767px) {
    .content-wrapper { padding: 12px !important; }
    .invoice-container { padding: 16px; border-radius: 10px; }
    .invoice-header {
      flex-direction: column;
      align-items: stretch;
    }
    .invoice-header .btn {
      margin-left: 0 !important;
      width: 100%;
      min-height: 44px;
    }
    .invoice-logo { height: 44px; }
    .invoice-summary { text-align: left; }
  }
  .invoice-header i {
    color: #0d6efd;
    margin-right: 10px;
  }
  .invoice-header h4 {
    margin: 0;
  }
  .invoice-header .btn {
    margin-left: 10px;
  }
  .invoice-logo {
    height: 60px;
  }
  .invoice-box hr {
    border-top: 1px solid #000;
    margin: 20px 0;
  }
  .table th {
    background-color: #0c2d48;
    color: #fff;
    text-align: center;
    vertical-align: middle;
  }
  .table td {
    text-align: center;
    vertical-align: middle;
  }
  .table .text-start {
    text-align: left !important;
  }
  .invoice-summary {
    text-align: right;
    margin-top: 20px;
  }
  .invoice-summary p {
    margin: 0;
  }
  .fw-bold {
    font-weight: bold;
  }
  .table-sm td, .table-sm th {
    padding: .3rem;
  }
</style>

<?php
// Get the requisition ID from URL parameter using service('request')
$request = service('request');
$requisitionId = $request->getGet('id') ?? '';
$requisition = null;
$invoiceItems = [];

// Find the specific requisition
foreach ($requisitions as $req) {
    if ($req['requisition_id'] == $requisitionId) {
        $requisition = $req;
        break;
    }
}

// If no requisition found, use the first one or show error
if (!$requisition && !empty($requisitions)) {
    $requisition = $requisitions[0];
    $requisitionId = $requisition['requisition_id'];
}

// Get all items for this requisition from the passed data
if ($requisition && isset($allInvoiceItems)) {
    foreach ($allInvoiceItems as $item) {
        if ($item['requisition_id'] == $requisitionId) {
            $invoiceItems[] = $item;
        }
    }
}
?>

<div class="content-wrapper">
  <div class="invoice-container">
    <!-- Header -->
    <div class="invoice-header">
      <div class="d-flex align-items-center">
        <i class="fa fa-paper-plane fa-2x"></i>
        <h4 class="mb-0">Send Invoice</h4>
      </div>
      <div>
        <button class="btn btn-primary" onclick="window.print()">Download PDF</button>
        <button class="btn btn-secondary" onclick="sendEmail()">Send to Email</button>
        <button class="btn btn-info text-white" onclick="sendSMS()">Send SMS</button>
      </div>
    </div>

    <?php if ($requisition): ?>
    <!-- Invoice Box -->
    <div class="invoice-box">
      <div class="d-flex justify-content-between align-items-start mb-3">
        <img src="/assets/logo.png" alt="ISP Logo" class="invoice-logo">
        <div class="text-end">
          <strong>INVOICE</strong><br>
          ISP Digital Demo<br>
          h<br>
          Mobile Number: 01925631826<br>
          Email: isppaybd@gmail.com
        </div>
      </div>

      <hr>

      <div class="row mb-4">
        <div class="col-md-6">
          <strong>BILL TO</strong><br>
          <strong><?= esc($requisition['vendor_suggestion'] ?? 'N/A') ?></strong><br>
          Mobile Number: <?= esc($requisition['vendor_mobile'] ?? 'N/A') ?><br>
          Emails: <?= esc($requisition['vendor_email'] ?? 'N/A') ?><br>
          Address: <?= esc($requisition['vendor_address'] ?? 'N/A') ?>
        </div>
        <div class="col-md-6">
          <div class="table-responsive">
          <table class="table table-sm">
            <tr><td class="text-start">Invoice No</td><td class="text-end"><?= esc($requisition['requisition_id'] ?? 'N/A') ?></td></tr>
            <tr><td class="text-start">Invoice of Month</td><td class="text-end"><?= date('M-Y', strtotime($requisition['requisition_date'] ?? date('Y-m-d'))) ?></td></tr>
            <tr><td class="text-start">Invoice Date</td><td class="text-end"><?= date('d M Y', strtotime($requisition['requisition_date'] ?? date('Y-m-d'))) ?></td></tr>
            <tr><td class="text-start">Payment Due</td><td class="text-end"><?= date('d M Y', strtotime($requisition['deadline'] ?? date('Y-m-d'))) ?></td></tr>
            <tr><td class="text-start">Amount Due(BDT)</td><td class="text-end fw-bold"><?= number_format($requisition['due'] ?? 0, 2) ?></td></tr>
          </table>
          </div>
        </div>
      </div>

      <!-- Invoice Table -->
      <div class="table-responsive">
      <table class="table table-bordered">
        <caption class="sr-only">Invoice items</caption>
        <thead>
          <tr>
            <th scope="col">Item</th>
            <th scope="col">Quantity</th>
            <th scope="col">Price</th>
            <th scope="col">VAT(%)</th>
            <th scope="col">Amount</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($invoiceItems)): ?>
            <?php foreach ($invoiceItems as $item): ?>
            <tr>
              <td class="text-start">
                <strong><?= esc($item['item_name'] ?? 'N/A') ?></strong><br>
                <small><?= esc($item['description'] ?? 'Description(Optional)') ?></small>
              </td>
              <td><?= esc($item['quantity'] ?? 1) ?></td>
              <td><?= number_format($item['rate'] ?? 0, 2) ?></td>
              <td><?= number_format($item['vat'] ?? 0, 2) ?></td>
              <td><?= number_format($item['total'] ?? 0, 2) ?></td>
            </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="5" class="text-center">No items found for this invoice</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
      </div>

      <!-- Summary -->
      <div class="invoice-summary">
        <p>Total Amount: <?= number_format($requisition['total_amount'] ?? 0, 2) ?></p>
        <p>Paid Amount: <?= number_format($requisition['received_amount'] ?? 0, 2) ?></p>
        <p class="fw-bold">Amount Due(BDT): <?= number_format($requisition['due'] ?? 0, 2) ?></p>
      </div>

      <hr>

      <!-- Transactions Table -->
      <h6>Transactions</h6>
      <div class="table-responsive">
      <table class="table table-bordered">
        <caption class="sr-only">Invoice transactions</caption>
        <thead>
          <tr>
            <th scope="col">Date</th>
            <th scope="col">Payment Method</th>
            <th scope="col">Description</th>
            <th scope="col">Amount</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($requisition['payment_date'])): ?>
            <tr>
              <td><?= date('d M Y', strtotime($requisition['payment_date'])) ?></td>
              <td><?= esc($requisition['payment_method'] ?? 'N/A') ?></td>
              <td><?= esc($requisition['remarks'] ?? 'Payment received') ?></td>
              <td><?= number_format($requisition['received_amount'] ?? 0, 2) ?></td>
            </tr>
          <?php else: ?>
            <tr>
              <td colspan="4" class="text-center">No Related Transaction Found</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
      </div>

      <p><strong>Remarks/Note :</strong> <?= esc($requisition['remarks'] ?? 'N/A') ?></p>
    </div>
    <?php else: ?>
    <div class="alert alert-warning">
      No invoice found. Please select a valid invoice.
    </div>
    <?php endif; ?>
  </div>
</div>

<script>
function sendEmail() {
    // Add email sending logic here
    alert('Email sending functionality to be implemented');
}

function sendSMS() {
    // Add SMS sending logic here
    alert('SMS sending functionality to be implemented');
}

// Add print styling
window.onbeforeprint = function() {
    // Hide all header buttons when printing (querySelector only matched
    // the first one, so "Send Email/SMS" was baked into every printed PDF)
    document.querySelectorAll('.invoice-header .btn').forEach(function(btn) {
        btn.style.display = 'none';
    });
};

window.onafterprint = function() {
    // Show buttons after printing
    document.querySelectorAll('.invoice-header .btn').forEach(function(btn) {
        btn.style.display = 'inline-block';
    });
};
</script>

<?= $this->endSection(); ?>