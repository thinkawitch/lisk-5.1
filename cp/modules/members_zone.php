<?php
chdir('../');
require_once('init/init.php');

class CpMemberZonePage extends CPModulePage
{
	/**
	 * @var MembersZone
	 */
	private $MZ;

	function __construct()
	{
		parent::__construct(true);
		$this->App->Load('members_zone', 'mod');

		$this->MZ = new MembersZone($this->iid);

		$this->AddBookmark('Members', '?action=members', 'img/ico/bookmarks/users.gif');
		$this->AddBookmark('Settings', '?action=settings', 'img/modules/members_zone/settings.gif');

		$this->SetPostAction('delete_selected', 'DeleteSelected');

		$this->SetGetAction('members_list', 'ListMembers');
		$this->SetGetPostAction('members_add', 'submit', 'MembersAddSubmit');
		$this->SetGetAction('members_add', 'MembersAddForm');
		$this->SetGetPostAction('members_edit', 'submit', 'MembersEditSubmit');
		$this->SetGetAction('members_edit', 'MembersEditForm');
		$this->SetGetAction('members_delete', 'MembersDelete');

		$this->SetGetPostAction('settings', 'submit', 'SaveSettings');
		$this->SetGetAction('settings', 'Settings');

		//GET SNIPPET
		$this->SetGetAction('get_snippet', 'GetSnippet');

		$this->titlePicture = 'cms/l_settings.jpg';
	}

	function Page()
	{
		$this->ListMembers();
	}

	function ListMembers()
	{
		$this->currentBookmark = 'Members';
		$this->SetTitle('Members', $this->titlePicture);

		Navigation::SetBack($this->back);
		$this->ParseBack();

		$DI = new Data($this->MZ->confDIMemberName);
		$Members = new CMSList($DI);
		$Members->Init();
		$Members->buttonView = false;
		$Members->buttonAdd = false;
		$Members->buttonEdit = false;
		$Members->buttonDelete = false;
		$Members->buttonAdd = false;

		$this->AddLink('Add '.$DI->label, '?action=members_add&back='.$this->setBack, 'img/ico/links/add.gif', 'Add '.$DI->label);
//
		$Members->SetCond(@$_GET['cond']);

		$Members->AddButton('Edit', '?action=members_edit&id=[id]&back=[back]', '', '<img src="img/cms/list/edit.gif" width="8" height="14" border="0" align="absmiddle"> ' );

		$Members->AddButton('Delete', '?action=members_delete&id=[id]&back=[back]', '', '<img src="img/cms/list/delete.gif" width="8" height="14" border="0" align="absmiddle">' );

		$Members->MakeLinkButtons($this);


		$this->Paging->SwitchOn('cp');
		$this->pageContent .= $Members->Render();
	}


	function MembersAddForm()
	{
		$this->currentBookmark = 'Members';
		$this->SetTitle('Add Member', $this->titlePicture);
		$this->ParseBack();

		$CmsAdd = new CMSAdd(new Data($this->MZ->confDIMemberName));
		$this->pageContent .= $CmsAdd->Render();
	}

	function MembersAddSubmit()
	{
		//$CmsAdd = new CMSAdd(new Data($this->MZ->confDIMemberName));
		//$CmsAdd->Insert();
		$this->MZ->RegisterMember($_POST);
		if (@$_POST['post_action'] == 2) Navigation::Jump(Navigation::Referer());
		else Navigation::JumpBack($this->back);
	}

	function MembersEditForm()
	{
		$this->currentBookmark='Members';
		$this->SetTitle('Edit Member', $this->titlePicture);
		$this->ParseBack();

		$CmsEdit = new CMSEdit(new Data($this->MZ->confDIMemberName));
		$CmsEdit->cond = "id='{$_GET['id']}'";
		$this->pageContent .= $CmsEdit->Render();
	}

	function MembersEditSubmit()
	{
		$CmsEdit = new CMSEdit(new Data($this->MZ->confDIMemberName));
		$CmsEdit->cond = "id='{$_GET['id']}'";
		$CmsEdit->Update();
		Navigation::JumpBack($this->back);
	}
	
	function MembersDelete()
	{
		$DI = new Data($this->MZ->confDIMemberName);
		$DI->Delete("id='{$_GET['id']}'");
		Navigation::JumpBack($this->back);
	}

	function DeleteSelected()
	{
		$CMSList = new CMSList(new Data($this->MZ->confDIMemberName));
		$CMSList->Init();
		$CMSList->DeleteSelected();
	}

	function Settings()
	{
		$this->SetBack();
		$this->ParseBack();

		$this->SetTitle('Members Zone Settings', $this->titlePicture);
		$this->currentBookmark = 'Settings';

		$this->settingsFields = array ();
		$this->settingsFieldsValues = array();
		
		$this->customizableDI = array($this->MZ->confDIMemberName);

		$this->pageContent .= $this->RenderSettingsPage($this->MZ);
	}

	function SaveSettings()
	{
		$this->MZ->SaveSettings();

		Navigation::JumpBack($this->setBack);
	}

	function GetSnippet()
	{
		$code = $this->MZ->GetSnippetCode($_GET['name']);
		$this->ShowSnippetCode($code);
	}
}

$Page = new CpMemberZonePage();
$Page->Render();
?>