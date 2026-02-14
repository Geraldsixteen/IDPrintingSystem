<?php
/**
 * Display a student's photo
 * 
 * @param string|null $filename The local filename in AdminSystem/Uploads/
 * @param string|null $photo_blob Optional database blob (binary or stream)
 * @return string HTML <img> or placeholder
 */
function displayPhoto($filename = null, $photo_blob = null) {

    // Local file path (inside LocalAdminSystem/AdminSystem/Uploads/)
    $localPath = __DIR__ . '/Uploads/' . $filename;

    // 1️⃣ Check local file first
    if ($filename && file_exists($localPath)) {
        return "<img src='Uploads/$filename' 
            style='width:70px;height:90px;object-fit:cover;border:2px solid #000'>";
    }

    // 2️⃣ Fallback: use database blob if available
    if ($photo_blob) {
        // If $photo_blob is a resource (stream), read it into a string
        if (is_resource($photo_blob)) {
            $photo_blob = stream_get_contents($photo_blob);
        }

        $base64 = base64_encode($photo_blob);
        return "<img src='data:image/jpeg;base64,$base64'
            style='width:70px;height:90px;object-fit:cover;border:2px solid #000'>";
    }

    // 3️⃣ No photo available
    return "<div style='width:70px;height:90px;border:2px solid red;
        display:flex;align-items:center;justify-content:center'>No Photo</div>";
}
