<?php
// ==================== PHOTO HELPER (Dynamic) ====================
// Automatically handles local or deployed environment for photos

function displayPhoto($filename){
    if(empty($filename)){
        return "<div style='width:180px;height:180px;display:flex;align-items:center;justify-content:center;
                background:#eee;color:#555;font-weight:600;border:2px solid #000;border-radius:8px;margin:20px auto;'>
                No Photo
                </div>";
    }

    // Check if running on deployed system
    $isProd = isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'onrender.com') !== false;

    if($isProd){
        // deployed system (Render)
        $baseUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/uploads/';
    } else {
        // local admin pulls photos from deployed registration system
        $baseUrl = 'https://idprintingsystem-1.onrender.com/uploads/';
    }

    return "<img src='" . htmlspecialchars($baseUrl . $filename) . "' alt='Photo' 
            style='width:180px;height:180px;object-fit:cover;border:2px solid #000;border-radius:8px;margin:20px auto;'>";
}
