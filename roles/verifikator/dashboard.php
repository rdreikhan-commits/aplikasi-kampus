<?php
/**
 * File: dashboard.php
 * Deskripsi: Dashboard dinamis untuk semua verifikator (BEM, BPM, BKKH, WR3).
 * Menampilkan kartu saldo khusus untuk BEM dan BPM.
 *
 * --- PERBAIKAN ---
 * Menambahkan CSS untuk memperbaiki warna teks tebal (nama kegiatan) 
 * pada popup SweetAlert2 di mode gelap (baris 160-166).
 */
check_role(['bem', 'bpm', 'bkh', 'wr3']);
$user_role = $_SESSION['user_role'];
$user_id = $_SESSION['user_id'];
$nama_lengkap = $_SESSION['nama_lengkap'];


// ===================================================================
// BAGIAN 1: LOGIKA UNTUK KARTU SALDO PRIBADI (KHUSUS BEM & BPM)
// ===================================================================
$notifikasi_cair = []; // Inisialisasi di luar if
if (in_array($user_role, ['bem', 'bpm'])) {
    // Query untuk Statistik SALDO
    $stmt_saldo = $conn->prepare("SELECT saldo FROM users WHERE id_user = ?");
    $stmt_saldo->bind_param("i", $user_id);
    $stmt_saldo->execute();
    $total_saldo = $stmt_saldo->get_result()->fetch_assoc()['saldo'] ?? 0;
    $stmt_saldo->close();

    $query_terpakai = "SELECT SUM(dana_diajukan) AS total FROM pengajuan WHERE id_user_ormawa = ? AND TRIM(LOWER(status)) IN ('disetujui wr3, siap diajukan ke bendahara', 'diajukan ke bendahara', 'dana cair', 'lpj diajukan', 'lpj ditolak bkkh', 'lpj diverifikasi', 'selesai')";
    $stmt_terpakai = $conn->prepare($query_terpakai);
    $stmt_terpakai->bind_param("i", $user_id);
    $stmt_terpakai->execute();
    $saldo_terpakai = $stmt_terpakai->get_result()->fetch_assoc()['total'] ?? 0;
    $stmt_terpakai->close();

    $query_proses = "SELECT SUM(dana_diajukan) AS total FROM pengajuan WHERE id_user_ormawa = ? AND TRIM(LOWER(status)) IN ('diajukan ke bem', 'diajukan ke bpm', 'verifikasi bkkh', 'verifikasi wr3')";
    $stmt_diajukan = $conn->prepare($query_proses);
    $stmt_diajukan->bind_param("i", $user_id);
    $stmt_diajukan->execute();
    $saldo_dalam_proses = $stmt_diajukan->get_result()->fetch_assoc()['total'] ?? 0;
    $stmt_diajukan->close();

    $sisa_saldo = $total_saldo - $saldo_terpakai - $saldo_dalam_proses;

    // === PENAMBAHAN BARU: Logika untuk notifikasi dana cair ===
    $stmt_notif = $conn->prepare(
        "SELECT id_pengajuan, nama_kegiatan 
         FROM pengajuan 
         WHERE id_user_ormawa = ? AND status = 'Dana Cair' AND notif_cair_terlihat = 0"
    );
    $stmt_notif->bind_param("i", $user_id);
    $stmt_notif->execute();
    $result_notif = $stmt_notif->get_result();
    while ($row = $result_notif->fetch_assoc()) {
        $notifikasi_cair[] = $row;
    }
    $stmt_notif->close();
}


// ===================================================================
// BAGIAN 2: LOGIKA UNTUK TUGAS VERIFIKASI (UNTUK SEMUA VERIFIKATOR)
// ===================================================================
$page_title = "Dashboard Verifikator";
$status_proposal_to_check = '';

// 1. MENENTUKAN TUGAS UTAMA (VERIFIKASI PROPOSAL) BERDASARKAN PERAN
if ($user_role == 'bem') {
    $status_proposal_to_check = 'Diajukan Ke BEM';
    $page_title = "Dashboard BEM";
} elseif ($user_role == 'bpm') {
    $status_proposal_to_check = 'Diajukan Ke BPM';
    $page_title = "Dashboard BPM";
} elseif ($user_role == 'bkh') {
    $status_proposal_to_check = 'Verifikasi BKKH';
    $page_title = "Dashboard BKKH";
} elseif ($user_role == 'wr3') {
    $status_proposal_to_check = 'Verifikasi WR3';
    $page_title = "Dashboard WR3";
}

// Query untuk mengambil data proposal yang perlu diverifikasi
$sql_proposal = "SELECT p.id_pengajuan, p.nama_kegiatan, p.tanggal_pengajuan, p.dana_diajukan, u.nama_lengkap AS nama_ormawa 
                 FROM pengajuan p JOIN users u ON p.id_user_ormawa = u.id_user 
                 WHERE p.status = ? ORDER BY p.tanggal_pengajuan DESC";
$stmt_proposal = $conn->prepare($sql_proposal);
$stmt_proposal->bind_param("s", $status_proposal_to_check);
$stmt_proposal->execute();
$result_proposal = $stmt_proposal->get_result();
$count_proposal = $result_proposal->num_rows;

// 2. INISIALISASI UNTUK TUGAS-TUGAS KHUSUS
$result_lpj = null;
$result_siap_diajukan = null;
$count_lpj = 0;
$count_siap_diajukan = 0;
$status_lpj_to_check = '';
$count_butuh_nomor = 0; 

// 3. MENENTUKAN TUGAS KHUSUS (HANYA UNTUK BKKH SEKARANG)
if ($user_role == 'bkh') {
    $status_lpj_to_check = 'LPJ Diajukan';
    
    $status_siap_bendahara = 'Disetujui WR3, Siap Diajukan ke Bendahara';
    $sql_siap_data = "SELECT p.id_pengajuan, p.nama_kegiatan, p.dana_diajukan, u.nama_lengkap AS nama_ormawa FROM pengajuan p JOIN users u ON p.id_user_ormawa = u.id_user WHERE status = ? ORDER BY p.tanggal_pengajuan DESC";
    $stmt_siap_data = $conn->prepare($sql_siap_data);
    $stmt_siap_data->bind_param("s", $status_siap_bendahara);
    $stmt_siap_data->execute();
    $result_siap_diajukan = $stmt_siap_data->get_result();
    $count_siap_diajukan = $result_siap_diajukan->num_rows;

    $status_arsip = [
        'Disetujui WR3, Siap Diajukan ke Bendahara', 'Diajukan ke Bendahara',
        'Dana Cair', 'LPJ Verifikasi BKKH', 'LPJ Ditolak BKKH', 'Selesai'
    ];
    $placeholders_arsip = implode(',', array_fill(0, count($status_arsip), '?'));
    $sql_butuh_nomor = "SELECT COUNT(id_pengajuan) AS total FROM pengajuan WHERE status IN ($placeholders_arsip) AND (nomor_surat IS NULL OR nomor_surat = '')";
    
    $stmt_butuh_nomor = $conn->prepare($sql_butuh_nomor);
    $types_arsip = str_repeat('s', count($status_arsip));
    $stmt_butuh_nomor->bind_param($types_arsip, ...$status_arsip);
    $stmt_butuh_nomor->execute();
    $result_butuh_nomor = $stmt_butuh_nomor->get_result();
    $count_butuh_nomor = $result_butuh_nomor->fetch_assoc()['total'] ?? 0;
}

if (!empty($status_lpj_to_check)) {
    $sql_lpj_data = "SELECT p.id_pengajuan, p.nama_kegiatan, p.tanggal_update, u.nama_lengkap AS nama_ormawa FROM pengajuan p JOIN users u ON p.id_user_ormawa = u.id_user WHERE p.status = ? ORDER BY p.tanggal_update DESC";
    $stmt_lpj_data = $conn->prepare($sql_lpj_data);
    $stmt_lpj_data->bind_param("s", $status_lpj_to_check);
    $stmt_lpj_data->execute();
    $result_lpj = $stmt_lpj_data->get_result();
    $count_lpj = $result_lpj->num_rows;
}
?>

<!-- Kustomisasi CSS untuk efek hover, gradient, dan responsivitas -->
<style>
    .card-hover {
        transition: transform .2s ease-in-out, box-shadow .2s ease-in-out;
    }
    .card-hover:hover {
        transform: translateY(-5px);
        box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
    }
    .bg-gradient-primary {
        background: linear-gradient(45deg, #0d6efd, #6f42c1);
    }
    .bg-gradient-success {
        background: linear-gradient(45deg, #198754, #20c997);
    }
    .bg-gradient-warning {
        background: linear-gradient(45deg, #ffc107, #fd7e14);
    }
    .bg-gradient-info {
        background: linear-gradient(45deg, #0dcaf0, #0d6efd);
    }
    
    /* --- PERBAIKAN DARK MODE SWEETALERT --- */
    @media (prefers-color-scheme: dark) {
        /* Target tag <strong> di dalam konten popup SweetAlert2 */
        .swal2-popup .swal2-html-container strong {
            /* Gunakan warna teks default popup (biasanya sudah terang di dark mode) */
            color: inherit; 
        }
    }
    /* --- AKHIR PERBAIKAN --- */
</style>

<div class="container-fluid px-4 py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
        <div>
            <h1 class="h3 mb-1"><?php echo $page_title; ?></h1>
            <p class="text-muted">Selamat Datang kembali, <?php echo htmlspecialchars($nama_lengkap); ?>!</p>
        </div>
    </div>
    
    <!-- Notifikasi untuk BKKH jika ada surat tanpa nomor -->
<?php if ($user_role == 'bkh' && $count_butuh_nomor > 0): ?>
    <div class="alert alert-danger border-start border-5 border-danger shadow-sm mb-4" role="alert">
        <div class="d-flex align-items-center">
            <div class="flex-shrink-0">
                <i class="bi bi-bell-fill fs-2 text-danger"></i>
            </div>
            <div class="flex-grow-1 ms-3">
                <h4 class="alert-heading fw-bold">Wahhh........</h4>
                <p class="mb-0">Kayanya ada yang butuh No surat untuk surat balasan nih. Coba cek di menu <a href="index.php?page=arsip_surat" class="alert-link">Arsip Surat</a>.</p>
            </div>
        </div>
    </div>
<?php endif; ?>



    <!-- Tampilkan Bagian Kartu Saldo HANYA untuk BEM dan BPM -->
    <?php if (in_array($user_role, ['bem', 'bpm'])): ?>
        <h2 class="h5 mb-3 text-muted">Status Dana Anda</h2>
        <div class="row">
            <div class="col-xl-4 col-md-6 mb-4">
               <div class="card border-start border-5 border-primary shadow-sm h-100 card-hover">
                    <div class="card-body p-4 d-flex align-items-center">
                        <div class="flex-shrink-0 bg-gradient-primary text-white rounded-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;"><i class="bi bi-wallet2 fs-4"></i></div>
                        <div class="flex-grow-1 ms-3">
                            <p class="text-muted mb-1 small">Total Saldo Diberikan</p>
                            <h5 class="fw-bold mb-0">Rp <?php echo number_format($total_saldo, 0, ',', '.'); ?></h5>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card border-start border-5 border-warning shadow-sm h-100 card-hover">
                    <div class="card-body p-4 d-flex align-items-center">
                        <div class="flex-shrink-0 bg-gradient-warning text-dark rounded-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;"><i class="bi bi-cash-coin fs-4"></i></div>
                        <div class="flex-grow-1 ms-3">
                            <p class="text-muted mb-1 small">Saldo Terpakai & Diproses</p>
                            <h5 class="fw-bold mb-0">Rp <?php echo number_format($saldo_terpakai + $saldo_dalam_proses, 0, ',', '.'); ?></h5>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-12 mb-4">
                <div class="card border-start border-5 border-success shadow-sm h-100 card-hover">
                    <div class="card-body p-4 d-flex align-items-center">
                        <div class="flex-shrink-0 bg-gradient-success text-white rounded-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;"><i class="bi bi-piggy-bank-fill fs-4"></i></div>
                        <div class="flex-grow-1 ms-3">
                            <p class="text-muted mb-1 small">Sisa Saldo Tersedia</p>
                            <h5 class="fw-bold mb-0 text-success">Rp <?php echo number_format($sisa_saldo, 0, ',', '.'); ?></h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <hr class="my-4">
    <?php endif; ?>

    <!-- Bagian Tugas Verifikasi -->
    <h2 class="h5 mb-3 text-muted">Tugas Verifikasi Anda</h2>
    <div class="row">
        <!-- Kartu: Verifikasi Proposal (Selalu Tampil) -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-start border-5 border-primary shadow-sm h-100 card-hover">
                <div class="card-body p-4 d-flex align-items-center">
                    <div class="flex-shrink-0 bg-gradient-info text-white rounded-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;"><i class="bi bi-file-earmark-text-fill fs-2"></i></div>
                    <div class="flex-grow-1 ms-3 text-end">
                        <h3 class="fw-bold mb-0"><?php echo $count_proposal; ?></h3>
                        <p class="text-muted mb-0">Verifikasi Proposal</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Kartu: Verifikasi LPJ (Hanya untuk BKKH) -->
        <?php if ($user_role == 'bkh'): ?>
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-start border-5 border-warning shadow-sm h-100 card-hover">
                <div class="card-body p-4 d-flex align-items-center">
                    <div class="flex-shrink-0 bg-gradient-warning text-dark rounded-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;"><i class="bi bi-journal-check fs-2"></i></div>
                    <div class="flex-grow-1 ms-3 text-end">
                        <h3 class="fw-bold mb-0"><?php echo $count_lpj; ?></h3>
                        <p class="text-muted mb-0">Verifikasi LPJ</p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Kartu: Siap ke Bendahara (Hanya untuk BKKH) -->
        <?php if ($user_role == 'bkh'): ?>
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-start border-5 border-success shadow-sm h-100 card-hover">
                <div class="card-body p-4 d-flex align-items-center">
                    <div class="flex-shrink-0 bg-gradient-success text-white rounded-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;"><i class="bi bi-send-check-fill fs-2"></i></div>
                    <div class="flex-grow-1 ms-3 text-end">
                        <h3 class="fw-bold mb-0"><?php echo $count_siap_diajukan; ?></h3>
                        <p class="text-muted mb-0">Siap ke Bendahara</p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Tabel Verifikasi Proposal -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-white border-0 py-3"><i class="bi bi-table me-2"></i>Tabel Verifikasi Proposal</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Nama Kegiatan</th>
                            <th>Ormawa</th>
                            <th>Tanggal Kegiatan</th>
                            <th>Dana Diajukan</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result_proposal->num_rows > 0): ?>
                            <?php $i = 1; while($row = $result_proposal->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td><?php echo htmlspecialchars($row['nama_kegiatan']); ?></td>
                                <td><?php echo htmlspecialchars($row['nama_ormawa']); ?></td>
                                <td><?php echo date('d M Y', strtotime($row['tanggal_pengajuan'])); ?></td>
                                <td>Rp <?php echo number_format($row['dana_diajukan'], 0, ',', '.'); ?></td>
                                <td class="text-center"><a href="index.php?page=verifikasi&id=<?php echo $row['id_pengajuan']; ?>" class="btn btn-primary btn-sm">Periksa Proposal</a></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">Tidak ada proposal untuk diverifikasi saat ini.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tabel Khusus BKKH: Siap Diajukan ke Bendahara -->
    <?php if ($user_role == 'bkh' && $result_siap_diajukan && $result_siap_diajukan->num_rows > 0): ?>
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-white border-0 py-3"><i class="bi bi-send-check me-2"></i>Tabel Proposal Siap Diajukan ke Bendahara</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Nama Kegiatan</th>
                                <th>Ormawa</th>
                                <th>Dana Diajukan</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1; while($row = $result_siap_diajukan->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td><?php echo htmlspecialchars($row['nama_kegiatan']); ?></td>
                                <td><?php echo htmlspecialchars($row['nama_ormawa']); ?></td>
                                <td>Rp <?php echo number_format($row['dana_diajukan'], 0, ',', '.'); ?></td>
                                <td class="text-center">
                                    <form action="index.php?page=ajukan_pencairan" method="POST" class="d-inline-block me-1">
                                        <input type="hidden" name="id" value="<?php echo $row['id_pengajuan']; ?>">
                                        <button type="submit" class="btn btn-success btn-sm">Ajukan Ke Bendahara</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Tabel Khusus BKKH: Verifikasi LPJ -->
    <?php if ($user_role == 'bkh' && $result_lpj && $result_lpj->num_rows > 0): ?>
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-white border-0 py-3"><i class="bi bi-journal-check me-2"></i>Tabel Verifikasi LPJ</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Nama Kegiatan</th>
                            <th>Ormawa</th>
                            <th>Tanggal Update</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; while($row = $result_lpj->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $i++; ?></td>
                            <td><?php echo htmlspecialchars($row['nama_kegiatan']); ?></td>
                            <td><?php echo htmlspecialchars($row['nama_ormawa']); ?></td>
                            <td><?php echo date('d M Y, H:i', strtotime($row['tanggal_update'])); ?></td>
                            <td class="text-center"><a href="index.php?page=verifikasi_lpj&id=<?php echo $row['id_pengajuan']; ?>" class="btn btn-warning btn-sm">Periksa LPJ</a></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- === PENAMBAHAN BARU: Skrip untuk SweetAlert2 Notifikasi Dana Cair === -->
<?php if (!empty($notifikasi_cair)): ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Fungsi untuk menandai notifikasi sebagai sudah dilihat
    function tandaiSudahDilihat(ids) {
        fetch('index.php?page=tandai_notif_terlihat', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ ids: ids })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                console.error('Gagal menandai notifikasi.');
            }
        });
    }

    const notifikasi = <?php echo json_encode($notifikasi_cair); ?>;
    const idsNotifikasi = notifikasi.map(n => n.id_pengajuan);
    
    let index = 0;
    function tampilkanNotifikasiBerikutnya() {
        if (index >= notifikasi.length) {
            if (idsNotifikasi.length > 0) {
                tandaiSudahDilihat(idsNotifikasi);
            }
            return;
        }

        const notif = notifikasi[index];
        Swal.fire({
            title: 'Horee... Dana sudah cair nih!',
            html: `Dana untuk kegiatan "<strong>${notif.nama_kegiatan}</strong>" telah dicairkan.<br><br>Jangan lupa LPJ nya yah biar bisa Ngajuin lagi 😉`,
            icon: 'success',
            confirmButtonText: 'Asiiik!',
        }).then(() => {
            index++;
            tampilkanNotifikasiBerikutnya();
        });
    }

    tampilkanNotifikasiBerikutnya();
});
</script>
<?php endif; ?>
