<?php
session_start();
require_once __DIR__ . '/../db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $complaint_id = intval($_POST['complaint_id']);
    $staff_id     = intval($_POST['staff_id']);
    $remarks      = trim($_POST['remarks'] ?? '');

    if ($complaint_id && $staff_id) {
        // 1. Insert into assignments
$stmt = $conn->prepare("INSERT INTO assignments (complaint_id, staff_id, assigned_at) VALUES (?, ?, NOW())");        $stmt->bind_param("ii", $complaint_id, $staff_id);
        $stmt->execute();

                // Get current status & priority (needed for the update log)
        $oldStmt = $conn->prepare("SELECT status, priority FROM complaints WHERE id = ?");
        $oldStmt->bind_param("i", $complaint_id);
        $oldStmt->execute();
        $oldRow = $oldStmt->get_result()->fetch_assoc();
       $old_status   = $oldRow['status'] ?? '';
$old_priority = $oldRow['priority'] ?? '';
$new_status   = ($old_status === 'Pending') ? 'In Progress' : $old_status;
        // 2. Update complaint status -> in_progress
// 2. Update complaint status -> in_progress (sirf agar abhi Pending ho)
$stmt2 = $conn->prepare("UPDATE complaints SET status = 'In Progress' WHERE id = ? AND status = 'Pending'");        $stmt2->bind_param("i", $complaint_id);
        $stmt2->execute();

        // 3. Log into complaint_updates
        $log_remarks = $remarks !== '' ? $remarks : 'Complaint assigned to staff.';
        $stmt3 = $conn->prepare("INSERT INTO complaint_updates (complaint_id, updated_by, old_status, new_status, remarks, old_priority, new_priority) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt3->bind_param("iisssss", $complaint_id, $_SESSION['user_id'], $old_status, $new_status, $log_remarks, $old_priority, $old_priority);
        $stmt3->execute();
        // 4. Notify the assigned staff
        $msg = "You have been assigned a new complaint (#$complaint_id).";
        $stmt4 = $conn->prepare("INSERT INTO notifications (user_id, message, is_read, created_at) VALUES (?, ?, 0, NOW())");
        $stmt4->bind_param("is", $staff_id, $msg);
        $stmt4->execute();
        // 5. Notify the complaint's original submitter too
$ownerStmt = $conn->prepare("SELECT user_id FROM complaints WHERE id = ?");
$ownerStmt->bind_param("i", $complaint_id);
$ownerStmt->execute();
$ownerResult = $ownerStmt->get_result();
if ($ownerRow = $ownerResult->fetch_assoc()) {
    $ownerId = $ownerRow['user_id'];
    $ownerMsg = "Your complaint #$complaint_id is now In Progress.";
    $stmt5 = $conn->prepare("INSERT INTO notifications (user_id, complaint_id, message, is_read, created_at) VALUES (?, ?, ?, 0, NOW())");
    $stmt5->bind_param("iis", $ownerId, $complaint_id, $ownerMsg);
    $stmt5->execute();
}
    }

    header("Location: complaint_detail.php?id=" . $complaint_id . "&assigned=1");
    exit();
}