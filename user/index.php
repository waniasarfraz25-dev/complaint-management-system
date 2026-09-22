<?php
session_start();
include '../db.php';

// Protect this page - only logged in users
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";
$messageType = "";

// Fetch categories for dropdown
$categories = [];
$catResult = mysqli_query($conn, "SELECT id, name, department_id FROM categories ORDER BY name ASC");
while ($row = mysqli_fetch_assoc($catResult)) {
    $categories[] = $row;
}

// Handle complaint submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $title       = trim($_POST['title']);
    $description = trim($_POST['description']);
    $location    = trim($_POST['location']);
    $priority    = trim($_POST['priority']);
    $category_id = intval($_POST['category_id']);

    $photoPath = null;

    // ===== File Upload =====
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {

        $allowed = ['jpg', 'jpeg', 'png', 'gif'];

        $fileName = $_FILES['photo']['name'];
        $fileTmp  = $_FILES['photo']['tmp_name'];
        $fileSize = $_FILES['photo']['size'];

        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // Reject PHP and all other non-image files
        if (!in_array($fileExt, $allowed, true)) {
            $message = "Invalid file type. Only JPG, JPEG, PNG and GIF files are allowed.";
            $messageType = "error";
        }

        // Maximum 5 MB
        elseif ($fileSize > 5 * 1024 * 1024) {
            $message = "File is too large. Maximum size is 5 MB.";
            $messageType = "error";
        }

        else {
            $newFileName = uniqid('complaint_', true) . '.' . $fileExt;

            $uploadDir = __DIR__ . '/../uploads/complaints/';

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            if (move_uploaded_file($fileTmp, $uploadDir . $newFileName)) {
                $photoPath = 'uploads/complaints/' . $newFileName;
            }
        }
    }

    // ===== Get department_id from selected category =====
    $department_id = null;

    $deptStmt = mysqli_prepare(
        $conn,
        "SELECT department_id FROM categories WHERE id = ?"
    );

    mysqli_stmt_bind_param($deptStmt, "i", $category_id);
    mysqli_stmt_execute($deptStmt);

    $deptResult = mysqli_stmt_get_result($deptStmt);

    if ($deptRow = mysqli_fetch_assoc($deptResult)) {
        $department_id = $deptRow['department_id'];
    }

    mysqli_stmt_close($deptStmt);


    // ===== Duplicate complaint check =====
    $dupCheck = mysqli_prepare(
        $conn,
        "SELECT title, description
         FROM complaints
         WHERE user_id = ?
         AND status IN ('Pending', 'In Progress')"
    );

    mysqli_stmt_bind_param($dupCheck, "i", $user_id);
    mysqli_stmt_execute($dupCheck);

    $dupResult = mysqli_stmt_get_result($dupCheck);

    $isDuplicate = false;

    $newText = strtolower(
        trim($title . ' ' . $description)
    );

    while ($row = mysqli_fetch_assoc($dupResult)) {

        $oldText = strtolower(
            trim($row['title'] . ' ' . $row['description'])
        );

        similar_text(
            $newText,
            $oldText,
            $percent
        );

        if ($percent >= 50) {
            $isDuplicate = true;
            break;
        }
    }

    mysqli_stmt_close($dupCheck);


    // ===== Insert complaint =====
    if ($isDuplicate) {

        $message = "You already have a similar pending complaint. Please wait for it to be resolved, or describe a clearly different issue.";
        $messageType = "error";

    } elseif (
        isset($_FILES['photo']) &&
        $_FILES['photo']['error'] == 0 &&
        $photoPath === null
    ) {

        // File was selected but rejected
        // Do not submit the complaint
        $message = "Invalid file type or file size.";
        $messageType = "error";

    } else {

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO complaints
            (
                user_id,
                department_id,
                category_id,
                title,
                description,
                location,
                priority,
                photo,
                status,
                created_at
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "iiisssss",
            $user_id,
            $department_id,
            $category_id,
            $title,
            $description,
            $location,
            $priority,
            $photoPath
        );

        if (mysqli_stmt_execute($stmt)) {

            $message = "Complaint submitted successfully!";
            $messageType = "success";

        } else {

            $message = "Something went wrong. Please try again.";
            $messageType = "error";
        }

        mysqli_stmt_close($stmt);
    }
}
    
$notifications = [];
$notifStmt = mysqli_prepare($conn, "SELECT id, message, created_at FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC");
mysqli_stmt_bind_param($notifStmt, "i", $user_id);
mysqli_stmt_execute($notifStmt);
$notifResult = mysqli_stmt_get_result($notifStmt);
while ($row = mysqli_fetch_assoc($notifResult)) {
    $notifications[] = $row;
}
mysqli_stmt_close($notifStmt);

// Mark all as read once fetched (simple approach)
if (count($notifications) > 0) {
    $updateStmt = mysqli_prepare($conn, "UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    mysqli_stmt_bind_param($updateStmt, "i", $user_id);
    mysqli_stmt_execute($updateStmt);
    mysqli_stmt_close($updateStmt);
}
// Fetch this user's complaints
$myComplaints = [];
$listStmt = mysqli_prepare($conn, "
    SELECT c.id, c.title, c.description, c.location, c.priority, c.status, c.created_at, c.photo, cat.name AS category_name, d.name AS department_name, f.id AS feedback_id
    FROM complaints c
    LEFT JOIN categories cat ON c.category_id = cat.id
    LEFT JOIN departments d ON c.department_id = d.id
    LEFT JOIN feedback f ON f.complaint_id = c.id
    WHERE c.user_id = ?
    ORDER BY c.created_at DESC
");
mysqli_stmt_bind_param($listStmt, "i", $user_id);
mysqli_stmt_execute($listStmt);
$listResult = mysqli_stmt_get_result($listStmt);
while ($row = mysqli_fetch_assoc($listResult)) {
    $myComplaints[] = $row;
}
mysqli_stmt_close($listStmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Dashboard - Smart Complaint & Service Request Management System</title>
    <style>
        :root {
            --navy: #0D1B2A;
            --deep-navy: #1B263B;
            --steel-blue: #3D5A80;
            --teal: #4B6B7C;
            --airy-blue: #98B4C7;
            --sky-blue: #C8D9E6;
            --pale-blue: #D8E6F2;
            --ice-blue: #EAF2F8;
            --beige: #F2EFE7;
            --white: #FFFFFF;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        body {
            background: var(--ice-blue);
            min-height: 100vh;
        }

        /* ===== Top bar ===== */
        .topbar {
            background: linear-gradient(135deg, var(--navy), var(--steel-blue));
            color: var(--white);
            padding: 16px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .topbar h1 {
            font-size: 18px;
            font-weight: 700;
        }

        .topbar .user-info {
            display: flex;
            align-items: center;
            gap: 14px;
            font-size: 13px;
        }

        .topbar a {
            color: var(--beige);
            text-decoration: none;
            font-weight: 600;
            background: rgba(255,255,255,0.15);
            padding: 6px 14px;
            border-radius: 6px;
            transition: background 0.2s ease;
        }

        .topbar a:hover { background: rgba(255,255,255,0.3); }

        /* ===== Container ===== */
        .container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 1fr;
            gap: 24px;
        }

        @media (min-width: 850px) {
            .container { grid-template-columns: 380px 1fr; align-items: start; }
        }

        /* ===== Cards ===== */
        .card {
            background: var(--white);
            border-radius: 12px;
            padding: 22px;
            box-shadow: 0 6px 20px rgba(13,27,42,0.08);
            border-top: 4px solid var(--steel-blue);
        }

        .card h2 {
            color: var(--navy);
            font-size: 16px;
            margin-bottom: 14px;
        }

        .form-group { margin-bottom: 12px; }

        .form-group label {
            display: block;
            margin-bottom: 4px;
            font-size: 12px;
            color: var(--deep-navy);
            font-weight: 600;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 9px 11px;
            border: 1.5px solid var(--pale-blue);
            border-radius: 6px;
            font-size: 12.5px;
            outline: none;
            background: var(--white);
            color: var(--navy);
            font-family: inherit;
        }

        .form-group textarea { min-height: 80px; resize: vertical; }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--steel-blue);
            box-shadow: 0 0 0 3px rgba(61, 90, 128, 0.18);
        }

        .ai-hint {
            font-size: 11px;
            color: var(--steel-blue);
            margin-top: 4px;
            display: none;
        }

        .ai-hint.show { display: block; }

        .btn-submit {
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 6px;
            background: linear-gradient(135deg, var(--navy), var(--steel-blue));
            color: var(--beige);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 6px;
        }

        .btn-submit:hover { box-shadow: 0 10px 22px rgba(61, 90, 128, 0.35); }

        .message {
            padding: 9px 10px;
            border-radius: 6px;
            font-size: 12px;
            margin-bottom: 14px;
            text-align: center;
        }

        .message.success { background: #e3ebe0; color: #2f6b2f; border: 1px solid #c2d6bd; }
        .message.error { background: #f2e2df; color: #a33a3a; border: 1px solid #e0c1bc; }

        /* ===== Complaint list ===== */
        .complaint-item {
            border-bottom: 1px solid var(--pale-blue);
            padding: 14px 0;
        }
        .complaint-item:last-child { border-bottom: none; }

        .complaint-item .row-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            margin-bottom: 4px;
        }

        .complaint-item h3 {
            font-size: 13.5px;
            color: var(--navy);
        }

        .status-badge {
            font-size: 10.5px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 20px;
            white-space: nowrap;
        }

        .status-Pending { background: #fbe8cf; color: #a3690f; }
        .status-InProgress, .status-In-Progress { background: #d6e6f7; color: #1c5a92; }
        .status-Resolved { background: #dcefdc; color: #2f6b2f; }
        .status-Closed { background: #e2e2e2; color: #555; }

        .complaint-item p {
            font-size: 12px;
            color: var(--teal);
            margin: 2px 0;
        }

        .meta {
            font-size: 11px;
            color: #8896a3;
            margin-top: 4px;
        }

        .empty-state {
            text-align: center;
            color: var(--teal);
            font-size: 12.5px;
            padding: 20px 0;
        }

        /* ===== Notification dropdown ===== */
        .notif-dropdown {
            display: none;
            position: absolute;
            top: 32px;
            right: 0;
            background: var(--white);
            color: var(--navy);
            width: 280px;
            max-height: 320px;
            overflow-y: auto;
            border-radius: 10px;
            box-shadow: 0 12px 30px rgba(13,27,42,0.25);
            font-size: 12px;
            z-index: 100;
            opacity: 0;
            transform: translateY(-8px);
            transition: opacity 0.2s ease, transform 0.2s ease;
        }

        .notif-dropdown.show {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }

        .notif-header {
            background: linear-gradient(135deg, var(--navy), var(--steel-blue));
            color: var(--beige);
            padding: 10px 14px;
            font-weight: 700;
            font-size: 12.5px;
            border-radius: 10px 10px 0 0;
        }

        .notif-empty {
            padding: 16px;
            text-align: center;
            color: var(--teal);
            font-size: 12px;
        }

        .notif-item {
            padding: 10px 14px;
            border-bottom: 1px solid var(--pale-blue);
            transition: background 0.15s ease;
        }

        .notif-item:last-child { border-bottom: none; }

        .notif-item:hover { background: var(--ice-blue); }

        .notif-msg {
            color: var(--deep-navy);
            line-height: 1.4;
        }

        .notif-time {
            color: #8896a3;
            font-size: 10px;
            margin-top: 4px;
        }
    </style>
</head>
<body>

    <div class="topbar">
        <h1>Smart Complaint & Service Request Management System</h1>
        <div class="user-info">
            <span>👋 <?php echo htmlspecialchars($_SESSION['name']); ?></span>

            <span style="position:relative; cursor:pointer;" onclick="document.getElementById('notifDropdown').classList.toggle('show')">
                🔔
                <?php if (count($notifications) > 0) { ?>
                    <span style="position:absolute; top:-6px; right:-8px; background:#c0392b; color:white; font-size:10px; padding:1px 5px; border-radius:50%;"><?php echo count($notifications); ?></span>
                <?php } ?>

                <div id="notifDropdown" class="notif-dropdown">
                    <div class="notif-header">🔔 Notifications</div>
                    <?php if (count($notifications) === 0) { ?>
                        <div class="notif-empty">No new notifications</div>
                    <?php } else { ?>
                        <?php foreach ($notifications as $n) { ?>
                            <div class="notif-item">
                                <div class="notif-msg"><?php echo htmlspecialchars($n['message']); ?></div>
                                <div class="notif-time"><?php echo date('d M, h:i A', strtotime($n['created_at'])); ?></div>
                            </div>
                        <?php } ?>
                    <?php } ?>
                </div>
            </span>

            <a href="../logout.php">Logout</a>
        </div>
    </div>

        <div class="container">

       <?php if (isset($_GET['fb']) && $_GET['fb'] === 'success') { ?>
    <div class="message success" style="grid-column: 1 / -1;">Feedback submitted successfully. Thank you!</div>
    <script>
        window.history.replaceState({}, document.title, window.location.pathname);
    </script>
<?php } ?>

        <!-- Complaint submission form -->
        <div class="card">
            <h2>Submit a New Complaint</h2>

            <?php if ($message) { ?>
                <div class="message <?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php } ?>

<form method="POST" action="index.php" id="complaintForm" enctype="multipart/form-data">

    <div class="form-group">
        <label>Title</label>
        <input type="text" name="title" placeholder="Short title, e.g. Wi-Fi not working" required>
    </div>

    <div class="form-group">
        <label>Description</label>
        <textarea name="description" id="description" placeholder="Describe your issue in detail..." required></textarea>
        <div class="ai-hint" id="aiHint">✨ Suggested category based on your description: <strong id="suggestedCategoryName"></strong></div>
    </div>

    <div class="form-group">
        <label>Category</label>
        <select name="category_id" id="category_id" required>
            <option value="">Select a category</option>
            <?php foreach ($categories as $cat) { ?>
                <option value="<?php echo $cat['id']; ?>" data-name="<?php echo strtolower(htmlspecialchars($cat['name'])); ?>">
                    <?php echo htmlspecialchars($cat['name']); ?>
                </option>
            <?php } ?>
        </select>
    </div>

    <div class="form-group">
        <label>Location</label>
        <input type="text" name="location" placeholder="e.g. Block A, Room 12" required>
    </div>

    <div class="form-group">
        <label>Priority</label>
        <select name="priority" required>
            <option value="Low">Low</option>
            <option value="Medium" selected>Medium</option>
            <option value="High">High</option>
        </select>
    </div>

    <div class="form-group">
        <label>Attach Photo (optional)</label>
<input type="file" name="photo" accept=".jpg,.jpeg,.png,.gif">    </div>

    <button type="submit" class="btn-submit">Submit Complaint</button>
</form>
        </div>

        <!-- My complaints list -->
        <div class="card">
            <h2>My Complaints</h2>

            <?php if (count($myComplaints) === 0) { ?>
                <div class="empty-state">You haven't submitted any complaints yet.</div>
            <?php } else { ?>
                <?php foreach ($myComplaints as $c) { ?>
                    <div class="complaint-item">
                        <div class="row-top">
                            <h3><?php echo htmlspecialchars($c['title']); ?></h3>
              <span class="status-badge status-<?php echo str_replace(' ', '', htmlspecialchars($c['status'])); ?>" id="status-badge-<?php echo $c['id']; ?>" data-id="<?php echo $c['id']; ?>">                                <?php echo htmlspecialchars($c['status']); ?>
                            </span>
                        </div>
                        <p><?php echo htmlspecialchars($c['description']); ?></p>
                        <?php if (!empty($c['photo'])) { ?> <img src="../<?php echo htmlspecialchars($c['photo']); ?>" alt="Complaint photo" style="max-width:100%; border-radius:8px; margin-top:8px; display:block;"> <?php } ?>
                                               <div class="meta">
                            📁 <?php echo htmlspecialchars($c['category_name'] ?? 'Uncategorized'); ?> ·
                            🏢 <?php echo htmlspecialchars($c['department_name'] ?? '—'); ?> ·
                            📍 <?php echo htmlspecialchars($c['location']); ?> ·
                            ⚡ <?php echo htmlspecialchars($c['priority']); ?> ·

                            🗓️ <?php echo date('d M Y, h:i A', strtotime($c['created_at'])); ?>
                        </div>
                        <?php
                        $st = strtolower($c['status']);
                        if ($st === 'resolved' || $st === 'closed') {
                            if (!empty($c['feedback_id'])) {
                                echo '<div style="margin-top:8px;color:#2f6b2f;font-size:12px;">✔ Feedback given</div>';
                            } else {
                                echo '<a href="feedback.php?id=' . $c['id'] . '" style="display:inline-block;margin-top:8px;background:#f5b301;color:#000;padding:5px 12px;border-radius:6px;text-decoration:none;font-size:12px;">⭐ Give Feedback</a>';
                            }
                        }
                        ?>
                    </div>
                <?php } ?>
            <?php } ?>
        </div>

    </div>
<script>
        // ===== Score-based rule-matching AI category suggestion =====
        // Har category ke against comprehensive keyword list.
        // Jis category ke sabse zyada keywords text mein match hon, wahi select hogi.
        const categoryKeywords = {
            "IT": [
                "internet", "wifi", "wi-fi", "wi fi", "network", "computer", "laptop",
                "software", "printer", "system", "login", "password", "server",
                "email", "website", "portal", "app", "application", "connection",
                "browser", "download", "upload", "virus", "hang", "crash", "screen",
                "keyboard", "mouse", "monitor", "lan", "router", "signal", "id card scanner"
            ],
            "Electrical": [
                "fan", "electricity", "light", "bulb", "wiring", "socket", "ac",
                "air conditioner", "power", "switch", "voltage", "current", "wire",
                "shock", "generator", "ups", "breaker", "circuit", "outlet", "plug",
                "tube light", "electric", "short circuit"
            ],
            "Maintenance": [
                "leak", "leakage", "water", "door", "chair", "desk", "window",
                "furniture", "wall", "ceiling", "broken", "crack", "paint",
                "pipe", "plumbing", "tap", "roof", "floor", "tile", "lock",
                "hinge", "damaged", "repair needed", "table"
            ],
            "Transport": [
                "bus", "van", "transport", "driver", "vehicle", "route", "shuttle",
                "pickup", "drop", "seat belt", "traffic", "parking spot", "fuel",
                "delay in bus", "late bus", "conductor"
            ],
            "Cleaning": [
                "clean", "dirty", "garbage", "trash", "washroom", "toilet", "dust",
                "sweep", "mop", "smell", "unhygienic", "waste", "litter", "stink",
                "bathroom", "sink", "hygiene", "cleaner", "sanitation"
            ],
            "Security": [
                "theft", "guard", "unsafe", "security", "camera", "cctv", "footage",
                "parking area", "stranger", "gate", "stolen", "missing item",
                "harassment", "fight", "intruder", "id card", "entry", "alarm",
                "fire alarm", "emergency exit", "lost"
            ],
            "Accounts": [
                "fee", "payment", "invoice", "refund", "salary", "bill", "receipt",
                "voucher", "scholarship", "due", "challan", "transaction", "account",
                "overcharge", "installment", "tuition", "money"
            ]
        };

        const descriptionBox = document.getElementById('description');
        const categorySelect = document.getElementById('category_id');
        const aiHint = document.getElementById('aiHint');
        const suggestedName = document.getElementById('suggestedCategoryName');

        function suggestCategory() {
            const rawText = descriptionBox.value.toLowerCase();
            // Punctuation hata kar clean text banate hain taake matching behtar ho
            const text = rawText.replace(/[.,!?;:()]/g, ' ');

            if (text.trim().length < 4) {
                aiHint.classList.remove('show');
                categorySelect.value = "";
                return;
            }

            // Har category ka score calculate karo (kitne keywords match hue)
            let bestCategory = null;
            let bestScore = 0;

            for (const category in categoryKeywords) {
                let score = 0;
                for (const keyword of categoryKeywords[category]) {
                    if (text.includes(keyword)) {
                        // Lamba/zyada specific keyword zyada weight leta hai
                        score += keyword.length >= 6 ? 2 : 1;
                    }
                }
                if (score > bestScore) {
                    bestScore = score;
                    bestCategory = category;
                }
            }

            if (bestCategory && bestScore > 0) {
                const options = categorySelect.querySelectorAll('option');
                let found = false;
                options.forEach(opt => {
                    if (opt.dataset.name === bestCategory.toLowerCase()) {
                        categorySelect.value = opt.value;
                        found = true;
                    }
                });

                if (found) {
                    suggestedName.textContent = bestCategory;
                    aiHint.classList.add('show');
                } else {
                    aiHint.classList.remove('show');
                }
            } else {
                // Koi keyword match nahi hua -> dropdown reset karo
                categorySelect.value = "";
                aiHint.classList.remove('show');
            }
        }

        descriptionBox.addEventListener('input', suggestCategory);

        // ===== Live status polling (AJAX) =====
        function checkStatusUpdates() {
            fetch('status_check.php')
                .then(res => res.json())
                .then(data => {
                    for (const complaintId in data) {
                        const newStatus = data[complaintId];
                        const badge = document.getElementById('status-badge-' + complaintId);
                        if (badge) {
                            const cleanStatus = newStatus.replace(/\s/g, '');
                            if (!badge.classList.contains('status-' + cleanStatus)) {
                                badge.className = 'status-badge status-' + cleanStatus;
                                badge.textContent = newStatus;
                            }
                        }
                    }
                })
                .catch(err => console.error('Status check failed:', err));
        }

        // Check every 8 seconds
        setInterval(checkStatusUpdates, 8000);
    </script>
</body>
</html>