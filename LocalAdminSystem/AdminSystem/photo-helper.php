<?php

function displayPhoto($filename){
    if(empty($filename)){
        return "<div style='width:70px;height:90px;border:2px solid #000;display:flex;align-items:center;justify-content:center'>No Photo</div>";
    }

    $ephemeralPath = __DIR__ . '/../Public/Uploads/' . $filename;
    $backupPath    = 'C:/LocalAdminSystem/UploadsBackup/' . $filename;

    // Prefer ephemeral folder
    if(file_exists($ephemeralPath)){
        $url = '/Public/Uploads/' . $filename;
        return "<img src='$url' style='width:70px;height:90px;object-fit:cover;border:2px solid #000'>";
    } 
    // Fallback to local backup
    elseif(file_exists($backupPath)){
        $type = pathinfo($backupPath, PATHINFO_EXTENSION);
        $data = file_get_contents($backupPath);
        $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        return "<img src='$base64' style='width:70px;height:90px;object-fit:cover;border:2px solid #000'>";
    }

    return "<div style='width:70px;height:90px;border:2px solid red;display:flex;align-items:center;justify-content:center'>Missing</div>";
}


