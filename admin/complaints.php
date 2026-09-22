<?php
require_once 'includes/header.php';

$result = $conn->query("
    SELECT c.id, c.title, c.status, c.priority, c.created_at,
           u.name AS user_name, d.name AS dept_name,
           (c.status IN ('Pending','In Progress')
            AND c.created_at < NOW() - INTERVAL 3 DAY) AS is_overdue
    FROM complaints c
    JOIN users u ON u.id = c.user_id
    LEFT JOIN departments d ON d.id = c.department_id
    ORDER BY c.created_at DESC
");
?>

<div class="card p-3">
    <h5 class="mb-3">All Complaints</h5>
<table id="complaintsTable" class="table table-bordered">
            <thead>
        <tr>
            <th>ID</th><th>Title</th><th>Submitted By</th><th>Department</th>
            <th>Status</th><th>Priority</th><th>Date</th><th>Action</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
    <?php
        $st = strtolower(str_replace(' ', '_', $row['status']));
        $badgeClass = $st === 'resolved' ? 'success' :
                     ($st === 'in_progress' ? 'info' :
                     ($st === 'closed' ? 'secondary' :
                     ($st === 'rejected' ? 'danger' : 'warning')));
    ?>
    <tr <?= $row['is_overdue'] ? 'class="table-danger"' : '' ?>>
        <td><?= $row['id'] ?></td>
        <td><?= htmlspecialchars($row['title']) ?></td>
        <td><?= htmlspecialchars($row['user_name']) ?></td>
        <td><?= htmlspecialchars($row['dept_name'] ?? '—') ?></td>
        <td>
            <span class="badge bg-<?= $badgeClass ?>"><?= htmlspecialchars($row['status']) ?></span>
            <?php if ($row['is_overdue']): ?>
                <span class="badge bg-danger">Overdue</span>
            <?php endif; ?>
        </td>
                <td><?= ucfirst($row['priority']) ?></td>
                <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                <td>
                    <a href="complaint_detail.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-primary">
                        <i class="fa fa-eye"></i> View
                    </a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
        <script>
$(document).ready(function() {
    $('#complaintsTable').DataTable();
});
</script>
    </table>
</div>



<?php require_once 'includes/footer.php'; ?>
<script>
$(document).ready(function () {
    $('#complaintsTable').DataTable({
        order: [[6, 'desc']]
    });
});
</script>