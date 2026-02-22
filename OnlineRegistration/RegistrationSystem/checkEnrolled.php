<?php 
require_once __DIR__ . '/../Config/database.php';
header('Content-Type: application/json; charset=utf-8');

$lrn       = trim($_GET['lrn'] ?? '');
$full_name = trim($_GET['full_name'] ?? '');
$level     = $_GET['level'] ?? '';

if (!$lrn || !$full_name || !$level) {
    echo json_encode(['success'=>false,'msg'=>'Incomplete information']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT * FROM enrolled_students
    WHERE lrn = :lrn 
      AND LOWER(full_name) = LOWER(:full_name)
      AND status = 'enrolled'
    LIMIT 1
");
$stmt->execute([
    ':lrn'=>$lrn,
    ':full_name'=>$full_name
]);

$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    echo json_encode(['success'=>false,'msg'=>'You are not enrolled']);
    exit;
}

$match = false;

if ($level === 'junior' && !empty($student['grade'])) {
    $match = true;
}

if ($level === 'senior' && !empty($student['strand'])) {
    $match = true;
}

if ($level === 'college' && !empty($student['course'])) {
    $match = true;
}

if (!$match) {
    echo json_encode(['success'=>false,'msg'=>'Please check your level selection']);
    exit;
}

echo json_encode([
    'success'=>true,
    'msg'=>'You are enrolled',
    'data'=>[
        'full_name'=>$student['full_name'],
        'grade'=>$student['grade'] ?? '',
        'strand'=>$student['strand'] ?? '',
        'course'=>$student['course'] ?? '',
        'home_address'=>$student['home_address'] ?? '',
        'guardian_name'=>$student['guardian_name'] ?? '',
        'guardian_contact'=>$student['guardian_contact'] ?? ''
    ]
]);