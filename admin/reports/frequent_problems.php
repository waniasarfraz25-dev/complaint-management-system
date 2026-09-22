<?php
session_start();
require '../../db.php';

$sql = "SELECT 
            title,
            COUNT(*) AS total
        FROM complaints
        GROUP BY title
        ORDER BY total DESC
        LIMIT 10";

$result = $conn->query($sql);
$rows = [];
while ($row = $result->fetch_assoc()) $rows[] = $row;

$labels = json_encode(array_column($rows, 'title'));
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
    .rank { display:inline-block; width:24px; height:24px; border-radius:50%; background:#3498db; color:#fff; text-align:center; line-height:24px; font-size:12px; margin-right:8px; }
</style>
</head>
<body>
<div class="wrap">
    <div class="sidebar">
        <h2>CMS Admin</h2>
        <a href="../dashboard.php">Dashboard</a>
        <a href="../complaints.php">Complaints</a>
        <a href="by_department.php">By Department</a>
        <a href="by_category.php">By Category</a>
        <a href="monthly_trends.php">Monthly Trends</a>
        <a href="frequent_problems.php" class="active">Frequent Problems</a>
    </div>
    <div class="main">
        <div class="topbar">
            <span>Welcome, <?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></span>
            <span><?= date('d M Y') ?></span>
        </div>
        <h1>System Reports — Most Frequent Problems</h1>

        <div class="card">
            <h3>Top 10 Most Reported Problems</h3>
            <canvas id="freqChart" height="100"></canvas>
        </div>

        <div class="card">
            <table>
                <tr><th>#</th><th>Problem</th><th>Times Reported</th></tr>
                <?php $i = 1; foreach ($rows as $r): ?>
                <tr>
                    <td><span class="rank"><?= $i++ ?></span></td>
                    <td><?= htmlspecialchars($r['title']) ?></td>
                    <td><?= $r['total'] ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
                <div style="margin-top:20px;">

            <a href="export_frequent_pdf.php"
               target="_blank"
               class="btn btn-pdf"
               style="
                   display:inline-block;
                   background:#3498db;
                   color:#fff;
                   padding:10px 18px;
                   border-radius:6px;
                   text-decoration:none;
                   font-size:14px;
               ">
                Export to PDF
            </a>

        </div>
    </div>
</div>

<script>
new Chart(document.getElementById('freqChart'), {
    type: 'bar',
    data: {
        labels: <?= $labels ?>,
        datasets: [{
            label: 'Times Reported',
            data: <?= $totals ?>,
            backgroundColor: '#e67e22',
            borderRadius: 6
        }]
    },
    options: {
        indexAxis: 'y',
        plugins: { legend: { display: false } },
        scales: { x: { beginAtZero: true } }
    }
});
</script>
</body>
</html>