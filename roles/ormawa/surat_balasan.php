<?php
/**
 * File: surat_balasan.php
 * Deskripsi: Halaman untuk menampilkan daftar surat balasan yang siap cetak.
 */
check_role(['ormawa', 'bem', 'bpm']);
$user_id = $_SESSION['user_id'];

// Daftar status di mana surat balasan sudah bisa dicetak
$final_statuses = [
    'Disetujui WR3, Siap Diajukan ke Bendahara',
    'Diajukan ke Bendahara',
    'Dana Cair',
    'LPJ Verifikasi BKKH',
    'LPJ Ditolak BKKH',
    'Selesai'
];
// Membuat placeholder untuk query IN(...)
$placeholders = implode(',', array_fill(0, count($final_statuses), '?'));

// Mengambil data pengajuan yang sudah final
$stmt = $conn->prepare(
    "SELECT id_pengajuan, nama_kegiatan, tanggal_pengajuan, status 
     FROM pengajuan 
     WHERE id_user_ormawa = ? AND status IN ($placeholders)
     ORDER BY tanggal_pengajuan DESC");

// Bind parameter: satu integer (user_id) dan beberapa string (status)
$types = 'i' . str_repeat('s', count($final_statuses));
$params = array_merge([$user_id], $final_statuses);
$stmt->bind_param($types, ...$params);

$stmt->execute();
$result = $stmt->get_result();
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Surat Balasan</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item active">Daftar Surat Persetujuan Proposal</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-envelope-paper-fill me-1"></i>
            Surat Siap Cetak
        </div>
        <div class="card-body">
            <p>Berikut adalah daftar proposal Anda yang telah disetujui dan surat balasannya siap untuk dicetak sebagai arsip.</p>
            <div class="table-responsive">
                <table class="table table-bordered" id="datatablesSimple">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Kegiatan</th>
                            <th>Tanggal Pengajuan</th>
                            <th>Status Terakhir</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php $i = 1; while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td><?php echo htmlspecialchars($row['nama_kegiatan']); ?></td>
                                <td><?php echo date('d M Y', strtotime($row['tanggal_pengajuan'])); ?></td>
                                <td><span class="badge bg-success"><?php echo htmlspecialchars($row['status']); ?></span></td>
                                <td>
                                    <a href="index.php?page=cetak_surat&id=<?php echo $row['id_pengajuan']; ?>" class="btn btn-info btn-sm text-white" target="_blank">
                                        <i class="bi bi-printer-fill me-1"></i> Cetak Surat
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">Belum ada surat balasan yang tersedia.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
