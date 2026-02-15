<?php

function displayPhoto($filename = null, $photo_blob = null){

    $uploadsDir = __DIR__ . '/../../LocalAdminSystem/AdminSystem/Uploads/';
    $webPath = '../../LocalAdminSystem/AdminSystem/Uploads/';

    if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0777, true);

    $safeFilename = $filename ? basename($filename) : null;
    $filePath = $safeFilename ? $uploadsDir . $safeFilename : null;

    /* ===============================
       1️⃣ DATABASE FIRST (if exists)
    ================================ */

    if (!empty($photo_blob) && $safeFilename) {

        if (is_resource($photo_blob)) {
            $photo_blob = stream_get_contents($photo_blob);
        }

        if (base64_encode(base64_decode($photo_blob, true)) === $photo_blob) {
            $photo_blob = base64_decode($photo_blob);
        }

        if ($photo_blob) {

            // Save locally if missing
            if (!file_exists($filePath)) {
                file_put_contents($filePath, $photo_blob);
            }

            return "<img src='{$webPath}{$safeFilename}' style='width:70px;height:90px;object-fit:cover;border:2px solid #000'>";
        }
    }

    /* ===============================
       2️⃣ UPLOADS BACKUP
    ================================ */

    if ($filePath && file_exists($filePath)) {

        return "<img src='{$webPath}{$safeFilename}' style='width:70px;height:90px;object-fit:cover;border:2px solid #000'>";
    }

    /* ===============================
       3️⃣ DEFAULT
    ================================ */

    $default = 'default.png';
    $defaultPath = $uploadsDir.$default;

    if (!file_exists($defaultPath)) {

        $img = imagecreatetruecolor(70,90);
        $bg = imagecolorallocate($img,200,200,200);
        imagefill($img,0,0,$bg);
        imagejpeg($img,$defaultPath);
        imagedestroy($img);
    }

    return "<img src='{$webPath}{$default}' style='width:70px;height:90px;object-fit:cover;border:2px solid #000'>";
}
