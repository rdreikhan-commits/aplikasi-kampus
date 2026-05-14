<?php
/**
 * File: roles/ormawa/peminjaman_tempat.php
 * Deskripsi: Halaman bagi Ormawa untuk meminjam fasilitas/ruangan.
 */

check_role(['ormawa', 'bem', 'bpm']);
$user_id = $_SESSION['user_id'];

// 1. Proses Form Peminjaman
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajukan_peminjaman'])) {
    $id_ruangan = (int)$_POST['id_ruangan'];
    $tgl_mulai = $_POST['tgl_mulai'];
    $tgl_selesai = $_POST['tgl_selesai'];
    $jam_mulai = $_POST['jam_mulai'];
    $jam_selesai = $_POST['jam_selesai'];
    $nama_kegiatan = sanitize_input($conn, $_POST['nama_kegiatan']);
    $deskripsi = sanitize_input($conn, $_POST['deskripsi']);

    // Cek Bentrok Waktu (Sangat Penting)
    // Syarat bentrok: Meminjam ruangan yang sama, status BUKAN Ditolak, dan ada overlap tanggal/waktu
    $sql_cek_bentrok = "SELECT * FROM peminjaman_tempat 
                        WHERE id_ruangan = ? AND status != 'Ditolak'
                        AND ((tgl_mulai <= ? AND tgl_selesai >= ?) 
                        AND (jam_mulai < ? AND jam_selesai > ?))";
    
    $stmt_cek = $conn->prepare($sql_cek_bentrok);
    $stmt_cek->bind_param("issss", $id_ruangan, $tgl_selesai, $tgl_mulai, $jam_selesai, $jam_mulai);
    $stmt_cek->execute();
    $result_cek = $stmt_cek->get_result();

    if ($result_cek->num_rows > 0) {
        $error_bentrok = "Maaf, Ruangan sudah dibooking pada tanggal/waktu tersebut oleh pihak lain.";
    } else {
        // Jika aman, masukkan ke database
        $sql_insert = "INSERT INTO peminjaman_tempat (id_user_ormawa, id_ruangan, tgl_mulai, tgl_selesai, jam_mulai, jam_selesai, nama_kegiatan, deskripsi_kegiatan) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("iissssss", $user_id, $id_ruangan, $tgl_mulai, $tgl_selesai, $jam_mulai, $jam_selesai, $nama_kegiatan, $deskripsi);
        
        if ($stmt_insert->execute()) {
            $sukses_pesan = "Pengajuan peminjaman ruangan berhasil dikirim, menunggu verifikasi BKKH.";
        } else {
            $error_bentrok = "Terjadi kesalahan saat menyimpan pengajuan: " . $conn->error;
        }
        $stmt_insert->close();
    }
    $stmt_cek->close();
}

// 2. Ambil Daftar Ruangan untuk Dropdown
$ruangan_list = [];
$res_ruangan = $conn->query("SELECT * FROM master_ruangan WHERE status_aktif = 'aktif'");
while ($r = $res_ruangan->fetch_assoc()) {
    $ruangan_list[] = $r;
}

// 3. Ambil Riwayat Peminjaman User Ini
$riwayat_peminjaman = [];
$stmt_riwayat = $conn->prepare("SELECT p.*, r.nama_ruangan FROM peminjaman_tempat p JOIN master_ruangan r ON p.id_ruangan = r.id_ruangan WHERE p.id_user_ormawa = ? ORDER BY p.tgl_pengajuan DESC");
$stmt_riwayat->bind_param("i", $user_id);
$stmt_riwayat->execute();
$res_riwayat = $stmt_riwayat->get_result();
while ($r = $res_riwayat->fetch_assoc()) {
    $riwayat_peminjaman[] = $r;
}
$stmt_riwayat->close();
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Peminjaman Tempat & Fasilitas</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item active">Ajukan Ruangan untuk Kegiatan Anda</li>
    </ol>

    <?php if(isset($error_bentrok)): ?>
        <div class="alert alert-danger"><i class="bi bi-x-circle me-1"></i> <?php echo $error_bentrok; ?></div>
    <?php endif; ?>
    <?php if(isset($sukses_pesan)): ?>
        <div class="alert alert-success"><i class="bi bi-check-circle me-1"></i> <?php echo $sukses_pesan; ?></div>
    <?php endif; ?>

    <div class="row">
        <!-- Form Peminjaman -->
        <div class="col-lg-5 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white"><i class="bi bi-calendar-plus me-1"></i> Form Pengajuan Baru</div>
                <div class="card-body">
                    <form action="index.php?page=peminjaman_tempat" method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Pilih Ruangan</label>
                            <select name="id_ruangan" class="form-select" required>
                                <option value="">-- Pilih Ruangan / Fasilitas --</option>
                                <?php foreach($ruangan_list as $ruang): ?>
                                    <option value="<?php echo $ruang['id_ruangan']; ?>">
                                        <?php echo htmlspecialchars($ruang['nama_ruangan']); ?> (Kapasitas: <?php echo $ruang['kapasitas']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nama Kegiatan</label>
                            <input type="text" name="nama_kegiatan" class="form-control" placeholder="Contoh: Seminar Nasional Teknologi" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Tanggal Mulai</label>
                                <input type="date" name="tgl_mulai" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Tanggal Selesai</label>
                                <input type="date" name="tgl_selesai" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Jam Mulai</label>
                                <input type="time" name="jam_mulai" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Jam Selesai</label>
                                <input type="time" name="jam_selesai" class="form-control" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Deskripsi Tambahan</label>
                            <textarea name="deskripsi" class="form-control" rows="3" placeholder="Sebutkan alat tambahan jika butuh..."></textarea>
                        </div>
                        <button type="submit" name="ajukan_peminjaman" class="btn btn-primary w-100">
                            <i class="bi bi-send me-1"></i> Ajukan Peminjaman
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Tabel Riwayat Saya -->
        <div class="col-lg-7 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white"><i class="bi bi-clock-history me-1"></i> Riwayat Pengajuan Tempat Saya</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered" style="font-size: 0.9rem;">
                            <thead class="table-light">
                                <tr>
                                    <th>Kegiatan</th>
                                    <th>Ruangan</th>
                                    <th>Waktu Pelaksanaan</th>
                                    <th>Status</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($riwayat_peminjaman) > 0): ?>
                                    <?php foreach($riwayat_peminjaman as $row): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($row['nama_kegiatan']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($row['nama_ruangan']); ?></td>
                                            <td>
                                                <?php echo date('d/m/Y', strtotime($row['tgl_mulai'])); ?> - <?php echo date('d/m/Y', strtotime($row['tgl_selesai'])); ?><br>
                                                <small class="text-muted"><?php echo date('H:i', strtotime($row['jam_mulai'])); ?> s/d <?php echo date('H:i', strtotime($row['jam_selesai'])); ?></small>
                                            </td>
                                            <td>
                                                <?php 
                                                    if($row['status'] == 'Disetujui') echo '<span class="badge bg-success">Disetujui</span>';
                                                    else if($row['status'] == 'Ditolak') echo '<span class="badge bg-danger">Ditolak</span><br><small>'.$row['catatan_penolakan'].'</small>';
                                                    else echo '<span class="badge bg-warning text-dark">Menunggu BKKH</span>';
                                                ?>
                                            </td>
                                            <td class="text-center">
                                                <a href="index.php?page=view_peminjaman&id=<?php echo $row['id_peminjaman']; ?>" class="btn btn-sm btn-outline-primary" title="Cetak Surat Izin">
                                                    <i class="bi bi-printer"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-center">Belum ada riwayat peminjaman tempat.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
