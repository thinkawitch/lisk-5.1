<?php
require_once('init/init.php');

class CpUsersPage extends CPPage
{
    /**
     * users list
     *
     * @var CMSList
     */
	private $usersList;
	
	/**
	 * groups list
	 *
	 * @var CMSList
	 */
	private $groupsList;
	
	/**
	 * cp groups
	 *
	 * @var CPGroup
	 */
	private $cpGroups;

	function __construct()
	{
		parent::__construct();

		$this->cpGroups = new CPGroup();
		$this->AddBookmark(LANG_CP_USERS, '?action=users', 'img/ico/bookmarks/users.gif');
		$this->AddBookmark(LANG_CP_GROUPS, '?action=groups', 'img/ico/bookmarks/cp_group.gif');
		$this->AddBookmark('CP Login History', '?action=history', 'img/ico/bookmarks/cp_group.gif');

		$this->usersList  = new CMSList('user_cp');
		$this->usersList->Init();
		$this->usersList->AlphabeticNavigation = true;
		$this->usersList->AlphabeticField = 'login';
		
		$this->groupsList = new CMSList('usergroup_cp');
		$this->groupsList->Init();

		$this->AddPostHandler('action', 'SaveRights', 'save_rights');
		$this->SetGetAction('users', 'Users');
		$this->SetGetAction('groups', 'Groups');
		$this->SetGetAction('history', 'History');

		$this->AddGetPostHandler('action', 'users', 'action', 'delete_selected', 'DeleteSelectedCPUsers');

		$this->AddGetHandler('rights', 'ShowRights', 'set');

		$this->SetPostAction('submit', 'Update');
	}

	function Page()
	{
		Navigation::Jump('?action=users');
	}

	function SaveRights()
	{
		$this->cpGroups->SaveRights($_POST['group_id'], $_POST['checks']);
		Navigation::Jump(Navigation::GetBack());
	}

	function ShowRights()
	{
		$this->currentBookmark = 'CP Groups';
		$this->SetTitle('Control Panel Groups');

		$this->ParseBack();

		$this->pageContent .= $this->cpGroups->RightsForm($_GET['id']);
	}

	function Groups()
	{
		$this->currentBookmark = LANG_CP_GROUPS;
		$this->SetTitle('Control Panel User Groups');

		// Links
		$this->groupsList->buttonView = false;
		$this->groupsList->buttonEdit = false;
		$this->groupsList->AddButton('Permissions', '?action=groups&rights=set&id=[id]&back=[back]', 'Grant/Deny Permissions', '<img src="img/cms/list/i_permissions.gif" width="12" height="14" border="0" align="absmiddle" hspace="2">');

		$removeCond = '[id]=='.LISK_GROUP_ADMINISTRATORS.' || [id]=='.LISK_GROUP_DEVELOPERS;
		$this->groupsList->RemoveButton('Permissions', $removeCond);

		$this->groupsList->AddButton('Rename', 'edit.php?type=usergroup_cp&id=[id]&back=[back]','Rename CP Group', '<img src="img/cms/list/edit.gif" width="8" height="14" border="0" align="absmiddle">');

		$this->groupsList->RemoveButton('Delete', $removeCond);
		$this->groupsList->RemoveButton('Rename', $removeCond);
		$this->groupsList->buttonDeleteAll = false;
		$this->groupsList->buttonCheckbox = false;
		$this->groupsList->MakeLinkButtons($this);

		$this->Paging->SwitchOn('cp');
		$this->pageContent .= $this->groupsList->Render();
		$this->SetBack();
	}

	function Users()
	{
		GLOBAL $Auth;

		$this->currentBookmark = LANG_CP_USERS;
		$this->SetTitle('Control Panel Users');

		$this->usersList->buttonView = false;

		if ($Auth->user['level'] == LISK_GROUP_DEVELOPERS) $removeCond = '0';
		elseif ($Auth->user['level'] == LISK_GROUP_ADMINISTRATORS) $removeCond = '[level]=='.LISK_GROUP_DEVELOPERS;
		else $removeCond = '[level]=='.LISK_GROUP_DEVELOPERS.' || [level]=='.LISK_GROUP_ADMINISTRATORS;

		$this->usersList->RemoveButton('Delete', $removeCond);
		$this->usersList->RemoveButton('Edit', $removeCond);
		$this->usersList->RemoveButton('<input type="checkbox" name="ids[]" value="[id]" />', $removeCond);

		$this->listFilter = $this->GetGroupsFilter();
		$this->usersList->SetCond(@$_GET['cond']);

		$this->usersList->MakeLinkButtons();

		$this->Paging->SwitchOn('cp');
		$this->pageContent .= $this->usersList->Render();
		$this->SetBack();
	}

	function DeleteSelectedCPUsers()
	{
		$this->usersList->DeleteSelected();
	}

	private function GetGroupsFilter()
	{
		$filter = array(
			'' => 'All users',
		);

		$groups = Data::Create('usergroup_cp');
		$groups->Select('', 'id,name');

		if (Utils::IsArray($groups->values))
		{
			foreach ($groups->values as $value)
			{
				$filter['level='.$value['id']] = $value['name'];
			}
		}

		return $filter;
	}

	function History()
	{
		GLOBAL $Paging;

		$this->currentBookmark = 'CP Login History';

		$Paging->SwitchOn('cp');
		$list = new CMSList('cp_login_history');
		$list->Init();
		$list->AlphabeticNavigation = true;
		$list->AlphabeticField = 'login';
		$list->buttonAdd = false;
		$list->buttonCheckbox = false;
		$list->buttonDelete = false;
		$list->buttonDeleteAll = false;
		$list->buttonEdit = $list->buttonView = false;
		
		$list->MakeLinkButtons();

		$this->pageContent .= $list->Render();
	}
}

$Page = new CpUsersPage();
$Page->Render();
?>