<?php
function displayPhoto($filename) {

    if (empty($filename)) {
        return "<div style='width:70px;height:90px;border:2px solid #000;
        display:flex;align-items:center;justify-content:center'>No Photo</div>";
    }

    // Physical path (disk)
    $ephemeralPath = __DIR__ . '/../../OnlineRegistration/Public/Uploads/' . $filename;

    // Browser URL (MUST include IDPrintingSystem)
    $ephemeralURL = '/IDPrintingSystem/OnlineRegistration/Public/Uploads/' . $filename;

    // Local Windows backup
    $backupPath = 'C:/LocalAdminSystem/UploadsBackup/' . $filename;

    // First: online uploads
    if (file_exists($ephemeralPath)) {
        return "<img src='$ephemeralURL'
            style='width:70px;height:90px;object-fit:cover;border:2px solid #000'>";
    }

    // Second: local backup
    if (file_exists($backupPath)) {
        $type = pathinfo($backupPath, PATHINFO_EXTENSION);
        $data = file_get_contents($backupPath);
        $base64 = 'data:image/'.$type.';base64,'.base64_encode($data);

        return "<img src='$base64'
            style='width:70px;height:90px;object-fit:cover;border:2px solid #000'>";
    }

    return "<div style='width:70px;height:90px;border:2px solid red;
    display:flex;align-items:center;justify-content:center'>Missing</div>";
}
