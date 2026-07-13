<!DOCTYPE html>
<html>
<head>
    <title><?= $title ?></title>
    <style>
        body { font-family: sans-serif; font-size: 10pt; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .header { text-align: center; margin-bottom: 20px; }
        .footer { text-align: right; font-size: 8pt; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h2><?= $title ?></h2>
        <p>Generated on: <?= date('d/m/Y H:i:s') ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th>SL</th>
                <th>Client Name</th>
                <th>Mobile</th>
                <th>Email</th>
                <th>Package</th>
                <th>Bandwidth</th>
                <th>Price</th>
                <th>Area</th>
                <th>Conn. Type</th>
                <th>Client Type</th>
                <th>Activation Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data as $index => $row): ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td><?= esc($row->name) ?></td>
                    <td><?= esc($row->mobile) ?></td>
                    <td><?= esc($row->email) ?></td>
                    <td><?= esc($row->package_name) ?></td>
                    <td><?= esc($row->bandwidth) ?></td>
                    <td><?= esc($row->price) ?></td>
                    <td><?= esc($row->area_name) ?></td>
                    <td><?= esc($row->connection_type) ?></td>
                    <td><?= esc($row->client_type) ?></td>
                    <td><?= date('d/m/Y', strtotime($row->created_at)) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="footer">
        Page {PAGENO} of {nb}
    </div>
</body>
</html>
