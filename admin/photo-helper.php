<?php 
// ==================== PHOTO HELPER (Dynamic) ====================
// Works for both local and deployed systems

function displayPhoto($filename){
    if(empty($filename)){
        return "<div style='width:70px;height:90px;display:flex;align-items:center;justify-content:center;
                background:#eee;color:#555;font-weight:600;border:2px solid #000;border-radius:6px;margin:0 auto;'>
                No Photo
                </div>";
    }

    // Check if running on deployed system
    $isProd = isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'onrender.com') !== false;

    if($isProd){
        // deployed system
        $baseUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/uploads/';
        $filePath = __DIR__ . '/../../public/uploads/' . $filename;
    } else {
        // local system
        $baseUrl = '../../public/uploads/';
        $filePath = __DIR__ . '/../../public/uploads/' . $filename;
    }

    if(file_exists($filePath)){
        return "<img src='" . htmlspecialchars($baseUrl . $filename) . "' alt='Photo' 
                style='width:70px;height:90px;object-fit:cover;border:2px solid #000;border-radius:6px;'>";
    } else {
        return "<img src='" . htmlspecialchars($baseUrl . 'default.png') . "' alt='No Photo' 
                style='width:70px;height:90px;object-fit:cover;border:2px solid #000;border-radius:6px;'>";
    }
}
