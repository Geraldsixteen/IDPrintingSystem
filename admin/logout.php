<?php
require_once __DIR__ . '/admin-auth.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// ================= PostgreSQL Connection =================
require_once __DIR__ . '/../config/database.php';

// ========================================================

// Clear remember-me cookies
setcookie('remember_username', '', time() - 3600, "/");
setcookie('remember_token', '', time() - 3600, "/");

// Clear token in DB if admin is logged in
if (isset($_SESSION['admin_id'])) {
    $id = $_SESSION['admin_id'];

    $stmt = $pdo->prepare("UPDATE admins SET remember_token = NULL WHERE id = :id");
    $stmt->execute(['id' => $id]);
}

// Clear all session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// Redirect back to login page
header("Location: login.php");
exit;
?>
