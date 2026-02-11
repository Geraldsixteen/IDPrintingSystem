<?php
// Load .env variables correctly
if (file_exists(__DIR__.'/.env')) {
    foreach (file(__DIR__.'/.env') as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;

        // Split into key=value
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $value = trim($parts[1]);
            putenv("$key=$value");
        }
    }
}

// Now read environment variables
$host = getenv("DB_HOST");
$port = getenv("DB_PORT");
$db   = getenv("DB_NAME");
$user = getenv("DB_USER");
$pass = getenv("DB_PASS");

// Optional: debug to check if variables are read correctly
// echo "$host, $port, $db, $user\n";

$dsn = "pgsql:DB_HOST=$host;DB_PORT=$port;DB_NAME=$db";

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
?>
