<?php

$GLOBALS['STAT_VISIT_MODULE_INFO'] = array(
	'name'			=> 'StatVisit',
	'sys_name'		=> LiskModule::MODULE_STAT_VISIT,
	'version'		=> '5.0.6',
	'description'	=> 'Visits statistics',
	'object_name'	=> 'StatVisit',
	'multiinstance'	=> false,
	'ss_integrated'	=> false
);

define('STAT_VISIT_NOT_DEFINED_COUNTRY',	300);

// Live time, for detection user online/offline, seconds
define('STAT_VISIT_LIVE_TIME',	240);

// Visitor Rules
$GLOBALS['DATA_STAT_VISIT_RULE'] = array(
	'table'	=> 'stat_visit_rules',
	'order'	=> 'id',
	'fields'	=> array(
		'id'			=>  LiskType::TYPE_HIDDEN,
		'cond_type'		=> array(
			'type'			=>  LiskType::TYPE_LIST,
			'object'		=> 'def_stat_visit_cond_type',
			'label'			=> 'Condition'
		),
		'cond_value'	=> array(
			'type'			=> LiskType::TYPE_INPUT,
			'label'			=> 'Condition Value'
		),
		'name'			=> LiskType::TYPE_INPUT,
	)
);

// Visitor Rules
$GLOBALS['LIST_STAT_VISIT_COND_TYPE']=array(
	'visitor_id'	=> 'Visitor ID',
	'ip'			=> 'IP Address',
	'bw'			=> 'Browser Information',
	'referrer'		=> 'Referrer'
);


$GLOBALS['LIST_VISITSTAT_HIDDEN_STAT']=array(
	0	=> 'No',
	1	=> 'Yes'
);

$GLOBALS['LIST_VISITSTAT_USER_TYPE']=array(
	0	=> '',
	1	=> 'visitor',
	2	=> 'SE bot'
);

$GLOBALS['LIST_STAT_VISIT_REPORT_FREQUENCY']=array(
	0	=> 'Daily',
	1	=> 'Every 3 days',
	2	=> 'Weekly',
);

class StatVisit extends LiskModule
{

	/**
	 * Visitor id. Stored in coockies
	 *
	 * @var string
	 */
	public $visitorId = null;
	
	/**
	 * Current visit id
	 *
	 * @var integer
	 */
	public $visitId = null;

	/**
	 * Enables/Disables sending report by email
	 *
	 * @var boolean
	 */
	public $confReport = false;
	
	/**
	 * Defines email address(s) the report will be send to
	 *
	 * @var string
	 */
	public $confReportEmail;
	
	/**
	 * path to folder where module stores its templates
	 *
	 * @var unknown_type
	 */
	public $tplPath = 'modules/stat_visit';

	/**
	 * constructor
	 *
	 * @return Statistics
	 */
	function StatVisit($instanceId=null)
	{
		$this->name = LiskModule::MODULE_STAT_VISIT;
		if ($instanceId != null) $this->Init($instanceId);
	}
	
	public static function GetInstalledIid()
	{
		GLOBAL $Db;
		$moduleInfo = $Db->Get('name="stat_visit"', null, 'sys_modules');
		
		return Utils::IsArray($moduleInfo) ? $moduleInfo['id'] : null;
	}

	/**
	 * Init module instance by IID
	 *
	 * @param integer $instanceId
	 */
	public function Init($instanceId)
	{
		parent::Init($instanceId);

		$this->version = $GLOBALS['STAT_VISIT_MODULE_INFO']['version'];

		$this->confReport		= ($this->config['send_report'] == 1) ? true : false;
		$this->confReportEmail	= $this->config['report_email'];

		$this->Debug('Send Report', $this->confReport);
		$this->Debug('confBaseUrl', $this->confReportEmail);
	}

	/**
	 * Save module settings
	 *
	 */
	function SaveSettings()
	{
		GLOBAL $Db;
		$this->config['send_report'] = ($this->confReport) ? 1 : 0;
		$this->config['report_email'] = $this->confReportEmail;

		$Db->Update('id='.$this->iid, array(
			'config' => serialize($this->config)
		), 'sys_modules');
	}

	function InstallConfigure($instanceId,$params)
	{
		$GLOBALS['App']->LoadModule('modules/stat_visit/stat_visit.install.mod.php', 1);
		installStatVisitModule($instanceId, $params['step']);
	}

	function Uninstall()
	{
		$GLOBALS['App']->LoadModule('modules/stat_visit/stat_visit.install.mod.php', 1);
		uninstallStatVisitModule($this->iid);
		parent::Uninstall();
	}

	function InitVisit()
	{
		if (isset($_COOKIE['visitor_id']) && strlen($_COOKIE['visitor_id']) && is_numeric($_COOKIE['visitor_id']))
		{
			$this->visitorId = $_COOKIE['visitor_id'];
			$_SESSION['visitor_id'] = $this->visitorId;
		}
		elseif (isset($_SESSION['visitor_id']) && strlen($_SESSION['visitor_id']) && is_numeric($_SESSION['visitor_id']))
		{
			$this->visitorId = $_SESSION['visitor_id'];
			Utils::SetCookie('visitor_id', $this->visitorId, 31536000);
		}
		else
		{
			// generate visitor Id
			$this->visitorId = date('YmdHis').rand(1, 100);
			Utils::SetCookie('visitor_id', $this->visitorId, 31536000);
			$_SESSION['visitor_id'] = $this->visitorId;
		}

		if (isset($_SESSION['visit_id']) && strlen($_SESSION['visit_id']) && is_numeric($_SESSION['visit_id']))
		{
			$this->visitId = $_SESSION['visit_id'];
		}
	}

	function AvailableSnippets()
	{
		return array(
			'stat_visit'	=> array(
				'description'	=> 'Main snippet that should be inserted into all site pages',
				'code'			=> '<lisk:snippet src="init/installed/stat_visit/stat_visit.snippet.php" name="stat_visit" />'
			),
		);
	}

	/**
	 * return javascript code that harvest users' data
	 *
	 * @return string
	 */
	function GetJSCode()
	{
 		GLOBAL $App;
		$statUrl = $App->httpRoot.'init/installed/stat_visit/stat_visit.php';

		return <<<EOD
(function LiskJsStats(){
	document.cookie = "path=/";
	var referrer = document.referrer;
	var page = window.location.href;

	var isBadHost = true;
	
	//bad hosts don't like the 'http' word in parameters
	if (isBadHost)
	{
		referrer = referrer.replace(/http:\/\//g, '');
		page = page.replace(/http:\/\//g, '');
	}
			
	var result = '?action=save_statistics'
		+ '&ref=' + encodeURIComponent(referrer)
		+ '&page=' + encodeURIComponent(page)
		+ '&c=' + (document.cookie ? "Y" : "N")
		+ '&j=' + (navigator.javaEnabled() ? "Y" : "N")
		+ '&wh=' + screen.width + 'x'+ screen.height
		+ '&px=' + (navigator.appName.substring(0,3) == "Mic" ? screen.colorDepth : screen.pixelDepth)
		+ '&title=' + encodeURIComponent(document.title);
	
	//get time zone difference
	var cd = new Date();
	var diff = cd.getTimezoneOffset()*(-1)/60;
	result += '&time_dif=' + diff;	
		
	var img = new Image(1, 1);
	img.src = '{$statUrl}' + result;
})();
EOD;

	}

	/**
	 * Return code that should be placed on every page where we want to know statistics
	 *
	 * @return string
	 */
	function GetJSString()
	{
		GLOBAL $App;
		$statUrl = $App->httpRoot.'init/installed/stat_visit/stat_visit.php';
		return '<script src="'.$statUrl.'" type="text/javascript"></script>';
	}

	function GetCountryByIp($ip)
	{
		GLOBAL $Db;

		$ip_array = explode('.', $ip);
		$ip = ($ip_array[0] * pow(256,3)) +
		   ($ip_array[1] * pow(256,2)) +
		   ($ip_array[2] * 256) +
			$ip_array[3];

		$countryId = $Db->Get("$ip >= ip_from AND $ip <= ip_to", 'country_id', 'stat_visit_ip2country');
		if ($countryId<1) $countryId = STAT_VISIT_NOT_DEFINED_COUNTRY;
		return $countryId;
	}

	function StatSiteEntrance($page, $referrer, $title, $screen_size, $screen_color, $time_dif)
	{
		GLOBAL $Db;

		$ip = $_SERVER['REMOTE_ADDR'];
		$bw = $_SERVER['HTTP_USER_AGENT'];

		$country = $this->GetCountryByIp($ip);

		$row=array(
			'date'			=> Format::DateTimeNow(),
			'visitor_id'	=> $this->visitorId,
			'ip'			=> $ip,
			'referrer'		=> $referrer,
			'page'			=> $page,
    		'bw'			=> $bw,
    		'screen_size'	=> $screen_size,
    		'screen_color'	=> $screen_color,
    		'time_dif'		=> $time_dif,
    		'country'		=> $country
    	);

		// insert new visit
		$visitId				= $Db->Insert($row, 'stat_visits');
		$this->visitId 			= $visitId;
		$_SESSION['visit_id']	= $this->visitId;

		$Db->Update('id='.$this->visitId,array(
			'pages_browsed'		=> 'sql:pages_browsed+1',
			'last_visit_time'	=> Format::DateTimeNow()
		),'stat_visits');

		$page = explode($_SERVER['HTTP_HOST'],$page);
		$page = $page[1];

		// save current page visit
		$Db->Insert(array(
			'visit_id'		=> $this->visitId,
			'date'			=> Format::DateTimeNow(),
			'page'			=> $page,
			'title'			=> $title
		),'stat_visits_history');
	}

	function StatPage()
	{
		GLOBAL $Db,$Page;

		if (basename($_SERVER['SCRIPT_NAME']) == 'stat_visit.php') return;

		$Db->Update('id='.$this->visitId, array(
			'pages_browsed'		=> 'sql:pages_browsed+1',
			'last_visit_time'	=> Format::DateTimeNow()
		), 'stat_visits');

		$pageUrl = $_SERVER['REQUEST_URI'];
		$pageUrl = Utils::RemoveSessionId($pageUrl);

		// save current page visit
		$Db->Insert(array(
			'visit_id'		=> $this->visitId,
			'date'			=> Format::DateTimeNow(),
			'page'			=> $pageUrl,
			'title'			=> $Page->title
		), 'stat_visits_history');
	}

	/**
	 * save statistics about the user if this is the beginning of the visit
	 * save page the user is on
	 * save in session visitor_id to set in cookie
	 *
	 * @param string $page
	 * @param string $referrer
	 * @param string $title
	 * @param string $screen_size
	 * @param string $screen_color
	 * @param int $time_dif
	 */
	public function SaveStatisitics($page, $referrer, $title, $screen_size, $screen_color, $time_dif)
	{
		if ($this->visitId == null) 
		{
			$this->StatSiteEntrance($page, $referrer, $title, $screen_size, $screen_color, $time_dif);
		}
		else 
		{
			$this->StatPage();
		}
	}
	
	public function SaveActionContactPage()
	{
		if (!$this->visitId) return;
		
		GLOBAL $Db;
		$Db->Update('id='.$this->visitId, array('a_contact_page' => 1), 'stat_visits');
	}
	
	public function SaveActionLead()
	{
		if (!$this->visitId) return;
		
		GLOBAL $Db;
		$Db->Update('id='.$this->visitId, array('a_lead' => 1), 'stat_visits');
	}

}
?>