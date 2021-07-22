<?php
/**
 * CMS Node Tree
 * @package lisk
 *
 */

class CMSNodeTree extends CMSCore
{

	public $diName;
	public $label;			// Tree DI label
	
	public $maxLevel;		// ?????? Max Level. 1-list, 2-folder, 3-folder-subfolder, etc.
    
	/**
	 * data item
	 *
	 * @var Data
	 */
	public $di;
	private $addButtons = array();	// additional buttons
	public $back;			// current page back
	
	public $viewField = 'name';		// main view filed (name)
	
	public $cl;			// current level (node ID)


	public $buttonNodeAdd 	= true;
	public $buttonNodeView 	= true;
	public $buttonNodeEdit 	= true;
	public $buttonNodeDelete	= true;
	public $buttonNodeCheckbox	= true;
	public $buttonNodeDeleteAll = true;

	function __construct($diName)
	{
		parent::__construct();
		GLOBAL $App;
		$App->Load('cpmodules','lang');
		$this->InitializeTree($diName);
	}

	private function InitializeTree($diName)
	{
		if (strlen($diName)) $_SESSION['SYS_CP_tree_name'] = $diName;
		
		$this->diName	= $_SESSION['SYS_CP_tree_name'];
		$this->di		= Data::Create($this->diName);
		$this->cl		= (!isset($_GET['cl']) || $_GET['cl'] < 1) ? 1 : $_GET['cl'];
		$this->label	= $this->di->label;

		//$nodeListFields			= explode(',',$this->node->listFields);
		//$this->nodeViewField	= $nodeListFields[0];


		// arrange current nesting level
		//$nodeParents = $Db->get("id = ".$this->cl, 'parents', $this->node->table);
		//$this->nestingLevel = substr_count($nodeParents, '>');
	}
	
	function AddButton($name, $link, $hint=null, $icon=null)
	{
		$this->addButtons[] = array(
			'name'	=> $name,
			'link'	=> $link,
			'hint'	=> $hint,
			'icon'	=> $icon,
		);
	}

	function DeleteSelected()
	{
		foreach (array_keys($_POST) as $key)
		{
			if (substr($key,0,2) == 'r_')
			{
				$id = substr($key,2);
				$cond = "id=$id";
				$this->di->Delete($cond);
			}
		}
		Navigation::Jump(Navigation::Referer());
	}

	function MakeLinkButtons()
	{
		GLOBAL $Page;

		$hiddenVariables = ''; //TODO
		$addUrl = "add.php?type={$this->diName}&back={$Page->setBack}&redefine=category&HIDDEN_parent_id={$this->cl}{$hiddenVariables}";
		$addUrl .= $this->GetRequiredUrlVars();
		$Page->AddLink('Add Category', $addUrl, 'img/ico/links/add.gif','Add new category.');

		
		$addUrl = "add.php?type={$this->diName}&back={$Page->setBack}&redefine=record&HIDDEN_parent_id={$this->cl}";
		$addUrl .= $this->GetRequiredUrlVars();
		$Page->AddLink('Add Record', $addUrl, 'img/ico/links/add.gif','Add new record');

		// Order Links
		if ($this->di->order=='oder')
		{
			$orderUrl = "order.php?type={$this->diName}&back={$Page->setBack}&cond=parent_id={$this->cl}";
			$orderUrl .= $this->GetRequiredUrlVars();
			$Page->AddLink(' Order ', $orderUrl, 'img/ico/links/order.gif','Change records order.');
		}

	}

	function AdditionalNavigation()
	{
		GLOBAL $Parser,$Page;

		$rez = $Parser->MakeView(array(
			'jump_field'	=> $this->MakeJumpField(),
			'navigation'	=> $this->RenderNavigation()
		),'cms/tree/tree','additional_navigation');
		
		$Page->customLine = $rez;
	}

	function RenderNavigation()
	{
		GLOBAL $Parser,$Db;
		$id			= $this->cl;
		$parents	= $Db->Get("id=$id",'parents', $this->di->table);
		$parents	= Utils::TreeToIn($parents."<$id>");
		$values		= $Db->Select("id IN $parents", 'id', 'id,name', $this->di->table);
		
		$Parser->SetAddVariables(array(
			'back'	=> $this->back
		));
		return $Parser->MakeNavigation($values,'cms/tree/tree','nav');
	}

	function Render($blockName='cms_list')
	{
		GLOBAL $Paging;

		$List = new CMSList($this->di);
		$List->SetCond("parent_id={$this->cl}");
		$List->Init();
								
		// copy buttons view status
		$List->buttonCheckbox	= $this->buttonNodeCheckbox;
		$List->buttonDeleteAll	= $this->buttonNodeDeleteAll;
		$List->buttonDelete 	= $this->buttonNodeDelete;
		$List->buttonEdit		= $this->buttonNodeEdit;
		$List->buttonView		= $this->buttonNodeView;

		$List->SetFieldLinkByCond('name','?cl=[id]&back=[back]',$this->di->fields['parent_id']->categoryCond);
				
		// node add buttons
		if (Utils::IsArray($this->addButtons))
		{
			foreach ($this->addButtons as $row)
			{
				$List->AddButton($row['name'], $row['link'], $row['hint'], $row['icon']);
			}
		}
		
		// initialize paging
		$Paging->SwitchOn('cp');
		return $List->Render($blockName);
	}

	private function NodeSort($parent, $rows, $cl)
	{
		STATIC $rez;
		if (Utils::IsArray($rows))
		{
			foreach($rows as $row)
			{
				if ($row['parent_id']==$parent)
				{
					$rez[$row['id']] = str_repeat("&nbsp;", substr_count($row['parents'],">")*2).$row[$this->viewField];
					$this->NodeSort($row['id'], $rows, $cl, $this->viewField);
				}
			}
		}
		return $rez;
	}


	function MakeJumpField($mode='')
	{
		GLOBAL $Db,$App;
		
		// mi prosto ubiraem [] iz tree cond i schitaem chto eto sql cond ?
		$cond = $this->di->fields['parent_id']->categoryCond;
		$cond = str_replace('[','',$cond);
		$cond = str_replace(']','',$cond);
		$cond = str_replace('==','=',$cond);

		$rows	= $Db->Select($cond, null, null, $this->di->table);
		$arr	= $this->NodeSort(0, $rows, $this->cl);

		$App->Load('list','type');
		$list	= new T_list(array(
			'object'	=> 'arr',
		    'name' => 'cat_dropdown'
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

		$list->values = $arr;
		$list->value = $this->cl;
		return $list->RenderFormTplView();
	}
}
?>