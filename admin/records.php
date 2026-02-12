<?php         
require_once __DIR__ . '/admin-auth.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// ================== PostgreSQL Connection ==================
require_once __DIR__ . '/../config/database.php';

// ================== PHOTO HELPER ==================
require_once __DIR__ . '/photo-helper.php'; // <- Include Option 1 helper

// GET filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$grade  = isset($_GET['grade']) ? $_GET['grade'] : '';
$strand = isset($_GET['strand']) ? $_GET['strand'] : '';
$course = isset($_GET['course']) ? $_GET['course'] : '';

// Base SQL
$sql = "SELECT * FROM register WHERE 1=1";
$params = [];

// Search filter
if (!empty($search)) {
    $sql .= " AND (lrn ILIKE :search OR full_name ILIKE :search OR id_number ILIKE :search)";
    $params[':search'] = "%$search%";
}

// Grade filter
if (!empty($grade)) {
    $sql .= " AND grade = :grade";
    $params[':grade'] = $grade;
}

// Strand filter
if (!empty($strand)) {
    $sql .= " AND strand = :strand";
    $params[':strand'] = $strand;
}

// Course filter
if (!empty($course)) {
    $sql .= " AND course = :course";
    $params[':course'] = $course;
}

$sql .= " ORDER BY created_at DESC";

// Prepare statement
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$result = $stmt->fetchAll();

// AJAX request: only output table rows
if(isset($_GET['ajax'])){
    foreach($result as $row): ?>
        <tr>
            <td><?= htmlspecialchars($row['lrn']) ?></td>
            <td><?= htmlspecialchars($row['full_name']) ?></td>
            <td><?= htmlspecialchars($row['id_number']) ?></td>
            <td><?= htmlspecialchars(getLevelDisplay($row)) ?></td>
            <td><?= htmlspecialchars($row['home_address']) ?></td>
            <td><?= htmlspecialchars($row['guardian_name']) ?></td>
            <td><?= htmlspecialchars($row['guardian_contact']) ?></td>
            <td><?= displayPhoto($row['photo']) ?></td>
            <td><?= $row['created_at'] ? date('Y-m-d H:i:s', strtotime($row['created_at'])) : '-' ?></td>
            <td><?= $row['restored_at'] ? date('Y-m-d H:i:s', strtotime($row['restored_at'])) : '-' ?></td>
            <td class="actions">
                <a href="update.php?id=<?= $row['id'] ?>"><button class="edit-btn">Edit</button></a>
                <a href="print.php?id=<?= $row['id'] ?>"><button class="edit-btn" style="background:#3498db;">🖨️ Print</button></a>
            </td>
        </tr>
    <?php endforeach;
    exit;
}

// Theme
$themeClass = (isset($_COOKIE['theme']) && $_COOKIE['theme']=='dark') ? 'dark' : '';

// Current filters
$currentParams = [
    'search' => $search,
    'grade'  => $grade,
    'strand' => $strand,
    'course' => $course
];

// Build filter links
function buildLink($params, $typeValue='', $type='') {
    if ($type && $typeValue !== '') {
        $params[$type] = $typeValue;
        if ($type == 'grade') {
            unset($params['strand'], $params['course']);
        } else if ($type == 'strand') {
            unset($params['grade'], $params['course']);
        } else if ($type == 'course') {
            unset($params['grade'], $params['strand']);
        }
    }
    return '?' . http_build_query($params);
}

function getLevelDisplay($row) {
    if (!empty($row['grade'])) return $row['grade'];
    if (!empty($row['strand'])) return $row['strand'];
    if (!empty($row['course'])) return $row['course'];
    return '';
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
<title>Manage Records - ID Printing System</title>
<link rel="stylesheet" href="../style.css">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:"Segoe UI", Arial, sans-serif; background:#c9dfff; display:flex; min-height:100vh; color:#222; }
.sidebar a:hover, .sidebar a.active { background:#1f2857; }
.main { flex:1; display:flex; flex-direction:column; }
.container { flex:1; display:flex; justify-content:center; padding:20px; }
.card { background:#fff; padding:25px; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.15); overflow-x:auto; max-height:85vh; overflow-y:auto; width:100%; max-width:1200px; }
.card::-webkit-scrollbar { width:6px; }
.card::-webkit-scrollbar-thumb { background:#3549a3; border-radius:3px; }
table { width:100%; border-collapse:separate; border-spacing:0; min-width:900px; border-radius:10px; overflow:hidden; font-size:14px; }
th, td { padding:12px 15px; text-align:center; vertical-align:middle; }
th { background:#002b80; color:#fff; font-weight:600; position:sticky; top:0; z-index:2; text-transform:uppercase; letter-spacing:0.5px; }
tbody tr:nth-child(even) { background:#f4f6ff; }
tbody tr:nth-child(odd) { background:#ffffff; }
tbody tr:hover { background:#e6ebff; transition:0.2s; }
td { border-bottom:1px solid #e0e6f0; }
body.dark tbody tr:nth-child(even) { background:#1e1e1e; }
body.dark tbody tr:nth-child(odd) { background:#2a2a2a; }
body.dark tbody tr:hover { background:#333; }
body.dark .card { background: #1e1e1e; }
body.dark .topbar{background:#111; color:#fff; border-bottom:1px solid white;}
img { width:70px; height:90px; object-fit:cover; border-radius:6px; border:2px solid #ddd; }
.actions a button { margin:2px; padding:6px 12px; border:none; border-radius:20px; cursor:pointer; color:white; font-size:13px; font-weight:500; box-shadow:0 2px 5px rgba(0,0,0,0.15); transition:0.2s; }
.edit-btn { background:#f39c12; }
.edit-btn:hover { background:#d9820f; }
.search-box { margin-bottom: 15px; display: flex; justify-content: flex-end; }
.search-box input[type="text"] { padding: 8px 12px; border: 1px solid #ccc; border-radius: 20px; width: 250px; outline: none; }
.search-box button { margin-left: 8px; padding: 8px 16px; border: none; border-radius: 20px; background: #002b80; color: white; cursor: pointer; }
.search-box button:hover { background: #1f2857; }
.filters { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:15px; }
.dropbtn { background-color: #002b80; color: white; padding: 8px 15px; font-size: 14px; border: none; border-radius: 8px; cursor: pointer; }
.dropbtn.active { background-color:#1f2857; }
.dropdown { z-index: 100; position: relative; display: inline-block; }
.dropdown-content { display: none; position: absolute; background-color: #f9f9f9; min-width: 160px; box-shadow: 0px 4px 12px rgba(0,0,0,0.2); border-radius: 6px; z-index: 1; }
.dropdown-content a { color: black; padding: 8px 12px; text-decoration: none; display: block; font-size: 14px; border-radius: 4px; }
.dropdown-content a:hover, .dropdown-content a.active { background-color: #002b80; color: white; }
.dropdown:hover .dropdown-content { display: block; }
@media (max-width:768px) { body { flex-direction:column; } .sidebar { width:100%; flex-direction:row; overflow-x:auto; } .sidebar a { margin:3px 10px; } .card { width:95%; } table { min-width:auto; font-size:12px; } .toggle-mode{height:60px;line-height:40px;margin:10px; white-space: nowrap;} }
</style>
</head>
<body class="<?= $themeClass ?>">

<div class="sidebar">
    <div>
        <h2>ID System</h2>
        <a href="index.php">🏠 Dashboard</a>
        <a href="records.php" class="active">📑 Records</a>
        <a href="archive.php">📁 Archive</a>
        <a href="logout.php">📤 Logout</a>
    </div>
    <div class="toggle-mode" onclick="toggleMode()">🌙 Dark Mode</div>
</div>

<div class="main">
    <div class="topbar"><span>Manage Records</span></div>
    <div class="container">
        <div class="card">
            <!-- Filters and Search -->
            <div class="filters">
                <!-- Junior High -->
                <div class="dropdown">
                    <button class="dropbtn <?= in_array($grade,$juniorGrades)?'active':'' ?>">Junior High</button>
                    <div class="dropdown-content">
                        <?php foreach($juniorGrades as $g): ?>
                            <a href="<?= buildLink($currentParams,$g,'grade') ?>" class="<?= $grade == $g ? 'active' : '' ?>"><?= $g ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <!-- Senior High -->
                <div class="dropdown">
                    <button class="dropbtn <?= in_array($strand,$seniorStrands)?'active':'' ?>">Senior High</button>
                    <div class="dropdown-content">
                        <?php foreach($seniorStrands as $s): ?>
                            <a href="<?= buildLink($currentParams,$s,'strand') ?>" class="<?= $strand == $s ? 'active' : '' ?>"><?= $s ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <!-- College -->
                <div class="dropdown">
                    <button class="dropbtn <?= in_array($course,$courses)?'active':'' ?>">College</button>
                    <div class="dropdown-content">
                        <?php foreach($courses as $c): ?>
                            <a href="<?= buildLink($currentParams,$c,'course') ?>" class="<?= $course == $c ? 'active' : '' ?>"><?= $c ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <!-- Reset -->
                <div class="dropdown">
                    <a href="records.php"><button class="dropbtn">Reset Filters</button></a>
                </div>
            </div>

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
                        <th>Registered At</th>
                        <th>Restored At</th>
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
                            <td><?= htmlspecialchars(getLevelDisplay($row)) ?></td>
                            <td><?= htmlspecialchars($row['home_address']) ?></td>
                            <td><?= htmlspecialchars($row['guardian_name']) ?></td>
                            <td><?= htmlspecialchars($row['guardian_contact']) ?></td>
                            <td>
                                <?= displayPhoto($row['photo']) ?>
                            </td>
                            <td><?= $row['created_at'] ? date('Y-m-d H:i:s', strtotime($row['created_at'])) : '-' ?></td>
                            <td><?= $row['restored_at'] ? date('Y-m-d H:i:s', strtotime($row['restored_at'])) : '-' ?></td>
                            <td class="actions">
                                <a href="update.php?id=<?= $row['id'] ?>"><button class="edit-btn">Edit</button></a>
                                <a href="print.php?id=<?= $row['id'] ?>"><button class="edit-btn" style="background:#3498db;">🖨️ Print</button></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="11" class="no-record">No records found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="../theme.js"></script>

<!-- Live auto-refresh table every 3 seconds -->
<script>
function fetchRecords() {
    fetch('records.php?ajax=1<?= $search ? "&search=" . urlencode($search) : "" ?><?= $grade ? "&grade=" . urlencode($grade) : "" ?><?= $strand ? "&strand=" . urlencode($strand) : "" ?><?= $course ? "&course=" . urlencode($course) : "" ?>')
    .then(res => res.text())
    .then(html => {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const tbody = doc.querySelector('tbody');
        if(tbody){
            document.querySelector('tbody').innerHTML = tbody.innerHTML;
        }
    })
    .catch(err => console.error(err));
}

// Refresh every 3 seconds
setInterval(fetchRecords, 3000);
</script>

</body>
</html>
