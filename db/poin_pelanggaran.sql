-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Generation Time: Mar 25, 2026 at 07:07 AM
-- Server version: 8.0.40
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `poin_pelanggaran`
--

-- --------------------------------------------------------

--
-- Table structure for table `guru`
--

CREATE TABLE `guru` (
  `nuptk` varchar(20) NOT NULL,
  `id_users` int NOT NULL,
  `nama` varchar(100) NOT NULL,
  `alamat` text,
  `tanggal_lahir` date NOT NULL,
  `jenis_kelamin` enum('L','P') NOT NULL,
  `agama` varchar(30) NOT NULL,
  `telepon` varchar(20) DEFAULT NULL,
  `jabatan` varchar(50) DEFAULT 'guru ngajar',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jenis_pelanggaran`
--

CREATE TABLE `jenis_pelanggaran` (
  `id` int NOT NULL,
  `nama_pelanggaran` varchar(100) NOT NULL,
  `poin` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `jenis_pelanggaran`
--

INSERT INTO `jenis_pelanggaran` (`id`, `nama_pelanggaran`, `poin`) VALUES
(1, 'Makan di dalam kelas', 8),
(2, 'Tidak menggunakan attribute yang lengkap', 14),
(3, 'Menggunakan handphone di jam pelajaran', 23);

-- --------------------------------------------------------

--
-- Table structure for table `kelas`
--

CREATE TABLE `kelas` (
  `id` int NOT NULL,
  `guru` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `nama_kelas` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ortu_wali_siswa`
--

CREATE TABLE `ortu_wali_siswa` (
  `id` int NOT NULL,
  `nama_ayah` varchar(100) DEFAULT NULL,
  `nama_ibu` varchar(100) DEFAULT NULL,
  `nama_wali` varchar(100) DEFAULT NULL,
  `pekerjaan_ayah` varchar(50) DEFAULT NULL,
  `pekerjaan_ibu` varchar(50) DEFAULT NULL,
  `pekerjaan_wali` varchar(50) DEFAULT NULL,
  `telepon_ayah` varchar(20) DEFAULT NULL,
  `telepon_ibu` varchar(20) DEFAULT NULL,
  `telepon_wali` varchar(20) DEFAULT NULL,
  `alamat_ayah` text,
  `alamat_ibu` text,
  `alamat_wali` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `ortu_wali_siswa`
--

INSERT INTO `ortu_wali_siswa` (`id`, `nama_ayah`, `nama_ibu`, `nama_wali`, `pekerjaan_ayah`, `pekerjaan_ibu`, `pekerjaan_wali`, `telepon_ayah`, `telepon_ibu`, `telepon_wali`, `alamat_ayah`, `alamat_ibu`, `alamat_wali`) VALUES
(1, 'bapak update sty', 'ibu sty', 'kakak sty', 'wirausaha', 'wiraswasta', 'tidak bekerja', '0893272871394', '0818397893243', '0897384723894', 'JL. Jakarta Raya', 'JL. Jakarta Raya', 'JL. Jakarta Raya');

-- --------------------------------------------------------

--
-- Table structure for table `siswa`
--

CREATE TABLE `siswa` (
  `nis` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `id_users` int NOT NULL,
  `id_ortu_wali_siswa` int DEFAULT NULL,
  `id_kelas` int DEFAULT NULL,
  `nama` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `alamat` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `tanggal_lahir` date NOT NULL,
  `jenis_kelamin` enum('L','P') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `agama` varchar(30) NOT NULL,
  `telepon` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('Y','N') DEFAULT 'Y',
  `role` enum('admin','guru','siswa') NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `status`, `role`, `created_at`, `updated_at`) VALUES
(2, 'adminsekolah', 'adminsekolah01@gmail.com', '$2y$12$vvF6M7R2tMQi4SKUkDMllO0Df6WMnxTZh01UX9zOxRcVOzlLA93w.', 'Y', 'admin', '2026-03-25 06:24:02', '2026-03-25 06:24:02'),
(3, 'guruayu', 'guruayu01@gmail.com', '$2y$12$030EeqDsMa6AlMfL3r/TNuznJkQhKccgoZeBLQFM5Z8D4JUM3DcwK', 'Y', 'guru', '2026-03-25 06:38:36', '2026-03-25 06:38:36'),
(4, 'siswaahmad', 'siswaahmad01@gmail.com', '$2y$12$QFdpc2iuISxQhIk1FTYh5egVv..qZYaEip1gjWIqWKBbsBenM0Qw6', 'Y', 'siswa', '2026-03-25 06:46:43', '2026-03-25 06:46:43'),
(5, 'siswasiti', 'siswasiti01@gmail.com', '$2y$12$L8h3gJgn7AYVUBEtS/qKzeQBx9PZZJNyEiaMKLnFxCnL1LQynQQLO', 'Y', 'siswa', '2026-03-25 06:50:26', '2026-03-25 06:50:26');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `guru`
--
ALTER TABLE `guru`
  ADD PRIMARY KEY (`nuptk`),
  ADD KEY `fk_guru_users` (`id_users`);

--
-- Indexes for table `jenis_pelanggaran`
--
ALTER TABLE `jenis_pelanggaran`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `kelas`
--
ALTER TABLE `kelas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nama_kelas` (`nama_kelas`),
  ADD KEY `fk_kelas_guru` (`guru`);

--
-- Indexes for table `ortu_wali_siswa`
--
ALTER TABLE `ortu_wali_siswa`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `siswa`
--
ALTER TABLE `siswa`
  ADD PRIMARY KEY (`nis`),
  ADD KEY `fk_siswa_user` (`id_users`),
  ADD KEY `fk_siswa_kelas` (`id_kelas`),
  ADD KEY `fk_siswa_ortu_wali_siswa` (`id_ortu_wali_siswa`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `password` (`password`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `jenis_pelanggaran`
--
ALTER TABLE `jenis_pelanggaran`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `kelas`
--
ALTER TABLE `kelas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ortu_wali_siswa`
--
ALTER TABLE `ortu_wali_siswa`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `guru`
--
ALTER TABLE `guru`
  ADD CONSTRAINT `fk_guru_users` FOREIGN KEY (`id_users`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `kelas`
--
ALTER TABLE `kelas`
  ADD CONSTRAINT `fk_kelas_guru` FOREIGN KEY (`guru`) REFERENCES `guru` (`nuptk`);

--
-- Constraints for table `siswa`
--
ALTER TABLE `siswa`
  ADD CONSTRAINT `fk_siswa_kelas` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id`),
  ADD CONSTRAINT `fk_siswa_ortu_wali_siswa` FOREIGN KEY (`id_ortu_wali_siswa`) REFERENCES `ortu_wali_siswa` (`id`),
  ADD CONSTRAINT `fk_siswa_user` FOREIGN KEY (`id_users`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
