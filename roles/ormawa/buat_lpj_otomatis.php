<?php
/**
 * File: roles/ormawa/buat_lpj_otomatis.php
 * Deskripsi: Form pembuatan Laporan Pertanggungjawaban (LPJ) otomatis.
 */

check_role(['ormawa', 'bem', 'bpm']);
$user_id = $_SESSION['user_id'];

// Ambil data profil untuk TTD
$stmt_p = $conn->prepare("SELECT nama_ketua, nama_sekretaris, nama_bendahara FROM users WHERE id_user = ?");
$stmt_p->bind_param("i", $user_id);
$stmt_p->execute();
$profile = $stmt_p->get_result()->fetch_assoc();

$sukses_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_lpj'])) {
    $nama_kegiatan = sanitize_input($conn, $_POST['nama_kegiatan']);
    $pendahuluan = sanitize_input($conn, $_POST['pendahuluan']);
    $pelaksanaan = sanitize_input($conn, $_POST['pelaksanaan']);
    $hasil = sanitize_input($conn, $_POST['hasil']);
    $hambatan = sanitize_input($conn, $_POST['hambatan']);
    $saran = sanitize_input($conn, $_POST['saran']);
    $penutup = sanitize_input($conn, $_POST['penutup']);
    
    $ttd_1 = ($_POST['ttd_1'] === 'custom') ? sanitize_input($conn, $_POST['ttd_1_custom']) : sanitize_input($conn, $_POST['ttd_1']);
    $ttd_2 = ($_POST['ttd_2'] === 'custom') ? sanitize_input($conn, $_POST['ttd_2_custom']) : sanitize_input($conn, $_POST['ttd_2']);
    $ttd_3 = ($_POST['ttd_3'] === 'custom') ? sanitize_input($conn, $_POST['ttd_3_custom']) : sanitize_input($conn, $_POST['ttd_3']);

    $status = isset($_POST['simpan_draft']) ? 'Draft' : 'Final';

    // Upload Files if any
    $ttd_files = ['ttd_1_file' => null, 'ttd_2_file' => null, 'ttd_3_file' => null];
    foreach ($ttd_files as $key => $val) {
        if (isset($_FILES[$key]) && $_FILES[$key]['error'] == 0) {
            $newName = "lpj_ttd_" . time() . "_" . $key . ".png";
            if (move_uploaded_file($_FILES[$key]["tmp_name"], "uploads/proposal_ttd/" . $newName)) {
                $ttd_files[$key] = $newName;
            }
        }
    }

    $stmt = $conn->prepare("INSERT INTO lpj_otomatis (id_user_ormawa, nama_kegiatan, pendahuluan, pelaksanaan_kegiatan, hasil_kegiatan, hambatan_kendala, saran_rekomendasi, penutup, ttd_1_key, ttd_2_key, ttd_3_key, ttd_1_file, ttd_2_file, ttd_3_file, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssssssssssss", $user_id, $nama_kegiatan, $pendahuluan, $pelaksanaan, $hasil, $hambatan, $saran, $penutup, $ttd_1, $ttd_2, $ttd_3, $ttd_files['ttd_1_file'], $ttd_files['ttd_2_file'], $ttd_files['ttd_3_file'], $status);
    
    if ($stmt->execute()) {
        $id_lpj = $stmt->insert_id;
        
        // Simpan Realisasi Anggaran
        if (isset($_POST['uraian'])) {
            $uraian = $_POST['uraian'];
            $estimasi = $_POST['estimasi'];
            $realisasi = $_POST['realisasi'];
            $ket = $_POST['keterangan'];

            $stmt_a = $conn->prepare("INSERT INTO lpj_anggaran (id_lpj, uraian, estimasi_dana, realisasi_dana, keterangan) VALUES (?, ?, ?, ?, ?)");
            for ($i = 0; $i < count($uraian); $i++) {
                if (!empty($uraian[$i])) {
                    $stmt_a->bind_param("isdds", $id_lpj, $uraian[$i], $estimasi[$i], $realisasi[$i], $ket[$i]);
                    $stmt_a->execute();
                }
            }
        }

        // Simpan Lampiran (Kwitansi & Dokumentasi)
        $handleLampiran = function($inputKey, $type, $lpjId, $conn) {
            if (isset($_FILES[$inputKey])) {
                $target_dir = "uploads/lpj_lampiran/";
                if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
                
                foreach ($_FILES[$inputKey]['tmp_name'] as $i => $tmp) {
                    if ($_FILES[$inputKey]['error'][$i] == 0) {
                        $ext = strtolower(pathinfo($_FILES[$inputKey]['name'][$i], PATHINFO_EXTENSION));
                        $newName = "lpj_" . $lpjId . "_" . $type . "_" . time() . "_" . $i . "." . $ext;
                        if (move_uploaded_file($tmp, $target_dir . $newName)) {
                            $stmt_l = $conn->prepare("INSERT INTO lpj_lampiran (id_lpj, nama_file, tipe_lampiran) VALUES (?, ?, ?)");
                            $stmt_l->bind_param("iss", $lpjId, $newName, $type);
                            $stmt_l->execute();
                        }
                    }
                }
            }
        };

        $handleLampiran('kwitansi', 'Kwitansi', $id_lpj, $conn);
        $handleLampiran('dokumentasi', 'Dokumentasi', $id_lpj, $conn);
        
        header("Location: index.php?page=view_lpj_otomatis&id=" . $id_lpj);
        exit();
    }
}
?>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Buat LPJ Otomatis</h1>
        <a href="index.php?page=arsip_lpj_otomatis" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-archive me-1"></i> Lihat Arsip LPJ
        </a>
    </div>

    <form action="" method="POST" enctype="multipart/form-data">
        <div class="row">
            <div class="col-lg-8">
                <!-- Konten Narasi LPJ -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">Batang Tubuh LPJ</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nama Kegiatan</label>
                            <input type="text" name="nama_kegiatan" class="form-control" required placeholder="Contoh: Malam Keakraban HIMATIF 2024">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Pendahuluan / Latar Belakang</label>
                            <textarea name="pendahuluan" class="form-control" rows="3" placeholder="Jelaskan secara singkat mengenai terlaksananya kegiatan ini..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Waktu & Tempat Pelaksanaan</label>
                            <textarea name="pelaksanaan" class="form-control" rows="2" placeholder="Contoh: Sabtu, 15 Mei 2024 di Villa Garut. Dihadiri oleh 100 peserta."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Hasil Kegiatan</label>
                            <textarea name="hasil" class="form-control" rows="3" placeholder="Apa saja capaian dari kegiatan ini?"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Hambatan & Kendala</label>
                            <textarea name="hambatan" class="form-control" rows="3" placeholder="Sebutkan kendala teknis atau non-teknis yang dihadapi..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Saran & Rekomendasi</label>
                            <textarea name="saran" class="form-control" rows="2" placeholder="Saran untuk panitia di masa mendatang..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Penutup</label>
                            <textarea name="penutup" class="form-control" rows="2">Demikian laporan pertanggungjawaban ini kami buat sebagai bahan evaluasi.</textarea>
                        </div>
                    </div>
                </div>

                <!-- Realisasi Anggaran -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-success text-white d-flex justify-content-between">
                        <span>Laporan Realisasi Dana</span>
                        <button type="button" class="btn btn-light btn-sm" onclick="addRow()">+ Tambah Baris</button>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-bordered mb-0" id="budgetTable">
                            <thead class="table-light">
                                <tr class="small text-center">
                                    <th>Uraian</th>
                                    <th style="width: 150px;">Estimasi (Rp)</th>
                                    <th style="width: 150px;">Realisasi (Rp)</th>
                                    <th>Keterangan</th>
                                    <th style="width: 50px;">#</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><input type="text" name="uraian[]" class="form-control form-control-sm border-0" placeholder="Contoh: Konsumsi"></td>
                                    <td><input type="number" name="estimasi[]" class="form-control form-control-sm border-0 text-end"></td>
                                    <td><input type="number" name="realisasi[]" class="form-control form-control-sm border-0 text-end"></td>
                                    <td><input type="text" name="keterangan[]" class="form-control form-control-sm border-0"></td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Lampiran: Kwitansi & Dokumentasi -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-dark text-white">Lampiran Bukti & Dokumentasi</div>
                    <div class="card-body">
                        <div class="mb-4">
                            <label class="form-label fw-bold"><i class="bi bi-receipt me-1"></i> Upload Kwitansi / Bukti Pembayaran</label>
                            <input type="file" name="kwitansi[]" class="form-control mb-2" multiple accept="image/*, application/pdf">
                            <small class="text-muted">Bisa pilih banyak file sekaligus (Gambar/PDF).</small>
                        </div>
                        <div class="mb-0">
                            <label class="form-label fw-bold"><i class="bi bi-camera me-1"></i> Upload Foto Dokumentasi Kegiatan</label>
                            <input type="file" name="dokumentasi[]" class="form-control mb-2" multiple accept="image/*">
                            <small class="text-muted">Bisa pilih banyak foto kegiatan sekaligus.</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- TTD Section -->
                <div class="card shadow-sm border-primary position-sticky" style="top: 20px;">
                    <div class="card-header bg-primary text-white text-center fw-bold">Tanda Tangan</div>
                    <div class="card-body">
                         <div class="mb-3">
                            <label class="form-label small">Tanda Tangan 1 (Ketua)</label>
                            <select name="ttd_1" class="form-select form-select-sm mb-1" onchange="toggleCustom(this, 'c1', 'f1')">
                                <option value="ketua"><?php echo htmlspecialchars($profile['nama_ketua'] ?? 'Ketua'); ?></option>
                                <option value="custom">-- Nama Kustom --</option>
                            </select>
                            <input type="text" name="ttd_1_custom" id="c1" class="form-control form-control-sm d-none mb-1" placeholder="Nama Kustom">
                            <input type="file" name="ttd_1_file" id="f1" class="form-control form-control-sm d-none" accept="image/png">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Tanda Tangan 2 (Sekretaris)</label>
                            <select name="ttd_2" class="form-select form-select-sm mb-1" onchange="toggleCustom(this, 'c2', 'f2')">
                                <option value="sekretaris"><?php echo htmlspecialchars($profile['nama_sekretaris'] ?? 'Sekretaris'); ?></option>
                                <option value="custom">-- Nama Kustom --</option>
                            </select>
                            <input type="text" name="ttd_2_custom" id="c2" class="form-control form-control-sm d-none mb-1" placeholder="Nama Kustom">
                            <input type="file" name="ttd_2_file" id="f2" class="form-control form-control-sm d-none" accept="image/png">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Tanda Tangan 3 (Bendahara)</label>
                            <select name="ttd_3" class="form-select form-select-sm mb-1" onchange="toggleCustom(this, 'c3', 'f3')">
                                <option value="bendahara"><?php echo htmlspecialchars($profile['nama_bendahara'] ?? 'Bendahara'); ?></option>
                                <option value="custom">-- Nama Kustom --</option>
                            </select>
                            <input type="text" name="ttd_3_custom" id="c3" class="form-control form-control-sm d-none mb-1" placeholder="Nama Kustom">
                            <input type="file" name="ttd_3_file" id="f3" class="form-control form-control-sm d-none" accept="image/png">
                        </div>

                        <hr>
                        <button type="submit" name="simpan_lpj" class="btn btn-primary w-100 mb-2">Simpan & Cetak LPJ</button>
                        <button type="submit" name="simpan_draft" class="btn btn-outline-primary w-100">Simpan Draft</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function addRow() {
    const table = document.getElementById('budgetTable').getElementsByTagName('tbody')[0];
    const newRow = table.insertRow();
    newRow.innerHTML = `
        <td><input type="text" name="uraian[]" class="form-control form-control-sm border-0"></td>
        <td><input type="number" name="estimasi[]" class="form-control form-control-sm border-0 text-end"></td>
        <td><input type="number" name="realisasi[]" class="form-control form-control-sm border-0 text-end"></td>
        <td><input type="text" name="keterangan[]" class="form-control form-control-sm border-0"></td>
        <td class="text-center"><button type="button" class="btn btn-link text-danger p-0" onclick="removeRow(this)"><i class="bi bi-trash"></i></button></td>
    `;
}

function removeRow(btn) {
    const row = btn.parentNode.parentNode;
    row.parentNode.removeChild(row);
}

function toggleCustom(sel, cId, fId) {
    const c = document.getElementById(cId);
    const f = document.getElementById(fId);
    if (sel.value === 'custom') {
        c.classList.remove('d-none');
        f.classList.remove('d-none');
    } else {
        c.classList.add('d-none');
        f.classList.add('d-none');
    }
}
</script>
