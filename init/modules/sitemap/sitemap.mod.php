<?php

$GLOBALS['SITEMAP_MODULE_INFO'] = array(
	'name'			=> 'Sitemap',
	'sys_name'		=> LiskModule::MODULE_SITEMAP,
	'version'		=> '5.0',
	'description'	=> 'Sitemap module',
	'object_name'	=> 'Sitemap',
	'multiinstance'	=> true,
	'ss_integrated'	=> true
);


/**
 * Sitemap Module Main Class
 *
 */
class Sitemap extends LiskModule
{

	/**
	 * News section base url
	 * used in tree mode
	 *
	 * @var string
	 */
	public $confBaseUrl;


	public $tplPath = 'modules/sitemap_';

	/**
	 * Constructor
	 *
	 * @return Sitemap
	 */
	function __construct($instanceId=null)
	{
		$this->name = LiskModule::MODULE_SITEMAP;
		if ($instanceId!=null) $this->Init($instanceId);
	}

	public function Init($instanceId)
	{
		parent::Init($instanceId);
		
        $this->version = $GLOBALS['SITEMAP_MODULE_INFO']['version'];
        
		$this->tplPath .= $instanceId.'/';

		$this->confBaseUrl	= $this->config['base_url'];
	}

	public function SaveSettings()
	{
		GLOBAL $Db;
		$this->config['base_url'] = $this->confBaseUrl;

		$Db->Update('id='.$this->iid, array(
			'config' => serialize($this->config)
		), 'sys_modules');
	}

	public function InstallConfigure($instanceId, $params)
	{
		$GLOBALS['App']->LoadModule('modules/sitemap/sitemap.install.mod.php', 1);
		installSitemapModule($instanceId, $params['path']);
	}

	public function Uninstall()
	{
		$GLOBALS['App']->LoadModule('modules/sitemap/sitemap.install.mod.php', 1);
		uninstallSitemapModule($this->iid);
		parent::Uninstall();
	}

	/**
	 * Render Sitemap page
	 *
	 * @return HTML
	 */
	public function Render()
	{
		GLOBAL $Db,$Parser,$Auth;
		
		$addCond = '';
		if (!$Auth->isAuthorized) $addCond = ' AND access_level!=3';
		
		$all = $Db->Select('access_level!=0'.$addCond, 'oder', null, 'sys_ss');
		if (Utils::IsArray($all))
		{
			foreach ($all as $k=>$row)
			{
				if ($row['page_type']==SCMS_LINK && $row['link_open_type']==1) $all[$k]['target'] = 'target="_blank"';
				else $all[$k]['target'] = '';
			}
		}
		
		$html = '';
		
		$format = array(
			'level0'	=> $Parser->GetHtml($this->tplPath.'sitemap', 'level_0'),
			'level1'	=> $Parser->GetHtml($this->tplPath.'sitemap', 'level_1'),
			'level2'	=> $Parser->GetHtml($this->tplPath.'sitemap', 'level_2'),
			'level3'	=> $Parser->GetHtml($this->tplPath.'sitemap', 'level_3'),
			'level4'	=> $Parser->GetHtml($this->tplPath.'sitemap', 'level_4'),
		);
		
		foreach ($format as $k=>$v)
		{
			$format[$k] = preg_replace('/{(\w+)}/e', '\$this->Reformat("\1")', $v);
		}
		
		$html = Utils::TreeStructureRender(1, $all, $format);
		
		return $Parser->MakeView(array('sitemap' => $html), $this->tplPath.'sitemap', 'sitemap');

	}

	
	private function Reformat($str)
	{
		return '['.strtolower($str).']';
	}
}
?>