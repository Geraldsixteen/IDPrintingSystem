<?php
function displayPhoto($filename){

    $placeholder = "<div style='width:70px;height:90px;display:flex;align-items:center;justify-content:center;
                    background:#eee;color:#555;font-weight:600;border:2px solid #000;border-radius:6px;margin:0 auto;'>
                    No Photo
                    </div>";

    if(empty($filename)){
        return $placeholder;
    }

    // REAL server path (go up 2 levels)
    $uploadDir = dirname(__DIR__, 2) . '/public/uploads/';

    // Browser URL
    $url = '/public/uploads/' . $filename;

    if(file_exists($uploadDir . $filename)){
        return "<img src='".htmlspecialchars($url)."' alt='Photo'
                style='width:70px;height:90px;object-fit:cover;border:2px solid #000;border-radius:6px;'>";
    }

    // Default fallback
    $defaultPath = dirname(__DIR__, 2) . '/public/uploads/default.png';
    $defaultUrl  = '/public/uploads/default.png';

    if(file_exists($defaultPath)){
        return "<img src='".htmlspecialchars($defaultUrl)."' alt='No Photo'
                style='width:70px;height:90px;object-fit:cover;border:2px solid #000;border-radius:6px;'>";
    }

    return $placeholder;
}
