<?php
require_once __DIR__ . '/../Config/database.php';

// Target folder in LocalAdminSystem/AdminSystem
$uploadDir = __DIR__ . '/Uploads/';

// Ensure the folder exists
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

// Fetch all students with a photo blob
$stmt = $pdo->query("SELECT photo, photo_blob FROM register WHERE photo_blob IS NOT NULL");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$count = 0;

foreach ($rows as $row) {
    $filename = $row['photo']; // filename stored in DB
    $filePath = $uploadDir . $filename;

    // Restore only if missing
    if ($filename && !file_exists($filePath)) {
        $photo_blob = $row['photo_blob'];
        if (is_resource($photo_blob)) {
            $photo_blob = stream_get_contents($photo_blob);
        }
        file_put_contents($filePath, $photo_blob);
        $count++;
    }
}

echo "Restored $count images to LocalAdminSystem/AdminSystem/Uploads/";
