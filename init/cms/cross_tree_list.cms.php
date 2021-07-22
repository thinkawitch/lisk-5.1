<?php
/**
 * CMS Cross Tree List
 * @package lisk
 *
 */

/*
CrossTreeList description format

'tree'	=> TREE_NAME,
'list'	=> LIST_NAME,
'label'	=> TREELIST_LABEL

*/

class CMSCrossTreeList extends CMSCore
{

	public $name;			// System Cross tree_list name (in CFG file)

	public $params;		// Array of cross tree description (in CFG file) CROSS_TREE_LIST_name

	/**
	 * @var CMSTree
	 */
	public $tree;			// node CMSTree (tree data source)
	/**
	 * @var CMSList
	 */
	public	$list;			// point CMSList instance (data destination)

	public $label;			// Cross TreeList Label

	public $parentId;		// parent Id value


	function __construct($name,$parentId)
	{
		parent::__construct();
		GLOBAL $App;

		if (strlen($name))
		{
			$_SESSION['SYS_cp_cross_name'] 		= $name;
			$_SESSION['SYS_cp_cross_parent_id']	= $parentId;
		}

		$this->name		= $_SESSION['SYS_cp_cross_name'];
		$this->parentId	= $_SESSION['SYS_cp_cross_parent_id'];

		$this->params	= $App->ReadCrossTreeList($this->name);

		$this->tree		= new CMSTree($this->params['tree']);
		$this->list		= new CMSList($this->params['list']);
		$this->list->Init();
		$this->label 	= $this->params['label'];

		// Process add
		if (@$_GET[$this->tree->treeName.'_action'] == 'add')
		{
			$this->Add($_GET[$this->tree->treeName.'_id']);
		}
		// Process remove
		if (@$_GET[$this->name.'_action'] == 'remove')
		{
			$this->Remove($_GET[$this->name.'_id']);
		}

	}

	function Add($id)
	{
		$di = Data::Create($this->params['list']);
		$di->Insert(array(
			'parent_id'	=> $this->parentId,
			'object_id'	=> $id,
			'name'		=> $this->tree->point->GetValue('id='.$id, 'name')
		));
		
		//fix ie bug
		$jump = Navigation::Referer();
		if ($jump == '' || substr($jump, -9) == 'index.htm')
		{
			$jump = Navigation::GetBack();
		}
		Navigation::Jump($jump);
	}

	function Remove($id)
	{
		$di = Data::Create($this->params['list']);
		$di->Delete('id='.$id);
		Navigation::Jump(Navigation::Referer());
	}

	function MakeLinkButtons()
	{
		GLOBAL $Page;
		// Order Links
		$orderUrl = "order.php?type={$this->list->dataItem->name}&back={$Page->setBack}&cond=parent_id={$this->parentId}";
		$Page->AddLink(' Order '.$this->list->dataItem->label, $orderUrl, 'img/ico/links/order.gif');
	}

	function Render()
	{
		GLOBAL $Parser;

		$this->tree->buttonPointEdit		= false;
		$this->tree->buttonPointDelete		= false;
		$this->tree->buttonPointCheckbox	= false;
		$this->tree->buttonPointDeleteAll	= false;
		$this->tree->buttonNodeEdit			= false;
		$this->tree->buttonNodeDelete		= false;
		$this->tree->buttonNodeCheckbox		= false;
		$this->tree->buttonNodeView			= false;
		$this->tree->buttonNodeDeleteAll	= false;

		$this->tree->AddPointButton('<img src="img/cms/add.gif" width="10" height="14" border="0" align="absmiddle"> Add', '?action=add&id=[id]&back=[back]');

		$treeHtml = $this->tree->RenderJS('cms/cross_tree/tree_js');
		$treeNavigation = $this->tree->RenderNavigation();

		$this->list->buttonEdit      = false;
		$this->list->buttonDeleteAll = false;
		$this->list->buttonView      = false;
		$this->list->buttonDelete    = false;
		$this->list->buttonCheckbox  = false;
		$this->list->AddButton('<img src="img/cms/remove.gif" width="16" height="14" border="0" align="absmiddle"> Remove', '?'.$_SERVER['QUERY_STRING']."&{$this->name}_action=remove&{$this->name}_id=[id]&back=[back]");

		$this->list->SetColumns('name');
		
		$this->list->cond = 'parent_id='.$this->parentId;

		$listHtml = $this->list->Render();

		return $Parser->MakeView(array(
			'tree_navigation'	=> $treeNavigation,
			'tree'				=> $treeHtml,
			'cross_list'		=> $listHtml
		), 'cms/cross_tree/cross_tree', 'view');
	}


}
?>