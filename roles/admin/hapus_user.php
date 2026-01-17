<?php
/**
 * File: roles/admin/hapus_user.php
 * Deskripsi: Memproses permintaan untuk menghapus pengguna dari database.
 */

// Memeriksa peran pengguna
check_role(['bkh']);

// Mengambil ID pengguna yang akan dihapus dari URL
$id_user_hapus = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Validasi Keamanan: Mencegah BKKH menghapus akunnya sendiri
if ($id_user_hapus == $_SESSION['user_id']) {
    echo "<script>alert('Anda tidak dapat menghapus akun Anda sendiri!'); window.location.href='index.php?page=manage_users';</script>";
    exit;
}

// Proses Penghapusan
$stmt = $conn->prepare("DELETE FROM users WHERE id_user = ?");
$stmt->bind_param("i", $id_user_hapus);

// Menjalankan query dan memberikan notifikasi
if ($stmt->execute()) {
    echo "<script>alert('Pengguna berhasil dihapus.'); window.location.href='index.php?page=manage_users';</script>";
} else {
    echo "<script>alert('Gagal menghapus pengguna.'); window.location.href='index.php?page=manage_users';</script>";
}
$stmt->close();
exit;

?>
