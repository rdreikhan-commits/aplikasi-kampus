<?php
/**
 * File: roles/ormawa/buat_surat_lain.php
 * Deskripsi: Generator berbagai jenis surat organisasi (Undangan, Tugas, dll).
 */

check_role(['ormawa', 'bem', 'bpm']);
$user_id = $_SESSION['user_id'];

// Ambil data profil untuk TTD
$stmt_p = $conn->prepare("SELECT * FROM users WHERE id_user = ?");
$stmt_p->bind_param("i", $user_id);
$stmt_p->execute();
$profile = $stmt_p->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_surat'])) {
    $jenis = sanitize_input($conn, $_POST['jenis_surat']);
    $nomor = sanitize_input($conn, $_POST['nomor_surat']);
    $perihal = sanitize_input($conn, $_POST['perihal']);
    $tujuan = sanitize_input($conn, $_POST['tujuan_surat']);
    
    // TTD Logic
    $ttd_key = ($_POST['ttd_key'] === 'custom') ? sanitize_input($conn, $_POST['ttd_nama_custom']) : sanitize_input($conn, $_POST['ttd_key']);
    $ttd_file = null;
    if ($_POST['ttd_key'] === 'custom' && isset($_FILES['ttd_file_custom']) && $_FILES['ttd_file_custom']['error'] == 0) {
        $target_dir = "uploads/proposal_ttd/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $newName = "surat_" . time() . ".png";
        if (move_uploaded_file($_FILES['ttd_file_custom']['tmp_name'], $target_dir . $newName)) {
            $ttd_file = $newName;
        }
    }

    // Capture dynamic fields into JSON
    $isi_data = [];
    if ($jenis == 'Undangan') {
        $isi_data = [
            'acara' => $_POST['und_acara'],
            'hari_tgl' => $_POST['und_tgl'],
            'waktu' => $_POST['und_waktu'],
            'tempat' => $_POST['und_tempat'],
            'isi_pembuka' => $_POST['und_pembuka']
        ];
    } elseif ($jenis == 'Tugas') {
        $isi_data = [
            'nama_petugas' => $_POST['tug_nama'],
            'nim_petugas' => $_POST['tug_nim'],
            'tugas' => $_POST['tug_deskripsi'],
            'tgl_pelaksanaan' => $_POST['tug_tgl']
        ];
    } elseif ($jenis == 'Permohonan') {
        $isi_data = [
            'nama_alat_tempat' => $_POST['per_nama'],
            'tgl_pakai' => $_POST['per_tgl'],
            'alasan' => $_POST['per_alasan']
        ];
    } elseif ($jenis == 'Keterangan') {
        $isi_data = [
            'nama_mhs' => $_POST['ket_nama'],
            'nim_mhs' => $_POST['ket_nim'],
            'jabatan_mhs' => $_POST['ket_jabatan'],
            'keperluan' => $_POST['ket_keperluan']
        ];
    }

    $isi_json = json_encode($isi_data);

    $stmt = $conn->prepare("INSERT INTO surat_otomatis (id_user_ormawa, jenis_surat, nomor_surat, perihal, tujuan_surat, isi_json, ttd_key, ttd_file_kustom) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssssss", $user_id, $jenis, $nomor, $perihal, $tujuan, $isi_json, $ttd_key, $ttd_file);
    
    if ($stmt->execute()) {
        $id_surat = $stmt->insert_id;
        header("Location: index.php?page=view_surat_lain&id=" . $id_surat);
        exit();
    }
}
?>

<div class="container-fluid px-4 py-4">
    <h1 class="h3 mb-4">Generator Surat Otomatis</h1>

    <form action="" method="POST" enctype="multipart/form-data">
        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">Informasi Dasar Surat</div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Jenis Surat</label>
                                <select name="jenis_surat" id="jenis_surat" class="form-select" onchange="switchForm(this.value)" required>
                                    <option value="">-- Pilih Jenis Surat --</option>
                                    <option value="Undangan">Surat Undangan</option>
                                    <option value="Tugas">Surat Tugas / Mandat</option>
                                    <option value="Permohonan">Surat Permohonan (Alat/Tempat)</option>
                                    <option value="Keterangan">Surat Keterangan Aktif</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Nomor Surat (Opsional)</label>
                                <input type="text" name="nomor_surat" class="form-control" placeholder="Contoh: 001/HIMATIF/ITG/V/2024">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Perihal</label>
                            <input type="text" name="perihal" class="form-control" placeholder="Contoh: Undangan Pemateri Seminar Nasional">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Tujuan Surat</label>
                            <input type="text" name="tujuan_surat" class="form-control" placeholder="Contoh: Yth. Bapak Ir. Budi Santoso, M.T.">
                        </div>
                    </div>
                </div>

                <!-- Dynamic Forms -->
                <div id="form_undangan" class="surat-fields d-none">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-info text-white">Detail Undangan</div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Kalimat Pembuka</label>
                                <textarea name="und_pembuka" class="form-control" rows="2">Sehubungan dengan akan dilaksanakannya kegiatan Seminar Nasional, kami bermaksud mengundang Bapak/Ibu untuk hadir sebagai pemateri.</textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nama Acara</label>
                                <input type="text" name="und_acara" class="form-control">
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-4"><label class="form-label">Hari / Tanggal</label><input type="text" name="und_tgl" class="form-control" placeholder="Senin, 20 Mei 2024"></div>
                                <div class="col-md-4"><label class="form-label">Waktu</label><input type="text" name="und_waktu" class="form-control" placeholder="08.00 s.d Selesai"></div>
                                <div class="col-md-4"><label class="form-label">Tempat</label><input type="text" name="und_tempat" class="form-control" placeholder="Aula ITG"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="form_tugas" class="surat-fields d-none">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-dark text-white">Detail Surat Tugas</div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-8"><label class="form-label">Nama Petugas</label><input type="text" name="tug_nama" class="form-control"></div>
                                <div class="col-md-4"><label class="form-label">NIM</label><input type="text" name="tug_nim" class="form-control"></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Uraian Tugas</label>
                                <textarea name="tug_deskripsi" class="form-control" rows="3" placeholder="Contoh: Menjadi delegasi dalam kegiatan Musyawarah Nasional..."></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Tanggal Pelaksanaan</label>
                                <input type="text" name="tug_tgl" class="form-control" placeholder="20 - 25 Mei 2024">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Permohonan & Keterangan (Similar logic) -->
                <div id="form_permohonan" class="surat-fields d-none">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-success text-white">Detail Permohonan</div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Nama Alat / Tempat yang Dipinjam</label>
                                <input type="text" name="per_nama" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Waktu Penggunaan</label>
                                <input type="text" name="per_tgl" class="form-control" placeholder="Rabu, 22 Mei 2024 Jam 13.00">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Alasan / Tujuan Peminjaman</label>
                                <textarea name="per_alasan" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="form_keterangan" class="surat-fields d-none">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-warning text-dark">Detail Surat Keterangan</div>
                        <div class="card-body">
                             <div class="row mb-3">
                                <div class="col-md-8"><label class="form-label">Nama Mahasiswa</label><input type="text" name="ket_nama" class="form-control"></div>
                                <div class="col-md-4"><label class="form-label">NIM</label><input type="text" name="ket_nim" class="form-control"></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Jabatan di Organisasi</label>
                                <input type="text" name="ket_jabatan" class="form-control" placeholder="Anggota Bidang Minat Bakat">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Tujuan / Keperluan Surat</label>
                                <input type="text" name="ket_keperluan" class="form-control" placeholder="Contoh: Persyaratan Beasiswa Unggulan">
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm border-primary">
                    <div class="card-header bg-primary text-white">Tanda Tangan</div>
                    <div class="card-body">
                         <div class="mb-3">
                            <label class="form-label small">Penandatangan</label>
                            <select name="ttd_key" class="form-select form-select-sm mb-1" onchange="toggleCustomTTD(this)">
                                <option value="ketua"><?php echo htmlspecialchars($profile['nama_ketua'] ?? 'Ketua'); ?></option>
                                <option value="sekretaris"><?php echo htmlspecialchars($profile['nama_sekretaris'] ?? 'Sekretaris'); ?></option>
                                <option value="bendahara"><?php echo htmlspecialchars($profile['nama_bendahara'] ?? 'Bendahara'); ?></option>
                                <option value="custom">-- Nama Kustom --</option>
                            </select>
                            <div id="box_custom_ttd" class="d-none mt-2">
                                <input type="text" name="ttd_nama_custom" class="form-control form-control-sm mb-1" placeholder="Nama & Jabatan">
                                <input type="file" name="ttd_file_custom" class="form-control form-control-sm" accept="image/png">
                            </div>
                        </div>
                        <hr>
                        <button type="submit" name="generate_surat" class="btn btn-primary w-100 btn-lg">
                            <i class="bi bi-printer me-2"></i> Generate Surat
                        </button>
                        <a href="index.php?page=dashboard" class="btn btn-outline-secondary w-100 mt-2">Batal</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function switchForm(val) {
    document.querySelectorAll('.surat-fields').forEach(f => f.classList.add('d-none'));
    if (val === 'Undangan') document.getElementById('form_undangan').classList.remove('d-none');
    if (val === 'Tugas') document.getElementById('form_tugas').classList.remove('d-none');
    if (val === 'Permohonan') document.getElementById('form_permohonan').classList.remove('d-none');
    if (val === 'Keterangan') document.getElementById('form_keterangan').classList.remove('d-none');
}

function toggleCustomTTD(sel) {
    const box = document.getElementById('box_custom_ttd');
    if (sel.value === 'custom') {
        box.classList.remove('d-none');
    } else {
        box.classList.add('d-none');
    }
}
</script>
