-- phpMyAdmin SQL Dump
-- version 4.8.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jun 09, 2019 at 03:26 PM
-- Server version: 10.2.23-MariaDB-log-cll-lve
-- PHP Version: 7.2.7

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `openbenc_benches`
--

-- --------------------------------------------------------

--
-- Table structure for table `benches`
--

CREATE TABLE `benches` (
  `benchID` bigint(20) NOT NULL,
  `latitude` float(10,6) NOT NULL,
  `longitude` float(10,6) NOT NULL,
  `address` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `inscription` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `present` tinyint(1) NOT NULL,
  `published` tinyint(1) NOT NULL,
  `added` datetime NOT NULL DEFAULT current_timestamp(),
  `userID` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `benches`
--
DELIMITER $$
CREATE TRIGGER `save_benches_history` BEFORE UPDATE ON `benches` FOR EACH ROW INSERT INTO benches_history
SELECT *, NOW() 
FROM benches 
WHERE benchID = OLD.benchID
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `benches_history`
--

CREATE TABLE `benches_history` (
  `benchID` bigint(20) NOT NULL,
  `latitude` float(10,6) NOT NULL,
  `longitude` float(10,6) NOT NULL,
  `inscription` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `present` tinyint(1) NOT NULL,
  `published` tinyint(1) NOT NULL,
  `added` datetime NOT NULL DEFAULT current_timestamp(),
  `userID` bigint(20) NOT NULL,
  `changed` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `licences`
--

CREATE TABLE `licences` (
  `shortName` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CC BY-SA 4.0',
  `longName` varchar(512) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Creative Commons Attribution-ShareAlike 4.0 International',
  `url` varchar(1024) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'https://creativecommons.org/licenses/by-sa/4.0/'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `licences`
--

INSERT INTO `licences` (`shortName`, `longName`, `url`) VALUES
('CC BY 1.0', 'Creative Commons Attribution 1.0', 'https://creativecommons.org/licenses/by/1.0/'),
('CC BY 2.0', 'Creative Commons Attribution 2.0 Generic', 'https://creativecommons.org/licenses/by/2.0/'),
('CC BY 3.0', 'Creative Commons Attribution 3.0', 'https://creativecommons.org/licenses/by/3.0/'),
('CC BY 4.0', 'Creative Commons Attribution 4.0', 'https://creativecommons.org/licenses/by/4.0/'),
('CC BY-NC 2.0', 'Creative Commons Attribution-NonCommercial 2.0 Generic', 'https://creativecommons.org/licenses/by-nc/2.0/'),
('CC BY-NC-ND 2.0', 'Creative Commons Attribution-NonCommercial-NoDerivs 2.0 Generic', 'https://creativecommons.org/licenses/by-nc-nd/2.0/'),
('CC BY-NC-SA 2.0', 'Attribution-NonCommercial-ShareAlike 2.0 Generic', 'https://creativecommons.org/licenses/by-nc-sa/2.0/'),
('CC BY-ND 2.0', 'Attribution-NoDerivs 2.0 Generic', 'https://creativecommons.org/licenses/by-nd/2.0/'),
('CC BY-SA 1.0', 'Creative Commons Attribution-ShareAlike 1.0', 'https://creativecommons.org/licenses/by-sa/1.0/'),
('CC BY-SA 2.0', 'Creative Commons Attribution-ShareAlike 2.0 Generic ', 'https://creativecommons.org/licenses/by-sa/2.0/'),
('CC BY-SA 3.0', 'Creative Commons Attribution-ShareAlike 3.0', 'https://creativecommons.org/licenses/by-sa/3.0/'),
('CC BY-SA 4.0', 'Creative Commons Attribution-ShareAlike 4.0 International', 'https://creativecommons.org/licenses/by-sa/4.0/'),
('CC Zero', 'Public Domain Dedication', 'https://creativecommons.org/publicdomain/zero/1.0/');

-- --------------------------------------------------------

--
-- Table structure for table `media`
--

CREATE TABLE `media` (
  `mediaID` bigint(20) NOT NULL,
  `benchID` bigint(20) NOT NULL,
  `userID` bigint(20) NOT NULL,
  `sha1` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `importURL` varchar(1024) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `licence` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT 'CC BY-SA 4.0',
  `media_type` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `width` int(11) DEFAULT NULL,
  `height` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `media_types`
--

CREATE TABLE `media_types` (
  `shortName` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `longName` varchar(512) COLLATE utf8mb4_unicode_ci NOT NULL,
  `displayOrder` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `media_types`
--

INSERT INTO `media_types` (`shortName`, `longName`, `displayOrder`) VALUES
('360', 'VR 360 panoramic ', 4),
('bench', 'Long shot of the bench', 1),
('inscription', 'Close-up of the inscription', 0),
('pano', 'Panoramic view', 3),
('view', 'View from the bench', 2);

-- --------------------------------------------------------

--
-- Table structure for table `merged_benches`
--

CREATE TABLE `merged_benches` (
  `benchID` bigint(20) NOT NULL,
  `mergedID` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tags`
--

CREATE TABLE `tags` (
  `tagID` bigint(20) NOT NULL,
  `tagText` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tags`
--

INSERT INTO `tags` (`tagID`, `tagText`) VALUES
(15, '18th Century'),
(14, '19th Century'),
(18, 'beach'),
(1, 'cat'),
(17, 'colleague'),
(2, 'dog'),
(13, 'emoji'),
(8, 'funny'),
(19, 'graveyard'),
(22, 'illegible'),
(9, 'indoors'),
(6, 'metal'),
(11, 'multiple plaques'),
(20, 'park'),
(16, 'picture'),
(10, 'poem'),
(12, 'quote'),
(7, 'stone'),
(21, 'train station'),
(5, 'wood');

-- --------------------------------------------------------

--
-- Table structure for table `tag_map`
--

CREATE TABLE `tag_map` (
  `mapID` bigint(20) NOT NULL,
  `benchID` bigint(20) NOT NULL,
  `tagID` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `userID` bigint(20) NOT NULL,
  `provider` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `providerID` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `benches`
--
ALTER TABLE `benches`
  ADD PRIMARY KEY (`benchID`),
  ADD KEY `contributor` (`userID`);

--
-- Indexes for table `benches_history`
--
ALTER TABLE `benches_history`
  ADD KEY `benchID` (`benchID`),
  ADD KEY `userID` (`userID`);

--
-- Indexes for table `licences`
--
ALTER TABLE `licences`
  ADD PRIMARY KEY (`shortName`);

--
-- Indexes for table `media`
--
ALTER TABLE `media`
  ADD PRIMARY KEY (`mediaID`),
  ADD KEY `contributorID` (`userID`),
  ADD KEY `licence` (`licence`),
  ADD KEY `media_type` (`media_type`),
  ADD KEY `benchID` (`benchID`);

--
-- Indexes for table `media_types`
--
ALTER TABLE `media_types`
  ADD PRIMARY KEY (`shortName`);

--
-- Indexes for table `merged_benches`
--
ALTER TABLE `merged_benches`
  ADD UNIQUE KEY `benchID` (`benchID`),
  ADD KEY `mergedID` (`mergedID`);

--
-- Indexes for table `tags`
--
ALTER TABLE `tags`
  ADD PRIMARY KEY (`tagID`),
  ADD UNIQUE KEY `tagText` (`tagText`);

--
-- Indexes for table `tag_map`
--
ALTER TABLE `tag_map`
  ADD PRIMARY KEY (`mapID`),
  ADD KEY `tag_map_ibfk_1` (`benchID`),
  ADD KEY `tag_map_ibfk_2` (`tagID`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`userID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `benches`
--
ALTER TABLE `benches`
  MODIFY `benchID` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `media`
--
ALTER TABLE `media`
  MODIFY `mediaID` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tags`
--
ALTER TABLE `tags`
  MODIFY `tagID` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `tag_map`
--
ALTER TABLE `tag_map`
  MODIFY `mapID` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `userID` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `benches`
--
ALTER TABLE `benches`
  ADD CONSTRAINT `benches_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `users` (`userID`);

--
-- Constraints for table `benches_history`
--
ALTER TABLE `benches_history`
  ADD CONSTRAINT `benches_history_ibfk_1` FOREIGN KEY (`benchID`) REFERENCES `benches` (`benchID`),
  ADD CONSTRAINT `benches_history_ibfk_2` FOREIGN KEY (`userID`) REFERENCES `users` (`userID`);

--
-- Constraints for table `media`
--
ALTER TABLE `media`
  ADD CONSTRAINT `media_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `users` (`userID`),
  ADD CONSTRAINT `media_ibfk_2` FOREIGN KEY (`licence`) REFERENCES `licences` (`shortName`),
  ADD CONSTRAINT `media_ibfk_3` FOREIGN KEY (`media_type`) REFERENCES `media_types` (`shortName`),
  ADD CONSTRAINT `media_ibfk_4` FOREIGN KEY (`benchID`) REFERENCES `benches` (`benchID`);

--
-- Constraints for table `merged_benches`
--
ALTER TABLE `merged_benches`
  ADD CONSTRAINT `merged_benches_ibfk_1` FOREIGN KEY (`benchID`) REFERENCES `benches` (`benchID`),
  ADD CONSTRAINT `merged_benches_ibfk_2` FOREIGN KEY (`mergedID`) REFERENCES `benches` (`benchID`);

--
-- Constraints for table `tag_map`
--
ALTER TABLE `tag_map`
  ADD CONSTRAINT `tag_map_ibfk_1` FOREIGN KEY (`benchID`) REFERENCES `benches` (`benchID`),
  ADD CONSTRAINT `tag_map_ibfk_2` FOREIGN KEY (`tagID`) REFERENCES `tags` (`tagID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
