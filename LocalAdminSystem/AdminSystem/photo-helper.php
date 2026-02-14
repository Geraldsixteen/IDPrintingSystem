<?php
/**
 * Display a student photo in records table
 *
 * @param string|null $filename  The photo filename stored in DB (e.g., 123456_1708100.jpg)
 * @param string|null $photo_blob The photo blob from DB (can be string or PDO resource)
 * @return string HTML <img> tag
 */
function displayPhoto($filename = null, $photo_blob = null){
    $uploadsDir = __DIR__ . '/../../LocalAdminSystem/AdminSystem/Uploads/';

    // Ensure uploads folder exists
    if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0777, true);

    // Sanitize filename
    $safeFilename = $filename ? basename($filename) : null;
    $filePath = $safeFilename ? $uploadsDir . $safeFilename : null;

    // 1. Use local file if exists
    if ($filePath && file_exists($filePath)) {
        return "<img src='../../LocalAdminSystem/AdminSystem/Uploads/$safeFilename' alt='Student Photo' style='width:70px;height:90px;object-fit:cover;border:2px solid #000'>";
    }

    // 2. Restore from DB blob if file missing
    if ($filePath && $photo_blob) {

        // If blob is a resource, read its contents
        if (is_resource($photo_blob)) {
            $photo_blob = stream_get_contents($photo_blob);
        }

        // If still Base64-encoded, decode it
        if (is_string($photo_blob) && base64_encode(base64_decode($photo_blob, true)) === $photo_blob) {
            $photo_blob = base64_decode($photo_blob);
        }

        // Write to local file
        if ($photo_blob) {
            file_put_contents($filePath, $photo_blob);
            return "<img src='../../LocalAdminSystem/AdminSystem/Uploads/$safeFilename' alt='Student Photo' style='width:70px;height:90px;object-fit:cover;border:2px solid #000'>";
        }
    }

    // 3. Fallback to default image
    $default = 'default.png';
    $defaultPath = $uploadsDir . $default;

    if (!file_exists($defaultPath)) {
        $img = imagecreatetruecolor(70, 90);
        $bg = imagecolorallocate($img, 200, 200, 200);
        imagefill($img, 0, 0, $bg);
        imagejpeg($img, $defaultPath);
        imagedestroy($img);
    }

    return "<img src='../../LocalAdminSystem/AdminSystem/Uploads/$default' alt='Default Photo' style='width:70px;height:90px;object-fit:cover;border:2px solid #000'>";
}
?>
