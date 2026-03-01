<?php
require_once __DIR__ . '/admin-auth.php';
require_once __DIR__ . '/../Config/database.php';

$id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("
    UPDATE register 
    SET status = 'rejected'
    WHERE id = :id
");
$stmt->execute([':id'=>$id]);

header("Location: pending.php");
exit;