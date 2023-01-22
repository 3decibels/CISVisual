-- phpMyAdmin SQL Dump
-- version 2.10.0.2
-- http://www.phpmyadmin.net
-- 
-- Host: localhost
-- Generation Time: May 17, 2008 at 07:23 AM
-- Server version: 5.0.45
-- PHP Version: 5.2.4

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

-- 
-- Database: `cisv`
-- 

-- --------------------------------------------------------

-- 
-- Table structure for table `cis_edits`
-- 

CREATE TABLE `cis_edits` (
  `editID` int(10) unsigned NOT NULL auto_increment,
  `editTransID` mediumint(8) unsigned NOT NULL,
  `titleID` mediumint(8) unsigned NOT NULL,
  `ID_MEMBER` mediumint(8) unsigned NOT NULL,
  `editTable` varchar(50) collate utf8_unicode_ci NOT NULL,
  `editField` varchar(50) collate utf8_unicode_ci NOT NULL,
  `editData` text collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`editID`),
  KEY `editTransID` (`editTransID`),
  KEY `titleID` (`titleID`),
  KEY `ID_MEMBER` (`ID_MEMBER`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `cis_edit_transactions`
-- 

CREATE TABLE `cis_edit_transactions` (
  `editTransID` mediumint(8) unsigned NOT NULL auto_increment,
  `titleID` mediumint(8) unsigned NOT NULL,
  `ID_MEMBER` mediumint(8) unsigned NOT NULL,
  `editTransDate` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `editTransComment` text collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`editTransID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `cis_genre_classifications`
-- 

CREATE TABLE `cis_genre_classifications` (
  `titleID` mediumint(8) unsigned NOT NULL,
  `genreID` smallint(3) unsigned NOT NULL,
  PRIMARY KEY  (`titleID`,`genreID`),
  KEY `genreID` (`genreID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `cis_genre_types`
-- 

CREATE TABLE `cis_genre_types` (
  `genreID` smallint(3) unsigned NOT NULL auto_increment,
  `genreName` varchar(40) collate utf8_unicode_ci NOT NULL,
  `genreDescription` text collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`genreID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=30 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `cis_groups`
-- 

CREATE TABLE `cis_groups` (
  `groupID` smallint(5) unsigned NOT NULL auto_increment,
  `groupName` varchar(255) collate utf8_unicode_ci NOT NULL,
  `groupDescription` text collate utf8_unicode_ci NOT NULL,
  `groupURL` varchar(150) collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`groupID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `cis_group_acl`
-- 

CREATE TABLE `cis_group_acl` (
  `groupID` smallint(5) unsigned NOT NULL,
  `ID_MEMBER` mediumint(8) unsigned NOT NULL,
  `groupRole` enum('member','maintainer','leader') collate utf8_unicode_ci NOT NULL default 'member',
  PRIMARY KEY  (`ID_MEMBER`,`groupID`),
  KEY `groupID` (`groupID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `cis_group_projects`
-- 

CREATE TABLE `cis_group_projects` (
  `projectID` mediumint(8) unsigned NOT NULL auto_increment,
  `groupID` smallint(5) unsigned NOT NULL,
  `titleID` mediumint(8) unsigned NOT NULL,
  `projectName` varchar(255) collate utf8_unicode_ci NOT NULL,
  `projectDescription` text collate utf8_unicode_ci NOT NULL,
  `projectURL` varchar(150) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`projectID`),
  KEY `titleID` (`titleID`),
  KEY `groupID` (`groupID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `cis_group_project_status`
-- 

CREATE TABLE `cis_group_project_status` (
  `projectID` mediumint(8) unsigned NOT NULL,
  `projectUpdateTime` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `ID_MEMBER` mediumint(8) unsigned NOT NULL,
  `projectUpdateText` text collate utf8_unicode_ci NOT NULL,
  `projectStatus` enum('In Progress','Stalled','Dropped','Complete') collate utf8_unicode_ci NOT NULL default 'In Progress',
  `projectPercent` smallint(2) NOT NULL,
  PRIMARY KEY  (`projectID`,`projectUpdateTime`),
  KEY `ID_MEMBER` (`ID_MEMBER`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `cis_images`
-- 

CREATE TABLE `cis_images` (
  `imageID` mediumint(8) unsigned NOT NULL auto_increment,
  `imageFile` varchar(255) collate utf8_unicode_ci NOT NULL,
  `titleID` mediumint(8) unsigned NOT NULL,
  `ID_MEMBER` mediumint(8) unsigned NOT NULL,
  PRIMARY KEY  (`imageID`),
  KEY `titleID` (`titleID`),
  KEY `ID_MEMBER` (`ID_MEMBER`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=8 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `cis_media`
-- 

CREATE TABLE `cis_media` (
  `mediaID` smallint(3) unsigned NOT NULL auto_increment,
  `mediaName` varchar(50) collate utf8_unicode_ci NOT NULL,
  `mediaDescription` text collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`mediaID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `cis_titles`
-- 

CREATE TABLE `cis_titles` (
  `titleID` mediumint(8) unsigned NOT NULL auto_increment,
  `titleName` varchar(255) collate utf8_unicode_ci NOT NULL,
  `mediaID` smallint(3) unsigned NOT NULL default '1',
  `titleDescription` text collate utf8_unicode_ci NOT NULL,
  `titlePros` text collate utf8_unicode_ci,
  `titleCons` text collate utf8_unicode_ci,
  `titleComments` text collate utf8_unicode_ci,
  `titleCreator` varchar(255) collate utf8_unicode_ci NOT NULL,
  `titleYear` year(4) default NULL,
  `titleType` enum('Doujin','Commercial') collate utf8_unicode_ci NOT NULL default 'Commercial',
  `titlePlot` enum('Linear','Branching') collate utf8_unicode_ci NOT NULL default 'Branching',
  `titleAvailable` enum('None','Localized','Licensed') collate utf8_unicode_ci NOT NULL default 'None',
  `titleStatus` enum('active','inactive','pending') collate utf8_unicode_ci NOT NULL default 'pending',
  `titleAdult` tinyint(1) NOT NULL default '0',
  `ID_MEMBER` mediumint(8) unsigned NOT NULL,
  `titleLastUpdate` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `titleImage` tinyint(1) NOT NULL default '0',
  `titleImageType` enum('jpg','gif','png') collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`titleID`),
  UNIQUE KEY `titleName` (`titleName`),
  KEY `ID_MEMBER` (`ID_MEMBER`),
  KEY `titleStatus` (`titleStatus`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=45 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `cis_title_alternates`
-- 

CREATE TABLE `cis_title_alternates` (
  `alternateID` mediumint(8) unsigned NOT NULL auto_increment,
  `titleID` mediumint(8) unsigned NOT NULL,
  `titleName` varchar(255) collate utf8_unicode_ci NOT NULL,
  `titleStatus` enum('active','inactive','pending') collate utf8_unicode_ci NOT NULL default 'pending',
  `mediaID` smallint(3) unsigned NOT NULL default '1',
  PRIMARY KEY  (`alternateID`),
  KEY `titleID` (`titleID`),
  KEY `mediaID` (`mediaID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=24 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `cis_title_links`
-- 

CREATE TABLE `cis_title_links` (
  `linkID` mediumint(8) unsigned NOT NULL auto_increment,
  `titleID` mediumint(8) unsigned NOT NULL,
  `linkName` varchar(255) collate utf8_unicode_ci NOT NULL,
  `linkURL` varchar(255) collate utf8_unicode_ci NOT NULL,
  `linkPriority` smallint(1) NOT NULL default '5',
  PRIMARY KEY  (`linkID`),
  KEY `titleID` (`titleID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `cis_title_ratings`
-- 

CREATE TABLE `cis_title_ratings` (
  `titleID` mediumint(8) unsigned NOT NULL,
  `ID_MEMBER` mediumint(8) unsigned NOT NULL,
  `ratingWeight` smallint(1) NOT NULL,
  `ratingMethod` enum('basic','batch') collate utf8_unicode_ci NOT NULL default 'basic',
  PRIMARY KEY  (`titleID`,`ID_MEMBER`),
  KEY `ID_MEMBER` (`ID_MEMBER`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `cis_title_ratings_batch`
-- 

CREATE TABLE `cis_title_ratings_batch` (
  `titleID` mediumint(8) unsigned NOT NULL,
  `ID_MEMBER` mediumint(8) unsigned NOT NULL,
  `ratingStory` smallint(1) NOT NULL,
  `ratingCharacter` smallint(1) NOT NULL,
  `ratingArt` smallint(1) NOT NULL,
  `ratingMusic` smallint(1) NOT NULL,
  `ratingVoice` smallint(1) default NULL,
  PRIMARY KEY  (`titleID`,`ID_MEMBER`),
  KEY `ID_MEMBER` (`ID_MEMBER`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

-- 
-- Table structure for table `cis_title_walkthroughs`
-- 

CREATE TABLE `cis_title_walkthroughs` (
  `walkID` mediumint(8) unsigned NOT NULL auto_increment,
  `titleID` mediumint(8) unsigned NOT NULL,
  `ID_MEMBER` mediumint(8) unsigned NOT NULL,
  `walkTitle` varchar(255) collate utf8_unicode_ci NOT NULL,
  `walkDate` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `walkAccessed` mediumint(8) unsigned NOT NULL default '0',
  `walkReviewed` tinyint(1) NOT NULL default '0',
  `walkAnonSubmission` tinyint(1) NOT NULL,
  `walkType` enum('text','file','link') collate utf8_unicode_ci NOT NULL,
  `walkLink` varchar(300) collate utf8_unicode_ci default NULL,
  `walkText` text collate utf8_unicode_ci,
  PRIMARY KEY  (`walkID`),
  KEY `ID_MEMBER` (`ID_MEMBER`),
  KEY `titleID` (`titleID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `cis_user_acl`
-- 

CREATE TABLE `cis_user_acl` (
  `ID_MEMBER` mediumint(8) unsigned NOT NULL,
  `userRole` enum('member','restricted','banned','admin','moderator') collate utf8_unicode_ci NOT NULL default 'member',
  PRIMARY KEY  (`ID_MEMBER`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- 
-- Constraints for dumped tables
-- 

-- 
-- Constraints for table `cis_edits`
-- 
ALTER TABLE `cis_edits`
  ADD CONSTRAINT `cis_edits_ibfk_1` FOREIGN KEY (`titleID`) REFERENCES `cis_titles` (`titleID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `cis_edits_ibfk_2` FOREIGN KEY (`ID_MEMBER`) REFERENCES `smf_members` (`ID_MEMBER`) ON DELETE NO ACTION ON UPDATE CASCADE;

-- 
-- Constraints for table `cis_genre_classifications`
-- 
ALTER TABLE `cis_genre_classifications`
  ADD CONSTRAINT `cis_genre_classifications_ibfk_1` FOREIGN KEY (`titleID`) REFERENCES `cis_titles` (`titleID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `cis_genre_classifications_ibfk_2` FOREIGN KEY (`genreID`) REFERENCES `cis_genre_types` (`genreID`) ON DELETE CASCADE ON UPDATE CASCADE;

-- 
-- Constraints for table `cis_group_projects`
-- 
ALTER TABLE `cis_group_projects`
  ADD CONSTRAINT `cis_group_projects_ibfk_2` FOREIGN KEY (`groupID`) REFERENCES `cis_groups` (`groupID`) ON DELETE CASCADE ON UPDATE CASCADE;

-- 
-- Constraints for table `cis_group_project_status`
-- 
ALTER TABLE `cis_group_project_status`
  ADD CONSTRAINT `cis_group_project_status_ibfk_1` FOREIGN KEY (`projectID`) REFERENCES `cis_group_projects` (`projectID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `cis_group_project_status_ibfk_2` FOREIGN KEY (`ID_MEMBER`) REFERENCES `smf_members` (`ID_MEMBER`) ON DELETE NO ACTION ON UPDATE CASCADE;

-- 
-- Constraints for table `cis_images`
-- 
ALTER TABLE `cis_images`
  ADD CONSTRAINT `cis_images_ibfk_1` FOREIGN KEY (`titleID`) REFERENCES `cis_titles` (`titleID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `cis_images_ibfk_2` FOREIGN KEY (`ID_MEMBER`) REFERENCES `smf_members` (`ID_MEMBER`) ON DELETE NO ACTION ON UPDATE CASCADE;

-- 
-- Constraints for table `cis_titles`
-- 
ALTER TABLE `cis_titles`
  ADD CONSTRAINT `cis_titles_ibfk_2` FOREIGN KEY (`ID_MEMBER`) REFERENCES `smf_members` (`ID_MEMBER`) ON DELETE NO ACTION ON UPDATE CASCADE;

-- 
-- Constraints for table `cis_title_alternates`
-- 
ALTER TABLE `cis_title_alternates`
  ADD CONSTRAINT `cis_title_alternates_ibfk_1` FOREIGN KEY (`titleID`) REFERENCES `cis_titles` (`titleID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `cis_title_alternates_ibfk_2` FOREIGN KEY (`mediaID`) REFERENCES `cis_media` (`mediaID`) ON DELETE CASCADE ON UPDATE CASCADE;

-- 
-- Constraints for table `cis_title_links`
-- 
ALTER TABLE `cis_title_links`
  ADD CONSTRAINT `cis_title_links_ibfk_1` FOREIGN KEY (`titleID`) REFERENCES `cis_titles` (`titleID`) ON DELETE CASCADE ON UPDATE CASCADE;

-- 
-- Constraints for table `cis_title_ratings`
-- 
ALTER TABLE `cis_title_ratings`
  ADD CONSTRAINT `cis_title_ratings_ibfk_1` FOREIGN KEY (`titleID`) REFERENCES `cis_titles` (`titleID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `cis_title_ratings_ibfk_2` FOREIGN KEY (`ID_MEMBER`) REFERENCES `smf_members` (`ID_MEMBER`) ON DELETE CASCADE ON UPDATE CASCADE;

-- 
-- Constraints for table `cis_title_ratings_batch`
-- 
ALTER TABLE `cis_title_ratings_batch`
  ADD CONSTRAINT `cis_title_ratings_batch_ibfk_1` FOREIGN KEY (`titleID`) REFERENCES `cis_titles` (`titleID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `cis_title_ratings_batch_ibfk_2` FOREIGN KEY (`ID_MEMBER`) REFERENCES `smf_members` (`ID_MEMBER`) ON DELETE CASCADE ON UPDATE CASCADE;

-- 
-- Constraints for table `cis_title_walkthroughs`
-- 
ALTER TABLE `cis_title_walkthroughs`
  ADD CONSTRAINT `cis_title_walkthroughs_ibfk_1` FOREIGN KEY (`ID_MEMBER`) REFERENCES `smf_members` (`ID_MEMBER`) ON DELETE NO ACTION ON UPDATE CASCADE,
  ADD CONSTRAINT `cis_title_walkthroughs_ibfk_2` FOREIGN KEY (`titleID`) REFERENCES `cis_titles` (`titleID`) ON DELETE CASCADE ON UPDATE CASCADE;

-- 
-- Constraints for table `cis_user_acl`
-- 
ALTER TABLE `cis_user_acl`
  ADD CONSTRAINT `cis_user_acl_ibfk_1` FOREIGN KEY (`ID_MEMBER`) REFERENCES `smf_members` (`ID_MEMBER`) ON DELETE CASCADE ON UPDATE CASCADE;
