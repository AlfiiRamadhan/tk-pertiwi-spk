USE db_tk_pertiwi;

-- Ubah role menjadi 3 role: admin, guru, kepala_sekolah
ALTER TABLE users
MODIFY role ENUM('admin','guru','kepala_sekolah') NOT NULL;

-- Pastikan akun Admin aktif dan password benar (password: admin)
INSERT INTO users(nama,username,password,role,status)
VALUES ('Administrator','admin','$2y$10$TUT7MdxSCXUPibV9f043ee4AF0CZceKi0v5gpucaqfGJucIi2x7kS','admin','aktif')
ON DUPLICATE KEY UPDATE
nama=VALUES(nama),
password=VALUES(password),
role='admin',
status='aktif';

-- Pastikan akun Guru aktif dan password benar (password: guru)
INSERT INTO users(nama,username,password,role,status)
VALUES ('Guru TK Pertiwi','guru1','$2y$10$l6wDxcBJnr83xxzyrV8WqeP.QFFTIRJV0/gV6IcHV.PI21uJmBCf2','guru','aktif')
ON DUPLICATE KEY UPDATE
nama=VALUES(nama),
password=VALUES(password),
role='guru',
status='aktif';

-- Pastikan akun Kepala Sekolah aktif dan password benar (password: kepala)
INSERT INTO users(nama,username,password,role,status)
VALUES ('Kepala Sekolah TK Pertiwi','kepala1','$2y$10$pwOyypDTwtWKk9yrVS3obeAuvygvGYBv5T7yS4RRWWoPvx0RuoWeC','kepala_sekolah','aktif')
ON DUPLICATE KEY UPDATE
nama=VALUES(nama),
password=VALUES(password),
role='kepala_sekolah',
status='aktif';