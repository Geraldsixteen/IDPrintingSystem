<?php
require_once __DIR__ . '/../Config/database.php';
header('Content-Type: application/json; charset=utf-8');

$lrn = trim($_GET['lrn'] ?? '');

if (!$lrn) {
    echo json_encode(['success'=>false,'msg'=>'Missing LRN.']);
    exit;
}

// Only check if student exists and is enrolled
$stmt = $pdo->prepare("
    SELECT * FROM enrolled_students
    WHERE lrn = :lrn AND status = 'enrolled'
");
$stmt->execute([':lrn' => $lrn]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    echo json_encode(['success'=>false,'msg'=>'Not enrolled']);
} else {
    echo json_encode(['success'=>true,'msg'=>'Enrolled']);
}