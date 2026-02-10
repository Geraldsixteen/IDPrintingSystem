<?php  
require_once __DIR__ . '/admin-auth.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/../config/database.php';

$msg = '';
$formData = [
    'lrn'=>'', 'full_name'=>'', 'id_number'=>'', 'grade'=>'', 
    'strand'=>'', 'course'=>'', 'home_address'=>'', 
    'guardian_name'=>'', 'guardian_contact'=>'', 'upload_photo'=>''
];

if (!isset($_GET['id'])) die("No record ID specified.");
$id = intval($_GET['id']);

$stmt = $pdo->prepare("SELECT * FROM register WHERE id = :id");
$stmt->execute(['id' => $id]);
$formData = $stmt->fetch();
if (!$formData) die("Record not found.");

if ($_SERVER['REQUEST_METHOD']==='POST') {

    $fields = ['lrn','full_name','id_number','grade','strand','course','home_address','guardian_name','guardian_contact'];
    foreach ($fields as $key) {
        $formData[$key] = trim($_POST[$key] ?? '');
    }

    // ================= FIX GRADE / STRAND / COURSE =================

// Detect which one user actually selected
$selected = '';

if (!empty($_POST['grade']))   $selected = 'grade';
if (!empty($_POST['strand'])) $selected = 'strand';
if (!empty($_POST['course'])) $selected = 'course';

// Reset all first
$formData['grade']  = '';
$formData['strand'] = '';
$formData['course'] = '';

// Apply only the selected value
if ($selected === 'grade') {
    $formData['grade'] = $_POST['grade'];
}

if ($selected === 'strand') {
    $formData['strand'] = $_POST['strand'];
}

if ($selected === 'course') {
    $formData['course'] = $_POST['course'];
}

// =============================================================

    // ===============================================================================

    if (isset($_FILES['upload_photo']) && $_FILES['upload_photo']['error']===0){
        $allowed=['jpg','jpeg','png','gif'];
        $ext=strtolower(pathinfo($_FILES['upload_photo']['name'],PATHINFO_EXTENSION));
        if(in_array($ext,$allowed)){
            $upload_dir='../uploads/';
            if(!is_dir($upload_dir)) mkdir($upload_dir,0755,true);
            $new_photo=time().'_'.basename($_FILES['upload_photo']['name']);
            if(move_uploaded_file($_FILES['upload_photo']['tmp_name'],$upload_dir.$new_photo)){
                if(!empty($formData['upload_photo']) && file_exists($upload_dir.$formData['upload_photo'])){
                    unlink($upload_dir.$formData['upload_photo']);
                }
                $formData['upload_photo']=$new_photo;
            } else $msg="Failed to upload photo.";
        } else $msg="Invalid file type.";
    }

    if ($msg===''){
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
                upload_photo = :upload_photo
            WHERE id = :id
        ");

        $success = $stmtUpd->execute([
            'lrn' => $formData['lrn'],
            'full_name' => $formData['full_name'],
            'id_number' => $formData['id_number'],
            'grade' => $formData['grade'],
            'strand' => $formData['strand'],
            'course' => $formData['course'],
            'home_address' => $formData['home_address'],
            'guardian_name' => $formData['guardian_name'],
            'guardian_contact' => $formData['guardian_contact'],
            'upload_photo' => $formData['upload_photo'],
            'id' => $id
        ]);

        $msg = $success ? "Record updated successfully!" : "Database error!";
    }
}

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
body.dark .topbar { background:#111; border-bottom:1px solid white; color:#fff; }
label{ display:block; margin:12px 0 6px; font-weight:600; }
input, select, button { padding:12px; width:100%; margin-bottom:15px; border:1px solid #ccc; border-radius:6px; font-size:15px; transition:0.3s; }
input:focus, select:focus{ border-color:#3549a3; outline:none; box-shadow:0 0 6px rgba(53,73,163,0.2); }
button{ background:#3549a3; color:white; border:none; cursor:pointer; font-weight:600; transition:0.3s; border-radius:25px; }
button:hover{ background:#2d3a80; }
.current-photo{ max-width:150px; height:auto; margin-top:10px; border-radius:4px; border:1px solid #ccc; }
.message{text-align:center;padding:10px;margin-bottom:10px;border-radius:5px;font-weight:600;}
.success{background:#2ecc71;color:#fff;}
.error{background:#e74c3c;color:#fff;}
body.dark .toggle-mode{ background:#3549a3; color:#fff; }
body.dark .toggle-mode:hover{ background:#2a3a80; }
</style>
<script>
function previewImage(input){
    const preview=document.getElementById('photoPreview');
    if(input.files && input.files[0]){
        const reader=new FileReader();
        reader.onload=e=>preview.src=e.target.result;
        reader.readAsDataURL(input.files[0]);
        preview.style.display='block';
    }
}
function toggleMode(){
    const isDark=document.body.classList.toggle('dark');
    document.cookie="theme="+(isDark?"dark":"light")+"; path=/";
    document.getElementById('toggleIcon').textContent = isDark ? '☀ Light' : '🌙 Dark';
}
(function(){
    const theme=document.cookie.split('; ').find(row=>row.startsWith('theme='))?.split('=')[1];
    if(theme==='dark') document.body.classList.add('dark');
})();
let toggleIndex=0;
function cycleField(){
    const gradeField=document.getElementById('gradeField');
    const strandField=document.getElementById('strandField');
    const courseField=document.getElementById('courseField');
    gradeField.style.display='none';
    strandField.style.display='none';
    courseField.style.display='none';
    const fields=['grade','strand','course'];
    const current=fields[toggleIndex % fields.length];
    if(current==='grade') gradeField.style.display='block';
    if(current==='strand') strandField.style.display='block';
    if(current==='course') courseField.style.display='block';
    toggleIndex++;
}
</script>
</head>
<body class="<?= $themeClass ?>">

<div class="sidebar">
    <div>
        <h2>ID System</h2>
        <a href="layout.php">🏠 Dashboard</a>
        <a href="print-id.php">🖨️ Print</a>
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

                <button type="button" onclick="cycleField()">Toggle: Grade / Strand / Course</button>

                <div id="gradeField" style="display:none;">
                    <label>Grade Level</label>
                    <select name="grade">
                        <option value="" disabled>-- Select Grade --</option>
                        <?php foreach(['Grade 7','Grade 8','Grade 9','Grade 10'] as $g): ?>
                        <option value="<?= $g ?>" <?= $formData['grade']==$g?'selected':'' ?>><?= $g ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div id="strandField" style="display:none;">
                    <label>Strand</label>
                    <select name="strand">
                        <option value="">-- Select Strand --</option>
                        <?php foreach(['HUMMS','ABM','STEM','GAS','ICT'] as $s): ?>
                        <option value="<?= $s ?>" <?= $formData['strand']==$s?'selected':'' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div id="courseField" style="display:none;">
                    <label>Course</label>
                    <select name="course">
                        <option value="">-- Select Course --</option>
                        <?php foreach(['BSIT','BSBA','BSHM','BEED','BSED'] as $c): ?>
                        <option value="<?= $c ?>" <?= $formData['course']==$c?'selected':'' ?>><?= $c ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <label>Home Address</label>
                <input type="text" name="home_address" value="<?= htmlspecialchars($formData['home_address']) ?>" required>
                <label>Guardian's Name</label>
                <input type="text" name="guardian_name" value="<?= htmlspecialchars($formData['guardian_name']) ?>" required>
                <label>Guardian's Contact Number</label>
                <input type="text" name="guardian_contact" value="<?= htmlspecialchars($formData['guardian_contact']) ?>" required>

                <label>Upload Photo (Leave empty to keep current)</label>
                <input type="file" name="upload_photo" accept="image/*" onchange="previewImage(this)">
                <?php if(!empty($formData['upload_photo'])): ?>
                    <img src="../uploads/<?= htmlspecialchars($formData['upload_photo']) ?>" id="photoPreview" class="current-photo">
                <?php else: ?>
                    <img id="photoPreview" class="current-photo" style="display:none;">
                <?php endif; ?>

                <button type="submit">Update Record</button>
            </form>
        </div>
    </div>
</div>
<script src="../theme.js"></script>
</body>
</html>
