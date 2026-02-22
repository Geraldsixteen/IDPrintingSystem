<?php  
session_start();
require_once __DIR__ . '/../Config/database.php';

// ===== UUID GENERATOR =====
function generate_uuid() {
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

// ===== PHP CONFIG =====
ini_set('upload_max_filesize', '10M');
ini_set('post_max_size', '12M');
ini_set('max_execution_time', '300');
ini_set('display_errors', 0);
error_reporting(E_ALL);

// ===== JSON RESPONSE FUNCTION =====
function send_json($arr){
    if(ob_get_level()) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($arr);
    exit;
}

// ===== HANDLE POST REGISTRATION =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- Gather inputs ---
    $original_base64  = $_POST['photo_base64'] ?? '';
    $level            = $_POST['level'] ?? '';
    $lrn              = trim($_POST['lrn'] ?? '');
    $full_name        = trim($_POST['full_name'] ?? '');
    $home_address     = trim($_POST['home_address'] ?? '');
    $guardian_name    = trim($_POST['guardian_name'] ?? '');
    $guardian_contact = trim($_POST['guardian_contact'] ?? '');

    // Validate mandatory fields
    if (!$original_base64 || !$lrn || !$full_name || !$level || !$home_address || !$guardian_name || !$guardian_contact) {
        send_json(['success'=>false,'msg'=>'Incomplete information.']);
    }

    // --- Map level to input value ---
    $inputValue = '';
    if ($level === 'junior') $inputValue = trim($_POST['grade'] ?? '');
    elseif ($level === 'senior') $inputValue = trim($_POST['strand'] ?? '');
    elseif ($level === 'college') $inputValue = trim($_POST['course'] ?? '');
    else send_json(['success'=>false,'msg'=>'Invalid level selection.']);

    if (!$inputValue) send_json(['success'=>false,'msg'=>'Please select Grade/Strand/Course.']);

    $photo_blob = base64_decode(str_replace('data:image/jpeg;base64,', '', $original_base64));

    $pdo->beginTransaction();

    // --- Lock enrolled student record ---
    $stmt = $pdo->prepare("
        SELECT lrn, full_name, grade, strand, course, status
        FROM enrolled_students
        WHERE lrn = :lrn
        FOR UPDATE
    ");
    $stmt->execute([':lrn'=>$lrn]);
    $enrolled = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$enrolled) { $pdo->rollBack(); send_json(['success'=>false,'msg'=>'You are not enrolled.']); }
    if ($enrolled['status'] !== 'enrolled') { $pdo->rollBack(); send_json(['success'=>false,'msg'=>'Enrollment inactive.']); }
    if (trim(strtolower($enrolled['full_name'])) !== trim(strtolower($full_name))) { $pdo->rollBack(); send_json(['success'=>false,'msg'=>'Name does not match record.']); }

    // --- Check level match ---
    $dbValue = $level==='junior' ? $enrolled['grade'] : ($level==='senior' ? $enrolled['strand'] : $enrolled['course']);
    if (!$dbValue || trim($dbValue) !== $inputValue) { $pdo->rollBack(); send_json(['success'=>false,'msg'=>'Grade/Strand/Course mismatch.']); }

    // --- Check duplicate registration ---
    $stmtExist = $pdo->prepare("SELECT id FROM register WHERE lrn = :lrn LIMIT 1");
    $stmtExist->execute([':lrn'=>$lrn]);
    if ($stmtExist->fetch()) { $pdo->rollBack(); send_json(['success'=>false,'msg'=>'Already registered.']); }

    // --- Generate unique ID number ---
    $currentYear = date('y');
    $prefix = 'S'.$currentYear.'-';
    $stmtId = $pdo->prepare("
        SELECT id_number FROM register
        WHERE id_number LIKE :prefix
        ORDER BY id DESC
        LIMIT 1
        FOR UPDATE
    ");
    $stmtId->execute([':prefix'=>$prefix.'%']);
    $lastId = $stmtId->fetchColumn();
    $num = $lastId ? intval(explode('-', $lastId)[1]) + 1 : 1;
    $id_number = $prefix . str_pad($num, 4, '0', STR_PAD_LEFT);

    // --- Insert registration ---
    $stmtInsert = $pdo->prepare("
        INSERT INTO register
        (lrn, full_name, id_number, grade, strand, course, home_address, guardian_name, guardian_contact, photo, photo_blob, created_at)
        VALUES
        (:lrn, :full_name, :id_number, :grade, :strand, :course, :home_address, :guardian_name, :guardian_contact, :photo, :photo_blob, NOW())
    ");
    $stmtInsert->execute([
        ':lrn'              => $lrn,
        ':full_name'        => $enrolled['full_name'],
        ':id_number'        => $id_number,
        ':grade'            => $level==='junior' ? $dbValue : null,
        ':strand'           => $level==='senior' ? $dbValue : null,
        ':course'           => $level==='college'? $dbValue : null,
        ':home_address'     => $home_address,
        ':guardian_name'    => $guardian_name,
        ':guardian_contact' => $guardian_contact,
        ':photo'            => null,
        ':photo_blob'       => $photo_blob
    ]);

    $pdo->commit();

    send_json([
        'success'=>true,
        'msg'=>'Successfully Registered!',
        'id_number'=>$id_number,
        'photo_base64'=>$original_base64
    ]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Registration</title>
<style>
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

.enrollment-status{
    font-size:13px;
    margin-top:-10px;
    margin-bottom:10px;
    font-weight:600;
}

.status-success{
    color:#28a745;
}

.status-error{
    color:#dc3545;
}

.status-checking{
    color:#ffc107;
}

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
<div class="enrollment-status"></div>
<div class="form-group"><input type="text" name="full_name" placeholder=" " required><label>Full Name</label></div>
<!-- ID number input removed -->
<div class="form-group">
<select name="grade" required>
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
<div class="enrollment-status"></div>
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
<div class="enrollment-status"></div>
<div class="form-group"><input type="text" name="full_name" placeholder=" " required><label>Full Name</label></div>
<!-- ID number input removed -->
<div class="form-group">
<select name="course" required>
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
// ===== TAB SWITCHING =====
const tabs = document.querySelectorAll('.tab-btn');
const forms = document.querySelectorAll('.reg-form');

// Store registered LRNs with the tab/level they were registered in
const registeredLRNs = new Map();

tabs.forEach(tab => {
    tab.addEventListener('click', () => {
        tabs.forEach(t => t.classList.remove('active'));
        forms.forEach(f => f.classList.remove('active'));

        tab.classList.add('active');
        document.getElementById(tab.dataset.target + 'Form').classList.add('active');
    });
});

// ===== HANDLE FORMS =====
document.querySelectorAll('.reg-form-inner').forEach(form => {

    const photoInput = form.querySelector('.photoInput');
    const preview = form.querySelector('.photoPreview');
    const lrnInput = form.querySelector('input[name="lrn"]');
    const statusDiv = form.querySelector('.enrollment-status');
    const submitBtn = form.querySelector('button');

    const allInputs = Array.from(form.querySelectorAll('input, select'));
    const editableInputs = allInputs.filter(i => i.name !== 'lrn' && i.type !== 'file');

    let resizedPhotoBase64 = '';
    let timer = null;

    preview.src = '';
    preview.style.display = 'none';

    // ===== PHOTO RESIZE + PREVIEW =====
    photoInput.addEventListener('change', e => {
        const file = e.target.files[0];
        if (!file) { resetPhoto(); return; }

        const reader = new FileReader();
        reader.onload = ev => {
            const img = new Image();
            img.onload = () => {
                const maxWidth = 800;
                const maxHeight = 1000;
                let w = img.width;
                let h = img.height;

                if (w > maxWidth) { h *= maxWidth / w; w = maxWidth; }
                if (h > maxHeight) { w *= maxHeight / h; h = maxHeight; }

                const canvas = document.createElement('canvas');
                canvas.width = w;
                canvas.height = h;
                canvas.getContext('2d').drawImage(img, 0, 0, w, h);

                resizedPhotoBase64 = canvas.toDataURL('image/jpeg', 0.85);
                preview.src = resizedPhotoBase64;
                preview.style.display = 'block';
            };
            img.src = ev.target.result;
        };
        reader.readAsDataURL(file);
    });

    function resetPhoto() {
        preview.src = '';
        preview.style.display = 'none';
        resizedPhotoBase64 = '';
        photoInput.value = '';
    }

    // ===== REALTIME LRN CHECK =====
    lrnInput.addEventListener('input', () => {
        clearTimeout(timer);
        const lrn = lrnInput.value.trim();

        if (!lrn) { clearStatus(); return; }

        // Check if already registered in this session
        if (registeredLRNs.has(lrn)) {
            const regLevel = registeredLRNs.get(lrn);
            setStatus(`This student is already registered under "${capitalize(regLevel)}" tab.`, 'error');
            highlightTab(regLevel);
            return;
        }

        statusDiv.innerHTML = 'Checking enrollment...';
        statusDiv.className = 'enrollment-status status-checking';

        timer = setTimeout(async () => {
            if (lrn.length < 6) { setStatus('Enter valid LRN', 'error'); return; }

            try {
                const res = await fetch(
                    `checkEnrolled.php?lrn=${encodeURIComponent(lrn)}&full_name=${encodeURIComponent(form.querySelector('input[name="full_name"]').value)}&level=${form.dataset.level}`
                );
                const data = await res.json();

                if (!data.success) {
                    setStatus(data.msg, 'error');
                    resetEditableInputs();
                } else {
                    setStatus('✔ Student is officially enrolled', 'success');
                    const d = data.data;
                    editableInputs.forEach(i => {
                        if (i.name in d) {
                            i.value = d[i.name] || '';
                            i.readOnly = true;
                            if (i.tagName === 'SELECT') i.disabled = true;
                        }
                    });
                }
            } catch (err) {
                console.error(err);
                setStatus('Server error', 'error');
            }
        }, 400);
    });

    function setStatus(msg, type) {
        statusDiv.innerHTML = msg;
        statusDiv.className = `enrollment-status status-${type}`;
    }

    function clearStatus() {
        statusDiv.innerHTML = '';
        statusDiv.className = 'enrollment-status';
    }

    function resetEditableInputs() {
        editableInputs.forEach(i => {
            i.value = '';
            i.readOnly = false;
            i.disabled = false;
        });
    }

    function capitalize(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    function highlightTab(level) {
        tabs.forEach(t => t.classList.remove('active'));
        forms.forEach(f => f.classList.remove('active'));

        const tab = document.querySelector(`.tab-btn[data-target="${level}"]`);
        const formToShow = document.getElementById(level + 'Form');
        if (tab && formToShow) {
            tab.classList.add('active');
            formToShow.classList.add('active');
        }
    }

    // ===== FORM SUBMIT =====
    form.addEventListener('submit', async e => {
        e.preventDefault();

        const lrn = lrnInput.value.trim();
        if (!resizedPhotoBase64) { setStatus('Please select a photo.', 'error'); return; }
        if (!statusDiv.classList.contains('status-success')) { setStatus('Student must be enrolled before registering.', 'error'); return; }
        if (registeredLRNs.has(lrn)) { 
            setStatus(`This student is already registered under "${capitalize(registeredLRNs.get(lrn))}" tab.`, 'error'); 
            highlightTab(registeredLRNs.get(lrn));
            return; 
        }

        submitBtn.disabled = true;
        submitBtn.innerText = 'Processing...';

        const fd = new FormData();
        fd.append('level', form.dataset.level);
        allInputs.forEach(input => {
            if (input.name && input.type !== 'file') fd.append(input.name, input.value);
        });
        fd.append('photo_base64', resizedPhotoBase64);

        try {
            const res = await fetch('index.php', { method: 'POST', body: fd });
            const data = await res.json();

            if (data.success) {
                setStatus(`✔ Registration Successful! ID: ${data.id_number}`, 'success');
                preview.src = data.photo_base64;
                preview.style.display = 'block';

                // Mark LRN as registered in this session
                registeredLRNs.set(lrn, form.dataset.level);

                // ===== RESET FORM CLEANLY =====
                resetPhoto();
                resetEditableInputs();
                allInputs.forEach(input => {
                    if (input.tagName === 'SELECT') input.selectedIndex = 0;
                });
                lrnInput.value = '';

                setTimeout(clearStatus, 5000);
            } else {
                setStatus(data.msg, 'error');
            }

        } catch (err) {
            console.error(err);
            setStatus('Submission failed. Please try again.', 'error');
        }

        submitBtn.disabled = false;
        submitBtn.innerText = 'Register';
    });
});
</script>
</body>
</html>
