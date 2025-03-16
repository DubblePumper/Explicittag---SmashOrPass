-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Gegenereerd op: 27 feb 2025 om 12:30
-- Serverversie: 8.0.40
-- PHP-versie: 8.1.31

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `movkcltg_explicittags`
--
USE `movkcltg_explicittags`;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `performers`
--

CREATE TABLE IF NOT EXISTS `performers` (
  `id` varchar(100) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `bio` text,
  `rating` decimal(3,2) DEFAULT NULL,
  `is_parent` tinyint(1) DEFAULT NULL,
  `gender` varchar(50) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `deathday` date DEFAULT NULL,
  `birthplace` varchar(255) DEFAULT NULL,
  `ethnicity` varchar(255) DEFAULT NULL,
  `nationality` varchar(255) DEFAULT NULL,
  `hair_color` varchar(50) DEFAULT NULL,
  `eye_color` varchar(50) DEFAULT NULL,
  `height` varchar(10) DEFAULT NULL,
  `weight` varchar(10) DEFAULT NULL,
  `measurements` varchar(20) DEFAULT NULL,
  `waist_size` varchar(10) DEFAULT NULL,
  `hip_size` varchar(10) DEFAULT NULL,
  `cup_size` varchar(10) DEFAULT NULL,
  `tattoos` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `piercings` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `fake_boobs` tinyint(1) DEFAULT NULL,
  `same_sex_only` tinyint(1) DEFAULT NULL,
  `career_start_year` int DEFAULT NULL,
  `career_end_year` int DEFAULT NULL,
  `image_amount` int DEFAULT NULL,
  `image_folder` varchar(255) DEFAULT NULL,
  `page` int DEFAULT NULL,
  `performer_number` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name_soundex` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `performer_images`
--

CREATE TABLE IF NOT EXISTS `performer_images` (
  `id` int NOT NULL AUTO_INCREMENT,
  `performer_id` varchar(100) DEFAULT NULL,
  `image_url` text,
  PRIMARY KEY (`id`),
  KEY `performer_id` (`performer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=151394 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `processed_videos`
--

CREATE TABLE IF NOT EXISTS `processed_videos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `source_type` enum('url','upload') NOT NULL,
  `source_path` varchar(255) DEFAULT '',
  `viewkey`VARCHAR(50) DEFAULT '0'
  `video_url` text,
  `processing_status` enum('pending','processing','completed','failed') DEFAULT 'pending',
  `result_data` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `user_ip` varchar(45) DEFAULT NULL,
  `download_progress` float DEFAULT '0',
  `status_message` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `processing_status` (`processing_status`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `supported_adult_websites`
--

CREATE TABLE IF NOT EXISTS `supported_adult_websites` (
  `website_id` int NOT NULL AUTO_INCREMENT,
  `website_name` varchar(255) NOT NULL,
  `website_url` text NOT NULL,
  PRIMARY KEY (`website_id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Gegevens worden geëxporteerd voor tabel `supported_adult_websites`
--

INSERT INTO `supported_adult_websites` (`website_id`, `website_name`, `website_url`) VALUES
(1, 'Pornhub', 'https://pornhub.com'),
(2, 'XVideos', 'https://xvideos.com'),
(3, 'XNXX', 'https://xnxx.com'),
(4, 'RedTube', 'https://redtube.com'),
(5, 'YouPorn', 'https://youporn.com'),
(6, 'xHamster', 'https://xhamster.com'),
(7, 'Tnaflix', 'https://tnaflix.com'),
(8, 'Tube8', 'https://tube8.com'),
(9, 'SpankBang', 'https://spankbang.com');

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `user_choices`
--

CREATE TABLE IF NOT EXISTS `user_choices` (
  `id` int NOT NULL AUTO_INCREMENT,
  `session_id` varchar(255) NOT NULL,
  `chosen_performer_id` varchar(100) NOT NULL,
  `rejected_performer_id` varchar(100) NOT NULL,
  `choice_time` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `session_id` (`session_id`),
  KEY `chosen_performer_id` (`chosen_performer_id`),
  KEY `rejected_performer_id` (`rejected_performer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Indexen voor geëxporteerde tabellen
--

--
-- Indexen voor tabel `performers`
--
ALTER TABLE `performers` ADD FULLTEXT KEY `ft_name` (`name`);
ALTER TABLE `performers` ADD FULLTEXT KEY `name_fulltext` (`name`);

--
-- Beperkingen voor geëxporteerde tabellen
--

--
-- Beperkingen voor tabel `performer_images`
--
ALTER TABLE `performer_images`
  ADD CONSTRAINT `performer_images_ibfk_1` FOREIGN KEY (`performer_id`) REFERENCES `performers` (`id`);

--
-- Beperkingen voor tabel `user_choices`
--
ALTER TABLE `user_choices`
  ADD CONSTRAINT `user_choices_ibfk_1` FOREIGN KEY (`chosen_performer_id`) REFERENCES `performers` (`id`),
  ADD CONSTRAINT `user_choices_ibfk_2` FOREIGN KEY (`rejected_performer_id`) REFERENCES `performers` (`id`);

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
