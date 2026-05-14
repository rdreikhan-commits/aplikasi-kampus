<?php
/**
 * File: roles/ormawa/arsip_surat_lain.php
 * Deskripsi: Daftar arsip surat-surat otomatis yang pernah dibuat.
 */

check_role(['ormawa', 'bem', 'bpm']);
$user_id = $_SESSION['user_id'];

// Proses Hapus
if (isset($_GET['hapus'])) {
    $id_h = (int)$_GET['hapus'];
    $stmt_h = $conn->prepare("DELETE FROM surat_otomatis WHERE id_surat = ? AND id_user_ormawa = ?");
    $stmt_h->bind_param("ii", $id_h, $user_id);
    if ($stmt_h->execute()) {
        echo "<script>alert('Surat berhasil dihapus!'); window.location='index.php?page=arsip_surat_lain';</script>";
    }
}

// Ambil data
$res = $conn->query("SELECT * FROM surat_otomatis WHERE id_user_ormawa = $user_id ORDER BY tgl_dibuat DESC");
?>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Arsip Surat Otomatis</h1>
        <a href="index.php?page=buat_surat_lain" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> Buat Surat Baru
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Tgl Dibuat</th>
                            <th>Jenis</th>
                            <th>Perihal</th>
                            <th>Tujuan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($s = $res->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($s['tgl_dibuat'])); ?></td>
                            <td><span class="badge bg-secondary"><?php echo $s['jenis_surat']; ?></span></td>
                            <td class="fw-bold"><?php echo htmlspecialchars($s['perihal']); ?></td>
                            <td><?php echo htmlspecialchars($s['tujuan_surat']); ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="index.php?page=view_surat_lain&id=<?php echo $s['id_surat']; ?>" class="btn btn-info text-white" title="Cetak">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                    <a href="index.php?page=arsip_surat_lain&hapus=<?php echo $s['id_surat']; ?>" class="btn btn-danger" onclick="return confirm('Hapus surat ini?')" title="Hapus">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; if($res->num_rows == 0): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">Belum ada surat yang dibuat.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
