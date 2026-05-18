<?php
/**
 * File: roles/ormawa/view_peminjaman_cetak.php
 * Deskripsi: Cetak Surat Permohonan Peminjaman Tempat.
 */

check_role(['ormawa', 'bem', 'bpm', 'bkh', 'wr3', 'bendahara']);

$id_peminjaman = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Ambil Data Peminjaman
$sql = "SELECT p.*, r.nama_ruangan, u.nama_lengkap AS nama_ormawa, u.logo_ormawa, u.nama_ketua, u.ttd_ketua, u.alamat, u.telepon
        FROM peminjaman_tempat p 
        JOIN master_ruangan r ON p.id_ruangan = r.id_ruangan 
        JOIN users u ON p.id_user_ormawa = u.id_user 
        WHERE p.id_peminjaman = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_peminjaman);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) die("Data peminjaman tidak ditemukan.");

// Ambil Konfigurasi Kop
$konfig = [];
$res_k = $conn->query("SELECT nama_konfigurasi, nilai_konfigurasi FROM konfigurasi WHERE nama_konfigurasi LIKE 'kop_%'");
while($rk = $res_k->fetch_assoc()) $konfig[$rk['nama_konfigurasi']] = $rk['nilai_konfigurasi'];

$ttd_path = 'uploads/profil/' . $data['ttd_ketua'];
$path_ttd = (file_exists($ttd_path) && !empty($data['ttd_ketua'])) ? $ttd_path : '';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Peminjaman - <?php echo htmlspecialchars($data['nama_kegiatan']); ?></title>
    <style>
        body { font-family: 'Times New Roman', Times, serif; line-height: 1.5; color: #000; background: #f0f0f0; padding: 20px; }
        .paper { background: #fff; width: 210mm; min-height: 297mm; padding: 25mm 20mm; margin: auto; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .header { display: flex; align-items: center; justify-content: space-between; border-bottom: 3px double #000; padding-bottom: 10px; margin-bottom: 20px; }
        .content { margin-top: 30px; }
        .meta-table { width: 100%; border: none; margin-bottom: 20px; }
        .meta-table td { padding: 2px; vertical-align: top; }
        .signature-box { margin-top: 50px; float: right; text-align: center; width: 250px; }
        .sig-space { height: 80px; position: relative; }
        @media print {
            body { background: none; padding: 0; }
            .paper { box-shadow: none; margin: 0; width: 100%; padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="text-align: center; margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer; background: #007bff; color: white; border: none; border-radius: 5px;">Cetak Surat (PDF)</button>
        <a href="index.php?page=peminjaman_tempat" style="margin-left: 10px; color: #666; text-decoration: none;">Kembali</a>
    </div>

    <div class="paper">
        <!-- Kop Surat -->
        <div class="header">
            <?php if(!empty($konfig['kop_logo'])): ?>
                <img src="uploads/sistem/<?php echo $konfig['kop_logo']; ?>" style="height: 80px;">
            <?php else: ?><div style="width: 80px;"></div><?php endif; ?>

            <div style="text-align: center; flex-grow: 1;">
                <h1 style="font-size: 14pt; margin: 0; text-transform: uppercase; font-weight: bold;"><?php echo htmlspecialchars($data['nama_ormawa']); ?></h1>
                <h2 style="font-size: 12pt; margin: 5px 0; text-transform: uppercase; font-weight: bold;">INSTITUT TEKNOLOGI GARUT</h2>
                <p style="font-size: 9.5pt; margin: 5px 0 0; font-style: italic; font-weight: normal; line-height: 1.3;">
                    <?php 
                    $alamat = !empty($data['alamat']) ? $data['alamat'] : 'Jl. Mayor Syamsu No. 1, Jayaraga, Kec. Tarogong Kidul, Kabupaten Garut, Jawa Barat';
                    $telepon = !empty($data['telepon']) ? $data['telepon'] : '';
                    echo htmlspecialchars($alamat);
                    if (!empty($telepon)) {
                        echo " | Telp: " . htmlspecialchars($telepon);
                    }
                    ?>
                </p>
            </div>

            <?php if(!empty($data['logo_ormawa'])): ?>
                <img src="uploads/profil/<?php echo $data['logo_ormawa']; ?>" style="height: 80px;">
            <?php else: ?><div style="width: 80px;"></div><?php endif; ?>
        </div>

        <div class="content">
            <table class="meta-table">
                <tr><td style="width: 80px;">Nomor</td><td>: -</td><td style="text-align: right;">Garut, <?php echo date('d F Y', strtotime($data['tgl_pengajuan'])); ?></td></tr>
                <tr><td>Lampiran</td><td>: -</td></tr>
                <tr><td>Perihal</td><td>: <b>Permohonan Izin Peminjaman Ruangan</b></td></tr>
            </table>

            <div style="margin-top: 30px;">
                <p>Yth. Bagian BKKH / Sarana Prasarana<br>Institut Teknologi Garut<br>Di Tempat</p>
            </div>

            <div style="margin-top: 30px; text-align: justify;">
                <p>Assalamu'alaikum Wr. Wb.</p>
                <p>Sehubungan dengan akan dilaksanakannya kegiatan <b><?php echo htmlspecialchars($data['nama_kegiatan']); ?></b> oleh <?php echo htmlspecialchars($data['nama_ormawa']); ?>, maka dengan ini kami bermaksud mengajukan permohonan izin peminjaman sarana/fasilitas sebagai berikut:</p>
                
                <table style="width: 100%; margin: 20px 0;">
                    <tr><td style="width: 150px;">Nama Ruangan</td><td>: <b><?php echo htmlspecialchars($data['nama_ruangan']); ?></b></td></tr>
                    <tr><td>Tanggal</td><td>: <?php echo date('d/m/Y', strtotime($data['tgl_mulai'])); ?> s/d <?php echo date('d/m/Y', strtotime($data['tgl_selesai'])); ?></td></tr>
                    <tr><td>Waktu</td><td>: <?php echo date('H:i', strtotime($data['jam_mulai'])); ?> - <?php echo date('H:i', strtotime($data['jam_selesai'])); ?> WIB</td></tr>
                    <tr><td>Keterangan</td><td>: <?php echo htmlspecialchars($data['deskripsi_kegiatan']); ?></td></tr>
                </table>

                <p>Demikian surat permohonan ini kami sampaikan. Atas perhatian dan izin yang Bapak/Ibu berikan, kami ucapkan terima kasih.</p>
                <p>Wassalamu'alaikum Wr. Wb.</p>
            </div>

            <div class="signature-box">
                <p style="margin-bottom: 0;">Hormat kami,</p>
                <p style="font-weight: bold; margin-top: 0;"><?php echo htmlspecialchars($data['nama_ormawa']); ?></p>
                <div class="sig-space">
                    <?php if ($path_ttd): ?>
                        <img src="<?php echo $path_ttd; ?>" style="max-height: 80px; position: absolute; left: 50%; transform: translateX(-50%);">
                    <?php endif; ?>
                </div>
                <p style="font-weight: bold; text-decoration: underline; margin-bottom: 0;">( <?php echo htmlspecialchars($data['nama_ketua']); ?> )</p>
                <p style="font-size: 10pt; margin-top: 0;">Ketua Umum</p>
            </div>
            <div style="clear: both;"></div>
        </div>
    </div>

</body>
</html>
