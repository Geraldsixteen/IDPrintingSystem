<?php
session_start();
require_once __DIR__ . '/../Config/database.php';

// Pages that don't require login
$public_pages = ['login.php', 'admin-register.php', 'reset-password.php'];
$current_page = basename($_SERVER['PHP_SELF']);

// Redirect to login if not logged in and page is not public
if (!in_array($current_page, $public_pages) && !isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}