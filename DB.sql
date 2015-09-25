/*
Navicat MySQL Data Transfer

Source Server         : localhost
Source Server Version : 50624
Source Host           : localhost:3306
Source Database       : flaxxis_tools

Target Server Type    : MYSQL
Target Server Version : 50624
File Encoding         : 65001

Date: 2015-09-25 16:07:24
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for `ws_backup`
-- ----------------------------
DROP TABLE IF EXISTS `ws_backup`;
CREATE TABLE `ws_backup` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `Ctime` datetime NOT NULL,
  `Name` varchar(255) DEFAULT NULL,
  `File` varchar(255) DEFAULT NULL,
  `Size` int(11) DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ws_backup
-- ----------------------------

-- ----------------------------
-- Table structure for `ws_calendar`
-- ----------------------------
DROP TABLE IF EXISTS `ws_calendar`;
CREATE TABLE `ws_calendar` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `Title` varchar(255) NOT NULL,
  `CustomerId` int(11) NOT NULL,
  `StatusId` tinyint(4) NOT NULL DEFAULT '0',
  `Start` datetime DEFAULT NULL,
  `End` datetime DEFAULT NULL,
  `IsAllDay` tinyint(4) DEFAULT NULL,
  `Description` text,
  `Classes` varchar(255) DEFAULT NULL,
  `Icon` varchar(255) DEFAULT NULL,
  `Ctime` datetime DEFAULT NULL,
  `Utime` datetime DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ws_calendar
-- ----------------------------

-- ----------------------------
-- Table structure for `ws_config`
-- ----------------------------
DROP TABLE IF EXISTS `ws_config`;
CREATE TABLE `ws_config` (
  `Id` int(10) NOT NULL AUTO_INCREMENT,
  `Code` varchar(255) NOT NULL DEFAULT '',
  `Value` text NOT NULL,
  `Description` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`Id`),
  KEY `code` (`Code`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ws_config
-- ----------------------------
INSERT INTO `ws_config` VALUES ('1', 'site_title', 'Test', 'Название сайта');
INSERT INTO `ws_config` VALUES ('2', 'site_email', 'test@gmail.com', 'Email сайта');
INSERT INTO `ws_config` VALUES ('3', 'email_name', 'Test', 'Имя отправщика');
INSERT INTO `ws_config` VALUES ('4', 'watermark_image', '', 'Защита изображений');
INSERT INTO `ws_config` VALUES ('5', 'text_editor', 'tinyMCE', '');
INSERT INTO `ws_config` VALUES ('6', 'debugReport', '0', '');

-- ----------------------------
-- Table structure for `ws_customers`
-- ----------------------------
DROP TABLE IF EXISTS `ws_customers`;
CREATE TABLE `ws_customers` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `ParentId` int(11) DEFAULT '0',
  `HashId` varchar(32) NOT NULL DEFAULT '',
  `Username` varchar(255) NOT NULL DEFAULT '',
  `StatusId` int(11) DEFAULT NULL,
  `TypeId` int(11) DEFAULT NULL,
  `Password` varchar(255) NOT NULL DEFAULT '',
  `CompanyName` varchar(255) DEFAULT NULL,
  `FirstName` varchar(255) NOT NULL DEFAULT '',
  `MiddleName` varchar(255) DEFAULT '',
  `LastName` varchar(255) NOT NULL DEFAULT '',
  `Gender` char(1) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `DateBirth` date NOT NULL DEFAULT '0000-00-00',
  `Email` varchar(255) NOT NULL DEFAULT '',
  `Phone` varchar(255) NOT NULL DEFAULT '',
  `Ctime` timestamp NULL DEFAULT '0000-00-00 00:00:00' COMMENT 'create time',
  `Utime` timestamp NULL DEFAULT '0000-00-00 00:00:00' COMMENT 'updatetime',
  `VisitTime` timestamp NULL DEFAULT '0000-00-00 00:00:00',
  `Ip` varchar(30) DEFAULT NULL,
  `Description` text,
  `Ban` tinyint(4) DEFAULT '0',
  `Ava` varchar(255) DEFAULT NULL,
  `HashVisit` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `hash_id` (`HashId`),
  KEY `email` (`Email`),
  KEY `parent_id` (`ParentId`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ws_customers
-- ----------------------------
INSERT INTO `ws_customers` VALUES ('3', '0', '', 'Superadmin', '1', '200', '1446a539243ff4d8c17e732cc541d5544f8cbc18df03cb3ad8165f7f6faba1ee', '', 'User', 'U', 'U', '', '0000-00-00', 'test@gmail.com', '1111111', '0000-00-00 00:00:00', '2015-09-25 13:10:13', '2015-09-25 13:08:31', '', '', '0', '', '');

-- ----------------------------
-- Table structure for `ws_files`
-- ----------------------------
DROP TABLE IF EXISTS `ws_files`;
CREATE TABLE `ws_files` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `TypeId` int(11) DEFAULT NULL,
  `Name` varchar(255) NOT NULL DEFAULT '',
  `Description` text NOT NULL,
  `Filename` varchar(255) NOT NULL DEFAULT '',
  `Location` varchar(255) NOT NULL DEFAULT '',
  `Size` int(11) NOT NULL DEFAULT '0',
  `HeaderType` varchar(255) NOT NULL DEFAULT 'application/octet-stream',
  `Downloads` int(11) NOT NULL DEFAULT '0',
  `Ctime` timestamp NULL DEFAULT NULL,
  `Utime` timestamp NULL DEFAULT NULL,
  `Sequence` int(11) DEFAULT NULL,
  `SmallImage` varchar(255) DEFAULT NULL,
  `GalleryId` int(11) DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `file_type` (`TypeId`),
  KEY `filename` (`Filename`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ws_files
-- ----------------------------

-- ----------------------------
-- Table structure for `ws_gallery`
-- ----------------------------
DROP TABLE IF EXISTS `ws_gallery`;
CREATE TABLE `ws_gallery` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `Name` varchar(255) NOT NULL,
  `TypeId` int(11) NOT NULL DEFAULT '1',
  `Description` text,
  PRIMARY KEY (`Id`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;

-- ----------------------------
-- Records of ws_gallery
-- ----------------------------

-- ----------------------------
-- Table structure for `ws_log`
-- ----------------------------
DROP TABLE IF EXISTS `ws_log`;
CREATE TABLE `ws_log` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `Timestamp` varchar(255) NOT NULL DEFAULT '',
  `Priority` varchar(255) NOT NULL DEFAULT '',
  `HashVisit` varchar(32) NOT NULL DEFAULT '',
  `Message` text NOT NULL,
  `Url` varchar(255) NOT NULL DEFAULT '',
  `ReffererUrl` varchar(255) NOT NULL DEFAULT '',
  `PriorityName` varchar(255) NOT NULL DEFAULT '',
  `Params` text,
  PRIMARY KEY (`Id`),
  KEY `level` (`Priority`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ws_log
-- ----------------------------

-- ----------------------------
-- Table structure for `ws_menus`
-- ----------------------------
DROP TABLE IF EXISTS `ws_menus`;
CREATE TABLE `ws_menus` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `ParentId` int(11) DEFAULT NULL,
  `TypeId` int(11) DEFAULT NULL,
  `NoDelete` tinyint(4) NOT NULL DEFAULT '0',
  `Name` varchar(255) NOT NULL DEFAULT '',
  `Url` varchar(255) NOT NULL DEFAULT '',
  `Controller` varchar(255) NOT NULL DEFAULT '',
  `Action` varchar(255) NOT NULL DEFAULT '',
  `Parameter` varchar(255) DEFAULT NULL,
  `PageTitle` varchar(255) DEFAULT NULL,
  `PageIntro` text,
  `PageBody` text,
  `Image` varchar(255) DEFAULT NULL,
  `RedirectUrl` varchar(255) DEFAULT NULL,
  `Sequence` int(11) DEFAULT '0',
  `MetatagKeywords` text,
  `MetatagDescription` text,
  PRIMARY KEY (`Id`),
  KEY `parent_id` (`ParentId`),
  KEY `sequence` (`Sequence`),
  KEY `url` (`Url`),
  KEY `name` (`Name`),
  KEY `parameter` (`Parameter`),
  KEY `type` (`TypeId`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ws_menus
-- ----------------------------
INSERT INTO `ws_menus` VALUES ('1', null, '1', '1', 'Главня', '/', 'index', 'index', null, null, null, null, null, null, '0', null, null);

-- ----------------------------
-- Table structure for `ws_news`
-- ----------------------------
DROP TABLE IF EXISTS `ws_news`;
CREATE TABLE `ws_news` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `Title` varchar(255) DEFAULT NULL,
  `Ctime` timestamp NULL DEFAULT NULL,
  `Utime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `Intro` text,
  `Content` longtext,
  `Start` timestamp NULL DEFAULT '0000-00-00 00:00:00',
  `End` timestamp NULL DEFAULT '0000-00-00 00:00:00',
  `Status` tinyint(4) DEFAULT NULL,
  `Image` varchar(255) DEFAULT NULL,
  `Keywords` varchar(255) NOT NULL,
  `Description` varchar(255) NOT NULL,
  PRIMARY KEY (`Id`),
  KEY `ctime` (`Ctime`),
  KEY `utime` (`Utime`),
  KEY `status` (`Status`),
  KEY `start_datetime` (`Start`),
  KEY `title` (`Title`),
  KEY `end_datetime` (`End`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ws_news
-- ----------------------------

-- ----------------------------
-- Table structure for `ws_visits`
-- ----------------------------
DROP TABLE IF EXISTS `ws_visits`;
CREATE TABLE `ws_visits` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `Hash` varchar(32) NOT NULL DEFAULT '',
  `CustomerId` int(11) DEFAULT NULL,
  `Ctime` timestamp NULL DEFAULT '0000-00-00 00:00:00',
  `Utime` timestamp NULL DEFAULT '0000-00-00 00:00:00',
  `TotalNumberOfPages` int(11) NOT NULL DEFAULT '0',
  `DurationInMinutes` int(11) NOT NULL DEFAULT '0',
  `StartUrl` text NOT NULL,
  `EndUrl` text NOT NULL,
  `ReferrerUrl` text NOT NULL,
  PRIMARY KEY (`Id`),
  KEY `hash_id` (`Hash`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of ws_visits
-- ----------------------------
