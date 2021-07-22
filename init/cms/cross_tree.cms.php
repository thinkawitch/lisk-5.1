<?php
/**
 * CMS Cross Tree
 * @package lisk
 *
 */

class CMSCrossTree extends CMSCore
{
	public $name;		// System Cross tree name (in CFG file)
	public $params;		// Array of cross tree description (in CFG file) CROSS_TREE_name

	/**
	 * @var CMSTree
	 */
	public $tree;		// node data item
	/**
	 * @var CMSList
	 */
	public $crossList;	// point data item
	/**
	 * @var string
	 */
	public $crossField; // cross field (true / false)
	
	public $label;		// Cross Tree Label

	public $cond;


	function __construct($name, $cond=null)
	{
		parent::__construct();
		GLOBAL $App;

		if (strlen($name))
		{
			$_SESSION['SYS_cp_cross_name'] = $name;
			$_SESSION['SYS_cp_cross_cond']	= $cond;
		}
		$this->name = $_SESSION['SYS_cp_cross_name'];
		$this->cond	= $_SESSION['SYS_cp_cross_cond'];

		$this->params = $App->ReadCrossTree($this->name);

		$this->tree			= new CMSTree($this->params['tree']);
		$this->crossList	= new CMSList($this->tree->params['point']);
		$this->crossList->Init();
		$this->crossField	= $this->params['cross_field'];
		$this->crossList->order = $this->crossField;
		$this->crossList->SetCond($this->crossField . ' <> 0');

		// $this->CrossList = new CMSList($this->params['cross_list']);
		$this->label = $this->params['name'];

		// set label to session for tree...
		$_SESSION['cross_tree_label'] = $this->label;

		if (@$_GET[$this->tree->treeName.'_action'] == 'add')
		{
			$this->Add($_GET[$this->tree->treeName.'_id']);
		}

		if (@$_GET[$this->name.'_action'] == 'remove')
		{
			$this->Remove($_GET[$this->name.'_id']);
		}
	}

	function Add($id)
	{
		GLOBAL $Db;
		$Db->Update('id='.$id, array($this->crossField => 1), $this->crossList->dataItem->table);
		
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
		GLOBAL $Db;
		$Db->Update('id='.$id, array($this->crossField => 0), $this->crossList->dataItem->table);
		Navigation::Jump(Navigation::Referer());
	}

	function MakeLinkButtons()
	{
	    GLOBAL $Page;
		// Order Links
		$orderUrl="order.php?type={$this->crossList->dataItem->name}&back={$Page->setBack}&field={$this->crossField}&cond={$this->crossField}<>0";
		$orderUrl .= $this->GetRequiredUrlVars();
		$Page->AddLink(' Order '.$this->crossList->dataItem->label, $orderUrl, 'img/ico/links/order.gif');
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

		$this->crossList->buttonEdit      = false;
		$this->crossList->buttonDeleteAll = false;
		$this->crossList->buttonView      = false;
		$this->crossList->buttonDelete    = false;
		$this->crossList->buttonCheckbox  = false;
		$this->crossList->AddButton('<img src="img/cms/remove.gif" width="16" height="14" border="0" align="absmiddle"> Remove', '?'.$_SERVER['QUERY_STRING']."&{$this->name}_action=remove&{$this->name}_id=[id]&back=[back]");

		$this->crossList->SetColumns('name');

		$listHtml = $this->crossList->Render();

		return $Parser->MakeView(array(
			'tree_navigation'	=> $treeNavigation,
			'tree'				=> $treeHtml,
			'cross_list'		=> $listHtml
		), 'cms/cross_tree/cross_tree', 'view');
	}
}

?>