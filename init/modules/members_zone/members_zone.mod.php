<?php

$GLOBALS['MEMBERS_ZONE_MODULE_INFO'] = array(
	'name'			=> 'Members Zone',
	'sys_name'		=> LiskModule::MODULE_MEMBERS_ZONE,
	'version'		=> '5.0',
	'description'	=> 'Members Zone module',
	'object_name'	=> 'MembersZone',
	'multiinstance'	=> false,
	'ss_integrated'	=> false,
);


/**
 * Main module class
 *
 */
class MembersZone extends LiskModule
{

	/**
	 * path to folder where module stores its templates
	 *
	 * @var string
	 */
	public $tplPath = 'modules/members_zone/';
	
	/**
	 * Member DataItem name
	 *
	 * @var string
	 */
	public $confDIMemberName;

	/**
	 * constructor
	 *
	 * @return MembersZone
	 */
	function __construct($instanceId=null)
	{
		$this->name = LiskModule::MODULE_MEMBERS_ZONE;
		
		if ($instanceId==null)
		{
			GLOBAL $Db;
			$instanceId = $Db->Get("name='members_zone'", 'id', 'sys_modules');
		}
		
		if ($instanceId) $this->Init($instanceId);
	}

	/**
	 * Init module instance by IID
	 *
	 * @param integer $instanceId
	 */
	function Init($instanceId)
	{
		parent::Init($instanceId);

		$this->version = $GLOBALS['MEMBERS_ZONE_MODULE_INFO']['version'];
		
		$this->confDIMemberName = $this->config['di_name_member'];
		$this->Debug('di_name_member', $this->confDIMemberName);
	}
	
	/**
	 * Save module settings
	 *
	 */
	function SaveSettings()
	{
		GLOBAL $Db;

		$Db->Update('id='.$this->iid, array(
			'config' => serialize($this->config)
		), 'sys_modules');
	}
	
	/**
	 * Install module
	 *
	 * @param integer $instanceId
	 * @param array $params
	 */
	function InstallConfigure($instanceId,$params)
	{
		$GLOBALS['App']->LoadModule('modules/members_zone/members_zone.install.mod.php', 1);
		installMembersZoneModule($instanceId, $params['path']);
	}

	/**
	 * Uninstall module
	 *
	 */
	function Uninstall()
	{
		$GLOBALS['App']->LoadModule('modules/members_zone/members_zone.install.mod.php', 1);
		uninstallMembersZoneModule($this->iid);
		parent::Uninstall();
	}
	
	/**
	 * Run snippet method
	 *
	 * @param array $params
	 * @return string
	 */
	function Snippet($params)
	{
		switch (strtolower($params['name']))
		{
			case 'login_form':
				return $this->RenderSnippetLoginForm();
				break;
		}
		return '';
	}

	/**
	 * Get available snippets
	 *
	 * @return array
	 */
	function AvailableSnippets()
	{
		return array(
			'login_form'	=> array(
				'description'	=> 'Login form snippet',
				'code'			=> '<lisk:snippet src="module" instanceId="'.$this->iid.'" name="login_form" />'
			),
		);
	}
	
	/**
	 * Render login page
	 *
	 * @return string
	 */
	function RenderLoginPage()
	{
		GLOBAL $Parser;
		return $Parser->GetHTML($this->tplPath.'login', 'login_form');
	}
	
	/**
	 * General Render Method
	 *
	 * @return string
	 */
	function Render()
	{
		return '';
	}
	
	/**
	 * Render login form snippet
	 *
	 * @return string
	 */
	function RenderSnippetLoginForm()
	{
		GLOBAL $Parser,$Auth;
		
		if (@$_POST['action']=='members_zone_login')
		{
			$Auth->Login($_POST['login'], $_POST['password']);
		}
		
		$DI = Data::Create($this->confDIMemberName);
		
		if ($Auth->isAuthorized)
		{
			$DI->value = $Auth->user;
			return $Parser->MakeView($DI, $this->tplPath.'snippets', 'logged_block');
		}
		else
		{
			return $Parser->MakeForm($DI, $this->tplPath.'snippets', 'login_form');
		}
	}
	
	/**
	 * Register new member
	 *
	 * @param array $member
	 */
	function RegisterMember($member)
	{
		GLOBAL $App;
		
		$DI = Data::Create($this->confDIMemberName);
		$DI->Insert($member);
		
		StatActionHandler::Set('STAT_OBJECT_USER', 'STAT_OBJECT_USER_REGISTER');
		
		$App->Load('mail','utils');
		$EMail = new EMail('members_zone_register_member');
		
		$member['members_zone_url'] = 'http://'.$_SERVER['HTTP_HOST'].$App->httpRoot.'members_zone/';
		$EMail->ParseVariables($member);
		
		$EMail->Send();
	}
	
	/**
	 * Check if login is unique
	 *
	 * @param string $login
	 * @return boolean
	 */
	function LoginIsUnique($login)
	{
		$DI = Data::Create($this->confDIMemberName);
		$login = Database::Escape($login);
		$row = $DI->GetValue('login='.$login);
		return !Utils::IsArray($row);
	}
}

?>