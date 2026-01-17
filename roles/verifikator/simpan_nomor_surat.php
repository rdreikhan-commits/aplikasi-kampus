<?php
/**
 * File: simpan_nomor_surat.php
 * Deskripsi: Menyimpan nomor surat ke database dan mencegah penimpaan.
 */

// GANTI BAGIAN INI DENGAN KODE KONEKSI DATABASE ANDA
// Contoh: require 'config/db.php';
//       require 'functions.php';
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "db_pengajuan";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// -----------------------------------------------------------

// Hanya proses jika metodenya POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Validasi input dasar
    if (isset($_POST['id_pengajuan']) && is_numeric($_POST['id_pengajuan']) && !empty($_POST['no_surat'])) {
        
        $id_pengajuan = (int)$_POST['id_pengajuan'];
        $no_surat = trim($_POST['no_surat']); // Menghapus spasi di awal dan akhir

        // Keamanan: Gunakan prepared statement untuk mencegah SQL Injection.
        // Klausa "AND no_surat IS NULL" adalah kunci agar nomor yang sudah ada tidak bisa ditimpa.
        $stmt = $conn->prepare("UPDATE pengajuan SET no_surat = ? WHERE id_pengajuan = ? AND no_surat IS NULL");
        
        if ($stmt === false) {
            die("Error preparing statement: " . $conn->error);
        }

        $stmt->bind_param("si", $no_surat, $id_pengajuan);

        if ($stmt->execute()) {
            // Jika berhasil, alihkan kembali ke halaman cetak surat
            header("Location: cetak_surat.php?id=" . $id_pengajuan);
            exit();
        } else {
            // Jika gagal
            die("Error: Gagal menyimpan nomor surat. " . $stmt->error);
        }
        $stmt->close();

    } else {
        die("Error: Data yang dikirim tidak lengkap atau tidak valid.");
    }
} else {
    // Jika diakses langsung tanpa metode POST, alihkan ke halaman utama
    header("Location: index.php");
    exit();
}

$conn->close();
?>
