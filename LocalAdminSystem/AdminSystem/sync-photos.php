<?php
/**
 * sync-photos.php
 * 
 * Restore all student photos from the database BLOB into LocalAdminSystem/Uploads/
 */

require_once __DIR__ . '/../Config/database.php'; // Adjust path if needed

// Directory to store images locally
$uploadDir = __DIR__ . '/Uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Fetch all records with photo blobs
$stmt = $pdo->query("SELECT lrn, photo_blob FROM register WHERE photo_blob IS NOT NULL");
$rows = $stmt->fetchAll();

$count = 0;

foreach ($rows as $row) {
    $filename = $row['lrn'] . '.jpg'; // Naming convention
    $filePath = $uploadDir . $filename;

    // Only create file if it doesn't exist
    if (!file_exists($filePath)) {
        file_put_contents($filePath, $row['photo_blob']);
        $count++;
    }
}

echo "Restored $count images to LocalAdminSystem/Uploads/";
