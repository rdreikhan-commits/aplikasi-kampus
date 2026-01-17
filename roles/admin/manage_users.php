<?php
/**
 * File: roles/admin/dashboard.php
 * Deskripsi: Halaman untuk BKKH mengelola data pengguna (CRUD & Status Akun).
 * PERBAIKAN: 
 * 1. Mengurutkan pengguna berdasarkan hierarki peran.
 * 2. Menyembunyikan tombol aktif/nonaktif untuk peran selain Ormawa.
 */

// Memeriksa peran pengguna, sekarang BKKH yang bisa mengakses
check_role(['bkh']);

// Mengambil ID pengguna yang sedang login agar tidak ditampilkan di daftar
$id_user_saat_ini = $_SESSION['user_id'];

// PERBAIKAN: Query diubah untuk mengurutkan berdasarkan peran yang ditentukan
$query = "SELECT * FROM users WHERE id_user != ? ORDER BY FIELD(role, 'bkh', 'wr3', 'bendahara', 'bem', 'bpm', 'ormawa'), nama_lengkap ASC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_user_saat_ini);
$stmt->execute();
$result = $stmt->get_result();
?>
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mt-4">Manajemen Pengguna</h3>
        <a href="index.php?page=tambah_user" class="btn btn-primary"><i class="bi bi-person-plus-fill me-2"></i>Tambah Pengguna Baru</a>
    </div>

    <?php
    // --- TAMBAHAN BARU: Notifikasi untuk hasil aktivasi/nonaktifkan ---
    if (isset($_GET['status'])) {
        if ($_GET['status'] == 'toggle_sukses') {
            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                      <strong>Berhasil!</strong> Status akun pengguna telah diubah.
                      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>';
        } elseif ($_GET['status'] == 'toggle_gagal') {
            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                      <strong>Gagal!</strong> Terjadi kesalahan saat mengubah status akun.
                      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>';
        }
    }
    ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Nama Lengkap</th>
                            <th>Username</th>
                            <th>Peran (Role)</th>
                            <th>Status Akun</th> 
                            <th style="width: 220px;">Aksi</th> 
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        if ($result->num_rows > 0):
                            while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo strtoupper($row['role']); ?></span></td>
                                    
                                    <td>
                                        <?php if ($row['status_akun'] == 'aktif'): ?>
                                            <span class="badge bg-success">Aktif</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Nonaktif</span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td>
                                        <!-- PERBAIKAN: Tombol toggle status hanya muncul untuk peran 'ormawa' -->
                                        <?php if ($row['role'] === 'ormawa'): ?>
                                            <?php
                                            if ($row['status_akun'] == 'aktif') {
                                                $link_toggle = "index.php?page=toggle_status&id={$row['id_user']}&new_status=nonaktif";
                                                $btn_class = "btn-dark";
                                                $btn_icon = "bi-slash-circle";
                                                $btn_title = "Nonaktifkan";
                                            } else {
                                                $link_toggle = "index.php?page=toggle_status&id={$row['id_user']}&new_status=aktif";
                                                $btn_class = "btn-success";
                                                $btn_icon = "bi-check-circle";
                                                $btn_title = "Aktifkan";
                                            }
                                            ?>
                                            <a href="<?php echo $link_toggle; ?>" class="btn btn-sm <?php echo $btn_class; ?>" title="<?php echo $btn_title; ?>"><i class="bi <?php echo $btn_icon; ?>"></i></a>
                                        <?php endif; ?>

                                        <a href="index.php?page=edit_user&id=<?php echo $row['id_user']; ?>" class="btn btn-sm btn-warning" title="Edit"><i class="bi bi-pencil-fill"></i></a>
                                        <a href="index.php?page=hapus_user&id=<?php echo $row['id_user']; ?>" class="btn btn-sm btn-danger" title="Hapus" onclick="return confirm('Apakah Anda yakin ingin menghapus pengguna ini? Menghapus pengguna juga akan menghapus semua data pengajuan terkait.');"><i class="bi bi-trash-fill"></i></a>
                                    </td>
                                </tr>
                            <?php endwhile;
                        else: ?>
                            <tr>
                                <td colspan="6" class="text-center">Belum ada data pengguna lain.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

