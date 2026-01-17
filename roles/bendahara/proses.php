<?php
/**
 * File: roles/bendahara/proses.php
 * Deskripsi: Halaman konfirmasi dan pemrosesan pencairan dana oleh Bendahara.
 */

check_role(['bendahara']);

// --- LOGIKA PEMROSESAN FORMULIR ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cairkan_dana'])) {
    $id_pengajuan = isset($_POST['id_pengajuan']) ? (int)$_POST['id_pengajuan'] : 0;
    
    // Siapkan query untuk update status pengajuan menjadi 'Dana Cair'
    $stmt = $conn->prepare("UPDATE pengajuan SET status = 'Dana Cair' WHERE id_pengajuan = ?");
    
    if ($stmt === false) {
        // Handle error: Gagal menyiapkan query
        redirect('index.php?page=riwayat&error=db_prepare_gagal&debug=' . urlencode(mysqli_error($conn)));
    }
    
    $stmt->bind_param("i", $id_pengajuan);
    
    if ($stmt->execute()) {
        // Redirect ke halaman dashboard dengan pesan sukses
        redirect('index.php?page=dashboard&status=pencairan_sukses');
    } else {
        // Redirect dengan pesan error jika eksekusi gagal
        redirect('index.php?page=proses&id=' . $id_pengajuan . '&error=pencairan_gagal');
    }
}
// --- AKHIR LOGIKA PEMROSESAN FORMULIR ---


$id_pengajuan = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Ambil data pengajuan untuk ditampilkan
$stmt = $conn->prepare(
    "SELECT p.id_pengajuan, p.nama_kegiatan, p.status, p.dana_diajukan, u.nama_lengkap AS nama_ormawa 
     FROM pengajuan p JOIN users u ON p.id_user_ormawa = u.id_user 
     WHERE p.id_pengajuan = ?"
);
$stmt->bind_param("i", $id_pengajuan);
$stmt->execute();
$pengajuan = $stmt->get_result()->fetch_assoc();

// Jika pengajuan tidak ditemukan
if (!$pengajuan) {
    echo "<div class='alert alert-danger'>Pengajuan tidak ditemukan.</div>";
    exit;
}

// Cek otorisasi: Hanya proposal dengan status 'Diajukan ke Bendahara' yang boleh diproses
if ($pengajuan['status'] !== 'Diajukan ke Bendahara') {
    echo "<div class='alert alert-warning'>Proposal ini tidak dalam status yang valid untuk diproses. Status saat ini: <strong>" . htmlspecialchars($pengajuan['status']) . "</strong></div>";
    exit;
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Konfirmasi Pencairan Dana</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="index.php?page=dashboard">Dashboard Bendahara</a></li>
        <li class="breadcrumb-item active">Konfirmasi</li>
    </ol>
    
    <?php if (isset($_GET['error']) && $_GET['error'] === 'pencairan_gagal'): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Maaf!</strong> Proses pencairan gagal. Silakan coba lagi.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header bg-success text-white">
            <h4><i class="bi bi-exclamation-triangle-fill me-1"></i> Mohon Konfirmasi</h4>
        </div>
        <div class="card-body">
            <p>Anda akan memproses pencairan dana untuk kegiatan:</p>
            <h4 class="text-primary"><?php echo htmlspecialchars($pengajuan['nama_kegiatan']); ?></h4>
            <table class="table table-bordered mt-3">
                <tr>
                    <th style="width: 30%;">Oleh</th>
                    <td><?php echo htmlspecialchars($pengajuan['nama_ormawa']); ?></td>
                </tr>
                <tr>
                    <th>Sebesar</th>
                    <td><strong>Rp <?php echo number_format($pengajuan['dana_diajukan'], 0, ',', '.'); ?></strong></td>
                </tr>
            </table>

            <div class="alert alert-warning mt-4">
                <strong>Perhatian!</strong> Pastikan Anda sudah melakukan transfer dana sebelum melanjutkan. Tindakan ini tidak dapat diurungkan.
            </div>

            <form method="POST" action="index.php?page=proses">
                <input type="hidden" name="id_pengajuan" value="<?php echo $id_pengajuan; ?>">
                <div class="d-flex justify-content-end gap-2">
                    <a href="index.php?page=dashboard" class="btn btn-secondary">Batal</a>
                    <button type="submit" name="cairkan_dana" class="btn btn-success">
                        <i class="bi bi-check-circle-fill me-1"></i> Ya, Konfirmasi Pencairan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
