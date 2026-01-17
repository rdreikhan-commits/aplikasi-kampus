<?php
/**
 * File: revisi_lpj.php
 * Deskripsi: Halaman untuk merevisi LPJ yang ditolak.
 */
check_role(['ormawa', 'bem', 'bpm']);

$id_pengajuan = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];

if ($id_pengajuan === 0) {
    echo "<div class='alert alert-danger'>ID Pengajuan tidak valid.</div>";
    return;
}

// Ambil data pengajuan untuk validasi
$stmt = $conn->prepare("SELECT * FROM pengajuan WHERE id_pengajuan = ? AND id_user_ormawa = ?");
$stmt->bind_param("ii", $id_pengajuan, $user_id);
$stmt->execute();
$pengajuan = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pengajuan) {
    echo "<div class='alert alert-danger'>Pengajuan tidak ditemukan atau Anda tidak memiliki izin.</div>";
    return;
}

// Halaman ini hanya bisa diakses jika status proposal adalah 'LPJ Ditolak BKKH'
$allowed_status = 'LPJ Ditolak bkkh';
if (trim(strtolower($pengajuan['status'])) !== strtolower($allowed_status)) {
    echo "<div class='container-fluid px-4 mt-4'><div class='alert alert-warning'>Anda tidak dapat merevisi LPJ untuk proposal ini. Status saat ini: <strong>" . htmlspecialchars($pengajuan['status']) . "</strong></div></div>";
    return;
}

// Ambil catatan penolakan terakhir dari kolom catatan_revisi
$catatan_penolakan = $pengajuan['catatan_revisi'] ?? '';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Revisi Laporan Pertanggungjawaban (LPJ)</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="index.php?page=riwayat">Riwayat</a></li>
        <li class="breadcrumb-item active">Revisi LPJ</li>
    </ol>

    <?php if (!empty($catatan_penolakan)): ?>
    <div class="alert alert-danger" role="alert">
        <h4 class="alert-heading"><i class="bi bi-exclamation-triangle-fill"></i> Catatan Penolakan dari BKKH</h4>
        <p>LPJ Anda untuk kegiatan "<strong><?php echo htmlspecialchars($pengajuan['nama_kegiatan']); ?></strong>" perlu direvisi. Mohon perhatikan catatan berikut dan unggah kembali file LPJ yang sudah diperbaiki.</p>
        <hr>
        <p class="mb-0 fst-italic">"<?php echo htmlspecialchars($catatan_penolakan); ?>"</p>
    </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-file-earmark-arrow-up-fill me-1"></i>
            Upload File Revisi LPJ
        </div>
        <div class="card-body">
            <?php
            if (isset($_GET['error'])) {
                $error_map = [
                    'file_kosong' => 'File LPJ wajib diunggah!',
                    'bukan_pdf' => 'Maaf, hanya file PDF yang diizinkan.',
                    'file_kebesaran' => 'Ukuran file tidak boleh lebih dari 5MB.',
                    'upload_gagal' => 'Terjadi kesalahan saat mengunggah file.',
                    'db_gagal' => 'Gagal memperbarui database. Silakan coba lagi.'
                ];
                $error_msg = $error_map[$_GET['error']] ?? 'Terjadi kesalahan tidak diketahui.';
                echo '<div class="alert alert-danger">' . $error_msg . '</div>';
            }
            ?>
            <form method="POST" action="index.php?page=revisi_lpj&id=<?php echo $id_pengajuan; ?>" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="file_lpj" class="form-label">Pilih File LPJ Baru (PDF, maks 5MB)</label>
                    <input class="form-control" type="file" id="file_lpj" name="file_lpj" accept=".pdf" required>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-send-fill me-1"></i> 
                    Kirim Ulang Revisi
                </button>
                <a href="index.php?page=riwayat" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>

