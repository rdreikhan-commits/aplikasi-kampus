<?php
/**
 * File: verifikasi_lpj.php
 * Deskripsi: Halaman untuk BKKH memverifikasi LPJ. Jika disetujui, status langsung Selesai.
 * --- PERBAIKAN ---
 * 1. (BARU) Menambahkan path 'uploads/lpj/' pada link file LPJ agar bisa diakses.
 */
check_role(['bkh']); // Hak akses sekarang hanya untuk BKKH

$id_pengajuan = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_pengajuan == 0) {
    redirect('index.php?page=dashboard');
}
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// 1. Logika untuk memproses form verifikasi (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'])) {
    $aksi = $_POST['aksi'];
    $catatan = $_POST['catatan'] ?? '';
    $new_status = '';
    $history_message = '';

    if ($aksi === 'tolak') {
        if (empty($catatan)) {
            header("Location: index.php?page=verifikasi_lpj&id=$id_pengajuan&error=catatan_kosong");
            exit;
        }
        $new_status = 'LPJ Ditolak BKKH';
        $history_message = 'LPJ ditolak oleh BKKH. Catatan: ' . $catatan;

    } elseif ($aksi === 'setuju') {
        $new_status = 'Selesai'; // LANGSUNG SELESAI
        $history_message = 'LPJ telah diverifikasi dan disetujui oleh BKKH. Proses pengajuan telah selesai.' . ($catatan ? ' Catatan: ' . $catatan : '');
    } else {
        header("Location: index.php?page=verifikasi_lpj&id=$id_pengajuan&error=aksi_invalid");
        exit;
    }

    // Update status dan simpan riwayat
    $stmt_update = $conn->prepare("UPDATE pengajuan SET status = ?, catatan_revisi = ? WHERE id_pengajuan = ?");
    $stmt_update->bind_param("ssi", $new_status, $catatan, $id_pengajuan);
    if ($stmt_update->execute()) {
        add_history($conn, $id_pengajuan, $user_id, $new_status, $history_message);
        header("Location: index.php?page=dashboard&status=verifikasi_lpj_sukses");
        exit;
    } else {
        header("Location: index.php?page=verifikasi_lpj&id=$id_pengajuan&error=update_gagal");
        exit;
    }
}

// 2. Tampilkan halaman verifikasi (GET)
$stmt = $conn->prepare("SELECT p.*, u.nama_lengkap AS nama_ormawa FROM pengajuan p JOIN users u ON p.id_user_ormawa = u.id_user WHERE p.id_pengajuan = ?");
$stmt->bind_param("i", $id_pengajuan);
$stmt->execute();
$pengajuan = $stmt->get_result()->fetch_assoc();

if (!$pengajuan) {
    echo "<div class='alert alert-danger'>Pengajuan tidak ditemukan.</div>";
    exit;
}

// Cek otorisasi: BKKH hanya bisa memproses status 'LPJ Diajukan'
if (trim($pengajuan['status']) !== 'LPJ Diajukan') {
    echo "<div class='alert alert-warning'>Anda tidak memiliki izin untuk memproses LPJ ini pada tahap ini. Status saat ini: <strong>" . htmlspecialchars($pengajuan['status']) . "</strong></div>";
    exit;
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Verifikasi Laporan Pertanggungjawaban (LPJ)</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="index.php?page=dashboard">Dashboard</a></li>
        <li class="breadcrumb-item active">Verifikasi LPJ</li>
    </ol>
    
    <div class="row">
        <!-- Kolom Informasi Kegiatan -->
        <div class="col-md-7">
            <div class="card shadow-sm mb-4">
                <div class="card-header"><i class="bi bi-info-circle-fill me-1"></i> Informasi Kegiatan</div>
                <div class="card-body">
                    <p><strong>Nama Kegiatan:</strong><br><?php echo htmlspecialchars($pengajuan['nama_kegiatan']); ?></p>
                    <p><strong>Ormawa Pengaju:</strong><br><?php echo htmlspecialchars($pengajuan['nama_ormawa']); ?></p>
                    <p><strong>File LPJ:</strong><br>
                        <?php if (!empty($pengajuan['file_lpj'])): ?>
                            <?php 
                            // --- PERBAIKAN LINK LPJ ---
                            // Asumsikan LPJ disimpan di folder 'uploads/lpj/'
                            // Dan database hanya menyimpan nama filenya
                            $filePath = 'uploads/lpj/' . basename($pengajuan['file_lpj']); 
                            ?>
                            <a href="<?php echo htmlspecialchars($filePath); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i> Lihat LPJ
                            </a>
                        <?php else: ?>
                            <span class="text-muted">File LPJ tidak tersedia.</span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Kolom Form Tindakan -->
        <div class="col-md-5">
            <div class="card shadow-sm mb-4">
                <div class="card-header"><i class="bi bi-check2-square me-1"></i> Form Tindakan Verifikasi</div>
                <div class="card-body">
                    <?php
                    if (isset($_GET['error'])) {
                        $error_map = [
                            'catatan_kosong' => 'Catatan wajib diisi jika menolak LPJ!',
                            'update_gagal' => 'Gagal memperbarui status pengajuan.',
                            'aksi_invalid' => 'Aksi yang dikirim tidak valid.'
                        ];
                        $error_msg = $error_map[$_GET['error']] ?? 'Terjadi kesalahan tidak diketahui.';
                        echo '<div class="alert alert-danger">' . $error_msg . '</div>';
                    }
                    ?>
                    
                    <form method="POST" action="index.php?page=verifikasi_lpj&id=<?php echo $id_pengajuan; ?>">
                        <div class="mb-3">
                            <label for="catatan" class="form-label">Catatan (Wajib diisi jika menolak)</label>
                            <textarea name="catatan" id="catatan" class="form-control" rows="4"></textarea>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" name="aksi" value="setuju" class="btn btn-success"><i class="bi bi-check-circle-fill"></i> Setujui & Selesaikan</button>
                            <button type="submit" name="aksi" value="tolak" class="btn btn-danger"><i class="bi bi-x-circle-fill"></i> Tolak LPJ</button>
                            <a href="index.php?page=dashboard" class="btn btn-secondary">Kembali</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
