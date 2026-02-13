<?php
require_once __DIR__ . '/admin-auth.php';
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo "Unauthorized";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id'])) {
    $id = intval($_POST['id']); // primary key

    // Fetch student from register
    $stmt = $pdo->prepare("SELECT * FROM register WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();

    if ($row) {
        // Insert into archive
        $stmtInsert = $pdo->prepare("
            INSERT INTO archive
            (lrn, full_name, id_number, grade, strand, course, home_address, guardian_name, guardian_contact, photo, created_at, printed_at, status)
            VALUES (:lrn, :full_name, :id_number, :grade, :strand, :course, :home_address, :guardian_name, :guardian_contact, :photo, :created_at, NOW(), 'Not Released')
        ");
        $stmtInsert->execute([
            'lrn' => $row['lrn'],
            'full_name' => $row['full_name'],
            'id_number' => $row['id_number'],
            'grade' => $row['grade'],
            'strand' => $row['strand'],
            'course' => $row['course'],
            'home_address' => $row['home_address'],
            'guardian_name' => $row['guardian_name'],
            'guardian_contact' => $row['guardian_contact'],
            'photo' => $row['photo'],
            'created_at' => $row['created_at'] // keep original registration date
        ]);


        // Delete from register using the POST id
        $stmtDel = $pdo->prepare("DELETE FROM register WHERE id = :id");
        $stmtDel->execute(['id' => $id]);

        echo "ok";
        exit;
    } else {
        echo "Student not found";
    }
} else {
    echo "Invalid request";
}

