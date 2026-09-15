-- =====================================================================
-- MIGRASI DATABASE db_slipgaji
-- Mengganti sistem "Periode Penggajian" lama (tabel periode_gaji) dengan
-- PERIODE TETAP tanggal 25 -> 25 (contoh: 25 November - 25 Desember).
--
-- Yang disimpan di database cukup TANGGAL MULAI periode, yaitu tanggal 25
-- pada bulan yang dipilih admin (kolom `gaji`.`tanggal_gajian`).
-- Tanggal selesainya dihitung otomatis oleh aplikasi (+1 bulan).
--
-- CARA PAKAI:
-- 1. Buka phpMyAdmin -> pilih database db_slipgaji -> tab SQL.
-- 2. Copy-paste seluruh isi file ini, lalu klik "Go" / "Jalankan".
-- 3. Aman dijalankan berulang kali (pakai IF EXISTS / IF NOT EXISTS).
-- =====================================================================

-- 1. Kolom baru: tanggal mulai periode (selalu tanggal 25)
ALTER TABLE `gaji`
  ADD COLUMN IF NOT EXISTS `tanggal_gajian` DATE DEFAULT NULL AFTER `pinjaman_karyawan`;

-- 2. Pindahkan data lama (kalau tabel periode_gaji masih ada):
--    ambil bulan dari periode lama, dipaksa ke tanggal 25.
--    (dilewati otomatis kalau tabel/kolom lama sudah tidak ada)
SET @punya_tabel_lama = (
  SELECT COUNT(*) FROM information_schema.TABLES
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'periode_gaji'
);
SET @punya_kolom_lama = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gaji' AND COLUMN_NAME = 'periode_id'
);
SET @sql_migrasi = IF(@punya_tabel_lama > 0 AND @punya_kolom_lama > 0,
  'UPDATE `gaji` g JOIN `periode_gaji` p ON g.periode_id = p.id
   SET g.tanggal_gajian = DATE_FORMAT(p.tanggal_mulai, ''%Y-%m-25'')
   WHERE g.tanggal_gajian IS NULL',
  'SELECT 1');
PREPARE jalankan FROM @sql_migrasi;
EXECUTE jalankan;
DEALLOCATE PREPARE jalankan;

-- 3. Baris gaji yang tidak punya periode sama sekali -> pakai bulan berjalan
UPDATE `gaji`
SET `tanggal_gajian` = DATE_FORMAT(CURDATE(), '%Y-%m-25')
WHERE `tanggal_gajian` IS NULL;

-- 4. Hapus kolom & tabel lama yang sudah tidak dipakai
ALTER TABLE `gaji` DROP COLUMN IF EXISTS `periode_id`;
ALTER TABLE `gaji` DROP COLUMN IF EXISTS `periode`;
ALTER TABLE `gaji` DROP COLUMN IF EXISTS `nama`;
ALTER TABLE `gaji` DROP COLUMN IF EXISTS `nik`;
ALTER TABLE `gaji` DROP COLUMN IF EXISTS `jabatan`;
ALTER TABLE `gaji` DROP COLUMN IF EXISTS `status`;

DROP TABLE IF EXISTS `periode_gaji`;

-- 5. Email karyawan boleh kosong (dulu wajib unik & bikin gagal simpan
--    kalau ada 2 karyawan tanpa email). Sekarang boleh NULL, tetap unik
--    kalau memang diisi.
ALTER TABLE `karyawan` MODIFY `email` VARCHAR(100) NULL DEFAULT NULL;
ALTER TABLE `karyawan` MODIFY `password` VARCHAR(255) NULL DEFAULT NULL;
UPDATE `karyawan` SET `email` = NULL WHERE `email` = '';
