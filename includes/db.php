<?php
// includes/db.php
declare(strict_types=1);

// Read env (Dotenv populates $_ENV); fallback to getenv()
$env = static function(string $key, ?string $default = null): ?string {
    /* A value explicitly set in .env WINS even when it is an empty
       string - otherwise an intentionally empty DB_PASS (passwordless
       local MySQL) would silently fall through to the coded default
       and produce an access-denied at connect time. getenv() is only
       consulted when the key is absent from .env entirely. */
    if (array_key_exists($key, $_ENV)) {
        return (string) $_ENV[$key];
    }
    $v = getenv($key);
    return ($v === false || $v === null || $v === '') ? $default : (string) $v;
};

$host   = $env('DB_HOST', '127.0.0.1');
$port   = (string)($env('DB_PORT', '3306'));
$name   = $env('DB_NAME', 'ecwid-googlesearchconsole');
$user   = $env('DB_USER', 'root');
$pass   = $env('DB_PASS', 'Makkpress@123AA');
$socket = $env('DB_SOCKET', '');
$debug  = ($env('APP_DEBUG', '0') === '1');

// Force TCP if someone set localhost
if ($host === 'localhost') {
    $host = '127.0.0.1';
}

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // If DB_SOCKET is provided and the file exists, use it.
    // Otherwise, fall back to TCP connection.
    if ($socket !== '' && is_file($socket)) {
        // Use UNIX socket explicitly
        $dsn = "mysql:unix_socket={$socket};dbname={$name};charset=utf8mb4";
    } else {
        // Use TCP
        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
    }

    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (Throwable $e) {
    http_response_code(500);

    // If this script is used in API context, return JSON. Otherwise, plain text.
    $isJson = (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
           || (isset($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'application/json'));

    $msg = $debug
        ? ('Database connection failed: ' . $e->getMessage())
        : 'Database connection failed.';

    if ($isJson) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => $msg], JSON_UNESCAPED_SLASHES);
    } else {
        echo $msg;
    }
    exit;
}

