<?= $this->extend('layout/main-layout'); ?>


<?= $this->section('content'); ?>

<div class="content-wrapper">
    <section class="content ipb-saas-list">
        
    <?= $this->include('components/page-header', [
      'title' => 'POP Funding',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'POP'],
        ['label' => 'POP Funding'],
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

            <?= form_open(route_to('route.Reseller.Funding.save'), 'id="form"'); ?>

            <input type="hidden" name="id" value="<?= isset($payment) ? $payment['id'] : '' ?>">
            <div class="box-body">
                <div class="form-group">
                    <label>POPs</label>
                    <?php
                    $options = ['' => '--Select--'];
                    foreach ($customers as $customer) {
                        $options[$customer->id] = $customer->name;
                    }
                    echo form_dropdown('customer', $options, isset($payment) ? $payment['customer'] : '', 'class="form-control"');
                    ?>
                    <small id="customer-error" class="error text-danger"></small>
                </div>

                <div class="row">
                    <div class="col-xs-6">

                        <label>Amount (৳)</label>
                        <?php
                        $attributes = [
                            'type'  => 'number',
                            'name'  => 'amount',
                            'id'    => 'amount',
                            'class' => 'form-control',
                            'value' => isset($payment) ? $payment['amount'] : '',
                            'min'   => '0'
                        ];

                        if (getSession('user_role') === 'resellerAdmin') {
                            $attributes['readonly'] = 'readonly'; // not disabled
                        }
                        ?>

                        <?= form_input($attributes); ?>
                        <small id="amount-error" class="error text-danger"></small>

                    </div>
                    <div class="col-xs-6">
                        <div class="form-group">
                            <label>
                                <?= (getSession('user_role') === 'resellerAdmin') ? 'Send Amount (৳)' : 'Received Amount (৳)' ?>
                            </label>
                            <?= form_input([
                                'type'  => 'number',
                                'name'  => 'received_amount',
                                'id'    => 'received_amount',
                                'class' => 'form-control',
                                'value' => isset($payment) ? $payment['received_amount'] : '',
                                'min'   => '0'
                            ]); ?>
                            <small id="received_amount-error" class="error text-danger"></small>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-xs-6">
                        <div class="form-group">
                            <label>Invoice Number</label>
                            <?= form_input(['type' => 'text', 'name' => 'invoice_number', 'class' => 'form-control', 'value' => isset($payment) ? $payment['invoice_number'] : '']); ?>
                            <small id="invoice_number-error" class="error text-danger"></small>
                        </div>
                    </div>
                    <div class="col-xs-6">
                        <div class="form-group">
                            <label>Received Date</label>
                            <?= form_input(['type' => 'date', 'name' => 'received_date', 'class' => 'form-control', 'value' => isset($payment) ? $payment['received_date'] : '']); ?>
                            <small id="received_date-error" class="error text-danger"></small>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Payment Method</label>

                    <?php
                    $options = [
                        '' => '--Select--',
                        'Cash' => 'Cash Payment',
                        'Bkash' => 'Bkash',
                        'Nagad' => 'Nagad',
                        'Rocket' => 'Rocket',
                        'Upay' => 'Upay',
                        'SSLCommerz' => 'SSLCommerz',
                    ];

                    // Render the dropdown using form_dropdown
                    echo form_dropdown('paid_via', $options, isset($payment) ? $payment['paid_via'] : '', 'class="form-control"');
                    ?>

                    <small id="paid_via-error" class="error text-danger"></small>
                </div>

                <div class="form-group">
                    <label>Comments</label>
                    <?= form_textarea(['name' => 'comments', 'class' => 'form-control', 'value' => isset($payment) ? $payment['comments'] : '']); ?>
                    <small id="comments-error" class="error text-danger"></small>
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <div class="radio">
                        <label class="radio-inline">
                            <?= form_radio(['name' => 'status', 'value' => 'successful', 'checked' => isset($payment) && $payment['status'] == 'successful']); ?>
                            Successful
                        </label>
                        <label class="radio-inline">
                            <?= form_radio(['name' => 'status', 'value' => 'pending', 'checked' => !isset($payment) || $payment['status'] == 'pending']); ?>
                            Pending
                        </label>
                    </div>
                    <small id="status-error" class="error text-danger"></small>
                </div>
            </div>

            <div class="box-body">
                <?= form_button(["content" => isset($payment) ? "Update Payment" : "Add Payment", "class" => "btn btn-warning", "type" => "submit"]); ?>
            </div>
            <?= form_close(); ?>
        </div>
    </section>
</div>

<?= $this->endSection('content'); ?>
<?= $this->section('script'); ?>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const amountInput = document.getElementById("amount");
        const receivedAmountInput = document.getElementById("received_amount");
        const errorText = document.getElementById("received_amount-error");

        function validateReceivedAmount() {
            const amount = parseFloat(amountInput.value) || 0;
            const receivedAmount = parseFloat(receivedAmountInput.value) || 0;

            if (receivedAmount > amount) {
                errorText.textContent = "Received amount cannot be more than the total amount.";
                receivedAmountInput.value = amount; // Reset to max allowed value
            } else {
                errorText.textContent = ""; // Clear error if valid
            }
        }

        // Validate on input change
        receivedAmountInput.addEventListener("input", validateReceivedAmount);
    });
</script>
<?= $this->endSection('script'); ?>