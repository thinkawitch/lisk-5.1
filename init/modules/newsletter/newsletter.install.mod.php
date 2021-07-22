<?php

function installNewsletterModule($instanceId, $path)
{
	GLOBAL $Db,$App,$FileSystem;
		
	$sql = "CREATE TABLE `newsletter_groups` (
	  `id` int(10) unsigned NOT NULL auto_increment,
	  `parent_id` int(10) unsigned NOT NULL default '0',
	  `parents` varchar(255) NOT NULL default '',
	  `name` varchar(255) NOT NULL default '',
	  `url` varchar(255) NOT NULL default '',
	  PRIMARY KEY  (`id`)
	)";
	$Db->Query($sql);

	$sql = "INSERT INTO `newsletter_groups` VALUES (1, 0, '', 'Newsletter', '')";
	$Db->Query($sql);
	$sql = "INSERT INTO `newsletter_groups` VALUES (".NEWSLETTER_GENERAL_GROUP_ID.", 1, '<1>', 'General', '')";
	$Db->Query($sql);

	$sql = "CREATE TABLE `newsletter_history` (
	  `id` int(10) unsigned NOT NULL auto_increment,
	  `date` date NOT NULL default '0000-00-00',
	  `subject` varchar(255) NOT NULL default '',
	  `content` mediumtext NOT NULL,
	  `users` tinyint(3) unsigned NOT NULL default '0',
	  PRIMARY KEY  (`id`)
	)";
	$Db->Query($sql);

	$sql = "CREATE TABLE `newsletter_subscribers` (
	  `id` int(10) unsigned NOT NULL auto_increment,
	  `parent_id` int(10) unsigned NOT NULL default '0',
	  `parents` varchar(255) NOT NULL default '',
	  `email` varchar(255) NOT NULL default '',
	  PRIMARY KEY  (`id`)
	)";
	$Db->Query($sql);

	$group_di	= array (
		'label' => 'Group',
		'table'	=> 'newsletter_groups',
		'order'	=> 'id',
		'fields'	=> array (
			'id'   => 'hidden',
			'parent_id' => array(
				'type'   => 'category',
				'object' => 'newsletter',
				'label'  => 'Parent',
			),
			'[newsletterCountGroupSubscribers]' => array(
				'type'  => 'void',
				'label' => 'Subscribers',
			),
			'parents' => 'hidden',
			'name'    => array(
				'type' => 'input',
				'check' => 'pre:empty',
			),
		),

		'redefine_edit' => array(
			'parent_id' => 'void',
			'parents'   => 'void',
		),

		'list_fields'	=> 'name,[newsletterCountGroupSubscribers]',
	);

	$subscriber_di	= array (
		'label'	=> 'Subscriber',
		'table'	=> 'newsletter_subscribers',
		'order'	=> 'id',
		'fields'	=> array (
			'id'		=> 'hidden',
			'parent_id' => array(
				'type'   => 'category',
				'object' => 'newsletter',
				'label'  => 'Group',
			),
			'parents' => 'hidden',
			'email'		=> array(
				'type' => 'input',
				'check' => 'pre:empty|pre:email',
			),
		),

		'redefine_edit' => array(
			'parent_id' => 'void',
			'parents'   => 'void',
		),

		'redefine_add' => array(
			'parent_id' => 'hidden',
			'parents'   => 'hidden',
		),

		'list_fields'	=> 'email',
	);

	$history_di = array(
		'table'		=> 'newsletter_history',
		'order'		=> 'id desc',
		'fields'	=> array (
			'id'		=> 'hidden',
			'date'		=> array(
				'type'		=> 'date',
				'def_value'	=> 'sql:NOW()'
			),
			'subject'	=> 'input',
			'content'	=> 'html',
			'users'		=> 'input'
		),
		'list_fields'	=> 'date,subject'
	);

	$groupDIName = 'dyn_newsletter_group_'.$instanceId;
	$subscriberDIName = 'dyn_newsletter_subscriber_'.$instanceId;
	$treeName = 'dyn_tree_newsletter_'.$instanceId;
	$historyDIName = 'dyn_newsletter_history_'.$instanceId;

	$tree = array(
		'name' => 'Newsletter',
		'max_level' => 1,
		'node' => $groupDIName,
		'point'=> $subscriberDIName,
	);

	$emailTplPath = 'modules/newsletter/templates/';
	$attachTplPath = 'modules/newsletter/attachments/';

	$config = array(
		'base_url'				=> $path,
		'tree_name'				=> $treeName,
		'di_name_group'			=> $groupDIName,
		'di_name_subscriber'	=> $subscriberDIName,
		'di_name_history'		=> $historyDIName,
		'email_tpl_path'		=> $emailTplPath,
		'attach_tpl_path'		=> $attachTplPath,
		'from_address'			=> 'newsletter@yourcompany.com',
		'attach_images'			=> true,
	);

	// update config
	$Db->Update("id=$instanceId", array(
		'config' => serialize($config)
	),'sys_modules');

	// install group DI
	$group_di['fields']['parent_id']['object'] = $treeName;
	$App->InstallDI($groupDIName, $group_di);

	// install items DI
	$subscriber_di['fields']['parent_id']['object'] = $treeName;
	$App->InstallDI($subscriberDIName, $subscriber_di);

	//install tree DI
	$App->InstallDI($treeName, $tree);

	$App->InstallDI($historyDIName, $history_di);

	//directory for email templates
	$FileSystem->CreateDir($App->sysRoot.$App->filePath.$emailTplPath);
	$FileSystem->CreateDir($App->sysRoot.$App->filePath.$attachTplPath);

	$installPath = $App->sysRoot.'init/modules/newsletter/install/';

	// copy templates files
	$sourceFolder		= $installPath.'tpl/';
	$destinationFolder	= $App->sysRoot.'tpl/modules/newsletter_'.$instanceId;
	$FileSystem->CopyDir($sourceFolder, $destinationFolder);

	//copy init files to installed folder
	$FileSystem->CreateDir($App->sysRoot.'init/installed/newsletter/');
	$FileSystem->CopyFile($installPath.'init/cms/newsletter.cms.php', $App->sysRoot.'init/installed/newsletter/newsletter.cms.php', '0755');
	
	//newsletter tpl sample
	$FileSystem->CopyDir($installPath.'templates/', $App->sysRoot.$App->filePath.$emailTplPath);

	// ADD newsletter into CP Menu
	$Db->Insert(array(
		'parent_id'		=> 1,
		'parents'		=> '<1>',
		'is_category'	=> 0,
		'name'			=> 'Newsletter',
		'url'			=> 'module_newsletter.php?iid='.$instanceId,
		'hint'			=> 'Manage newsletter'
	),'sys_cp_menu');

}

function uninstallNewsletterModule($instanceId)
{
	GLOBAL $Db,$App,$FileSystem;
	
	//remove tables
	$Db->Query('DROP TABLE `newsletter_groups`');
	$Db->Query('DROP TABLE `newsletter_subscribers`');
	$Db->Query('DROP TABLE `newsletter_history`');

	//remove dataitems
	$groupDIName = 'dyn_newsletter_group_'.$instanceId;
	$subscriberDIName = 'dyn_newsletter_subscriber_'.$instanceId;
	$treeName = 'dyn_tree_newsletter_'.$instanceId;
	$historyDIName = 'dyn_newsletter_history_'.$instanceId;
	$App->UninstallDI($groupDIName);
	$App->UninstallDI($subscriberDIName);
	$App->UninstallDI($treeName);
	$App->UninstallDI($historyDIName);

	//remove files
	$FileSystem->DeleteDir($App->sysRoot.$App->filePath.'modules/newsletter/');
	$FileSystem->DeleteDir($App->sysRoot.'tpl/modules/newsletter_'.$instanceId);
    $FileSystem->DeleteDir($App->sysRoot.'init/installed/newsletter/');

	//remove from cp menu
	$Db->Delete("url='module_newsletter.php?iid={$instanceId}'", 'sys_cp_menu');
}
?>