<?php  
session_start();
error_reporting(E_ALL);

require_once __DIR__ . '/../Config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $lrn = trim($_POST['lrn'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $id_number = trim($_POST['id_number'] ?? '');
    $strand = trim($_POST['strand'] ?? '');
    $home_address = trim($_POST['home_address'] ?? '');
    $guardian_name = trim($_POST['guardian_name'] ?? '');
    $guardian_contact = trim($_POST['guardian_contact'] ?? '');

    if (!$lrn || !$full_name || !$id_number || !$strand) {
        echo json_encode(['success' => false, 'msg' => 'Please fill in all required fields.']);
        exit;
    }

    $photo_blob = null;
    $photo_filename = null;

    if (!empty($_FILES['photo']['tmp_name'])) {
        $tmp = $_FILES['photo']['tmp_name'];
        $info = getimagesize($tmp);
        if (!$info) { echo json_encode(['success'=>false,'msg'=>'Invalid image']); exit; }

        $src = imagecreatefromstring(file_get_contents($tmp));
        $dst = imagecreatetruecolor(300,400);
        imagecopyresampled($dst,$src,0,0,0,0,300,400,imagesx($src),imagesy($src));

        $photo_filename = $lrn.'_'.time().'.jpg';
        $uploadDir = __DIR__.'\..\uploads\\'; // Windows-compatible path
        if(!is_dir($uploadDir)) mkdir($uploadDir,0777,true);

        $photo_path = $uploadDir.$photo_filename;
        imagejpeg($dst,$photo_path,85);
        imagedestroy($src);
        imagedestroy($dst);

        $photo_blob = file_get_contents($photo_path);
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO register 
            (lrn, full_name, id_number, strand, home_address, guardian_name, guardian_contact, photo, photo_blob, created_at)
            VALUES (:lrn, :full_name, :id_number, :strand, :home_address, :guardian_name, :guardian_contact, :photo, :photo_blob, NOW())
        ");
        $stmt->bindParam(':lrn',$lrn);
        $stmt->bindParam(':full_name',$full_name);
        $stmt->bindParam(':id_number',$id_number);
        $stmt->bindParam(':strand',$strand);
        $stmt->bindParam(':home_address',$home_address);
        $stmt->bindParam(':guardian_name',$guardian_name);
        $stmt->bindParam(':guardian_contact',$guardian_contact);
        $stmt->bindParam(':photo',$photo_filename);
        $stmt->bindParam(':photo_blob',$photo_blob,PDO::PARAM_LOB);
        $stmt->execute();

        echo json_encode(['success'=>true,'msg'=>'Successfully Registered!']);
        exit;

    } catch(PDOException $e) {
        echo json_encode(['success'=>false,'msg'=>'Database error: '.$e->getMessage()]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Senior High Registration - ID Printing System</title>
<style>
/* Reset & body */
body { margin:0; font-family:"Segoe UI",Arial,sans-serif; background:#f0f4ff; display:flex; justify-content:center; padding:20px; }
.main { width:100%; max-width:480px; }

/* Topbar */
.topbar { text-align:center; margin-bottom:25px; }
.topbar img { width:80px; height:auto; display:block; margin:0 auto 10px; }
.topbar h3 { margin:0; font-size:1.4rem; color:#002b80; }

/* Card */
.card { background:#fff; padding:25px; border-radius:15px; box-shadow:0 6px 20px rgba(0,0,0,0.1); }
.card h3 { text-align:center; margin-bottom:20px; font-size:1.2rem; color:#002b80; }

/* Floating Labels */
.form-group { position: relative; margin-bottom:18px; }
.form-group input, .form-group select { width:100%; padding:14px 12px 14px 12px; border-radius:10px; border:1px solid #ccc; font-size:14px; outline:none; background:transparent; }
.form-group label { position:absolute; left:12px; top:14px; color:#999; font-size:14px; pointer-events:none; transition:0.2s all; }
.form-group input:focus + label,
.form-group input:not(:placeholder-shown) + label,
.form-group select:focus + label,
.form-group select:not([value=""]) + label { top:-8px; left:10px; font-size:12px; color:#002b80; background:#fff; padding:0 5px; }

/* Button */
.card button { margin-top:10px; width:100%; padding:14px; border:none; border-radius:12px; background:#002b80; color:white; font-weight:600; font-size:16px; cursor:pointer; transition:0.2s; }
.card button:hover { background:#1f2857; }

/* Success popup */
#popupMsg {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) scale(0.8);
    background: #28a745;
    color: white;
    padding: 20px 25px;
    border-radius: 12px;
    font-weight: 600;
    box-shadow: 0 4px 15px rgba(0,0,0,0.3);
    z-index: 9999;
    opacity: 0;
    transition: opacity 0.5s, transform 0.3s;
    text-align: center;
    min-width: 220px;
    max-width: 90%;
}
#popupMsg.show { opacity:1; transform: translate(-50%, -50%) scale(1); }
#popupMsg .checkmark { display:block; width:50px; height:50px; margin:0 auto 10px; border-radius:50%; background:white; position:relative; }
#popupMsg .checkmark:after { content:''; position:absolute; left:14px; top:8px; width:12px; height:25px; border:solid #28a745; border-width:0 4px 4px 0; transform: rotate(45deg); animation: check 0.5s ease forwards; }
@keyframes check { from {width:0; height:0;} to {width:12px; height:25px;} }

/* Responsive */
@media(max-width:500px) { body { padding:15px; } .card { padding:20px; } }
</style>
</head>
<body>
<div class="main">
    <div class="topbar">
        <img src="../Public/cdlb.png" alt="CDLB Logo">
        <h3>Senior High Student ID Registration</h3>
    </div>
    <div class="card">  
        <h3>Register Student</h3>
        <form id="reg-form" enctype="multipart/form-data">
            <div class="form-group">
                <input type="text" name="lrn" placeholder=" " required>
                <label>LRN</label>
            </div>
            <div class="form-group">
                <input type="text" name="full_name" placeholder=" " required>
                <label>Full Name</label>
            </div>
            <div class="form-group">
                <input type="text" name="id_number" placeholder=" " required>
                <label>ID Number</label>
            </div>
            <div class="form-group">
                <select name="strand" required>
                    <option value="" hidden></option>
                    <option value="STEM">STEM</option>
                    <option value="HUMMS">HUMMS</option>
                    <option value="ABM">ABM</option>
                    <option value="GAS">GAS</option>
                    <option value="ICT">ICT</option>
                </select>
                <label>Strand</label>
            </div>
            <div class="form-group">
                <input type="text" name="home_address" placeholder=" " required>
                <label>Home Address</label>
            </div>
            <div class="form-group">
                <input type="text" name="guardian_name" placeholder=" " required>
                <label>Guardian's Name</label>
            </div>
            <div class="form-group">
                <input type="text" name="guardian_contact" placeholder=" " required>
                <label>Guardian's Contact</label>
            </div>
            <div class="form-group">
                <input type="file" name="photo" accept="image/*" required>
                <label>Upload Photo</label>
            </div>
            <button type="submit">Register</button>
        </form>
    </div>
</div>

<script>
const form = document.getElementById('reg-form');
form.addEventListener('submit', function(e){
    e.preventDefault();
    const formData = new FormData(form);

    fetch('index.php', { method:'POST', body:formData })
    .then(res=>res.json())
    .then(data=>{
        if(data.success){
            window.scrollTo({ top: 0, behavior: 'smooth' });

            const popup = document.createElement('div');
            popup.id = 'popupMsg';
            popup.innerHTML = `<span class="checkmark"></span>${data.msg}`;
            document.body.appendChild(popup);

            setTimeout(()=> popup.classList.add('show'), 50);
            form.reset();

            setTimeout(()=>{
                popup.classList.remove('show');
                setTimeout(()=> popup.remove(), 500);
            }, 2500);
        } else {
            alert('Error: '+(data.msg || 'Unknown'));
        }
    })
    .catch(err=>{
        console.error(err);
        alert('Error submitting form.');
    });
});
</script>
</body>
</html>
