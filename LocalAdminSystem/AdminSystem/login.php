<?php
session_start();
require_once __DIR__ . '/../Config/database.php';

$err = "";

// Handle POST login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

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

                // Force clear any old cookies
                setcookie('remember_username', '', time() - 3600, "/");
                setcookie('remember_token', '', time() - 3600, "/");

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
body{
background:#285599;
display:flex;
justify-content:center;
align-items:center;
height:100vh;
font-family:Arial;
}

ul{
background:#ececec;
padding:3em;
width:350px;
border-radius:5px;
list-style:none;
text-align:center;
}

input{
width:100%;
padding:10px;
margin-bottom:15px;
}
form ul img { display: block; margin: 0 auto 1em auto; width: 80px; height: 100px; }
form ul h3 { text-align: center; color: #001d43; margin-bottom: 20px; }
button{
width:100%;
padding:10px;
background:#001d43;
color:white;
border:none;
font-weight:bold;
}
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

<form method="post" autocomplete="off">
    <ul>
        <img src="../cdlb.png" alt="Logo">
        <h3>Admin Login</h3>

        <?php if ($err): ?>
            <div class="error"><?= htmlspecialchars($err) ?></div>
        <?php endif; ?>

        <li>
            <input type="text" name="username" id="username" placeholder="Admin Username" required>
        </li>

        <li>
            <input type="password" name="password" id="password" placeholder="Password" required>
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
