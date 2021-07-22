<?php

/**
 * Class to handle action statistics calls
 * application will work even if no module installed
 *
 */
class StatActionHandler
{
	/**
	 * Check if module is installed
	 *
	 * @return boolean
	 */
	static function IsInstalled()
	{
		GLOBAL $Db;
		
		static $installed = null;
		
		if ($installed===null)
		{
			$row = $Db->Get("name='stat_action'", null, 'sys_modules');
			if (Utils::IsArray($row)) $installed = true;
			else $installed = false;
		}
		
		return $installed;
	}
	
	/**
	 * Do action statistics
	 *
	 * @param const ACTION_OBJECT $object
	 * @param const ACTION $action
	 * @param string $param
	 * @param integer $quantity
	 */
	static function Set($object, $action, $param=null, $quantity=1)
	{
		GLOBAL $App;
		
		if (!self::IsInstalled()) return;
		
		$App->LoadModule('installed/stat_action/stat_action.cfg.php');
		
		if (!defined($object) || !defined($action)) return;
		
		$object = constant($object);
		$action = constant($action);
		
		$App->Load('stat_action', 'mod');
		
		StatAction::Set($object, $action, $param, $quantity);
	}
	
	/**
	 * Do action statistics about site referers and search engines
	 *
	 */
	static function SetVisitor()
	{
	    GLOBAL $App;
	    if (!isset($_SERVER['HTTP_REFERER']) || !strlen($_SERVER['HTTP_REFERER'])) return;
	    
	    $selfPrefix = 'http://'.strtolower($_SERVER['HTTP_HOST']).$App->httpRoot;
	    $referer = strtolower($_SERVER['HTTP_REFERER']);
	    
	    if (substr($referer, 0, strlen($selfPrefix)) == $selfPrefix) return;
	    
	    $m = array();
	    
	    //google
	    if (preg_match('/google\.[\w]{2,3}\.?[\w]{0,3}/', $referer, $m))
	    {
	        self::Set('STAT_OBJECT_REFERER', 'STAT_OBJECT_REFERER_GOOGLE');
	        return;
	    }
	    
	    //yahoo
	    if (preg_match('/search\.yahoo\.com/', $referer, $m))
	    {
	        self::Set('STAT_OBJECT_REFERER', 'STAT_OBJECT_REFERER_YAHOO');
	        return;
	    }
	    
	    //msn
	    if (preg_match('/search\.msn\.[\w]{2,3}\.?[\w]{0,3}/', $referer, $m) || preg_match('/search\.live\.com/', $referer, $m))
	    {
	        self::Set('STAT_OBJECT_REFERER', 'STAT_OBJECT_REFERER_MSN');
	        return;
	    }
	    
	    self::Set('STAT_OBJECT_REFERER', 'STAT_OBJECT_REFERER_OTHER');
	}
}

?>