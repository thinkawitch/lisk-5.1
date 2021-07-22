-- phpMyAdmin SQL Dump
-- version 3.4.3.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jul 19, 2011 at 06:37 PM
-- Server version: 5.5.14
-- PHP Version: 5.3.3

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `lisk_5_1`
--

-- --------------------------------------------------------

--
-- Table structure for table `sys_backup`
--

CREATE TABLE IF NOT EXISTS `sys_backup` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `filename` varchar(255) NOT NULL,
  `type` tinyint(3) unsigned NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `sys_backup`
--


-- --------------------------------------------------------

--
-- Table structure for table `sys_cache`
--

CREATE TABLE IF NOT EXISTS `sys_cache` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `url` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT '',
  `cache_time` int(11) unsigned DEFAULT '0',
  `cache_level` varchar(10) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;

--
-- Dumping data for table `sys_cache`
--

INSERT INTO `sys_cache` (`id`, `url`, `cache_time`, `cache_level`) VALUES
(1, 'main/', 5, '*'),
(3, 'example/*/', 5, '*');

-- --------------------------------------------------------

--
-- Table structure for table `sys_content`
--

CREATE TABLE IF NOT EXISTS `sys_content` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) unsigned NOT NULL DEFAULT '0',
  `parents` varchar(255) NOT NULL DEFAULT '',
  `url` varchar(255) NOT NULL DEFAULT '',
  `oder` int(10) unsigned NOT NULL DEFAULT '0',
  `key` varchar(255) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `content` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `sys_content`
--


-- --------------------------------------------------------

--
-- Table structure for table `sys_content_categories`
--

CREATE TABLE IF NOT EXISTS `sys_content_categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) unsigned NOT NULL DEFAULT '0',
  `parents` varchar(255) NOT NULL DEFAULT '',
  `url` varchar(255) NOT NULL DEFAULT '',
  `oder` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

--
-- Dumping data for table `sys_content_categories`
--

INSERT INTO `sys_content_categories` (`id`, `parent_id`, `parents`, `url`, `oder`, `name`) VALUES
(1, 0, '', '', 0, 'Content Blocks'),
(2, 1, '<1>', '', 0, 'General');

-- --------------------------------------------------------

--
-- Table structure for table `sys_cp_groups`
--

CREATE TABLE IF NOT EXISTS `sys_cp_groups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `rights` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

--
-- Dumping data for table `sys_cp_groups`
--

INSERT INTO `sys_cp_groups` (`id`, `name`, `rights`) VALUES
(1, 'Administrators', ''),
(2, 'Developers', '');

-- --------------------------------------------------------

--
-- Table structure for table `sys_cp_logins`
--

CREATE TABLE IF NOT EXISTS `sys_cp_logins` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `login` varchar(255) NOT NULL DEFAULT '',
  `ip` varchar(50) NOT NULL DEFAULT '',
  `level` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `sys_cp_logins`
--


-- --------------------------------------------------------

--
-- Table structure for table `sys_cp_menu`
--

CREATE TABLE IF NOT EXISTS `sys_cp_menu` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `oder` int(10) unsigned NOT NULL DEFAULT '0',
  `parent_id` int(10) unsigned NOT NULL DEFAULT '0',
  `parents` varchar(255) NOT NULL DEFAULT '',
  `is_category` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '',
  `url` varchar(255) NOT NULL DEFAULT '',
  `hint` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=72 ;

--
-- Dumping data for table `sys_cp_menu`
--

INSERT INTO `sys_cp_menu` (`id`, `oder`, `parent_id`, `parents`, `is_category`, `name`, `url`, `hint`) VALUES
(1, 0, 0, '', 1, 'root', '', ''),
(21, 6, 62, '<1><62>', 0, 'Dev Tools', 'dev_tools.php', 'Developer tools'),
(6, 0, 1, '<1>', 0, 'Site Structure', 'scms.php', 'Site Structure management'),
(7, 3, 1, '<1>', 0, 'Content Blocks', 'list_content.php', 'Site content'),
(8, 2, 1, '<1>', 0, 'Modules Management', 'modules.php', 'Modules  Management'),
(9, 6, 1, '<1>', 0, 'File Manager', 'file_manager.php', ''),
(10, 7, 1, '<1>', 0, 'Backup', 'backup.php', ''),
(11, 1, 17, '<1><17>', 0, 'Users and Groups', 'cp_users.php', ''),
(12, 2, 17, '<1><17>', 0, 'CP Menu', 'node_tree.php?type=sys_cp_menu', ''),
(17, 10, 1, '<1>', 1, 'CP Settings', '', ''),
(57, 5, 1, '<1>', 1, 'Templates', '', ''),
(24, 3, 17, '<1><17>', 0, 'CP Paging', 'edit.php?type=paging&cond=name=''cp''', 'Manage CP default paging settings'),
(43, 4, 17, '<1><17>', 0, 'Custom Links', 'cp_custom_links.php', 'Manage Custom Links'),
(44, 9, 1, '<1>', 0, 'CP Messages', 'cp_messages.php', 'Send/Receive messages with other CP users'),
(58, 15, 57, '<1><57>', 0, 'Email Templates', 'list_email.php', 'Email Templates'),
(59, 16, 57, '<1><57>', 0, 'Page Templates', 'list_template.php', 'Page Templates'),
(60, 5, 62, '<1><62>', 0, 'Cache Settings', 'list.php?type=sys_cache', ''),
(61, 8, 62, '<1><62>', 0, 'Cron Jobs', 'cron_jobs.php', ''),
(62, 11, 1, '<1>', 1, 'Dev Tools', '', ''),
(63, 2, 68, '<1><68>', 0, 'Visits Statistics', 'visits_statistics.php', 'Visits Statistics'),
(66, 8, 1, '<1>', 0, 'Mail Dispatcher', 'mail_dispatcher.php', ''),
(68, 1, 1, '<1>', 1, 'Lisk Statistics', '', ''),
(69, 17, 68, '<1><68>', 0, 'Action Statistics', 'action_statistics.php', ''),
(70, 18, 68, '<1><68>', 0, 'Google Analytics', 'google_analytics.php', ''),
(71, 4, 1, '<1>', 0, 'System Footer', 'system_footer.php', '');

-- --------------------------------------------------------

--
-- Table structure for table `sys_cp_messages`
--

CREATE TABLE IF NOT EXISTS `sys_cp_messages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_from` int(10) unsigned NOT NULL DEFAULT '0',
  `id_to` int(10) unsigned NOT NULL DEFAULT '0',
  `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `subject` varchar(255) NOT NULL DEFAULT '',
  `message` text NOT NULL,
  `is_deleted_from` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `is_deleted_to` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `is_read` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `sys_cp_messages`
--


-- --------------------------------------------------------

--
-- Table structure for table `sys_cp_users`
--

CREATE TABLE IF NOT EXISTS `sys_cp_users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `login` varchar(100) NOT NULL DEFAULT '',
  `password` varchar(32) NOT NULL DEFAULT '',
  `email` varchar(100) NOT NULL,
  `sid` varchar(32) NOT NULL DEFAULT '',
  `level` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `lastdate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `lastlogin` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `custom_links` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `login` (`login`),
  KEY `sid` (`sid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

--
-- Dumping data for table `sys_cp_users`
--

INSERT INTO `sys_cp_users` (`id`, `login`, `password`, `email`, `sid`, `level`, `lastdate`, `lastlogin`, `custom_links`) VALUES(1, 'developer', 'a783e0d3f6371547a6ba4115aeff9aa1', '', 'a4c01e8b236f6169e1fcf8985991a95c', 2, '2010-11-08 06:16:10', '2010-11-01 03:39:00', '');
INSERT INTO `sys_cp_users` (`id`, `login`, `password`, `email`, `sid`, `level`, `lastdate`, `lastlogin`, `custom_links`) VALUES(2, 'admin', '8a009043c36d184ce65ab27b0dfdce75', '', '3997a757e873dc9a93ecee5fcfa7b3b6', 1, '2010-11-04 07:11:04', '2010-11-04 02:29:32', '');

-- --------------------------------------------------------

--
-- Table structure for table `sys_cron_jobs`
--

CREATE TABLE IF NOT EXISTS `sys_cron_jobs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `path` varchar(255) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `last_run` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `periodicity` int(10) unsigned NOT NULL DEFAULT '0',
  `object` varchar(30) NOT NULL DEFAULT '',
  `method` varchar(30) NOT NULL DEFAULT '',
  `status` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

--
-- Dumping data for table `sys_cron_jobs`
--

INSERT INTO `sys_cron_jobs` (`id`, `path`, `name`, `last_run`, `periodicity`, `object`, `method`, `status`) VALUES
(1, '', 'self', '2009-05-26 08:37:25', 0, '', '', 1),
(2, 'init/cron/mail_dispatcher.cron.php', 'mail_dispatcher', '2009-05-26 08:37:25', 1, '', 'cron_mail_dispatcher', 1);

-- --------------------------------------------------------

--
-- Table structure for table `sys_di`
--

CREATE TABLE IF NOT EXISTS `sys_di` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `data` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `sys_di`
--


-- --------------------------------------------------------

--
-- Table structure for table `sys_email`
--

CREATE TABLE IF NOT EXISTS `sys_email` (
  `id` varchar(255) NOT NULL DEFAULT '',
  `recipients` text NOT NULL,
  `subject` varchar(255) NOT NULL DEFAULT '',
  `body` text NOT NULL,
  `from_header` varchar(255) NOT NULL DEFAULT '',
  `content_type_header` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `sys_email`
--


-- --------------------------------------------------------

--
-- Table structure for table `sys_email_history`
--

CREATE TABLE IF NOT EXISTS `sys_email_history` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` mediumtext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `sys_email_history`
--


-- --------------------------------------------------------

--
-- Table structure for table `sys_email_history_recipients`
--

CREATE TABLE IF NOT EXISTS `sys_email_history_recipients` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) unsigned NOT NULL,
  `email` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `sys_email_history_recipients`
--


-- --------------------------------------------------------

--
-- Table structure for table `sys_email_queue`
--

CREATE TABLE IF NOT EXISTS `sys_email_queue` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` mediumtext NOT NULL,
  `body` mediumtext NOT NULL,
  `header` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `sys_email_queue`
--


-- --------------------------------------------------------

--
-- Table structure for table `sys_email_queue_recipients`
--

CREATE TABLE IF NOT EXISTS `sys_email_queue_recipients` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) unsigned NOT NULL,
  `email` varchar(255) NOT NULL,
  `params` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `sys_email_queue_recipients`
--


-- --------------------------------------------------------

--
-- Table structure for table `sys_footer`
--

CREATE TABLE IF NOT EXISTS `sys_footer` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `oder` int(10) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `content` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- Dumping data for table `sys_footer`
--

INSERT INTO `sys_footer` (`id`, `oder`, `name`, `content`) VALUES
(1, 1, 'Google Analytics', '');

-- --------------------------------------------------------

--
-- Table structure for table `sys_modules`
--

CREATE TABLE IF NOT EXISTS `sys_modules` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `config` text NOT NULL,
  `object_name` varchar(255) NOT NULL DEFAULT '',
  `name` varchar(50) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `sys_modules`
--


-- --------------------------------------------------------

--
-- Table structure for table `sys_paging`
--

CREATE TABLE IF NOT EXISTS `sys_paging` (
  `name` varchar(255) NOT NULL DEFAULT '',
  `paging_type` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `items_per_page` int(10) unsigned NOT NULL DEFAULT '0',
  `pages_per_page` int(10) unsigned NOT NULL DEFAULT '0',
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `sys_paging`
--

INSERT INTO `sys_paging` (`name`, `paging_type`, `items_per_page`, `pages_per_page`) VALUES
('cp', 1, 20, 10);

-- --------------------------------------------------------

--
-- Table structure for table `sys_profiler`
--

CREATE TABLE IF NOT EXISTS `sys_profiler` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `pageurl` varchar(255) NOT NULL DEFAULT '',
  `total_time` float(10,5) NOT NULL DEFAULT '0.00000',
  `render_time` float(10,5) NOT NULL DEFAULT '0.00000',
  `sql_time` float(10,5) NOT NULL DEFAULT '0.00000',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `sql_log` text NOT NULL,
  `page_cached` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `sys_profiler`
--


-- --------------------------------------------------------

--
-- Table structure for table `sys_settings`
--

CREATE TABLE IF NOT EXISTS `sys_settings` (
  `name` varchar(255) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `sys_settings`
--

INSERT INTO `sys_settings` (`name`, `value`) VALUES
('mail_dispatcher', 'a:1:{s:11:"mailer_type";s:7:"phpmail";}');

-- --------------------------------------------------------

--
-- Table structure for table `sys_ss`
--

CREATE TABLE IF NOT EXISTS `sys_ss` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `oder` int(10) unsigned NOT NULL DEFAULT '0',
  `parent_id` int(10) unsigned NOT NULL DEFAULT '0',
  `parents` varchar(255) NOT NULL DEFAULT '',
  `page_type` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  `global_tpl` varchar(255) NOT NULL DEFAULT '',
  `section_tpl` varchar(255) NOT NULL DEFAULT 'default',
  `subsection_tpl` varchar(255) NOT NULL DEFAULT '',
  `page_tpl` varchar(255) NOT NULL DEFAULT '',
  `url` varchar(255) NOT NULL DEFAULT '',
  `content` mediumtext NOT NULL,
  `cp_handler` varchar(255) NOT NULL DEFAULT '',
  `site_handler` varchar(255) NOT NULL DEFAULT '',
  `is_locked` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `access_level` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `pageset_overview` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `link_href` varchar(255) NOT NULL DEFAULT '',
  `link_open_type` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `link_redirect` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `instance_id` int(10) unsigned NOT NULL DEFAULT '0',
  `hide_from_menu` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `auto_url_generation` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- Dumping data for table `sys_ss`
--

INSERT INTO `sys_ss` (`id`, `oder`, `parent_id`, `parents`, `page_type`, `name`, `title`, `global_tpl`, `section_tpl`, `subsection_tpl`, `page_tpl`, `url`, `content`, `cp_handler`, `site_handler`, `is_locked`, `access_level`, `pageset_overview`, `link_href`, `link_open_type`, `link_redirect`, `instance_id`, `hide_from_menu`, `auto_url_generation`) VALUES
(1, 0, 0, '', 0, 'Home', '', '', 'default', '', '', '', '', '', '', 0, 0, 0, '', 0, 0, 0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `login` varchar(100) NOT NULL DEFAULT '',
  `password` varchar(32) NOT NULL DEFAULT '',
  `email` varchar(255) NOT NULL DEFAULT '',
  `sid` varchar(32) NOT NULL DEFAULT '',
  `level` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `lastdate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `lastlogin` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `login` (`login`),
  KEY `sid` (`sid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
