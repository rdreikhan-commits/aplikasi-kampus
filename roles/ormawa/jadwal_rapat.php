<?php
/**
 * File: roles/ormawa/jadwal_rapat.php
 * Deskripsi: Halaman jadwal rapat. BEM dapat menjadwalkan rapat dengan ormawa lain.
 */

$user_role = $_SESSION['user_role'];
$user_id = $_SESSION['user_id'];

// Ambil jadwal rapat yang relevan (Direncanakan)
// Tampilkan semua jika admin/bkh, tampilkan sesuai target_peserta jika ormawa
$query = "SELECT r.*, u.nama_lengkap as penyelenggara 
          FROM jadwal_rapat r 
          JOIN users u ON r.id_penyelenggara = u.id_user 
          ORDER BY r.tanggal_rapat ASC, r.jam_rapat ASC";
$result = $conn->query($query);
?>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">📅 Jadwal Rapat & Koordinasi</h1>
            <p class="text-muted">Pantau agenda pertemuan antara BEM, BPM, dan ORMAWA.</p>
        </div>
        <?php if (in_array($user_role, ['bem', 'bpm'])): ?>
        <button type="button" class="btn btn-success rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahRapat">
            <i class="bi bi-calendar-plus me-1"></i> Jadwalkan Rapat
        </button>
        <?php endif; ?>
    </div>

    <div class="row">
        <?php if ($result->num_rows > 0): ?>
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Agenda Rapat</th>
                                    <th>Waktu & Tempat</th>
                                    <th>Penyelenggara</th>
                                    <th>Peserta</th>
                                    <th>Status</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?php echo htmlspecialchars($row['judul_rapat']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($row['deskripsi']); ?></small>
                                        </td>
                                        <td>
                                            <div><i class="bi bi-calendar3 me-2"></i><?php echo date('d M Y', strtotime($row['tanggal_rapat'])); ?></div>
                                            <div><i class="bi bi-clock me-2"></i><?php echo date('H:i', strtotime($row['jam_rapat'])); ?> WIB</div>
                                            <div class="text-primary small mt-1"><i class="bi bi-geo-alt-fill me-2"></i><?php echo htmlspecialchars($row['lokasi']); ?></div>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['penyelenggara']); ?></td>
                                        <td>
                                            <?php 
                                                $peserta = explode(',', $row['target_peserta']);
                                                foreach($peserta as $p):
                                                    $class = 'bg-secondary';
                                                    if($p == 'bem') $class = 'bg-primary';
                                                    if($p == 'bpm') $class = 'bg-warning text-dark';
                                                    if($p == 'ormawa') $class = 'bg-info text-dark';
                                                    echo "<span class='badge $class me-1'>".strtoupper($p)."</span>";
                                                endforeach;
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                                $statusClass = 'bg-info';
                                                if($row['status'] == 'Selesai') $statusClass = 'bg-success';
                                                if($row['status'] == 'Dibatalkan') $statusClass = 'bg-danger';
                                            ?>
                                            <span class="badge <?php echo $statusClass; ?>"><?php echo $row['status']; ?></span>
                                        </td>
                                        <td class="text-center">
                                            <?php if (!empty($row['link_meeting'])): ?>
                                            <a href="<?php echo $row['link_meeting']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-camera-video me-1"></i> Join
                                            </a>
                                            <?php endif; ?>
                                            
                                            <?php if (in_array($user_role, ['bem', 'bpm'])): ?>
                                            <form action="index.php?page=jadwal_rapat" method="POST" class="d-inline" onsubmit="return confirm('Hapus jadwal rapat ini?')">
                                                <input type="hidden" name="id_rapat" value="<?php echo $row['id_rapat']; ?>">
                                                <button type="submit" name="hapus_rapat" class="btn btn-sm btn-outline-danger border-0">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="col-12">
                <div class="card shadow-sm border-0 py-5 text-center">
                    <i class="bi bi-calendar-x display-1 text-muted opacity-25"></i>
                    <p class="mt-3 text-muted">Belum ada jadwal rapat terdaftar.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if (in_array($user_role, ['bem', 'bpm'])): ?>
<!-- Modal Tambah Rapat -->
<div class="modal fade" id="modalTambahRapat" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form action="index.php?page=jadwal_rapat" method="POST">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Jadwalkan Rapat Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Judul / Agenda Rapat</label>
                            <input type="text" name="judul_rapat" class="form-control" required placeholder="Contoh: Rapat Koordinasi Program Kerja Semester Genap">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Tanggal</label>
                            <input type="date" name="tanggal_rapat" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Jam</label>
                            <input type="time" name="jam_rapat" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Lokasi / Tempat</label>
                            <input type="text" name="lokasi" class="form-control" required placeholder="Contoh: Sekretariat BEM / Zoom Meeting">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Link Meeting (Opsional)</label>
                            <input type="url" name="link_meeting" class="form-control" placeholder="https://zoom.us/j/...">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Deskripsi / Pembahasan</label>
                            <textarea name="deskripsi" class="form-control" rows="3" placeholder="Apa saja yang akan dibahas?"></textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Target Peserta</label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="peserta[]" value="bem" id="p_bem" checked>
                                    <label class="form-check-label" for="p_bem">BEM</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="peserta[]" value="bpm" id="p_bpm">
                                    <label class="form-check-label" for="p_bpm">BPM</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="peserta[]" value="ormawa" id="p_ormawa">
                                    <label class="form-check-label" for="p_ormawa">Seluruh ORMAWA</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah_rapat" class="btn btn-success">Simpan Jadwal</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
