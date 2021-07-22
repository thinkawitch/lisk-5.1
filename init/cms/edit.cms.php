<?php
/**
 * CMS Edit
 * @package lisk
 *
 */

class CMSEdit extends CMSCore
{
	/**
	 * data item
	 *
	 * @var Data
	 */
	public $dataItem;
	public $cond;

	function __construct($dataItem)
	{
		parent::__construct();

		GLOBAL $App;
		$App->Load('cpmodules', 'lang');

		if (!($dataItem instanceof Data)) $dataItem = Data::Create($dataItem);
		$this->dataItem = $dataItem;
	}

	function Update()
	{
		$this->dataItem->Update($this->cond, $_POST);
	}

	function Render()
	{
		GLOBAL $Parser;
		$this->dataItem->Get($this->cond);
		
		// looking for HIDDEN fields and its values
	    foreach ($this->dataItem->fields as $fieldName=>$fieldObj)
		{
		    if (isset($_GET['HIDDEN_'.$fieldName]))
	        {
	            $value = $_GET['HIDDEN_'.$fieldName];
	            
	            if ($fieldObj->type == LiskType::TYPE_DATE || $fieldObj->type == LiskType::TYPE_DATETIME)
	            {
	                $value = str_replace('"', '', $value);
	                $value = str_replace('\'', '', $value);
	            }
	            
	            $this->dataItem->value[$fieldName] = $value;
	        }
		}
		
		return $Parser->MakeDynamicForm($this->dataItem, 'cms/edit');
	}
}

?>