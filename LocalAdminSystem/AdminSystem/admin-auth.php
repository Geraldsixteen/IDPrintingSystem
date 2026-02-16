<?php
session_start();

// Pages that don't require login
$public_pages = ['login.php', 'admin-register.php', 'reset-password.php'];

$current_page = basename($_SERVER['PHP_SELF']);

// Redirect to login if not logged in and not a public page
if (!in_array($current_page, $public_pages) && !isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
?>
