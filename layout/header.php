<?php require_once __DIR__ . '/../config/auth.php'; // env.php di-load dalam auth.php ?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'SPK TK Pertiwi') ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/tk-theme.css" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/pastel-ceria.css" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/tema-modul-tk.css" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/background-tk-tanpa-pelangi.css" rel="stylesheet">
<style>
:root{--navy:#496b78;--blue:#7399a8;--sky:#edf5f5;--peach:#d9a07e;--cream:#fbfaf7;--ink:#34444b;--muted:#7c8b91;--line:#e3e8e7}
*{box-sizing:border-box}
body{margin:0;background:var(--cream);color:var(--ink);font-family:Inter,Segoe UI,Arial,sans-serif}
.sidebar{position:fixed;left:0;top:0;bottom:0;width:245px;background:#fff;border-right:1px solid var(--line);z-index:1000;padding:22px 14px}
.brand{display:flex;align-items:center;gap:11px;padding:8px 10px 25px}
.brand-icon{width:42px;height:42px;border-radius:12px;background:var(--navy);color:#fff;display:flex;align-items:center;justify-content:center}
.brand strong{font-size:16px;color:#496b78;display:block}.brand small{color:var(--muted)}
.menu-title{font-size:11px;text-transform:uppercase;color:#9aa6b2;font-weight:700;padding:10px 12px 7px;letter-spacing:.7px}
.nav-item-custom{display:flex;align-items:center;gap:12px;padding:11px 13px;margin:3px 0;border-radius:10px;color:#5d6d74;text-decoration:none;font-size:14px}
.nav-item-custom i{width:19px;text-align:center}.nav-item-custom:hover{background:#f0f6f6;color:var(--navy)}
.nav-item-custom.active{background:#e5eff0;color:#496b78;font-weight:700}
.main{margin-left:245px;min-height:100vh}
.topbar{height:72px;background:#fff;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;padding:0 30px}
.page-title{font-weight:700;color:#496b78;font-size:18px}.user-box{display:flex;align-items:center;gap:10px}.avatar{width:36px;height:36px;border-radius:50%;background:#e6f0ef;color:var(--navy);display:flex;align-items:center;justify-content:center}
.content{padding:30px}
.card{border:1px solid var(--line);border-radius:15px;box-shadow:0 4px 16px rgba(70,90,95,.07)}
.stat-card{padding:20px}.stat-icon{width:45px;height:45px;border-radius:12px;background:#edf4f4;color:#5f8793;display:flex;align-items:center;justify-content:center;font-size:19px}
.stat-value{font-size:27px;font-weight:750;color:var(--navy)}
.btn-primary{background:#5d7f8b;border-color:#5d7f8b}.btn-primary:hover{background:#4d6d78;border-color:#4d6d78}
.btn-warning{background:var(--peach);border-color:var(--peach);color:#fff}.btn-warning:hover{color:#fff}
.table thead th{background:#f6f8f7;color:#526273;font-size:13px;border-bottom:1px solid var(--line)}
.table td{vertical-align:middle;font-size:14px}
.section-title{font-size:20px;font-weight:700;color:#496b78}
.badge-score{font-size:.85rem}
@media(max-width:900px){.sidebar{width:72px;padding:15px 8px}.brand div:not(.brand-icon),.menu-title,.nav-item-custom span{display:none}.brand{justify-content:center;padding-bottom:20px}.nav-item-custom{justify-content:center}.main{margin-left:72px}.topbar{padding:0 18px}.content{padding:18px}}
</style>
<link href="<?= BASE_URL ?>/assets/redesign-tk-pertiwi.css" rel="stylesheet">

<style>
/* PERBAIKAN SIDEBAR / MENU */
.sidebar{
    width:260px !important;
    padding:18px 14px !important;
    display:flex !important;
    flex-direction:column !important;
    overflow:hidden !important;
}
.main{
    margin-left:260px !important;
    overflow:visible !important;
}
.brand{
    min-height:86px;
    padding:10px 10px 20px !important;
    gap:12px !important;
}
.brand-icon{
    flex:0 0 48px;
    width:48px !important;
    height:48px !important;
}
.brand-text{
    min-width:0;
    line-height:1.2;
}
.brand-text strong{
    display:block;
    font-size:17px !important;
    white-space:nowrap;
}
.brand-text small{
    display:block;
    margin-top:4px;
    font-size:12px;
    line-height:1.25;
    color:#75858d !important;
    white-space:normal;
}
.menu-title{
    margin-top:4px;
    padding:10px 12px 6px !important;
}
.nav-item-custom{
    min-height:46px;
    display:flex !important;
    align-items:center !important;
    gap:12px !important;
    margin:4px 6px !important;
    padding:11px 13px !important;
    border-radius:12px !important;
    font-size:14px !important;
    font-weight:600;
}
.nav-item-custom i{
    flex:0 0 22px;
    width:22px !important;
    font-size:15px !important;
}
.nav-item-custom span{
    line-height:1.2;
}
.nav-item-custom.active{
    color:#fff !important;
    background:linear-gradient(135deg,#4f9c96,#72abd0) !important;
    box-shadow:0 7px 18px rgba(79,156,150,.22) !important;
}
.nav-item-custom.active i,
.nav-item-custom.active span{
    color:#fff !important;
}
.sidebar-menu-scroll{
    flex:1 1 auto;
    min-height:0;
    overflow-y:auto;
    overflow-x:hidden;
    padding-bottom:8px;
    scrollbar-width:thin;
}
.sidebar-account{
    flex:0 0 auto;
    background:#fff;
    border-top:1px solid #e7eded;
    padding-top:7px;
}
.sidebar-account .menu-title{margin-top:0 !important;}
@media(max-width:900px){
    .sidebar{width:74px !important;padding:14px 8px !important}
    .main{margin-left:74px !important}
    .brand{justify-content:center !important;min-height:auto}
    .brand-text,.menu-title,.nav-item-custom span{display:none !important}
    .nav-item-custom{justify-content:center !important;margin:4px !important;padding:11px 8px !important}
}
</style>

</head>
<?php
$__path = $_SERVER['PHP_SELF'] ?? '';
$__module_class = 'module-blue';
if (strpos($__path, '/anak/') !== false) $__module_class='module-anak';
elseif (strpos($__path, '/kelas/') !== false) $__module_class='module-kelas';
elseif (strpos($__path, '/kriteria/') !== false) $__module_class='module-kriteria';
elseif (strpos($__path, '/penilaian/') !== false) $__module_class='module-penilaian';
elseif (strpos($__path, '/saw/') !== false) $__module_class='module-saw';
elseif (strpos($__path, '/rekap/') !== false) $__module_class='module-laporan';
elseif (strpos($__path, '/laporan/') !== false) $__module_class='module-laporan';

function menu_active($path, $needle) {
    if ($needle === 'dashboard') {
        // Match baik di root '/' maupun di subfolder '/tk_pertiwi_spk/'
        return preg_match('#/(index\.php)?$#', $path) ? ' active' : '';
    }
    return strpos($path, '/' . $needle . '/') !== false ? ' active' : '';
}
?>
<body class="tk-theme tk-soft-bg <?= $__module_class ?>">
<aside class="sidebar">
<div class="brand"><div class="brand-icon"><i class="fa-solid fa-child-reaching"></i></div><div class="brand-text"><strong>TK Pertiwi</strong><small>SPK Perkembangan Anak</small></div></div>
<div class="sidebar-menu-scroll">
<div class="menu-title">Menu Utama</div>
<a class="nav-item-custom<?= menu_active($__path, 'dashboard') ?>" href="<?= BASE_URL ?>/index.php"><i class="fa-solid fa-gauge-high"></i><span>Dashboard</span></a>

<?php if (has_role(['admin','guru'])): ?>
<a class="nav-item-custom<?= menu_active($__path, 'anak') ?>" href="<?= BASE_URL ?>/anak/index.php"><i class="fa-solid fa-children"></i><span>Data Anak</span></a>
<?php endif; ?>

<?php if (has_role('admin')): ?>
<a class="nav-item-custom<?= menu_active($__path, 'kelas') ?>" href="<?= BASE_URL ?>/kelas/index.php"><i class="fa-solid fa-school"></i><span>Data Kelas</span></a>
<a class="nav-item-custom<?= menu_active($__path, 'kriteria') ?>" href="<?= BASE_URL ?>/kriteria/index.php"><i class="fa-solid fa-list-check"></i><span>Data Kriteria</span></a>
<?php endif; ?>

<?php if (has_role(['admin','guru'])): ?>
<a class="nav-item-custom<?= menu_active($__path, 'penilaian') ?>" href="<?= BASE_URL ?>/penilaian/index.php"><i class="fa-solid fa-clipboard-check"></i><span>Penilaian</span></a>
<a class="nav-item-custom<?= (strpos($__path, '/saw/index.php') !== false) ? ' active' : '' ?>" href="<?= BASE_URL ?>/saw/index.php"><i class="fa-solid fa-calculator"></i><span>Proses SAW</span></a>
<?php endif; ?>

<?php if (has_role(['admin','guru','kepala_sekolah'])): ?>
<a class="nav-item-custom<?= strpos($__path, '/saw/riwayat.php') !== false ? ' active' : '' ?>" href="<?= BASE_URL ?>/saw/riwayat.php"><i class="fa-solid fa-ranking-star"></i><span>Hasil SAW</span></a>
<a class="nav-item-custom<?= menu_active($__path, 'rekap') ?>" href="<?= BASE_URL ?>/rekap/index.php"><i class="fa-solid fa-table-list"></i><span>Rekap Nilai</span></a>
<a class="nav-item-custom<?= menu_active($__path, 'laporan') ?>" href="<?= BASE_URL ?>/laporan/index.php"><i class="fa-solid fa-chart-column"></i><span>Laporan</span></a>
<?php endif; ?>
</div>
<div class="sidebar-account">
<div class="menu-title">Akun</div>
<a class="nav-item-custom" href="<?= BASE_URL ?>/logout.php"><i class="fa-solid fa-right-from-bracket"></i><span>Keluar</span></a>
</div>
</aside>
<div class="main">
<header class="topbar">
<div class="page-title"><?= e($title ?? 'Dashboard') ?></div>
<div class="user-box"><div class="text-end d-none d-sm-block"><div class="fw-semibold small"><?= e($_SESSION['user']['nama'] ?? '') ?></div><div class="text-muted small"><?= e(role_label($_SESSION['user']['role'] ?? '')) ?></div></div><div class="avatar"><i class="fa-solid fa-user"></i></div></div>
</header>
<div class="content">
