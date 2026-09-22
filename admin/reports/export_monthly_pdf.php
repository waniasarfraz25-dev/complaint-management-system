<?php
session_start();

require '../../db.php';
require '../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;


// =====================================
// GET MONTHLY TRENDS DATA
// =====================================

$sql = "SELECT 
            DATE_FORMAT(created_at, '%Y-%m') AS month,
            COUNT(*) AS total
        FROM complaints
        GROUP BY month
        ORDER BY month ASC";

$result = $conn->query($sql);

$rows = [];

while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}


// =====================================
// BUILD PDF HTML
// =====================================

$html = '
<html>

<head>

<style>

    body {
        font-family: Arial, sans-serif;
        color: #2c3e50;
    }

    h1 {
        font-size: 20px;
        color: #2c3e50;
        border-bottom: 2px solid #3498db;
        padding-bottom: 8px;
    }

    p.meta {
        color: #777;
        font-size: 12px;
        margin-top: -5px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    th,
    td {
        border: 1px solid #ddd;
        padding: 8px 10px;
        font-size: 13px;
        text-align: left;
    }

    th {
        background: #2c3e50;
        color: #fff;
    }

    tr:nth-child(even) {
        background: #f7f9fb;
    }

</style>

</head>

<body>

<h1>
    System Report — Monthly Trends
</h1>

<p class="meta">
    Generated on ' . date('d M Y, h:i A') . '
</p>

<table>

    <tr>
        <th>Month</th>
        <th>Total Complaints</th>
    </tr>
';


// =====================================
// ADD MONTHLY DATA
// =====================================

foreach ($rows as $r) {

    $html .= '
    <tr>

        <td>
            ' . htmlspecialchars($r['month']) . '
        </td>

        <td>
            ' . htmlspecialchars($r['total']) . '
        </td>

    </tr>
    ';
}


$html .= '

</table>

</body>

</html>
';


// =====================================
// GENERATE PDF
// =====================================

$options = new Options();

$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);

$dompdf->loadHtml($html);

$dompdf->setPaper('A4', 'portrait');

$dompdf->render();


// =====================================
// OPEN PDF
// =====================================

$dompdf->stream(
    'monthly_trends_report.pdf',
    [
        'Attachment' => false
    ]
);

exit;

?>