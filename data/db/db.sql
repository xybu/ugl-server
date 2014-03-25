-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               5.6.16-log - MySQL Community Server (GPL)
-- Server OS:                    Win32
-- HeidiSQL Version:             8.1.0.4545
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

-- Dumping database structure for ugl_test
DROP DATABASE IF EXISTS `ugl_test`;
CREATE DATABASE IF NOT EXISTS `ugl_test` /*!40100 DEFAULT CHARACTER SET latin1 */;
USE `ugl_test`;


-- Dumping structure for table ugl_test.authentications
DROP TABLE IF EXISTS `authentications`;
CREATE TABLE IF NOT EXISTS `authentications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'refer to users.id',
  `provider` varchar(100) NOT NULL,
  `provider_uid` varchar(255) NOT NULL,
  `email` varchar(200) NOT NULL,
  `display_name` varchar(150) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `avatar_url` varchar(300) NOT NULL,
  `website_url` varchar(300) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `provider_uid` (`provider_uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Data exporting was unselected.


-- Dumping structure for table ugl_test.boards
DROP TABLE IF EXISTS `boards`;
CREATE TABLE IF NOT EXISTS `boards` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order` tinyint(3) unsigned DEFAULT NULL,
  `user_id` int(13) unsigned DEFAULT NULL,
  `group_id` int(13) unsigned DEFAULT NULL,
  `title` varchar(128) NOT NULL,
  `description` varchar(150) DEFAULT '',
  `created_at` datetime NOT NULL,
  `last_active_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `creator_user_id` (`user_id`),
  KEY `group_id` (`group_id`),
  CONSTRAINT `creator_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Data exporting was unselected.


-- Dumping structure for table ugl_test.groups
DROP TABLE IF EXISTS `groups`;
CREATE TABLE IF NOT EXISTS `groups` (
  `id` int(13) unsigned NOT NULL AUTO_INCREMENT,
  `status` tinyint(4) DEFAULT '2' COMMENT '0=closed 1=inactive 2=private 3=public',
  `alias` varchar(48) NOT NULL DEFAULT '',
  `description` varchar(200) DEFAULT '',
  `avatar_url` varchar(255) DEFAULT NULL,
  `tags` varchar(255) DEFAULT NULL,
  `creator_user_id` int(13) unsigned NOT NULL,
  `num_of_users` int(11) NOT NULL DEFAULT '1',
  `users` text NOT NULL,
  `created_at` datetime NOT NULL,
  `_preferences` tinytext,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `alias` (`alias`),
  KEY `FK_groups_users` (`creator_user_id`),
  CONSTRAINT `FK_groups_users` FOREIGN KEY (`creator_user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Data exporting was unselected.


-- Dumping structure for table ugl_test.news
DROP TABLE IF EXISTS `news`;
CREATE TABLE IF NOT EXISTS `news` (
  `id` int(13) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `group_id` int(11) unsigned NOT NULL,
  `visibility` bit(1) NOT NULL,
  `category` varchar(32) DEFAULT '',
  `descriptor` smallint(5) unsigned NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `group_id` (`group_id`),
  CONSTRAINT `group_id` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`),
  CONSTRAINT `user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Data exporting was unselected.


-- Dumping structure for table ugl_test.subjects
DROP TABLE IF EXISTS `subjects`;
CREATE TABLE IF NOT EXISTS `subjects` (
  `id` int(13) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(13) unsigned DEFAULT NULL,
  `user_id` int(13) unsigned NOT NULL,
  `group_id` int(13) unsigned DEFAULT NULL,
  `board_id` int(13) unsigned NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `body` tinytext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Data exporting was unselected.


-- Dumping structure for table ugl_test.users
DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(13) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(200) NOT NULL,
  `__password` varchar(200) NOT NULL,
  `nickname` varchar(100) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `avatar_url` varchar(300) DEFAULT NULL,
  `phone` varchar(36) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `__token_active_at` datetime NOT NULL,
  `_preferences` tinytext,
  `_joined_groups` tinytext,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Data exporting was unselected.


-- Dumping structure for table ugl_test.wallets
DROP TABLE IF EXISTS `wallets`;
CREATE TABLE IF NOT EXISTS `wallets` (
  `id` int(13) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(13) unsigned DEFAULT NULL,
  `group_id` int(13) unsigned DEFAULT NULL,
  `name` varchar(48) DEFAULT NULL,
  `description` varchar(200) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Data exporting was unselected.


-- Dumping structure for table ugl_test.wallet_records
DROP TABLE IF EXISTS `wallet_records`;
CREATE TABLE IF NOT EXISTS `wallet_records` (
  `id` int(16) unsigned NOT NULL AUTO_INCREMENT,
  `wallet_id` int(16) unsigned NOT NULL,
  `user_id` int(13) unsigned NOT NULL,
  `category` varchar(64) NOT NULL,
  `sub_category` varchar(64) NOT NULL,
  `amount` int(11) NOT NULL DEFAULT '0',
  `description` varchar(200) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Data exporting was unselected.
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
