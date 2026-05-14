<?php
/**
 * File: roles/ormawa/arsip_lpj_otomatis.php
 * Deskripsi: Daftar arsip LPJ otomatis yang pernah dibuat.
 */

check_role(['ormawa', 'bem', 'bpm']);
$user_id = $_SESSION['user_id'];

// Proses Hapus
if (isset($_GET['hapus'])) {
    $id_h = (int)$_GET['hapus'];
    $stmt_h = $conn->prepare("DELETE FROM lpj_otomatis WHERE id_lpj = ? AND id_user_ormawa = ?");
    $stmt_h->bind_param("ii", $id_h, $user_id);
    if ($stmt_h->execute()) {
        echo "<script>alert('LPJ berhasil dihapus!'); window.location='index.php?page=arsip_lpj_otomatis';</script>";
    }
}

// Ambil data
$res = $conn->query("SELECT * FROM lpj_otomatis WHERE id_user_ormawa = $user_id ORDER BY tgl_dibuat DESC");
?>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Arsip LPJ Otomatis</h1>
        <a href="index.php?page=buat_lpj_otomatis" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> Buat LPJ Baru
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Tgl Dibuat</th>
                            <th>Nama Kegiatan</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($l = $res->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($l['tgl_dibuat'])); ?></td>
                            <td class="fw-bold"><?php echo htmlspecialchars($l['nama_kegiatan']); ?></td>
                            <td>
                                <span class="badge <?php echo $l['status'] == 'Final' ? 'bg-success' : 'bg-warning text-dark'; ?>">
                                    <?php echo $l['status']; ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="index.php?page=view_lpj_otomatis&id=<?php echo $l['id_lpj']; ?>" class="btn btn-info text-white" title="Cetak">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                    <a href="index.php?page=arsip_lpj_otomatis&hapus=<?php echo $l['id_lpj']; ?>" class="btn btn-danger" onclick="return confirm('Hapus LPJ ini?')" title="Hapus">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; if($res->num_rows == 0): ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">Belum ada LPJ yang dibuat.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
