<?php
require_once "../config/koneksi.php";
require_once "../config/auth.php";
require_role(['admin','guru','kepala_sekolah']);
require_once "../config/functions.php";
$title = "Rekap Nilai Per Bulan";

$namaBulan = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

$kriteria = $pdo->query("SELECT * FROM kriteria ORDER BY kode")->fetchAll();

// Tahun tersedia
$tahunList = $pdo->query("SELECT DISTINCT tahun FROM penilaian ORDER BY tahun DESC")->fetchAll(PDO::FETCH_COLUMN);
$tahunSekarang = (int)date('Y');
if (!in_array($tahunSekarang, $tahunList)) $tahunList[] = $tahunSekarang;
sort($tahunList);

$kelasList = $pdo->query("SELECT id,nama FROM kelas ORDER BY nama")->fetchAll();

$selectedKelas = isset($_GET['kelas']) ? (int)$_GET['kelas'] : -1;
$selectedBulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : (int)date('n');
$selectedTahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');

// Ambil data rekap
$rekap = [];
$rataRata = [];
if ($selectedKelas > 0) {
    // Ambil semua anak di kelas ini
    $sk = $pdo->prepare("SELECT id, nis, nama FROM anak WHERE kelas_id = ? ORDER BY nama");
    $sk->execute([$selectedKelas]);
    $anakList = $sk->fetchAll();

    if ($anakList) {
        $ids = array_column($anakList, 'id');
        $in = implode(',', array_fill(0, count($ids), '?'));

        // Ambil nilai per anak per kriteria untuk bulan & tahun ini
        $stmt = $pdo->prepare("SELECT anak_id, kriteria_id, nilai FROM penilaian WHERE anak_id IN ($in) AND bulan = ? AND tahun = ?");
        $params = array_merge($ids, [$selectedBulan, $selectedTahun]);
        $stmt->execute($params);

        $nilaiMap = [];
        foreach ($stmt as $r) {
            $nilaiMap[(int)$r['anak_id']][(int)$r['kriteria_id']] = (int)$r['nilai'];
        }

        foreach ($anakList as $a) {
            $rekap[$a['id']] = [
                'nis' => $a['nis'],
                'nama' => $a['nama'],
                'nilai' => []
            ];
            foreach ($kriteria as $k) {
                $rekap[$a['id']]['nilai'][$k['id']] = $nilaiMap[$a['id']][$k['id']] ?? null;
            }
        }

        // Hitung rata-rata per kriteria
        foreach ($kriteria as $k) {
            $total = 0;
            $count = 0;
            foreach ($rekap as $a) {
                if ($a['nilai'][$k['id']] !== null) {
                    $total += $a['nilai'][$k['id']];
                    $count++;
                }
            }
            $rataRata[$k['id']] = $count > 0 ? $total / $count : null;
        }
    }
}

include "../layout/header.php";
?>

<style>
.rekap-table thead th { background: #edf4f4 !important; color: #496b78 !important; border-color: #dce6e5 !important; }
.rekap-table tbody tr:nth-child(even) { background: #fbfcfb; }
.rekap-table tbody tr:hover { background: #f0f6f6; }
.rekap-rata { background: #f4f8f7 !important; font-weight: 700; }
</style>

<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div>
        <div class="section-title">Rekap Nilai Per Bulan</div>
        <div class="text-muted small">Ringkasan nilai perkembangan anak per kelas untuk periode tertentu.</div>
    </div>
</div>

<!-- Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small text-muted mb-1">Kelas</label>
                <select name="kelas" class="form-select" required>
                    <option value="-1">-- Pilih Kelas --</option>
                    <?php foreach($kelasList as $kl): ?>
                        <option value="<?=$kl['id']?>" <?=$selectedKelas==$kl['id']?'selected':''?>><?=e($kl['nama'])?></option>
                    <?php endforeach;?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Bulan</label>
                <select name="bulan" class="form-select">
                    <?php foreach($namaBulan as $num=>$nama): ?>
                        <option value="<?=$num?>" <?=$selectedBulan==$num?'selected':''?>><?=$nama?></option>
                    <?php endforeach;?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Tahun</label>
                <select name="tahun" class="form-select">
                    <?php foreach($tahunList as $th): ?>
                        <option value="<?=$th?>" <?=$selectedTahun==(int)$th?'selected':''?>><?=$th?></option>
                    <?php endforeach;?>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100"><i class="fa-solid fa-filter me-2"></i>Tampilkan</button>
            </div>
        </form>
    </div>
</div>

<?php if ($selectedKelas < 0): ?>
    <div class="alert alert-info">Pilih kelas terlebih dahulu untuk melihat rekap nilai.</div>
<?php elseif (empty($rekap)): ?>
    <div class="alert alert-warning">Belum ada data anak atau data penilaian untuk kelas <b><?= e($namaBulan[$selectedBulan].' '.$selectedTahun) ?></b>.</div>
<?php else: ?>

<!-- Info Periode -->
<div class="mb-3">
    <span class="badge rounded-pill px-3 py-2" style="background:#5d7f8b;font-size:13px">
        <i class="fa-solid fa-calendar me-2"></i><?= e($namaBulan[$selectedBulan].' '.$selectedTahun) ?> • <?= count($rekap) ?> anak
    </span>
</div>

<!-- Tabel Rekap -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-sm align-middle mb-0 rekap-table">
                <thead>
                    <tr>
                        <th style="min-width:50px" class="text-center">No</th>
                        <th style="min-width:80px">NIS</th>
                        <th style="min-width:180px">Nama Anak</th>
                        <?php foreach($kriteria as $k): ?>
                            <th class="text-center" style="min-width:140px">
                                <?= e($k['kode']) ?>
                                <div class="small fw-normal text-muted"><?= e($k['nama']) ?></div>
                            </th>
                        <?php endforeach;?>
                    </tr>
                </thead>
                <tbody>
                    <?php $no=1; foreach($rekap as $a): ?>
                    <tr>
                        <td class="text-center"><?= $no++ ?></td>
                        <td><?= e($a['nis']) ?></td>
                        <td><b><?= e($a['nama']) ?></b></td>
                        <?php foreach($kriteria as $k): $v = $a['nilai'][$k['id']]; ?>
                            <td class="text-center">
                                <?php if ($v !== null): ?>
                                    <?= $v ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        <?php endforeach;?>
                    </tr>
                    <?php endforeach;?>
                </tbody>
                <tfoot>
                    <tr class="rekap-rata">
                        <td colspan="3" class="text-end">Rata-rata</td>
                        <?php foreach($kriteria as $k): ?>
                            <td class="text-center">
                                <?php if ($rataRata[$k['id']] !== null): ?>
                                    <?= number_format($rataRata[$k['id']], 2) ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        <?php endforeach;?>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- Keterangan -->
<div class="mt-3 p-3 rounded-3" style="background:#f8fafc;border:1px solid #e3e8e7">
    <div class="small text-muted">
        <b>Keterangan Nilai:</b>
        <span class="badge bg-danger ms-2">1 - BB</span>
        <span class="badge bg-warning text-dark ms-1">2 - MB</span>
        <span class="badge bg-info text-dark ms-1">3 - BSH</span>
        <span class="badge bg-success ms-1">4 - BSB</span>
    </div>
</div>

<?php endif; ?>

<?php include "../layout/footer.php"; ?>
