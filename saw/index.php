<?php
require_once "../config/koneksi.php";
require_once "../config/auth.php";
require_role(['admin','guru']);
require_once "../config/functions.php";

$title = "Proses SAW";

$namaBulan = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

$kriteria = $pdo->query("SELECT * FROM kriteria ORDER BY kode")->fetchAll();
$totalBobot = array_sum(array_column($kriteria, 'bobot'));
$siap = count($kriteria) === 6 && abs($totalBobot - 1) < 0.00001;

// Filter bulan & tahun
$selectedBulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : (int)date('n');
$selectedTahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');

// Tahun tersedia
$tahunList = $pdo->query("SELECT DISTINCT tahun FROM penilaian ORDER BY tahun DESC")->fetchAll(PDO::FETCH_COLUMN);
$tahunSekarang = (int)date('Y');
if (!in_array($tahunSekarang, $tahunList)) $tahunList[] = $tahunSekarang;
sort($tahunList);

$jalankan = $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'jalankan';
if ($jalankan) {
    check_csrf();
    $selectedBulan = (int)($_POST['bulan'] ?? $selectedBulan);
    $selectedTahun = (int)($_POST['tahun'] ?? $selectedTahun);
}

// Kelompokkan anak per kelas (kelas tanpa anggota dilewati)
$grupAnak = [];
foreach ($pdo->query("SELECT id, nama FROM kelas ORDER BY nama")->fetchAll() as $kl) {
    $stmt = $pdo->prepare("SELECT id, nis, nama FROM anak WHERE kelas_id = ? ORDER BY nama");
    $stmt->execute([$kl['id']]);
    $anakKelas = $stmt->fetchAll();
    if ($anakKelas) {
        $grupAnak[] = ['nama' => $kl['nama'], 'anak' => $anakKelas];
    }
}
$tanpaKelas = $pdo->query("SELECT id, nis, nama FROM anak WHERE kelas_id IS NULL ORDER BY nama")->fetchAll();
if ($tanpaKelas) {
    $grupAnak[] = ['nama' => 'Tanpa Kelas', 'anak' => $tanpaKelas];
}
$jumlahSemuaAnak = 0;
foreach ($grupAnak as $g) {
    $jumlahSemuaAnak += count($g['anak']);
}

$grupHasil = [];

if ($siap) {
    foreach ($grupAnak as $grp) {
        $ids = array_column($grp['anak'], 'id');
        $in = implode(',', array_fill(0, count($ids), '?'));

        $nilaiMap = [];
        $stmt = $pdo->prepare("SELECT anak_id, kriteria_id, nilai FROM penilaian WHERE anak_id IN ($in) AND bulan = ? AND tahun = ?");
        $stmt->execute(array_merge($ids, [$selectedBulan, $selectedTahun]));
        foreach ($stmt as $d) {
            $nilaiMap[(int)$d['anak_id']][(int)$d['kriteria_id']] = (float)$d['nilai'];
        }

        // Nilai ekstrem per kriteria hanya dari anak dalam kelas ini
        $ekstrem = [];
        $stmt = $pdo->prepare("SELECT kriteria_id, MAX(nilai) AS e_max, MIN(nilai) AS e_min FROM penilaian WHERE anak_id IN ($in) AND bulan = ? AND tahun = ? GROUP BY kriteria_id");
        $stmt->execute(array_merge($ids, [$selectedBulan, $selectedTahun]));
        foreach ($stmt as $ex) {
            $ekstrem[(int)$ex['kriteria_id']] = [
                'max' => (float)$ex['e_max'],
                'min' => (float)$ex['e_min']
            ];
        }

        $matrix = [];
        $normalisasi = [];
        $hasil = [];

        foreach ($grp['anak'] as $a) {
            $matrix[$a['id']] = [];
            $normalisasi[$a['id']] = [];
            $score = 0;
            $lengkap = true;

            foreach ($kriteria as $k) {
                $v = $nilaiMap[$a['id']][$k['id']] ?? null;
                $matrix[$a['id']][$k['id']] = $v;

                if ($v === null) {
                    $lengkap = false;
                    $normalisasi[$a['id']][$k['id']] = null;
                    continue;
                }

                $max = $ekstrem[(int)$k['id']]['max'] ?? 0;
                $min = $ekstrem[(int)$k['id']]['min'] ?? 0;

                $r = ($k['atribut'] === 'cost')
                    ? (($v > 0 && $min > 0) ? $min / $v : 0)
                    : (($max > 0) ? $v / $max : 0);

                $normalisasi[$a['id']][$k['id']] = $r;
                $score += $r * (float)$k['bobot'];
            }

            $hasil[] = ['anak' => $a, 'score' => $lengkap ? $score : null];
        }

        usort($hasil, function($a, $b) {
            return ($b['score'] ?? -1) <=> ($a['score'] ?? -1);
        });

        // Ranking dimulai dari 1 di dalam kelas masing-masing
        $rank = 1;
        foreach ($hasil as &$rw) {
            if ($rw['score'] === null) continue;
            $rw['rank'] = $rank++;
        }
        unset($rw);

        $grupHasil[] = [
            'nama' => $grp['nama'],
            'anak' => $grp['anak'],
            'matrix' => $matrix,
            'normalisasi' => $normalisasi,
            'hasil' => $hasil
        ];
    }

    if ($jalankan) {
        $pdo->beginTransaction();
        try {
            $stmtProses = $pdo->prepare("INSERT INTO proses_saw(user_id,jumlah_anak,total_bobot,bulan,tahun,keterangan) VALUES(?,?,?,?,?,?)");
            $stmtProses->execute([
                $_SESSION['user']['id'] ?? null,
                $jumlahSemuaAnak,
                $totalBobot,
                $selectedBulan,
                $selectedTahun,
                'Perhitungan SAW per kelas penilaian perkembangan anak'
            ]);
            $prosesId = (int)$pdo->lastInsertId();

            $stmtDetail = $pdo->prepare("INSERT INTO detail_saw(proses_id,anak_id,kriteria_id,nilai_awal,nilai_normalisasi,bobot,nilai_terbobot) VALUES(?,?,?,?,?,?,?)");
            foreach ($grupHasil as $g) {
                foreach ($g['hasil'] as $r) {
                    foreach ($kriteria as $k) {
                        $awal = $g['matrix'][$r['anak']['id']][$k['id']];
                        $norm = $g['normalisasi'][$r['anak']['id']][$k['id']];
                        $terbobot = $norm === null ? null : $norm * (float)$k['bobot'];
                        $stmtDetail->execute([$prosesId,$r['anak']['id'],$k['id'],$awal,$norm,$k['bobot'],$terbobot]);
                    }
                }
            }

            $stmtHasil = $pdo->prepare("INSERT INTO hasil_saw(proses_id,anak_id,nilai_preferensi,ranking) VALUES(?,?,?,?)");
            foreach ($grupHasil as $g) {
                foreach ($g['hasil'] as $r) {
                    if (!isset($r['rank'])) continue;
                    $stmtHasil->execute([$prosesId,$r['anak']['id'],(float)$r['score'],$r['rank']]);
                }
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}

include "../layout/header.php";
?>

<style>
.saw-step{border-left:4px solid #d9a07e}
.saw-table thead th{background:#edf4f4!important;color:#496b78!important;border-color:#dce6e5!important}
.saw-table tbody tr:nth-child(even){background:#fbfcfb}
.saw-code{display:inline-block;font-weight:700;color:#5d7f8b}
.saw-kriteria{font-size:11px;color:#7c8b91;line-height:1.25}
.saw-result{background:#f4f8f7;border:1px solid #dce8e6}
</style>
<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div>
        <div class="section-title">Proses Perhitungan SAW Per Kelas</div>
    </div>
    <?php if ($siap): ?>
        <form method="get" class="row g-2 align-items-end mb-3">
            <div class="col-auto">
                <label class="form-label small text-muted mb-1">Bulan</label>
                <select name="bulan" class="form-select form-select-sm">
                    <?php foreach($namaBulan as $num=>$nama): ?>
                        <option value="<?=$num?>" <?=$selectedBulan==$num?'selected':''?>><?=$nama?></option>
                    <?php endforeach;?>
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label small text-muted mb-1">Tahun</label>
                <select name="tahun" class="form-select form-select-sm">
                    <?php foreach($tahunList as $th): ?>
                        <option value="<?=$th?>" <?=$selectedTahun==(int)$th?'selected':''?>><?=$th?></option>
                    <?php endforeach;?>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-filter me-1"></i>Filter</button>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php if ($siap): ?>
<div class="mb-3">
    <span class="badge rounded-pill px-3 py-2" style="background:#d9a07e;font-size:13px">
        <i class="fa-solid fa-calendar me-2"></i>Periode: <?= e($namaBulan[$selectedBulan].' '.$selectedTahun) ?>
    </span>
</div>
<?php endif; ?>

<?php if ($siap && $jalankan): ?>
    <form method="post" class="m-0 mb-4">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="aksi" value="jalankan">
        <input type="hidden" name="bulan" value="<?=$selectedBulan?>">
        <input type="hidden" name="tahun" value="<?=$selectedTahun?>">
        <button class="btn btn-primary px-4">
            <i class="fa-solid fa-play me-2"></i>Jalankan Proses SAW — <?= e($namaBulan[$selectedBulan].' '.$selectedTahun) ?>
        </button>
    </form>
<?php elseif ($siap && !$jalankan): ?>
    <form method="post" class="m-0 mb-4">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="aksi" value="jalankan">
        <input type="hidden" name="bulan" value="<?=$selectedBulan?>">
        <input type="hidden" name="tahun" value="<?=$selectedTahun?>">
        <button class="btn btn-primary px-4">
            <i class="fa-solid fa-play me-2"></i>Jalankan Proses SAW — <?= e($namaBulan[$selectedBulan].' '.$selectedTahun) ?>
        </button>
    </form>
<?php endif; ?>

<?php if (count($kriteria) !== 6): ?>
    <div class="alert alert-danger">Sistem membutuhkan tepat 6 kriteria C1 sampai C6. Saat ini terdapat <b><?= count($kriteria) ?></b> kriteria.</div>
<?php elseif (abs($totalBobot - 1) > 0.00001): ?>
    <div class="alert alert-warning">Total bobot saat ini <b><?= number_format($totalBobot,2) ?></b>. Total bobot harus tepat <b>1,00</b>.</div>
<?php elseif (!$jalankan): ?>
    <div class="card mb-4 saw-step">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-1">Perhitungan Siap Dilakukan</h5>
            <div class="row g-3">
                <?php foreach ($kriteria as $k): ?>
                    <div class="col-6 col-md-4 col-xl-2">
                        <div class="border rounded-3 p-3 h-100" style="background:#f8fafc">
                            <div class="fw-bold" style="color:#5d7f8b"><?= e($k['kode']) ?> <span class="text-muted fw-normal">• <?= ucfirst(e($k['atribut'])) ?></span></div>
                            <div class="small mt-1"><?= e($k['nama']) ?></div>
                            <div class="small text-muted mt-2">Bobot <?= number_format($k['bobot'],2) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php else: ?>
<div class="alert alert-success"><i class="fa-solid fa-database me-2"></i>Hasil proses SAW berhasil dihitung dan disimpan ke database. <a href="riwayat.php" class="alert-link">Lihat Riwayat Hasil</a>.</div>

<?php if (!$grupHasil): ?>
    <div class="alert alert-info">Belum ada data anak yang dapat diproses.</div>
<?php else: ?>
<?php foreach ($grupHasil as $g): ?>

<div class="d-flex flex-wrap align-items-center gap-2 mt-4 mb-3">
    <span class="badge rounded-pill px-3 py-2" style="background:#5d7f8b;font-size:13px">
        <i class="fa-solid fa-users-rectangle me-2"></i><?= e($g['nama']) ?> • <?= count($g['anak']) ?> anak
    </span>
</div>

<!-- LANGKAH 1 -->
<div class="card mb-4 saw-step">
    <div class="card-body p-3 p-md-4">
        <div class="mb-3">
            <h5 class="fw-bold mb-1">Langkah 1: Matriks Keputusan (X) — <?= e($g['nama']) ?></h5>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-sm align-middle mb-0 saw-table">
                <thead>
                    <tr>
                        <th style="min-width:180px">Anak</th>
                        <?php foreach ($kriteria as $k): ?>
                            <th class="text-center" style="min-width:145px">
                                <?= e($k['kode']) ?> - <?= e($k['nama']) ?>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($g['anak'] as $a): ?>
                    <tr>
                        <td><b><?= e($a['nama']) ?></b><div class="small text-muted"><?= e($a['nis']) ?></div></td>
                        <?php foreach ($kriteria as $k): $v=$g['matrix'][$a['id']][$k['id']]; ?>
                            <td class="text-center"><?= $v === null ? '<span class="text-muted">-</span>' : (int)$v ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- LANGKAH 2 -->
<div class="card mb-4 saw-step">
    <div class="card-body p-3 p-md-4">
        <div class="mb-3">
            <h5 class="fw-bold mb-1">Langkah 2: Matriks Normalisasi (R) — <?= e($g['nama']) ?></h5>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-sm align-middle mb-0 saw-table">
                <thead>
                    <tr>
                        <th style="min-width:180px">Anak</th>
                        <?php foreach ($kriteria as $k): ?>
                            <th class="text-center" style="min-width:155px">
                                <?= e($k['kode']) ?> - <?= e($k['nama']) ?>
                                <div class="small fw-normal mt-1"><?= ucfirst(e($k['atribut'])) ?> • Bobot <?= number_format($k['bobot'],2) ?></div>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($g['anak'] as $a): ?>
                    <tr>
                        <td><b><?= e($a['nama']) ?></b><div class="small text-muted"><?= e($a['nis']) ?></div></td>
                        <?php foreach ($kriteria as $k): $r=$g['normalisasi'][$a['id']][$k['id']]; ?>
                            <td class="text-center"><?= $r === null ? '<span class="text-muted">-</span>' : number_format($r,4) ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- LANGKAH 3-4 -->
<div class="card mb-4 saw-step">
    <div class="card-body p-3 p-md-4">
        <div class="mb-3">
            <h5 class="fw-bold mb-1">Langkah 3–4: Hasil Perangkingan Akhir — <?= e($g['nama']) ?></h5>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-sm align-middle mb-0 saw-table">
                <thead>
                    <tr>
                        <th class="text-center" style="width:80px">Ranking</th>
                        <th style="min-width:180px">Nama Anak</th>
                        <?php foreach ($kriteria as $k): ?>
                            <th class="text-center" style="min-width:145px"><?= e($k['kode']) ?> - <?= e($k['nama']) ?></th>
                        <?php endforeach; ?>
                        <th class="text-center" style="min-width:145px">Nilai Preferensi (V)</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($g['hasil'] as $r): $score=$r['score']; ?>
                    <tr class="<?= (isset($r['rank']) && $r['rank'] === 1) ? 'table-primary' : '' ?>">
                        <td class="text-center"><?= $r['rank'] ?? '-' ?></td>
                        <td><b><?= e($r['anak']['nama']) ?></b></td>
                        <?php foreach ($kriteria as $k): $rval=$g['normalisasi'][$r['anak']['id']][$k['id']]; ?>
                            <td class="text-center"><?= $rval === null ? '-' : number_format($rval,4) ?></td>
                        <?php endforeach; ?>
                        <td class="text-center fw-bold"><?= $score === null ? '-' : number_format($score,6) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        $lengkap=array_values(array_filter($g['hasil'],fn($x)=>$x['score']!==null));
        if($lengkap): $terbaik=$lengkap[0]; ?>
            <div class="mt-3 p-3 rounded-3" style="background:#edf4f4;border:1px solid #d6e5e5">
                <i class="fa-solid fa-trophy me-2" style="color:#d9a07e"></i>
                <b>Hasil tertinggi <?= e($g['nama']) ?>:</b> <?= e($terbaik['anak']['nama']) ?> dengan nilai preferensi <b><?= number_format($terbaik['score'],6) ?></b>.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php endforeach; ?>
<?php endif; ?>
<?php endif; ?>

<?php include "../layout/footer.php"; ?>
