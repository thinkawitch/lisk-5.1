<?php

$GLOBALS['NEWS_MODULE_INFO'] = array(
	'name'			=> 'News',
	'sys_name'		=> LiskModule::MODULE_NEWS,
	'version'		=> '5.0',
	'description'	=> 'Site news module',
	'object_name'	=> 'News',
	'multiinstance'	=> true,
	'ss_integrated'	=> true
);

$GLOBALS['LIST_TYPE_NEWS']	= array(
	'0'	=>	'Automaticaly by date',
	'1'	=>	'Custom'
);
// zachem eto ?
//$GLOBALS['IMAGE_NEWS'] = array(
//	'thumbnails'	=> array(
//		0	=> array (
//			'name'		=> 'small',
//			'height'	=> 220,
//			'width'		=> 220,
//		),
//	),
//	'no_image'	=> false,
//	'path'		=> 'news/',
//);

/**
 * News Module Main Class
 *
 */
class News extends LiskModule
{

	public $confTypeNews;
	public $confLastNewsCount;

	/**
	 * News section base url
	 * used in tree mode
	 *
	 * @var string
	 */
	public $confBaseUrl;

	public $confDINewsName;

	public $confDIHotNewsName;

	public $confCrossListName;

	public $tplPath='modules/news_';


	//paging
	public $confPagingItemsPerPage;
	public $confPagingPagesPerPage;

	/**
	 * Constructor
	 *
	 * @return News
	 */
	function __construct($instanceId=null)
	{
		$this->name = LiskModule::MODULE_NEWS;
		if ($instanceId!=null) $this->Init($instanceId);
	}

	public function Init($instanceId)
	{
		parent::Init($instanceId);

		$this->tplPath .= $instanceId.'/';
        
		$this->version = $GLOBALS['NEWS_MODULE_INFO']['version'];
		
		$this->confTypeNews = $this->config['type_news'];
		$this->confLastNewsCount = $this->config['last_news_count'];

		$this->confBaseUrl	= $this->config['base_url'];
		$this->confDINewsName = $this->config['news_di'];
		$this->confDIHotNewsName = $this->config['hot_news_di'];
		$this->confCrossListName = $this->config['cross_list'];

		$this->confPagingItemsPerPage = $this->config['items_per_page'];
		$this->confPagingPagesPerPage = $this->config['pages_per_page'];

		$this->Debug('confTypeNews', $this->confTypeNews);
		$this->Debug('confLastNewsCount', $this->confLastNewsCount);
		$this->Debug('news DI', $this->confDINewsName);
		$this->Debug('hot_news DI', $this->confDIHotNewsName);
		$this->Debug('cross_list DI', $this->confCrossListName);
	}

	public function SaveSettings()
	{
		GLOBAL $Db;
		$this->config['base_url'] = $this->confBaseUrl;
		$this->config['news_di'] = $this->confDINewsName;
		$this->config['hot_news_di'] = $this->confDIHotNewsName;
		$this->config['cross_list'] = $this->confCrossListName;
		$this->config['type_news'] = $this->confTypeNews;
		$this->config['last_news_count'] = $this->confLastNewsCount;
		$this->config['items_per_page'] = $this->confPagingItemsPerPage;
		$this->config['pages_per_page'] = $this->confPagingPagesPerPage;

		$Db->Update('id='.$this->iid, array(
			'config' => serialize($this->config)
		), 'sys_modules');
	}

	function InstallConfigure($instanceId, $params)
	{
		$GLOBALS['App']->LoadModule('modules/news/news.install.mod.php', 1);
		installNewsModule($instanceId, $params['path']);
	}

	function Uninstall()
	{
		$GLOBALS['App']->LoadModule('modules/news/news.install.mod.php', 1);
		uninstallNewsModule($this->iid);
		parent::Uninstall();
	}

	/**
	 * Render News page
	 *
	 * @return string
	 */
	function Render()
	{
		GLOBAL $Page;
		$id = @$Page->parameters[0];
		
		if ($id) return $this->RenderNewsView($id);
		else return $this->RenderNewsList();
	}

	private function RenderNewsList()
	{
		GLOBAL $Parser, $Paging;
		
		$di = Data::Create($this->confDINewsName);

		$Paging->SwitchOn('system');
		$Paging->SetItemsPerPage($this->confPagingItemsPerPage);
		$Paging->pagesPerPage = $this->confPagingPagesPerPage;
		$di->Select();

		$caption['paging'] = $Paging->Render();
		$Paging->SwitchOff();
		$Parser->SetCaptionVariables($caption);

		return $Parser->MakeList($di, $this->tplPath.'list', 'news_list');
	}

	private function RenderNewsView($id)
	{
		GLOBAL $Parser;
		$DI = Data::Create($this->confDINewsName);
		$DI->Get('id='.$id);
		return $Parser->MakeView($DI, $this->tplPath.'view', 'view');
	}

	public function GetLatestNews()
	{
		$limit = $this->confLastNewsCount;

		if ($this->confTypeNews==1)
		{
		    // Custom
			$DI = Data::Create($this->confDIHotNewsName);
			return $DI->SelectValues('is_hot_news>0', null, $DI->order);
		}
		else
		{
		    // Automaticaly by date
			$DI = Data::Create($this->confDINewsName);
			return $DI->SelectValues('1=1', null, $DI->order.' LIMIT '.$limit);
		}
	}

	public function RenderLatestNews()
	{
		GLOBAL $Parser;
		$list = $this->GetLatestNews();
		$DI   = Data::Create($this->confDINewsName);
		$DI->values = $list;
		$Parser->SetAddVariables(array('module_base_url' => $this->confBaseUrl));

		return $Parser->MakeList($DI, $this->tplPath.'latest', 'latest_news');
	}

	public function Snippet($params)
	{
		switch (strtolower($params['name']))
		{
			case 'latest_news':
				return $this->RenderLatestNews();
				break;
		}
		return '';
	}

	/**
	 * Get all available snippets of module
	 *
	 * @return array
	 */
	public function AvailableSnippets()
	{
		return array(
			'latest_news'	=> array(
				'description'	=> 'Snippet to display latest news',
				'code'			=> '<lisk:snippet src="module" instanceId="[iid]" name="latest_news" />'
			),
		);
	}
}
?>