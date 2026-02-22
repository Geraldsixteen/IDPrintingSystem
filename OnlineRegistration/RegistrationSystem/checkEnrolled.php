<?php 
require_once __DIR__ . '/../Config/database.php';
header('Content-Type: application/json; charset=utf-8');

$lrn   = trim($_GET['lrn'] ?? '');
$level = $_GET['level'] ?? '';

if (!$lrn || !$level) {
    echo json_encode(['success'=>false,'msg'=>'Missing LRN or level.']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT * FROM enrolled_students
    WHERE lrn = :lrn AND status = 'enrolled'
    LIMIT 1
");
$stmt->execute([':lrn'=>$lrn]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    echo json_encode(['success'=>false,'msg'=>'You are not enrolled']);
    exit;
}

$match = false;

// ===== LEVEL CHECKING =====
if ($level === 'junior') {
    $allowed = ['Grade 7','Grade 8','Grade 9','Grade 10'];
    if (!empty($student['grade']) && in_array($student['grade'], $allowed)) {
        $match = true;
    }

} elseif ($level === 'senior') {
    $allowed = ['STEM','HUMMS','ABM','GAS','ICT'];
    if (!empty($student['strand']) && in_array($student['strand'], $allowed)) {
        $match = true;
    }

} elseif ($level === 'college') {
    $allowed = ['BSBA','BSE','BEE','BSCS','BAE'];
    if (!empty($student['course']) && in_array($student['course'], $allowed)) {
        $match = true;
    }
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