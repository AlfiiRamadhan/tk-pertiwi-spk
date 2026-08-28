TK PERTIWI - SPK PENILAIAN PERKEMBANGAN ANAK

LOGIN SISTEM:
1. Admin
   Username: admin
   Password: admin

2. Guru
   Username: guru1
   Password: guru123

KEPALA SEKOLAH:
- Tidak memiliki akun login.
- Kepala Sekolah hanya sebagai pihak yang melihat/menerima hasil SAW dan laporan.
- Hasil dan laporan dapat ditampilkan atau dicetak oleh Admin/Guru untuk Kepala Sekolah.

UNTUK DATABASE YANG SUDAH ADA:
1. Buka phpMyAdmin.
2. Pilih database db_tk_pertiwi.
3. Import file update_login_admin_guru.sql.
4. Login menggunakan akun Admin atau Guru.

Jika login masih bermasalah, buka sekali:
http://localhost/tk_pertiwi_spk/setup_password.php
Setelah berhasil, hapus file setup_password.php.

DATABASE SAW:
- proses_saw
- detail_saw
- hasil_saw


CATATAN DATABASE VERSI FINAL:
- Instalasi baru: cukup import database.sql.
- Jika memakai database dari program versi sebelumnya, baca dan jalankan MIGRASI_FINAL_SAW.sql sesuai kebutuhan.
- File migrasi skala biner/"Kurang-Cukup-Baik" yang bertentangan dengan BB-MB-BSH-BSB telah dihapus.
