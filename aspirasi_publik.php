<?php
/**
 * File: aspirasi_publik.php
 * Deskripsi: Halaman publik untuk mengirim aspirasi/keluhan tanpa login.
 */
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suara Mahasiswa - Aspirasi & Keluhan Kampus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #f8fafc;
            color: #1e293b;
        }
        .hero-section {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            padding: 80px 0;
            color: white;
            border-bottom-left-radius: 50px;
            border-bottom-right-radius: 50px;
        }
        .form-card {
            margin-top: -60px;
            border-radius: 24px;
            border: none;
            box-shadow: 0 20px 40px rgba(0,0,0,0.05);
        }
        .btn-primary {
            background: #2563eb;
            border: none;
            padding: 12px 30px;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-primary:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.2);
        }
        .form-control, .form-select {
            border-radius: 12px;
            padding: 12px 16px;
            border: 1px solid #e2e8f0;
        }
        .form-control:focus {
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
            border-color: #2563eb;
        }
    </style>
</head>
<body>

    <div class="hero-section text-center">
        <div class="container">
            <h1 class="display-4 fw-bold mb-3">Suara Mahasiswa</h1>
            <p class="lead opacity-75">Sampaikan aspirasi, keluhan, atau saran Anda demi kemajuan kampus kita.</p>
        </div>
    </div>

    <div class="container mb-5 pb-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card form-card p-4 p-md-5">
                    <?php if (isset($_GET['status']) && $_GET['status'] == 'aspirasi_sukses'): ?>
                        <div class="text-center py-4">
                            <div class="bg-success-subtle text-success rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width: 80px; height: 80px;">
                                <i class="bi bi-check-lg display-4"></i>
                            </div>
                            <h2 class="fw-bold">Terima Kasih!</h2>
                            <p class="text-muted">Aspirasi Anda telah kami terima dan akan segera ditinjau oleh BPM.</p>
                            <a href="index.php?page=aspirasi" class="btn btn-outline-primary rounded-pill px-4">Kirim Aspirasi Lain</a>
                        </div>
                    <?php else: ?>
                        <form action="index.php?page=aspirasi" method="POST">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Nama Lengkap (Opsional)</label>
                                    <input type="text" name="nama" class="form-control" placeholder="Tuliskan nama Anda atau kosongkan untuk anonim">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Email (Opsional)</label>
                                    <input type="email" name="email" class="form-control" placeholder="Untuk menerima tanggapan">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Kategori Aspirasi</label>
                                    <select name="kategori" class="form-select" required>
                                        <option value="" disabled selected>Pilih Kategori</option>
                                        <option value="Fasilitas">Fasilitas (Gedung, AC, Toilet, dll)</option>
                                        <option value="Layanan Kampus">Layanan Kampus (Akademik, Keuangan, dll)</option>
                                        <option value="Ormawa">Ormawa (BEM, BPM, UKM, dll)</option>
                                        <option value="Lainnya">Lainnya</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Subjek</label>
                                    <input type="text" name="subjek" class="form-control" required placeholder="Judul singkat keluhan Anda">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold">Isi Aspirasi / Keluhan</label>
                                    <textarea name="isi" class="form-control" rows="6" required placeholder="Ceritakan keluhan atau saran Anda secara detail..."></textarea>
                                </div>
                                <div class="col-12 text-center mt-5">
                                    <button type="submit" name="kirim_aspirasi" class="btn btn-primary px-5 shadow">
                                        <i class="bi bi-send-fill me-2"></i> Kirim Aspirasi Sekarang
                                    </button>
                                    <p class="mt-3 small text-muted">Aspirasi Anda akan dikirimkan secara langsung kepada BPM untuk ditindaklanjuti.</p>
                                </div>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <footer class="text-center py-4 text-muted">
        <p>&copy; <?php echo date('Y'); ?> SI-Keuangan Kampus. All Rights Reserved.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
