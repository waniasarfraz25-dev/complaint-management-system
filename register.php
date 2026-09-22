<?php
include 'db.php';

$message = "";
$messageType = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name  = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check if email already exists (prepared statement)
    $check = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
    mysqli_stmt_bind_param($check, "s", $email);
    mysqli_stmt_execute($check);
    mysqli_stmt_store_result($check);

    if (mysqli_stmt_num_rows($check) > 0) {
        $message = "An account with this email already exists!";
        $messageType = "error";
    } else {
        // Insert new user (prepared statement - prevents SQL injection)
        $stmt = mysqli_prepare($conn, "INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, 'user')");
        mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $phone, $password);

        if (mysqli_stmt_execute($stmt)) {
            $message = "Account created successfully! You can now log in.";
            $messageType = "success";
        } else {
            $message = "Something went wrong. Please try again.";
            $messageType = "error";
        }
        mysqli_stmt_close($stmt);
    }
    mysqli_stmt_close($check);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Smart Complaint & Service Request Management System</title>
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

        html, body { height: 100%; }

        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            background-image: url('assets/hero-bg.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            padding: 40px 20px;
            overflow: hidden;
        }

        /* soft overlay for contrast - photo still fully visible underneath */
        body::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(13,27,42,0.35) 0%, rgba(27,38,59,0.20) 45%, rgba(13,27,42,0.45) 100%);
            z-index: 0;
        }

        /* ===== Small brand header above the form (kept compact so the photo stays the star) ===== */
        .hero-text {
            position: relative;
            z-index: 2;
            text-align: center;
            max-width: 480px;
            margin-bottom: 18px;
        }

        .hero-text h1 {
            font-size: 24px;
            font-weight: 700;
            color: var(--white);
            text-shadow: 0 2px 12px rgba(0,0,0,0.55);
            line-height: 1.3;
            margin-bottom: 6px;
        }

        .hero-text p {
            font-size: 13px;
            color: var(--pale-blue);
            text-shadow: 0 1px 8px rgba(0,0,0,0.55);
        }

        /* ===== Compact centered form card - narrow on purpose so the photo (person + admin) stays visible on both sides ===== */
        .form-card {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 360px;
            background: rgba(242, 239, 231, 0.93);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 14px;
            padding: 26px 24px;
            box-shadow: 0 20px 50px rgba(13, 27, 42, 0.55);
            border-top: 4px solid var(--steel-blue);
            animation: fadeIn 0.7s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-card h2 {
            color: var(--navy);
            margin-bottom: 3px;
            font-size: 18px;
            font-weight: 700;
        }

        .subtitle {
            color: var(--steel-blue);
            font-size: 11.5px;
            margin-bottom: 16px;
        }

        .form-group { margin-bottom: 10px; }

        .form-group label {
            display: block;
            margin-bottom: 3px;
            font-size: 11px;
            color: var(--deep-navy);
            font-weight: 600;
        }

        .form-group input {
            width: 100%;
            padding: 8px 11px;
            border: 1.5px solid var(--pale-blue);
            border-radius: 6px;
            font-size: 12.5px;
            outline: none;
            background: var(--white);
            color: var(--navy);
            transition: all 0.25s ease;
        }

        .form-group input::placeholder { color: #9AA5B1; }

        .form-group input:focus {
            border-color: var(--steel-blue);
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(61, 90, 128, 0.18);
        }

        .btn-register {
            width: 100%;
            padding: 9px;
            border: none;
            border-radius: 6px;
            background: linear-gradient(135deg, var(--navy), var(--steel-blue));
            color: var(--beige);
            font-size: 12.5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 4px;
            letter-spacing: 0.3px;
        }

        .btn-register:hover {
            box-shadow: 0 10px 22px rgba(61, 90, 128, 0.35);
            transform: translateY(-2px);
        }

        .message {
            padding: 8px 10px;
            border-radius: 6px;
            font-size: 11px;
            margin-bottom: 12px;
            text-align: center;
        }

        .message.success { background: #e3ebe0; color: #2f6b2f; border: 1px solid #c2d6bd; }
        .message.error { background: #f2e2df; color: #a33a3a; border: 1px solid #e0c1bc; }

        .login-link {
            text-align: center;
            margin-top: 14px;
            font-size: 11px;
            color: var(--teal);
        }

        .login-link a {
            color: var(--navy);
            text-decoration: none;
            font-weight: 700;
        }
        .login-link a:hover { color: var(--steel-blue); }

        @media (max-width: 640px) {
            .hero-text h1 { font-size: 19px; }
            .form-card { max-width: 320px; }
        }
    </style>
</head>
<body>

    <!-- Compact brand header - kept small so the photo remains the visual focus -->
    <div class="hero-text">
        <h1>Smart Complaint &amp; Service Request Management System</h1>
        <p>Submit, track and resolve complaints — all in real time.</p>
    </div>

    <!-- Narrow centered form - deliberately compact so the photo stays visible on both sides -->
    <div class="form-card">
        <h2>Create Account</h2>
        <p class="subtitle">Sign up to submit and track your complaints</p>

        <?php if ($message) { ?>
            <div class="message <?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php } ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" placeholder="Enter your name" required>
            </div>

            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="you@example.com" required>
            </div>

            <div class="form-group">
                <label>Phone Number</label>
                <input type="text" name="phone" placeholder="03XX-XXXXXXX" required>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Create a password" required>
            </div>

            <button type="submit" class="btn-register">Register</button>
        </form>

        <p class="login-link">Already have an account? <a href="login.php">Log in here</a></p>
    </div>

</body>
</html>