<?php

// Always load .env from htdocs root
$envFile = realpath($_SERVER['DOCUMENT_ROOT'] . '/.env');

if (!$envFile) {
    die("❌ Shared .env not found");
}

foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (strpos(trim($line), '#') === 0) continue;
    putenv($line);
}

// Get variables
$host = getenv('DB_HOST');
$port = getenv('DB_PORT');
$db   = getenv('DB_NAME');
$user = getenv('DB_USER');
$pass = getenv('DB_PASSWORD');   // ← FIXED NAME

if (!$host) {
    die("❌ ENV NOT LOADED (ADMIN)");
}

// Render PostgreSQL requires SSL
$dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=require";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

} catch (PDOException $e) {
    die("PostgreSQL connection failed: " . $e->getMessage());
}
