<?php 
// ==================== PHOTO HELPER (Dynamic) ====================
// Automatically handles local or deployed environment for photos

function displayPhoto($filename){
    // If no photo
    if(empty($filename)){
        return "<div style='width:70px;height:90px;display:flex;align-items:center;justify-content:center;
                background:#eee;color:#555;font-weight:600;border:2px solid #000;border-radius:6px;margin:0 auto;'>
                No Photo
                </div>";
    }

    // Check if running on deployed system
    $isProd = isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'onrender.com') !== false;

    if($isProd){
        // deployed system (Render)
        $baseUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/uploads/';
        $filePath = __DIR__ . '/../public/uploads/' . $filename;
    } else {
        // local system: use local uploads folder
        $baseUrl = '../public/uploads/';
        $filePath = __DIR__ . '/../public/uploads/' . $filename;
    }

    // If file exists, show it, otherwise show default
    if(file_exists($filePath)){
        return "<img src='" . htmlspecialchars($baseUrl . $filename) . "' alt='Photo' 
                style='width:70px;height:90px;object-fit:cover;border:2px solid #000;border-radius:6px;'>";
    } else {
        return "<img src='" . htmlspecialchars($baseUrl . 'default.png') . "' alt='No Photo' 
                style='width:70px;height:90px;object-fit:cover;border:2px solid #000;border-radius:6px;'>";
    }
}
