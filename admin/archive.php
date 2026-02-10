<?php 
require_once __DIR__ . '/admin-auth.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// ================== PostgreSQL Connection ==================
// Include your existing database connection
require_once __DIR__ . '/../config/database.php';

// ==========================================================

// GET filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$grade  = isset($_GET['grade']) ? $_GET['grade'] : '';
$strand = isset($_GET['strand']) ? $_GET['strand'] : '';
$course = isset($_GET['course']) ? $_GET['course'] : '';

// Restore record
$msg = '';
if (isset($_POST['restore_id'])) {
    $id = intval($_POST['restore_id']);

    $stmt = $pdo->prepare("SELECT * FROM archive WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();

    if ($row) {
        $stmtInsert = $pdo->prepare("
            INSERT INTO register
            (lrn, full_name, id_number, grade, strand, course, home_address, guardian_name, guardian_contact, upload_photo, created_at)
            VALUES (:lrn, :full_name, :id_number, :grade, :strand, :course, :home_address, :guardian_name, :guardian_contact, :upload_photo, NOW())
        ");
        $stmtInsert->execute([
            'lrn' => $row['lrn'],
            'full_name' => $row['full_name'],
            'id_number' => $row['id_number'],
            'grade' => $row['grade'],
            'strand' => $row['strand'],
            'course' => $row['course'],
            'home_address' => $row['home_address'],
            'guardian_name' => $row['guardian_name'],
            'guardian_contact' => $row['guardian_contact'],
            'upload_photo' => $row['upload_photo']
        ]);

        $stmtDel = $pdo->prepare("DELETE FROM archive WHERE id = :id");
        $stmtDel->execute(['id' => $id]);

        $msg = "Record restored successfully!";
    }
}

// Toggle status
if (isset($_POST['toggle_status'])) {
    $id = intval($_POST['toggle_status']);

    $stmt = $pdo->prepare("SELECT status FROM archive WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();

    if ($row) {
        $newStatus = ($row['status'] == 'Released') ? 'Not Released' : 'Released';
        $stmtUpd = $pdo->prepare("UPDATE archive SET status = :status WHERE id = :id");
        $stmtUpd->execute(['status' => $newStatus, 'id' => $id]);

        $msg = "Status updated!";
    }
}

// Build SQL with filters
$sql = "SELECT * FROM archive WHERE 1=1";
$params = [];

// Search filter
if (!empty($search)) {
    $sql .= " AND (lrn ILIKE :search OR full_name ILIKE :search OR id_number ILIKE :search)";
    $params['search'] = "%$search%";
}

// Other filters
if (!empty($grade))  { $sql .= " AND grade = :grade";  $params['grade'] = $grade; }
if (!empty($strand)) { $sql .= " AND strand = :strand"; $params['strand'] = $strand; }
if (!empty($course)) { $sql .= " AND course = :course"; $params['course'] = $course; }

$sql .= " ORDER BY printed_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$result = $stmt->fetchAll();

// Theme
$themeClass = (isset($_COOKIE['theme']) && $_COOKIE['theme']=='dark') ? 'dark' : '';

// Current filters for links
$currentParams = [
    'search' => $search,
    'grade'  => $grade,
    'strand' => $strand,
    'course' => $course
];

// Build filter link (clears unrelated filters)
function buildLink($params, $typeValue='', $type='') {
    if($type && $typeValue) {
        $params[$type] = $typeValue;
        if($type == 'grade')  unset($params['strand'], $params['course']);
        if($type == 'strand') unset($params['grade'], $params['course']);
        if($type == 'course') unset($params['grade'], $params['strand']);
    }
    return '?'.http_build_query($params);
}

// Filter options
$juniorGrades = ['Grade 7','Grade 8','Grade 9','Grade 10'];
$seniorStrands = ['HUMMS','ABM','STEM','GAS','ICT'];
$courses = ['BSIT','BSBA','BSHM','BEED','BSED'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Archive - ID Printing System</title>
<link rel="stylesheet" href="../style.css">
<style>
/* Your existing CSS kept as is */
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:"Segoe UI", Arial, sans-serif; background:#c9dfff; display:flex; min-height:100vh; color:#222; }
.sidebar a:hover, .sidebar a.active { background:#1f2857; }
.main { flex:1; display:flex; flex-direction:column; }
.container { flex:1; display:flex; justify-content:center; padding:20px; }
.card { background:#fff; padding:25px; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.15); overflow-x:auto; max-height:85vh; overflow-y:auto; width:100%; max-width:1200px; }
.card::-webkit-scrollbar { width:6px; }
.card::-webkit-scrollbar-thumb { background:#3549a3; border-radius:3px; }
table { width:100%; border-collapse:separate; border-spacing:0; min-width:900px; border-radius:10px; overflow:hidden; font-size:14px; }
th, td { padding:12px 15px; text-align:center; vertical-align:middle; border-bottom:1px solid #e0e6f0; }
th { background:#002b80; color:#fff; font-weight:600; position:sticky; top:0; z-index:2; text-transform:uppercase; letter-spacing:0.5px; }
tbody tr:nth-child(even) { background:#f4f6ff; }
tbody tr:nth-child(odd) { background:#ffffff; }
tbody tr:hover { background:#e6ebff; transition:0.2s; }
body.dark tbody tr:nth-child(even) { background:#1e1e1e; }
body.dark tbody tr:nth-child(odd) { background:#2a2a2a; }
body.dark tbody tr:hover { background:#333; }
body.dark .card { background: #1e1e1e; }
body.dark .topbar{background:#111; border-bottom:1px solid white; color:#fff;}
img { width:70px; height:90px; object-fit:cover; border-radius:6px; border:2px solid #ddd; }
.actions button { margin:2px; padding:6px 12px; border:none; border-radius:20px; cursor:pointer; color:white; font-size:13px; font-weight:500; box-shadow:0 2px 5px rgba(0,0,0,0.15); transition:0.2s; }
.status-btn { background:#3498db; }
.status-btn:hover { background:#217dbb; }
.restore-btn { background:#2ecc71; }
.restore-btn:hover { background:#27ae60; }
.search-box { margin-bottom:15px; display:flex; justify-content:flex-end; }
.search-box input[type="text"] { padding:8px 12px; border:1px solid #ccc; border-radius:20px; width:250px; outline:none; }
.search-box button { margin-left:8px; padding:8px 16px; border:none; border-radius:20px; background:#002b80; color:white; cursor:pointer; }
.search-box button:hover { background:#1f2857; }
.filters { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:15px; }
.dropbtn { background-color: #002b80; color: white; padding: 8px 15px; font-size: 14px; border: none; border-radius: 8px; cursor: pointer; }
.dropbtn.active { background-color:#1f2857; }
.dropdown { z-index: 100; position: relative; display: inline-block; }
.dropdown-content { display: none; position: absolute; background-color: #f9f9f9; min-width: 160px; box-shadow: 0px 4px 12px rgba(0,0,0,0.2); border-radius: 6px; z-index: 1; }
.dropdown-content a { color: black; padding: 8px 12px; text-decoration: none; display: block; font-size: 14px; border-radius: 4px; }
.dropdown-content a:hover, .dropdown-content a.active { background-color: #002b80; color: white; }
.dropdown:hover .dropdown-content { display: block; }
@media (max-width:768px) { body { flex-direction:column; } .sidebar { width:100%; flex-direction:row; overflow-x:auto; } .sidebar a { margin:3px 10px; } .card { width:95%; } table { min-width:auto; font-size:12px; } .toggle-mode{height:60px;line-height:40px;margin:10px; white-space: nowrap; } }
</style>
</head>
<body class="<?= $themeClass ?>">

<div class="sidebar">
    <div>
        <h2>ID System</h2>
        <a href="index.php">🏠 Dashboard</a>
        <a href="print-id.php">🖨️ Print</a>
        <a href="records.php">📑 Records</a>
        <a href="archive.php" class="active">📁 Archive</a>
        <a href="logout.php">📤 Logout</a>
    </div>
    <div class="toggle-mode" onclick="toggleMode()">🌙 Dark Mode</div>
</div>

<div class="main">
    <div class="topbar"><span>Archived Records</span></div>
    <div class="container">
        <div class="card">

            <?php if($msg): ?>
                <div class="message success"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>

            <!-- Filters -->
            <div class="filters">
                <div class="dropdown">
                    <button class="dropbtn <?= in_array($grade,$juniorGrades)?'active':'' ?>">Junior High</button>
                    <div class="dropdown-content">
                        <?php foreach($juniorGrades as $g): ?>
                            <a href="<?= buildLink($currentParams,$g,'grade') ?>" class="<?= $grade == $g ? 'active' : '' ?>"><?= $g ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="dropdown">
                    <button class="dropbtn <?= in_array($strand,$seniorStrands)?'active':'' ?>">Senior High</button>
                    <div class="dropdown-content">
                        <?php foreach($seniorStrands as $s): ?>
                            <a href="<?= buildLink($currentParams,$s,'strand') ?>" class="<?= $strand == $s ? 'active' : '' ?>"><?= $s ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="dropdown">
                    <button class="dropbtn <?= in_array($course,$courses)?'active':'' ?>">College</button>
                    <div class="dropdown-content">
                        <?php foreach($courses as $c): ?>
                            <a href="<?= buildLink($currentParams,$c,'course') ?>" class="<?= $course == $c ? 'active' : '' ?>"><?= $c ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="dropdown">
                    <a href="archive.php"><button class="dropbtn">Reset Filters</button></a>
                </div>
            </div>

            <!-- Search -->
            <form method="GET" class="search-box">
                <input type="text" name="search" placeholder="Search LRN, Name, ID..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit">Search</button>
            </form>

            <!-- Table -->
            <table>
                <thead>
                    <tr>
                        <th>LRN</th>
                        <th>Full Name</th>
                        <th>ID Number</th>
                        <th>Grade / Strand / Course</th>
                        <th>Address</th>
                        <th>Guardian</th>
                        <th>Contact</th>
                        <th>Photo</th>
                        <th>Printed At</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if($result): ?>
                    <?php foreach($result as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['lrn']) ?></td>
                            <td><?= htmlspecialchars($row['full_name']) ?></td>
                            <td><?= htmlspecialchars($row['id_number']) ?></td>
                            <td><?= htmlspecialchars($row['grade'] ?: $row['strand'] ?: $row['course']) ?></td>
                            <td><?= htmlspecialchars($row['home_address']) ?></td>
                            <td><?= htmlspecialchars($row['guardian_name']) ?></td>
                            <td><?= htmlspecialchars($row['guardian_contact']) ?></td>
                            <td>
                                <?php if(!empty($row['upload_photo']) && file_exists("../uploads/".$row['upload_photo'])): ?>
                                    <img src="../uploads/<?= htmlspecialchars($row['upload_photo']) ?>" alt="Photo">
                                <?php else: ?>
                                    No photo
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($row['printed_at']) ?></td>
                            <td>
                                <span class="status-badge <?= $row['status']=='Released'?'status-released':'status-pending' ?>">
                                    <?= htmlspecialchars($row['status']) ?>
                                </span>
                            </td>
                            <td class="actions">
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="toggle_status" value="<?= $row['id'] ?>">
                                    <button class="status-btn">🔁</button>
                                </form>
                                <form method="POST" style="display:inline" onsubmit="return confirm('Restore this record?')">
                                    <input type="hidden" name="restore_id" value="<?= $row['id'] ?>">
                                    <button class="restore-btn">🔄</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="11">No records found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>

        </div>
    </div>
</div>

<script src="../theme.js"></script>
</body>
</html>
