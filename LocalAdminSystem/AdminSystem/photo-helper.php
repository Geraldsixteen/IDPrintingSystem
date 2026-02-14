<?php

function displayPhoto($filename = null, $photo_blob = null) {

    // Uploads folder (case sensitive!)
    $localPath = __DIR__ . '/Uploads/' . $filename;

    /* 1️⃣ Try LOCAL FILE first */
    if (!empty($filename) && file_exists($localPath)) {

        return "<img src='Uploads/$filename'
        style='width:70px;height:90px;object-fit:cover;border:2px solid #000'>";
    }

    /* 2️⃣ Fallback to DATABASE BLOB */
    if (!empty($photo_blob)) {

        if (is_resource($photo_blob)) {
            $photo_blob = stream_get_contents($photo_blob);
        }

        $base64 = base64_encode($photo_blob);

        return "<img src='data:image/jpeg;base64,$base64'
        style='width:70px;height:90px;object-fit:cover;border:2px solid #000'>";
    }

    /* 3️⃣ Nothing found */
    return "<div style='width:70px;height:90px;border:2px solid red;
    display:flex;align-items:center;justify-content:center'>No Photo</div>";
}
