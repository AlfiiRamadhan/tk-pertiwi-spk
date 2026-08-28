<?php
session_start();
require_once "config/env.php";
require_once "config/koneksi.php";
require_once "config/auth.php";

$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    check_csrf();
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($username === "" || $password === "") {
        $error = "Username dan password wajib diisi.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user["password"])) {
            $_SESSION["user"] = [
                "id" => $user["id"],
                "nama" => $user["nama"],
                "username" => $user["username"],
                "role" => $user["role"]
            ];
            header("Location: index.php");
            exit;
        }
        $error = "Username atau password tidak sesuai.";
    }
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login | SPK TK Pertiwi</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/tk-theme.css" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/pastel-ceria.css" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/tema-modul-tk.css" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/background-tk-tanpa-pelangi.css" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/redesign-tk-pertiwi.css" rel="stylesheet">
</head>
<body class="tk-theme tk-soft-bg">
<div class="tk-login">
  <div class="tk-login-card">
    <div class="row g-0">
      <div class="col-lg-6 tk-login-side">
        <div class="tk-login-icon"><i class="fa-solid fa-children"></i></div>
        <div class="small text-uppercase mb-2" style="letter-spacing:1.5px;opacity:.8">TK Pertiwi</div>
        <h1 class="fw-bold mb-3">SPK Perkembangan Anak</h1>
        <p class="mb-4" style="opacity:.88;line-height:1.7">
          Sistem untuk membantu pengelolaan penilaian perkembangan anak secara terstruktur menggunakan metode SAW.
        </p>
        <div class="d-flex gap-2 flex-wrap">
          <span class="badge rounded-pill text-bg-light text-dark px-3 py-2">Penilaian</span>
          <span class="badge rounded-pill text-bg-light text-dark px-3 py-2">Perkembangan</span>
          <span class="badge rounded-pill text-bg-light text-dark px-3 py-2">SAW</span>
        </div>
      </div>
      <div class="col-lg-6 tk-login-form">
        <div class="tk-brand-small mb-2"><i class="fa-solid fa-school me-2"></i>Selamat Datang</div>
        <h3 class="fw-bold mb-1">Masuk ke Sistem</h3>
        <p class="text-muted mb-4"> </p>

        <?php if ($error): ?>
          <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post">
          <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
          <div class="mb-3">
            <label class="form-label fw-semibold">Username</label>
            <div class="input-group">
              <span class="input-group-text bg-white"><i class="fa-solid fa-user" style="color:#6f9298"></i></span>
              <input type="text" name="username" class="form-control tk-input" placeholder="Masukkan username" required>
            </div>
          </div>
          <div class="mb-4">
            <label class="form-label fw-semibold">Password</label>
            <div class="input-group">
              <span class="input-group-text bg-white"><i class="fa-solid fa-lock" style="color:#6f9298"></i></span>
              <input type="password" name="password" class="form-control tk-input" placeholder="Masukkan password" required>
            </div>
          </div>
          <button class="btn tk-btn w-100"><i class="fa-solid fa-arrow-right-to-bracket me-2"></i>Masuk</button>
        </form>
        <div class="tk-footer-note text-center">Sistem Pendukung Keputusan • TK Pertiwi</div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
