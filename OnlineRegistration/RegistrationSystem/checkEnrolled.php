<?php 
require_once __DIR__ . '/../Config/database.php';
header('Content-Type: application/json; charset=utf-8');

// ===== HELPER FUNCTION =====
function send_json($arr) {
    if (ob_get_level()) ob_end_clean();
    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    exit;
}

// ===== GET AND CLEAN INPUT =====
$lrn       = trim($_GET['lrn'] ?? '');
$full_name = trim($_GET['full_name'] ?? '');
$level     = strtolower(trim($_GET['level'] ?? ''));

if (!$lrn || !$full_name || !$level) {
    send_json(['success'=>false,'msg'=>'Incomplete information']);
}

// ===== FETCH STUDENT =====
$stmt = $pdo->prepare("
    SELECT * FROM enrolled_students
    WHERE lrn = :lrn 
      AND LOWER(full_name) = LOWER(:full_name)
      AND status = 'enrolled'
    LIMIT 1
");
$stmt->execute([
    ':lrn' => $lrn,
    ':full_name' => $full_name
]);

$student = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$student) send_json(['success'=>false,'msg'=>'You are not enrolled']);

// ===== VALIDATE LEVEL =====
$levelMap = [
    'junior' => 'grade',
    'senior' => 'strand',
    'college' => 'course'
];

if (!isset($levelMap[$level]) || empty($student[$levelMap[$level]])) {
    send_json(['success'=>false,'msg'=>'Please check your level selection']);
}

// ===== RETURN STUDENT DATA =====
send_json([
    'success' => true,
    'msg' => 'You are enrolled',
    'data' => [
        'full_name'       => $student['full_name'],
        'grade'           => $student['grade'] ?? '',
        'strand'          => $student['strand'] ?? '',
        'course'          => $student['course'] ?? '',
        'home_address'    => $student['home_address'] ?? '',
        'guardian_name'   => $student['guardian_name'] ?? '',
        'guardian_contact'=> $student['guardian_contact'] ?? ''
    ]
]);