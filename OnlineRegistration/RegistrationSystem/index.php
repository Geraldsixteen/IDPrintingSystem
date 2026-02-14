<?php
session_start();
require_once __DIR__ . '/../Config/database.php';

// Ensure large uploads work (server fallback)
ini_set('upload_max_filesize', '10M');
ini_set('post_max_size', '12M');
ini_set('max_execution_time', '300');
ini_set('display_errors', 0);
error_reporting(E_ALL);

// JSON helper
function send_json($arr){
    if(ob_get_level()) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($arr);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $lrn = isset($_POST['lrn']) ? trim($_POST['lrn']) : '';
    $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $id_number = isset($_POST['id_number']) ? trim($_POST['id_number']) : '';
    $strand = isset($_POST['strand']) ? trim($_POST['strand']) : '';
    $home_address = isset($_POST['home_address']) ? trim($_POST['home_address']) : '';
    $guardian_name = isset($_POST['guardian_name']) ? trim($_POST['guardian_name']) : '';
    $guardian_contact = isset($_POST['guardian_contact']) ? trim($_POST['guardian_contact']) : '';
    $photo_base64 = isset($_POST['photo_base64']) ? $_POST['photo_base64'] : '';

    if (!$lrn || !$full_name || !$id_number || !$strand) {
        send_json(['success'=>false,'msg'=>'Please fill in all required fields.']);
    }

    if (!$photo_base64) {
        send_json(['success'=>false,'msg'=>'No photo uploaded']);
    }

    // Strip data URL prefix if present
    if (preg_match('/^data:image\/\w+;base64,/', $photo_base64)) {
        $photo_base64 = preg_replace('/^data:image\/\w+;base64,/', '', $photo_base64);
    }

    $image_data = base64_decode($photo_base64);
    if (!$image_data) {
        send_json(['success'=>false,'msg'=>'Invalid image data']);
    }

    $photo_filename = $lrn . '_' . time() . '.jpg';

    try {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Check duplicate
        $check = $pdo->prepare("SELECT COUNT(*) FROM register WHERE lrn=:lrn OR id_number=:id_number");
        $check->execute([':lrn'=>$lrn, ':id_number'=>$id_number]);
        if ($check->fetchColumn() > 0) {
            send_json(['success'=>false,'msg'=>'LRN or ID Number already exists']);
        }

        $stmt = $pdo->prepare("
            INSERT INTO register
            (lrn, full_name, id_number, strand, home_address, guardian_name, guardian_contact, photo, photo_blob, created_at)
            VALUES
            (:lrn, :full_name, :id_number, :strand, :home_address, :guardian_name, :guardian_contact, :photo, :photo_blob, NOW())
        ");

        $stmt->execute([
            ':lrn' => $lrn,
            ':full_name' => $full_name,
            ':id_number' => $id_number,
            ':strand' => $strand,
            ':home_address' => $home_address,
            ':guardian_name' => $guardian_name,
            ':guardian_contact' => $guardian_contact,
            ':photo' => $photo_filename,
            ':photo_blob' => $photo_base64
        ]);

        send_json([
            'success' => true,
            'msg' => 'Successfully Registered!',
            'photo_base64' => 'data:image/jpeg;base64,' . $photo_base64
        ]);

    } catch(PDOException $e){
        send_json(['success'=>false,'msg'=>'Database error: '.$e->getMessage()]);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Senior High Registration</title>
<style>
body{margin:0;font-family:"Segoe UI",Arial,sans-serif;background:#f0f4ff;display:flex;justify-content:center;padding:20px;}
.main{width:100%;max-width:500px;}
.topbar{background:white;text-align:center;margin-bottom:20px;}
.topbar img{width:80px;display:block;margin:0 auto 10px;}
.topbar h3{margin:0;color:#002b80;}
.card{margin:0 10px;background:#fff;padding:25px;border-radius:15px;box-shadow:0 6px 20px rgba(0,0,0,0.1);}
.card h3{text-align:center;margin-bottom:20px;color:#002b80;}
.form-group{position:relative;margin-bottom:18px;}
.form-group input,.form-group select{width:95%;padding:14px;border-radius:10px;border:1px solid #ccc;font-size:14px;background:transparent;}
.form-group label{position:absolute;left:12px;top:14px;color:#999;font-size:14px;pointer-events:none;transition:.2s}
.form-group input:focus+label,
.form-group input:not(:placeholder-shown)+label,
.form-group select:focus+label,
.form-group select:not([value=""])+label{top:-8px;left:10px;font-size:12px;color:#002b80;background:#fff;padding:0 5px}
.card button{margin-top:10px;width:100%;padding:14px;border:none;border-radius:12px;background:#002b80;color:white;font-weight:600;font-size:16px;cursor:pointer}
.card button:hover{background:#1f2857}
#popupMsg{position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:#28a745;color:white;padding:20px;border-radius:12px;font-weight:600;display:none;text-align:center;min-width:220px;max-width:90%;}
#photoPreview{display:block;margin:10px auto;width:150px;height:200px;object-fit:cover;border-radius:10px;border:1px solid #ccc;}
</style>
</head>
<body>
<div class="main">
<div class="topbar">
<img src="cdlb.png" alt="CDLB Logo">
<h3>Senior High Student ID Registration</h3>
</div>
<div class="card">
<h3>Register Student</h3>
<form id="reg-form">
<div class="form-group"><input type="text" name="lrn" placeholder=" " required><label>LRN</label></div>
<div class="form-group"><input type="text" name="full_name" placeholder=" " required><label>Full Name</label></div>
<div class="form-group"><input type="text" name="id_number" placeholder=" " required><label>ID Number</label></div>
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
<div class="form-group"><input type="text" name="home_address" placeholder=" " required><label>Home Address</label></div>
<div class="form-group"><input type="text" name="guardian_name" placeholder=" " required><label>Guardian's Name</label></div>
<div class="form-group"><input type="text" name="guardian_contact" placeholder=" " required><label>Guardian's Contact</label></div>
<div class="form-group">
<input type="file" id="photoInput" accept="image/*" required>
<img id="photoPreview" src="" alt="Photo Preview">
</div>
<button type="submit">Register</button>
</form>
</div>
</div>

<script>
const form = document.getElementById('reg-form');
const preview = document.getElementById('photoPreview');
const photoInput = document.getElementById('photoInput');

let resizedPhotoBase64 = '';

photoInput.addEventListener('change', e => {
    const file = e.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function(ev){
        const img = new Image();
        img.onload = function(){
            const maxWidth = 800;
            const maxHeight = 1000;
            let w = img.width;
            let h = img.height;

            if (w > maxWidth) { h = h * (maxWidth/w); w = maxWidth; }
            if (h > maxHeight) { w = w * (maxHeight/h); h = maxHeight; }

            const canvas = document.createElement('canvas');
            canvas.width = w;
            canvas.height = h;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(img,0,0,w,h);
            resizedPhotoBase64 = canvas.toDataURL('image/jpeg', 0.85);
            preview.src = resizedPhotoBase64;
        }
        img.src = ev.target.result;
    }
    reader.readAsDataURL(file);
});

form.addEventListener('submit', async e => {
    e.preventDefault();

    if (!resizedPhotoBase64) {
        alert('Please select a photo');
        return;
    }

    const fd = new FormData();
    for (let input of form.elements) {
        if (input.name && input.type !== 'file') fd.append(input.name, input.value);
    }
    fd.append('photo_base64', resizedPhotoBase64);

    try {
        const res = await fetch('index.php', { method: 'POST', body: fd });
        const text = await res.text();

        let data;
        try { data = JSON.parse(text); }
        catch(err){ throw new Error("Invalid JSON: " + text); }

        if (data.success) {
            const p = document.createElement('div');
            p.id = 'popupMsg';
            p.innerHTML = '<div>✔</div>' + data.msg;
            document.body.appendChild(p);
            p.classList.add('show');

            form.reset();
            preview.src = '';
            resizedPhotoBase64 = '';

            setTimeout(() => p.remove(), 2500);
        } else {
            alert(data.msg);
        }
    } catch(err) {
        alert('Submit failed: ' + err.message);
    }
});
</script>
</body>
</html>
