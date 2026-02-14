<?php

$backupDir = '/mnt/data/UploadsBackup/';

// Ensure backup folder exists
if (!is_dir($backupDir)) {
    die("Backup directory not found.");
}

// Get files safely
$allowedFiles = array_values(array_filter(scandir($backupDir), function ($f) use ($backupDir) {
    return is_file($backupDir . $f);
}));

// ================= SINGLE FILE DOWNLOAD =================
if (isset($_GET['file'])) {

    $filename = basename($_GET['file']);
    $path = $backupDir . $filename;

    if (!in_array($filename, $allowedFiles)) {
        http_response_code(403);
        exit("Access denied.");
    }

    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($path));

    readfile($path);
    exit;
}

// ================= DOWNLOAD ALL ZIP =================
if (isset($_GET['all'])) {

    if (!class_exists('ZipArchive')) {
        die("ZIP extension not installed.");
    }

    $zipFile = tempnam(sys_get_temp_dir(), 'backup_') . '.zip';

    $zip = new ZipArchive();

    if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
        die("Cannot create ZIP.");
    }

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
}

// ================= HTML LIST =================
echo "<h3>Backup Images</h3><ul>";

foreach ($allowedFiles as $file) {
    echo "<li><a href='?file=" . urlencode($file) . "'>$file</a></li>";
}

echo "</ul><br>";
echo "<a href='?all=1'>Download All as ZIP</a>";
