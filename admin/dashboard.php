<?php
require_once __DIR__ . '/includes/header.php';
// ---- Summary counts ----
$total     = $conn->query("SELECT COUNT(*) c FROM complaints")->fetch_assoc()['c'];
$pending   = $conn->query("SELECT COUNT(*) c FROM complaints WHERE status='Pending'")->fetch_assoc()['c'];
$progress  = $conn->query("SELECT COUNT(*) c FROM complaints WHERE status='In Progress'")->fetch_assoc()['c'];
$resolved  = $conn->query("SELECT COUNT(*) c FROM complaints WHERE status='Resolved'")->fetch_assoc()['c'];

// Overdue = pending/in_progress complaints older than 3 days
$overdue   = $conn->query("
    SELECT COUNT(*) c FROM complaints
    WHERE status IN ('Pending','In Progress')
    AND created_at < NOW() - INTERVAL 3 DAY
")->fetch_assoc()['c'];
// Recent 5 complaints for quick glance
$recent = $conn->query("
    SELECT c.id, c.title, c.status, c.priority, c.created_at, u.name AS user_name
    FROM complaints c
    JOIN users u ON u.id = c.user_id
    ORDER BY c.created_at DESC LIMIT 5
");
?>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card card-stat p-3" style="background:#3b82f6;">
            <span>Total Complaints</span>
            <h2><?= $total ?></h2>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-stat p-3" style="background:#f59e0b;">
            <span>Pending</span>
            <h2><?= $pending ?></h2>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-stat p-3" style="background:#6366f1;">
            <span>In Progress</span>
            <h2><?= $progress ?></h2>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-stat p-3" style="background:#10b981;">
            <span>Resolved</span>
            <h2><?= $resolved ?></h2>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card card-stat p-3" style="background:#ef4444;">
            <span><i class="fa fa-triangle-exclamation"></i> Overdue Complaints</span>
            <h2><?= $overdue ?></h2>
        </div>
    </div>
</div>

<div class="card p-3">
    <h6 class="mb-3">Recent Complaints</h6>
    <table class="table table-hover align-middle">
        <thead>
        <tr><th>#</th><th>Title</th><th>By</th><th>Status</th><th>Priority</th><th>Date</th></tr>
        </thead>
        <tbody>
        <?php while ($row = $recent->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><a href="complaint_detail.php?id=<?= $row['id'] ?>"><?= htmlspecialchars($row['title']) ?></a></td>
                <td><?= htmlspecialchars($row['user_name']) ?></td>
                <td><span class="badge bg-secondary"><?= $row['status'] ?></span></td>
                <td><?= $row['priority'] ?></td>
                <td><?= date('d M, H:i', strtotime($row['created_at'])) ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php';