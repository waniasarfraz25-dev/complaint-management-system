<?php
session_start();
require '../db.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$user_id = $_SESSION['user_id'];
$complaint_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$msg = "";

// Complaint verify: apna ho + resolved/closed ho
$stmt = $conn->prepare("SELECT id, title, status FROM complaints WHERE id=? AND user_id=?");
$stmt->bind_param("ii", $complaint_id, $user_id);
$stmt->execute();
$complaint = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$complaint) { die("Complaint not found or not yours."); }

$status = strtolower($complaint['status']);
if ($status !== 'resolved' && $status !== 'closed') {
    die("Feedback sirf Resolved/Closed complaint par de sakte hain.");
}

// Pehle se diya hua feedback check
$stmt = $conn->prepare("SELECT id FROM feedback WHERE complaint_id=?");
$stmt->bind_param("i", $complaint_id);
$stmt->execute();
$already = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$already) {
    $rating  = (int)($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');

    if ($rating < 1 || $rating > 5) {
        $msg = "<p style='color:red'>Please select 1 to 5 stars.</p>";
    } else {
               $stmt = $conn->prepare("INSERT INTO feedback (complaint_id, user_id, rating, comment) VALUES (?,?,?,?)");
        $stmt->bind_param("iiis", $complaint_id, $user_id, $rating, $comment);
        if ($stmt->execute()) {
            $stmt->close();

            // Admin(s) ko notification bhejo
            $stars = str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
            $notifMsg = "Feedback on \"" . $complaint['title'] . "\" — Ratings: " . $stars . "  Comment: " . ($comment !== '' ? $comment : "No comment");            $admins = $conn->query("SELECT id FROM users WHERE role = 'admin'");
            while ($a = $admins->fetch_assoc()) {
                $n = $conn->prepare("INSERT INTO notifications (user_id, complaint_id, message, is_read, created_at) VALUES (?, ?, ?, 0, NOW())");
                $n->bind_param("iis", $a['id'], $complaint_id, $notifMsg);
                $n->execute();
                $n->close();
            }

            header("Location: index.php?fb=success");
            exit;
        } else {
            $msg = "<p style='color:red'>Error: " . htmlspecialchars($stmt->error) . "</p>";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Give Feedback</title>
  <style>
    body{font-family:Arial;background:#f4f6f9;padding:40px}
    .box{max-width:520px;margin:auto;background:#fff;padding:25px;border-radius:10px;box-shadow:0 2px 8px #ccc}
    .stars{direction:rtl;display:inline-flex}
    .stars input{display:none}
    .stars label{font-size:38px;color:#ccc;cursor:pointer;padding:0 4px}
    .stars input:checked ~ label,
    .stars label:hover,
    .stars label:hover ~ label{color:#f5b301}
    textarea{width:100%;height:100px;padding:8px;margin-top:10px}
    button{background:#2c7be5;color:#fff;border:0;padding:10px 22px;border-radius:6px;cursor:pointer;margin-top:12px}
  </style>
</head>
<body>
<div class="box">
  <h2>Feedback</h2>
  <p><b>Complaint:</b> <?= htmlspecialchars($complaint['title']) ?></p>

  <?php if ($already): ?>
<p style="color:green">You have already submitted feedback for this complaint. Thank you!</p>    <a href="index.php">← Back</a>
  <?php else: ?>
    <?= $msg ?>
    <form method="post">
      <label><b>Rating:</b></label><br>
      <div class="stars">
        <input type="radio" name="rating" id="s5" value="5"><label for="s5">★</label>
        <input type="radio" name="rating" id="s4" value="4"><label for="s4">★</label>
        <input type="radio" name="rating" id="s3" value="3"><label for="s3">★</label>
        <input type="radio" name="rating" id="s2" value="2"><label for="s2">★</label>
        <input type="radio" name="rating" id="s1" value="1"><label for="s1">★</label>
      </div>
<textarea name="comment" placeholder="Your comments (optional)"></textarea>      <button type="submit">Submit Feedback</button>
      <a href="index.php" style="margin-left:10px">Cancel</a>
    </form>
  <?php endif; ?>
</div>
</body>
</html>