-- ========================================================
-- Database Schema: Student Money Tracker (keuangan_mahasiswa)
-- Designed for MySQL / MariaDB (XAMPP / Production)
-- ========================================================

CREATE DATABASE IF NOT EXISTS `keuangan_mahasiswa` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `keuangan_mahasiswa`;

-- --------------------------------------------------------
-- 1. Table structure for table `users`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) DEFAULT NULL,
  `password` VARCHAR(255) NOT NULL,
  `monthly_budget` DECIMAL(15,2) NOT NULL DEFAULT 1000000.00,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 2. Table structure for table `transactions`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `transactions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `transaction_date` DATE NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  `category` VARCHAR(50) NOT NULL DEFAULT '-',
  `type` ENUM('Income', 'Pengeluaran', 'Tabungan') NOT NULL,
  `amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_user_date` (`user_id`, `transaction_date`),
  CONSTRAINT `fk_transactions_user` 
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 3. Seed Initial Dummy Data for Testing
-- Default user: demo / password123
-- --------------------------------------------------------
INSERT INTO `users` (`id`, `username`, `email`, `password`, `monthly_budget`) VALUES
(1, 'mahasiswa_demo', 'demo@student.ac.id', '$2y$10$eA0o6lJb9eS2wA4ZrqrE3.Ocl5qFq5lC4UjK.Z5W4uU4b5g1s7U3O', 1000000.00)
ON DUPLICATE KEY UPDATE `username` = `username`;

INSERT INTO `transactions` (`user_id`, `transaction_date`, `description`, `category`, `type`, `amount`) VALUES
(1, '2026-11-01', 'Kiriman Orang Tua Awal Bulan', '-', 'Income', 1000000.00),
(1, '2026-11-02', 'Beli Buku Pemrograman Web', 'Pendidikan', 'Pengeluaran', 120000.00),
(1, '2026-11-03', 'Makan Siang Kantin Kampus', 'Kebutuhan Pokok', 'Pengeluaran', 25000.00),
(1, '2026-11-04', 'Tabungan Darurat Pekan 1', '-', 'Tabungan', 50000.00),
(1, '2026-11-05', 'Beli Obat & Vitamin', 'Kesehatan', 'Pengeluaran', 35000.00),
(1, '2026-11-06', 'Nonton Bioskop Weekend', 'Hiburan', 'Pengeluaran', 45000.00);
