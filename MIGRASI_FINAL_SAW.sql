-- MIGRASI FINAL UNTUK PROGRAM tk_pertiwi_spk(4)
-- Jalankan HANYA jika database lama sudah dipakai dan memiliki kolom bulan/tahun pada penilaian.
USE db_tk_pertiwi;

-- Nilai awal perkembangan tetap 1=BB, 2=MB, 3=BSH, 4=BSB.
ALTER TABLE penilaian
  MODIFY nilai TINYINT NOT NULL COMMENT '1=BB, 2=MB, 3=BSH, 4=BSB';

-- Simpan periode proses SAW agar riwayat hasil jelas.
ALTER TABLE proses_saw
  ADD COLUMN bulan TINYINT NULL AFTER total_bobot,
  ADD COLUMN tahun SMALLINT NULL AFTER bulan;

-- Nilai akhir SAW cukup nilai preferensi + ranking.
ALTER TABLE hasil_saw
  DROP COLUMN status_perkembangan;
