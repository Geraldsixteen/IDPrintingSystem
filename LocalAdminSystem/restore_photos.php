<?php
// LocalAdminSystem/restore_sync_photos.php

require_once __DIR__ . '/Config/database.php';
require_once __DIR__ . '/AdminSystem/photo-helper.php';

echo "Starting smart photo restore...\n";

/**
 * Restore missing photo files from BYTEA
 */
function restoreTable(PDO $pdo, string $table): int {

    // ---- SECURITY: whitelist tables ----
    $allowedTables = ['register','archive'];
    if (!in_array($table, $allowedTables)) {
        throw new Exception("Invalid table name: $table");
    }

    // ---- STREAM rows (prevents memory crash) ----
    $stmt = $pdo->query("SELECT id, photo, photo_blob, restored_at FROM $table");

    $count = 0;

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

        // Skip empty filename
        if (empty($row['photo'])) {
            continue;
        }

        // Attempt restore
        $restored = getStudentPhotoUrl($row);

        // Only update DB if file was actually recreated AND never marked before
        if ($restored === true && empty($row['restored_at'])) {

            $update = $pdo->prepare("
                UPDATE $table
                SET restored_at = NOW()
                WHERE id = :id
            ");

            $update->execute([':id' => $row['id']]);

            echo "✔ Restored {$row['photo']} from {$table}\n";
            $count++;
        }
    }

    return $count;
}

try {

    $total = 0;
    $total += restoreTable($pdo,'register');
    $total += restoreTable($pdo,'archive');

    echo "\nDONE. Total restored: $total\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}