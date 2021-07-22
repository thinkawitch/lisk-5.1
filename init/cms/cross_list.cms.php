<?php
/**
 * CMS Cross List
 * @package lisk
 *
 */

class CMSCrossList extends CMSCore
{
	public $name;			// System Cross tree name (in CFG file)
	public $cond;			// additional cond on parent list
	public $params;		// Array of cross tree description (in CFG file) CROSS_LIST_name

	/**
	 * @var CMSList
	 */
	public $list;			//
	/**
	 * @var CMSList
	 */
	public $crossList;		//

	public $label;			// Cross List Label
	
	public $crossField;	// field in parent list that indicates if the item is in the cross list

	function __construct($name, $cond=null)
	{
		parent::__construct();
		
		GLOBAL $App;

		if (strlen($name))
		{
			$_SESSION['cms_cross_name'] = $name;
			$_SESSION['cms_cross_cond']	= $cond;
		}
			
		$this->name = $_SESSION['cms_cross_name'];
		$this->cond	= $_SESSION['cms_cross_cond'];


		$this->params = $App->ReadCrossList($this->name);

		$this->list = new CMSList($this->params['list']);
		$this->list->Init();
		
		$this->crossList = new CMSList($this->params['list']);
		$this->crossList->Init();
		
		$this->crossField	= $this->params['cross_field'];
		$this->label		= $this->params['name'];
		
		
		$this->crossList->SetCond("{$this->crossField}>0");
		$this->crossList->order = $this->crossField;
	}

	function Render()
	{
		GLOBAL $Parser, $Paging;
		
		$this->CheckAction();

		$this->list->buttonEdit		= false;
		$this->list->buttonDeleteAll= false;
		$this->list->buttonView		= true;
		$this->list->buttonDelete	= false;
		$this->list->buttonCheckbox	= false;
		$this->list->AddButton('<img src="img/cms/add.gif" width="10" height="14" border="0" align="absmiddle"> Add', '?'.$_SERVER['QUERY_STRING']."&{$this->name}_action=add&{$this->name}_id=[id]&back=[back]");

		$Paging->SwitchOn('cp');
		$listHtml = $this->list->Render();
		$Paging->SwitchOff();

		$this->crossList->buttonEdit = false;
		$this->crossList->buttonDeleteAll = false;
		$this->crossList->buttonView = false;
		$this->crossList->buttonDelete = false;
		$this->crossList->buttonCheckbox = false;
		$this->crossList->AddButton('<img src="img/cms/remove.gif" width="16" height="14" border="0" align="absmiddle"> Remove','?'.$_SERVER['QUERY_STRING']."&{$this->name}_action=remove&{$this->name}_id=[id]&back=[back]");

		$crossListHtml = $this->crossList->Render();

		return $Parser->MakeView(array(
			'list'				=> $listHtml,
			'cross_list'		=> $crossListHtml
		), 'cms/cross_list/cross_list', 'view');
	}
	
	private function CheckAction()
	{
		$getAction	= isset($_GET[$this->name.'_action']) ? $_GET[$this->name.'_action'] : null;
		$getId		= isset($_GET[$this->name.'_id']) ? $_GET[$this->name.'_id'] : null;
		
		if ($getAction == 'add')
		{
			$this->Add($getId);
			Navigation::Jump(Navigation::Referer());
		}
		
		if ($getAction == 'remove')
		{
			$this->Remove($getId);
			Navigation::Jump(Navigation::Referer());
		}
	}

	function Add($id)
	{
		$row = $this->list->dataItem->GetValue('id='.$id);
		if (Utils::IsArray($row))
		{
			$uptadeValues = array(
				$this->crossField	=> '1'
			);
		}
		$this->list->dataItem->Update('id='.$id, $uptadeValues);
	}

	function Remove($id)
	{
		$row = $this->list->dataItem->GetValue('id='.$id);
		if (Utils::IsArray($row))
		{
			$updateValues = array(
				$this->crossField => '0'
			);

			$this->list->dataItem->Update('id='.$id, $updateValues);
		}
	}

	function MakeLinkButtons()
	{
		GLOBAL $Page;
		$orderUrl = "order.php?type={$this->crossList->dataItem->name}&back={$Page->setBack}&cond={$this->crossField}>0&field={$this->crossField}";
		$orderUrl .= $this->GetRequiredUrlVars();
		$Page->AddLink(' Order '.$this->label, $orderUrl, 'img/ico/links/order.gif');
	}
}

?>