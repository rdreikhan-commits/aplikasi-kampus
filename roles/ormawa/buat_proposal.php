<?php
/**
 * File: roles/ormawa/buat_proposal.php
 * Deskripsi: Form pembuatan proposal otomatis bagi Ormawa.
 */

check_role(['ormawa', 'bem', 'bpm']);
$user_id = $_SESSION['user_id'];

// Proses Simpan Proposal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_proposal'])) {
    $nama_kegiatan = sanitize_input($conn, $_POST['nama_kegiatan']);
    $latar_belakang = sanitize_input($conn, $_POST['latar_belakang']);
    $tujuan = sanitize_input($conn, $_POST['tujuan']);
    $sasaran = sanitize_input($conn, $_POST['sasaran']);
    $penutup = sanitize_input($conn, $_POST['penutup']);
    
    $ttd_1 = ($_POST['ttd_1'] === 'custom') ? sanitize_input($conn, $_POST['ttd_1_custom']) : sanitize_input($conn, $_POST['ttd_1']);
    $ttd_1_jab = ($_POST['ttd_1'] === 'custom') ? sanitize_input($conn, $_POST['ttd_1_custom_jabatan']) : null;
    $ttd_1_nim = ($_POST['ttd_1'] === 'custom') ? sanitize_input($conn, $_POST['ttd_1_custom_nim']) : null;

    $ttd_2 = ($_POST['ttd_2'] === 'custom') ? sanitize_input($conn, $_POST['ttd_2_custom']) : sanitize_input($conn, $_POST['ttd_2']);
    $ttd_2_jab = ($_POST['ttd_2'] === 'custom') ? sanitize_input($conn, $_POST['ttd_2_custom_jabatan']) : null;
    $ttd_2_nim = ($_POST['ttd_2'] === 'custom') ? sanitize_input($conn, $_POST['ttd_2_custom_nim']) : null;

    $ttd_3 = ($_POST['ttd_3'] === 'custom') ? sanitize_input($conn, $_POST['ttd_3_custom']) : sanitize_input($conn, $_POST['ttd_3']);
    $ttd_3_jab = ($_POST['ttd_3'] === 'custom') ? sanitize_input($conn, $_POST['ttd_3_custom_jabatan']) : null;
    $ttd_3_nim = ($_POST['ttd_3'] === 'custom') ? sanitize_input($conn, $_POST['ttd_3_custom_nim']) : null;
    
    // Upload Custom TTD Files
    $ttd_files = ['ttd_1_file' => null, 'ttd_2_file' => null, 'ttd_3_file' => null];
    $target_dir = "uploads/proposal_ttd/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

    foreach ($ttd_files as $key => $val) {
        if (isset($_FILES[$key]) && $_FILES[$key]['error'] == 0) {
            $ext = strtolower(pathinfo($_FILES[$key]["name"], PATHINFO_EXTENSION));
            if ($ext == 'png') {
                $newName = "custom_" . time() . "_" . $key . ".png";
                if (move_uploaded_file($_FILES[$key]["tmp_name"], $target_dir . $newName)) {
                    $ttd_files[$key] = $newName;
                }
            }
        }
    }

    $status = isset($_POST['simpan_draft']) ? 'Draft' : 'Final';

    // 1. Simpan Header Proposal
    $stmt = $conn->prepare("INSERT INTO proposal_otomatis (id_user_ormawa, nama_kegiatan, latar_belakang, tujuan, sasaran, penutup, ttd_1_key, ttd_1_custom_jabatan, ttd_1_custom_nim, ttd_2_key, ttd_2_custom_jabatan, ttd_2_custom_nim, ttd_3_key, ttd_3_custom_jabatan, ttd_3_custom_nim, ttd_1_file, ttd_2_file, ttd_3_file, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssssssssssssssss", $user_id, $nama_kegiatan, $latar_belakang, $tujuan, $sasaran, $penutup, $ttd_1, $ttd_1_jab, $ttd_1_nim, $ttd_2, $ttd_2_jab, $ttd_2_nim, $ttd_3, $ttd_3_jab, $ttd_3_nim, $ttd_files['ttd_1_file'], $ttd_files['ttd_2_file'], $ttd_files['ttd_3_file'], $status);
    
    if ($stmt->execute()) {
        $id_proposal = $stmt->insert_id;

        // 2. Simpan RAB
        if (isset($_POST['rab_rincian'])) {
            foreach ($_POST['rab_rincian'] as $key => $rincian) {
                if (empty($rincian)) continue;
                $vol = (int)$_POST['rab_vol'][$key];
                $sat = sanitize_input($conn, $_POST['rab_sat'][$key]);
                $harga = (float)$_POST['rab_harga'][$key];
                $total = $vol * $harga;

                $stmt_rab = $conn->prepare("INSERT INTO proposal_rab (id_proposal, rincian, volume, satuan, harga_satuan, total_harga) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt_rab->bind_param("isisdd", $id_proposal, $rincian, $vol, $sat, $harga, $total);
                $stmt_rab->execute();
            }
        }

        // 3. Simpan Panitia
        if (isset($_POST['panitia_nama'])) {
            foreach ($_POST['panitia_nama'] as $key => $nama) {
                if (empty($nama)) continue;
                $jab = sanitize_input($conn, $_POST['panitia_jabatan'][$key]);
                $nim = sanitize_input($conn, $_POST['panitia_nim'][$key]);

                $stmt_pan = $conn->prepare("INSERT INTO proposal_panitia (id_proposal, jabatan, nama_mahasiswa, nim) VALUES (?, ?, ?, ?)");
                $stmt_pan->bind_param("isss", $id_proposal, $jab, $nama, $nim);
                $stmt_pan->execute();
            }
        }

        header("Location: index.php?page=view_proposal&id=" . $id_proposal);
        exit();
    } else {
        $error_msg = "Gagal menyimpan proposal: " . $conn->error;
    }
}
?>

<div class="container-fluid px-4 py-4">
    <h1 class="mt-4">Pembuatan Proposal Otomatis</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="index.php?page=dashboard">Dashboard</a></li>
        <li class="breadcrumb-item active">Buat Proposal</li>
    </ol>

    <?php if(isset($sukses_msg)): ?>
        <div class="alert alert-success"><i class="bi bi-check-circle me-1"></i> <?php echo $sukses_msg; ?></div>
    <?php endif; ?>

    <form action="" method="POST" id="proposalForm" enctype="multipart/form-data">
        <div class="row">
            <!-- Bagian Kiri: Narasi -->
            <div class="col-lg-7">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <i class="bi bi-pencil-square me-1"></i> I. Pendahuluan & Narasi
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nama Kegiatan</label>
                            <input type="text" name="nama_kegiatan" class="form-control" required placeholder="Contoh: Lomba Coding Nasional 2024">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Latar Belakang</label>
                            <textarea name="latar_belakang" class="form-control" rows="4" placeholder="Jelaskan alasan kegiatan ini diadakan..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Tujuan Kegiatan</label>
                            <textarea name="tujuan" class="form-control" rows="3" placeholder="Apa yang ingin dicapai?"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Sasaran / Peserta</label>
                            <input type="text" name="sasaran" class="form-control" placeholder="Contoh: Mahasiswa Se-Indonesia">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Penutup</label>
                            <textarea name="penutup" class="form-control" rows="2" placeholder="Kalimat penutup proposal..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-cash-stack me-1"></i> II. Rencana Anggaran Biaya (RAB)</span>
                        <button type="button" class="btn btn-sm btn-light" onclick="addRow('rabTable')"><i class="bi bi-plus-lg"></i> Tambah Baris</button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="rabTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Rincian Kebutuhan</th>
                                        <th style="width: 80px;">Vol</th>
                                        <th style="width: 100px;">Satuan</th>
                                        <th>Harga Satuan</th>
                                        <th style="width: 50px;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><input type="text" name="rab_rincian[]" class="form-control form-control-sm" placeholder="Misal: Konsumsi Peserta"></td>
                                        <td><input type="number" name="rab_vol[]" class="form-control form-control-sm" value="1"></td>
                                        <td><input type="text" name="rab_sat[]" class="form-control form-control-sm" placeholder="Box/Org"></td>
                                        <td><input type="number" name="rab_harga[]" class="form-control form-control-sm" placeholder="0"></td>
                                        <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)"><i class="bi bi-trash"></i></button></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bagian Kanan: Panitia & Aksi -->
            <div class="col-lg-5">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-people-fill me-1"></i> III. Susunan Panitia</span>
                        <button type="button" class="btn btn-sm btn-light" onclick="addRow('panitiaTable')"><i class="bi bi-plus-lg"></i></button>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered" id="panitiaTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Jabatan</th>
                                    <th>Nama Mahasiswa</th>
                                    <th style="width: 50px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><input type="text" name="panitia_jabatan[]" class="form-control form-control-sm" value="Ketua Pelaksana"></td>
                                    <td><input type="text" name="panitia_nama[]" class="form-control form-control-sm" placeholder="Nama Lengkap"></td>
                                    <input type="hidden" name="panitia_nim[]" value="">
                                    <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)"><i class="bi bi-trash"></i></button></td>
                                </tr>
                                <tr>
                                    <td><input type="text" name="panitia_jabatan[]" class="form-control form-control-sm" value="Sekretaris"></td>
                                    <td><input type="text" name="panitia_nama[]" class="form-control form-control-sm" placeholder="Nama Lengkap"></td>
                                    <input type="hidden" name="panitia_nim[]" value="">
                                    <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)"><i class="bi bi-trash"></i></button></td>
                                </tr>
                                <tr>
                                    <td><input type="text" name="panitia_jabatan[]" class="form-control form-control-sm" value="Bendahara"></td>
                                    <td><input type="text" name="panitia_nama[]" class="form-control form-control-sm" placeholder="Nama Lengkap"></td>
                                    <input type="hidden" name="panitia_nim[]" value="">
                                    <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)"><i class="bi bi-trash"></i></button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card shadow-sm border-primary">
                    <div class="card-header bg-dark text-white">
                        <i class="bi bi-pen me-1"></i> Penandatangan (Pilih dari Profil)
                    </div>
                    <div class="card-body">
                        <?php
                        // Ambil data TTD dari profil user
                        $stmt_u = $conn->prepare("SELECT nama_ketua, nama_sekretaris, nama_bendahara, ttd_ketua, ttd_sekretaris, ttd_bendahara FROM users WHERE id_user = ?");
                        $stmt_u->bind_param("i", $user_id);
                        $stmt_u->execute();
                        $profile = $stmt_u->get_result()->fetch_assoc();
                        ?>
                        <div class="mb-3">
                            <label class="form-label small">Tanda Tangan 1 (Ketua Pelaksana)</label>
                            <select name="ttd_1" class="form-select form-select-sm mb-1" onchange="toggleCustom(this, 'box_custom_1')">
                                <option value="ketua"><?php echo htmlspecialchars($profile['nama_ketua'] ?? 'Ketua'); ?></option>
                                <option value="sekretaris"><?php echo htmlspecialchars($profile['nama_sekretaris'] ?? 'Sekretaris'); ?></option>
                                <option value="bendahara"><?php echo htmlspecialchars($profile['nama_bendahara'] ?? 'Bendahara'); ?></option>
                                <option value="custom">-- Nama Kustom --</option>
                                <option value="none">-- Tidak Ada --</option>
                            </select>
                            <div id="box_custom_1" class="d-none">
                                <input type="text" name="ttd_1_custom" class="form-control form-control-sm mb-1" placeholder="Nama Lengkap">
                                <input type="text" name="ttd_1_custom_jabatan" class="form-control form-control-sm mb-1" placeholder="Jabatan (Contoh: Kaprodi / Pembina)">
                                <input type="text" name="ttd_1_custom_nim" class="form-control form-control-sm mb-1" placeholder="NIM / NIDN">
                            </div>
                            <div id="custom_ttd_1_file" class="d-none">
                                <label class="small text-muted">Upload TTD (PNG Transparan):</label>
                                <input type="file" name="ttd_1_file" class="form-control form-control-sm" accept="image/png">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Tanda Tangan 2 (Sekretaris)</label>
                            <select name="ttd_2" class="form-select form-select-sm mb-1" onchange="toggleCustom(this, 'box_custom_2', 'custom_ttd_2_file')">
                                <option value="sekretaris"><?php echo htmlspecialchars($profile['nama_sekretaris'] ?? 'Sekretaris'); ?></option>
                                <option value="ketua"><?php echo htmlspecialchars($profile['nama_ketua'] ?? 'Ketua'); ?></option>
                                <option value="bendahara"><?php echo htmlspecialchars($profile['nama_bendahara'] ?? 'Bendahara'); ?></option>
                                <option value="custom">-- Nama Kustom --</option>
                                <option value="none">-- Tidak Ada --</option>
                            </select>
                            <div id="box_custom_2" class="d-none">
                                <input type="text" name="ttd_2_custom" class="form-control form-control-sm mb-1" placeholder="Nama Lengkap">
                                <input type="text" name="ttd_2_custom_jabatan" class="form-control form-control-sm mb-1" placeholder="Jabatan">
                                <input type="text" name="ttd_2_custom_nim" class="form-control form-control-sm mb-1" placeholder="NIM / NIDN">
                            </div>
                            <div id="custom_ttd_2_file" class="d-none">
                                <label class="small text-muted">Upload TTD (PNG Transparan):</label>
                                <input type="file" name="ttd_2_file" class="form-control form-control-sm" accept="image/png">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Tanda Tangan 3 (Mengetahui)</label>
                            <select name="ttd_3" class="form-select form-select-sm mb-1" onchange="toggleCustom(this, 'box_custom_3', 'custom_ttd_3_file')">
                                <option value="ketua"><?php echo htmlspecialchars($profile['nama_ketua'] ?? 'Ketua'); ?></option>
                                <option value="sekretaris"><?php echo htmlspecialchars($profile['nama_sekretaris'] ?? 'Sekretaris'); ?></option>
                                <option value="bendahara"><?php echo htmlspecialchars($profile['nama_bendahara'] ?? 'Bendahara'); ?></option>
                                <option value="custom">-- Nama Kustom --</option>
                                <option value="none">-- Tidak Ada --</option>
                            </select>
                            <div id="box_custom_3" class="d-none">
                                <input type="text" name="ttd_3_custom" class="form-control form-control-sm mb-1" placeholder="Nama Lengkap">
                                <input type="text" name="ttd_3_custom_jabatan" class="form-control form-control-sm mb-1" placeholder="Jabatan">
                                <input type="text" name="ttd_3_custom_nim" class="form-control form-control-sm mb-1" placeholder="NIM / NIDN">
                            </div>
                            <div id="custom_ttd_3_file" class="d-none">
                                <label class="small text-muted">Upload TTD (PNG Transparan):</label>
                                <input type="file" name="ttd_3_file" class="form-control form-control-sm" accept="image/png">
                            </div>
                        </div>
                        <script>
                        function toggleCustom(sel, boxId, fileId) {
                            const box = document.getElementById(boxId);
                            const fileBox = fileId ? document.getElementById(fileId) : null;
                            if (sel.value === 'custom') {
                                box.classList.remove('d-none');
                                if(fileBox) fileBox.classList.remove('d-none');
                                box.querySelectorAll('input').forEach(i => i.required = true);
                            } else {
                                box.classList.add('d-none');
                                if(fileBox) fileBox.classList.add('d-none');
                                box.querySelectorAll('input').forEach(i => i.required = false);
                            }
                        }
                        </script>
                        <div class="alert alert-info py-2 small">
                            <i class="bi bi-info-circle me-1"></i> Data TTD diambil dari menu <b>Profil</b>. Pastikan sudah mengunggah TTD transparan di sana.
                        </div>

                        <hr>
                        <p class="text-muted small">Setelah disimpan, sistem akan menghasilkan file PDF dengan Kop Surat & TTD Digital otomatis.</p>
                        <div class="row g-2">
                            <div class="col-6">
                                <button type="submit" name="simpan_draft" class="btn btn-outline-primary w-100 mb-2">
                                    <i class="bi bi-journal-text me-2"></i> Simpan Draft
                                </button>
                            </div>
                            <div class="col-6">
                                <button type="submit" name="simpan_proposal" class="btn btn-primary w-100 mb-2">
                                    <i class="bi bi-save me-2"></i> Simpan & Cetak
                                </button>
                            </div>
                        </div>
                        <a href="index.php?page=dashboard" class="btn btn-outline-secondary btn-sm w-100 mt-2">Batal</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function addRow(tableId) {
    const table = document.getElementById(tableId).getElementsByTagName('tbody')[0];
    const newRow = table.rows[0].cloneNode(true);
    
    // Clear inputs in new row
    const inputs = newRow.getElementsByTagName('input');
    for (let i = 0; i < inputs.length; i++) {
        if(inputs[i].type !== 'hidden') inputs[i].value = '';
    }
    
    table.appendChild(newRow);
}

function removeRow(btn) {
    const row = btn.parentNode.parentNode;
    const table = row.parentNode;
    if (table.rows.length > 1) {
        table.removeChild(row);
    } else {
        alert("Minimal harus ada satu baris!");
    }
}
</script>
