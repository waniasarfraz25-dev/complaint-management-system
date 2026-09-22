<?php
require_once 'includes/header.php';

$id = intval($_GET['id'] ?? 0);

$stmt = $conn->prepare("
    SELECT c.*, u.name AS user_name, u.email AS user_email, d.name AS dept_name, cat.name AS cat_name
    FROM complaints c
    JOIN users u ON u.id = c.user_id
    LEFT JOIN departments d ON d.id = c.department_id
    LEFT JOIN categories cat ON cat.id = c.category_id
    WHERE c.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$complaint = $stmt->get_result()->fetch_assoc();

if (!$complaint) {
    echo "<div class='alert alert-danger'>Complaint not found.</div>";
    require_once 'includes/footer.php';
    exit();
}

// Staff list for assign dropdown (users with role = staff, same department)
$staffStmt = $conn->prepare("SELECT id, name FROM users WHERE role='staff' AND department_id = ?");
$staffStmt->bind_param("i", $complaint['department_id']);
$staffStmt->execute();
$staffList = $staffStmt->get_result();

// Already assigned?
// Already assigned?
$assignStmt = $conn->prepare("
    SELECT a.staff_id, u.name AS staff_name, a.assigned_at
    FROM assignments a JOIN users u ON u.id = a.staff_id
    WHERE a.complaint_id = ? ORDER BY a.assigned_at DESC LIMIT 1
");
$assignStmt->bind_param("i", $id);
$assignStmt->execute();
$currentAssignment = $assignStmt->get_result()->fetch_assoc();
// Update history
$updates = $conn->prepare("SELECT * FROM complaint_updates WHERE complaint_id = ? ORDER BY created_at DESC");
$updates->bind_param("i", $id);
$updates->execute();
$updateResult = $updates->get_result();

// Fetch feedback (if any) for this complaint
$fbStmt = $conn->prepare("
    SELECT f.*, u.name AS user_name
    FROM feedback f
    JOIN users u ON u.id = f.user_id
    WHERE f.complaint_id = ?
");
$fbStmt->bind_param("i", $id);
$fbStmt->execute();
$feedback = $fbStmt->get_result()->fetch_assoc();
?>
<?php if (isset($_GET['error']) && $_GET['error'] === 'no_feedback'): ?>
    <div class="alert alert-warning">
This complaint cannot be closed until the user submits feedback.    </div>
<?php endif; ?>
<div class="row g-3">

    <div class="col-md-7">
        <div class="card p-4">
            <h5><?= htmlspecialchars($complaint['title']) ?></h5>
            <p class="text-muted mb-1">Complaint #<?= $complaint['id'] ?> • <?= date('d M Y, H:i', strtotime($complaint['created_at'])) ?></p>
<span class="badge bg-warning mb-3"><?= htmlspecialchars($complaint['status']) ?></span>
            <p><strong>Description:</strong><br><?= nl2br(htmlspecialchars($complaint['description'])) ?></p>
            <p><strong>Category:</strong> <?= htmlspecialchars($complaint['cat_name'] ?? '—') ?></p>
            <p><strong>Department:</strong> <?= htmlspecialchars($complaint['dept_name'] ?? '—') ?></p>
<p><strong>Priority:</strong> <?= htmlspecialchars(ucfirst($complaint['priority'])) ?></p>            <?php if (!empty($complaint['photo'])): ?>
    <p><strong>Attachment:</strong></p>
    <a href="../<?= htmlspecialchars($complaint['photo']) ?>" target="_blank">
        <img src="../<?= htmlspecialchars($complaint['photo']) ?>"
             alt="Complaint photo"
             style="max-width: 100%; max-height: 300px; border-radius: 8px; margin-bottom: 15px;">
    </a>
<?php endif; ?>
            <hr>
            <h6>Submitted By</h6>
            <p><?= htmlspecialchars($complaint['user_name']) ?> (<?= htmlspecialchars($complaint['user_email']) ?>)</p>
        </div>


        <div class="card p-4 mt-3">
            <h6>Update History</h6>
            <?php if ($updateResult->num_rows === 0): ?>
                <p class="text-muted">No updates yet.</p>
            <?php endif; ?>
            <ul class="list-unstyled">
                <?php while ($u = $updateResult->fetch_assoc()): ?>
                    <li class="mb-2 border-bottom pb-2">
                       <strong><?= htmlspecialchars($u['new_status']) ?></strong> — <?= htmlspecialchars($u['remarks']) ?>
<br><small class="text-muted"><?= date('d M Y, H:i', strtotime($u['created_at'])) ?></small>
                    </li>
                <?php endwhile; ?>
            </ul>
        </div>
        
        <div class="card p-4 mt-3">
            <h6>User Feedback</h6>
            <?php if ($feedback): ?>
                <p class="mb-1">
                    <strong>Rating:</strong>
                    <?= str_repeat('⭐', (int)$feedback['rating']) ?>
                    (<?= (int)$feedback['rating'] ?>/5)
                </p>
                <p class="mb-1"><strong>Comment:</strong> <?= htmlspecialchars($feedback['comment']) ?></p>
                <p class="text-muted small mb-0">
                    By <?= htmlspecialchars($feedback['user_name']) ?> on
                    <?= date('d M Y, H:i', strtotime($feedback['created_at'])) ?>
                </p>
            <?php else: ?>
                <p class="text-muted mb-0">No feedback submitted yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-md-5">
        <div class="card p-4">
            <h6>Assign Complaint</h6>

            <?php if ($currentAssignment): ?>
                <div class="alert alert-info py-2">
                    Currently assigned to <strong><?= htmlspecialchars($currentAssignment['staff_name']) ?></strong>
                    on <?= date('d M Y', strtotime($currentAssignment['assigned_at'])) ?>
                </div>
            <?php endif; ?>

            <form action="assign_action.php" method="POST">
                <input type="hidden" name="complaint_id" value="<?= $complaint['id'] ?>">

                
                
                <div class="mb-3">
                    <label class="form-label">Assign to Staff</label>
                    <select name="staff_id" class="form-select" required>
                        <option value="">-- Select Staff --</option>
                        <?php while ($s = $staffList->fetch_assoc()): ?>
                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Remarks (optional)</label>
                    <textarea name="remarks" class="form-control" rows="2"></textarea>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="fa fa-user-check"></i> Assign
                </button>
            </form>
        </div>
        <div class="card p-4 mt-3">
            <h6>Update Status</h6>
            <form action="update_status.php" method="POST">
                                <input type="hidden" name="complaint_id" value="<?= $complaint['id'] ?>">

                <div class="mb-3">
                    <label class="form-label">New Priority</label>
                    <select name="priority" class="form-select" required>
                        <option value="Low" <?= $complaint['priority']=='Low'?'selected':'' ?>>Low</option>
                        <option value="Medium" <?= $complaint['priority']=='Medium'?'selected':'' ?>>Medium</option>
                        <option value="High" <?= $complaint['priority']=='High'?'selected':'' ?>>High</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">New Status</label>
                    <select name="status" class="form-select" required>
                        <option value="In Progress">In Progress</option>
                        <option value="Resolved">Resolved</option>
                        <option value="Closed">Closed</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Remarks</label>
                    <textarea name="remarks" class="form-control" rows="2"></textarea>
                </div>

                <button type="submit" class="btn btn-success w-100">Update Status</button>
            </form>
        </div>

    

    </div>
</div>

<?php require_once 'includes/footer.php'; ?>