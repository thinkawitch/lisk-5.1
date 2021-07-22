<?php

function installStatActionModule($instanceId)
{
	GLOBAL $App, $FileSystem, $Db;

	$App->Load('install', 'utils');
	
	// module config
	$config = array(
		'send_report'	=> true,
		'report_email'	=> 'admin@yoursitesitename.com',
		'report_period'	=> 1,
	);

	// update config
	$Db->Update("id=$instanceId", array('config' => serialize($config)), 'sys_modules');

	$installPath = $App->sysRoot.'init/modules/stat_action/install/';

	//copy files to installed folder
	$FileSystem->CreateDir($App->sysRoot.'init/installed/stat_action/');

	$FileSystem->CopyFile($installPath.'init/installed/stat_action.cms.php', $App->sysRoot.'init/installed/stat_action/stat_action.cms.php');
	$FileSystem->CopyFile($installPath.'init/installed/stat_action.cron.php', $App->sysRoot.'init/installed/stat_action/stat_action.cron.php');
	$FileSystem->CopyFile($installPath.'init/installed/stat_action.cfg.php', $App->sysRoot.'init/installed/stat_action/stat_action.cfg.php', '0777');
	

	Install::ExecuteDump($App->sysRoot.'init/modules/stat_action/dump.sql');

    /*
	// ADD stat visit to CP Menu
	$Db->Insert(array(
		'parent_id'		=> 1,
		'parents'		=> '<1>',
		'is_category'	=> 0,
		'name'			=> 'Actions Statistics',
		'url'			=> 'module_stat_action.php',
		'hint'			=> 'Actions Statistics'
	),'sys_cp_menu');
	*/
	
	//add cron job
	$Db->Insert(array(
		'path' => 'init/installed/stat_action/stat_action.cron.php',
		'name' => 'stat_action',
	    'method' => 'cron_stat_action',
		'periodicity' => 1440, //once per day
	), 'sys_cron_jobs');
	
}

function uninstallStatActionModule()
{
	GLOBAL $App, $FileSystem, $Db;

	$FileSystem->DeleteDir($App->sysRoot.'init/installed/stat_action/');
	
	$Db->Query("DROP TABLE `stat_actions`");

	//remove from cp menu
	$Db->Delete("url='module_stat_action.php'", 'sys_cp_menu');
	
	//remove from cron jobs
	$Db->Delete("name='stat_action'", 'sys_cron_jobs');
}

?>