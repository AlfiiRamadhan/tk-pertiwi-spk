<?php
require_once "config/env.php";
require_once "config/koneksi.php"; require_once "config/auth.php"; require_login();
$title="Dashboard";
$anak=$pdo->query("SELECT COUNT(*) FROM anak")->fetchColumn();
$kelas=$pdo->query("SELECT COUNT(*) FROM kelas")->fetchColumn();
$kriteria=$pdo->query("SELECT COUNT(*) FROM kriteria")->fetchColumn();
$penilaian=$pdo->query("SELECT COUNT(*) FROM penilaian")->fetchColumn();
include "layout/header.php";
?>
<div class="d-flex justify-content-between align-items-center mb-4">
<div><div class="section-title">Selamat Datang 👋</div><div class="text-muted">Kelola penilaian perkembangan anak TK Pertiwi dalam satu sistem.</div></div>
<?php if (has_role(['admin','guru'])): ?>
<a href="<?= BASE_URL ?>/penilaian/index.php" class="btn btn-primary"><i class="fa-solid fa-plus me-2"></i>Input Penilaian</a>
<?php else: ?>
<a href="<?= BASE_URL ?>/saw/riwayat.php" class="btn btn-primary"><i class="fa-solid fa-ranking-star me-2"></i>Lihat Hasil SAW</a>
<?php endif; ?>
</div>
<div class="card mb-4 dashboard-hero">
<div class="card-body p-4 p-md-5"><div class="row align-items-center"><div class="col-md-8"><div class="small opacity-75 mb-2">SISTEM PENDUKUNG KEPUTUSAN</div><h2 class="fw-bold">Penilaian Perkembangan Anak</h2><p class="mb-0 opacity-75">Gunakan metode SAW untuk membantu melihat hasil perkembangan berdasarkan enam aspek penilaian.</p></div><div class="col-md-4 text-center d-none d-md-block"><div style="display:inline-flex;width:145px;height:145px;border-radius:50%;align-items:center;justify-content:center;background:rgba(255,255,255,.14);box-shadow:0 10px 30px rgba(55,80,110,.10)"><i class="fa-solid fa-children" style="font-size:88px;opacity:.30"></i></div></div></div></div>
</div>
<div class="row g-3 mb-4">
<?php foreach([["Jumlah Anak",$anak,"fa-children"],["Jumlah Kelas",$kelas,"fa-school"],["Kriteria",$kriteria,"fa-list-check"],["Penilaian",$penilaian,"fa-clipboard-check"]] as $c):?>
<div class="col-sm-6 col-xl-3"><div class="card stat-card h-100"><div class="d-flex justify-content-between align-items-center"><div><div class="text-muted small mb-1"><?=e($c[0])?></div><div class="stat-value"><?=$c[1]?></div></div><div class="stat-icon"><i class="fa-solid <?=$c[2]?>"></i></div></div></div></div>
<?php endforeach;?>
</div>
<div class="row g-4">
<div class="col-lg-7"><div class="card h-100"><div class="card-body p-4"><h5 class="fw-bold mb-1">Alur Penilaian</h5><p class="text-muted small mb-4">Tahapan pengolahan data sampai hasil akhir.</p>
<div class="row g-3">
<?php $steps=[["01","Data Anak","Masukkan identitas anak."],["02","Penilaian","Beri nilai BSB / BSH / MB / BB pada C1–C6."],["03","Proses SAW","Normalisasi dan pembobotan otomatis."],["04","Laporan","Lihat ranking dan hasil perkembangan."]];foreach($steps as $s):?>
<div class="col-md-6"><div class="p-3 rounded-3" style="background:#f7f9f7"><div class="d-flex gap-3"><span class="fw-bold" style="color:#d9a07e"><?=$s[0]?></span><div><b><?=$s[1]?></b><div class="small text-muted"><?=$s[2]?></div></div></div></div></div>
<?php endforeach;?>
</div></div></div></div>
<div class="col-lg-5"><div class="card h-100"><div class="card-body p-4"><h5 class="fw-bold mb-1">Skala Perkembangan</h5><p class="text-muted small">Pedoman skala nilai perkembangan anak.</p>
<div class="d-grid gap-2"><?php foreach([[4,"BSB","Berkembang Sangat Baik (BSB)","text-bg-success"],[3,"BSH","Berkembang Sesuai Harapan (BSH)","text-bg-primary"],[2,"MB","Mulai Berkembang (MB)","text-bg-warning"],[1,"BB","Belum Berkembang (BB)","text-bg-danger"]] as $x):?><div class="d-flex align-items-center gap-3 p-2 border rounded-3"><span class="badge <?=$x[3]?>"><?=$x[0]?></span><div><b><?=$x[1]?></b><div class="small text-muted"><?=$x[2]?></div></div></div><?php endforeach;?></div>
</div></div></div>
</div>
<?php include "layout/footer.php"; ?>
