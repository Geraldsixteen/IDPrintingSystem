<?php  
require_once __DIR__ . '/admin-auth.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// ================== PostgreSQL Connection ==================
require_once __DIR__ . '/../config/database.php';

// ==========================================================

$student = null;

if (isset($_POST['search'])) {
    $id_number = trim($_POST['id_number']);

    $stmt = $pdo->prepare("SELECT * FROM register WHERE id_number = ? LIMIT 1");
    $stmt->execute([$id_number]);
    $student = $stmt->fetch();
}

// Determine theme
$themeClass = (isset($_COOKIE['theme']) && $_COOKIE['theme'] == 'dark') ? 'dark' : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Print ID - ID Printing System</title>
<link rel="stylesheet" href="../style.css">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:"Segoe UI", Arial, sans-serif; background:#c9dfff; display:flex; min-height:100vh; color:#333; transition:0.3s ease, color 0.3s ease; }
.sidebar a:hover, .sidebar a.active { background:#1f2857; }
.main { flex:1; display:flex; flex-direction:column; }
.container { padding:20px; flex:1; display:flex; justify-content:center; align-items:flex-start; }
.card { background:white; padding:25px; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.15); width:100%; max-width:600px; max-height:85vh; overflow-y:auto; transition:0.3s, color 0.3s; }
label { display:block; margin:12px 0 6px; font-weight:600; }
input, button { padding:12px; width:100%; margin-bottom:15px; border:1px solid #ccc; border-radius:6px; font-size:15px; }
input:focus { border-color:#3549a3; outline:none; box-shadow:0 0 6px rgba(53,73,163,0.2); }
button { background:#3549a3; color:white; border:none; cursor:pointer; font-size:16px; font-weight:600; transition:0.3s; }
button:hover { background:#2d3a80; }
.preview { border:1px solid #ddd; padding:15px; text-align:left; margin-top:15px; background:#fafafa; border-radius:6px; transition:0.3s, color 0.3s; }
.preview h3 { margin-bottom:10px; text-align:center; }
body.dark .card { background:#2c2c2c; color:#eaeaea; }
body.dark .preview { background:#1f1f1f; color:#ccc; }
body.dark .topbar{background:#111; color:#fff; border-bottom:1px solid white;}
@media (max-width:768px) { body { flex-direction:column; } .sidebar { width:100%; flex-direction:row; overflow-x:auto; } .sidebar a { margin:3px 10px; } .card { width:95%; } table { min-width:auto; font-size:12px; } .toggle-mode{height:60px;line-height:40px;margin:10px; white-space: nowrap; } }
</style>
</head>
<body class="<?= $themeClass ?>">

<div class="sidebar">
    <div>
        <h2>ID System</h2>
        <a href="index.php">🏠 Dashboard</a>
        <a href="print-id.php" class="active">🖨️ Print</a>
        <a href="records.php">📑 Records</a>
        <a href="archive.php">📁 Archive</a>
        <a href="logout.php">📤 Logout</a>
    </div>
    <div class="toggle-mode" onclick="toggleMode()">🌙 Dark Mode</div>
</div>

<div class="main">
    <div class="topbar">Print ID</div>
    <div class="container">
        <div class="card">
            <form method="POST">
                <label for="id_number">Enter ID Number:</label>
                <input type="text" name="id_number" id="id_number" placeholder="Type ID Number" required>
                <button type="submit" name="search">Search & Preview</button>
            </form>

            <div class="preview">
                <h3>ID Preview</h3>
                <?php if ($student): ?>
                    <p><strong>LRN:</strong> <?= htmlspecialchars($student['lrn']) ?></p>
                    <p><strong>Full Name:</strong> <?= htmlspecialchars($student['full_name']) ?></p>
                    <p><strong>ID Number:</strong> <?= htmlspecialchars($student['id_number']) ?></p>
                    <?php
                        if (!empty($student['grade'])) {
                            echo "<p><strong>Grade:</strong> ".htmlspecialchars($student['grade'])."</p>";
                        } elseif (!empty($student['strand'])) {
                            echo "<p><strong>Strand:</strong> ".htmlspecialchars($student['strand'])."</p>";
                        } elseif (!empty($student['course'])) {
                            echo "<p><strong>Course:</strong> ".htmlspecialchars($student['course'])."</p>";
                        }
                    ?>
                    <p><strong>Address:</strong> <?= htmlspecialchars($student['home_address']) ?></p>
                    <p><strong>Guardian:</strong> <?= htmlspecialchars($student['guardian_name']) ?></p>
                    <p><strong>Contact:</strong> <?= htmlspecialchars($student['guardian_contact']) ?></p>
                    <form method="POST" action="print.php" target="_blank">
                        <input type="hidden" name="id_number" value="<?= htmlspecialchars($student['id_number']) ?>">
                        <button type="submit">🖨️ Print ID</button>
                    </form>
                <?php else: ?>
                    <p>No ID selected</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="../theme.js"></script>
</body>
</html>
