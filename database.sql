-- =========================================================
-- DATABASE SISTEM ABSENSI LEGIO MARIA (LARAGON MYSQL)
-- Nama Database: absensi_legio_maria
-- =========================================================

CREATE DATABASE IF NOT EXISTS `absensi_legio_maria` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `absensi_legio_maria`;

-- 1. Tabel Administrator / Perwira Petugas
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `nama_lengkap` VARCHAR(100) NOT NULL,
  `role` VARCHAR(30) DEFAULT 'admin',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Tabel Profil Presidium Legio Maria
CREATE TABLE IF NOT EXISTS `presidium` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_presidium` VARCHAR(150) NOT NULL,
  `nama_kuria` VARCHAR(150) DEFAULT 'Kuria Maria Ratu Rosari',
  `paroki` VARCHAR(150) NOT NULL,
  `hari_rapat` VARCHAR(50) DEFAULT 'Minggu',
  `jam_rapat` VARCHAR(20) DEFAULT '10:00 WIB',
  `lokasi_rapat` VARCHAR(150) DEFAULT 'Ruang Rapat Paroki',
  `pembimbing_rohani` VARCHAR(150) DEFAULT 'Pastor Paroki'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Tabel Anggota Legio Maria (Kapasitas Tak Terbatas / Dynamic)
CREATE TABLE IF NOT EXISTS `anggota` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nomor_anggota` VARCHAR(50) NOT NULL UNIQUE,
  `nama_lengkap` VARCHAR(120) NOT NULL,
  `jenis_anggota` ENUM('Aktif', 'Auksiler', 'Praetorian', 'Adjutorian') DEFAULT 'Aktif',
  `jabatan` ENUM('Pemimpin', 'Wakil Pemimpin', 'Sekretaris', 'Bendahara', 'Anggota') DEFAULT 'Anggota',
  `jenis_kelamin` ENUM('L', 'P') DEFAULT 'P',
  `no_telepon` VARCHAR(25) DEFAULT NULL,
  `alamat` TEXT DEFAULT NULL,
  `tanggal_bergabung` DATE DEFAULT NULL,
  `status` ENUM('Aktif', 'Cuti', 'Pindah', 'Berhenti') DEFAULT 'Aktif',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Tabel Pertemuan / Rapat Mingguan Presidium
CREATE TABLE IF NOT EXISTS `pertemuan` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nomor_pertemuan` INT NOT NULL,
  `tanggal` DATE NOT NULL,
  `buku_pegangan_bab` VARCHAR(100) DEFAULT NULL,
  `tema_alokusi` VARCHAR(255) DEFAULT NULL,
  `pembaca_alokusi` VARCHAR(120) DEFAULT NULL,
  `catatan_rapat` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Tabel Catatan Absensi Kehadiran
CREATE TABLE IF NOT EXISTS `absensi` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `pertemuan_id` INT NOT NULL,
  `anggota_id` INT NOT NULL,
  `status_kehadiran` ENUM('Hadir', 'Izin', 'Sakit', 'Alpa', 'Tugas Kerasulan') DEFAULT 'Hadir',
  `laporan_tugas` TEXT DEFAULT NULL,
  `keterangan` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unik_absensi` (`pertemuan_id`, `anggota_id`),
  FOREIGN KEY (`pertemuan_id`) REFERENCES `pertemuan`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`anggota_id`) REFERENCES `anggota`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- DATA DEFAULT INITIAL SEED
-- =========================================================

-- Admin Default: username: admin | password: admin123
INSERT INTO `users` (`username`, `password`, `nama_lengkap`, `role`) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sekretaris Presidium', 'admin')
ON DUPLICATE KEY UPDATE `username`=`username`;

-- Info Presidium Default
INSERT INTO `presidium` (`id`, `nama_presidium`, `nama_kuria`, `paroki`, `hari_rapat`, `jam_rapat`, `lokasi_rapat`, `pembimbing_rohani`)
VALUES (1, 'Presidium Maria Bintang Samudera', 'Kuria Maria Ratu Damai', 'Paroki Hati Kudus Yesus', 'Setiap Minggu', '11:00 WIB', 'Gedung Karya Pastoral Lt. 2', 'RP. Yohanes, O.Carm')
ON DUPLICATE KEY UPDATE `nama_presidium`=`nama_presidium`;

-- Contoh Awal Anggota Legio Maria (Bisa ditambah tak terbatas lewat web)
INSERT INTO `anggota` (`nomor_anggota`, `nama_lengkap`, `jenis_anggota`, `jabatan`, `jenis_kelamin`, `no_telepon`, `tanggal_bergabung`, `status`) VALUES
('LM-001', 'Maria Elisabeth Hutabarat', 'Aktif', 'Pemimpin', 'P', '081234567890', '2023-01-15', 'Aktif'),
('LM-002', 'Yohanes Baptista Simanjuntak', 'Aktif', 'Wakil Pemimpin', 'L', '081234567891', '2023-02-10', 'Aktif'),
('LM-003', 'Theresia Novita Sari', 'Aktif', 'Sekretaris', 'P', '081234567892', '2023-03-01', 'Aktif'),
('LM-004', 'Fransiskus Xaverius Siregar', 'Aktif', 'Bendahara', 'L', '081234567893', '2023-03-15', 'Aktif'),
('LM-005', 'Agnes Monica Pasaribu', 'Aktif', 'Anggota', 'P', '081234567894', '2023-05-20', 'Aktif'),
('LM-006', 'Antonius Stefanus Gultom', 'Aktif', 'Anggota', 'L', '081234567895', '2023-06-12', 'Aktif'),
('LM-007', 'Katarina Laboure Sinaga', 'Auksiler', 'Anggota', 'P', '081234567896', '2023-07-05', 'Aktif'),
('LM-008', 'Mikael Bernard Tambunan', 'Auksiler', 'Anggota', 'L', '081234567897', '2023-08-18', 'Aktif')
ON DUPLICATE KEY UPDATE `nomor_anggota`=`nomor_anggota`;

