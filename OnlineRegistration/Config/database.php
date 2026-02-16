<?php
date_default_timezone_set('Asia/Manila');

// Use environment variables if available, fallback to Render database
$host = getenv('DB_HOST');
$port = getenv('DB_PORT');
$db   = getenv('DB_NAME');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');

// DSN string for PostgreSQL
$dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=require";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    // echo "Connected to PostgreSQL successfully!";
    $pdo->exec("SET TIME ZONE 'Asia/Manila'");
} catch (PDOException $e) {
    die("PostgreSQL connection failed: " . $e->getMessage());
}
