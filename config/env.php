<?php
/**
 * Konfigurasi Environment
 * 
 * Di lokal (XAMPP):  set BASE_URL = '/tk_pertiwi_spk' di environment, atau biarkan kosong dan 
 *                    sesuaikan APP_BASE_PATH di bawah.
 * Di Railway/prod:   BASE_URL akan kosong '' karena app berjalan di root '/'.
 */

// BASE_URL: prefix URL aplikasi (tanpa trailing slash)
// Lokal XAMPP   -> '/tk_pertiwi_spk'
// Railway/prod  -> '' (root)
define('BASE_URL', rtrim(getenv('BASE_URL') !== false ? getenv('BASE_URL') : '/tk_pertiwi_spk', '/'));

// APP_ENV: 'local' | 'production'
define('APP_ENV', getenv('APP_ENV') ?: 'local');
?>
