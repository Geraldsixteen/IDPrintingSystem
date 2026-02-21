<?php 
require_once __DIR__ . '/../Config/database.php';
header('Content-Type: application/json; charset=utf-8');

$lrn = trim($_GET['lrn'] ?? '');
$level = $_GET['level'] ?? '';

if (!$lrn || !$level) {
    echo json_encode(['success'=>false,'msg'=>'Missing LRN or level.']);
    exit;
}

// Map form level to enrolled_students column
$level_map = [
    'junior' => ['Grade 7','Grade 8','Grade 9','Grade 10'],
    'senior' => ['STEM','HUMMS','ABM','GAS','ICT'],
    'college'=> ['BSBA','BSE','BEE','BSCS','BAE']
];

$allowed_strands = $level_map[$level] ?? [];

// Check student exists and matches level
$stmt = $pdo->prepare("
    SELECT * FROM enrolled_students
    WHERE lrn = :lrn AND status = 'enrolled'
    LIMIT 1
");
$stmt->execute([':lrn' => $lrn]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    echo json_encode(['success'=>false,'msg'=>'Not enrolled']);
    exit;
}

// Check strand/grade/course matches level
if (!in_array($student['strand'], $allowed_strands)) {
    echo json_encode(['success'=>false,'msg'=>'Level mismatch']);
    exit;
}

// All checks passed → return student info
echo json_encode([
    'success' => true,
    'msg' => 'You are enrolled',
    'data' => [
        'full_name' => $student['full_name'],
        'strand' => $student['strand'],
        'home_address' => $student['home_address'] ?? '',
        'guardian_name' => $student['guardian_name'] ?? '',
        'guardian_contact' => $student['guardian_contact'] ?? ''
    ]
]);