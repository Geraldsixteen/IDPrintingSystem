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
        $uploadDir = __DIR__.'/../uploads/';
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
<link rel="stylesheet" href="registration.css">
<style>
/* Mobile-friendly layout */
body {
    margin:0;
    font-family:"Segoe UI",Arial,sans-serif;
    background:#c9dfff;
    display:flex;
    justify-content:center;
    padding:20px;
}
.main { width:100%; max-width:450px; }
.topbar {
    text-align:center;
    margin-bottom:20px;
}
.topbar img { width:80px; height:auto; display:block; margin:0 auto 10px; }
.card {
    background:#fff;
    padding:20px;
    border-radius:12px;
    box-shadow:0 4px 15px rgba(0,0,0,0.15);
}
.card h3 { text-align:center; margin-bottom:15px; }
.card label { display:block; margin:10px 0 5px; font-weight:600; }
.card input, .card select { width:100%; padding:10px; border-radius:8px; border:1px solid #ccc; font-size:14px; }
.card button { margin-top:15px; width:100%; padding:12px; border:none; border-radius:10px; background:#002b80; color:white; font-weight:600; font-size:16px; cursor:pointer; }
.card button:hover { background:#1f2857; }

/* Success popup with checkmark */
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
    min-width: 200px;
    max-width: 90%;
}
#popupMsg.show {
    opacity: 1;
    transform: translate(-50%, -50%) scale(1);
}
#popupMsg .checkmark {
    display:block;
    width:50px;
    height:50px;
    margin:0 auto 10px;
    border-radius:50%;
    background:white;
    position:relative;
}
#popupMsg .checkmark:after {
    content:'';
    position:absolute;
    left:14px; top:8px;
    width:12px; height:25px;
    border:solid #28a745;
    border-width:0 4px 4px 0;
    transform: rotate(45deg);
    animation: check 0.5s ease forwards;
}
@keyframes check { from {width:0; height:0;} to {width:12px; height:25px;} }

@media(max-width:500px) {
    body { padding:15px; }
    .card { padding:15px; }
}
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
            <label>LRN</label><input type="text" name="lrn" required>
            <label>Full Name</label><input type="text" name="full_name" required>
            <label>ID Number</label><input type="text" name="id_number" required>
            <label>Strand</label>
            <select name="strand" required>
                <option value="">-- Select Strand --</option>
                <option value="STEM">STEM</option>
                <option value="HUMMS">HUMMS</option>
                <option value="ABM">ABM</option>
                <option value="GAS">GAS</option>
                <option value="ICT">ICT</option>
            </select>
            <label>Home Address</label><input type="text" name="home_address" required>
            <label>Guardian's Name</label><input type="text" name="guardian_name" required>
            <label>Guardian's Contact</label><input type="text" name="guardian_contact" required>
            <label>Upload Photo</label><input type="file" name="photo" accept="image/*" required>
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
            // Scroll to top for mobile
            window.scrollTo({ top: 0, behavior: 'smooth' });

            // Create popup with checkmark
            const popup = document.createElement('div');
            popup.id = 'popupMsg';
            popup.innerHTML = `<span class="checkmark"></span>${data.msg}`;
            document.body.appendChild(popup);

            setTimeout(()=> popup.classList.add('show'), 50);
            form.reset();

            // Auto-hide after 2.5 seconds
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
