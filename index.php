<?php

require_once 'config.php';
require_once 'functions.php';

// Pastikan skrip terhenti jika file penting tidak ada
if (!file_exists('config.php') || !file_exists('functions.php')) {
    http_response_code(500);
    die("<h1>500 - Internal Server Error</h1><p>File konfigurasi atau fungsi tidak ditemukan.</p>");
}

start_output_buffering();
initialize_session();

// =================================================================
// PUSAT LOGIKA PEMROSESAN GET (AKSI TANPA SUBMIT FORM)
// Aksi GET seharusnya tidak mengubah status data secara langsung.
// Aksi di sini hanya untuk menampilkan halaman atau data.
// =================================================================
$page_action = $_GET['page'] ?? '';

// --- Logika untuk toggle status user (GET) ---
if ($page_action === 'toggle_status' && isset($_GET['id']) && isset($_GET['new_status'])) {
    // Aksi ini seharusnya diproses oleh POST untuk alasan keamanan (idempotensi),
    // tapi karena struktur awal Anda menggunakan GET, saya pertahankan.
    // Namun, idealnya Anda menggunakan form dengan method POST.
    check_role(['bkh']);
    
    $id_user_target = intval($_GET['id']);
    $status_baru = sanitize_input($conn, $_GET['new_status']) === 'aktif' ? 'aktif' : 'nonaktif';
    
    // Pastikan ID target tidak kosong dan statusnya valid
    if ($id_user_target > 0) {
        $stmt = $conn->prepare("UPDATE users SET status_akun = ? WHERE id_user = ?");
        if ($stmt === false) {
            redirect('index.php?page=manage_users&error=db_prepare_gagal');
        }
        $stmt->bind_param("si", $status_baru, $id_user_target);
        if ($stmt->execute()) {
            redirect('index.php?page=manage_users&status=toggle_sukses');
        } else {
            redirect('index.php?page=manage_users&error=toggle_gagal');
        }
    } else {
        redirect('index.php?page=manage_users&error=invalid_id');
    }
}

// =================================================================
// PUSAT LOGIKA PEMROSESAN POST (AKSI DARI SUBMIT FORM)
// Aksi POST seharusnya digunakan untuk semua aksi yang mengubah data
// di database, seperti tambah, edit, atau verifikasi.
// =================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Logika untuk Tambah User
    if ($page_action === 'tambah_user' && isset($_POST['nama_lengkap'])) {
        check_role(['bkh']);
        $nama_lengkap = sanitize_input($conn, $_POST['nama_lengkap']);
        $username = sanitize_input($conn, $_POST['username']);
        $password = $_POST['password'];
        $role = sanitize_input($conn, $_POST['role']);
        
        if (empty($nama_lengkap) || empty($username) || empty($password) || empty($role)) {
            redirect('index.php?page=tambah_user&error=form_kosong');
        }
        
        $stmt_check = $conn->prepare("SELECT id_user FROM users WHERE username = ?");
        $stmt_check->bind_param("s", $username);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            redirect('index.php?page=tambah_user&error=username_duplikat');
        }
        
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (nama_lengkap, username, password, user_role, status_akun) VALUES (?, ?, ?, ?, 'aktif')");
        $stmt->bind_param("ssss", $nama_lengkap, $username, $hashed_password, $role);
        
        if ($stmt->execute()) {
            redirect('index.php?page=manage_users&status=tambah_user_sukses');
        } else {
            redirect('index.php?page=manage_users&error=gagal_simpan');
        }
    }

    // +++ PENAMBAHAN BARU: Logika Aspirasi Publik +++
    if ($page_action === 'aspirasi' && isset($_POST['kirim_aspirasi'])) {
        $nama = sanitize_input($conn, $_POST['nama'] ?? 'Anonim');
        $email = sanitize_input($conn, $_POST['email'] ?? '');
        $kategori = sanitize_input($conn, $_POST['kategori']);
        $subjek = sanitize_input($conn, $_POST['subjek']);
        $isi = sanitize_input($conn, $_POST['isi']);
        
        $stmt = $conn->prepare("INSERT INTO aspirasi (nama_pelapor, email_pelapor, kategori, subjek, isi_aspirasi) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $nama, $email, $kategori, $subjek, $isi);
        if ($stmt->execute()) {
            redirect('index.php?page=aspirasi&status=aspirasi_sukses');
        } else {
            redirect('index.php?page=aspirasi&error=db_gagal');
        }
    }

    if ($page_action === 'manage_aspirasi' && isset($_POST['tanggapi_aspirasi'])) {
        check_role(['bpm']);
        $id = intval($_POST['id_aspirasi']);
        $tanggapan = sanitize_input($conn, $_POST['tanggapan']);
        $status = sanitize_input($conn, $_POST['status']);
        
        $stmt = $conn->prepare("UPDATE aspirasi SET tanggapan_bpm = ?, status = ? WHERE id_aspirasi = ?");
        $stmt->bind_param("ssi", $tanggapan, $status, $id);
        $stmt->execute();
        redirect('index.php?page=manage_aspirasi&status=tanggapan_sukses');
    }

    // Logika untuk Edit User
    if ($page_action === 'edit_user') {
        check_role(['bkh']);
        $id_user_edit = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if ($id_user_edit <= 0) {
            redirect('index.php?page=manage_users&error=invalid_id');
        }
        
        $nama_lengkap = sanitize_input($conn, $_POST['nama_lengkap']);
        $username = sanitize_input($conn, $_POST['username']);
        $password_baru = $_POST['password'];
        $role = sanitize_input($conn, $_POST['role']);
        
        // --- PERBAIKAN VALIDASI ROLE (UNTUK EDIT) ---
        if (empty($role)) {
             redirect('index.php?page=edit_user&id=' . $id_user_edit . '&error=form_kosong');
        }
        $allowed_roles = ['ormawa', 'bpm', 'bem', 'bkh', 'wr3', 'bendahara', 'admin', 'sarpras', 'sarpras_barang'];
        if (!in_array($role, $allowed_roles)) {
            redirect('index.php?page=edit_user&id=' . $id_user_edit . '&error=gagal_simpan&pesan=Role+tidak+valid');
            exit(); 
        }
        // --- AKHIR PERBAIKAN ---

        $stmt_check = $conn->prepare("SELECT id_user FROM users WHERE username = ? AND id_user != ?");
        if ($stmt_check === false) {
            redirect('index.php?page=edit_user&id=' . $id_user_edit . '&error=db_prepare_gagal');
        }
        $stmt_check->bind_param("si", $username, $id_user_edit);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            redirect('index.php?page=edit_user&id=' . $id_user_edit . '&error=username_duplikat');
        }
        
        if (!empty($password_baru)) {
            $hashed_password = password_hash($password_baru, PASSWORD_DEFAULT);
            $stmt_update = $conn->prepare("UPDATE users SET nama_lengkap = ?, username = ?, password = ?, role = ? WHERE id_user = ?");
            if ($stmt_update === false) {
                redirect('index.php?page=edit_user&id=' . $id_user_edit . '&error=db_prepare_gagal');
            }
            $stmt_update->bind_param("ssssi", $nama_lengkap, $username, $hashed_password, $role, $id_user_edit);
        } else {
            $stmt_update = $conn->prepare("UPDATE users SET nama_lengkap = ?, username = ?, role = ? WHERE id_user = ?");
            if ($stmt_update === false) {
                redirect('index.php?page=edit_user&id=' . $id_user_edit . '&error=db_prepare_gagal');
            }
            $stmt_update->bind_param("sssi", $nama_lengkap, $username, $role, $id_user_edit);
        }
        
        if ($stmt_update->execute()) {
            redirect('index.php?page=manage_users&status=edit_user_sukses');
        } else {
            redirect('index.php?page=edit_user&id=' . $id_user_edit . '&error=update_gagal');
        }
    }

    // Logika untuk Atur Saldo
    if ($page_action === 'atur_saldo') {
        check_role(['bkh']);
        $id_pengguna = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if ($id_pengguna <= 0) {
            redirect('index.php?page=manage_saldo&error=invalid_id');
        }
        
        $saldo_baru = preg_replace('/[^0-9]/', '', $_POST['saldo']);
        if (!is_numeric($saldo_baru) || $saldo_baru < 0) {
            redirect('index.php?page=atur_saldo&id=' . $id_pengguna . '&error=saldo_invalid');
        }
        
        $stmt = $conn->prepare("UPDATE users SET saldo = ? WHERE id_user = ? AND role IN ('ormawa', 'bem', 'bpm')");
        if ($stmt === false) {
            redirect('index.php?page=atur_saldo&id=' . $id_pengguna . '&error=db_prepare_gagal');
        }
        $stmt->bind_param("di", $saldo_baru, $id_pengguna);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            redirect('index.php?page=manage_saldo&status=saldo_sukses');
        } else {
            redirect('index.php?page=atur_saldo&id=' . $id_pengguna . '&error=update_gagal');
        }
    }

    // Logika untuk Tambah Pengajuan
    if ($page_action === 'tambah') {
        check_role(['ormawa', 'bem', 'bpm']);
        $id_user = $_SESSION['user_id'];
        $user_role = $_SESSION['user_role'];
        $nama_kegiatan = sanitize_input($conn, $_POST['nama_kegiatan']);
        $tanggal_pengajuan = sanitize_input($conn, $_POST['tanggal_pengajuan']);
        $dana_diajukan = (float)preg_replace('/[^0-9]/', '', $_POST['dana_diajukan']);
        
        if (empty($nama_kegiatan) || empty($tanggal_pengajuan) || empty($dana_diajukan)) {
            redirect('index.php?page=tambah&error=form_kosong');
        }

        $stmt_saldo = $conn->prepare("SELECT saldo FROM users WHERE id_user = ?");
        $stmt_saldo->bind_param("i", $id_user);
        $stmt_saldo->execute();
        $total_saldo = $stmt_saldo->get_result()->fetch_assoc()['saldo'] ?? 0;
        
        $stmt_terpakai = $conn->prepare("SELECT SUM(dana_diajukan) AS total FROM pengajuan WHERE id_user_ormawa = ? AND status NOT IN ('Ditolak BEM', 'Ditolak BKH', 'Ditolak WR3', 'Ditolak Bendahara')");
        $stmt_terpakai->bind_param("i", $id_user);
        $stmt_terpakai->execute();
        $saldo_terpakai = $stmt_terpakai->get_result()->fetch_assoc()['total'] ?? 0;

        $sisa_saldo = $total_saldo - $saldo_terpakai;

        if ($dana_diajukan > $sisa_saldo) {
            redirect('index.php?page=tambah&error=saldo_tidak_cukup');
        }
        
        if (!isset($_FILES['file_proposal']) || $_FILES['file_proposal']['error'] != 0) {
            redirect('index.php?page=tambah&error=file_kosong');
        }

        $target_dir = "uploads/proposal/";
        if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
        $file_extension = strtolower(pathinfo($_FILES["file_proposal"]["name"], PATHINFO_EXTENSION));
        
        if ($file_extension !== "pdf") {
            redirect('index.php?page=tambah&error=bukan_pdf');
        }

        $file_name = "proposal_" . $id_user . "_" . time() . '.' . $file_extension;
        $target_file = $target_dir . $file_name;
        
        if (move_uploaded_file($_FILES["file_proposal"]["tmp_name"], $target_file)) {
            $status = ($user_role === 'bem' || $user_role === 'bpm') ? "Verifikasi BKKH" : "Diajukan Ke BEM";

            $stmt = $conn->prepare("INSERT INTO pengajuan (id_user_ormawa, nama_kegiatan, dana_diajukan, tanggal_pengajuan, file_proposal, status) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt === false) {
                unlink($target_file);
                redirect('index.php?page=tambah&error=db_prepare_gagal');
            }
            $stmt->bind_param("isdsss", $id_user, $nama_kegiatan, $dana_diajukan, $tanggal_pengajuan, $file_name, $status);
            if ($stmt->execute()) {
                $new_id = $conn->insert_id;
                add_history($conn, $new_id, $id_user, $status, 'Proposal awal telah diajukan oleh Ormawa.');
                redirect('index.php?page=riwayat&status=tambah_sukses');
            } else {
                unlink($target_file);
                redirect('index.php?page=tambah&error=db_gagal');
            }
        } else {
            redirect('index.php?page=tambah&error=upload_gagal');
        }
    }

    // Logika untuk Edit Pengajuan
    if ($page_action === 'edit') {
        check_role(['ormawa', 'bem', 'bpm']);
        $id_pengajuan = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $user_id = $_SESSION['user_id'];
        
        if ($id_pengajuan <= 0) {
            redirect('index.php?page=riwayat&error=invalid_id');
        }

        $stmt_old = $conn->prepare("SELECT file_proposal, status FROM pengajuan WHERE id_pengajuan = ? AND id_user_ormawa = ?");
        if ($stmt_old === false) {
            redirect('index.php?page=riwayat&error=db_prepare_gagal');
        }
        $stmt_old->bind_param("ii", $id_pengajuan, $user_id);
        $stmt_old->execute();
        $old_data = $stmt_old->get_result()->fetch_assoc();
        $stmt_old->close();

        if (!$old_data) {
            redirect('index.php?page=riwayat&error=unauthorized');
        }
        
        if (strpos(strtolower($old_data['status']), 'ditolak') === false) {
            redirect('index.php?page=riwayat&error=edit_disallowed');
        }

        $nama_kegiatan = sanitize_input($conn, $_POST['nama_kegiatan']);
        $tanggal_pengajuan = sanitize_input($conn, $_POST['tanggal_pengajuan']);
        $dana_diajukan = preg_replace('/[^0-9]/', '', $_POST['dana_diajukan']);
        
        if (empty($nama_kegiatan) || empty($tanggal_pengajuan) || empty($dana_diajukan)) {
            redirect('index.php?page=edit&id=' . $id_pengajuan . '&error=form_kosong');
        }

        $file_name = $old_data['file_proposal'];

        if (isset($_FILES['file_proposal']) && $_FILES['file_proposal']['error'] == 0) {
            $target_dir = "uploads/proposal/";
            $file_extension = strtolower(pathinfo($_FILES["file_proposal"]["name"], PATHINFO_EXTENSION));
            if ($file_extension != "pdf") redirect('index.php?page=edit&id=' . $id_pengajuan . '&error=bukan_pdf');

            $new_file_name = "proposal_" . $user_id . "_" . time() . '.' . $file_extension;
            $target_file = $target_dir . $new_file_name;

            if (move_uploaded_file($_FILES["file_proposal"]["tmp_name"], $target_file)) {
                if (!empty($file_name) && file_exists($target_dir . $file_name)) unlink($target_dir . $file_name);
                $file_name = $new_file_name;
            } else {
                redirect('index.php?page=edit&id=' . $id_pengajuan . '&error=upload_gagal');
            }
        }
        
        // --- PERBAIKAN: Alur revisi dinamis berdasarkan siapa yang menolak ---
        $current_status = $old_data['status'];
        $new_status = '';

        switch ($current_status) {
            case 'Ditolak BEM':
                $new_status = 'Diajukan Ke BEM';
                break;
            case 'Ditolak BPM':
                $new_status = 'Diajukan Ke BPM';
                break;
            case 'Ditolak BKKH':
                $new_status = 'Verifikasi BKKH';
                break;
            case 'Ditolak WR3':
                $new_status = 'Verifikasi WR3';
                break;
            default:
                // Fallback jika status ditolak tidak dikenali, kembali ke awal
                $new_status = 'Diajukan Ke BEM';
                break;
        }
        
        // PERBAIKAN: Update juga membersihkan catatan revisi
        $stmt_update = $conn->prepare("UPDATE pengajuan SET nama_kegiatan = ?, dana_diajukan = ?, tanggal_pengajuan = ?, file_proposal = ?, status = ?, catatan_revisi = NULL WHERE id_pengajuan = ?");
        if ($stmt_update === false) {
            redirect('index.php?page=edit&id=' . $id_pengajuan . '&error=db_prepare_gagal');
        }
        $stmt_update->bind_param("sdsssi", $nama_kegiatan, $dana_diajukan, $tanggal_pengajuan, $file_name, $new_status, $id_pengajuan);
        
        if ($stmt_update->execute()) {
            add_history($conn, $id_pengajuan, $user_id, $new_status, 'Proposal telah direvisi dan diajukan kembali.');
            redirect('index.php?page=riwayat&status=edit_sukses');
        } else {
            redirect('index.php?page=edit&id=' . $id_pengajuan . '&error=db_gagal');
        }
    }

    // PERBAIKAN: Logika untuk mengajukan pencairan dana, dengan redirect yang benar
    if ($page_action === 'ajukan_pencairan') {
        check_role(['bkh']);
        $id_pengajuan = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $user_id = $_SESSION['user_id'];
        
        if ($id_pengajuan <= 0) {
            redirect('index.php?page=dashboard&error=invalid_id');
        }

        $stmt_check = $conn->prepare("SELECT status FROM pengajuan WHERE id_pengajuan = ?");
        if ($stmt_check === false) {
            redirect('index.php?page=dashboard&error=db_prepare_gagal');
        }
        $stmt_check->bind_param("i", $id_pengajuan);
        $stmt_check->execute();
        $result = $stmt_check->get_result();
        
        if ($result->num_rows > 0) {
            $pengajuan = $result->fetch_assoc();
            
            if (trim($pengajuan['status']) === 'Disetujui WR3, Siap Diajukan ke Bendahara') {
                $new_status = 'Diajukan ke Bendahara';
                $stmt_update = $conn->prepare("UPDATE pengajuan SET status = ? WHERE id_pengajuan = ?");
                if ($stmt_update === false) {
                    redirect('index.php?page=dashboard&error=db_prepare_gagal');
                }
                $stmt_update->bind_param("si", $new_status, $id_pengajuan);
                
                if ($stmt_update->execute()) {
                    add_history($conn, $id_pengajuan, $user_id, $new_status, 'Pengajuan pencairan dana telah diajukan ke Bendahara.');
                    redirect('index.php?page=dashboard&success=bendahara_sukses'); 
                } else {
                    redirect('index.php?page=dashboard&error=db_gagal');
                }
            } else {
                redirect('index.php?page=dashboard&error=status_salah');
            }
        } else {
            redirect('index.php?page=dashboard&error=not_found');
        }
    }
    
    // Logika verifikasi oleh Bendahara
    if ($page_action === 'verifikasi_bendahara') {
        check_role(['bendahara']);
        $id_pengajuan = isset($_POST['id_pengajuan']) ? intval($_POST['id_pengajuan']) : 0;
        $dana_disetujui = preg_replace('/[^0-9]/', '', $_POST['dana_disetujui']);
        $status_verifikasi = sanitize_input($conn, $_POST['status_verifikasi']);
        $catatan = sanitize_input($conn, $_POST['catatan']);
        $user_id = $_SESSION['user_id'];
        
        if ($id_pengajuan <= 0) {
            redirect('index.php?page=proses&error=invalid_id');
        }

        $stmt_check_status = $conn->prepare("SELECT status FROM pengajuan WHERE id_pengajuan = ?");
        if ($stmt_check_status === false) {
            redirect('index.php?page=proses&error=db_prepare_gagal');
        }
        $stmt_check_status->bind_param("i", $id_pengajuan);
        $stmt_check_status->execute();
        $result_status = $stmt_check_status->get_result();
        $pengajuan_status = $result_status->fetch_assoc();
        
        if (trim($pengajuan_status['status']) !== 'Diajukan ke Bendahara') {
            redirect('index.php?page=proses&error=status_tidak_sesuai');
        }
        
        if ($status_verifikasi === 'disetujui') {
            $new_status = 'Dana Cair'; 
            $stmt_update = $conn->prepare("UPDATE pengajuan SET status = ?, dana_disetujui = ?, catatan = ? WHERE id_pengajuan = ?");
            if ($stmt_update === false) {
                redirect('index.php?page=proses&error=db_prepare_gagal');
            }
            $stmt_update->bind_param("sssi", $new_status, $dana_disetujui, $catatan, $id_pengajuan);
            $history_message = 'Pengajuan pencairan telah diverifikasi dan disetujui oleh Bendahara. Dana telah dicairkan. Catatan: ' . $catatan;
        } else {
            $new_status = 'Ditolak Bendahara';
            $stmt_update = $conn->prepare("UPDATE pengajuan SET status = ?, catatan = ? WHERE id_pengajuan = ?");
            if ($stmt_update === false) {
                redirect('index.php?page=proses&error=db_prepare_gagal');
            }
            $stmt_update->bind_param("ssi", $new_status, $catatan, $id_pengajuan);
            $history_message = 'Pengajuan pencairan telah ditolak oleh Bendahara. Catatan: ' . $catatan;
        }

        if ($stmt_update->execute()) {
            add_history($conn, $id_pengajuan, $user_id, $new_status, $history_message);
            redirect('index.php?page=proses&status=verifikasi_sukses');
        } else {
            redirect('index.php?page=proses&error=verifikasi_gagal');
        }
    }

    // --- PENAMBAHAN BARU: Logika untuk Atur Profil ---
    if ($page_action === 'profil') {
        check_login(); // Pastikan user sudah login
        
        $id_user = $_SESSION['user_id'];
        $nama_lengkap = sanitize_input($conn, $_POST['nama_lengkap']);
        
        if (empty($nama_lengkap)) {
            redirect('index.php?page=profil&error=form_kosong');
        }

        $nama_ketua = sanitize_input($conn, $_POST['nama_ketua'] ?? '');
        $nama_sekretaris = sanitize_input($conn, $_POST['nama_sekretaris'] ?? '');
        $nama_bendahara = sanitize_input($conn, $_POST['nama_bendahara'] ?? '');

        // Ambil data lama untuk TTD & Logo
        $stmt_old = $conn->prepare("SELECT foto_profil, logo_ormawa, ttd_ketua, ttd_sekretaris, ttd_bendahara FROM users WHERE id_user = ?");
        $stmt_old->bind_param("i", $id_user);
        $stmt_old->execute();
        $old_data = $stmt_old->get_result()->fetch_assoc();
        $old_photo = $old_data['foto_profil'] ?? null;
        $old_logo = $old_data['logo_ormawa'] ?? null;
        $old_ttd_k = $old_data['ttd_ketua'] ?? null;
        $old_ttd_s = $old_data['ttd_sekretaris'] ?? null;
        $old_ttd_b = $old_data['ttd_bendahara'] ?? null;
        $stmt_old->close();

        $new_photo_name = $old_photo;
        $new_logo_name = $old_logo;
        $new_ttd_k = $old_ttd_k;
        $new_ttd_s = $old_ttd_s;
        $new_ttd_b = $old_ttd_b;

        $target_dir = "uploads/profil/";
        if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }

        // Helper function for upload
        $handleUpload = function($fileKey, $oldFile) use ($id_user, $target_dir) {
            if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] == 0) {
                $file_info = pathinfo($_FILES[$fileKey]["name"]);
                $ext = strtolower($file_info['extension']);
                if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                    if (!empty($oldFile) && file_exists($target_dir . $oldFile)) { unlink($target_dir . $oldFile); }
                    $newName = $fileKey . "_" . $id_user . "_" . time() . '.' . $ext;
                    if (move_uploaded_file($_FILES[$fileKey]["tmp_name"], $target_dir . $newName)) {
                        return $newName;
                    }
                }
            }
            return $oldFile;
        };

        $new_photo_name = $handleUpload('foto_profil', $old_photo);
        $new_logo_name = $handleUpload('logo_ormawa', $old_logo);
        $new_ttd_k = $handleUpload('ttd_ketua', $old_ttd_k);
        $new_ttd_s = $handleUpload('ttd_sekretaris', $old_ttd_s);
        $new_ttd_b = $handleUpload('ttd_bendahara', $old_ttd_b);

        // Update database
        $stmt_update = $conn->prepare("UPDATE users SET nama_lengkap = ?, foto_profil = ?, logo_ormawa = ?, nama_ketua = ?, nama_sekretaris = ?, nama_bendahara = ?, ttd_ketua = ?, ttd_sekretaris = ?, ttd_bendahara = ? WHERE id_user = ?");
        $stmt_update->bind_param("sssssssssi", $nama_lengkap, $new_photo_name, $new_logo_name, $nama_ketua, $nama_sekretaris, $nama_bendahara, $new_ttd_k, $new_ttd_s, $new_ttd_b, $id_user);

        if ($stmt_update->execute()) {
            $_SESSION['nama_lengkap'] = $nama_lengkap;
            $_SESSION['foto_profil'] = $new_photo_name;
            redirect('index.php?page=profil&status=update_sukses');
        } else {
            redirect('index.php?page=profil&error=db_gagal');
        }
    }

    // +++ PENAMBAHAN BARU: Logika untuk Pengumuman di Pusat Informasi +++
    if ($page_action === 'pusat_informasi' && isset($_POST['tambah_pengumuman'])) {
        check_role(['bem', 'bpm']); // BEM for news, BPM for regulasi (handled separately usually but shared here)
        $judul = sanitize_input($conn, $_POST['judul']);
        $isi = $_POST['isi']; 
        $id_user = $_SESSION['user_id'];
        
        $file_name = null;
        if (isset($_FILES['lampiran']) && $_FILES['lampiran']['error'] == 0) {
            $target_dir = "uploads/pengumuman/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            $ext = strtolower(pathinfo($_FILES["lampiran"]["name"], PATHINFO_EXTENSION));
            $file_name = "announcement_" . time() . "." . $ext;
            move_uploaded_file($_FILES["lampiran"]["tmp_name"], $target_dir . $file_name);
        }

        $stmt = $conn->prepare("INSERT INTO pengumuman (judul, isi, file_lampiran, id_user_upload) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $judul, $isi, $file_name, $id_user);
        if ($stmt->execute()) {
            redirect('index.php?page=pusat_informasi&status=pengumuman_sukses');
        } else {
            redirect('index.php?page=pusat_informasi&error=db_gagal');
        }
    }

    if ($page_action === 'pusat_informasi' && isset($_POST['hapus_pengumuman'])) {
        check_role(['bem']);
        $id = intval($_POST['id_pengumuman']);
        $stmt = $conn->prepare("DELETE FROM pengumuman WHERE id_pengumuman = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        redirect('index.php?page=pusat_informasi&status=hapus_sukses');
    }

    // +++ PENAMBAHAN BARU: Logika untuk Jadwal Rapat +++
    if ($page_action === 'jadwal_rapat' && isset($_POST['tambah_rapat'])) {
        check_role(['bem', 'bpm']);
        $judul = sanitize_input($conn, $_POST['judul_rapat']);
        $deskripsi = sanitize_input($conn, $_POST['deskripsi']);
        $tanggal = sanitize_input($conn, $_POST['tanggal_rapat']);
        $jam = sanitize_input($conn, $_POST['jam_rapat']);
        $lokasi = sanitize_input($conn, $_POST['lokasi']);
        $link = sanitize_input($conn, $_POST['link_meeting']);
        $peserta = isset($_POST['peserta']) ? implode(',', $_POST['peserta']) : '';
        $id_user = $_SESSION['user_id'];

        $stmt = $conn->prepare("INSERT INTO jadwal_rapat (judul_rapat, deskripsi, tanggal_rapat, jam_rapat, lokasi, link_meeting, id_penyelenggara, target_peserta) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssis", $judul, $deskripsi, $tanggal, $jam, $lokasi, $link, $id_user, $peserta);
        if ($stmt->execute()) {
            redirect('index.php?page=jadwal_rapat&status=rapat_sukses');
        } else {
            redirect('index.php?page=jadwal_rapat&error=db_gagal');
        }
    }

    if ($page_action === 'jadwal_rapat' && isset($_POST['hapus_rapat'])) {
        check_role(['bem', 'bpm']);
        $id = intval($_POST['id_rapat']);
        $stmt = $conn->prepare("DELETE FROM jadwal_rapat WHERE id_rapat = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        redirect('index.php?page=jadwal_rapat&status=hapus_sukses');
    }
}
// === PENAMBAHAN BARU: LOGIKA UNTUK INPUT NOMOR SURAT ===
// PERBAIKAN: Logika ini harus di dalam blok POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $page_action === 'input_nomor_surat') {
    check_role(['bkh']); // Hanya BKKH yang bisa melakukan ini
    
    $id_pengajuan = isset($_POST['id_pengajuan']) ? intval($_POST['id_pengajuan']) : 0;
    $nomor_surat = sanitize_input($conn, $_POST['nomor_surat']);

    if ($id_pengajuan > 0 && !empty($nomor_surat)) {
        $stmt = $conn->prepare("UPDATE pengajuan SET nomor_surat = ? WHERE id_pengajuan = ?");
        $stmt->bind_param("si", $nomor_surat, $id_pengajuan);
        if ($stmt->execute()) {
            redirect('index.php?page=arsip_surat&status=nomor_sukses');
        } else {
            redirect('index.php?page=arsip_surat&error=nomor_gagal');
        }
    } else {
        redirect('index.php?page=arsip_surat&error=form_kosong');
    }
}

// =================================================================
// PETA HALAMAN DAN HAK AKSES (ROUTING)
// =================================================================
$page_map = [
    'login'            => ['file' => 'login.php', 'roles' => []],
    'logout'           => ['file' => 'logout.php', 'roles' => []],
    'dashboard'        => ['file' => 'roles/ormawa/dashboard.php', 'roles' => ['ormawa', 'bem', 'bpm', 'bkh', 'wr3', 'bendahara', 'sarpras', 'sarpras_barang']],
    'tambah'           => ['file' => 'roles/ormawa/tambah_pengajuan.php', 'roles' => ['ormawa', 'bem', 'bpm']],
    'edit'             => ['file' => 'roles/ormawa/edit.php', 'roles' => ['ormawa', 'bem', 'bpm']],
    'riwayat'          => ['file' => 'roles/ormawa/riwayat.php', 'roles' => ['ormawa', 'bem', 'bpm', 'bkh', 'bendahara']],
    'upload_lpj'       => ['file' => 'roles/ormawa/upload_lpj.php', 'roles' => ['ormawa', 'bem', 'bpm']],
    'revisi_lpj'       => ['file' => 'roles/ormawa/upload_lpj.php', 'roles' => ['ormawa', 'bem', 'bpm']],
    'arsip_lpj'        => ['file' => 'roles/ormawa/arsip_lpj.php', 'roles' => ['ormawa', 'bem', 'bpm']],
    'arsip_digital'    => ['file' => 'roles/ormawa/arsip_digital.php', 'roles' => ['ormawa', 'bem', 'bpm', 'bkh', 'wr3', 'bendahara']],
    'peminjaman_tempat'=> ['file' => 'roles/ormawa/peminjaman_tempat.php', 'roles' => ['ormawa', 'bem', 'bpm']],
    'buat_proposal'    => ['file' => 'roles/ormawa/buat_proposal.php', 'roles' => ['ormawa', 'bem', 'bpm']],
    'arsip_proposal'   => ['file' => 'roles/ormawa/arsip_proposal.php', 'roles' => ['ormawa', 'bem', 'bpm']],
    'edit_proposal'    => ['file' => 'roles/ormawa/edit_proposal.php', 'roles' => ['ormawa', 'bem', 'bpm']],
    'buat_surat_lain'   => ['file' => 'roles/ormawa/buat_surat_lain.php', 'roles' => ['ormawa', 'bem', 'bpm']],
    'arsip_surat_lain'  => ['file' => 'roles/ormawa/arsip_surat_lain.php', 'roles' => ['ormawa', 'bem', 'bpm']],
    'view_surat_lain'   => ['file' => 'roles/ormawa/view_surat_lain.php', 'roles' => ['ormawa', 'bem', 'bpm', 'bkh', 'wr3', 'bendahara']],
    'buat_lpj_otomatis' => ['file' => 'roles/ormawa/buat_lpj_otomatis.php', 'roles' => ['ormawa', 'bem', 'bpm']],
    'arsip_lpj_otomatis'=> ['file' => 'roles/ormawa/arsip_lpj_otomatis.php', 'roles' => ['ormawa', 'bem', 'bpm']],
    'view_lpj_otomatis' => ['file' => 'roles/ormawa/view_lpj_otomatis.php', 'roles' => ['ormawa', 'bem', 'bpm', 'bkh', 'wr3', 'bendahara']],
    'view_peminjaman'   => ['file' => 'roles/ormawa/view_peminjaman_cetak.php', 'roles' => ['ormawa', 'bem', 'bpm', 'bkh', 'wr3', 'bendahara']],
    'view_proposal'    => ['file' => 'roles/ormawa/view_proposal_otomatis.php', 'roles' => ['ormawa', 'bem', 'bpm', 'bkh', 'wr3', 'bendahara']],
    'detail'           => ['file' => 'roles/ormawa/detail.php', 'roles' => ['ormawa', 'bem', 'bpm', 'bkh', 'wr3', 'bendahara']],
    'cetak_surat'      => ['file' => 'roles/ormawa/cetak_surat.php', 'roles' => ['ormawa', 'bem', 'bpm', 'bkh', 'bendahara']],
    'verify_page'      => ['file' => 'verify_page.php', 'roles' => []],
    'surat_balasan'    => ['file' => 'roles/ormawa/cetak_surat.php', 'roles' => []],
    'verifikasi'       => ['file' => 'roles/verifikator/verifikasi.php', 'roles' => ['bem', 'bpm', 'bkh', 'wr3']],
    'verifikasi_lpj'   => ['file' => 'roles/verifikator/verifikasi_lpj.php', 'roles' => ['bkh', 'wr3']],
    'verifikasi_tempat'=> ['file' => 'roles/verifikator/verifikasi_tempat.php', 'roles' => ['bkh']],
    'ajukan_pencairan' => ['file' => 'roles/verifikator/ajukan_pencairan.php', 'roles' => ['bkh']],
    'arsip_surat'      => ['file' => 'roles/verifikator/arsip_surat.php', 'roles' => ['bkh']],
    'manage_users'     => ['file' => 'roles/admin/manage_users.php', 'roles' => ['bkh']],
    'hapus_user'       => ['file' => 'roles/admin/hapus_user.php', 'roles' => ['bkh']],
    'manage_saldo'     => ['file' => 'roles/admin/manage_saldo.php', 'roles' => ['bkh', 'wr3']],
    'tambah_user'      => ['file' => 'roles/admin/tambah_user.php', 'roles' => ['bkh']],
    'edit_user'        => ['file' => 'roles/admin/edit_user.php', 'roles' => ['bkh']],
    'atur_saldo'       => ['file' => 'roles/admin/atur_saldo.php', 'roles' => ['bkh']],
    'proses'           => ['file' => 'roles/bendahara/proses.php', 'roles' => ['bendahara']],
    'profil'           => ['file' => 'roles/profil.php', 'roles' => ['ormawa','bpm','bem','bkh','wr3','bendahara','admin','sarpras','sarpras_barang']],
    'atur_sistem'      => ['file' => 'roles/admin/atur_sistem.php', 'roles' => ['bkh']],
    'input_nomor_surat'  => ['file' => null, 'roles' => ['bkh']],
    'peminjaman_barang' => ['file' => 'roles/ormawa/peminjaman_barang.php', 'roles' => ['ormawa', 'bem', 'bpm']],
    'sarpras_verifikasi_ruangan' => ['file' => 'roles/sarpras/verifikasi_ruangan.php', 'roles' => ['sarpras']],
    'sarpras_verifikasi_barang'  => ['file' => 'roles/sarpras/verifikasi_barang.php', 'roles' => ['sarpras_barang']],
    'manage_barang'     => ['file' => 'roles/sarpras/manage_barang.php', 'roles' => ['sarpras_barang']],
    'verifikasi_barang_bkkh' => ['file' => 'roles/verifikator/verifikasi_barang_bkkh.php', 'roles' => ['bkh']],
    'manage_regulasi'   => ['file' => 'roles/verifikator/manage_regulasi.php', 'roles' => ['bpm']],
    'pusat_informasi'   => ['file' => 'roles/ormawa/pusat_informasi.php', 'roles' => ['ormawa', 'bem', 'bpm', 'bkh', 'wr3', 'bendahara']],
    'jadwal_rapat'      => ['file' => 'roles/ormawa/jadwal_rapat.php', 'roles' => ['ormawa', 'bem', 'bpm', 'bkh', 'wr3', 'bendahara']],
    'aspirasi'          => ['file' => 'aspirasi_publik.php', 'roles' => []],
    'manage_aspirasi'   => ['file' => 'roles/verifikator/manage_aspirasi.php', 'roles' => ['bpm']],
    'buat_surat_peringatan' => ['file' => 'roles/verifikator/buat_surat_peringatan.php', 'roles' => ['bpm', 'bkh']],
];

$dashboard_map = [
    'ormawa'      => 'roles/ormawa/dashboard.php',
    'bem'         => 'roles/verifikator/dashboard.php',
    'bpm'         => 'roles/verifikator/dashboard.php',
    'bkh'         => 'roles/verifikator/dashboard.php',
    'wr3'         => 'roles/verifikator/dashboard.php',
    'bendahara'   => 'roles/bendahara/dashboard.php',
    'sarpras'     => 'roles/sarpras/dashboard.php',
    'sarpras_barang'=> 'roles/sarpras/dashboard.php',
];

$page = $_GET['page'] ?? 'dashboard';
$page_config = $page_map[$page] ?? null;

if (!$page_config) {
    http_response_code(404);
    die("<h1>404 - Halaman Tidak Ditemukan</h1><p>Halaman '<b>" . htmlspecialchars($page) . "</b>' belum terdaftar di router index.php.</p>");
}

$allowed_roles = $page_config['roles'];
$is_public = empty($allowed_roles);
$user_role = $_SESSION['user_role'] ?? null;

if (!$is_public) {
    check_login();
    check_role($allowed_roles);
}

$content_file = ($page === 'dashboard' && isset($dashboard_map[$user_role])) ? $dashboard_map[$user_role] : $page_config['file'];
$is_standalone = in_array($page, ['login', 'logout', 'cetak_surat', 'surat_balasan','verify_page', 'aspirasi', 'view_surat_lain', 'view_proposal', 'view_peminjaman']);

// =================================================================
// RENDER VIEW
// =================================================================
if (!$is_standalone) {
    include 'templates/header.php';
    include 'templates/sidebar.php';
    echo '<div class="main-content-inner">';

    echo '
    <div aria-live="polite" aria-atomic="true" class="position-relative">
        <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1100">
        </div>
    </div>
    ';

    echo '
    <script>
    function showToast(message, type) {
        const toastContainer = document.querySelector(".toast-container");
        if (!toastContainer) return;

        const icon = type === "success" ? "bi-check-circle-fill" : "bi-exclamation-triangle-fill";
        const headerClass = type === "success" ? "bg-success" : "bg-danger";
        const toastId = "toast-" + Math.random().toString(36).substr(2, 9);

        const toastHTML = `
            <div id="${toastId}" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header ${headerClass} text-white">
                    <i class="bi ${icon} me-2"></i>
                    <strong class="me-auto">Notifikasi Sistem</strong>
                    <small>Baru saja</small>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                    ${message}
                </div>
            </div>
        `;

        toastContainer.insertAdjacentHTML("beforeend", toastHTML);
        
        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement, {
            delay: 5000 
        });
        
        toastElement.addEventListener("hidden.bs.toast", function () {
            toastElement.remove();
        });

        toast.show();
    }
    </script>
    ';


    // Penanganan pesan terpusat dan lebih rapi
    $message = '';
    $alert_type = '';

    // Prioritaskan pesan error
    if (isset($_GET['error'])) {
        $alert_type = 'danger';
        $error = $_GET['error'];
        switch ($error) {
            case 'file_kosong':
                $message = 'Gagal mengajukan pengajuan. File proposal tidak ditemukan.';
                break;
            case 'bukan_pdf':
                $message = 'Gagal mengajukan pengajuan. File harus berformat PDF.';
                break;
            case 'bukan_gambar':
                $message = 'Gagal memperbarui profil. File harus berformat JPG atau PNG.';
                break;
            // +++ PENAMBAHAN BARU: PESAN ERROR UNTUK LOGO +++
            case 'logo_bukan_gambar':
                $message = 'Gagal memperbarui logo. File yang diupload harus berupa gambar.';
                break;
            case 'logo_terlalu_besar':
                $message = 'Gagal memperbarui logo. Ukuran file tidak boleh melebihi 1MB.';
                break;
            case 'logo_format_salah':
                $message = 'Gagal memperbarui logo. Hanya format JPG, PNG, JPEG, GIF, & SVG yang diizinkan.';
                break;
            case 'file_terlalu_besar':
                $message = 'Gagal memperbarui profil. Ukuran file tidak boleh melebihi 2MB.';
                break;
            case 'upload_gagal':
                $message = 'Gagal mengunggah file. Silakan coba lagi.';
                break;
            case 'db_gagal':
            case 'update_gagal':
            case 'toggle_gagal':
            case 'gagal_simpan':
                $message = 'Gagal menyimpan data ke database. Silakan coba lagi.';
                break;
            case 'edit_disallowed':
                $message = 'Pengajuan hanya bisa direvisi jika berstatus Ditolak.';
                break;
            case 'unauthorized':
                $message = 'Anda tidak memiliki hak akses untuk aksi ini.';
                break;
            case 'status_salah':
            case 'status_tidak_sesuai':
                $message = 'Status pengajuan tidak sesuai untuk aksi ini.';
                break;
            case 'saldo_tidak_cukup':
                $message = 'Dana yang Anda ajukan melebihi sisa saldo Anda.';
                break;
            case 'invalid_id':
                $message = 'ID pengajuan tidak valid. Aksi dibatalkan.';
                break;
            case 'db_prepare_gagal':
                $message = 'Terjadi kesalahan pada persiapan query database.';
                break;
            case 'form_kosong':
                $message = 'Mohon lengkapi semua field pada formulir.';
                break;
            case 'username_duplikat':
                $message = 'Username sudah digunakan, silakan gunakan username lain.';
                break;
            default:
                $message = 'Terjadi kesalahan yang tidak diketahui. Silakan coba lagi.';
                break;
        }
    // Jika tidak ada error, baru cek pesan sukses
    } elseif (isset($_GET['status']) || isset($_GET['success'])) {
        $alert_type = 'success';
        $status_key = $_GET['status'] ?? $_GET['success'];
         switch ($status_key) {
            case 'tambah_sukses':
                $message = 'Pengajuan berhasil ditambahkan. Silakan tunggu proses verifikasi.';
                break;
            case 'edit_sukses':
                $message = 'Pengajuan berhasil direvisi dan diajukan kembali.';
                break;
            case 'cair_sukses': // Dari ajukan_pencairan.php versi lama
            case 'bendahara_sukses': // Dari ajukan_pencairan.php versi baru
                $message = 'Proposal berhasil diteruskan ke Bendahara untuk proses pencairan.';
                break;
            case 'verifikasi_sukses':
                $message = 'Verifikasi berhasil disimpan.';
                break;
            // --- PERBAIKAN NOTIFIKASI ---
            case 'update_sukses':
                $message = 'Profil berhasil diperbarui.';
                break;
            case 'sukses': // Ini adalah status dari atur_sistem.php
                $message = 'Pengaturan sistem telah berhasil diperbarui.';
                break;
            // +++ PENAMBAHAN BARU: PESAN SUKSES UNTUK LOGO +++
            case 'logo_update_sukses':
                $message = 'Logo sistem berhasil diperbarui.';
                break;
            case 'saldo_sukses':
                $message = 'Saldo pengguna berhasil diperbarui.';
                break;
            case 'toggle_sukses':
                $message = 'Status akun pengguna berhasil diubah.';
                break;
            case 'tambah_user_sukses':
                $message = 'User baru berhasil ditambahkan.';
                break;
            case 'edit_user_sukses':
                $message = 'Data user berhasil diperbarui.';
                break;
            default:
                $message = 'Operasi berhasil.';
                break;
        }
    }

    if (!empty($message)) {
        // Panggil fungsi JavaScript untuk menampilkan toast
        echo "<script>
            // Pastikan DOM sudah siap sebelum menjalankan script
            document.addEventListener('DOMContentLoaded', function() {
                showToast('" . addslashes($message) . "', '" . $alert_type . "');
            });
        </script>";
    }
}

if (file_exists($content_file)) {
    include $content_file;
} else {
    echo "Error: File konten tidak ditemukan di '<b>" . htmlspecialchars($content_file) . "</b>'";
}

if (!$is_standalone) {
    echo '</div>';
    include 'templates/footer.php';
}
?>
