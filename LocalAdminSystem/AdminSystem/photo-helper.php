<?php
function displayPhoto($filename) {
    if (empty($filename)) {
        return "<div style='width:70px;height:90px;border:2px solid #000;display:flex;align-items:center;justify-content:center'>No Photo</div>";
    }

    // 1️⃣ Ephemeral folder (web-accessible)
    $ephemeralPath = __DIR__ . '/../../OnlineRegistration/Public/Uploads/' . $filename;
    $ephemeralURL  = '/OnlineRegistration/Public/Uploads/' . $filename;

    // 2️⃣ Local backup folder
    $backupPath = 'C:/LocalAdminSystem/UploadsBackup/' . $filename;

    // Check ephemeral folder first
    if (file_exists($ephemeralPath)) {
        return "<img src='$ephemeralURL' style='width:70px;height:90px;object-fit:cover;border:2px solid #000'>";
    } 
    // Fallback to local backup
    elseif (file_exists($backupPath)) {
        $type = pathinfo($backupPath, PATHINFO_EXTENSION);
        $data = file_get_contents($backupPath);
        $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        return "<img src='$base64' style='width:70px;height:90px;object-fit:cover;border:2px solid #000'>";
    }

    // Missing file
    return "<div style='width:70px;height:90px;border:2px solid red;display:flex;align-items:center;justify-content:center'>Missing</div>";
}
