<?php 
require_once __DIR__ . '/admin-auth.php';
require_once __DIR__ . '/../Config/database.php';

if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo "Unauthorized";
    exit;
}

// ================= HANDLE POST =================
// Accept either single id (id=1) or multiple ids (ids=1,2,3)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $ids = [];

    // Multiple IDs from "ids" parameter (comma-separated)
    if (!empty($_POST['ids'])) {
        $ids = array_filter(array_map('intval', explode(',', $_POST['ids'])));
    } 
    // Single ID from "id" parameter
    elseif (!empty($_POST['id'])) {
        $ids[] = intval($_POST['id']);
    }

    if (empty($ids)) {
        echo "No valid student IDs provided";
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Fetch all students
        $inQuery = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT * FROM register WHERE id IN ($inQuery)");
        $stmt->execute($ids);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$students) {
            echo "No students found";
            exit;
        }

        // Insert into archive
        $stmtInsert = $pdo->prepare("
            INSERT INTO archive
            (lrn, full_name, id_number, grade, strand, course, home_address, guardian_name, guardian_contact, photo, photo_blob, created_at, printed_at, status)
            VALUES
            (:lrn, :full_name, :id_number, :grade, :strand, :course, :home_address, :guardian_name, :guardian_contact, :photo, :photo_blob, :created_at, NOW(), 'Not Released')
        ");

        foreach ($students as $row) {
            $stmtInsert->execute([
                ':lrn' => $row['lrn'],
                ':full_name' => $row['full_name'],
                ':id_number' => $row['id_number'],
                ':grade' => $row['grade'],
                ':strand' => $row['strand'],
                ':course' => $row['course'],
                ':home_address' => $row['home_address'],
                ':guardian_name' => $row['guardian_name'],
                ':guardian_contact' => $row['guardian_contact'],
                ':photo' => $row['photo'] ?? null,
                ':photo_blob' => $row['photo_blob'] ?? null,
                ':created_at' => $row['created_at'] ?? date('Y-m-d H:i:s'),
            ]);
        }

        // Delete from register
        $stmtDel = $pdo->prepare("DELETE FROM register WHERE id IN ($inQuery)");
        $stmtDel->execute($ids);

        $pdo->commit();
        echo "ok";
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo "Database error: " . $e->getMessage();
        exit;
    }

} else {
    echo "Invalid request";
}
