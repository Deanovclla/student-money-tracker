<?php
// config.php - Konfigurasi Koneksi Database & Sesi
// Mendukung konfigurasi Environment Variables dengan fallback default XAMPP/Localhost

$host = getenv('DB_HOST') ?: "localhost";
$user = getenv('DB_USER') ?: "root";
$pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : "";
$db   = getenv('DB_NAME') ?: "keuangan_mahasiswa";
$port = getenv('DB_PORT') ? (int)getenv('DB_PORT') : 3306;

// Menetapkan koneksi database MySQL
$conn = mysqli_connect($host, $user, $pass, $db, $port);

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Set charset utf8mb4 untuk mendukung karakter internasional & emoji
mysqli_set_charset($conn, "utf8mb4");

// Inisialisasi sesi jika belum aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
