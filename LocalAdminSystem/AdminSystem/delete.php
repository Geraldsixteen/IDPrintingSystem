<?php
require_once __DIR__ . '/admin-auth.php';
require_once __DIR__ . '/../Config/database.php';

if (!isset($_GET['id'])) {
    die("No ID specified.");
}

$id = intval($_GET['id']);

// Optional: Fetch student info to delete photo if needed
$stmt = $pdo->prepare("SELECT photo FROM register WHERE id = ?");
$stmt->execute([$id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if ($student) {
    // Delete the photo file if exists
    if (!empty($student['photo']) && file_exists(__DIR__ . '/uploads/' . $student['photo'])) {
        unlink(__DIR__ . '/uploads/' . $student['photo']);
    }

    // Delete record from database
    $stmt = $pdo->prepare("DELETE FROM register WHERE id = ?");
    $stmt->execute([$id]);

    header("Location: records.php?deleted=1");
    exit;
} else {
    die("Student record not found.");
}