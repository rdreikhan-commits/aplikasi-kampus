<?php
/**
 * File: roles/ormawa/pusat_informasi.php
 * Deskripsi: Pusat Informasi Terpadu (Berita BEM & Regulasi BPM).
 */

$user_role = $_SESSION['user_role'];
$user_id = $_SESSION['user_id'];

// Ambil Berita/Pengumuman (BEM)
$query_news = "SELECT p.*, u.nama_lengkap as pengunggah 
               FROM pengumuman p 
               JOIN users u ON p.id_user_upload = u.id_user 
               ORDER BY p.tanggal_upload DESC";
$news_result = $conn->query($query_news);

// Ambil Regulasi (BPM)
$query_reg = "SELECT r.*, u.nama_lengkap as pengupload 
              FROM regulasi r 
              JOIN users u ON r.id_user_upload = u.id_user 
              ORDER BY r.tgl_upload DESC";
$reg_result = $conn->query($query_reg);
?>

<div class="container-fluid px-4 py-4">
    <!-- Header Section -->
    <div class="row align-items-center mb-4">
        <div class="col-md-8">
            <h1 class="h2 fw-bold text-gradient mb-1">Pusat Informasi & Berita</h1>
            <p class="text-muted">Satu pintu untuk seluruh informasi, kebijakan, dan pengumuman terbaru.</p>
        </div>
        <div class="col-md-4 text-md-end">
            <?php if ($user_role === 'bem'): ?>
            <button type="button" class="btn btn-primary shadow-sm rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalTambahNews">
                <i class="bi bi-plus-circle me-2"></i> Buat Berita
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <ul class="nav nav-pills mb-4 bg-white p-2 rounded-pill shadow-sm d-inline-flex border" id="pills-tab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active rounded-pill px-4" id="pills-news-tab" data-bs-toggle="pill" data-bs-target="#pills-news" type="button" role="tab">
                <i class="bi bi-megaphone me-2"></i> Berita & Pengumuman
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-pill px-4" id="pills-reg-tab" data-bs-toggle="pill" data-bs-target="#pills-reg" type="button" role="tab">
                <i class="bi bi-journal-text me-2"></i> Regulasi & Pedoman
            </button>
        </li>
    </ul>

    <div class="tab-content" id="pills-tabContent">
        <!-- Tab Berita & Pengumuman -->
        <div class="tab-pane fade show active" id="pills-news" role="tabpanel">
            <div class="row">
                <?php if ($news_result->num_rows > 0): ?>
                    <?php while ($row = $news_result->fetch_assoc()): ?>
                        <div class="col-lg-6 mb-4">
                            <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden news-card">
                                <div class="row g-0 h-100">
                                    <div class="col-md-4 bg-primary d-flex align-items-center justify-content-center text-white p-4">
                                        <div class="text-center">
                                            <div class="display-6 fw-bold"><?php echo date('d', strtotime($row['tanggal_upload'])); ?></div>
                                            <div class="text-uppercase small"><?php echo date('M Y', strtotime($row['tanggal_upload'])); ?></div>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="card-body p-4 d-flex flex-column h-100">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <span class="badge bg-primary-subtle text-primary rounded-pill px-3">BEM News</span>
                                                <?php if ($user_role === 'bem'): ?>
                                                <form action="index.php?page=pusat_informasi" method="POST" onsubmit="return confirm('Hapus berita ini?')">
                                                    <input type="hidden" name="id_pengumuman" value="<?php echo $row['id_pengumuman']; ?>">
                                                    <button type="submit" name="hapus_pengumuman" class="btn btn-link text-danger p-0">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                                <?php endif; ?>
                                            </div>
                                            <h5 class="fw-bold mb-2"><?php echo htmlspecialchars($row['judul']); ?></h5>
                                            <p class="text-muted small mb-4">
                                                <?php 
                                                    $isi = strip_tags($row['isi']);
                                                    echo (strlen($isi) > 100) ? substr($isi, 0, 100) . '...' : $isi;
                                                ?>
                                            </p>
                                            <div class="mt-auto d-flex justify-content-between align-items-center">
                                                <div class="small text-muted">
                                                    <i class="bi bi-person me-1"></i> <?php echo htmlspecialchars($row['pengunggah']); ?>
                                                </div>
                                                <button class="btn btn-link btn-sm text-primary p-0 fw-bold text-decoration-none" data-bs-toggle="modal" data-bs-target="#modalDetailNews<?php echo $row['id_pengumuman']; ?>">
                                                    Detail <i class="bi bi-arrow-right ms-1"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Detail News -->
                        <div class="modal fade" id="modalDetailNews<?php echo $row['id_pengumuman']; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content rounded-4 border-0">
                                    <div class="modal-header border-0 pb-0">
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body p-4 pt-0">
                                        <span class="badge bg-primary-subtle text-primary rounded-pill px-3 mb-3">Pengumuman Resmi</span>
                                        <h3 class="fw-bold mb-3"><?php echo htmlspecialchars($row['judul']); ?></h3>
                                        <div class="d-flex align-items-center mb-4 p-3 bg-light rounded-3">
                                            <div class="me-3">
                                                <i class="bi bi-calendar-event fs-4 text-primary"></i>
                                            </div>
                                            <div class="small">
                                                <div class="text-muted">Dipublikasikan pada</div>
                                                <div class="fw-bold text-dark"><?php echo date('d F Y, H:i', strtotime($row['tanggal_upload'])); ?></div>
                                            </div>
                                            <div class="ms-auto ps-3 border-start small">
                                                <div class="text-muted">Oleh</div>
                                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($row['pengunggah']); ?></div>
                                            </div>
                                        </div>
                                        <div class="content-text mb-4" style="line-height: 1.8;">
                                            <?php echo nl2br(htmlspecialchars($row['isi'])); ?>
                                        </div>
                                        <?php if (!empty($row['file_lampiran'])): ?>
                                        <div class="p-3 border rounded-4 d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-file-earmark-pdf fs-2 text-danger me-3"></i>
                                                <div>
                                                    <div class="fw-bold small">Lampiran Berkas</div>
                                                    <div class="text-muted extra-small">Klik untuk mengunduh lampiran resmi</div>
                                                </div>
                                            </div>
                                            <a href="uploads/pengumuman/<?php echo $row['file_lampiran']; ?>" target="_blank" class="btn btn-outline-primary btn-sm rounded-pill px-3">Unduh</a>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-5">
                        <i class="bi bi-megaphone display-1 text-muted opacity-25"></i>
                        <p class="mt-3 text-muted">Belum ada berita atau pengumuman dari BEM.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tab Regulasi & Pedoman -->
        <div class="tab-pane fade" id="pills-reg" role="tabpanel">
            <div class="row">
                <?php if ($reg_result->num_rows > 0): ?>
                    <?php while($r = $reg_result->fetch_assoc()): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden card-hover">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <span class="badge <?php 
                                        echo ($r['kategori'] == 'Undang-Undang') ? 'bg-danger-subtle text-danger' : 
                                             (($r['kategori'] == 'Pengumuman') ? 'bg-warning-subtle text-warning-emphasis' : 'bg-success-subtle text-success'); 
                                    ?> rounded-pill px-3">
                                        <?php echo $r['kategori']; ?>
                                    </span>
                                    <small class="text-muted"><?php echo date('d M Y', strtotime($r['tgl_upload'])); ?></small>
                                </div>
                                
                                <h5 class="fw-bold mb-2"><?php echo htmlspecialchars($r['judul']); ?></h5>
                                <p class="text-muted small mb-4"><?php echo htmlspecialchars($r['deskripsi']); ?></p>
                                
                                <div class="d-flex justify-content-between align-items-center mt-auto pt-3 border-top">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm rounded-circle bg-light d-flex align-items-center justify-content-center me-2" style="width: 30px; height: 30px;">
                                            <i class="bi bi-person text-secondary" style="font-size: 0.8rem;"></i>
                                        </div>
                                        <span class="extra-small text-muted"><?php echo htmlspecialchars($r['pengupload']); ?></span>
                                    </div>
                                    
                                    <?php if(!empty($r['file_path'])): ?>
                                        <a href="<?php echo $r['file_path']; ?>" target="_blank" class="btn btn-dark btn-sm rounded-pill px-3">
                                            <i class="bi bi-eye me-1"></i> Lihat
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-5">
                        <i class="bi bi-journal-x display-1 text-muted opacity-25"></i>
                        <p class="mt-3 text-muted">Belum ada regulasi atau pedoman dari BPM.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah News (BEM) -->
<?php if ($user_role === 'bem'): ?>
<div class="modal fade" id="modalTambahNews" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form action="index.php?page=pusat_informasi" method="POST" enctype="multipart/form-data">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header bg-primary text-white rounded-top-4 p-4">
                    <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2"></i> Buat Berita & Pengumuman Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Judul Berita</label>
                        <input type="text" name="judul" class="form-control rounded-3" required placeholder="Judul yang menarik dan informatif">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Isi / Konten Berita</label>
                        <textarea name="isi" class="form-control rounded-3" rows="10" required placeholder="Tuliskan isi berita selengkap mungkin..."></textarea>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-bold">Lampiran Berkas (Opsional)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-file-earmark-arrow-up"></i></span>
                            <input type="file" name="lampiran" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                        </div>
                        <small class="text-muted mt-2 d-block">Format yang diizinkan: PDF, JPG, PNG. Maksimal 2MB.</small>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah_pengumuman" class="btn btn-primary rounded-pill px-4 shadow">
                        Publikasikan Sekarang
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<style>
.text-gradient {
    background: linear-gradient(90deg, #0d6efd, #0099ff);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
.news-card {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.news-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1) !important;
}
.card-hover:hover {
    transform: scale(1.02);
}
.extra-small { font-size: 0.75rem; }
.nav-pills .nav-link.active {
    background-color: #0d6efd;
    color: white;
    box-shadow: 0 4px 10px rgba(13, 110, 253, 0.3);
}
.nav-pills .nav-link {
    color: #6c757d;
    font-weight: 500;
}
</style>
