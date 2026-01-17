<?php
/**
 * File: ajukan_pencairan.php
 * Deskripsi: Halaman ini berfungsi untuk menampilkan konfirmasi dan memproses
 * pengajuan pencairan dana oleh BKKH ke bendahara.
 */

// Pastikan hanya BKKH yang bisa mengakses halaman ini.
// Asumsikan fungsi check_role() sudah didefinisikan di tempat lain.
check_role(['bkh']);

$id_pengajuan = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error_message = '';

// === BAGIAN BARU: HANDLE PENGIRIMAN FORMULIR (POST REQUEST) ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Memastikan ID pengajuan valid sebelum memproses.
    // Perbaikan: Ambil ID dari POST, bukan GET.
    $id_pengajuan_post = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    if ($id_pengajuan_post <= 0) {
        $error_message = 'ID pengajuan tidak valid. Aksi dibatalkan.';
    } else {
        // Status yang akan diubah di database.
        $status_baru = 'Diajukan ke Bendahara';

        // Persiapan query untuk UPDATE status proposal.
        // Menggunakan prepared statement untuk keamanan.
        $stmt = $conn->prepare("UPDATE pengajuan SET status = ? WHERE id_pengajuan = ? AND status = 'Disetujui WR3, Siap Diajukan ke Bendahara'");
        
        // Periksa jika prepared statement gagal.
        if ($stmt === false) {
            $error_message = 'Terjadi kesalahan pada database (prepare). Silakan hubungi admin.';
        } else {
            $stmt->bind_param("si", $status_baru, $id_pengajuan_post);
            
            // Jalankan query.
            if ($stmt->execute()) {
                // Simpan riwayat histori
                $stmt_histori = $conn->prepare("INSERT INTO histori_status (id_pengajuan, id_user, status, catatan) VALUES (?, ?, ?, ?)");
                $catatan_histori = "Pengajuan pencairan dana berhasil diteruskan ke bendahara.";
                $stmt_histori->bind_param("iiss", $id_pengajuan_post, $_SESSION['user_id'], $status_baru, $catatan_histori);
                $stmt_histori->execute();
                $stmt_histori->close();
                
                // Redirect ke halaman verifikasi dengan pesan sukses.
                header('Location: index.php?page=dashboard&status=cair_sukses');
                exit;
            } else {
                // Redirect dengan pesan error jika query gagal.
                $error_message = 'Gagal menyimpan data ke database (execute). Silakan coba lagi.';
            }
        }
    }
}
// === AKHIR BAGIAN BARU: HANDLE PENGIRIMAN FORMULIR ===

// Ambil data pengajuan untuk ditampilkan.
$stmt = $conn->prepare(
    "SELECT p.*, u.nama_lengkap AS nama_ormawa 
    FROM pengajuan p JOIN users u ON p.id_user_ormawa = u.id_user 
    WHERE p.id_pengajuan = ?"
);

// Periksa jika prepared statement gagal.
if ($stmt === false) {
    $error_message = 'Terjadi kesalahan pada database saat mengambil data.';
} else {
    $stmt->bind_param("i", $id_pengajuan);
    $stmt->execute();
    $pengajuan = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    // Perbaikan: Hanya tampilkan error "tidak ditemukan" jika tidak ada error lain.
    if (!$pengajuan && empty($error_message) && $id_pengajuan > 0) {
        $error_message = 'Pengajuan tidak ditemukan.';
    } else if ($id_pengajuan === 0) {
        // Jika tidak ada ID, set pesan error yang jelas.
        $error_message = 'Tidak ada ID pengajuan yang diberikan. Silakan kembali ke daftar pengajuan.';
    }

    if ($pengajuan) {
        // Cek otorisasi: Hanya proposal dengan status tertentu yang boleh diajukan.
        $status_valid = 'Disetujui WR3, Siap Diajukan ke Bendahara';
        if ($pengajuan['status'] !== $status_valid && empty($error_message)) {
            $error_message = 'Proposal ini tidak dalam status yang valid untuk diajukan ke bendahara. Status saat ini: <strong>' . htmlspecialchars($pengajuan['status']) . '</strong>';
        }
    }
}

// Sederhanakan penentuan dana disetujui
$dana_disetujui = $pengajuan['dana_disetujui'] ?? $pengajuan['dana_diajukan'] ?? 0;

?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Ajukan Pencairan Dana</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="index.php?page=dashboard">Dashboard</a></li>
        <li class="breadcrumb-item active">Ajukan Pencairan</li>
    </ol>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger">
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <?php if ($pengajuan && empty($error_message)): ?>
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h4><i class="bi bi-check-circle-fill me-2"></i>Konfirmasi Pengajuan Pencairan</h4>
        </div>
        <div class="card-body">
            <p>Anda akan mengajukan pencairan dana untuk proposal berikut:</p>
            <ul class="list-group list-group-flush mb-4">
                <li class="list-group-item"><strong>Nama Kegiatan:</strong> <?php echo htmlspecialchars($pengajuan['nama_kegiatan']); ?></li>
                <li class="list-group-item"><strong>Ormawa Pengaju:</strong> <?php echo htmlspecialchars($pengajuan['nama_ormawa']); ?></li>
                <li class="list-group-item"><strong>Dana Final Disetujui (WR3):</strong> <span class="fw-bold fs-5 text-success">Rp <?php echo number_format($dana_disetujui, 0, ',', '.'); ?></span></li>
            </ul>
            <p class="text-danger">Tindakan ini akan mengubah status proposal menjadi "Diajukan ke Bendahara" dan tidak dapat dibatalkan. Pastikan semua data sudah benar.</p>
            
            <form method="POST" action="index.php?page=ajukan_pencairan">
                <!-- Perbaikan: Tambahkan hidden input untuk mengirim ID melalui POST -->
                <input type="hidden" name="id" value="<?php echo $id_pengajuan; ?>">
                <div class="d-flex justify-content-end gap-2">
                    <a href="index.php?page=dashboard" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send-check-fill me-2"></i>Ya, Ajukan ke Bendahara
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>
