<?php
define('ROOT_PATH', '../../');
require_once('../init.php');

$jobs = $Db->Select('`status`=1', null, null, 'sys_cron_jobs');

// update main cron time
$Db->Update('id=1', array('last_run' => Format::DateTimeNow()), 'sys_cron_jobs');
	
$now = time();

if (!Utils::IsArray($jobs)) return;

foreach ($jobs as $job)
{
	$lastrun = strtotime($job['last_run']);
	$periodicity = $job['periodicity'] * 60;
	
	if ($lastrun + $periodicity < $now)
	{
		runCronJob($job['id'], $job['path'], $job['object'], $job['method']);
	}
}

//to make sure all shutdown work is made
$App->Destroy();


function runCronJob($id, $path, $object=null, $method=null)
{
	GLOBAL $Db, $App;
	
	// include file
	if (strlen($path)) $path = $App->sysRoot.$path;
	
	if (!file_exists($path)) return;
	
	include_once($path);
	$execStr = null;
	// run function or object method
	echo "\r\nrun ";

	if (strlen($object) && strlen($method))
	{
		$execStr = '$CronJob = new '.$object.'();';
		$execStr .= '$CronJob->'.$method.'();';
	}
	elseif(!strlen($object) && strlen($method))
	{
		$execStr = $method.'();';
	}
	
	$date = Format::DateTimeNow();
	echo $execStr.' '.$date;
	
	if (strlen($execStr)) eval($execStr);
	
	// update last run
	$Db->Update('id='.$id, array(
		'last_run'	=> $date
	), 'sys_cron_jobs');
}

?>