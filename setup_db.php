<?php
/**
 * setup_db.php — Script satu kali untuk import database ke Railway MySQL
 * PENTING: Hapus file ini setelah database berhasil diimport!
 */

// Kunci keamanan — ganti sebelum upload jika mau lebih aman
define('SETUP_KEY', 'tkpertiwi2024');

$key = $_GET['key'] ?? '';
if ($key !== SETUP_KEY) {
    http_response_code(403);
    die("<h2>403 Forbidden</h2><p>Akses ditolak. Tambahkan <code>?key=tkpertiwi2024</code> di URL.</p>");
}

// Baca env vars
$host = getenv('DB_HOST') ?: (getenv('MYSQLHOST') ?: 'localhost');
$port = getenv('DB_PORT') ?: (getenv('MYSQLPORT') ?: 3306);
$name = getenv('DB_NAME') ?: (getenv('MYSQLDATABASE') ?: 'db_tk_pertiwi');
$user = getenv('DB_USER') ?: (getenv('MYSQLUSER') ?: 'root');
$pass = getenv('DB_PASS') ?: (getenv('MYSQLPASSWORD') ?: '');

$action = $_POST['action'] ?? '';

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Setup Database — SPK TK Pertiwi</title>
<style>
body{font-family:monospace;background:#1e1e2e;color:#cdd6f4;padding:30px;max-width:800px;margin:0 auto}
h1{color:#cba6f7}h2{color:#89b4fa}
.box{background:#181825;border:1px solid #313244;border-radius:8px;padding:20px;margin:15px 0}
.success{color:#a6e3a1}.error{color:#f38ba8}.warn{color:#f9e2af}.info{color:#89dceb}
pre{background:#11111b;padding:15px;border-radius:6px;overflow-x:auto;font-size:13px}
button{background:#cba6f7;color:#1e1e2e;border:none;padding:12px 24px;border-radius:6px;cursor:pointer;font-size:15px;font-weight:bold}
button:hover{background:#b4befe}
table{width:100%;border-collapse:collapse}td,th{padding:8px 12px;border:1px solid #313244;text-align:left}
th{background:#181825;color:#89b4fa}
</style>
</head>
<body>
<h1>🗄️ Setup Database — SPK TK Pertiwi</h1>

<div class="box">
<h2>Konfigurasi Database</h2>
<table>
<tr><th>Parameter</th><th>Nilai</th><th>Status</th></tr>
<tr><td>Host</td><td><?= htmlspecialchars($host) ?></td><td><?= $host !== 'localhost' ? '<span class="success">✓</span>' : '<span class="warn">⚠ default localhost</span>' ?></td></tr>
<tr><td>Port</td><td><?= htmlspecialchars($port) ?></td><td>-</td></tr>
<tr><td>Database</td><td><?= htmlspecialchars($name) ?></td><td>-</td></tr>
<tr><td>User</td><td><?= htmlspecialchars($user) ?></td><td>-</td></tr>
<tr><td>Password</td><td><?= $pass ? '(set)' : '<span class="error">KOSONG!</span>' ?></td><td>-</td></tr>
</table>
</div>

<?php
// Test koneksi
try {
    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo '<div class="box"><span class="success">✅ Koneksi database BERHASIL!</span></div>';
    $connected = true;
} catch (PDOException $e) {
    echo '<div class="box"><span class="error">❌ Koneksi GAGAL: ' . htmlspecialchars($e->getMessage()) . '</span></div>';
    $connected = false;
}

if ($connected) {
    // Cek tabel yang sudah ada
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo '<div class="box"><h2>Tabel di Database</h2>';
    if (empty($tables)) {
        echo '<span class="warn">⚠ Database masih kosong — belum ada tabel</span>';
    } else {
        echo '<span class="success">✓ Tabel ditemukan: ' . implode(', ', $tables) . '</span>';
    }
    echo '</div>';

    // Proses import jika di-submit
    if ($action === 'import') {
        $sqlFile = __DIR__ . '/database.sql';
        if (!file_exists($sqlFile)) {
            echo '<div class="box"><span class="error">❌ File database.sql tidak ditemukan!</span></div>';
        } else {
            $sql = file_get_contents($sqlFile);
            // Hapus baris CREATE DATABASE dan USE (sudah punya DB dari Railway)
            $sql = preg_replace('/^CREATE DATABASE.*?;/mi', '', $sql);
            $sql = preg_replace('/^USE\s+\S+;/mi', '', $sql);

            // Jalankan per statement
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            $success = 0; $errors = [];
            foreach ($statements as $stmt) {
                if (empty($stmt)) continue;
                try {
                    $pdo->exec($stmt);
                    $success++;
                } catch (PDOException $e) {
                    $errors[] = htmlspecialchars($e->getMessage());
                }
            }
            echo '<div class="box">';
            echo '<span class="success">✅ Import selesai! ' . $success . ' statement berhasil.</span><br>';
            if (!empty($errors)) {
                echo '<br><span class="warn">⚠ Beberapa error (mungkin tabel sudah ada):</span><ul>';
                foreach ($errors as $err) echo '<li class="error">' . $err . '</li>';
                echo '</ul>';
            }
            echo '<br><br><span class="info">👉 Silakan cek tabel di bawah dan <strong>hapus file setup_db.php</strong> dari server!</span>';
            echo '</div>';

            // Tampilkan tabel setelah import
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            echo '<div class="box"><span class="success">Tabel setelah import: ' . implode(', ', $tables) . '</span></div>';
        }
    }
}
?>

<?php if ($connected && $action !== 'import'): ?>
<div class="box">
<h2>Import database.sql</h2>
<p class="warn">⚠ Ini akan mengimport semua tabel dan data awal dari <code>database.sql</code></p>
<form method="post">
<input type="hidden" name="action" value="import">
<button type="submit">🚀 Import Database Sekarang</button>
</form>
</div>
<?php endif; ?>

<div class="box">
<p class="error">⚠️ <strong>PENTING:</strong> Hapus file <code>setup_db.php</code> setelah database berhasil diimport!</p>
</div>

</body>
</html>
