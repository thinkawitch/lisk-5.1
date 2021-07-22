<?php

function cron_stat_action()
{
	GLOBAL $Db,$App,$Parser;
	
	echo "\r\nstat_action crond opened\r\n";

	$App->Load('stat_action', 'mod');
	
	$App->tplPath = '../../cp/tpl/';
	$Parser->tpl = new Template();
	
	$StatAction = new StatAction( $Db->Get("name='stat_action'", 'id', 'sys_modules') );

	if ($StatAction->config['send_report'])
	{
		$App->LoadModule('installed/stat_action/stat_action.cfg.php');
		$App->LoadModule('installed/stat_action/stat_action.cms.php');
		
		$CMSStatAction = new CMSStatAction();
		
		$job = $Db->Get("name='stat_action'", null, 'sys_cron_jobs');
		
		list($runY,$runM,$runD, $runH,$runI,$runS) = sscanf($job['last_run'], '%04d-%02d-%02d %02d:%02d:%02d');
		
		$params = array(
			'start_date_year' => $runY,
			'start_date_month' => sprintf('%02d', $runM),
			'start_date_day' => sprintf('%02d', $runD),
			
			'end_date_year' => date('Y'),
			'end_date_month' => date('m'),
			'end_date_day' => date('d'),
		);
				
		GLOBAL $LIST_STAT_ACTION_OBJECTS, $DATA_STATS_ACTIONS_REPORT;
		
		foreach (array_keys($LIST_STAT_ACTION_OBJECTS) as $k)
		{
			$params['object'.$k] = array();
			foreach (array_keys($GLOBALS['LIST_STAT_ACTION_'.$k]) as $k2)
			{
				$params['object'.$k][] = $k2;
			}
		}
		
		foreach ($LIST_STAT_ACTION_OBJECTS as $key => $object)
		{
			$DATA_STATS_ACTIONS_REPORT['fields']['object'.$key] = array(
				'type' => 'prop',
				'label' => $object,
				'object' => 'def_stat_action_'.$key
			);
		}
		
		$view = $CMSStatAction->RenderReport($params, false);

		$content = $view['list'];
		
		//file_put_contents($App->sysRoot.'report.html', $content);
		
		$App->Load('mail', 'utils');
		$MyMail = new EMail();
		$MyMail->html = true;
		$MyMail->from = 'stat_action@noreply.com';
		$MyMail->message = $content;
		$MyMail->subject = 'Action Statistics Report';
		$MyMail->AddRecipient($StatAction->config['report_email']);
		$MyMail->Send();
		
		echo "report sent\r\n";
	}
	else
	{
		echo "reports turned off\r\n";
	}
	
	$App->tplPath = 'tpl/';
	
	echo "closed stat_action crond\r\n";
}

?>