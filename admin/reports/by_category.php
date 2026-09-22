<?php
session_start();
require '../../db.php';

$sql = "SELECT 
            cat.name AS cat_name,
            COUNT(c.id) AS total,
            SUM(CASE WHEN c.status='Pending' THEN 1 ELSE 0 END) AS pending,
            SUM(CASE WHEN c.status='In Progress' THEN 1 ELSE 0 END) AS in_progress,
            SUM(CASE WHEN c.status IN ('Resolved','Closed') THEN 1 ELSE 0 END) AS resolved,
            ROUND(AVG(CASE WHEN c.resolved_at IS NOT NULL 
                THEN TIMESTAMPDIFF(HOUR, c.created_at, c.resolved_at) END), 1) AS avg_hours
        FROM complaints c
        JOIN categories cat ON c.category_id = cat.id
        GROUP BY cat.name
        ORDER BY total DESC";

$result = $conn->query($sql);
$rows = [];
while ($row = $result->fetch_assoc()) $rows[] = $row;

$labels = json_encode(array_column($rows, 'cat_name'));
$totals = json_encode(array_column($rows, 'total'));
?>
<!DOCTYPE html>
<html>
<head>
<title>System Reports</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<style>
    * { box-sizing: border-box; }
    body { font-family: 'Segoe UI', sans-serif; margin: 0; background: #f4f6f9; }
    .wrap { display: flex; }
    .sidebar { width: 220px; background: #1e2a3a; min-height: 100vh; color: #fff; padding: 20px 0; }
    .sidebar h2 { text-align: center; font-size: 20px; margin-bottom: 30px; }
    .sidebar a { display: block; padding: 12px 25px; color: #b9c2cc; text-decoration: none; font-size: 14px; }
    .sidebar a.active, .sidebar a:hover { background: #2c3e50; color: #fff; border-left: 3px solid #3498db; }
    .main { flex: 1; padding: 30px; }
    .topbar { display: flex; justify-content: space-between; margin-bottom: 20px; color: #555; }
    h1 { color: #2c3e50; margin-bottom: 25px; }
    .card { background: #fff; border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.06); margin-bottom: 25px; }
    .card h3 { margin-top: 0; color: #34495e; }
    table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    th, td { padding: 12px 10px; text-align: left; border-bottom: 1px solid #eee; font-size: 14px; }
    th { color: #7f8c8d; text-transform: uppercase; font-size: 12px; }
    tr:hover { background: #f9fbfd; }
    .badge { padding: 3px 10px; border-radius: 12px; font-size: 12px; color: #fff; }
    .b-pending { background: #e67e22; }
    .b-progress { background: #f1c40f; color: #333; }
    .b-resolved { background: #27ae60; }
    .btn { padding: 10px 18px; border: none; border-radius: 6px; color: #fff; cursor: pointer; font-size: 14px; margin-right: 10px; }
    .btn-pdf { background: #e74c3c; }
    .btn-excel { background: #27ae60; }
</style>
</head>
<body>
<div class="wrap">
    <div class="sidebar">
        <h2>CMS Admin</h2>
        <a href="../dashboard.php">Dashboard</a>
        <a href="../complaints.php">Complaints</a>
        <a href="by_department.php">By Department</a>
        <a href="by_category.php" class="active">By Category</a>
        <a href="monthly_trends.php">Monthly Trends</a>
        <a href="frequent_problems.php">Frequent Problems</a>
    </div>
    <div class="main">
        <div class="topbar">
            <span>Welcome, <?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></span>
            <span><?= date('d M Y') ?></span>
        </div>
        <h1>System Reports — Complaints by Category</h1>

        <div class="card">
            <h3>Complaints by Category</h3>
            <canvas id="catChart" height="90"></canvas>
        </div>

        <div class="card">
            <table>
                <tr>
                    <th>Category</th><th>Total</th><th>Pending</th>
                    <th>In Progress</th><th>Resolved</th><th>Avg Resolution (hrs)</th>
                </tr>
                <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['cat_name']) ?></td>
                    <td><?= $r['total'] ?></td>
                    <td><span class="badge b-pending"><?= $r['pending'] ?></span></td>
                    <td><span class="badge b-progress"><?= $r['in_progress'] ?></span></td>
                    <td><span class="badge b-resolved"><?= $r['resolved'] ?></span></td>
                    <td><?= $r['avg_hours'] !== null ? $r['avg_hours'] : '-' ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
            <div style="margin-top:20px;"> 

    <a href="export_category_pdf.php"
       target="_blank"
       class="btn btn-pdf"
       style="text-decoration:none; display:inline-block;">
        Export to PDF
    </a>


</div>
        </div>
    </div>
</div>

<script>
new Chart(document.getElementById('catChart'), {
    type: 'bar',
    data: {
        labels: <?= $labels ?>,
        datasets: [{
            label: 'Total Complaints',
            data: <?= $totals ?>,
            backgroundColor: '#9b59b6',
            borderRadius: 6
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
    }
});
</script>
</body>
</html>