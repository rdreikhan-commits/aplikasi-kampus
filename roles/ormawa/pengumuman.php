<?php
/**
 * File: roles/ormawa/pengumuman.php
 * Deskripsi: Halaman pengumuman sistem. BEM dapat mengupload pengumuman baru.
 */

$user_role = $_SESSION['user_role'];
$user_id = $_SESSION['user_id'];

// Ambil pengumuman aktif
$query = "SELECT p.*, u.nama_lengkap as pengunggah 
          FROM pengumuman p 
          JOIN users u ON p.id_user_upload = u.id_user 
          ORDER BY p.tanggal_upload DESC";
$result = $conn->query($query);
?>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">📢 Pusat Pengumuman</h1>
            <p class="text-muted">Informasi terbaru seputar kegiatan dan kebijakan kampus.</p>
        </div>
        <?php if ($user_role === 'bem'): ?>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
            <i class="bi bi-plus-circle me-1"></i> Tambah Pengumuman
        </button>
        <?php endif; ?>
    </div>

    <div class="row">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge bg-primary-subtle text-primary"><?php echo date('d M Y', strtotime($row['tanggal_upload'])); ?></span>
                                <?php if ($user_role === 'bem'): ?>
                                <form action="index.php?page=pengumuman" method="POST" onsubmit="return confirm('Hapus pengumuman ini?')">
                                    <input type="hidden" name="id_pengumuman" value="<?php echo $row['id_pengumuman']; ?>">
                                    <button type="submit" name="hapus_pengumuman" class="btn btn-link text-danger p-0">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                            <h5 class="card-title fw-bold"><?php echo htmlspecialchars($row['judul']); ?></h5>
                            <p class="card-text text-muted small">
                                <?php 
                                    $isi = strip_tags($row['isi']);
                                    echo (strlen($isi) > 150) ? substr($isi, 0, 150) . '...' : $isi;
                                ?>
                            </p>
                        </div>
                        <div class="card-footer bg-transparent border-0 d-flex justify-content-between align-items-center pb-3">
                            <div class="small">
                                <i class="bi bi-person-circle me-1"></i> <?php echo htmlspecialchars($row['pengunggah']); ?>
                            </div>
                            <div>
                                <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalDetail<?php echo $row['id_pengumuman']; ?>">
                                    Baca Selengkapnya
                                </button>
                                <?php if (!empty($row['file_lampiran'])): ?>
                                <a href="uploads/pengumuman/<?php echo $row['file_lampiran']; ?>" target="_blank" class="btn btn-light btn-sm">
                                    <i class="bi bi-paperclip"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Detail -->
                <div class="modal fade" id="modalDetail<?php echo $row['id_pengumuman']; ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title"><?php echo htmlspecialchars($row['judul']); ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3 text-muted small">
                                    <span><i class="bi bi-calendar-event me-1"></i> <?php echo date('d F Y H:i', strtotime($row['tanggal_upload'])); ?></span>
                                    <span class="ms-3"><i class="bi bi-person me-1"></i> <?php echo htmlspecialchars($row['pengunggah']); ?></span>
                                </div>
                                <div class="announcement-content">
                                    <?php echo nl2br(htmlspecialchars($row['isi'])); ?>
                                </div>
                                <?php if (!empty($row['file_lampiran'])): ?>
                                <div class="mt-4 p-3 bg-light rounded d-flex align-items-center">
                                    <i class="bi bi-file-earmark-pdf fs-3 text-danger me-3"></i>
                                    <div>
                                        <div class="fw-bold">Lampiran Berkas</div>
                                        <a href="uploads/pengumuman/<?php echo $row['file_lampiran']; ?>" target="_blank" class="text-decoration-none">Unduh Lampiran</a>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="text-center py-5">
                    <i class="bi bi- megaphone display-1 text-muted opacity-25"></i>
                    <p class="mt-3 text-muted">Belum ada pengumuman terbaru saat ini.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($user_role === 'bem'): ?>
<!-- Modal Tambah -->
<div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="index.php?page=pengumuman" method="POST" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Buat Pengumuman Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Judul Pengumuman</label>
                        <input type="text" name="judul" class="form-control" required placeholder="Contoh: Info Pencairan Dana Hibah Tahap 1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Isi Pengumuman</label>
                        <textarea name="isi" class="form-control" rows="8" required placeholder="Tuliskan detail pengumuman di sini..."></textarea>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-bold">File Lampiran (Opsional)</label>
                        <input type="file" name="lampiran" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                        <small class="text-muted">Format: PDF, JPG, PNG. Maks 2MB.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah_pengumuman" class="btn btn-primary">Publikasikan</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
