<?php
/**
 * Display a student photo in records table
 *
 * @param string|null $filename  The photo filename stored in DB (e.g., 123456_1708100.jpg)
 * @param string|null $photo_blob The photo blob from DB
 * @return string HTML <img> tag
 */
function displayPhoto($filename = null, $photo_blob = null){
    // Physical path to Uploads folder
    $uploadDir = __DIR__ . '/Uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    // Full local path to the file
    $localPath = $uploadDir . $filename;

    // Base URL relative to the PHP file
    $baseURL = 'Uploads/'; // Relative to records.php

    // 1. Use local file if it exists
    if ($filename && file_exists($localPath)) {
        $url = $baseURL . $filename;
        return "<img src='$url' alt='Student Photo' style='width:70px;height:90px;object-fit:cover;border:2px solid #000'>";
    }

    // 2. Restore from DB blob if missing locally
    if ($filename && $photo_blob) {
        if (is_resource($photo_blob)) {
            $photo_blob = stream_get_contents($photo_blob);
        }
        file_put_contents($localPath, $photo_blob);
        $url = $baseURL . $filename;
        return "<img src='$url' alt='Student Photo' style='width:70px;height:90px;object-fit:cover;border:2px solid #000'>";
    }

    // 3. Fallback to default image
    $default = 'default.png'; // Ensure this file exists in Uploads/
    $defaultPath = $uploadDir . $default;

    if (!file_exists($defaultPath)) {
        // Optional: create a blank placeholder if default.png missing
        $img = imagecreatetruecolor(70, 90);
        $bg = imagecolorallocate($img, 200, 200, 200);
        imagefill($img, 0, 0, $bg);
        imagejpeg($img, $defaultPath);
        imagedestroy($img);
    }

    $url = $baseURL . $default;
    return "<img src='$url' alt='Default Photo' style='width:70px;height:90px;object-fit:cover;border:2px solid #000'>";
}
?>
