<?php
session_start();

// List of pages that do NOT require login
$public_pages = ['login.php', 'admin-register.php', 'reset-password.php'];

// Current page filename
$current_page = basename($_SERVER['PHP_SELF']);

// =======================
// 1️⃣ Force session login
// =======================
if (!in_array($current_page, $public_pages)) {

    // If session is missing, redirect to login
    if (!isset($_SESSION['admin_id'])) {

        // Clear any stale remember-me cookies
        setcookie('remember_username', '', time() - 3600, "/");
        setcookie('remember_token', '', time() - 3600, "/");

        // Optionally destroy session as extra safety
        $_SESSION = [];
        session_destroy();

        header("Location: login.php");
        exit;
    }
}

// =======================
// 2️⃣ Optional: Prevent old cookies auto-login
// =======================
// This means login.php should not auto-login from cookies automatically unless session is created
// Remove or comment out auto-login by cookies if you want stricter control
