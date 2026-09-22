<?php
session_start();

require '../../db.php';
require '../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;


// ===============================
// GET CATEGORY REPORT DATA
// ===============================

$sql = "SELECT 
            cat.name AS cat_name,
            COUNT(c.id) AS total,
            SUM(CASE WHEN c.status='Pending' THEN 1 ELSE 0 END) AS pending,
            SUM(CASE WHEN c.status='In Progress' THEN 1 ELSE 0 END) AS in_progress,
            SUM(CASE WHEN c.status IN ('Resolved','Closed') THEN 1 ELSE 0 END) AS resolved,
            ROUND(
                AVG(
                    CASE 
                        WHEN c.resolved_at IS NOT NULL
                        THEN TIMESTAMPDIFF(HOUR, c.created_at, c.resolved_at)
                    END
                ), 
                1
            ) AS avg_hours
        FROM complaints c
        JOIN categories cat ON c.category_id = cat.id
        GROUP BY cat.name
        ORDER BY total DESC";


$result = $conn->query($sql);

$rows = [];

while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}


// ===============================
// BUILD PDF HTML
// ===============================

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
        font-size: 12px;
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
    System Report — Complaints by Category
</h1>

<p class="meta">
    Generated on ' . date('d M Y, h:i A') . '
</p>

<table>

    <tr>
        <th>Category</th>
        <th>Total</th>
        <th>Pending</th>
        <th>In Progress</th>
        <th>Resolved</th>
        <th>Avg Resolution (hrs)</th>
    </tr>
';


// ===============================
// ADD TABLE ROWS
// ===============================

foreach ($rows as $r) {

    $avg_hours = $r['avg_hours'] !== null
        ? htmlspecialchars($r['avg_hours'])
        : '-';

    $html .= '
    <tr>

        <td>
            ' . htmlspecialchars($r['cat_name']) . '
        </td>

        <td>
            ' . htmlspecialchars($r['total']) . '
        </td>

        <td>
            ' . htmlspecialchars($r['pending']) . '
        </td>

        <td>
            ' . htmlspecialchars($r['in_progress']) . '
        </td>

        <td>
            ' . htmlspecialchars($r['resolved']) . '
        </td>

        <td>
            ' . $avg_hours . '
        </td>

    </tr>
    ';
}


$html .= '

</table>

</body>

</html>
';


// ===============================
// GENERATE PDF
// ===============================

$options = new Options();

$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);

$dompdf->loadHtml($html);

$dompdf->setPaper('A4', 'landscape');

$dompdf->render();


// ===============================
// OPEN PDF IN BROWSER
// ===============================

$dompdf->stream(
    'category_report.pdf',
    [
        'Attachment' => false
    ]
);

exit;

?>