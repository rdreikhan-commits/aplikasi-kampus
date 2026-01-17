<?php
/**
 * File: roles/verifikator/verifikasi.php
 * Deskripsi: Halaman untuk verifikator (BEM, BPM, BKKH, WR3) memverifikasi proposal pengajuan.
 */

// Memeriksa peran pengguna yang diizinkan mengakses halaman ini
check_role(['bem', 'bpm', 'bkh', 'wr3']);

// Mengambil ID pengajuan dari URL dan memastikan valid
$id_pengajuan = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_pengajuan <= 0) {
    // Jika ID tidak valid, redirect ke dashboard
    redirect('index.php?page=dashboard&error=invalid_id');
}

// Mengambil data pengguna yang sedang login
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// 1. Logika untuk memproses form verifikasi (saat tombol Setuju/Tolak diklik)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'])) {
    $aksi = sanitize_input($conn, $_POST['aksi']); // 'setuju' atau 'tolak'
    $catatan = sanitize_input($conn, $_POST['catatan'] ?? ''); // Catatan dari verifikator

    // Validasi dasar
    if (empty($aksi) || !in_array($aksi, ['setuju', 'tolak'])) {
        redirect("index.php?page=verifikasi&id=$id_pengajuan&error=aksi_invalid");
    }
    if ($aksi === 'tolak' && empty($catatan)) {
        redirect("index.php?page=verifikasi&id=$id_pengajuan&error=catatan_kosong");
    }

    // Tentukan status baru dan pesan histori berdasarkan peran dan aksi
    $new_status = '';
    $history_message = '';

    // Logika Alur Persetujuan/Penolakan
    if ($aksi === 'setuju') {
        switch ($user_role) {
            case 'bem':
                $new_status = 'Diajukan Ke BPM';
                $history_message = 'Disetujui oleh BEM.' . ($catatan ? ' Catatan: ' . $catatan : ' Catatan: -');
                break;
            case 'bpm':
                $new_status = 'Verifikasi BKKH'; // Status internal database
                $history_message = 'Disetujui oleh BPM.' . ($catatan ? ' Catatan: ' . $catatan : ' Catatan: -');
                break;
            case 'bkh':
                $new_status = 'Verifikasi WR3'; // Status internal database
                // --- PERBAIKAN TEKS CATATAN SESUAI PERMINTAAN ---
                $history_message = 'Disetujui oleh BKKH .' . ($catatan ? ' Catatan: ' . $catatan : ' Catatan: -');
                // --- AKHIR PERBAIKAN ---
                break;
            case 'wr3':
                $new_status = 'Disetujui WR3, Siap Diajukan ke Bendahara'; // Status internal database
                $history_message = 'Proposal disetujui oleh WR3 & Dikembalikan ke BKKH' . ($catatan ? ' Catatan: ' . $catatan : ' Catatan: -');
                break;
        }
    } else { // Jika aksi === 'tolak'
        switch ($user_role) {
            case 'bem':
                $new_status = 'Ditolak BEM';
                $history_message = 'Ditolak oleh BEM. Catatan: ' . $catatan;
                break;
            case 'bpm':
                $new_status = 'Ditolak BPM';
                $history_message = 'Ditolak oleh BPM. Catatan: ' . $catatan;
                break;
            case 'bkh':
                $new_status = 'Ditolak BKKH';
                $history_message = 'Ditolak oleh BKKH. Catatan: ' . $catatan;
                break;
            case 'wr3':
                $new_status = 'Ditolak WR3';
                $history_message = 'Ditolak oleh WR3. Catatan: ' . $catatan;
                break;
        }
    }

    // Pastikan status baru sudah ditentukan
    if (empty($new_status)) {
         redirect("index.php?page=verifikasi&id=$id_pengajuan&error=status_error");
    }

    // Update status pengajuan di database
    // Kolom 'catatan_revisi' diisi jika ditolak, dikosongkan jika disetujui
    $catatan_update = ($aksi === 'tolak') ? $catatan : NULL;
    $stmt_update = $conn->prepare("UPDATE pengajuan SET status = ?, catatan_revisi = ? WHERE id_pengajuan = ?");
    if ($stmt_update === false) {
         redirect("index.php?page=verifikasi&id=$id_pengajuan&error=db_prepare_gagal");
    }
    $stmt_update->bind_param("ssi", $new_status, $catatan_update, $id_pengajuan);

    // Eksekusi update dan tambahkan histori jika berhasil
    if ($stmt_update->execute()) {
        // Panggil fungsi add_history untuk mencatat ke tabel histori_status
        add_history($conn, $id_pengajuan, $user_id, $new_status, $history_message);
        redirect("index.php?page=dashboard&status=verifikasi_sukses"); // Redirect ke dashboard setelah berhasil
    } else {
        redirect("index.php?page=verifikasi&id=$id_pengajuan&error=update_gagal"); // Redirect jika gagal update
    }
    $stmt_update->close();
    exit(); // Hentikan eksekusi setelah redirect
}


// 2. Mengambil data pengajuan untuk ditampilkan di halaman (GET Request)
$stmt_detail = $conn->prepare(
    "SELECT p.*, u.nama_lengkap AS nama_ormawa
     FROM pengajuan p
     JOIN users u ON p.id_user_ormawa = u.id_user
     WHERE p.id_pengajuan = ?"
);
if ($stmt_detail === false) {
    die("Gagal menyiapkan statement: " . $conn->error); // Tampilkan error jika prepare gagal
}
$stmt_detail->bind_param("i", $id_pengajuan);
$stmt_detail->execute();
$result_detail = $stmt_detail->get_result();
$pengajuan = $result_detail->fetch_assoc();
$stmt_detail->close();

// Jika pengajuan tidak ditemukan
if (!$pengajuan) {
    echo "<div class='container-fluid px-4'><div class='alert alert-danger mt-4'>Pengajuan tidak ditemukan.</div></div>";
    return; // Hentikan eksekusi skrip
}

// Cek Otorisasi: Pastikan peran pengguna cocok dengan status pengajuan saat ini
$status_saat_ini = trim(strtolower($pengajuan['status'])); // Ambil status saat ini dan jadikan lowercase
$status_yang_diharapkan = '';

switch ($user_role) {
    case 'bem': $status_yang_diharapkan = 'diajukan ke bem'; break;
    case 'bpm': $status_yang_diharapkan = 'diajukan ke bpm'; break;
    case 'bkh': $status_yang_diharapkan = 'verifikasi bkkh'; break;
    case 'wr3': $status_yang_diharapkan = 'verifikasi wr3'; break;
}

// Jika status saat ini tidak cocok dengan yang diharapkan untuk peran ini
if ($status_saat_ini !== $status_yang_diharapkan) {
    echo "<div class='container-fluid px-4'><div class='alert alert-warning mt-4'>Anda tidak memiliki izin untuk memproses proposal ini pada tahap ini, atau proposal sudah diproses. Status saat ini: <strong>" . htmlspecialchars($pengajuan['status']) . "</strong></div></div>";
    return; // Hentikan eksekusi skrip
}

// Mendapatkan nama file proposal saja (tanpa path)
$nama_file_proposal = basename($pengajuan['file_proposal'] ?? '');
$path_proposal = 'uploads/proposal/' . $nama_file_proposal; // Bentuk path lengkap
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Verifikasi Proposal Pengajuan</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="index.php?page=dashboard">Dashboard</a></li>
        <li class="breadcrumb-item active">Verifikasi Proposal</li>
    </ol>

    <?php
    // Menampilkan pesan error jika ada dari redirect sebelumnya
    if (isset($_GET['error'])) {
        $error_map = [
            'aksi_invalid' => 'Aksi yang dikirim tidak valid.',
            'catatan_kosong' => 'Catatan wajib diisi jika Anda menolak proposal.',
            'status_error' => 'Gagal menentukan status proposal selanjutnya.',
            'db_prepare_gagal' => 'Gagal menyiapkan perintah database.',
            'update_gagal' => 'Gagal memperbarui status proposal di database.',
        ];
        $error_message = $error_map[$_GET['error']] ?? 'Terjadi kesalahan tidak diketahui.';
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Error!</strong> ' . htmlspecialchars($error_message) . '
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>';
    }
    ?>

    <div class="row">
        <!-- Kolom Kiri: Detail Pengajuan -->
        <div class="col-lg-7">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <i class="bi bi-info-circle-fill me-1"></i> Detail Pengajuan
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th style="width: 30%;">Nama Kegiatan</th>
                            <td>: <?php echo htmlspecialchars($pengajuan['nama_kegiatan']); ?></td>
                        </tr>
                        <tr>
                            <th>Ormawa Pengaju</th>
                            <td>: <?php echo htmlspecialchars($pengajuan['nama_ormawa']); ?></td>
                        </tr>
                        
                         <tr>
                            <th>Tanggal Kegiatan</th>
                            <td>: <?php echo isset($pengajuan['tanggal_kegiatan']) ? date('d F Y', strtotime($pengajuan['tanggal_kegiatan'])) : date('d F Y', strtotime($pengajuan['tanggal_pengajuan'])) ; ?></td>
                         </tr>
                        <tr>
                            <th>Dana Diajukan</th>
                            <td>: <strong>Rp <?php echo number_format($pengajuan['dana_diajukan'], 0, ',', '.'); ?></strong></td>
                        </tr>
                        <tr>
                            <th>File Proposal</th>
                            <td>:
                                <?php if (!empty($nama_file_proposal) && file_exists($path_proposal)): ?>
                                <a href="<?php echo htmlspecialchars($path_proposal); ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-eye-fill me-1"></i> Lihat Proposal
                                </a>
                                <?php else: ?>
                                <span class="text-muted fst-italic">File tidak ditemukan atau belum diunggah.</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Form Tindakan Verifikasi -->
        <div class="col-lg-5">
            <div class="card shadow-sm mb-4">
                 <div class="card-header bg-white py-3">
                    <i class="bi bi-check2-square me-1"></i> Tindakan Verifikasi
                </div>
                <div class="card-body">
                    <form action="index.php?page=verifikasi&id=<?php echo $id_pengajuan; ?>" method="POST">
                        <div class="mb-3">
                            <label for="catatan" class="form-label">Catatan (Wajib diisi jika menolak)</label>
                            <textarea class="form-control" id="catatan" name="catatan" rows="5" placeholder="Berikan alasan penolakan atau catatan tambahan jika disetujui..."></textarea>
                        </div>
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                             <button type="submit" name="aksi" value="tolak" class="btn btn-danger me-md-2 mb-2 mb-md-0">
                                <i class="bi bi-x-circle-fill me-1"></i> Tolak
                            </button>
                            <button type="submit" name="aksi" value="setuju" class="btn btn-success">
                                <i class="bi bi-check-circle-fill me-1"></i> Setujui & Lanjutkan
                            </button>
                        </div>
                         <a href="index.php?page=dashboard" class="btn btn-secondary mt-3 w-100">
                            <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
