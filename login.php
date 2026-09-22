<?php
session_start();
include 'db.php';

$message = "";
$messageType = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Look up user by email (prepared statement)
    $stmt = mysqli_prepare($conn, "SELECT id, name, email, password, role FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        if (password_verify($password, $row['password'])) {
            // Correct password - start session
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['name']    = $row['name'];
            $_SESSION['email']   = $row['email'];
            $_SESSION['role']    = $row['role'];

            // Redirect based on role
            if ($row['role'] == 'admin') {
           header("Location: admin/dashboard.php");
                 exit();
            } else {
                header("Location: user/index.php");
                exit();
            }
        } else {
            $message = "Incorrect password. Please try again.";
            $messageType = "error";
        }
    } else {
        $message = "No account found with this email.";
        $messageType = "error";
    }
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Smart Complaint & Service Request Management System</title>
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

        body::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(13,27,42,0.35) 0%, rgba(27,38,59,0.20) 45%, rgba(13,27,42,0.45) 100%);
            z-index: 0;
        }

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

        .btn-login {
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

        .btn-login:hover {
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

        .register-link {
            text-align: center;
            margin-top: 14px;
            font-size: 11px;
            color: var(--teal);
        }

        .register-link a {
            color: var(--navy);
            text-decoration: none;
            font-weight: 700;
        }
        .register-link a:hover { color: var(--steel-blue); }

        @media (max-width: 640px) {
            .hero-text h1 { font-size: 19px; }
            .form-card { max-width: 320px; }
        }
    </style>
</head>
<body>

    <div class="hero-text">
        <h1>Smart Complaint &amp; Service Request Management System</h1>
        <p>Submit, track and resolve complaints — all in real time.</p>
    </div>

    <div class="form-card">
        <h2>Welcome Back</h2>
        <p class="subtitle">Log in to continue</p>

        <?php if ($message) { ?>
            <div class="message <?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php } ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="you@example.com" required>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Enter your password" required>
            </div>

            <button type="submit" class="btn-login">Login</button>
        </form>

        <p class="register-link">Don't have an account? <a href="register.php">Register here</a></p>
    </div>

</body>
</html>