<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: 'hindsiliguri', 'Courier New', Courier, monospace;
            margin: 0;
            padding: 0;
            font-size: 12px;
            color: #000;
        }

        .receipt {
            width: 100%;
            max-width: 58mm;
            margin: 0 auto;
            padding: 2mm;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .header {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 1mm;
            text-transform: uppercase;
        }

        .subheader {
            font-size: 10px;
            margin-bottom: 2mm;
            line-height: 1.2;
        }

        .divider {
            border-top: 1px dashed #000;
            margin: 2mm 0;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin: 2mm 0;
        }

        .details-table td {
            padding: 1mm 0;
            vertical-align: top;
        }

        .label {
            font-weight: bold;
            width: 35%;
        }

        .footer {
            font-size: 10px;
            margin-top: 4mm;
            line-height: 1.4;
        }

        .invoice-id {
            font-weight: bold;
            margin: 2mm 0;
        }

        .amount-section {
            font-size: 14px;
            font-weight: bold;
            margin-top: 2mm;
        }
    </style>
    <title>Receipt</title>
</head>

<body>
    <div class="receipt">
        <div class="text-center header">
            <?= $companyName; ?>
        </div>
        <div class="text-center subheader">
            <?= $companyAddress; ?><br>
            Tel: <?= $companyMobile; ?>
        </div>

        <div class="divider"></div>

        <div class="text-center invoice-id">
            INVOICE: <?= $details->invoice; ?>
        </div>

        <div class="divider"></div>

        <table class="details-table">
            <tr>
                <td class="label">Date:</td>
                <td><?= date("d-m-Y H:i", strtotime($details->paid_at ?? $details->created_at)); ?></td>
            </tr>
            <tr>
                <td class="label">Customer:</td>
                <td><?= $customerName; ?></td>
            </tr>
            <?php if (!empty($user->pppoe_id)): ?>
            <tr>
                <td class="label">User ID:</td>
                <td><?= $user->pppoe_id; ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <td class="label">Month:</td>
                <td><?= $details->month; ?></td>
            </tr>
            <tr>
                <td class="label">Method:</td>
                <td><?= ucwords($details->paid_via); ?></td>
            </tr>
        </table>

        <div class="divider"></div>

        <table width="100%" class="amount-section">
            <tr>
                <td>TOTAL PAID:</td>
                <td class="text-right"><?= number_format($details->pay_amount, 2); ?> ৳</td>
            </tr>
        </table>

        <div class="divider"></div>

        <div class="text-center footer">
            Thank you for your payment!<br>
            Software by ISPPAYBD.COM
        </div>
    </div>
</body>

</html>