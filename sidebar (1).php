<?php
/**
 * File: sidebar.php
 * Deskripsi: Bagian sidebar navigasi dengan fitur tema terang/redup dan logo sistem dinamis.
 */

// === PERBAIKAN: Mengambil data dari session untuk efisiensi ===
$konfigurasi = $_SESSION['konfigurasi'] ?? [];
$nama_aplikasi = htmlspecialchars($konfigurasi['nama_aplikasi'] ?? 'SI-Keuangan');
$logo_file = $konfigurasi['logo_sistem'] ?? 'default_logo.png';

$logo_path = 'https://placehold.co/200x60/FFFFFF/000000?text=No+Logo';
$potential_path = 'uploads/sistem/' . $logo_file;
if (!empty($logo_file) && file_exists($potential_path)) {
    $logo_path = $potential_path;
}
// === AKHIR PERBAIKAN ===

$currentPage = $_GET['page'] ?? 'dashboard';
$user_role = $_SESSION['user_role'] ?? null;
$user_status = $_SESSION['status_akun'] ?? 'nonaktif';

// === PENAMBAHAN BARU: Mendefinisikan judul halaman di sini agar bisa dipakai di beberapa tempat ===
$page_title_map = [
    'dashboard' => 'Dashboard', 'tambah' => 'Buat Pengajuan', 'riwayat' => 'Riwayat Pengajuan',
    'detail' => 'Detail Pengajuan', 'edit' => 'Revisi Pengajuan', 'upload_lpj' => 'Upload LPJ',
    'revisi_lpj' => 'Revisi LPJ', 'verifikasi' => 'Verifikasi Proposal', 'verifikasi_lpj' => 'Verifikasi LPJ',
    'manage_users' => 'Manajemen Pengguna', 'tambah_user' => 'Tambah Pengguna', 'edit_user' => 'Edit Pengguna',
    'ajukan_pencairan' => 'Ajukan Pencairan', 'arsip_surat' => 'Arsip Surat Balasan',
    'proses' => 'Proses Pencairan Dana', 'manage_saldo' => 'Manajemen Saldo', 'atur_saldo' => 'Atur Saldo Pengguna',
    'profil' => 'Atur Profil', 'atur_sistem' => 'Pengaturan Sistem'
];
$current_title = $page_title_map[$currentPage] ?? '';
?>

<!-- =================================================================
CSS untuk Tema Terang dan Redup (TIDAK ADA PERUBAHAN)
================================================================== -->
<style>
    :root {
        --sidebar-bg: #0d1117;
        --main-bg: #f8f9fa;
        --card-bg: #ffffff;
        --card-border: #dee2e6;
        --text-color: #212529;
        --text-muted: #6c757d;
        --link-color: #0d6efd;
        --bs-success-rgb: 25, 135, 84;
    }
    [data-bs-theme="dark"] {
        --sidebar-bg: #161b22;
        --main-bg: #0d1117;
        --card-bg: #161b22;
        --card-border: #30363d;
        --text-color: #f8f9fa;
        --text-muted: #8b949e;
        --link-color: #3b82f6;
        --bs-success-rgb: 34, 197, 94;
    }
    body { 
        background-color: var(--main-bg);
        color: var(--text-color);
    }
    .sidebar { background-color: var(--sidebar-bg); }
    .content-wrapper, .main-content-inner { background-color: var(--main-bg); }
    .card {
        background-color: var(--card-bg) !important;
        border: 1px solid var(--card-border);
    }
    .navbar-custom {
        background-color: var(--card-bg) !important;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }
    h1, h2, h3, h4, h5, h6, p, strong, small, th, td, li, .form-label, .form-control {
        color: var(--text-color);
    }
    a { color: var(--link-color); }
    .dropdown-item, .breadcrumb-item a, .dropdown-toggle { color: var(--text-color); }
    [data-bs-theme="dark"] .dropdown-toggle,
    [data-bs-theme="dark"] .breadcrumb-item.active,
    [data-bs-theme="dark"] .text-muted,
    [data-bs-theme="dark"] a.text-muted { color: var(--text-muted) !important; }
    [data-bs-theme="dark"] .form-control {
        background-color: #21262d;
        border-color: var(--card-border);
    }
    [data-bs-theme="dark"] .bg-light { background-color: #21262d !important; }
    .dropdown-menu { background-color: var(--card-bg); }
    .table {
        background-color: var(--card-bg) !important;
        color: var(--text-color);
        border-color: var(--card-border);
    }
    [data-bs-theme="dark"] .table-light {
        --bs-table-bg: #21262d;
        --bs-table-border-color: var(--card-border);
    }
    [data-bs-theme="dark"] .table-striped>tbody>tr:nth-of-type(odd)>* {
        --bs-table-accent-bg: rgba(255, 255, 255, 0.03);
    }
    [data-bs-theme="dark"] .page-link {
        background-color: #21262d;
        border-color: var(--card-border);
        color: var(--text-color);
    }
    [data-bs-theme="dark"] .page-item.disabled .page-link {
        background-color: #161b22;
        border-color: var(--card-border);
        color: var(--text-muted);
    }
    [data-bs-theme="dark"] .card-header.bg-white { background-color: var(--card-bg) !important; }
    [data-bs-theme="dark"] .modal-content {
        background-color: var(--card-bg);
        border-color: var(--card-border);
    }
    [data-bs-theme="dark"] .modal-header,
    [data-bs-theme="dark"] .modal-footer { border-color: var(--card-border); }
    [data-bs-theme="dark"] .list-group-item {
        background-color: transparent;
        border-color: var(--card-border);
    }
    [data-bs-theme="dark"] .btn-close { filter: invert(1) grayscale(100%) brightness(200%); }
    .theme-switcher { padding: 0.5rem 1rem; }
    .theme-switcher .form-check-label { color: #c9d1d9; cursor: pointer; }
    .theme-switcher .form-check-input { cursor: pointer; }

    /* === PENYESUAIAN CSS BARU === */
    .sidebar-brand-logo {
        height: 50px; /* Ukuran logo di sidebar */
        max-width: 150px;
        object-fit: contain;
    }
    .sidebar-brand-text {
        font-size: 1.25rem;
        font-weight: 600;
        color: #fff;
    }
    /* Pastikan ada CSS untuk body saat dark-mode aktif */
body.dark-mode {
    background-color: #333;
    color: #eee;
}

/* Warna ikon untuk tema terang (default) */
.theme-icon-current {
    color: orange; /* Atau warna lain yang cocok untuk matahari */
    font-size: 1.1rem; /* Sesuaikan ukuran jika perlu */
}

/* Warna ikon untuk tema gelap */
body.dark-mode .theme-icon-current {
    color: #f0e68c; /* Atau warna lain yang cocok untuk bulan */
}
</style>

<div class="page-wrapper">
    <aside class="sidebar d-flex flex-column p-3" id="sidebar">
        <!-- === PERUBAHAN: Logo dan Nama Aplikasi kembali ke sidebar === -->
        <a href="index.php?page=dashboard" class="d-flex align-items-center mb-3 text-white text-decoration-none">
            <img src="<?php echo htmlspecialchars($logo_path); ?>" alt="Logo Sistem" class="sidebar-brand-logo me-2">
            <span class="sidebar-brand-text"><?php echo $nama_aplikasi; ?></span>
        </a>
        <hr class="text-secondary mt-0 mb-3">

        <ul class="nav nav-pills flex-column mb-auto">
            <!-- LOGIKA MENU ANDA TIDAK DIUBAH SAMA SEKALI -->
            <li class="nav-item">
                <a href="index.php?page=dashboard" class="nav-link text-white <?php echo ($currentPage == 'dashboard') ? 'active' : ''; ?>">
                    <i class="bi bi-speedometer2 me-2"></i> Dashboard
                </a>
            </li>

            <?php if ($user_role): ?>
                <?php if (in_array($user_role, ['ormawa', 'bem', 'bpm'])):
                    if ($user_status === 'aktif'): ?>
                        <li>
                            <a href="index.php?page=tambah" class="nav-link text-white <?php echo ($currentPage == 'tambah') ? 'active' : ''; ?>">
                                <i class="bi bi-plus-circle me-2"></i> Buat Pengajuan
                            </a>
                        </li>
                    <?php endif; ?>
                    <li>
                        <a href="index.php?page=riwayat" class="nav-link text-white <?php echo (in_array($currentPage, ['riwayat', 'detail', 'edit', 'upload_lpj', 'revisi_lpj'])) ? 'active' : ''; ?>">
                            <i class="bi bi-clock-history me-2"></i> Riwayat
                        </a>
                    </li>
                <?php endif; ?>
                
                <?php if (in_array($user_role, ['bem', 'bpm', 'bkh', 'wr3'])): ?>
                <?php endif; ?>

                <?php if (in_array($user_role, ['bkh', 'wr3'])): ?>
                    <li>
        <a href="index.php?page=manage_saldo" class="nav-link text-white <?php echo ($currentPage == 'manage_saldo' || $currentPage == 'atur_saldo') ? 'active' : ''; ?>">
            <i class="bi bi-cash-coin me-2"></i> 
            <?php 
                // Jika user adalah wr3, tampilkan 'Rincian Saldo', selain itu 'Manajemen Saldo'
                echo ($user_role === 'wr3') ? 'Rincian Saldo' : 'Manajemen Saldo'; 
            ?>
        </a>
    </li>
                <?php endif; ?>

                <?php if ($user_role == 'bkh'): 
                    $isManageUserPage = in_array($currentPage, ['manage_users', 'tambah_user', 'edit_user']);
                ?>
                    <li>
                        <a href="index.php?page=manage_users" class="nav-link text-white <?php echo $isManageUserPage ? 'active' : ''; ?>">
                            <i class="bi bi-people-fill me-2"></i> Manajemen User
                        </a>
                    </li>
                    <li>
                        <a href="index.php?page=atur_sistem" class="nav-link text-white <?php echo ($currentPage == 'atur_sistem') ? 'active' : ''; ?>">
                            <i class="bi bi-gear-fill me-2"></i> Manajemen Sistem
                        </a>
                    </li>
                    <li>
                        <a href="index.php?page=arsip_surat" class="nav-link text-white <?php echo ($currentPage == 'arsip_surat') ? 'active' : ''; ?>">
                            <i class="bi bi-archive-fill me-2"></i> Arsip Surat
                        </a>
                    </li>
                <?php endif; ?>
            <?php endif; ?>
        </ul>

        <hr class="text-secondary">
        
    </aside>

    <div class="sidebar-backdrop" id="sidebar-backdrop"></div>
    <div class="content-wrapper" id="content-wrapper">
        <nav class="navbar navbar-expand-lg navbar-light navbar-custom">
            <div class="container-fluid">
                <!-- === PERUBAHAN: Tombol toggle kembali seperti semula === -->
                <button class="btn " type="button" id="sidebar-toggler">
                    <i class="bi bi-list fs-4"></i>
                </button>
    <div class="theme-switcher">
    <input class="d-none" type="checkbox" role="switch" id="theme-toggle">
    <label class="form-check-label" for="theme-toggle" style="cursor: pointer;">
    <i class="bi bi-sun-fill theme-icon-current"></i> </label></div>
                <!-- Judul Halaman ditampilkan kembali di tengah -->
               
                <div class="dropdown ms-auto">
                    <a href="#" class="d-flex align-items-center text-dark text-decoration-none dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <?php
                            $foto_profil_path = !empty($_SESSION['foto_profil']) 
                                              ? 'uploads/profil/' . htmlspecialchars($_SESSION['foto_profil']) 
                                              : 'assets/images/default-avatar.png';
                        ?>
                        <img src="<?php echo $foto_profil_path; ?>" alt="Foto" width="32" height="32" class="rounded-circle me-2" style="object-fit: cover;">
                        <strong><?php echo isset($_SESSION['nama_lengkap']) ? htmlspecialchars($_SESSION['nama_lengkap']) : 'Pengguna'; ?></strong>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end text-small shadow">
                        <li><a class="dropdown-item" href="index.php?page=profil"><i class="bi bi-person-fill me-2"></i>Atur Profil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Sign out</a></li>
                    </ul>
                </div>
            </div>
        </nav>
        <div class="main-content-inner">

<script>
    (() => {
        'use strict'

        const getStoredTheme = () => localStorage.getItem('theme')
        const setStoredTheme = theme => localStorage.setItem('theme', theme)

        const getPreferredTheme = () => {
            const storedTheme = getStoredTheme()
            if (storedTheme) {
                return storedTheme
            }
            return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
        }

        const setTheme = theme => {
            document.documentElement.setAttribute('data-bs-theme', theme)
        }

        setTheme(getPreferredTheme())

        const themeToggle = document.getElementById('theme-toggle')

        const showActiveTheme = (theme) => {
            if (!themeToggle) return;
            themeToggle.checked = theme === 'dark';
        }

        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
            const storedTheme = getStoredTheme()
            if (!storedTheme) {
                setTheme(getPreferredTheme())
            }
        })

        window.addEventListener('DOMContentLoaded', () => {
            showActiveTheme(getPreferredTheme())

            if (themeToggle) {
                themeToggle.addEventListener('change', () => {
                    const theme = themeToggle.checked ? 'dark' : 'light';
                    setStoredTheme(theme);
                    setTheme(theme);
                    showActiveTheme(theme);
                })
            }

            // === PENAMBAHAN BARU: MENGUBAH JUDUL & IKON TAB BROWSER ===
            // Atur judul tab browser
            const appName = '<?php echo addslashes($nama_aplikasi); ?>';
            const pageTitle = '<?php echo addslashes($current_title); ?>';
            if (pageTitle) {
                document.title = `${pageTitle} - ${appName}`;
            } else {
                document.title = appName;
            }

            // Atur ikon (favicon) tab browser
            const faviconUrl = '<?php echo addslashes($logo_path); ?>';
            let favicon = document.querySelector("link[rel*='icon']");
            if (!favicon) {
                favicon = document.createElement('link');
                favicon.rel = 'shortcut icon';
                document.getElementsByTagName('head')[0].appendChild(favicon);
            }
            favicon.type = 'image/png';
            favicon.href = faviconUrl;
            // === AKHIR PENAMBAHAN BARU ===
        })
    })()





    document.addEventListener('DOMContentLoaded', function() {
    const themeToggle = document.getElementById('theme-toggle');
    const themeIcon = document.querySelector('.theme-icon-current');
    const body = document.body;

    // Fungsi untuk mengatur tema dan ikon (logika sudah diperbaiki)
    function setTheme(isDarkMode) {
        if (isDarkMode) {
            // JIKA MODE GELAP...
            body.classList.add('dark-mode');
            themeIcon.classList.remove('bi-moon-fill');
            themeIcon.classList.add('bi-sun-fill'); // TAMPILKAN MATAHARI ☀️
            localStorage.setItem('theme', 'dark');
        } else {
            // JIKA MODE TERANG...
            body.classList.remove('dark-mode');
            themeIcon.classList.remove('bi-sun-fill');
            themeIcon.classList.add('bi-moon-fill'); // TAMPILKAN BULAN 🌙
            localStorage.setItem('theme', 'light');
        }
    }

    // Periksa preferensi tema yang tersimpan saat halaman dimuat
    const savedTheme = localStorage.getItem('theme');
    // Jika tema tersimpan adalah 'dark' ATAU tidak ada tema tersimpan DAN sistem user mode gelap
    const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;

    if (savedTheme === 'dark' || (savedTheme === null && prefersDark)) {
        themeToggle.checked = true;
        setTheme(true);
    } else {
        themeToggle.checked = false;
        setTheme(false);
    }

    // Event listener untuk perubahan checkbox
    themeToggle.addEventListener('change', function() {
        setTheme(this.checked);
    });
});
</script>

