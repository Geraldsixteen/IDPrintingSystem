<?php 
//require_once __DIR__ . '/admin-auth.php';
require_once __DIR__ . '/../Config/database.php';

$err = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($username) || empty($newPassword) || empty($confirmPassword)) {
        $err = "All fields are required.";
    } elseif ($newPassword !== $confirmPassword) {
        $err = "Passwords do not match.";
    } elseif (strlen($newPassword) < 6) {
        $err = "Password must be at least 6 characters long.";
    } else {
        // Check if username exists
        $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = :username");
        $stmt->execute(['username' => $username]);
        $userExists = $stmt->fetch();

        if (!$userExists) {
            $err = "The username you entered is not registered.";
        } else {
            // Update password
            $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt2 = $pdo->prepare("UPDATE admins SET password = :password WHERE username = :username");
            $exec = $stmt2->execute([
                'password' => $hashed,
                'username' => $username
            ]);

            if ($exec) {
                $success = "✅ Password reset successful! You can now login.";
            } else {
                $err = "Database error: Could not update password.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Reset Password</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">

<style>
body {
    background: rgb(40, 85, 153);
    margin: 0;
    padding: 0;
    display: flex;
    justify-content: center;
    height: 100vh;
    align-items: center;
    font-family: Arial, sans-serif;
}
ul {
    background: rgb(236, 236, 236);
    border-radius: 5px;
    padding: 3em;
    box-shadow: 8px 8px 10px rgba(0,0,0,.3);
    list-style: none;
    margin: 0;
    width: 100%;
    max-width: 400px;
}
#username, #NewPassword, #ConfirmPassword {
    padding: 10px;
    width: 100%;
    font-size: 18px;
    border: 1px solid rgb(180, 180, 180);
    margin-bottom: 15px;
    outline: none;
    border-radius: 3px;
}
#Reset {
    background: rgb(0, 29, 67);
    padding: 10px;
    width: 100%;
    font-size: 20px;
    border: none;
    border-radius: 3px;
    font-weight: bold;
    color: #fff;
    cursor: pointer;
}
#Reset:hover {
    background: rgba(0, 29, 67, 0.8);
}
ul img {
    display: block;
    margin: 0 auto 1em auto;
    width: 80px;
    height: 100px;
}
.error {
    color: red;
    margin-bottom: 10px;
    text-align: center;
}
.success {
    color: green;
    margin-bottom: 10px;
    text-align: center;
}
a {
    font-family: Arial, Helvetica, sans-serif;
    text-decoration: none;
    color: black;
    display: block;
    text-align: center;
    margin-top: 10px;
}
a:hover {
    text-decoration: underline;
}
li i {
    margin-right: 8px;
    color: rgb(40, 85, 153);
}
</style>
</head>
<body>

<form method="POST">
  <ul>
    <img src="../cdlb.png" alt="Logo">
    <h3 style="text-align:center;">Forgot Password</h3>

    <?php if ($err): ?>
      <div class="error"><?= htmlspecialchars($err) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <li>
        <i class="fas fa-user"></i>
        <input type="text" name="username" id="username" placeholder="Your Username" required>
    </li>
    <li>
        <i class="fas fa-lock"></i>
        <input type="password" name="new_password" id="NewPassword" placeholder="New Password" required>
    </li>
    <li>
        <i class="fas fa-lock"></i>
        <input type="password" name="confirm_password" id="ConfirmPassword" placeholder="Confirm New Password" required>
    </li>
    <li>
        <button type="submit" id="Reset">Reset Password</button>
    </li>
    <a href="login.php">Back to Login</a>
  </ul>
</form>

</body>
</html>
