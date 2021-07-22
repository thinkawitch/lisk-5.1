<?php

abstract class LiskModule
{
	const MODULE_CATALOGUE        = 'catalogue';
	const MODULE_EVENTS           = 'events';
	const MODULE_DOWNLOADS        = 'downloads';
	const MODULE_FAQ              = 'faq';
	const MODULE_NEWS             = 'news';
	const MODULE_STRUCTURED_LIST  = 'structured_list';
	const MODULE_CONTACT_FORM     = 'contact_form';
	const MODULE_GUESTBOOK        = 'guestbook';
	const MODULE_MEDIA_GALLERY    = 'media_gallery';
	const MODULE_POLL             = 'poll';
	const MODULE_STAT_VISIT       = 'stat_visit';
	const MODULE_SITE_SEARCH      = 'site_search';
	const MODULE_NEWSLETTER       = 'newsletter';
	const MODULE_METATAGS_MANAGER = 'metatags_manager';
	const MODULE_SITEMAP          = 'sitemap';
	const MODULE_MEMBERS_ZONE     = 'members_zone';
	const MODULE_STAT_ACTION      = 'stat_action';
	const MODULE_SUPPORT    	  = 'support_form';
	
	/**
	 * Module system name
	 * MODULE_XXX const
	 *
	 * @var string/const
	 */
	public $name;

	/**
	 * System modules table
	 *
	 * @var string
	 */
	protected $modulesTableName = 'sys_modules';

	/**
	 * Module Instance ID
	 *
	 * @var integer
	 */
	public $iid;

	/**
	 * Module config array
	 * Containts module related settings
	 *
	 * @var HashTable
	 */
	public $config;

	/**
	 * Module version
	 *
	 * @var string
	 */
	public $version;


	function __construct()
	{
		
	}
	
	/**
	 * Check if module can be installed
	 *
	 * @return boolean
	 */
	public static function IsInstallPossible()
	{
	    GLOBAL $App;
	    $checkDirs = array(
	        $App->filePath.'modules/',
	        $App->initPath.'installed/',
	        $App->tplPath.'modules/',
	    );
	    
	    foreach ($checkDirs as $dir)
	    {
	        if (!FileSystem::CheckPermissions($App->sysRoot.$dir, '0777')) return false;
	    }
	    
	    return true;
	}

	public function Install($params)
	{
		GLOBAL $Db;

		$name = strtoupper($this->name).'_MODULE_INFO';
		$moduleInfo = $GLOBALS[$name];
		
		$step = $params['step'];
		
		if ($step == 1)
		{
			$instanceId = $Db->Insert(array(
				'name'			=> $this->name,
				'object_name'	=> $moduleInfo['object_name']
			), $this->modulesTableName);
		}
		else
		{
			$instanceId = $Db->Get("name='{$this->name}'", 'id', $this->modulesTableName);
		}

		$this->InstallConfigure($instanceId, $params);

		return $instanceId;
	}

	public function Uninstall()
	{
		GLOBAL $Db;
		$Db->Delete('id='.$this->iid, $this->modulesTableName);
		//remove from cp menu, if added
		$Db->Delete('url='.Database::Escape($this->name.'.php?iid='.$this->iid), 'sys_cp_menu');
	}

	abstract public function InstallConfigure($intstanceId, $params);

	/**
	 * Define if module has one or no instances installed
	 *
	 * @return boolean
	 */
	public function IsLastInstance()
	{
		GLOBAL $Db;
		$count = $Db->Get('name='.Database::Escape($this->name), 'COUNT(id)', $this->modulesTableName);
		return $count<=1;
	}

	public function Init($instanceId)
	{
		$this->iid = $instanceId;
		$this->ReadConfig();
		$this->Debug('Init. Instance ID', $instanceId);
	}

	public function Search()
	{
		//TODO
	}

	private function ReadConfig()
	{
		GLOBAL $Db;
		$serArr = $Db->Get('id='.$this->iid, 'config', $this->modulesTableName);
		$this->config = unserialize($serArr);
	}
	
	protected function SaveConfig()
	{
	    GLOBAL $Db;
	    $Db->Update(
	    	'id='.$this->iid,
	        array('config' => serialize($this->config)),
	        $this->modulesTableName
        );
	}

	protected function Debug($name, $value, $error=null)
	{
		GLOBAL $Debug, $App;
		if ($App->debug) $Debug->AddDebug($this->name, $name, $value, $error);
	}

	public function Snippet($params)
	{
		GLOBAL $App;
		$App->RaiseError("Module {$this->name} does not support snippet <b>{$params['name']}</b>");
	}

	public function AvailableSnippets()
	{
		return array();
	}

	public function GetSnippetCode($name)
	{
		$snippets = $this->AvailableSnippets();
		$code = $snippets[$name]['code'];
		$code = Format::String($code, array('iid' => $this->iid));
		return $code;
	}

	public function GetAvailableSnippets()
	{
		$snippets = $this->AvailableSnippets();
		$rez = array();
		foreach ($snippets as $name=>$info)
		{
			$rez[] = array(
				'name'			=> $name,
				'description'	=> $info['description']
			);
		}
		return $rez;
	}
	
	public function UpdateBaseUrl($baseUrl)
	{
	    if (!isset($this->config['base_url'])) return;
	    if ($this->config['base_url'] == $baseUrl) return;
	    
	    $this->config['base_url'] = $baseUrl;
		$this->SaveConfig();
	}
	
}

?>