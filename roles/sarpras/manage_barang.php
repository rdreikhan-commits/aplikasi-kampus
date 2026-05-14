<?php
/**
 * File: roles/sarpras/manage_barang.php
 */

check_role(['sarpras_barang']);

if (isset($_POST['tambah_barang'])) {
    $nama = sanitize_input($conn, $_POST['nama_barang']);
    $stok = (int)$_POST['stok'];
    $conn->query("INSERT INTO master_barang (nama_barang, stok_total, stok_tersedia) VALUES ('$nama', $stok, $stok)");
}

$barang = $conn->query("SELECT * FROM master_barang");
?>

<div class="container-fluid px-4 py-4">
    <h1 class="h3 mb-4">Manajemen Inventaris Barang</h1>

    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">Tambah Barang Baru</div>
                <div class="card-body">
                    <form action="" method="POST">
                        <div class="mb-3">
                            <label class="form-label">Nama Barang</label>
                            <input type="text" name="nama_barang" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Total Stok</label>
                            <input type="number" name="stok" class="form-control" value="1" min="1" required>
                        </div>
                        <button type="submit" name="tambah_barang" class="btn btn-primary w-100">Simpan Barang</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nama Barang</th>
                                <th>Total</th>
                                <th>Tersedia</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($b = $barang->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($b['nama_barang']); ?></td>
                                <td><?php echo $b['stok_total']; ?></td>
                                <td><?php echo $b['stok_tersedia']; ?></td>
                                <td><span class="badge bg-success">Aktif</span></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
