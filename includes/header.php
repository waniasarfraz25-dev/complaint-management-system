<?php
// admin/includes/header.php
// Har admin page ke start mein: require_once 'includes/header.php';

session_start();

// ---- Role check: sirf admin andar aa sake ----
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once __DIR__ . '/../db.php'; // <-- apne db.php ka path check kar lena
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Complaint Management System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background:#f4f6f9; }
        .sidebar {
            min-height:100vh; background:#1e293b; color:#fff; width:230px; position:fixed;
        }
        .sidebar a { color:#cbd5e1; display:block; padding:12px 20px; text-decoration:none; }
        .sidebar a:hover, .sidebar a.active { background:#334155; color:#fff; }
        .content { margin-left:230px; padding:25px; }
        .card-stat { border:none; border-radius:12px; color:#fff; }
        .card-stat h2 { font-weight:700; }
        .topbar { background:#fff; padding:12px 20px; border-radius:8px; margin-bottom:20px;
                  display:flex; justify-content:space-between; align-items:center; box-shadow:0 1px 3px rgba(0,0,0,.08);}
    </style>
</head>
<body>

<div class="sidebar">
    <h4 class="text-center py-3">CMS Admin</h4>
    <a href="dashboard.php"><i class="fa fa-gauge"></i> Dashboard</a>
    <a href="complaints.php"><i class="fa fa-list"></i> Complaints</a>
    <a href="departments.php"><i class="fa fa-building"></i> Departments</a>
    <a href="categories.php"><i class="fa fa-tags"></i> Categories</a>
<a href="reports/by_department.php"><i class="fa fa-chart-bar"></i> Reports</a>    <a href="../logout.php"><i class="fa fa-right-from-bracket"></i> Logout</a>
</div>

<div class="content">
    <div class="topbar">
        <h5 class="mb-0">Welcome, <?= htmlspecialchars($_SESSION['name'] ?? 'Admin') ?></h5>
        <span class="text-muted"><?= date('d M Y') ?></span>
    </div>