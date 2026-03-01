<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

/* ===== Load .env ===== */
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeload();


/* ===============================
   HELPER
=================================*/
function send_json($arr){
    if(ob_get_level()) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($arr);
    exit;
}

/* ===============================
   HANDLE POST
=================================*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* ========= OTP REQUEST ========= */
    if(isset($_POST['otp_request'], $_POST['email'])){

        $email = trim($_POST['email']);

        if(!filter_var($email, FILTER_VALIDATE_EMAIL) || !str_ends_with($email,'@gmail.com')){
            send_json(['success'=>false,'msg'=>'Enter a valid Gmail account.']);
        }

        $otp_code = str_pad(random_int(0,999999),6,'0',STR_PAD_LEFT);
        $otp_expiry = time() + 300; // 5 minutes

        $_SESSION['otp'][$email] = [
            'code' => $otp_code,
            'expiry' => $otp_expiry,
            'verified' => false
        ];

        try{
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['MAIL_USERNAME']; // from .env
            $mail->Password   = $_ENV['MAIL_PASSWORD']; // from .env
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;

            $mail->setFrom($_ENV['MAIL_FROM'],'CDLB Registration');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Your OTP Code';
            $mail->Body    = "
                Hello Student,<br><br>
                Your OTP Code is: <b>$otp_code</b><br>
                This code will expire in 5 minutes.
            ";

            $mail->send();
            send_json(['success'=>true,'msg'=>'OTP sent successfully.']);

        }catch(Exception $e){
            send_json([
                'success'=>false,
                'msg'=>'Mailer Error: '.$mail->ErrorInfo
            ]);
        }
    }

    /* ========= OTP VERIFY ========= */
    if(isset($_POST['otp_verify'], $_POST['email'], $_POST['otp'])){

        $email = trim($_POST['email']);
        $otp_input = trim($_POST['otp']);

        if(!isset($_SESSION['otp'][$email])){
            send_json(['success'=>false,'msg'=>'No OTP requested.']);
        }

        $otpData = $_SESSION['otp'][$email];

        if(time() > $otpData['expiry']){
            unset($_SESSION['otp'][$email]);
            send_json(['success'=>false,'msg'=>'OTP expired.']);
        }

        if($otp_input !== $otpData['code']){
            send_json(['success'=>false,'msg'=>'Invalid OTP.']);
        }

        $_SESSION['otp'][$email]['verified'] = true;

        send_json(['success'=>true,'msg'=>'OTP verified successfully.']);
    }

    /* ========= REGISTRATION ========= */
    try {

        $level = $_POST['level'] ?? '';
        $lrn = trim($_POST['lrn'] ?? '');
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $home_address = trim($_POST['home_address'] ?? '');
        $guardian_name = trim($_POST['guardian_name'] ?? '');
        $guardian_contact = trim($_POST['guardian_contact'] ?? '');
        $photo_base64 = $_POST['photo_base64'] ?? '';

        if(!$lrn || !$full_name || !$email || !$level || !$home_address || !$guardian_name || !$guardian_contact || !$photo_base64){
            send_json(['success'=>false,'msg'=>'Incomplete information.']);
        }

        /* ---- CHECK OTP VERIFIED ---- */
        if(
            !isset($_SESSION['otp'][$email]) ||
            $_SESSION['otp'][$email]['verified'] !== true
        ){
            send_json(['success'=>false,'msg'=>'Please verify OTP first.']);
        }

        /* ---- DETERMINE LEVEL FIELD ---- */
        $grade = $strand = $course = null;

        if($level === 'junior'){
            $grade = trim($_POST['grade'] ?? '');
        } elseif($level === 'senior'){
            $strand = trim($_POST['strand'] ?? '');
        } elseif($level === 'college'){
            $course = trim($_POST['course'] ?? '');
        }

        if(!$grade && !$strand && !$course){
            send_json(['success'=>false,'msg'=>'Please select Grade/Strand/Course.']);
        }

        /* ---- DECODE PHOTO ---- */
        $photo_blob = base64_decode(
            preg_replace('#^data:image/\w+;base64,#i','',$photo_base64)
        );

        if(!$photo_blob){
            send_json(['success'=>false,'msg'=>'Invalid photo data.']);
        }

        $pdo->beginTransaction();

        if(!isset($pdo)){
            send_json(['success'=>false,'msg'=>'Database connection not found']);
        }

        /* ---- DUPLICATE CHECK ---- */
        $stmt = $pdo->prepare("SELECT id FROM register WHERE lrn=:lrn OR email=:email LIMIT 1");
        $stmt->execute([
            ':lrn'=>$lrn,
            ':email'=>$email
        ]);

        if($stmt->fetch()){
            $pdo->rollBack();
            send_json(['success'=>false,'msg'=>'Student already registered.']);
        }

        $photoFilename = 'STU_'.time().'_'.mt_rand(1000,9999).'.jpg';

        /* ---- INSERT (NO ID NUMBER YET) ---- */
        $stmtInsert = $pdo->prepare("
            INSERT INTO register
            (lrn, full_name, id_number, grade, strand, course, email,
             home_address, guardian_name, guardian_contact,
             photo, photo_blob, created_at, status, email_verified)
            VALUES
            (:lrn, :full_name, NULL, :grade, :strand, :course, :email,
             :home_address, :guardian_name, :guardian_contact,
             :photo, :photo_blob, NOW(), 'pending', true)
        ");

        $stmtInsert->bindParam(':lrn',$lrn);
        $stmtInsert->bindParam(':full_name',$full_name);
        $stmtInsert->bindParam(':grade',$grade);
        $stmtInsert->bindParam(':strand',$strand);
        $stmtInsert->bindParam(':course',$course);
        $stmtInsert->bindParam(':email',$email);
        $stmtInsert->bindParam(':home_address',$home_address);
        $stmtInsert->bindParam(':guardian_name',$guardian_name);
        $stmtInsert->bindParam(':guardian_contact',$guardian_contact);
        $stmtInsert->bindParam(':photo',$photoFilename);
        $stmtInsert->bindParam(':photo_blob',$photo_blob, PDO::PARAM_LOB);

        $stmtInsert->execute();

        $pdo->commit();

        unset($_SESSION['otp'][$email]);

        send_json([
            'success'=>true,
            'msg'=>'Registration submitted. Waiting for admin approval.'
        ]);

    }catch(Exception $e){
        send_json([
            'success'=>false,
            'msg'=>'Server error: '.$e->getMessage()
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
/* ===== CSS STYLING ===== */
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
.enrollment-status{font-size:13px;margin-top:-10px;margin-bottom:10px;font-weight:600;}
.status-success{color:#28a745;}
.status-error{color:#dc3545;}
.status-checking{color:#ffc107;}
.photoPreview{display:none;margin:10px auto;width:150px;height:200px;object-fit:cover;border-radius:10px;border:1px solid #ccc;}
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
  echo '<div class="form-group" style="display:flex;align-items:center;gap:10px;">
        <input type="email" name="email" placeholder=" " required pattern=".+@gmail\.com$">
        <label>Gmail Address</label>
        <button type="button" class="otp-btn">Get OTP</button>
    </div>

    <div class="form-group otp-verification" style="display:none;flex-direction:column;">
        <input type="text" name="otp" placeholder="Enter OTP">
        <button type="button" class="verify-otp-btn">Verify OTP</button>
        <span class="otp-msg"></span>
    </div>';
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

<script>
document.addEventListener('DOMContentLoaded', () => {
    // ===== TAB SWITCHING =====
    const tabs = document.querySelectorAll('.tab-btn');
    const formsEl = document.querySelectorAll('.reg-form');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            // Remove active from all tabs and forms
            tabs.forEach(t => t.classList.remove('active'));
            formsEl.forEach(f => f.classList.remove('active'));

            // Add active to clicked tab
            tab.classList.add('active');

            // Add active to corresponding form
            const targetForm = document.getElementById(tab.dataset.target + 'Form');
            if (targetForm) targetForm.classList.add('active');
        });
    });

    // ===== FORM HANDLING =====
    document.querySelectorAll('.reg-form-inner').forEach(form => {
        const photoInput = form.querySelector('.photoInput');
        const preview = form.querySelector('.photoPreview');
        const otpBtn = form.querySelector('.otp-btn');
        const otpSection = form.querySelector('.otp-verification');
        const verifyBtn = form.querySelector('.verify-otp-btn');
        const otpInput = form.querySelector('input[name="otp"]');
        const otpMsg = form.querySelector('.otp-msg');
        const statusDiv = form.querySelector('.enrollment-status');
        let resizedPhotoBase64 = '';
        let otpVerified = false;

        // ===== PHOTO PREVIEW & RESIZE =====
        photoInput.addEventListener('change', e => {
            const file = e.target.files[0];
            if (!file) {
                preview.src = '';
                preview.style.display = 'none';
                resizedPhotoBase64 = '';
                return;
            }
            const reader = new FileReader();
            reader.onload = ev => {
                const img = new Image();
                img.onload = () => {
                    let w = img.width, h = img.height, maxW = 800, maxH = 1000;
                    if (w > maxW) { h *= maxW / w; w = maxW; }
                    if (h > maxH) { w *= maxH / h; h = maxH; }
                    const canvas = document.createElement('canvas');
                    canvas.width = w; canvas.height = h;
                    canvas.getContext('2d').drawImage(img, 0, 0, w, h);
                    resizedPhotoBase64 = canvas.toDataURL('image/jpeg', 0.85);
                    preview.src = resizedPhotoBase64;
                    preview.style.display = 'block';
                };
                img.src = ev.target.result;
            };
            reader.readAsDataURL(file);
        });

        // ===== OTP REQUEST =====
        otpBtn.addEventListener('click', () => {
            const emailValue = form.querySelector('input[name="email"]').value.trim();
            if (!emailValue) {
                showPopup('Enter Gmail first');
                return;
            }
            showPopup('Sending OTP...');
            otpSection.style.display = 'flex';
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ otp_request: 1, email: emailValue })
            })
            .then(r => r.json())
            .then(d => showPopup(d.msg))
            .catch(() => showPopup('Error sending OTP'));
        });

        // ===== OTP VERIFY =====
        verifyBtn.addEventListener('click', () => {
            const emailValue = form.querySelector('input[name="email"]').value.trim();
            const otpValue = otpInput.value.trim();
            if (!otpValue) { showPopup('Enter OTP first'); return; }
            showPopup('Verifying OTP...');
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ otp_verify: 1, email: emailValue, otp: otpValue })
            })
            .then(r => r.json())
            .then(d => {
                showPopup(d.msg);
                if (d.success) {
                    otpVerified = true;
                    otpSection.style.display = 'none';
                    statusDiv.textContent = 'OTP verified ✅';
                    statusDiv.className = 'enrollment-status status-success';
                }
            })
            .catch(() => showPopup('Error verifying OTP'));
        });

        // ===== FORM SUBMISSION =====
        form.addEventListener('submit', e => {
            e.preventDefault();
            if (!otpVerified) { showPopup('Verify OTP first'); return; }

            const formData = new FormData(form);
            formData.append('photo_base64', resizedPhotoBase64);
            formData.append('level', form.dataset.level);
            formData.append('otp', otpInput.value.trim());

            fetch('', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(d => {
                    showPopup(d.msg);
                    if (d.success) {
                        form.reset();
                        preview.src = '';
                        preview.style.display = 'none';
                        otpVerified = false;
                        statusDiv.textContent = '';
                        otpSection.style.display = 'none';
                        otpMsg.textContent = '';
                    }
                })
                .catch(() => statusDiv.textContent = 'Server error');
        });
    });

    // ===== POPUP FUNCTION =====
    function showPopup(msg) {
        const p = document.getElementById('popupMsg');
        p.textContent = msg;
        p.style.display = 'block';
        setTimeout(() => { p.style.display = 'none'; }, 4000);
    }
});
</script>
</body>
</html>