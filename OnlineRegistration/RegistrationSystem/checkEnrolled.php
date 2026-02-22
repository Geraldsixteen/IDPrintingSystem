<?php 
require_once __DIR__ . '/../Config/database.php';
header('Content-Type: application/json; charset=utf-8');

$lrn   = trim($_GET['lrn'] ?? '');
$level = trim($_GET['level'] ?? '');
$name  = trim($_GET['full_name'] ?? '');
$value = trim($_GET['value'] ?? ''); // grade/strand/course

if (!$lrn || !$level || !$name || !$value) {
    echo json_encode(['success'=>false,'msg'=>'Missing required fields.']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT * FROM enrolled_students
    WHERE lrn = :lrn
    LIMIT 1
");
$stmt->execute([':lrn' => $lrn]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    echo json_encode(['success'=>false,'msg'=>'You are not enrolled.']);
    exit;
}

// Check level match
if ($student['level'] !== $level) {
    echo json_encode(['success'=>false,'msg'=>'Please check your information (Level mismatch).']);
    exit;
}

// Check name match (case insensitive)
if (strcasecmp($student['full_name'], $name) !== 0) {
    echo json_encode(['success'=>false,'msg'=>'Please check your information (Name mismatch).']);
    exit;
}

// Determine correct column to compare
$dbValue = '';
if ($level === 'junior') {
    $dbValue = $student['grade'];
} elseif ($level === 'senior') {
    $dbValue = $student['strand'];
} elseif ($level === 'college') {
    $dbValue = $student['course'];
}

// Check grade/strand/course match
if ($dbValue !== $value) {
    echo json_encode(['success'=>false,'msg'=>'Please check your information (Program mismatch).']);
    exit;
}

// Check status
if ($student['status'] !== 'enrolled') {
    echo json_encode(['success'=>false,'msg'=>'You are not enrolled.']);
    exit;
}

// SUCCESS
echo json_encode([
    'success' => true,
    'msg' => 'You are enrolled',
    'data' => [
        'full_name' => $student['full_name'],
        'home_address' => $student['home_address'] ?? '',
        'guardian_name' => $student['guardian_name'] ?? '',
        'guardian_contact' => $student['guardian_contact'] ?? ''
    ]
]);