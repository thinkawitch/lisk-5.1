<?php
require_once('init/init.php');

class CpHomePage extends CPPage
{
	function __construct()
	{
		parent::__construct();
		$this->App->Load('dev_tools', 'cms');
	}

	function Page()
	{
		GLOBAL $Parser,$Db,$App,$Auth;
		$this->SetGlobalTemplate('global_home');

		Navigation::SetBack(0);

		// logins history
		$Db->SetLimit(0, 10);
		$di = Data::Create('cp_login_history');
		$di->Select();

		$lastLogins = '';
		if (Utils::IsArray($di->values))
		{
			$lastLogins = $Parser->MakeList($di, 'home', 'last_logins');
		}

		$lastLogin = $di->GetValue("login='{$Auth->user['login']}' ORDER BY date DESC");

		$usersOnline = $Parser->MakeList($this->GetOnlineUsers(), 'home', 'cp_users_online');

		$links = $this->GetUserLinks($Auth->user['id']);

		if (Utils::IsArray($links)) $quickCustomLinks = $Parser->MakeTable($links, 3, 'home', 'quick_links');
		else $quickCustomLinks = $Parser->GetHTML('home', 'quick_links_absent');

		// messages
		$di = Data::Create('cp_message');
		$di->values = $this->GetUserInbox($Auth->user['id']);
		$di->fields['date']->format = 'd-m-Y H:i';
		$lastInbox = $Parser->MakeList($di, 'home', 'last_inbox_messages');

		$this->pageContent=$Parser->MakeView(array(
			'cp_user_login_name'  => $Auth->user['login'],
			'cp_user_login_ip'    => $lastLogin['ip'],
			'cp_user_login_date'  => date('j F Y H:i', strtotime($lastLogin['date'])),
			'cp_user_login_group' => $Db->Get('id='.$Auth->user['level'], 'name', 'sys_cp_groups'),

			'last_inbox_messages' => $lastInbox,

			'last_logins'	     => $lastLogins,
			'cp_users_online'    => $usersOnline,
			'quick_custom_links' => $quickCustomLinks,

			'lisk_version' => $App->version,
			'lisk_licensed_to' => $App->licensedTo,

			'stat_visit' => $this->StatVisitBlock(),
			'report'     => $this->Report(),

		), 'home', 'content');
	}

	private function GetOnlineUsers()
	{
		GLOBAL $Db;

		$time = time() - 5*60;
		$date = date('Y-m-d H:i:s', $time);

		$users = $Db->Select("lastdate>='$date'", 'login', 'id,login', 'sys_cp_users');

		return $users;
	}

	/**
	 * Get user links from db
	 *
	 * @param integer $userId
	 * @return array
	 */
	private function GetUserLinks($userId)
	{
		$dataItem = Data::Create('user_cp');
		$dataItem->ReSet('custom_links');

		$links = @unserialize($dataItem->GetValue('id='.$userId, 'custom_links'));
		if (Utils::IsArray($links)) return $links;
		else return array();
	}

	private function GetUserInbox($userId)
	{
		$di = Data::Create('cp_message');
		$cond = "id_to='$userId' AND  is_deleted_to=0 AND is_read=0";
		return $di->SelectValues($cond, null, 'date DESC LIMIT 5');
	}

	private function StatVisitBlock()
	{
		GLOBAL $Db,$Parser,$App;

		$count = $Db->Get("name='stat_visit'", 'COUNT(id)', 'sys_modules');
		if ($count >= 1)
		{
			$App->Load('stat_visit', 'mod');
			$App->LoadModule('installed/stat_visit/stat_visit.cms.php');
			$StatVisit = new CMSStatVisit();
			$info = $StatVisit->__GetDailyData(date("Y-m-d"));

			$usersOnline = 0;

			if (Utils::IsArray($info['rows']))
			{
				foreach ($info['rows'] as $row)
				{
					if (time() - strtotime($row['exit_time']) <= 240) $usersOnline++;
				}
			}

			return $Parser->MakeView(array(
				'users_online'	=> $usersOnline,
				'users_today'	=> $info['visitors'],
				'ppv'			=> ($info['visitors']>0) ? round($info['pages']/$info['visitors']) : 0,
				'tpv'			=> ($info['visitors']>0) ? Format::TimeLength(round($info['time']/$info['visitors']),'short') : 0
			), 'home', 'stat_visit');
		}

		return '';
	}

	private function Report()
	{
		GLOBAL $App, $Db, $Parser;

		$now = time();

		$selfTest = $Db->Get('id=1', '*', 'sys_cron_jobs');
		$lastRun = strtotime($selfTest['last_run']) + 60;
		$cronStatus = ($lastRun >= $now) ? 'Ok' : 'Warning';

		$jobsActive = $Db->Get('status=1', '0+COUNT(id)', 'sys_cron_jobs');
		$jobsPaused = $Db->Get('status=0', '0+COUNT(id)', 'sys_cron_jobs');

		$mailsToSend = $Db->Get(null, '0+COUNT(id)', 'sys_email_queue');
		$selfTest = $Db->Get('id=2', '*', 'sys_cron_jobs');
		$lastRun = strtotime($selfTest['last_run']) + 60;
		$mailStatus = ($lastRun >= $now) ? 'Ok' : 'Warning';

		$dirs = array(
			'init/installed/',
			$App->filePath,
			'tpl/modules/'
		);
		list($v1, $v2) = CMSDevTools::CheckPermissions($dirs);
		$filesStatus = $v1 ? 'Ok' : 'Warning';

		$App->Load('backup', 'lang');
		$App->Load('backup', 'obj');
		$lastBackup = $Db->Get('type='.BACKUP_TYPE_BACKUP.' ORDER BY `date` DESC', null, 'sys_backup');
		$backupDate = 'never';
		if (Utils::IsArray($lastBackup))
		{
		    $backupDate = Format::DateTime($lastBackup['date']);
		}

		return $Parser->MakeView(
		    array(
		        'jobs_active' => $jobsActive,
		        'jobs_paused' => $jobsPaused,
		        'cron_status' => $cronStatus,
		        'mails_to_send' => $mailsToSend,
		        'mail_status' => $mailStatus,
		        'files_status' => $filesStatus,
		        'backup_date' => $backupDate,
		    ),
		    'home',
		    'report'
        );
	}
}

$Page = new CpHomePage();
$Page->Render();

?>