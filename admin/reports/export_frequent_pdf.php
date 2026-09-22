<?php
session_start();

require '../../db.php';
require '../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;


// =====================================
// GET MOST FREQUENT PROBLEMS DATA
// =====================================

$sql = "SELECT 
            title,
            COUNT(*) AS total
        FROM complaints
        GROUP BY title
        ORDER BY total DESC
        LIMIT 10";

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
        padding: 9px 10px;
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
    System Report — Most Frequent Problems
</h1>

<p class="meta">
    Generated on ' . date('d M Y, h:i A') . '
</p>

<table>

    <tr>
        <th>#</th>
        <th>Problem</th>
        <th>Total Complaints</th>
    </tr>
';


// =====================================
// ADD TABLE ROWS
// =====================================

$number = 1;

foreach ($rows as $r) {

    $html .= '
    <tr>

        <td>
            ' . $number . '
        </td>

        <td>
            ' . htmlspecialchars($r['title']) . '
        </td>

        <td>
            ' . htmlspecialchars($r['total']) . '
        </td>

    </tr>
    ';

    $number++;
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
// OPEN PDF IN BROWSER
// =====================================

$dompdf->stream(
    'frequent_problems_report.pdf',
    [
        'Attachment' => false
    ]
);

exit;

?>