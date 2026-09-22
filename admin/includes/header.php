<?php
// admin/includes/header.php
// Har admin page ke start mein: require_once 'includes/header.php';

session_start();

// ---- Role check: sirf admin andar aa sake ----
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

 require_once __DIR__ . '/../../db.php';

// ---- Admin notifications fetch karo ----
$admin_id = $_SESSION['user_id'];
$adminNotifications = [];
$notifStmt = $conn->prepare("SELECT id, message, created_at FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC");
$notifStmt->bind_param("i", $admin_id);
$notifStmt->execute();
$notifResult = $notifStmt->get_result();
while ($row = $notifResult->fetch_assoc()) {
    $adminNotifications[] = $row;
}
$notifStmt->close();
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
     <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
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
        <div style="display:flex; align-items:center; gap:18px;">
            <span style="position:relative; cursor:pointer;" onclick="document.getElementById('adminNotifDropdown').classList.toggle('show')">
                <i class="fa fa-bell" style="font-size:18px; color:#334155;"></i>
                <?php if (count($adminNotifications) > 0): ?>
                    <span style="position:absolute; top:-8px; right:-10px; background:#ef4444; color:#fff; font-size:10px; padding:1px 5px; border-radius:50%;"><?= count($adminNotifications) ?></span>
                <?php endif; ?>

                <div id="adminNotifDropdown" class="admin-notif-dropdown">
                    <div style="background:#1e293b; color:#fff; padding:10px 14px; font-weight:700; font-size:13px; border-radius:8px 8px 0 0;">🔔 Notifications</div>
                    <?php if (count($adminNotifications) === 0): ?>
                        <div style="padding:16px; text-align:center; color:#888; font-size:12px;">No new notifications</div>
                    <?php else: ?>
                        <?php foreach ($adminNotifications as $n): ?>
                            <div style="padding:10px 14px; border-bottom:1px solid #eee; font-size:12px;">
                                <div><?= htmlspecialchars($n['message']) ?></div>
                                <div style="color:#888; font-size:10px; margin-top:4px;"><?= date('d M, h:i A', strtotime($n['created_at'])) ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </span>
            <span class="text-muted"><?= date('d M Y') ?></span>
        </div>
    </div>

    <style>
        .admin-notif-dropdown {
            display:none; position:absolute; top:28px; right:0; background:#fff; width:280px;
            max-height:320px; overflow-y:auto; border-radius:10px; box-shadow:0 12px 30px rgba(0,0,0,0.2);
            z-index:100;
        }
        .admin-notif-dropdown.show { display:block; }
    </style>