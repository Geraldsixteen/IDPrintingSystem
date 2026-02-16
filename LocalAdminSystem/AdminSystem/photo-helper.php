<?php
/**
 * displayPhoto
 * Returns the correct image URL or base64 for display in <img>
 * Auto-restores missing files from DB BLOB
 *
 * @param string|null $filename - the photo filename stored in Uploads folder
 * @param string|null $blob - the photo BLOB from the database
 * @return string - URL or data URI
 */
function displayPhoto($filename = null, $blob = null) {
    $uploadsDir = __DIR__ . "/Uploads/";  // main folder (uppercase U)

    // Ensure folder exists
    if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0777, true);

    // Safe filename
    $filename = basename($filename ?? '');
    $filePath = $uploadsDir . $filename;

    // 1️⃣ Auto-restore file if missing and BLOB exists
    if ($filename && !file_exists($filePath) && $blob) {
        if (is_resource($blob)) $blob = stream_get_contents($blob);
        file_put_contents($filePath, $blob);
    }

    // 2️⃣ Use file if exists
    if ($filename && file_exists($filePath)) {
        return "Uploads/{$filename}";
    }

    // 3️⃣ Fallback to BLOB if file somehow still missing
    if ($blob) {
        if (is_resource($blob)) $blob = stream_get_contents($blob);
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->buffer($blob) ?: 'image/jpeg';
        return "data:{$mime};base64," . base64_encode($blob);
    }

    // 4️⃣ Default placeholder
    return "Uploads/default.png"; // make sure default.png exists
}
