-- --------------------------------------------------------
-- Värd:                         127.0.0.1
-- Serverversion:                9.5.0 - MySQL Community Server - GPL
-- Server-OS:                    Win64
-- HeidiSQL Version:             12.13.0.7147
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumpar databasstruktur för quacker
DROP DATABASE IF EXISTS `quacker`;
CREATE DATABASE IF NOT EXISTS `quacker` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `quacker`;

-- Dumpar struktur för tabell quacker.comments
DROP TABLE IF EXISTS `comments`;
CREATE TABLE IF NOT EXISTS `comments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `quack_id` int DEFAULT NULL COMMENT 'ON DELETE CASCADE',
  `user_id` int DEFAULT NULL COMMENT 'ON DELETE CASCADE',
  `content` varchar(280) NOT NULL,
  `created_at` timestamp NULL DEFAULT (now()),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `fk_comments_quack_new` (`quack_id`),
  CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`quack_id`) REFERENCES `quacks` (`id`),
  CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_comments_quack` FOREIGN KEY (`quack_id`) REFERENCES `quacks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_comments_quack_cascade` FOREIGN KEY (`quack_id`) REFERENCES `quacks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_comments_quack_new` FOREIGN KEY (`quack_id`) REFERENCES `quacks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumpar data för tabell quacker.comments: ~5 rows (ungefär)
DELETE FROM `comments`;
INSERT INTO `comments` (`id`, `quack_id`, `user_id`, `content`, `created_at`) VALUES
	(6, 69, 1, 'test🙃', '2026-05-11 12:43:41'),
	(10, 72, 4, 'test', '2026-05-11 18:33:09'),
	(11, 72, 4, 'test', '2026-05-11 18:33:18'),
	(12, 72, 4, 'test', '2026-05-11 18:33:35'),
	(15, 72, 4, 'test', '2026-05-11 18:34:04');

-- Dumpar struktur för tabell quacker.follows
DROP TABLE IF EXISTS `follows`;
CREATE TABLE IF NOT EXISTS `follows` (
  `follower_id` int DEFAULT NULL COMMENT 'ON DELETE CASCADE',
  `following_id` int DEFAULT NULL COMMENT 'ON DELETE CASCADE',
  `created_at` timestamp NULL DEFAULT (now()),
  KEY `follower_id` (`follower_id`),
  KEY `following_id` (`following_id`),
  CONSTRAINT `follows_ibfk_1` FOREIGN KEY (`follower_id`) REFERENCES `users` (`id`),
  CONSTRAINT `follows_ibfk_2` FOREIGN KEY (`following_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Sammansatt PK (follower_id, following_id)';

-- Dumpar data för tabell quacker.follows: ~3 rows (ungefär)
DELETE FROM `follows`;
INSERT INTO `follows` (`follower_id`, `following_id`, `created_at`) VALUES
	(4, 1, '2026-05-06 17:02:37'),
	(1, 6, '2026-05-11 08:34:23'),
	(1, 13, '2026-05-11 08:34:24');

-- Dumpar struktur för tabell quacker.hashtags
DROP TABLE IF EXISTS `hashtags`;
CREATE TABLE IF NOT EXISTS `hashtags` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tag_name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tag_name` (`tag_name`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumpar data för tabell quacker.hashtags: ~5 rows (ungefär)
DELETE FROM `hashtags`;
INSERT INTO `hashtags` (`id`, `tag_name`) VALUES
	(1, 'cool'),
	(2, 'quackify'),
	(3, 'btc'),
	(5, 'wsg'),
	(6, 'coolness');

-- Dumpar struktur för tabell quacker.likes
DROP TABLE IF EXISTS `likes`;
CREATE TABLE IF NOT EXISTS `likes` (
  `user_id` int DEFAULT NULL COMMENT 'ON DELETE CASCADE',
  `quack_id` int DEFAULT NULL COMMENT 'ON DELETE CASCADE',
  `created_at` timestamp NULL DEFAULT (now()),
  KEY `user_id` (`user_id`),
  KEY `fk_likes_quack` (`quack_id`),
  CONSTRAINT `fk_likes_quack` FOREIGN KEY (`quack_id`) REFERENCES `quacks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `likes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `likes_ibfk_2` FOREIGN KEY (`quack_id`) REFERENCES `quacks` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Sammansatt PK (user_id, quack_id)';

-- Dumpar data för tabell quacker.likes: ~3 rows (ungefär)
DELETE FROM `likes`;
INSERT INTO `likes` (`user_id`, `quack_id`, `created_at`) VALUES
	(1, 14, '2026-04-16 14:25:17'),
	(4, 17, '2026-05-06 16:13:01'),
	(13, 13, '2026-05-11 08:52:55');

-- Dumpar struktur för tabell quacker.messages
DROP TABLE IF EXISTS `messages`;
CREATE TABLE IF NOT EXISTS `messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sender_id` int DEFAULT NULL,
  `receiver_id` int DEFAULT NULL,
  `message_text` text,
  `image_path` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT (now()),
  PRIMARY KEY (`id`),
  KEY `sender_id` (`sender_id`),
  KEY `receiver_id` (`receiver_id`),
  CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`),
  CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumpar data för tabell quacker.messages: ~23 rows (ungefär)
DELETE FROM `messages`;
INSERT INTO `messages` (`id`, `sender_id`, `receiver_id`, `message_text`, `image_path`, `is_read`, `created_at`) VALUES
	(22, 4, 1, 'hello', NULL, 1, '2026-05-05 12:26:12'),
	(24, 4, 1, 'yo', NULL, 1, '2026-05-05 14:21:30'),
	(26, 4, 1, 'answer me bruh', NULL, 1, '2026-05-05 14:25:41'),
	(27, 1, 4, 'aight bet', NULL, 1, '2026-05-05 14:25:54'),
	(28, 4, 1, 'wsg', NULL, 1, '2026-05-05 14:38:09'),
	(29, 1, 4, 'yo', NULL, 1, '2026-05-06 16:35:28'),
	(30, 4, 1, 'wsg', NULL, 1, '2026-05-06 16:37:44'),
	(31, 4, 1, 'bruh', NULL, 1, '2026-05-06 16:42:48'),
	(32, 4, 1, 'yo', NULL, 1, '2026-05-06 16:43:14'),
	(33, 4, 1, 'yo again', NULL, 1, '2026-05-06 16:43:22'),
	(34, 4, 1, 'yo test noti', NULL, 1, '2026-05-06 16:48:52'),
	(35, 4, 1, 'test again', NULL, 1, '2026-05-06 16:49:08'),
	(36, 4, 1, 'test noti', NULL, 1, '2026-05-06 16:59:17'),
	(37, 4, 1, 'test again', NULL, 1, '2026-05-06 16:59:41'),
	(38, 4, 1, 'yo', NULL, 1, '2026-05-06 17:00:55'),
	(39, 4, 1, 'Wsg', NULL, 1, '2026-05-07 08:39:21'),
	(42, 1, 4, 'cool place huh', 'b1448871fa87bcac01cb.jpg', 1, '2026-05-11 07:02:19'),
	(43, 1, 4, 'video', NULL, 1, '2026-05-11 19:46:45'),
	(44, 1, 4, 'heres a video', '4bdf0aa1ee8d88e64c9d.mp4', 1, '2026-05-11 19:49:15'),
	(45, 4, 1, 'bet', NULL, 1, '2026-05-11 21:03:05'),
	(46, 4, 1, 'yo', NULL, 1, '2026-05-11 21:06:38'),
	(47, 4, 1, 'whats up brah', NULL, 1, '2026-05-11 21:06:45'),
	(48, 1, 13, 'hi', NULL, 0, '2026-05-17 15:17:30');

-- Dumpar struktur för tabell quacker.notifications
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL COMMENT 'Mottagare',
  `source_user_id` int DEFAULT NULL COMMENT 'Sändare/Triggare',
  `type` varchar(255) DEFAULT NULL COMMENT 'mention, message, like, follow, comment',
  `source_id` int DEFAULT NULL COMMENT 'ID för quack eller meddelande',
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT (now()),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `source_user_id` (`source_user_id`),
  KEY `fk_notifications_quack` (`source_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`source_user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=171 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumpar data för tabell quacker.notifications: ~24 rows (ungefär)
DELETE FROM `notifications`;
INSERT INTO `notifications` (`id`, `user_id`, `source_user_id`, `type`, `source_id`, `is_read`, `created_at`) VALUES
	(29, 1, 4, 'like', 17, 1, '2026-05-06 16:13:01'),
	(31, 1, 4, 'requack', 15, 1, '2026-05-06 16:17:47'),
	(46, 4, 1, 'quack', 49, 1, '2026-05-06 17:04:30'),
	(55, 4, 1, 'quack', 54, 1, '2026-05-06 17:43:08'),
	(101, 4, 1, 'requack', 69, 1, '2026-05-08 10:33:56'),
	(104, 4, 1, 'message', 42, 1, '2026-05-11 07:02:19'),
	(105, 1, 13, 'requack', 15, 1, '2026-05-11 07:12:48'),
	(114, 6, 1, 'follow', 1, 0, '2026-05-11 08:34:23'),
	(115, 13, 1, 'follow', 1, 1, '2026-05-11 08:34:24'),
	(133, 1, 13, 'like', 13, 1, '2026-05-11 08:52:55'),
	(137, 4, 1, 'comment', 69, 1, '2026-05-11 12:43:41'),
	(138, 4, 1, 'comment', 69, 1, '2026-05-11 12:44:36'),
	(139, 4, 1, 'comment', 69, 1, '2026-05-11 12:51:20'),
	(140, 4, 1, 'comment', 69, 1, '2026-05-11 12:51:33'),
	(141, 1, 4, 'comment', 72, 1, '2026-05-11 18:33:09'),
	(142, 1, 4, 'comment', 72, 1, '2026-05-11 18:33:18'),
	(143, 1, 4, 'comment', 72, 1, '2026-05-11 18:33:35'),
	(144, 1, 4, 'comment', 49, 1, '2026-05-11 18:33:43'),
	(145, 1, 4, 'comment', 72, 1, '2026-05-11 18:34:05'),
	(146, 1, 4, 'comment', 15, 1, '2026-05-11 18:42:56'),
	(147, 1, 4, 'comment', 15, 1, '2026-05-11 18:44:17'),
	(148, 4, 1, 'message', 43, 1, '2026-05-11 19:46:45'),
	(161, 4, 1, 'quack', 103, 1, '2026-05-11 20:21:13'),
	(170, 4, 1, 'quack', 114, 0, '2026-05-17 20:38:47');

-- Dumpar struktur för tabell quacker.quack_hashtags
DROP TABLE IF EXISTS `quack_hashtags`;
CREATE TABLE IF NOT EXISTS `quack_hashtags` (
  `quack_id` int DEFAULT NULL COMMENT 'ON DELETE CASCADE',
  `hashtag_id` int DEFAULT NULL COMMENT 'ON DELETE CASCADE',
  KEY `hashtag_id` (`hashtag_id`),
  KEY `fk_quack_hashtags_quack` (`quack_id`),
  CONSTRAINT `fk_quack_hashtags_quack` FOREIGN KEY (`quack_id`) REFERENCES `quacks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `quack_hashtags_ibfk_2` FOREIGN KEY (`hashtag_id`) REFERENCES `hashtags` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Many to many för trending tags';

-- Dumpar data för tabell quacker.quack_hashtags: ~2 rows (ungefär)
DELETE FROM `quack_hashtags`;
INSERT INTO `quack_hashtags` (`quack_id`, `hashtag_id`) VALUES
	(54, 1),
	(69, 2);

-- Dumpar struktur för tabell quacker.quack_images
DROP TABLE IF EXISTS `quack_images`;
CREATE TABLE IF NOT EXISTS `quack_images` (
  `id` int NOT NULL AUTO_INCREMENT,
  `quack_id` int DEFAULT NULL COMMENT 'ON DELETE CASCADE',
  `image_path` varchar(255) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_images_quack` (`quack_id`),
  CONSTRAINT `fk_images_quack` FOREIGN KEY (`quack_id`) REFERENCES `quacks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `quack_images_ibfk_1` FOREIGN KEY (`quack_id`) REFERENCES `quacks` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumpar data för tabell quacker.quack_images: ~19 rows (ungefär)
DELETE FROM `quack_images`;
INSERT INTO `quack_images` (`id`, `quack_id`, `image_path`, `file_type`) VALUES
	(6, 11, 'uploads/quacks/7174b44f3835c093bbcda46347a41b5e.jpg', 'image/jpeg'),
	(7, 11, 'uploads/quacks/0ad5e03f9f8e124eca9a9038c7db0a39.jpg', 'image/jpeg'),
	(8, 12, 'uploads/quacks/785a7533158cf4bb536786ba3a98149b.jpg', 'image/jpeg'),
	(9, 12, 'uploads/quacks/b98757f0e5a6b31fb39cb653174ffd16.jpg', 'image/jpeg'),
	(10, 12, 'uploads/quacks/d52f769185e0a35192c4821d1a70281e.jpg', 'image/jpeg'),
	(11, 12, 'uploads/quacks/4e93a2c83fc46029c46004c42f5be33b.jpg', 'image/jpeg'),
	(12, 13, 'uploads/quacks/c2b69c7eb47b1135b70a6210bddb297d.jpg', 'image/jpeg'),
	(13, 14, 'uploads/quacks/c315884682372963e8479287548c49f1.jpg', 'image/jpeg'),
	(14, 14, 'uploads/quacks/e89045fb5896629c3aa9e0e752473c32.jpg', 'image/jpeg'),
	(15, 14, 'uploads/quacks/111e229c446c64ae34fcab36ea0c517c.jpg', 'image/jpeg'),
	(16, 15, 'uploads/quacks/0a413b1118d557ed35608a9943c90407.jpg', 'image/jpeg'),
	(17, 15, 'uploads/quacks/564ea0737db82a21f62074ccd7118e24.jpg', 'image/jpeg'),
	(18, 15, 'uploads/quacks/9f384a4387e664d85ec7d6c7b71829f7.jpg', 'image/jpeg'),
	(19, 15, 'uploads/quacks/800fdc274b7c65b88f7c5919779e80f2.jpg', 'image/jpeg'),
	(37, 103, 'uploads/quacks/e9efbd8175b5e9353df64b6e8b666f29.mp4', 'video/mp4'),
	(38, 103, 'uploads/quacks/c8a867351c74ad36a47dd7f92c09a5a4.png', 'image/png'),
	(39, 103, 'uploads/quacks/4ec7d52fc2da537981c482e8d5076ce6.png', 'image/png'),
	(40, 103, 'uploads/quacks/18fc84ae655b0f3478385baaa2eed447.png', 'image/png'),
	(50, 114, 'uploads/quacks/b386d6bacd4c316f04fd1be3dedcb9a5.png', 'image/png');

-- Dumpar struktur för tabell quacker.quacks
DROP TABLE IF EXISTS `quacks`;
CREATE TABLE IF NOT EXISTS `quacks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL COMMENT 'Skaparen',
  `parent_id` int DEFAULT NULL COMMENT 'För Re-quacks (självrefererande)',
  `content` varchar(280) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT (now()),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `parent_id` (`parent_id`),
  CONSTRAINT `quacks_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `quacks_ibfk_2` FOREIGN KEY (`parent_id`) REFERENCES `quacks` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=115 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumpar data för tabell quacker.quacks: ~14 rows (ungefär)
DELETE FROM `quacks`;
INSERT INTO `quacks` (`id`, `user_id`, `parent_id`, `content`, `created_at`) VALUES
	(11, 1, NULL, 'Cool images!', '2026-04-13 12:41:07'),
	(12, 1, NULL, '4 goated images!🍎', '2026-04-13 12:46:09'),
	(13, 1, NULL, 'bruh', '2026-04-13 13:08:11'),
	(14, 1, NULL, '3 pics!', '2026-04-13 13:08:32'),
	(15, 1, NULL, 'dw', '2026-04-17 09:22:05'),
	(17, 1, NULL, 'increasing my quacktivity! 😀', '2026-05-03 13:10:44'),
	(48, 4, 15, NULL, '2026-05-06 16:17:47'),
	(49, 1, NULL, 'cool quack! 🦆', '2026-05-06 17:04:30'),
	(54, 1, NULL, 'testing hashtags #cool', '2026-05-06 17:43:08'),
	(69, 4, NULL, 'this is a quack #quackify', '2026-05-07 17:02:19'),
	(72, 1, 69, NULL, '2026-05-08 10:33:56'),
	(74, 13, 15, NULL, '2026-05-11 07:12:48'),
	(103, 1, NULL, 'test', '2026-05-11 20:21:13'),
	(114, 1, NULL, 'test', '2026-05-17 20:38:47');

-- Dumpar struktur för tabell quacker.users
DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `display_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL COMMENT 'Hashat med password_hash()',
  `profile_image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'default_pfp.jpg',
  `bio` text,
  `is_admin` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT (now()),
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumpar data för tabell quacker.users: ~4 rows (ungefär)
DELETE FROM `users`;
INSERT INTO `users` (`id`, `username`, `display_name`, `email`, `password`, `profile_image`, `bio`, `is_admin`, `created_at`, `reset_token`, `reset_expires`) VALUES
	(1, 'david123', 'david', 'david.norberg@elev.ga.ntig.se', '$2y$12$7IlXICR5D1SIbTunBGzFDOFnQaNxO7s.iihoukUHU1xCTu1qRh8E.', 'default_pfp.jpg', 'quack master', 1, '2026-03-31 11:05:17', NULL, NULL),
	(4, 'Larry', 'Larry', 'larrytest@testmail123.com', '$2y$12$p.cWeBWOjGS2bjqxt4lgOOldLj666/1hfo.Jqd/aix8UU1jRdkEl6', 'default_pfp.jpg', NULL, 0, '2026-05-05 12:25:44', NULL, NULL),
	(6, 'Bobby', 'Bobby', 'Bobby@testermail.tester123', '$2y$12$JZe5DM1FZLFKJ54TvLDMNO382jItiKcLhtVLX1sFoR0S4JaIh6tnW', 'default_pfp.jpg', NULL, 0, '2026-05-06 18:56:14', NULL, NULL),
	(13, 'Goat', 'Penguin', '123@mail.com', '$2y$12$JwqhDRB8Y/i4T5KLGDEU3Om6nFnAWMgFRJ0KESKni2OzEaXu6Sqcm', 'a4bd3cff25e7e5fcdc6ef9c9a1e1123b.png', 'im a chinese penguin', 0, '2026-05-08 09:13:38', NULL, NULL);

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
