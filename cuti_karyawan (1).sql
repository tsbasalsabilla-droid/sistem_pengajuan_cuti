-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 19, 2026 at 09:58 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cuti_karyawan`
--

-- --------------------------------------------------------

--
-- Table structure for table `approval_logs`
--

CREATE TABLE `approval_logs` (
  `id` int(11) UNSIGNED NOT NULL,
  `cuti_id` int(11) UNSIGNED NOT NULL,
  `approver_id` int(11) UNSIGNED NOT NULL,
  `role_approver` varchar(50) NOT NULL,
  `status` enum('approved','rejected') NOT NULL,
  `catatan` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `approval_logs`
--

INSERT INTO `approval_logs` (`id`, `cuti_id`, `approver_id`, `role_approver`, `status`, `catatan`, `created_at`) VALUES
(1, 1, 0, '', 'approved', NULL, '2026-05-25 13:55:54'),
(2, 4, 5, 'hrd', 'rejected', 'Ditolak Hrd', '2026-06-03 16:14:05'),
(3, 12, 11, 'direktur', 'approved', 'Disetujui Direktur', '2026-06-08 12:26:54'),
(4, 11, 11, 'direktur', 'approved', 'Disetujui Direktur', '2026-06-08 12:52:49'),
(5, 7, 11, 'direktur', 'approved', 'Disetujui Direktur', '2026-06-08 13:16:40'),
(6, 10, 7, 'hrd', 'rejected', 'Ditolak Hrd', '2026-06-08 13:54:49'),
(7, 10, 7, 'hrd', 'rejected', 'Ditolak Hrd', '2026-06-08 13:54:52'),
(8, 8, 8, 'hrd', 'approved', 'Disetujui Hrd', '2026-06-08 15:26:20'),
(9, 9, 8, 'hrd', 'approved', 'Disetujui Hrd', '2026-06-08 15:32:10'),
(10, 9, 8, 'hrd', 'approved', 'Disetujui Hrd', '2026-06-08 15:32:11'),
(11, 13, 6, 'hrd', 'approved', 'Disetujui HRD', '2026-06-08 15:38:26'),
(12, 13, 8, 'hrd', 'rejected', 'Ditolak Hrd', '2026-06-08 15:39:27'),
(13, 14, 6, 'hrd', 'approved', 'Disetujui HRD', '2026-06-08 15:43:05'),
(14, 14, 11, 'direktur', 'approved', 'Disetujui Direktur', '2026-06-08 15:44:05'),
(15, 15, 6, 'hrd', 'approved', 'Disetujui HRD', '2026-06-08 16:07:20'),
(16, 15, 6, 'hrd', 'approved', 'Disetujui HRD', '2026-06-08 16:07:40'),
(17, 15, 8, 'hrd', 'approved', 'Disetujui Hrd', '2026-06-08 16:10:48'),
(18, 0, 15, 'teman', 'rejected', 'Ditolak Teman', '2026-06-11 17:19:48'),
(19, 0, 15, 'teman', 'rejected', 'Ditolak Teman', '2026-06-11 17:20:04'),
(20, 0, 15, 'teman', 'rejected', 'Ditolak Teman', '2026-06-11 17:20:11'),
(21, 0, 15, 'teman', 'rejected', 'Ditolak Teman', '2026-06-11 17:26:04'),
(22, 0, 15, 'teman', 'rejected', 'Ditolak Teman', '2026-06-11 17:26:09'),
(23, 0, 15, 'teman', 'rejected', 'Ditolak Teman', '2026-06-11 17:26:14'),
(24, 0, 15, 'teman', 'rejected', 'Ditolak Teman', '2026-06-11 17:27:14'),
(25, 0, 15, 'teman', 'rejected', 'Ditolak Teman', '2026-06-11 17:28:16'),
(26, 0, 15, 'teman', 'rejected', 'Ditolak Teman', '2026-06-11 17:33:16'),
(27, 0, 15, 'teman', 'rejected', 'Ditolak Teman', '2026-06-11 17:33:22'),
(28, 0, 15, 'teman', 'rejected', 'Ditolak Teman', '2026-06-11 17:33:26'),
(29, 0, 15, 'teman', 'rejected', 'Ditolak Teman', '2026-06-11 17:36:59'),
(30, 0, 15, 'teman', 'rejected', 'Ditolak Teman', '2026-06-11 17:37:49'),
(31, 0, 15, 'teman', 'rejected', 'Ditolak Teman', '2026-06-11 17:37:54'),
(32, 17, 15, 'teman', 'rejected', 'Ditolak Teman', '2026-06-11 17:46:12'),
(33, 18, 1, 'teman', 'approved', 'Disetujui Teman', '2026-06-11 17:48:14'),
(34, 18, 7, 'spv', 'approved', 'Disetujui Spv', '2026-06-11 18:04:01'),
(35, 18, 8, 'hrd', 'approved', 'Disetujui HRD', '2026-06-11 18:20:50'),
(36, 18, 9, 'direktur', 'approved', 'Disetujui Direktur', '2026-06-11 18:21:42'),
(37, 0, 15, 'teman', 'rejected', 'Ditolak tanpa alasan spesifik.', '2026-06-12 14:09:01'),
(38, 0, 15, 'teman', 'rejected', 'karena david suka bolos\r\n', '2026-06-12 14:12:55'),
(39, 0, 1, 'teman', 'rejected', 'lalapo', '2026-06-12 14:24:57'),
(40, 0, 7, 'spv', 'approved', 'Disetujui oleh SPV', '2026-06-12 15:54:57'),
(41, 0, 7, 'spv', 'rejected', 'uuu', '2026-06-12 16:09:28'),
(42, 0, 7, 'spv', 'approved', 'Disetujui oleh SPV', '2026-06-14 19:13:17'),
(43, 0, 8, 'hrd', 'approved', 'Disetujui HRD', '2026-06-14 19:52:29'),
(44, 0, 8, 'hrd', 'approved', 'Disetujui HRD', '2026-06-14 19:52:38'),
(45, 0, 7, 'spv', 'approved', 'Disetujui oleh SPV', '2026-06-15 09:46:11'),
(46, 0, 8, 'hrd', 'approved', 'Disetujui HRD', '2026-06-15 09:47:07'),
(47, 0, 9, 'direktur', 'approved', 'Disetujui oleh DIREKTUR', '2026-06-15 09:47:30'),
(48, 0, 7, 'spv', 'approved', 'Disetujui oleh SPV', '2026-06-15 11:18:08'),
(49, 0, 9, 'direktur', 'approved', 'Disetujui oleh DIREKTUR', '2026-06-15 11:19:00'),
(50, 0, 7, 'spv', 'approved', 'Disetujui oleh SPV', '2026-06-15 13:31:03'),
(51, 0, 7, 'spv', 'approved', 'Disetujui oleh SPV', '2026-06-15 14:19:06'),
(52, 0, 9, 'direktur', 'approved', 'Disetujui oleh DIREKTUR', '2026-06-15 14:21:53'),
(53, 0, 7, 'spv', 'approved', 'Disetujui oleh SPV', '2026-06-15 14:24:43'),
(54, 0, 9, 'direktur', 'rejected', 'Ditolak tanpa alasan spesifik.', '2026-06-15 14:27:35'),
(55, 0, 9, 'direktur', 'rejected', 'Ditolak tanpa alasan spesifik.', '2026-06-15 14:27:50'),
(56, 0, 7, 'spv', 'rejected', 'gapapa', '2026-06-15 14:43:19'),
(57, 0, 9, 'direktur', 'rejected', 'Ditolak tanpa alasan spesifik.', '2026-06-15 14:48:34'),
(58, 0, 7, 'spv', 'approved', 'Disetujui oleh SPV', '2026-06-15 15:02:04'),
(59, 0, 9, 'direktur', 'rejected', 'hajing', '2026-06-15 15:05:17'),
(60, 0, 9, 'direktur', 'rejected', 'adalah pokonya', '2026-06-17 19:40:24'),
(61, 0, 9, 'direktur', 'rejected', 'adadeh', '2026-06-17 19:43:04'),
(62, 0, 9, 'direktur', 'rejected', 'ada', '2026-06-17 19:48:28'),
(63, 0, 9, 'direktur', 'approved', 'Disetujui oleh DIREKTUR', '2026-06-17 19:57:07'),
(64, 47, 0, '', 'approved', NULL, '2026-06-19 04:09:45');

-- --------------------------------------------------------

--
-- Table structure for table `aturan_cuti`
--

CREATE TABLE `aturan_cuti` (
  `id` int(11) NOT NULL,
  `masa_kerja_min` int(11) NOT NULL,
  `jatah_cuti` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cuti_bersama`
--

CREATE TABLE `cuti_bersama` (
  `id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `keterangan` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cuti_bersama`
--

INSERT INTO `cuti_bersama` (`id`, `tanggal`, `keterangan`, `created_at`, `updated_at`) VALUES
(1, '2026-05-01', 'Lebaran', NULL, NULL),
(4, '2026-06-16', 'Tahun Baru Islam', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `detail_status_cuti`
--

CREATE TABLE `detail_status_cuti` (
  `id` int(10) UNSIGNED NOT NULL,
  `pengajuan_id` int(10) UNSIGNED DEFAULT NULL,
  `approved_by` int(10) UNSIGNED DEFAULT NULL,
  `level_approval` enum('spv','teman','hrd','direktur') DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `catatan` text DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `detail_status_cuti`
--

INSERT INTO `detail_status_cuti` (`id`, `pengajuan_id`, `approved_by`, `level_approval`, `status`, `catatan`, `approved_at`) VALUES
(1, 3, NULL, 'spv', 'pending', NULL, NULL),
(2, 4, NULL, 'spv', 'pending', NULL, NULL),
(3, 1, NULL, 'spv', 'pending', NULL, NULL),
(4, 2, NULL, 'spv', 'pending', NULL, NULL),
(5, 3, NULL, 'spv', 'pending', NULL, NULL),
(6, 4, NULL, 'spv', 'pending', NULL, NULL),
(7, 10, NULL, 'spv', 'pending', NULL, NULL),
(8, 16, NULL, 'spv', 'pending', NULL, NULL),
(9, 17, 15, 'teman', 'rejected', NULL, NULL),
(10, 18, 1, 'teman', 'approved', NULL, NULL),
(11, 18, 7, 'spv', 'approved', NULL, NULL),
(12, 19, 15, 'teman', 'rejected', NULL, NULL),
(13, 20, 15, 'teman', 'rejected', 'karena david suka bolos\r\n', NULL),
(14, 21, 1, 'teman', 'rejected', 'lalapo', NULL),
(15, 22, NULL, 'teman', 'pending', NULL, NULL),
(16, 22, 17, 'teman', 'approved', NULL, '2026-06-12 08:12:01'),
(17, 22, 17, 'teman', 'rejected', 'xfgyhuji', '2026-06-12 08:15:20'),
(18, 22, 1, 'teman', 'approved', NULL, '2026-06-12 08:17:29'),
(19, 22, 18, 'teman', 'approved', NULL, '2026-06-12 08:25:39'),
(20, 23, NULL, 'teman', 'pending', NULL, NULL),
(21, 23, 17, 'teman', 'approved', NULL, '2026-06-12 08:47:43'),
(22, 23, 1, 'teman', 'approved', NULL, '2026-06-12 08:51:41'),
(23, 23, 18, 'teman', 'approved', NULL, '2026-06-12 08:51:56'),
(24, 23, 7, 'spv', 'approved', NULL, '2026-06-12 08:54:57'),
(25, 22, 7, 'spv', 'rejected', 'uuu', '2026-06-12 09:09:28'),
(27, 24, 1, 'teman', 'approved', NULL, '2026-06-14 06:50:32'),
(28, 24, 17, 'teman', 'approved', NULL, '2026-06-14 11:13:39'),
(29, 24, 15, 'teman', 'approved', NULL, '2026-06-14 11:13:59'),
(30, 24, 7, 'spv', 'approved', NULL, '2026-06-14 12:13:16'),
(32, 25, 18, 'teman', 'approved', NULL, '2026-06-15 02:44:28'),
(33, 25, 15, 'teman', 'approved', NULL, '2026-06-15 02:44:45'),
(34, 25, 1, 'teman', 'approved', NULL, '2026-06-15 02:45:04'),
(35, 25, 7, 'spv', 'approved', NULL, '2026-06-15 02:46:11'),
(36, 25, 9, 'direktur', 'approved', NULL, '2026-06-15 02:47:29'),
(44, 25, 8, 'hrd', 'approved', NULL, '2026-06-15 10:36:47'),
(45, 26, 15, 'teman', 'rejected', 'kelamaan libur', '2026-06-15 04:12:07'),
(46, 26, 17, 'teman', 'approved', 'Disetujui Teman Sejawat', '2026-06-15 04:12:52'),
(47, 26, 1, 'teman', 'approved', 'Disetujui Teman Sejawat', '2026-06-15 04:13:09'),
(48, 26, 7, 'spv', 'approved', NULL, '2026-06-15 04:18:07'),
(49, 26, 8, 'hrd', 'approved', 'Disetujui HRD', NULL),
(50, 26, 9, 'direktur', 'approved', NULL, '2026-06-15 04:19:00'),
(51, 27, NULL, 'teman', 'pending', NULL, NULL),
(52, 31, NULL, 'teman', 'pending', NULL, NULL),
(53, 31, 18, 'teman', 'approved', 'Disetujui Teman Sejawat', '2026-06-15 06:25:30'),
(54, 31, 1, 'teman', 'approved', 'Disetujui Teman Sejawat', '2026-06-15 06:26:22'),
(55, 27, 15, 'teman', 'approved', 'Disetujui Teman Sejawat', '2026-06-15 06:28:24'),
(56, 27, 18, 'teman', 'approved', 'Disetujui Teman Sejawat', '2026-06-15 06:28:36'),
(57, 27, 1, 'teman', 'approved', 'Disetujui Teman Sejawat', '2026-06-15 06:28:40'),
(58, 32, NULL, 'teman', 'pending', NULL, NULL),
(59, 32, 15, 'teman', 'approved', 'Disetujui Teman Sejawat', '2026-06-15 06:30:29'),
(60, 32, 1, 'teman', 'approved', 'Disetujui Teman Sejawat', '2026-06-15 06:30:37'),
(61, 31, 7, 'spv', 'approved', NULL, '2026-06-15 06:31:03'),
(62, 32, 7, 'spv', 'approved', NULL, '2026-06-15 07:19:06'),
(63, 32, 8, 'hrd', 'approved', 'Disetujui HRD', NULL),
(64, 32, 9, 'direktur', 'approved', NULL, '2026-06-15 07:21:53'),
(65, 33, NULL, 'teman', 'pending', NULL, NULL),
(66, 34, NULL, 'teman', 'pending', NULL, NULL),
(67, 33, 1, 'teman', 'approved', 'Disetujui Teman Sejawat', '2026-06-15 07:22:47'),
(68, 34, 1, 'teman', 'rejected', 'aku kita', '2026-06-15 07:22:56'),
(69, 33, 15, 'teman', 'approved', 'Disetujui Teman Sejawat', '2026-06-15 07:23:27'),
(70, 34, 15, 'teman', 'rejected', 'kita bersama', '2026-06-15 07:23:37'),
(71, 33, 7, 'spv', 'approved', NULL, '2026-06-15 07:24:43'),
(72, 24, 9, 'direktur', 'rejected', 'Ditolak tanpa alasan spesifik.', '2026-06-15 07:27:35'),
(73, 23, 9, 'direktur', 'rejected', 'Ditolak tanpa alasan spesifik.', '2026-06-15 07:27:50'),
(74, 27, 7, 'spv', 'rejected', 'gapapa', '2026-06-15 07:43:19'),
(75, 28, 8, 'hrd', 'approved', 'Disetujui HRD', NULL),
(76, 28, 9, 'direktur', 'rejected', 'Ditolak tanpa alasan spesifik.', '2026-06-15 07:48:34'),
(77, 35, NULL, 'teman', 'pending', NULL, NULL),
(78, 35, 1, 'teman', 'approved', 'Disetujui Teman Sejawat', '2026-06-15 08:01:36'),
(79, 35, 18, 'teman', 'approved', 'Disetujui Teman Sejawat', '2026-06-15 08:01:47'),
(80, 35, 7, 'spv', 'approved', NULL, '2026-06-15 08:02:04'),
(81, 29, 8, 'hrd', 'approved', 'Disetujui HRD', NULL),
(82, 35, 8, 'hrd', 'approved', 'Disetujui HRD', NULL),
(83, 35, 9, 'direktur', 'rejected', 'hajing', '2026-06-15 08:05:17'),
(84, 36, 8, 'hrd', 'approved', 'Disetujui HRD', NULL),
(85, 33, 8, 'hrd', 'approved', 'Disetujui HRD', NULL),
(86, 31, 8, 'hrd', 'approved', 'Disetujui HRD', NULL),
(87, 37, NULL, 'teman', 'pending', NULL, NULL),
(88, 37, 17, 'teman', 'approved', 'Disetujui Teman Sejawat', '2026-06-17 11:59:49'),
(89, 37, 15, 'teman', 'rejected', 'kebanyakan libur\r\n', '2026-06-17 12:00:36'),
(90, 37, 1, 'teman', 'approved', 'Disetujui Teman Sejawat', '2026-06-17 12:01:13'),
(91, 29, 9, 'direktur', 'rejected', 'adalah pokonya', '2026-06-17 12:40:24'),
(92, 31, 9, 'direktur', 'rejected', 'adadeh', '2026-06-17 12:42:57'),
(93, 33, 9, 'direktur', 'rejected', 'ada', '2026-06-17 12:48:28'),
(94, 36, 9, 'direktur', 'approved', NULL, '2026-06-17 12:57:07'),
(95, 38, 8, 'teman', 'approved', 'Otomatis Disetujui (Pengajuan oleh HRD)', NULL),
(96, 38, 8, 'spv', 'approved', 'Otomatis Disetujui (Pengajuan oleh HRD)', NULL),
(97, 38, 8, 'hrd', 'approved', 'Disetujui HRD', NULL),
(98, 39, NULL, 'direktur', 'pending', 'Menunggu persetujuan Direktur', NULL),
(99, 39, 8, 'spv', 'approved', 'Otomatis Disetujui (Pengajuan oleh HRD)', NULL),
(100, 39, 8, 'hrd', 'approved', 'Disetujui HRD', NULL),
(101, 40, NULL, 'direktur', 'pending', 'Menunggu persetujuan Direktur', NULL),
(102, 40, 8, 'spv', 'approved', 'Otomatis Disetujui (Pengajuan oleh HRD)', NULL),
(103, 40, 8, 'hrd', 'approved', 'Disetujui HRD', NULL),
(104, 41, NULL, 'direktur', 'pending', 'Menunggu persetujuan Direktur', NULL),
(105, 41, 8, 'spv', 'approved', 'Otomatis Disetujui (Pengajuan oleh HRD)', NULL),
(106, 41, 8, 'hrd', 'approved', 'Disetujui HRD', NULL),
(107, 42, NULL, 'teman', 'pending', NULL, NULL),
(108, 42, 15, 'teman', 'rejected', 'banyak alasan', '2026-06-19 02:49:32'),
(109, 42, 17, 'teman', 'approved', 'Disetujui Teman Sejawat', '2026-06-19 02:50:37'),
(110, 42, 18, 'teman', 'rejected', 'adadeh', '2026-06-19 02:51:28'),
(111, 43, NULL, 'teman', 'pending', NULL, NULL),
(112, 43, 15, 'teman', 'rejected', 'gamau approve', '2026-06-19 03:06:41'),
(113, 43, 17, 'teman', 'rejected', 'adadeh', '2026-06-19 03:09:04'),
(114, 43, NULL, 'spv', 'rejected', 'Ditolak otomatis karena penolakan Teman Sejawat sudah mencapai 2 suara.', '2026-06-19 03:09:05'),
(115, 43, NULL, 'hrd', 'rejected', 'Ditolak otomatis karena penolakan Teman Sejawat sudah mencapai 2 suara.', '2026-06-19 03:09:09'),
(116, 43, NULL, 'direktur', 'rejected', 'Ditolak otomatis karena penolakan Teman Sejawat sudah mencapai 2 suara.', '2026-06-19 03:09:09'),
(117, 44, NULL, 'teman', 'pending', NULL, NULL),
(118, 44, 18, 'teman', 'approved', 'Disetujui Teman Sejawat', '2026-06-19 03:14:18'),
(119, 44, 17, 'teman', 'rejected', 'adalah', '2026-06-19 03:15:04'),
(120, 44, 15, 'teman', 'rejected', 'apa', '2026-06-19 03:15:24'),
(121, 44, NULL, 'spv', 'rejected', 'Ditolak otomatis karena mayoritas voting Teman Sejawat memilih Reject.', '2026-06-19 03:15:25'),
(122, 44, NULL, 'hrd', 'rejected', 'Ditolak otomatis karena mayoritas voting Teman Sejawat memilih Reject.', '2026-06-19 03:15:25'),
(123, 44, NULL, 'direktur', 'rejected', 'Ditolak otomatis karena mayoritas voting Teman Sejawat memilih Reject.', '2026-06-19 03:15:25'),
(124, 45, NULL, 'teman', 'pending', NULL, NULL),
(125, 45, 15, 'teman', 'approved', 'Disetujui Teman Sejawat', '2026-06-19 03:20:10'),
(126, 45, 17, 'teman', 'rejected', 'poijhg', '2026-06-19 03:20:38'),
(127, 45, 18, 'teman', 'rejected', 'sdfgh', '2026-06-19 03:21:00'),
(128, 45, NULL, 'spv', 'rejected', 'Ditolak otomatis karena mayoritas voting Teman Sejawat memilih Reject.', '2026-06-19 03:21:00'),
(129, 45, NULL, 'hrd', 'rejected', 'Ditolak otomatis karena mayoritas voting Teman Sejawat memilih Reject.', '2026-06-19 03:21:00'),
(130, 45, NULL, 'direktur', 'rejected', 'Ditolak otomatis karena mayoritas voting Teman Sejawat memilih Reject.', '2026-06-19 03:21:00'),
(131, 46, NULL, 'teman', 'pending', NULL, NULL),
(132, 46, 15, 'teman', 'approved', 'Disetujui Teman Sejawat', '2026-06-19 03:54:11'),
(133, 46, 17, 'teman', 'approved', 'Disetujui Teman Sejawat', '2026-06-19 03:54:37'),
(134, 46, 18, 'teman', 'approved', 'Disetujui Teman Sejawat', '2026-06-19 03:54:59'),
(135, 47, NULL, 'teman', 'pending', NULL, NULL),
(136, 47, 1, 'teman', 'approved', 'Disetujui Teman Sejawat', '2026-06-19 04:05:55'),
(137, 47, 17, 'teman', 'approved', 'Disetujui Teman Sejawat', '2026-06-19 04:06:36'),
(138, 47, 18, 'teman', 'approved', 'Disetujui Teman Sejawat', '2026-06-19 04:08:40');

-- --------------------------------------------------------

--
-- Table structure for table `divisi`
--

CREATE TABLE `divisi` (
  `id` int(11) NOT NULL,
  `nama_divisi` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `divisi`
--

INSERT INTO `divisi` (`id`, `nama_divisi`) VALUES
(1, 'HR'),
(2, 'IT'),
(3, 'Finance'),
(4, 'Marketing'),
(5, 'Operasional'),
(6, 'R&D'),
(7, 'Event Organizer');

-- --------------------------------------------------------

--
-- Table structure for table `jabatan`
--

CREATE TABLE `jabatan` (
  `id` int(11) NOT NULL,
  `jabatan` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jabatan`
--

INSERT INTO `jabatan` (`id`, `jabatan`) VALUES
(1, 'Staff Keuangan'),
(2, 'Staff IT'),
(3, 'HRD'),
(4, 'Manager'),
(5, 'Direktur'),
(6, 'Supervisor Marketing');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `version` varchar(255) NOT NULL,
  `class` varchar(255) NOT NULL,
  `group` varchar(255) NOT NULL,
  `namespace` varchar(255) NOT NULL,
  `time` int(11) NOT NULL,
  `batch` int(11) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pegawai`
--

CREATE TABLE `pegawai` (
  `id` int(11) NOT NULL,
  `nama` varchar(255) NOT NULL,
  `nip` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('karyawan','spv','hrd','direktur') DEFAULT 'karyawan',
  `id_jabatan` int(11) NOT NULL,
  `id_divisi` int(11) NOT NULL,
  `saldo_cuti` int(11) NOT NULL DEFAULT 12,
  `no_hp` varchar(255) NOT NULL,
  `alamat` text NOT NULL,
  `tanggal_masuk` date NOT NULL,
  `status_aktif` enum('aktif','nonaktif') NOT NULL,
  `foto` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pegawai`
--

INSERT INTO `pegawai` (`id`, `nama`, `nip`, `email`, `password`, `role`, `id_jabatan`, `id_divisi`, `saldo_cuti`, `no_hp`, `alamat`, `tanggal_masuk`, `status_aktif`, `foto`, `created_at`, `updated_at`) VALUES
(1, 'David Lee ', '19870614', 'david@gmail.com', '$2y$10$SyE6Yx/FfcETS6KizNuoL.8aY4UVrakMYaLnF07vkmyHJwYOqofA2', 'karyawan', 1, 3, 4, '086520351952', 'jl. Borobudur No 25', '2026-05-01', 'aktif', 'david.jpg', '2026-05-21 10:22:43', '2026-05-21 10:22:43'),
(7, 'Robert Putra', '14680538', 'robert@gmail.com', '$2y$10$ukycJPWtpxYcBK3nRF3fGOrMBIeJyolgkjTRRBRTgY6HhU5Sw2e2C', 'spv', 6, 4, 2, '081234567890', 'Jl. Bunga Kencana Putri No 14 ', '2026-05-20', 'aktif', 'david.jpg', '2026-06-04 04:35:49', '2026-06-17 12:57:07'),
(8, 'Roberto ', '1293063749', 'roberto@gmail.com', '$2y$10$5o8Y/LcA0D.rP4luO7m6ROOccbeRRMvjr7Y/BP1lkeUldqNiL218y', 'hrd', 3, 6, 9, '082156789012', 'Jl. Kenanga No. 45', '2026-05-10', 'aktif', 'david.jpg', '2026-06-05 02:22:11', '2026-06-05 02:22:11'),
(9, 'Jennie', '1372880', 'jennie@gmail.com', '$2y$10$oCxs7AY1.k9hH811MIm7Juxwm5hN9KIZYraCbPKowODJ8QtoY.EwO', 'direktur', 5, 0, 12, '082267890123', 'Jl. Bougenville No. 7', '2026-04-24', 'aktif', 'david.jpg', '2026-06-05 04:48:29', '2026-06-05 04:48:29'),
(15, 'Alisya', '4567868', 'alisya@gmail.com', '$2y$10$/LRWyIxHfusJ6pAXS72OG.ASKT/9uUAGBEkZGUlgDVofp69sdyuVO', 'karyawan', 1, 3, 9, '085390123456', 'Jl. Cendana No. 19', '2026-05-27', 'aktif', 'david.jpg', '2026-06-11 09:48:10', '2026-06-11 11:21:42'),
(17, 'Bunga', '234678', 'bunga@gmail.com', '$2y$10$0EKZeyMlnzQqA5hLzIXU/.IcIfyP.dQy7nqEqgsSNfwTGtS4bQeWS', 'karyawan', 1, 3, 7, '089823456789', 'Jl. Kamboja No. 34', '2026-05-18', 'aktif', 'david.jpg', '2026-06-12 07:47:39', '2026-06-15 07:21:53'),
(18, 'Abimanyu', '987654', 'abimanyu@gmail.com', '$2y$10$IPYU3.TmhvVkoG5rK5h7zO4fjw5Uj3sMXfh89u0PrWMW.0zavF6gK', 'karyawan', 1, 3, 3, '082267890123', 'Jl. Merpati No. 14', '2026-03-17', 'aktif', 'david.jpg', '2026-06-12 08:20:14', '2026-06-15 04:19:00');

-- --------------------------------------------------------

--
-- Table structure for table `pengajuan_cuti`
--

CREATE TABLE `pengajuan_cuti` (
  `id` int(11) NOT NULL,
  `pegawai_id` int(11) NOT NULL,
  `tanggal_mulai` date NOT NULL,
  `tanggal_selesai` date NOT NULL,
  `total_hari` int(11) NOT NULL,
  `alasan` text NOT NULL,
  `status` enum('pending_teman_sejawat','pending_spv','pending_hrd','pending_direktur','approved','rejected') NOT NULL,
  `catatan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pengajuan_cuti`
--

INSERT INTO `pengajuan_cuti` (`id`, `pegawai_id`, `tanggal_mulai`, `tanggal_selesai`, `total_hari`, `alasan`, `status`, `catatan`) VALUES
(7, 8, '2026-06-10', '2026-06-12', 3, 'jdiqjd', 'approved', NULL),
(8, 1, '2026-06-10', '2026-06-12', 3, 'uji coba', 'approved', NULL),
(9, 7, '2026-06-08', '2026-06-10', 3, 'rhty', 'approved', NULL),
(10, 1, '2026-06-09', '2026-06-10', 2, 'Mau liburan ke swis, ke jepang, ke thailand, ke mekah ke seluruh dunia', 'rejected', NULL),
(11, 1, '2026-06-09', '2026-06-10', 2, 'mau ke rumah calon istri lmaran', 'approved', NULL),
(12, 1, '2026-06-10', '2026-06-12', 3, 'mau lamaran\r\n', 'approved', NULL),
(13, 7, '2026-06-09', '2026-06-11', 3, 'bita mau ke pan tai', 'rejected', NULL),
(14, 7, '2026-06-09', '2026-06-10', 2, 'okin', 'approved', NULL),
(15, 7, '2026-06-10', '2026-06-12', 3, 'kjibnjklmniopqrstu', 'approved', NULL),
(18, 15, '2026-06-10', '2026-06-12', 3, 'asdf', 'approved', NULL),
(19, 1, '2026-06-16', '2026-06-18', 3, 'kucing sakit', 'rejected', NULL),
(20, 1, '2026-06-25', '2026-06-26', 2, 'kakakaka', 'rejected', NULL),
(21, 15, '2026-06-16', '2026-06-18', 3, 'h', 'rejected', NULL),
(22, 15, '2026-06-24', '2026-06-26', 3, 'olk\r\n\r\n\r\n', 'rejected', NULL),
(23, 15, '2026-06-20', '2026-06-21', 2, 'yyuiy', 'rejected', NULL),
(24, 18, '2026-06-17', '2026-06-18', 2, 'menikah', 'rejected', NULL),
(25, 17, '2026-06-17', '2026-06-19', 3, 'rrpv', 'approved', NULL),
(26, 18, '2026-06-22', '2026-06-30', 9, 'liburan', 'approved', NULL),
(27, 17, '2026-06-25', '2026-06-26', 2, 'kk', 'rejected', NULL),
(28, 7, '2026-06-16', '2026-06-18', 3, 'iuokmnhgrd\r\n', 'rejected', NULL),
(29, 7, '2026-06-17', '2026-06-19', 3, 'ty', 'rejected', NULL),
(30, 7, '2026-06-19', '2026-06-20', 2, 'rrrtyghbbe', 'pending_hrd', NULL),
(31, 17, '2026-06-16', '2026-06-17', 2, '3', 'rejected', NULL),
(32, 17, '2026-06-27', '2026-06-28', 2, 'iuytjy', 'approved', NULL),
(33, 17, '2026-06-16', '2026-06-18', 3, 'aku', 'rejected', NULL),
(34, 17, '2026-06-23', '2026-06-25', 3, 'kamu', 'rejected', NULL),
(35, 17, '2026-06-19', '2026-06-20', 2, 'yh', 'rejected', NULL),
(36, 7, '2026-06-18', '2026-06-19', 2, 'asdfrgthy', 'approved', NULL),
(37, 18, '2026-06-18', '2026-06-19', 2, 'sakit', 'rejected', NULL),
(41, 8, '2026-06-15', '2026-06-17', 2, 'sakit', 'pending_direktur', NULL),
(44, 1, '2026-06-17', '2026-06-17', 1, 'sakit', 'rejected', NULL),
(45, 1, '2026-06-15', '2026-06-15', 1, 'sakit lagi', 'rejected', NULL),
(46, 1, '2026-06-20', '2026-06-22', 1, 'mau hiling', 'pending_hrd', NULL),
(47, 15, '2026-06-24', '2026-06-26', 3, 'fikir', 'rejected', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `role`
--

CREATE TABLE `role` (
  `id` int(11) NOT NULL,
  `role` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role`
--

INSERT INTO `role` (`id`, `role`) VALUES
(1, 'pegawai'),
(2, 'SPV'),
(3, 'HRD'),
(4, 'direktur');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `approval_logs`
--
ALTER TABLE `approval_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cuti_id` (`cuti_id`),
  ADD KEY `approver_id` (`approver_id`);

--
-- Indexes for table `cuti_bersama`
--
ALTER TABLE `cuti_bersama`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `detail_status_cuti`
--
ALTER TABLE `detail_status_cuti`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `divisi`
--
ALTER TABLE `divisi`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `jabatan`
--
ALTER TABLE `jabatan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pegawai`
--
ALTER TABLE `pegawai`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pengajuan_cuti`
--
ALTER TABLE `pengajuan_cuti`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `role`
--
ALTER TABLE `role`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `approval_logs`
--
ALTER TABLE `approval_logs`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT for table `cuti_bersama`
--
ALTER TABLE `cuti_bersama`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `detail_status_cuti`
--
ALTER TABLE `detail_status_cuti`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=139;

--
-- AUTO_INCREMENT for table `divisi`
--
ALTER TABLE `divisi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `jabatan`
--
ALTER TABLE `jabatan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pegawai`
--
ALTER TABLE `pegawai`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `pengajuan_cuti`
--
ALTER TABLE `pengajuan_cuti`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `role`
--
ALTER TABLE `role`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
