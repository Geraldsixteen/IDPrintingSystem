<?php

function displayPhoto($filename=null,$photo_blob=null){

    $uploadDir = __DIR__.'/Uploads/';
    if(!is_dir($uploadDir)) mkdir($uploadDir,0777,true);

    $localPath = $uploadDir.$filename;

    /* 1. Local file first */
    if($filename && file_exists($localPath)){
        return "<img src='Uploads/$filename' style='width:70px;height:90px;object-fit:cover;border:2px solid #000'>";
    }

    /* 2. Restore from blob */
    if($filename && $photo_blob){

        if(is_resource($photo_blob)){
            $photo_blob = stream_get_contents($photo_blob);
        }

        file_put_contents($localPath,$photo_blob);

        return "<img src='Uploads/$filename' style='width:70px;height:90px;object-fit:cover;border:2px solid #000'>";
    }

    /* 3. Fallback avatar */
    return "<img src='Uploads/default.png' style='width:70px;height:90px;object-fit:cover;border:2px solid #000'>";
}
