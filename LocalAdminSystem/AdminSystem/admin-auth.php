<?php
session_start();
require_once __DIR__ . '/../Config/database.php';

// Pages that don't require login
$public_pages = ['login.php', 'admin-register.php', 'reset-password.php'];
$current_page = basename($_SERVER['PHP_SELF']);

// If not logged in, check Remember Me cookies
if (!isset($_SESSION['admin_id'])) {
    if (!empty($_COOKIE['remember_username']) && !empty($_COOKIE['remember_token'])) {
        $username = $_COOKIE['remember_username'];
        $token = $_COOKIE['remember_token'];

        // Validate token against DB
        $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = :username AND remember_token = :token");
        $stmt->execute(['username' => $username, 'token' => $token]);
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Token valid → log in user
            $_SESSION['admin_id'] = $row['id'];
            $_SESSION['admin_username'] = $username;
        } else {
            // Invalid token → clear cookies
            setcookie('remember_username', '', time() - 3600, "/");
            setcookie('remember_token', '', time() - 3600, "/");
        }
    }
}

// Redirect to login if not logged in and page is not public
if (!in_array($current_page, $public_pages) && !isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
