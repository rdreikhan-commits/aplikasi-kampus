<?php
/**
 * File: roles/sarpras/verifikasi_barang.php
 */

check_role(['sarpras_barang']);

// Proses Aksi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi_sarpras_barang'])) {
    $id_peminjaman = (int)$_POST['id_peminjaman'];
    $aksi = $_POST['aksi_sarpras_barang'];
    $catatan = sanitize_input($conn, $_POST['catatan_sarpras'] ?? '');

    $new_status = ($aksi === 'setuju') ? 'Disetujui' : 'Ditolak';

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE peminjaman_barang SET status_sarpras = ?, catatan_penolakan = ? WHERE id_peminjaman_barang = ?");
        $stmt->bind_param("ssi", $new_status, $catatan, $id_peminjaman);
        $stmt->execute();

        // Jika disetujui, kurangi stok barang
        if ($new_status === 'Disetujui') {
            $stmt_data = $conn->prepare("SELECT kebutuhan_barang FROM peminjaman_barang WHERE id_peminjaman_barang = ?");
            $stmt_data->bind_param("i", $id_peminjaman);
            $stmt_data->execute();
            $data = $stmt_data->get_result()->fetch_assoc();
            $items = json_decode($data['kebutuhan_barang'], true);

            foreach ($items as $item) {
                $id_b = (int)$item['id_barang'];
                $qty = (int)$item['qty'];
                $conn->query("UPDATE master_barang SET stok_tersedia = stok_tersedia - $qty WHERE id_barang = $id_b");
            }
        }

        $conn->commit();
        $msg = "Persetujuan barang berhasil diproses!";
    } catch (Exception $e) {
        $conn->rollback();
        $err = "Gagal memproses: " . $e->getMessage();
    }
}

// Ambil data yang SUDAH DIVERIFIKASI BKKH
$sql = "SELECT p.*, u.nama_lengkap AS nama_ormawa 
        FROM peminjaman_barang p 
        JOIN users u ON p.id_user_ormawa = u.id_user 
        WHERE p.status_bkkh = 'Diverifikasi'
        ORDER BY p.tgl_pengajuan DESC";
$res = $conn->query($sql);
?>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 fw-bold">Persetujuan Peminjaman Barang</h1>
        <div class="text-muted small">Persetujuan Tahap 2 (Final)</div>
    </div>

    <?php if(isset($msg)): ?><div class="alert alert-success border-0 shadow-sm"><?php echo $msg; ?></div><?php endif; ?>
    <?php if(isset($err)): ?><div class="alert alert-danger border-0 shadow-sm"><?php echo $err; ?></div><?php endif; ?>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr class="text-secondary text-uppercase small fw-bold">
                            <th class="ps-4 py-3">Ormawa</th>
                            <th class="py-3">Kegiatan</th>
                            <th class="py-3">Daftar Barang</th>
                            <th class="py-3 text-center">Waktu Pinjam</th>
                            <th class="py-3 text-center">Status</th>
                            <th class="py-3 pe-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $res->fetch_assoc()): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold"><?php echo htmlspecialchars($row['nama_ormawa']); ?></div>
                                <div class="text-muted extra-small">ID: #B-<?php echo $row['id_peminjaman_barang']; ?></div>
                            </td>
                            <td><span class="fw-medium"><?php echo htmlspecialchars($row['nama_kegiatan']); ?></span></td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    <?php 
                                    $items = json_decode($row['kebutuhan_barang'], true);
                                    if (is_array($items)) {
                                        foreach($items as $item) {
                                            $id_b = isset($item['id_barang']) ? (int)$item['id_barang'] : 0;
                                            if ($id_b > 0) {
                                                $b_res = $conn->query("SELECT nama_barang FROM master_barang WHERE id_barang = $id_b");
                                                $b_info = $b_res ? $b_res->fetch_assoc() : null;
                                                echo '<span class="badge bg-secondary-subtle text-secondary-emphasis rounded-pill">' . htmlspecialchars($b_info['nama_barang'] ?? 'Unknown') . ' ('.($item['qty'] ?? 0).')</span>';
                                            }
                                        }
                                    } else {
                                        echo '<span class="text-muted small">Data barang tidak valid</span>';
                                    }
                                    ?>
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="small fw-bold"><?php echo date('d M Y', strtotime($row['tgl_mulai'])); ?></div>
                                <div class="text-muted extra-small">s/d <?php echo date('d M Y', strtotime($row['tgl_selesai'])); ?></div>
                            </td>
                            <td class="text-center">
                                <?php 
                                if($row['status_sarpras'] == 'Disetujui') echo '<span class="badge bg-success-subtle text-success py-2 px-3 rounded-3"><i class="bi bi-check2-circle me-1"></i> Disetujui</span>';
                                else if($row['status_sarpras'] == 'Ditolak') echo '<span class="badge bg-danger-subtle text-danger py-2 px-3 rounded-3"><i class="bi bi-x-circle me-1"></i> Ditolak</span>';
                                else echo '<span class="badge bg-warning-subtle text-warning-emphasis py-2 px-3 rounded-3"><i class="bi bi-hourglass-split me-1"></i> Menunggu</span>';
                                ?>
                            </td>
                            <td class="pe-4 text-center">
                                <?php if($row['status_sarpras'] == 'Pending'): ?>
                                    <button class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalB<?php echo $row['id_peminjaman_barang']; ?>">
                                        Proses <i class="bi bi-pencil-square ms-1"></i>
                                    </button>
                                <?php else: ?>
                                    <i class="bi bi-dash-circle text-muted"></i>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <!-- Modal -->
                        <div class="modal fade" id="modalB<?php echo $row['id_peminjaman_barang']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form action="" method="POST">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Konfirmasi Persetujuan Barang</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="id_peminjaman" value="<?php echo $row['id_peminjaman_barang']; ?>">
                                            <p>Kegiatan: <strong><?php echo htmlspecialchars($row['nama_kegiatan']); ?></strong></p>
                                            <div class="mb-3">
                                                <label class="form-label">Catatan Tambahan</label>
                                                <textarea name="catatan_sarpras" class="form-control" rows="3"></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="submit" name="aksi_sarpras_barang" value="tolak" class="btn btn-danger">Tolak</button>
                                            <button type="submit" name="aksi_sarpras_barang" value="setuju" class="btn btn-success">Setujui</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; if($res->num_rows == 0) echo "<tr><td colspan='6' class='text-center py-4'>Tidak ada pengajuan barang yang membutuhkan persetujuan.</td></tr>"; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
