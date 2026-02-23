<?php  
session_start();
require_once __DIR__ . '/../Config/database.php';

// ===== JSON RESPONSE =====
function send_json($arr){
    if(ob_get_level()) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($arr);
    exit;
}

// ===== HANDLE POST =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        $level            = $_POST['level'] ?? '';
        $lrn              = trim($_POST['lrn'] ?? '');
        $full_name        = trim($_POST['full_name'] ?? '');
        $home_address     = trim($_POST['home_address'] ?? '');
        $guardian_name    = trim($_POST['guardian_name'] ?? '');
        $guardian_contact = trim($_POST['guardian_contact'] ?? '');
        $original_base64  = $_POST['photo_base64'] ?? '';

        if (!$lrn || !$full_name || !$level || !$home_address || !$guardian_name || !$guardian_contact || !$original_base64) {
            send_json(['success'=>false,'msg'=>'Incomplete information']);
        }

        $levelField = $level==='junior' ? 'grade' : ($level==='senior' ? 'strand' : 'course');
        $inputValue = trim($_POST[$levelField] ?? '');

        if(!$inputValue){
            send_json(['success'=>false,'msg'=>'Please select Grade/Strand/Course.']);
        }

        $photo_blob = base64_decode(
            preg_replace('#^data:image/\w+;base64,#i', '', $original_base64)
        );

        if(!$photo_blob){
            send_json(['success'=>false,'msg'=>'Invalid photo data.']);
        }

        $pdo->beginTransaction();

        // ===== LOCK ENROLLED =====
        $stmt = $pdo->prepare("SELECT * FROM enrolled_students WHERE lrn = :lrn FOR UPDATE");
        $stmt->execute([':lrn'=>$lrn]);
        $enrolled = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$enrolled) {
            $pdo->rollBack();
            send_json(['success'=>false,'msg'=>'You are not enrolled.']);
        }

        if ($enrolled['status'] !== 'enrolled') {
            $pdo->rollBack();
            send_json(['success'=>false,'msg'=>'Enrollment inactive.']);
        }

        if (trim(strtolower($enrolled['full_name'])) !== trim(strtolower($full_name))) {
            $pdo->rollBack();
            send_json(['success'=>false,'msg'=>'Name does not match record.']);
        }

        // ===== STRICT LEVEL VALIDATION =====
        $dbValue = trim($enrolled[$levelField] ?? '');

        // 1️⃣ If database has NO value → incomplete enrollment record
        if ($dbValue === '') {
            $pdo->rollBack();
            send_json([
                'success'=>false,
                'msg'=>'Enrollment information incomplete. Please contact registrar.'
            ]);
        }

        // 2️⃣ If value does not match selected input
        if ($dbValue !== $inputValue) {
            $pdo->rollBack();
            send_json([
                'success'=>false,
                'msg'=>'Grade/Strand/Course does not match enrollment record.'
            ]);
        }

        // ===== DUPLICATE CHECK =====
        $stmtExist = $pdo->prepare("SELECT id FROM register WHERE lrn = :lrn LIMIT 1");
        $stmtExist->execute([':lrn'=>$lrn]);
        if ($stmtExist->fetch()) {
            $pdo->rollBack();
            send_json(['success'=>false,'msg'=>'Already registered.']);
        }

        // ===== GENERATE ID =====
        $currentYear = date('y');
        $prefix = 'S'.$currentYear.'-';

        $stmtId = $pdo->prepare("
            SELECT id_number 
            FROM register 
            WHERE id_number LIKE :prefix 
            ORDER BY id DESC 
            LIMIT 1 
            FOR UPDATE
        ");
        $stmtId->execute([':prefix'=>$prefix.'%']);
        $lastId = $stmtId->fetchColumn();

        $num = $lastId ? intval(explode('-', $lastId)[1]) + 1 : 1;
        $id_number = $prefix . str_pad($num, 4, '0', STR_PAD_LEFT);

        // ===== INSERT =====
        $stmtInsert = $pdo->prepare("
            INSERT INTO register
            (lrn, full_name, id_number, grade, strand, course, home_address, guardian_name, guardian_contact, photo, photo_blob, created_at)
            VALUES (:lrn, :full_name, :id_number, :grade, :strand, :course, :home_address, :guardian_name, :guardian_contact, :photo, :photo_blob, NOW())
        ");

        $stmtInsert->bindValue(':lrn', $lrn);
        $stmtInsert->bindValue(':full_name', $enrolled['full_name']);
        $stmtInsert->bindValue(':id_number', $id_number);
        $stmtInsert->bindValue(':grade', $level==='junior' ? $inputValue : null);
        $stmtInsert->bindValue(':strand', $level==='senior' ? $inputValue : null);
        $stmtInsert->bindValue(':course', $level==='college'? $inputValue : null);
        $stmtInsert->bindValue(':home_address', $home_address);
        $stmtInsert->bindValue(':guardian_name', $guardian_name);
        $stmtInsert->bindValue(':guardian_contact', $guardian_contact);
        $stmtInsert->bindValue(':photo', $photoFilename);
        $stmtInsert->bindValue(':photo_blob', $photo_blob, PDO::PARAM_LOB);

        $stmtInsert->execute();

        $pdo->commit();

        send_json([
            'success'=>true,
            'msg'=>'Successfully Registered!',
            'id_number'=>$id_number,
            'photo_base64'=>$original_base64
        ]);

    } catch (Exception $e) {

        if($pdo->inTransaction()){
            $pdo->rollBack();
        }

        send_json([
            'success'=>false,
            'msg'=>'Server Error: '.$e->getMessage()
        ]);
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
/* ========== BASIC STYLING ========== */
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
    display: none;
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

<!-- ================= FORMS ================= -->
<?php
$forms = [
  'junior'=>['label'=>'Register Junior High Student','select'=>'grade','options'=>['Grade 7','Grade 8','Grade 9','Grade 10']],
  'senior'=>['label'=>'Register Senior High Student','select'=>'strand','options'=>['STEM','HUMMS','ABM','GAS','ICT']],
  'college'=>['label'=>'Register College Student','select'=>'course','options'=>['BSBA','BSE','BEE','BSCS','BAE']],
];

foreach($forms as $level=>$f){
  $id = $level==='junior'?'juniorHighForm':($level==='senior'?'seniorHighForm':'collegeForm');
  echo '<div class="card reg-form'.($level==='junior'?' active':'').'" id="'.$id.'">';
  echo '<h3>'.$f['label'].'</h3>';
  echo '<form class="reg-form-inner" data-level="'.$level.'">';
  echo '<div class="form-group"><input type="text" name="lrn" placeholder=" " required><label>LRN</label></div>';
  echo '<div class="enrollment-status"></div>';
  echo '<div class="form-group"><input type="text" name="full_name" placeholder=" " required><label>Full Name</label></div>';
  echo '<div class="form-group"><select name="'.$f['select'].'" required><option value="" hidden></option>';
  foreach($f['options'] as $opt) echo '<option value="'.$opt.'">'.$opt.'</option>';
  echo '</select><label>'.ucfirst($f['select']).'</label></div>';
  echo '<div class="form-group"><input type="text" name="home_address" placeholder=" " required><label>Home Address</label></div>';
  echo '<div class="form-group"><input type="text" name="guardian_name" placeholder=" " required><label>Guardian\'s Name</label></div>';
  echo '<div class="form-group"><input type="text" name="guardian_contact" placeholder=" " required><label>Guardian\'s Contact</label></div>';
  echo '<div class="form-group"><input type="file" class="photoInput" accept="image/*" required><img class="photoPreview" src="" alt="Photo Preview"></div>';
  echo '<button type="submit">Register</button></form></div>';
}
?>

<div id="popupMsg"></div>
</div>

<!-- ================= JS ================= -->
<script>
// ===== TAB SWITCH =====
const tabs = document.querySelectorAll('.tab-btn');
const formsEl = document.querySelectorAll('.reg-form');
const registeredLRNs = new Map();

tabs.forEach(tab=>tab.addEventListener('click',()=>{
  tabs.forEach(t=>t.classList.remove('active'));
  formsEl.forEach(f=>f.classList.remove('active'));
  tab.classList.add('active');
  document.getElementById(tab.dataset.target+'Form').classList.add('active');
}));

document.querySelectorAll('.reg-form-inner').forEach(form=>{
  const photoInput = form.querySelector('.photoInput');
  const preview = form.querySelector('.photoPreview');
  const lrnInput = form.querySelector('input[name="lrn"]');
  const statusDiv = form.querySelector('.enrollment-status');
  const submitBtn = form.querySelector('button');
  const allInputs = Array.from(form.querySelectorAll('input, select'));
  const editableInputs = allInputs.filter(i=>i.name!=='lrn'&&i.type!=='file');

  let resizedPhotoBase64 = '';
  let timer=null;
  preview.style.display='none';

  // ===== PHOTO RESIZE =====
  photoInput.addEventListener('change',e=>{
    const file = e.target.files[0];
    if(!file){resetPhoto(); return;}
    const reader = new FileReader();
    reader.onload=ev=>{
      const img=new Image();
      img.onload=()=>{
        let w=img.width,h=img.height,maxW=800,maxH=1000;
        if(w>maxW){h*=maxW/w;w=maxW;}
        if(h>maxH){w*=maxH/h;h=maxH;}
        const canvas=document.createElement('canvas');
        canvas.width=w; canvas.height=h;
        canvas.getContext('2d').drawImage(img,0,0,w,h);
        resizedPhotoBase64 = canvas.toDataURL('image/jpeg',0.85);
        preview.src=resizedPhotoBase64;
        preview.style.display='block';
      };
      img.src=ev.target.result;
    };
    reader.readAsDataURL(file);
  });
  function resetPhoto(){preview.src='';preview.style.display='none';resizedPhotoBase64='';photoInput.value='';}
  function setStatus(msg,type){statusDiv.innerHTML=msg;statusDiv.className='enrollment-status status-'+type;}
  function clearStatus(){statusDiv.innerHTML='';statusDiv.className='enrollment-status';}
  function resetEditableInputs(){editableInputs.forEach(i=>{i.value='';i.readOnly=false;i.disabled=false;});}
  function capitalize(s){return s.charAt(0).toUpperCase()+s.slice(1);}
  function highlightTab(level){tabs.forEach(t=>t.classList.remove('active'));formsEl.forEach(f=>f.classList.remove('active'));const t=document.querySelector(`.tab-btn[data-target="${level}"]`);const f=document.getElementById(level+'Form');if(t&&f){t.classList.add('active');f.classList.add('active');}}

    const levelFieldMap = {junior:'grade', senior:'strand', college:'course'};
    const fieldName = levelFieldMap[form.dataset.level];

function checkEnrollment(){
    clearTimeout(timer);

    const lrn = lrnInput.value.trim();
    const fullName = form.querySelector('input[name="full_name"]').value.trim();
    const fieldValue = form.querySelector(`select[name="${fieldName}"]`)?.value || '';

    // ✅ STOP EARLY CHECKING
    if(
        lrn.length < 12 ||          // typical LRN length
        fullName.length < 5 ||      // prevent 1-letter checking
        fieldValue === ''           // require grade/strand/course
    ){
        clearStatus();
        return;
    }

    statusDiv.innerHTML='Checking enrollment...';
    statusDiv.className='enrollment-status status-checking';

    timer=setTimeout(async()=>{
        try{
            const res=await fetch(
                `checkEnrolled.php?lrn=${encodeURIComponent(lrn)}&full_name=${encodeURIComponent(fullName)}&level=${form.dataset.level}&${fieldName}=${encodeURIComponent(fieldValue)}`
            );

            const data=await res.json();

            if(!data.success){
                setStatus(data.msg,'error');

                // 🔓 unlock LRN if failed
                lrnInput.readOnly = false;

            }else{
                setStatus('✔ Student is officially enrolled','success');

                // 🔒 lock LRN when valid
                lrnInput.readOnly = true;
            }

        }catch(err){
            console.error(err);
            setStatus('Server error','error');
        }
    },500);
}

lrnInput.addEventListener('input', checkEnrollment);
form.querySelector('input[name="full_name"]').addEventListener('blur', checkEnrollment);
form.querySelector(`select[name="${fieldName}"]`).addEventListener('change', checkEnrollment);

  // ===== FORM SUBMIT =====
  form.addEventListener('submit',async e=>{
    e.preventDefault();
    const lrn=lrnInput.value.trim();
    if(!resizedPhotoBase64){setStatus('Please select a photo.','error');return;}
    if(!statusDiv.classList.contains('status-success')){setStatus('Student must be enrolled before registering.','error');return;}
    if(registeredLRNs.has(lrn)){setStatus(`This student is already registered under "${capitalize(registeredLRNs.get(lrn))}" tab.`,'error');highlightTab(registeredLRNs.get(lrn));return;}
    submitBtn.disabled=true;submitBtn.innerText='Processing...';
    const fd=new FormData();fd.append('level',form.dataset.level);allInputs.forEach(i=>{if(i.name&&i.type!=='file')fd.append(i.name,i.value);});fd.append('photo_base64',resizedPhotoBase64);
    try{
      const res=await fetch('index.php',{method:'POST',body:fd});
      const data=await res.json();
      if(data.success){showPopup(`✔ Registration Successful!<br>ID: ${data.id_number}`, "success");preview.src=data.photo_base64;preview.style.display='block';registeredLRNs.set(lrn,form.dataset.level);resetPhoto();resetEditableInputs();allInputs.forEach(i=>{
    if(i.tagName==='SELECT'){
        i.selectedIndex=0;
        i.disabled=false;
    }
    if(i.readOnly) i.readOnly=false;});lrnInput.value='';
    lrnInput.readOnly = false;setTimeout(clearStatus,5000);
    } else {showPopup(data.msg,'error');}
    }catch(err){console.error(err);setStatus('Submission failed. Please try again.','error');}
    submitBtn.disabled=false;submitBtn.innerText='Register';
  });
});

function showPopup(message, type="success"){
    const popup = document.getElementById("popupMsg");

    popup.innerHTML = message;
    popup.style.display = "block";

    if(type === "success"){
        popup.style.background = "#28a745";
    } else {
        popup.style.background = "#dc3545";
    }

    setTimeout(()=>{
        popup.style.display = "none";
    }, 4000);
}
</script>
</body>
</html>