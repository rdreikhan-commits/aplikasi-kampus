<?php
/**
 * File: roles/ormawa/dashboard.php
 * Deskripsi: Dashboard lengkap dengan semua statistik saldo, proposal, dan notifikasi dana cair.
 *
 * --- PERBAIKAN ---
 * Menambahkan CSS untuk memperbaiki warna teks tebal (nama kegiatan) 
 * pada popup SweetAlert2 di mode gelap (baris 108-114).
 */
check_role(['ormawa']);

// Ambil data penting dari session
$user_id = $_SESSION['user_id'];
$nama_lengkap = $_SESSION['nama_lengkap'];
$status_akun = $_SESSION['status_akun'] ?? 'nonaktif';

// --- Query untuk Statistik SALDO ---
$stmt_saldo = $conn->prepare("SELECT saldo FROM users WHERE id_user = ?");
$stmt_saldo->bind_param("i", $user_id);
$stmt_saldo->execute();
$total_saldo = $stmt_saldo->get_result()->fetch_assoc()['saldo'] ?? 0;
$stmt_saldo->close();

$status_terpakai_arr = ['disetujui wr3, siap diajukan ke bendahara', 'diajukan ke bendahara', 'dana cair', 'lpj diajukan', 'lpj ditolak bkkh', 'lpj diverifikasi', 'selesai'];
$placeholders_terpakai = implode(',', array_fill(0, count($status_terpakai_arr), '?'));
$stmt_terpakai = $conn->prepare("SELECT SUM(dana_diajukan) AS total FROM pengajuan WHERE id_user_ormawa = ? AND TRIM(LOWER(status)) IN ($placeholders_terpakai)");
$types_terpakai = 'i' . str_repeat('s', count($status_terpakai_arr));
$params_terpakai = array_merge([$user_id], $status_terpakai_arr);
$stmt_terpakai->bind_param($types_terpakai, ...$params_terpakai);
$stmt_terpakai->execute();
$saldo_terpakai = $stmt_terpakai->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_terpakai->close();

$status_proses_arr = ['diajukan ke bem', 'diajukan ke bpm', 'verifikasi bkkh', 'verifikasi wr3'];
$placeholders_proses = implode(',', array_fill(0, count($status_proses_arr), '?'));
$stmt_diajukan = $conn->prepare("SELECT SUM(dana_diajukan) AS total FROM pengajuan WHERE id_user_ormawa = ? AND TRIM(LOWER(status)) IN ($placeholders_proses)");
$types_proses = 'i' . str_repeat('s', count($status_proses_arr));
$params_proses = array_merge([$user_id], $status_proses_arr);
$stmt_diajukan->bind_param($types_proses, ...$params_proses);
$stmt_diajukan->execute();
$saldo_dalam_proses = $stmt_diajukan->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_diajukan->close();

$sisa_saldo = $total_saldo - $saldo_terpakai - $saldo_dalam_proses;
$saldo_digunakan_dan_proses = $saldo_terpakai + $saldo_dalam_proses;

// --- Query untuk Statistik PROPOSAL ---
$stmt_total_proposal = $conn->prepare("SELECT COUNT(id_pengajuan) AS total FROM pengajuan WHERE id_user_ormawa = ?");
$stmt_total_proposal->bind_param("i", $user_id);
$stmt_total_proposal->execute();
$total_diajukan = $stmt_total_proposal->get_result()->fetch_assoc()['total'];
$stmt_total_proposal->close();

$status_akhir_arr = ['draft', 'ditolak bem', 'ditolak bpm', 'ditolak bkkh', 'ditolak wr3', 'lpj ditolak bkkh', 'selesai'];
$placeholders_akhir = implode(',', array_fill(0, count($status_akhir_arr), '?'));
$stmt_proses_count = $conn->prepare("SELECT COUNT(id_pengajuan) AS total FROM pengajuan WHERE id_user_ormawa = ? AND TRIM(LOWER(status)) NOT IN ($placeholders_akhir)");
$types_akhir = 'i' . str_repeat('s', count($status_akhir_arr));
$params_akhir = array_merge([$user_id], $status_akhir_arr);
$stmt_proses_count->bind_param($types_akhir, ...$params_akhir);
$stmt_proses_count->execute();
$total_proses = $stmt_proses_count->get_result()->fetch_assoc()['total'];
$stmt_proses_count->close();

// === PENAMBAHAN BARU: Logika untuk notifikasi dana cair ===
$notifikasi_cair_ormawa = [];
$stmt_notif_ormawa = $conn->prepare(
    "SELECT id_pengajuan, nama_kegiatan 
     FROM pengajuan 
     WHERE id_user_ormawa = ? AND status = 'Dana Cair' AND notif_cair_terlihat = 0"
);
$stmt_notif_ormawa->bind_param("i", $user_id);
$stmt_notif_ormawa->execute();
$result_notif_ormawa = $stmt_notif_ormawa->get_result();
while ($row_ormawa = $result_notif_ormawa->fetch_assoc()) {
    $notifikasi_cair_ormawa[] = $row_ormawa;
}
$stmt_notif_ormawa->close();
?>

<!-- Kustomisasi CSS -->
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
    .chart-container {
        position: relative;
        height: 300px;
        width: 100%;
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
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-4 dashboard-header">
        <div>
            <h1 class="h3 mb-1">Dashboard</h1>
            <p class="text-muted">Selamat Datang kembali, <?php echo htmlspecialchars($nama_lengkap); ?>!</p>
        </div>
    </div>

    <!-- Alert Status Akun -->
    <?php if ($status_akun == 'nonaktif'): ?>
    <div class="alert alert-danger d-flex align-items-center mb-4 shadow-sm" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-3 fs-4"></i>
        <div>
            <h5 class="alert-heading mb-1">Akun Dinonaktifkan!</h5>
            Anda tidak dapat membuat pengajuan baru sampai akun Anda diaktifkan kembali.
        </div>
    </div>
    <?php endif; ?>

    <!-- Visualisasi Dana dan Statistik Utama -->
    <div class="row">
        <!-- Kolom Grafik -->
        <div class="col-xl-7 mb-4">
            <div class="card border-4 shadow-sm h-100 card-hover">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0">Visualisasi Dana</h5>
                </div>
                <div class="card-body d-flex align-items-center justify-content-center">
                    <div class="chart-container">
                        <canvas id="danaChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <!-- Kolom Statistik Keuangan -->
        <div class="col-xl-5 mb-4 financial-stats">
            <div class="card border-start border-4 border-primary shadow-sm card-hover mb-3">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 bg-gradient-primary text-white rounded-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                            <i class="bi bi-wallet2 fs-4"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <p class="text-muted mb-1 small">Total Saldo Diberikan</p>
                            <h5 class="fw-bold mb-0">Rp <?php echo number_format($total_saldo, 0, ',', '.'); ?></h5>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card border-start border-4 border-warning shadow-sm card-hover mb-3">
                <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 bg-gradient-warning text-dark rounded-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                <i class="bi bi-cash-coin fs-4"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="text-muted mb-1 small">Saldo Terpakai & Diproses</p>
                                <h5 class="fw-bold mb-0">Rp <?php echo number_format($saldo_digunakan_dan_proses, 0, ',', '.'); ?></h5>
                            </div>
                        </div>
                </div>
            </div>
             <div class="card border-start border-4 border-success shadow-sm card-hover">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 bg-gradient-success text-white rounded-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                            <i class="bi bi-piggy-bank-fill fs-4"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <p class="text-muted mb-1 small">Sisa Saldo Tersedia</p>
                            <h5 class="fw-bold mb-0 text-success">Rp <?php echo number_format($sisa_saldo, 0, ',', '.'); ?></h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Statistik Proposal & Akses Cepat -->
    <div class="row">
        <div class="col-lg-4 col-md-6 mb-4">
             <div class="card border-4 shadow-sm h-100 card-hover">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center">
                         <div class="flex-shrink-0 bg-gradient-info text-white rounded-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                            <i class="bi bi-file-earmark-text-fill fs-4"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <p class="text-muted mb-1">Total Proposal Diajukan</p>
                            <h4 class="fw-bold mb-0"><?php echo $total_diajukan; ?> Proposal</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card border-4 shadow-sm h-100 card-hover">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 bg-secondary text-white rounded-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                            <i class="bi bi-hourglass-split fs-4"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <p class="text-muted mb-1">Proposal Dalam Proses</p>
                            <h4 class="fw-bold mb-0 "><?php echo $total_proses; ?> Proposal</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-12 mb-4">
            <div class="card shadow-sm h-100 border-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0"><i class="bi bi-person-badge-fill me-2"></i>Informasi Akun</h5>
                </div>
                <div class="card-body p-4">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item px-0 d-flex justify-content-between ">
                            <span class="text-muted">Nama Lengkap</span>
                            <strong><?php echo htmlspecialchars($nama_lengkap); ?></strong>
                        </li>
                        <li class="list-group-item px-0 d-flex justify-content-between">
                            <span class="text-muted">Peran</span>
                            <strong><?php echo strtoupper(htmlspecialchars($_SESSION['user_role'])); ?></strong>
                        </li>
                        <li class="list-group-item px-0 d-flex justify-content-between">
                            <span class="text-muted">Status Akun</span>
                            <span>
                                <?php if ($status_akun === 'aktif'): ?>
                                    <span class="badge bg-success-subtle text-success-emphasis rounded-pill">Aktif</span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger-emphasis rounded-pill">Nonaktif</span>
                                <?php endif; ?>
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Library Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<!-- Script untuk menginisialisasi Chart -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    const ctx = document.getElementById('danaChart').getContext('2d');
    const sisaSaldo = <?php echo $sisa_saldo; ?>;
    const danaDigunakan = <?php echo $saldo_digunakan_dan_proses; ?>;
    const totalSaldo = <?php echo $total_saldo; ?>;
    
    if (totalSaldo === 0) {
        const canvas = document.getElementById('danaChart');
        const parent = canvas.parentElement;
        parent.innerHTML = '<div class="text-center text-muted p-5"><i class="bi bi-info-circle fs-2 mb-2"></i><br>Belum ada data dana yang dapat divisualisasikan.</div>';
    } else {
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Sisa Saldo Tersedia', 'Dana Terpakai & Diproses'],
                datasets: [{
                    label: 'Distribusi Dana',
                    data: [sisaSaldo, danaDigunakan],
                    backgroundColor: [
                        'rgba(25, 135, 84, 0.8)', 
                        'rgba(255, 193, 7, 0.8)'
                    ],
                    borderColor: [
                        'rgba(25, 135, 84, 1)',
                        'rgba(255, 193, 7, 1)'
                    ],
                    borderWidth: 1,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            font: { size: 14 }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed !== null) {
                                    label += new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(context.parsed);
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>

<!-- === PENAMBAHAN BARU: Skrip untuk SweetAlert2 Notifikasi Dana Cair === -->
<?php if (!empty($notifikasi_cair_ormawa)): ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    function tandaiSudahDilihat(ids) {
        fetch('index.php?page=tandai_notif_terlihat', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ ids: ids })
        });
    }

    const notifikasi = <?php echo json_encode($notifikasi_cair_ormawa); ?>;
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
            title: 'Horee... Duitnya udah cair nih!',
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
