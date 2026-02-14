<?php  
require_once __DIR__ . '/admin-auth.php';
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/photo-helper.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// ================= GET STUDENTS =================
$idsArray = [];

if (isset($_GET['id'])) {
    $idsArray[] = intval($_GET['id']);
} elseif (isset($_GET['ids']) && !empty(trim($_GET['ids']))) {
    $idsArray = array_filter(array_map('intval', explode(',', $_GET['ids'])));
}

if (empty($idsArray)) die("No students selected.");

// Fetch students
$inQuery = implode(',', array_fill(0, count($idsArray), '?'));
$stmt = $pdo->prepare("SELECT * FROM register WHERE id IN ($inQuery) ORDER BY full_name ASC");
$stmt->execute($idsArray);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (!$students) die("No students found.");

// ================= HELPER =================
function studentLevel($row) {
    if (!empty($row['grade']))  return "Grade: " . htmlspecialchars($row['grade']);
    if (!empty($row['strand'])) return "Strand: " . htmlspecialchars($row['strand']);
    if (!empty($row['course'])) return "Course: " . htmlspecialchars($row['course']);
    return "";
}

// Default USB printer
$defaultPrinter = "L3110 Series";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Print Student IDs</title>
<style>
.photo {
    width: 210px;
    height: 210px;
    margin: 20px auto 10px auto; /* top, sides auto for centering, bottom 10px */
    border: 2px solid #000;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}
.photo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}   
body{margin:0;font-family:"Segoe UI",Arial,sans-serif;background:#f4f4f4;}
.wrapper{display:flex;flex-wrap:wrap;gap:40px;justify-content:center;margin:30px;}
.card{width:360px;height:540px;border-radius:12px;box-shadow:0 6px 18px rgba(0,0,0,.15);padding:20px;box-sizing:border-box;background:#fff;display:flex;flex-direction:column;justify-content:space-between;}
.front,.back{text-align:center;}
.info{font-weight:600;margin:4px 0;}
.back{margin-left:15px;font-size:13px;text-align:left;padding:15px;}
.box{border:1px solid #ccc;border-radius:6px;padding:8px;margin-bottom:10px;}
.box strong{display:block;margin-bottom:3px;}
.sign{display:flex;justify-content:space-between;margin-top:30px;font-weight:600;}
h3{text-align:center;margin-bottom:10px;}
button{display:block;margin:20px auto;padding:10px 20px;font-size:16px;border:none;border-radius:8px;background:#3549a3;color:#fff;cursor:pointer;}
button:hover{background:#2d3a80;}

/* PRINT STYLES */
@media print{
    @page {
        size: landscape;
        margin: 10mm;
    }

    body{margin:0;background:#fff;}
    button{display:none;}
    .wrapper{gap:0;justify-content:flex-start;flex-wrap:wrap;}
    .card{margin-left:20px;margin-bottom:20px;page-break-inside:avoid;}
}
</style>
</head>
<body>

<button id="printBtn">🖨️ Print IDs</button>

<div class="wrapper">
<?php foreach($students as $row): ?>
    <div class="card front">
        <div style="font-size:18px;font-weight:bold;">Colegio de Los Baños</div>
        <div>CDLB</div>
        <div>Los Baños, Laguna</div>
        <div class="photo"><?= displayPhoto($row['photo'] ?? null, $row['photo_blob'] ?? null) ?></div>
        <div class="info">LRN: <?= htmlspecialchars($row['lrn']) ?></div>
        <div class="info" style="font-size:20px;"><?= htmlspecialchars($row['full_name']) ?></div>
        <div class="info">ID: <?= htmlspecialchars($row['id_number']) ?></div>
        <div class="info"><?= studentLevel($row) ?></div>
    </div>

    <div class="card back">
        <h3>Student Details</h3>
        <div class="box"><strong>Address:</strong> <?= htmlspecialchars($row['home_address']) ?></div>
        <div class="box"><strong>Guardian:</strong> <?= htmlspecialchars($row['guardian_name']) ?></div>
        <div class="box"><strong>Contact:</strong> <?= htmlspecialchars($row['guardian_contact']) ?></div>
        <div class="box"><strong>Level:</strong> <?= studentLevel($row) ?></div>
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
<?php endforeach; ?>
</div>

<script>
document.getElementById('printBtn').addEventListener('click', function(){
    // Open print dialog
    window.print();

    // After print dialog is closed
    setTimeout(function(){
        const printed = confirm("Did you print the IDs successfully?");
        if(printed){
            // Archive printed students
            const ids = <?= json_encode(array_column($students,'id')) ?>;
            fetch("archive_stud.php", {
                method: "POST",
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: "ids=" + encodeURIComponent(ids.join(',')),
                credentials: "same-origin"
            })
            .then(res => res.text())
            .then(data => {
                if(data.trim() === "ok"){
                    alert("All students archived successfully!");
                    window.location.href = "archive.php";
                } else {
                    alert("Archiving failed: " + data);
                    window.location.href = "records.php";
                }
            })
            .catch(err => {
                alert("Error: " + err);
                window.location.href = "records.php";
            });
        } else {
            // Go back to records if not printed
            window.location.href = "records.php";
        }
    }, 500);
});
</script>

</body>
</html>
