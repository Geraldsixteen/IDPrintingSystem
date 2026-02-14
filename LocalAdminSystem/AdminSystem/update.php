<?php
require_once __DIR__ . '/admin-auth.php';
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/photo-helper.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// ================= INITIALIZE =================
$msg = '';
$formData = [
    'lrn'=>'', 'full_name'=>'', 'id_number'=>'', 'grade'=>'', 
    'strand'=>'', 'course'=>'', 'home_address'=>'', 
    'guardian_name'=>'', 'guardian_contact'=>'', 'photo'=>null
];

if (!isset($_GET['id'])) die("No record ID specified.");
$id = intval($_GET['id']);

// Fetch record
$stmt = $pdo->prepare("SELECT * FROM register WHERE id = :id");
$stmt->execute(['id' => $id]);
$formData = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$formData) die("Record not found.");

// Upload folder
$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

$currentPhoto = $formData['photo'] ?? null;

// ================= HANDLE FORM POST =================
if ($_SERVER['REQUEST_METHOD']==='POST') {

    // Sanitize input
    $fields = ['lrn','full_name','id_number','grade','strand','course','home_address','guardian_name','guardian_contact'];
    foreach ($fields as $key) {
        $formData[$key] = trim($_POST[$key] ?? '');
    }

    // Handle level selection (grade/strand/course)
    if (!empty($_POST['level'])) {
        list($type, $value) = explode(':', $_POST['level']);
        $formData['grade'] = $formData['strand'] = $formData['course'] = '';
        $formData[$type] = $value;
    }

    // ================= PHOTO HANDLING =================
    if (!empty($_FILES['photo']['tmp_name'])) {
        $imageInfo = getimagesize($_FILES['photo']['tmp_name']);
        if ($imageInfo === false) {
            $msg = "Invalid image.";
        } else {
            $allowed = ['image/jpeg','image/png','image/gif'];
            if (!in_array($imageInfo['mime'], $allowed)) {
                $msg = "Only JPG, PNG, GIF allowed.";
            } elseif ($_FILES['photo']['size'] > 3*1024*1024) {
                $msg = "Max 3MB.";
            } else {
                // Unique filename: ID_timestamp.ext
                $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                $filename = $id . '_' . time() . '.' . $ext;
                $destPath = $uploadDir . $filename;

                if (move_uploaded_file($_FILES['photo']['tmp_name'], $destPath)) {
                    // Delete old photo
                    if (!empty($currentPhoto)) {
                        $oldPath = $uploadDir . $currentPhoto;
                        if (file_exists($oldPath)) unlink($oldPath);
                    }
                    $formData['photo'] = $filename;
                } else {
                    $msg = "Failed to upload photo.";
                }
            }
        }
    } else {
        $formData['photo'] = $currentPhoto;
    }

    // ================= UPDATE DATABASE =================
    if ($msg === '') {
        $stmtUpd = $pdo->prepare("
            UPDATE register SET
                lrn = :lrn,
                full_name = :full_name,
                id_number = :id_number,
                grade = :grade,
                strand = :strand,
                course = :course,
                home_address = :home_address,
                guardian_name = :guardian_name,
                guardian_contact = :guardian_contact,
                photo = :photo
            WHERE id = :id
        ");
        $stmtUpd->execute([
            ':lrn' => $formData['lrn'],
            ':full_name' => $formData['full_name'],
            ':id_number' => $formData['id_number'],
            ':grade' => $formData['grade'],
            ':strand' => $formData['strand'],
            ':course' => $formData['course'],
            ':home_address' => $formData['home_address'],
            ':guardian_name' => $formData['guardian_name'],
            ':guardian_contact' => $formData['guardian_contact'],
            ':photo' => $formData['photo'],
            ':id' => $id
        ]);

        $msg = "Record updated successfully!";
    }
}

// ================= THEME =================
$themeClass = (isset($_COOKIE['theme']) && $_COOKIE['theme']=='dark') ? 'dark' : '';
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
body.dark .topbar { background:#111; border-bottom:1px solid white; color:#fff; transition:0.3s; }
label{ display:block; margin:12px 0 6px; font-weight:600; transition:0.3s; }
input, select, button { padding:12px; width:100%; margin-bottom:15px; border:1px solid #ccc; border-radius:6px; font-size:15px; transition:0.3s; }
input:focus, select:focus{ border-color:#3549a3; outline:none; box-shadow:0 0 6px rgba(53,73,163,0.2); }
body.dark input, body.dark select { background:#2c2c2c; color:#eaeaea; border:1px solid #555; }
button{ background:#3549a3; color:white; border:none; cursor:pointer; font-weight:600; transition:0.3s; border-radius:25px; }
button:hover{ background:#2d3a80; }
.current-photo{ max-width:150px; height:auto; margin-top:10px; border-radius:4px; border:1px solid #ccc; }
.message{text-align:center;padding:10px;margin-bottom:10px;border-radius:5px;font-weight:600;}
.success{background:#2ecc71;color:#fff;}
.error{background:#e74c3c;color:#fff;}
</style>

<script src="../theme.js"></script>

</head>
<body class="<?= $themeClass ?>">

<div class="sidebar">
    <div>
        <h2>ID System</h2>
        <a href="index.php">🏠 Dashboard</a>
        <a href="records.php">📑 Records</a>
        <a href="archive.php">📁 Archive</a>
        <a href="logout.php">📤 Logout</a>
    </div>
    <div class="toggle-mode" onclick="toggleMode()">🌙 Dark Mode</div>
</div>
<div class="main">
    <div class="topbar">Edit Record</div>
    <div class="container">
        <div class="card">
            <?php if($msg!==''): ?>
            <div class="message <?= strpos($msg,'successfully')!==false?'success':'error' ?>"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>

            <form action="update.php?id=<?= $id ?>" method="POST" enctype="multipart/form-data">
                <label>LRN</label>
                <input type="text" name="lrn" value="<?= htmlspecialchars($formData['lrn']) ?>" required>
                <label>Full Name</label>
                <input type="text" name="full_name" value="<?= htmlspecialchars($formData['full_name']) ?>" required>
                <label>ID Number</label>
                <input type="text" name="id_number" value="<?= htmlspecialchars($formData['id_number']) ?>" required>

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
                        <?php foreach(['BSIT','BSBA','BSHM','BEED','BSED'] as $c): ?>
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
                    <?= displayPhoto($formData['photo'] ?? null, $formData['photo_blob'] ?? null) ?>
                </div>

                <button type="submit">Update Record</button>
            </form>
        </div>
    </div>
</div>

</body>

<script>
function previewImage(input){
    if(input.files && input.files[0]){
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('photoPreview').innerHTML =
            "<img src='"+e.target.result+"' style='width:70px;height:90px;object-fit:cover;border:2px solid #000'>";
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

</html>
