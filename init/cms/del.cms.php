<?php
/**
 * CMS Del
 * @package lisk
 *
 */

class CMSDel extends CMSCore
{
	/**
	 * data item
	 *
	 * @var Data
	 */
	public $dataItem;
	
	function __construct($dataItem, $redefine='del')
	{
		parent::__construct();
		
		GLOBAL $App;
		$App->Load('cpmodules', 'lang');
		
		if (!($dataItem instanceof Data)) $dataItem = Data::Create($dataItem);
		$this->dataItem = $dataItem;
		$this->dataItem->ReSet($redefine);
	}
	
	function Delete()
	{
		$this->dataItem->Delete('id='.Database::Escape($_GET['id']));
	}
}

?>