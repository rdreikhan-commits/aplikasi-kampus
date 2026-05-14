<?php
/**
 * File: roles/ormawa/view_surat_lain.php
 * Deskripsi: Halaman cetak untuk berbagai jenis surat organisasi.
 */

check_role(['ormawa', 'bem', 'bpm', 'bkh', 'wr3', 'bendahara']);

$id_surat = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Ambil Data Surat
$stmt = $conn->prepare("SELECT s.*, u.nama_lengkap AS nama_ormawa, u.logo_ormawa FROM surat_otomatis s JOIN users u ON s.id_user_ormawa = u.id_user WHERE s.id_surat = ?");
$stmt->bind_param("i", $id_surat);
$stmt->execute();
$surat = $stmt->get_result()->fetch_assoc();

if (!$surat) die("Surat tidak ditemukan.");

$data = json_encode([]); // Default
if (!empty($surat['isi_json'])) {
    $data = json_decode($surat['isi_json'], true);
}

// Ambil Konfigurasi Kop
$konfig = [];
$res_k = $conn->query("SELECT nama_konfigurasi, nilai_konfigurasi FROM konfigurasi WHERE nama_konfigurasi LIKE 'kop_%'");
while($rk = $res_k->fetch_assoc()) $konfig[$rk['nama_konfigurasi']] = $rk['nilai_konfigurasi'];

// TTD Logic
$ttd_name = $surat['ttd_key'];
$ttd_path = '';

$stmt_u = $conn->prepare("SELECT * FROM users WHERE id_user = ?");
$stmt_u->bind_param("i", $surat['id_user_ormawa']);
$stmt_u->execute();
$user_ormawa = $stmt_u->get_result()->fetch_assoc();

if ($surat['ttd_key'] == 'ketua') { $ttd_name = $user_ormawa['nama_ketua']; $ttd_path = 'uploads/profil/' . $user_ormawa['ttd_ketua']; }
elseif ($surat['ttd_key'] == 'sekretaris') { $ttd_name = $user_ormawa['nama_sekretaris']; $ttd_path = 'uploads/profil/' . $user_ormawa['ttd_sekretaris']; }
elseif ($surat['ttd_key'] == 'bendahara') { $ttd_name = $user_ormawa['nama_bendahara']; $ttd_path = 'uploads/profil/' . $user_ormawa['ttd_bendahara']; }
else {
    // Custom
    if (!empty($surat['ttd_file_kustom'])) $ttd_path = 'uploads/proposal_ttd/' . $surat['ttd_file_kustom'];
}

$path_ttd = (file_exists($ttd_path) && !empty($ttd_path)) ? $ttd_path : '';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat <?php echo $surat['jenis_surat']; ?> - <?php echo htmlspecialchars($surat['perihal']); ?></title>
    <style>
        body { font-family: 'Times New Roman', Times, serif; line-height: 1.5; color: #000; background: #f0f0f0; padding: 20px; }
        .paper { background: #fff; width: 210mm; min-height: 297mm; padding: 25mm 20mm; margin: auto; box-shadow: 0 0 10px rgba(0,0,0,0.1); position: relative; }
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
        
        /* New E-Signature Box */
        .e-sig-box {
            display: inline-flex;
            align-items: center;
            border: 1px solid #000;
            padding: 10px;
            margin-top: 20px;
            min-width: 320px;
            text-align: left;
        }
        .e-sig-qr {
            width: 70px;
            height: 70px;
            margin-right: 15px;
        }
        .e-sig-text {
            font-size: 9pt;
            line-height: 1.2;
        }
        .e-sig-footer {
            position: absolute;
            bottom: 20mm;
            left: 20mm;
            right: 20mm;
            font-size: 8pt;
            font-style: italic;
            color: #666;
            border-top: 1px solid #eee;
            padding-top: 5px;
        }
    </style>
</head>
<body>

    <div class="no-print" style="text-align: center; margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer; background: #007bff; color: white; border: none; border-radius: 5px;">Cetak Surat (PDF)</button>
        <a href="index.php?page=arsip_surat_lain" style="margin-left: 10px; color: #666; text-decoration: none;">Kembali ke Arsip</a>
    </div>

    <div class="paper">
        <!-- Kop Surat -->
        <div class="header">
            <?php if(!empty($konfig['kop_logo'])): ?>
                <img src="uploads/sistem/<?php echo $konfig['kop_logo']; ?>" style="height: 80px;">
            <?php else: ?><div style="width: 80px;"></div><?php endif; ?>

            <div style="text-align: center; flex-grow: 1;">
                <h1 style="font-size: 14pt; margin: 0;"><?php echo htmlspecialchars($konfig['kop_baris1'] ?? 'INSTITUT TEKNOLOGI GARUT'); ?></h1>
                <h2 style="font-size: 12pt; margin: 5px 0; text-transform: uppercase;"><?php echo htmlspecialchars($surat['nama_ormawa']); ?></h2>
                <p style="font-size: 9pt; margin: 0; font-style: italic;"><?php echo htmlspecialchars($konfig['kop_baris3'] ?? ''); ?></p>
            </div>

            <?php if(!empty($user_ormawa['logo_ormawa'])): ?>
                <img src="uploads/profil/<?php echo $user_ormawa['logo_ormawa']; ?>" style="height: 80px;">
            <?php else: ?><div style="width: 80px;"></div><?php endif; ?>
        </div>

        <div class="content">
            <table class="meta-table">
                <tr><td style="width: 80px;">Nomor</td><td>: <?php echo htmlspecialchars($surat['nomor_surat'] ?: '-'); ?></td><td style="text-align: right;">Garut, <?php echo date('d F Y', strtotime($surat['tgl_dibuat'])); ?></td></tr>
                <tr><td>Lampiran</td><td>: -</td></tr>
                <tr><td>Perihal</td><td>: <b><?php echo htmlspecialchars($surat['perihal']); ?></b></td></tr>
            </table>

            <div style="margin-top: 30px;">
                <p><?php echo nl2br(htmlspecialchars($surat['tujuan_surat'])); ?></p>
                <p>Di Tempat</p>
            </div>

            <div style="margin-top: 30px; text-align: justify;">
                <p>Assalamu'alaikum Wr. Wb.</p>
                
                <?php if ($surat['jenis_surat'] == 'Undangan'): ?>
                    <p><?php echo nl2br(htmlspecialchars($data['isi_pembuka'])); ?></p>
                    <table style="width: 100%; margin: 20px 0;">
                        <tr><td style="width: 120px;">Acara</td><td>: <?php echo htmlspecialchars($data['acara']); ?></td></tr>
                        <tr><td>Hari / Tanggal</td><td>: <?php echo htmlspecialchars($data['hari_tgl']); ?></td></tr>
                        <tr><td>Waktu</td><td>: <?php echo htmlspecialchars($data['waktu']); ?></td></tr>
                        <tr><td>Tempat</td><td>: <?php echo htmlspecialchars($data['tempat']); ?></td></tr>
                    </table>
                    <p>Demikian surat undangan ini kami sampaikan, atas kehadiran Bapak/Ibu kami ucapkan terima kasih.</p>

                <?php elseif ($surat['jenis_surat'] == 'Tugas'): ?>
                    <p>Yang bertanda tangan di bawah ini, Pengurus <?php echo htmlspecialchars($surat['nama_ormawa']); ?> Institut Teknologi Garut, memberikan tugas kepada:</p>
                    <table style="width: 100%; margin: 20px 0;">
                        <tr><td style="width: 120px;">Nama</td><td>: <?php echo htmlspecialchars($data['nama_petugas']); ?></td></tr>
                        <tr><td>NIM</td><td>: <?php echo htmlspecialchars($data['nim_petugas']); ?></td></tr>
                    </table>
                    <p>Untuk melaksanakan tugas sebagai berikut:</p>
                    <p style="padding-left: 20px;"><i><?php echo nl2br(htmlspecialchars($data['tugas'])); ?></i></p>
                    <p>Kegiatan tersebut akan dilaksanakan pada tanggal <?php echo htmlspecialchars($data['tgl_pelaksanaan']); ?>. Demikian surat tugas ini dibuat untuk dipergunakan sebagaimana mestinya.</p>

                <?php elseif ($surat['jenis_surat'] == 'Permohonan'): ?>
                    <p>Sehubungan dengan akan dilaksanakannya kegiatan kami, maka dengan ini kami bermaksud mengajukan permohonan peminjaman <b><?php echo htmlspecialchars($data['nama_alat_tempat']); ?></b> yang akan digunakan pada:</p>
                    <table style="width: 100%; margin: 20px 0;">
                        <tr><td style="width: 120px;">Waktu</td><td>: <?php echo htmlspecialchars($data['tgl_pakai']); ?></td></tr>
                        <tr><td>Keperluan</td><td>: <?php echo htmlspecialchars($data['alasan']); ?></td></tr>
                    </table>
                    <p>Demikian surat permohonan ini kami sampaikan, atas kerja samanya kami ucapkan terima kasih.</p>

                <?php elseif ($surat['jenis_surat'] == 'Keterangan'): ?>
                    <p>Yang bertanda tangan di bawah ini, menerangkan bahwa:</p>
                    <table style="width: 100%; margin: 20px 0;">
                        <tr><td style="width: 120px;">Nama</td><td>: <?php echo htmlspecialchars($data['nama_mhs']); ?></td></tr>
                        <tr><td>NIM</td><td>: <?php echo htmlspecialchars($data['nim_mhs']); ?></td></tr>
                        <tr><td>Jabatan</td><td>: <?php echo htmlspecialchars($data['jabatan_mhs']); ?></td></tr>
                    </table>
                    <p>Adalah benar merupakan anggota aktif dari <?php echo htmlspecialchars($surat['nama_ormawa']); ?> Institut Teknologi Garut periode saat ini. Surat keterangan ini dibuat untuk keperluan <?php echo htmlspecialchars($data['keperluan']); ?>.</p>
                
                <?php elseif ($surat['jenis_surat'] == 'Peringatan'): ?>
                    <div style="text-align: center; margin-bottom: 20px;">
                        <h3 style="text-decoration: underline; margin-bottom: 5px;"><?php echo strtoupper($data['tingkat_sp']); ?></h3>
                        <p style="margin-top: 0;">Nomor: <?php echo htmlspecialchars($surat['nomor_surat']); ?></p>
                    </div>
                    <p>Berdasarkan hasil evaluasi dan pemantauan kinerja organisasi, dengan ini kami sampaikan bahwa <b><?php echo htmlspecialchars($surat['tujuan_surat']); ?></b> telah melakukan pelanggaran berupa:</p>
                    <div style="padding: 10px 20px; border-left: 4px solid #000; background: #f9f9f9; margin-bottom: 20px;">
                        <p style="margin-bottom: 5px;"><b>Alasan:</b> <?php echo htmlspecialchars($data['alasan']); ?></p>
                        <p style="margin-top: 0;"><b>Keterangan:</b> <?php echo nl2br(htmlspecialchars($data['deskripsi_pelanggaran'])); ?></p>
                    </div>
                    <p>Sehubungan dengan hal tersebut, maka organisasi Saudara diberikan sanksi sebagai berikut:</p>
                    <p style="padding-left: 20px;"><b><?php echo nl2br(htmlspecialchars($data['sanksi'] ?: 'Peringatan keras agar tidak mengulangi kesalahan yang sama.')); ?></b></p>
                    <p>Demikian surat peringatan ini dikeluarkan agar dapat menjadi perhatian dan segera dilakukan perbaikan sebagaimana mestinya. Atas perhatiannya kami ucapkan terima kasih.</p>
                <?php endif; ?>

                <p>Wassalamu'alaikum Wr. Wb.</p>
            </div>

            <div class="signature-box" style="width: auto; float: right; text-align: left;">
                <p style="margin-bottom: 5px;">Garut, <?php echo date('d F Y', strtotime($surat['tgl_dibuat'])); ?></p>
                
                <div class="e-sig-box">
                    <div class="e-sig-qr">
                        <?php 
                            // Generate QR code pointing to verification page
                            $verify_link = "index.php?page=verify_page&id=".$surat['id_surat']."&type=surat";
                            $qr_data = urlencode("https://".$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF'])."/".$verify_link);
                            echo '<a href="'.$verify_link.'" target="_blank" title="Klik untuk Verifikasi Keaslian">';
                            echo '<img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data='.$qr_data.'" style="width: 100%; height: 100%;">';
                            echo '</a>';
                        ?>
                    </div>
                    <div class="e-sig-text">
                        <div>Ditandatangani secara elektronik oleh:</div>
                        <div style="text-transform: uppercase; font-weight: bold; margin-top: 2px;">
                            <?php 
                                if ($surat['jenis_surat'] == 'Peringatan') {
                                    echo htmlspecialchars($surat['ttd_nama_kustom'] ?: 'Pihak Berwenang');
                                } else {
                                    echo htmlspecialchars($user_ormawa['nama_lengkap']);
                                }
                            ?>
                        </div>
                        <div style="margin-top: 8px; font-weight: bold; border-top: 1px solid #ddd; pt-1;">
                            <?php echo htmlspecialchars($ttd_name ?: ($surat['ttd_nama_kustom'] ?? '')); ?>
                        </div>
                    </div>
                </div>
            </div>
            <div style="clear: both;"></div>

            <div class="e-sig-footer">
                Dokumen ini ditandatangani secara elektronik menggunakan Sertifikat Elektronik yang diterbitkan oleh Sistem Informasi Keuangan (SI-Keuangan) Institut Teknologi Garut. Keaslian dokumen dapat dicek melalui scan QR Code di atas.
            </div>
        </div>
    </div>

</body>
</html>
