<?php
/**
 * File: profil.php
 * Deskripsi: Halaman untuk pengguna mengedit nama dan foto profil mereka.
 */

// Ambil data user yang sedang login dari database
$id_user = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT nama_lengkap, username, foto_profil FROM users WHERE id_user = ?");
if ($stmt === false) {
    die("Gagal mempersiapkan statement: " . $conn->error);
}
$stmt->bind_param("i", $id_user);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    die("User tidak ditemukan.");
}

$foto_path = !empty($user['foto_profil']) ? 'uploads/profil/' . $user['foto_profil'] : 'assets/images/default-avatar.png'; // Ganti dengan path avatar default Anda

?>

<div class="container-fluid">
    <div class="row">
        <div class="col-lg-6 col-md-8 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Atur Profil Pengguna</h4>
                </div>
                <div class="card-body">
                    <form action="index.php?page=profil" method="POST" enctype="multipart/form-data">
                        <div class="text-center mb-4">
                            <img src="<?php echo htmlspecialchars($foto_path); ?>" alt="Foto Profil" class="rounded-circle" width="120" height="120" style="object-fit: cover; border: 4px solid #eee;">
                        </div>

                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" id="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled readonly>
                            <small class="form-text text-muted">Username tidak dapat diubah.</small>
                        </div>

                        <div class="mb-3">
                            <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                            <input type="text" name="nama_lengkap" id="nama_lengkap" class="form-control" value="<?php echo htmlspecialchars($user['nama_lengkap']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="foto_profil" class="form-label">Ganti Foto Profil (Opsional)</label>
                            <input type="file" name="foto_profil" id="foto_profil" class="form-control" accept="image/png, image/jpeg, image/jpg">
                            <small class="form-text text-muted">Format yang didukung: JPG, PNG. Maksimal 2MB.</small>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
