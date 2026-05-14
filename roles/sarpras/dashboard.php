<?php
/**
 * File: roles/sarpras/dashboard.php
 */

check_role(['sarpras', 'sarpras_barang']);
$role = $_SESSION['user_role'];

// Ambil Statistik
$count_ruangan_pending = $conn->query("SELECT COUNT(*) FROM peminjaman_tempat WHERE status_bkkh = 'Diverifikasi' AND status_sarpras = 'Pending'")->fetch_row()[0];
$count_ruangan_total = $conn->query("SELECT COUNT(*) FROM peminjaman_tempat")->fetch_row()[0];

$count_barang_pending = $conn->query("SELECT COUNT(*) FROM peminjaman_barang WHERE status_bkkh = 'Diverifikasi' AND status_sarpras = 'Pending'")->fetch_row()[0];
$count_barang_total = $conn->query("SELECT COUNT(*) FROM peminjaman_barang")->fetch_row()[0];
?>

<style>
    .glass-card {
        background: rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        transition: all 0.3s ease;
    }
    .glass-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.2);
    }
    .stat-icon {
        width: 60px;
        height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 15px;
        font-size: 1.5rem;
        margin-bottom: 1rem;
    }
    .gradient-ruangan { background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%); }
    .gradient-barang { background: linear-gradient(135deg, #10b981 0%, #3b82f6 100%); }
    .gradient-total { background: linear-gradient(135deg, #f59e0b 0%, #ef4444 100%); }
</style>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1 fw-bold text-gradient">Dashboard Sarpras</h1>
            <p class="text-muted">Manajemen Fasilitas & Inventaris Kampus</p>
        </div>
        <div class="badge bg-primary-subtle text-primary p-2 px-3 rounded-pill">
            <i class="bi bi-shield-check me-2"></i> Role: <?php echo ($role == 'sarpras') ? 'Sarpras Ruangan' : 'Sarpras Barang'; ?>
        </div>
    </div>

    <div class="row">
        <?php if ($role == 'sarpras'): ?>
        <!-- Card Ruangan -->
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card glass-card h-100 shadow-sm border-0">
                <div class="card-body p-4 text-center">
                    <div class="stat-icon gradient-ruangan text-white mx-auto shadow">
                        <i class="bi bi-building"></i>
                    </div>
                    <h5 class="fw-bold mb-1">Verifikasi Ruangan</h5>
                    <p class="text-muted small mb-3">Persetujuan izin pemakaian gedung</p>
                    <div class="d-flex justify-content-center gap-3 mb-4">
                        <div class="text-center">
                            <h4 class="fw-bold mb-0 text-primary"><?php echo $count_ruangan_pending; ?></h4>
                            <span class="text-muted extra-small">Menunggu</span>
                        </div>
                        <div class="vr"></div>
                        <div class="text-center">
                            <h4 class="fw-bold mb-0"><?php echo $count_ruangan_total; ?></h4>
                            <span class="text-muted extra-small">Total Pengajuan</span>
                        </div>
                    </div>
                    <a href="index.php?page=sarpras_verifikasi_ruangan" class="btn btn-primary w-100 rounded-pill py-2 shadow-sm">
                        Kelola Sekarang <i class="bi bi-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($role == 'sarpras_barang'): ?>
        <!-- Card Barang -->
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card glass-card h-100 shadow-sm border-0">
                <div class="card-body p-4 text-center">
                    <div class="stat-icon gradient-barang text-white mx-auto shadow">
                        <i class="bi bi-tools"></i>
                    </div>
                    <h5 class="fw-bold mb-1">Verifikasi Barang</h5>
                    <p class="text-muted small mb-3">Persetujuan peminjaman sarana</p>
                    <div class="d-flex justify-content-center gap-3 mb-4">
                        <div class="text-center">
                            <h4 class="fw-bold mb-0 text-success"><?php echo $count_barang_pending; ?></h4>
                            <span class="text-muted extra-small">Menunggu</span>
                        </div>
                        <div class="vr"></div>
                        <div class="text-center">
                            <h4 class="fw-bold mb-0"><?php echo $count_barang_total; ?></h4>
                            <span class="text-muted extra-small">Total Pengajuan</span>
                        </div>
                    </div>
                    <a href="index.php?page=sarpras_verifikasi_barang" class="btn btn-success w-100 rounded-pill py-2 shadow-sm">
                        Kelola Sekarang <i class="bi bi-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Card Master Barang -->
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card glass-card h-100 shadow-sm border-0">
                <div class="card-body p-4 text-center">
                    <div class="stat-icon gradient-total text-white mx-auto shadow">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <h5 class="fw-bold mb-1">Inventaris Barang</h5>
                    <p class="text-muted small mb-3">Manajemen stok & katalog</p>
                    <div class="h4 fw-bold text-warning mt-3 mb-4">
                        <i class="bi bi-archive me-2"></i> Kelola Stok
                    </div>
                    <a href="index.php?page=manage_barang" class="btn btn-outline-warning w-100 rounded-pill py-2 border-2">
                        Buka Katalog <i class="bi bi-gear ms-2"></i>
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
