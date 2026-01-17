<?php
/**
 * File: atur_saldo.php
 * Deskripsi: Halaman untuk BKKH mengatur saldo pengguna.
 */
check_role(['bkh']);
$id_pengguna = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Ambil data Pengguna yang akan diubah
$pengguna = null;
if ($id_pengguna > 0) {
    $stmt = $conn->prepare("SELECT id_user, nama_lengkap, saldo FROM users WHERE id_user = ? AND role IN ('ormawa', 'bem', 'bpm')");
    $stmt->bind_param("i", $id_pengguna);
    $stmt->execute();
    $pengguna = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if (!$pengguna) {
    echo "<div class='alert alert-danger'>Pengguna tidak ditemukan.</div>";
    exit();
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Atur Saldo Pengguna</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="index.php?page=manage_saldo">Manajemen Saldo</a></li>
        <li class="breadcrumb-item active">Atur Saldo</li>
    </ol>

    <div class="card shadow-sm">
        <div class="card-header">
            <h5>Mengatur Saldo untuk: <?php echo htmlspecialchars($pengguna['nama_lengkap']); ?></h5>
        </div>
        <div class="card-body">
            <?php
            if (isset($_GET['error'])) {
                $error_msg = '';
                if ($_GET['error'] === 'saldo_invalid') {
                    $error_msg = 'Jumlah saldo tidak valid. Harap masukkan angka saja.';
                } elseif ($_GET['error'] === 'update_gagal') {
                     $error_msg = 'Gagal memperbarui saldo. Tidak ada data yang diubah atau terjadi kesalahan.';
                }
                echo '<div class="alert alert-danger">' . $error_msg . '</div>';
            }
            ?>
            
            <form method="POST" action="index.php?page=atur_saldo&id=<?php echo $id_pengguna; ?>">
                <div class="mb-3">
                    <label class="form-label">Saldo Saat Ini</label>
                    <input type="text" class="form-control" value="Rp <?php echo number_format($pengguna['saldo'], 0, ',', '.'); ?>" disabled>
                </div>
                <div class="mb-3">
                    <label for="saldo" class="form-label">Masukkan Saldo Baru</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="number" class="form-control" id="saldo" name="saldo" value="<?php echo number_format($pengguna['saldo'], 0, '', ''); ?>" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                <a href="index.php?page=manage_saldo" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>
