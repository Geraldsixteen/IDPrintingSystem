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
$host = "dpg-d5vnr7e3jp1c73cc3bag-a.oregon-postgres.render.com";  // replace with your DB host
$port = "5432";  // replace with your DB port
$db   = "id_printing_db";  // replace with your DB name
$user = "auto";
$pass = "5NpiRI7p6ZF6xTUN2U4blfnoN5BDIzRJ";

// Optional: debug to check if variables are read correctly
// echo "$host, $port, $db, $user\n";

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
?>
