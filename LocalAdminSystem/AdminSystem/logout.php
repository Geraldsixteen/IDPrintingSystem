<?php
session_start();
require_once __DIR__ . '/../Config/database.php';

// Destroy session
$_SESSION = [];
session_destroy();

// Redirect to login
header("Location: login.php");
exit;
