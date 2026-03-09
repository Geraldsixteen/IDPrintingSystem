<?php
date_default_timezone_set('Asia/Manila');

// Environment variables for PostgreSQL
$host = getenv('DB_HOST');
$port = getenv('DB_PORT') ?: 5432;
$db   = getenv('DB_NAME');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');

// DSN string for PostgreSQL
$dsn = "pgsql:host=$host;port=$port;dbname=$db";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    $pdo->exec("SET TIME ZONE 'Asia/Manila'");
} catch (PDOException $e) {
    die("PostgreSQL connection failed: " . $e->getMessage());
}