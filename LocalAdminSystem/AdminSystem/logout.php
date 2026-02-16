<?php
session_start();
require_once __DIR__ . '/../Config/database.php';

// Clear remember-me cookies
setcookie('remember_username', '', time() - 3600, "/");
setcookie('remember_token', '', time() - 3600, "/");

// Clear token in DB
if (isset($_SESSION['admin_id'])) {
    $stmt = $pdo->prepare("UPDATE admins SET remember_token = NULL WHERE id = :id");
    $stmt->execute(['id' => $_SESSION['admin_id']]);
}

// Destroy session
$_SESSION = [];
session_destroy();

// Redirect to login
header("Location: login.php");
exit;
