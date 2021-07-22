<?php
/**
 * Lisk Type Void
 * @package lisk
 *
 */
class T_void extends T_hidden
{
	
	function __construct(array $info, Data $dataItem=null)
	{
		parent::__construct($info, $dataItem);
	}
	
	function Insert(&$values)
	{
		return false;
	}
	
	function Update(&$values)
	{
		return false;
	}
	
	function Delete(&$values)
	{
		return false;
	}
}

?>