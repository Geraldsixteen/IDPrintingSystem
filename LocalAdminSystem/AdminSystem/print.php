<?php
require_once __DIR__ . '/admin-auth.php';
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/photo-helper.php';
require_once __DIR__ . '/../vendor/autoload.php'; // dompdf

use Dompdf\Dompdf;
use Dompdf\Options;

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

// ================= GENERATE HTML FOR PDF =================
ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student IDs</title>
<style>
body{font-family:"Segoe UI",Arial,sans-serif;margin:0;padding:0;}
.card{width:360px;height:540px;border:1px solid #000;margin:10px;padding:10px;box-sizing:border-box;display:flex;flex-direction:column;justify-content:space-between;}
.photo{width:180px;height:180px;object-fit:cover;border:2px solid #000;border-radius:8px;margin:0 auto;}
.info{font-weight:600;margin:4px 0;text-align:center;}
.back{font-size:12px;text-align:left;}
</style>
</head>
<body>
<?php foreach($students as $row): ?>
    <div class="card">
        <div style="text-align:center;font-weight:bold;">Colegio de Los Baños</div>
        <div class="photo">
            <?= displayPhoto($row['photo'] ?? null, $row['photo_blob'] ?? null) ?>
        </div>
        <div class="info"><?= htmlspecialchars($row['full_name']) ?></div>
        <div class="info">LRN: <?= htmlspecialchars($row['lrn']) ?></div>
        <div class="info">ID: <?= htmlspecialchars($row['id_number']) ?></div>
        <div class="info"><?= studentLevel($row) ?></div>
        <div class="back">
            <strong>Address:</strong> <?= htmlspecialchars($row['home_address']) ?><br>
            <strong>Guardian:</strong> <?= htmlspecialchars($row['guardian_name']) ?><br>
            <strong>Contact:</strong> <?= htmlspecialchars($row['guardian_contact']) ?><br>
        </div>
    </div>
<?php endforeach; ?>
</body>
</html>
<?php
$html = ob_get_clean();

// ================= GENERATE PDF =================
$options = new Options();
$options->set('isRemoteEnabled', true); // allow images from local/URLs
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Save PDF temporarily
$tempPdf = __DIR__ . '/temp_ids.pdf';
file_put_contents($tempPdf, $dompdf->output());

// ================= PRINT DIRECTLY TO PRINTER =================
$printerName = "L3110 Series"; // change this to your printer name anytime
exec("print /D:\"$printerName\" \"$tempPdf\"");

// Optional: delete temporary PDF after sending to printer
unlink($tempPdf);

echo "Sent to printer: $printerName";

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Print Student IDs</title>
<style>
body{margin:0;font-family:"Segoe UI",Arial,sans-serif;background:#f4f4f4;}
#printBtn{display:block;margin:20px auto;padding:10px 20px;font-size:16px;background:#3549a3;color:white;border:none;border-radius:8px;cursor:pointer;}
#printBtn:hover{background:#2d3a80;}
.wrapper{display:flex;flex-wrap:wrap;gap:40px;justify-content:center;margin:30px;}
.card{width:360px;height:540px;border-radius:12px;box-shadow:0 6px 18px rgba(0,0,0,.15);padding:20px;box-sizing:border-box;background:#fff;display:flex;flex-direction:column;justify-content:space-between;}
.front,.back{text-align:center;}
.photo{width:180px;height:180px;object-fit:cover;border:2px solid #000;border-radius:8px;margin:20px auto;}
.info{font-weight:600;margin:4px 0;}
.back{font-size:13px;text-align:left;padding:15px;}
.box{border:1px solid #ccc;border-radius:6px;padding:8px;margin-bottom:10px;}
.box strong{display:block;margin-bottom:3px;}
.sign{display:flex;justify-content:space-between;margin-top:30px;font-weight:600;}
h3{text-align:center;margin-bottom:10px;}
@media print{
    body{margin:0;background:#fff;}
    #printBtn{display:none;}
    .wrapper{gap:0;justify-content:flex-start;flex-wrap:wrap;}
    .card{margin-bottom:20px;page-break-inside:avoid;}
}
</style>
</head>
<body>

<button id="printBtn">🖨️ Print</button>

<div class="wrapper">

<?php foreach($students as $row): ?>

    <!-- FRONT -->
    <div class="card front">
        <div style="font-size:18px;font-weight:bold;">Colegio de Los Baños</div>
        <div>CDLB</div>
        <div>Los Baños, Laguna</div>

        <!-- PHOTO -->
        <div class="photo">
            <?= displayPhoto($row['photo'] ?? null, $row['photo_blob'] ?? null) ?>
        </div>

        <div class="info">LRN: <?= htmlspecialchars($row['lrn']) ?></div>
        <div class="info" style="font-size:20px;"><?= htmlspecialchars($row['full_name']) ?></div>
        <div class="info">ID: <?= htmlspecialchars($row['id_number']) ?></div>
        <div class="info"><?= studentLevel($row) ?></div>
    </div>

    <!-- BACK -->
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
    window.print();

    if(confirm("Printing done? Click OK to archive all printed students.")){
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
            }
        })
        .catch(err => alert("Error: " + err));
    }
});
</script>

</body>
</html>
