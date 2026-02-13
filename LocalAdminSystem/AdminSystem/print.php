<?php 
require_once __DIR__ . '/admin-auth.php';
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/photo-helper.php'; // <-- include photo helper

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Get student ID from URL (GET)
if (isset($_GET['id'])) {
    $studentId = intval($_GET['id']);
} else {
    die("No student selected."); // stops page if ID not provided
}

// Fetch the student data from register
$stmt = $pdo->prepare("SELECT * FROM register WHERE id = :id LIMIT 1");
$stmt->execute(['id' => $studentId]);
$row = $stmt->fetch();

if (!$row) {
    die("Student not found.");
}

// ================= HELPER FUNCTION =================
function studentLevel($row) {
    if (!empty($row['grade']))  return "Grade: " . htmlspecialchars($row['grade']);
    if (!empty($row['strand'])) return "Strand: " . htmlspecialchars($row['strand']);
    if (!empty($row['course'])) return "Course: " . htmlspecialchars($row['course']);
    return "";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Print Student ID</title>
<style>
body{margin:0;background:#fff;font-family:"Segoe UI", Arial, sans-serif;}
.wrapper{display:flex;justify-content:center;gap:40px;margin-top:30px;}
.card{width:360px;height:540px;border-radius:12px;box-shadow:0 6px 18px rgba(0,0,0,.15);padding:20px;box-sizing:border-box;}
.front, .back{text-align:center;}
.photo{width:180px;height:180px;object-fit:cover;border:2px solid #000;border-radius:8px;margin:20px auto;}
.info{font-weight:600;margin:4px 0;}
.back{font-size:13px; text-align:left; padding:15px;}
.box{border:1px solid #ccc;border-radius:6px;padding:8px;margin-bottom:10px;}
.box strong{display:block;margin-bottom:3px;}
.sign{display:flex;justify-content:space-between;margin-top:30px;font-weight:600;}
h3{text-align:center;margin-bottom:10px;}
@media print{body{margin:0;}}
#printBtn{display:block;margin:20px auto;padding:10px 20px;font-size:16px;background:#3549a3;color:white;border:none;border-radius:8px;cursor:pointer;}
#printBtn:hover{background:#2d3a80;}
</style>
</head>
<body>

<button id="printBtn">🖨️ Print ID</button>

<div class="wrapper">

    <!-- FRONT -->
    <div class="card front">
        <div style="font-size:18px;font-weight:bold;">Colegio de Los Baños</div>
        <div>CDLB</div>
        <div>Los Baños, Laguna</div>

        <!-- Use photo helper -->
        <div class="photo">
            <?= displayPhoto($row['photo']) ?>
        </div>

        <div class="info">LRN: <?= htmlspecialchars($row['lrn']) ?></div>
        <div class="info" style="font-size:20px;"><?= htmlspecialchars($row['full_name']) ?></div>
        <div class="info">ID: <?= htmlspecialchars($row['id_number']) ?></div>
        <div class="info"><?= studentLevel($row) ?></div>
    </div>

    <!-- BACK -->
    <div class="card back">
        <h3>Student Details</h3>

        <div class="box"><strong>Address:</strong><?= htmlspecialchars($row['home_address']) ?></div>
        <div class="box"><strong>Guardian:</strong><?= htmlspecialchars($row['guardian_name']) ?></div>
        <div class="box"><strong>Contact:</strong><?= htmlspecialchars($row['guardian_contact']) ?></div>
        <div class="box"><strong>Level:</strong><?= studentLevel($row) ?></div>

        <div class="box" style="font-size:11px;">
            <strong>TERMS</strong>
            <ol>
                <li>Non-transferable</li>
                <li>Must be worn inside campus</li>
                <li>Tampering voids this ID</li>
            </ol>
        </div>

        <div class="sign"><div>Registrar</div><div>Director</div></div>
    </div>

</div>

<script>
document.getElementById('printBtn').addEventListener('click', function(){
    // Open print dialog
    window.print();

    // After print, confirm archiving
    if(confirm("Did you complete printing? Click OK to archive the student.")){
        fetch("archive_stud.php", {
        method: "POST",
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: "id=<?= $row['id'] ?>",
        credentials: "same-origin"
    })
    .then(response => response.text())
    .then(data => {
        if(data.trim() === "ok"){
            alert("Student archived successfully!");
            window.location.href = "archive.php";
        } else {
            alert("Archiving failed: " + data);
        }
    })
    .catch(err => alert("Error: " + err));
}});
</script>

</body>
</html>
