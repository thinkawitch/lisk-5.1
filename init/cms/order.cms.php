<?php
/**
 * CMS Order
 * @package lisk
 *
 */

class CMSOrder
{
	/**
	 * DataItem to Sort
	 *
	 * @var Data
	 */
	public $dataItem;
	
	/**
	 * Fild name that is showen in listbox
	 *
	 * @var string
	 */
	public $viewField;
	
	/**
	 * Cond that is used for list to sort select
	 *
	 * @var string
	 */
	public $cond;
	
	/**
	 * Field that represent order (by default "oder")
	 *
	 * @var string
	 */
	public $field;
	
	function __construct($dataItem)
	{
		GLOBAL $App;
		$App->Load('cpmodules', 'lang');
		
		if (!($dataItem instanceof Data)) $dataItem = Data::Create($dataItem);
		$this->dataItem = $dataItem;

		list($this->viewField) = explode(',', $this->dataItem->listFields);
		$this->field = isset($_GET['field']) ? $_GET['field'] : 'oder';
		$this->cond = isset($_GET['cond']) ? $_GET['cond'] : null;
	}
	
	function Save()
	{
		GLOBAL $Db;

		$rows = $Db->Select($this->cond, null, null, $this->dataItem->table);
		$size = sizeof($rows);
		$new = explode(',', $_POST['id_set']);

		for ($i=1; $i<=$size; $i++)
		{
			$iidd = $new[$i-1];
			$Db->Update("id='$iidd'", array($this->field => $i), $this->dataItem->table);
		}
	}
	
	function Render()
	{
		GLOBAL $Parser, $Db;
		
		$rows = $Db->Select($this->cond, $this->field, "id,{$this->viewField}", $this->dataItem->table);
		$size = sizeof($rows);
		
		if (Utils::IsArray($rows))
		{
			foreach ($rows as $key=>$row)
			{
				$viewValue = $row[$this->viewField];
				$viewValue = (strlen($viewValue) > 80) ? substr($viewValue, 0, 80).'...' : $viewValue;
				$rows[$key]['name'] = $viewValue;
			}
		}

		$Parser->SetCaptionVariables(array ('size' => $size));

		return $Parser->MakeList($rows, 'cms/order');
	}
}

?>