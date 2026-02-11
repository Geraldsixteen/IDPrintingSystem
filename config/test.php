<?php 

// Read environment variables correctly
$host = 'localhost';
$port = '5432';
$db   = 'id-printing-db';
$user = 'postgres';
$pass = 'idprintingsystem';
// DSN string for PostgreSQL
$dsn = "pgsql:host=$host;port=$port;dbname=$db";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    // echo "Connected to PostgreSQL successfully!";
} catch (PDOException $e) {
    die("PostgreSQL connection failed: " . $e->getMessage());
}
