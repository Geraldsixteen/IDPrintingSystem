<?php
session_start();
error_reporting(E_ALL);

require_once __DIR__ . '/../Config/database.php';

$response = ['success' => false, 'msg' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    header('Content-Type: application/json');

    // Collect form fields
    $lrn = trim($_POST['lrn'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $id_number = trim($_POST['id_number'] ?? '');
    $strand = trim($_POST['strand'] ?? '');
    $home_address = trim($_POST['home_address'] ?? '');
    $guardian_name = trim($_POST['guardian_name'] ?? '');
    $guardian_contact = trim($_POST['guardian_contact'] ?? '');

    // Basic validation
    if (!$lrn || !$full_name || !$id_number || !$strand) {
        $response['msg'] = "Please fill in all required fields.";
        echo json_encode($response);
        exit;
    }

    // ==== Handle photo upload ====
    $photo_blob = null;
    $photo_filename = null;

    if (!empty($_FILES['photo']['tmp_name'])) {
        $tmp = $_FILES['photo']['tmp_name'];
        $original = basename($_FILES['photo']['name']);

        // Validate image extension
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($ext, $allowed)) {
            echo json_encode(['success'=>false,'msg'=>'Invalid image type']);
            exit;
        }

        // Read binary for DB
        $photo_blob = file_get_contents($tmp);

        // === Local Admin Backup ===
        $localBackupDir = __DIR__ . '/../../LocalAdminSystem/AdminSystem/Uploads/';
        if (!is_dir($localBackupDir)) mkdir($localBackupDir, 0777, true);

        $photo_filename = $lrn . '_' . time() . '.' . $ext;
        copy($tmp, $localBackupDir . $photo_filename);

        // === Persistent Render Backup ===
        $renderBackupDir = '/mnt/data/UploadsBackup/';
        if (!is_dir($renderBackupDir)) mkdir($renderBackupDir, 0777, true);

        copy($tmp, $renderBackupDir . $photo_filename);
    }

    // Check duplicate LRN
    $check = $pdo->prepare("SELECT id FROM register WHERE lrn = :lrn");
    $check->execute(['lrn' => $lrn]);
    if ($check->rowCount() > 0) {
        $response['msg'] = 'This LRN is already registered!';
        echo json_encode($response);
        exit;
    }

    // Insert into DB
    try {
        $stmt = $pdo->prepare("
            INSERT INTO register 
            (lrn, full_name, id_number, strand, home_address, guardian_name, guardian_contact, photo_blob, created_at)
            VALUES (:lrn, :full_name, :id_number, :strand, :home_address, :guardian_name, :guardian_contact, :photo_blob, NOW())
        ");
        $stmt->bindParam(':lrn', $lrn);
        $stmt->bindParam(':full_name', $full_name);
        $stmt->bindParam(':id_number', $id_number);
        $stmt->bindParam(':strand', $strand);
        $stmt->bindParam(':home_address', $home_address);
        $stmt->bindParam(':guardian_name', $guardian_name);
        $stmt->bindParam(':guardian_contact', $guardian_contact);
        $stmt->bindParam(':photo_blob', $photo_blob, PDO::PARAM_LOB);

        $stmt->execute();

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
            'photo_filename' => $photo_filename,
            'created_at' => date('Y-m-d H:i:s')
        ];

    } catch (PDOException $e) {
        $response['success'] = false;
        $response['msg'] = "Database error: " . $e->getMessage();
    }

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
        <img src="../Public/cdlb.png">
        <h3>Senior High Student ID Registration</h3>
    </div>

    <div class="content">
        <div class="card">  
            <h3>Senior High Student Registration</h3>
            <form id="reg-form" action="index.php" method="POST" enctype="multipart/form-data">
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
    fetch('index.php', { method:'POST', body:formData })
    .then(res => res.json())
    .then(data => {
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
