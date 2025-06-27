
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
-- Table structure for table `wp_bmltwf_service_bodies`
--

CREATE TABLE `wp_bmltwf_service_bodies` (
  `service_body_bigint` bigint(20) NOT NULL,
  `service_body_name` tinytext NOT NULL,
  `service_body_description` text DEFAULT NULL,
  `show_on_form` tinyint(1) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Dumping data for table `wp_bmltwf_service_bodies`
--

INSERT INTO `wp_bmltwf_service_bodies` (`service_body_bigint`, `service_body_name`, `service_body_description`, `show_on_form`) VALUES
(21, 'NSW Mid North Coast Port Macquarie Area', 'NSW Mid North Coast Port Macquarie Area', 1),
(16, 'South Australia Area', '', 1),
(17, 'Theresa Ball', '', 1),
(18, 'Crystal Richmond', '', 1),
(19, 'Western Australia Area', '', 1),
(15, 'Greater Queensland Area', 'Greater Queensland is now part of the Northern Australian Area which is All of QLD and NT excluding the Gold Coast and Sunshine Coast.  If your meeting is in QLD but not in the Gold Coast or Sunshine Coast select Greater Queensland.', 1),
(12, 'NSW Central Coast Area', '', 1),
(13, 'NSW Far North Coast Area', '', 1),
(14, 'NSW South Coast Area', '', 1),
(8, 'Canberra/A.C.T. Area', '', 1),
(9, 'Gold Coast Area', '', 1),
(10, 'Newcastle and Hunter Valley Area', '', 1),
(11, 'Northern Territory Area', 'Northern Territory is now part of the Northern Australian Area which is All of QLD and NT excluding the Gold Coast and Sunshine Coast.  If your meeting is in the Northern Territory Select Northern Territory Area.', 1),
(6, 'Samantha Gibson', 'Eastern Sydney Metropolitan Area', 1),
(5, 'Danielle Owens', 'Greater Western Sydney Area', 1),
(4, 'Andrew Jackson', 'Southern Sydney Metropolitan Area', 1),
(3, 'Julie James', 'Northern Metropolitan Area of Sydney', 1),
(1, 'Brendan Carter', 'Danielle Novak', 0),
(2, 'Samuel Gomez', 'Sydney Metropolitan Service Committee. Provides H&amp;amp;amp;I, PI and Phoneline services for the combined Sydney Areas, North, South, East and West.', 0),
(23, 'NSW Country', 'Canowindra and other country NSW towns.', 1),
(24, 'Tony Cobb', 'Mooloolaba and Northern Queensland coast', 1),
(26, 'Manning Great Lakes', '', 1),
(28, 'NSW Coffs Kempsey', '', 1),
(31, 'Blue Mountains and Central West', '', 1),
(32, 'Cairns', '', 1),
(33, 'Townsville', '', 1),
(34, 'Toowoomba', '', 1),
(35, 'NA Online Australia', '', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `wp_bmltwf_service_bodies`
--
ALTER TABLE `wp_bmltwf_service_bodies`
  ADD PRIMARY KEY (`service_body_bigint`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
