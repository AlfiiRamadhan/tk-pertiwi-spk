<?php
// Baca konfigurasi database dari environment variables
// Di Railway: set DB_HOST, DB_NAME, DB_USER, DB_PASS pada Railway environment variables
// Di lokal XAMPP: nilai fallback di bawah akan digunakan
$host = getenv('DB_HOST') !== false ? getenv('DB_HOST') : 'localhost';
$db   = getenv('DB_NAME') !== false ? getenv('DB_NAME') : 'db_tk_pertiwi';
$user = getenv('DB_USER') !== false ? getenv('DB_USER') : 'root';
$pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';

// Port MySQL (Railway sering menggunakan port custom)
$port = getenv('DB_PORT') !== false ? (int)getenv('DB_PORT') : 3306;

try {
    $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    // Di production, jangan tampilkan detail error ke user
    $env = getenv('APP_ENV') ?: 'local';
    if ($env === 'production') {
        die("Koneksi database gagal. Silakan hubungi administrator.");
    }
    die("Koneksi database gagal: " . htmlspecialchars($e->getMessage()));
}
?>
