<?php

$GLOBALS['GUESTBOOK_MODULE_INFO'] = array(
	'name'			=> 'Guest Book',
	'sys_name'		=> LiskModule::MODULE_GUESTBOOK,
	'version'		=> '5.0',
	'description'	=> 'Guest Book',
	'object_name'	=> 'Guestbook',
	'multiinstance'	=> true,
	'ss_integrated'	=> true
);

/**
 * Module Guestbook main class
 *
 */
class Guestbook extends LiskModule
{

	/**
	 * DataItem name
	 *
	 * @var string
	 */
	public $confDIName;

	/**
	 * If to approve messages before publishing
	 *
	 * @var boolean
	 */
	public $confUseApprove;

	/**
	 * If to save messages without limitation
	 *
	 * @var boolean
	 */
	public $confUseAntiBot;

	/**
	 * Guestbook base url
	 *
	 * @var string
	 */
	public $confBaseUrl;

	/**
	 * Paging settings
	 *
	 * @var integer
	 */
	public $confPagingItemsPerPage;
	public $confPagingPagesPerPage;

	/**
	 * Templates path
	 *
	 * @var string
	 */
	var $tplPath = 'modules/guestbook_';

	/**
	 * Constructor
	 *
	 * @param integer $instanceId
	 * @return Guestbook
	 */
	function __construct($instanceId=null)
	{
		$this->name = LiskModule::MODULE_GUESTBOOK;
		if ($instanceId!=null) $this->Init($instanceId);
	}

	/**
	 * Initialize module
	 *
	 * @param integer $instanceId
	 */
	public function Init($instanceId)
	{
		parent::Init($instanceId);

		$this->tplPath .= $instanceId.'/';

		$this->version = $GLOBALS['GUESTBOOK_MODULE_INFO']['version'];

		$this->confBaseUrl            = $this->config['base_url'];
		$this->confDIName 			  = $this->config['di_name'];
		$this->confUseAntiBot		  = $this->config['antibot'];
		$this->confUseApprove		  = $this->config['approve'];
		$this->confPagingItemsPerPage = $this->config['items_per_page'];
		$this->confPagingPagesPerPage = $this->config['pages_per_page'];

		$this->Debug('DI', $this->confDIName);
		$this->Debug('antibot', $this->confUseAntiBot);
		$this->Debug('approve', $this->confUseApprove);
	}

	/**
	 * Save settings
	 *
	 */
	public function SaveSettings()
	{
		GLOBAL $Db;
		$this->config['base_url'] 		= $this->confBaseUrl;
		$this->config['di_name']		= $this->confDIName;
		$this->config['antibot']		= $this->confUseAntiBot;
		$this->config['approve']		= $this->confUseApprove;
		$this->config['items_per_page'] = $this->confPagingItemsPerPage;
		$this->config['pages_per_page'] = $this->confPagingPagesPerPage;
		$Db->Update('id='.$this->iid, array(
			'config' => serialize($this->config)
		), 'sys_modules');
	}

	/**
	 * Install new module instance
	 *
	 * @param integer $instanceId
	 * @param array $params
	 */
	public function InstallConfigure($instanceId, $params)
	{
		$GLOBALS['App']->LoadModule('modules/guestbook/guestbook.install.mod.php', 1);
		installGuestbookModule($instanceId, $params['path']);
	}

	/**
	 * Uninstall module instance
	 *
	 */
	public function Uninstall()
	{
		$GLOBALS['App']->LoadModule('modules/guestbook/guestbook.install.mod.php', 1);
		uninstallGuestbookModule($this->iid);
		parent::Uninstall();
	}

	/**
	 * Render Module
	 *
	 * @return string
	 */
	public function Render()
	{
		GLOBAL $App,$Parser,$Paging,$Page;

		$DI = Data::Create($this->confDIName);

		//new message
		if (@$_POST['action'] == 'submit')
		{
			$this->AddMessage();
			Navigation::Jump($App->httpRoot.$this->confBaseUrl.'message_added/');
		}

		if (@$Page->parameters[0] == 'message_added')
		{
			return $this->RenderMessageAdded();
		}

		$Paging->SwitchOn('system');
		$Paging->SetItemsPerPage($this->confPagingItemsPerPage);
		$Paging->pagesPerPage = $this->confPagingPagesPerPage;

		$cond = '';
		if ($this->confUseApprove) $cond = 'is_approved=1';
		
		$DI->Select($cond);

		$caption['paging'] = $Paging->Render();
		$Paging->SwitchOff();
		$caption['add_record'] = $this->RenderAddForm();

		$Parser->SetCaptionVariables($caption);
		return $Parser->MakeList(
			$DI,
			$this->tplPath.'guestbook',
			'list'
		);
	}

	/**
	 * Add new message from post
	 *
	 */
	public function AddMessage()
	{
		$DI = new Data($this->confDIName);
		$_POST['date'] = Format::DateTimeNow();
		//anti bot/flood javascript protection
		if ($this->confUseAntiBot && @$_POST['_zxcv']<5) return;
		
		$DI->Insert($_POST);
	}

	/**
	 * Render add form
	 *
	 * @return string
	 */
	public function RenderAddForm()
	{
		GLOBAL $Parser;
		$DI = Data::Create($this->confDIName);
		return $Parser->MakeForm($DI, $this->tplPath.'guestbook', 'add_form');
	}

	/**
	 * Render message added
	 *
	 * @return string
	 */
	public function RenderMessageAdded()
	{
		GLOBAL $Parser;
		$block = 'record_added_1';
		if ($this->confUseApprove) $block = 'record_added_2';
		
		return $Parser->MakeView(array('back' => $this->confBaseUrl), $this->tplPath.'guestbook', $block);
	}

	/**
	 * developer method to fill guestbook for testing
	 *
	 * @param unknown_type $qty
	 */
	public function FillRandomMessages($qty=12)
	{
		$di = new Data($this->confDIName);
		for($i=0; $i<=$qty; $i++)
		{
			$di->Insert(
				array(
					'date' => date('Y-m-d H:i:s', rand(time(),time()+86400*7)),
					'name' => rand(10,99).'_'.uniqid(''),
					'message'  => 'message   '.rand(10,99).'_'.uniqid(''),
					'caption' => 'nick_'.rand(10,99),
					'email'    => 'email_'.rand(10,99).'@dd.com',
					'is_approved' => rand(0,1),
				)
			);
		}
	}
}

?>