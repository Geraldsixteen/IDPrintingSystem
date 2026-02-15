<?php
// require_once __DIR__ . '/admin-auth.php';
require_once __DIR__ . '/../Config/database.php';

$err = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validate username
    if (empty($username)) {
        $err = "Username is required.";
    } elseif (strlen($username) < 3) {
        $err = "Username must be at least 3 characters long.";
    }

    // Validate password
    if (!$err && $password !== $confirmPassword) {
        $err = "Passwords do not match.";
    } elseif (!$err && strlen($password) < 6) {
        $err = "Password must be at least 6 characters long.";
    }

    if (!$err) {
        try {
            // Check total admins
            $stmtTotal = $pdo->query("SELECT COUNT(*) AS total FROM admins");
            $totalRow = $stmtTotal->fetch();

            if ($totalRow['total'] >= 3) {
                $err = "Only 3 admin accounts are allowed.";
            } else {
                // Check if username already exists
                $stmtCheck = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
                $stmtCheck->execute([$username]);

                if ($stmtCheck->rowCount() > 0) {
                    $err = "That username is already taken.";
                } else {
                    // Insert new admin
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    $stmtInsert = $pdo->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
                    if ($stmtInsert->execute([$username, $hashed])) {
                        $success = "✅ Admin registration successful! You can now login.";
                    } else {
                        $err = "Database error while creating admin.";
                    }
                }
            }
        } catch (PDOException $e) {
            $err = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Registration</title>
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
    text-align: center;
}
#username, #password, #confirm_password {
    padding: 10px;
    width: 100%;
    font-size: 18px;
    border: 1px solid rgb(180, 180, 180);
    margin-bottom: 15px;
    outline: none;
    border-radius: 3px;
}
#Register {
    background: rgb(0, 29, 67);
    padding: 10px;
    width: 100%;
    font-size: 20px;
    border: none;
    border-radius: 3px;
    font-weight: bold;
    color: #fff;
    transition: .2s ease;
    cursor: pointer;
}
#Register:hover {
    background: rgba(0, 29, 67, 0.8);
}
ul img {
    display: block;
    margin: 0 auto 1em auto;
    width: 80px;
    height: 100px;
}
.error { color: red; margin-bottom: 10px; }
.success { color: green; margin-bottom: 10px; }
a { font-family: Arial, Helvetica, sans-serif; text-decoration: none; color: black; display: block; text-align: center; margin-top: 10px; }
a:hover { text-decoration: underline; }
li i { margin-right: 8px; color: rgb(40, 85, 153); }
</style>
</head>
<body>

<form method="POST">
  <ul>
    <img src="../cdlb.png" alt="Logo">
    <h3>Admin Registration</h3>

    <?php if ($err): ?>
      <div class="error"><?= htmlspecialchars($err) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <li>
        <i class="fas fa-user"></i>
        <input type="text" name="username" id="username" placeholder="Username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
    </li>
    <li>
        <i class="fas fa-lock"></i>
        <input type="password" name="password" id="password" placeholder="Password" required>
    </li>
    <li>
        <i class="fas fa-lock"></i>
        <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password" required>
    </li>
    <li>
        <button type="submit" id="Register">Register</button>
    </li>
    <a href="login.php">Already have an account? Login</a>
  </ul>
</form>

</body>
</html>
