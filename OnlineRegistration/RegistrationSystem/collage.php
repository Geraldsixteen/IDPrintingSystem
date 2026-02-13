<?php  
session_start();

// ================== PostgreSQL Connection ==================
// Include your existing database connection
require_once __DIR__ . '/../Config/database.php';
// ==========================================================

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lrn = trim($_POST['lrn']);
    $full_name = trim($_POST['full_name']);
    $id_number = trim($_POST['id_number']);
    $course = trim($_POST['course']);
    $home_address = trim($_POST['home_address']);
    $guardian_name = trim($_POST['guardian_name']);
    $guardian_contact = trim($_POST['guardian_contact']);
    $upload_photo = '';

    // Handle photo capture or upload
    if (!empty($_POST['captured_photo'])) {
        $img = str_replace('data:image/png;base64,', '', $_POST['captured_photo']);
        $img = str_replace(' ', '+', $img);
        $data = base64_decode($img);
        $upload_dir = __DIR__ . '/../uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $upload_photo = time() . '_captured.png';
        file_put_contents($upload_dir . $upload_photo, $data);
    } elseif (isset($_FILES['upload_photo']) && $_FILES['upload_photo']['error'] === 0) {
        $allowed = ['jpg','jpeg','png','gif'];
        $ext = strtolower(pathinfo($_FILES['upload_photo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $upload_dir = __DIR__ . '/../uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $upload_photo = time() . '_' . basename($_FILES['upload_photo']['name']);
            move_uploaded_file($_FILES['upload_photo']['tmp_name'], $upload_dir . $upload_photo);
        } else {
            $msg = "Invalid file type. Allowed: jpg, jpeg, png, gif.";
        }
    } else {
        $msg = "Please upload or take a photo.";
    }

    // Insert to DB if no errors
    if ($msg === '') {
        // Check if LRN already exists
        $check = $pdo->prepare("SELECT id FROM register WHERE lrn = :lrn");
        $check->execute(['lrn' => $lrn]);

        if ($check->rowCount() > 0) {
            $msg = "This LRN is already registered!";
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO register 
                (lrn, full_name, id_number, course, home_address, guardian_name, guardian_contact, upload_photo, created_at)
                VALUES (:lrn, :full_name, :id_number, :course, :home_address, :guardian_name, :guardian_contact, :upload_photo, NOW())
            ");

            $success = $stmt->execute([
                'lrn' => $lrn,
                'full_name' => $full_name,
                'id_number' => $id_number,
                'course' => $course,
                'home_address' => $home_address,
                'guardian_name' => $guardian_name,
                'guardian_contact' => $guardian_contact,
                'upload_photo' => $upload_photo
            ]);

            if ($success) {
                $_SESSION['success'] = "Registration successful!";
                header("Location: collage.php");
                exit;
            } else {
                $msg = "Database error: " . implode(", ", $stmt->errorInfo());
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>College Registration - ID Printing System</title>
<link rel="stylesheet" href="registration.css">
</head>
<body>
<div class="main">
    <div class="topbar">
        <img src="../cdlb.png" alt="Logo">
        <h3>College Student ID Registration</h3>
    </div>

    <div class="content">
        <div class="card">  
            <h3>College Student Registration</h3>
            <?php
            if (isset($_SESSION['success'])) { 
                echo '<div class="message success">'.$_SESSION['success'].'</div>'; 
                unset($_SESSION['success']); 
            } elseif ($msg!=='') { 
                echo '<div class="message error">'.$msg.'</div>'; 
            }
            ?>
            <form id="reg-form" action="collage.php" method="POST" enctype="multipart/form-data">
                <label>LRN</label>
                <input type="text" name="lrn" id="lrn" required>
                <label>Full Name</label>
                <input type="text" name="full_name" id="full_name" required>
                <label>ID Number</label>
                <input type="text" name="id_number" id="id_number" required>
                <label>Course</label>
                <select name="course" id="course" required>
                    <option value="" disabled>-- Select Course --</option>
                    <option value="BSIT">BSIT</option>
                    <option value="BSBA">BSBA</option>
                    <option value="BSHM">BSHM</option>
                    <option value="BEED">BEED</option>
                    <option value="BSED">BSED</option>
                </select>
                <label>Home Address</label>
                <input type="text" name="home_address" id="home_address" required>
                <label>Guardian's Name</label>
                <input type="text" name="guardian_name" id="guardian_name" required>
                <label>Guardian's Contact</label>
                <input type="text" name="guardian_contact" id="guardian_contact" required>
                <label>Upload or Take Photo</label>
                <label for="upload_photo" class="btn">📁 Choose Photo</label>
                <input type="file" name="upload_photo" id="upload_photo" accept="image/*" style="display:none;">
                <button type="button" id="start-camera" class="capture-btn">📸 Take Photo</button>
                <video id="camera" autoplay></video>
                <canvas id="captured" width="320" height="240"></canvas>
                <input type="hidden" name="captured_photo" id="captured_photo">
                <center><img id="preview-photo" src="" alt=""></center>
                <center><button type="submit" class="submit">Register</button></center>
            </form>
        </div>
    </div>
</div>

<script>
// Camera capture with preview
const camera = document.getElementById('camera');
const canvas = document.getElementById('captured');
const startBtn = document.getElementById('start-camera');
const hiddenInput = document.getElementById('captured_photo');
const previewImg = document.getElementById('preview-photo');
let stream = null;

startBtn.addEventListener('click', async () => {
    if (!stream) {
        try {
            stream = await navigator.mediaDevices.getUserMedia({video:true});
            camera.srcObject = stream; camera.style.display='block';
            startBtn.textContent='📸 Capture Photo';
        } catch { alert('Camera access denied.'); }
    } else {
        const context = canvas.getContext('2d');
        canvas.style.display='block'; camera.style.display='none';
        context.drawImage(camera,0,0,canvas.width,canvas.height);
        hiddenInput.value = canvas.toDataURL('image/png');
        previewImg.src = hiddenInput.value;
        previewImg.style.display='block';
        stream.getTracks().forEach(track => track.stop());
        stream=null; startBtn.textContent='Retake Photo';
    }
});
</script>
<script src="../theme.js"></script>
</body>
</html>
