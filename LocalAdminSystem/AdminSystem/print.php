<?php
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/photo-helper.php';

if (!isset($_GET['id'])) die("No student selected.");

$id = intval($_GET['id']);

// Fetch student
$stmt = $pdo->prepare("SELECT * FROM register WHERE id = ?");
$stmt->execute([$id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$student) die("Student not found.");

// Helper
function studentLevel($row) {
    if (!empty($row['grade'])) return "Grade: " . htmlspecialchars($row['grade']);
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
body {margin:0;font-family:"Segoe UI",Arial,sans-serif;background:#f4f4f4;}
.wrapper {display:flex;justify-content:center;margin:30px;}
.card {width:360px;height:540px;border-radius:12px;box-shadow:0 6px 18px rgba(0,0,0,.15);padding:20px;display:flex;flex-direction:column;justify-content:space-between;background:#fff;}
.photo img {width:100%;height:100%;object-fit:cover;border:2px solid #000;border-radius:8px;}
.info {font-weight:600;margin:4px 0;}
.back {margin-left:15px;font-size:13px;text-align:left;padding:15px;}
.box {border:1px solid #ccc;border-radius:6px;padding:8px;margin-bottom:10px;}
.sign {display:flex;justify-content:space-between;margin-top:30px;font-weight:600;}
button {display:block;margin:20px auto;padding:10px 20px;font-size:16px;border:none;border-radius:8px;background:#3549a3;color:#fff;cursor:pointer;}
button:hover {background:#2d3a80;}
@media print { @page {size: landscape;margin:10mm;} button{display:none;} body{background:#fff;} }
</style>
</head>
<body>

<button id="printBtn">🖨️ Print ID</button>

<div class="wrapper">
    <div class="card front">
        <div style="font-size:18px;font-weight:bold;">Colegio de Los Baños</div>
        <div>CDLB</div>
        <div>Los Baños, Laguna</div>
        <div class="photo"><?= displayPhoto($student['photo'] ?? null, $student['photo_blob'] ?? null) ?></div>
        <div class="info">LRN: <?= htmlspecialchars($student['lrn']) ?></div>
        <div class="info" style="font-size:20px;"><?= htmlspecialchars($student['full_name']) ?></div>
        <div class="info">ID: <?= htmlspecialchars($student['id_number']) ?></div>
        <div class="info"><?= studentLevel($student) ?></div>
    </div>
    <div class="card back">
        <h3>Student Details</h3>
        <div class="box"><strong>Address:</strong> <?= htmlspecialchars($student['home_address']) ?></div>
        <div class="box"><strong>Guardian:</strong> <?= htmlspecialchars($student['guardian_name']) ?></div>
        <div class="box"><strong>Contact:</strong> <?= htmlspecialchars($student['guardian_contact']) ?></div>
        <div class="box"><strong>Level:</strong> <?= studentLevel($student) ?></div>
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
document.getElementById('printBtn').addEventListener('click', async function() {
    // Open print dialog
    window.print();

    // Ask user if printing completed
    const printed = confirm("Did you complete printing this ID? Click OK if printed, Cancel if not.");

    if (printed) {
        try {
            // Archive student
            const res = await fetch("archive_stud.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "id=<?= $id ?>"
            });
            const text = await res.text();
            if (text.trim() !== "ok" && text.trim() !== "already archived") {
                alert("Archiving failed: " + text);
                return;
            }

            alert("ID printed and archived successfully!");
            window.location.href = "archive.php"; // go to archive

        } catch (err) {
            alert("Error: " + err);
        }
    } else {
        alert("Printing canceled. Staying on records page.");
        window.location.href = "records.php"; // back to records
    }
});
</script>

</body>
</html>
