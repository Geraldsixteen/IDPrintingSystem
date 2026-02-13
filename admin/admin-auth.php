<?php
session_start();

// ---------------- CONFIG: Admin MAC ----------------
$allowed_mac = '90-10-57-42-A6-30';  // replace with your PC MAC

// ---------------- GET ACTIVE MACs ----------------
$macs = [];
exec('getmac', $output);
foreach ($output as $line) {
    if (strpos($line, 'Media disconnected') !== false) continue;
    if (preg_match('/([0-9A-F]{2}[-:]){5}([0-9A-F]{2})/i', $line, $matches)) {
        $macs[] = strtoupper($matches[0]);
    }
}

// ---------------- CHECK MAC ----------------
$client_ip = $_SERVER['REMOTE_ADDR'] ?? '';
$current_page = basename($_SERVER['PHP_SELF']);

// Only allow this PC
if ($client_ip !== '192.168.100.124' && !in_array(strtoupper($allowed_mac), $macs)) {
    http_response_code(403);
    die("
        <h2 style='text-align:center;margin-top:50px'>
        🚫 Access Denied<br><br>
        Admin can only be used on the authorized computer.
        </h2>
    ");
}

// ---------------- CHECK SESSION ----------------
// Allow login, register, reset-password without session
$public_pages = ['login.php', 'admin-register.php', 'reset-password.php'];

if (!in_array($current_page, $public_pages) && !isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
?>
