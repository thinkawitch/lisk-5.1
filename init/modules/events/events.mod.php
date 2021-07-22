<?php

$GLOBALS['EVENTS_MODULE_INFO'] = array(
	'name'			=> 'Events',
	'sys_name'		=> LiskModule::MODULE_EVENTS,
	'version'		=> '5.0',
	'description'	=> 'Events',
	'object_name'	=> 'Events',
	'multiinstance'	=> true,
	'ss_integrated'	=> true
);

class Events extends LiskModule
{

	public $confDIName;

	/**
	 * Events section base url
	 * used for calendar links
	 *
	 * @var string
	 */
	public $confBaseUrl;

	public $tplPath = 'modules/events_';

	function __construct($instanceId=null)
	{
		$this->name = LiskModule::MODULE_EVENTS;
		if ($instanceId!=null) $this->Init($instanceId);
	}

	public function Init($instanceId)
	{
		parent::Init($instanceId);

		$this->version = $GLOBALS['EVENTS_MODULE_INFO']['version'];

		$this->tplPath .= $instanceId.'/';
		$this->confDIName		= $this->config['di_name'];
		$this->confBaseUrl		= $this->config['base_url'];
		$this->Debug('confDIName', $this->confDIName);
	}

	public function SaveSettings()
	{
		GLOBAL $Db;
		$this->config['di_name'] = $this->confDIName;

		$Db->Update('id='.$this->iid, array(
			'config' => serialize($this->config)
		), 'sys_modules');
	}

	public function InstallConfigure($instanceId, $params)
	{
		$GLOBALS['App']->LoadModule('modules/events/events.install.mod.php', 1);
		installEventsModule($instanceId, $params['path']);
	}

	public function Uninstall()
	{
		$GLOBALS['App']->LoadModule('modules/events/events.install.mod.php', 1);
		uninstallEventsModule($this->iid);
		parent::Uninstall();
	}

	public function Render()
	{
		GLOBAL $App,$Parser,$Page;
		$App->Load('calendar', 'class');

		if (strlen(@$Page->parameters[0])) $_GET['year'] = $Page->parameters[0];
		if (strlen(@$Page->parameters[1])) $_GET['month'] = sprintf('%02d', $Page->parameters[1]);
		if (strlen(@$Page->parameters[2])) $_GET['day'] = sprintf('%02d', $Page->parameters[2]);

		$EventsDI = Data::Create($this->confDIName, false);
		$Calendar = new Calendar($App->httpRoot.$this->confBaseUrl, $EventsDI->table, null, Data::Create($this->confDIName), 'image');
		$Calendar->SetTplName($this->tplPath.'calendar');

		return $Parser->MakeView(array(
			'list'		=> $this->RenderEventsList($Calendar->CurrentDate()),
			'calendar'	=> $Calendar->Render()
		), $this->tplPath.'events', 'view');
	}

	private function RenderEventsList($date)
	{
		GLOBAL $Parser;

		if ($date=='') $date = date('Y-m-d');

		$DIEvents = Data::Create($this->confDIName);
		$DIEvents->Select("SUBSTRING(date, 1, 10)='$date'");

		$Parser->SetCaptionVariables(array(
			'event_date'	=> Format::Date($date, 'd M, Y')
		));

		return $Parser->MakeList($DIEvents, $this->tplPath.'list', 'list');
	}

	public function Snippet($params)
	{
		switch ($params['name'])
		{
			case 'calendar':
				return $this->SnippetCalendar();
				
			case 'latest_events':
				return $this->SnippetLatestEvents($params);
				
			default:
			    GLOBAL $App;
				$App->RaiseError("Module events does not support snippet <b>{$params['name']}</b>");
				break;
		}
	}

	private function SnippetCalendar()
	{
		GLOBAL $App;
		$App->Load('calendar', 'class');

		$EventsDI = Data::Create($this->confDIName,false);

		$Calendar = new Calendar($App->httpRoot.$this->confBaseUrl, $EventsDI->table, null);
		$Calendar->SetTplName($this->tplPath.'calendar');

		return $Calendar->Render();
	}
	
	private function SnippetLatestEvents($params)
	{
		GLOBAL $Parser,$Db;

		$EventsDI = Data::Create($this->confDIName);
		$limit = intval($params['limit']);
		if (!$limit) $limit = 3;
		
		$Db->SetLimit(0, $limit);
		$EventsDI->Select();
		
		$values = &$EventsDI->values;
		if (Utils::IsArray($values))
		{
			foreach ($values as $k=>$v)
			{
				list($y,$m,$d) = sscanf($v['date'], '%04d-%02d-%02d');
				$values[$k]['url'] = $this->confBaseUrl."$y/$m/$d/";
			}
		}

		return $Parser->MakeList($EventsDI, $this->tplPath.'snippets', 'latest');
	}
	
	/**
	 * Get all available snippets of module
	 *
	 * @return array
	 */
	public function AvailableSnippets()
	{
		return array(
			'calendar'	=> array(
				'description'	=> 'Snippet to display calendar with events',
				'code'			=> '<lisk:snippet src="module" instanceId="[iid]" name="calendar" />'
			),
			'latest_events'	=> array(
				'description'	=> 'Snippet to display latest events',
				'code'			=> '<lisk:snippet src="module" instanceId="[iid]" name="latest_events" limit="3" />'
			),
		);
	}
}

?>