<?php
function displayPhoto($filename = null, $photo_blob = null){
    $adminUploadsDir = __DIR__ . '/../../LocalAdminSystem/AdminSystem/Uploads/';

    if (!is_dir($adminUploadsDir)) mkdir($adminUploadsDir, 0777, true);

    // Ensure filename is safe
    if ($filename) {
        $safeFilename = basename($filename); // prevents ../ path issues
        $filePath = $adminUploadsDir . $safeFilename;

        // 1. Use local file if it exists
        if (file_exists($filePath)) {
            return "<img src='../../LocalAdminSystem/AdminSystem/Uploads/$safeFilename' alt='Student Photo' style='width:70px;height:90px;object-fit:cover;border:2px solid #000'>";
        }

        // 2. Restore from DB blob if missing locally
        if ($photo_blob) {
            // Decode if Base64
            if (!empty($photo_blob) && base64_encode(base64_decode($photo_blob, true)) === $photo_blob) {
                $photo_blob = base64_decode($photo_blob);
            }

            file_put_contents($filePath, $photo_blob);
            return "<img src='../../LocalAdminSystem/AdminSystem/Uploads/$safeFilename' alt='Student Photo' style='width:70px;height:90px;object-fit:cover;border:2px solid #000'>";
        }
    }

    // 3. Fallback to default
    $default = 'default.png';
    $defaultPath = $adminUploadsDir . $default;

    if (!file_exists($defaultPath)) {
        $img = imagecreatetruecolor(70, 90);
        $bg = imagecolorallocate($img, 200, 200, 200);
        imagefill($img, 0, 0, $bg);
        imagejpeg($img, $defaultPath);
        imagedestroy($img);
    }

    return "<img src='../../LocalAdminSystem/AdminSystem/Uploads/$default' alt='Default Photo' style='width:70px;height:90px;object-fit:cover;border:2px solid #000'>";
}