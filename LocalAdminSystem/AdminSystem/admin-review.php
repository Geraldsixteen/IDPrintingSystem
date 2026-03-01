<?php
require_once __DIR__ . '/admin-auth.php';
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/photo-helper.php';

// Handle approve/reject actions
if(isset($_GET['action'], $_GET['id'])){
    $id = intval($_GET['id']);
    $action = $_GET['action'];
    if(in_array($action,['approve','reject'])){
        if($action === 'approve'){
            // Generate ID number only now
            $currentYear = date('y');
            $prefix = 'S'.$currentYear.'-';
            $stmtId = $pdo->prepare("SELECT id_number FROM register WHERE id_number LIKE :prefix ORDER BY id DESC LIMIT 1 FOR UPDATE");
            $stmtId->execute([':prefix'=>$prefix.'%']);
            $lastId = $stmtId->fetchColumn();
            $num = $lastId ? intval(explode('-', $lastId)[1]) + 1 : 1;
            $id_number = $prefix . str_pad($num, 4, '0', STR_PAD_LEFT);

            $stmt = $pdo->prepare("UPDATE register SET status='approved', id_number=:id_number WHERE id=:id");
            $stmt->execute([':id_number'=>$id_number, ':id'=>$id]);
        } else {
            // If rejected, delete the record
            $stmt = $pdo->prepare("DELETE FROM register WHERE id=:id");
            $stmt->execute([':id'=>$id]);
        }

        header("Location: admin-review.php?msg=success");
        exit;
    }
}

// ================= GET FILTERS =================
$search = $_GET['search'] ?? '';
$grade  = $_GET['grade'] ?? '';
$strand = $_GET['strand'] ?? '';
$course = $_GET['course'] ?? '';

// ================= FILTER OPTIONS =================
$juniorGrades = ['Grade 7','Grade 8','Grade 9','Grade 10'];
$seniorStrands = ['HUMMS','ABM','STEM','GAS','ICT'];
$courses = ['BSBA','BSE','BEE','BSCS','BAE'];

// ================= BUILD SQL =================
$sql = "SELECT * FROM register WHERE status='pending'";
$params = [];

if ($search) {
    $sql .= " AND (lrn LIKE :search OR full_name LIKE :search OR email LIKE :search)";
    $params[':search'] = "%$search%";
}
if ($grade) {
    $sql .= " AND grade = :grade";
    $params[':grade'] = $grade;
}
if ($strand) {
    $sql .= " AND strand = :strand";
    $params[':strand'] = $strand;
}
if ($course) {
    $sql .= " AND course = :course";
    $params[':course'] = $course;
}

$sql .= " ORDER BY created_at ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

$themeClass = ($_COOKIE['theme'] ?? '') === 'dark' ? 'dark' : '';

function buildLink($params, $value='', $type='') {
    if ($type && $value) {
        $params[$type] = $value;
        if ($type === 'grade')  unset($params['strand'], $params['course']);
        if ($type === 'strand') unset($params['grade'], $params['course']);
        if ($type === 'course') unset($params['grade'], $params['strand']);
    }
    return '?'.http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Review - Pending Students</title>
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
.approve-btn { background:#2ecc71; }
.reject-btn { background:#e74c3c; }
.filters { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:15px; }
.dropbtn { background-color: #002b80; color: white; padding: 8px 15px; font-size: 14px; border: none; border-radius: 8px; cursor: pointer; }
.dropbtn.active { background-color:#1f2857; }
.dropdown { z-index: 100; position: relative; display: inline-block; }
.dropdown-content { display: none; position: absolute; background-color: #f9f9f9; min-width: 160px; box-shadow: 0px 4px 12px rgba(0,0,0,0.2); border-radius: 6px; z-index: 1; }
.dropdown-content a { color: black; padding: 8px 12px; text-decoration: none; display: block; font-size: 14px; border-radius: 4px; }
.dropdown-content a:hover, .dropdown-content a.active { background-color: #002b80; color: white; }
.dropdown:hover .dropdown-content { display: block; }
.search-box { margin-bottom: 15px; display: flex; justify-content: flex-end; }
.search-box input[type="text"] { padding: 8px 12px; border: 1px solid #ccc; border-radius: 20px; width: 250px; outline: none; }
.search-box button { margin-left: 8px; padding: 8px 16px; border: none; border-radius: 20px; background: #002b80; color: white; cursor: pointer; }
.search-box button:hover { background: #1f2857; }
.success-msg{
    background:#2ecc71;
    color:white;
    padding:12px;
    border-radius:8px;
    margin-bottom:15px;
    text-align:center;
    font-weight:600;
    animation:fadeout 3s forwards;
}
@keyframes fadeout{
    0%{opacity:1;}
    70%{opacity:1;}
    100%{opacity:0;}
}
</style>
</head>
<body class="<?= $themeClass ?>">

<div class="sidebar">
    <div>
        <h2>ID System</h2>
        <a href="index.php">🏠 Dashboard</a>
        <a href="admin-review.php" class="active">📝 Review</a>
        <a href="records.php">📑 Records</a>
        <a href="archive.php">📁 Archive</a>
        <a href="logout.php" onclick="return confirm('Are you sure you want to logout?')">📤 Logout</a>
    </div>
    <div class="toggle-mode" onclick="toggleMode()">🌙 Dark Mode</div>
</div>

<div class="main">
    <div class="topbar"><span>Pending Registrations</span></div>
    <div class="container">
        <div class="card">
            <?php if(isset($_GET['msg'])): ?>
                <div class="success-msg">✅ Action completed successfully!</div>
            <?php endif; ?>

            <!-- Filters -->
            <div class="filters">
                <div class="dropdown">
                    <button class="dropbtn <?= in_array($grade,$juniorGrades)?'active':'' ?>" data-type="grade">Junior High</button>
                    <div class="dropdown-content">
                        <?php foreach($juniorGrades as $g): ?>
                            <a href="<?= buildLink($_GET, $g, 'grade') ?>" class="<?= $grade == $g ? 'active' : '' ?>"><?= $g ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="dropdown">
                    <button class="dropbtn <?= in_array($strand,$seniorStrands)?'active':'' ?>" data-type="strand">Senior High</button>
                    <div class="dropdown-content">
                        <?php foreach($seniorStrands as $s): ?>
                            <a href="<?= buildLink($_GET, $s, 'strand') ?>" class="<?= $strand == $s ? 'active' : '' ?>"><?= $s ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="dropdown">
                    <button class="dropbtn <?= in_array($course,$courses)?'active':'' ?>" data-type="course">College</button>
                    <div class="dropdown-content">
                        <?php foreach($courses as $c): ?>
                            <a href="<?= buildLink($_GET, $c, 'course') ?>" class="<?= $course == $c ? 'active' : '' ?>"><?= $c ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="dropdown">
                    <a href="admin-review.php"><button class="dropbtn">Reset Filters</button></a>
                </div>
            </div>

            <!-- Search -->
            <form method="GET" class="search-box">
                <input type="text" name="search" placeholder="Search LRN, Name, Email..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit">Search</button>
            </form>

            <!-- Table -->
            <table>
                <thead>
                    <tr>
                        <th>LRN</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Grade / Strand / Course</th>
                        <th>Address</th>
                        <th>Guardian</th>
                        <th>Contact</th>
                        <th>Photo</th>
                        <th>Registered At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($students): ?>
                        <?php foreach($students as $s): ?>
                        <tr>
                            <td><?= htmlspecialchars($s['lrn']) ?></td>
                            <td><?= htmlspecialchars($s['full_name']) ?></td>
                            <td><?= htmlspecialchars($s['email']) ?></td>
                            <td><?= htmlspecialchars(!empty($s['grade'])?$s['grade']:(!empty($s['strand'])?$s['strand']:$s['course'])) ?></td>
                            <td><?= htmlspecialchars($s['home_address']) ?></td>
                            <td><?= htmlspecialchars($s['guardian_name']) ?></td>
                            <td><?= htmlspecialchars($s['guardian_contact']) ?></td>
                            <td>
                                <img src="<?= getStudentPhotoUrl($s) ?>" alt="Photo">
                            </td>
                            <td><?= $s['created_at'] ? date('Y-m-d H:i:s', strtotime($s['created_at'])) : '-' ?></td>
                            <td class="actions">
                                <a href="?id=<?= $s['id'] ?>&action=approve"><button class="approve-btn">✅ Approve</button></a>
                                <a href="?id=<?= $s['id'] ?>&action=reject"><button class="reject-btn">❌ Reject</button></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="10" class="no-record">No pending registrations</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

        </div>
    </div>
</div>

<script src="../theme.js"></script>
</body>
</html>