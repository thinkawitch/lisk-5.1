<?php
/**
 * CLASS Tree
 * tool to work with tree structures
 * @package lisk
 *
 */

define ('TREE_NODE_LIST',	0);
define ('TREE_POINT_LIST',	1);
define ('TREE_POINT',		2);

class Tree
{
	/**
	 * Current Tree name
	 *
	 * @var string
	 */
	public $name;
	
	/**
	 * Node DataItem instance
	 *
	 * @var DataItem
	 */
	public $Node;
	
	/**
	 * Point DataItem instance
	 *
	 * @var DataItem
	 */
	public $Point;
	
	/**
	 * Current mode see constance for values
	 *
	 * @var integer
	 */
	public $curMode;

	/**
	 * Current level. Current Node Id
	 *
	 * @var integer
	 */
	public $cl;
	
	/**
	 * Current item (node/point) id
	 *
	 * @var integer
	 */
	public $currentId;
	
	/**
	 * Current record name
	 *
	 * @var string
	 */
	public $curName;
	
	/**
	 * Current record parents string
	 *
	 * @var string
	 */
	public $parents;

	/**
	 * If render methods should make db select on call
	 *
	 * @var boolean
	 */
	public $selfSelect = true;

	/**
	 * Tree Structure Constructor
	 *
	 * @param string $name Tree name
	 * @return Tree
	 */
	function __construct($name)
	{
		GLOBAL $App;
		$this->name = $name;

		$tree = $App->ReadTree($name);

		$this->Node = Data::Create($tree['node']);
		$this->Point = Data::Create($tree['point']);

		$this->InitUrl($GLOBALS['contentUrl']);
	}

	/**
	 * Initialize tree by query url
	 *
	 * @param string $url
	 */
	public function InitUrl($url)
	{
		// razborka urla
		if ($node = $this->Node->GetValue("url='$url'", 'id,parent_id,parents,name'))
		{
			//$this->curMode = TREE_NODE;
			$this->cl		= $node['id'];
			$this->currentId = $node['id'];
			$this->parents	= $node['parents'];
			$this->curName	= $node['name'];

			// detect is the current node contains nodes or not
			$childId = $this->Node->GetValue("parent_id={$this->cl}", 'id');

			// if no nodes found it's items list
			// else nodes list
			if ($childId === false) $this->curMode = TREE_POINT_LIST;
			else $this->curMode = TREE_NODE_LIST;
		}
		elseif ($point = $this->Point->GetValue("url='$url'", 'id,parent_id,parents,name'))
		{
			$this->curMode	= TREE_POINT;
			$this->cl		= $point['parent_id'];
			$this->currentId = $point['id'];
			$this->parents	= $point['parents'];
			$this->curName	= $point['name'];
		}
		else
		{
			// nothing's found
		}
	}

	/**
	 * Add paging variables in render
	 *
	 * @param Data $di
	 */
	private function ProcessPaging(Data $di)
	{
		GLOBAL $Parser,$Paging;

		if (!$Paging->IsOn()) return;

		$Parser->SetCaptionVariables(array(
			'paging_items_total' => $Paging->itemsTotal,
		));

		if ($Paging->pagesTotal>1)
		{
			$Parser->SetCaptionVariables(array(
				'paging_from' => $Paging->showFrom,
				'paging_to'   => $Paging->showTo,
				'paging'      => $Paging->Render(),
			));
		}

		if (Utils::IsArray($di->values))
		{
			foreach ($di->values as $k=>$v)
			{
				$di->values[$k]['paging_index'] =  $Paging->showFrom + $k;
			}
		}

		$Paging->SwitchOff();
	}

	/**
	 * Render Categorie list
	 *
	 * @param string $tplName
	 * @param string $blockName
	 * @return HTML
	 */
	public function RenderCategoriesList($tplName, $blockName)
	{
		GLOBAL $Parser;

		if ($this->selfSelect) $this->Node->Select('parent_id='.$this->cl);

		$this->ProcessPaging($this->Node);

		return $Parser->MakeList($this->Node, $tplName, $blockName);
	}

	/**
	 * Render Categorie table
	 *
	 * @param int $cols
	 * @param string $tplName
	 * @param string $blockName
	 * @return HTML
	 */
	public function RenderCategoriesTable($cols, $tplName, $blockName)
	{
		GLOBAL $Parser;

		if ($this->selfSelect) $this->Node->Select('parent_id='.$this->cl);

		$this->ProcessPaging($this->Node);

		return $Parser->MakeTable($this->Node, $cols, $tplName, $blockName);
	}

	/**
	 * Render Items (Points) list
	 *
	 * @param string $tplName
	 * @param string $blockName
	 * @return HTML
	 */
	public function RenderItemsList($tplName,$blockName)
	{
		GLOBAL $Parser;

		if ($this->selfSelect) $this->Point->Select('parent_id='.$this->cl);

		$this->ProcessPaging($this->Point);

		return $Parser->MakeList($this->Point, $tplName, $blockName);
	}

	/**
	 * Render Items (Points) table
	 *
	 * @param int $cols
	 * @param string $tplName
	 * @param string $blockName
	 * @return HTML
	 */
	public function RenderItemsTable($cols, $tplName, $blockName)
	{
		GLOBAL $Parser;

		if ($this->selfSelect) $this->Point->Select('parent_id='.$this->cl);

		$this->ProcessPaging($this->Point);

		return $Parser->MakeTable($this->Point, $cols, $tplName, $blockName);
	}

	/**
	 * Render Point view
	 *
	 * @param string $tplName
	 * @param string $blockName
	 * @return HTML
	 */
	public function RenderItemView($tplName, $blockName)
	{
		GLOBAL $Parser;

		if ($this->selfSelect) $this->Point->Get('id='.$this->currentId);

		return $Parser->MakeView($this->Point, $tplName, $blockName);
	}

	/**
	 * Render Navigation string
	 *
	 * @param string $tplName
	 * @param string $blockName
	 * @return HTML
	 */
	public function RenderNavigation($tplName, $blockName, $showRoot=true)
	{
		GLOBAL $Parser;
		$values = $this->GetNavigationRows();

		// hide root if needed
		if (!$showRoot) array_shift($values);

		return $Parser->MakeNavigation($values, $tplName, $blockName);
	}

	/**
	 * Get navigation string array
	 *
	 * @return array
	 */
    public function GetNavigationRows()
	{
		$in = Utils::TreeToIn($this->parents);

		if (strlen($in))
		{
			$rows = $this->Node->SelectValues("id IN $in");
			$rows = Utils::OrderByParents($in, $rows);
		}
		else
		{
			$rows = array();
		}

		$result = $rows;
		$result[] = array(
			'name'	=> $this->curName,
			'url'	=> ''
		);
		return $result;
	}
}

?>