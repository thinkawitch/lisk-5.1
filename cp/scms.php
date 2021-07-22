<?php
require_once('init/init.php');

class CpSiteStructurePage extends CPPage
{
    /**
     * @var CMSContentTree
     */
	private $scms;
	
	private $selfUri = 'scms.php?z=x';

	function __construct()
	{
		parent::__construct();
		
		$this->App->Load('cp_scms', 'lang');

		$this->AddBookmark(LANG_CP_SCMS_SSTRUCT, $this->selfUri.'&action=list', 'img/cms/scms/mode_normal.gif');
		$this->AddBookmark(LANG_CP_SCMS_TREEVIEW, $this->selfUri.'&action=tree', 'img/cms/scms/tab_tree_view.gif');

		// Create SCMS Content Tree instance
		$this->scms = new CMSContentTree();

		// Delete
		$this->SetGetAction('delete', 'Delete');
		$this->SetPostAction('delete_selected', 'DeleteSelected');

		// Properties
		$this->SetGetAction('edit', 'Edit');
		$this->SetGetPostAction('edit', 'submit', 'EditPost');

		// Add page
		$this->SetGetAction('add', 'Add');
		$this->SetGetPostAction('add', 'submit', 'AddPost');

		// manage content
		$this->SetGetAction('manage_content', 'ManageContent');
		$this->SetGetPostAction('manage_content', 'submit', 'ManageContentPost');

		//tree mode methods
		$this->SetGetAction('tree', 'Tree');
		$this->SetGetAction('tree_info', 'TreeInfo');

		$this->SetTitle(LANG_CP_SCMS_SSTRUCT, 'cms/scms/uho.gif');
	}

	function Page()
	{
		$this->currentBookmark = LANG_CP_SCMS_SSTRUCT;

		// Button links (add, order)
		$this->scms->MakeLinkButtons();

		$this->SetBack();

		//$this->Scms->back = $this->setBack;
		$this->scms->AdditionalNavigation();

		$this->pageContent .= $this->scms->Render($this->setBack);
	}

	function Add()
	{
		GLOBAL $App;
		$this->currentBookmark = LANG_CP_SCMS_SSTRUCT;

		$this->SetTitle(LANG_CP_SCMS_ADD.$this->scms->di->label, 'cms/add/uho.gif');
        
		$parentId = $_GET['HIDDEN_parent_id'];
        
		switch ($_GET['page_type'])
		{
			case SCMS_CONTENT:
				$this->pageContent .= $this->scms->RenderAddContent($parentId);
				break;
				
			case SCMS_PAGESET:
				$this->pageContent .= $this->scms->RenderAddPageset($parentId);
				break;
				
			case SCMS_LINK:
				$this->pageContent .= $this->scms->RenderAddLink($parentId);
				break;
				
			case SCMS_CUSTOM:
				$this->pageContent .= $this->scms->RenderAddCustom($parentId);
				break;
				
			case SCMS_MODULE:
				$this->pageContent .= $this->scms->RenderAddModule($parentId);
				break;
				
			default:
				$App->RaiseError('Undefined page_type in cp/scms.php::Add()!');
		}

		$this->ParseBack();
	}

	function AddPost()
	{
		$parentId = $_POST['cat_dropdown'];
		$_POST['parent_id'] = $parentId;
		
		switch ($_GET['page_type'])
		{
			case SCMS_CUSTOM:
				$this->scms->AddCustomSubmit($_POST);
				break;

			case SCMS_MODULE:
        		// check file permissions
        		if (!LiskModule::IsInstallPossible())
        		{
        		    $this->SetError('Module can\'t be installed. Please check file permissions!');
        		    Navigation::Jump($this->selfUri.'&cl='.$parentId);
        		}
				$this->scms->AddModuleSubmit($_POST);
				break;

			default:
				$this->scms->di->Insert($_POST);
		}

		Navigation::Jump($this->selfUri.'&cl='.$parentId);
	}

	function Edit()
	{
		$this->currentBookmark = LANG_CP_SCMS_SSTRUCT;

		$this->SetTitle(LANG_CP_SCMS_EDIT.$this->scms->di->label, 'cms/edit/uho.gif');

		$this->pageContent .= $this->scms->RenderEdit($_GET['id']);

		$this->ParseBack();
	}

	function EditPost()
	{
		GLOBAL $Db;
		
		$parentId = isset($_POST['cat_dropdown']) ? $_POST['cat_dropdown'] : null;
		$id = intval($_POST['id']);
		
		$_POST['parent_id'] = $parentId;
		$this->scms->di->forceUrlUpdate = false;

		$previous = $this->scms->di->GetValue('id='.$id);
		$toAddToMenu = isset($_POST['add_to_cp_menu']) && $_POST['add_to_cp_menu'];
		
		if ($previous['page_type'] == SCMS_CUSTOM)
		{
			if ($toAddToMenu)
			{
				//remove previous menu string
				if (strlen($previous['cp_handler']))
				{
					$Db->Delete("url='{$previous['cp_handler']}'", 'sys_cp_menu');
				}

				//add new menu string
				$Db->Insert(array(
					'parent_id'		=> 1,
					'parents'		=> '<1>',
					'is_category'	=> 0,
					'name'			=> $_POST['cp_menu_title'],
					'url'			=> $_POST['cp_handler'],
					'hint'			=> $_POST['cp_menu_title']
				), 'sys_cp_menu');
			}
		}
		elseif ($previous['page_type'] == SCMS_MODULE)
		{
			if ($toAddToMenu)
			{
				$iid = $previous['instance_id'];
				$module = $Db->Get('id='.$iid, 'name', 'sys_modules');
				if ($module)
				{
					//remove previous menu string
					$Db->Delete("url='{$module}.php?iid={$iid}'", 'sys_cp_menu');

					//add new menu string
					$Db->Insert(array(
						'parent_id'		=> 1,
						'parents'		=> '<1>',
						'is_category'	=> 0,
						'name'			=> $_POST['cp_menu_title'],
						'url'			=> "{$module}.php?iid={$iid}",
						'hint'			=> $_POST['cp_menu_title']
					), 'sys_cp_menu');
				}
			}
		}

		$this->scms->di->Update('id='.$id, $_POST);
		Navigation::JumpBack($this->back);
	}

	function ManageContent()
	{
		GLOBAL $Parser;
		$this->currentBookmark = LANG_CP_SCMS_SSTRUCT;

        $id = intval($_GET['id']);
		$this->scms->di->Get('id='.$id);

		$this->pageContent .= $Parser->MakeForm($this->scms->di, 'cms/content_manage', 'content');

		$this->ParseBack();
		$this->SetTitle(LANG_CP_SCMS_EDIT.$this->scms->di->label, 'cms/edit/uho.gif');
	}

	function ManageContentPost()
	{
	    $id = intval($_GET['id']);
		$this->scms->di->Update('id='.$id, $_POST);
		Navigation::JumpBack($this->back);
	}

	function Delete()
	{
		$id = intval($_GET['id']);

		// check if we can delete
		$deleteFlag = true;
		if ($this->scms->developerMode)
		{
			$subRecords = $this->scms->di->SelectValues("parents LIKE '%<$id>%' AND is_locked=1", 'id,name');
		}
		else
		{
			$subRecords = $this->scms->di->SelectValues("parents LIKE '%<$id>%' AND (is_locked=1 OR page_type=".SCMS_MODULE." OR page_type=".SCMS_CUSTOM.")", 'id,name');
		}
		if (Utils::IsArray($subRecords)) $deleteFlag = false;

		if ($deleteFlag)
		{
			$this->scms->di->Delete('id='.$id);
		}
		else
		{
			$this->SetError(LANG_CP_SCMS_ERRCDELPS);
		}

		Navigation::JumpBack($this->back);
	}

	function DeleteSelected()
	{
	    $ids = isset($_POST['ids']) ? $_POST['ids'] : null;
	    if (Utils::IsArray($ids))
	    {
    		foreach ($ids as $id)
    		{
    			$this->scms->di->Delete('id='.Database::Escape($id));
    		}
	    }
		Navigation::Jump(Navigation::Referer());
	}

	function Tree()
	{
		$this->currentBookmark = LANG_CP_SCMS_TREEVIEW;

		$this->SetBack();
		$this->ParseBack();

		$this->scms->MakeJsLinkButtons();

		$this->pageContent .= $this->scms->RenderJS();
	}

	function TreeInfo()
	{
		GLOBAL $App;
		$App->debug = false;
		$this->bookmarks = '';
		$this->customLine = '';
		$this->title = '';
		$this->globalTemplate = '0';
		$this->pageContent = $this->scms->RenderTreeInfo($_GET['id']);
	}
}

$Page = new CpSiteStructurePage();
$Page->Render();
?>