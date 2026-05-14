<?php
/**
 * File: arsip_lpj.php
 * Deskripsi: Menampilkan arsip LPJ yang sudah disetujui (status = 'Selesai')
 */

check_role(['ormawa', 'bem', 'bpm']); 
$user_id = $_SESSION['user_id'];

// Mengambil data pengajuan milik pengguna yang sudah selesai (LPJ disetujui)
$stmt = $conn->prepare(
    "SELECT p.id_pengajuan, p.nama_kegiatan, p.tanggal_pengajuan, p.status, p.file_lpj
    FROM pengajuan p
    WHERE p.id_user_ormawa = ? AND p.status = 'Selesai'
    ORDER BY p.tanggal_update DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<div class="container-fluid px-4">
    <h1 class="mt-4">Arsip LPJ</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item active">Arsip Laporan Pertanggungjawaban</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-archive-fill me-1"></i>
            Daftar LPJ Disetujui
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="arsipLpjTable" class="table table-bordered table-hover dt-responsive nowrap" style="width:100%">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Kegiatan</th>
                            <th>Status</th>
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
                                <td>
                                    <span class='badge bg-success'><?php echo htmlspecialchars($row['status']); ?></span>
                                </td>
                                <td>
                                    <?php if (!empty($row['file_lpj'])): ?>
                                        <a href="uploads/lpj/<?php echo htmlspecialchars($row['file_lpj']); ?>" target="_blank" class="btn btn-success btn-sm" title="Download LPJ">
                                            <i class="bi bi-download"></i> Download LPJ
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">File tidak tersedia</span>
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

<!-- Aset CSS & JS untuk DataTables Responsive -->
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

<script>
    $(document).ready(function() {
        $('#arsipLpjTable').DataTable({
            responsive: true,
            "language": { 
                "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json",
                "emptyTable": "Belum ada arsip LPJ yang disetujui"
            }
        });
    });
</script>
