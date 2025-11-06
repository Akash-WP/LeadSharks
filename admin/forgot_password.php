<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

require_once __DIR__ . '/../initialize.php'; // DB constants
require __DIR__ . '/../vendor/autoload.php'; // PHPMailer
require_once __DIR__ . '/../classes/master.php'; // Include Master class
require_once('../config.php') 

$Master = new Master(); // Instantiate Master
$message = '';
$showOtpField = false;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($mysqli->connect_error) die("DB Connection failed: " . $mysqli->connect_error);

$message = '';
$showOtpField = false;

// Step 1: Send OTP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_otp') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';

    if (!$username || !$email) $message = "Username and Email required.";
    else {
        $stmt = $mysqli->prepare("SELECT id FROM users WHERE username=? AND email=? LIMIT 1");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if (!$user) $message = "Username or email not found.";
        else {
            $otp = random_int(100000, 999999);
            $otp_hash = password_hash($otp, PASSWORD_DEFAULT);
            $expires_at = (new DateTime('+10 minutes'))->format('Y-m-d H:i:s');

            $stmt = $mysqli->prepare("INSERT INTO password_otps (user_id, otp_hash, expires_at, used) VALUES (?,?,?,0)");
            $stmt->bind_param("iss", $user['id'], $otp_hash, $expires_at);
            $stmt->execute();

            // Send OTP via email
            $subject = 'Password Reset OTP';
            $body = "<p>Your OTP is: <strong>$otp</strong> (expires in 10 minutes)</p>";
            if ($Master->send_email($email, $subject, $body)) {
                $showOtpField = true;
                $_SESSION['otp_user_id'] = $user['id'];
                $_SESSION['otp_email'] = $email;
                $message = "OTP sent to your email!";
            } else {
                $message = "Failed to send OTP. Please try again.";
            }
        }
    }
}

// Step 2: Verify OTP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'verify_otp') {
    $otp = $_POST['otp'] ?? '';
    $user_id = $_SESSION['otp_user_id'] ?? 0;

    $stmt = $mysqli->prepare("SELECT * FROM password_otps WHERE user_id=? AND used=0 ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $otp_row = $stmt->get_result()->fetch_assoc();

    if (!$otp_row) $message = "No OTP found, resend.";
    elseif (!password_verify($otp, $otp_row['otp_hash'])) $message = "Invalid OTP.";
    elseif (new DateTime() > new DateTime($otp_row['expires_at'])) $message = "OTP expired.";
    else {
        $stmt = $mysqli->prepare("UPDATE password_otps SET used=1 WHERE id=?");
        $stmt->bind_param("i", $otp_row['id']);
        $stmt->execute();
        $_SESSION['reset_user_id'] = $user_id;
        header("Location: reset_password.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .forgot-card {
            max-width: 400px;
            margin: 80px auto;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            background-color: #fff;
        }

        .forgot-card h4 {
            margin-bottom: 20px;
            font-weight: 600;
            text-align: center;
        }
    </style>
</head>

<body>

    <div class="forgot-card">
        <h4>Forgot Password</h4>

        <?php if ($message): ?>
            <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="POST">
            <?php if (!$showOtpField): ?>
                <input type="hidden" name="action" value="send_otp">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-control" name="username" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Send OTP</button>
            <?php else: ?>
                <input type="hidden" name="action" value="verify_otp">
                <div class="mb-3">
                    <label class="form-label">Enter OTP</label>
                    <input type="text" class="form-control" name="otp" maxlength="6" required>
                </div>
                <button type="submit" class="btn btn-success w-100">Verify OTP</button>
            <?php endif; ?>
        </form>

        <div class="mt-3 text-center">
            <a href="login.php">Back to Login</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>