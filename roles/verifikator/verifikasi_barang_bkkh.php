<?php
/**
 * File: roles/verifikator/verifikasi_barang_bkkh.php
 */

check_role(['bkh']);

// Proses Aksi BKKH
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi_bkkh_barang'])) {
    $id_peminjaman = (int)$_POST['id_peminjaman'];
    $aksi = $_POST['aksi_bkkh_barang'];
    $catatan = sanitize_input($conn, $_POST['catatan_bkkh'] ?? '');

    $new_status = ($aksi === 'verifikasi') ? 'Diverifikasi' : 'Ditolak';

    $stmt = $conn->prepare("UPDATE peminjaman_barang SET status_bkkh = ?, catatan_penolakan = ? WHERE id_peminjaman_barang = ?");
    $stmt->bind_param("ssi", $new_status, $catatan, $id_peminjaman);
    
    if ($stmt->execute()) {
        $msg = "Verifikasi barang oleh BKKH berhasil!";
    } else {
        $err = "Gagal memproses: " . $conn->error;
    }
}

// Ambil data yang BELUM DIVERIFIKASI BKKH
$sql = "SELECT p.*, u.nama_lengkap AS nama_ormawa 
        FROM peminjaman_barang p 
        JOIN users u ON p.id_user_ormawa = u.id_user 
        WHERE p.status_bkkh = 'Pending'
        ORDER BY p.tgl_pengajuan DESC";
$res = $conn->query($sql);
?>

<div class="container-fluid px-4 py-4">
    <h1 class="h3 mb-4">Verifikasi Peminjaman Barang (BKKH)</h1>

    <?php if(isset($msg)): ?><div class="alert alert-success"><?php echo $msg; ?></div><?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Ormawa</th>
                            <th>Kegiatan</th>
                            <th>Barang & Qty</th>
                            <th>Waktu</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $res->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['nama_ormawa']); ?></td>
                            <td><?php echo htmlspecialchars($row['nama_kegiatan']); ?></td>
                            <td>
                                <ul class="mb-0 small ps-3">
                                    <?php 
                                    $items = json_decode($row['kebutuhan_barang'], true);
                                    if (is_array($items)) {
                                        foreach($items as $item) {
                                            $id_b = isset($item['id_barang']) ? (int)$item['id_barang'] : 0;
                                            if ($id_b > 0) {
                                                $b_res = $conn->query("SELECT nama_barang FROM master_barang WHERE id_barang = $id_b");
                                                $b_info = $b_res ? $b_res->fetch_assoc() : null;
                                                echo "<li>" . htmlspecialchars($b_info['nama_barang'] ?? 'Unknown') . " (" . ($item['qty'] ?? 0) . ")</li>";
                                            }
                                        }
                                    }
                                    ?>
                                </ul>
                            </td>
                            <td class="small">
                                <?php echo date('d/m/y', strtotime($row['tgl_mulai'])); ?> - <?php echo date('d/m/y', strtotime($row['tgl_selesai'])); ?>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalBKKH<?php echo $row['id_peminjaman_barang']; ?>">Periksa</button>
                            </td>
                        </tr>

                        <!-- Modal -->
                        <div class="modal fade" id="modalBKKH<?php echo $row['id_peminjaman_barang']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form action="" method="POST">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Verifikasi BKKH (Barang)</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="id_peminjaman" value="<?php echo $row['id_peminjaman_barang']; ?>">
                                            <p>Kegiatan: <strong><?php echo htmlspecialchars($row['nama_kegiatan']); ?></strong></p>
                                            <div class="mb-3">
                                                <label class="form-label">Catatan (Jika ditolak)</label>
                                                <textarea name="catatan_bkkh" class="form-control" rows="3"></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="submit" name="aksi_bkkh_barang" value="tolak" class="btn btn-danger">Tolak</button>
                                            <button type="submit" name="aksi_bkkh_barang" value="verifikasi" class="btn btn-success">Verifikasi</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; if($res->num_rows == 0) echo "<tr><td colspan='5' class='text-center py-4'>Tidak ada antrean verifikasi barang.</td></tr>"; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
