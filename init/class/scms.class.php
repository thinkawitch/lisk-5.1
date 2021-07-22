<?php
/**
 * Scms
 * @package lisk
 *
 */

// Page Structure Types
define('SCMS_PAGESET',	0);
define('SCMS_LINK',		1);
define('SCMS_CONTENT',	2);
define('SCMS_CUSTOM',	3);
define('SCMS_MODULE',	4);

// SCMS structure names
$LIST_SCMS_TYPE = array(
	SCMS_CONTENT	=> 'Content Page',
	SCMS_PAGESET	=> 'Set of pages',
	SCMS_LINK		=> 'Link',
	SCMS_CUSTOM		=> 'Custom Page',
	SCMS_MODULE		=> 'Site Module',
);

$LIST_SCMS_TYPE_ADMINISTRATOR = array(
	SCMS_CONTENT	=> 'Content Page',
	SCMS_PAGESET	=> 'Page set',
	SCMS_LINK		=> 'Link'
);

// Access levels
$LIST_SCMS_ACCESS_LEVEL = array(
	1 => 'Public & Members',
	2 => 'Public Only',
	3 => 'Members Only',
	0 => 'Locked',
);

$LIST_SCMS_PAGESET_OVERVIEW = array(
	0	=> 'No',
	1	=> 'Yes'
);

// Main SCMS DataItem
$DATA_DI_SCMS = array(
	'table'  => 'sys_ss',
	'order'  => 'oder',

	'fields' => array(
		'id' 	=> LiskType::TYPE_HIDDEN,
		'oder'	=> LiskType::TYPE_HIDDEN,
		'parent_id' => array(
			'type'  => LiskType::TYPE_TREE,
			'object'=> 'Obj_DIScms',
			'label' => 'Parent',
			//'category_cond'	=> "[page_type]=".SCMS_MENU,  //TODO, zachem eto bilo nuzhno ??
		),
		'parents' => LiskType::TYPE_HIDDEN,

		'page_type' => array(
			'type'  => LiskType::TYPE_LIST,
			'object'=> 'def_scms_type',
			'label' => 'Page Type',
		
		),

		'name' => array(
			'type'  => LiskType::TYPE_INPUT,
			'label' => 'Site section name',
			'check' => 'pre:empty',
		),

		'title'	=> array(
			'type'	=> LiskType::TYPE_INPUT,
		),

		'global_tpl' => LiskType::TYPE_HIDDEN,
		'section_tpl' => LiskType::TYPE_HIDDEN,
		'subsection_tpl' => LiskType::TYPE_HIDDEN,
		'page_tpl'   => LiskType::TYPE_HIDDEN,

		'url'		 => LiskType::TYPE_INPUT,

		'content'	 => LiskType::TYPE_HTML,

		'cp_handler' => array(
			'type'	 => LiskType::TYPE_INPUT,
		),

		'site_handler' => array(
			'type'	 => LiskType::TYPE_INPUT,
		),

		'is_locked' => array(
			'type'  => LiskType::TYPE_FLAG,
			'label' => 'Locked',
		),

		'access_level' => array(
			'type'   => LiskType::TYPE_LIST,
			'object' => 'def_scms_access_level',
		),

		'pageset_overview'	=> array(
			'type'	 => LiskType::TYPE_LIST,
			'object' => 'def_scms_pageset_overview'
		),

		'instance_id' => array(
			'type' => LiskType::TYPE_HIDDEN,
		),
		'hide_from_menu' => array(
			'type' => LiskType::TYPE_FLAG,
		),

		'link_href' => array(
			'type' => LiskType::TYPE_INPUT,
		),
		'link_open_type' => array(
			'type' => LiskType::TYPE_FLAG,
		),
		'link_redirect' => array(
			'type' => LiskType::TYPE_FLAG,
		),
		
		'auto_url_generation' => array(
			'type' => LiskType::TYPE_FLAG,
		),
	),

	'label' => 'Page',

	'redefine_add_administrator' => array(
		'page_type' => array(
			'type'  => LiskType::TYPE_LIST,
			'object'=> 'def_scms_type_administrator',
			'label' => 'Page Type',
		),
	)
);



// Main SCMS DataItem
class DIScms extends Data
{
	private $oldUrl;
	public $forceUrlUpdate = false;

	function __construct($initFields=true)
	{
		parent::__construct('di_scms', $initFields, 'Obj_DIScms');
	}

	public function TgerBeforeInsert(&$values)
	{
		parent::TgerBeforeInsert($values);
		if (!strlen($values['url']))
		{
			$parentUrl = $this->GetValue('id='.$values['parent_id'], 'url');
			$values['url'] = $parentUrl.Format::ToUrl($values['name']).'/';
		}
		return true;
	}

	public function TgerBeforeUpdate($cond, &$update)
	{
	    $parentId = isset($update['parent_id']) ? $update['parent_id'] : null;
	    $url = isset($update['url']) ? $update['url'] : null;

		// do not update if parent_id is undefinded
		if (!strlen($parentId))
		{
			$this->oldUrl = '';
			return true;
		}

		$oldValues = $this->GetValue($cond);
		$this->oldUrl = $oldValues['url'];
		$parentUrl = $this->GetValue('id='.$parentId, 'url');
		// update if url is empty /*or the name has been changed*/
		if (!strlen($url) /*|| $updateValues['name']!=$oldValues['name']*/ || $this->forceUrlUpdate)
		{
			$update['url'] = $parentUrl.Format::ToUrl($update['name']).'/';
		}
		return true;
	}

	public function TgerAfterUpdate($cond, $updateValues)
	{
		GLOBAL $Db, $App;
		$newValues = $this->GetValue($cond);
		if ($newValues['page_type'] == SCMS_PAGESET && $this->oldUrl != '')
		{
			$x = strlen($this->oldUrl) + 1;
			$sql = "UPDATE {$this->table}
			SET
				url = CONCAT('{$newValues['url']}', SUBSTRING(url, $x))
			WHERE
				parents LIKE '%<{$newValues['id']}>%'
			";
			$Db->Query($sql);
		}

		//update module's base_url
		$rows = $Db->Select($cond, null, null, $this->table);
		if (!Utils::IsArray($rows)) return true;

		foreach ($rows as $row)
		{
			if ($row['page_type'] == SCMS_MODULE && $row['instance_id'])
			{
                $newUrl = $row['url'];
				    
			    $module = $App->GetModuleInstance($row['instance_id']);
			    $module->UpdateBaseUrl($newUrl);
			}
		}

		return true;
	}

	public function TgerAfterDelete($cond, $values)
	{
	    GLOBAL $App,$Db;
		//uninstall modules
		if (!Utils::IsArray($values)) return true;
		
	    foreach ($values as $row)
		{
			if ($row['page_type'] == SCMS_MODULE)
			{
    			$module = $App->GetModuleInstance($row['instance_id']);
    			$module->Uninstall();
			}

		    if ($row['page_type'] == SCMS_CUSTOM)
        	{
        		//remove from cp menu, if any
        		$cpMenuUrl = $this->value['cp_handler'];
        		if (strlen($cpMenuUrl))
        		{
        			$Db->Delete('url='.Database::Escape($cpMenuUrl), 'sys_cp_menu');
        		}
        	}
		}
	}
}

class Scms
{
	public $values;
	public $cl;
	
	/**
	 * Contains additional navigation array that will be used for SCMS page navigation parsing
	 *
	 * @var hash array
	 */
	public $navigation = array();

	function __construct()
	{

	}

	public function AddNavigation($navigation)
	{
		if (!Utils::IsArray($navigation)) return;

		foreach ($navigation as $row)
		{
			$this->navigation[] = array(
				'name'	=> $row['name'],
				'url'	=> $row['url']
			);
		}
	}

	public function GetParentName()
	{
		GLOBAL $Db;

		return $Db->Get('id='.$this->values['parent_id'], 'name', 'sys_ss');
	}

	public function RenderNavigation()
	{
		GLOBAL $Parser,$Db;

		$cond = Utils::TreeToIn($this->values['parents']."<{$this->cl}>");
		$rows = $Db->Select("id IN $cond", 'id', 'id,name,url', 'sys_ss');

		// Merge with Additional navigation
		$rows = Utils::MergeArrays($rows,$this->navigation);

		// Kill duplicates, happens with merge with add. nav.
		$previousName = '';
		$result = array();
		foreach ($rows as $row)
		{
			if (strtolower($row['name']) != strtolower($previousName))
			{
				$result[]=array(
					'name'	=> $row['name'],
					'url'	=> $row['url']
				);
			}
			$previousName = $row['name'];
		}

		return $Parser->MakeNavigation($result, 'scms_blocks', 'navigation');
	}

	public function RenderMenu($pageId, $blockName='menu')
	{
		GLOBAL $Db,$Parser;

		if (!strlen($pageId)) $pageId = $GLOBALS['Page']->cl;

		if (!is_numeric($pageId)) $pageId = $Db->Get("url='$pageId'", 'id', 'sys_ss');

		$values = $Db->Select("parent_id=$pageId AND hide_from_menu=0 AND access_level!=0", 'oder', 'id,name,url,page_type,link_open_type', 'sys_ss');
        if (Utils::IsArray($values))
        {
            foreach ($values as $k=>$v)
            {
                if ($v['page_type']==SCMS_LINK && $v['link_open_type']==1)
                {
                    $values[$k]['target'] = ' target="_blank"';
                }
            }
        }

		return $Parser->MakeList($values,'scms_blocks', $blockName);
	}

	public function RenderFullMenu($tplName, $startLevel=1)
	{
		GLOBAL $Parser,$Page,$Db;

		$pageId = $GLOBALS['Page']->cl;
		if (strlen($pageId)) $parents = $Page->curPage['parents']."<$pageId>";
		else $parents = '<1>';

		// Select all related categories
		$selectCond = Utils::TreeToIn($parents);
		$categories = $Db->Select("parent_id IN $selectCond AND access_level!=0 AND hide_from_menu=0", 'oder', 'id,parent_id,parents,name,url,page_type,link_open_type', 'sys_ss');
	    if (Utils::IsArray($categories))
        {
            foreach ($categories as $k=>$v)
            {
                if ($v['page_type'] == SCMS_LINK && $v['link_open_type'] == 1)
                {
                    $categories[$k]['target'] = ' target="_blank"';
                }
                else $categories[$k]['target'] = '';
            }
        }

		// get parents array
		$parentsArr = Utils::TreeToArray($parents);

		//render header
		$GLOBALS['full_menu_result'] = $Parser->GetHtml($tplName, 'header');

		//render menu
		if (Utils::IsArray($categories)) $this->FullMenuBuilder($categories, $parentsArr, $pageId, $startLevel, $tplName);

		//render footer
		$GLOBALS['full_menu_result'] .= $Parser->GetHtml($tplName, 'footer');

		// remove global var
		$rez = $GLOBALS['full_menu_result'];
		unset($GLOBALS['full_menu_result']);

		return $rez;
	}

	private function FullMenuBuilder($categories, $parentsArr, $cl, $level, $tplName)
	{
		GLOBAL $Parser;

		foreach ($categories as $row)
		{
			if (Utils::TreeLevel($row['parents']) == $level)
			{
				$blockName = ($row['id']==$parentsArr[$level] || $row['id']==$cl) ? 'level'.$level.'_active' : 'level'.$level;
				$GLOBALS['full_menu_result'] .= $Parser->MakeView(array(
					'name'	=> $row['name'],
					'url'	=> $row['url'],
				    'target' => $row['target'],
				), $tplName, $blockName);

				if ($row['id'] == $parentsArr[$level])
				{
					$this->FullMenuBuilder($categories, $parentsArr, $cl, $level+1, $tplName);
				}
			}
		}
	}

	public function CheckAccessLevel()
	{
		GLOBAL $Auth, $App;

		// locked
		if ($this->values['access_level'] == 0)
		{
			$App->RaiseError('Access denied.');
		}

		// members only
		if ($this->values['access_level'] == 3)
		{
			if (!$Auth->isAuthorized)
			{
				$Auth->SetUrlRequested();
				Navigation::Jump($App->httpRoot.$Auth->loginPageUrl);
			}
		}
	}
}

if (INIT_NAME != 'cp')
{
	$Scms = new Scms();
}

?>