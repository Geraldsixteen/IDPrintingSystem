<?php 
require_once __DIR__ . '/admin-auth.php';

/* ================= SECURITY ================= */
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// ================== PostgreSQL Connection ==================
// Include your existing database connection
require_once __DIR__ . '/../config/database.php';

// ==========================================================

/* ================= VALIDATION ================= */
if (!isset($_POST['id_number']) || empty($_POST['id_number'])) {
    die("<h3 style='text-align:center;color:red;'>No ID selected.</h3>");
}

$id_number = trim($_POST['id_number']);

/* ================= FETCH RECORD ================= */
$stmt = $pdo->prepare("SELECT * FROM register WHERE id_number = :id_number LIMIT 1");
$stmt->execute(['id_number' => $id_number]);
$row = $stmt->fetch();

if (!$row) {
    die("<h3 style='text-align:center;color:red;'>Record not found.</h3>");
}

/* ================= HELPER ================= */
function studentLevel($row) {
    if (!empty($row['grade']))  return "Grade: " . htmlspecialchars($row['grade']);
    if (!empty($row['strand'])) return "Strand: " . htmlspecialchars($row['strand']);
    if (!empty($row['course'])) return "Course: " . htmlspecialchars($row['course']);
    return "";
}

/* ================= AUTO ARCHIVE ================= */
// Move record to archive after printing
function archiveStudent($pdo, $student) {
    // Insert into archive
    $stmtInsert = $pdo->prepare("
        INSERT INTO archive
        (lrn, full_name, id_number, grade, strand, course, home_address, guardian_name, guardian_contact, upload_photo, printed_at, status)
        VALUES (:lrn, :full_name, :id_number, :grade, :strand, :course, :home_address, :guardian_name, :guardian_contact, :upload_photo, NOW(), 'Not Released')
    ");
    $stmtInsert->execute([
        'lrn' => $student['lrn'],
        'full_name' => $student['full_name'],
        'id_number' => $student['id_number'],
        'grade' => $student['grade'],
        'strand' => $student['strand'],
        'course' => $student['course'],
        'home_address' => $student['home_address'],
        'guardian_name' => $student['guardian_name'],
        'guardian_contact' => $student['guardian_contact'],
        'upload_photo' => $student['upload_photo']
    ]);

    // Delete from register
    $stmtDel = $pdo->prepare("DELETE FROM register WHERE id_number = :id_number");
    $stmtDel->execute(['id_number' => $student['id_number']]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Print Student ID</title>

<style>
body{
    margin:0;
    background:#fff;
    font-family:"Segoe UI", Arial, sans-serif;
}

.wrapper{
    display:flex;
    justify-content:center;
    gap:40px;
    margin-top:30px;
}

.card{
    width:360px;
    height:540px;
    border-radius:12px;
    box-shadow:0 6px 18px rgba(0,0,0,.15);
    padding:20px;
    box-sizing:border-box;
}

.front{text-align:center;}

.photo{
    width:180px;
    height:180px;
    object-fit:cover;
    border:2px solid #000;
    border-radius:8px;
    margin:20px auto;
}

.info{font-weight:600;margin:4px 0;}

.back{font-size:13px;}

.box{
    border:1px solid #ccc;
    border-radius:6px;
    padding:8px;
    margin-bottom:10px;
}

.sign{
    display:flex;
    justify-content:space-between;
    margin-top:40px;
}

@media print{
    body{margin:0;}
}
</style>
</head>

<body>

<div class="wrapper">

    <!-- FRONT -->
    <div class="card front">
        <div style="font-size:18px;font-weight:bold;">Colegio de Los Baños</div>
        <div>CDLB</div>
        <div>Los Baños, Laguna</div>

        <img src="../uploads/<?= htmlspecialchars($row['upload_photo']) ?>" class="photo">

        <div class="info">LRN: <?= htmlspecialchars($row['lrn']) ?></div>
        <div class="info" style="font-size:20px;">
            <?= htmlspecialchars($row['full_name']) ?>
        </div>
        <div class="info">ID: <?= htmlspecialchars($row['id_number']) ?></div>
        <div class="info"><?= studentLevel($row) ?></div>
    </div>

    <!-- BACK -->
    <div class="card back">
        <h3 style="text-align:center;">Student Details</h3>

        <div class="box">
            <strong>Address:</strong><br>
            <?= htmlspecialchars($row['home_address']) ?>
        </div>

        <div class="box">
            <strong>Guardian:</strong><br>
            <?= htmlspecialchars($row['guardian_name']) ?>
        </div>

        <div class="box">
            <strong>Contact:</strong><br>
            <?= htmlspecialchars($row['guardian_contact']) ?>
        </div>

        <div style="font-size:11px;">
            <strong>TERMS</strong>
            <ol>
                <li>Non-transferable</li>
                <li>Must be worn inside campus</li>
                <li>Tampering voids this ID</li>
            </ol>
        </div>

        <div class="sign">
            <div>Registrar</div>
            <div>Director</div>
        </div>
    </div>

</div>

<script>
window.onload = function(){
    // Print first
    window.print();

    // Then archive the student record via PHP
    setTimeout(function(){
        fetch("<?= $_SERVER['PHP_SELF'] ?>", {
            method: "POST",
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: "archive=1&id_number=<?= urlencode($row['id_number']) ?>"
        }).then(()=>console.log("Student archived"));
    }, 500);
};
</script>

</body>
</html>

<?php
/* ================= ARCHIVE PROCESS ================= */
if (isset($_POST['archive']) && $_POST['archive'] == 1 && !empty($_POST['id_number'])) {
    archiveStudent($pdo, $row);
}
?>
