<?php

// Load .env
$envFile = __DIR__ . '/../.env';

if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        putenv($line);
    }
}

// ENV variables
$host = getenv('DB_HOST');
$port = getenv('DB_PORT');
$db   = getenv('DB_NAME');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');

// Stop if ENV not loaded
if (!$host || !$port || !$db || !$user) {
    die("❌ ENV NOT LOADED");
}

// IMPORTANT: sslmode=require for Render
$dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=require";

try {

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    echo "✅ Connected to Render PostgreSQL";

} catch (PDOException $e) {

    die("❌ PostgreSQL connection failed: " . $e->getMessage());
}
