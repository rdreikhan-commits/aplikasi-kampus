<?php
/**
 * File: upload_lpj.php
 * Deskripsi: Menangani pengunggahan file LPJ dan memperbarui status pengajuan.
 */

// Pengecekan peran pengguna
check_role(['ormawa', 'bem', 'bpm']);

// Mengambil ID pengajuan dari URL
$id_pengajuan = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// 1. Logika untuk memproses pengunggahan LPJ
// SARAN: Untuk arsitektur yang lebih baik, logika POST ini sebaiknya dipindahkan
// ke file index.php agar konsisten dengan halaman lain.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $file_lpj = $_FILES['file_lpj'] ?? null;
    $target_dir = "uploads/lpj/";
    
    // Validasi file yang diunggah
    if (!isset($file_lpj) || $file_lpj['error'] !== UPLOAD_ERR_OK) {
        header("Location: index.php?page=upload_lpj&id=$id_pengajuan&error=upload_gagal");
        exit;
    }

    $file_name = basename($file_lpj["name"]);
    $file_type = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    if ($file_type != "pdf") {
        header("Location: index.php?page=upload_lpj&id=$id_pengajuan&error=bukan_pdf");
        exit;
    }
    
    if ($file_lpj["size"] > 5000000) { // 5 MB
        header("Location: index.php?page=upload_lpj&id=$id_pengajuan&error=file_kebesaran");
        exit;
    }

    // Buat nama file unik untuk menghindari tumpang tindih
    $new_file_name = 'lpj_' . uniqid() . '_' . $user_id . '.' . $file_type;
    $target_file = $target_dir . $new_file_name;

    // Pastikan direktori ada
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    if (move_uploaded_file($file_lpj["tmp_name"], $target_file)) {
        // File berhasil diunggah, sekarang update database
        $new_status = 'LPJ Diajukan';
        $keterangan = 'LPJ telah diunggah kembali (direvisi) dan diajukan ke BKKH';

        // Update status pengajuan dan bersihkan catatan revisi sebelumnya
        $stmt_update = $conn->prepare("UPDATE pengajuan SET status = ?, file_lpj = ?, catatan_revisi = NULL WHERE id_pengajuan = ? AND id_user_ormawa = ?");
        $stmt_update->bind_param("ssii", $new_status, $new_file_name, $id_pengajuan, $user_id);
        
        if ($stmt_update->execute()) {
            // Tambahkan histori baru
            add_history($conn, $id_pengajuan, $user_id, $new_status, $keterangan);
            
            // Berhasil, redirect ke halaman riwayat dengan pesan sukses
            header("Location: index.php?page=riwayat&status=revisi_lpj_sukses");
            exit;
        } else {
            // Gagal, hapus file yang sudah diunggah dan redirect dengan pesan error
            unlink($target_file);
            header("Location: index.php?page=upload_lpj&id=$id_pengajuan&error=db_gagal");
            exit;
        }
    } else {
        // Gagal mengunggah file
        header("Location: index.php?page=upload_lpj&id=$id_pengajuan&error=upload_gagal");
        exit;
    }
}

// 2. Tampilkan formulir unggah LPJ (jika belum POST)
$stmt = $conn->prepare("SELECT nama_kegiatan, status, catatan_revisi FROM pengajuan WHERE id_pengajuan = ? AND id_user_ormawa = ?");
$stmt->bind_param("ii", $id_pengajuan, $user_id);
$stmt->execute();
$pengajuan = $stmt->get_result()->fetch_assoc();

if (!$pengajuan) {
    echo "<div class='alert alert-danger'>Pengajuan tidak ditemukan atau bukan milik Anda.</div>";
    exit;
}

// =================================================================
// INI BAGIAN YANG DIPERBAIKI
// =================================================================
// Cek otorisasi: Izinkan upload jika status 'Dana Cair' ATAU 'LPJ Ditolak BKKH'
$allowed_statuses = ['Dana Cair', 'LPJ Ditolak BKKH'];

if (!in_array(trim($pengajuan['status']), $allowed_statuses)) {
    echo "<div class='alert alert-warning'>Anda tidak dapat mengunggah LPJ untuk proposal ini. Status saat ini: <strong>" . htmlspecialchars($pengajuan['status']) . "</strong></div>";
    exit;
}
// =================================================================
// AKHIR BAGIAN PERBAIKAN
// =================================================================

$page_title = ($pengajuan['status'] === 'LPJ Ditolak BKKH') ? 'Revisi Laporan Pertanggungjawaban (LPJ)' : 'Upload Laporan Pertanggungjawaban (LPJ)';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $page_title; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="index.php?page=riwayat">Riwayat</a></li>
        <li class="breadcrumb-item active"><?php echo ($pengajuan['status'] === 'LPJ Ditolak BKKH') ? 'Revisi LPJ' : 'Upload LPJ'; ?></li>
    </ol>

    <div class="card shadow-sm">
        <div class="card-header">
            <i class="bi bi-cloud-arrow-up-fill me-1"></i>
            Upload LPJ untuk: <strong><?php echo htmlspecialchars($pengajuan['nama_kegiatan']); ?></strong>
        </div>
        <div class="card-body">
            <?php if ($pengajuan['status'] === 'LPJ Ditolak BKKH' && !empty($pengajuan['catatan_revisi'])): ?>
                <div class="alert alert-danger">
                    <h5 class="alert-heading"><i class="bi bi-exclamation-triangle-fill"></i> LPJ Ditolak!</h5>
                    <p>LPJ Anda sebelumnya ditolak dengan catatan sebagai berikut. Mohon perbaiki LPJ Anda dan unggah kembali versi terbarunya.</p>
                    <hr>
                    <p class="mb-0"><strong>Catatan dari BKKH:</strong> <?php echo nl2br(htmlspecialchars($pengajuan['catatan_revisi'])); ?></p>
                </div>
            <?php endif; ?>

            <form method="POST" action="index.php?page=upload_lpj&id=<?php echo $id_pengajuan; ?>" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="file_lpj" class="form-label">Pilih File LPJ Baru (PDF, maks 5MB)</label>
                    <input class="form-control" type="file" id="file_lpj" name="file_lpj" accept=".pdf" required>
                    <div class="form-text">LPJ akan dikirimkan kembali ke BKKH untuk diverifikasi.</div>
                </div>
                <div class="d-flex justify-content-end gap-2">
                    <a href="index.php?page=riwayat" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send-fill me-1"></i> Kirim LPJ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
