<?php  
session_start();
require_once __DIR__ . '/../Config/database.php';

function generate_uuid() {
    // Generates a random UUID v4
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}


// Ensure large uploads work
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

    $level = $_POST['level'] ?? ''; // junior, senior, college
    $lrn = trim($_POST['lrn'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $strand = trim($_POST['strand'] ?? '');
    $home_address = trim($_POST['home_address'] ?? '');
    $guardian_name = trim($_POST['guardian_name'] ?? '');
    $guardian_contact = trim($_POST['guardian_contact'] ?? '');
    $photo_base64 = $_POST['photo_base64'] ?? '';

    if (!$lrn || !$full_name || (!$strand && $level != 'college')) {
        send_json(['success'=>false,'msg'=>'Please fill in all required fields.']);
    }

    if (!$photo_base64) {
        send_json(['success'=>false,'msg'=>'No photo uploaded']);
    }

    if (preg_match('/^data:image\/\w+;base64,/', $photo_base64)) {
        $photo_base64 = preg_replace('/^data:image\/\w+;base64,/', '', $photo_base64);
    }

    $image_data = base64_decode($photo_base64);
    if (!$image_data) send_json(['success'=>false,'msg'=>'Invalid image data']);

    $photo_filename = $lrn . '_' . time() . '.jpg';

    try {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // ===== AUTO-GENERATE ID NUMBER =====
        $currentYear = date('y'); // e.g., '26'
        $prefix = 'S' . $currentYear . '-';
        $stmtId = $pdo->prepare("
            SELECT id_number FROM (
                SELECT id_number FROM register WHERE id_number LIKE :prefix
                UNION ALL
                SELECT id_number FROM archive  WHERE id_number LIKE :prefix
            ) AS all_ids
            ORDER BY id_number DESC
            LIMIT 1
        ");
        $stmtId->execute([':prefix' => $prefix . '%']);
        $lastId = $stmtId->fetchColumn();

        if ($lastId) {
            $num = intval(substr($lastId, 4)) + 1;
        } else {
            $num = 1;
        }
        $id_number = $prefix . str_pad($num, 4, '0', STR_PAD_LEFT);
        // ====================================

        // Check if LRN exists
        $check = $pdo->prepare("
            SELECT COUNT(*) FROM (
                SELECT lrn,id_number FROM register WHERE lrn=:lrn OR id_number=:id_number
                UNION ALL
                SELECT lrn,id_number FROM archive  WHERE lrn=:lrn OR id_number=:id_number
            ) AS check_all
        ");

        $check->execute([':lrn'=>$lrn, ':id_number'=>$id_number]);
        if ($check->fetchColumn() > 0) send_json(['success'=>false,'msg'=>'This LRN is already registered.']);

        $stmt = $pdo->prepare("
            INSERT INTO register
            (lrn, full_name, id_number, grade, strand, course, home_address, guardian_name, guardian_contact, photo, photo_blob, created_at)
            VALUES
            (:lrn, :full_name, :id_number, :grade, :strand, :course, :home_address, :guardian_name, :guardian_contact, :photo, :photo_blob, NOW())
        ");

        // Map level to fields
        $grade = $level === 'junior' ? $strand : null;
        $course = $level === 'college' ? $strand : null;
        $strand_val = $level === 'senior' ? $strand : null;

        $stmt->execute([
            ':lrn' => $lrn,
            ':full_name' => $full_name,
            ':id_number' => $id_number,
            ':grade' => $grade,
            ':strand' => $strand_val,
            ':course' => $course,
            ':home_address' => $home_address,
            ':guardian_name' => $guardian_name,
            ':guardian_contact' => $guardian_contact,
            ':photo' => $photo_filename,
            ':photo_blob' => $photo_base64
        ]);

        send_json([
            'success' => true,
            'msg' => 'Successfully Registered!',
            'photo_base64' => 'data:image/jpeg;base64,' . $photo_base64,
            'id_number' => $id_number
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
<title>Student Registration</title>
<style>
/* ... keep your existing CSS ... */
.photoPreview {
    display: block;
    margin: 10px auto;
    width: 150px;
    height: 200px;
    object-fit: cover;
    border-radius: 10px;
    border: 1px solid #ccc;
}
body{margin:0;font-family:"Segoe UI",Arial,sans-serif;background:#f0f4ff;display:flex;justify-content:center;padding:20px;}
.main{width:100%;max-width:600px;}
.topbar{width: 100%;background:white;text-align:center;margin-bottom:20px;}
.topbar img{width:80px;display:block;margin:0 auto 10px;}
.topbar h3{margin:0;color:#002b80;}
.card{background:#fff;padding:25px;border-radius:15px;box-shadow:0 6px 20px rgba(0,0,0,0.1);}
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

.photoPreview {
    display: none; /* hide by default */
    margin: 10px auto;
    width: 150px;
    height: 200px;
    object-fit: cover;
    border-radius: 10px;
    border: 1px solid #ccc;
}

.tabs{display:flex;justify-content:center;margin-bottom:15px;gap:10px;}
.tab-btn{flex:1;background-color:#3498db;color:white;border:none;padding:10px 0;border-radius:8px 8px 0 0;cursor:pointer;font-weight:600;transition:0.2s;}
.tab-btn:hover{background-color:#5dade2;}
.tab-btn.active{background-color:#002b80;}
.reg-form{display:none;}
.reg-form.active{display:block;}
</style>
</style>
</head>
<body>
<div class="main">
<div class="topbar">
<img src="cdlb.png" alt="Logo">
<h3>Student ID Registration</h3>
</div>

<div class="tabs">
  <button class="tab-btn active" data-target="juniorHigh">Junior High</button>
  <button class="tab-btn" data-target="seniorHigh">Senior High</button>
  <button class="tab-btn" data-target="college">College</button>
</div>

<!-- JUNIOR HIGH FORM -->
<div class="card reg-form active" id="juniorHighForm">
<h3>Register Junior High Student</h3>
<form class="reg-form-inner" data-level="junior">
<div class="form-group"><input type="text" name="lrn" placeholder=" " required><label>LRN</label></div>
<div class="form-group"><input type="text" name="full_name" placeholder=" " required><label>Full Name</label></div>
<!-- ID number input removed -->
<div class="form-group">
<select name="strand" required>
<option value="" hidden></option>
<option value="Grade 7">Grade 7</option>
<option value="Grade 8">Grade 8</option>
<option value="Grade 9">Grade 9</option>
<option value="Grade 10">Grade 10</option>
</select>
<label>Grade</label>
</div>
<div class="form-group"><input type="text" name="home_address" placeholder=" " required><label>Home Address</label></div>
<div class="form-group"><input type="text" name="guardian_name" placeholder=" " required><label>Guardian's Name</label></div>
<div class="form-group"><input type="text" name="guardian_contact" placeholder=" " required><label>Guardian's Contact</label></div>
<div class="form-group"><input type="file" class="photoInput" accept="image/*" required><img class="photoPreview" src="" alt="Photo Preview"></div>
<button type="submit">Register</button>
</form>
</div>

<!-- SENIOR HIGH FORM -->
<div class="card reg-form" id="seniorHighForm">
<h3>Register Senior High Student</h3>
<form class="reg-form-inner" data-level="senior">
<div class="form-group"><input type="text" name="lrn" placeholder=" " required><label>LRN</label></div>
<div class="form-group"><input type="text" name="full_name" placeholder=" " required><label>Full Name</label></div>
<!-- ID number input removed -->
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
<div class="form-group"><input type="file" class="photoInput" accept="image/*" required><img class="photoPreview" src="" alt="Photo Preview"></div>
<button type="submit">Register</button>
</form>
</div>

<!-- COLLEGE FORM -->
<div class="card reg-form" id="collegeForm">
<h3>Register College Student</h3>
<form class="reg-form-inner" data-level="college">
<div class="form-group"><input type="text" name="lrn" placeholder=" " required><label>LRN</label></div>
<div class="form-group"><input type="text" name="full_name" placeholder=" " required><label>Full Name</label></div>
<!-- ID number input removed -->
<div class="form-group">
<select name="strand" required>
<option value="" hidden></option>
<option value="BSBA">BSBA</option>
<option value="BSE">BSE</option>
<option value="BEE">BEE</option>
<option value="BSCS">BSCS</option>
<option value="BAE">BAE</option>
</select>
<label>Course</label>
</div>
<div class="form-group"><input type="text" name="home_address" placeholder=" " required><label>Home Address</label></div>
<div class="form-group"><input type="text" name="guardian_name" placeholder=" " required><label>Guardian's Name</label></div>
<div class="form-group"><input type="text" name="guardian_contact" placeholder=" " required><label>Guardian's Contact</label></div>
<div class="form-group"><input type="file" class="photoInput" accept="image/*" required><img class="photoPreview" src="" alt="Photo Preview"></div>
<button type="submit">Register</button>
</form>
</div>

<div id="popupMsg"></div>
</div>

<script>
// Tab switching
const tabs = document.querySelectorAll('.tab-btn');
const forms = document.querySelectorAll('.reg-form');
tabs.forEach(tab => {
    tab.addEventListener('click', () => {
        tabs.forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        forms.forEach(f => f.classList.remove('active'));
        document.getElementById(tab.dataset.target + 'Form').classList.add('active');
    });
});

document.querySelectorAll('.reg-form-inner').forEach(form => {
    const photoInput = form.querySelector('.photoInput');
    const preview = form.querySelector('.photoPreview');
    let resizedPhotoBase64 = '';

    preview.src = '';

    photoInput.addEventListener('change', e => {
    const file = e.target.files[0];
    if(!file){
        preview.src='';
        preview.style.display = 'none'; // hide if no file
        return;
    }
    const reader = new FileReader();
    reader.onload = function(ev){
        const img = new Image();
        img.onload = function(){
            const maxWidth = 800, maxHeight = 1000;
            let w=img.width,h=img.height;
            if(w>maxWidth){h=h*(maxWidth/w);w=maxWidth;}
            if(h>maxHeight){w=w*(maxHeight/h);h=maxHeight;}
            const canvas=document.createElement('canvas');
            canvas.width=w; canvas.height=h;
            canvas.getContext('2d').drawImage(img,0,0,w,h);
            resizedPhotoBase64 = canvas.toDataURL('image/jpeg',0.85);
            preview.src = resizedPhotoBase64;
            preview.style.display = 'block'; // show after selection
        }
        img.src = ev.target.result;
    }
    reader.readAsDataURL(file);
});


    form.addEventListener('submit', async e => {
    e.preventDefault();
    if(!resizedPhotoBase64){alert('Please select a photo'); return;}
    const fd = new FormData();
    fd.append('level', form.dataset.level);
    for(let input of form.elements){
        if(input.name && input.type!=='file') fd.append(input.name,input.value);
    }
    fd.append('photo_base64',resizedPhotoBase64);
    try{
        const res = await fetch('index.php',{method:'POST',body:fd});
        const data = await res.json();
        if(data.success){
            const p=document.getElementById('popupMsg');
            p.innerHTML='✔ '+data.msg+'<br>ID Number: '+data.id_number;
            p.style.display='block';
            setTimeout(()=>p.style.display='none',2500);

            // Show the uploaded photo in preview (optional)
            preview.src = data.photo_base64;
            preview.style.display = 'block';

            // Reset form fields
            form.querySelectorAll('input:not([type=file]), select').forEach(i=>i.value='');
            resizedPhotoBase64 = '';

            // Reset file input
            photoInput.value = '';
            // Hide preview again
            preview.src = '';
            preview.style.display = 'none';

        } else alert(data.msg);
    } catch(err){alert('Submit failed: '+err.message);}
});
});
</script>
</body>
</html>
