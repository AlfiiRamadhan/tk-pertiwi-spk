<?php
/**
 * db_import.php — Script PHP untuk import database.sql
 * Dijalankan oleh start.sh saat startup Railway
 */

$host = getenv('DB_HOST') ?: (getenv('MYSQLHOST') ?: '');
$port = getenv('DB_PORT') ?: (getenv('MYSQLPORT') ?: '3306');
$name = getenv('DB_NAME') ?: (getenv('MYSQLDATABASE') ?: 'railway');
$user = getenv('DB_USER') ?: (getenv('MYSQLUSER') ?: 'root');
$pass = getenv('DB_PASS') ?: (getenv('MYSQLPASSWORD') ?: '');

// Coba juga dari MYSQL_URL jika tersedia
$mysql_url = getenv('MYSQL_URL') ?: '';
if (empty($host) && !empty($mysql_url)) {
    $parsed = parse_url($mysql_url);
    $host = $parsed['host'] ?? '';
    $port = $parsed['port'] ?? 3306;
    $name = ltrim($parsed['path'] ?? '/railway', '/');
    $user = $parsed['user'] ?? 'root';
    $pass = $parsed['pass'] ?? '';
}

echo "=== DB Import Script ===\n";
echo "Host : $host\n";
echo "Port : $port\n";
echo "DB   : $name\n";
echo "User : $user\n";
echo "Pass : " . ($pass ? '(set)' : '(kosong)') . "\n\n";

if (empty($host)) {
    echo "ERROR: DB_HOST tidak di-set! Cek environment variables.\n";
    exit(0); // Jangan exit 1 agar PHP server tetap jalan
}

// Coba koneksi max 30x
$connected = false;
for ($i = 1; $i <= 30; $i++) {
    try {
        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        echo "Database terhubung! (percobaan ke-$i)\n";
        $connected = true;
        break;
    } catch (PDOException $e) {
        echo "Mencoba konek... ($i/30): " . $e->getMessage() . "\n";
        sleep(2);
    }
}

if (!$connected) {
    echo "PERINGATAN: Tidak bisa konek ke database setelah 30 percobaan.\n";
    echo "PHP server tetap dijalankan.\n";
    exit(0);
}

// Cek apakah tabel users sudah ada
$tableExists = $pdo->query("SELECT COUNT(*) FROM information_schema.tables 
    WHERE table_schema='$name' AND table_name='users'")->fetchColumn();

if ($tableExists > 0) {
    echo "Database sudah ada — skip import.\n";
    exit(0);
}

// Import database.sql
$sqlFile = __DIR__ . '/database.sql';
if (!file_exists($sqlFile)) {
    echo "ERROR: database.sql tidak ditemukan!\n";
    exit(0);
}

echo "Database kosong — mengimpor database.sql...\n";
$sql = file_get_contents($sqlFile);

// Hapus CREATE DATABASE dan USE (Railway sudah sediakan DB)
$sql = preg_replace('/^CREATE DATABASE.*?;\s*/mi', '', $sql);
$sql = preg_replace('/^USE\s+\S+;\s*/mi', '', $sql);

// Jalankan per statement
$statements = array_filter(array_map('trim', explode(';', $sql)));
$ok = 0;
foreach ($statements as $stmt) {
    if (empty($stmt)) continue;
    try {
        $pdo->exec($stmt);
        $ok++;
    } catch (PDOException $e) {
        echo "  Skip: " . $e->getMessage() . "\n";
    }
}

echo "Import selesai! $ok statement berhasil.\n";
echo "Tabel: " . implode(', ', $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN)) . "\n";
