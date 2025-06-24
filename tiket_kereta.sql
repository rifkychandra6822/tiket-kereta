-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 18, 2025 at 03:49 PM
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
-- Database: `tiket_kereta`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `id_pengguna` int(11) DEFAULT NULL,
  `level_akses` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `detail_pesanan_makanan`
--

CREATE TABLE `detail_pesanan_makanan` (
  `id` int(11) NOT NULL,
  `id_pesanan` int(11) DEFAULT NULL,
  `id_menu` int(11) DEFAULT NULL,
  `jumlah` int(11) DEFAULT NULL,
  `subtotal` double DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `detail_pesanan_makanan`
--

INSERT INTO `detail_pesanan_makanan` (`id`, `id_pesanan`, `id_menu`, `jumlah`, `subtotal`) VALUES
(19, 1, 1, 1, 30000),
(20, 1, 2, 1, 35000),
(21, 2, 1, 1, 30000);

-- --------------------------------------------------------

--
-- Table structure for table `gerbong`
--

CREATE TABLE `gerbong` (
  `id` int(11) NOT NULL,
  `id_kereta` int(11) DEFAULT NULL,
  `nomor_gerbong` varchar(10) DEFAULT NULL,
  `kelas` varchar(50) DEFAULT NULL,
  `tipe` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gerbong`
--

INSERT INTO `gerbong` (`id`, `id_kereta`, `nomor_gerbong`, `kelas`, `tipe`) VALUES
(1, 1, 'A1', 'Eksekutif', 'AC'),
(2, 1, 'A2', 'Eksekutif', 'AC'),
(3, 2, 'B1', 'Bisnis', 'Non-AC'),
(4, 3, 'C1', 'Ekonomi', 'AC'),
(5, 4, 'D1', 'Ekonomi', 'Non-AC'),
(6, 5, 'E1', 'Eksekutif', 'AC'),
(7, 1, 'A1', 'Eksekutif', 'AC'),
(8, 1, 'A2', 'Eksekutif', 'AC'),
(9, 2, 'B1', 'Bisnis', 'Non-AC'),
(10, 3, 'C1', 'Ekonomi', 'AC'),
(11, 4, 'D1', 'Ekonomi', 'Non-AC'),
(12, 5, 'E1', 'Eksekutif', 'AC'),
(13, 1, 'A1', 'Eksekutif', 'AC'),
(14, 1, 'A2', 'Eksekutif', 'AC'),
(15, 2, 'B1', 'Bisnis', 'Non-AC'),
(16, 3, 'C1', 'Ekonomi', 'AC'),
(17, 4, 'D1', 'Ekonomi', 'Non-AC'),
(18, 5, 'E1', 'Eksekutif', 'AC'),
(19, 1, 'A1', 'Eksekutif', 'AC'),
(20, 1, 'A2', 'Eksekutif', 'AC'),
(21, 2, 'B1', 'Bisnis', 'Non-AC'),
(22, 3, 'C1', 'Ekonomi', 'AC'),
(23, 4, 'D1', 'Ekonomi', 'Non-AC'),
(24, 5, 'E1', 'Eksekutif', 'AC'),
(25, 1, 'A1', 'Eksekutif', 'AC'),
(26, 1, 'A2', 'Eksekutif', 'AC'),
(27, 2, 'B1', 'Bisnis', 'Non-AC'),
(28, 3, 'C1', 'Ekonomi', 'AC'),
(29, 4, 'D1', 'Ekonomi', 'Non-AC'),
(30, 5, 'E1', 'Eksekutif', 'AC'),
(31, 1, 'A1', 'Eksekutif', 'AC'),
(32, 1, 'A2', 'Eksekutif', 'AC'),
(33, 2, 'B1', 'Bisnis', 'Non-AC'),
(34, 3, 'C1', 'Ekonomi', 'AC'),
(35, 4, 'D1', 'Ekonomi', 'Non-AC'),
(36, 5, 'E1', 'Eksekutif', 'AC'),
(37, 1, 'A1', 'Eksekutif', 'AC'),
(38, 2, 'B1', 'Bisnis', 'AC'),
(39, 3, 'C1', 'Ekonomi', 'Non-AC');

-- --------------------------------------------------------

--
-- Table structure for table `jadwal_kereta`
--

CREATE TABLE `jadwal_kereta` (
  `id` int(11) NOT NULL,
  `id_kereta` int(11) DEFAULT NULL,
  `stasiun_awal` varchar(100) DEFAULT NULL,
  `stasiun_tujuan` varchar(100) DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `waktu_berangkat` time DEFAULT NULL,
  `waktu_tiba` time DEFAULT NULL,
  `tarif` double DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jadwal_kereta`
--

INSERT INTO `jadwal_kereta` (`id`, `id_kereta`, `stasiun_awal`, `stasiun_tujuan`, `tanggal`, `waktu_berangkat`, `waktu_tiba`, `tarif`, `status`) VALUES
(1, 1, 'Gambir', 'Surabaya Pasar Turi', '2025-07-01', '08:00:00', '16:00:00', 450000, 'Tersedia'),
(2, 2, 'Yogyakarta', 'Jakarta', '2025-07-01', '09:00:00', '15:00:00', 375000, 'Tersedia'),
(3, 3, 'Bandung', 'Solo', '2025-07-02', '07:00:00', '14:00:00', 200000, 'Tersedia'),
(4, 4, 'Semarang', 'Malang', '2025-07-03', '06:30:00', '13:30:00', 220000, 'Tersedia'),
(5, 5, 'Jakarta', 'Blitar', '2025-07-04', '10:00:00', '18:00:00', 480000, 'Tersedia'),
(6, 1, 'Gambir', 'Surabaya Pasar Turi', '2025-07-01', '08:00:00', '16:00:00', 450000, 'Tersedia'),
(7, 2, 'Yogyakarta', 'Jakarta', '2025-07-01', '09:00:00', '15:00:00', 375000, 'Tersedia'),
(8, 3, 'Bandung', 'Solo', '2025-07-02', '07:00:00', '14:00:00', 200000, 'Tersedia'),
(9, 4, 'Semarang', 'Malang', '2025-07-03', '06:30:00', '13:30:00', 220000, 'Tersedia'),
(10, 5, 'Jakarta', 'Blitar', '2025-07-04', '10:00:00', '18:00:00', 480000, 'Tersedia'),
(11, 1, 'Gambir', 'Surabaya Pasar Turi', '2025-07-01', '08:00:00', '16:00:00', 450000, 'Tersedia'),
(12, 2, 'Yogyakarta', 'Jakarta', '2025-07-01', '09:00:00', '15:00:00', 375000, 'Tersedia'),
(13, 3, 'Bandung', 'Solo', '2025-07-02', '07:00:00', '14:00:00', 200000, 'Tersedia'),
(14, 4, 'Semarang', 'Malang', '2025-07-03', '06:30:00', '13:30:00', 220000, 'Tersedia'),
(15, 5, 'Jakarta', 'Blitar', '2025-07-04', '10:00:00', '18:00:00', 480000, 'Tersedia'),
(16, 1, 'Gambir', 'Surabaya Pasar Turi', '2025-07-01', '08:00:00', '16:00:00', 450000, 'Tersedia'),
(17, 2, 'Yogyakarta', 'Jakarta', '2025-07-01', '09:00:00', '15:00:00', 375000, 'Tersedia'),
(18, 3, 'Bandung', 'Solo', '2025-07-02', '07:00:00', '14:00:00', 200000, 'Tersedia'),
(19, 4, 'Semarang', 'Malang', '2025-07-03', '06:30:00', '13:30:00', 220000, 'Tersedia'),
(20, 5, 'Jakarta', 'Blitar', '2025-07-04', '10:00:00', '18:00:00', 480000, 'Tersedia'),
(21, 1, 'Gambir', 'Surabaya', '2025-07-01', '08:00:00', '16:00:00', 450000, 'Tersedia'),
(22, 2, 'Yogyakarta', 'Jakarta', '2025-07-02', '09:00:00', '15:00:00', 375000, 'Tersedia');

-- --------------------------------------------------------

--
-- Table structure for table `kereta`
--

CREATE TABLE `kereta` (
  `id` int(11) NOT NULL,
  `nama_kereta` varchar(100) DEFAULT NULL,
  `jenis_kereta` varchar(50) DEFAULT NULL,
  `kapasitas` int(11) DEFAULT NULL,
  `kelas` varchar(50) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kereta`
--

INSERT INTO `kereta` (`id`, `nama_kereta`, `jenis_kereta`, `kapasitas`, `kelas`, `status`) VALUES
(1, 'Argo Bromo Anggrek', 'Eksekutif', 350, 'Eksekutif', 'Aktif'),
(2, 'Taksaka', 'Bisnis', 300, 'Bisnis', 'Aktif'),
(3, 'Lodaya', 'Ekonomi', 400, 'Ekonomi', 'Aktif'),
(4, 'Senja Utama', 'Ekonomi', 420, 'Ekonomi', 'Aktif'),
(5, 'Gajayana', 'Eksekutif', 380, 'Eksekutif', 'Aktif'),
(6, 'Argo Bromo Anggrek', 'Eksekutif', 350, 'Eksekutif', 'Aktif'),
(7, 'Taksaka', 'Bisnis', 300, 'Bisnis', 'Aktif'),
(8, 'Lodaya', 'Ekonomi', 400, 'Ekonomi', 'Aktif'),
(9, 'Senja Utama', 'Ekonomi', 420, 'Ekonomi', 'Aktif'),
(10, 'Gajayana', 'Eksekutif', 380, 'Eksekutif', 'Aktif'),
(11, 'Argo Bromo Anggrek', 'Eksekutif', 350, 'Eksekutif', 'Aktif'),
(12, 'Taksaka', 'Bisnis', 300, 'Bisnis', 'Aktif'),
(13, 'Lodaya', 'Ekonomi', 400, 'Ekonomi', 'Aktif'),
(14, 'Senja Utama', 'Ekonomi', 420, 'Ekonomi', 'Aktif'),
(15, 'Gajayana', 'Eksekutif', 380, 'Eksekutif', 'Aktif'),
(16, 'Argo Bromo Anggrek', 'Eksekutif', 350, 'Eksekutif', 'Aktif'),
(17, 'Taksaka', 'Bisnis', 300, 'Bisnis', 'Aktif'),
(18, 'Lodaya', 'Ekonomi', 400, 'Ekonomi', 'Aktif'),
(19, 'Senja Utama', 'Ekonomi', 420, 'Ekonomi', 'Aktif'),
(20, 'Gajayana', 'Eksekutif', 380, 'Eksekutif', 'Aktif'),
(21, 'Argo Bromo Anggrek', 'Eksekutif', 350, 'Eksekutif', 'Aktif'),
(22, 'Taksaka', 'Bisnis', 300, 'Bisnis', 'Aktif'),
(23, 'Lodaya', 'Ekonomi', 400, 'Ekonomi', 'Aktif'),
(24, 'Senja Utama', 'Ekonomi', 420, 'Ekonomi', 'Aktif'),
(25, 'Gajayana', 'Eksekutif', 380, 'Eksekutif', 'Aktif'),
(26, 'Argo Bromo Anggrek', 'Eksekutif', 350, 'Eksekutif', 'Aktif'),
(27, 'Taksaka', 'Bisnis', 300, 'Bisnis', 'Aktif'),
(28, 'Lodaya', 'Ekonomi', 400, 'Ekonomi', 'Aktif'),
(29, 'Senja Utama', 'Ekonomi', 420, 'Ekonomi', 'Aktif'),
(30, 'Gajayana', 'Eksekutif', 380, 'Eksekutif', 'Aktif'),
(31, 'Argo Bromo Anggrek', 'Eksekutif', 350, 'Eksekutif', 'Aktif'),
(32, 'Taksaka', 'Bisnis', 300, 'Bisnis', 'Aktif'),
(33, 'Lodaya', 'Ekonomi', 400, 'Ekonomi', 'Aktif'),
(34, 'Senja Utama', 'Ekonomi', 420, 'Ekonomi', 'Aktif'),
(35, 'Gajayana', 'Eksekutif', 380, 'Eksekutif', 'Aktif'),
(36, 'Argo Bromo Anggrek', 'Eksekutif', 350, 'Eksekutif', 'Aktif'),
(37, 'Taksaka', 'Bisnis', 300, 'Bisnis', 'Aktif'),
(38, 'Lodaya', 'Ekonomi', 400, 'Ekonomi', 'Aktif'),
(39, 'Senja Utama', 'Ekonomi', 420, 'Ekonomi', 'Aktif'),
(40, 'Gajayana', 'Eksekutif', 380, 'Eksekutif', 'Aktif'),
(41, 'Argo Bromo Anggrek', 'Eksekutif', 350, 'Eksekutif', 'Aktif'),
(42, 'Taksaka', 'Bisnis', 300, 'Bisnis', 'Aktif'),
(43, 'Lodaya', 'Ekonomi', 400, 'Ekonomi', 'Aktif');

-- --------------------------------------------------------

--
-- Table structure for table `kursi`
--

CREATE TABLE `kursi` (
  `id` int(11) NOT NULL,
  `id_gerbong` int(11) DEFAULT NULL,
  `nomor_kursi` varchar(10) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `kelas` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kursi`
--

INSERT INTO `kursi` (`id`, `id_gerbong`, `nomor_kursi`, `status`, `kelas`) VALUES
(1, 1, '1A', 'Tersedia', 'Eksekutif'),
(2, 1, '1B', 'Tersedia', 'Eksekutif'),
(3, 2, '2A', 'Terisi', 'Eksekutif'),
(4, 3, '3A', 'Tersedia', 'Bisnis'),
(5, 3, '3B', 'Tersedia', 'Bisnis'),
(6, 4, '4A', 'Tersedia', 'Ekonomi'),
(7, 4, '4B', 'Tersedia', 'Ekonomi'),
(8, 5, '5A', 'Tersedia', 'Ekonomi'),
(9, 6, '6A', 'Tersedia', 'Eksekutif'),
(10, 1, '1A', 'Tersedia', 'Eksekutif'),
(11, 1, '1B', 'Tersedia', 'Eksekutif'),
(12, 2, '2A', 'Terisi', 'Eksekutif'),
(13, 3, '3A', 'Tersedia', 'Bisnis'),
(14, 3, '3B', 'Tersedia', 'Bisnis'),
(15, 4, '4A', 'Tersedia', 'Ekonomi'),
(16, 5, '5A', 'Tersedia', 'Ekonomi'),
(17, 6, '6A', 'Tersedia', 'Eksekutif'),
(18, 1, '1A', 'Tersedia', 'Eksekutif'),
(19, 1, '1B', 'Tersedia', 'Eksekutif'),
(20, 2, '2A', 'Terisi', 'Eksekutif'),
(21, 3, '3A', 'Tersedia', 'Bisnis'),
(22, 3, '3B', 'Tersedia', 'Bisnis'),
(23, 4, '4A', 'Tersedia', 'Ekonomi'),
(24, 5, '5A', 'Tersedia', 'Ekonomi'),
(25, 6, '6A', 'Tersedia', 'Eksekutif'),
(26, 1, '1A', 'Tersedia', 'Eksekutif'),
(27, 1, '1B', 'Tersedia', 'Eksekutif'),
(28, 2, '2A', 'Terisi', 'Eksekutif'),
(29, 3, '3A', 'Tersedia', 'Bisnis'),
(30, 3, '3B', 'Tersedia', 'Bisnis'),
(31, 4, '4A', 'Tersedia', 'Ekonomi'),
(32, 5, '5A', 'Tersedia', 'Ekonomi'),
(33, 6, '6A', 'Tersedia', 'Eksekutif'),
(34, 1, '1A', 'Tersedia', 'Eksekutif'),
(35, 1, '1B', 'Tersedia', 'Eksekutif'),
(36, 2, '2A', 'Terisi', 'Eksekutif'),
(37, 3, '3A', 'Tersedia', 'Bisnis'),
(38, 3, '3B', 'Tersedia', 'Bisnis'),
(39, 4, '4A', 'Tersedia', 'Ekonomi'),
(40, 5, '5A', 'Tersedia', 'Ekonomi'),
(41, 6, '6A', 'Tersedia', 'Eksekutif'),
(42, 1, '1A', 'Tersedia', 'Eksekutif'),
(43, 1, '1B', 'Terisi', 'Eksekutif'),
(44, 2, '2A', 'Tersedia', 'Bisnis'),
(45, 2, '2B', 'Terisi', 'Bisnis'),
(46, 3, '3A', 'Tersedia', 'Ekonomi'),
(47, 3, '3B', 'Tersedia', 'Ekonomi');

-- --------------------------------------------------------

--
-- Table structure for table `menu_makanan`
--

CREATE TABLE `menu_makanan` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `harga` double DEFAULT NULL,
  `status` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menu_makanan`
--

INSERT INTO `menu_makanan` (`id`, `nama`, `harga`, `status`) VALUES
(1, 'Nasi Goreng Spesial', 35000, 1),
(2, 'Ayam Bakar', 40000, 1),
(3, 'Air Mineral', 8000, 1),
(4, 'Teh Manis Dingin', 10000, 1),
(5, 'Roti Bakar', 15000, 1),
(6, 'Mie Goreng', 30000, 1),
(7, 'Sate Ayam', 45000, 1),
(8, 'Nasi Uduk', 25000, 1),
(9, 'Nasi Goreng Spesial', 35000, 1),
(10, 'Ayam Bakar', 40000, 1),
(11, 'Air Mineral', 8000, 1),
(12, 'Teh Manis Dingin', 10000, 1),
(13, 'Roti Bakar', 15000, 1),
(14, 'Mie Goreng', 30000, 1),
(15, 'Sate Ayam', 45000, 1),
(16, 'Nasi Uduk', 25000, 1),
(17, 'Nasi Goreng Spesial', 35000, 1),
(18, 'Ayam Bakar', 40000, 1),
(19, 'Air Mineral', 8000, 1),
(20, 'Teh Manis Dingin', 10000, 1),
(21, 'Roti Bakar', 15000, 1),
(22, 'Mie Goreng', 30000, 1),
(23, 'Sate Ayam', 45000, 1),
(24, 'Nasi Uduk', 25000, 1),
(25, 'Nasi Goreng', 30000, 1),
(26, 'Ayam Bakar', 35000, 1),
(27, 'Air Mineral', 5000, 1);

-- --------------------------------------------------------

--
-- Table structure for table `notifikasi`
--

CREATE TABLE `notifikasi` (
  `id` int(11) NOT NULL,
  `id_pengguna` int(11) DEFAULT NULL,
  `judul` varchar(100) DEFAULT NULL,
  `pesan` text DEFAULT NULL,
  `tanggal` datetime DEFAULT NULL,
  `status` tinyint(1) DEFAULT NULL,
  `dari_sistem` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pembayaran`
--

CREATE TABLE `pembayaran` (
  `id` int(11) NOT NULL,
  `id_tiket` int(11) DEFAULT NULL,
  `metode` varchar(50) DEFAULT NULL,
  `jumlah` double DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `refund` tinyint(1) DEFAULT NULL,
  `validasi` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pembayaran`
--

INSERT INTO `pembayaran` (`id`, `id_tiket`, `metode`, `jumlah`, `tanggal`, `status`, `refund`, `validasi`) VALUES
(1, 1, 'Transfer', 450000, '2025-06-10', 'Berhasil', 0, 1),
(2, 2, 'Transfer', 450000, '2025-06-10', 'Berhasil', 0, 1),
(3, 3, 'Transfer', 375000, '2025-06-11', 'Berhasil', 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `pengguna`
--

CREATE TABLE `pengguna` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `nomor_hp` varchar(20) DEFAULT NULL,
  `tanggal_daftar` date DEFAULT NULL,
  `validasi_akun` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pengguna`
--

INSERT INTO `pengguna` (`id`, `nama`, `email`, `password`, `alamat`, `nomor_hp`, `tanggal_daftar`, `validasi_akun`) VALUES
(1, 'Ahmad Fadli', 'ahmad@example.com', '123456', 'Jl. Merdeka No.1', '081234567890', '2025-06-01', 1),
(2, 'Siti Aminah', 'siti@example.com', '123456', 'Jl. Sudirman No.2', '081234567891', '2025-06-01', 1),
(3, 'Budi Hartono', 'budi@example.com', '123456', 'Jl. Gatot Subroto No.3', '081234567892', '2025-06-01', 1);

-- --------------------------------------------------------

--
-- Table structure for table `penumpang`
--

CREATE TABLE `penumpang` (
  `id` int(11) NOT NULL,
  `id_pengguna` int(11) DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `penumpang`
--

INSERT INTO `penumpang` (`id`, `id_pengguna`, `tanggal_lahir`) VALUES
(1, 1, '1990-05-01'),
(2, 2, '1995-08-17'),
(3, 3, '1987-12-25');

-- --------------------------------------------------------

--
-- Table structure for table `pesanan_makanan`
--

CREATE TABLE `pesanan_makanan` (
  `id` int(11) NOT NULL,
  `id_tiket` int(11) DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `jumlah` int(11) DEFAULT NULL,
  `total` double DEFAULT NULL,
  `konfirmasi` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pesanan_makanan`
--

INSERT INTO `pesanan_makanan` (`id`, `id_tiket`, `tanggal`, `jumlah`, `total`, `konfirmasi`) VALUES
(1, 1, '2025-07-01', 2, 65000, 1),
(2, 2, '2025-07-01', 1, 30000, 1);

-- --------------------------------------------------------

--
-- Table structure for table `petugas_kereta`
--

CREATE TABLE `petugas_kereta` (
  `id` int(11) NOT NULL,
  `id_pengguna` int(11) DEFAULT NULL,
  `no_karyawan` varchar(20) DEFAULT NULL,
  `tugas` text DEFAULT NULL,
  `status_validasi` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tiket`
--

CREATE TABLE `tiket` (
  `id` int(11) NOT NULL,
  `id_penumpang` int(11) DEFAULT NULL,
  `id_jadwal` int(11) DEFAULT NULL,
  `id_kursi` int(11) DEFAULT NULL,
  `tanggal_pesan` date DEFAULT NULL,
  `status_tiket` varchar(50) DEFAULT NULL,
  `total_pembayaran` double DEFAULT NULL,
  `status_refund` tinyint(1) DEFAULT NULL,
  `validasi_tiket` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tiket`
--

INSERT INTO `tiket` (`id`, `id_penumpang`, `id_jadwal`, `id_kursi`, `tanggal_pesan`, `status_tiket`, `total_pembayaran`, `status_refund`, `validasi_tiket`) VALUES
(1, 1, 1, 1, '2025-06-10', 'Lunas', 450000, 0, 1),
(2, 2, 1, 2, '2025-06-10', 'Lunas', 450000, 0, 1),
(3, 3, 2, 3, '2025-06-11', 'Lunas', 375000, 0, 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_pengguna` (`id_pengguna`);

--
-- Indexes for table `detail_pesanan_makanan`
--
ALTER TABLE `detail_pesanan_makanan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_pesanan` (`id_pesanan`),
  ADD KEY `id_menu` (`id_menu`);

--
-- Indexes for table `gerbong`
--
ALTER TABLE `gerbong`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_kereta` (`id_kereta`);

--
-- Indexes for table `jadwal_kereta`
--
ALTER TABLE `jadwal_kereta`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_kereta` (`id_kereta`);

--
-- Indexes for table `kereta`
--
ALTER TABLE `kereta`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `kursi`
--
ALTER TABLE `kursi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_gerbong` (`id_gerbong`);

--
-- Indexes for table `menu_makanan`
--
ALTER TABLE `menu_makanan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_pengguna` (`id_pengguna`);

--
-- Indexes for table `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_tiket` (`id_tiket`);

--
-- Indexes for table `pengguna`
--
ALTER TABLE `pengguna`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `penumpang`
--
ALTER TABLE `penumpang`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_pengguna` (`id_pengguna`);

--
-- Indexes for table `pesanan_makanan`
--
ALTER TABLE `pesanan_makanan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_tiket` (`id_tiket`);

--
-- Indexes for table `petugas_kereta`
--
ALTER TABLE `petugas_kereta`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_pengguna` (`id_pengguna`);

--
-- Indexes for table `tiket`
--
ALTER TABLE `tiket`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_penumpang` (`id_penumpang`),
  ADD KEY `id_jadwal` (`id_jadwal`),
  ADD KEY `id_kursi` (`id_kursi`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `detail_pesanan_makanan`
--
ALTER TABLE `detail_pesanan_makanan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `gerbong`
--
ALTER TABLE `gerbong`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `jadwal_kereta`
--
ALTER TABLE `jadwal_kereta`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `kereta`
--
ALTER TABLE `kereta`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `kursi`
--
ALTER TABLE `kursi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `menu_makanan`
--
ALTER TABLE `menu_makanan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `notifikasi`
--
ALTER TABLE `notifikasi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pembayaran`
--
ALTER TABLE `pembayaran`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `pengguna`
--
ALTER TABLE `pengguna`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `penumpang`
--
ALTER TABLE `penumpang`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `pesanan_makanan`
--
ALTER TABLE `pesanan_makanan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `petugas_kereta`
--
ALTER TABLE `petugas_kereta`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tiket`
--
ALTER TABLE `tiket`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin`
--
ALTER TABLE `admin`
  ADD CONSTRAINT `admin_ibfk_1` FOREIGN KEY (`id_pengguna`) REFERENCES `pengguna` (`id`);

--
-- Constraints for table `detail_pesanan_makanan`
--
ALTER TABLE `detail_pesanan_makanan`
  ADD CONSTRAINT `detail_pesanan_makanan_ibfk_1` FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan_makanan` (`id`),
  ADD CONSTRAINT `detail_pesanan_makanan_ibfk_2` FOREIGN KEY (`id_menu`) REFERENCES `menu_makanan` (`id`);

--
-- Constraints for table `gerbong`
--
ALTER TABLE `gerbong`
  ADD CONSTRAINT `gerbong_ibfk_1` FOREIGN KEY (`id_kereta`) REFERENCES `kereta` (`id`);

--
-- Constraints for table `jadwal_kereta`
--
ALTER TABLE `jadwal_kereta`
  ADD CONSTRAINT `jadwal_kereta_ibfk_1` FOREIGN KEY (`id_kereta`) REFERENCES `kereta` (`id`);

--
-- Constraints for table `kursi`
--
ALTER TABLE `kursi`
  ADD CONSTRAINT `kursi_ibfk_1` FOREIGN KEY (`id_gerbong`) REFERENCES `gerbong` (`id`);

--
-- Constraints for table `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD CONSTRAINT `notifikasi_ibfk_1` FOREIGN KEY (`id_pengguna`) REFERENCES `pengguna` (`id`);

--
-- Constraints for table `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD CONSTRAINT `pembayaran_ibfk_1` FOREIGN KEY (`id_tiket`) REFERENCES `tiket` (`id`);

--
-- Constraints for table `penumpang`
--
ALTER TABLE `penumpang`
  ADD CONSTRAINT `penumpang_ibfk_1` FOREIGN KEY (`id_pengguna`) REFERENCES `pengguna` (`id`);

--
-- Constraints for table `pesanan_makanan`
--
ALTER TABLE `pesanan_makanan`
  ADD CONSTRAINT `pesanan_makanan_ibfk_1` FOREIGN KEY (`id_tiket`) REFERENCES `tiket` (`id`);

--
-- Constraints for table `petugas_kereta`
--
ALTER TABLE `petugas_kereta`
  ADD CONSTRAINT `petugas_kereta_ibfk_1` FOREIGN KEY (`id_pengguna`) REFERENCES `pengguna` (`id`);

--
-- Constraints for table `tiket`
--
ALTER TABLE `tiket`
  ADD CONSTRAINT `tiket_ibfk_1` FOREIGN KEY (`id_penumpang`) REFERENCES `penumpang` (`id`),
  ADD CONSTRAINT `tiket_ibfk_2` FOREIGN KEY (`id_jadwal`) REFERENCES `jadwal_kereta` (`id`),
  ADD CONSTRAINT `tiket_ibfk_3` FOREIGN KEY (`id_kursi`) REFERENCES `kursi` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- Update database untuk menambahkan admin statis
USE tiket_kereta;

-- Tambah data admin ke tabel pengguna
INSERT INTO `pengguna` (`id`, `nama`, `email`, `password`, `alamat`, `nomor_hp`, `tanggal_daftar`, `validasi_akun`) VALUES
(4, 'Administrator System', 'admin@keretaconnect.com', 'AdmiN123', 'Kantor Pusat PT. Kereta Connect Indonesia', '021-555-0001', '2025-01-01', 1);

-- Tambah data admin ke tabel admin
INSERT INTO `admin` (`id`, `id_pengguna`, `level_akses`) VALUES
(1, 4, 'Super Admin');

-- Update AUTO_INCREMENT
ALTER TABLE `pengguna` AUTO_INCREMENT = 5;
ALTER TABLE `admin` AUTO_INCREMENT = 2;

-- Tambah notifikasi sistem untuk admin
INSERT INTO `notifikasi` (`id`, `id_pengguna`, `judul`, `pesan`, `tanggal`, `status`, `dari_sistem`) VALUES
(1, 4, 'Welcome Admin', 'Selamat datang di sistem admin Kereta Connect. Anda memiliki akses penuh untuk mengelola sistem.', NOW(), 0, 1);

COMMIT;