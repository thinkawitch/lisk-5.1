CREATE TABLE `stat_actions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `object` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `action` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `param` varchar(255) NOT NULL DEFAULT '',
  `quantity` int(10) unsigned NOT NULL DEFAULT '1',
  `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `object` (`object`)
) ENGINE=MyISAM;