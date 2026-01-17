<?php
// Keamanan sudah ditangani oleh router di index.php
if ($_SESSION['user_role'] !== 'bkh') {
    exit('Akses ditolak.');
}

// FUNGSI BANTUAN UNTUK MEMPERBARUI KONFIGURASI DI DATABASE (VERSI DISEMPURNAKAN)
function update_konfigurasi($conn, $nama_konfigurasi, $nilai_konfigurasi) {
    // Menggunakan "INSERT ... ON DUPLICATE KEY UPDATE" untuk operasi yang lebih aman dan efisien (UPSERT).
    $stmt = $conn->prepare(
        "INSERT INTO konfigurasi (nama_konfigurasi, nilai_konfigurasi) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE nilai_konfigurasi = VALUES(nilai_konfigurasi)"
    );
    // Tipe "ss" (string, string) dapat menangani nilai NULL dengan benar di MySQLi
    $stmt->bind_param("ss", $nama_konfigurasi, $nilai_konfigurasi);
    $stmt->execute();
    return $stmt->affected_rows > 0; // Mengembalikan true jika ada perubahan
}

/**
 * FUNGSI BANTUAN UNTUK MENGELOLA UPLOAD FILE (DENGAN ERROR HANDLING)
 * @return null|array ['filename' => string] jika sukses, ['error' => string] jika gagal
 */
function handle_upload_sistem($file_input_name, $upload_dir = 'uploads/sistem/') {
    // Periksa apakah file diupload atau tidak
    if (!isset($_FILES[$file_input_name]) || $_FILES[$file_input_name]['error'] === UPLOAD_ERR_NO_FILE || empty($_FILES[$file_input_name]['name'])) {
        return null; // Tidak ada file yang diupload, ini bukan error
    }

    // Tangani error upload PHP lainnya
    if ($_FILES[$file_input_name]['error'] !== UPLOAD_ERR_OK) {
        // Berikan pesan error berdasarkan kode error
        switch ($_FILES[$file_input_name]['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return ['error' => "Ukuran file terlalu besar. Periksa 'upload_max_filesize' di php.ini."];
            case UPLOAD_ERR_PARTIAL:
                return ['error' => "File hanya terupload sebagian."];
            default:
                return ['error' => "Terjadi error upload yang tidak diketahui (Kode: " . $_FILES[$file_input_name]['error'] . ")"];
        }
    }

    // Pastikan direktori ada
    if (!is_dir($upload_dir)) {
        // Coba buat direktori, beri @ untuk menekan warning jika gagal (akan ditangani di bawah)
        @mkdir($upload_dir, 0755, true);
    }

    // PERIKSA KRUSIAL: Apakah direktori bisa ditulisi?
    if (!is_dir($upload_dir) || !is_writable($upload_dir)) {
        return ['error' => "Folder upload '{$upload_dir}' tidak ada atau tidak dapat ditulisi (periksa file permissions)."];
    }
    
    $file_tmp_path = $_FILES[$file_input_name]['tmp_name'];
    $file_name = basename($_FILES[$file_input_name]['name']);
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'svg'];

    if (in_array($file_ext, $allowed_ext)) {
        $new_file_name = uniqid('logo_', true) . '.' . $file_ext;
        $dest_path = $upload_dir . $new_file_name;

        if (move_uploaded_file($file_tmp_path, $dest_path)) {
            return ['filename' => $new_file_name]; // Sukses
        } else {
            return ['error' => "Gagal memindahkan file yang diupload. Kemungkinan masalah permission."];
        }
    } else {
        return ['error' => "Format file '{$file_ext}' tidak diizinkan. Hanya boleh: " . implode(', ', $allowed_ext)];
    }
}

// --- LOGIKA UNTUK MEMPROSES FORM SAAT DISIMPAN ---
$upload_errors = [];
$update_success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Update Nama Aplikasi
    if (isset($_POST['nama_aplikasi'])) {
        if (update_konfigurasi($conn, 'nama_aplikasi', trim($_POST['nama_aplikasi']))) {
            $update_success = true;
        }
    }

    // 2. Update Logo Sistem
    $upload_result_sistem = handle_upload_sistem('logo_baru');
    if ($upload_result_sistem) { // Hanya proses jika ada hasil (bukan null)
        if (isset($upload_result_sistem['filename'])) {
            if (update_konfigurasi($conn, 'logo_sistem', $upload_result_sistem['filename'])) {
                $update_success = true;
            }
        } elseif (isset($upload_result_sistem['error'])) {
            $upload_errors[] = "Logo Sistem: " . $upload_result_sistem['error'];
        }
    }

    // 3. Update Logo Kop Surat
    $upload_result_kop = handle_upload_sistem('logo_kop_baru');
    if ($upload_result_kop) {
        if (isset($upload_result_kop['filename'])) {
            if (update_konfigurasi($conn, 'kop_logo', $upload_result_kop['filename'])) {
                $update_success = true;
            }
        } elseif (isset($upload_result_kop['error'])) {
            $upload_errors[] = "Logo Kop Surat: " . $upload_result_kop['error'];
        }
    }
    
    // 4. Update Teks Kop Surat
    $kop_fields = ['kop_baris1', 'kop_baris2', 'kop_baris3', 'kop_baris4'];
    foreach ($kop_fields as $field) {
        if (isset($_POST[$field])) {
            $nilai = trim($_POST[$field]);
            $nilai_untuk_db = ($nilai === '') ? null : $nilai;
            if (update_konfigurasi($conn, $field, $nilai_untuk_db)) {
                $update_success = true;
            }
        }
    }

    // 5. Reload konfigurasi ke session
    if (function_exists('load_konfigurasi')) {
        load_konfigurasi($conn, true); 
    }
    
    // 6. Redirect berdasarkan hasil
    if (!empty($upload_errors)) {
        // Jika ada error upload, prioritaskan tampilkan error
        $error_string = implode(' | ', $upload_errors);
        header('Location: index.php?page=atur_sistem&status=error&msg=' . urlencode($error_string));
    } elseif ($update_success) {
        // Jika tidak ada error DAN ada update yang sukses
        header('Location: index.php?page=atur_sistem&status=sukses');
    } else {
        // Tidak ada error, tapi tidak ada yang berubah
        header('Location: index.php?page=atur_sistem');
    }
    exit();
}

// --- Mengambil data konfigurasi LANGSUNG dari database untuk memastikan data terbaru ---
// (Logika pengambilan data Anda sudah bagus, tidak perlu diubah)
$konfigurasi_db = [];
$stmt_konfig = $conn->prepare("SELECT nama_konfigurasi, nilai_konfigurasi FROM konfigurasi");
$stmt_konfig->execute();
$result_konfig = $stmt_konfig->get_result();
while ($row_konfig = $result_konfig->fetch_assoc()) {
    $konfigurasi_db[$row_konfig['nama_konfigurasi']] = $row_konfig['nilai_konfigurasi'];
}
$stmt_konfig->close();
$konfigurasi = $konfigurasi_db;

// --- Pengaturan Sistem Eksisting ---
$nama_aplikasi_saat_ini = htmlspecialchars($konfigurasi['nama_aplikasi'] ?? 'SI-Keuangan');
$logo_saat_ini = $konfigurasi['logo_sistem'] ?? ''; // Ubah default ke string kosong
$path_logo = 'https://placehold.co/200x60/EFEFEF/AAAAAA?text=No+Logo';
if (!empty($logo_saat_ini) && file_exists('uploads/sistem/' . $logo_saat_ini)) {
    $path_logo = 'uploads/sistem/' . $logo_saat_ini . '?v=' . time(); // Tambahkan cache buster
}

// --- Pengaturan Kop Surat ---
$kop_logo_saat_ini = $konfigurasi['kop_logo'] ?? '';

// (Logika kop surat Anda sudah bagus)
$kop_baris1_val = $konfigurasi['kop_baris1'] ?? '';
$kop_baris1 = htmlspecialchars(empty($kop_baris1_val) ? 'INSTITUT TEKNOLOGI GARUT' : $kop_baris1_val);

$kop_baris2_val = $konfigurasi['kop_baris2'] ?? '';
$kop_baris2 = htmlspecialchars(empty($kop_baris2_val) ? 'BIRO KETENAGAAN KEMAHASISWAAN DAN HUBUNGAN MASYARAKAT (BKKH)' : $kop_baris2_val);

$kop_baris3_val = $konfigurasi['kop_baris3'] ?? '';
$kop_baris3 = htmlspecialchars(empty($kop_baris3_val) ? 'Jl. Mayor Syamsu No.1, Jayaraga, Kec. Tarogong Kidul, Kabupaten Garut, Jawa Barat' : $kop_baris3_val);

$kop_baris4_val = $konfigurasi['kop_baris4'] ?? '';
$kop_baris4 = htmlspecialchars(empty($kop_baris4_val) ? 'Telp. 0262-232773, Email: itg@garut.ac.id' : $kop_baris4_val);


$path_logo_kop = 'https://placehold.co/100x100/EFEFEF/AAAAAA?text=No+Logo';
// --- PERBAIKAN BUG COPY-PASTE ---
// Variabel $logo_saat_ini diganti menjadi $kop_logo_saat_ini
if (!empty($kop_logo_saat_ini) && file_exists('uploads/sistem/' . $kop_logo_saat_ini)) {
    $path_logo_kop = 'uploads/sistem/' . $kop_logo_saat_ini . '?v=' . time(); // Tambahkan cache buster
}
?>

<div class="container-fluid">
    <!-- Notifikasi Dinamis (Sukses atau Error) -->
    <?php if (isset($_GET['status']) && $_GET['status'] === 'sukses'): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <strong>Berhasil!</strong> Pengaturan sistem telah berhasil diperbarui.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php elseif (isset($_GET['status']) && $_GET['status'] === 'error'): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Gagal Memperbarui!</strong> Terjadi kesalahan berikut:
        <ul class="mb-0">
            <?php 
                $error_messages = explode(' | ', $_GET['msg'] ?? 'Terjadi kesalahan tidak diketahui.');
                foreach ($error_messages as $msg) {
                    echo '<li>' . htmlspecialchars($msg) . '</li>';
                }
            ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h4 class="mb-0">Pengaturan Sistem</h4>
                </div>
                <div class="card-body">
                    <form action="index.php?page=atur_sistem" method="post" enctype="multipart/form-data">
                        
                        <!-- Input Nama Aplikasi -->
                        <div class="mb-4">
                            <label for="nama_aplikasi" class="form-label fw-semibold">Nama Aplikasi</label>
                            <input type="text" class="form-control" id="nama_aplikasi" name="nama_aplikasi" value="<?php echo $nama_aplikasi_saat_ini; ?>" required>
                            <small class="form-text text-muted">Nama ini akan muncul di sidebar dan halaman login.</small>
                        </div>
                        
                        <hr>

                        <!-- Input Logo Sistem -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Logo Sistem</label>
                            <div class="text-center mb-3">
                                <img id="preview_sistem" src="<?php echo htmlspecialchars($path_logo); ?>" alt="Logo Sistem" class="img-fluid" style="max-height: 80px; border: 1px solid #ddd; padding: 5px; border-radius: 5px;">
                            </div>
                            <label for="logo_baru" class="form-label">Upload Logo Baru (Opsional)</label>
                            <input class="form-control" type="file" id="logo_baru" name="logo_baru" onchange="previewImage(event, 'preview_sistem')">
                            <small class="form-text text-muted">Kosongkan jika tidak ingin mengubah logo. Rekomendasi rasio 3:1 atau 4:1.</small>
                        </div>

                        <hr class="my-4">

                        <!-- Form Pengaturan Kop Surat -->
                        <h5 class="mb-3 fw-semibold">Pengaturan Kop Surat</h5>
                        
                        <!-- Input Logo Kop Surat -->
                        <div class="mb-4">
                             <label class="form-label fw-semibold">Logo Kop Surat</label>
                             <div class="text-center mb-3">
                                 <img id="preview_kop" src="<?php echo htmlspecialchars($path_logo_kop); ?>" alt="Logo Kop Surat" class="img-fluid" style="max-height: 100px; border: 1px solid #ddd; padding: 5px; border-radius: 5px;">
                             </div>
                             <label for="logo_kop_baru" class="form-label">Upload Logo Baru (Opsional)</label>
                             <input class="form-control" type="file" id="logo_kop_baru" name="logo_kop_baru" onchange="previewImage(event, 'preview_kop')">
                             <small class="form-text text-muted">Kosongkan jika tidak ingin mengubah logo.</small>
                        </div>

                        <!-- Input Teks Kop Surat -->
                        <div class="mb-3">
                            <label for="kop_baris1" class="form-label">Baris 1</label>
                            <input type="text" class="form-control" id="kop_baris1" name="kop_baris1" value="<?php echo $kop_baris1; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="kop_baris2" class="form-label">Baris 2</label>
                            <input type="text" class="form-control" id="kop_baris2" name="kop_baris2" value="<?php echo $kop_baris2; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="kop_baris3" class="form-label">Baris 3 (Alamat)</label>
                            <input type="text" class="form-control" id="kop_baris3" name="kop_baris3" value="<?php echo $kop_baris3; ?>">
                        </div>
                         <div class="mb-3">
                            <label for="kop_baris4" class="form-label">Baris 4 (Kontak)</label>
                            <input type="text" class="form-control" id="kop_baris4" name="kop_baris4" value="<?php echo $kop_baris4; ?>">
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 mt-3">
                            <i class="bi bi-save me-2"></i>Simpan Pengaturan
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Fungsi preview dibuat lebih fleksibel untuk menangani banyak gambar
    function previewImage(event, previewId) {
        const reader = new FileReader();
        reader.onload = function(){
            const output = document.getElementById(previewId);
            output.src = reader.result;
        };
        if (event.target.files[0]) {
            reader.readAsDataURL(event.target.files[0]);
        }
    }
</script>

