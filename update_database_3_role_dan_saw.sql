USE db_tk_pertiwi;

-- Ubah role lama menjadi 3 role baru
ALTER TABLE users
MODIFY role ENUM('admin','guru','kepala_sekolah') NOT NULL;

-- Jika sebelumnya ada akun operator, ubah menjadi guru
UPDATE users
SET role='guru',
    nama=CASE WHEN username='operator1' THEN 'Guru TK Pertiwi' ELSE nama END,
    username=CASE WHEN username='operator1' THEN 'guru1' ELSE username END
WHERE role='operator' OR username='operator1';

-- Pastikan akun Admin aktif dan password benar (password: admin)
INSERT INTO users (nama, username, password, role, status)
VALUES ('Administrator','admin','$2y$10$XYrO6VkKYKfa/BUpAhMUd.2Wtl8vEQ24mg/NMXjEVvzWMwI2uZctK','admin','aktif')
ON DUPLICATE KEY UPDATE
 nama=VALUES(nama), password=VALUES(password), role='admin', status='aktif';

-- Pastikan akun Guru aktif dan password benar (password: guru)
INSERT INTO users (nama, username, password, role, status)
VALUES ('Guru TK Pertiwi','guru1','$2y$10$.6UUvvoLXU4u9.84.FHrjOQVRQIwSryBYbGjIbxFThYuWka5X0A5a','guru','aktif')
ON DUPLICATE KEY UPDATE
 nama=VALUES(nama), password=VALUES(password), role='guru', status='aktif';

-- Tambahkan akun Kepala Sekolah (password: kepala)
INSERT INTO users (nama, username, password, role, status)
VALUES ('Kepala Sekolah TK Pertiwi','kepala1','$2y$10$.WwckWaF3bCheqnrqqkc5OV3ceVjmV.7u9S3O8EOG2e2UfgdZ.WqC','kepala_sekolah','aktif')
ON DUPLICATE KEY UPDATE
 nama=VALUES(nama), password=VALUES(password), role='kepala_sekolah', status='aktif';

CREATE TABLE IF NOT EXISTS proses_saw (
 id INT AUTO_INCREMENT PRIMARY KEY,
 tanggal_proses DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 user_id INT NULL,
 jumlah_anak INT NOT NULL DEFAULT 0,
 total_bobot DECIMAL(8,4) NOT NULL DEFAULT 0,
 bulan TINYINT NULL,
 tahun SMALLINT NULL,
 keterangan VARCHAR(255) NULL,
 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS detail_saw (
 id INT AUTO_INCREMENT PRIMARY KEY,
 proses_id INT NOT NULL,
 anak_id INT NOT NULL,
 kriteria_id INT NOT NULL,
 nilai_awal DECIMAL(10,4) NULL,
 nilai_normalisasi DECIMAL(12,6) NULL,
 bobot DECIMAL(8,4) NOT NULL,
 nilai_terbobot DECIMAL(12,6) NULL,
 UNIQUE KEY uk_detail_saw (proses_id, anak_id, kriteria_id),
 FOREIGN KEY (proses_id) REFERENCES proses_saw(id) ON DELETE CASCADE,
 FOREIGN KEY (anak_id) REFERENCES anak(id) ON DELETE CASCADE,
 FOREIGN KEY (kriteria_id) REFERENCES kriteria(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS hasil_saw (
 id INT AUTO_INCREMENT PRIMARY KEY,
 proses_id INT NOT NULL,
 anak_id INT NOT NULL,
 nilai_preferensi DECIMAL(12,6) NOT NULL,
 ranking INT NOT NULL,
 UNIQUE KEY uk_hasil_saw (proses_id, anak_id),
 FOREIGN KEY (proses_id) REFERENCES proses_saw(id) ON DELETE CASCADE,
 FOREIGN KEY (anak_id) REFERENCES anak(id) ON DELETE CASCADE
);