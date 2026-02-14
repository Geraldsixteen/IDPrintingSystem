<?php
require_once __DIR__ . '/../Config/database.php';

$uploadDir = __DIR__ . '/Uploads/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

$stmt = $pdo->query("SELECT photo, photo_blob FROM register WHERE photo_blob IS NOT NULL");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$count = 0;

foreach ($rows as $row) {
    $filename = $row['photo']; // use DB filename including timestamp
    $filePath = $uploadDir . $filename;

    if (!file_exists($filePath)) {
        file_put_contents($filePath, $row['photo_blob']);
        $count++;
    }
}

echo "Restored $count images to AdminSystem/Uploads/";
