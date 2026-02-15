<?php 
//require_once __DIR__ . '/admin-auth.php';
require_once __DIR__ . '/../Config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$themeClass = (isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark') ? 'dark' : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard - ID Printing System</title>
<link rel="stylesheet" href="../style.css">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:"Segoe UI", Arial, sans-serif; background:#c9dfff; display:flex; min-height:100vh; color:#333; transition:0.3s ease, color 0.3s ease; }
.sidebar a:hover, .sidebar a.active { background:#1f2857; }
.main { flex:1; display:flex; flex-direction:column; }
.container { padding:20px; flex:1; display:flex; justify-content:center; align-items:flex-start; }

.toggle-mode{
margin:15px;
padding:10px;
text-align:center;
background:white;
color:#3549a3;
border-radius:30px;
cursor:pointer;
font-weight:600;
}

/* TOPBAR */
.topbar{
background:white;
padding:15px;
font-weight:600;
border-bottom:1px solid #ccc;
text-align:center;
}

/* CARD */
.card{
background:white;
padding:25px;
border-radius:12px;
box-shadow:0 4px 12px rgba(0,0,0,.15);
text-align:center;
margin:15px;
}

.card h3{
margin-bottom:10px;
color:#3549a3;
}

.card p{
margin-bottom:20px;
color:#555;
}

/* BUTTON */
.btn{
padding:10px 20px;
border-radius:20px;
background:#3549a3;
color:white;
font-weight:600;
text-decoration:none;
display:inline-block;
transition:.3s;
}

.btn:hover{background:#2d3a80}

/* DARK MODE */
body.dark{background:#111;color:#eee}

body.dark .sidebar{background:#111}

body.dark .sidebar a:hover,
body.dark .sidebar a.active{background:#222}

body.dark .topbar{background:#111;color:white;border-bottom:1px solid #333}

body.dark .card{background:#2c2c2c}

body.dark .card p{color:#ccc}

body.dark .btn{background:#3549a3}

body.dark .toggle-mode{background:#333;color:white}

/* MOBILE */
@media(max-width:768px){
body{flex-direction:column;}

.sidebar{
width:100%;
flex-direction:row;
overflow-x:auto;
}

.sidebar a{white-space:nowrap}

.toggle-mode{height:60px;line-height:40px;margin:10px;
 white-space: nowrap; border-radius:20px;}
}
</style>
</head>

<body class="<?= $themeClass ?>">

<!-- SIDEBAR -->
<div class="sidebar">
    <div>
        <h2>ID System</h2>
        <a href="index.php" class="active">🏠 Dashboard</a>
        <a href="records.php">📑 Records</a>
        <a href="archive.php">📁 Archive</a>
        <a href="logout.php">📤 Logout</a>
    </div>
    <div class="toggle-mode" onclick="toggleMode()">🌙 Dark Mode</div>
</div>

<!-- MAIN -->
<div class="main">

<div class="topbar">
<img src="../cdlb.png" style="max-width:120px;display:block;margin:0 auto;">
<br>
Dashboard
</div>

<div class="container">

<div class="card">
<h3>Manage Records</h3>
<p>View and update all registered ID records.</p>
<a href="records.php" class="btn">View Records</a>
</div>

<div class="card">
<h3>View Archive</h3>
<p>Check previously archived ID records.</p>
<a href="archive.php" class="btn">Go to Archive</a>
</div>

</div>
</div>

<script src="../theme.js"></script>

</body>
</html>
