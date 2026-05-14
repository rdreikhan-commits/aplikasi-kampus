<?php
/**
 * File: roles/verifikator/buat_surat_peringatan.php
 * Deskripsi: Generator Surat Peringatan (SP) untuk ORMAWA dari BPM/BKKH.
 */
check_role(['bpm', 'bkh']);
$user_id = $_SESSION['user_id'];

// Ambil daftar ormawa (user dengan role ormawa/bem/bpm)
$stmt_ormawa = $conn->prepare("SELECT id_user, nama_lengkap FROM users WHERE role IN ('ormawa', 'bem', 'bpm') ORDER BY nama_lengkap ASC");
$stmt_ormawa->execute();
$ormawas = $stmt_ormawa->get_result();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_sp'])) {
    $jenis = 'Peringatan';
    $nomor = sanitize_input($conn, $_POST['nomor_surat']);
    $perihal = sanitize_input($conn, $_POST['perihal']);
    $id_target = intval($_POST['id_ormawa_target']);
    
    // Ambil nama ormawa target
    $stmt_t = $conn->prepare("SELECT nama_lengkap FROM users WHERE id_user = ?");
    $stmt_t->bind_param("i", $id_target);
    $stmt_t->execute();
    $target_info = $stmt_t->get_result()->fetch_assoc();
    $tujuan = "Yth. Ketua " . $target_info['nama_lengkap'];

    $isi_data = [
        'tingkat_sp' => $_POST['tingkat_sp'],
        'alasan' => $_POST['alasan'],
        'deskripsi_pelanggaran' => $_POST['deskripsi_pelanggaran'],
        'sanksi' => $_POST['sanksi'],
        'tgl_peringatan' => $_POST['tgl_peringatan']
    ];

    $isi_json = json_encode($isi_data);
    $ttd_key = 'custom';
    $ttd_nama = sanitize_input($conn, $_POST['ttd_nama']);
    
    $stmt = $conn->prepare("INSERT INTO surat_otomatis (id_user_ormawa, jenis_surat, nomor_surat, perihal, tujuan_surat, isi_json, ttd_key, ttd_nama_kustom, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Final')");
    $stmt->bind_param("isssssss", $user_id, $jenis, $nomor, $perihal, $tujuan, $isi_json, $ttd_key, $ttd_nama);
    
    if ($stmt->execute()) {
        $id_surat = $stmt->insert_id;
        header("Location: index.php?page=view_surat_lain&id=" . $id_surat);
        exit();
    }
}
?>

<div class="container-fluid px-4 py-4">
    <div class="mb-4">
        <h1 class="h3 fw-bold text-danger"><i class="bi bi-exclamation-octagon-fill me-2"></i>Buat Surat Peringatan (SP)</h1>
        <p class="text-muted">Gunakan form ini untuk menerbitkan surat peringatan resmi kepada organisasi mahasiswa.</p>
    </div>

    <form action="" method="POST">
        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0 rounded-4 mb-4">
                    <div class="card-header bg-danger text-white py-3 rounded-top-4">
                        <h5 class="mb-0 fw-bold">Detail Pelanggaran & Peringatan</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Target Organisasi (ORMAWA)</label>
                                <select name="id_ormawa_target" class="form-select" required>
                                    <option value="" disabled selected>Pilih Ormawa yang akan diberi peringatan</option>
                                    <?php while($o = $ormawas->fetch_assoc()): ?>
                                        <option value="<?php echo $o['id_user']; ?>"><?php echo htmlspecialchars($o['nama_lengkap']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Nomor Surat</label>
                                <input type="text" name="nomor_surat" class="form-control" required placeholder="Contoh: 015/SP/BPM/V/2024">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Tingkat Peringatan</label>
                                <select name="tingkat_sp" class="form-select" required>
                                    <option value="Surat Peringatan 1 (SP-1)">Surat Peringatan 1 (SP-1)</option>
                                    <option value="Surat Peringatan 2 (SP-2)">Surat Peringatan 2 (SP-2)</option>
                                    <option value="Surat Peringatan 3 (SP-3)">Surat Peringatan 3 (SP-3 / Pembekuan)</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Perihal</label>
                                <input type="text" name="perihal" class="form-control" required value="Surat Peringatan Pelanggaran Peraturan Organisasi">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Alasan Utama (Singkat)</label>
                                <input type="text" name="alasan" class="form-control" required placeholder="Contoh: Keterlambatan Pengumpulan LPJ Kegiatan">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Deskripsi Pelanggaran</label>
                                <textarea name="deskripsi_pelanggaran" class="form-control" rows="4" required placeholder="Jelaskan secara detail pelanggaran yang dilakukan..."></textarea>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Sanksi yang Diberikan</label>
                                <textarea name="sanksi" class="form-control" rows="3" placeholder="Contoh: Penangguhan dana kegiatan selama 1 bulan ke depan..."></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Tanggal Surat</label>
                                <input type="date" name="tgl_peringatan" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm border-0 rounded-4 mb-4">
                    <div class="card-header bg-dark text-white py-3 rounded-top-4">
                        <h5 class="mb-0 fw-bold">Pengesahan</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-4">
                            <label class="form-label fw-bold">Nama Penandatangan</label>
                            <input type="text" name="ttd_nama" class="form-control" required placeholder="Nama Lengkap & Jabatan">
                            <small class="text-muted">Contoh: Ketua BPM ITG / Kepala BKKH ITG</small>
                        </div>
                        <hr>
                        <button type="submit" name="generate_sp" class="btn btn-danger w-100 btn-lg rounded-pill shadow mb-3">
                            <i class="bi bi-file-earmark-pdf-fill me-2"></i> Terbitkan Surat
                        </button>
                        <a href="index.php?page=dashboard" class="btn btn-outline-secondary w-100 rounded-pill">Batal</a>
                    </div>
                </div>
                
                <div class="alert alert-warning border-0 shadow-sm rounded-4">
                    <h6 class="fw-bold"><i class="bi bi-info-circle-fill me-2"></i>Perhatian</h6>
                    <small>Penerbitan surat peringatan adalah langkah formal. Pastikan data pelanggaran sudah tervalidasi dengan benar sebelum diterbitkan.</small>
                </div>
            </div>
        </div>
    </form>
</div>
