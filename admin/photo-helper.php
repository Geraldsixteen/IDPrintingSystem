<?php 
// ==================== PHOTO HELPER (Dynamic) ====================
// Works for local admin (pulls images from deployed system) or local dev

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
        $file = __DIR__ . '/../public/uploads/' . $filename;
    } else {
        // local admin pulls photos from deployed registration system
        $baseUrl = 'https://idprintingsystem-1.onrender.com/uploads/';
        $file = null; // we don’t have local copy
    }

    // If file exists (only for deployed system)
    if($file && file_exists($file)){
        return "<img src='" . htmlspecialchars($baseUrl . $filename) . "' alt='Photo' style='width:180px;height:180px;object-fit:cover;border:2px solid #000;border-radius:8px;margin:20px auto;'>";
    }

    // For local admin, always use URL
    return "<img src='" . htmlspecialchars($baseUrl . $filename) . "' alt='Photo' style='width:180px;height:180px;object-fit:cover;border:2px solid #000;border-radius:8px;margin:20px auto;'>";
}
