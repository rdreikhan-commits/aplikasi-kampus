<?php
/**
 * File: roles/admin/manage_saldo.php
 * Deskripsi: Halaman khusus untuk BKKH dan WR3 mengelola dan memantau rincian saldo pengguna.
 */
// PERBAIKAN: Menambahkan 'wr3' ke dalam array peran yang diizinkan
check_role(['bkh', 'wr3']);

// Ambil role pengguna yang sedang login untuk membedakan tampilan
$current_user_role = $_SESSION['user_role'] ?? null;

// 1. Ambil semua data pengguna yang relevan (ormawa, bem, bpm)
$users_query = "SELECT id_user, nama_lengkap, role, saldo FROM users WHERE role IN ('ormawa', 'bem', 'bpm') ORDER BY nama_lengkap ASC";
$users_stmt = $conn->prepare($users_query);
if ($users_stmt === false) {
    die("ERROR: Gagal mempersiapkan query pengguna. " . htmlspecialchars($conn->error));
}
$users_stmt->execute();
$users_result = $users_stmt->get_result();
$users = $users_result->fetch_all(MYSQLI_ASSOC);
$user_ids = array_column($users, 'id_user');

// Inisialisasi array untuk menampung data yang akan diolah
$proposals_by_user = [];
$saldo_data = [];

// 2. Jika ada pengguna, ambil SEMUA pengajuan mereka dalam satu query besar untuk efisiensi
if (!empty($user_ids)) {
    $placeholders = implode(',', array_fill(0, count($user_ids), '?'));
    $types = str_repeat('i', count($user_ids));

    $proposals_query = "
        SELECT id_user_ormawa, nama_kegiatan, dana_diajukan, tanggal_pengajuan, status
        FROM pengajuan
        WHERE id_user_ormawa IN ($placeholders)
        ORDER BY tanggal_pengajuan DESC";

    $proposals_stmt = $conn->prepare($proposals_query);
    if ($proposals_stmt === false) {
        die("ERROR: Gagal mempersiapkan query pengajuan. " . htmlspecialchars($conn->error));
    }
    $proposals_stmt->bind_param($types, ...$user_ids);
    $proposals_stmt->execute();
    $proposals_result = $proposals_stmt->get_result();

    // 3. Olah data pengajuan dan kelompokkan berdasarkan user_id, sekaligus hitung total saldo
    while ($p_row = $proposals_result->fetch_assoc()) {
        $user_id = $p_row['id_user_ormawa'];

        // Kelompokkan proposal berdasarkan pengguna
        if (!isset($proposals_by_user[$user_id])) {
            $proposals_by_user[$user_id] = [];
        }
        $proposals_by_user[$user_id][] = $p_row;

        // Inisialisasi data saldo jika belum ada
        if (!isset($saldo_data[$user_id])) {
            $saldo_data[$user_id] = ['terpakai' => 0, 'proses' => 0];
        }

        // Tentukan status dan kalkulasi saldo
        $status_clean = trim(strtolower($p_row['status']));
        $status_terpakai = ['disetujui wr3, siap diajukan ke bendahara', 'diajukan ke bendahara', 'dana cair', 'lpj diajukan', 'lpj ditolak bkkh', 'lpj diverifikasi', 'selesai'];
        $status_proses = ['diajukan ke bem', 'diajukan ke bpm', 'verifikasi bkkh', 'verifikasi wr3'];

        if (in_array($status_clean, $status_terpakai)) {
            $saldo_data[$user_id]['terpakai'] += $p_row['dana_diajukan'];
        } elseif (in_array($status_clean, $status_proses)) {
            $saldo_data[$user_id]['proses'] += $p_row['dana_diajukan'];
        }
    }
}
?>

<div class="container-fluid px-4">
    <h3 class="mt-4">Rincian Saldo</h3>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item active">Daftar Rincian Saldo Pengguna</li>
    </ol>

    <div class="card shadow-sm">
        <div class="card-header">
            <i class="bi bi-table me-1"></i>
            Data Saldo Ormawa, BEM, dan BPM
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Nama Ormawa</th>
                            <th>Saldo Awal</th>
                            <th>Total Terpakai & Diproses</th>
                            <th>Sisa Saldo</th>
                            <th>Rincian Kegiatan</th>
                            <?php // PERBAIKAN: Hanya tampilkan kolom Aksi untuk BKKH ?>
                            <?php if ($current_user_role === 'bkh'): ?>
                                <th>Aksi</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($users)):
                            $no = 1;
                            foreach ($users as $row):
                                // Ambil data yang sudah diolah
                                $user_id = $row['id_user'];
                                $saldo_awal = $row['saldo'] ?? 0;
                                $saldo_terpakai = $saldo_data[$user_id]['terpakai'] ?? 0;
                                $saldo_dalam_proses = $saldo_data[$user_id]['proses'] ?? 0;
                                $total_penggunaan = $saldo_terpakai + $saldo_dalam_proses;
                                $sisa_saldo = $saldo_awal - $total_penggunaan;
                                $user_proposals = $proposals_by_user[$user_id] ?? [];
                        ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($row['nama_lengkap']); ?><br>
                                        <small class="text-muted"><?php echo strtoupper($row['role']); ?></small>
                                    </td>
                                    <td>Rp <?php echo number_format($saldo_awal, 0, ',', '.'); ?></td>
                                    <td class="text-danger">Rp <?php echo number_format($total_penggunaan, 0, ',', '.'); ?></td>
                                    <td class="fw-bold text-success">Rp <?php echo number_format($sisa_saldo, 0, ',', '.'); ?></td>
                                    <td>
                                        <?php if (!empty($user_proposals)): ?>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#rincianModal-<?php echo $user_id; ?>">
                                                <i class="bi bi-eye-fill"></i> Lihat Rincian
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted">Belum ada pengajuan</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php // PERBAIKAN: Hanya tampilkan tombol Atur untuk BKKH ?>
                                    <?php if ($current_user_role === 'bkh'): ?>
                                    <td>
                                        <a href="index.php?page=atur_saldo&id=<?php echo $row['id_user']; ?>" class="btn btn-sm btn-info" title="Atur Saldo">
                                            <i class="bi bi-wallet2"></i> Atur
                                        </a>
                                    </td>
                                    <?php endif; ?>
                                </tr>

                                <!-- Modal untuk setiap pengguna -->
                                <?php if (!empty($user_proposals)): ?>
                                <div class="modal fade" id="rincianModal-<?php echo $user_id; ?>" tabindex="-1" aria-labelledby="rincianModalLabel-<?php echo $user_id; ?>" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="rincianModalLabel-<?php echo $user_id; ?>">Rincian Kegiatan: <?php echo htmlspecialchars($row['nama_lengkap']); ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <ul class="list-group">
                                                    <?php foreach ($user_proposals as $proposal): ?>
                                                        <li class="list-group-item d-flex justify-content-between align-items-start">
                                                            <div class="ms-2 me-auto">
                                                                <div class="fw-bold"><?php echo htmlspecialchars($proposal['nama_kegiatan']); ?></div>
                                                                <small class="text-muted">Diajukan pada: <?php echo date('d M Y', strtotime($proposal['tanggal_pengajuan'])); ?></small>
                                                            </div>
                                                            <span class="badge bg-primary rounded-pill">Rp <?php echo number_format($proposal['dana_diajukan'], 0, ',', '.'); ?></span>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>

                            <?php endforeach;
                        else: 
                            // PERBAIKAN: Sesuaikan colspan berdasarkan role
                            $colspan = ($current_user_role === 'bkh') ? 7 : 6;
                        ?>
                            <tr><td colspan="<?php echo $colspan; ?>" class="text-center">Tidak ada pengguna (Ormawa/BEM/BPM) yang terdaftar.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

