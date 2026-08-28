<?php
require_once "../config/koneksi.php";
require_once "../config/auth.php";
require_role(['admin','guru','kepala_sekolah']);
require_once "../config/functions.php";
$title = "Hasil SAW";
$namaBulan = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

$prosesTerakhir = $pdo->query("SELECT id, bulan, tahun FROM proses_saw ORDER BY id DESC LIMIT 1")->fetch();
$prosesId = (int)($prosesTerakhir['id'] ?? 0);
$hasil = [];
if ($prosesId > 0) {
    $stmt = $pdo->prepare("\n        SELECT hs.*, a.nis, a.nama, k.nama AS nama_kelas\n        FROM hasil_saw hs\n        JOIN anak a ON a.id = hs.anak_id\n        LEFT JOIN kelas k ON k.id = a.kelas_id\n        WHERE hs.proses_id = ?\n        ORDER BY COALESCE(k.nama, '~') ASC, hs.ranking ASC\n    ");
    $stmt->execute([$prosesId]);
    $hasil = $stmt->fetchAll();
}

// Kelompokkan hasil per kelas (urutan sudah dari query: nama kelas, lalu ranking)
$grupHasil = [];
foreach ($hasil as $h) {
    $namaKelas = $h['nama_kelas'] ?? 'Tanpa Kelas';
    if (!isset($grupHasil[$namaKelas])) {
        $grupHasil[$namaKelas] = [];
    }
    $grupHasil[$namaKelas][] = $h;
}

// Waksen warna bergantian agar tabel antar kelas mudah dibedakan
$palet = [
    ['border' => '#5d7f8b', 'badge' => '#5d7f8b'],
    ['border' => '#d9a07e', 'badge' => '#c98a5e'],
    ['border' => '#6f8f6a', 'badge' => '#6f8f6a'],
    ['border' => '#8b6a9e', 'badge' => '#8b6a9e'],
];

include "../layout/header.php";
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <div class="section-title">Hasil SAW</div>
        <div class="text-muted small">Hasil akhir perhitungan dan perangkingan perkembangan anak. Ranking dihitung di dalam kelas masing-masing.<?php if (!empty($prosesTerakhir['bulan']) && !empty($prosesTerakhir['tahun'])): ?> Periode: <?= e(($namaBulan[$prosesTerakhir['bulan']] ?? $prosesTerakhir['bulan']).' '.$prosesTerakhir['tahun']) ?>.<?php endif; ?></div>
    </div>
</div>

<?php if (!$hasil): ?>
<div class="alert alert-info">
    Belum ada hasil SAW. Admin atau Guru dapat menjalankan Proses SAW terlebih dahulu.
</div>
<?php else: ?>
<?php $i = 0; foreach ($grupHasil as $namaKelas => $rows): $w = $palet[$i % count($palet)]; $i++; ?>
<div class="card mb-4" style="border-left:4px solid <?= $w['border'] ?>">
    <div class="card-body p-4">
        <div class="mb-3">
            <span class="badge rounded-pill px-3 py-2" style="background:<?= $w['badge'] ?>;font-size:13px">
                <i class="fa-solid fa-users-rectangle me-2"></i><?= e($namaKelas) ?> • <?= count($rows) ?> anak
            </span>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0">
                <thead>
                    <tr>
                        <th class="text-center" style="width:90px">Ranking</th>
                        <th style="min-width:100px">NIS</th>
                        <th style="min-width:180px">Nama Anak</th>
                        <th class="text-center" style="min-width:150px">Nilai Preferensi</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $h): $utama = (int)$h['ranking'] === 1; ?>
                    <tr class="<?= $utama ? 'table-primary' : '' ?>">
                        <td class="text-center fw-bold"><?= (int)$h['ranking'] ?></td>
                        <td><?= e($h['nis']) ?></td>
                        <td><b><?= e($h['nama']) ?></b></td>
                        <td class="text-center"><?= number_format((float)$h['nilai_preferensi'], 6) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php $terbaik = $rows[0]; ?>
        <div class="mt-3 p-3 rounded-3" style="background:#edf4f4;border:1px solid #d6e5e5">
            <i class="fa-solid fa-trophy me-2" style="color:#d9a07e"></i>
            <b>Hasil tertinggi <?= e($namaKelas) ?>:</b> <?= e($terbaik['nama']) ?> dengan nilai preferensi <b><?= number_format((float)$terbaik['nilai_preferensi'], 6) ?></b>.
        </div>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php include "../layout/footer.php"; ?>
