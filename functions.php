<?php
/**
 * File: functions.php
 * Deskripsi: Kumpulan fungsi bantuan untuk aplikasi.
 */

defined('APP_RUNNING') or die('Akses langsung tidak diizinkan.');

/**
 * Memulai output buffering. Harus dipanggil di awal setiap skrip.
 */
function start_output_buffering() {
    ob_start();
}

/**
 * Memulai sesi dengan konfigurasi yang aman.
 */
function initialize_session() {
    if (session_status() === PHP_SESSION_NONE) {
        $lifetime = 60 * 60 * 24; // Sesi bertahan 1 hari
        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        session_start();
    }
}

/**
 * Membersihkan input teks sebelum disimpan ke database.
 */
function sanitize_input($conn, $data) {
    $data = trim($data);
    $data = stripslashes($data);
    return mysqli_real_escape_string($conn, $data);
}

/**
 * Mengarahkan pengguna ke halaman lain di dalam aplikasi.
 */
function redirect($path) {
    // Membersihkan buffer output sebelum melakukan redirect
    ob_end_clean();
    header("Location: " . BASE_URL . '/' . $path);
    exit();
}

/**
 * Memeriksa apakah pengguna sudah login.
 */
function check_login() {
    if (!isset($_SESSION['user_id'])) {
        redirect('login.php');
    }
}

/**
 * Memeriksa apakah peran pengguna diizinkan mengakses halaman.
 */
function check_role($allowed_roles = []) {
    if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
        show_access_denied();
    }
}

/**
 * Menampilkan halaman "Akses Ditolak" secara konsisten.
 */
function show_access_denied() {
    http_response_code(403);
    // Kita panggil header dan footer agar tampilannya tetap konsisten
    include 'templates/header.php';
    include 'templates/sidebar.php';
    echo "<div class='main-content-inner'>";
    echo "<div class='container-fluid px-4'>
            <div class='alert alert-danger mt-4'>
                <h4><i class='bi bi-exclamation-triangle-fill'></i> Akses Ditolak</h4>
                <p>Anda tidak memiliki hak akses untuk melihat halaman ini.</p>
                <a href='index.php?page=dashboard' class='btn btn-danger'>Kembali ke Dashboard</a>
            </div>
          </div>";
    echo "</div>";
    include 'templates/footer.php';
    exit();
}

/**
 * Menambahkan catatan ke tabel riwayat status pengajuan.
 */
function add_history($conn, $id_pengajuan, $id_user, $status, $catatan) {
    $stmt = $conn->prepare(
        "INSERT INTO histori_status (id_pengajuan, id_user, status, catatan) 
         VALUES (?, ?, ?, ?)"
    );
    if ($stmt) {
        $stmt->bind_param("isss", $id_pengajuan, $id_user, $status, $catatan);
        return $stmt->execute();
    }
    return false;
}
?>
