CREATE DATABASE IF NOT EXISTS db_tk_pertiwi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_tk_pertiwi;

CREATE TABLE users (
 id INT AUTO_INCREMENT PRIMARY KEY,
 nama VARCHAR(100) NOT NULL,
 username VARCHAR(50) NOT NULL UNIQUE,
 password VARCHAR(255) NOT NULL,
 role ENUM('admin','guru','kepala_sekolah') NOT NULL,
 status ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE kelas (
 id INT AUTO_INCREMENT PRIMARY KEY,
 nama VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE anak (
 id INT AUTO_INCREMENT PRIMARY KEY,
 nis VARCHAR(30) NOT NULL UNIQUE,
 nama VARCHAR(100) NOT NULL,
 jk ENUM('L','P') NOT NULL,
 tgl_lahir DATE NULL,
 alamat VARCHAR(255) NULL,
 kelas_id INT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (kelas_id) REFERENCES kelas(id) ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE TABLE kriteria (
 id INT AUTO_INCREMENT PRIMARY KEY,
 kode VARCHAR(5) NOT NULL UNIQUE,
 nama VARCHAR(100) NOT NULL,
 bobot DECIMAL(5,2) NOT NULL,
 atribut ENUM('benefit','cost') NOT NULL DEFAULT 'benefit'
);

CREATE TABLE penilaian (
 id INT AUTO_INCREMENT PRIMARY KEY,
 anak_id INT NOT NULL,
 kriteria_id INT NOT NULL,
 nilai TINYINT NOT NULL COMMENT '1=BB, 2=MB, 3=BSH, 4=BSB',
 bulan TINYINT NOT NULL,
 tahun SMALLINT NOT NULL,
 tanggal DATE NOT NULL,
 UNIQUE KEY uk_penilaian_bulanan (anak_id,kriteria_id,bulan,tahun),
 FOREIGN KEY (anak_id) REFERENCES anak(id) ON DELETE CASCADE,
 FOREIGN KEY (kriteria_id) REFERENCES kriteria(id) ON DELETE CASCADE
);

CREATE TABLE proses_saw (
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

CREATE TABLE detail_saw (
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

CREATE TABLE hasil_saw (
 id INT AUTO_INCREMENT PRIMARY KEY,
 proses_id INT NOT NULL,
 anak_id INT NOT NULL,
 nilai_preferensi DECIMAL(12,6) NOT NULL,
 ranking INT NOT NULL,
 UNIQUE KEY uk_hasil_saw (proses_id, anak_id),
 FOREIGN KEY (proses_id) REFERENCES proses_saw(id) ON DELETE CASCADE,
 FOREIGN KEY (anak_id) REFERENCES anak(id) ON DELETE CASCADE
);

INSERT INTO users(nama,username,password,role) VALUES
('Administrator','admin','$2y$10$UEI.skbfcCFEko6/rw1sC.GEp0xNZp6F3qgTLhOSQ0pfkbyf/l0R2','admin'),
('Guru TK Pertiwi','guru1','$2y$10$XqJggG1G8FKw2YqhPBDrBuS9X/.aJojL0NnLuCruT/8BacVs/QIPO','guru'),
('Kepala Sekolah TK Pertiwi','kepala1','$2y$10$OfAwENY8lnoiVso4hHvJ9un/eAp3Q5eIQfiu4eoXYvf4FxXUsscL2','kepala_sekolah');

INSERT INTO kelas(nama) VALUES ('Kelas A'),('Kelas B');

INSERT INTO kriteria(kode,nama,bobot,atribut) VALUES
('C1','Nilai Agama dan Moral',0.15,'benefit'),
('C2','Fisik Motorik',0.15,'benefit'),
('C3','Kognitif',0.20,'benefit'),
('C4','Bahasa',0.15,'benefit'),
('C5','Sosial Emosional',0.20,'benefit'),
('C6','Seni',0.15,'benefit');