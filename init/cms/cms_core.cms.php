<?php
/**
 * CMS Core
 * @package lisk
 *
 */
class CMSCore
{
	protected $loadRequired = false;
	protected $loadFile;
	protected $loadType;
	
	function __construct()
	{
		$this->LoadModules();
	}
	
	private function LoadModules()
	{
        GLOBAL $App;
        
        if (isset($_GET['load_name']) && isset($_GET['load_type']))
        {
			$App->Load($_GET['load_name'], $_GET['load_type']);
			
			$this->loadRequired = true;
			$this->loadFile = $_GET['load_name'];
			$this->loadType = $_GET['load_type'];
        }
	}
	
	public function Load($name, $type)
	{
		GLOBAL $App;
		$App->Load($name, $type);
		
		$this->loadRequired = true;
		$this->loadFile = $name;
		$this->loadType = $type;
	}
	
	public function GetRequiredUrlVars()
	{
	    if ($this->loadRequired) return "&load_name={$this->loadFile}&load_type={$this->loadType}";
	    else return '';
	}
}

?>