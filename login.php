<?php
/**
 * File: login.php
 * Deskripsi: Halaman login pengguna yang aman dan lengkap dengan logo dinamis.
 */

// === PERBAIKAN: Memuat file konfigurasi agar koneksi DB tersedia ===
require_once 'config.php';
require_once 'functions.php';
// === AKHIR PERBAIKAN ===

// --- BLOK PHP INI TIDAK DIUBAH, HANYA DIBERSIHKAN DARI ERROR ---
if (isset($_SESSION['user_id'])) {
    redirect('index.php?page=dashboard');
}

// === PENAMBAHAN BARU: LOGIKA UNTUK MENGAMBIL LOGO & NAMA APLIKASI ===
$nama_aplikasi = 'SI-Keuangan'; // Fallback nama
$logo_path = 'https://placehold.co/200x60/FFFFFF/000000?text=SI-Keuangan'; // Fallback logo

$result_konfig = $conn->query("SELECT nama_konfigurasi, nilai_konfigurasi FROM konfigurasi WHERE nama_konfigurasi IN ('logo_sistem', 'nama_aplikasi')");
if ($result_konfig) {
    while($row = $result_konfig->fetch_assoc()) {
        if ($row['nama_konfigurasi'] == 'nama_aplikasi') {
            $nama_aplikasi = $row['nilai_konfigurasi'];
        }
        if ($row['nama_konfigurasi'] == 'logo_sistem') {
            $potential_path = 'uploads/sistem/' . $row['nilai_konfigurasi'];
            if (!empty($row['nilai_konfigurasi']) && file_exists($potential_path)) {
                $logo_path = $potential_path;
            }
        }
    }
}
// === AKHIR PENAMBAHAN BARU ===


$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize_input($conn, $_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "Username dan password wajib diisi!";
    } else {
        $stmt = $conn->prepare("SELECT id_user, nama_lengkap, password, role, status_akun, foto_profil FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id_user'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['status_akun'] = $user['status_akun'];
                $_SESSION['foto_profil'] = $user['foto_profil']; 
                
                // === PERBAIKAN: Simpan semua konfigurasi ke session untuk efisiensi ===
                $konfigurasi_ses = [];
                $result_konfig_ses = $conn->query("SELECT nama_konfigurasi, nilai_konfigurasi FROM konfigurasi");
                if ($result_konfig_ses) {
                    while ($row_ses = $result_konfig_ses->fetch_assoc()) {
                        $konfigurasi_ses[$row_ses['nama_konfigurasi']] = $row_ses['nilai_konfigurasi'];
                    }
                }
                $_SESSION['konfigurasi'] = $konfigurasi_ses;
                // === AKHIR PERBAIKAN ===
                
                redirect('index.php?page=dashboard');
            } else {
                $error = "Username atau password yang Anda masukkan salah!";
            }
        } else {
            $error = "Username atau password yang Anda masukkan salah!";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- PERUBAHAN: Judul halaman dinamis -->
    <title>Login - <?php echo htmlspecialchars($nama_aplikasi); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #111827; /* Dark background */
            background-image:
                linear-gradient(rgba(17, 24, 39, 0.95), rgba(17, 24, 39, 0.95)),
                url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100' viewBox='0 0 100 100'%3E%3Cg fill='%231f2937' fill-opacity='0.4'%3E%3Crect x='0' y='0' width='10' height='10'/%3E%3C/g%3E%3C/svg%3E");
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 1.5rem;
            color: #d1d5db;
        }

        /* === PENAMBAHAN BARU: Kontainer utama untuk mengatur tata letak vertikal === */
        .main-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
        }

        /* === PENAMBAHAN BARU: Styling untuk judul di atas kartu login === */
        .page-title {
            text-align: center;
            margin-bottom: 2rem;
            color: #86c6f0ff;
        }
        .page-title h1 {
            font-size: 1.5rem;
            font-weight: 600;
        }
        .page-title h2 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #9ca3af;
        }
        /* === AKHIR PENAMBAHAN === */

        .login-card {
            width: 100%;
            max-width: 450px;
            background: rgba(31, 41, 55, 0.5);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-radius: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 3rem;
            animation: fadeIn 0.8s ease-out;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.75);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }

        .login-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        .login-header img {
            height: 100px; /* Menyesuaikan tinggi agar lebih proporsional */
            max-width: 270px; /* Batas lebar maksimum */
            object-fit: contain; /* Pastikan gambar tidak terdistorsi */
            margin-bottom: 1.5rem;
        }
        .login-header h5 {
            color: #2563eb;
            font-weight: 700;
            font-size: 1.8rem;
        }
        .login-header p {
            color: #86c6f0ff;
        }
        .br{
            color: yellow;
        }

        .form-group {
            position: relative;
            margin-bottom: 1.5rem;
        }
        .form-control {
            background-color: rgba(55, 65, 81, 0.5);
            border: 1px solid #4b5563;
            border-radius: 0.75rem;
            height: 55px;
            padding-left: 3.5rem;
            color: #fff;
            font-size: 1rem;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        .form-control::placeholder {
            color: #9ca3af;
        }
        .form-control:focus {
            background-color: rgba(55, 65, 81, 0.7);
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
            outline: none;
            color: #fff;
        }
        
        .input-icon {
            position: absolute;
            left: 1.25rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 1.25rem;
            pointer-events: none;
        }
        .toggle-password {
            position: absolute;
            right: 1.25rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #9ca3af;
            cursor: pointer;
            padding: 0;
        }

        .btn-login {
            background: #2563eb;
            border: none;
            border-radius: 0.75rem;
            padding: 0.9rem;
            font-size: 1.1rem;
            font-weight: 600;
            color: white;
            transition: all 0.3s ease;
            width: 100%;
        }
        .btn-login:hover {
            background-color: #3b82f6;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(59, 130, 246, 0.2);
        }
        
        .alert-custom {
            background-color: rgba(239, 68, 68, 0.1);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 0.75rem;
        }
        img{
            border-radius: 8px;
        }

        @media (max-width: 576px) {
            .login-card {
                padding: 2rem;
            }
            /* === PENAMBAHAN BARU: Ukuran font judul lebih kecil di mobile === */
            .page-title h1 {
                font-size: 1.2rem;
            }
            .page-title h2 {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    
    <!-- === PERUBAHAN STRUKTUR: Membungkus semua konten dalam .main-container === -->
    <div class="main-container">
        <!-- === PERUBAHAN: Memindahkan judul ke sini dengan class baru === -->
        <div class="page-title">
            <h1>Biro Ketenagaan Kemahasiswaan<br>dan Hubungan Masyarakat</h1>
            <h2>Institut Teknologi Garut</h2> <!-- Ejaan diperbaiki -->
        </div>

        <div class="login-card">
            <div class="login-header">
                <!-- === PERUBAHAN: MENAMPILKAN LOGO DINAMIS DARI DATABASE === -->
                
                <img src="<?php echo htmlspecialchars($logo_path); ?>" alt="Logo Sistem">
                <!-- === AKHIR PERUBAHAN === -->
                <h5><?php echo htmlspecialchars($nama_aplikasi); ?></h5>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-custom text-center"><?php echo $error; ?></div>
            <?php endif; ?>

            <form action="index.php?page=login" method="POST">
                <div class="form-group">
                    <i class="bi bi-person input-icon"></i>
                    <input type="text" class="form-control" id="username" name="username" placeholder="Username" required>
                </div>
                <div class="form-group">
                    <i class="bi bi-lock input-icon"></i>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                    <button type="button" class="toggle-password" onclick="togglePassword()">
                        <i class="bi bi-eye" id="toggleIcon"></i>
                    </button>
                </div>
                <button type="submit" class="btn btn-login mt-3">Masuk</button>
                <a href="panduan.php" class="text-left text-secondary small">Panduan Pengajuan </a>
            </form>
        </div>
      
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById("password");
            const toggleIcon = document.getElementById("toggleIcon");
            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                toggleIcon.classList.remove("bi-eye");
                toggleIcon.classList.add("bi-eye-slash");
            } else {
                passwordInput.type = "password";
                toggleIcon.classList.remove("bi-eye-slash");
                toggleIcon.classList.add("bi-eye");
            }
        }
    </script>
</body>
</html>

