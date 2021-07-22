<?php

function installStructuredListModule($instanceId, $path)
{
	GLOBAL $App,$FileSystem;
	
	$sql1 = "CREATE TABLE `mod_structured_list_{IID}` (
		  `id` int(11) NOT NULL auto_increment,
		  `oder` int(11) unsigned  NOT NULL,
		  `name` varchar(255) NOT NULL default '',
		  `short_description` text NOT NULL,
		  PRIMARY KEY  (`id`)
		)
	";

	$sl_di = array(
		'table' => 'mod_structured_list_',
		'order' => 'oder',
		'label'	=>	'Records',
		'list_fields' => 'name',
		'fields' => array(
			'id' => array(
				'type' => LiskType::TYPE_HIDDEN
			),
			'oder' => array(
				'type' => LiskType::TYPE_HIDDEN
			),
			'name' => array(
				'type' => LiskType::TYPE_INPUT,
				'label' => 'Title'
			),
			'short_description' => array(
				'type' => LiskType::TYPE_TEXT,
				'label' => 'Short Description'
			),
		)
	);

	GLOBAL $Db,$App;

	$DIName	    = 'dyn_structured_list_'.$instanceId;

	$config = array(
		'base_url'				=> $path,
		'structured_list_di'	=> $DIName,

		'items_per_page' => 10,
		'pages_per_page' => 5,
	);

	// update config
	$Db->Update("id=$instanceId",array(
		'config'	=> serialize($config)
	),'sys_modules');

	// install DI
	$sl_di['table'] .= $instanceId;
	$App->InstallDI($DIName, $sl_di);

	//install tables sql
	$sql = str_replace('{IID}', $instanceId, $sql1);
	$Db->Query($sql);

	$installPath = $App->sysRoot.'init/modules/structured_list/install/';

	//copy templates
	$sourceFolder		= $installPath.'tpl/';
	$destinationFolder	= $App->sysRoot.'tpl/modules/structured_list_'.$instanceId;
	$FileSystem->CopyDir($sourceFolder, $destinationFolder);

}

function UninstallStructuredListModule($instanceId)
{
	GLOBAL $Db,$App,$FileSystem;
	
	//drop table sql
	$sql = "DROP TABLE `mod_structured_list_[iid]`";

	//drop tables
	$sql = Format::String($sql,array(
		'iid' => $instanceId
	));
	$Db->Query($sql);

	//data items
	$DIName = 'dyn_structured_list_'.$instanceId;

	//uninstall dataitems
	$App->UninstallDI($DIName);

	//delete tpl files
	$FileSystem->DeleteDir($App->sysRoot.'tpl/modules/structured_list_'.$instanceId.'/');
}
?>