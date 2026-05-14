<?php
/**
 * File: roles/ormawa/view_lpj_otomatis.php
 * Deskripsi: Halaman cetak laporan pertanggungjawaban (LPJ).
 */

check_role(['ormawa', 'bem', 'bpm', 'bkh', 'wr3', 'bendahara']);

$id_lpj = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Ambil Data LPJ
$stmt = $conn->prepare("SELECT l.*, u.nama_lengkap AS nama_ormawa, u.logo_ormawa FROM lpj_otomatis l JOIN users u ON l.id_user_ormawa = u.id_user WHERE l.id_lpj = ?");
$stmt->bind_param("i", $id_lpj);
$stmt->execute();
$lpj = $stmt->get_result()->fetch_assoc();

if (!$lpj) die("LPJ tidak ditemukan.");

// Ambil Anggaran
$anggaran = $conn->query("SELECT * FROM lpj_anggaran WHERE id_lpj = $id_lpj");

// Ambil Konfigurasi Kop
$konfig = [];
$res_k = $conn->query("SELECT nama_konfigurasi, nilai_konfigurasi FROM konfigurasi WHERE nama_konfigurasi LIKE 'kop_%'");
while($rk = $res_k->fetch_assoc()) $konfig[$rk['nama_konfigurasi']] = $rk['nilai_konfigurasi'];

// Helper TTD
$getTTD = function($key, $user, $customFile = null) {
    if ($key === 'none' || empty($key)) return null;
    $name = $key; $file = ''; $dir = 'uploads/profil/';
    if($key == 'ketua') { $name = $user['nama_ketua']; $file = $user['ttd_ketua']; }
    elseif($key == 'sekretaris') { $name = $user['nama_sekretaris']; $file = $user['ttd_sekretaris']; }
    elseif($key == 'bendahara') { $name = $user['nama_bendahara']; $file = $user['ttd_bendahara']; }
    if (!empty($customFile)) { $file = $customFile; $dir = 'uploads/proposal_ttd/'; }
    $path = (!empty($file) && file_exists($dir . $file)) ? $dir . $file : '';
    return ['name' => $name, 'path' => $path];
};

$stmt_u = $conn->prepare("SELECT * FROM users WHERE id_user = ?");
$stmt_u->bind_param("i", $lpj['id_user_ormawa']);
$stmt_u->execute();
$user_ormawa = $stmt_u->get_result()->fetch_assoc();

$ttd1 = $getTTD($lpj['ttd_1_key'], $user_ormawa, $lpj['ttd_1_file']);
$ttd2 = $getTTD($lpj['ttd_2_key'], $user_ormawa, $lpj['ttd_2_file']);
$ttd3 = $getTTD($lpj['ttd_3_key'], $user_ormawa, $lpj['ttd_3_file']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>LPJ - <?php echo htmlspecialchars($lpj['nama_kegiatan']); ?></title>
    <style>
        body { font-family: 'Arial', sans-serif; line-height: 1.6; color: #333; background: #f4f4f4; padding: 20px; }
        .paper { background: #fff; width: 210mm; min-height: 297mm; padding: 25mm 20mm; margin: auto; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .header { display: flex; align-items: center; justify-content: space-between; border-bottom: 3px double #000; padding-bottom: 10px; margin-bottom: 30px; }
        .title { text-align: center; font-size: 16pt; font-weight: bold; margin-bottom: 30px; text-transform: uppercase; text-decoration: underline; }
        .section-title { font-weight: bold; font-size: 12pt; margin-top: 20px; margin-bottom: 5px; }
        .content { text-align: justify; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 10pt; }
        table th, table td { border: 1px solid #000; padding: 8px; }
        .signature-row { display: flex; justify-content: space-around; margin-top: 50px; page-break-inside: avoid; }
        .sig { text-align: center; width: 200px; }
        .sig-space { height: 80px; position: relative; }
        .no-print { text-align: center; margin-bottom: 20px; }
        @media print {
            body { background: none; padding: 0; }
            .paper { box-shadow: none; margin: 0; width: 100%; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer; background: #28a745; color: white; border: none; border-radius: 5px;">Cetak LPJ (PDF)</button>
        <a href="index.php?page=arsip_lpj_otomatis" style="margin-left: 10px; color: #666; text-decoration: none;">Kembali ke Arsip</a>
    </div>

    <div class="paper">
        <div class="header">
            <?php if(!empty($konfig['kop_logo'])): ?><img src="uploads/sistem/<?php echo $konfig['kop_logo']; ?>" style="height: 80px;"><?php endif; ?>
            <div style="text-align: center; flex-grow: 1;">
                <h1 style="font-size: 14pt; margin: 0;"><?php echo htmlspecialchars($konfig['kop_baris1'] ?? 'INSTITUT TEKNOLOGI GARUT'); ?></h1>
                <h2 style="font-size: 12pt; margin: 5px 0; text-transform: uppercase;"><?php echo htmlspecialchars($lpj['nama_ormawa']); ?></h2>
                <p style="font-size: 9pt; margin: 0; font-style: italic;"><?php echo htmlspecialchars($konfig['kop_baris3'] ?? ''); ?></p>
            </div>
            <?php if(!empty($user_ormawa['logo_ormawa'])): ?><img src="uploads/profil/<?php echo $user_ormawa['logo_ormawa']; ?>" style="height: 80px;"><?php endif; ?>
        </div>

        <div class="title">LAPORAN PERTANGGUNGJAWABAN<br><?php echo htmlspecialchars($lpj['nama_kegiatan']); ?></div>

        <div class="section-title">I. PENDAHULUAN</div>
        <div class="content"><?php echo nl2br(htmlspecialchars($lpj['pendahuluan'])); ?></div>

        <div class="section-title">II. PELAKSANAAN KEGIATAN</div>
        <div class="content"><?php echo nl2br(htmlspecialchars($lpj['pelaksanaan_kegiatan'])); ?></div>

        <div class="section-title">III. HASIL KEGIATAN</div>
        <div class="content"><?php echo nl2br(htmlspecialchars($lpj['hasil_kegiatan'])); ?></div>

        <div class="section-title">IV. HAMBATAN DAN KENDALA</div>
        <div class="content"><?php echo nl2br(htmlspecialchars($lpj['hambatan_kendala'])); ?></div>

        <div class="section-title">V. LAPORAN KEUANGAN (REALISASI)</div>
        <table>
            <thead>
                <tr style="background: #eee;">
                    <th>Uraian</th>
                    <th>Estimasi (Rp)</th>
                    <th>Realisasi (Rp)</th>
                    <th>Selisih (Rp)</th>
                    <th>Ket.</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $total_est = 0; $total_real = 0;
                while($a = $anggaran->fetch_assoc()): 
                    $selisih = $a['estimasi_dana'] - $a['realisasi_dana'];
                    $total_est += $a['estimasi_dana']; $total_real += $a['realisasi_dana'];
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($a['uraian']); ?></td>
                    <td align="right"><?php echo number_format($a['estimasi_dana'], 0, ',', '.'); ?></td>
                    <td align="right"><?php echo number_format($a['realisasi_dana'], 0, ',', '.'); ?></td>
                    <td align="right" style="color: <?php echo $selisih < 0 ? 'red' : 'green'; ?>">
                        <?php echo number_format(abs($selisih), 0, ',', '.'); ?>
                    </td>
                    <td><?php echo htmlspecialchars($a['keterangan']); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
            <tfoot>
                <tr style="font-weight: bold; background: #eee;">
                    <td>TOTAL</td>
                    <td align="right"><?php echo number_format($total_est, 0, ',', '.'); ?></td>
                    <td align="right"><?php echo number_format($total_real, 0, ',', '.'); ?></td>
                    <td align="right"><?php echo number_format(abs($total_est - $total_real), 0, ',', '.'); ?></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

        <div class="section-title">VI. SARAN DAN REKOMENDASI</div>
        <div class="content"><?php echo nl2br(htmlspecialchars($lpj['saran_rekomendasi'])); ?></div>

        <div class="section-title">VII. PENUTUP</div>
        <div class="content"><?php echo nl2br(htmlspecialchars($lpj['penutup'])); ?></div>

        <!-- LAMPIRAN (Page Break) -->
        <div style="page-break-before: always;"></div>
        <div class="title" style="margin-top: 50px;">LAMPIRAN I: BUKTI PEMBAYARAN / KWITANSI</div>
        <div style="display: flex; flex-wrap: wrap; gap: 10px; justify-content: center;">
            <?php 
            $res_k = $conn->query("SELECT * FROM lpj_lampiran WHERE id_lpj = $id_lpj AND tipe_lampiran = 'Kwitansi'");
            while($k = $res_k->fetch_assoc()):
                $ext = pathinfo($k['nama_file'], PATHINFO_EXTENSION);
            ?>
                <div style="border: 1px solid #ccc; padding: 5px; width: 30%;">
                    <?php if(in_array($ext, ['jpg','jpeg','png'])): ?>
                        <img src="uploads/lpj_lampiran/<?php echo $k['nama_file']; ?>" style="width: 100%; height: auto;">
                    <?php else: ?>
                        <div style="height: 150px; display: flex; align-items: center; justify-content: center; background: #eee;">PDF FILE</div>
                    <?php endif; ?>
                </div>
            <?php endwhile; if($res_k->num_rows == 0) echo "<p align='center'>Tidak ada lampiran kwitansi.</p>"; ?>
        </div>

        <div style="page-break-before: always;"></div>
        <div class="title" style="margin-top: 50px;">LAMPIRAN II: DOKUMENTASI KEGIATAN</div>
        <div style="display: flex; flex-wrap: wrap; gap: 15px; justify-content: center;">
            <?php 
            $res_d = $conn->query("SELECT * FROM lpj_lampiran WHERE id_lpj = $id_lpj AND tipe_lampiran = 'Dokumentasi'");
            while($d = $res_d->fetch_assoc()):
            ?>
                <div style="border: 1px solid #ccc; padding: 5px; width: 45%;">
                    <img src="uploads/lpj_lampiran/<?php echo $d['nama_file']; ?>" style="width: 100%; height: auto;">
                </div>
            <?php endwhile; if($res_d->num_rows == 0) echo "<p align='center'>Tidak ada foto dokumentasi.</p>"; ?>
        </div>

        <div style="margin-top: 40px; text-align: right;">Garut, <?php echo date('d F Y', strtotime($lpj['tgl_dibuat'])); ?></div>

        <div class="signature-row">
            <?php if($ttd1): ?>
            <div class="sig">
                Ketua Pelaksana
                <div class="sig-space">
                    <?php if($ttd1['path']): ?><img src="<?php echo $ttd1['path']; ?>" style="max-height: 80px; position: absolute; left: 50%; transform: translateX(-50%);"><?php endif; ?>
                </div>
                ( <?php echo htmlspecialchars($ttd1['name']); ?> )
            </div>
            <?php endif; ?>

            <?php if($ttd2): ?>
            <div class="sig">
                Sekretaris
                <div class="sig-space">
                    <?php if($ttd2['path']): ?><img src="<?php echo $ttd2['path']; ?>" style="max-height: 80px; position: absolute; left: 50%; transform: translateX(-50%);"><?php endif; ?>
                </div>
                ( <?php echo htmlspecialchars($ttd2['name']); ?> )
            </div>
            <?php endif; ?>
        </div>

        <?php if($ttd3): ?>
        <div class="signature-row" style="justify-content: center; margin-top: 20px;">
            <div class="sig">
                Bendahara
                <div class="sig-space">
                    <?php if($ttd3['path']): ?><img src="<?php echo $ttd3['path']; ?>" style="max-height: 80px; position: absolute; left: 50%; transform: translateX(-50%);"><?php endif; ?>
                </div>
                ( <?php echo htmlspecialchars($ttd3['name']); ?> )
            </div>
        </div>
        <?php endif; ?>
    </div>

</body>
</html>
