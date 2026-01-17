<?php
/**
 * File: riwayat.php
 * Deskripsi: Menampilkan tabel riwayat pengajuan proposal dengan tombol aksi dinamis.
 * Versi: Final dengan tambahan DataTables Responsive untuk tampilan di semua perangkat.
 */

// Asumsi fungsi ini ada dan berfungsi
check_role(['ormawa', 'bem', 'bpm']); 
$user_id = $_SESSION['user_id'];

// Mengambil data pengajuan milik pengguna yang sedang login
// Menggunakan p.id_user_ormawa untuk memfilter sesuai user
$stmt = $conn->prepare(
    "SELECT p.id_pengajuan, p.nama_kegiatan, p.tanggal_pengajuan, p.status, p.dana_diajukan
    FROM pengajuan p
    WHERE p.id_user_ormawa = ? 
    ORDER BY p.tanggal_pengajuan DESC, p.id_pengajuan DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!-- Menambahkan Viewport Meta Tag untuk responsivitas -->
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<div class="container-fluid px-4">
    <h1 class="mt-4">Riwayat Pengajuan Proposal</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item active">Riwayat</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-clock-history me-1"></i>
            Riwayat Pengajuan Proposal Saya
        </div>
        <div class="card-body">
            <!-- Wrapper table-responsive tetap ada untuk fallback -->
            <div class="table-responsive">
                <!-- MODIFIKASI: Tambahkan kelas dt-responsive dan nowrap agar DataTables bisa mengontrol responsivitas -->
                <table id="riwayatTable" class="table table-bordered table-hover dt-responsive nowrap" style="width:100%">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Kegiatan</th>
                            <th>Tanggal Kegiatan</th>
                            <th>Status</th>
                            <th>Dana Diajukan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($result->num_rows > 0): 
                            $i = 1; 
                            while($row = $result->fetch_assoc()): 
                        ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td><?php echo htmlspecialchars($row['nama_kegiatan']); ?></td>
                                <td><?php echo date('d M Y', strtotime($row['tanggal_pengajuan'])); ?></td>
                                <td>
                                    <?php 
                                    $status = htmlspecialchars($row['status']);
                                    $status_lower = strtolower(trim($status));
                                    
                                    $badge_class = 'bg-secondary';
                                    $status_text = 'Menunggu status';
                                    
                                    if (!empty($status_lower)) {
                                        $status_text = ucwords($status);

                                        // Logika untuk warna badge berdasarkan alur status
                                        if (strpos($status_lower, 'ditolak') !== false) {
                                            $badge_class = 'bg-danger';
                                        } elseif (strpos($status_lower, 'diajukan') !== false || strpos($status_lower, 'verifikasi') !== false) {
                                            $badge_class = 'bg-warning text-dark';
                                        } elseif (strpos($status_lower, 'disetujui') !== false || strpos($status_lower, 'cair') !== false || strpos($status_lower, 'selesai') !== false || strpos($status_lower, 'diverifikasi') !== false) {
                                            $badge_class = 'bg-success';
                                        } elseif (strpos($status_lower, 'draft') !== false) {
                                            $badge_class = 'bg-secondary';
                                        } else {
                                            $badge_class = 'bg-info'; // Warna default untuk status lainnya
                                        }
                                    }
                                    echo "<span class='badge {$badge_class}'>{$status_text}</span>";
                                    ?>
                                </td>
                                <td>Rp <?php echo number_format($row['dana_diajukan'], 0, ',', '.'); ?></td>
                                <td>
                                    <a href="index.php?page=detail&id=<?php echo $row['id_pengajuan']; ?>" class="btn btn-primary btn-sm" title="Lihat Detail">
                                        <i class="bi bi-eye-fill"></i>
                                    </a>
                                    
                                    <?php
                                    $current_status_lower = strtolower(trim($row['status']));

                                    // Logika Tombol Revisi Proposal
                                    $status_revisi_proposal = ['ditolak bem', 'ditolak bpm', 'ditolak bkkh', 'ditolak wr3'];
                                    if (in_array($current_status_lower, $status_revisi_proposal)): 
                                    ?>
                                        <a href="index.php?page=edit&id=<?php echo $row['id_pengajuan']; ?>" class="btn btn-warning btn-sm" title="Revisi Proposal">
                                            <i class="bi bi-pencil-fill"></i>
                                        </a>
                                    <?php 
                                    // Logika Tombol Revisi LPJ
                                    elseif ($current_status_lower == 'lpj ditolak bkkh'): 
                                    ?>
                                        <a href="index.php?page=revisi_lpj&id=<?php echo $row['id_pengajuan']; ?>" class="btn btn-warning btn-sm" title="Revisi LPJ">
                                            <i class="bi bi-file-earmark-arrow-up-fill"></i>
                                        </a>
                                    <?php endif; ?>

                                    <?php
                                    // Logika Tombol Upload LPJ Awal
                                    if ($current_status_lower == 'dana cair'):
                                    ?>
                                    <a href="index.php?page=upload_lpj&id=<?php echo $row['id_pengajuan']; ?>" class="btn btn-info btn-sm text-white" title="Upload LPJ">
                                        <i class="bi bi-cloud-arrow-up-fill"></i>
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php 
                            endwhile; 
                        endif; 
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Library Scripts -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- DataTables & Bootstrap 5 -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">

<!-- PENAMBAHAN: Aset CSS & JS untuk DataTables Responsive -->
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>


<script>
    $(document).ready(function() {
        $('#riwayatTable').DataTable({
            // PENAMBAHAN: Opsi untuk mengaktifkan mode responsif
            responsive: true,
            
            "language": { 
                "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json",
                "emptyTable": "Anda belum memiliki riwayat pengajuan"
            }
        });
    });
</script>

