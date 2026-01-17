<?php
/**
 * File: header.php
 * Deskripsi: Bagian atas template HTML dengan layout baru yang elegan dan responsif.
 */
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Pengajuan Keuangan</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- CSS Kustom untuk Layout Baru -->
    <style>
        :root {
            --sidebar-width: 260px;
            --sidebar-bg: #1e293b; /* Warna biru gelap */
            --sidebar-link-color: #cbd5e1;
            --sidebar-link-hover: #334155;
            --sidebar-link-active: #2563eb; /* Warna biru cerah */
        }
        body {
            background-color: #a2abb4ff; /* Warna latar belakang lebih lembut */
            overflow-x: hidden;
        }
        .page-wrapper {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }
        .sidebar {
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            color: white;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 1030; /* Lebih tinggi dari navbar */
            transition: transform 0.3s ease-in-out;
            overflow-y: auto;
        }
        .sidebar.collapsed {
            transform: translateX(calc(-1 * var(--sidebar-width)));
        }
        .sidebar .nav-link {
            color: var(--sidebar-link-color);
            transition: all 0.2s;
            border-radius: 0.375rem;
            margin-bottom: 0.25rem;
        }
        .sidebar .nav-link:hover {
            background: var(--sidebar-link-hover);
            color: #fff;
        }
        .sidebar .nav-link.active {
            background: var(--sidebar-link-active);
            color: #fff;
            font-weight: 600;
        }
        .content-wrapper {
            flex-grow: 1;
            padding-left: var(--sidebar-width);
            transition: padding-left 0.3s ease-in-out;
        }
        .content-wrapper.collapsed {
            padding-left: 0;
        }
        .navbar-custom {
            /* Navbar sekarang sticky/fixed */
            position: sticky;
            top: 0;
            z-index: 1020;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .main-content-inner {
            padding: 24px;
        }

        /* Tampilan Mobile */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(calc(-1 * var(--sidebar-width)));
            }
            .sidebar.toggled {
                transform: translateX(0);
            }
            .content-wrapper {
                padding-left: 0;
            }
            /* Backdrop saat sidebar mobile terbuka */
            .sidebar-backdrop {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                z-index: 1029;
                display: none;
            }
            .sidebar.toggled ~ .sidebar-backdrop {
                display: block;
            }
        }
    </style>
</head>
<body>

