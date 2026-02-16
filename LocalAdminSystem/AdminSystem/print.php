<?php 
require_once __DIR__ . '/admin-auth.php';
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/photo-helper.php';

if (!isset($_GET['id'])) die("No student selected.");
$id = intval($_GET['id']);

// Fetch student
$stmt = $pdo->prepare("SELECT * FROM register WHERE id = ?");
$stmt->execute([$id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$student) die("Student not found.");

function studentLevel($row) {
    if (!empty($row['grade'])) return htmlspecialchars($row['grade']);
    if (!empty($row['strand'])) return htmlspecialchars($row['strand']);
    if (!empty($row['course'])) return htmlspecialchars($row['course']);
    return "";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Print Student ID</title>
<style>
@page { size: 120mm 90mm; margin:0; }

html, body {
    margin:0; padding:0;
    width:120mm; height:90mm;
    font-family: Arial, sans-serif;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
    background:white;
}

/* Tray container */
.wrapper {
    display:flex; justify-content:space-between; align-items:center;
    width:120mm; height:90mm;
    padding:0; box-sizing:border-box;
}

/* PVC card */
.card {
    position:relative; width:54mm; height:85.6mm; overflow:hidden;
    border:0.1mm solid transparent; /* optional */
}

/* Background */
.id-bg { position:absolute; width:100%; height:100%; object-fit:cover; left:0; top:0; }

/* Crop marks */
.crop { position:absolute; width:4mm; height:4mm; border:1px solid black; }
.crop.tl { top:0; left:0; border-right:none; border-bottom:none; }
.crop.tr { top:0; right:0; border-left:none; border-bottom:none; }
.crop.bl { bottom:0; left:0; border-right:none; border-top:none; }
.crop.br { bottom:0; right:0; border-left:none; border-top:none; }

/* Card content */
.vertical-layout {
    position:absolute; inset:0;
    display:flex; flex-direction:column;
    align-items:center; justify-content:center; gap:1.5mm;
    text-align:center;
}

/* Photo */
.photo { width:22mm; height:28mm; margin-bottom:1.5mm; border-radius:2px; }
.photo img { width:100%; height:100%; object-fit:cover; }

/* Text */
.lrn { font-size:7pt; color:#fff; font-weight:bold; }
.name { font-size:10pt; color:#fff; font-weight:bold; }
.idnum { font-size:8pt; color:#fff; font-weight:bold; }
.level { font-size:12pt; color:#00ff2a; font-weight:bold; text-shadow:1px 1px black; }

/* Back card */
.box { font-size:8pt; color:#fff; text-align:left; width:90%; margin:1mm 0; }

/* Print button */
#printBtn {
    display:block; margin:10px auto; padding:10px 20px; font-size:16px;
    border:none; border-radius:8px; background:#3549a3; color:#fff; cursor:pointer;
}
#printBtn:hover { background:#2d3a80; }

@media print { #printBtn { display:none; } }
</style>
</head>
<body>

<button id="printBtn">🖨️ Print ID</button>

<div class="wrapper">

<!-- Left: Front -->
<div class="card">
    <img class="id-bg" src="IDFront.png">
    <div class="vertical-layout">
        <div class="photo"><?= displayPhoto($student['photo'] ?? null, $student['photo_blob'] ?? null) ?></div>
        <div class="lrn">LRN: <?= htmlspecialchars($student['lrn']) ?></div>
        <div class="name"><?= htmlspecialchars($student['full_name']) ?></div>
        <div class="idnum">ID: <?= htmlspecialchars($student['id_number']) ?></div>
        <div class="level"><?= studentLevel($student) ?></div>
    </div>
    <div class="crop tl"></div><div class="crop tr"></div><div class="crop bl"></div><div class="crop br"></div>
</div>

<!-- Right: Back -->
<div class="card">
    <img class="id-bg" src="IDBack.png">
    <div class="vertical-layout">
        <div class="box"><strong>Address:</strong> <?= htmlspecialchars($student['home_address']) ?></div>
        <div class="box"><strong>Guardian:</strong> <?= htmlspecialchars($student['guardian_name']) ?></div>
        <div class="box"><strong>Contact:</strong> <?= htmlspecialchars($student['guardian_contact']) ?></div>
        <div class="box"><strong>Level:</strong> <?= studentLevel($student) ?></div>
    </div>
    <div class="crop tl"></div><div class="crop tr"></div><div class="crop bl"></div><div class="crop br"></div>
</div>

</div>

<script>
document.getElementById('printBtn').addEventListener('click', async function(){
    window.print();

    const printed = confirm("Did you complete printing this ID? Click OK if printed, Cancel if not.");
    if(!printed){ alert("Printing canceled."); window.location.href="records.php"; return; }

    try{
        const res = await fetch("archive_stud.php", {
            method:"POST",
            headers:{"Content-Type":"application/x-www-form-urlencoded"},
            body:"id=<?= $id ?>"
        });
        const text = await res.text();
        if(text.trim() !== "ok" && text.trim() !== "already archived"){ alert("Archiving failed: "+text); return; }
        alert("ID printed and archived successfully!");
        window.location.href="archive.php";
    }catch(err){ alert("Error: "+err); }
});
</script>

</body>
</html>
