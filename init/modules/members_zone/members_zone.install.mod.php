<?php

function installMembersZoneModule($instanceId, $path) 
{
	GLOBAL $Db,$App,$FileSystem;
	
	$sql = "CREATE TABLE `users` (
	  `id` bigint(20) unsigned NOT NULL auto_increment,
	  `login` varchar(100) NOT NULL default '',
	  `password` varchar(32) NOT NULL default '',
	  `email` varchar(255) NOT NULL default '',
	  `sid` varchar(32) NOT NULL default '',
	  `level` tinyint(1) unsigned NOT NULL default '0',
	  `lastdate` datetime NOT NULL default '0000-00-00 00:00:00',
	  `lastlogin` datetime NOT NULL default '0000-00-00 00:00:00',
	  PRIMARY KEY  (`id`),
	  UNIQUE KEY `login` (`login`),
	  KEY `sid` (`sid`)
	)";
	$Db->Query($sql);
	
	$sql = "INSERT INTO `sys_email` VALUES ('members_zone_forgot_password', '%EMAIL%', 'Password Recovery', 'Hello dear member,\r\nyour login: %LOGIN%\r\nyour password: %PASSWORD%\r\n\r\nMembers zone:\r\n%MEMBERS_ZONE_URL%', 'recovery@site.com', 0)";
	$Db->Query($sql);
	
	$sql = "INSERT INTO `sys_email` VALUES ('members_zone_register_member', '%EMAIL%', 'Registration', 'Hello dear member,\r\nyour login: %LOGIN%\r\nyour password: %PASSWORD%\r\n\r\nMembers zone:\r\n%MEMBERS_ZONE_URL%', 'registration@site.com', 0)";
	$Db->Query($sql);
	
	
	$DIMemberName	= 'dyn_members_zone_member';
	$DIMemberStructure = array (
		'table'	=> 'users',
		'label'	=> 'Member',
		'order'	=> 'login',
		'fields'	=> array (
			'id'		=> LiskType::TYPE_HIDDEN,
			'login'		=> array(
				'type' => LiskType::TYPE_INPUT,
				'check' => 'empty',
			),
			'password'	=> array(
				'type'		=> LiskType::TYPE_PASSWORD,
				'view'		=> '***',
				'check' => 'empty|min:5',
			),
			'email'		=> array(
				'type' => LiskType::TYPE_INPUT,
				'check' => 'empty',
			),
			'sid'		=> LiskType::TYPE_HIDDEN,
			'level'		=> array (
				'type'		=> LiskType::TYPE_HIDDEN,
				'def_value'	=> 1,
			),
			'lastdate'	=> LiskType::TYPE_HIDDEN,
			'lastlogin'	=> array (
					'type'	=> LiskType::TYPE_DATETIME,
					'name'	=> 'Last login time'
			),
		),
		'list_fields'	=> 'login,lastlogin',
	);
	
	// install dataItems
	$App->InstallDI($DIMemberName, $DIMemberStructure);
	
	$config = array(
		'base_url'       => $path,
		'di_name_member' => $DIMemberName,
	);
	
	// update config
	$Db->Update("id=$instanceId",array(
		'config'	=>serialize($config)
	),'sys_modules');
	
	
	$installPath = $App->sysRoot.'init/modules/members_zone/install/';
	
	$FileSystem->CopyDir($installPath.'tpl/modules/members_zone/', $App->sysRoot.'tpl/modules/members_zone/','0755');
	$FileSystem->CopyFile($installPath.'members_zone.php', $App->sysRoot.'members_zone.php');

}


function uninstallMembersZoneModule() 
{
	GLOBAL $Db,$App,$FileSystem;

	// drop tables sql
	$sql = "DROP TABLE `users` ";
	$Db->Query($sql);
	
	$Db->Query("DELETE FROM sys_email where id='members_zone_forgot_password'");
	$Db->Query("DELETE FROM sys_email where id='members_zone_register_member'");
	
	$App->UninstallDI('dyn_members_zone_member');
	
	$FileSystem->DeleteDir($App->sysRoot.'tpl/modules/members_zone/');
	
	$FileSystem->DeleteFile($App->sysRoot.'members_zone.php');
}
?>