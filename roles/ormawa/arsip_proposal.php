<?php
/**
 * File: roles/ormawa/arsip_proposal.php
 * Deskripsi: Daftar proposal yang pernah dibuat (Draft & Final).
 */

check_role(['ormawa', 'bem', 'bpm']);
$user_id = $_SESSION['user_id'];

// Logika Hapus Proposal
if (isset($_GET['hapus'])) {
    $id_hapus = (int)$_GET['hapus'];
    $stmt = $conn->prepare("DELETE FROM proposal_otomatis WHERE id_proposal = ? AND id_user_ormawa = ?");
    $stmt->bind_param("ii", $id_hapus, $user_id);
    if ($stmt->execute()) {
        echo "<script>window.location.href='index.php?page=arsip_proposal&status=hapus_sukses';</script>";
    }
}

// Ambil Daftar Proposal
$sql = "SELECT * FROM proposal_otomatis WHERE id_user_ormawa = ? ORDER BY tgl_dibuat DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Arsip Proposal Otomatis</h1>
        <a href="index.php?page=buat_proposal" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> Buat Proposal Baru
        </a>
    </div>

    <?php if(isset($_GET['status']) && $_GET['status'] == 'hapus_sukses'): ?>
        <div class="alert alert-success">Proposal berhasil dihapus.</div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Tanggal</th>
                            <th>Nama Kegiatan</th>
                            <th>Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td class="ps-3"><?php echo date('d/m/Y H:i', strtotime($row['tgl_dibuat'])); ?></td>
                                <td class="fw-bold"><?php echo htmlspecialchars($row['nama_kegiatan']); ?></td>
                                <td>
                                    <?php if($row['status'] == 'Draft'): ?>
                                        <span class="badge bg-warning text-dark">Draft</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Final</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <a href="index.php?page=view_proposal&id=<?php echo $row['id_proposal']; ?>" class="btn btn-sm btn-outline-info" title="Lihat/Cetak">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                        <a href="index.php?page=edit_proposal&id=<?php echo $row['id_proposal']; ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="index.php?page=arsip_proposal&hapus=<?php echo $row['id_proposal']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus proposal ini?')" title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center py-5 text-muted">Belum ada proposal yang disimpan.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
