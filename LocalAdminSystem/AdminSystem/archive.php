<?php 
//require_once __DIR__ . '/admin-auth.php';
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/photo-helper.php';

// GET filters
$search = $_GET['search'] ?? '';
$grade  = $_GET['grade'] ?? '';
$strand = $_GET['strand'] ?? '';
$course = $_GET['course'] ?? '';

// ================== Theme ==================
$themeClass = ($_COOKIE['theme'] ?? '') === 'dark' ? 'dark' : '';

// ================== Build SQL with Filters ==================
$sql = "SELECT * FROM archive WHERE 1=1";
$params = [];

// Search
if ($search !== '') {
    $sql .= " AND (lrn LIKE :search OR full_name LIKE :search OR id_number LIKE :search)";
    $params[':search'] = "%$search%";
}

// Grade/Strand/Course filters
if ($grade !== '')  { $sql .= " AND grade = :grade";   $params[':grade'] = $grade; }
if ($strand !== '') { $sql .= " AND strand = :strand"; $params[':strand'] = $strand; }
if ($course !== '') { $sql .= " AND course = :course"; $params[':course'] = $course; }

$sql .= " ORDER BY printed_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ================== Filter Links ==================
$currentParams = compact('search', 'grade', 'strand', 'course');

function buildLink($params, $value='', $type='') {
    if ($type && $value) {
        $params[$type] = $value;
        if ($type === 'grade')  unset($params['strand'], $params['course']);
        if ($type === 'strand') unset($params['grade'], $params['course']);
        if ($type === 'course') unset($params['grade'], $params['strand']);
    }
    return '?'.http_build_query($params);
}

// ================== Filter Options ==================
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
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:"Segoe UI",Arial,sans-serif;background:#c9dfff;display:flex;min-height:100vh;color:#222;}
.sidebar a:hover,.sidebar a.active{background:#1f2857;}
.main{flex:1;display:flex;flex-direction:column;}
.container{flex:1;display:flex;justify-content:center;padding:20px;}
.card{background:#fff;padding:25px;border-radius:10px;box-shadow:0 4px 12px rgba(0,0,0,0.15);overflow-x:auto;max-height:85vh;overflow-y:auto;width:100%;max-width:1200px;}
.card::-webkit-scrollbar{width:6px;}
.card::-webkit-scrollbar-thumb{background:#3549a3;border-radius:3px;}
table{width:100%;border-collapse:separate;border-spacing:0;min-width:600px;border-radius:10px;overflow:hidden;font-size:14px;}
th,td{padding:12px 15px;text-align:center;vertical-align:middle;}
th{background:#002b80;color:#fff;font-weight:600;position:sticky;top:0;z-index:2;text-transform:uppercase;letter-spacing:0.5px;}
tbody tr:nth-child(even){background:#f4f6ff;}
tbody tr:nth-child(odd){background:#fff;}
tbody tr:hover{background:#e6ebff;transition:0.2s;}
td{border-bottom:1px solid #e0e6f0;}
body.dark tbody tr:nth-child(even){background:#1e1e1e;}
body.dark tbody tr:nth-child(odd){background:#2a2a2a;}
body.dark tbody tr:hover{background:#333;}
body.dark .card{background:#1e1e1e;}
body.dark .topbar{background:#111;border-bottom:1px solid white;color:#fff;}
img{width:70px;height:90px;object-fit:cover;border-radius:6px;border:2px solid #ddd;}
.actions button{margin:2px;padding:6px 12px;border:none;border-radius:20px;cursor:pointer;color:white;font-size:13px;font-weight:500;box-shadow:0 2px 5px rgba(0,0,0,0.15);transition:0.2s;}
.status-btn{background:#3498db;}
.status-btn:hover{background:#217dbb;}
.restore-btn{background:#2ecc71;}
.restore-btn:hover{background:#27ae60;}
.status-badge{padding:5px 10px;border-radius:20px;color:white;font-weight:600;font-size:12px;}
.status-released{background:#28a745;}
.status-pending{background:#dc3545;display:inline-block;}
.search-box{margin-bottom:15px;display:flex;justify-content:flex-end;}
.search-box input[type="text"]{padding:8px 12px;border:1px solid #ccc;border-radius:20px;width:250px;outline:none;}
.search-box button{margin-left:8px;padding:8px 16px;border:none;border-radius:20px;background:#002b80;color:white;cursor:pointer;}
.search-box button:hover{background:#1f2857;}
.filters{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:15px;}
.dropbtn{background-color:#002b80;color:white;padding:8px 15px;font-size:14px;border:none;border-radius:8px;cursor:pointer;}
.dropbtn.active{background-color:#1f2857;}
.dropdown{z-index:100;position:relative;display:inline-block;}
.dropdown-content{display:none;position:absolute;background-color:#f9f9f9;min-width:160px;box-shadow:0px 4px 12px rgba(0,0,0,0.2);border-radius:6px;z-index:1;}
.dropdown-content a{color:black;padding:8px 12px;text-decoration:none;display:block;font-size:14px;border-radius:4px;}
.dropdown-content a:hover,.dropdown-content a.active{background-color:#002b80;color:white;}
.dropdown:hover .dropdown-content{display:block;}
@media(max-width:768px){body{flex-direction:column;}.sidebar{width:100%;flex-direction:row;overflow-x:auto;}.sidebar a{margin:3px 10px;}.card{width:95%;}table{min-width:auto;font-size:12px;}.toggle-mode{height:60px;line-height:40px;margin:10px;white-space:nowrap;}}

/* Your existing CSS plus checkbox & batch buttons */
.actions button{margin:2px;padding:6px 12px;border:none;border-radius:20px;cursor:pointer;color:white;font-size:13px;font-weight:500;box-shadow:0 2px 5px rgba(0,0,0,0.15);transition:0.2s;}
.status-btn{background:#3498db;}
.status-btn:hover{background:#217dbb;}
.restore-btn{background:#2ecc71;}
.restore-btn:hover{background:#27ae60;}
.batch-btn{background:#e67e22;}
.batch-btn:hover{background:#d35400;}
.status-badge{padding:5px 10px;border-radius:20px;color:white;font-weight:600;font-size:12px;}
.status-released{background:#28a745;}
.status-pending{background:#dc3545;display:inline-block;}
.checkbox-col{width:30px;}
</style>
</head>
<body class="<?= $themeClass ?>">

<div class="sidebar">
    <div>
        <h2>ID System</h2>
        <a href="index.php">🏠 Dashboard</a>
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

            <!-- Filters -->
            <div class="filters">
                <!-- Grade / Strand / Course Filters (same as before) -->
                <div class="dropdown">
                    <button class="dropbtn <?= in_array($grade,$juniorGrades)?'active':'' ?>">Junior High</button>
                    <div class="dropdown-content">
                        <?php foreach($juniorGrades as $g): ?>
                            <a href="<?= buildLink($currentParams,$g,'grade') ?>" class="<?= $grade==$g?'active':'' ?>"><?= $g ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="dropdown">
                    <button class="dropbtn <?= in_array($strand,$seniorStrands)?'active':'' ?>">Senior High</button>
                    <div class="dropdown-content">
                        <?php foreach($seniorStrands as $s): ?>
                            <a href="<?= buildLink($currentParams,$s,'strand') ?>" class="<?= $strand==$s?'active':'' ?>"><?= $s ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="dropdown">
                    <button class="dropbtn <?= in_array($course,$courses)?'active':'' ?>">College</button>
                    <div class="dropdown-content">
                        <?php foreach($courses as $c): ?>
                            <a href="<?= buildLink($currentParams,$c,'course') ?>" class="<?= $course==$c?'active':'' ?>"><?= $c ?></a>
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

            <!-- Batch Action Buttons -->
            <div style="margin-bottom:10px;">
                <button class="batch-btn" id="restoreSelected">🔄 Restore Selected</button>
                <button class="batch-btn" id="toggleSelected">🔁 Toggle Status Selected</button>
                <button class="batch-btn" id="printSelected">🖨️ Print Selected</button>
            </div>

            <!-- Table -->
            <table>
                <thead>
                    <tr>
                        <th class="checkbox-col"><input type="checkbox" id="selectAll"></th>
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
                        <th>Released At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($result): ?>
                        <?php foreach($result as $row): ?>
                        <tr>
                            <td><input type="checkbox" class="rowCheckbox" value="<?= $row['id'] ?>"></td>
                            <td><?= htmlspecialchars($row['lrn']) ?></td>
                            <td><?= htmlspecialchars($row['full_name']) ?></td>
                            <td><?= htmlspecialchars($row['id_number']) ?></td>
                            <td><?= htmlspecialchars($row['grade'] ?: $row['strand'] ?: $row['course']) ?></td>
                            <td><?= htmlspecialchars($row['home_address']) ?></td>
                            <td><?= htmlspecialchars($row['guardian_name']) ?></td>
                            <td><?= htmlspecialchars($row['guardian_contact']) ?></td>
                            <td><?= displayPhoto($row['photo'] ?? null, $row['photo_blob'] ?? null) ?></td>
                            <td><?= $row['printed_at'] ? date('Y-m-d H:i:s', strtotime($row['printed_at'])) : '-' ?></td>
                            <td><span class="status-badge <?= $row['status']=='Released'?'status-released':'status-pending' ?>"><?= htmlspecialchars($row['status']) ?></span></td>
                            <td><?= $row['released_at'] ? date('Y-m-d H:i:s', strtotime($row['released_at'])) : '-' ?></td>
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
                        <tr><td colspan="13">No records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

        </div>
    </div>
</div>

<script src="../theme.js"></script>
<script>
// Select All
document.getElementById('selectAll').addEventListener('change', function(){
    const checked = this.checked;
    document.querySelectorAll('.rowCheckbox').forEach(cb => cb.checked = checked);
});

// Batch Restore
document.getElementById('restoreSelected').addEventListener('click', function(){
    const ids = Array.from(document.querySelectorAll('.rowCheckbox:checked')).map(cb=>cb.value);
    if(ids.length===0){ alert('Select at least one student'); return; }
    if(!confirm('Restore selected records?')) return;

    fetch('batch-archive.php', {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({action:'restore', ids})
    })
    .then(res=>res.json())
    .then(data=>{
        alert(data.msg);
        if(data.success) location.reload();
    });
});

// Batch Toggle Status
document.getElementById('toggleSelected').addEventListener('click', function(){
    const ids = Array.from(document.querySelectorAll('.rowCheckbox:checked')).map(cb=>cb.value);
    if(ids.length===0){ alert('Select at least one student'); return; }

    fetch('batch-archive.php', {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({action:'toggle', ids})
    })
    .then(res=>res.json())
    .then(data=>{
        alert(data.msg);
        if(data.success) location.reload();
    });
});

// Batch Print
document.getElementById('printSelected').addEventListener('click', function(){
    const ids = Array.from(document.querySelectorAll('.rowCheckbox:checked')).map(cb=>cb.value);
    if(ids.length===0){ alert('Select at least one student'); return; }

    // Open multiple print windows
    ids.forEach(id => window.open('print.php?id='+id, '_blank'));
});
</script>
</body>
</html>
