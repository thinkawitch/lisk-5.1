<?php
/**
 * CMS Content Tree
 * @package lisk
 *
 */

class CMSContentTree extends CMSCore
{

	private $cl;			// current level (node ID)
	private $clMode;		// 0 - nothing 1 - something

	private $nestingLevel;

	private $pointAddButtons = array();
	private $nodeAddButtons	 = array();

	private $back;

	public $developerMode = false;

	private $treeJSStructure = array();

	/**
	 * @var DIScms
	 */
	public $di;

	function __construct()
	{
	    parent::__construct();
		GLOBAL $App,$Auth;
		$App->Load('cpmodules', 'lang');
		$this->developerMode = ($Auth->user['level'] == LISK_GROUP_DEVELOPERS) ? true : false;

		$this->InitializeTree();
	}

	private function InitializeTree()
	{
		GLOBAL $Db;

		$this->di = new DIScms();

		$this->cl = (!isset($_GET['cl']) || $_GET['cl'] < 1) ? 1 : intval($_GET['cl']);

		$isNode = $Db->Get('parent_id='.$this->cl, 'id', $this->di->table);
		$isNode = !empty($isNode);

		if ($isNode) $this->clMode = 1;
		else $this->clMode = 0;

		// arrange current nesting level
		$nodeParents = $Db->Get('id='.$this->cl, 'parents', $this->di->table);
		$this->nestingLevel = substr_count($nodeParents, '>');
	}

	function AdditionalNavigation()
	{
		GLOBAL $Parser,$Page;

		$rez = $Parser->MakeView(array(
			'jump_field'	=> $this->MakeJumpField(),
			'navigation'	=> $this->RenderNavigation()
		), 'cms/tree/tree', 'additional_navigation');

		$Page->customLine = $rez;
	}

	function RenderNavigation()
	{
		GLOBAL $Parser;
		$Parser->SetAddVariables(array(
			'back'	=> $this->back
		));

		// tree to nav
		GLOBAL $Db;
		$id = ($this->cl > 0) ? $this->cl : 1;
		$parents = $Db->Get('id='.$id, 'parents', $this->di->table);
		$parents = Utils::TreeToIn($parents."<$id>");
		$names = $Db->Select("id IN $parents", 'id', 'id,name', $this->di->table);

		return $Parser->MakeNavigation($names, 'cms/tree/tree', 'nav');
	}

	function MakeJumpField($mode='', $disabled=false)
	{
		GLOBAL $Db,$App;

		$rows = $Db->Select('page_type='.SCMS_PAGESET, 'oder', null, $this->di->table);
		$arr = $this->NodeSort(0, $rows, $this->cl);

		$App->Load('list', 'type');
		$list	= new T_list(array(
		    'name' => 'quickjump',
			'object'	=> 'arr',
		));

		switch ($mode)
		{
			case 'search':
				$list->AddFormParam('style', 'font-size: 12px;');
				if ($disabled) $list->AddFormParam('disabled', 'disabled');
				break;
				
			default:
				$list->autoJump = "?back={$this->back}&cl=";
				$list->AddFormParam('style', 'font-size: 10px;');
				if ($disabled) $list->AddFormParam('disabled', 'disabled');
				break;
		}

		$list->name = 'cat_dropdown';
		$list->values = $arr;
		$list->value = $this->cl;
		$list->AddFormParam('class', 'hform');
		
		return $list->RenderFormTplView();
	}

	private function NodeSort($parent, $rows, $cl)
	{
		STATIC $rez = null;
		if (Utils::IsArray($rows))
		{
			foreach($rows as $row)
			{
				if ($row['parent_id'] == $parent)
				{
					$rez[$row['id']] = str_repeat('&nbsp;', substr_count($row['parents'], '>') * 2).$row['name'];
					$this->NodeSort($row['id'], $rows, $cl);
				}
			}
		}
		return $rez;
	}

	function MakeLinkButtons()
	{
		GLOBAL $Page;

		// Add link that goes with submenu
		$Page->AddLink(LANG_CP_SCMS_ADDPAGE, '#', 'img/ico/links/add.gif', LANG_CP_SCMS_ADDNPAGE, 'id="idLinkSubmenu"');

		// Order Links
		if ($this->clMode == 1)
		{
			$orderUrl = "order.php?type={$this->di->name}&back={$Page->setBack}&cond=parent_id={$this->cl}";
			$orderUrl .= $this->GetRequiredUrlVars();
			$Page->AddLink(LANG_CP_SCMS_ORDER.$this->di->label, $orderUrl, 'img/ico/links/order.gif', LANG_CP_SCMS_CORDER);
		}
	}

	function Render($back)
	{
		GLOBAL $Parser,$App;

		$this->back = $back;

		// Add SCMS Menu stuff
		if ($this->developerMode)
		{
			$resultHtml = $Parser->MakeView(array(
				'parent_id'	=> $this->cl
			), 'ss/add_menu', 'developer');
		}
		else
		{
			$resultHtml = $Parser->MakeView(array(
				'parent_id'	=> $this->cl
			), 'ss/add_menu', 'administrator');
		}

		switch ($this->clMode)
		{
			case 0:
				return $resultHtml.$Parser->GetHtml('cms/tree/tree', 'empty');
				break;
				
			case 1:
				// initialize paging ia tak dumau paging tut ne nuzen
				//$Paging->SwitchOn('cp');

				$values = $this->di->SelectValues('parent_id='.$this->cl);
               
                if (Utils::IsArray($values))
                {
                    
    				foreach($values as $k=>$row)
    				{
    				    $values[$k]['row_id'] = 'idRow_'.$row['id'];

    					// Manage Content link
    					$manageContent = '<a href="?action=manage_content&id=[id]&back=[back]" liskHint="[hint]"><img src="img/cms/scms/i_content.gif" width="16" height="12" border="0" align="absmiddle">&nbsp;[message]</a>';
    					$manageContent = Format::String($manageContent, array(
    						'id'		=> $row['id'],
    						'back'		=> $this->back,
    						'hint'		=> LANG_CP_SCMS_EPCONT,
    						'message'	=> LANG_CP_SCMS_ECONT
    					));

    					$deleteButton = '<a href="#delete" class="delete" rel="?action=delete&id=[id]&back=[back]" onclick="return false" liskHint="[hint]"><img src="img/cms/list/delete.gif" width="8" height="14" border="0" align="absmiddle">&nbsp;[message]</a>';
    					$deleteButton = Format::String($deleteButton, array(
    						'id'		=> $row['id'],
    						'back'		=> $this->back,
    						'hint'		=> LANG_CP_SCMS_DPAGE,
    						'message'	=> LANG_CP_SCMS_DELETE
    					));

    					$checkBox = "<input type=\"checkbox\" name=\"ids[]\" value=\"{$row['id']}\" />";
    					$isLocked = '<img src="img/cms/scms/i_locked.gif" width="9" height="11" border="0" align="absmiddle">&nbsp;Locked';

    					switch($row['page_type'])
    					{
    						case SCMS_PAGESET:
    							// Add link to name if page set
    							$values[$k]['name']	= "<a href=\"?cl={$row['id']}&back={$this->back}\">{$row['name']}</a>";

    							// add manage content if
    							if ($row['pageset_overview'] == 1) $values[$k]['manage_content'] = $manageContent;

    							// Set Add link as enter node
    							$enterNode = '<a href="?cl=[id]&back=[back]" liskHint="[hint]"><img src="img/cms/scms/i_enter.gif" width="11" height="9" border="0" align="absmiddle">&nbsp;[message]</a>';
    							$enterNode = Format::String($enterNode, array(
    								'id'		=> $row['id'],
    								'back'		=> $this->back,
    								'hint'		=> LANG_CP_SCMS_ESECT,
    								'message'	=> LANG_CP_SCMS_ENTER
    							));

    							$values[$k]['additional_link'] = $enterNode;

    							// Add delete &  if not locked
    							if ($row['is_locked'] == 0) $values[$k]['delete_button'] = $deleteButton;
    							else $values[$k]['delete_button'] = $isLocked;

    							break;

    						case SCMS_CONTENT:
    							// manage content link
    							$values[$k]['manage_content'] = $manageContent;

    							// Add delete & checkbox if not locked
    							if ($row['is_locked']==0)
    							{
    								$values[$k]['delete_button'] = $deleteButton;
    								$values[$k]['checkbox'] = $checkBox;
    							}
    							else
    							{
    								$values[$k]['delete_button'] = $isLocked;
    							}
    							break;
    							
    						case SCMS_LINK:
    							// Add follow Link
    							$row['link_href'] = str_replace('[/]', $App->httpRoot, $row['link_href']);
    							$followLink = "<a href=\"{$row['link_href']}\" target=\"_blank\" liskHint=\"".LANG_CP_SCMS_ONWIN."\"><img src=\"img/cms/scms/i_follow.gif\" width=\"13\" height=\"7\" border=\"0\" align=\"absmiddle\">&nbsp;".LANG_CP_SCMS_FLINK."</a>";

    							$values[$k]['additional_link'] = $followLink;
    							// Add delete & checkbox if not locked
    							if ($row['is_locked']==0)
    							{
    								$values[$k]['delete_button'] = $deleteButton;
    								$values[$k]['checkbox'] = $checkBox;
    							}
    							else
    							{
    								$values[$k]['delete_button'] = $isLocked;
    							}
    							break;
    							
    						case SCMS_CUSTOM:
    							$ml = $row['cp_handler'];
    							if (strpos($ml, '?')===false) $ml .= '?back='.$this->back;
    							else $ml .= '&back='.$this->back;
    							$manageLink = "<a href=\"{$ml}\" liskHint=\"".LANG_CP_SCMS_MCPAGE."\"><img src=\"img/cms/scms/manage.gif\" width=15 height=17 border=0 align=\"absmiddle\">&nbsp;".LANG_CP_SCMS_MANAGE."</a>";

    							$values[$k]['additional_link'] = $manageLink;

    							$values[$k]['manage_content'] = $manageContent;

    							// Add delete & checkbox if not locked
    							if ($row['is_locked']==0 && $this->developerMode)
    							{
    								$values[$k]['delete_button'] = $deleteButton;
    								$values[$k]['checkbox'] = $checkBox;
    							}
    							else
    							{
    								$values[$k]['delete_button'] = $isLocked;
    							}
    							break;

    						case SCMS_MODULE:
    							// manage links
    							$manageLink = "<a href=\"{$row['cp_handler']}&back={$this->back}\" liskHint=\"".LANG_CP_SCMS_MMSETS."\"><img src=\"img/cms/scms/manage.gif\" width=15 height=17 border=0 align=\"absmiddle\">&nbsp;".LANG_CP_SCMS_MANAGE."</a>";

    							$values[$k]['additional_link'] = $manageLink;
    							$values[$k]['manage_content'] = $manageContent;


    							// Add delete & checkbox if not locked
    							if ($row['is_locked']==0 && $this->developerMode)
    							{
    								$values[$k]['delete_button'] = $deleteButton;
    								$values[$k]['checkbox'] = $checkBox;
    							}
    							else
    							{
    								$values[$k]['delete_button'] = $isLocked;
    							}
    							break;
    					}
				    }
				}
                
				$Parser->SetAddVariables(array('back'=>$this->back));
				$Parser->listDecoration1 = 'ListTD1';
				$Parser->listDecoration2 = 'ListTD2';

				$Parser->SetCaptionVariables(array(
				    'dataitem_name' => $this->di->name,
		    		'paging_pcp' => isset($_GET['pcp']) ? intval($_GET['pcp']) : 0,
				));

				$this->di->values = $values;
				$resultHtml .= $Parser->MakeList($this->di, 'cms/scms/list', 'list');

				return $resultHtml;
				break;
				
			case 2:
				$App->RaiseError('Scms :: case 2 is impossible');
				break;
		}
	}

	function RenderAddContent($parentId)
	{
		GLOBAL $Parser, $LIST_SCMS_TYPE;
		$this->cl = $parentId;

		$tpl = 'ss/add/content_developer';
		if (!$this->developerMode)
		{
			$this->di->ReSet('add_administrator');
			$tpl = 'ss/add/content';
		}

		$parentInfo = $this->di->GetValue('id='.$parentId, 'url,name');

		$Parser->SetAddVariables(array(
			'section_tpl'       => $this->RenderTplListField('section_tpl', null, 'ss/section/'),
			'subsection_tpl'    => $this->RenderTplListField('subsection_tpl', null, 'ss/subsection/'),
			'page_tpl'          => $this->RenderTplListField('page_tpl', null, 'ss/page/'),
			'global_tpl'        => $this->RenderGlobalTplListField(null),
			'parent_name'		=> $parentInfo['name'],
			'page_type_name'	=> $LIST_SCMS_TYPE[SCMS_CONTENT],
			'parent_url'		=> $parentInfo['url'],
			'parent_id'			=> $parentId
		));
		$this->di->value['page_type'] = SCMS_CONTENT;
		$this->di->value['auto_url_generation'] = 1;
		return $Parser->MakeForm($this->di, $tpl, 'form');
	}

	function RenderAddPageset($parentId)
	{
		GLOBAL $Parser, $LIST_SCMS_TYPE;
		$this->cl = $parentId;

		$tpl = 'ss/add/pageset_developer';
		if (!$this->developerMode)
		{
			$this->di->ReSet('add_administrator');
			$tpl = 'ss/add/pageset';
		}

		$parentInfo = $this->di->GetValue('id='.$parentId, 'url,name');
        
		$Parser->SetAddVariables(array(
			'section_tpl'       => $this->RenderTplListField('section_tpl', null, 'ss/section/'),
			'subsection_tpl'    => $this->RenderTplListField('subsection_tpl', null, 'ss/subsection/'),
			'page_tpl'          => $this->RenderTplListField('page_tpl', null, 'ss/page/'),
			'global_tpl'        => $this->RenderGlobalTplListField(null),
			'parent_name'		=> $parentInfo['name'],
			'page_type_name'	=> $LIST_SCMS_TYPE[SCMS_PAGESET],
			'parent_url'		=> $parentInfo['url'],
			'parent_id'			=> $parentId
		));
		$this->di->value['page_type'] = SCMS_PAGESET;
		$this->di->value['auto_url_generation'] = 1;
		return $Parser->MakeForm($this->di, $tpl, 'form');
	}

	function RenderAddLink($parentId)
	{
		GLOBAL $Parser, $LIST_SCMS_TYPE;
		$this->cl = $parentId;

		$tpl = 'ss/add/link_developer';
		if (!$this->developerMode)
		{
			$this->di->ReSet('add_administrator');
			$tpl = 'ss/add/link';
		}

		$parentInfo = $this->di->GetValue('id='.$parentId, 'url,name');

		$Parser->SetAddVariables(array(
			'parent_name'		=> $parentInfo['name'],
			'page_type_name'	=> $LIST_SCMS_TYPE[SCMS_LINK],
			'parent_url'		=> $parentInfo['url'],
			'parent_id'			=> $parentId
		));
		$this->di->value['page_type'] = SCMS_LINK;
		$this->di->value['link_href'] = 'http://';
		return $Parser->MakeForm($this->di, $tpl, 'form');
	}

	function RenderAddCustom($parentId)
	{
	    if (!$this->developerMode)
	    {
	        return 'custom page for developers only!';
	    }
	    
		GLOBAL $Parser, $LIST_SCMS_TYPE;
		$this->cl = $parentId;

		$tpl = 'ss/add/custom_developer';
		$parentInfo = $this->di->GetValue('id='.$parentId, 'url,name');

		//enable JS auto url generation
		$this->di->fields['site_handler']->check = 'pre:empty';
		$this->di->fields['site_handler']->InitCheck();
		$this->di->fields['cp_handler']->check = 'pre:empty';
		$this->di->fields['cp_handler']->InitCheck();
		$this->di->InitCheckParams();

		$Parser->SetAddVariables(array(
			'section_tpl'       => $this->RenderTplListField('section_tpl', null, 'ss/section/'),
			'subsection_tpl'    => $this->RenderTplListField('subsection_tpl', null, 'ss/subsection/'),
			'page_tpl'          => $this->RenderTplListField('page_tpl', null, 'ss/page/'),
			'global_tpl'        => $this->RenderGlobalTplListField(null),
			'parent_name'		=> $parentInfo['name'],
			'page_type_name'	=> $LIST_SCMS_TYPE[SCMS_CUSTOM],
			'parent_url'		=> $parentInfo['url'],
			'parent_id'			=> $parentId
		));
		$this->di->value['page_type'] = SCMS_CUSTOM;
		$this->di->value['auto_url_generation'] = 1;
		return $Parser->MakeForm($this->di, $tpl, 'form');
	}

	function RenderAddModule($parentId)
	{
	    if (!$this->developerMode)
	    {
	        return 'module page for developers only!';
	    }
	    
		GLOBAL $Parser;
		$this->cl = $parentId;

		$tpl = 'ss/add/module_developer';
		$parentInfo = $this->di->GetValue('id='.$parentId, 'url,name');

		$Parser->SetAddVariables(array(
			'section_tpl'       => $this->RenderTplListField('section_tpl', null, 'ss/section/'),
			'subsection_tpl'    => $this->RenderTplListField('subsection_tpl', null, 'ss/subsection/'),
			'page_tpl'          => $this->RenderTplListField('page_tpl', null, 'ss/page/'),
			'global_tpl'        => $this->RenderGlobalTplListField(null),
			'parent_name'		=> $parentInfo['name'],
			'module'			=> $this->RenderModuleListField(),
			'parent_url'		=> $parentInfo['url'],
			'parent_id'			=> $parentId
		));
		$this->di->value['page_type'] = SCMS_MODULE;
		$this->di->value['auto_url_generation'] = 1;
		return $Parser->MakeForm($this->di, $tpl, 'form');
	}

	function AddModuleSubmit($values)
	{
		GLOBAL $App,$Db;

		// module system name
		$moduleName = $values['module'];

		$liskModules = $this->GetAvailableModules();

		//Check multiinstance allowed
		if (!$liskModules[$moduleName]['multiinstance'])
		{
			$isInstalled = $Db->Select("name='{$moduleName}'", null, 'id', 'sys_modules');
			if ($isInstalled!=false)
			{
				$App->SetError("Module {$moduleName} is already installed. You can not install it twice.");
				return false;
			}
		}

		$_POST['page_type']	= SCMS_MODULE;
		$ssId = $this->di->Insert($_POST);

		$path = $this->di->GetValue("id=$ssId", 'url');


		// load module
		$App->Load($moduleName);

		// create object instance
		$module = new $liskModules[$moduleName]['object_name']();

		//which installation step
		$step = intval(@$_GET['step']);
		if ($step < 1) $step = 1;

		//install/configurate module
		$instanceId = $module->Install(array(
			'path'	=> $path,
			'step'	=> $step,
			'page_name' => $values['name'],
		));

		// update Site Structure
		$this->di->Update("id=$ssId", array(
			'instance_id'	=> $instanceId,
			'cp_handler'	=> 'module_'.$moduleName.'.php?iid='.$instanceId,
		));


		if (@$values['add_to_cp_menu'])
		{
			// ADD item to CP Menu
			$Db->Insert(array(
				'parent_id'		=> 1,
				'parents'		=> '<1>',
				'is_category'	=> 0,
				'name'			=> $values['cp_menu_title'],
				'url'			=> 'module_'.$moduleName.'.php?iid='.$instanceId,
				'hint'			=> $values['cp_menu_title']
			), 'sys_cp_menu');
		}

		return true;
	}

	function AddCustomSubmit($values)
	{
		GLOBAL $Db;
		if (@$values['add_to_cp_menu'])
		{
			// ADD item to CP Menu
			$Db->Insert(array(
				'parent_id'		=> 1,
				'parents'		=> '<1>',
				'is_category'	=> 0,
				'name'			=> $values['cp_menu_title'],
				'url'			=> $values['cp_handler'],
				'hint'			=> $values['cp_menu_title']
			),'sys_cp_menu');
		}
		$this->di->Insert($values);
	}

	function RenderEdit($id)
	{
		$this->di->Get('id='.$id);
		$value = $this->di->value;

		switch ($value['page_type'])
		{
			case SCMS_CONTENT:
				return $this->RenderEditContent();
				break;
				
			case SCMS_PAGESET:
				return $this->RenderEditPageset();
				break;
				
			case SCMS_LINK:
				return $this->RenderEditLink();
				break;
				
			case SCMS_CUSTOM:
				return $this->RenderEditCustom();
				break;
				
			case SCMS_MODULE:
				return $this->RenderEditModule();
				break;
		}

		return '';
	}

	private function RenderEditContent()
	{
		GLOBAL $Parser, $Db;
		GLOBAL $LIST_SCMS_TYPE;

		// set right parent ID
		$this->cl = $this->di->value['parent_id'];

		$parent = $Db->Get('id='.$this->cl, null, 'sys_ss');

		// parent field if locked - show only
		$parentField = ($this->di->value['is_locked'] == 1) ? $parent['name'] : $this->MakeJumpField('search');

		$tpl = 'ss/edit/content_developer';
		if (!$this->developerMode)
		{
			$tpl = 'ss/edit/content';
		}

		$value = $this->di->value;

		$Parser->SetAddVariables(array(
			'section_tpl'       => $this->RenderTplListField('section_tpl', $value['section_tpl'], 'ss/section/'),
			'subsection_tpl'    => $this->RenderTplListField('subsection_tpl', $value['subsection_tpl'], 'ss/subsection/'),
			'page_tpl'			=> $this->RenderTplListField('page_tpl', $value['page_tpl'], 'ss/page/'),
			'global_tpl'		=> $this->RenderGlobalTplListField($value['global_tpl']),
			'parent'			=> $parentField,
			'parent_url'		=> $parent['url'],
			'page_type_name'	=> $LIST_SCMS_TYPE[SCMS_CONTENT],
		));

		return $Parser->MakeForm($this->di, $tpl, 'form');
	}

	private function RenderEditPageset()
	{
		GLOBAL $Parser,$Db;
		GLOBAL $LIST_SCMS_TYPE;

		// set right parent ID
		$this->cl = $this->di->value['parent_id'];

		$parent = $Db->Get('id='.$this->cl, null, 'sys_ss');

		// parent field if locked - show only
		$parentField = ($this->di->value['is_locked'] == 1) ? $parent['name'] : $this->MakeJumpField('search');

		$tpl = 'ss/edit/pageset_developer';
		if (!$this->developerMode)
		{
			$tpl = 'ss/edit/pageset';
		}

		$value = $this->di->value;

		$Parser->SetAddVariables(array(
			'section_tpl'       => $this->RenderTplListField('section_tpl', $value['section_tpl'], 'ss/section/'),
			'subsection_tpl'    => $this->RenderTplListField('subsection_tpl', $value['subsection_tpl'], 'ss/subsection/'),
			'page_tpl'			=> $this->RenderTplListField('page_tpl', $value['page_tpl'], 'ss/page/'),
			'global_tpl'		=> $this->RenderGlobalTplListField($value['global_tpl']),
			'parent'			=> $parentField,
			'parent_url'		=> $parent['url'],
			'page_type_name'	=> $LIST_SCMS_TYPE[SCMS_PAGESET],
		));

		return $Parser->MakeForm($this->di, $tpl, 'form');
	}

	private function RenderEditLink()
	{
		GLOBAL $Parser,$Db;
		GLOBAL $LIST_SCMS_TYPE;

		// set right parent ID
		$this->cl = $this->di->value['parent_id'];

		// parent field if locked - show only
		$parentField = ($this->di->value['is_locked'] == 1) ? $Db->Get('id='.$this->cl, 'name', 'sys_ss') : $this->MakeJumpField('search');
		$tpl = 'ss/edit/link_developer';
		if (!$this->developerMode)
		{
			$tpl = 'ss/edit/link';
		}

		$Parser->SetAddVariables(array(
			'parent'			=> $parentField,
			'page_type_name'	=> $LIST_SCMS_TYPE[SCMS_LINK],
		));

		return $Parser->MakeForm($this->di, $tpl, 'form');
	}

	private function RenderEditCustom()
	{
		GLOBAL $Parser,$Db;
		GLOBAL $LIST_SCMS_TYPE;

		// set right parent ID
		$this->cl = $this->di->value['parent_id'];

		$parent = $Db->Get('id='.$this->cl, null, 'sys_ss');
		// parent field if locked - show only
		$parentField = ($this->di->value['is_locked'] == 1) ? $parent['name'] : $this->MakeJumpField('search');

		$tpl = 'ss/edit/custom_developer';
		if (!$this->developerMode)
		{
			$tpl = 'ss/edit/custom';
		}

		$value = $this->di->value;

		$Parser->SetAddVariables(array(
			'section_tpl'       => $this->RenderTplListField('section_tpl', $value['section_tpl'], 'ss/section/'),
			'subsection_tpl'    => $this->RenderTplListField('subsection_tpl', $value['subsection_tpl'], 'ss/subsection/'),
			'page_tpl'			=> $this->RenderTplListField('page_tpl', $value['page_tpl'], 'ss/page/'),
			'global_tpl'		=> $this->RenderGlobalTplListField($value['global_tpl']),
			'parent'			=> $parentField,
			'parent_url'		=> $parent['url'],
			'page_type_name'	=> $LIST_SCMS_TYPE[SCMS_CUSTOM],
		));

		return $Parser->MakeForm($this->di, $tpl, 'form');
	}

	private function RenderEditModule()
	{
		GLOBAL $Parser,$Db;

		// set right parent ID
		$this->cl = $this->di->value['parent_id'];

		$parent = $Db->Get('id='.$this->cl, null, 'sys_ss');
		// parent field if locked - show only
		$parentField = ($this->di->value['is_locked'] == 1) ? $parent['name'] : $this->MakeJumpField('search');

		$tpl = 'ss/edit/module_developer';
		if (!$this->developerMode)
		{
			$tpl = 'ss/edit/module';
		}

		$value = $this->di->value;

		$Parser->SetAddVariables(array(
			'section_tpl'       => $this->RenderTplListField('section_tpl', $value['section_tpl'], 'ss/section/'),
			'subsection_tpl'    => $this->RenderTplListField('subsection_tpl', $value['subsection_tpl'], 'ss/subsection/'),
			'page_tpl'			=> $this->RenderTplListField('page_tpl', $value['page_tpl'], 'ss/page/'),
			'global_tpl'		=> $this->RenderGlobalTplListField($value['global_tpl']),
			'parent'			=> $parentField,
			'parent_url'		=> $parent['url'],
			'module_name'		=> $Db->Get('id='.$value['instance_id'], 'name', 'sys_modules')
		));

		return $Parser->MakeForm($this->di, $tpl, 'form');
	}

	private function GetFilesListRecursive($dir, $level=0, $reinit=false)
	{
	    GLOBAL $App;
	    STATIC $list = array();
	    if ($reinit) $list = array();

	    $path = $App->sysRoot.'tpl/'.$dir;
	    
	    $exclude = array('.', '..', '.svn', 'empty.htm');

		if (false !== ($handle = opendir($path)))
		{
		    while (false !== ($file = readdir($handle)))
		    {

		        if (in_array($file, $exclude)) continue;

		        list($name) = explode('.', $file);
				if (is_file($path.$file))
				{
					$list[$dir.$name] = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level).$name;
				}
				if (is_dir($path.$file))
				{
				    $innerDir = FileSystem::NormalizeDirPath($dir.$file);

				    $list[$innerDir] = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level).'['.$name.']';
				    $this->GetFilesListRecursive($innerDir, $level+1);
				}
			}
			closedir($handle);
		}

	    return $list;
	}

	private function RenderTplListField($fieldName, $curTpl=null, $dir='ss/section/')
	{
		GLOBAL $App,$Parser;

		$first = array(
			'empty'	=> LANG_CP_SCMS_NTEMPLATE
		);

		$tplsList = $this->GetFilesListRecursive($dir,0,true);
		$tplsList = Utils::MergeArrays($first, $tplsList);

		$list = array();
		foreach ($tplsList as $name=>$caption)
		{
			$selected = $curTpl==$name ? 'selected="selected"' : '';
			$choosable = substr($name, -1, 1) == '/' ? 'disabled="disabled"' : ' style="background-color:#E0FFA9"';
			$list[] = array(
				'value' => $name,
				'caption' => $caption,
				'selected' => $selected,
				'choosable' => $choosable,
			);
		}

		$Parser->SetCaptionVariables(array('name' => $fieldName, 'params' => 'class="hform"'));
		return $Parser->MakeList($list, 'ss/template_select', 'list');
	}

	private function RenderGlobalTplListField($curTpl=null)
	{
		GLOBAL $App;

		$rez = array();

		$stplPath = $App->sysRoot . 'tpl/';
		if (false !== ($handle = opendir($stplPath)))
		{
		    while (false !== ($file = readdir($handle)))
		    {
				if ($file != '.' && $file != '..' && substr($file, 0, 6) == 'global')
				{
					list($name) = explode('.', $file);
					$rez[$name] = $name;
				}
			}
			closedir($handle);
		}

		$App->Load(LiskType::TYPE_LIST, 'type');
		$list = new T_list(array(
		    'name' =>  'global_tpl',
			'object' => 'arr',
		));
		$list->values = $rez;
		$list->value = $curTpl;
		$list->AddFormParam('class', 'hform');
		
		return $list->RenderFormTplView();
	}

	private function RenderModuleListField()
	{
		GLOBAL $App;

		$modulesList = $this->GetAvailableModules();
		$available = array(
		    '' => '- Please select -',
		);

		foreach ($modulesList as $name=>$info)
		{
			if ($info['ss_integrated'])
			{
				$available[$name] = $info['name'];
			}
		}

		$App->Load(LiskType::TYPE_LIST, 'type');
		$list = new T_list(array(
		    'name' => 'module',
			'object'	=> 'arr',
		));
		$list->values = $available;
		$list->AddFormParam('class', 'hform');
		
		return $list->RenderFormTplView();
	}

	public function GetAvailableModules()
	{
		GLOBAL $App;
        $rez = array();
		$d = dir($App->sysRoot.'init/modules/');
		if ($d)
		{
    		while (false!==($entry=readdir($d->handle)))
    		{
    			if (is_dir($d->path.$entry)
    			    && $entry != '.' && $entry != '..'
    			    && file_exists($App->sysRoot.$App->initPath.'modules/'.$entry.'/'.$entry.'.mod.php'))
    			{
    				$App->Load($entry, 'mod');
    				$name = strtoupper($entry).'_MODULE_INFO';
    				if (isset($GLOBALS[$name]) && Utils::IsArray($GLOBALS[$name])) $rez[$entry] = $GLOBALS[$name];
    			}
    		}
    		$d->close();
		}
		return $rez;
	}

	function MakeJSLinkButtons()
	{
		GLOBAL $Page;

		$Page->AddLink("Expand all", "javascript: expandAll();", 'img/cms/tree/link_collapse.gif');
		$Page->AddLink("Collapse all", "javascript: collapseAll();", 'img/cms/tree/link_expand.gif');
	}


	function RenderJS()
	{
		GLOBAL $Parser,$Page;

		$rez = $Parser->MakeView(array(
			'js_tree'			=> $this->GetJSStructure(),
			'node_name'			=> $this->di->cfgName,
			'view_check'		=> (isset($_GET['checkboxes']) && $_GET['checkboxes'] == 'true') ? 'true' : 'false',
			'cur_nav_level'		=> $Page->back,
			'cross_tree_label'	=> isset($_SESSION['cross_tree_label']) ? $_SESSION['cross_tree_label'] : '',
			'query_string'		=> '?'.$_SERVER['QUERY_STRING'],
			'tree_name'			=> 'tree_name',
		), 'cms/scms/tree_js', 'tree');

		return $rez;
	}

	private function GetJSStructure()
	{
		GLOBAL $Db;
		$this->GenerateTreeJSStructure(1);
		$rootName = $Db->Get('id=1', 'name', $this->di->table);
		if (!strlen($rootName))
		{
			$rootName = 'Root';
		}
		$rootName = $this->EscapeJsStr($rootName);
		$str = "t.add(1, 0, \"&nbsp;$rootName\", \"javascript:DisplayPageInfo(1);\", \"\", true);\r\n";
		foreach ($this->treeJSStructure as $key=>$row)
		{
			$mode=0;
			if ($row['type'] == 'node')
			{
				foreach ($this->treeJSStructure as $row2)
				{
					if ($row2['parent_id'] == $row['id'])
					{
						if ($row2['type'] == 'node') $mode = 1;
						if ($row2['type'] == 'point') $mode = 2;
					}
				}
				$this->treeJSStructure[$key]['mode']=$mode;
			}
		}

		foreach ($this->treeJSStructure as $rec)
		{
			$rec['name'] = $this->EscapeJsStr($rec['name']);
			$images='';
			switch($rec['page_type'])
			{
				case SCMS_CONTENT:
					$images = 'img/cms/scms/type/2.gif';
					break;
					
				case SCMS_CUSTOM:
					$images = 'img/cms/scms/type/3.gif';
					break;
					
				case SCMS_LINK:
					$images = 'img/cms/scms/type/1.gif';
					break;
					
				case SCMS_PAGESET:
					$images = 'img/cms/scms/type/0.gif';
					break;
					
				case SCMS_MODULE:
					$images = 'img/cms/scms/type/4.gif';
					break;
			}
			$str .= "t.add({$rec['id']}, {$rec['parent_id']}, \"&nbsp;{$rec['name']}\", \"javascript:DisplayPageInfo('{$rec['id']}');\", \"$images\", null);\r\n";

		}
		return $str;
	}

	private function GenerateTreeJSStructure($id)
	{
		$subCategories = $this->di->SelectValues('parent_id='.$id, 'id,parent_id,parents,name,page_type');
		if (Utils::IsArray($subCategories))
		{
			foreach ($subCategories as $cat)
			{
				$nestingLevel = substr_count($cat['parents'], '>');
				$this->treeJSStructure[] = array(
					'id'		=> $cat['id'],
					'parent_id'	=> $cat['parent_id'],
					'name'		=> $cat['name'],
					'type'		=> 'node',
					'nesting'	=> $nestingLevel,
					'page_type' => $cat['page_type'],
				);
				$this->GenerateTreeJSStructure($cat['id']);
			}
		}
	}

	private function EscapeJsStr($str)
	{
		//for now
		return strtr($str, array('\\'=>'\\\\',"'"=>"\\'",'"'=>'\\"',"\r"=>'\\r',"\n"=>'\\n','</'=>'<\/'));
	}

	function RenderTreeInfo($id)
	{
		GLOBAL $App,$Parser;
		$this->di->Get('id='.$id);
		$item = $this->di->value;

		$App->Load('cp_scms', 'lang');

		switch($item['page_type'])
		{
			case SCMS_CONTENT:
				$inner = $this->RenderViewContent();
				break;

			case SCMS_CUSTOM:
				$inner = $this->RenderViewCustom();
				break;

			case SCMS_LINK:
				$inner = $this->RenderViewLink();
				break;

			case SCMS_MODULE:
				$inner = $this->RenderViewModule();
				break;

			case SCMS_PAGESET:
				$inner = $this->RenderViewPageset();
				break;
		}

		$info['info'] = $inner;

		return $Parser->MakeView($info, 'cms/scms/tree_js', 'info');
	}

	function RenderViewContent()
	{
		GLOBAL $Parser,$Db,$App;
		$parent = $Db->Get('id='.$this->cl, null, 'sys_ss');
		$Parser->SetCaptionVariables(array(
			'parent' => $parent['name'],
		));
		$this->di->value['url'] = $App->httpRoot.$this->di->value['url'];
		return $Parser->MakeView($this->di, 'cms/scms/info_content', 'view');
	}

	function RenderViewCustom()
	{
		GLOBAL $Parser,$Db,$App;
		$parent = $Db->Get('id='.$this->cl, null, 'sys_ss');
		$Parser->SetCaptionVariables(array(
			'parent' => $parent['name'],
		));
		$this->di->value['url'] = $App->httpRoot.$this->di->value['url'];
		return $Parser->MakeView($this->di, 'cms/scms/info_custom', 'view');
	}

	function RenderViewLink()
	{
		GLOBAL $Parser,$Db, $App;
		$parent = $Db->Get('id='.$this->cl, null, 'sys_ss');
		$Parser->SetCaptionVariables(array(
			'parent' => $parent['name'],
		));
		$this->di->value['url'] = $App->httpRoot.$this->di->value['url'];
		return $Parser->MakeView($this->di, 'cms/scms/info_link', 'view');
	}

	function RenderViewModule()
	{
		GLOBAL $Parser, $Db, $App;
		$parent = $Db->Get('id='.$this->cl, null, 'sys_ss');
		$type = $Db->Get('id='.$this->di->value['instance_id'], null, 'sys_modules');
		$Parser->SetCaptionVariables(array(
			'parent' => $parent['name'],
			'module_name' => $type['object_name'],
		));
		$this->di->value['url'] = $App->httpRoot.$this->di->value['url'];
		return $Parser->MakeView($this->di, 'cms/scms/info_module', 'view');
	}

	function RenderViewPageset()
	{
		GLOBAL $Parser, $Db, $App;
		$parent = $Db->Get('id='.$this->cl, null, 'sys_ss');
		$Parser->SetCaptionVariables(array(
			'parent' => $parent['name'],
		));
		$this->di->value['url'] = $App->httpRoot.$this->di->value['url'];
		return $Parser->MakeView($this->di, 'cms/scms/info_pageset', 'view');
	}
}

?>