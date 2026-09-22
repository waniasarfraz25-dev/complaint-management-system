<?php
session_start();
require_once __DIR__ . '/../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $complaint_id = intval($_POST['complaint_id']);
    $status       = trim($_POST['status']);
    $remarks      = trim($_POST['remarks'] ?? '');
    $priority = trim($_POST['priority'] ?? '');

    $allowedStatuses = [ 'In Progress' , 'Resolved', 'Closed'];
$allowedPriorities = ['Low', 'Medium', 'High'];
if ($complaint_id && in_array($status, $allowedStatuses) && in_array($priority, $allowedPriorities)) {
        // Agar admin 'Closed' select kar raha hai, to check karo user ne feedback diya hai ya nahi
        if ($status === 'Closed') {
            $fbCheck = $conn->prepare("SELECT id FROM feedback WHERE complaint_id = ?");
            $fbCheck->bind_param("i", $complaint_id);
            $fbCheck->execute();
            $fbExists = $fbCheck->get_result()->fetch_assoc();

            if (!$fbExists) {
                header("Location: complaint_detail.php?id=" . $complaint_id . "&error=no_feedback");
                exit();
            }
        }      

// 0. Get old status first (needed for complaint_updates log)
$oldStmt = $conn->prepare("SELECT status, priority, user_id FROM complaints WHERE id = ?");        $oldStmt->bind_param("i", $complaint_id);
        $oldStmt->execute();
        $oldRow = $oldStmt->get_result()->fetch_assoc();
        $old_status = $oldRow['status'] ?? '';
        $old_priority = $oldRow['priority'] ?? '';
        $ownerId    = $oldRow['user_id'] ?? null;

        // 1. Update complaint status (and resolved_at if Resolved)
        if ($status === 'Resolved') {
    $stmt = $conn->prepare("UPDATE complaints SET status = ?, priority = ?, resolved_at = NOW() WHERE id = ?");
} else {
    $stmt = $conn->prepare("UPDATE complaints SET status = ?, priority = ? WHERE id = ?");
}
$stmt->bind_param("ssi", $status, $priority, $complaint_id);
        $stmt->execute();

        // 2. Log into complaint_updates
        $log_remarks = $remarks !== '' ? $remarks : "Status changed to $status.";
        $stmt2 = $conn->prepare("INSERT INTO complaint_updates (complaint_id, updated_by, old_status, new_status, old_priority, new_priority, remarks, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
$stmt2->bind_param("iisssss", $complaint_id, $_SESSION['user_id'], $old_status, $status, $old_priority, $priority, $log_remarks);
        $stmt2->execute();

        // 3. Notify the complaint's original submitter
        if ($ownerId) {
            $ownerMsg = "Your complaint #$complaint_id status changed to $status.";
            $stmt3 = $conn->prepare("INSERT INTO notifications (user_id, complaint_id, message, is_read, created_at) VALUES (?, ?, ?, 0, NOW())");
            $stmt3->bind_param("iis", $ownerId, $complaint_id, $ownerMsg);
            $stmt3->execute();
        }
    }

    header("Location: complaint_detail.php?id=" . $complaint_id . "&updated=1");
    exit();
}
?>