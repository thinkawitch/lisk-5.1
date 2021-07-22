<?php

function installDownloadsModule($instanceId, $path)
{
	GLOBAL $Db,$App,$FileSystem;

	$sql1 = "CREATE TABLE `mod_downloads_[iid]` (
	  `id` int(10) unsigned NOT NULL auto_increment,
	  `oder` int(10) unsigned NOT NULL default '0',
	  `name` varchar(255) NOT NULL default '',
	  `file` varchar(255) NOT NULL default '',
	  `file_id` varchar(50) NOT NULL default '',
	  `downloads` int(10) unsigned NOT NULL default '0',
	  PRIMARY KEY  (`id`)
	)";

	$downloads_di = array(
		'table' => 'mod_downloads_',
		'label'	=> 'Downloads',
		'order' => 'oder',
		'fields' => array(
			'id'   => LiskType::TYPE_HIDDEN,
			'oder' => LiskType::TYPE_HIDDEN,
			'name' => array(
				'type' => LiskType::TYPE_INPUT,
				'check' => 'empty',
			),
			'file' => array(
				'type' => LiskType::TYPE_FILE,
				'path' => 'PATH',
			),
			'file_id' 	=> LiskType::TYPE_HIDDEN,
			'downloads'	=> LiskType::TYPE_HIDDEN,
		),
		'list_fields'	=> 'name,downloads'
	);

	$DIName	= 'dyn_downloads_'.$instanceId;

	$config = array(
		'di_name'		=> $DIName,
		'items_per_page'=> 10,
		'pages_per_page'=> 10
	);

	// update config
	$Db->Update("id=$instanceId",array(
		'config'	=> serialize($config)
	),'sys_modules');


	// install DI
	$downloads_di['table'] .= $instanceId;
	$downloads_di['fields']['file']['path'] = 'modules/downloads_'.$instanceId.'/';
	$App->InstallDI($DIName,$downloads_di);

	//install table sql
	$sql1 = Format::String($sql1,array(
		'iid'	=> $instanceId,
		'path'	=> $path
	));
	$Db->Query($sql1);

	$installPath = $App->sysRoot.'init/modules/downloads/install/';

	// copy templates files
	$sourceFolder		= $installPath.'tpl/';
	$destinationFolder	= $App->sysRoot.'tpl/modules/downloads_'.$instanceId;
	$FileSystem->CopyDir($sourceFolder, $destinationFolder);

	// create directory for files
	$FileSystem->CreateDir($App->sysRoot.$App->filePath.'modules/downloads_'.$instanceId.'/');
}

function uninstallDownloadsModule($instanceId)
{
	GLOBAL $Db,$App,$FileSystem;
	
	// drop tables sql
	$sql1 = "DROP TABLE `mod_downloads_[iid]` ";

	// drop tables
	$sql1 = Format::String($sql1,array(
		'iid'	=> $instanceId
	));
	$Db->Query($sql1);

	// data items
	$diName	= 'dyn_downloads_'.$instanceId;

	//uninstall dataitems
	$App->UninstallDI($diName);

	//delete templates folder
	$tplFolder	= $App->sysRoot.'tpl/modules/downloads_'.$instanceId;
	$FileSystem->DeleteDir($tplFolder);

	//delete files
	$FileSystem->DeleteDir($App->sysRoot.$App->filePath.'modules/downloads_'.$instanceId.'/');
}
?>