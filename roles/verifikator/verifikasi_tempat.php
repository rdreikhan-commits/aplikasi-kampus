<?php
/**
 * File: roles/verifikator/verifikasi_tempat.php
 * Deskripsi: Halaman bagi BKKH untuk memverifikasi pengajuan peminjaman ruangan.
 */

check_role(['bkh']);

// Proses Aksi Setuju / Tolak
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'])) {
    $id_peminjaman = (int)$_POST['id_peminjaman'];
    $aksi = $_POST['aksi'];
    $catatan = sanitize_input($conn, $_POST['catatan_penolakan'] ?? '');

    $new_status_bkkh = ($aksi === 'setuju') ? 'Diverifikasi' : 'Ditolak';

    $stmt = $conn->prepare("UPDATE peminjaman_tempat SET status_bkkh = ?, catatan_penolakan = ? WHERE id_peminjaman = ?");
    $stmt->bind_param("ssi", $new_status_bkkh, $catatan, $id_peminjaman);
    
    if ($stmt->execute()) {
        $msg = "Pengajuan peminjaman berhasil " . strtolower($new_status_bkkh) . "!";
    } else {
        $err = "Gagal memproses peminjaman: " . $conn->error;
    }
    $stmt->close();
}

// Ambil semua data peminjaman tempat
$peminjaman = [];
$sql = "SELECT p.*, r.nama_ruangan, u.nama_lengkap AS nama_ormawa 
        FROM peminjaman_tempat p 
        JOIN master_ruangan r ON p.id_ruangan = r.id_ruangan 
        JOIN users u ON p.id_user_ormawa = u.id_user 
        ORDER BY p.tgl_pengajuan DESC";
$res = $conn->query($sql);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $peminjaman[] = $row;
    }
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Verifikasi Peminjaman Tempat (BKKH)</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item active">Tahap 1: Verifikasi BKKH</li>
    </ol>

    <?php if(isset($msg)): ?>
        <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-1"></i> <?php echo $msg; ?> <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if(isset($err)): ?>
        <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-x-circle me-1"></i> <?php echo $err; ?> <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-white">
            <i class="bi bi-table me-1"></i> Daftar Pengajuan Ruangan
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="tabelPeminjaman" class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Ormawa</th>
                            <th>Kegiatan</th>
                            <th>Ruangan</th>
                            <th>Waktu Pelaksanaan</th>
                            <th>Status BKKH</th>
                            <th>Status Sarpras</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($peminjaman) > 0): ?>
                            <?php $i = 1; foreach($peminjaman as $row): ?>
                                <tr>
                                    <td><?php echo $i++; ?></td>
                                    <td><?php echo htmlspecialchars($row['nama_ormawa']); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['nama_kegiatan']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($row['deskripsi_kegiatan']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['nama_ruangan']); ?></td>
                                    <td>
                                        <?php echo date('d M Y', strtotime($row['tgl_mulai'])); ?> s/d <?php echo date('d M Y', strtotime($row['tgl_selesai'])); ?><br>
                                        <small class="text-muted"><i class="bi bi-clock"></i> <?php echo date('H:i', strtotime($row['jam_mulai'])); ?> - <?php echo date('H:i', strtotime($row['jam_selesai'])); ?></small>
                                    </td>
                                    <td>
                                        <?php 
                                            if($row['status_bkkh'] == 'Diverifikasi') echo '<span class="badge bg-success">Diverifikasi</span>';
                                            else if($row['status_bkkh'] == 'Ditolak') echo '<span class="badge bg-danger">Ditolak</span>';
                                            else echo '<span class="badge bg-warning text-dark">Pending</span>';
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                            if($row['status_sarpras'] == 'Disetujui') echo '<span class="badge bg-success">Disetujui</span>';
                                            else if($row['status_sarpras'] == 'Ditolak') echo '<span class="badge bg-danger">Ditolak</span>';
                                            else echo '<span class="badge bg-secondary text-white">Pending</span>';
                                        ?>
                                    </td>
                                    <td>
                                        <?php if($row['status_bkkh'] == 'Pending'): ?>
                                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalAksi<?php echo $row['id_peminjaman']; ?>">
                                                Proses
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-secondary" disabled>Selesai</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>

                                <!-- Modal Aksi -->
                                <div class="modal fade" id="modalAksi<?php echo $row['id_peminjaman']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form action="index.php?page=verifikasi_tempat" method="POST">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Verifikasi Peminjaman Tempat</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Ormawa: <strong><?php echo htmlspecialchars($row['nama_ormawa']); ?></strong></p>
                                                    <p>Kegiatan: <strong><?php echo htmlspecialchars($row['nama_kegiatan']); ?></strong></p>
                                                    <input type="hidden" name="id_peminjaman" value="<?php echo $row['id_peminjaman']; ?>">
                                                    <div class="mb-3">
                                                        <label class="form-label">Catatan (Wajib jika ditolak)</label>
                                                        <textarea name="catatan_penolakan" class="form-control" rows="3" placeholder="Alasan penolakan / Instruksi khusus..."></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="submit" name="aksi" value="tolak" class="btn btn-danger">Tolak Peminjaman</button>
                                                    <button type="submit" name="aksi" value="setuju" class="btn btn-success">Setujui Peminjaman</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center py-3">Belum ada pengajuan peminjaman tempat.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<script>
    $(document).ready(function() {
        $('#tabelPeminjaman').DataTable({
            "language": { "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json" }
        });
    });
</script>
