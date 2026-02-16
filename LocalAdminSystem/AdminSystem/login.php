<?php
session_start();
require_once __DIR__ . '/../Config/database.php';

$err = "";

// Handle POST login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if (!$username || !$password) {
        $err = "Please fill in both fields.";
    } else {
        $stmt = $pdo->prepare("SELECT id, password FROM admins WHERE username = :username");
        $stmt->execute(['username' => $username]);
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (password_verify($password, $row['password'])) {
                session_regenerate_id(true);
                $_SESSION['admin_id'] = $row['id'];
                $_SESSION['admin_username'] = $username;

                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    setcookie('remember_username', $username, time() + 86400*30, "/");
                    setcookie('remember_token', $token, time() + 86400*30, "/");

                    $stmtUpdate = $pdo->prepare("UPDATE admins SET remember_token = :token WHERE id = :id");
                    $stmtUpdate->execute(['token' => $token, 'id' => $row['id']]);
                } else {
                    // Clear previous cookies & token
                    setcookie('remember_username', '', time() - 3600, "/");
                    setcookie('remember_token', '', time() - 3600, "/");
                    $stmtUpdate = $pdo->prepare("UPDATE admins SET remember_token = NULL WHERE id = :id");
                    $stmtUpdate->execute(['id' => $row['id']]);
                }

                header("Location: index.php");
                exit;
            } else {
                $err = "Invalid username or password.";
            }
        } else {
            $err = "Invalid username or password.";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
<style>
body {
    background: #285599;
    margin: 0;
    padding: 0;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    font-family: Arial, sans-serif;
}
form ul {
    background: #ececec;
    border-radius: 5px;
    padding: 3em 2em;
    box-shadow: 8px 8px 10px rgba(0,0,0,.3);
    list-style: none;
    margin: 0;
    width: 350px;
    text-align: center;
}
form ul img { display: block; margin: 0 auto 1em auto; width: 80px; height: 100px; }
form ul h3 { text-align: center; color: #001d43; margin-bottom: 20px; }
#username, #password { padding: 10px; width: 80%; font-size: 16px; border: 1px solid #b4b4b4; border-radius: 3px; margin-top: 10px; outline: none; }
#Login { background: #001d43; width: 100%; padding: 10px; font-size: 18px; border: none; border-radius: 3px; font-weight: bold; color: #fff; cursor: pointer; transition: .2s ease; }
#Login:hover { background: rgba(0, 29, 67, 0.8); }
.error { color: red; margin-bottom: 10px; }
.success { color: green; margin-bottom: 10px; }
a { text-decoration: none; color: #001d43; display: block; margin-top: 10px; }
a:hover { text-decoration: underline; }
li { margin-bottom: 10px; }
li i { margin-right: 8px; color: #285599; }
#remember { margin-right: 5px; }
</style>
</head>
<body>

<form method="post">
    <ul>
        <img src="../cdlb.png" alt="Logo">
        <h3>Admin Login</h3>

        <?php if ($err): ?>
            <div class="error"><?= htmlspecialchars($err) ?></div>
        <?php endif; ?>

        <li>
            <i class="fas fa-user"></i>
            <input type="text" name="username" id="username" placeholder="Admin Username" value="<?= htmlspecialchars($_COOKIE['remember_username'] ?? '') ?>" required>
        </li>

        <li>
            <i class="fas fa-lock"></i>
            <input type="password" name="password" id="password" placeholder="Password" required>
        </li>

        <li>
            <input type="checkbox" name="remember" id="remember" <?= isset($_COOKIE['remember_username']) ? 'checked' : '' ?>>
            <label for="remember">Remember Me</label>
        </li>

        <li>
            <button type="submit" id="Login">Login</button>
        </li>

        <a href="reset-password.php">Forgot password?</a>
        <a href="admin-register.php">Register first</a>
    </ul>
</form>

</body>
</html>
