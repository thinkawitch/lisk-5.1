<?php

function installPollModule($instanceId, $path)
{
	GLOBAL $Db,$App,$FileSystem;
	
	$sql = "CREATE TABLE `mod_poll_polls` (
		  `id` int(10) unsigned NOT NULL auto_increment,
		  `date` datetime NOT NULL default '0000-00-00 00:00:00',
		  `oder` int(10) unsigned NOT NULL default '0',
		  `name` varchar(255) NOT NULL default '',
		  `poll_uniq_id` varchar(255) default NULL,
		  `is_active` tinyint(3) unsigned NOT NULL default '0',
		  PRIMARY KEY  (`id`)
		)";
	$Db->Query($sql);

	$sql = "CREATE TABLE `mod_poll_answers` (
		  `id` int(10) unsigned NOT NULL auto_increment,
		  `oder` int(10) unsigned NOT NULL default '0',
		  `poll_id` int(10) unsigned NOT NULL default '0',
		  `answer` varchar(255) NOT NULL default '',
		  `votes` int(10) unsigned NOT NULL default '0',
		  PRIMARY KEY  (`id`)
		) ";
	$Db->Query($sql);

	$DIPollName	= 'dyn_poll_poll';

	$DIPollStructure = array (
		'table' => 'mod_poll_polls',
		'order' => 'date DESC',
		'label' => 'Poll',
		'list_fields' => 'name',
		'fields' => array (
			'id' => array (
				'type' => LiskType::TYPE_HIDDEN,
			),
			'oder' => array (
				'type' => LiskType::TYPE_HIDDEN,
			),
			'date' => array(
				'type'		=> LiskType::TYPE_HIDDEN,
				'def_value'	=> 'sql:NOW()'
			),
			'name' => array (
				'type'	=> LiskType::TYPE_INPUT,
				'label'	=> 'Name',
				'check'	=> 'empty',
			),
			'is_active'	=> array(
				'type'				=> LiskType::TYPE_FLAG,
				'default_checked'	=> true,
				'label'				=> 'Active',
			),
		),
		'list_fields'	=> 'name,date,is_active',
		'redefine_list'	=> array(
			'date' => array(
				'type' => LiskType::TYPE_DATE,
				'label'		=> 'Created on'
			),
		),
		'redefine_add'	=> array(
			'name' => array (
				'type'	=> LiskType::TYPE_INPUT,
				'label'	=> 'Name/Question',
				'check'	=> 'empty',
			),
		)
	);

	$DIAnswerName = 'dyn_poll_answer';

	$DIAnswerStructure = array(
		'table' => 'mod_poll_answers',
		'order' => 'oder',
		'label' => 'Poll Answer',
		'list_fields' => 'answer,votes',
		'fields' => array (
			'id' => array (
				'type' => LiskType::TYPE_HIDDEN,
			),
			'oder' => array (
				'type' => LiskType::TYPE_HIDDEN,
			),
			'poll_id' => array (
				'type' => LiskType::TYPE_HIDDEN,
			),
			'answer' => array (
				'type' => LiskType::TYPE_INPUT,
				'label' => 'Answer',
				'check' => 'empty',
			),
			'votes' => array (
				'type' => LiskType::TYPE_INPUT,
				'label' => 'Votes',
			),
		),

		'redefine_add' => array(
			'votes' => 'void',
		),
	);

	$App->InstallDI($DIPollName, $DIPollStructure);
	$App->InstallDI($DIAnswerName, $DIAnswerStructure);

	$config = array(
		'base_url'			=> $path,
		'di_name_poll'		=> $DIPollName,
		'di_name_answer'	=> $DIAnswerName,
		'vote_frequency'    => 0,
		'vote_mode'			=> 1,
	);

	// update config
	$Db->Update("id=$instanceId", array(
		'config'	=> serialize($config)
	),'sys_modules');

	$installPath = $App->sysRoot.'init/modules/poll/install/';

	// copy templates files
	$sourceFolder		= $installPath.'tpl/';
	$destinationFolder	= $App->sysRoot.'tpl/modules/poll_'.$instanceId;
	$FileSystem->CopyDir($sourceFolder, $destinationFolder);

}

function uninstallPollModule($instanceId)
{
	GLOBAL $Db,$App,$FileSystem;
	
	// drop tables sql
	$sql = "DROP TABLE `mod_poll_polls` ";
	$Db->Query($sql);

	$sql = "DROP TABLE `mod_poll_answers` ";
	$Db->Query($sql);

	$App->UninstallDI('dyn_poll_poll');
	$App->UninstallDI('dyn_poll_answer');

	//delete templates folder
	$tplFolder	= $App->sysRoot.'tpl/modules/poll_'.$instanceId;
	$FileSystem->DeleteDir($tplFolder);
}

?>