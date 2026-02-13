<?php
function displayPhoto($filename){

    $placeholder = "<div style='width:70px;height:90px;display:flex;align-items:center;justify-content:center;
                    background:#eee;color:#555;font-weight:600;border:2px solid #000;border-radius:6px;margin:0 auto;'>
                    No Photo
                    </div>";

    if(empty($filename)){
        return $placeholder;
    }

    // REAL server path
    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/Id-Printing-System/public/uploads/';

    // REAL browser URL
    $url = '/Id-Printing-System/public/uploads/' . $filename;

    if(file_exists($uploadDir . $filename)){
        return "<img src='".htmlspecialchars($url)."' alt='Photo'
                style='width:70px;height:90px;object-fit:cover;border:2px solid #000;border-radius:6px;'>";
    }

    return $placeholder;
}
