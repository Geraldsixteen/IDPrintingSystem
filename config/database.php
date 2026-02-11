<?php
// Get DB credentials
$host = "dpg-d5vnr7e3jp1c73cc3bag-a.oregon-postgres.render.com";
$port = "5432";
$db   = "id_printing_db";
$user = "auto";
$pass = "5NpiRI7p6ZF6xTUN2U4blfnoN5BDIzRJ";

// DSN
$dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=require";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    // echo "Connected successfully!";
} catch (PDOException $e) {
    die("PostgreSQL connection failed: " . $e->getMessage());
}
