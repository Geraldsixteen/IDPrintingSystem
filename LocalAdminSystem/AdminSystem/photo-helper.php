<?php
/**
 * Get a student photo URL for display.
 * Restores from BLOB if missing, otherwise returns placeholder.
 *
 * @param array|null $student Student record with 'photo' and 'photo_blob'.
 * @param int $width Width of placeholder if needed.
 * @param int $height Height of placeholder if needed.
 * @return string Relative URL to photo for <img src="">
 */
function getStudentPhotoUrl($student = null, int $width = 70, int $height = 90): string {
    $uploadsDir = __DIR__ . '/Uploads/'; // Make sure folder exists
    if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0777, true);

    $filename = basename($student['photo'] ?? '');
    $filePath = $uploadsDir . $filename;

    // 1️⃣ Use file if valid
    if ($filename && file_exists($filePath) && @getimagesize($filePath)) {
        return 'Uploads/' . $filename;
    }

    // 2️⃣ Restore from BLOB if available
    if (!file_exists($filePath) && !empty($student['photo_blob'])) {
        $blob = $student['photo_blob'];
        if (is_resource($blob)) $blob = stream_get_contents($blob);

        // Decode base64 if necessary
        if (preg_match('/^[A-Za-z0-9+\/=]+$/', trim($blob))) {
            $decoded = base64_decode($blob, true);
            if ($decoded !== false) $blob = $decoded;
        }

        file_put_contents($filePath, $blob);

        if (@getimagesize($filePath)) {
            return 'Uploads/' . $filename;
        } else {
            @unlink($filePath); // remove corrupted
        }
    }

    // 3️⃣ Fallback placeholder
    $default = $uploadsDir . 'default.png';
    if (!file_exists($default)) {
        $im = imagecreatetruecolor($width, $height);
        $bg = imagecolorallocate($im, 200, 200, 200);
        imagefilledrectangle($im, 0, 0, $width, $height, $bg);
        imagepng($im, $default);
        imagedestroy($im);
    }

    return 'Uploads/default.png';
}
