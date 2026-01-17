<?php
/**
 * File: cetak_surat.php
 * Deskripsi: Menghasilkan surat balasan resmi dengan kop dinamis yang diambil langsung dari database.
 */

// Membuat URL dasar yang benar untuk tautan verifikasi QR
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$path = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
$base_url_correct = $protocol . '://' . $host . $path . '/';

// Memeriksa dan memuat library QR Code
if (!file_exists('phpqrcode/qrlib.php')) {
    die("Error: Library QR Code (phpqrcode/qrlib.php) tidak ditemukan.");
}
require_once 'phpqrcode/qrlib.php';

// Validasi ID Pengajuan dari URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID Pengajuan tidak valid.");
}
$id_pengajuan = (int)$_GET['id'];

// --- Mengambil data konfigurasi LANGSUNG dari database ---
$konfigurasi = [];
$stmt_konfig = $conn->prepare("SELECT nama_konfigurasi, nilai_konfigurasi FROM konfigurasi");
$stmt_konfig->execute();
$result_konfig = $stmt_konfig->get_result();
while ($row_konfig = $result_konfig->fetch_assoc()) {
    $konfigurasi[$row_konfig['nama_konfigurasi']] = $row_konfig['nilai_konfigurasi'];
}
$stmt_konfig->close();

// PERBAIKAN: Menyiapkan variabel untuk kop surat, dengan fallback jika nilai di DB kosong
$kop_logo = !empty($konfigurasi['kop_logo']) ? $konfigurasi['kop_logo'] : '';
$kop_baris1 = !empty($konfigurasi['kop_baris1']) ? htmlspecialchars($konfigurasi['kop_baris1']) : 'INSTITUT TEKNOLOGI GARUT';
$kop_baris2 = !empty($konfigurasi['kop_baris2']) ? htmlspecialchars($konfigurasi['kop_baris2']) : 'BIRO KETENAGAAN KEMAHASISWAAN DAN HUBUNGAN MASYARAKAT (BKKH)';
$kop_baris3 = !empty($konfigurasi['kop_baris3']) ? htmlspecialchars($konfigurasi['kop_baris3']) : 'Jl. Mayor Syamsu No.1, Jayaraga, Kec. Tarogong Kidul, Kabupaten Garut, Jawa Barat';
$kop_baris4 = !empty($konfigurasi['kop_baris4']) ? htmlspecialchars($konfigurasi['kop_baris4']) : 'Telp. 0262-232773, Email: itg@garut.ac.id';


// Menentukan path logo kop surat, dengan gambar placeholder sebagai fallback
$path_logo_kop = 'https://placehold.co/100x100/EFEFEF/AAAAAA?text=No+Logo';
if (!empty($kop_logo) && file_exists('uploads/sistem/' . $kop_logo)) {
    $path_logo_kop = 'uploads/sistem/' . $kop_logo;
}
// --- AKHIR PENYESUAIAN ---


// 1. Ambil data utama pengajuan
$stmt_pengajuan = $conn->prepare(
    "SELECT p.*, u.nama_lengkap AS nama_ormawa, u.role AS pengaju_role
     FROM pengajuan p 
     JOIN users u ON p.id_user_ormawa = u.id_user 
     WHERE p.id_pengajuan = ?"
);
$stmt_pengajuan->bind_param("i", $id_pengajuan);
$stmt_pengajuan->execute();
$result_pengajuan = $stmt_pengajuan->get_result();
if ($result_pengajuan->num_rows === 0) {
    die("Pengajuan tidak ditemukan.");
}
$pengajuan = $result_pengajuan->fetch_assoc();
$stmt_pengajuan->close();

// Proteksi: Hentikan jika nomor surat belum diterbitkan
if (empty($pengajuan['nomor_surat'])) {
    echo '<!DOCTYPE html><html lang="id"><head><title>Akses Ditolak</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-light d-flex align-items-center justify-content-center vh-100"><div class="text-center"><h1 class="display-4">Akses Ditolak</h1><p class="lead">Surat balasan ini belum dapat diakses karena Nomor Surat resmi belum diterbitkan.</p><p>Silakan hubungi pihak BKKH untuk informasi lebih lanjut.</p><a href="javascript:history.back()"</a></div></body></html>';
    exit; 
}

// 2. Ambil seluruh riwayat status pengajuan
$stmt_histori = $conn->prepare(
    "SELECT h.status, h.tanggal_update, u.nama_lengkap AS nama_pejabat, u.role AS pejabat_role
     FROM histori_status h
     JOIN users u ON h.id_user = u.id_user
     WHERE h.id_pengajuan = ?
     ORDER BY h.tanggal_update ASC"
);
$stmt_histori->bind_param("i", $id_pengajuan);
$stmt_histori->execute();
$all_history = $stmt_histori->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_histori->close();

// 3. Ambil data pejabat sebagai fallback jika data histori tidak lengkap
$pejabat_fallback = [];
$nama_bkh_fallback = '';
$stmt_pejabat = $conn->prepare("SELECT nama_lengkap, role FROM users WHERE role IN ('bkh', 'wr3')");
$stmt_pejabat->execute();
$result_pejabat = $stmt_pejabat->get_result();
while ($row = $result_pejabat->fetch_assoc()) {
    $pejabat_fallback[$row['role']] = $row['nama_lengkap'];
    if($row['role'] === 'bkh') {
        $nama_bkh_fallback = $row['nama_lengkap'];
    }
}
$stmt_pejabat->close();

// 4. Proses data histori untuk ditampilkan di rekam jejak
$rekam_jejak = [
    'Verifikasi BKKH' => ['pejabat' => $pejabat_fallback['bkh'] ?? '-', 'tanggal' => '<em class="text-muted">Belum diproses</em>'],
    'Persetujuan WR3' => ['pejabat' => $pejabat_fallback['wr3'] ?? '-', 'tanggal' => '<em class="text-muted">Belum diproses</em>']
];

foreach ($all_history as $item) {
    $pejabat_role = $item['pejabat_role'];
    if (strpos(strtolower($item['status']), 'ditolak') === false) {
        if ($pejabat_role === 'bkh') {
            $rekam_jejak['Verifikasi BKKH']['pejabat'] = $item['nama_pejabat'];
            $rekam_jejak['Verifikasi BKKH']['tanggal'] = date('d F Y, H:i', strtotime($item['tanggal_update']));
        }
        if ($pejabat_role === 'wr3') {
            $rekam_jejak['Persetujuan WR3']['pejabat'] = $item['nama_pejabat'];
            $rekam_jejak['Persetujuan WR3']['tanggal'] = date('d F Y, H:i', strtotime($item['tanggal_update']));
        }
    }
}


// 5. Logika Pembuatan QR Code
$qr_content_url = '#';
$qr_file_path = '';
$final_statuses_for_qr = ['disetujui wr3, siap diajukan ke bendahara', 'diajukan ke bendahara', 'dana cair', 'lpj diajukan', 'lpj diverifikasi', 'proses selesai', 'selesai'];

if (in_array(strtolower(trim($pengajuan['status'])), $final_statuses_for_qr)) {
    $kode_unik = $pengajuan['unique_code'];
    if (empty($kode_unik)) {
        $kode_unik = hash('sha256', $id_pengajuan . $pengajuan['nama_kegiatan'] . time());
        $stmt_update_code = $conn->prepare("UPDATE pengajuan SET unique_code = ? WHERE id_pengajuan = ?");
        $stmt_update_code->bind_param("si", $kode_unik, $id_pengajuan);
        $stmt_update_code->execute();
        $stmt_update_code->close();
    }
    
    $qr_content_url = $base_url_correct . 'index.php?page=verify_page&id=' . $id_pengajuan . '&verify=' . $kode_unik;
    
    $qr_dir = 'uploads/qrcode/';
    if (!is_dir($qr_dir)) mkdir($qr_dir, 0777, true);
    $qr_file_name = 'qrcode_' . $id_pengajuan . '.png';
    $qr_file_path = $qr_dir . $qr_file_name;

    if (file_exists($qr_file_path)) unlink($qr_file_path);
    QRcode::png($qr_content_url, $qr_file_path, QR_ECLEVEL_L, 4);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Persetujuan - <?php echo htmlspecialchars($pengajuan['nama_kegiatan']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
    body { 
        background-color: #e9ecef; font-family: 'Times New Roman', Times, serif; font-size: 12pt; 
    }
    .paper-container { 
        width: 21.5cm; min-height: 33.0cm; padding: 2cm; margin: 2rem auto; 
        background: white; box-shadow: 0 0 15px rgba(0,0,0,0.1); 
        display: flex; flex-direction: column; 
    }
    .kop-surat { display: flex; align-items: center; text-align: center; border-bottom: 4px double #000; padding-bottom: 15px; margin-bottom: 2rem; }
    .kop-surat .logo { width: 80px; height: auto; margin-right: 20px; }
    .kop-surat .text-kop { flex-grow: 1; }
    .kop-surat h4, .kop-surat h5 { margin: 0; font-weight: bold; }
    .kop-surat p { margin: 5px 0 0; font-size: 11pt; }
    .judul-surat { text-align: center; font-weight: bold; text-decoration: underline; font-size: 14pt; margin-bottom: 1.5rem; }
    .konten { flex-grow: 1; }
    .konten p { text-align: justify; line-height: 1.5; margin-bottom: 1rem; }
    .table-detail td { padding: 4px 8px; vertical-align: top; }
    .table-detail td:first-child { width: 30%; font-weight: bold; }
    .table-histori { margin: 1.5rem 0; border-collapse: collapse; width: 100%; }
    .table-histori th, .table-histori td { border: 1px solid #333; padding: 8px; text-align: center; }
    .tanda-tangan-wrapper { margin-top: auto; text-align: center; page-break-inside: avoid; }
    .tanda-tangan { display: inline-block; text-align: center; width: 300px; }
    .tanda-tangan p { margin: 1px 0; }
    .tanda-tangan .jabatan { font-weight: bold; }
    .tanda-tangan .barcode-img { width: 100px; height: 100px; margin: 10px 0; }
    .tanda-tangan .nama { margin-top: 4px; font-weight: bold; text-decoration: underline; }
    
    @page {
        size: 21.5cm 33.0cm; /* Ukuran Kertas F4 */
        margin: 1.5cm;
    }
    @media print {
        body { background-color: white; margin: 0; }
        .btn-print-area { display: none; }
        .paper-container {
            margin: 0; padding: 0; box-shadow: none;
            width: 100%; height: auto; min-height: 0;
        }
    }
</style>
</head>
<body>
    <div class="text-center my-3 btn-print-area">
        <button onclick="window.print()" class="btn btn-primary"><i class="bi bi-printer-fill"></i> Cetak Surat Ini</button>
        <a href="index.php?page=detail&id=<?php echo $id_pengajuan; ?>" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
    </div>
    <div class="paper-container">
        <header>
            <div class="kop-surat">
                <!-- Data Kop Surat Diambil Langsung dari Database -->
                <img src="<?php echo htmlspecialchars($path_logo_kop); ?>" alt="Logo Institut" class="logo">
                <div class="text-kop">
                    <h4><?php echo $kop_baris1; ?></h4>
                    <h5><?php echo $kop_baris2; ?></h5>
                    <p><?php echo $kop_baris3; ?><br><?php echo $kop_baris4; ?></p>
                </div>
            </div>
        </header>

        <main class="konten">
            <h5 class="judul-surat">SURAT KETERANGAN PERSETUJUAN PROPOSAL</h5>
            <p class="text-center mb-4">Nomor: <?php echo htmlspecialchars($pengajuan['nomor_surat']); ?></p>

            <p>Dengan hormat,</p>
            <p>Berdasarkan proposal yang diajukan, dengan ini kami menerangkan bahwa kegiatan di bawah ini:</p>
            
            <table class="table-detail table-borderless my-4" style="margin-left: 2rem;">
                <tr><td>Nama Kegiatan</td><td>: <?php echo htmlspecialchars($pengajuan['nama_kegiatan']); ?></td></tr>
                <tr><td>Ormawa Pelaksana</td><td>: <?php echo htmlspecialchars($pengajuan['nama_ormawa']); ?></td></tr>
                <tr><td>Tanggal Kegiatan</td><td>: <?php echo date('d F Y', strtotime($pengajuan['tanggal_pengajuan'])); ?></td></tr>
                <tr><td>Dana yang Diajukan</td><td>: Rp <?php echo number_format($pengajuan['dana_diajukan'], 0, ',', '.'); ?></td></tr>
            </table>

            <p>Telah melalui seluruh proses verifikasi dan dengan ini dinyatakan <strong>DISETUJUI</strong> untuk dilanjutkan ke tahap pencairan dana. Berikut adalah rekam jejak persetujuan:</p>

            <table class="table-histori table-sm">
                <thead><tr><th>Tahapan</th><th>Pejabat</th><th>Tanggal Persetujuan</th></tr></thead>
                <tbody>
                    <?php
                    foreach ($rekam_jejak as $tahapan => $jejak) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($tahapan) . "</td>";
                        echo "<td>" . htmlspecialchars($jejak['pejabat']) . "</td>";
                        echo "<td>" . $jejak['tanggal'] . "</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
            
            <p>Demikian surat keterangan ini dibuat untuk dapat dipergunakan sebagaimana mestinya.</p>
        </main>
        
        <footer class="tanda-tangan-wrapper">
            <div class="tanda-tangan">
                <p>Garut, <?php echo date('d F Y'); ?></p>
                <p class="jabatan">Kepala BKKH,</p>

                <a href="<?php echo htmlspecialchars($qr_content_url); ?>" target="_blank" title="Klik untuk verifikasi">
                    <?php if (!empty($qr_file_path) && file_exists($qr_file_path)): ?>
                        <img 
                            src="<?php echo $qr_file_path; ?>?t=<?php echo time(); ?>" 
                            alt="QR Code Verifikasi" 
                            class="barcode-img"
                        >
                    <?php else: ?>
                        <p class="text-danger small mt-4">(Gagal membuat QR Code)</p>
                    <?php endif; ?>
                </a>

                <p class="nama">
                    <?php echo htmlspecialchars($nama_bkh_fallback); ?>
                </p>
            </div>
        </footer>
    </div>
</body>
</html>

