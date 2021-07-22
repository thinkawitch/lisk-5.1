<?php

function installStatVisitModule($instanceId, $step)
{
	GLOBAL $App, $FileSystem, $Db;

	$App->Load('install','utils');
			
	//db insert steps
	if ($step>=1 && $step<=3)
	{
		GLOBAL $ProgressBar;
		$ProgressBar->SwitchOn();
		$ProgressBar->Header("Installing module StatVisit. Step {$step} of 4.");
		
		Install::ExecuteSimpleDump($App->sysRoot.'init/modules/stat_visit/dump'.$step.'.sql');
		
		$ProgressBar->Footer( Navigation::AddGetVariable(array('step'=>$step+1)) );
		$ProgressBar->SwitchOff();
		exit();
	}
	
	//last step, make general installation
	if ($step==4)
	{
		// 1 set DB
		// 2 config [net ego]
		// copy stat_visit.php file v koren'
		// set up cron (db->insert)
	
		// module config
		$config = array(
			'send_report'	=> true,
			'report_email'	=> 'admin@yoursitesitename.com'
		);
	
		// update config
		$Db->Update("id=$instanceId", array(
			'config' => serialize($config)
		),'sys_modules');
	
		$installPath = $App->sysRoot.'init/modules/stat_visit/install/';
	
		//copy files to installed folder
		$FileSystem->CreateDir($App->sysRoot.'init/installed/stat_visit/');
		$FileSystem->CopyFile($installPath.'stat_visit.php', $App->sysRoot.'init/installed/stat_visit/stat_visit.php');
		$FileSystem->CopyFile($installPath.'init/snippet/stat_visit.snippet.php', $App->sysRoot.'init/installed/stat_visit/stat_visit.snippet.php');
		$FileSystem->CopyFile($installPath.'init/cms/stat_visit.cms.php', $App->sysRoot.'init/installed/stat_visit/stat_visit.cms.php');
		$FileSystem->CopyFile($installPath.'init/cron/stat_visit.cron.php', $App->sysRoot.'init/installed/stat_visit/stat_visit.cron.php', '0755');
		
		//tpl files
		$FileSystem->CopyDir($installPath.'tpl/modules/stat_visit/', $App->sysRoot.'tpl/modules/stat_visit/');
	
		// ADD stat visit to CP Menu
		$Db->Insert(array(
			'parent_id'		=> 1,
			'parents'		=> '<1>',
			'is_category'	=> 0,
			'name'			=> 'Visits Statistics',
			'url'			=> 'module_stat_visit.php',
			'hint'			=> 'Visits Statistics'
		),'sys_cp_menu');
		
		//add cron job
		$Db->Insert(array(
			'path' => 'init/installed/stat_visit/stat_visit.cron.php',
			'name' => 'stat_visit',
		    'method' => 'cron_stat_visit',
			'periodicity' => 1440, //once per day
		), 'sys_cron_jobs');
	}
	
}

function uninstallStatVisitModule()
{
	GLOBAL $App, $FileSystem, $Db;
	
	$FileSystem->DeleteDir($App->sysRoot.'init/installed/stat_visit/');
	
	
	$fileName = $App->sysRoot.'tpl/modules/stat_visit/';
	$FileSystem->DeleteDir($fileName);
	
	$Db->Query("DROP TABLE `stat_visit_countries`");
	$Db->Query("DROP TABLE `stat_visit_ip2country`");
	$Db->Query("DROP TABLE `stat_visit_rules`");
	$Db->Query("DROP TABLE `stat_visits`");
	$Db->Query("DROP TABLE `stat_visits_history`");

	//remove from cp menu
	$Db->Delete("url='module_stat_visit.php'", 'sys_cp_menu');
	
	//remove from cron jobs
	$Db->Delete("name='stat_visit'", 'sys_cron_jobs');
}

?>