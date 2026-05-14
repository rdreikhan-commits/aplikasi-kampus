<?php
/**
 * File: roles/verifikator/manage_aspirasi.php
 * Deskripsi: Halaman khusus BPM untuk mengelola aspirasi masuk dari publik.
 */
check_role(['bpm']);

$query = "SELECT * FROM aspirasi ORDER BY tanggal_masuk DESC";
$result = $conn->query($query);
?>

<div class="container-fluid px-4 py-4">
    <div class="mb-4">
        <h1 class="h3 fw-bold">📢 Kelola Aspirasi & Suara Mahasiswa</h1>
        <p class="text-muted">Kelola seluruh aspirasi, keluhan, dan saran yang masuk dari publik secara anonim maupun terdata.</p>
    </div>

    <div class="row">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card border-0 shadow-sm rounded-4 h-100 card-hover">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <span class="badge <?php 
                                    echo ($row['status'] == 'Pending') ? 'bg-warning text-dark' : 
                                         (($row['status'] == 'Selesai') ? 'bg-success' : 'bg-primary'); 
                                ?> rounded-pill px-3">
                                    <?php echo $row['status']; ?>
                                </span>
                                <small class="text-muted"><?php echo date('d/m/y H:i', strtotime($row['tanggal_masuk'])); ?></small>
                            </div>
                            
                            <h5 class="fw-bold mb-2"><?php echo htmlspecialchars($row['subjek']); ?></h5>
                            <div class="badge bg-light text-dark mb-3"><?php echo $row['kategori']; ?></div>
                            
                            <p class="text-muted small mb-4">
                                <?php echo nl2br(htmlspecialchars(substr($row['isi_aspirasi'], 0, 150))) . (strlen($row['isi_aspirasi']) > 150 ? '...' : ''); ?>
                            </p>
                            
                            <div class="border-top pt-3 d-flex justify-content-between align-items-center">
                                <div class="small">
                                    <div class="text-muted small">Pelapor:</div>
                                    <div class="fw-bold"><?php echo htmlspecialchars($row['nama_pelapor']); ?></div>
                                    <?php if (!empty($row['email_pelapor'])): ?>
                                        <div class="text-primary small"><i class="bi bi-envelope me-1"></i><?php echo htmlspecialchars($row['email_pelapor']); ?></div>
                                    <?php endif; ?>
                                </div>
                                <button class="btn btn-primary btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalTanggapi<?php echo $row['id_aspirasi']; ?>">
                                    Tanggapi
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Tanggapi -->
                <div class="modal fade" id="modalTanggapi<?php echo $row['id_aspirasi']; ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <form action="index.php?page=manage_aspirasi" method="POST">
                            <input type="hidden" name="id_aspirasi" value="<?php echo $row['id_aspirasi']; ?>">
                            <div class="modal-content rounded-4 border-0">
                                <div class="modal-header border-0 pb-0">
                                    <h5 class="fw-bold">Tindak Lanjuti Aspirasi</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body p-4">
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <div class="small text-muted mb-1">Nama Pelapor:</div>
                                            <div class="fw-bold"><?php echo htmlspecialchars($row['nama_pelapor']); ?></div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="small text-muted mb-1">Email Kontak:</div>
                                            <div class="fw-bold text-primary"><?php echo !empty($row['email_pelapor']) ? htmlspecialchars($row['email_pelapor']) : '-'; ?></div>
                                        </div>
                                    </div>

                                    <div class="p-3 bg-light rounded-4 mb-4">
                                        <div class="small text-muted mb-1">Pesan dari Mahasiswa:</div>
                                        <div class="fw-bold mb-2"><?php echo htmlspecialchars($row['subjek']); ?></div>
                                        <div style="font-style: italic;"><?php echo nl2br(htmlspecialchars($row['isi_aspirasi'])); ?></div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Tanggapan / Tindakan BPM</label>
                                        <textarea name="tanggapan" class="form-control" rows="5" required placeholder="Tuliskan tanggapan atau langkah yang diambil oleh BPM..."><?php echo htmlspecialchars($row['tanggapan_bpm']); ?></textarea>
                                    </div>
                                    
                                    <div class="mb-0">
                                        <label class="form-label fw-bold">Status Tindak Lanjut</label>
                                        <select name="status" class="form-select">
                                            <option value="Pending" <?php echo $row['status'] == 'Pending' ? 'selected' : ''; ?>>Pending (Belum Direspon)</option>
                                            <option value="Ditindaklanjuti" <?php echo $row['status'] == 'Ditindaklanjuti' ? 'selected' : ''; ?>>Ditindaklanjuti</option>
                                            <option value="Selesai" <?php echo $row['status'] == 'Selesai' ? 'selected' : ''; ?>>Selesai (Masalah Teratasi)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="modal-footer border-0 p-4 pt-0">
                                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                                    <button type="submit" name="tanggapi_aspirasi" class="btn btn-primary rounded-pill px-4 shadow">Simpan Perubahan</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <i class="bi bi-chat-left-dots display-1 text-muted opacity-25"></i>
                <p class="mt-3 text-muted">Belum ada aspirasi atau keluhan yang masuk.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
