<?php
require_once('../config.php');
if (!isset($_GET['token'])) die('Invalid request');
$token = $_GET['token'];
$qry = $conn->query("SELECT * FROM users WHERE reset_token = '{$token}' AND token_expiry > NOW()");
if ($qry->num_rows == 0) die('Invalid or expired token');
?>
<!DOCTYPE html>
<html>
<head><title>Reset Password</title></head>
<body>
<form method="post">
  <input type="hidden" name="token" value="<?= $token ?>">
  <label>New Password</label>
  <input type="password" name="new_password" required><br>
  <label>Confirm Password</label>
  <input type="password" name="confirm_password" required><br>
  <button type="submit">Save</button>
</form>
</body>
</html>
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  extract($_POST);
  if ($new_password !== $confirm_password) {
    echo "Passwords do not match.";
    exit;
  }
  $hash = md5($new_password);
  $conn->query("UPDATE users SET password = '{$hash}', reset_token = NULL, token_expiry = NULL WHERE reset_token = '{$token}'");
  echo "<script>alert('Password updated');location.href='login.php';</script>";
}
?>
