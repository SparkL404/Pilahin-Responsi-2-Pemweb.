-- ============================================================================
-- Pilahin Database Schema & Seed (1-Click Import phpMyAdmin / MySQL / MariaDB)
-- Panduan Pilah Sampah Rumah Tangga & Pelaporan Sampah Liar (SDG 11 & SDG 12)
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `pilahin_db`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `pilahin_db`;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `items`;
DROP TABLE IF EXISTS `search_misses`;
DROP TABLE IF EXISTS `quiz_questions`;
DROP TABLE IF EXISTS `quiz_attempts`;
DROP TABLE IF EXISTS `reports`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `admins`;

SET FOREIGN_KEY_CHECKS = 1;

-- ----------------------------------------------------------------------------
-- 1. Tabel Kategori Tempat Sampah
-- ----------------------------------------------------------------------------
CREATE TABLE `categories` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(50) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `color` VARCHAR(20) NOT NULL,
  `bin` VARCHAR(80) NOT NULL,
  `examples` TEXT NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categories_slug` (`slug`),
  UNIQUE KEY `uq_categories_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 2. Tabel Kata Kunci Barang / Sampah (Relasi ke categories)
-- ----------------------------------------------------------------------------
CREATE TABLE `items` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `keyword` VARCHAR(100) NOT NULL,
  `category_id` INT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_items_keyword` (`keyword`),
  KEY `idx_items_category` (`category_id`),
  CONSTRAINT `fk_items_category`
    FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 3. Tabel Log Pencarian Tidak Ditemukan (Smart Search Miss Log)
-- ----------------------------------------------------------------------------
CREATE TABLE `search_misses` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `query_text` VARCHAR(80) NOT NULL,
  `hit_count` INT UNSIGNED NOT NULL DEFAULT 1,
  `status` ENUM('pending', 'resolved', 'ignored') NOT NULL DEFAULT 'pending',
  `last_searched_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_search_misses_query` (`query_text`),
  KEY `idx_search_misses_status_hits` (`status`, `hit_count`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 4. Tabel Bank Soal Kuis Edukasi
-- ----------------------------------------------------------------------------
CREATE TABLE `quiz_questions` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `question` VARCHAR(255) NOT NULL,
  `option_a` VARCHAR(150) NOT NULL,
  `option_b` VARCHAR(150) NOT NULL,
  `option_c` VARCHAR(150) NOT NULL,
  `answer_index` TINYINT UNSIGNED NOT NULL,
  `explanation` TEXT NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_quiz_active_order` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 5. Tabel Rekap Percobaan & Skor Kuis Warga
-- ----------------------------------------------------------------------------
CREATE TABLE `quiz_attempts` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `score` TINYINT UNSIGNED NOT NULL,
  `total_questions` TINYINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_quiz_attempts_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 6. Tabel Laporan Tumpukan Sampah Liar
-- ----------------------------------------------------------------------------
CREATE TABLE `reports` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `waste_type` VARCHAR(100) NOT NULL,
  `location` VARCHAR(200) NOT NULL,
  `notes` TEXT NULL,
  `status` ENUM('baru', 'diproses', 'selesai') NOT NULL DEFAULT 'baru',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_reports_status` (`status`),
  KEY `idx_reports_waste_type` (`waste_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 7. Tabel Admin Panel (Autentikasi PHP Session)
-- Catatan: Akun admin default (username: admin / password: admin123)
-- otomatis dibuat oleh includes/auth.php menggunakan password_hash() native PHP
-- saat halaman admin/login.php pertama kali diakses jika tabel admins masih kosong.
-- ----------------------------------------------------------------------------
CREATE TABLE `admins` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admins_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- SEED DATA AWAL (Migrasi 100% dari data.js + Data Contoh Demo)
-- ============================================================================

INSERT INTO `categories` (`id`, `slug`, `name`, `color`, `bin`, `examples`, `sort_order`) VALUES
(1, 'organik', 'Organik', '#3E9B4F', 'hijau', 'Sisa makanan, daun, kulit buah, ranting', 1),
(2, 'plastik', 'Plastik', '#E8B923', 'kuning', 'Botol, gelas, sedotan, kantong, kemasan', 2),
(3, 'kertas', 'Kertas', '#2F7DE1', 'biru', 'Kardus, kertas HVS, koran, buku bekas', 3),
(4, 'b3', 'B3', '#D8453B', 'merah', 'Baterai, lampu bekas, obat kedaluwarsa, cat', 4),
(5, 'residu', 'Residu', '#7C8A85', 'abu-abu', 'Tisu kotor, popok, puntung rokok, pecahan keramik', 5),
(6, 'elektronik', 'Elektronik', '#8A56D6', 'ungu (e-waste)', 'Charger rusak, kabel, headset, mouse', 6);

INSERT INTO `items` (`keyword`, `category_id`) VALUES
-- Organik (11)
('kulit pisang', 1),
('kulit buah', 1),
('sisa nasi', 1),
('sisa makanan', 1),
('daun', 1),
('ranting', 1),
('tulang', 1),
('ampas kopi', 1),
('kulit telur', 1),
('sayur busuk', 1),
('rumput', 1),
-- Plastik (9)
('botol plastik', 2),
('gelas plastik', 2),
('sedotan', 2),
('kantong plastik', 2),
('kresek', 2),
('kemasan', 2),
('botol', 2),
('tutup botol', 2),
('bungkus snack', 2),
-- Kertas (8)
('kardus', 3),
('hvs', 3),
('koran', 3),
('buku', 3),
('majalah', 3),
('kertas', 3),
('amplop', 3),
('kotak susu', 3),
-- B3 (9)
('baterai', 4),
('lampu', 4),
('obat', 4),
('cat', 4),
('pestisida', 4),
('aki', 4),
('termometer', 4),
('sprai', 4),
('tinta printer', 4),
-- Residu (8)
('tisu', 5),
('popok', 5),
('puntung', 5),
('rokok', 5),
('keramik', 5),
('pembalut', 5),
('pecahan', 5),
('masker bekas', 5),
-- Elektronik (9)
('charger', 6),
('kabel', 6),
('headset', 6),
('earphone', 6),
('mouse', 6),
('hp', 6),
('handphone', 6),
('keyboard', 6),
('powerbank', 6);

INSERT INTO `quiz_questions` (`id`, `question`, `option_a`, `option_b`, `option_c`, `answer_index`, `explanation`, `sort_order`, `is_active`) VALUES
(1, 'Kulit pisang masuk ke mana?', 'Organik', 'Residu', 'Plastik', 0, 'Kulit pisang bahan alami dan bisa jadi kompos.', 1, 1),
(2, 'Baterai bekas termasuk jenis apa?', 'Elektronik biasa', 'B3', 'Residu', 1, 'Baterai mengandung logam berbahaya, jadi masuk B3.', 2, 1),
(3, 'Tempat sampah warna biru untuk apa?', 'Kertas', 'Organik', 'B3', 0, 'Biru untuk kertas: kardus, koran, buku bekas.', 3, 1),
(4, 'Popok bekas masuk ke mana?', 'Plastik', 'Residu', 'Kertas', 1, 'Popok sulit didaur ulang, jadi masuk Residu.', 4, 1),
(5, 'Kabel charger rusak masuk ke mana?', 'Elektronik', 'Organik', 'Plastik', 0, 'Kabel dan charger masuk e-waste.', 5, 1);

-- Data contoh awal untuk demonstrasi Dashboard Admin & Analitik SDGs
INSERT INTO `reports` (`waste_type`, `location`, `notes`, `status`, `created_at`) VALUES
('Plastik', 'Jl. HR Boenyamin, Grendeng (dekat gerbang Unsoed)', 'Tumpukan kantong kresek dan botol plastik di tepi selokan sejak 2 hari lalu.', 'baru', DATE_SUB(NOW(), INTERVAL 2 HOUR)),
('Campuran', 'Jl. Kampus, Karangwangkal, Purwokerto Utara', 'Sampah rumah tangga menumpuk di dekat pos ronda RT 03/RW 02.', 'diproses', DATE_SUB(NOW(), INTERVAL 1 DAY)),
('B3', 'Jl. Prof. Dr. Boenyamin, Dukuhwaluh', 'Terdapat pecahan lampu neon dan beberapa baterai bekas di lahan kosong.', 'baru', DATE_SUB(NOW(), INTERVAL 2 DAY)),
('Organik', 'Taman Kota Andhang Pangrenan, Purwokerto Selatan', 'Tumpukan daun kering dan sisa makanan di area parkir selatan.', 'selesai', DATE_SUB(NOW(), INTERVAL 4 DAY));

INSERT INTO `search_misses` (`query_text`, `hit_count`, `status`) VALUES
('styrofoam', 7, 'pending'),
('minyak jelantah', 5, 'pending'),
('kaleng soda', 3, 'pending');

INSERT INTO `quiz_attempts` (`score`, `total_questions`, `created_at`) VALUES
(5, 5, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(4, 5, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(4, 5, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(3, 5, DATE_SUB(NOW(), INTERVAL 18 HOUR)),
(5, 5, DATE_SUB(NOW(), INTERVAL 8 HOUR)),
(4, 5, DATE_SUB(NOW(), INTERVAL 3 HOUR));
