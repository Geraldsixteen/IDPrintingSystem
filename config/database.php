<?php 
// Load .env variables
if (file_exists(__DIR__.'/.env')) {
    foreach (file(__DIR__.'/.env') as $line) {
        if (trim($line) === '' || strpos($line, '#') === 0) continue;
        putenv(trim($line));
    }
}

// Read environment variables correctly
$host = getenv("DB_HOST");
$port = getenv("DB_PORT");
$db   = getenv("DB_NAME");
$user = getenv("DB_USER");
$pass = getenv("DB_PASS");

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
