<?php
/**
 * File: roles/bendahara/dashboard.php
 * Deskripsi: Halaman dashboard untuk Bendahara dengan perbaikan total.
 */

check_role(['bendahara']);

// === PERUBAHAN: Query sekarang mengambil 'nomor_surat' untuk validasi ===
$stmt = $conn->prepare(
    "SELECT p.id_pengajuan, p.nama_kegiatan, p.dana_diajukan, p.tanggal_pengajuan, u.nama_lengkap AS nama_ormawa, p.nomor_surat 
     FROM pengajuan p 
     JOIN users u ON p.id_user_ormawa = u.id_user 
     WHERE p.status = 'Diajukan ke Bendahara' 
     ORDER BY p.tanggal_pengajuan ASC"
);
$stmt->execute();
$result_cair = $stmt->get_result();
$proposals_to_cair = $result_cair->fetch_all(MYSQLI_ASSOC); // Ambil semua data ke array
$count_cair = count($proposals_to_cair);

// === PENAMBAHAN BARU: Logika untuk memeriksa apakah ada surat tanpa nomor ===
$ada_yang_belum_bernomor = false;
if ($count_cair > 0) {
    foreach ($proposals_to_cair as $proposal) {
        if (empty($proposal['nomor_surat'])) {
            $ada_yang_belum_bernomor = true;
            break; // Hentikan loop jika sudah ditemukan satu
        }
    }
}

?>
<div class="container-fluid px-4">
    <h1 class="mt-4">Dashboard Bendahara</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item active">Pencairan Dana</li>
    </ol>

    <!-- Notifikasi dari aksi sebelumnya -->
    <?php if (isset($_GET['status']) && $_GET['status'] === 'verifikasi_sukses'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Verifikasi pengajuan berhasil disimpan.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['error']) && $_GET['error'] === 'verifikasi_gagal'): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            Verifikasi pengajuan gagal disimpan. Silakan coba lagi.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Kartu Statistik -->
    <div class="row">
        <div class="col-xl-4 col-md-6 ">
           <div class="card border-start border-4 border-success shadow-sm card-hover">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <i class="bi bi-cash-stack fs-1"></i>
                        <div class="text-end">
                            <div class="fs-3"><?php echo $count_cair; ?></div>
                            <div>Proposal Siap Cair</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div><br>

    <!-- === PENAMBAHAN BARU: Pesan Informasi untuk Bendahara === -->
    <?php if ($ada_yang_belum_bernomor): ?>
    <div class="alert alert-info" role="alert">
        <h4 class="alert-heading"><i class="bi bi-info-circle-fill"></i> Pemberitahuan</h4>
        <p>Beberapa proposal yang tercantum di bawah ini masih menunggu penerbitan Nomor Surat Resmi oleh BKKH. Tombol <strong>Proses Pencairan</strong> akan aktif secara otomatis setelah nomor surat diterbitkan.</p>
    </div>
    <?php endif; ?>


    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-table me-1"></i>
            Daftar Proposal Siap Dicairkan
        </div>
        <div class="card-body">
            <p>Berikut adalah daftar proposal final yang telah diajukan oleh BKKH dan siap untuk proses transfer dana.</p>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Nama Kegiatan</th>
                            <th>Ormawa</th>
                            <th>Tanggal Diajukan</th>
                            <th>Dana Disetujui</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($count_cair > 0):
                            $no = 1;
                            foreach ($proposals_to_cair as $row): ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo htmlspecialchars($row['nama_kegiatan']); ?></td>
                                    <td><?php echo htmlspecialchars($row['nama_ormawa']); ?></td>
                                    <td><?php echo date('d M Y', strtotime($row['tanggal_pengajuan'])); ?></td>
                                    <td><strong>Rp <?php echo number_format($row['dana_diajukan'], 0, ',', '.'); ?></strong></td>
                                    <td>
                                        <!-- === PERUBAHAN: Tombol Lihat Surat selalu ada === -->
                                        <a href="index.php?page=cetak_surat&id=<?php echo $row['id_pengajuan']; ?>" class="btn btn-sm btn-secondary" target="_blank" title="Lihat Surat (Mungkin belum resmi)">
                                            <i class="bi bi-file-earmark-text"></i> Lihat Surat
                                        </a>

                                        <?php if (!empty($row['nomor_surat'])): ?>
                                            <!-- Jika nomor surat ada, tombol Proses Pencairan menjadi aktif -->
                                            <a href="index.php?page=proses&id=<?php echo $row['id_pengajuan']; ?>" class="btn btn-sm btn-success">
                                                <i class="bi bi-cash-coin me-1"></i> Proses Pencairan
                                            </a>
                                        <?php else: ?>
                                            <!-- Jika nomor surat kosong, tombol proses dinonaktifkan -->
                                            <button class="btn btn-sm btn-success" disabled title="Nomor surat belum diterbitkan oleh BKKH">
                                                <i class="bi bi-cash-coin me-1"></i> Proses Pencairan
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach;
                        else: ?>
                            <tr>
                                <td colspan="6" class="text-center">Tidak ada proposal yang perlu diproses saat ini.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

