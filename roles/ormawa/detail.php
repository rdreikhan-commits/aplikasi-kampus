<?php
/**
 * File: detail.php
 * Deskripsi: Menampilkan detail lengkap dan riwayat status sebuah pengajuan proposal dengan visualisasi timeline.
 * --- PERBAIKAN ---
 * 1. Mengubah urutan baris tabel agar tombol "Lihat Surat Balasan" muncul di bawah tombol "Unduh LPJ".
 * 2. Mengubah tanggal di timeline riwayat agar menggunakan tanggal update status (histori_status.tanggal_update).
 * 3. Mengganti label "Tanggal Pengajuan" menjadi "Tanggal Kegiatan" di Informasi Proposal.
 * 4. Memastikan nama pengguna (yang melakukan aksi) tetap ditampilkan di setiap langkah riwayat.
 * 5. Mengubah teks tampilan status "Verifikasi..." menjadi "Diajukan ke..." pada array $status_info.
 * 6. Mengubah teks tampilan status persetujuan menjadi "Disetujui oleh..." (tanpa "Proposal").
 */

// Hak akses untuk semua peran yang relevan.
check_role(['ormawa', 'bem', 'bpm', 'bkh', 'wr3', 'bendahara']);

// Mengambil ID pengajuan dari URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<div class='alert alert-danger'>ID Pengajuan tidak valid.</div>";
    return;
}
$id_pengajuan = $_GET['id'];
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// 1. Mengambil data utama pengajuan
$stmt_pengajuan = $conn->prepare(
    "SELECT p.*, u.nama_lengkap AS nama_ormawa
     FROM pengajuan p
     JOIN users u ON p.id_user_ormawa = u.id_user
     WHERE p.id_pengajuan = ?"
);
$stmt_pengajuan->bind_param("i", $id_pengajuan);
$stmt_pengajuan->execute();
$result_pengajuan = $stmt_pengajuan->get_result();

if ($result_pengajuan->num_rows === 0) {
    echo "<div class='alert alert-danger'>Pengajuan tidak ditemukan.</div>";
    return;
}
$pengajuan = $result_pengajuan->fetch_assoc();
$stmt_pengajuan->close(); // Tutup statement setelah selesai

// Keamanan: Admin dan verifikator level atas bisa melihat semua.
// Ormawa hanya bisa lihat proposalnya sendiri. BEM/BPM juga dianggap Ormawa di sini.
if (in_array($user_role, ['ormawa', 'bem', 'bpm']) && $pengajuan['id_user_ormawa'] != $user_id) {
    echo "<div class='alert alert-danger'>Anda tidak memiliki izin untuk melihat detail pengajuan ini.</div>";
    return;
}

// 2. Mengambil data riwayat status dari tabel histori_status
$stmt_histori = $conn->prepare(
    "SELECT h.*, u.nama_lengkap AS nama_user
     FROM histori_status h
     LEFT JOIN users u ON h.id_user = u.id_user
     WHERE h.id_pengajuan = ?
     ORDER BY h.tanggal_update ASC" // Pastikan terurut dari yang terlama
);
$stmt_histori->bind_param("i", $id_pengajuan);
$stmt_histori->execute();
$result_histori = $stmt_histori->get_result();

// Membuat array terurut dari histori untuk timeline
$histori_ordered = [];
while ($row = $result_histori->fetch_assoc()) {
    $histori_ordered[] = $row;
}
$stmt_histori->close(); // Tutup statement setelah selesai

// Cari catatan penolakan terakhir (jika ada)
$catatan_penolakan = null;
if (strpos($pengajuan['status'], 'Ditolak') !== false) {
    // Loop mundur untuk menemukan catatan penolakan terakhir yang relevan
    foreach (array_reverse($histori_ordered) as $histori_item) {
        // Cocokkan status case-insensitive
        if (strcasecmp($histori_item['status'], $pengajuan['status']) === 0) {
            $catatan_penolakan = $histori_item['catatan'];
            break;
        }
    }
}


// Informasi untuk setiap status (Ikon dan Nama Tampilan)
// --- PERBAIKAN TEKS TAMPILAN v4 ---
$status_info = [
    'Draft' => ['icon' => 'bi-pencil-square', 'name' => 'Proposal Disimpan (Draft)'],
    'Diajukan' => ['icon' => 'bi-send', 'name' => 'Proposal Diajukan Ormawa'],
    'Diajukan Ke BEM' => ['icon' => 'bi-search', 'name' => 'Diajukan ke BEM'], // <-- DIUBAH
    'Disetujui BEM' => ['icon' => 'bi-check2-circle', 'name' => 'Disetujui oleh BEM'],
    'Ditolak BEM' => ['icon' => 'bi-x-circle', 'name' => 'Proposal Ditolak oleh BEM'],
    'Diajukan Ke BPM' => ['icon' => 'bi-search', 'name' => 'Diajukan ke BPM'], // <-- DIUBAH
    'Disetujui BPM' => ['icon' => 'bi-check2-circle', 'name' => 'Disetujui oleh BPM'],
    'Ditolak BPM' => ['icon' => 'bi-x-circle', 'name' => 'Proposal Ditolak oleh BPM'],
    'Verifikasi BKKH' => ['icon' => 'bi-search', 'name' => 'Diajukan ke BKKH'], // <-- DIUBAH
    'Disetujui BKKH' => ['icon' => 'bi-check2-circle', 'name' => 'Disetujui oleh BKKH'],
    'Ditolak BKKH' => ['icon' => 'bi-x-circle', 'name' => 'Proposal Ditolak oleh BKKH'],
    'Verifikasi WR3' => ['icon' => 'bi-search', 'name' => 'Diajukan ke WR3'], // <-- DIUBAH
    'Ditolak WR3' => ['icon' => 'bi-x-circle', 'name' => 'Proposal Ditolak oleh WR3'],
    'Disetujui WR3, Siap Diajukan ke Bendahara' => ['icon' => 'bi-check2-circle', 'name' => 'Disetujui oleh WR3'],
    'Diajukan ke Bendahara' => ['icon' => 'bi-send-check', 'name' => 'Proposal Diajukan ke Bendahara'],
    'Dana Cair' => ['icon' => 'bi-wallet2', 'name' => 'Dana Sudah Cair'],
    'LPJ Diajukan' => ['icon' => 'bi-journal-arrow-up', 'name' => 'LPJ Diajukan'],
    'LPJ Ditolak BKKH' => ['icon' => 'bi-journal-x', 'name' => 'LPJ Ditolak oleh BKKH'],
    'LPJ Diverifikasi' => ['icon' => 'bi-journal-check', 'name' => 'LPJ Diverifikasi oleh BKKH'],
    'Selesai' => ['icon' => 'bi-patch-check-fill', 'name' => 'Proses Selesai'],
];
// --- AKHIR PERBAIKAN ---

?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Detail Pengajuan Proposal</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="index.php?page=riwayat">Riwayat</a></li>
        <li class="breadcrumb-item active">Detail</li>
    </ol>

    <?php if ($catatan_penolakan) : ?>
    <div class="alert alert-warning" role="alert">
        <h4 class="alert-heading"><i class="bi bi-exclamation-triangle-fill"></i> Catatan Revisi/Penolakan Terakhir</h4>
        <p class="mb-0 fst-italic">"<?php echo htmlspecialchars($catatan_penolakan); ?>"</p>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- Kolom Informasi Proposal -->
        <div class="col-lg-5">
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-white border-0 py-3"><i class="bi bi-info-circle-fill me-1"></i> Informasi Proposal</div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th style="width: 35%;">Nama Kegiatan</th>
                            <td>: <?php echo htmlspecialchars($pengajuan['nama_kegiatan']); ?></td>
                        </tr>
                        <tr>
                            <th>Ormawa Pengaju</th>
                            <td>: <?php echo htmlspecialchars($pengajuan['nama_ormawa']); ?></td>
                        </tr>
                        <tr>
                            <!-- PERBAIKAN LABEL -->
                            <th>Tanggal Kegiatan</th>
                            <!-- Asumsi kolom tanggal_pengajuan di DB berisi tanggal kegiatan -->
                            <td>: <?php echo isset($pengajuan['tanggal_pengajuan']) ? date('d F Y', strtotime($pengajuan['tanggal_pengajuan'])) : '-'; ?></td>
                        </tr>
                        <tr>
                            <th>Dana Diajukan</th>
                            <td>: <strong>Rp <?php echo number_format($pengajuan['dana_diajukan'], 0, ',', '.'); ?></strong></td>
                        </tr>
                        <tr>
                            <th>Status Terakhir</th>
                            <td>: <span class="badge bg-primary fs-6"><?php echo htmlspecialchars($pengajuan['status']); ?></span></td>
                        </tr>
                        <tr>
                            <th>File Proposal</th>
                            <td>:
                                <?php if (!empty($pengajuan['file_proposal']) && file_exists('uploads/proposal/' . $pengajuan['file_proposal'])): ?>
                                <a href="uploads/proposal/<?php echo htmlspecialchars($pengajuan['file_proposal']); ?>" target="_blank" class="btn btn-outline-primary btn-sm"><i class="bi bi-download me-1"></i> Unduh Proposal</a>
                                <?php else: ?>
                                <span class="text-muted fst-italic">File tidak ditemukan</span>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <?php
                        // --- PERBAIKAN URUTAN: Tombol LPJ Muncul Dahulu ---
                        if (!empty($pengajuan['file_lpj'])) :
                        ?>
                            <tr>
                                <th>File LPJ</th>
                                <td>:
                                    <?php if (file_exists('uploads/lpj/' . $pengajuan['file_lpj'])): ?>
                                    <a href="uploads/lpj/<?php echo htmlspecialchars($pengajuan['file_lpj']); ?>" target="_blank" class="btn btn-outline-success btn-sm"><i class="bi bi-download me-1"></i> Unduh LPJ</a>
                                    <?php else: ?>
                                    <span class="text-muted fst-italic">File tidak ditemukan</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php
                        // --- PERBAIKAN URUTAN: Tombol Surat Balasan Muncul Setelah LPJ ---
                        $status_untuk_surat = ['disetujui wr3, siap diajukan ke bendahara', 'diajukan ke bendahara', 'dana cair', 'lpj diajukan', 'lpj ditolak bkkh', 'lpj diverifikasi', 'selesai'];
                        // Tambah Pengecekan Nomor Surat
                        if (!empty($pengajuan['nomor_surat']) && in_array(strtolower(trim($pengajuan['status'])), $status_untuk_surat)):
                        ?>
                            <tr>
                                <th>Surat Balasan</th>
                                <td>: <a href="index.php?page=cetak_surat&id=<?php echo htmlspecialchars($id_pengajuan); ?>" target="_blank" class="btn btn-info btn-sm text-white"><i class="bi bi-envelope-paper-fill me-2"></i>Lihat Surat Balasan</a></td>
                            </tr>
                        <?php elseif (in_array(strtolower(trim($pengajuan['status'])), $status_untuk_surat)): ?>
                            <tr>
                                <th>Surat Balasan</th>
                                <td>: <span class="text-muted fst-italic">Nomor surat belum diterbitkan</span></td>
                            </tr>
                        <?php endif; ?>

                    </table>
                </div>
            </div>
        </div>

        <!-- Kolom Riwayat Status Timeline -->
        <div class="col-lg-7">
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-white border-0 py-3"><i class="bi bi-clock-history me-1"></i> Riwayat Alur Proposal</div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <?php
                        // Tampilkan timeline HANYA berdasarkan riwayat yang ada di database
                        $displayed_statuses = []; // Kosongkan, kita akan tampilkan semua dari histori

                        if (empty($histori_ordered)) {
                            echo '<li class="list-group-item text-muted">Belum ada riwayat status untuk pengajuan ini.</li>';
                        } else {
                            foreach ($histori_ordered as $histori_item) {
                                $status_alur = $histori_item['status'];
                                $tanggal_update = $histori_item['tanggal_update']; // Tanggal aktual status
                                $nama_user = $histori_item['nama_user'] ?? 'Sistem'; // Fallback jika user tidak ada
                                $catatan = $histori_item['catatan'];

                                // Lewati duplikasi status berturut-turut (misal revisi berulang)
                                // if (in_array($status_alur, $displayed_statuses)) {
                                //     continue;
                                // }
                                // $displayed_statuses[] = $status_alur; // Catat status ini sebagai sudah ditampilkan

                                $info = $status_info[$status_alur] ?? ['icon' => 'bi-question-circle', 'name' => $status_alur];

                                // Tentukan warna ikon dan teks berdasarkan status
                                $text_color = 'text-success'; // Default untuk status selesai/setuju
                                if (strpos(strtolower($status_alur), 'ditolak') !== false) {
                                    $text_color = 'text-danger'; // Merah untuk ditolak
                                } elseif ($status_alur === $pengajuan['status'] && strcasecmp($status_alur, 'selesai') !== 0) {
                                    $text_color = 'text-primary'; // Biru untuk status saat ini (kecuali selesai)
                                } elseif (strcasecmp($status_alur, 'selesai') !== 0) {
                                    $text_color = 'text-secondary'; // Abu-abu untuk status proses sebelumnya
                                }
                        ?>
                        <li class="list-group-item d-flex align-items-start border-0 px-0">
                             <i class="bi <?php echo $info['icon']; ?> fs-4 <?php echo $text_color; ?> me-3"></i>
                             <div>
                                 <h6 class="mb-1 fw-bold <?php echo $text_color; ?>"><?php echo htmlspecialchars($info['name']); ?></h6>
                                 <small class="text-muted">
                                     <?php echo date('d M Y, H:i', strtotime($tanggal_update)); // <-- PERBAIKAN: Gunakan tanggal update ?>
                                     oleh <?php echo htmlspecialchars($nama_user); // Nama pengguna ditampilkan di sini ?>
                                 </small>

                                 <?php if (!empty($catatan)) : ?>
                                      <p class="mb-0 fst-italic bg-light p-2 rounded small mt-1">"<?php echo htmlspecialchars($catatan); ?>"</p>
                                 <?php endif; ?>
                             </div>
                        </li>
                        <?php
                            } // Akhir loop foreach
                        } // Akhir else jika histori tidak kosong

                        // Cek jika status terakhir di pengajuan belum ada di histori (jarang terjadi)
                        $current_status = $pengajuan['status'];
                        $last_histori_status = end($histori_ordered)['status'] ?? null;
                        // Gunakan strcasecmp untuk perbandingan case-insensitive
                        if ($last_histori_status !== null && strcasecmp($current_status, $last_histori_status) !== 0) {
                             $info = $status_info[$current_status] ?? ['icon' => 'bi-question-circle', 'name' => $current_status];
                             $text_color = (strcasecmp($current_status, 'selesai') === 0) ? 'text-success' : 'text-primary';
                         ?>
                         <li class="list-group-item d-flex align-items-start border-0 px-0">
                              <i class="bi <?php echo $info['icon']; ?> fs-4 <?php echo $text_color; ?> me-3"></i>
                              <div>
                                  <h6 class="mb-1 fw-bold <?php echo $text_color; ?>"><?php echo htmlspecialchars($info['name']); ?></h6>
                                  <small class="text-muted">Status terakhir yang tercatat di pengajuan.</small>
                              </div>
                         </li>
                         <?php } ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

