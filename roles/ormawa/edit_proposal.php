<?php
/**
 * File: roles/ormawa/edit_proposal.php
 * Deskripsi: Edit draft proposal yang sudah disimpan.
 */

check_role(['ormawa', 'bem', 'bpm']);
$user_id = $_SESSION['user_id'];
$id_proposal = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Ambil data lama
$stmt = $conn->prepare("SELECT * FROM proposal_otomatis WHERE id_proposal = ? AND id_user_ormawa = ?");
$stmt->bind_param("ii", $id_proposal, $user_id);
$stmt->execute();
$proposal = $stmt->get_result()->fetch_assoc();

if (!$proposal) {
    die("Proposal tidak ditemukan.");
}

// Ambil RAB
$rab = [];
$res_rab = $conn->query("SELECT * FROM proposal_rab WHERE id_proposal = $id_proposal");
while($r = $res_rab->fetch_assoc()) $rab[] = $r;

// Ambil Panitia
$panitia = [];
$res_pan = $conn->query("SELECT * FROM proposal_panitia WHERE id_proposal = $id_proposal");
while($p = $res_pan->fetch_assoc()) $panitia[] = $p;


// Proses Simpan Perubahan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['simpan_proposal']) || isset($_POST['simpan_draft']))) {
    $nama_kegiatan = sanitize_input($conn, $_POST['nama_kegiatan']);
    $latar_belakang = sanitize_input($conn, $_POST['latar_belakang']);
    $tujuan = sanitize_input($conn, $_POST['tujuan']);
    $sasaran = sanitize_input($conn, $_POST['sasaran']);
    $penutup = sanitize_input($conn, $_POST['penutup']);
    
    $ttd_1 = ($_POST['ttd_1'] === 'custom') ? sanitize_input($conn, $_POST['ttd_1_custom']) : sanitize_input($conn, $_POST['ttd_1']);
    $ttd_2 = ($_POST['ttd_2'] === 'custom') ? sanitize_input($conn, $_POST['ttd_2_custom']) : sanitize_input($conn, $_POST['ttd_2']);
    $ttd_3 = ($_POST['ttd_3'] === 'custom') ? sanitize_input($conn, $_POST['ttd_3_custom']) : sanitize_input($conn, $_POST['ttd_3']);
    
    // Upload Custom TTD Files
    $ttd_files = ['ttd_1_file' => $proposal['ttd_1_file'], 'ttd_2_file' => $proposal['ttd_2_file'], 'ttd_3_file' => $proposal['ttd_3_file']];
    $target_dir = "uploads/proposal_ttd/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

    foreach ($ttd_files as $key => $val) {
        if (isset($_FILES[$key]) && $_FILES[$key]['error'] == 0) {
            $ext = strtolower(pathinfo($_FILES[$key]["name"], PATHINFO_EXTENSION));
            if ($ext == 'png') {
                if (!empty($val) && file_exists($target_dir . $val)) @unlink($target_dir . $val);
                $newName = "custom_" . time() . "_" . $key . ".png";
                if (move_uploaded_file($_FILES[$key]["tmp_name"], $target_dir . $newName)) {
                    $ttd_files[$key] = $newName;
                }
            }
        }
    }

    $status = isset($_POST['simpan_draft']) ? 'Draft' : 'Final';

    // Update Header
    $stmt_up = $conn->prepare("UPDATE proposal_otomatis SET nama_kegiatan=?, latar_belakang=?, tujuan=?, sasaran=?, penutup=?, ttd_1_key=?, ttd_2_key=?, ttd_3_key=?, ttd_1_file=?, ttd_2_file=?, ttd_3_file=?, status=? WHERE id_proposal=? AND id_user_ormawa=?");
    $stmt_up->bind_param("ssssssssssssii", $nama_kegiatan, $latar_belakang, $tujuan, $sasaran, $penutup, $ttd_1, $ttd_2, $ttd_3, $ttd_files['ttd_1_file'], $ttd_files['ttd_2_file'], $ttd_files['ttd_3_file'], $status, $id_proposal, $user_id);
    
    if ($stmt_up->execute()) {
        // Hapus RAB & Panitia lama lalu insert baru (cara termudah untuk update tabel relasi)
        $conn->query("DELETE FROM proposal_rab WHERE id_proposal = $id_proposal");
        $conn->query("DELETE FROM proposal_panitia WHERE id_proposal = $id_proposal");

        // Simpan RAB
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

        // Simpan Panitia
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
    }
}
?>

<div class="container-fluid px-4 py-4">
    <h1 class="mt-4">Edit Proposal</h1>
    <form action="" method="POST" id="proposalForm">
        <div class="row">
            <div class="col-lg-7">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">I. Pendahuluan & Narasi</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nama Kegiatan</label>
                            <input type="text" name="nama_kegiatan" class="form-control" value="<?php echo htmlspecialchars($proposal['nama_kegiatan']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Latar Belakang</label>
                            <textarea name="latar_belakang" class="form-control" rows="4"><?php echo htmlspecialchars($proposal['latar_belakang']); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Tujuan Kegiatan</label>
                            <textarea name="tujuan" class="form-control" rows="3"><?php echo htmlspecialchars($proposal['tujuan']); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Sasaran / Peserta</label>
                            <input type="text" name="sasaran" class="form-control" value="<?php echo htmlspecialchars($proposal['sasaran']); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Penutup</label>
                            <textarea name="penutup" class="form-control" rows="2"><?php echo htmlspecialchars($proposal['penutup']); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                        <span>II. Rencana Anggaran Biaya (RAB)</span>
                        <button type="button" class="btn btn-sm btn-light" onclick="addRow('rabTable')">Tambah Baris</button>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered" id="rabTable">
                            <tbody>
                                <?php foreach($rab as $r): ?>
                                <tr>
                                    <td><input type="text" name="rab_rincian[]" class="form-control form-control-sm" value="<?php echo htmlspecialchars($r['rincian']); ?>"></td>
                                    <td style="width: 80px;"><input type="number" name="rab_vol[]" class="form-control form-control-sm" value="<?php echo $r['volume']; ?>"></td>
                                    <td style="width: 100px;"><input type="text" name="rab_sat[]" class="form-control form-control-sm" value="<?php echo htmlspecialchars($r['satuan']); ?>"></td>
                                    <td><input type="number" name="rab_harga[]" class="form-control form-control-sm" value="<?php echo $r['harga_satuan']; ?>"></td>
                                    <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)"><i class="bi bi-trash"></i></button></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <!-- Panitia & Signers Section (Similar to buat_proposal but loaded with data) -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                        <span>III. Susunan Panitia</span>
                        <button type="button" class="btn btn-sm btn-light" onclick="addRow('panitiaTable')"><i class="bi bi-plus-lg"></i></button>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered" id="panitiaTable">
                            <tbody>
                                <?php foreach($panitia as $p): ?>
                                <tr>
                                    <td><input type="text" name="panitia_jabatan[]" class="form-control form-control-sm" value="<?php echo htmlspecialchars($p['jabatan']); ?>"></td>
                                    <td><input type="text" name="panitia_nama[]" class="form-control form-control-sm" value="<?php echo htmlspecialchars($p['nama_mahasiswa']); ?>"></td>
                                    <input type="hidden" name="panitia_nim[]" value="">
                                    <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)"><i class="bi bi-trash"></i></button></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card shadow-sm border-primary">
                    <div class="card-header bg-dark text-white">Tanda Tangan</div>
                    <div class="card-body">
                        <?php
                        $stmt_u = $conn->prepare("SELECT nama_ketua, nama_sekretaris, nama_bendahara FROM users WHERE id_user = ?");
                        $stmt_u->bind_param("i", $user_id);
                        $stmt_u->execute();
                        $profile = $stmt_u->get_result()->fetch_assoc();

                        function getOptionValue($key, $profile) {
                            $core = ['ketua', 'sekretaris', 'bendahara', 'none'];
                            if (in_array($key, $core)) return $key;
                            return 'custom';
                        }
                        ?>
                        <div class="mb-3">
                            <label class="form-label small">Tanda Tangan 1</label>
                            <select name="ttd_1" class="form-select form-select-sm mb-1" onchange="toggleCustom(this, 'custom_1')">
                                <option value="ketua" <?php echo ($proposal['ttd_1_key'] == 'ketua') ? 'selected' : ''; ?>><?php echo htmlspecialchars($profile['nama_ketua'] ?? 'Ketua'); ?></option>
                                <option value="sekretaris" <?php echo ($proposal['ttd_1_key'] == 'sekretaris') ? 'selected' : ''; ?>><?php echo htmlspecialchars($profile['nama_sekretaris'] ?? 'Sekretaris'); ?></option>
                                <option value="bendahara" <?php echo ($proposal['ttd_1_key'] == 'bendahara') ? 'selected' : ''; ?>><?php echo htmlspecialchars($profile['nama_bendahara'] ?? 'Bendahara'); ?></option>
                                <option value="custom" <?php echo (!in_array($proposal['ttd_1_key'], ['ketua', 'sekretaris', 'bendahara', 'none'])) ? 'selected' : ''; ?>>-- Nama Kustom --</option>
                                <option value="none" <?php echo ($proposal['ttd_1_key'] == 'none') ? 'selected' : ''; ?>>-- Tidak Ada --</option>
                            </select>
                            <input type="text" name="ttd_1_custom" id="custom_1" class="form-control form-control-sm <?php echo (getOptionValue($proposal['ttd_1_key'], $profile) == 'custom') ? '' : 'd-none'; ?> mb-1" value="<?php echo (getOptionValue($proposal['ttd_1_key'], $profile) == 'custom') ? htmlspecialchars($proposal['ttd_1_key']) : ''; ?>" placeholder="Masukkan Nama & Jabatan">
                            <div id="custom_ttd_1_file" class="<?php echo (getOptionValue($proposal['ttd_1_key'], $profile) == 'custom') ? '' : 'd-none'; ?>">
                                <label class="small text-muted">Ganti TTD (PNG):</label>
                                <input type="file" name="ttd_1_file" class="form-control form-control-sm" accept="image/png">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label small">Tanda Tangan 2</label>
                            <select name="ttd_2" class="form-select form-select-sm mb-1" onchange="toggleCustom(this, 'custom_2', 'custom_ttd_2_file')">
                                <option value="sekretaris" <?php echo ($proposal['ttd_2_key'] == 'sekretaris') ? 'selected' : ''; ?>><?php echo htmlspecialchars($profile['nama_sekretaris'] ?? 'Sekretaris'); ?></option>
                                <option value="ketua" <?php echo ($proposal['ttd_2_key'] == 'ketua') ? 'selected' : ''; ?>><?php echo htmlspecialchars($profile['nama_ketua'] ?? 'Ketua'); ?></option>
                                <option value="bendahara" <?php echo ($proposal['ttd_2_key'] == 'bendahara') ? 'selected' : ''; ?>><?php echo htmlspecialchars($profile['nama_bendahara'] ?? 'Bendahara'); ?></option>
                                <option value="custom" <?php echo (!in_array($proposal['ttd_2_key'], ['ketua', 'sekretaris', 'bendahara', 'none'])) ? 'selected' : ''; ?>>-- Nama Kustom --</option>
                                <option value="none" <?php echo ($proposal['ttd_2_key'] == 'none') ? 'selected' : ''; ?>>-- Tidak Ada --</option>
                            </select>
                            <input type="text" name="ttd_2_custom" id="custom_2" class="form-control form-control-sm <?php echo (getOptionValue($proposal['ttd_2_key'], $profile) == 'custom') ? '' : 'd-none'; ?> mb-1" value="<?php echo (getOptionValue($proposal['ttd_2_key'], $profile) == 'custom') ? htmlspecialchars($proposal['ttd_2_key']) : ''; ?>" placeholder="Masukkan Nama & Jabatan">
                            <div id="custom_ttd_2_file" class="<?php echo (getOptionValue($proposal['ttd_2_key'], $profile) == 'custom') ? '' : 'd-none'; ?>">
                                <label class="small text-muted">Ganti TTD (PNG):</label>
                                <input type="file" name="ttd_2_file" class="form-control form-control-sm" accept="image/png">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small">Tanda Tangan 3</label>
                            <select name="ttd_3" class="form-select form-select-sm mb-1" onchange="toggleCustom(this, 'custom_3', 'custom_ttd_3_file')">
                                <option value="ketua" <?php echo ($proposal['ttd_3_key'] == 'ketua') ? 'selected' : ''; ?>><?php echo htmlspecialchars($profile['nama_ketua'] ?? 'Ketua'); ?></option>
                                <option value="sekretaris" <?php echo ($proposal['ttd_3_key'] == 'sekretaris') ? 'selected' : ''; ?>><?php echo htmlspecialchars($profile['nama_sekretaris'] ?? 'Sekretaris'); ?></option>
                                <option value="bendahara" <?php echo ($proposal['ttd_3_key'] == 'bendahara') ? 'selected' : ''; ?>><?php echo htmlspecialchars($profile['nama_bendahara'] ?? 'Bendahara'); ?></option>
                                <option value="custom" <?php echo (!in_array($proposal['ttd_3_key'], ['ketua', 'sekretaris', 'bendahara', 'none'])) ? 'selected' : ''; ?>>-- Nama Kustom --</option>
                                <option value="none" <?php echo ($proposal['ttd_3_key'] == 'none') ? 'selected' : ''; ?>>-- Tidak Ada --</option>
                            </select>
                            <input type="text" name="ttd_3_custom" id="custom_3" class="form-control form-control-sm <?php echo (getOptionValue($proposal['ttd_3_key'], $profile) == 'custom') ? '' : 'd-none'; ?> mb-1" value="<?php echo (getOptionValue($proposal['ttd_3_key'], $profile) == 'custom') ? htmlspecialchars($proposal['ttd_3_key']) : ''; ?>" placeholder="Masukkan Nama & Jabatan">
                            <div id="custom_ttd_3_file" class="<?php echo (getOptionValue($proposal['ttd_3_key'], $profile) == 'custom') ? '' : 'd-none'; ?>">
                                <label class="small text-muted">Ganti TTD (PNG):</label>
                                <input type="file" name="ttd_3_file" class="form-control form-control-sm" accept="image/png">
                            </div>
                        </div>

                        <script>
                        function toggleCustom(sel, inputId, fileId) {
                            const input = document.getElementById(inputId);
                            const fileBox = document.getElementById(fileId);
                            if (sel.value === 'custom') {
                                input.classList.remove('d-none');
                                fileBox.classList.remove('d-none');
                                input.required = true;
                            } else {
                                input.classList.add('d-none');
                                fileBox.classList.add('d-none');
                                input.required = false;
                            }
                        }
                        </script>

                        <div class="row g-2 mt-3">
                            <div class="col-6"><button type="submit" name="simpan_draft" class="btn btn-outline-primary w-100">Simpan Draft</button></div>
                            <div class="col-6"><button type="submit" name="simpan_proposal" class="btn btn-primary w-100">Simpan & Cetak</button></div>
                        </div>
                        <a href="index.php?page=arsip_proposal" class="btn btn-outline-secondary btn-sm w-100 mt-2">Batal</a>
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
    const inputs = newRow.getElementsByTagName('input');
    for (let i = 0; i < inputs.length; i++) { if(inputs[i].type !== 'hidden') inputs[i].value = ''; }
    table.appendChild(newRow);
}
function removeRow(btn) {
    const row = btn.parentNode.parentNode;
    if (row.parentNode.rows.length > 1) row.parentNode.removeChild(row);
}
</script>
