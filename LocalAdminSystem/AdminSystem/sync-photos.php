<?php
require_once __DIR__ . '/../Config/database.php';

// Main uploads folder
$uploadsDir = __DIR__ . '/Uploads/';

// Ensure the folder exists
if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0777, true);

// Fetch all students with a photo filename or BLOB
$stmt = $pdo->query("SELECT photo, photo_blob FROM register");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$count = 0;

foreach ($rows as $row) {
    // Safe filename
    $filename = basename($row['photo'] ?? '');
    if (!$filename) continue; // skip if no filename

    $filePath = $uploadsDir . $filename;

    // Restore only if missing
    if (!file_exists($filePath) && !empty($row['photo_blob'])) {
        $photo_blob = $row['photo_blob'];
        if (is_resource($photo_blob)) $photo_blob = stream_get_contents($photo_blob);

        // Save the file
        file_put_contents($filePath, $photo_blob);
        $count++;
    }
}

echo "Self-healing complete: Restored $count missing images to Uploads/";
