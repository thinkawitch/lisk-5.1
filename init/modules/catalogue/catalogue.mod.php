<?php

$GLOBALS['CATALOGUE_MODULE_INFO'] = array(
	'name'			=> 'Catalogue',
	'sys_name'		=> LiskModule::MODULE_CATALOGUE,
	'version'		=> '5.0',
	'description'	=> 'Products catalogue module',
	'object_name'	=> 'Catalogue',
	'multiinstance'	=> true,
	'ss_integrated'	=> true
);

/**
 * Catalogue Module Main Class
 *
 */
class Catalogue extends LiskModule
{

	/**
	 * Catalogue section base url
	 * used in tree mode
	 *
	 * @var string
	 */
	public $confBaseUrl;

	public $confDICategoriesName;

	public $confDIItemsName;

	public $confDITreeName;

	public $confDICrossTreeName;

	//paging
	public $confPagingItemsPerPage;
	public $confPagingPagesPerPage;

	/**
	 * Module templates folder
	 *
	 * @var string
	 */
	public $tplPath = 'modules/catalogue_';

	/**
	 * Tree Class object instance
	 * used for Front End rendering
	 *
	 * @var Tree
	 */
	public $tree;

	/**
	 * Constructor
	 *
	 * @return Catalogue
	 */
	function __construct($instanceId=null)
	{
		$this->name = LiskModule::MODULE_CATALOGUE;
		if ($instanceId != null) $this->Init($instanceId);
	}

	function Init($instanceId)
	{
		parent::Init($instanceId);

		$this->tplPath .= $instanceId.'/';

		$this->version = $GLOBALS['CATALOGUE_MODULE_INFO']['version'];

		$this->confBaseUrl				= $this->config['base_url'];
		$this->confDICategoriesName 	= $this->config['categories_di'];
		$this->confDIItemsName			= $this->config['items_di'];
		$this->confDITreeName			= $this->config['tree_di'];
		$this->confDICrossTreeName		= $this->config['cross_tree_di'];
		$this->confPagingItemsPerPage	= $this->config['items_per_page'];
		$this->confPagingPagesPerPage 	= $this->config['pages_per_page'];

		$this->Debug('confBaseUrl', $this->confBaseUrl);
		$this->Debug('categories DI', $this->confDICategoriesName);
		$this->Debug('items DI', $this->confDIItemsName);
		$this->Debug('tree name', $this->confDITreeName);
		$this->Debug('cross tree name', $this->confDICrossTreeName);
	}

	function SaveSettings()
	{
		GLOBAL $Db;
		$this->config['base_url'] 		= $this->confBaseUrl;
		$this->config['categories_di']	= $this->confDICategoriesName;
		$this->config['items_di']		= $this->confDIItemsName;
		$this->config['tree_di'] 		= $this->confDITreeName;
		$this->config['cross_tree_di'] 	= $this->confDICrossTreeName;
		$this->config['items_per_page'] = $this->confPagingItemsPerPage;
		$this->config['pages_per_page'] = $this->confPagingPagesPerPage;
		$Db->Update('id='.$this->iid, array(
			'config' => serialize($this->config)
		), 'sys_modules');
	}

	function InstallConfigure($instanceId, $params)
	{
	    GLOBAL $App;
		$App->LoadModule('modules/catalogue/catalogue.install.mod.php', 1);
		installCatalogueModule($instanceId, $params['path'], $params['page_name']);
	}

	function Uninstall()
	{
		GLOBAL $App;
		$App->LoadModule('modules/catalogue/catalogue.install.mod.php', 1);
		uninstallCatalogueModule($this->iid);
		parent::Uninstall();
	}

	function Snippet($params)
	{
		switch ($params['name'])
		{
			case 'menu':
				return $this->SnippetMenu($params);

			case 'hot_items':
				return $this->SnippetHotItems($params);

			default:
			    GLOBAL $App;
				$App->RaiseError("Module catalogue does not support snippet <b>{$params['name']}</b>");
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
			'menu'	=> array(
				'description'	=> 'Snippet to display main catalogue categories',
				'code'			=> '<lisk:snippet src="module" instanceId="[iid]" name="menu" />'
			),
			'hot_items' => array(
				'description'	=> 'Snippet to display hot items',
				'code'			=> '<lisk:snippet src="module" instanceId="[iid]" name="hot_items" />'
			),
		);
	}

	private function SwitchPagingOn()
	{
		GLOBAL $Paging;
		$Paging->SwitchOn('system');
		$Paging->SetItemsPerPage($this->confPagingItemsPerPage);
		$Paging->pagesPerPage = $this->confPagingPagesPerPage;
	}

	/**
	 * Render Catalogue page
	 *
	 * @return HTML
	 */
	public function Render()
	{
		GLOBAL $Scms, $App;
		$App->Load('tree', 'utils');
		$this->tree = new Tree($this->confDITreeName);

		$navigation = $this->tree->GetNavigationRows();

		$Scms->AddNavigation($navigation);

		if ($this->tree->cl == 1) return $this->RenderFeaturedItems();

		switch ($this->tree->curMode)
		{
			case TREE_NODE_LIST:
				return $this->RenderCategoriesList();
				break;
				
			case TREE_POINT_LIST:
				return $this->RenderItemsList();
				break;
				
			case TREE_POINT:
				return $this->RenderItemFullView();
				break;
		}
		
		return '';
	}

	private function RenderFeaturedItems()
	{
		GLOBAL $Parser;
		$Product = $this->tree->Point;
		$Product->Select('hot>0');

		$rez = null;
		if (Utils::IsArray($Product->values))
		{
			foreach ($Product->values as $row)
			{
				$rez[]['item_view'] = $this->RenderItemView($row, 'item_small_view');
			}

		}
		return $Parser->MakeTable($rez, 3, $this->tplPath.'featured_products');
	}

	private function RenderItemFullView()
	{
		$info = $this->tree->Point->GetValue('id='.$this->tree->currentId);
		return $this->RenderItemView($info, 'item_full_view');
	}

	private function RenderCategoriesList()
	{
		GLOBAL $Parser;
		$node = $this->tree->Node->GetValue('id='.$this->tree->cl);
		$Parser->SetCaptionVariables(array(
			'name' => $node['name'],
		));
		$this->SwitchPagingOn();
		return $this->tree->RenderCategoriesList($this->tplPath.'categories_list', 'list');
	}

	private function RenderItemsList()
	{
		GLOBAL $Parser, $Paging;
		$this->SwitchPagingOn();
		$this->tree->Point->Select('parent_id='.$this->tree->cl);
		$caption = array();
		if ($Paging->pagesTotal>1)
		{
			$caption['paging'] = $Paging->Render();
		}
		$Paging->SwitchOff();
		$rez = array();
		if (Utils::IsArray($this->tree->Point->values))
		{
			foreach ($this->tree->Point->values as $row)
			{
				$rez[]['view'] = $this->RenderItemView($row, 'item_small_view');
			}
		}
		$node = $this->tree->Node->GetValue('id='.$this->tree->cl);
		$caption['name'] = $node['name'];

		$Parser->SetCaptionVariables($caption);
		return $Parser->MakeTable($rez, 3, $this->tplPath.'items_list');
	}

	private function RenderItemView($info, $tplName)
	{
		GLOBAL $Parser;
		$Product = $this->tree->Point;
		$Product->value = $info;
		return $Parser->MakeView($Product, $this->tplPath.$tplName, 'view');
	}

	public function SnippetMenu($params)
	{
		GLOBAL $App,$Parser;
		$App->Load('tree', 'utils');
		$this->tree = new Tree($this->confDITreeName);
		$this->tree->Node->Select('parent_id=1');
		return $Parser->MakeList($this->tree->Node, $this->tplPath.'menu', 'menu');
	}

	public function SnippetHotItems($params)
	{
		GLOBAL $App,$Db,$Parser;

		$App->Load('tree', 'utils');
		$this->tree = new Tree($this->confDITreeName);

		$Product = $this->tree->Point;

		if (isset($params['count'])) $Db->SetLimit(0, $params['count']);

		$Product->Select('hot>0');
		$Db->ResetLimit();
		$rez = array();
		if (Utils::IsArray($Product->values))
		{
			foreach ($Product->values as $row)
			{
				$rez[]['item_view'] = $this->RenderItemView($row, 'item_small_view');
			}
		}

		return $Parser->MakeTable($rez, 3, $this->tplPath.'snippet_hot_items');
	}
	
    public function UpdateBaseUrl($baseUrl)
	{
	    GLOBAL $Db;
	    
	    if (!isset($this->config['base_url'])) return;
	    if ($this->config['base_url'] == $baseUrl) return;
	    
	    $oldUrl = $this->config['base_url'];
        
	    //save module settings
	    $this->config['base_url'] = $baseUrl;
		$this->SaveConfig();
		
		$len = strlen($oldUrl) + 1;
		
		//update categories urls
		$di = Data::Create($this->confDICategoriesName, false);
		$table = $di->table;
		$sql = "UPDATE $table
			SET url = CONCAT('$baseUrl', SUBSTRING(url, $len))
		";
		$Db->Query($sql);
		
		//update items urls
		$di = Data::Create($this->confDIItemsName, false);
		$table = $di->table;
		$sql = "UPDATE $table
			SET url = CONCAT('$baseUrl', SUBSTRING(url, $len))
		";
		$Db->Query($sql);
	}
}

?>