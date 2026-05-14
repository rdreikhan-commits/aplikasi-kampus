<?php
/**
 * File: profil.php
 * Deskripsi: Halaman untuk pengguna mengedit nama dan foto profil mereka.
 */

// Ambil data user yang sedang login dari database
$id_user = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT nama_lengkap, username, foto_profil, logo_ormawa, nama_ketua, nama_sekretaris, nama_bendahara, ttd_ketua, ttd_sekretaris, ttd_bendahara FROM users WHERE id_user = ?");
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

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="foto_profil" class="form-label">Ganti Foto Profil (Opsional)</label>
                                <input type="file" name="foto_profil" id="foto_profil" class="form-control" accept="image/png, image/jpeg, image/jpg">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="logo_ormawa" class="form-label">Logo Ormawa (Untuk Kop Surat)</label>
                                <input type="file" name="logo_ormawa" id="logo_ormawa" class="form-control" accept="image/png, image/jpeg, image/jpg">
                            </div>
                        </div>
                        <small class="form-text text-muted">Format: JPG, PNG. Maksimal 2MB.</small>

                        <hr>
                        <h5 class="mb-3 text-secondary">Struktur Organigram & TTD Digital</h5>
                        
                        <!-- Ketua -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama Ketua</label>
                                <input type="text" name="nama_ketua" class="form-control" value="<?php echo htmlspecialchars($user['nama_ketua'] ?? ''); ?>" placeholder="Nama Lengkap Ketua">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">TTD Ketua (PNG Transparan)</label>
                                <input type="file" name="ttd_ketua" class="form-control" accept="image/png">
                            </div>
                        </div>

                        <!-- Sekretaris -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama Sekretaris</label>
                                <input type="text" name="nama_sekretaris" class="form-control" value="<?php echo htmlspecialchars($user['nama_sekretaris'] ?? ''); ?>" placeholder="Nama Lengkap Sekretaris">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">TTD Sekretaris (PNG Transparan)</label>
                                <input type="file" name="ttd_sekretaris" class="form-control" accept="image/png">
                            </div>
                        </div>

                        <!-- Bendahara -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama Bendahara</label>
                                <input type="text" name="nama_bendahara" class="form-control" value="<?php echo htmlspecialchars($user['nama_bendahara'] ?? ''); ?>" placeholder="Nama Lengkap Bendahara">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">TTD Bendahara (PNG Transparan)</label>
                                <input type="file" name="ttd_bendahara" class="form-control" accept="image/png">
                            </div>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">Simpan Perubahan Profil</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
