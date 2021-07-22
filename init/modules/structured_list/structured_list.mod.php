<?php

$GLOBALS['STRUCTURED_LIST_MODULE_INFO'] = array(
	'name'			=> 'Structured List',
	'sys_name'		=> LiskModule::MODULE_STRUCTURED_LIST,
	'version'		=> '5.0',
	'description'	=> 'Structured list',
	'object_name'	=> 'StructuredList',
	'multiinstance'	=> true,
	'ss_integrated'	=> true
);

/**
 * Structured List Module Main Class
 *
 */
class StructuredList extends LiskModule
{

	/**
	 * Structured list section base url
	 * used in tree mode
	 *
	 * @var string
	 */
	public $confBaseUrl;

	public $confDIName;

	public $tplPath = 'modules/structured_list_';


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
		$this->name = LiskModule::MODULE_STRUCTURED_LIST;
		if ($instanceId != null) $this->Init($instanceId);
	}

	public function Init($instanceId)
	{
		parent::Init($instanceId);

		$this->tplPath.=$instanceId.'/';

		$this->version = $GLOBALS['STRUCTURED_LIST_MODULE_INFO']['version'];

		$this->confBaseUrl	= $this->config['base_url'];
		$this->confDIName = $this->config['structured_list_di'];

		$this->confPagingItemsPerPage = $this->config['items_per_page'];
		$this->confPagingPagesPerPage = $this->config['pages_per_page'];

		$this->Debug('list DI', $this->confDIName);
	}

	function SaveSettings()
	{
		GLOBAL $Db;
		$this->config['base_url'] = $this->confBaseUrl;
		$this->config['structured_list_di'] = $this->confDIName;
		$this->config['items_per_page'] = $this->confPagingItemsPerPage;
		$this->config['pages_per_page'] = $this->confPagingPagesPerPage;

		$Db->Update('id='.$this->iid, array(
			'config' => serialize($this->config)
		), 'sys_modules');
	}

	function InstallConfigure($instanceId, $params)
	{
		$GLOBALS['App']->LoadModule('modules/structured_list/structured_list.install.mod.php', 1);
		installStructuredListModule($instanceId, $params['path']);
	}

	function Uninstall() {
		$GLOBALS['App']->LoadModule('modules/structured_list/structured_list.install.mod.php', 1);
		uninstallStructuredListModule($this->iid);
		parent::Uninstall();
	}

	public function Render()
	{
		return $this->RenderList();
	}

	private function RenderList()
	{
		GLOBAL $Parser, $Paging;

		$di = Data::Create($this->confDIName);

		$Paging->SwitchOn('system');
		$Paging->SetItemsPerPage($this->confPagingItemsPerPage);
		$Paging->pagesPerPage = $this->confPagingPagesPerPage;
		$di->Select();

		$caption['paging'] = $Paging->Render();
		$Paging->SwitchOff();
		$Parser->SetCaptionVariables($caption);

		return $Parser->MakeList($di, $this->tplPath.'list', 'structured_list');
	}

	public function RenderLatest($params)
	{
		GLOBAL $Parser, $Db;
		$count = $params['count'];
		$DI = Data::Create($this->confDIName);
		if ($count>0) $Db->SetLimit(0, $count);
		
		$DI->values = $Db->Select(null, 'id DESC', null, $DI->table);
		$Db->ResetLimit();
		return $Parser->MakeList($DI, $this->tplPath.'snippet_latest', 'latest_structured_list');

	}

	public function Snippet($params)
	{
		switch (strtolower($params['name']))
		{
			case 'last':
			default:
				return $this->RenderLatest($params);
				break;
		}
	}
	
	/**
	 * Get all available snippets of module
	 *
	 * @return array
	 */
	public function AvailableSnippets()
	{
		return array(
			'last'	=> array(
				'description'	=> 'Snippet to display last (count) records',
				'code'			=> '<lisk:snippet src="module" instanceId="[iid]" name="last" count="1" />'
			),
		);
	}
}
?>