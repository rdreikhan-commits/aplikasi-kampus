-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3308
-- Waktu pembuatan: 13 Okt 2025 pada 05.52
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_pengajuan`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `dana`
--

CREATE TABLE `dana` (
  `id_dana` int(11) NOT NULL,
  `id_pengajuan` int(11) NOT NULL,
  `nominal_cair` decimal(15,2) NOT NULL,
  `tanggal_cair` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `histori_status`
--

CREATE TABLE `histori_status` (
  `id_histori` int(11) NOT NULL,
  `id_pengajuan` int(11) NOT NULL,
  `status` varchar(100) NOT NULL,
  `tanggal_update` timestamp NOT NULL DEFAULT current_timestamp(),
  `id_user` int(11) NOT NULL,
  `catatan` text DEFAULT NULL,
  `unique_code` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `histori_status`
--

INSERT INTO `histori_status` (`id_histori`, `id_pengajuan`, `status`, `tanggal_update`, `id_user`, `catatan`, `unique_code`) VALUES
(515, 165, 'Verifikasi BKKH', '2025-09-30 09:56:42', 7, 'Proposal awal telah diajukan oleh Ormawa.', NULL),
(516, 165, 'Verifikasi WR3', '2025-09-30 09:58:04', 13, 'Proposal telah diverifikasi oleh BKKH. Catatan: -', NULL),
(517, 165, 'Disetujui WR3, Siap Diajukan ke Bendahara', '2025-09-30 09:58:37', 5, 'Proposal disetujui oleh WR3. Catatan: -', NULL),
(518, 165, 'Diajukan ke Bendahara', '2025-09-30 09:59:06', 13, 'Pengajuan pencairan dana telah diajukan ke Bendahara.', NULL),
(519, 165, 'LPJ Diajukan', '2025-09-30 10:03:40', 7, 'LPJ telah diunggah kembali (direvisi) dan diajukan ke BKKH', NULL),
(520, 165, 'Selesai', '2025-09-30 10:04:05', 13, 'LPJ telah diverifikasi dan disetujui oleh BKKH. Proses pengajuan telah selesai.', NULL),
(521, 167, 'Verifikasi BKKH', '2025-09-30 11:56:29', 3, 'Proposal awal telah diajukan oleh Ormawa.', NULL),
(522, 167, 'Verifikasi WR3', '2025-09-30 11:57:02', 13, 'Proposal telah diverifikasi oleh BKKH. Catatan: -', NULL),
(523, 167, 'Disetujui WR3, Siap Diajukan ke Bendahara', '2025-09-30 11:57:22', 5, 'Proposal disetujui oleh WR3. Catatan: -', NULL),
(524, 167, 'Diajukan ke Bendahara', '2025-10-01 07:40:59', 13, 'Pengajuan pencairan dana telah diajukan ke Bendahara.', NULL),
(525, 165, 'LPJ Ditolak BKKH', '2025-10-01 07:41:19', 13, 'LPJ ditolak oleh BKKH. Catatan: Perbaiki Lagi', NULL),
(526, 165, 'LPJ Diajukan', '2025-10-01 07:43:51', 7, 'LPJ telah diunggah kembali (direvisi) dan diajukan ke BKKH', NULL),
(527, 165, 'Selesai', '2025-10-01 07:44:36', 13, 'LPJ telah diverifikasi dan disetujui oleh BKKH. Proses pengajuan telah selesai.', NULL),
(528, 168, 'Verifikasi BKKH', '2025-10-01 07:45:12', 7, 'Proposal awal telah diajukan oleh Ormawa.', NULL),
(529, 168, 'Ditolak BKKH', '2025-10-01 07:45:43', 13, 'Ditolak oleh BKKH. Catatan: Perbaiki Lagi Yah', NULL),
(530, 168, 'Verifikasi BKKH', '2025-10-01 07:46:19', 7, 'Proposal telah direvisi dan diajukan kembali.', NULL),
(531, 168, 'Verifikasi WR3', '2025-10-01 07:46:37', 13, 'Proposal telah diverifikasi oleh BKKH. Catatan: -', NULL),
(532, 168, 'Ditolak WR3', '2025-10-01 07:47:13', 5, 'Ditolak oleh WR3. Catatan: Omekeun', NULL),
(533, 168, 'Verifikasi WR3', '2025-10-01 07:48:06', 7, 'Proposal telah direvisi dan diajukan kembali.', NULL),
(534, 168, 'Disetujui WR3, Siap Diajukan ke Bendahara', '2025-10-01 07:50:08', 5, 'Proposal disetujui oleh WR3. Catatan: -', NULL),
(535, 167, 'LPJ Diajukan', '2025-10-01 07:51:01', 3, 'LPJ telah diunggah kembali (direvisi) dan diajukan ke BKKH', NULL),
(536, 167, 'Selesai', '2025-10-01 07:51:23', 13, 'LPJ telah diverifikasi dan disetujui oleh BKKH. Proses pengajuan telah selesai.', NULL),
(537, 169, 'Verifikasi BKKH', '2025-10-01 07:51:54', 3, 'Proposal awal telah diajukan oleh Ormawa.', NULL),
(538, 169, 'Verifikasi WR3', '2025-10-01 07:53:26', 13, 'Proposal telah diverifikasi oleh BKKH. Catatan: -', NULL),
(539, 169, 'Disetujui WR3, Siap Diajukan ke Bendahara', '2025-10-01 07:53:46', 5, 'Proposal disetujui oleh WR3. Catatan: -', NULL),
(540, 168, 'Diajukan ke Bendahara', '2025-10-01 07:55:24', 13, 'Pengajuan pencairan dana telah diajukan ke Bendahara.', NULL),
(541, 169, 'Diajukan ke Bendahara', '2025-10-01 07:55:27', 13, 'Pengajuan pencairan dana telah diajukan ke Bendahara.', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `konfigurasi`
--

CREATE TABLE `konfigurasi` (
  `id` int(11) NOT NULL,
  `nama_konfigurasi` varchar(100) NOT NULL,
  `nilai_konfigurasi` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `konfigurasi`
--

INSERT INTO `konfigurasi` (`id`, `nama_konfigurasi`, `nilai_konfigurasi`) VALUES
(8, 'nama_aplikasi', 'SI-Keuangan'),
(10, 'logo_sistem', 'logo_68d4becd6eb820.94998886.png'),
(11, 'kop_logo', 'logo_68d4be346fa2a7.73853197.png'),
(12, 'kop_baris1', 'INSTITUT TEKNOLOGI GARUT'),
(13, 'kop_baris2', 'BIRO KETENAGAAN KEMAHASISWAAN DAN HUBUNGAN MASYARAKAT (BKKH)'),
(14, 'kop_baris3', 'Jl. Mayor Syamsu No.1, Jayaraga, Kec. Tarogong Kidul, Kabupaten Garut, Jawa Barat'),
(15, 'kop_baris4', 'Telp. 0262-232773, Email: itg@garut.ac.id');

-- --------------------------------------------------------

--
-- Struktur dari tabel `notifikasi`
--

CREATE TABLE `notifikasi` (
  `id_notif` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `pesan` text NOT NULL,
  `status_baca` enum('belum','sudah') DEFAULT 'belum',
  `waktu` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengajuan`
--

CREATE TABLE `pengajuan` (
  `id_pengajuan` int(11) NOT NULL,
  `id_user_ormawa` int(11) NOT NULL,
  `nama_kegiatan` varchar(255) NOT NULL,
  `dana_diajukan` decimal(15,2) NOT NULL DEFAULT 0.00,
  `judul_kegiatan` varchar(255) NOT NULL,
  `deskripsi_kegiatan` text NOT NULL,
  `tanggal_pengajuan` datetime NOT NULL DEFAULT current_timestamp(),
  `nominal_pengajuan` decimal(15,2) NOT NULL,
  `file_proposal` varchar(255) DEFAULT NULL,
  `file_lpj` varchar(255) DEFAULT NULL,
  `tanggal_upload_lpj` date DEFAULT NULL,
  `status` enum('Draft','Diajukan Ke BEM','Ditolak BEM','Diajukan Ke BPM','Ditolak BPM','Verifikasi BKKH','Ditolak BKKH','Verifikasi WR3','Ditolak WR3','Disetujui WR3, Siap Diajukan ke Bendahara','Diajukan ke Bendahara','Dana Cair','LPJ Diajukan','LPJ Ditolak BKKH','LPJ Diverifikasi','Selesai') NOT NULL DEFAULT 'Draft',
  `catatan_revisi` text DEFAULT NULL,
  `unique_code` varchar(64) DEFAULT NULL,
  `nomor_surat` varchar(100) DEFAULT NULL COMMENT 'Nomor surat resmi yang diinput oleh BKKH',
  `notif_cair_terlihat` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=belum, 1=sudah',
  `tanggal_update` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pengajuan`
--

INSERT INTO `pengajuan` (`id_pengajuan`, `id_user_ormawa`, `nama_kegiatan`, `dana_diajukan`, `judul_kegiatan`, `deskripsi_kegiatan`, `tanggal_pengajuan`, `nominal_pengajuan`, `file_proposal`, `file_lpj`, `tanggal_upload_lpj`, `status`, `catatan_revisi`, `unique_code`, `nomor_surat`, `notif_cair_terlihat`, `tanggal_update`) VALUES
(165, 7, 'isro miraj', 500000.00, '', '', '2025-09-30 00:00:00', 0.00, 'proposal_7_1759226202.pdf', 'lpj_68dcdbb7a5589_7.pdf', NULL, 'Selesai', '', '0db7d5238807f0bc3b8e5056b3b70e426941caa57ba242163476d4f5b9c5570d', '102/SKPP/BKKH/IX.2025', 0, '2025-10-01 07:44:36'),
(167, 3, 'musyma', 500000.00, '', '', '2025-10-11 00:00:00', 0.00, 'proposal_3_1759233389.pdf', 'lpj_68dcdd64f2752_3.pdf', NULL, 'Selesai', '', 'da0e89729264ab9085d7c2d0f6b0d44bcea87f251d565e9d4ed955b546b22505', '44444', 0, '2025-10-01 07:51:23'),
(168, 7, 'Maulid Nabi', 1000000.00, '', '', '2025-10-01 00:00:00', 0.00, 'proposal_7_1759304712.pdf', NULL, NULL, 'Diajukan ke Bendahara', '', NULL, '102/SKPP/BKKH/IX.2025', 0, '2025-10-08 03:55:43'),
(169, 3, 'isro miraj', 1000000.00, '', '', '2025-10-01 00:00:00', 0.00, 'proposal_3_1759305114.pdf', NULL, NULL, 'Diajukan ke Bendahara', '', NULL, '102/SKPP/BKKH/IX.2025', 0, '2025-10-08 03:55:52');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id_user` int(11) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('ormawa','bpm','bem','bkh','wr3','bendahara','admin') NOT NULL,
  `foto_profil` varchar(255) DEFAULT NULL,
  `status_akun` enum('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  `saldo` decimal(15,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id_user`, `nama_lengkap`, `username`, `password`, `role`, `foto_profil`, `status_akun`, `saldo`) VALUES
(3, 'BPM', 'bpm', '$2y$10$0nbVmO07.6lCSs9YmPgcMu9PrN.qgWU.nCnJ8.x9fpxuGGj33WLu2', 'bpm', 'user_3_1758707965.png', 'aktif', 5000000.00),
(5, 'Wakil Rektor 3', 'wr3', '$2y$10$qmV1LVcFuZkPtuPwUDj0UOlNHRMPz90PcWzVdxLf4S7l1Klh5ID.S', 'wr3', NULL, 'aktif', 0.00),
(6, 'Bendahara', 'bendahara', '$2y$10$O1nAQbhoBQwujD1nsU3Lo.fX0Gy0sYA96qbI7KZNfAYwcBtjGtJbO', 'bendahara', 'user_6_1758674399.png', 'aktif', 0.00),
(7, 'BEM', 'bem', '$2y$10$I31DaeVy93wIKsjlP/BfceFWuAbrQAvMbPDlxiWhynsLZIVkwVL8S', 'bem', NULL, 'aktif', 7000000.00),
(8, 'Himatif', 'himatif', '$2y$10$GNJyZdalD1Xy7oQnxsDvGu0V0cTpHdSOuGaD.kYnSSOCKQW9V/YY2', 'ormawa', 'user_8_1758723472.jpg', 'aktif', 3000000.00),
(13, 'Ncep Jainul Hayat', 'bkkh', '$2y$10$PmGf0e0N8MCVNk2WQijx9OISW.NHmaL2IGSZ0wpw5I.6HKf03rUDm', 'bkh', 'user_13_1759232167.png', 'aktif', 0.00),


--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `dana`
--
ALTER TABLE `dana`
  ADD PRIMARY KEY (`id_dana`),
  ADD KEY `id_pengajuan` (`id_pengajuan`);

--
-- Indeks untuk tabel `histori_status`
--
ALTER TABLE `histori_status`
  ADD PRIMARY KEY (`id_histori`),
  ADD KEY `id_pengajuan` (`id_pengajuan`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `idx_id_pengajuan` (`id_pengajuan`);

--
-- Indeks untuk tabel `konfigurasi`
--
ALTER TABLE `konfigurasi`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nama_konfigurasi` (`nama_konfigurasi`);

--
-- Indeks untuk tabel `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD PRIMARY KEY (`id_notif`),
  ADD KEY `id_user` (`id_user`);

--
-- Indeks untuk tabel `pengajuan`
--
ALTER TABLE `pengajuan`
  ADD PRIMARY KEY (`id_pengajuan`),
  ADD KEY `id_user_ormawa` (`id_user_ormawa`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `dana`
--
ALTER TABLE `dana`
  MODIFY `id_dana` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `histori_status`
--
ALTER TABLE `histori_status`
  MODIFY `id_histori` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=542;

--
-- AUTO_INCREMENT untuk tabel `konfigurasi`
--
ALTER TABLE `konfigurasi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT untuk tabel `notifikasi`
--
ALTER TABLE `notifikasi`
  MODIFY `id_notif` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `pengajuan`
--
ALTER TABLE `pengajuan`
  MODIFY `id_pengajuan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=170;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `dana`
--
ALTER TABLE `dana`
  ADD CONSTRAINT `dana_ibfk_1` FOREIGN KEY (`id_pengajuan`) REFERENCES `pengajuan` (`id_pengajuan`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `histori_status`
--
ALTER TABLE `histori_status`
  ADD CONSTRAINT `fk_pengajuan_history` FOREIGN KEY (`id_pengajuan`) REFERENCES `pengajuan` (`id_pengajuan`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD CONSTRAINT `notifikasi_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `pengajuan`
--
ALTER TABLE `pengajuan`
  ADD CONSTRAINT `pengajuan_ibfk_1` FOREIGN KEY (`id_user_ormawa`) REFERENCES `users` (`id_user`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
