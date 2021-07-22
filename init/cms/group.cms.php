<?php
/**
 * CP Group
 * @package lisk
 *
 */


// reserved groups
define('LISK_GROUP_ADMINISTRATORS', 1);
define('LISK_GROUP_DEVELOPERS', 2);

class CPGroup
{
	public $menu;
	public $rights = array();
	public $rowsHtml = '';
	public $values = array();
	
	public $rezMenu = array();
	
	function __construct()
	{
		$CPMenu = Data::Create('sys_cp_menu');
		$CPMenu->Select('id!=1');
		$values = $CPMenu->values;
		$this->BuildCPMenu(1, $values, $GLOBALS['cpMenuCfg']);
		
		$this->menu = $GLOBALS['cpMenuCfg'];
	}
	
	function BuildCPMenu($id, $values, &$arrayToInsert)
	{
		foreach ($values as $row)
		{
			if ($row['parent_id']==$id)
			{
				$arrayToInsert[$row['name']] = array(
					'link'	=> $row['url'],
					'hint'	=> $row['hint'],
				);
				$this->BuildCPMenu($row['id'], $values, $arrayToInsert[$row['name']]['submenu']);
			}
		}
	}
	
	function RightsForm($groupId)
	{
		GLOBAL $Parser;
		
		$this->menu = $this->NormalizeMenu($this->menu, 'root');
		
		$groups = Data::Create('usergroup_cp');
		$groups->Get("id='$groupId'");
		$rights = @unserialize($groups->value['rights']);
		
		if ($rights !== false) $this->rights = $rights;
		else $this->rights = array();
		
		if ($groupId == LISK_GROUP_ADMINISTRATORS || $groupId == LISK_GROUP_DEVELOPERS)
		{
			$disabled = ' disabled="true" ';
		}
		else
		{
			$disabled = '';
		}
		
		foreach ($this->menu as $v)
		{
			if (isset($this->rights[$v['id']]) && $this->rights[$v['id']]) $checked = 'checked';
			else $checked = '';
			
			$this->rowsHtml .= $Parser->MakeView(array(
				'name'		=> $v['name'],
				'shift' 	=> str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $v['level']),
				'level'		=> $v['level'],
				'checked'	=> $checked,
				'id'		=> $v['id'],
				'disabled'	=> $disabled,
				'parent'	=> $v['parent']
			),'cms/edit_group','section');
		}
		
		
		return $Parser->MakeView(array(
			'rows'		=> $this->rowsHtml,
			'group_id'	=> $groupId
		), 'cms/edit_group', 'view');
	}
	
	function NormalizeMenu($arr, $parent, $level=0)
	{
		static $rez;
		
		if (!Utils::IsArray($arr)) return array();
			
		foreach ($arr as $key=>$value)
		{
			if (!Utils::IsArray($value)) {
				$value=array(
					'link'	=> $value
				);
			}
			$id 	= (!isset($value['id']) || !strlen($value['id'])) ? str_replace(array(' ','\'','"'), array('_','',''),$key) : $value['id'];
			$name	= $key;
			
			$subMenu = $value['submenu'];
			
			$rez[] = array(
				'name'	=> $name,
				'id'	=> $id,
				'parent'=> $parent,
				'level'	=> $level
			);

			if (Utils::IsArray($subMenu))
			{
				$this->NormalizeMenu($subMenu, $id,$level+1);
			}
		}
	
		return $rez;
	}
	
	function SaveRights($groupId, $rights)
	{
		if ($groupId == LISK_GROUP_ADMINISTRATORS || $groupId == LISK_GROUP_DEVELOPERS) return;
		
		GLOBAL $Db;
		$group = Data::Create('usergroup_cp', false);
		$Db->Update('id='.$groupId, array('rights' => serialize($rights)), $group->table);
	}
	
	function GetRights($groupId)
	{
		$rights = Data::Create('usergroup_cp');
		$rights->Get('id='.$groupId);
		$this->values = @unserialize($rights->value['rights']);
	}
}
?>