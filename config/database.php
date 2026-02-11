<?php
<<<<<<< HEAD
// Load environment variables from .env
$envFile = __DIR__ . '../.env';
=======
// Load .env from parent folder
$envFile = __DIR__ . '/../.env';
>>>>>>> 0ca43ff8251e713858ae919e5ec3b5d529ab92c6
if (file_exists($envFile)) {
    foreach (file($envFile) as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
<<<<<<< HEAD
=======

>>>>>>> 0ca43ff8251e713858ae919e5ec3b5d529ab92c6
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            putenv(trim($parts[0]) . '=' . trim($parts[1]));
        }
    }
}

<<<<<<< HEAD
// Get variables
$host = getenv('DB_HOST');
$port = getenv('DB_PORT');
$db   = getenv('DB_NAME');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');

// DSN string for PostgreSQL
=======
// Now read environment variables
$host = getenv("DB_HOST");
$port = getenv("DB_PORT");
$db   = getenv("DB_NAME");
$user = getenv("DB_USER");
$pass = getenv("DB_PASS");

>>>>>>> 0ca43ff8251e713858ae919e5ec3b5d529ab92c6
$dsn = "pgsql:host=$host;port=$port;dbname=$db";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
<<<<<<< HEAD
    // echo "Connected to PostgreSQL successfully!";
=======
    // echo "Connected successfully!";
>>>>>>> 0ca43ff8251e713858ae919e5ec3b5d529ab92c6
} catch (PDOException $e) {
    die("PostgreSQL connection failed: " . $e->getMessage());
}
?>
