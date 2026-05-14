<?php
/**
 * File: roles/verifikator/manage_regulasi.php
 * Khusus untuk BPM mengelola Undang-Undang dan Pengumuman.
 */

check_role(['bpm']);
$user_id = $_SESSION['user_id'];

// Proses Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_regulasi'])) {
    $judul = sanitize_input($conn, $_POST['judul']);
    $deskripsi = sanitize_input($conn, $_POST['deskripsi']);
    $kategori = $_POST['kategori'];
    
    $file_path = '';
    if (isset($_FILES['file_regulasi']) && $_FILES['file_regulasi']['error'] == 0) {
        $target_dir = "uploads/regulasi/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        
        $file_name = time() . '_' . basename($_FILES['file_regulasi']['name']);
        $target_file = $target_dir . $file_name;
        
        if (move_uploaded_file($_FILES['file_regulasi']['tmp_name'], $target_file)) {
            $file_path = $target_file;
        }
    }
    
    $stmt = $conn->prepare("INSERT INTO regulasi (judul, deskripsi, file_path, kategori, id_user_upload) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssi", $judul, $deskripsi, $file_path, $kategori, $user_id);
    
    if ($stmt->execute()) {
        $msg = "Regulasi/Pengumuman berhasil diterbitkan!";
    } else {
        $err = "Gagal menerbitkan: " . $conn->error;
    }
}

// Proses Hapus
if (isset($_GET['hapus'])) {
    $id_h = (int)$_GET['hapus'];
    // Ambil path file untuk dihapus secara fisik
    $stmt_f = $conn->prepare("SELECT file_path FROM regulasi WHERE id_regulasi = ?");
    $stmt_f->bind_param("i", $id_h);
    $stmt_f->execute();
    $f_data = $stmt_f->get_result()->fetch_assoc();
    if ($f_data && !empty($f_data['file_path']) && file_exists($f_data['file_path'])) {
        unlink($f_data['file_path']);
    }
    
    $conn->query("DELETE FROM regulasi WHERE id_regulasi = $id_h");
    header("Location: index.php?page=manage_regulasi&status=deleted");
    exit();
}

$list_regulasi = $conn->query("SELECT * FROM regulasi ORDER BY tgl_upload DESC");
?>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 fw-bold">Pusat Regulasi & Pengumuman BPM</h1>
        <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalTambah">
            <i class="bi bi-plus-lg me-2"></i> Terbitkan Baru
        </button>
    </div>

    <?php if(isset($msg)): ?><div class="alert alert-success border-0 shadow-sm"><?php echo $msg; ?></div><?php endif; ?>
    <?php if(isset($_GET['status']) && $_GET['status'] == 'deleted'): ?><div class="alert alert-info border-0 shadow-sm">Data telah dihapus.</div><?php endif; ?>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr class="text-secondary small fw-bold">
                            <th class="ps-4 py-3">Judul & Kategori</th>
                            <th class="py-3">Deskripsi Singkat</th>
                            <th class="py-3">Tanggal Terbit</th>
                            <th class="py-3">File</th>
                            <th class="py-3 pe-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($r = $list_regulasi->fetch_assoc()): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold"><?php echo htmlspecialchars($r['judul']); ?></div>
                                <span class="badge bg-info-subtle text-info rounded-pill extra-small"><?php echo $r['kategori']; ?></span>
                            </td>
                            <td><div class="text-muted small text-truncate" style="max-width: 200px;"><?php echo htmlspecialchars($r['deskripsi']); ?></div></td>
                            <td class="small"><?php echo date('d M Y, H:i', strtotime($r['tgl_upload'])); ?></td>
                            <td>
                                <?php if(!empty($r['file_path'])): ?>
                                    <a href="<?php echo $r['file_path']; ?>" target="_blank" class="btn btn-sm btn-light border rounded-pill">
                                        <i class="bi bi-file-earmark-pdf text-danger me-1"></i> Lihat File
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted small">No File</span>
                                <?php endif; ?>
                            </td>
                            <td class="pe-4 text-center">
                                <a href="index.php?page=manage_regulasi&hapus=<?php echo $r['id_regulasi']; ?>" 
                                   class="btn btn-sm btn-outline-danger border-0" 
                                   onclick="return confirm('Hapus regulasi ini?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; if($list_regulasi->num_rows == 0) echo "<tr><td colspan='5' class='text-center py-4 text-muted'>Belum ada regulasi yang diterbitkan.</td></tr>"; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow rounded-4">
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Terbitkan Regulasi/Pengumuman</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Judul</label>
                        <input type="text" name="judul" class="form-control" required placeholder="Contoh: UU Ormawa No 1 2024">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Kategori</label>
                        <select name="kategori" class="form-select" required>
                            <option value="Undang-Undang">Undang-Undang</option>
                            <option value="Pengumuman">Pengumuman</option>
                            <option value="Pedoman">Pedoman</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Isi / Keterangan Singkat</label>
                        <textarea name="deskripsi" class="form-control" rows="3" placeholder="Jelaskan isi singkat regulasi..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">File Dokumen (PDF/Gambar)</label>
                        <input type="file" name="file_regulasi" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-toggle="modal">Batal</button>
                    <button type="submit" name="tambah_regulasi" class="btn btn-primary rounded-pill px-4">Terbitkan Sekarang</button>
                </div>
            </form>
        </div>
    </div>
</div>
