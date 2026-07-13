<?php
// Fallbacks for variables that might not be passed from all controllers
$companyName = $companyName ?? getSetting('app_name', 'ISP', $details->admin_id ?? null);
$companyMobile = $companyMobile ?? getSetting('company_mobile', '', $details->admin_id ?? null);
$companyAddress = $companyAddress ?? getSetting('company_address', '', $details->admin_id ?? null);

$customerName = $customerName ?? ($user->name ?? '--');
$customerMobile = $customerMobile ?? ($user->mobile ?? '--');
$customerEmail = $customerEmail ?? ($user->email ?? '--');
$customerAddress = $customerAddress ?? ($user->address ?? '--');
?>
<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>PAYMENT INVOICE</title>
    <style type="text/css">
        @page {
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'hindsiliguri', 'Helvetica', 'Arial', sans-serif;
            color: #333;
            margin: 0;
            padding: 0;
            overflow-x: clip;
        }

        .invoice-container {
            padding: 40px;
            max-width: 100%;
            box-sizing: border-box;
        }

        .header {
            background-color: #f8f9fa;
            padding: 30px 40px;
            border-bottom: 5px solid #2c3e50;
            box-sizing: border-box;
        }

        .header table,
        .billing-info,
        .invoice-details-table {
            width: 100%;
            max-width: 100%;
        }

        .company-name {
            color: #2c3e50;
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 5px;
            word-break: break-word;
        }

        .company-details {
            font-size: 13px;
            color: #7f8c8d;
            line-height: 1.4;
            word-break: break-word;
        }

        .invoice-title {
            text-align: right;
            color: #2c3e50;
            font-size: 32px;
            font-weight: 300;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .billing-info {
            margin-top: 40px;
            width: 100%;
        }

        .info-box {
            vertical-align: top;
        }

        .info-title {
            font-size: 14px;
            font-weight: bold;
            color: #2c3e50;
            text-transform: uppercase;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }

        .info-text {
            font-size: 13px;
            line-height: 1.6;
        }

        .invoice-details-table {
            width: 100%;
            margin-top: 50px;
            border-collapse: collapse;
        }

        .invoice-details-table th {
            background-color: #2c3e50;
            color: #ffffff;
            padding: 12px 15px;
            text-align: left;
            font-size: 14px;
            text-transform: uppercase;
        }

        .invoice-details-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #f1f1f1;
            font-size: 14px;
        }

        .invoice-details-table tr:nth-child(even) {
            background-color: #fcfcfc;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-successful {
            background-color: #e8f5e9;
            color: #2e7d32;
        }

        .status-pending {
            background-color: #fff3e0;
            color: #ef6c00;
        }

        .footer {
            margin-top: 100px;
            text-align: center;
            border-top: 1px solid #eee;
            padding-top: 20px;
            font-size: 12px;
            color: #95a5a6;
        }

        .total-section {
            margin-top: 30px;
            text-align: right;
        }

        .total-box {
            display: inline-block;
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            min-width: 200px;
        }

        .total-label {
            font-size: 14px;
            color: #7f8c8d;
        }

        .total-amount {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            margin-top: 5px;
        }

        @media screen and (max-width: 767px) {
            .header,
            .invoice-container {
                padding: 16px !important;
            }
            .header table,
            .billing-info {
                display: block;
            }
            .header table tr,
            .billing-info tr {
                display: flex;
                flex-direction: column;
                gap: 12px;
            }
            .header table td,
            .billing-info td {
                display: block;
                width: 100% !important;
                text-align: left !important;
            }
            .invoice-title {
                text-align: left;
                font-size: 22px;
            }
            .company-name {
                font-size: 20px;
            }
            .invoice-details-table {
                display: block;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            .total-section {
                text-align: left;
            }
            .total-box {
                display: block;
                width: 100%;
                box-sizing: border-box;
            }
            .footer {
                margin-top: 40px;
                padding: 16px;
            }
        }
    </style>
</head>

<body>
    <div class="header">
        <table width="100%">
            <tr>
                <td width="60%">
                    <div class="company-name"><?= $companyName; ?></div>
                    <div class="company-details">
                        <?= $companyAddress; ?><br>
                        Phone: <?= $companyMobile; ?>
                    </div>
                </td>
                <td width="40%" align="right">
                    <div class="invoice-title">Invoice</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="invoice-container">
        <table class="billing-info">
            <tr>
                <td class="info-box" width="50%">
                    <div class="info-title">Bill To</div>
                    <div class="info-text">
                        <strong><?= $customerName; ?></strong><br>
                        <?= $customerAddress; ?><br>
                        Phone: <?= $customerMobile; ?><br>
                        Email: <?= $customerEmail; ?>
                    </div>
                </td>
                <td class="info-box" width="50%" align="right">
                    <div class="info-title">Invoice Details</div>
                    <div class="info-text">
                        Invoice ID: <strong><?= $details->invoice ?? '--'; ?></strong><br>
                        Issue Date: <?= date("d M, Y", strtotime($details->created_at)); ?><br>
                        Status: <span class="status-badge <?= ($details->status === 'successful') ? 'status-successful' : 'status-pending' ?>">
                            <?= ($details->status === 'successful') ? 'Successful' : 'Pending' ?>
                        </span>
                    </div>
                </td>
            </tr>
        </table>

        <table class="invoice-details-table">
            <thead>
                <tr>
                    <th width="60%">Description</th>
                    <th width="20%">Month</th>
                    <th width="20%" align="right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Internet Service Payment (via <?= ucwords($details->paid_via ?? 'N/A'); ?>)</td>
                    <td><?= $details->month; ?></td>
                    <td align="right"><?= number_format($details->pay_amount, 2); ?> ৳</td>
                </tr>
                <?php if (!empty($details->method_trx)): ?>
                <tr>
                    <td colspan="3" style="font-size: 12px; color: #7f8c8d;">
                        Transaction ID: <?= $details->method_trx; ?>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="total-section">
            <div class="total-box">
                <div class="total-label">Total Amount Paid</div>
                <div class="total-amount"><?= number_format($details->pay_amount, 2); ?> ৳</div>
            </div>
        </div>

        <div style="margin-top: 50px; font-size: 13px;">
            <p><strong>Note:</strong> This is an electronically generated invoice and does not require a physical signature.</p>
        </div>

        <div class="footer">
            Thank you for choosing <?= $companyName; ?>!<br>
            &copy; <?= date("Y"); ?> <?= $companyName; ?>. All rights reserved.
        </div>
    </div>
</body>

</html>

