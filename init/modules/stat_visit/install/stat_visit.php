<?php
chdir('../../../');
require_once 'init/init.php';

$App->Load('stat_visit', 'mod');
$StatVisit = new StatVisit();
$StatVisit->InitVisit();

$getAction = isset($_GET['action']) ? $_GET['action'] : null;
$flagStatVisitDone = isset($GLOBALS['flagStatVisitDone']) ? $GLOBALS['flagStatVisitDone'] : false;

if ($getAction == 'save_statistics')
{
	if (!$flagStatVisitDone)
	{
		$page			= isset($_GET['page']) ? $_GET['page'] : null;
		$referrer		= isset($_GET['ref']) ? $_GET['ref'] : null;
		$title			= isset($_GET['title']) ? $_GET['title'] : null;

		$screen_size	= isset($_GET['wh']) ? $_GET['wh'] : null;
		$screen_color	= isset($_GET['px']) ? $_GET['px'] : null;
		$time_dif		= isset($_GET['time_dif']) ? $_GET['time_dif'] : null;

		$StatVisit->SaveStatisitics($page, $referrer, $title, $screen_size, $screen_color, $time_dif);
		$GLOBALS['flagStatVisitDone'] = true;
	}
	
	exit();
}

echo $StatVisit->GetJSCode();
exit();
?>