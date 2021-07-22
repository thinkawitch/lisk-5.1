<?php

function installMetatagsManagerModule()
{
	GLOBAL $Db;
	
	$sql1 = "CREATE TABLE `mod_metatags_pages` (
	  `id` int(10) unsigned NOT NULL auto_increment,
	  `name` varchar(255) NOT NULL default '',
	  `title` varchar(255) NOT NULL default '',
	  `description` text NOT NULL,
	  `keywords` text NOT NULL,
	  `revisit_after` varchar(255) NOT NULL default '',
	  `robots` varchar(255) NOT NULL default '',
	  `language` varchar(255) NOT NULL default '',
	  `classification` varchar(255) NOT NULL default '',
	  `page_type` varchar(255) NOT NULL default '',
	  `page_topic` varchar(255) NOT NULL default '',
	  `copyright` varchar(255) NOT NULL default '',
	  `author` varchar(255) NOT NULL default '',
	  `url` varchar(255) NOT NULL default '',
	  PRIMARY KEY  (`id`)
	)";

	$sql2 = "CREATE TABLE `mod_metatags_presets` (
	  `id` int(10) unsigned NOT NULL auto_increment,
	  `oder` int(10) unsigned NOT NULL default '0',
	  `def` tinyint(3) unsigned NOT NULL default '0',
	  `name` varchar(255) NOT NULL default '',
	  `title` varchar(255) NOT NULL default '',
	  `description` text NOT NULL,
	  `keywords` text NOT NULL,
	  `revisit_after` varchar(255) NOT NULL default '',
	  `robots` varchar(255) NOT NULL default '',
	  `language` varchar(255) NOT NULL default '',
	  `classification` varchar(255) NOT NULL default '',
	  `page_type` varchar(255) NOT NULL default '',
	  `page_topic` varchar(255) NOT NULL default '',
	  `copyright` varchar(255) NOT NULL default '',
	  `author` varchar(255) NOT NULL default '',
	  `url` varchar(255) NOT NULL default '',
	  PRIMARY KEY  (`id`)
	)";

	$sql3 = "INSERT INTO `mod_metatags_presets` VALUES (1, 0, 1, 'Default', 'Default Title', 'Default description', '', '', '', '', '', '', '', '', '', '');";

	$Db->Query($sql1);
	$Db->Query($sql2);
	$Db->Query($sql3);

	// ADD to CP Menu
	$Db->Insert(array(
		'parent_id'		=> 1,
		'parents'		=> '<1>',
		'is_category'	=> 0,
		'name'			=> 'Metatags Manager',
		'url'			=> 'module_metatags_manager.php',
		'hint'			=> 'Metatags Manager'
	),'sys_cp_menu');

}

function uninstallMetatagsManagerModule()
{
	GLOBAL $Db;

	$Db->Query("DROP TABLE `mod_metatags_pages`");
	$Db->Query("DROP TABLE `mod_metatags_presets`");

	//remove from cp menu
	$Db->Delete("url='module_metatags_manager.php'", 'sys_cp_menu');
}


?>