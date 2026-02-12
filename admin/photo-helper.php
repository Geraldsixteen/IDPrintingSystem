<?php  
// ==================== PHOTO HELPER (Dynamic) ====================
// Works for both local and deployed systems

function displayPhoto($filename){
    // Default placeholder
    $placeholder = "<div style='width:70px;height:90px;display:flex;align-items:center;justify-content:center;
                    background:#eee;color:#555;font-weight:600;border:2px solid #000;border-radius:6px;margin:0 auto;'>
                    No Photo
                    </div>";

    if(empty($filename)){
        return $placeholder;
    }

    // Absolute server path for checking file existence
    $filePath = __DIR__ . '/../../public/uploads/' . $filename;

    // Browser-accessible URL (absolute path ensures AJAX works)
    $url = '/public/uploads/' . $filename;

    if(file_exists($filePath)){
        return "<img src='" . htmlspecialchars($url) . "' alt='Photo' 
                style='width:70px;height:90px;object-fit:cover;border:2px solid #000;border-radius:6px;'>";
    } else {
        // fallback default image
        return "<img src='/public/uploads/default.png' alt='No Photo' 
                style='width:70px;height:90px;object-fit:cover;border:2px solid #000;border-radius:6px;'>";
    }
}
