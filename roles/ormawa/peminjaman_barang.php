<?php
/**
 * File: roles/ormawa/peminjaman_barang.php
 */

check_role(['ormawa', 'bem', 'bpm']);
$id_user = $_SESSION['user_id'];

// Proses Pengajuan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajukan_barang'])) {
    $nama_kegiatan = sanitize_input($conn, $_POST['nama_kegiatan']);
    $tgl_mulai = $_POST['tgl_mulai'];
    $tgl_selesai = $_POST['tgl_selesai'];
    
    $barang_selected = $_POST['barang'] ?? [];
    $qty_selected = $_POST['qty'] ?? [];
    
    $items = [];
    foreach($barang_selected as $index => $id_b) {
        if (!empty($qty_selected[$index]) && $qty_selected[$index] > 0) {
            $items[] = [
                'id_barang' => (int)$id_b,
                'qty' => (int)$qty_selected[$index]
            ];
        }
    }
    
    if (empty($items)) {
        $err = "Pilih minimal satu barang dan jumlahnya.";
    } else {
        $items_json = json_encode($items);
        $stmt = $conn->prepare("INSERT INTO peminjaman_barang (id_user_ormawa, nama_kegiatan, tgl_mulai, tgl_selesai, kebutuhan_barang) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $id_user, $nama_kegiatan, $tgl_mulai, $tgl_selesai, $items_json);
        
        if ($stmt->execute()) {
            $msg = "Pengajuan peminjaman barang berhasil dikirim! Menunggu verifikasi BKKH.";
        } else {
            $err = "Gagal mengirim pengajuan: " . $conn->error;
        }
    }
}

// Ambil riwayat peminjaman barang ormawa ini
$riwayat = $conn->query("SELECT * FROM peminjaman_barang WHERE id_user_ormawa = $id_user ORDER BY tgl_pengajuan DESC");
// Ambil daftar barang tersedia
$master_barang = $conn->query("SELECT * FROM master_barang WHERE status_aktif = 'aktif' AND stok_tersedia > 0");
?>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Peminjaman Sarana & Barang</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
            <i class="bi bi-plus-circle me-2"></i> Ajukan Peminjaman
        </button>
    </div>

    <?php if(isset($msg)): ?><div class="alert alert-success"><?php echo $msg; ?></div><?php endif; ?>
    <?php if(isset($err)): ?><div class="alert alert-danger"><?php echo $err; ?></div><?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="card-title mb-3">Riwayat Pengajuan Barang</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Kegiatan</th>
                            <th>Barang</th>
                            <th>Waktu Pinjam</th>
                            <th>Status BKKH</th>
                            <th>Status Sarpras</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($r = $riwayat->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($r['nama_kegiatan']); ?></td>
                            <td>
                                <ul class="mb-0 small ps-3">
                                    <?php 
                                    $items = json_decode($r['kebutuhan_barang'], true);
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
                                <?php echo date('d/m/y', strtotime($r['tgl_mulai'])); ?> - <?php echo date('d/m/y', strtotime($r['tgl_selesai'])); ?>
                            </td>
                            <td>
                                <?php 
                                if($r['status_bkkh'] == 'Diverifikasi') echo '<span class="badge bg-success">Diverifikasi</span>';
                                else if($r['status_bkkh'] == 'Ditolak') echo '<span class="badge bg-danger">Ditolak</span>';
                                else echo '<span class="badge bg-warning text-dark">Pending</span>';
                                ?>
                            </td>
                            <td>
                                <?php 
                                if($r['status_sarpras'] == 'Disetujui') echo '<span class="badge bg-success">Disetujui</span>';
                                else if($r['status_sarpras'] == 'Ditolak') echo '<span class="badge bg-danger">Ditolak</span>';
                                else echo '<span class="badge bg-warning text-dark">Pending</span>';
                                ?>
                            </td>
                        </tr>
                        <?php endwhile; if($riwayat->num_rows == 0) echo "<tr><td colspan='5' class='text-center py-4'>Belum ada riwayat pengajuan barang.</td></tr>"; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Form Peminjaman Barang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Kegiatan</label>
                        <input type="text" name="nama_kegiatan" class="form-control" required placeholder="Contoh: Seminar Nasional">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tanggal Mulai</label>
                            <input type="date" name="tgl_mulai" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tanggal Selesai</label>
                            <input type="date" name="tgl_selesai" class="form-control" required>
                        </div>
                    </div>
                    
                    <h6 class="border-bottom pb-2 mb-3 mt-4">Pilih Barang yang Dibutuhkan</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Pilih</th>
                                    <th>Nama Barang</th>
                                    <th>Stok Tersedia</th>
                                    <th>Jumlah Pinjam</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($mb = $master_barang->fetch_assoc()): ?>
                                <tr>
                                    <td><input type="checkbox" name="barang[]" value="<?php echo $mb['id_barang']; ?>"></td>
                                    <td><?php echo htmlspecialchars($mb['nama_barang']); ?></td>
                                    <td><?php echo $mb['stok_tersedia']; ?></td>
                                    <td><input type="number" name="qty[]" class="form-control form-control-sm" style="width: 80px;" min="0" max="<?php echo $mb['stok_tersedia']; ?>"></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="ajukan_barang" class="btn btn-primary">Kirim Pengajuan</button>
                </div>
            </form>
        </div>
    </div>
</div>
