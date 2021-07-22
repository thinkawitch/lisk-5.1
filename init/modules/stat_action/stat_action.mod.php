<?php

$GLOBALS['STAT_ACTION_MODULE_INFO'] = array(
	'name'			=> 'StatAction',
	'sys_name'		=> LiskModule::MODULE_STAT_ACTION,
	'version'		=> '5.0',
	'description'	=> 'Actions statistics',
	'object_name'	=> 'StatAction',
	'multiinstance'	=> false,
	'ss_integrated'	=> false
);


class StatAction extends LiskModule
{
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
	 * How often to send report, in minutes
	 *
	 * @var int
	 */
	public $confReportPeriod = 1440;
	
	/**
	 * path to folder where module stores its templates
	 *
	 * @var unknown_type
	 */
	public $tplPath='modules/stat_action';

	/**
	 * constructor
	 *
	 * @return Statistics
	 */
	function __construct($instanceId=null)
	{
		$this->name = LiskModule::MODULE_STAT_ACTION;

		if ($instanceId!=null) $this->Init($instanceId);
	}

	/**
	 * Init module instance by IID
	 *
	 * @param integer $instanceId
	 */
	function Init($instanceId)
	{
		parent::Init($instanceId);

		$this->version = $GLOBALS['STAT_ACTION_MODULE_INFO']['version'];

		$this->confReport		= (isset($this->config['send_report']) && $this->config['send_report'] == 1) ? true : false;
		$this->confReportEmail	= $this->config['report_email'];
		$this->confReportPeriod	= $this->config['report_period'];

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
		$this->config['report_period'] = $this->confReportPeriod;

		$Db->Update('id='.$this->iid, array(
			'config' => serialize($this->config)
		), 'sys_modules');
	}

	function InstallConfigure($instanceId,$params)
	{
		$GLOBALS['App']->LoadModule('modules/stat_action/stat_action.install.mod.php', 1);
		installStatActionModule($instanceId,$params['path']);
	}

	function Uninstall()
	{
		$GLOBALS['App']->LoadModule('modules/stat_action/stat_action.install.mod.php', 1);
		uninstallStatActionModule($this->iid);
		parent::Uninstall();
	}
	
	/**
	 * Set stats action
	 *
	 * @param  const ACTION_OBJECT $object
	 * @param  const ACTION $action
	 * @param  string $param
	 * @return true or raise error
	 */
	static function Set($object, $action, $param=null, $quantity = 1)
	{
		GLOBAL $App, $Db, $Auth;
		
		$objects = $GLOBALS['LIST_STAT_ACTION_OBJECTS'];
		
		// get key if name was entered and not id/key
		if (!is_int($object))
		{
			$array = array_flip($objects);
			$object = $array[$object];
		}

		if (!array_key_exists($object, $objects))
		{
			$App->RaiseError("Class StatAction: Object `$object` is undefined in \$LIST_STAT_ACTION_OBJECTS");
		}
		
		$actions = $GLOBALS['LIST_STAT_ACTION_'.$object];

		if (!is_int($action))
		{
			$array = array_flip($actions);
			$action = $array[$action];
		}

		if (!array_key_exists($action, $actions))
		{
			$App->RaiseError("Class StatAction: Action `$action` is undefined in \$LIST_STAT_ACTIONS");
		}

		$Db->Insert(array(
			'object' 		=> $object,
			'action' 		=> $action,
			'param'			=> is_null($param) ? '' : $param,
			'quantity' 		=> $quantity,
			'date' 			=> date('Y-m-d H:i:s'),
			'user_id'		=> $Auth->user['id']
		), 'stat_actions');
		
		return true;
	}
}
?>