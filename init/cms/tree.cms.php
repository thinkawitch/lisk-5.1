<?php
/**
 * CMS Tree
 * @package lisk
 *
 */

class CMSTree extends CMSCore
{
	public $treeName;		// System Tree name (in CFG file)
	public $params;		// Array of tree description (in CFG file)
	public	$label;			// Tree Label
	public	$maxLevel;		// Max Level. 1-list, 2-folder, 3-folder-subfolder, etc.

	/**
	 * @var Data
	 */
	public $node;			// node data item
	/**
	 * @var Data
	 */
	public $point;			// point data item
		
	public $cond = null;			// conditional applies both for node/point
	public $hiddenVariables;

	public $cl;			// current level (node ID)
	public $clMode;		// 0 - nothing 1 - nodes 2 - points
	public $nestingLevel;	// current Nesting Level (see max level)

	public $nodeViewField;	// name of the node's display field
	public $pointViewField; // name of the point's display field

	private $treeJSStructure	= array();	// array that stores structure for JS Tree

	public $buttonNodeAdd 	    = true;
	public $buttonNodeView 	= true;
	public $buttonNodeEdit 	= true;
	public $buttonNodeDelete	= true;
	public $buttonNodeCheckbox	= true;
	public $buttonNodeDeleteAll= true;
	public $buttonPointView 	= true;
	public $buttonPointEdit 	= true;
	public $buttonPointDelete	= true;
	public $buttonPointCheckbox = true;
	public $buttonPointDeleteAll= true;

	protected $pointAddButtons	= array();
	protected $nodeAddButtons	= array();

	public $back;

	function __construct($treeName)
	{
		parent::__construct();
		GLOBAL $App;
		$App->Load('cpmodules', 'lang');
		$this->InitializeTree($treeName);

	}

	private function InitializeTree($treeName)
	{
		GLOBAL $App,$Db;
		if (!strlen($treeName))
		{
			$this->treeName = $_SESSION['cms_tree_name'];
		}
		else
		{
			$_SESSION['cms_tree_name'] = $treeName;
			$this->treeName = $treeName;
		}

		$this->params = $App->ReadTree($this->treeName);

		$this->node		= Data::Create($this->params['node']);
		$this->point	= Data::Create($this->params['point']);
		$this->label	= $this->params['name'];
		$this->maxLevel	= $this->params['max_level'];
		$this->cl		= (!isset($_GET['cl']) || $_GET['cl'] < 1) ? 1 : $_GET['cl'];

		$nodeListFields = explode(',', $this->node->listFields);
		foreach ($nodeListFields as $name)
		{
		    $fieldObj = $this->node->fields[$name];
		    //name should be the string type
		    if ($fieldObj instanceof T_Input || $fieldObj instanceof T_Text)
		    {
		        $this->nodeViewField = $name;
		        break;
		    }
		}
		if ($this->nodeViewField == null) $App->RaiseError('['.$this->treeName.'] Tree::nodeViewField is undefined!');
		
		
		$pointListFields = explode(',', $this->point->listFields);
		foreach ($pointListFields as $name)
		{
		    $fieldObj = $this->point->fields[$name];
		    //name should be the string type
		    if ($fieldObj instanceof T_Input || $fieldObj instanceof T_Text)
		    {
		        $this->pointViewField = $name;
		        break;
		    }
		}
		if ($this->pointViewField == null) $App->RaiseError('['.$this->treeName.'] Tree::pointViewField is undefined!');
		
		
		// define current level mode
		
		$isNode = $Db->Get('parent_id='.$this->cl, 'id', $this->node->table);
		$isPoint = $Db->Get('parent_id='.$this->cl, 'id', $this->point->table);
		$isNode = (!empty($isNode));
		$isPoint = (!empty($isPoint));

		if ($isNode) $this->clMode = 1;
		elseif ($isPoint) $this->clMode = 2;
		else $this->clMode = 0;

		// arrange current nesting level
		$nodeParents = $Db->get('id='.$this->cl, 'parents', $this->node->table);
		$this->nestingLevel = substr_count($nodeParents, '>');
	}
	
	public function SetCond($cond)
	{
		$this->cond = (strlen($cond)) ? $cond : null;
		if ($this->cond != null)
		{
			$array = explode('&', $this->cond);
			foreach ($array as $key=>$item)
			{
				$array[$key] = 'HIDDEN_'.$item;
			}
			$this->hiddenVariables = implode('&', $array);
		}
		else
		{
			$this->hiddenVariables = null;
		}
	}

	public function AddNodeButton($name, $link, $hint=null, $icon=null)
	{
		$this->nodeAddButtons[] = array(
			'name'	=> $name,
			'link'	=> $link,
			'hint'	=> $hint,
			'icon'	=> $icon,
		);
	}

	public function AddPointButton($name, $link, $hint=null, $icon=null)
	{
		$this->pointAddButtons[] = array(
			'name'	=> $name,
			'link'	=> $link,
			'hint'	=> $hint,
			'icon'	=> $icon,
		);
	}

	public function DeleteSelected()
	{
		switch ($this->clMode)
		{
			case 1:
				$di = $this->node;
				break;

			case 2:
				$di = $this->point;
				break;
		}

		foreach ($_POST['ids'] as $id)
		{
            $di->Delete('id='.$id);
		}
		
		Navigation::Jump(Navigation::Referer());
	}
	
	private function DeleteJSSelected()
	{
		foreach ($_POST as $key=>$value)
		{
			if ( (substr($key,0,3) == 'ch_') && $value == '1')
			{
				$id = substr($key, 3);
				$DataItem = $this->node;
				if (substr($id, 0, 5) == '99999')
				{
					$DataItem = $this->point;
					$id = substr($id, 5);
				}
				else
				{
					if($id == 1) continue;
				}

				$cond = "id=$id";
				$DataItem->Delete($cond);
			}
		}
	}

	public function MakeLinkButtons()
	{
		GLOBAL $Page;
			
		// Add links
		if ($this->buttonNodeAdd && $this->clMode != 2 && $this->nestingLevel < $this->maxLevel)
		{
			$addUrl = "add.php?type={$this->node->name}&back={$Page->setBack}&HIDDEN_parent_id={$this->cl}&{$this->hiddenVariables}";
			$addUrl .= $this->GetRequiredUrlVars();
			$Page->AddLink('Add '.$this->node->label, $addUrl, 'img/ico/links/add.gif', 'Add new category.');
		}

		if ($this->clMode != 1)
		{
			$addUrl = "add.php?type={$this->point->name}&back={$Page->setBack}&HIDDEN_parent_id={$this->cl}";
			$addUrl .= $this->GetRequiredUrlVars();
			$Page->AddLink('Add '.$this->point->label,$addUrl, 'img/ico/links/add.gif', 'Add new record to the list.');
		}

		// Order Links
		if ($this->clMode == 1 && $this->node->order == 'oder')
		{
			$orderUrl = "order.php?type={$this->node->name}&back={$Page->setBack}&cond=parent_id={$this->cl}";
			$orderUrl .= $this->GetRequiredUrlVars();
			$Page->AddLink(' Order '.$this->node->label, $orderUrl, 'img/ico/links/order.gif', 'Change categories order.');
		}

		if ($this->clMode == 2 && $this->point->order == 'oder')
		{
			$orderUrl = "order.php?type={$this->point->name}&back={$Page->setBack}&cond=parent_id={$this->cl}";
			$orderUrl .= $this->GetRequiredUrlVars();
			$Page->AddLink(' Order '.$this->point->label, $orderUrl, 'img/ico/links/order.gif', 'Change records order.');
		}

	}

	public function MakeJSLinkButtons()
	{
		GLOBAL $Page;

		$Page->AddLink("Expand all", "javascript: expandAll();", 'img/cms/tree/link_collapse.gif');
		$Page->AddLink("Collapse all", "javascript: collapseAll();", 'img/cms/tree/link_expand.gif');

		if (@$_GET['checkboxes'] == 'true')
		{
			$Page->AddLink("Hide checkboxes", Navigation::AddGetVariable(array('checkboxes' => 'false')), 'img/cms/tree/link_check_hide.gif');
			$Page->AddLink("Delete selected", "javascript: if(confirm('Are you sure to delete selected items?')) document.getElementById('treeForm').submit();" , 'img/cms/tree/link_del_selected.gif');
		}
		else
		{
			$Page->AddLink("View checkboxes", Navigation::AddGetVariable(array('checkboxes'=>'true')), 'img/cms/tree/link_check_view.gif');
		}
	}

	public function AdditionalNavigation()
	{
		GLOBAL $Parser,$Page;

		$Page->customLine = $Parser->MakeView(array(
			'jump_field'	=> $this->MakeJumpField(),
			'navigation'	=> $this->RenderNavigation()
		), 'cms/tree/tree', 'additional_navigation');
		
	}
	
	public function Search()
	{
		GLOBAL $Parser,$Page;

		$GLOBALS['DATA_TREE_SEARCH'] = array(
			'fields'	=> array(
				'keyword'		=> LiskType::TYPE_INPUT,
				'search_for' => array(
					'type'		=> LiskType::TYPE_RADIO,
					'object'	=> 'def_tree_search'
				)
			)
		);

		$GLOBALS['LIST_TREE_SEARCH'] = array(
			'item'		=> 'Item',
			'category'	=> 'Category'
		);

		$SearchDI = Data::Create('tree_search');
		$rez = '';
		
		if (strlen(@$_GET['keyword']))
		{
			$rez = $this->RenderSearchResult($_GET['search_for'], $_GET['cat_dropdown'], $_GET['keyword'], $Page->back);

			$SearchDI->value = $_GET;
			$this->cl = $_GET['cat_dropdown'];
		}

		if (@$_GET['search_for'] != 'category')
		{
			$SearchDI->value['search_for'] = 'item';
		}

		$Parser->SetCaptionVariables(array(
			'search_in'	=> $this->MakeJumpField('search'),
			'results'	=> $rez
		));

		return $Parser->MakeForm($SearchDI, 'cms/tree/tree_search', 'search');
	}
	
	public function Advanced()
	{
		GLOBAL $Parser,$App;
		
		if (@$_POST['action'] == 'advanced_copy') Navigation::Jump(Navigation::Referer());
		
		if (@$_POST['action'] == 'advanced_move')
		{
			$from = $_POST['copy_from'];
			$to = $_POST['copy_to'];
			$Node = $this->node;
			$Point = $this->point;
			
			if ($from != $to)
			{
				$Node->Select('parent_id='.$from);
				$counter1 = 0;
				$counter2 = 0;
				if (Utils::IsArray($Node->values))
				{
					foreach ($Node->values as $category)
					{
						$Node->Update('id='.$category['id'], array(
							'id' => $category['id'],
							'parent_id'	=> $to,
							'name' => $category['name'],
						));
						$counter1++;
					}
				}
				
				$Point->Select('parent_id='.$from);
				if (Utils::IsArray($Point->values))
				{
					foreach ($Point->values as $item)
					{
						$Point->Update('id='.$item['id'], array(
							'id' => $item['id'],
							'parent_id'	=> $to,
							'name' => $category['name'],
						));
						$counter2++;
					}
				}
				$App->SetError("$counter1 categories and $counter2 items has been moved.");
			}
			Navigation::Jump(Navigation::Referer());
		}
		
		$values = array(
			'copy_from'	=> $this->MakeJumpField('search','copy_from'),
			'copy_to'	=> $this->MakeJumpField('search','copy_to'),
		);
		return $Parser->MakeView($values, 'cms/tree/tree_advanced', 'advanced');
	}

	public function RenderNavigation()
	{
		GLOBAL $Parser;
		$Parser->setAddVariables(array(
			'back'	=> $this->back
		));
		return $Parser->MakeNavigation(Utils::TreeToNavigation($this->cl,$this->treeName), 'cms/tree/tree', 'nav');
	}

	public function RenderJS($tplName='cms/tree/tree_js')
	{
		GLOBAL $Parser,$Page;
		
		if (@$_POST['action'] == 'js_delete')
		{
			$this->DeleteJSSelected();
			Navigation::Jump(Navigation::Referer());
		}

		return $Parser->MakeView(array(
			'js_tree'			=> $this->GetJSStructure(),
			'nodes_menu'		=> $this->GetJSNodeMenu(),
			'hidden_checkboxes'	=> $this->GetHiddenCheckboxes(),
			'node_name'			=> $this->node->name,
			'point_name'		=> $this->point->name,
			'view_check'		=> (@$_GET['checkboxes'] == 'true') ? 'true' : 'false',
			'cur_nav_level'		=> $Page->back,
			'cross_tree_label'	=> @$_SESSION['cross_tree_label'],
			'query_string'		=> '?'.$_SERVER['QUERY_STRING'],
			'tree_name'			=> $this->treeName
		), $tplName, 'tree');

	}

	public function Render()
	{
		GLOBAL $Parser,$Paging,$Page;

		$this->back = $Page->setBack;

		switch ($this->clMode)
		{
			case 0:
				return $Parser->GetHtml('cms/tree/tree', 'empty');
				break;
				
			case 1:
				// nodes
				$list = new CMSList($this->node);
				$list->Init();
				$cond = 'parent_id='.$this->cl;
				$cond .= ($this->cond != null) ? '&'.$this->cond : '';
				
				$list->SetCond($cond);
								
				// copy buttons view status
				$list->buttonCheckbox	= $this->buttonNodeCheckbox;
				$list->buttonDeleteAll	= $this->buttonNodeDeleteAll;
				$list->buttonDelete 	= $this->buttonNodeDelete;
				$list->buttonEdit		= $this->buttonNodeEdit;
				$list->buttonView		= $this->buttonNodeView;
			
				$list->SetFieldLink('name', '?cl=[id]&back=[back]');
				
				// node add buttons
				if (Utils::IsArray($this->nodeAddButtons))
				{
					foreach ($this->nodeAddButtons as $row)
					{
						$list->AddButton($row['name'], $row['link'], $row['hint'], $row['icon']);
					}
				}
				// initialize paging
				$Paging->SwitchOn('cp');
				return $list->Render();
				break;
				
			case 2:
				$list = new CMSList($this->point);
				$list->Init();
				$list->SetCond('parent_id='.$this->cl);
				// copy buttons view status
				$list->buttonCheckbox	= $this->buttonPointCheckbox;
				$list->buttonDeleteAll	= $this->buttonPointDeleteAll;
				$list->buttonDelete 	= $this->buttonPointDelete;
				$list->buttonEdit		= $this->buttonPointEdit;
				$list->buttonView		= $this->buttonPointView;
				// point add buttons
				if (Utils::IsArray($this->pointAddButtons))
				{
					foreach ($this->pointAddButtons as $row)
					{
						$list->AddButton($row['name'], $row['link'], $row['hint'], $row['icon']);
					}
				}

				// initialize paging
				$Paging->SwitchOn('cp');
				return $list->Render();
				break;
		}
		return '';
	}

	public function RenderSearchResult($searchFor, $searchIn, $keyword)
	{
		$cond = array();

		switch ($searchFor)
		{
			case 'item':
				$di = $this->point;
				break;
				
			case 'category':
				$di = $this->node;
				break;
		}

		// set label for parent_id field
		$di->fields['parent_id']->label = 'Category';

		$fields = $di->GetFieldsByType('input,text,html');
		$searchFields = explode(',', $fields);
		foreach ($searchFields as $field)
		{
			$cond[] = "$field LIKE '%{$keyword}%'";
		}
		$cond = implode(' OR ',$cond);
		$cond = "parents LIKE '%<{$searchIn}>%' AND ($cond)";

		$List = new CMSList($di);
		$List->Init();
		$List->buttonDeleteAll = false;
		$List->buttonCheckbox = false;
		$List->SetColumns('name,parent_id');
		$List->cond = $cond;
		return $List->Render();
	}

	private function NodeSort($parent, $rows, $cl)
	{
		STATIC $rez;
		if (Utils::IsArray($rows))
		{
			foreach($rows as $row)
			{
				if ($row['parent_id'] == $parent)
				{
					$rez[$row['id']] = str_repeat('&nbsp;', substr_count($row['parents'], '>') * 2).$row[$this->nodeViewField];
					$this->NodeSort($row['id'], $rows, $cl, $this->nodeViewField);
				}
			}
		}
		return $rez;
	}

	private function GetJSStructure()
	{
		GLOBAL $Db;
		$this->GenerateTreeJSStructure(1);
		$rootName = $Db->Get('id=1', 'name', $this->node->table);
		if (!strlen($rootName))
		{
			$rootName = 'Root';
		}
		$rootName = $this->EscapeJsStr($rootName);
		$str = "t.add(1, 0, \"$rootName\", \"\", \"\", true);\n\r";
		foreach ($this->treeJSStructure as $key=>$row) {
			$mode=0;
			if ($row['type'] == 'node')
			{
				foreach ($this->treeJSStructure as $row2)
				{
					if ($row2['parent_id'] == $row['id'])
					{
						if ($row2['type'] == 'node') $mode = 1;
						if ($row2['type'] == 'point') $mode = 2;
					}
				}
				$this->treeJSStructure[$key]['mode'] = $mode;
			}
		}

		foreach ($this->treeJSStructure as $rec)
		{
			$rec['name'] = $this->EscapeJsStr($rec['name']);
			if ($rec['type']=='node')
			{
				$str.="t.add({$rec['id']}, {$rec['parent_id']}, \"{$rec['name']}\", \"\", \"img/cms/tree/js/folder.gif, img/cms/tree/js/folderopen.gif\", null);\n\r";
			}
			else
			{
				$str.="t.add({$rec['id']}, {$rec['parent_id']}, \"{$rec['name']}\", \"\", \"\", null);\n\r";
			}
		}
		return $str;
	}

	private function EscapeJsStr($str)
	{
		//for now
		return str_replace('"', '\"', $str);
	}

	private function GetJSNodeMenu()
	{
		$nodesMenu = '';
		foreach ($this->treeJSStructure as $obj)
		{
			if ($obj['type'] == 'node')
			{
				if ($obj['mode'] == 1)
				{
					$nodesMenu .= "t.setNodeCtxMenu({$obj['id']}, nodeMenu1);\n\r";
				}
				elseif ($obj['mode'] == 2)
				{
					$nodesMenu .= "t.setNodeCtxMenu({$obj['id']}, nodeMenu2);\n\r";
				}
				elseif ($obj['mode'] == 0)
				{
					if ($obj['nesting'] < $this->maxLevel)
					{
						$nodesMenu .= "t.setNodeCtxMenu({$obj['id']}, nodeMenu0);\n\r";
					}
					else
					{
						$nodesMenu .= "t.setNodeCtxMenu({$obj['id']}, nodeMenu2);\n\r";
					}
				}
			}
		}
		return $nodesMenu;
	}

	private function GenerateTreeJSStructure($id)
	{
		$N = $this->node;
		$P = $this->point;

		$subCategories = $N->SelectValues('parent_id='.$id, "id,parent_id,parents,{$this->nodeViewField}");
		if ($subCategories != false)
		{
			foreach ($subCategories as $cat)
			{
				$nestingLevel = substr_count($cat['parents'], '>');
				$this->treeJSStructure[] = array(
					'id'		=> $cat['id'],
					'parent_id'	=> $cat['parent_id'],
					'name'		=> $cat[$this->nodeViewField],
					'type'		=> 'node',
					'nesting'	=> $nestingLevel
				);
				$this->GenerateTreeJSStructure($cat['id']);
			}
		}
		else
		{
			$items = $P->SelectValues('parent_id='.$id, "id,parent_id,{$this->pointViewField}");
			if ($items != false)
			{
				foreach ($items as $item)
				{
					$this->treeJSStructure[] = array(
						'id'		=> '99999'.$item['id'],
						'parent_id'	=> $item['parent_id'],
						'name'		=> $item[$this->pointViewField],
						'type'		=> 'point'
					);
				}
			}
		}
	}

	private function GetHiddenCheckboxes()
	{
		$arr = $this->treeJSStructure;
		$str = '';
		if (Utils::IsArray($arr))
		{
			foreach ($arr as $element)
			{
				$str .= "<input type=\"hidden\" name=\"ch_{$element['id']}\" id=\"ch_{$element['id']}\" value=\"0\">";
			}
		}
		return  $str;
	}

	public function MakeJumpField($mode='', $name='cat_dropdown')
	{
		GLOBAL $Db,$App;

		$rows = $Db->Select(null, $this->node->order, null, $this->node->table);
		$arr = $this->NodeSort(0, $rows, $this->cl);

		$App->Load('list', 'type');
		$list	= new T_list(array(
			'object'	=> 'arr',
		));

		switch ($mode)
		{
			case 'search':
				$list->AddFormParam('style', 'font-size: 12px;');
				break;
				
			default:
				$list->autoJump = "?back={$this->back}&cl=";
				$list->AddFormParam('style', 'font-size: 10px;');
				break;

		}

		$list->name = $name;
		$list->values = $arr;
		$list->value = $this->cl;
		return $list->RenderFormTplView();
	}
}
?>