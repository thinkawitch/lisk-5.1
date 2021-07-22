<?php
/**
 * CMS Add
 * @package lisk
 *
 */

class CMSAdd extends CMSCore
{
	/**
	 * @var Data
	 */
	public $dataItem;
	
	function __construct($dataItem, $redefine='add')
	{
		parent::__construct();
		
		GLOBAL $App;
		$App->Load('cpmodules', 'lang');
		
		if (!($dataItem instanceof Data)) $dataItem = Data::Create($dataItem);
		$this->dataItem = $dataItem;
		
		$this->dataItem->ReSet($redefine);
	}
	
	function Insert()
	{
		$this->dataItem->Insert($_POST);
	}
	
	function Render()
	{
		GLOBAL $Parser;
		
		// looking for HIDDEN fields and it's values
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
		
		return $Parser->MakeDynamicForm($this->dataItem, 'cms/add');
	}
}

?>