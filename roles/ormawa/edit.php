<?php
/**
 * File: edit.php
 * Deskripsi: Halaman untuk merevisi proposal yang ditolak, dengan perbaikan.
 */
check_role(['ormawa', 'bem', 'bpm']);

$id_pengajuan = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];

if ($id_pengajuan === 0) {
    echo "<div class='alert alert-danger'>ID Pengajuan tidak valid.</div>";
    return;
}

// Ambil data pengajuan untuk validasi dan mengisi form
$stmt = $conn->prepare("SELECT * FROM pengajuan WHERE id_pengajuan = ? AND id_user_ormawa = ?");
$stmt->bind_param("ii", $id_pengajuan, $user_id);
$stmt->execute();
$pengajuan = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pengajuan) {
    echo "<div class='alert alert-danger'>Pengajuan tidak ditemukan atau Anda tidak memiliki izin.</div>";
    return;
}

// --- PERBAIKAN: Memberikan pesan yang lebih jelas dan mencegah akses ulang ---
// Pastikan hanya proposal yang ditolak yang bisa direvisi
if (strpos(strtolower($pengajuan['status']), 'ditolak') === false) {
    // Cek apakah proposal ini baru saja direvisi (statusnya kembali ke awal)
    if ($pengajuan['status'] === 'Diajukan Ke BEM' || $pengajuan['status'] === 'Verifikasi BKKH') {
         echo "<div class='container-fluid px-4 mt-4'><div class='alert alert-success'><h4 class='alert-heading'>Revisi Telah Terkirim!</h4><p>Proposal ini sudah berhasil Anda revisi dan sedang dalam proses verifikasi kembali. Anda akan diarahkan ke halaman riwayat.</p></div></div>";
         echo '<meta http-equiv="refresh" content="3;url=index.php?page=riwayat-pengajuan">';
    } else {
        // Tampilkan pesan umum jika statusnya lain
        echo "<div class='container-fluid px-4 mt-4'><div class='alert alert-warning'>Proposal ini tidak dapat direvisi karena statusnya saat ini adalah '<strong>" . htmlspecialchars($pengajuan['status']) . "</strong>', bukan 'Ditolak'.</div></div>";
    }
    return; // Hentikan eksekusi sisa halaman
}

// PERBAIKAN: Mengambil catatan revisi langsung dari tabel 'pengajuan' kolom 'catatan_revisi'
$catatan_penolakan = $pengajuan['catatan_revisi'] ?? 'Tidak ada catatan spesifik yang ditemukan untuk penolakan ini.';

?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Revisi Pengajuan Proposal</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="index.php?page=riwayat-pengajuan">Riwayat Pengajuan</a></li>
        <li class="breadcrumb-item active">Revisi Proposal</li>
    </ol>

    <div class="alert alert-danger" role="alert">
        <h4 class="alert-heading"><i class="bi bi-exclamation-triangle-fill"></i> Catatan Penolakan</h4>
        <p>Proposal Anda untuk kegiatan "<strong><?php echo htmlspecialchars($pengajuan['nama_kegiatan']); ?></strong>" perlu direvisi. Mohon perhatikan catatan berikut dan ajukan kembali.</p>
        <hr>
        <!-- Variabel ini sekarang berisi data dari kolom 'catatan_revisi' -->
        <p class="mb-0 fst-italic"><b>CATATAN. </b>"<?php echo htmlspecialchars($catatan_penolakan); ?>"</p>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-pencil-square me-1"></i>
            Formulir Revisi Proposal
        </div>
        <div class="card-body">
            <?php
            if (isset($_GET['error'])) {
                $error_map = [
                    'bukan_pdf' => 'Maaf, hanya file PDF yang diizinkan.',
                    'file_kebesaran' => 'Ukuran file tidak boleh lebih dari 5MB.',
                    'upload_gagal' => 'Terjadi kesalahan saat mengunggah file.',
                    'db_gagal' => 'Gagal memperbarui database. Silakan coba lagi.'
                ];
                $error_msg = $error_map[$_GET['error']] ?? 'Terjadi kesalahan tidak diketahui.';
                echo '<div class="alert alert-danger">' . $error_msg . '</div>';
            }
            ?>
            <form method="POST" action="index.php?page=edit&id=<?php echo $id_pengajuan; ?>" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="nama_kegiatan" class="form-label">Nama Kegiatan</label>
                    <input type="text" class="form-control" id="nama_kegiatan" name="nama_kegiatan" value="<?php echo htmlspecialchars($pengajuan['nama_kegiatan']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="tanggal_pengajuan" class="form-label">Tanggal Pelaksanaan</label>
                    <input type="date" class="form-control" id="tanggal_pengajuan" name="tanggal_pengajuan" value="<?php echo htmlspecialchars($pengajuan['tanggal_pengajuan']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="dana_diajukan" class="form-label">Dana Diajukan (Rp)</label>
                    <input type="text" class="form-control" id="dana_diajukan" name="dana_diajukan" value="<?php echo number_format($pengajuan['dana_diajukan'], 0, ',', '.'); ?>" required onkeyup="formatRupiah(this)">
                </div>
                <div class="mb-3">
                    <label for="file_proposal" class="form-label">Ubah File Proposal (PDF, maks 5MB)</label>
                    <input class="form-control" type="file" id="file_proposal" name="file_proposal" accept=".pdf">
                    <div class="form-text">Kosongkan jika tidak ingin mengubah file proposal yang sudah ada. File saat ini: <a href="uploads/proposal/<?php echo htmlspecialchars($pengajuan['file_proposal']); ?>" target="_blank"><?php echo htmlspecialchars($pengajuan['file_proposal']); ?></a></div>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-send-fill me-1"></i> Kirim Ulang Revisi
                </button>
                <a href="index.php?page=riwayat-pengajuan" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>

<script>
function formatRupiah(input) {
    let value = input.value;
    value = value.replace(/[^,\d]/g, '').toString();
    let split = value.split(',');
    let sisa = split[0].length % 3;
    let rupiah = split[0].substr(0, sisa);
    let ribuan = split[0].substr(sisa).match(/\d{3}/gi);

    if (ribuan) {
        let separator = sisa ? '.' : '';
        rupiah += separator + ribuan.join('.');
    }
    
    rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
    input.value = rupiah;
}
</script>

