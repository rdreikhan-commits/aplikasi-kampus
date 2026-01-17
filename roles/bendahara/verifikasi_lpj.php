<?php
/**
 * File: roles/bendahara/verifikasi_lpj.php
 * Deskripsi: Halaman untuk Bendahara memverifikasi Laporan Pertanggungjawaban (LPJ).
 */

// Memeriksa peran pengguna
check_role(['bendahara']);

// Mengambil ID pengajuan dari URL dan ID pengguna dari session
$id_pengajuan = isset($_GET['id']) ? intval($_GET['id']) : 0;
$id_user = $_SESSION['user_id'];

// --- Proses Form Verifikasi Jika Disubmit ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $catatan = sanitize($conn, $_POST['catatan']);
    $aksi = $_POST['aksi']; // 'selesai' atau 'tolak'
    
    $status_baru = '';
    $catatan_histori = '';

    if ($aksi == 'selesai') {
        $status_baru = 'Selesai';
        $catatan_histori = 'LPJ disetujui dan pengajuan dianggap selesai.';
    } else { // Jika aksi 'tolak'
        if(empty($catatan)){
             // Catatan wajib diisi jika menolak LPJ
             echo "<script>alert('Catatan wajib diisi jika menolak LPJ!');</script>";
        } else {
            $status_baru = 'LPJ Ditolak';
            $catatan_histori = 'LPJ ditolak, perlu revisi.';
        }
    }

    // Lanjutkan update ke database hanya jika status baru sudah ditentukan
    if(!empty($status_baru)){
        // Query UPDATE juga akan mengosongkan catatan revisi jika LPJ disetujui
        $stmt = $conn->prepare("UPDATE pengajuan SET status = ?, catatan_revisi = ? WHERE id_pengajuan = ?");
        $stmt->bind_param("ssi", $status_baru, $catatan, $id_pengajuan);
        if ($stmt->execute()) {
            add_history($conn, $id_pengajuan, $id_user, $status_baru, $catatan_histori . " Catatan: " . $catatan);
            echo "<script>alert('Verifikasi LPJ berhasil!'); window.location.href='index.php?page=dashboard';</script>";
        } else {
            echo "<script>alert('Gagal menyimpan aksi verifikasi!');</script>";
        }
        $stmt->close();
    }
}

// --- Mengambil Data Pengajuan untuk Ditampilkan ---
// Query hanya akan mengambil data jika statusnya 'LPJ Diajukan'
$query = "SELECT p.*, u.nama_lengkap AS nama_ormawa FROM pengajuan p JOIN users u ON p.id_user_ormawa = u.id_user WHERE p.id_pengajuan = ? AND p.status = 'LPJ Diajukan'";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_pengajuan);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

// Jika data tidak ditemukan, kembali ke dashboard
if (!$data) {
    echo "<script>alert('Data LPJ tidak ditemukan atau status tidak valid.'); window.location.href='index.php?page=dashboard';</script>";
    exit;
}
?>
<div class="container-fluid">
    <h3>Verifikasi Laporan Pertanggungjawaban (LPJ)</h3>
    <div class="row mt-4">
        <!-- Kolom Kiri: Detail Pengajuan -->
        <div class="col-md-7">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5>Informasi Kegiatan</h5>
                </div>
                <div class="card-body">
                    <p><strong>Judul Kegiatan:</strong><br><?php echo htmlspecialchars($data['judul_kegiatan']); ?></p>
                    <p><strong>Ormawa Pengaju:</strong><br><?php echo htmlspecialchars($data['nama_ormawa']); ?></p>
                    <p><strong>Nominal Dicairkan:</strong><br>Rp <?php echo number_format($data['nominal_pengajuan'], 0, ',', '.'); ?></p>
                    <p><strong>File LPJ:</strong><br>
                        <a href="uploads/lpj/<?php echo $data['file_lpj']; ?>" target="_blank" class="btn btn-outline-success btn-sm">
                            <i class="bi bi-download"></i> Unduh LPJ
                        </a>
                    </p>
                </div>
            </div>
        </div>
        <!-- Kolom Kanan: Form Tindakan -->
        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5>Form Tindakan Verifikasi LPJ</h5>
                </div>
                <div class="card-body">
                    <form action="index.php?page=verifikasi_lpj&id=<?php echo $id_pengajuan; ?>" method="POST">
                        <div class="mb-3">
                            <label for="catatan" class="form-label">Catatan (Wajib diisi jika menolak)</label>
                            <textarea class="form-control" id="catatan" name="catatan" rows="5"></textarea>
                        </div>
                        <div class="d-grid gap-2">
                             <button type="submit" name="aksi" value="selesai" class="btn btn-success"><i class="bi bi-check-circle"></i> Setujui & Selesaikan</button>
                             <button type="submit" name="aksi" value="tolak" class="btn btn-danger"><i class="bi bi-x-circle"></i> Tolak LPJ</button>
                             <a href="index.php?page=dashboard" class="btn btn-secondary">Kembali</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
