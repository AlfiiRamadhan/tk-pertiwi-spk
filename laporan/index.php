<?php
require_once "../config/koneksi.php";
require_once "../config/auth.php";
require_role(['admin','guru','kepala_sekolah']);
require_once "../config/functions.php";

$title = "Laporan";

$namaBulan = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

$anak_list = $pdo->query("SELECT * FROM anak ORDER BY nama")->fetchAll();
$kriteria = $pdo->query("SELECT * FROM kriteria ORDER BY kode")->fetchAll();
$kelasList = $pdo->query("SELECT id,nama FROM kelas ORDER BY nama")->fetchAll();

// Tahun tersedia
$tahunList = $pdo->query("SELECT DISTINCT tahun FROM penilaian ORDER BY tahun DESC")->fetchAll(PDO::FETCH_COLUMN);
$tahunSekarang = (int)date('Y');
if (!in_array($tahunSekarang, $tahunList)) $tahunList[] = $tahunSekarang;
sort($tahunList);

// Periode laporan digunakan untuk laporan per anak maupun per kelas
$bulan_laporan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : (int)date('n');
$tahun_laporan = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');
if ($bulan_laporan < 1 || $bulan_laporan > 12) $bulan_laporan = (int)date('n');
if ($tahun_laporan < 2000 || $tahun_laporan > 2100) $tahun_laporan = (int)date('Y');

// ========== LAPORAN PER ANAK ==========
$id_anak = isset($_GET['anak_id']) ? (int)$_GET['anak_id'] : 0;
$anak = null;
$detail = [];
$nilai_akhir = null;
$pesan = '';

if ($id_anak > 0) {
    $stmt = $pdo->prepare("SELECT a.*, k.nama AS nama_kelas FROM anak a LEFT JOIN kelas k ON a.kelas_id = k.id WHERE a.id = ?");
    $stmt->execute([$id_anak]);
    $anak = $stmt->fetch();

    if (!$anak) {
        $pesan = "Data anak tidak ditemukan.";
    } elseif (count($kriteria) === 0) {
        $pesan = "Data kriteria belum tersedia.";
    } else {
        $total_bobot = array_sum(array_column($kriteria, 'bobot'));
        if (abs($total_bobot - 1) >= 0.00001) {
            $pesan = "Total bobot kriteria harus 1,00. Total saat ini " . number_format($total_bobot, 2) . ".";
        } else {
            $stmt = $pdo->prepare("SELECT kriteria_id, nilai FROM penilaian WHERE anak_id = ? AND bulan = ? AND tahun = ?");
            $stmt->execute([$id_anak, $bulan_laporan, $tahun_laporan]);
            $nilai = [];
            foreach ($stmt->fetchAll() as $p) {
                $nilai[$p['kriteria_id']] = (float)$p['nilai'];
            }

            $lengkap = true;
            foreach ($kriteria as $k) {
                $nilai_asli = $nilai[$k['id']] ?? null;
                if ($nilai_asli === null) $lengkap = false;

                $extStmt = $pdo->prepare("SELECT MAX(p.nilai) AS e_max, MIN(p.nilai) AS e_min FROM penilaian p JOIN anak a ON a.id = p.anak_id WHERE p.kriteria_id = ? AND a.kelas_id <=> ? AND p.bulan = ? AND p.tahun = ?");
                $extStmt->execute([$k['id'], $anak['kelas_id'], $bulan_laporan, $tahun_laporan]);
                $ext = $extStmt->fetch();
                $maksimum = (float)($ext['e_max'] ?? 0);
                $minimum = (float)($ext['e_min'] ?? 0);

                if ($nilai_asli !== null) {
                    $normalisasi = ($k['atribut'] === 'cost')
                        ? (($nilai_asli > 0 && $minimum > 0) ? $minimum / $nilai_asli : 0)
                        : (($maksimum > 0) ? $nilai_asli / $maksimum : 0);
                } else {
                    $normalisasi = 0;
                }

                $kontribusi = $normalisasi * (float)$k['bobot'];
                $detail[] = [
                    'kode' => $k['kode'], 'nama' => $k['nama'], 'bobot' => (float)$k['bobot'],
                    'nilai' => $nilai_asli, 'maksimum' => $maksimum, 'normalisasi' => $normalisasi, 'kontribusi' => $kontribusi
                ];
            }

            if ($lengkap) {
                $nilai_akhir = array_sum(array_column($detail, 'kontribusi'));
            }
        }
    }
}

// ========== LAPORAN PER KELAS ==========
$mode_laporan = $_GET['mode'] ?? 'anak';
$kelas_id = isset($_GET['kelas_id']) ? (int)$_GET['kelas_id'] : -1;

$hasilKelas = [];
$namaKelas = '';
if ($mode_laporan === 'kelas' && $kelas_id > 0 && count($kriteria) === 6) {
    $total_bobot = array_sum(array_column($kriteria, 'bobot'));
    if (abs($total_bobot - 1) < 0.00001) {
        $sk = $pdo->prepare("SELECT id, nis, nama FROM anak WHERE kelas_id = ? ORDER BY nama");
        $sk->execute([$kelas_id]);
        $anakKelas = $sk->fetchAll();

        $ck = $pdo->prepare("SELECT nama FROM kelas WHERE id = ?");
        $ck->execute([$kelas_id]);
        $namaKelas = $ck->fetchColumn() ?? '';

        if ($anakKelas) {
            $ids = array_column($anakKelas, 'id');
            $in = implode(',', array_fill(0, count($ids), '?'));

            $stmt = $pdo->prepare("SELECT anak_id, kriteria_id, nilai FROM penilaian WHERE anak_id IN ($in) AND bulan = ? AND tahun = ?");
            $stmt->execute(array_merge($ids, [$bulan_laporan, $tahun_laporan]));
            $nilaiMap = [];
            foreach ($stmt as $r) {
                $nilaiMap[(int)$r['anak_id']][(int)$r['kriteria_id']] = (float)$r['nilai'];
            }

            $ekstrem = [];
            $stmt = $pdo->prepare("SELECT kriteria_id, MAX(nilai) AS e_max, MIN(nilai) AS e_min FROM penilaian WHERE anak_id IN ($in) AND bulan = ? AND tahun = ? GROUP BY kriteria_id");
            $stmt->execute(array_merge($ids, [$bulan_laporan, $tahun_laporan]));
            foreach ($stmt as $ex) {
                $ekstrem[(int)$ex['kriteria_id']] = ['max' => (float)$ex['e_max'], 'min' => (float)$ex['e_min']];
            }

            foreach ($anakKelas as $a) {
                $score = 0;
                $lengkap = true;
                $row = ['nama' => $a['nama'], 'nis' => $a['nis'], 'nilai' => []];

                foreach ($kriteria as $k) {
                    $v = $nilaiMap[$a['id']][$k['id']] ?? null;
                    $row['nilai'][$k['id']] = $v;

                    if ($v === null) {
                        $lengkap = false;
                        continue;
                    }

                    $max = $ekstrem[$k['id']]['max'] ?? 0;
                    $min = $ekstrem[$k['id']]['min'] ?? 0;
                    $r = ($k['atribut'] === 'cost')
                        ? (($v > 0 && $min > 0) ? $min / $v : 0)
                        : (($max > 0) ? $v / $max : 0);
                    $score += $r * (float)$k['bobot'];
                }

                $row['score'] = $lengkap ? $score : null;
                $hasilKelas[] = $row;
            }

            usort($hasilKelas, fn($a, $b) => ($b['score'] ?? -1) <=> ($a['score'] ?? -1));

            $rank = 1;
            foreach ($hasilKelas as &$rw) {
                if ($rw['score'] === null) { $rw['rank'] = '-'; continue; }
                $rw['rank'] = $rank++;
            }
            unset($rw);
        }
    }
}

include "../layout/header.php";
?>

<style>
.laporan-wrap{max-width:1100px;margin:0 auto;position:relative;z-index:1}
.pilih-box{background:#fff;border:1px solid #e1e7e8;border-radius:18px;padding:20px;margin-bottom:20px;box-shadow:0 8px 25px rgba(48,71,82,.07);position:relative;z-index:10}
.pilih-box h4{color:#35566b;font-weight:800;margin-bottom:12px}
.pilih-form{display:flex;gap:10px;align-items:end;flex-wrap:wrap}
.pilih-form .field{flex:1;min-width:180px}
.laporan-paper{background:#fff;border:1px solid #ddd;box-shadow:0 8px 25px rgba(0,0,0,.06)}
.laporan-header{text-align:center;padding:28px 25px 20px;border-bottom:3px solid #222}
.laporan-header h1{margin:0;font-size:22px;font-weight:800}
.laporan-header h2{margin:7px 0 0;font-size:18px;font-weight:700}
.laporan-header p{margin:7px 0 0;font-size:12px}
.laporan-content{padding:25px}
.identitas{width:100%;border-collapse:collapse;margin-bottom:22px;font-size:13px}
.identitas td{padding:5px;vertical-align:top}
.identitas .label{width:140px;font-weight:700}
.section-title{font-size:14px;font-weight:800;margin:20px 0 9px;text-transform:uppercase}
.laporan-table{width:100%;border-collapse:collapse;font-size:12px}
.laporan-table th,.laporan-table td{border:1px solid #333;padding:8px}
.laporan-table th{text-align:center;background:#eee;font-weight:800}
.center{text-align:center}
.hasil-box{margin-top:20px;border:1px solid #333;padding:14px}
.hasil-row{display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #ddd}
.hasil-row:last-child{border-bottom:0}
.rekomendasi{margin-top:15px;padding:12px 14px;border:1px solid #333;font-size:12px;line-height:1.6}
.signature{width:270px;margin-left:auto;text-align:center;margin-top:45px;font-size:12px}
.signature .space{height:65px}

.kelas-table{width:100%;border-collapse:collapse;font-size:13px}
.kelas-table th,.kelas-table td{border:1px solid #333;padding:8px 10px}
.kelas-table th{text-align:center;background:#eee;font-weight:800;font-size:12px}
.kelas-table td{text-align:center}
.kelas-table tbody tr:nth-child(even){background:#f9f9f9}
.kelas-table tbody tr:hover{background:#f0f6f6}
.kelas-table .rank-cell{font-weight:800;font-size:14px}

.mode-tabs{display:flex;gap:0;margin-bottom:20px}
.mode-tabs a{padding:12px 24px;border:1px solid #e1e7e8;background:#fff;color:#5d6d74;text-decoration:none;font-weight:700;font-size:14px;transition:.2s}
.mode-tabs a:first-child{border-radius:12px 0 0 12px}
.mode-tabs a:last-child{border-radius:0 12px 12px 0}
.mode-tabs a.active{background:linear-gradient(135deg,#4f9c96,#72abd0);color:#fff;border-color:transparent}

@media(max-width:700px){
    .pilih-form{flex-direction:column;align-items:stretch}
    .laporan-content{padding:15px}
    .laporan-table,.kelas-table{font-size:10px}
}

@media print{
    @page{size:A4 landscape;margin:10mm}
    html,body{background:#fff!important;color:#000!important}
    body{-webkit-print-color-adjust:exact!important;print-color-adjust:exact!important}
    .sidebar,.topbar,.footer,.navbar,nav,.pilih-box,.no-print,.mode-tabs{display:none!important}
    .main,.content,.container,.container-fluid{margin:0!important;padding:0!important;max-width:none!important;width:100%!important}
    .laporan-wrap{max-width:none!important}
    .laporan-paper{border:0!important;box-shadow:none!important}
    .laporan-content{padding:20px 0 0!important}
    .laporan-table th,.kelas-table th{background:#eee!important}
    .kelas-print-title{display:block!important;text-align:center;font-size:18px;font-weight:800;margin-bottom:10px}
}
.kelas-print-title{display:none}
</style>

<div class="laporan-wrap">

    <!-- MODE TABS -->
    <div class="mode-tabs no-print">
        <a href="?mode=anak" class="<?= $mode_laporan === 'anak' ? 'active' : '' ?>">
            <i class="fa-solid fa-user me-2"></i>Laporan Per Anak
        </a>
        <a href="?mode=kelas" class="<?= $mode_laporan === 'kelas' ? 'active' : '' ?>">
            <i class="fa-solid fa-users-rectangle me-2"></i>Laporan Per Kelas
        </a>
    </div>

    <?php if ($mode_laporan === 'anak'): ?>

        <!-- ========== LAPORAN PER ANAK ========== -->
        <div class="pilih-box no-print">
            <h4><i class="fa fa-file-lines me-2"></i>Laporan Per Anak</h4>
            <form method="get" class="pilih-form">
                <input type="hidden" name="mode" value="anak">
                <div class="field">
                    <label class="form-label">Pilih Nama Anak</label>
                    <select name="anak_id" class="form-select" required>
                        <option value="">-- Pilih Nama Anak --</option>
                        <?php foreach ($anak_list as $a): ?>
                            <option value="<?= (int)$a['id'] ?>" <?= $id_anak == $a['id'] ? 'selected' : '' ?>>
                                <?= e($a['nama']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label class="form-label">Bulan</label>
                    <select name="bulan" class="form-select">
                        <?php foreach ($namaBulan as $num=>$nama): ?>
                            <option value="<?= $num ?>" <?= $bulan_laporan==$num ? 'selected' : '' ?>><?= e($nama) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label class="form-label">Tahun</label>
                    <select name="tahun" class="form-select">
                        <?php foreach ($tahunList as $th): ?>
                            <option value="<?= (int)$th ?>" <?= $tahun_laporan==(int)$th ? 'selected' : '' ?>><?= (int)$th ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fa fa-eye me-1"></i> Tampilkan</button>
                <?php if ($anak): ?>
                    <button type="button" onclick="window.print()" class="btn btn-dark"><i class="fa fa-print me-1"></i> Cetak</button>
                <?php endif; ?>
            </form>
        </div>

        <?php if ($pesan): ?>
            <div class="alert alert-warning"><?= e($pesan) ?></div>
        <?php endif; ?>

        <?php if ($anak && !$pesan): ?>
            <div class="laporan-paper">
                <div class="laporan-header">
                    <h1>TK PERTIWI</h1>
                    <h2>LAPORAN HASIL PENILAIAN PERKEMBANGAN ANAK</h2>
                    <p>Metode Simple Additive Weighting (SAW)</p>
                </div>
                <div class="laporan-content">
                    <table class="identitas">
                        <tr><td class="label">Nama Anak</td><td>: <?= e($anak['nama']) ?></td></tr>
                        <tr><td class="label">NIS</td><td>: <?= e($anak['nis']) ?></td></tr>
                        <tr><td class="label">Kelas</td><td>: <?= e($anak['nama_kelas'] ?? '-') ?></td></tr>
                        <tr><td class="label">Periode Penilaian</td><td>: <?= e($namaBulan[$bulan_laporan].' '.$tahun_laporan) ?></td></tr>
                        <tr><td class="label">Tanggal Laporan</td><td>: <?= date('d-m-Y') ?></td></tr>
                    </table>

                    <div class="section-title">A. Hasil Penilaian</div>
                    <table class="laporan-table">
                        <thead>
                            <tr>
                                <th style="width:55px">No.</th>
                                <th style="width:70px">Kode</th>
                                <th>Nama Kriteria</th>
                                <th style="width:90px">Bobot</th>
                                <th style="width:110px">Nilai</th>
                                <th style="width:105px">Normalisasi</th>
                                <th style="width:110px">Kontribusi</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $no=1; foreach ($detail as $d): ?>
                            <tr>
                                <td class="center"><?= $no++ ?></td>
                                <td class="center"><?= e($d['kode']) ?></td>
                                <td><?= e($d['nama']) ?></td>
                                <td class="center"><?= number_format($d['bobot'],2) ?></td>
                                <td class="center"><?= $d['nilai'] === null ? '-' : e(nilai_label($d['nilai'])) ?></td>
                                <td class="center"><?= $d['nilai'] === null ? '-' : number_format($d['normalisasi'],4) ?></td>
                                <td class="center"><?= $d['nilai'] === null ? '-' : number_format($d['kontribusi'],4) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="section-title">B. Hasil Akhir</div>
                    <div class="hasil-box">
                        <div class="hasil-row">
                            <strong>Nilai Akhir SAW</strong>
                            <strong><?= $nilai_akhir === null ? '-' : number_format($nilai_akhir,4) ?></strong>
                        </div>
                    </div>

                    <div class="rekomendasi">
                        <strong>Keterangan:</strong>
                        <?php if ($nilai_akhir === null): ?>
                            Penilaian pada periode ini belum lengkap sehingga nilai preferensi SAW belum dapat dihitung.
                        <?php else: ?>
                            Nilai preferensi SAW merupakan hasil penggabungan seluruh kriteria setelah normalisasi dan pembobotan. Kategori BB, MB, BSH, dan BSB tetap digunakan pada nilai awal tiap kriteria, bukan sebagai kategori nilai akhir SAW.
                        <?php endif; ?>
                    </div>

                    <div class="signature">
                        Solok, <?= date('d-m-Y') ?><br>
                        Kepala Sekolah
                        <div class="space"></div>
                        <strong>( __________________________ )</strong>
                    </div>
                </div>
            </div>
        <?php elseif ($id_anak === 0): ?>
            <div class="alert alert-info">Silakan pilih nama anak terlebih dahulu untuk menampilkan laporan.</div>
        <?php endif; ?>

    <?php else: ?>

        <!-- ========== LAPORAN PER KELAS ========== -->
        <div class="pilih-box no-print">
            <h4><i class="fa fa-users-rectangle me-2"></i>Laporan Per Kelas</h4>
            <form method="get" class="pilih-form">
                <input type="hidden" name="mode" value="kelas">
                <div class="field">
                    <label class="form-label">Kelas</label>
                    <select name="kelas_id" class="form-select" required>
                        <option value="-1">-- Pilih Kelas --</option>
                        <?php foreach ($kelasList as $kl): ?>
                            <option value="<?= $kl['id'] ?>" <?= $kelas_id == $kl['id'] ? 'selected' : '' ?>><?= e($kl['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label class="form-label">Bulan</label>
                    <select name="bulan" class="form-select">
                        <?php foreach ($namaBulan as $num=>$nama): ?>
                            <option value="<?=$num?>" <?= $bulan_laporan==$num ? 'selected' : '' ?>><?=$nama?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label class="form-label">Tahun</label>
                    <select name="tahun" class="form-select">
                        <?php foreach ($tahunList as $th): ?>
                            <option value="<?=$th?>" <?= $tahun_laporan==(int)$th ? 'selected' : '' ?>><?=$th?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fa fa-eye me-1"></i> Tampilkan</button>
                <?php if ($kelas_id > 0 && $hasilKelas): ?>
                    <button type="button" onclick="window.print()" class="btn btn-dark"><i class="fa fa-print me-1"></i> Cetak</button>
                <?php endif; ?>
            </form>
        </div>

        <?php if ($kelas_id < 0): ?>
            <div class="alert alert-info">Pilih kelas terlebih dahulu untuk menampilkan laporan.</div>
        <?php elseif (empty($hasilKelas)): ?>
            <div class="alert alert-warning">Belum ada data anak atau data penilaian untuk <b><?= e($namaBulan[$bulan_laporan].' '.$tahun_laporan) ?></b> di kelas ini.</div>
        <?php else: ?>

            <div class="laporan-paper">
                <div class="laporan-header">
                    <h1>TK PERTIWI</h1>
                    <h2>LAPORAN PENILAIAN PERKEMBANGAN ANAK PER KELAS</h2>
                    <p>Kelas: <?= e($namaKelas) ?> | Periode: <?= e($namaBulan[$bulan_laporan].' '.$tahun_laporan) ?> | Metode SAW</p>
                </div>

                <div class="laporan-content">
                    <div class="kelas-print-title no-print" style="display:none">
                        LAPORAN PERKEMBANGAN ANAK — <?= e($namaKelas) ?> — <?= e($namaBulan[$bulan_laporan].' '.$tahun_laporan) ?>
                    </div>

                    <table class="kelas-table">
                        <thead>
                            <tr>
                                <th style="width:45px">No</th>
                                <th style="width:50px">Rank</th>
                                <th>Nama Anak</th>
                                <?php foreach ($kriteria as $k): ?>
                                    <th style="width:90px"><?= e($k['kode']) ?><div style="font-weight:400;font-size:10px"><?= e($k['nama']) ?></div></th>
                                <?php endforeach; ?>
                                <th style="width:100px">Nilai SAW</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $no=1; foreach ($hasilKelas as $r): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td class="rank-cell"><?= $r['rank'] ?></td>
                                <td style="text-align:left;font-weight:700"><?= e($r['nama']) ?></td>
                                <?php foreach ($kriteria as $k): $v = $r['nilai'][$k['id']] ?? null; ?>
                                    <td><?= $v !== null ? e(nilai_label($v)) : '-' ?></td>
                                <?php endforeach; ?>
                                <td style="font-weight:700"><?= $r['score'] !== null ? number_format($r['score'],4) : '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="signature">
                        Solok, <?= date('d-m-Y') ?><br>
                        Kepala Sekolah
                        <div class="space"></div>
                        <strong>( __________________________ )</strong>
                    </div>
                </div>
            </div>

        <?php endif; ?>

    <?php endif; ?>

</div>

<?php include "../layout/footer.php"; ?>
