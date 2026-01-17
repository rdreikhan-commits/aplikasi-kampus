<?php
/**
 * File: roles/ormawa/tambah_pengajuan.php
 * Deskripsi: Formulir untuk menambahkan pengajuan proposal baru dengan alur dinamis.
 *
 * --- PERBAIKAN ---
 * 1. (Sebelumnya) Memperbaiki query INSERT agar lolos di server hosting (Strict Mode).
 * 2. (Sebelumnya) Memperbaiki logika blocking agar case-insensitive.
 * 3. (Sebelumnya) Mengubah logika blocking agar 'lpj ditolak bkkh' memblokir.
 * 4. (BARU) Mengubah logika blocking (baris 174) agar proposal yang
 * ditolak ('ditolak bem', 'ditolak bpm', dll.) JUGA MEMBLOKIR pengajuan baru.
 */

// Menambahkan 'bem' dan 'bpm' agar bisa akses halaman
check_role(['ormawa', 'bem', 'bpm']);

$user_id = $_SESSION['user_id'];
// Mengambil peran pengguna yang sedang login
$user_role = $_SESSION['user_role'];
$status_akun = $_SESSION['status_akun'] ?? 'nonaktif';
$bisa_mengajukan = true;
$pesan_blokir = "";
$sisa_saldo = 0;
$error_msg = "";

// --- LOGIKA PERHITUNGAN SISA SALDO YANG BENAR (DIAMBIL DARI DASHBOARD) ---

// Ambil Saldo Awal
$stmt_saldo = $conn->prepare("SELECT saldo FROM users WHERE id_user = ?");
$stmt_saldo->bind_param("i", $user_id);
$stmt_saldo->execute();
$total_saldo = $stmt_saldo->get_result()->fetch_assoc()['saldo'] ?? 0;
$stmt_saldo->close();

// Hitung Saldo Terpakai (Dana sudah berkomitmen atau cair)
// PERHATIAN: Status 'lpj ditolak bkkh' sekarang TIDAK dianggap terpakai untuk perhitungan saldo,
// karena dana sudah cair sebelumnya. Anda mungkin perlu merevisi ini jika logikanya berbeda.
// Untuk sekarang, saya biarkan 'lpj ditolak bkkh' tetap di sini agar konsisten dengan perhitungan SISA saldo sebelumnya.
$status_terpakai = [
    'disetujui wr3, siap diajukan ke bendahara',
    'diajukan ke bendahara',
    'dana cair',
    'lpj diajukan',
    'lpj ditolak bkkh',
    'lpj diverifikasi',
    'selesai'
];
$placeholders_terpakai = implode(',', array_fill(0, count($status_terpakai), '?'));
$stmt_terpakai = $conn->prepare("SELECT SUM(dana_diajukan) AS total FROM pengajuan WHERE id_user_ormawa = ? AND TRIM(LOWER(status)) IN ($placeholders_terpakai)");
$types_terpakai = 'i' . str_repeat('s', count($status_terpakai));
$params_terpakai = array_merge([$user_id], $status_terpakai);
$stmt_terpakai->bind_param($types_terpakai, ...$params_terpakai);
$stmt_terpakai->execute();
$saldo_terpakai = $stmt_terpakai->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_terpakai->close();

// Hitung Saldo yang diajukan (masih dalam proses verifikasi awal)
$status_proses = [
    'diajukan ke bem',
    'diajukan ke bpm',
    'verifikasi bkkh',
    'verifikasi wr3'
];
$placeholders_proses = implode(',', array_fill(0, count($status_proses), '?'));
$stmt_diajukan = $conn->prepare("SELECT SUM(dana_diajukan) AS total FROM pengajuan WHERE id_user_ormawa = ? AND TRIM(LOWER(status)) IN ($placeholders_proses)");
$types_proses = 'i' . str_repeat('s', count($status_proses));
$params_proses = array_merge([$user_id], $status_proses);
$stmt_diajukan->bind_param($types_proses, ...$params_proses);
$stmt_diajukan->execute();
$saldo_dalam_proses = $stmt_diajukan->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_diajukan->close();

// Hitung Sisa Saldo Final
$sisa_saldo = $total_saldo - $saldo_terpakai - $saldo_dalam_proses;
// --- AKHIR LOGIKA PERHITUNGAN SISA SALDO ---


// 1. Logika Pemrosesan Formulir
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari formulir
    $nama_kegiatan = $_POST['nama_kegiatan'];
    $tanggal_pengajuan = $_POST['tanggal_pengajuan']; // Ini adalah Tanggal Pelaksanaan dari form
    // Konversi format Rupiah ke angka float
    $dana_diajukan = (float)preg_replace('/[^\d]/', '', $_POST['dana_diajukan']);
    $file_proposal = $_FILES['file_proposal'];

    // Validasi Sisi Server
    if ($dana_diajukan > $sisa_saldo) {
        $error_msg = 'Dana yang diajukan melebihi sisa saldo Anda!';
    } elseif ($file_proposal['error'] === UPLOAD_ERR_NO_FILE) {
        $error_msg = 'File proposal wajib diunggah.';
    } elseif ($file_proposal['type'] !== 'application/pdf') {
        $error_msg = 'Maaf, hanya file PDF yang diizinkan.';
    } elseif ($file_proposal['size'] > 5000000) { // 5MB
        $error_msg = 'Ukuran file proposal tidak boleh lebih dari 5MB.';
    }

    if (empty($error_msg)) {
        // Proses Upload File
        $upload_dir = 'uploads/proposal/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        // Ganti nama file agar unik dan aman
        $file_extension = pathinfo($file_proposal['name'], PATHINFO_EXTENSION);
        $file_name = "proposal_" . $user_id . "_" . time() . "." . $file_extension;
        $file_path = $upload_dir . $file_name;

        if (move_uploaded_file($file_proposal['tmp_name'], $file_path)) {

            // --- LOGIKA BARU UNTUK MENENTUKAN STATUS ---
            if ($user_role === 'bem' || $user_role === 'bpm') {
                $status = "Verifikasi BKKH";
            } else {
                $status = "Diajukan Ke BEM";
            }
            // --- AKHIR LOGIKA BARU ---

            // --- PERBAIKAN QUERY INSERT (AGAR LOLOS STRICT MODE DI HOSTING) ---
            // Mengisi kolom NOT NULL yang tidak ada di form dengan data default
            $judul_kegiatan_default = $nama_kegiatan; // Isi judul_kegiatan dgn nama_kegiatan
            $deskripsi_kegiatan_default = $nama_kegiatan; // Isi deskripsi dgn nama_kegiatan
            $nominal_pengajuan_default = 0.00; // Sesuai struktur DB

            // Simpan data ke database
            // Kolom 'tanggal_pengajuan' di DB (yang auto NOW()) BUKAN $tanggal_pengajuan (yang dari form)
            // $tanggal_pengajuan dari form kita anggap 'tanggal_pelaksanaan' (jika ada kolomnya)
            // Berdasarkan DB Anda, $tanggal_pengajuan (dari form) dimasukkan ke kolom 'tanggal_pengajuan'.
            // Kolom 'tanggal_diajukan' yang error kemarin, kita HAPUS.
            $stmt_insert = $conn->prepare(
                "INSERT INTO pengajuan (
                    id_user_ormawa, nama_kegiatan, tanggal_pengajuan, dana_diajukan, file_proposal, status,
                    judul_kegiatan, deskripsi_kegiatan, nominal_pengajuan
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );

            if ($stmt_insert) {
                // Tipe data: i (id_user), s (nama), s (tgl), d (dana), s (file), s (status), s (judul), s (desk), d (nominal)
                $stmt_insert->bind_param("issdssssd",
                    $user_id, $nama_kegiatan, $tanggal_pengajuan, $dana_diajukan, $file_path, $status,
                    $judul_kegiatan_default, $deskripsi_kegiatan_default, $nominal_pengajuan_default
                );

                if ($stmt_insert->execute()) {
                    // PERBAIKAN: Tambahkan pencatatan ke tabel histori
                    $id_pengajuan_baru = $conn->insert_id;
                    $catatan_histori = '';
                    if ($user_role === 'bem' || $user_role === 'bpm') {
                        $catatan_histori = "Proposal diajukan oleh " . $_SESSION['nama_lengkap'] . " dan langsung diteruskan untuk verifikasi BKKH.";
                    } else {
                        $catatan_histori = "Proposal diajukan oleh Ormawa dan menunggu verifikasi BEM.";
                    }

                    $stmt_histori = $conn->prepare("INSERT INTO histori_status (id_pengajuan, id_user, status, catatan, tanggal_update) VALUES (?, ?, ?, ?, NOW())");
                    if($stmt_histori){
                        $stmt_histori->bind_param("iiss", $id_pengajuan_baru, $user_id, $status, $catatan_histori);
                        $stmt_histori->execute();
                        $stmt_histori->close();
                    }

                    header("Location: index.php?page=riwayat-pengajuan&status=tambah_sukses");
                    exit();
                } else {
                    // Tampilkan error database yang sesungguhnya untuk debug
                    $error_msg = 'Gagal menyimpan data ke database. Error: ' . $stmt_insert->error;
                }
                $stmt_insert->close();
            } else {
                // Tampilkan error database yang sesungguhnya untuk debug
                $error_msg = 'Gagal menyiapkan statement database. Error: ' . $conn->error;
            }
        } else {
            $error_msg = 'Maaf, terjadi kesalahan saat mengupload file.';
        }
    }
}


// --- LOGIKA UNTUK MEMBLOKIR PENGAJUAN ---
// 2. Cek status akun
if ($status_akun == 'nonaktif') {
    $bisa_mengajukan = false;
    $pesan_blokir = "Akun Anda saat ini sedang dinonaktifkan oleh BKKH. Anda tidak dapat membuat pengajuan proposal baru.";
}

// 3. Cek proposal aktif atau saldo habis (hanya jika akun aktif)
if ($bisa_mengajukan) {

    // --- PERBAIKAN LOGIKA BLOKING v3 ---
    // Daftar status yang dianggap sudah SELESAI (gunakan lowercase)
    // Sekarang HANYA 'selesai' yang dianggap selesai. Status ditolak akan memblokir.
    $status_benar_benar_selesai = ['selesai'];

    // Buat placeholder '?' sejumlah status di atas
    $placeholders = implode(',', array_fill(0, count($status_benar_benar_selesai), '?'));

    // Query untuk mencari proposal yang statusnya BUKAN 'selesai' (gunakan LOWER() dan TRIM())
    // Jika ada proposal dengan status selain 'selesai', maka $bisa_mengajukan = false
    $query_check = "SELECT nama_kegiatan, status FROM pengajuan WHERE id_user_ormawa = ? AND TRIM(LOWER(status)) NOT IN ($placeholders)";

    $stmt_check = $conn->prepare($query_check);
    if ($stmt_check) {
        // Gabungkan tipe data dan nilainya untuk bind_param
        $types = 'i' . str_repeat('s', count($status_benar_benar_selesai));
        $params = array_merge([$user_id], $status_benar_benar_selesai); // Array status sudah lowercase

        // Gunakan spread operator (...) untuk bind_param dinamis
        $stmt_check->bind_param($types, ...$params);
        $stmt_check->execute();

        $result_check = $stmt_check->get_result();
        if ($result_check->num_rows > 0) {
            $proposal_aktif = $result_check->fetch_assoc();
            $bisa_mengajukan = false;
            // Pesan disesuaikan agar lebih umum
            $pesan_blokir = "Anda belum bisa mengajukan proposal baru karena masih ada proposal/LPJ yang belum selesai diproses atau ditolak. <br><b>Nama Kegiatan:</b> " . htmlspecialchars($proposal_aktif['nama_kegiatan']) . " <br><b>Status Saat Ini:</b> " . htmlspecialchars($proposal_aktif['status']);
        }
        $stmt_check->close();
    }
    // --- AKHIR PERBAIKAN LOGIKA BLOKING v3 ---

    // LOGIKA BARU: Cek jika sisa saldo habis dan tidak ada proposal aktif lain
    if ($bisa_mengajukan && $sisa_saldo <= 0) {
        $bisa_mengajukan = false;
        $pesan_blokir = "Anda tidak dapat mengajukan proposal baru karena sisa saldo Anda saat ini adalah <strong>Rp 0</strong>.";
    }
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Buat Pengajuan Proposal Baru</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item active">Formulir Pengajuan</li>
    </ol>

    <div class="card">
        <div class="card-header"><i class="bi bi-plus-circle-fill me-1"></i> Isi Data Proposal</div>
        <div class="card-body">

            <?php if (!$bisa_mengajukan): ?>
                <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-3 fs-4"></i>
                    <div>
                        <h4 class="alert-heading fw-bold">Pengajuan Ditangguhkan</h4>
                        <p class="mb-0"><?php echo $pesan_blokir; ?></p>
                    </div>
                </div>
            <?php else: ?>
                <?php if (!empty($error_msg)): ?>
                    <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                        <i class="bi bi-x-circle-fill me-3 fs-4"></i>
                        <div>
                            <p class="mb-0 fw-bold"><?php echo $error_msg; ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="mb-4">
                        <label for="nama_kegiatan" class="form-label"><i class="bi bi-journal-text me-1"></i> Nama Kegiatan</label>
                        <input type="text" class="form-control" id="nama_kegiatan" name="nama_kegiatan" required>
                    </div>
                    <div class="mb-4">
                        <label for="tanggal_pengajuan" class="form-label"><i class="bi bi-calendar-date-fill me-1"></i> Tanggal Kegiatan</label>
                        <input type="date" class="form-control" id="tanggal_pengajuan" name="tanggal_pengajuan" required>
                    </div>
                    <div class="mb-4">
                        <label for="dana_diajukan" class="form-label"><i class="bi bi-cash-stack me-1"></i> Dana yang Diajukan (Rp)</label>
                        <input type="text" class="form-control" id="dana_diajukan" name="dana_diajukan" required onkeyup="formatRupiah(this)" placeholder="Contoh: 1000.000">
                        <div class="mt-3 p-3 bg-light rounded shadow-sm">
                            <i class="bi bi-wallet-fill me-1 text-success"></i> Sisa Saldo Anda:
                            <strong class="text-success">Rp <?php echo number_format($sisa_saldo, 0, ',', '.'); ?></strong>
                        </div>
                    </div>
                    <div class="mb-5">
                        <label for="file_proposal" class="form-label"><i class="bi bi-file-earmark-pdf-fill me-1"></i> Upload File Proposal (PDF, maks 5MB)</label>
                        <input class="form-control" type="file" id="file_proposal" name="file_proposal" accept=".pdf" required>
                    </div>
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary me-2"><i class="bi bi-send-fill me-1"></i> Ajukan</button>
                        <a href="index.php?page=dashboard" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function formatRupiah(input) {
    var value = input.value.replace(/[^,\d]/g, '').toString();
    var split = value.split(',');
    var sisa = split[0].length % 3;
    var rupiah = split[0].substr(0, sisa);
    var ribuan = split[0].substr(sisa).match(/\d{3}/gi);

    if (ribuan) {
        separator = sisa ? '.' : '';
        rupiah += separator + ribuan.join('.');
    }

    rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
    input.value = rupiah;
}
</script>

