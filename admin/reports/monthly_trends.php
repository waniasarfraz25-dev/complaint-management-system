<?php
session_start();
require '../../db.php';

$sql = "SELECT 
            DATE_FORMAT(created_at, '%Y-%m') AS month,
            COUNT(*) AS total
        FROM complaints
        GROUP BY month
        ORDER BY month ASC";

$result = $conn->query($sql);
$rows = [];
while ($row = $result->fetch_assoc()) $rows[] = $row;

$labels = json_encode(array_column($rows, 'month'));
$totals = json_encode(array_column($rows, 'total'));

// current page ka naam nikal lo taake sidebar m active link highlight ho sake
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html>
<head>
<title>System Reports</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<style>
    * { box-sizing: border-box; }
    body {
        font-family: 'Segoe UI', sans-serif;
        margin: 0;
        background: #f4f6f9;
    }
    .wrap { display: flex; }

    /* Sidebar */
    .sidebar {
        width: 230px;
        background: linear-gradient(180deg, #1e2a3a 0%, #16202c 100%);
        min-height: 100vh;
        color: #fff;
        padding: 20px 0;
        box-shadow: 2px 0 10px rgba(0,0,0,0.15);
    }
    .sidebar h2 {
        text-align: center;
        font-size: 20px;
        margin-bottom: 30px;
        letter-spacing: 0.5px;
    }
    .sidebar a {
        display: flex;
        align-items: center;
        padding: 12px 25px;
        color: #b9c2cc;
        text-decoration: none;
        font-size: 14px;
        border-left: 3px solid transparent;
        transition: all 0.2s ease-in-out;
    }
    .sidebar a.active,
    .sidebar a:hover {
        background: #2c3e50;
        color: #fff;
        border-left: 3px solid #3498db;
    }

    /* Main content */
    .main { flex: 1; padding: 30px; }
    .topbar {
        display: flex;
        justify-content: space-between;
        margin-bottom: 20px;
        color: #555;
        font-size: 14px;
    }
    h1 {
        color: #2c3e50;
        margin-bottom: 25px;
        font-size: 24px;
    }
    .card {
        background: #fff;
        border-radius: 14px;
        padding: 25px;
        box-shadow: 0 6px 18px rgba(0,0,0,0.08);
        margin-bottom: 25px;
        transition: box-shadow 0.2s ease;
    }
    .card:hover {
        box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    }
    .card h3 {
        margin-top: 0;
        color: #34495e;
        font-size: 17px;
        border-bottom: 1px solid #eef1f4;
        padding-bottom: 10px;
        margin-bottom: 20px;
    }
</style>
</head>
<body>
<div class="wrap">
    <div class="sidebar">
        <h2>CMS Admin</h2>
        <a href="../dashboard.php" class="<?= $current_page == 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
        <a href="../complaints.php" class="<?= $current_page == 'complaints.php' ? 'active' : '' ?>">Complaints</a>
        <a href="by_department.php" class="<?= $current_page == 'by_department.php' ? 'active' : '' ?>">By Department</a>
        <a href="by_category.php" class="<?= $current_page == 'by_category.php' ? 'active' : '' ?>">By Category</a>
        <a href="monthly_trends.php" class="<?= $current_page == 'monthly_trends.php' ? 'active' : '' ?>">Monthly Trends</a>
        <a href="frequent_problems.php" class="<?= $current_page == 'frequent_problems.php' ? 'active' : '' ?>">Frequent Problems</a>
    </div>
    <div class="main">
        <div class="topbar">
            <span>Welcome, <?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></span>
            <span><?= date('d M Y') ?></span>
        </div>
        <h1>System Reports — Monthly Trends</h1>
          <a href="export_monthly_pdf.php" target="_blank" style="display:inline-block; background:#3498db; color:#fff; padding:10px 18px; border-radius:6px; text-decoration:none; font-size:14px; margin-bottom:20px;">⬇ Export PDF</a>
        <div class="card">
            <h3>Complaints per Month</h3>
            <canvas id="trendChart" height="90"></canvas>
        </div>
    </div>
</div>

<script>
new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: {
        labels: <?= $labels ?>,
        datasets: [{
            label: 'Complaints',
            data: <?= $totals ?>,
            borderColor: '#3498db',
            backgroundColor: 'rgba(52,152,219,0.2)',
            fill: true,
            tension: 0.3,
            pointBackgroundColor: '#3498db',
            pointRadius: 4
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