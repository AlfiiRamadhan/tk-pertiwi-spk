<?php
require_once "../config/koneksi.php";
require_once "../config/auth.php";
require_role(['admin','guru']);
require_once "../config/functions.php";
$title = "Input Penilaian";

$namaBulan = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

$kriteria = $pdo->query("SELECT * FROM kriteria ORDER BY kode")->fetchAll();

// Tahun tersedia dari data + tahun sekarang
$tahunList = $pdo->query("SELECT DISTINCT tahun FROM penilaian ORDER BY tahun DESC")->fetchAll(PDO::FETCH_COLUMN);
$tahunSekarang = (int)date('Y');
if (!in_array($tahunSekarang, $tahunList)) $tahunList[] = $tahunSekarang;
sort($tahunList);

// POST: Simpan penilaian
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $anak_id = (int)$_POST['anak_id'];
    $bulan = (int)$_POST['bulan'];
    $tahun = (int)$_POST['tahun'];

    foreach ($kriteria as $k) {
        $nilai = (int)($_POST['nilai'][$k['id']] ?? -1);
        if (!in_array($nilai, [1,2,3,4], true)) continue;
        $pdo->prepare("INSERT INTO penilaian(anak_id, kriteria_id, nilai, bulan, tahun, tanggal) VALUES(?,?,?,?,?,?) ON DUPLICATE KEY UPDATE nilai=VALUES(nilai), tanggal=VALUES(tanggal)")
            ->execute([$anak_id, $k['id'], $nilai, $bulan, $tahun, "$tahun-".str_pad($bulan,2,'0',STR_PAD_LEFT)."-01"]);
    }

    $ks = $pdo->prepare("SELECT kelas_id FROM anak WHERE id=?");
    $ks->execute([$anak_id]);
    $kid = (int)$ks->fetchColumn();
    $redirect = "index.php?kelas=$kid&anak=$anak_id&bulan=$bulan&tahun=$tahun";
    header("Location:$redirect");
    exit;
}

$kelasList = $pdo->query("SELECT id,nama FROM kelas ORDER BY nama")->fetchAll();
$selectedKelas = isset($_GET['kelas']) ? (int)$_GET['kelas'] : -1;
$selectedBulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : (int)date('n');
$selectedTahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');

$anak = [];
if ($selectedKelas > 0) {
    $sk = $pdo->prepare("SELECT id,nis,nama FROM anak WHERE kelas_id=? ORDER BY nama");
    $sk->execute([$selectedKelas]);
    $anak = $sk->fetchAll();
}

$selected = (int)($_GET['anak'] ?? 0);
$old = [];
if ($selected) {
    $s = $pdo->prepare("SELECT kriteria_id, nilai FROM penilaian WHERE anak_id=? AND bulan=? AND tahun=?");
    $s->execute([$selected, $selectedBulan, $selectedTahun]);
    foreach ($s as $r) {
        $old[$r['kriteria_id']] = $r;
    }
}

include "../layout/header.php";
?>

<h3>Input Penilaian Perkembangan Anak</h3>

<div class="card">
    <div class="card-body">

        <!-- Filter: Kelas + Anak + Bulan + Tahun -->
        <form method="get" class="row g-2 mb-4">
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Kelas</label>
                <select name="kelas" class="form-select" onchange="this.form.submit()">
                    <option value="-1">-- Pilih Kelas --</option>
                    <?php foreach($kelasList as $kl): ?>
                        <option value="<?=$kl['id']?>" <?=$selectedKelas==$kl['id']?'selected':''?>><?=e($kl['nama'])?></option>
                    <?php endforeach;?>
                </select>
            </div>
            <?php if($selectedKelas > 0): ?>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Anak</label>
                <select name="anak" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Pilih Anak --</option>
                    <?php foreach($anak as $a): ?>
                        <option value="<?=$a['id']?>" <?=$selected==$a['id']?'selected':''?>><?=e($a['nama'])?></option>
                    <?php endforeach;?>
                </select>
            </div>
            <?php endif; ?>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Bulan</label>
                <select name="bulan" class="form-select" onchange="this.form.submit()">
                    <?php foreach($namaBulan as $num=>$nama): ?>
                        <option value="<?=$num?>" <?=$selectedBulan==$num?'selected':''?>><?=$nama?></option>
                    <?php endforeach;?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Tahun</label>
                <select name="tahun" class="form-select" onchange="this.form.submit()">
                    <?php foreach($tahunList as $th): ?>
                        <option value="<?=$th?>" <?=$selectedTahun==(int)$th?'selected':''?>><?=$th?></option>
                    <?php endforeach;?>
                </select>
            </div>

        </form>

        <?php if($selected && count($kriteria) === 6): ?>
        <div class="mb-3 p-3 rounded-3" style="background:#edf4f4;border:1px solid #d6e5e5">
            <strong><?= $namaBulan[$selectedBulan] ?> <?= $selectedTahun ?></strong> — Penilaian untuk anak yang dipilih di bulan ini.
        </div>

        <form method="post">
            <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
            <input type="hidden" name="anak_id" value="<?=$selected?>">
            <input type="hidden" name="bulan" value="<?=$selectedBulan?>">
            <input type="hidden" name="tahun" value="<?=$selectedTahun?>">

            <div class="table-responsive">
                <table class="table table-bordered">
                    <tr>
                        <th>Kode</th>
                        <th>Kriteria</th>
                        <th>Nilai</th>
                        <th>Keterangan</th>
                    </tr>
                    <?php foreach($kriteria as $k): $v = $old[$k['id']]['nilai'] ?? ''; ?>
                    <tr>
                        <td><?=e($k['kode'])?></td>
                        <td><?=e($k['nama'])?></td>
                        <td>
                            <select class="form-select" name="nilai[<?=$k['id']?>]" required>
                                <option value="">Pilih</option>
                                <?php foreach([4=>'BSB',3=>'BSH',2=>'MB',1=>'BB'] as $n=>$lbl): ?>
                                    <option value="<?=$n?>" <?=($v!=='' && (int)$v===$n)?'selected':''?>><?=$n?> - <?=$lbl?></option>
                                <?php endforeach;?>
                            </select>
                        </td>
                        <td><?= $v!=='' ? e(nilai_desc($v, $k['kode'])) : '-' ?></td>
                    </tr>
                    <?php endforeach;?>
                </table>
            </div>
            <button class="btn btn-success"><i class="fa-solid fa-save me-2"></i>Simpan Penilaian</button>
        </form>

        <?php elseif($selected): ?>
            <div class="alert alert-warning">Kriteria harus tepat 6 sebelum penilaian.</div>
        <?php else: ?>
            <div class="alert alert-info"><?= $selectedKelas<0 ? 'Pilih kelas terlebih dahulu untuk menampilkan daftar anak.' : 'Pilih anak untuk memasukkan penilaian.' ?></div>
        <?php endif; ?>

    </div>
</div>

<?php include "../layout/footer.php"; ?>
