<?php
date_default_timezone_set('Asia/Manila');

// Use environment variables if available, fallback to Render database
$host = getenv('DB_HOST') ?: 'dpg-d5vnr7e3jp1c73cc3bag-a.oregon-postgres.render.com';
$port = getenv('DB_PORT') ?: '5432';
$db   = getenv('DB_NAME') ?: 'id_printing_db';
$user = getenv('DB_USER') ?: 'auto';
$pass = getenv('DB_PASS') ?: '5NpiRI7p6ZF6xTUN2U4blfnoN5BDIzRJ';

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
