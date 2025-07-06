-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jun 24, 2025 at 05:56 PM
-- Server version: 10.6.19-MariaDB
-- PHP Version: 8.1.32

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
--

-- --------------------------------------------------------

--
-- Table structure for table `wp_bmltwf_service_bodies_access`
--

CREATE TABLE `wp_bmltwf_service_bodies_access` (
  `service_body_bigint` bigint(20) NOT NULL,
  `wp_uid` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Dumping data for table `wp_bmltwf_service_bodies_access`
--

INSERT INTO `wp_bmltwf_service_bodies_access` (`service_body_bigint`, `wp_uid`) VALUES
(2, 1),
(2, 22),
(2, 24),
(2, 96),
(2, 126),
(3, 1),
(3, 22),
(3, 24),
(3, 96),
(3, 126),
(4, 1),
(4, 22),
(4, 24),
(4, 96),
(4, 126),
(5, 1),
(5, 22),
(5, 24),
(5, 96),
(5, 126),
(6, 1),
(6, 24),
(6, 60),
(6, 96),
(6, 126),
(8, 1),
(8, 22),
(8, 24),
(8, 96),
(8, 126),
(9, 1),
(9, 22),
(9, 24),
(9, 60),
(9, 96),
(9, 126),
(10, 1),
(10, 22),
(10, 24),
(10, 96),
(10, 126),
(11, 1),
(11, 22),
(11, 24),
(11, 96),
(11, 126),
(12, 1),
(12, 22),
(12, 24),
(12, 96),
(12, 126),
(13, 1),
(13, 22),
(13, 24),
(13, 96),
(13, 126),
(14, 1),
(14, 22),
(14, 24),
(14, 96),
(14, 126),
(15, 1),
(15, 22),
(15, 24),
(15, 60),
(15, 96),
(15, 126),
(16, 1),
(16, 96),
(16, 126),
(16, 284),
(17, 1),
(17, 96),
(17, 126),
(17, 159),
(17, 283),
(18, 1),
(18, 96),
(18, 126),
(18, 159),
(18, 283),
(19, 96),
(19, 644),
(21, 22),
(21, 24),
(21, 96),
(23, 22),
(23, 24),
(23, 96),
(24, 22),
(24, 24),
(24, 60),
(24, 96),
(26, 22),
(26, 24),
(26, 96),
(28, 22),
(28, 24),
(28, 96),
(31, 22),
(31, 24),
(31, 96),
(32, 22),
(32, 24),
(32, 60),
(32, 96),
(33, 22),
(33, 24),
(33, 60),
(33, 96),
(34, 22),
(34, 24),
(34, 60),
(34, 96),
(35, 22),
(35, 24),
(35, 60),
(35, 96),
(35, 698);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `wp_bmltwf_service_bodies_access`
--
ALTER TABLE `wp_bmltwf_service_bodies_access`
  ADD KEY `service_body_bigint` (`service_body_bigint`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
