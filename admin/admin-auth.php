<?php
session_start();

// ================== CONFIG ==================
// Only allow this MAC address (your admin PC)
$allowed_mac = '90-10-57-42-A6-30'; // <-- replace with your active MAC

// ================== GET ACTIVE MAC ADDRESSES ==================
$macs = [];
exec('getmac', $output);

foreach ($output as $line) {
    // Skip disconnected adapters
    if (strpos($line, 'Media disconnected') !== false) continue;

    // Match MAC addresses like XX-XX-XX-XX-XX-XX
    if (preg_match('/([0-9A-F]{2}[-:]){5}([0-9A-F]{2})/i', $line, $matches)) {
        $macs[] = strtoupper($matches[0]);
    }
}

// ================== CHECK MAC ==================
if (!in_array(strtoupper($allowed_mac), $macs)) {
    http_response_code(403);
    die("
        <h2 style='text-align:center;margin-top:50px'>
        🚫 Access Denied<br><br>
        This admin system can only be used on the authorized computer.
        </h2>
    ");
}
?>
