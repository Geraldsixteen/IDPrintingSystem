<?php
require_once __DIR__ . '/../Config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id'])) {
    exit("Invalid request");
}

$id = intval($_POST['id']);

try {
    // Fetch student from register
    $stmt = $pdo->prepare("SELECT * FROM register WHERE id=?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        exit("Student not found");
    }

    // Check if already archived
    $stmtCheck = $pdo->prepare("SELECT id FROM archive WHERE id_number = ?");
    $stmtCheck->execute([$row['id_number']]);
    $archived = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($archived) {
        // Already archived: update printed_at timestamp instead of inserting duplicate
        $update = $pdo->prepare("UPDATE archive SET printed_at = NOW() WHERE id = ?");
        $update->execute([$archived['id']]);
        echo "already archived";
        exit;
    }

    // Not archived yet: insert into archive
    $insert = $pdo->prepare("
        INSERT INTO archive
        (lrn, full_name, id_number, grade, strand, course, home_address,
         guardian_name, guardian_contact, photo, photo_blob, created_at, printed_at, status)
        VALUES (?,?,?,?,?,?,?,?,?,?,?, ?, NOW(), 'Not Released')
    ");

    $insert->execute([
        $row['lrn'],
        $row['full_name'],
        $row['id_number'],
        $row['grade'],
        $row['strand'],
        $row['course'],
        $row['home_address'],
        $row['guardian_name'],
        $row['guardian_contact'],
        $row['photo'] ?? null,
        $row['photo_blob'] ?? null,
        $row['created_at'] ?? date('Y-m-d H:i:s')
    ]);

    // Delete from register
    $pdo->prepare("DELETE FROM register WHERE id=?")->execute([$id]);

    echo "ok";

} catch (Exception $e) {
    http_response_code(500);
    echo "Archive failed: " . $e->getMessage();
}
