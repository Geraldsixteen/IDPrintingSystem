<?php
// ==================== PHOTO HELPER ====================
function displayPhoto($filename){
    // Default placeholder
    $placeholder = "<div style='width:70px;height:90px;display:flex;align-items:center;justify-content:center;
                    background:#eee;color:#555;font-weight:600;border:2px solid #000;border-radius:6px;margin:0 auto;'>
                    No Photo
                    </div>";

    if(empty($filename)){
        return $placeholder;
    }

    // Server path (for file existence check)
    $filePath = dirname(__DIR__,2) . '/public/uploads/' . $filename;

    // Browser-accessible URL (works both for normal page load and AJAX)
    $url = '../public/uploads/' . $filename;

    if(file_exists($filePath)){
        return "<img src='" . htmlspecialchars($url) . "' alt='Photo'
                style='width:70px;height:90px;object-fit:cover;border:2px solid #000;border-radius:6px;'>";
    }

    // Fallback default image
    $defaultPath = __DIR__ . '/../public/uploads/default.png';
    $defaultUrl  = '../public/uploads/default.png';

    if(file_exists($defaultPath)){
        return "<img src='" . htmlspecialchars($defaultUrl) . "' alt='No Photo'
                style='width:70px;height:90px;object-fit:cover;border:2px solid #000;border-radius:6px;'>";
    }

    return $placeholder;
}
