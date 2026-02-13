<?php

function displayPhoto($filename){

    if(empty($filename)){
        return "<div style='width:70px;height:90px;border:2px solid #000;display:flex;align-items:center;justify-content:center'>No Photo</div>";
    }

    // Docker layout:
    // /var/www/html/Public/Uploads/

    $realPath = '/var/www/html/Public/Uploads/' . $filename;

    // Browser URL
    $url = '/Public/Uploads/' . $filename;

    if(file_exists($realPath)){
        return "<img src='$url' style='width:70px;height:90px;object-fit:cover;border:2px solid #000'>";
    }

    return "<div style='width:70px;height:90px;border:2px solid red;display:flex;align-items:center;justify-content:center'>Missing</div>";
}

