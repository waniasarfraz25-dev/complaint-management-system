<?php
session_start();
require_once __DIR__ . '/../db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit();
}

$user_id = $_SESSION['user_id'];
$data = [];

$stmt = mysqli_prepare($conn, "SELECT id, status FROM complaints WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) {
    $data[$row['id']] = $row['status'];
}
mysqli_stmt_close($stmt);

echo json_encode($data);