<?php

function installGuestbookModule($instanceId, $path)
{
	GLOBAL $Db,$App,$FileSystem;

	$sql1 = "CREATE TABLE `mod_guestbook_[iid]` (
			  `id` int(10) unsigned NOT NULL auto_increment,
			  `date` datetime NOT NULL default '0000-00-00 00:00:00',
			  `name` varchar(255) NOT NULL default '',
			  `email` varchar(255) NOT NULL default '',
			  `caption` varchar(255) NOT NULL default '',
			  `message` text NOT NULL,
			  `is_approved` tinyint(3) unsigned NOT NULL default '0',
			  PRIMARY KEY  (`id`)
			)";

	$DIName	= 'dyn_guestbook_'.$instanceId;

	$DIStructure = array(
		'label' => 'Guestbook',
		'table' => 'mod_guestbook_',
		'order' => 'date DESC',
		'fields' => array(
			'id' => LiskType::TYPE_HIDDEN,
			'date' => array(
				'type'   => LiskType::TYPE_DATETIME,
				'format' => 'm/d/Y',
				'def_value'	=> 'sql:NOW()'
			),
			'name' => array(
				'type'  => LiskType::TYPE_INPUT,
				'label' => 'Name',
				'check' => 'empty',
			),
			'email' => array(
				'type'  => LiskType::TYPE_INPUT,
				'label' => 'Email',
				'check' => 'pre:email',
			),
			'caption' => array(
				'type'  => LiskType::TYPE_INPUT,
				'check' => 'empty',
			),
			'message' => array(
				'type'  => LiskType::TYPE_TEXT,
				'label' => 'Message',
				'check' => 'empty',
			),
			'is_approved' => array(
				'type' => LiskType::TYPE_FLAG,
				'label'=> 'Approved',
				'is_system' => true,
			),
		),

		'redefine_no_approving' => array(
			'is_approved' => 'void',
		),

		'list_fields' => 'date,caption',
	);

	$config = array(
		'base_url'				=> $path,
		'di_name'				=> $DIName,
		'antibot'				=> true,
		'approve'				=> true,
		'items_per_page'		=> 10,
		'pages_per_page'		=> 5,
	);

	// update config
	$Db->Update("id=$instanceId", array(
		'config'	=> serialize($config)
	),'sys_modules');


	// install DI
	$DIStructure['table'] .= $instanceId;
	$App->InstallDI($DIName, $DIStructure);

	//install SQL
	$sqlToInstall = array($sql1);
	foreach ($sqlToInstall as $sql)
	{
		$sql = Format::String($sql,array(
			'iid'	=> $instanceId,
			'path'	=> $path,
		));
		$Db->Query($sql);
	}

	$installPath = $App->sysRoot.'init/modules/guestbook/install/';

	// copy templates files
	$sourceFolder = $installPath.'tpl/';
	$destinationFolder	= $App->sysRoot.'tpl/modules/guestbook_'.$instanceId;
	$FileSystem->CopyDir($sourceFolder, $destinationFolder);
}

function uninstallGuestbookModule($instanceId)
{
	GLOBAL $Db,$App,$FileSystem;
	
	// drop tables sql
	$sql1 = "DROP TABLE `mod_guestbook_{$instanceId}` ";
	$Db->Query($sql1);

	// uninstall data items
	$DIName = 'dyn_guestbook_'.$instanceId;
	$App->UninstallDI($DIName);

	//delete templates folder
	$tplFolder	= $App->sysRoot.'tpl/modules/guestbook_'.$instanceId;
	$FileSystem->DeleteDir($tplFolder);
}
?>