<?php
/**
 * File: roles/admin/tambah_user.php
 * Deskripsi: HANYA MENAMPILKAN form untuk menambah pengguna baru.
 */
check_role(['bkh']);
?>

<div class="container-fluid px-4">
    <h3 class="mt-4">Tambah Pengguna Baru</h3>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="index.php?page=manage_users">Manajemen Pengguna</a></li>
        <li class="breadcrumb-item active">Tambah Pengguna</li>
    </ol>

    <div class="card shadow-sm mt-4">
        <div class="card-body">
            <?php
            // Menampilkan notifikasi error dari URL
            if (isset($_GET['error'])) {
                $error_msg = '';
                if ($_GET['error'] === 'username_duplikat') {
                    $error_msg = 'Username sudah digunakan. Silakan pilih username lain.';
                } elseif ($_GET['error'] === 'gagal_simpan') {
                    $error_msg = 'Terjadi kesalahan saat menyimpan data.';
                }
                if (!empty($error_msg)) {
                    echo '<div class="alert alert-danger">' . $error_msg . '</div>';
                }
            }
            ?>
            <form action="index.php?page=tambah_user" method="POST">
                <div class="mb-3">
                    <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                    <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" required>
                </div>
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="mb-3">
                    <label for="role" class="form-label">Peran (Role)</label>
                    <select class="form-select" id="role" name="role" required>
                        <option value="">-- Pilih Peran --</option>
                        <option value="ormawa">Ormawa</option>
                        <option value="bem">BEM</option>
                        <option value="bpm">BPM</option>
                        <option value="bkh">BKKH</option>
                        <option value="wr3">WR3</option>
                        <option value="bendahara">Bendahara</option>
                        <option value="sarpras">Sarpras Ruangan</option>
                        <option value="sarpras_barang">Sarpras Barang</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="index.php?page=manage_users" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>