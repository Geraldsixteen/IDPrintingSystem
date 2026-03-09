<?php
date_default_timezone_set('Asia/Manila');

// Environment variables (for hosting)
$host = getenv('DB_HOST');
$port = getenv('DB_PORT');
$db   = getenv('DB_NAME');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');

// If not set (local development)
if (!$host) {
    $host = "postgres.railway.internal";
    $port = "5432";
    $db   = "railway";
    $user = "postgres";
    $pass = "YyVzOGHYPIKIIStLogTRBImXpiOTkPEZ";
}

$dsn = "pgsql:host=$host;port=$port;dbname=$db";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    $pdo->exec("SET TIME ZONE 'Asia/Manila'");
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}