<?php
/**
 * File: roles/admin/edit_user.php
 * Deskripsi: HANYA MENAMPILKAN form untuk BKKH mengedit data pengguna.
 */
check_role(['bkh']);
$id_user_edit = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Mengambil Data Pengguna yang Akan Diedit
$stmt = $conn->prepare("SELECT id_user, nama_lengkap, username, role FROM users WHERE id_user = ?");
$stmt->bind_param("i", $id_user_edit);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Jika pengguna tidak ditemukan, keluar dengan pesan
if (!$user) {
    echo "<div class='alert alert-danger'>Pengguna tidak ditemukan.</div>";
    exit;
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Edit Pengguna</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="index.php?page=manage_users">Manajemen Pengguna</a></li>
        <li class="breadcrumb-item active">Edit Pengguna</li>
    </ol>
    
    <div class="card shadow-sm mt-4">
        <div class="card-header">
            <h5>Mengedit Data: <?php echo htmlspecialchars($user['nama_lengkap']); ?></h5>
        </div>
        <div class="card-body">
            <?php
            // Menampilkan notifikasi error dari URL
            if (isset($_GET['error'])) {
                $error_msg = '';
                if ($_GET['error'] === 'username_duplikat') {
                    $error_msg = 'Username sudah digunakan. Silakan pilih username lain.';
                } elseif ($_GET['error'] === 'update_gagal') {
                    $error_msg = 'Terjadi kesalahan saat memperbarui data.';
                }
                if ($error_msg) {
                    echo '<div class="alert alert-danger">' . $error_msg . '</div>';
                }
            }
            ?>
            <form action="index.php?page=edit_user&id=<?php echo $id_user_edit; ?>" method="POST">
                <div class="mb-3">
                    <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                    <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" value="<?php echo htmlspecialchars($user['nama_lengkap']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password Baru</label>
                    <input type="password" class="form-control" id="password" name="password">
                    <div class="form-text">Kosongkan jika tidak ingin mengubah password.</div>
                </div>
                <div class="mb-3">
                    <label for="role" class="form-label">Peran (Role)</label>
                    <select class="form-select" id="role" name="role" required>
                        <option value="ormawa" <?php if($user['role'] == 'ormawa') echo 'selected'; ?>>Ormawa</option>
                        <option value="bem" <?php if($user['role'] == 'bem') echo 'selected'; ?>>BEM</option>
                        <option value="bpm" <?php if($user['role'] == 'bpm') echo 'selected'; ?>>BPM</option>
                        <option value="bkh" <?php if($user['role'] == 'bkh') echo 'selected'; ?>>BKKH</option>
                        <option value="wr3" <?php if($user['role'] == 'wr3') echo 'selected'; ?>>WR3</option>
                        <option value="bendahara" <?php if($user['role'] == 'bendahara') echo 'selected'; ?>>Bendahara</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Update Data</button>
                <a href="index.php?page=manage_users" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>
