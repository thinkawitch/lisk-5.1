<?php

function installEventsModule($instanceId, $path)
{
	GLOBAL $Db,$App,$FileSystem;

	$sql1 = "CREATE TABLE `mod_events_[iid]` (
	  `id` int(10) unsigned NOT NULL auto_increment,
	  `name` varchar(255) NOT NULL default '',
	  `date` datetime NOT NULL default '0000-00-00 00:00:00',
	  `event_type` varchar(255) NOT NULL default '',
	  `contact` text NOT NULL,
	  `description` text NOT NULL,
	  PRIMARY KEY  (`id`)
	)";


	$di = array(
		'table'	=> 'mod_events_',
		'label'	=> 'events',
		'order'	=> 'date DESC',
		'fields'=> array(
			'id'		=> LiskType::TYPE_HIDDEN,
			'name'		=> LiskType::TYPE_INPUT,
			'event_type'=> array(
				'type'		=> LiskType::TYPE_LIST,
				'object'	=> array(
					'FFCC88'	=> 'Event type 1',
					'55FF22'	=> 'Event type 2'
				)
			),
			'date'		=> LiskType::TYPE_DATETIME,
			'contact'	=> array(
				'type'		=> LiskType::TYPE_TEXT,
				'label'		=> 'Contact Information'
			),
			'description'	=> LiskType::TYPE_HTML,
		),
		'list_fields'	=> 'date,name,event_type',
		'redefine_siteview'	=> array(
			'date'				=> array(
				'type'				=> LiskType::TYPE_DATETIME,
				'format'			=> 'H:i'
			)
		)
	);

	$DIName	= 'dyn_events_'.$instanceId;

	$config = array(
		'di_name'		=> $DIName,
		'base_url'		=> $path
	);

	// update config
	$Db->Update("id=$instanceId",array(
		'config'	=>serialize($config)
	),'sys_modules');


	// install DI
	$di['table'] .= $instanceId;
	$App->InstallDI($DIName,$di);

	//install table sql
	$sql1 = Format::String($sql1,array(
		'iid'	=> $instanceId,
		'path'	=> $path
	));
	$Db->Query($sql1);

	$installPath = $App->sysRoot.'init/modules/events/install/';

	// copy templates files
	$sourceFolder		= $installPath.'tpl/';
	$destinationFolder	= $App->sysRoot.'tpl/modules/events_'.$instanceId;
	$FileSystem->CopyDir($sourceFolder, $destinationFolder);

}

function uninstallEventsModule($instanceId)
{
	GLOBAL $Db,$App,$FileSystem;

	// drop tables sql
	$sql1 = "DROP TABLE `mod_events_[iid]` ";

	// drop tables
	$sql1 = Format::String($sql1, array(
		'iid'	=> $instanceId
	));
	$Db->Query($sql1);

	// data items
	$diName	= 'dyn_events_'.$instanceId;

	//uninstall dataitems
	$App->UninstallDI($diName);

	//delete templates folder
	$tplFolder	= $App->sysRoot.'tpl/modules/events_'.$instanceId;
	$FileSystem->DeleteDir($tplFolder);
}
?>