<?php
// download.php

// --------------- CONFIG ----------------
$backupDir = '/mnt/data/UploadsBackup/'; // Path to backup folder
$allowedFiles = array_diff(scandir($backupDir), ['.', '..']); // list all files in backup

// --------------- DOWNLOAD INDIVIDUAL FILE ----------------
if (isset($_GET['file'])) {
    $filename = basename($_GET['file']); // sanitize input
    $path = $backupDir . $filename;

    if (!file_exists($path)) {
        http_response_code(404);
        echo "File not found.";
        exit;
    }

    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="'.basename($path).'"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($path));
    readfile($path);
    exit;
}

// --------------- DOWNLOAD ALL AS ZIP ----------------
if (isset($_GET['all']) && $_GET['all'] == 1) {
    $zipFile = '/tmp/backups.zip';
    $zip = new ZipArchive();
    if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
        foreach ($allowedFiles as $file) {
            $zip->addFile($backupDir . $file, $file);
        }
        $zip->close();

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="backups.zip"');
        header('Content-Length: ' . filesize($zipFile));
        readfile($zipFile);
        unlink($zipFile);
        exit;
    } else {
        echo "Failed to create zip.";
        exit;
    }
}

// --------------- HTML LIST ----------------
echo "<h3>Backup Images</h3>";
echo "<ul>";
foreach ($allowedFiles as $file) {
    echo "<li><a href='?file=$file'>$file</a></li>";
}
echo "</ul>";
echo "<br><a href='?all=1'>Download All as ZIP</a>";
