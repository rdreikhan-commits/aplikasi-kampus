<?php
/**
 * File: verify_page.php
 * Deskripsi: Halaman publik untuk memverifikasi keaslian surat persetujuan melalui QR Code.
 */

// Sertakan file konfigurasi dan fungsi dasar
require_once 'config.php';
require_once 'functions.php';

// Ambil parameter dari URL
$id_pengajuan = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$unique_code_from_url = isset($_GET['verify']) ? trim($_GET['verify']) : '';
$verification_data = null;
$error_message = '';

if ($id_pengajuan > 0 && !empty($unique_code_from_url)) {
    // Langkah 1: Validasi kode unik dan ambil data dasar
    $stmt = $conn->prepare(
        "SELECT p.nama_kegiatan, p.status AS status_saat_ini, u.nama_lengkap AS nama_ormawa
         FROM pengajuan p
         JOIN users u ON p.id_user_ormawa = u.id_user
         WHERE p.id_pengajuan = ? AND p.unique_code = ?"
    );
    $stmt->bind_param("is", $id_pengajuan, $unique_code_from_url);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $verification_data = $result->fetch_assoc();

        // Langkah 2: Ambil seluruh riwayat untuk dianalisis
        $stmt_hist = $conn->prepare(
            "SELECT h.status, h.tanggal_update, u.nama_lengkap AS pejabat, u.role
             FROM histori_status h JOIN users u ON h.id_user = u.id_user
             WHERE h.id_pengajuan = ? ORDER BY h.tanggal_update ASC"
        );
        $stmt_hist->bind_param("i", $id_pengajuan);
        $stmt_hist->execute();
        $all_history = $stmt_hist->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_hist->close();

        // Langkah 3: Temukan persetujuan terakhir dari BKKH dan WR3 dari riwayat
        $bkh_approval = null;
        $wr3_approval = null;
        foreach (array_reverse($all_history) as $item) { // Loop dari data terbaru
            // Cari persetujuan terakhir BKKH
            if (is_null($bkh_approval) && $item['role'] === 'bkh' && !str_contains(strtolower($item['status']), 'ditolak')) {
                $bkh_approval = $item;
            }
            // Cari persetujuan terakhir WR3
            if (is_null($wr3_approval) && $item['role'] === 'wr3' && !str_contains(strtolower($item['status']), 'ditolak')) {
                $wr3_approval = $item;
            }
            // Optimasi: berhenti jika keduanya sudah ditemukan
            if ($bkh_approval && $wr3_approval) {
                break;
            }
        }
        
        // Tambahkan data persetujuan ke hasil verifikasi
        $verification_data['pejabat_bkh'] = $bkh_approval['pejabat'] ?? null;
        $verification_data['tanggal_bkh'] = $bkh_approval['tanggal_update'] ?? null;
        $verification_data['pejabat_wr3'] = $wr3_approval['pejabat'] ?? null;
        $verification_data['tanggal_wr3'] = $wr3_approval['tanggal_update'] ?? null;

    } else {
        $error_message = "Kode verifikasi tidak valid atau data tidak ditemukan di dalam sistem.";
    }
    $stmt->close();
} else {
    $error_message = "Parameter verifikasi tidak lengkap.";
}

// Menentukan kelas tema berdasarkan hasil verifikasi
$theme_class = $verification_data ? 'theme-valid' : 'theme-invalid';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Dokumen - SI Keuangan ITG</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #0c0a09;
            background-image:
                linear-gradient(rgba(17, 24, 39, 0.97), rgba(17, 24, 39, 0.97)),
                url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100' viewBox='0 0 100 100'%3E%3Cg fill='%231f2937' fill-opacity='0.6'%3E%3Crect x='0' y='0' width='10' height='10'/%3E%3C/g%3E%3C/svg%3E");
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 1.5rem;
            color: #d1d5db;
        }

        .verify-card {
            width: 100%;
            max-width: 700px;
            background: rgba(31, 41, 55, 0.6);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            overflow: hidden;
            animation: fadeIn 0.8s ease-out;
            transition: box-shadow 0.4s ease;
        }

        .theme-valid.verify-card {
            box-shadow: 0 0 50px rgba(34, 197, 94, 0.25), 0 25px 50px -12px rgba(0, 0, 0, 0.75);
        }
        .theme-invalid.verify-card {
            box-shadow: 0 0 50px rgba(239, 68, 68, 0.25), 0 25px 50px -12px rgba(0, 0, 0, 0.75);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .verify-header {
            padding: 3rem 2rem;
            text-align: center;
            position: relative;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .verify-icon {
            font-size: 5rem;
            margin-bottom: 1rem;
            display: inline-block;
            transform: scale(0);
            animation: popIn 0.5s cubic-bezier(0.68, -0.55, 0.27, 1.55) 0.3s forwards;
        }

        @keyframes popIn { to { transform: scale(1); } }

        .theme-valid .verify-icon { color: #22c55e; }
        .theme-invalid .verify-icon { color: #ef4444; }

        .logo-itg {
            position: absolute;
            top: 25px;
            left: 30px;
            width: 60px;
            opacity: 0.3;
            filter: grayscale(100%) brightness(150%);
            transition: all 0.4s ease;
        }
        .verify-card:hover .logo-itg {
            opacity: 0.6;
        }

        .verify-body { padding: 2rem 3rem; }
        
        .info-list { list-style: none; padding: 0; margin: 0; }
        .info-list li {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: flex-start;
            padding: 1.25rem 0;
            border-bottom: 1px solid #374151;
            gap: 1rem;
        }
        .info-list li:last-child { border-bottom: none; }
        .info-list .label { color: #9ca3af; flex-shrink: 0; }
        .info-list .value { color: #f9fafb; font-weight: 600; text-align: right; word-break: break-word; }
        .info-list .value .badge { font-size: 0.9rem; padding: 0.4em 0.8em; }
        .info-list .value small { display: block; font-weight: 400; color: #9ca3af; font-size: 0.85rem; }
        
        .card-footer {
            background-color: rgba(17, 24, 39, 0.5);
            border-top: 1px solid #374151;
        }

        @media (max-width: 576px) {
            body { padding: 1rem; }
            .verify-body { padding: 1.5rem; }
            .info-list li { flex-direction: column; align-items: flex-start; gap: 0.25rem; }
            .info-list .value { text-align: left; }
        }
    </style>
</head>
<body>
    <div class="verify-card <?php echo $theme_class; ?>">
        <?php if ($verification_data): ?>
            <!-- Tampilan Jika Verifikasi Berhasil -->
            <div class="verify-header">
             
                <i class="bi bi-patch-check-fill verify-icon"></i>
                <h2 class="mt-2 text-white fw-bold">Dokumen Terverifikasi</h2>
                <p class="text-secondary mb-0">Surat ini dinyatakan sah dan tercatat dalam sistem.</p>
            </div>
            <div class="verify-body">
                <ul class="info-list">
                    <li>
                        <span class="label">Nama Kegiatan</span>
                        <span class="value"><?php echo htmlspecialchars($verification_data['nama_kegiatan']); ?></span>
                    </li>
                    <li>
                        <span class="label">Ormawa Pelaksana</span>
                        <span class="value"><?php echo htmlspecialchars($verification_data['nama_ormawa']); ?></span>
                    </li>
                     <li>
                        <span class="label">Status Saat Ini</span>
                        <span class="value"><span class="badge bg-primary"><?php echo htmlspecialchars($verification_data['status_saat_ini']); ?></span></span>
                    </li>
                    <li>
                        <span class="label">Disetujui oleh</span>
                        <span class="value">
                            <?php echo $verification_data['pejabat_bkh'] ? htmlspecialchars($verification_data['pejabat_bkh']) : '<em class="text-warning">BKKH</em>'; ?>
                            <?php if ($verification_data['tanggal_bkh']): ?>
                                <small><?php echo date('d F Y, H:i', strtotime($verification_data['tanggal_bkh'])); ?> WIB</small>
                            <?php endif; ?>
                        </span>
                    </li>
                    <li>
                        <span class="label">Disetujui oleh</span>
                        <span class="value">
                             <?php echo $verification_data['pejabat_wr3'] ? htmlspecialchars($verification_data['pejabat_wr3']) : '<em class="text-warning">WAKIL REKTOR3</em>'; ?>
                            <?php if ($verification_data['tanggal_wr3']): ?>
                                <small><?php echo date('d F Y, H:i', strtotime($verification_data['tanggal_wr3'])); ?> WIB</small>
                            <?php endif; ?>
                        </span>
                    </li>
                </ul>
            </div>
        <?php else: ?>
            <!-- Tampilan Jika Verifikasi Gagal -->
            <div class="verify-header">
                <img src="https://itg.ac.id/wp-content/uploads/2022/01/logo-itg-new.png" alt="Logo ITG" class="logo-itg">
                <i class="bi bi-shield-fill-x verify-icon"></i>
                <h2 class="mt-2 text-white fw-bold">Dokumen Tidak Valid</h2>
                <p class="text-secondary mb-0"><?php echo $error_message; ?></p>
            </div>
        <?php endif; ?>
         <div class="card-footer text-center text-secondary small">
            &copy; <?php echo date('Y'); ?> Institut Teknologi Garut
        </div>
    </div>
</body>
</html>

