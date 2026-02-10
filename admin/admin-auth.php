<?php
// ================== ADMIN AUTHENTICATION =================
session_start();

// -------- CONFIG: Your Admin PC MAC --------
$allowed_mac = '90-10-57-42-A6-30'; // <-- replace with your actual admin PC MAC

// -------- GET ACTIVE MAC ADDRESSES --------
$macs = [];
exec('getmac', $output); // Windows command

foreach ($output as $line) {
    // Skip disconnected adapters
    if (strpos($line, 'Media disconnected') !== false) continue;

    // Extract MAC address
    if (preg_match('/([0-9A-F]{2}[-:]){5}([0-9A-F]{2})/i', $line, $matches)) {
        $macs[] = strtoupper($matches[0]);
    }
}

// -------- CHECK MAC --------
if (!in_array(strtoupper($allowed_mac), $macs)) {
    http_response_code(403);
    die("
        <h2 style='text-align:center;margin-top:50px'>
        🚫 Access Denied<br><br>
        This admin system can only be used on the authorized computer.
        </h2>
    ");
}

// -------- CHECK ADMIN LOGIN --------
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
?>
