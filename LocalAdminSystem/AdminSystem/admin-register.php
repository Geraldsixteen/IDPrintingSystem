<?php
require_once __DIR__ . '/../Config/database.php';

$err = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    // ================= VALIDATION =================
    if ($username === '') {
        $err = "Username required.";
    } elseif (strlen($username) < 3) {
        $err = "Username must be at least 3 characters.";
    } elseif (strlen($password) < 6) {
        $err = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm) {
        $err = "Passwords do not match.";
    }

    if (!$err) {
        try {

            // LIMIT ADMIN COUNT
            $count = $pdo->query("SELECT COUNT(*) FROM admins")->fetchColumn();

            if ($count >= 3) {
                $err = "Maximum of 3 admins allowed.";
            } else {

                // CHECK DUPLICATE USERNAME
                $check = $pdo->prepare("SELECT id FROM admins WHERE username=?");
                $check->execute([$username]);

                if ($check->fetch()) {
                    $err = "Username already exists.";
                } else {

                    // CREATE ADMIN
                    $hash = password_hash($password, PASSWORD_DEFAULT);

                    $insert = $pdo->prepare("INSERT INTO admins (username,password) VALUES (?,?)");

                    if ($insert->execute([$username,$hash])) {
                        $success = "Admin created successfully.";
                        $_POST = []; // clear form
                    } else {
                        $err = "Registration failed.";
                    }
                }
            }

        } catch (PDOException $e) {
            $err = "Database error.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Admin Register</title>

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

button{
width:100%;
padding:10px;
background:#001d43;
color:white;
border:none;
font-weight:bold;
}

ul img { display: block; margin: 0 auto 1em auto; width: 80px; height: 100px; }

.login{text-decoration:none; color:#001d43; display:block; margin-top:10px;}
.login:hover { text-decoration: underline; }

.error{color:red}
.success{color:green}
</style>
</head>

<body>

<form method="post" autocomplete="off">

<ul>
<img src="../cdlb.png" alt="Logo">
<h3>Admin Registration</h3>

<?php if($err): ?><div class="error"><?= $err ?></div><?php endif; ?>
<?php if($success): ?><div class="success"><?= $success ?></div><?php endif; ?>

<input type="text" name="username" placeholder="Username" autocomplete="off" required>

<input type="password" name="password" placeholder="Password" autocomplete="new-password" required>

<input type="password" name="confirm_password" placeholder="Confirm Password" autocomplete="new-password" required>

<button>Register</button>

<a class="login" href="login.php">Back to Login</a>

</ul>

</form>

</body>
</html>
