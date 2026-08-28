-- MIGRASI UNTUK DATABASE LAMA YANG BELUM MEMILIKI PERIODE BULAN/TAHUN
USE db_tk_pertiwi;

ALTER TABLE penilaian
  ADD COLUMN bulan TINYINT NULL AFTER nilai,
  ADD COLUMN tahun SMALLINT NULL AFTER bulan;

UPDATE penilaian
SET bulan = MONTH(tanggal), tahun = YEAR(tanggal)
WHERE bulan IS NULL OR tahun IS NULL;

ALTER TABLE penilaian
  MODIFY bulan TINYINT NOT NULL,
  MODIFY tahun SMALLINT NOT NULL,
  MODIFY nilai TINYINT NOT NULL COMMENT '1=BB, 2=MB, 3=BSH, 4=BSB',
  DROP INDEX uk_penilaian,
  ADD UNIQUE KEY uk_penilaian_bulanan (anak_id,kriteria_id,bulan,tahun);
