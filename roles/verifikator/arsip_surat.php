<?php
/**
 * File: arsip_surat.php
 * Deskripsi: Halaman untuk BKKH melihat, menginput nomor, dan mencetak ulang surat balasan.
 * Versi: Final dengan fitur input nomor surat melalui modal.
 */
check_role(['bkh']);

// --- LOGIKA HAPUS DATA (TIDAK ADA PERUBAHAN) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_semua_arsip'])) {
    
    $conn->begin_transaction();
    try {
        $final_statuses = [
            'Disetujui WR3, Siap Diajukan ke Bendahara', 'Diajukan ke Bendahara',
            'Dana Cair', 'LPJ Verifikasi BKKH', 'LPJ Ditolak BKKH', 'Selesai'
        ];
        $placeholders = implode(',', array_fill(0, count($final_statuses), '?'));
        $types = str_repeat('s', count($final_statuses));

        $stmt_get_data = $conn->prepare(
            "SELECT id_pengajuan, file_proposal, file_lpj FROM pengajuan WHERE status IN ($placeholders)"
        );
        if (!$stmt_get_data) throw new Exception("Gagal mempersiapkan query untuk mengambil data arsip.");
        
        $stmt_get_data->bind_param($types, ...$final_statuses);
        $stmt_get_data->execute();
        $result_data = $stmt_get_data->get_result();
        
        $ids_to_delete = [];
        $files_to_delete = [];
        while ($row = $result_data->fetch_assoc()) {
            $ids_to_delete[] = $row['id_pengajuan'];
            if (!empty($row['file_proposal'])) {
                $files_to_delete[] = 'uploads/proposal/' . $row['file_proposal'];
            }
            if (!empty($row['file_lpj'])) {
                $files_to_delete[] = 'uploads/lpj/' . $row['file_lpj'];
            }
        }
        $stmt_get_data->close();

        if (!empty($ids_to_delete)) {
            $placeholders_ids = implode(',', array_fill(0, count($ids_to_delete), '?'));
            $types_ids = str_repeat('i', count($ids_to_delete));

            $stmt_delete_pengajuan = $conn->prepare("DELETE FROM pengajuan WHERE id_pengajuan IN ($placeholders_ids)");
            if (!$stmt_delete_pengajuan) throw new Exception("Gagal mempersiapkan query hapus pengajuan.");

            $stmt_delete_pengajuan->bind_param($types_ids, ...$ids_to_delete);
            if (!$stmt_delete_pengajuan->execute()) {
                throw new Exception("Gagal mengeksekusi penghapusan pengajuan: " . $stmt_delete_pengajuan->error);
            }
            $stmt_delete_pengajuan->close();
        }

        $conn->commit();

        foreach ($files_to_delete as $file_path) {
            if (file_exists($file_path)) {
                @unlink($file_path);
            }
        }

        redirect('index.php?page=arsip_surat&status=hapus_arsip_sukses');

    } catch (Exception $e) {
        $conn->rollback();
        redirect('index.php?page=arsip_surat&error=hapus_arsip_gagal');
    }
}


// === PERUBAHAN DI SINI: Query mengambil kolom 'nomor_surat' ===
$final_statuses = [
    'Disetujui WR3, Siap Diajukan ke Bendahara', 'Diajukan ke Bendahara',
    'Dana Cair', 'LPJ Verifikasi BKKH', 'LPJ Ditolak BKKH', 'Selesai'
];
$placeholders = implode(',', array_fill(0, count($final_statuses), '?'));

$stmt = $conn->prepare(
    "SELECT p.id_pengajuan, p.nama_kegiatan, u.nama_lengkap AS nama_ormawa, p.tanggal_pengajuan, p.status, p.nomor_surat
     FROM pengajuan p
     JOIN users u ON p.id_user_ormawa = u.id_user
     WHERE p.status IN ($placeholders)
     ORDER BY p.tanggal_pengajuan DESC");

$types = str_repeat('s', count($final_statuses));
$stmt->bind_param($types, ...$final_statuses);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Arsip Surat Balasan</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item active">Arsip & Penerbitan Nomor Surat</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>
                <i class="bi bi-archive-fill me-1"></i>
                Seluruh Surat Balasan yang Telah Diterbitkan
            </span>
            
            <?php if ($result->num_rows > 0): ?>
            <form id="formHapusArsip" method="POST" action="index.php?page=arsip_surat">
                <input type="hidden" name="hapus_semua_arsip" value="1">
                <button type="submit" class="btn btn-danger btn-sm">
                    <i class="bi bi-trash-fill me-1"></i> Hapus Semua Arsip
                </button>
            </form>
            <?php endif; ?>
            
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="datatablesSimple">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Kegiatan</th>
                            <th>Ormawa</th>
                            <!-- === PENAMBAHAN BARU: Kolom Nomor Surat === -->
                            <th>Nomor Surat</th>
                            <th>Status Terakhir</th>
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
                                    <td><?php echo htmlspecialchars($row['nama_ormawa']); ?></td>
                                    <!-- === PENAMBAHAN BARU: Menampilkan Nomor Surat === -->
                                    <td>
                                        <?php if (!empty($row['nomor_surat'])): ?>
                                            <strong><?php echo htmlspecialchars($row['nomor_surat']); ?></strong>
                                        <?php else: ?>
                                            <span class="text-muted fst-italic">Belum diterbitkan</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge bg-success"><?php echo htmlspecialchars($row['status']); ?></span></td>
                                    <td>
                                        <!-- === PERUBAHAN: Tombol Aksi Dinamis === -->
                                        <?php if (!empty($row['nomor_surat'])): ?>
                                            <a href="index.php?page=cetak_surat&id=<?php echo $row['id_pengajuan']; ?>" class="btn btn-info btn-sm text-white" target="_blank">
                                                <i class="bi bi-printer-fill me-1"></i> Cetak Ulang
                                            </a>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#inputNomorModal-<?php echo $row['id_pengajuan']; ?>">
                                                <i class="bi bi-input-cursor-text me-1"></i> Input Nomor Surat
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>

                                <!-- === PENAMBAHAN BARU: Modal untuk Input Nomor Surat === -->
                                <div class="modal fade" id="inputNomorModal-<?php echo $row['id_pengajuan']; ?>" tabindex="-1" aria-labelledby="modalLabel-<?php echo $row['id_pengajuan']; ?>" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <form action="index.php?page=input_nomor_surat" method="POST">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="modalLabel-<?php echo $row['id_pengajuan']; ?>">Input Nomor Surat Resmi</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Kegiatan: <strong><?php echo htmlspecialchars($row['nama_kegiatan']); ?></strong></p>
                                                    <input type="hidden" name="id_pengajuan" value="<?php echo $row['id_pengajuan']; ?>">
                                                    <div class="mb-3">
                                                        <label for="nomor_surat-<?php echo $row['id_pengajuan']; ?>" class="form-label">Nomor Surat</label>
                                                        <input type="text" class="form-control" id="nomor_surat-<?php echo $row['id_pengajuan']; ?>" name="nomor_surat" placeholder="Contoh: 123/SKPP/BKKH/IX/2025" required>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-primary">Simpan Nomor Surat</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
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

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    $('#datatablesSimple').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json",
            "emptyTable": "Belum ada data arsip yang tersedia"
        }
    });

    const formHapus = document.getElementById('formHapusArsip');
    if (formHapus) {
        formHapus.addEventListener('submit', function(event) {
            event.preventDefault(); 
            Swal.fire({
                title: 'Apakah Anda Yakin?',
                text: "Semua data arsip, riwayat, dan file terkait akan dihapus secara permanen. Tindakan ini tidak dapat dibatalkan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus Semua!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    formHapus.submit();
                }
            });
        });
    }
});
</script>

