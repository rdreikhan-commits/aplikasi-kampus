<?php
/**
 * File: config.php
 * Deskripsi: Pusat konfigurasi, inisialisasi, dan bootstrap aplikasi.
 */

// 1. Kunci keamanan untuk mencegah file lain diakses langsung.
define('APP_RUNNING', true);

// 2. URL dasar aplikasi Anda.
define('BASE_URL', 'http://localhost:8000');

// 3. Pengaturan dan Koneksi Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Sesuaikan jika ada password
define('DB_NAME', 'db_pengajuan'); // Sesuaikan dengan nama database Anda
define('DB_PORT', 3306); // Sesuaikan dengan port MySQL Anda

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

// 4. Memuat semua fungsi bantuan dari functions.php
require_once 'functions.php';

// 5. Memulai sesi dengan aman menggunakan fungsi yang sudah kita buat.
initialize_session();
?>
