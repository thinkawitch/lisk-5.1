<?php

function cron_stat_visit()
{
	GLOBAL $Db, $App, $Parser;
	
	echo "\r\nstat visit crond opened\r\n";
	
	$Parser->tpl->SetRoot('../../tpl/');
	
	$App->Load('stat_visit','mod');
	
	$StatVisit = new StatVisit( $Db->Get("name='stat_visit'", 'id', 'sys_modules') );

	if ($StatVisit->config['send_report'])
	{
		$App->LoadModule('installed/stat_visit/stat_visit.cms.php');
		$CMSStatVisit = new CMSStatVisit();
		
		$content = $CMSStatVisit->ReportDaily();
	
		$App->Load('mail','utils');
		$MyMail = new EMail();
		$MyMail->html = true;
		$MyMail->from = 'stat_visit@noreply.com';
		$MyMail->message = $content;
		$MyMail->subject = 'Visit Statistics Report';
		$MyMail->AddRecipient($StatVisit->config['report_email']);
		$MyMail->Send();
		
		echo "report sent\r\n";
	}
	else echo "reports turned off\r\n";
	
	$Parser->tpl->SetRoot($App->tplPath);
	
	echo "closed stat visit crond\r\n";
}

?>