<?php
require_once __DIR__ . '/admin-auth.php';
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/photo-helper.php';

$msg = '';

if (!isset($_GET['id'])) die("No record ID specified.");
$id = intval($_GET['id']);

// ---------------- FETCH RECORD ----------------
$stmt = $pdo->prepare("SELECT * FROM register WHERE id = :id");
$stmt->execute([':id' => $id]);
$formData = $stmt->fetch(PDO::FETCH_ASSOC);
$stmtOld = $formData; // store original data for comparison
if (!$formData) die("Record not found.");

// ---------------- UPLOAD DIR ----------------
$uploadDir = __DIR__ . '/Uploads/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

// ---------------- HANDLE FORM SUBMIT ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Collect fields
    $fields = ['lrn', 'full_name', 'grade', 'strand', 'course', 'home_address', 'guardian_name', 'guardian_contact'];
    foreach ($fields as $f) {
        $formData[$f] = trim($_POST[$f] ?? '');
    }

    // Handle level selector (grade/strand/course)
    if (!empty($_POST['level'])) {
        list($type, $value) = explode(':', $_POST['level']);
        $formData['grade'] = $formData['strand'] = $formData['course'] = '';
        $formData[$type] = $value;
    }

    // ---------------- HANDLE PHOTO ----------------
    if (!empty($_FILES['photo']['tmp_name'])) {
        $info = getimagesize($_FILES['photo']['tmp_name']);
        if (!$info) {
            $msg = "Invalid image.";
        } else {
            $filename = $id . '_' . time() . '.jpg';
            $dest = $uploadDir . $filename;

            // Resize to 300x400
            $src = imagecreatefromstring(file_get_contents($_FILES['photo']['tmp_name']));
            $dst = imagecreatetruecolor(300, 400);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, 300, 400, imagesx($src), imagesy($src));
            imagejpeg($dst, $dest, 85);
            imagedestroy($src);
            imagedestroy($dst);

            $blob = file_get_contents($dest);

            // Duplicate photo check
            $stmtCheck = $pdo->prepare("SELECT id, photo_blob FROM register WHERE id != :id");
            $stmtCheck->execute([':id'=>$id]);
            $all = $stmtCheck->fetchAll(PDO::FETCH_ASSOC);

            foreach ($all as $row) {
                $existingBlob = $row['photo_blob'];
                if (is_resource($existingBlob)) $existingBlob = stream_get_contents($existingBlob);
                if (sha1($existingBlob) === sha1($blob)) {
                    unlink($dest);
                    $msg = "Duplicate photo detected.";
                    break;
                }
            }

            // Save if no duplicate
            if ($msg === '') {
                if (!empty($formData['photo'])) {
                    $old = $uploadDir . $formData['photo'];
                    if (file_exists($old)) unlink($old);
                }
                $formData['photo'] = $filename;
                $formData['photo_blob'] = $blob;
            }
        }
    }

    // ---------------- CHECK CHANGES ----------------
    $changed = false;
    $compareFields = ['lrn','full_name','grade','strand','course','home_address','guardian_name','guardian_contact'];
    foreach ($compareFields as $f) {
        if (($formData[$f] ?? '') != ($stmtOld[$f] ?? '')) {
            $changed = true;
            break;
        }
    }
    if (!empty($_FILES['photo']['tmp_name'])) $changed = true;

    if (!$changed) {
        echo "<script>
            alert('⚠ Nothing new to update.');
            window.location='update.php?id=$id';
        </script>";
        exit;
    }

   
    $stmt = $pdo->prepare("
        UPDATE register SET
            lrn=:lrn,
            full_name=:full_name,
            grade=:grade,
            strand=:strand,
            course=:course,
            home_address=:home_address,
            guardian_name=:guardian_name,
            guardian_contact=:guardian_contact,
            photo=:photo,
            photo_blob=:photo_blob
        WHERE id=:id
    ");

    $stmt->bindValue(':lrn', $formData['lrn']);
    $stmt->bindValue(':full_name', $formData['full_name']);
    $stmt->bindValue(':grade', $formData['grade']);
    $stmt->bindValue(':strand', $formData['strand']);
    $stmt->bindValue(':course', $formData['course']);
    $stmt->bindValue(':home_address', $formData['home_address']);
    $stmt->bindValue(':guardian_name', $formData['guardian_name']);
    $stmt->bindValue(':guardian_contact', $formData['guardian_contact']);
    $stmt->bindValue(':photo', $formData['photo']);
    $stmt->bindValue(':photo_blob', $formData['photo_blob'] ?? null, PDO::PARAM_LOB);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);

    $stmt->execute();

    header("Location: records.php?updated=1");
    exit;
}


$themeClass = (isset($_COOKIE['theme']) && $_COOKIE['theme'] == 'dark') ? 'dark' : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Record - ID Printing System</title>
<link rel="stylesheet" href="../style.css">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:"Segoe UI", Arial, sans-serif; background:#c9dfff; display:flex; min-height:100vh; color:#222; transition:0.3s; }
.sidebar a:hover, .sidebar a.active { background:#1f2857; }
.main { flex:1; display:flex; flex-direction:column; }
.container { flex:1; display:flex; justify-content:center; padding:20px; transition:0.3s; }
.card { background:#fff; padding:25px; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.15); overflow-x:auto; max-height:85vh; overflow-y:auto; width:100%; max-width:700px; transition:0.3s; }
.card::-webkit-scrollbar { width:6px; }
.card::-webkit-scrollbar-thumb { background:#3549a3; border-radius:3px; }
body.dark .card { background: #1e1e1e; color:#eaeaea; }
.topbar a{ position: absolute; top: 10px; right: 20px; color:#fff; text-decoration:none; font-size:24px; border-radius: 20px; padding: 5px 12px 7px 12px; font-weight:bolder; background:rgba(0, 0, 0, 0.4); color: black; border:none; cursor:pointer; transition:0.3s; }
.topbar a:hover { background:rgba(0, 0, 0, 0.6); color: white; transform:scale(1.1); transition:0.3s; }
body.dark .topbar a:hover { background:rgba(255, 255, 255, 0.6); color: black;  }
body.dark .topbar a { background:rgba(255, 255, 255, 0.4); color: white; }
.topbar { background:rgb(255, 255, 255); color:black; padding:15px 20px; font-size:25px; font-weight:600; border-bottom:1px solid #ccc; transition:0.3s; }
body.dark .topbar { background:#111; border-bottom:1px solid white; color:#fff; transition:0.3s; }
label{ display:block; margin:12px 0 6px; font-weight:600; transition:0.3s; }
input, select, button { padding:12px; width:100%; margin-bottom:15px; border:1px solid #ccc; border-radius:6px; font-size:15px; transition:0.3s; }
input:focus, select:focus{ border-color:#3549a3; outline:none!important ; box-shadow:0 0 6px rgba(53,73,163,0.2); }
body.dark input, body.dark select { background:#2c2c2c; color:#eaeaea; border:1px solid #555; }
button{ background:#3549a3; color:white; border:none; cursor:pointer; font-weight:600; transition:0.3s; border-radius:25px; }
button:hover{ background:#2d3a80; }
.current-photo{ max-width:150px; height:auto; margin-top:10px; border-radius:4px; border:1px solid #ccc; }
.message{text-align:center;padding:10px;margin-bottom:10px;border-radius:5px;font-weight:600;}
.success{background:#2ecc71;color:#fff;}
.error{background:#e74c3c;color:#fff;}
.photoPreview { width:70px; height:90px; object-fit:cover; border:2px solid #000; border-radius:5px; }
</style>
</head>
<body class="<?= $themeClass ?>">
<div class="sidebar">
    <div>
        <h2>ID System</h2>
        <a href="index.php">🏠 Dashboard</a>
        <a href="admin-review.php">📝 Review</a>
        <a href="records.php">📑 Records</a>
        <a href="archive.php">📁 Archive</a>
        <a href="logout.php" onclick="return confirm('Are you sure you want to logout?')">📤 Logout</a>
    </div>
    <div class="toggle-mode" onclick="toggleMode()">🌙 Dark Mode</div>
</div>
<div class="main">
    <div class="topbar"><a href="records.php">&times;</a>Edit Record</div>
    <div class="container">
        <div class="card">
            <form action="update.php?id=<?= $id ?>" method="POST" enctype="multipart/form-data">
                <label>LRN</label>
                <input type="text" name="lrn" value="<?= htmlspecialchars($formData['lrn']) ?>" required>

                <label>Full Name</label>
                <input type="text" name="full_name" value="<?= htmlspecialchars($formData['full_name']) ?>" required>

                <label>Grade / Strand / Course</label>
                <select name="level">
                    <optgroup label="Grades">
                        <?php foreach(['Grade 7','Grade 8','Grade 9','Grade 10'] as $g): ?>
                        <option value="grade:<?= $g ?>" <?= $formData['grade']==$g?'selected':'' ?>><?= $g ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                    <optgroup label="Strands">
                        <?php foreach(['HUMMS','ABM','STEM','GAS','ICT'] as $s): ?>
                        <option value="strand:<?= $s ?>" <?= $formData['strand']==$s?'selected':'' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                    <optgroup label="Courses">
                        <?php foreach(['BSBA','BSE','BEE','BSCS','BAE'] as $c): ?>
                        <option value="course:<?= $c ?>" <?= $formData['course']==$c?'selected':'' ?>><?= $c ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                </select>

                <label>Home Address</label>
                <input type="text" name="home_address" value="<?= htmlspecialchars($formData['home_address']) ?>" required>

                <label>Guardian's Name</label>
                <input type="text" name="guardian_name" value="<?= htmlspecialchars($formData['guardian_name']) ?>" required>

                <label>Guardian's Contact Number</label>
                <input type="text" name="guardian_contact" value="<?= htmlspecialchars($formData['guardian_contact']) ?>" required>

                <label>Upload Photo (Leave empty to keep current)</label>
                <input type="file" name="photo" accept="image/*" onchange="previewImage(this)">

                <div id="photoPreview">
                    <?php $previewURL = getStudentPhotoUrl($formData); ?>
                    <img id="previewImg" src="<?= $previewURL ?>" class="photoPreview" alt="Photo">
                </div>

                <button type="submit">Update Record</button>
            </form>
        </div>
    </div>
</div>

<script>
function previewImage(input){
    if(input.files && input.files[0]){
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('previewImg').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
<script src="../theme.js"></script>
</body>
</html>
