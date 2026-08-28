<?php
require_once "../config/koneksi.php"; require_once "../config/auth.php"; require_role(['admin','guru']);
$title="Data Anak";
if($_SERVER['REQUEST_METHOD']==='POST'){ check_csrf(); $aksi=$_POST['aksi']??'';
 if($aksi==='hapus'){ $pdo->prepare("DELETE FROM anak WHERE id=?")->execute([(int)$_POST['id']]); header("Location:index.php"); exit; }
 if($aksi==='simpan'){
  $id=(int)($_POST['id']??0); $data=[trim($_POST['nis']),trim($_POST['nama']),$_POST['jk'],$_POST['tgl_lahir']?:null,trim($_POST['alamat']), (int)$_POST['kelas_id']];
  if($id){$pdo->prepare("UPDATE anak SET nis=?,nama=?,jk=?,tgl_lahir=?,alamat=?,kelas_id=? WHERE id=?")->execute([...$data,$id]);}
  else{$pdo->prepare("INSERT INTO anak(nis,nama,jk,tgl_lahir,alamat,kelas_id) VALUES(?,?,?,?,?,?)")->execute($data);}
  header("Location:index.php"); exit;
 }}
$edit=null;if(isset($_GET['edit'])){$s=$pdo->prepare("SELECT * FROM anak WHERE id=?");$s->execute([(int)$_GET['edit']]);$edit=$s->fetch();}
$kelas=$pdo->query("SELECT * FROM kelas ORDER BY nama")->fetchAll();
$kelas_a_row=$pdo->query("SELECT id FROM kelas WHERE nama LIKE '%A%'")->fetch();
$kelas_b_row=$pdo->query("SELECT id FROM kelas WHERE nama LIKE '%B%'")->fetch();
$kelas_a_id=$kelas_a_row?$kelas_a_row['id']:null;
$kelas_b_id=$kelas_b_row?$kelas_b_row['id']:null;
$s_a=$pdo->prepare("SELECT * FROM anak WHERE kelas_id=? ORDER BY nama");$s_a->execute([$kelas_a_id]);$rows_a=$s_a->fetchAll();
$s_b=$pdo->prepare("SELECT * FROM anak WHERE kelas_id=? ORDER BY nama");$s_b->execute([$kelas_b_id]);$rows_b=$s_b->fetchAll();
include "../layout/header.php"; ?>
<div class="d-flex justify-content-between align-items-center mb-3"><h3>Data Anak</h3><a class="btn btn-primary" href="?tambah=1">+ Tambah</a></div>
<?php if(isset($_GET['tambah'])||$edit): ?><div class="card mb-4"><div class="card-body"><h5><?= $edit?'Edit':'Tambah' ?> Anak</h5>
<form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="aksi" value="simpan"><input type="hidden" name="id" value="<?=e($edit['id']??0)?>">
<div class="row g-2"><div class="col-md-2"><input class="form-control" name="nis" placeholder="NIS" required value="<?=e($edit['nis']??'')?>"></div>
<div class="col-md-4"><input class="form-control" name="nama" placeholder="Nama anak" required value="<?=e($edit['nama']??'')?>"></div>
<div class="col-md-2"><select class="form-select" name="jk" required><option value="">JK</option><option value="L" <?=($edit['jk']??'')==='L'?'selected':''?>>L</option><option value="P" <?=($edit['jk']??'')==='P'?'selected':''?>>P</option></select></div>
<div class="col-md-2"><input type="date" class="form-control" name="tgl_lahir" value="<?=e($edit['tgl_lahir']??'')?>"></div>
<div class="col-md-2"><select class="form-select" name="kelas_id" required><option value="">Kelas</option><?php foreach($kelas as $k):?><option value="<?=$k['id']?>" <?=($edit['kelas_id']??'')==$k['id']?'selected':''?>><?=e($k['nama'])?></option><?php endforeach;?></select></div>
<div class="col-12"><input class="form-control" name="alamat" placeholder="Alamat" value="<?=e($edit['alamat']??'')?>"></div></div>
<button class="btn btn-success mt-3">Simpan</button> <a class="btn btn-secondary mt-3" href="index.php">Batal</a></form></div></div><?php endif; ?>
<div class="card mb-4"><div class="card-header"><h5>Anak Kelas A</h5></div><div class="card-body table-responsive"><table class="table table-bordered align-middle"><thead><tr><th>No</th><th>NIS</th><th>Nama</th><th>JK</th><th>Tgl Lahir</th><th>Alamat</th><th>Aksi</th></tr></thead><tbody>
<?php foreach($rows_a as $i=>$r): ?><tr><td><?=$i+1?></td><td><?=e($r['nis'])?></td><td><?=e($r['nama'])?></td><td><?=e($r['jk'])?></td><td><?=e($r['tgl_lahir'])?></td><td><?=e($r['alamat']??'')?></td><td><div class="d-flex gap-1"><a class="btn btn-sm btn-warning" href="?edit=<?=$r['id']?>">Edit</a>
<form method="post" class="d-inline" onsubmit="return confirm('Hapus data?')"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?=$r['id']?>"><button class="btn btn-sm btn-danger">Hapus</button></form></div></td></tr><?php endforeach; ?>
<?php if(!$rows_a): ?><tr><td colspan="7" class="text-center text-muted">Belum ada data anak</td></tr><?php endif; ?>
</tbody></table></div></div>

<div class="card mb-4"><div class="card-header"><h5>Anak Kelas B</h5></div><div class="card-body table-responsive"><table class="table table-bordered align-middle"><thead><tr><th>No</th><th>NIS</th><th>Nama</th><th>JK</th><th>Tgl Lahir</th><th>Alamat</th><th>Aksi</th></tr></thead><tbody>
<?php foreach($rows_b as $i=>$r): ?><tr><td><?=$i+1?></td><td><?=e($r['nis'])?></td><td><?=e($r['nama'])?></td><td><?=e($r['jk'])?></td><td><?=e($r['tgl_lahir'])?></td><td><?=e($r['alamat']??'')?></td><td><div class="d-flex gap-1"><a class="btn btn-sm btn-warning" href="?edit=<?=$r['id']?>">Edit</a>
<form method="post" class="d-inline" onsubmit="return confirm('Hapus data?')"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?=$r['id']?>"><button class="btn btn-sm btn-danger">Hapus</button></form></div></td></tr><?php endforeach; ?>
<?php if(!$rows_b): ?><tr><td colspan="7" class="text-center text-muted">Belum ada data anak</td></tr><?php endif; ?>
</tbody></table></div></div><?php include "../layout/footer.php"; ?>
