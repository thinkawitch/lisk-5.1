<?php

$GLOBALS['SITE_SEARCH_MODULE_INFO'] = array(
	'name'			=> 'SiteSearch',
	'sys_name'		=> LiskModule::MODULE_SITE_SEARCH,
	'version'		=> '5.0',
	'description'	=> 'Site search module',
	'object_name'	=> 'SiteSearch',
	'multiinstance'	=> false,
	'ss_integrated'	=> true
);



class SiteSearch extends LiskModule
{

	/**
	 * path to folder where module stores its templates
	 *
	 * @var string
	 */
	public $tplPath = 'modules/site_search/';

	public $confPagingItemsPerPage;
	public $confPagingPagesPerPage;

	/**
	 * constructor
	 *
	 * @return SiteSearch
	 */
	function __construct($instanceId=null)
	{
		$this->name = LiskModule::MODULE_SITE_SEARCH;
		if ($instanceId!=null) $this->Init($instanceId);
	}

	/**
	 * Init module instance by IID
	 *
	 * @param integer $instanceId
	 */
	public function Init($instanceId)
	{
		parent::Init($instanceId);

		$this->version = $GLOBALS['SITE_SEARCH_MODULE_INFO']['version'];

		$this->confPagingItemsPerPage = $this->config['items_per_page'];
		$this->confPagingPagesPerPage = $this->config['pages_per_page'];

		$this->Debug('Items per page', $this->confPagingItemsPerPage);
		$this->Debug('Pages per page', $this->confPagingPagesPerPage);
	}

	/**
	 * Save module settings
	 *
	 */
	public function SaveSettings()
	{
		GLOBAL $Db;

		$this->config['items_per_page'] = $this->confPagingItemsPerPage;
		$this->config['pages_per_page'] = $this->confPagingPagesPerPage;

		$Db->Update('id='.$this->iid, array(
			'config' => serialize($this->config)
		), 'sys_modules');
	}

	public function InstallConfigure($instanceId,$params)
	{
		$GLOBALS['App']->LoadModule('modules/site_search/site_search.install.mod.php', 1);
		installSiteSearchModule($instanceId, $params['path']);
	}

	public function Uninstall()
	{
		$GLOBALS['App']->LoadModule('modules/site_search/site_search.install.mod.php', 1);
		uninstallSiteSearchModule($this->iid);
		parent::Uninstall();
	}
	
	/**
	 * Run snippet method
	 *
	 * @param array $params
	 * @return string
	 */
	public function Snippet($params)
	{
		switch (strtolower($params['name']))
		{
			case 'site_search':
				return $this->RenderSnippetSiteSearch();
				break;
		}
		return '';
	}

	public function AvailableSnippets()
	{
		return array(
			'site_search' => array(
				'description' => 'Site search form snippet',
				'code'		  => '<lisk:snippet src="module" instanceId="'.$this->iid.'" name="site_search" />'
			),
		);
	}

	public function Render()
	{
		GLOBAL $Parser,$Db,$Paging,$App,$Page;
		
		$result = '';
		
		if (isset($_POST['query']) && strlen($_POST['query']))
		{
		    StatActionHandler::Set('STAT_OBJECT_SEARCH', 'STAT_OBJECT_SEARCH_SEARCH');
		    
			$jump = $Page->GetPageUrl().'search/'.urlencode(trim($_POST['query'])).'/';
			Navigation::Jump($jump);
		}
		
		$query = urldecode(@$Page->parameters[1]);

		$Paging->SwitchOn('system');
		$Paging->SetItemsPerPage($this->confPagingItemsPerPage);
		$Paging->pagesPerPage = $this->confPagingPagesPerPage;

		if (strlen($query))
		{
			$queryCond = Database::Escape('%'.$query.'%');
			$ptPageset = SCMS_PAGESET;
			//search all pages except pagesets with pageset_overview=off
			$cond = " ((page_type!=$ptPageset) OR (page_type=$ptPageset AND pageset_overview=1)) AND (name LIKE $queryCond OR content LIKE $queryCond)";
			$rows = $Db->Select($cond, 'id', 'name,title,content,url', 'sys_ss');
			
			if (Utils::IsArray($rows))
			{
				$Parser->SetCaptionVariables(array(
					'TOTAL'		=> $Paging->itemsTotal,
					'query'		=> $query,
					'paging'	=> $Paging->Render()
				));

				// prepair rows for parsing
				$startWith = $Paging->pcp * $Paging->GetItemsPerPage();
				$i = 1;

				foreach ($rows as $key=>$row)
				{
					$rows[$key]['content'] = Format::SearchResult($row['content'], $query);
					$rows[$key]['url'] = $App->domain.$App->httpRoot.$row['url'];
					$rows[$key]['number'] = $startWith + $i;
					$i++;
				}

				$result = $Parser->MakeList($rows, $this->tplPath.'search_result', 'list');
			}
			else
			{
				$result = $Parser->MakeView(array(
					'query'	=> $query
				),$this->tplPath.'search_empty', 'view');
			}
		}

		return $Parser->MakeView(array(
			'result' => $result,
			'query' => $query,

		), $this->tplPath.'site_search', 'view');
	}
	
	/**
	 * Render site search snippet
	 *
	 * @return string
	 */
	public function RenderSnippetSiteSearch()
	{
		GLOBAL $Parser, $Db;
		$view['url'] = $Db->Get('instance_id='.$this->iid, 'url', 'sys_ss');
		return $Parser->MakeView($view, $this->tplPath.'snippet', 'view');
	}
}
?>