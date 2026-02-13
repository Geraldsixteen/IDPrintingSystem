<?php

function displayPhoto($filename){

    if(empty($filename)){
        return "<div style='width:70px;height:90px;border:2px solid #000;display:flex;align-items:center;justify-content:center'>No Photo</div>";
    }

    /*
    Your structure:

    htdocs/
      Id-Printing-System/
        public/uploads/
        students/
        IDPrintingSystem/admin/  <-- YOU ARE HERE
    */

    // REAL filesystem path (go UP 2 folders, then public/uploads)
    $realPath = dirname(__DIR__,2) . '/public/uploads/' . $filename;

    // REAL browser URL
    $url = '/Id-Printing-System/public/uploads/' . $filename;

    if(file_exists($realPath)){
        return "<img src='$url' style='width:70px;height:90px;object-fit:cover;border:2px solid #000'>";
    }

    return "<div style='width:70px;height:90px;border:2px solid red;display:flex;align-items:center;justify-content:center'>Missing</div>";
}
