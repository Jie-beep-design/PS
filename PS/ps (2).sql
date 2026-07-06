-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 24, 2026 at 04:23 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ps`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`, `email`) VALUES
(1, 'jiji', '$2y$10$EzqmFj4x/Gilfu.S6dy7je07xGy3cYuofDYGuf0OoDldRY1O3uzrq', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `admin_cabang`
--

CREATE TABLE `admin_cabang` (
  `id` int(11) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `qris_image` varchar(255) DEFAULT NULL,
  `dana_image` varchar(255) DEFAULT NULL,
  `nama_rental` varchar(255) DEFAULT NULL,
  `lokasi` varchar(100) DEFAULT NULL,
  `jam_buka` time DEFAULT '08:00:00',
  `jam_tutup` time DEFAULT '22:00:00',
  `foto_rental` varchar(255) DEFAULT NULL,
  `no_telp` varchar(50) DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `status_toko` enum('Buka','Tutup') DEFAULT 'Buka',
  `keterangan_tutup` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_cabang`
--

INSERT INTO `admin_cabang` (`id`, `email`, `username`, `password`, `qris_image`, `dana_image`, `nama_rental`, `lokasi`, `jam_buka`, `jam_tutup`, `foto_rental`, `no_telp`, `reset_token`, `status_toko`, `keterangan_tutup`) VALUES
(1, 'ji2493169@gmail.com', 'aji sukma', '$2y$10$SixvvDduVsC3lXQ5vh.bnuzxtFkRtXlYyax2TZrTr3Pp5aVsBVDj.', 'uploads/qris_1_1782283910.png', NULL, 'rental winongan', 'Jl. keinci, kec. Winongan, kab. Pasuruan (pas pojok jalan)', '07:00:00', '22:00:00', 'uploads/1779255757_fotorentalku.jpg', '083170109759', NULL, 'Buka', 'sedan renovasi, buka kembali tanggal 21 mei 2026 pukul 08.00'),
(10, 'samsul@gmail.com', 'samsul', '$2y$10$LOIR6qI1NeEsgcagBT/hpOGHto/kguCgezJD/gHOUZcLVXwMhsK6i', NULL, NULL, 'rentalan samsul', 'Jl. mangga, kec. Winongan, kab. Pasuruan (kiri jalan dari arah timur)', '08:00:00', '23:00:00', 'uploads/1780469663_rentalansamsul.webp', '082334567890', NULL, 'Tutup', 'sedan renovasi, buka kembali tanggal 16 mei 2026 pukul 08.00');

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT 0,
  `nama_pelanggan` varchar(255) DEFAULT NULL,
  `no_telp` varchar(50) DEFAULT NULL,
  `console_id` int(11) DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `jam_mulai` time DEFAULT NULL,
  `jam_selesai` time DEFAULT NULL,
  `durasi_tersisa` int(11) DEFAULT 0,
  `status` enum('pending','aktif','pause','selesai') NOT NULL DEFAULT 'pending',
  `status_pembayaran` enum('belum bayar','sudah bayar') DEFAULT 'belum bayar',
  `bukti_pembayaran` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `nama_pelanggan`, `no_telp`, `console_id`, `tanggal`, `jam_mulai`, `jam_selesai`, `durasi_tersisa`, `status`, `status_pembayaran`, `bukti_pembayaran`) VALUES
(1, 0, NULL, NULL, 0, '2026-05-06', '07:30:34', '08:30:34', 0, 'selesai', 'belum bayar', NULL),
(2, 0, NULL, NULL, 1, '2026-05-06', '08:29:00', '10:29:30', 0, 'selesai', 'belum bayar', NULL),
(3, 0, NULL, NULL, 1, '2026-05-06', '08:30:44', '10:30:44', 0, 'selesai', 'belum bayar', NULL),
(4, 0, NULL, NULL, 1, '2026-05-06', '08:31:05', '09:31:05', 3576, 'selesai', 'belum bayar', NULL),
(5, 0, NULL, NULL, 1, '2026-05-06', '08:35:39', '09:35:39', 3595, 'selesai', 'belum bayar', NULL),
(6, 0, NULL, NULL, 1, '2026-05-06', '10:03:51', '12:03:55', 0, 'selesai', 'belum bayar', NULL),
(7, 0, NULL, NULL, 1, '2026-05-06', '10:35:54', '11:35:54', 0, 'selesai', 'belum bayar', NULL),
(8, 0, NULL, NULL, 2, '2026-05-06', '10:39:36', '11:39:36', 0, 'selesai', 'belum bayar', NULL),
(9, 0, NULL, NULL, 1, '2026-05-07', '12:51:04', '13:51:04', 0, 'selesai', 'belum bayar', NULL),
(10, 0, 'sapri lukman', '085231786559', 2, '2026-05-07', '09:00:00', '10:00:00', 1, 'selesai', 'sudah bayar', NULL),
(11, 0, NULL, NULL, 2, '2026-05-07', '14:06:32', '15:06:32', 0, 'selesai', 'belum bayar', NULL),
(12, 0, 'sapri lukman', '085231786559', 1, '2026-05-07', '19:18:00', '22:54:35', 26919, 'selesai', 'sudah bayar', NULL),
(13, 0, NULL, NULL, 1, '2026-05-08', '05:40:27', '06:40:27', 3586, 'selesai', 'belum bayar', NULL),
(14, 0, 'sulaiman', '085231786005', 2, '2026-05-08', '11:08:00', '12:08:00', 1, 'selesai', 'sudah bayar', NULL),
(15, 0, 'sulaiman', '085231786559', 1, '2026-05-08', '07:50:00', '08:50:00', 1, 'selesai', 'sudah bayar', NULL),
(16, 0, 'sulaiman', '085231786559', 1, '2026-05-08', '09:00:00', '10:00:00', 1, 'selesai', 'sudah bayar', NULL),
(17, 0, 'sulaiman', '085231786559', 1, '2026-05-15', '18:00:00', '19:00:00', 1, 'selesai', 'belum bayar', NULL),
(18, 0, 'maskun', '082334567890', 2, '2026-05-15', '17:35:00', '18:35:00', 1, 'selesai', 'sudah bayar', NULL),
(20, 0, 'sukma', '083170109759', 2, '2026-05-25', '12:30:00', '13:30:05', 0, 'selesai', 'sudah bayar', NULL),
(23, 0, 'doni', '083170109759', 1, '2026-06-03', '15:33:00', '16:33:00', 1, 'selesai', 'belum bayar', NULL),
(25, 0, 'sulaiman', '083170109759', 1, '2026-06-22', '15:32:00', '16:32:00', 1, 'selesai', 'belum bayar', NULL),
(27, 0, 'sulaiman', '083170109759', 1, '2026-06-22', '15:52:00', '16:52:00', 1, 'selesai', 'sudah bayar', 'uploads/1782118393_QR_Code_Pembayaran.png');

-- --------------------------------------------------------

--
-- Table structure for table `consoles`
--

CREATE TABLE `consoles` (
  `id` int(11) NOT NULL,
  `admin_cabang_id` int(11) DEFAULT NULL,
  `nama_console` varchar(50) DEFAULT NULL,
  `harga_per_jam` int(11) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `status` enum('tersedia','digunakan','pause') DEFAULT 'tersedia',
  `lokasi` varchar(100) DEFAULT NULL,
  `tipe` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `consoles`
--

INSERT INTO `consoles` (`id`, `admin_cabang_id`, `nama_console`, `harga_per_jam`, `foto`, `status`, `lokasi`, `tipe`) VALUES
(1, 1, 'PS4 TV 1', 5000, 'uploads/1778044466_30c1e9c659e18f743e63cc26b625e551.jpg', 'tersedia', 'Winongan', 'PS4'),
(2, 10, 'PS3 TV 1', 5000, 'uploads/1778055915_f14d1fd7979e034774d75a6e3d3e5dc2.jpg', 'tersedia', 'winongan', 'PS3'),
(3, 1, 'PS5 PRO TV 2', 10000, 'uploads/1778477735_PS5 Pro.webp', 'tersedia', 'Winongan', 'PS5');

-- --------------------------------------------------------

--
-- Table structure for table `laporan_bug`
--

CREATE TABLE `laporan_bug` (
  `id` int(11) NOT NULL,
  `admin_cabang_id` int(11) DEFAULT NULL,
  `kategori_masalah` varchar(100) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `status` enum('Menunggu','Diproses','Selesai') DEFAULT 'Menunggu',
  `tanggal_laporan` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `laporan_bug`
--

INSERT INTO `laporan_bug` (`id`, `admin_cabang_id`, `kategori_masalah`, `deskripsi`, `status`, `tanggal_laporan`) VALUES
(1, 1, 'Masalah Data', 'status consol tidak terupdate otomatis', 'Selesai', '2026-05-20 12:32:58');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `password` varchar(100) NOT NULL,
  `role` enum('admin','user') DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `nama`, `password`, `role`) VALUES
(1, 'alienmars366@gmail.com', 'aji mars', '$2y$10$2uzfpLOG.UScLXIhwf6ByOLO3KLA1CBCks8v9OBoDpM.Hxf0iU1YW', 'user'),
(2, 'samsul@123', 'samsul prime', '$2y$10$JTCNoQmmduES2L9Bd3nCRuFUsZEvMjoPTYhxxe7ESVLymgGTdc9QG', 'user');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admin_cabang`
--
ALTER TABLE `admin_cabang`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `consoles`
--
ALTER TABLE `consoles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `laporan_bug`
--
ALTER TABLE `laporan_bug`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `admin_cabang`
--
ALTER TABLE `admin_cabang`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `consoles`
--
ALTER TABLE `consoles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `laporan_bug`
--
ALTER TABLE `laporan_bug`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
