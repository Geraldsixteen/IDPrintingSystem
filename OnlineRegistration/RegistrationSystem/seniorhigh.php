<?php
session_start();
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING); // hide notices/warnings for JSON output

// ================== PostgreSQL Connection ==================
// Adjust this absolute path to match your Config folder on Render
require_once __DIR__ . '/Config/database.php';
// ==========================================================

$response = ['success' => false, 'msg' => ''];

// ===== POST handling =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    header('Content-Type: application/json'); // JSON response

    $lrn = trim($_POST['lrn'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $id_number = trim($_POST['id_number'] ?? '');
    $strand = trim($_POST['strand'] ?? '');
    $home_address = trim($_POST['home_address'] ?? '');
    $guardian_name = trim($_POST['guardian_name'] ?? '');
    $guardian_contact = trim($_POST['guardian_contact'] ?? '');
    $photo_filename = null;

    // Validate required fields
    if (!$lrn || !$full_name || !$id_number || !$strand) {
        $response['msg'] = "Please fill in all required fields.";
        echo json_encode($response);
        exit;
    }

if (!empty($_FILES['photo']['tmp_name'])) {
    $fileTmp = $_FILES['photo']['tmp_name'];
    $fileName = basename($_FILES['photo']['name']);
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','gif'];

    if (!in_array($fileExt, $allowed)) {
        $response['msg'] = "Only JPG, PNG, GIF files are allowed.";
        echo json_encode($response);
        exit;
    } elseif ($_FILES['photo']['size'] > 3 * 1024 * 1024) {
        $response['msg'] = "Image too large (max 3MB).";
        echo json_encode($response);
        exit;
    } else {
        // Clean filename
        $photo_filename = time() . '_' . preg_replace("/[^a-zA-Z0-9_\-\.]/", "", $fileName);

        // === 1️⃣ Save to ephemeral folder ===
        $uploadDir = __DIR__ . '/../Public/Uploads/'; // move outside of the PHP folder if needed
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $targetPath = $uploadDir . $photo_filename;
        if (!move_uploaded_file($fileTmp, $targetPath)) {
            $response['msg'] = "Failed to upload photo.";
            echo json_encode($response);
            exit;
        }

        // === 2️⃣ Auto backup to local system ===
        $backupDir = 'C:/LocalAdminSystem/UploadsBackup/';
        if (!is_dir($backupDir)) mkdir($backupDir, 0777, true);
        $backupPath = $backupDir . $photo_filename;
        if (!copy($targetPath, $backupPath)) {
            // Optional: log but don’t block registration
            error_log("Failed to backup photo to local folder: $backupPath");
        }
    }
}


    // ===== Check if LRN exists =====
    $check = $pdo->prepare("SELECT id FROM register WHERE lrn = :lrn");
    $check->execute(['lrn' => $lrn]);
    if ($check->rowCount() > 0) {
        $response['msg'] = 'This LRN is already registered!';
        echo json_encode($response);
        exit;
    }

    // ===== Insert into database =====
    $stmt = $pdo->prepare("
        INSERT INTO register 
        (lrn, full_name, id_number, strand, home_address, guardian_name, guardian_contact, photo, created_at)
        VALUES (:lrn, :full_name, :id_number, :strand, :home_address, :guardian_name, :guardian_contact, :photo, NOW())
    ");
    $stmt->execute([
        ':lrn' => $lrn,
        ':full_name' => $full_name,
        ':id_number' => $id_number,
        ':strand' => $strand,
        ':home_address' => $home_address,
        ':guardian_name' => $guardian_name,
        ':guardian_contact' => $guardian_contact,
        ':photo' => $photo_filename
    ]);

    $response['success'] = true;
    $response['msg'] = 'Registration successful!';
    $response['data'] = [
        'id' => $pdo->lastInsertId(),
        'lrn' => $lrn,
        'full_name' => $full_name,
        'id_number' => $id_number,
        'strand' => $strand,
        'home_address' => $home_address,
        'guardian_name' => $guardian_name,
        'guardian_contact' => $guardian_contact,
        'photo' => $photo_filename,
        'created_at' => date('Y-m-d H:i:s')
    ];

    echo json_encode($response);
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Senior High Registration - ID Printing System</title>
<link rel="stylesheet" href="registration.css">
</head>
<body>
<div class="main">
    <div class="topbar">
        <img src="/Public/cdlb.png">
        <h3>Senior High Student ID Registration</h3>
    </div>

    <div class="content">
        <div class="card">  
            <h3>Senior High Student Registration</h3>
            <form id="reg-form" action="seniorhigh.php" method="POST" enctype="multipart/form-data">
                <label>LRN</label>
                <input type="text" name="lrn" required>
                <label>Full Name</label>
                <input type="text" name="full_name" required>
                <label>ID Number</label>
                <input type="text" name="id_number" required>
                <label>Strand</label>
                <select name="strand" required>
                    <option value="">-- Select Strand --</option>
                    <option value="STEM">STEM</option>
                    <option value="HUMMS">HUMMS</option>
                    <option value="ABM">ABM</option>
                    <option value="GAS">GAS</option>
                    <option value="ICT">ICT</option>
                </select>
                <label>Home Address</label>
                <input type="text" name="home_address" required>
                <label>Guardian's Name</label>
                <input type="text" name="guardian_name" required>
                <label>Guardian's Contact</label>
                <input type="text" name="guardian_contact" required>
                <label>Upload Photo</label>
                <input type="file" name="photo" accept="image/*" required>
                <center><button type="submit" class="submit">Register</button></center>
            </form>
        </div>
    </div>
</div>

<script>
const form = document.getElementById('reg-form');

form.addEventListener('submit', function(e){
    e.preventDefault();
    const formData = new FormData(form);

    fetch('seniorhigh.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.text())
    .then(text => {
        let data;
        try {
            data = JSON.parse(text);
        } catch(err){
            console.error('Invalid JSON response:', text);
            alert('Server error, check console.');
            return;
        }
        if(data.success){
            alert(data.msg);
            form.reset();
        } else {
            alert('Error: ' + (data.msg || 'Unknown'));
        }
    })
    .catch(err => console.error(err));
});
</script>
</body>
</html>
