<?php
/**
 * File: roles/ormawa/view_proposal_otomatis.php
 * Deskripsi: Halaman untuk melihat hasil proposal dan mencetaknya (Auto-PDF).
 */

check_role(['ormawa', 'bem', 'bpm', 'bkh', 'wr3', 'bendahara']);

$id_proposal = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Ambil Header
$stmt = $conn->prepare("SELECT p.*, u.nama_lengkap AS nama_ormawa FROM proposal_otomatis p JOIN users u ON p.id_user_ormawa = u.id_user WHERE p.id_proposal = ?");
$stmt->bind_param("i", $id_proposal);
$stmt->execute();
$proposal = $stmt->get_result()->fetch_assoc();

if (!$proposal) {
    die("Proposal tidak ditemukan.");
}

// Ambil RAB
$rab = [];
$res_rab = $conn->query("SELECT * FROM proposal_rab WHERE id_proposal = $id_proposal");
while($r = $res_rab->fetch_assoc()) $rab[] = $r;

// Ambil Panitia
$panitia = [];
$res_pan = $conn->query("SELECT * FROM proposal_panitia WHERE id_proposal = $id_proposal");
while($p = $res_pan->fetch_assoc()) $panitia[] = $p;

// Ambil Konfigurasi Kop Surat
$konfig = [];
$res_k = $conn->query("SELECT nama_konfigurasi, nilai_konfigurasi FROM konfigurasi WHERE nama_konfigurasi LIKE 'kop_%'");
while($rk = $res_k->fetch_assoc()) $konfig[$rk['nama_konfigurasi']] = $rk['nilai_konfigurasi'];

// Ambil Detail User Pembuat (untuk Organigram & TTD)
$stmt_u = $conn->prepare("SELECT * FROM users WHERE id_user = ?");
$stmt_u->bind_param("i", $proposal['id_user_ormawa']);
$stmt_u->execute();
$user_ormawa = $stmt_u->get_result()->fetch_assoc();

// Helper untuk TTD
$getTTD = function($key, $user, $customFile = null, $customJab = null, $customNim = null) {
    if ($key === 'none' || empty($key)) return null;
    
    $name = $key; 
    $jab = ''; 
    $nim = '';
    $file = '';
    $dir = 'uploads/profil/';

    if($key == 'ketua') { 
        $name = $user['nama_ketua']; 
        $file = $user['ttd_ketua']; 
        $jab = 'Ketua Pelaksana';
    }
    elseif($key == 'sekretaris') { 
        $name = $user['nama_sekretaris']; 
        $file = $user['ttd_sekretaris']; 
        $jab = 'Sekretaris';
    }
    elseif($key == 'bendahara') { 
        $name = $user['nama_bendahara']; 
        $file = $user['ttd_bendahara']; 
        $jab = 'Bendahara';
    } else {
        // Custom name
        $jab = $customJab;
        $nim = $customNim;
    }
    
    // Prioritas file kustom untuk proposal ini
    if (!empty($customFile)) {
        $file = $customFile;
        $dir = 'uploads/proposal_ttd/';
    }
    
    $path = (!empty($file) && file_exists($dir . $file)) ? $dir . $file : '';
    return ['name' => $name, 'jab' => $jab, 'nim' => $nim, 'path' => $path];
};

$ttd1 = $getTTD($proposal['ttd_1_key'], $user_ormawa, $proposal['ttd_1_file'], $proposal['ttd_1_custom_jabatan'], $proposal['ttd_1_custom_nim']);
$ttd2 = $getTTD($proposal['ttd_2_key'], $user_ormawa, $proposal['ttd_2_file'], $proposal['ttd_2_custom_jabatan'], $proposal['ttd_2_custom_nim']);
$ttd3 = $getTTD($proposal['ttd_3_key'], $user_ormawa, $proposal['ttd_3_file'], $proposal['ttd_3_custom_jabatan'], $proposal['ttd_3_custom_nim']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Proposal - <?php echo htmlspecialchars($proposal['nama_kegiatan']); ?></title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Times New Roman', Times, serif; line-height: 1.6; color: #000; background: #f0f0f0; padding: 20px; }
        .paper { background: #fff; width: 210mm; min-height: 297mm; padding: 20mm; margin: auto; box-shadow: 0 0 10px rgba(0,0,0,0.1); position: relative; overflow: hidden; }
        .header { text-align: center; border-bottom: 3px double #000; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 18pt; text-transform: uppercase; }
        .header p { margin: 5px 0; font-size: 10pt; }
        .title { text-align: center; font-weight: bold; text-decoration: underline; text-transform: uppercase; margin: 30px 0; }
        .section-title { font-weight: bold; margin-top: 20px; display: block; text-decoration: underline; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table, th, td { border: 1px solid black; }
        th, td { padding: 8px; text-align: left; }
        .no-border, .no-border tr, .no-border td { border: none !important; }
        .sig { text-align: center; width: 200px; }
        .sig-space { height: 80px; }
        
        @media print {
            body { background: none; padding: 0; margin: 0; }
            .paper { box-shadow: none; margin: 0; width: 210mm !important; padding: 20mm !important; }
            .no-print { display: none; }
            @page { 
                margin: 0; 
                size: A4;
            }
        }


        /* New E-Signature Box for Proposal */
        .e-sig-box {
            display: table;
            width: 250px;
            border: 1px solid #000;
            padding: 5px;
            margin-top: 5px;
            background: #fff;
        }
        .e-sig-qr-cell {
            display: table-cell;
            width: 70px;
            height: 70px;
            vertical-align: middle;
            text-align: center;
            padding: 5px;
        }
        .e-sig-qr-cell img {
            width: 60px;
            height: 60px;
            display: block;
            margin: 0 auto;
        }
        .e-sig-text-cell {
            display: table-cell;
            vertical-align: middle;
            font-size: 7.5pt;
            line-height: 1.1;
            text-align: left;
        }
        
        /* Prevent Page Break for Signatures */
        .sig-container {
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .signature-box {
            page-break-inside: avoid;
            break-inside: avoid;
        }
    </style>
</head>
<body>

    <div class="no-print" style="text-align: center; margin-bottom: 20px; background: #fff; padding: 15px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer; background: #007bff; color: white; border: none; border-radius: 5px; font-weight: bold;">
            <i class="bi bi-printer"></i> Cetak / Simpan PDF
        </button>
        <a href="index.php?page=dashboard" style="text-decoration: none; margin-left: 10px; color: #666; font-size: 14px;">Kembali ke Dashboard</a>
    </div>

    <div class="paper">
        <div class="header" style="display: flex; align-items: center; justify-content: space-between; border-bottom: 3px double #000; padding-bottom: 10px;">
            <!-- Logo ITG (Kiri) -->
            <?php if(!empty($konfig['kop_logo'])): ?>
                <img src="uploads/sistem/<?php echo $konfig['kop_logo']; ?>" style="height: 80px;">
            <?php else: ?>
                <div style="width: 80px;"></div>
            <?php endif; ?>

            <!-- Teks Tengah -->
            <div style="text-align: center; flex-grow: 1; padding: 0 10px;">
                <h1 style="font-size: 14pt; margin: 0;"><?php echo htmlspecialchars($konfig['kop_baris1'] ?? 'INSTITUT TEKNOLOGI GARUT'); ?></h1>
                <h2 style="font-size: 12pt; margin: 5px 0; text-transform: uppercase;">
                    <?php echo htmlspecialchars($proposal['nama_ormawa']); ?>
                </h2>
                <p style="font-size: 9pt; margin: 0; font-style: italic;">
                    <?php echo htmlspecialchars($konfig['kop_baris3'] ?? ''); ?>
                </p>
                <p style="font-size: 9pt; margin: 0;">
                    <?php echo htmlspecialchars($konfig['kop_baris4'] ?? ''); ?>
                </p>
            </div>

            <!-- Logo Ormawa (Kanan) -->
            <?php if(!empty($user_ormawa['logo_ormawa'])): ?>
                <img src="uploads/profil/<?php echo $user_ormawa['logo_ormawa']; ?>" style="height: 80px;">
            <?php else: ?>
                <div style="width: 80px;"></div>
            <?php endif; ?>
        </div>

        <div class="title">PROPOSAL KEGIATAN<br><?php echo strtoupper($proposal['nama_kegiatan']); ?></div>
        
        <!-- ... (latar belakang, tujuan, rab, panitia) ... -->
        <div class="content">
            <span class="section-title">I. LATAR BELAKANG</span>
            <p style="text-align: justify;"><?php echo nl2br(htmlspecialchars($proposal['latar_belakang'])); ?></p>

            <span class="section-title">II. TUJUAN KEGIATAN</span>
            <p><?php echo nl2br(htmlspecialchars($proposal['tujuan'])); ?></p>

            <span class="section-title">III. SASARAN</span>
            <p><?php echo htmlspecialchars($proposal['sasaran']); ?></p>

            <span class="section-title">IV. RENCANA ANGGARAN BIAYA (RAB)</span>
            <table>
                <thead>
                    <tr>
                        <th style="width: 30px;">No</th>
                        <th>Rincian Kebutuhan</th>
                        <th style="width: 40px;">Vol</th>
                        <th style="width: 60px;">Satuan</th>
                        <th>Harga Satuan</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $total_rab = 0; foreach($rab as $idx => $r): 
                        $total_rab += $r['total_harga'];
                    ?>
                        <tr>
                            <td><?php echo $idx+1; ?></td>
                            <td><?php echo htmlspecialchars($r['rincian']); ?></td>
                            <td><?php echo $r['volume']; ?></td>
                            <td><?php echo htmlspecialchars($r['satuan']); ?></td>
                            <td>Rp <?php echo number_format($r['harga_satuan'], 0, ',', '.'); ?></td>
                            <td>Rp <?php echo number_format($r['total_harga'], 0, ',', '.'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr style="font-weight: bold; background: #eee;">
                        <td colspan="5" style="text-align: right;">TOTAL ANGGARAN</td>
                        <td>Rp <?php echo number_format($total_rab, 0, ',', '.'); ?></td>
                    </tr>
                </tbody>
            </table>

            <span class="section-title">V. SUSUNAN PANITIA</span>
            <table>
                <thead>
                    <tr>
                        <th style="width: 30px;">No</th>
                        <th>Jabatan</th>
                        <th>Nama Mahasiswa</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($panitia as $idx => $p): ?>
                        <tr>
                            <td><?php echo $idx+1; ?></td>
                            <td><?php echo htmlspecialchars($p['jabatan']); ?></td>
                            <td><?php echo htmlspecialchars($p['nama_mahasiswa']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <span class="section-title">VI. PENUTUP</span>
            <p style="text-align: justify;"><?php echo nl2br(htmlspecialchars($proposal['penutup'])); ?></p>
        </div>

        <!-- Penanda Tangan (Ketua, Sekretaris, Mengetahui) -->
        <table style="width: 100%; border: none; margin-top: 40px; table-layout: fixed;" class="no-border">
            <tr>
                <!-- Kolom Kiri: Ketua Pelaksana -->
                <td style="vertical-align: top; padding: 0;">
                    <?php if($ttd1): ?>
                    <div class="sig-container">
                        <div style="font-weight: bold; margin-bottom: 5px;">Ketua Pelaksana</div>
                        <div class="e-sig-box">
                            <div class="e-sig-qr-cell">
                                <?php 
                                    $verify_link = "index.php?page=verify_page&id=".$proposal['id_proposal']."&type=proposal_otomatis&signer=ketua_pelaksana";
                                    $qr_data = urlencode("https://".$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF'])."/".$verify_link);
                                    echo '<a href="'.$verify_link.'" target="_blank">';
                                    echo '<img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data='.$qr_data.'" style="width: 55px; height: 55px;">';
                                    echo '</a>';
                                ?>
                            </div>
                            <div class="e-sig-text-cell">
                                <div style="color: #666; font-size: 6.5pt;">Ditandatangani elektronik:</div>
                                <div style="font-weight: bold; font-size: 7pt; margin-bottom: 2px;">
                                    <?php echo strtoupper(htmlspecialchars($ttd1['jab'])); ?>
                                </div>
                                <div style="font-weight: bold; border-top: 1px solid #eee; padding-top: 2px; font-size: 8.5pt;">
                                    <?php echo htmlspecialchars($ttd1['name']); ?>
                                </div>
                                <?php if(!empty($ttd1['nim'])): ?>
                                    <div style="font-size: 7pt; color: #444;">NIM/NIDN: <?php echo htmlspecialchars($ttd1['nim']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </td>

                <!-- Kolom Tengah: Kosong / Spacer -->
                <td style="width: 20px;"></td>

                <!-- Kolom Kanan: Sekretaris -->
                <td style="vertical-align: top; padding: 0; text-align: right;">
                    <?php if($ttd2): ?>
                    <div class="sig-container" style="display: inline-block; text-align: left;">
                        <div style="font-weight: bold; margin-bottom: 5px;">Sekretaris</div>
                        <div class="e-sig-box">
                            <div class="e-sig-qr-cell">
                                <?php 
                                    $verify_link_sek = "index.php?page=verify_page&id=".$proposal['id_proposal']."&type=proposal_otomatis&signer=sekretaris";
                                    $qr_data_sek = urlencode("https://".$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF'])."/".$verify_link_sek);
                                    echo '<a href="'.$verify_link_sek.'" target="_blank">';
                                    echo '<img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data='.$qr_data_sek.'" style="width: 55px; height: 55px;">';
                                    echo '</a>';
                                ?>
                            </div>
                            <div class="e-sig-text-cell">
                                <div style="color: #666; font-size: 6.5pt;">Ditandatangani elektronik:</div>
                                <div style="font-weight: bold; font-size: 7pt; margin-bottom: 2px;">
                                    <?php echo strtoupper(htmlspecialchars($ttd2['jab'])); ?>
                                </div>
                                <div style="font-weight: bold; border-top: 1px solid #eee; padding-top: 2px; font-size: 8.5pt;">
                                    <?php echo htmlspecialchars($ttd2['name']); ?>
                                </div>
                                <?php if(!empty($ttd2['nim'])): ?>
                                    <div style="font-size: 7pt; color: #444;">NIM/NIDN: <?php echo htmlspecialchars($ttd2['nim']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </td>
            </tr>

            <!-- Baris Kedua: Mengetahui -->
            <?php if($ttd3): ?>
            <tr>
                <td colspan="3" style="text-align: center; padding-top: 30px;">
                    <div class="sig-container" style="display: inline-block; text-align: center;">
                        <div style="font-weight: bold; margin-bottom: 5px;">Mengetahui,<br>Ketua <?php echo htmlspecialchars($proposal['nama_ormawa']); ?></div>
                        <div class="e-sig-box" style="margin-left: auto; margin-right: auto; text-align: left;">
                            <div class="e-sig-qr-cell">
                                <?php 
                                    $verify_link_ko = "index.php?page=verify_page&id=".$proposal['id_proposal']."&type=proposal_otomatis&signer=ketua_ormawa";
                                    $qr_data_ko = urlencode("https://".$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF'])."/".$verify_link_ko);
                                    echo '<a href="'.$verify_link_ko.'" target="_blank">';
                                    echo '<img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data='.$qr_data_ko.'" style="width: 55px; height: 55px;">';
                                    echo '</a>';
                                ?>
                            </div>
                            <div class="e-sig-text-cell">
                                <div style="color: #666; font-size: 6.5pt;">Ditandatangani elektronik:</div>
                                <div style="font-weight: bold; font-size: 7pt; margin-bottom: 2px;">
                                    <?php echo strtoupper(htmlspecialchars($ttd3['jab'])); ?>
                                </div>
                                <div style="font-weight: bold; border-top: 1px solid #eee; padding-top: 2px; font-size: 8.5pt;">
                                    <?php echo htmlspecialchars($ttd3['name']); ?>
                                </div>
                                <?php if(!empty($ttd3['nim'])): ?>
                                    <div style="font-size: 7pt; color: #444;">NIM/NIDN: <?php echo htmlspecialchars($ttd3['nim']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
            <?php endif; ?>
        </table>

    </div>

</body>
</html>
